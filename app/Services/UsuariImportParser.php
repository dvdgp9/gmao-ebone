<?php

namespace App\Services;

/**
 * Converteix qualsevol llista de persones (text lliure, columnes enganxades d'Excel,
 * format d'Outlook, CSV o les files d'un full de càlcul) en files crues:
 *   ['nom', 'cognoms', 'email', 'rol', 'torns', 'instalacio', 'extra' => [...]]
 * No accedeix a la base de dades: resoldre rols, torns i instal·lacions és feina
 * d'UsuariImportResolver.
 */
class UsuariImportParser
{
    public const MAX_FILES = 500;

    private const EMAIL_REGEX = '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i';

    /** Capçaleres que s'identifiquen per coincidència exacta (després de normalitzar). */
    private const CAPCALERES_EXACTES = [
        'nom_complet' => [
            'nom complet', 'nombre completo', 'treballador', 'treballadora', 'trabajador', 'trabajadora',
            'empleat', 'empleada', 'empleado', 'persona', 'usuari', 'usuario', 'full name', 'contacte', 'contacto',
        ],
    ];

    /** Paraules clau per identificar capçaleres, en ordre de prioritat. */
    private const CAPCALERES_PARAULES = [
        'email' => ['email', 'mail', 'correu', 'correo'],
        'cognoms' => ['cognom', 'cognoms', 'apellido', 'apellidos', 'surname'],
        'nom' => ['nom', 'nombre', 'name'],
        'rol' => ['rol', 'puesto', 'carrec', 'cargo', 'categoria', 'lloc', 'perfil', 'funcio', 'funcion'],
        'torns' => ['torn', 'torns', 'turno', 'turnos', 'horari', 'horario'],
        'instalacio' => ['instalacio', 'instalacions', 'instalacion', 'instalaciones', 'centre', 'centro', 'equipament', 'equipamiento'],
    ];

