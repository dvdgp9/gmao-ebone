<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Accions en bloc sobre usuaris: decideix a qui s'aplica cada acció i resumeix el resultat. Sense BD.
 */
class UsuariMassiu
{
    public const ACCIONS = ['activar', 'desactivar', 'assignar', 'treure', 'enllacos'];
    public const MAX_USUARIS = 5000;

    private const MOTIUS = [
        'ja_actiu' => 'ja estaven actius',
        'ja_inactiu' => 'ja estaven desactivats',
        'propi' => 'el teu propi compte',
        'superadmin' => 'superadmins, que ja tenen accés a tot',
        'no_assignat' => 'no hi eren',
        'inactiu' => 'comptes desactivats',
    ];

    /**
     * «3, 1,abc,3» → [3, 1]. Els ids arriben en una sola cadena perquè max_input_vars no talli la selecció.
     *
     * @return list<int>
     */
    public static function parseIds(string $text): array
    {
        $ids = [];
        foreach (explode(',', $text) as $part) {
            $part = trim($part);
            if (ctype_digit($part) && (int)$part > 0 && !in_array((int)$part, $ids, true)) {
                $ids[] = (int)$part;
            }
            if (count($ids) >= self::MAX_USUARIS) {
                break;
            }
        }

        return $ids;
    }

    /**
     * @param list<array{id: int, actiu: mixed, is_superadmin: mixed, instalacio_ids: list<int>}> $usuaris
     * @return array{aplicar: list<int>, omesos: array<int, string>}
     */
    public static function planificar(string $accio, array $usuaris, int $editorId, ?int $instalacioId = null): array
    {
        if (!in_array($accio, self::ACCIONS, true)) {
            throw new InvalidArgumentException('Acció desconeguda: ' . $accio);
        }
        if (in_array($accio, ['assignar', 'treure'], true) && !$instalacioId) {
            throw new InvalidArgumentException('Cal indicar la instal·lació.');
        }

        $aplicar = [];
        $omesos = [];
        foreach ($usuaris as $usuari) {
            $id = (int)$usuari['id'];
            $actiu = !empty($usuari['actiu']);
            $instalacions = array_map('intval', (array)($usuari['instalacio_ids'] ?? []));

            $motiu = match ($accio) {
                'activar' => $actiu ? 'ja_actiu' : null,
                'desactivar' => $id === $editorId ? 'propi' : (!$actiu ? 'ja_inactiu' : null),
                'assignar' => !empty($usuari['is_superadmin']) ? 'superadmin' : null,
                'treure' => !in_array((int)$instalacioId, $instalacions, true) ? 'no_assignat' : null,
                'enllacos' => !$actiu ? 'inactiu' : null,
            };

            if ($motiu === null) {
                $aplicar[] = $id;
            } else {
                $omesos[$id] = $motiu;
            }
        }

        return ['aplicar' => $aplicar, 'omesos' => $omesos];
    }

    public static function missatge(string $accio, array $pla, string $instalacioNom = ''): string
    {
        $n = count($pla['aplicar']);
        $singular = $n === 1;

        if ($n === 0) {
            $text = 'Cap usuari modificat.';
        } else {
            $text = match ($accio) {
                'activar' => $n . ($singular ? ' usuari activat.' : ' usuaris activats.'),
                'desactivar' => $n . ($singular ? ' usuari desactivat.' : ' usuaris desactivats.'),
                'assignar' => $n . ($singular ? ' usuari assignat a ' : ' usuaris assignats a ') . $instalacioNom . '.',
                'treure' => $n . ($singular ? ' usuari tret de ' : ' usuaris trets de ') . $instalacioNom . '.',
                'enllacos' => ($singular ? 'Enllaç generat per a 1 usuari.' : 'Enllaços generats per a ' . $n . ' usuaris.'),
                default => $n . ' usuaris modificats.',
            };
        }

        if ($pla['omesos'] !== []) {
            $perMotiu = array_count_values($pla['omesos']);
            $parts = [];
            foreach ($perMotiu as $motiu => $quants) {
                $parts[] = (self::MOTIUS[$motiu] ?? $motiu) . ' (' . $quants . ')';
            }
            $text .= ' No s\'han tocat: ' . implode(', ', $parts) . '.';
        }

        return $text;
    }
}
