<?php

namespace App\Services;

/**
 * Lògica pura (sense BD) de la importació d'usuaris.
 *
 * Context esperat:
 *   instalacions          list<{id, nom}>   instal·lacions que pot fer servir qui importa
 *   rols                  list<{id, nom}>   rols assignables (mai superadmin)
 *   torns                 map instalacioId => list<{id, nom}>
 *   default_instalacio_id ?int
 *   default_rol_id        ?int
 * Només validate():
 *   is_superadmin         bool
 *   current_instalacio_id ?int
 *   existing              map email en minúscules => {id, actiu, is_superadmin, instalacio_ids}
 */
class UsuariImportResolver
{
    public const ACCIO_CREAR = 'crear';
    public const ACCIO_ASSIGNAR = 'assignar';
    public const ACCIO_ACTUALITZAR = 'actualitzar';

    /** Com s'escriu el «puesto» a la vida real → nom del rol. Normalitzat amb UsuariImportParser::normalize. */
    private const ROL_ALIES = [
        'tecnic' => [
            'tecnic', 'tecnica', 'tecnico', 'operari', 'operaria', 'operario', 'mantenidor', 'mantenidora',
            'oficial', 'tecnic de manteniment', 'tecnico de mantenimiento', 'operari de manteniment',
            'operario de mantenimiento',
        ],
        'cap_manteniment' => [
            'cap de manteniment', 'cap manteniment', 'jefe de mantenimiento', 'jefe mantenimiento', 'cap', 'jefe',
            'encarregat', 'encarregada', 'encargado', 'encargada', 'coordinador', 'coordinadora', 'supervisor',
            'supervisora', 'responsable de manteniment', 'responsable de mantenimiento',
        ],
        'admin_instalacio' => [
            'admin', 'administrador', 'administradora', 'admin instalacio', 'administrador instalacion',
            'administrador de la instalacion', 'administrador de la instalacio', 'gestor', 'gestora',
        ],
        'lectura' => [
            'lectura', 'lector', 'lectora', 'consulta', 'direccio', 'direccion', 'director', 'directora', 'auditor',
            'auditora', 'client', 'cliente', 'ajuntament', 'ayuntamiento',
        ],
    ];

    private const TOTS_ELS_TORNS = [
        'tots', 'totes', 'tot', 'todos', 'todas', 'todo', 'all', 'tots els torns', 'todos los turnos',
    ];

    /** Àlies més curts que això només compten si coincideixen exactament («cap» ≠ «cap de setmana»). */
    private const MIN_LLARGADA_PREFIX = 6;

    /**
     * Converteix les files crues del parser en files editables del panell.
     *
     * @return list<array{nom: string, cognoms: string, email: string, instalacio_id: ?int, rol_id: ?int, torn_ids: list<int>, avisos: array<string, string>}>
     */
    public static function resolve(array $rawRows, array $ctx): array
    {
        $resultat = [];

        foreach ($rawRows as $raw) {
            $avisos = [];
            $extra = array_values((array)($raw['extra'] ?? []));
            $rolText = trim((string)($raw['rol'] ?? ''));
            $instalacioText = trim((string)($raw['instalacio'] ?? ''));
            $tornsText = trim((string)($raw['torns'] ?? ''));

            $rolId = $rolText !== '' ? self::matchRol($rolText, $ctx['rols'] ?? []) : null;
            $instalacioId = $instalacioText !== '' ? self::matchInstalacio($instalacioText, $ctx['instalacions'] ?? []) : null;

            // Dades sense capçalera: primer rol i instal·lació, després torns (depenen de la instal·lació).
            $pendents = [];
            foreach ($extra as $valor) {
                if ($rolText === '' && ($id = self::matchRol($valor, $ctx['rols'] ?? [])) !== null) {
                    $rolText = $valor;
                    $rolId = $id;
                } elseif ($instalacioText === '' && ($id = self::matchInstalacio($valor, $ctx['instalacions'] ?? [])) !== null) {
                    $instalacioText = $valor;
                    $instalacioId = $id;
                } else {
                    $pendents[] = $valor;
                }
            }

            if ($instalacioText !== '' && $instalacioId === null) {
                $avisos['instalacio'] = 'Instal·lació no reconeguda: «' . $instalacioText . '».';
            }
            $instalacioId ??= $ctx['default_instalacio_id'] ?? null;

            if ($rolText !== '' && $rolId === null) {
                $avisos['rol'] = 'Rol no reconegut: «' . $rolText . '». S\'ha posat el rol per defecte.';
            }
            $rolId ??= $ctx['default_rol_id'] ?? null;

            $tornsInstalacio = $instalacioId !== null ? ($ctx['torns'][$instalacioId] ?? []) : [];
            $tornIds = [];
            $noReconeguts = [];
            if ($tornsText !== '') {
                [$tornIds, $noReconeguts] = self::matchTorns($tornsText, $tornsInstalacio);
            }

            $dadesNoReconegudes = [];
            foreach ($pendents as $valor) {
                [$ids, $sobrants] = self::matchTorns($valor, $tornsInstalacio);
                if ($tornsText === '' && $ids !== [] && $sobrants === []) {
                    $tornsText = $valor;
                    $tornIds = $ids;
                } else {
                    $dadesNoReconegudes[] = $valor;
                }
            }

            if ($tornsText !== '' && $instalacioId === null) {
                $avisos['torns'] = 'Tria la instal·lació per assignar els torns «' . $tornsText . '».';
            } elseif ($noReconeguts !== []) {
                $avisos['torns'] = 'Torns no reconeguts: «' . implode('», «', $noReconeguts) . '»';
            }
            if ($dadesNoReconegudes !== []) {
                $avisos['extra'] = 'Dades no reconegudes: «' . implode('», «', $dadesNoReconegudes) . '»';
            }

            $resultat[] = [
                'nom' => (string)($raw['nom'] ?? ''),
                'cognoms' => (string)($raw['cognoms'] ?? ''),
                'email' => (string)($raw['email'] ?? ''),
                'instalacio_id' => $instalacioId !== null ? (int)$instalacioId : null,
                'rol_id' => $rolId !== null ? (int)$rolId : null,
                'torn_ids' => $tornIds,
                'avisos' => $avisos,
            ];
        }

        return $resultat;
    }