    private const ACCENTS = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ñ' => 'n', 'ç' => 'c', 'l·l' => 'l', 'ŀl' => 'l', '·' => '', 'ŀ' => 'l',
    ];

    /**
     * @return array{files: list<array>, avisos: list<string>}
     */
    public static function parseText(string $text): array
    {
        $text = self::toUtf8($text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $linies = [];
        foreach (explode("\n", $text) as $linia) {
            if (trim($linia) === '') {
                continue;
            }
            foreach (self::expandMultipleEmails($linia) as $part) {
                $linies[] = $part;
            }
        }

        $delimitador = self::detectDelimiter($linies);
        if ($delimitador === null) {
            return self::parseRows(array_map(static fn(string $l) => [$l], $linies), true);
        }

        $taula = array_map(static fn(string $l) => explode($delimitador, $l), $linies);

        return self::parseTable($taula);
    }

    /**
     * Files d'un full de càlcul o d'un text ja separat en columnes.
     *
     * @return array{files: list<array>, avisos: list<string>}
     */
    public static function parseTable(array $table): array
    {
        $files = [];
        foreach ($table as $fila) {
            $cells = array_map(static fn($c) => trim(self::toUtf8((string)($c ?? ''))), array_values((array)$fila));
            if (implode('', $cells) !== '') {
                $files[] = $cells;
            }
        }

        if ($files === []) {
            return ['files' => [], 'avisos' => []];
        }

        $mapa = self::detectHeader($files[0]);
        if ($mapa === null) {
            return self::parseRows($files, false);
        }

        $capcalera = array_shift($files);
        $avisos = [];
        $ignorades = [];
        foreach ($capcalera as $i => $nom) {
            if ($nom !== '' && !isset($mapa[$i])) {
                $ignorades[] = $nom;
            }
        }
        if ($ignorades !== []) {
            $avisos[] = 'Columnes ignorades: ' . implode(', ', $ignorades);
        }

        $teCognoms = in_array('cognoms', $mapa, true);
        $resultat = [];
        foreach ($files as $cells) {
            $valors = ['nom' => [], 'cognoms' => [], 'nom_complet' => [], 'email' => [], 'rol' => [], 'torns' => [], 'instalacio' => []];
            foreach ($mapa as $i => $camp) {
                if (($cells[$i] ?? '') !== '') {
                    $valors[$camp][] = $cells[$i];
                }
            }

            $email = '';
            if (preg_match(self::EMAIL_REGEX, implode(' ', $valors['email']), $m)) {
                $email = $m[0];
            } elseif ($valors['email'] !== []) {
                // Mal escrit («pere.rius@ebone»): es conserva perquè la validació ho assenyali.
                $email = implode(' ', $valors['email']);
            } else {
                foreach ($cells as $i => $valor) {
                    if (!isset($mapa[$i]) && preg_match(self::EMAIL_REGEX, $valor, $m)) {
                        $email = $m[0];
                        break;
                    }
                }
            }

            $nom = self::cleanName(implode(' ', $valors['nom']));
            $cognoms = self::cleanName(implode(' ', $valors['cognoms']));
            $nomComplet = self::cleanName(implode(' ', $valors['nom_complet']));
            if ($nom === '' && $nomComplet !== '') {
                if ($teCognoms) {
                    $nom = $nomComplet;
                } else {
                    [$nom, $cognoms] = self::splitFullName($nomComplet);
                }
            } elseif (!$teCognoms && $nom !== '' && str_contains($nom, ' ')) {
                [$nom, $cognoms] = self::splitFullName($nom);
            }

            $resultat[] = self::row([
                'nom' => $nom,
                'cognoms' => $cognoms,
                'email' => $email,
                'rol' => implode(', ', $valors['rol']),
                'torns' => implode(', ', $valors['torns']),
                'instalacio' => implode(', ', $valors['instalacio']),
            ]);

            if (count($resultat) >= self::MAX_FILES) {
                break;
            }
        }

        return self::limitWarning(['files' => $resultat, 'avisos' => $avisos], count($files));
    }

    /**
     * «Juan García López» → ['Juan', 'García López']; «Duran Pérez, Raül» → ['Raül', 'Duran Pérez'].
     * Amb quatre paraules o més s'assumeix nom compost: «José Luis García López».
     *
     * @return array{0: string, 1: string}
     */
    public static function splitFullName(string $name): array
    {
        $name = self::cleanName($name);
        if ($name === '') {
            return ['', ''];
        }

        if (substr_count($name, ',') === 1) {
            [$cognoms, $nom] = array_map([self::class, 'cleanName'], explode(',', $name));
            if ($nom !== '' && $cognoms !== '') {
                return [$nom, $cognoms];
            }
            $name = trim($nom . ' ' . $cognoms);
        }

        $paraules = preg_split('/\s+/u', $name) ?: [];
        if (count($paraules) === 1) {
            return [$paraules[0], ''];
        }

        $paraulesNom = count($paraules) >= 4 ? 2 : 1;

        return [
            implode(' ', array_slice($paraules, 0, $paraulesNom)),
            implode(' ', array_slice($paraules, $paraulesNom)),
        ];
    }

    /**
     * Minúscules, sense accents ni signes. No depèn d'iconv (vegeu Lessons al scratchpad).
     */
    public static function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = strtr($value, self::ACCENTS);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    /**
     * Files sense capçalera. En text lliure cada línia és una sola cel·la.
     */
    private static function parseRows(array $files, bool $textLliure): array
    {
        $resultat = [];
        $senseEmail = [];

        foreach ($files as $cells) {
            $cells = array_values(array_filter(array_map('trim', $cells), static fn($c) => $c !== ''));
            $posicioEmail = null;
            $email = '';
            foreach ($cells as $i => $cell) {
                if (preg_match(self::EMAIL_REGEX, $cell, $m, PREG_OFFSET_CAPTURE)) {
                    $posicioEmail = $i;
                    $email = $m[0][0];
                    $abans = substr($cell, 0, $m[0][1]);
                    $despres = substr($cell, $m[0][1] + strlen($email));
                    break;
                }
            }

            if ($posicioEmail === null) {
                if ($textLliure) {
                    $senseEmail[] = implode(' ', $cells);
                    continue;
                }
                // Taula sense capçalera i sense email: es manté perquè es pugui completar.
                $abans = '';
                $despres = '';
                $posicioEmail = count($cells);
            }

            $cellsNom = array_slice($cells, 0, $posicioEmail);
            $extra = array_slice($cells, $posicioEmail + 1);
            $segmentsAbans = self::segments($abans);
            $extra = array_merge(self::segments($despres), $extra);

            if ($cellsNom === []) {
                $nomComplet = array_shift($segmentsAbans) ?? '';
                $extra = array_merge($segmentsAbans, $extra);
                [$nom, $cognoms] = self::splitFullName($nomComplet);
            } elseif (count($cellsNom) === 1) {
                [$nom, $cognoms] = self::splitFullName($cellsNom[0]);
                $extra = array_merge($segmentsAbans, $extra);
            } else {
                $nom = self::cleanName($cellsNom[0]);
                $cognoms = self::cleanName($cellsNom[1]);
                $extra = array_merge(array_slice($cellsNom, 2), $segmentsAbans, $extra);
            }

            if ($nom === '' && $email === '') {
                continue;
            }

            $resultat[] = self::row([
                'nom' => $nom,
                'cognoms' => $cognoms,
                'email' => $email,
                'extra' => array_values(array_filter(array_map([self::class, 'cleanName'], $extra), static fn($e) => $e !== '')),
            ]);

            if (count($resultat) >= self::MAX_FILES) {
                break;
            }
        }

        $avisos = [];
        if ($senseEmail !== []) {
            $mostra = array_map(static fn($l) => '«' . mb_strimwidth($l, 0, 60, '…') . '»', array_slice($senseEmail, 0, 3));
            $avisos[] = (count($senseEmail) === 1 ? 'S\'ha ignorat 1 línia sense email: ' : 'S\'han ignorat ' . count($senseEmail) . ' línies sense email: ')
                . implode(', ', $mostra) . (count($senseEmail) > 3 ? '…' : '');
        }

        return self::limitWarning(['files' => $resultat, 'avisos' => $avisos], count($files) - count($senseEmail));
    }

    /**
     * Una línia amb diversos emails («A <a@x>; B <b@x>») es parteix en una línia per persona.
     */
    private static function expandMultipleEmails(string $linia): array
    {
        if (str_contains($linia, "\t") || preg_match_all(self::EMAIL_REGEX, $linia, $m, PREG_OFFSET_CAPTURE) < 2) {
            return [$linia];
        }

        $parts = [];
        $inici = 0;
        foreach ($m[0] as [$email, $offset]) {
            $fi = $offset + strlen($email);
            if (($linia[$fi] ?? '') === '>') {
                $fi++;
            }
            $parts[] = ltrim(substr($linia, $inici, $fi - $inici), " \t;,");
            $inici = $fi;
        }

        return $parts;
    }

    private static function detectDelimiter(array $linies): ?string
    {
        if ($linies === []) {
            return null;
        }

        foreach (["\t", ';'] as $delimitador) {
            foreach ($linies as $linia) {
                if (str_contains($linia, $delimitador)) {
                    return $delimitador;
                }
            }
        }

        $ambComes = count(array_filter($linies, static fn($l) => substr_count($l, ',') >= 2));

        return $ambComes > 0 && $ambComes >= count($linies) / 2 ? ',' : null;
    }

    /**
     * @return array<int, string>|null índex de columna → camp, o null si la fila no és una capçalera
     */
    private static function detectHeader(array $cells): ?array
    {
        foreach ($cells as $cell) {
            if (str_contains($cell, '@')) {
                return null;
            }
        }

        $mapa = [];
        foreach ($cells as $i => $cell) {
            $camp = self::headerField($cell);
            if ($camp !== null) {
                $mapa[$i] = $camp;
            }
        }

        return $mapa === [] ? null : $mapa;
    }

    private static function headerField(string $capcalera): ?string
    {
        $normalitzada = self::normalize($capcalera);
        if ($normalitzada === '') {
            return null;
        }

        foreach (self::CAPCALERES_EXACTES as $camp => $alies) {
            if (in_array($normalitzada, $alies, true)) {
                return $camp;
            }
        }

        $paraules = explode(' ', $normalitzada);
        $conte = static fn(array $claus) => array_intersect($paraules, $claus) !== [];

        if ($conte(self::CAPCALERES_PARAULES['email'])) {
            return 'email';
        }
        if ($conte(self::CAPCALERES_PARAULES['cognoms'])) {
            return $conte(self::CAPCALERES_PARAULES['nom']) ? 'nom_complet' : 'cognoms';
        }
        // «Nom de la instal·lació» és la instal·lació, no el nom de la persona.
        foreach (['instalacio', 'torns', 'rol', 'nom'] as $camp) {
            if ($conte(self::CAPCALERES_PARAULES[$camp])) {
                return $camp;
            }
        }

        return null;
    }

    /** Parteix un tros de text lliure per separadors visuals: dos espais, « - », « | ». */
    private static function segments(string $text): array
    {
        $parts = preg_split('/\s{2,}|\s[-–—|·]\s|\(|\)|<|>/u', $text) ?: [];

        return array_values(array_filter(array_map([self::class, 'cleanName'], $parts), static fn($p) => $p !== ''));
    }

    private static function cleanName(string $value): string
    {
        $value = preg_replace('/^\s*(\d+\s*[.)\-:]|[-*•·–—])\s*/u', '', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = trim($value, " \t\n\r\0\x0B.,;:-–—|\"'«»<>()[]");

        $teLletres = preg_match('/\p{L}/u', $value) === 1;
        if ($teLletres && (mb_strtoupper($value, 'UTF-8') === $value || mb_strtolower($value, 'UTF-8') === $value)) {
            $value = mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }

        return $value;
    }

    private static function row(array $values): array
    {
        $row = array_merge([
            'nom' => '',
            'cognoms' => '',
            'email' => '',
            'rol' => '',
            'torns' => '',
            'instalacio' => '',
            'extra' => [],
        ], $values);
        $row['email'] = mb_strtolower(trim($row['email']), 'UTF-8');

        return $row;
    }

    private static function limitWarning(array $result, int $totalFiles): array
    {
        if ($totalFiles > self::MAX_FILES) {
            $result['avisos'][] = 'Només s\'han llegit les primeres ' . self::MAX_FILES . ' persones. Importa la resta en un altre bloc.';
        }

        return $result;
    }

    private static function toUtf8(string $text): string
    {
        if (str_starts_with($text, "\xEF\xBB\xBF")) {
            $text = substr($text, 3);
        }

        return mb_check_encoding($text, 'UTF-8') ? $text : mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
    }
}
