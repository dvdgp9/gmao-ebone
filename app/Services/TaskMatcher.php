<?php

namespace App\Services;

use App\Models\Database;

class TaskMatcher
{
    public const STATUS_MATCH = 'match';
    public const STATUS_NEW = 'new';
    public const STATUS_REVIEW = 'review';
    public const STATUS_ERROR = 'error';

    public static function normalize(string $value): string
    {
        $value = trim(mb_strtolower($value));
        if ($value === '') {
            return '';
        }

        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($converted !== false) {
            $value = $converted;
        }

        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    public static function match(array $input): array
    {
        $name = trim((string)($input['nom'] ?? ''));
        $normalizedName = self::normalize($name);

        if ($normalizedName === '') {
            return [
                'status' => self::STATUS_ERROR,
                'score' => 0,
                'matched_task' => null,
                'message' => 'Falta el nom de la tasca.',
            ];
        }

        $candidates = self::loadCandidates();
        if (empty($candidates)) {
            return [
                'status' => self::STATUS_NEW,
                'score' => 0,
                'matched_task' => null,
                'message' => 'Es crearà una tasca nova al catàleg global.',
            ];
        }

        $best = null;
        foreach ($candidates as $candidate) {
            $score = self::score($normalizedName, $input, $candidate);
            if ($best === null || $score > $best['score']) {
                $best = [
                    'score' => $score,
                    'candidate' => $candidate,
                ];
            }
        }

        if ($best === null) {
            return [
                'status' => self::STATUS_NEW,
                'score' => 0,
                'matched_task' => null,
                'message' => 'Es crearà una tasca nova al catàleg global.',
            ];
        }

        $candidate = $best['candidate'];
        $score = (int)$best['score'];
        $normativaInput = (int)($input['normativa_id'] ?? 0);
        $normativaCandidate = (int)($candidate['normativa_id'] ?? 0);
        $differentKnownNormativa = $normativaInput > 0 && $normativaCandidate > 0 && $normativaInput !== $normativaCandidate;

        if ($score >= 90 && !$differentKnownNormativa) {
            return [
                'status' => self::STATUS_MATCH,
                'score' => $score,
                'matched_task' => $candidate,
                'message' => 'Es reutilitzarà una tasca existent del catàleg.',
            ];
        }

        if ($score >= 70 || $differentKnownNormativa) {
            return [
                'status' => self::STATUS_REVIEW,
                'score' => $score,
                'matched_task' => $candidate,
                'message' => $differentKnownNormativa
                    ? 'Nom semblant, però normativa diferent. Requereix revisió manual.'
                    : 'Possible coincidència. Requereix revisió manual abans d’importar.',
            ];
        }

        return [
            'status' => self::STATUS_NEW,
            'score' => $score,
            'matched_task' => null,
            'message' => 'Es crearà una tasca nova al catàleg global.',
        ];
    }

    public static function rememberAlias(int $tascaCatalegId, string $alias): void
    {
        $normalized = self::normalize($alias);
        if ($tascaCatalegId <= 0 || $normalized === '') {
            return;
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare('
                INSERT INTO tasques_cataleg_alias (tasca_cataleg_id, alias, alias_normalitzat)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE tasca_cataleg_id = VALUES(tasca_cataleg_id), alias = VALUES(alias)
            ');
            $stmt->execute([$tascaCatalegId, $alias, $normalized]);
        } catch (\Throwable $e) {
            // La migració pot no estar aplicada encara; la importació no debe fallar por no poder guardar el alias.
        }
    }

    private static function loadCandidates(): array
    {
        $db = Database::getInstance();
        $rows = $db->query('
            SELECT tc.id, tc.codi, tc.nom, tc.sistema_id, tc.tipus_equip_id,
                   tc.periodicitat_normativa_id, tc.normativa_id, tc.empresa_responsable
            FROM tasques_cataleg tc
            WHERE tc.activa = 1
        ')->fetchAll();

        $candidates = [];
        foreach ($rows as $row) {
            $row['match_text'] = $row['nom'];
            $row['match_text_normalized'] = self::normalize((string)$row['nom']);
            $row['matched_by_alias'] = false;
            $candidates[] = $row;
        }

        try {
            $aliases = $db->query('
                SELECT a.alias, a.alias_normalitzat, tc.id, tc.codi, tc.nom, tc.sistema_id,
                       tc.tipus_equip_id, tc.periodicitat_normativa_id, tc.normativa_id,
                       tc.empresa_responsable
                FROM tasques_cataleg_alias a
                JOIN tasques_cataleg tc ON tc.id = a.tasca_cataleg_id
                WHERE tc.activa = 1
            ')->fetchAll();

            foreach ($aliases as $row) {
                $row['match_text'] = $row['alias'];
                $row['match_text_normalized'] = $row['alias_normalitzat'] ?: self::normalize((string)$row['alias']);
                $row['matched_by_alias'] = true;
                $candidates[] = $row;
            }
        } catch (\Throwable $e) {
        }

        return $candidates;
    }

    private static function score(string $normalizedName, array $input, array $candidate): int
    {
        $candidateName = (string)($candidate['match_text_normalized'] ?? '');
        $score = self::nameScore($normalizedName, $candidateName);

        if ((int)($input['normativa_id'] ?? 0) > 0 && (int)($input['normativa_id'] ?? 0) === (int)($candidate['normativa_id'] ?? 0)) {
            $score += 20;
        }

        if ((int)($input['periodicitat_id'] ?? 0) > 0 && (int)($input['periodicitat_id'] ?? 0) === (int)($candidate['periodicitat_normativa_id'] ?? 0)) {
            $score += 15;
        }

        if ((int)($input['sistema_id'] ?? 0) > 0 && (int)($input['sistema_id'] ?? 0) === (int)($candidate['sistema_id'] ?? 0)) {
            $score += 10;
        }

        if ((int)($input['tipus_equip_id'] ?? 0) > 0 && (int)($input['tipus_equip_id'] ?? 0) === (int)($candidate['tipus_equip_id'] ?? 0)) {
            $score += 5;
        }

        return min(100, $score);
    }

    private static function nameScore(string $a, string $b): int
    {
        if ($a === '' || $b === '') {
            return 0;
        }

        if ($a === $b) {
            return 55;
        }

        similar_text($a, $b, $percent);
        $score = (int)round($percent * 0.55);

        $tokensA = array_values(array_filter(explode(' ', $a), static fn(string $token): bool => mb_strlen($token) > 2));
        $tokensB = array_values(array_filter(explode(' ', $b), static fn(string $token): bool => mb_strlen($token) > 2));
        if (!empty($tokensA) && !empty($tokensB)) {
            $intersection = count(array_intersect($tokensA, $tokensB));
            $union = count(array_unique(array_merge($tokensA, $tokensB)));
            $tokenScore = $union > 0 ? (int)round(($intersection / $union) * 50) : 0;
            $score = max($score, $tokenScore);
        }

        return min(55, $score);
    }
}