    /**
     * @return array{files: list<array{accio: ?string, errors: list<string>, avisos: list<string>}>, resum: array{usuaris_nous: int, existents: int, errors: int, files: int}}
     */
    public static function validate(array $rows, array $ctx): array
    {
        $instalacionsPermeses = array_map('intval', array_column($ctx['instalacions'] ?? [], 'id'));
        $rolsPermesos = array_map('intval', array_column($ctx['rols'] ?? [], 'id'));
        $isSuperadmin = !empty($ctx['is_superadmin']);
        $currentInstalacioId = isset($ctx['current_instalacio_id']) ? (int)$ctx['current_instalacio_id'] : null;
        $existents = $ctx['existing'] ?? [];

        $files = [];
        $vistes = [];
        $usuarisNous = [];
        $totalExistents = 0;
        $totalErrors = 0;

        foreach (array_values($rows) as $index => $row) {
            $errors = [];
            $avisos = [];
            $accio = null;

            $email = mb_strtolower(trim((string)($row['email'] ?? '')), 'UTF-8');
            $nom = trim((string)($row['nom'] ?? ''));
            $cognoms = trim((string)($row['cognoms'] ?? ''));
            $instalacioId = (int)($row['instalacio_id'] ?? 0);
            $rolId = (int)($row['rol_id'] ?? 0);
            $tornIds = array_map('intval', (array)($row['torn_ids'] ?? []));
            $emailValid = $email !== '' && strlen($email) <= 255 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
            $existent = $emailValid ? ($existents[$email] ?? null) : null;

            if ($email === '') {
                $errors[] = 'Falta l\'email.';
            } elseif (!$emailValid) {
                $errors[] = 'L\'email no és vàlid.';
            }

            if ($existent === null) {
                if ($nom === '') {
                    $errors[] = 'Falta el nom.';
                } elseif (mb_strlen($nom) > 100) {
                    $errors[] = 'El nom és massa llarg (màxim 100 caràcters).';
                }
                if (mb_strlen($cognoms) > 200) {
                    $errors[] = 'Els cognoms són massa llargs (màxim 200 caràcters).';
                }
            }

            if ($instalacioId <= 0) {
                $errors[] = 'Tria una instal·lació.';
            } elseif (!in_array($instalacioId, $instalacionsPermeses, true)) {
                $errors[] = 'No pots assignar usuaris a aquesta instal·lació.';
            }

            if (!in_array($rolId, $rolsPermesos, true)) {
                $errors[] = 'Tria un rol.';
            }

            if ($instalacioId > 0) {
                $tornsValids = array_map('intval', array_column($ctx['torns'][$instalacioId] ?? [], 'id'));
                if (array_diff($tornIds, $tornsValids) !== []) {
                    $errors[] = 'Hi ha torns que no són d\'aquesta instal·lació.';
                }
            }

            if ($email !== '' && $instalacioId > 0) {
                $clau = $email . '|' . $instalacioId;
                if (isset($vistes[$clau])) {
                    $errors[] = 'Fila repetida: mateix email i instal·lació que la fila ' . ($vistes[$clau] + 1) . '.';
                } else {
                    $vistes[$clau] = $index;
                }
            }

            if ($existent !== null) {
                $instalacionsExistent = array_map('intval', (array)($existent['instalacio_ids'] ?? []));
                $jaHiEs = in_array($instalacioId, $instalacionsExistent, true);

                if (!empty($existent['is_superadmin'])) {
                    $errors[] = 'És un superadmin: ja té accés a totes les instal·lacions.';
                } elseif (!$isSuperadmin && !in_array((int)$currentInstalacioId, $instalacionsExistent, true)) {
                    // Si no, un admin podria afegir-se un compte aliè i generar-li un enllaç d'accés.
                    $errors[] = 'Aquest email ja té compte en una altra instal·lació. Només un superadmin el pot afegir aquí.';
                } elseif ($jaHiEs) {
                    $accio = self::ACCIO_ACTUALITZAR;
                    $avisos[] = 'Ja és a aquesta instal·lació: se n\'actualitzaran el rol i els torns.';
                } else {
                    $accio = self::ACCIO_ASSIGNAR;
                    $avisos[] = 'Ja té compte: s\'afegirà a aquesta instal·lació i mantindrà la seva contrasenya.';
                }

                if ($accio !== null && empty($existent['actiu'])) {
                    $avisos[] = 'L\'usuari està desactivat: no podrà entrar fins que el reactivis.';
                }
            } else {
                $accio = self::ACCIO_CREAR;
            }

            if ($errors !== []) {
                $accio = null;
                $totalErrors++;
            } elseif ($accio === self::ACCIO_CREAR) {
                $usuarisNous[$email] = true;
            } else {
                $totalExistents++;
            }

            $files[] = ['accio' => $accio, 'errors' => $errors, 'avisos' => $avisos];
        }

        return [
            'files' => $files,
            'resum' => [
                'usuaris_nous' => count($usuarisNous),
                'existents' => $totalExistents,
                'errors' => $totalErrors,
                'files' => count($files),
            ],
        ];
    }

    private static function matchRol(string $text, array $rols): ?int
    {
        $normalitzat = UsuariImportParser::normalize($text);
        if ($normalitzat === '') {
            return null;
        }

        $idsPerNom = [];
        foreach ($rols as $rol) {
            $idsPerNom[$rol['nom']] = (int)$rol['id'];
            if (UsuariImportParser::normalize($rol['nom']) === $normalitzat) {
                return (int)$rol['id'];
            }
        }

        $alies = [];
        foreach (self::ROL_ALIES as $rolNom => $llista) {
            foreach ($llista as $a) {
                $alies[$a] = $rolNom;
            }
        }

        if (isset($alies[$normalitzat], $idsPerNom[$alies[$normalitzat]])) {
            return $idsPerNom[$alies[$normalitzat]];
        }

        // «Tècnic de piscines», «Oficial de primera»... Els àlies llargs primer.
        uksort($alies, static fn($a, $b) => strlen($b) <=> strlen($a));
        foreach ($alies as $a => $rolNom) {
            if (strlen($a) >= self::MIN_LLARGADA_PREFIX && str_starts_with($normalitzat, $a . ' ') && isset($idsPerNom[$rolNom])) {
                return $idsPerNom[$rolNom];
            }
        }

        return null;
    }

    private static function matchInstalacio(string $text, array $instalacions): ?int
    {
        $normalitzat = UsuariImportParser::normalize($text);
        if ($normalitzat === '') {
            return null;
        }

        $paraules = explode(' ', $normalitzat);
        $parcials = [];
        foreach ($instalacions as $instalacio) {
            $nom = UsuariImportParser::normalize($instalacio['nom']);
            if ($nom === $normalitzat) {
                return (int)$instalacio['id'];
            }
            // «Sant Joan» dins «CEM Sant Joan»; «Piscina Cornellà» dins «Piscina Municipal Cornellà».
            $totesLesParaules = array_diff($paraules, explode(' ', $nom)) === [];
            if (strlen($normalitzat) >= 4 && (str_contains($nom, $normalitzat) || str_contains($normalitzat, $nom) || $totesLesParaules)) {
                $parcials[] = (int)$instalacio['id'];
            }
        }

        return count($parcials) === 1 ? $parcials[0] : null;
    }

    /**
     * @return array{0: list<int>, 1: list<string>} ids reconeguts i trossos de text no reconeguts
     */
    private static function matchTorns(string $text, array $torns): array
    {
        $normalitzat = UsuariImportParser::normalize($text);
        if ($normalitzat === '') {
            return [[], []];
        }

        if (in_array($normalitzat, self::TOTS_ELS_TORNS, true)) {
            return [array_map(static fn($t) => (int)$t['id'], $torns), []];
        }

        // Primer el text sencer: un torn es pot dir «Dissabte i diumenge».
        $sencer = self::matchTorn($normalitzat, $torns);
        if ($sencer !== null) {
            return [[$sencer], []];
        }

        $ids = [];
        $noReconeguts = [];
        foreach (preg_split('/\s*[,;\/+]\s*|\s+(?:i|y|and)\s+/u', $text) ?: [] as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $id = self::matchTorn(UsuariImportParser::normalize($part), $torns);
            if ($id === null) {
                $noReconeguts[] = $part;
            } elseif (!in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return [$ids, $noReconeguts];
    }

    private static function matchTorn(string $normalitzat, array $torns): ?int
    {
        if ($normalitzat === '') {
            return null;
        }

        $perPrefix = [];
        foreach ($torns as $torn) {
            $nom = UsuariImportParser::normalize($torn['nom']);
            if ($nom === $normalitzat) {
                return (int)$torn['id'];
            }
            if (str_starts_with($nom, $normalitzat . ' ')) {
                $perPrefix[] = (int)$torn['id'];
            }
        }

        return count($perPrefix) === 1 ? $perPrefix[0] : null;
    }
}
