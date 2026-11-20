<?php

namespace App\Models;

use App\Core\Model;

/**
 * Paràmetres de l'aigua de piscina. Una fila per tasca del pla (una per vas),
 * dia i moment (inici de jornada, 14 h, final), com el full de paràmetres.
 * El clor i el pH es mesuren a cada moment; la resta, un cop al dia abans
 * d'obrir al públic. La tasca es dona per feta amb la primera mesura del dia.
 */
class MesuraPiscina extends Model
{
    protected static string $table = 'mesures_piscina';

    public const OK = 'ok';
    public const FORA = 'fora';
    public const TANCAR = 'tancar';

    /** Moments del dia, fixos. L'hora és orientativa. */
    public const MOMENTS = [
        'inici' => 'Inici jornada',
        'migdia' => 'Mant. 14 h',
        'final' => 'Final jornada',
    ];

    /**
     * Paràmetres del formulari, en l'ordre del full Param_Aigua_Piscina.
     * 'norma' és el text del valor normal (R.D. 742/2013); la comprovació és a avaluar().
     * 'cada_moment' = es mesura a cada moment; si no, un cop al dia.
     */
    public const PARAMETRES = [
        'clor_lliure' => ['nom' => 'Clor lliure', 'unitat' => 'mg/l', 'norma' => '0,5 – 2', 'min' => 0, 'max' => 20, 'cada_moment' => true],
        'clor_total' => ['nom' => 'Clor total', 'unitat' => 'mg/l', 'norma' => '', 'min' => 0, 'max' => 20, 'cada_moment' => true],
        'clor_combinat' => ['nom' => 'Clor combinat', 'unitat' => 'mg/l', 'norma' => '< 0,6', 'min' => 0, 'max' => 20, 'cada_moment' => true],
        'ph' => ['nom' => 'pH', 'unitat' => '', 'norma' => '7,2 – 8', 'min' => 0, 'max' => 14, 'cada_moment' => true],
        'co2_interior' => ['nom' => 'CO₂ interior', 'unitat' => '', 'norma' => '< exterior + 500', 'min' => 0, 'max' => 99999, 'cada_moment' => false],
        'co2_exterior' => ['nom' => 'CO₂ exterior', 'unitat' => '', 'norma' => '', 'min' => 0, 'max' => 99999, 'cada_moment' => false],
        'temp_aigua' => ['nom' => 'Temperatura del vas', 'unitat' => 'ºC', 'norma' => '', 'min' => -10, 'max' => 60, 'cada_moment' => false],
        'temp_ambient' => ['nom' => 'Temperatura ambient', 'unitat' => 'ºC', 'norma' => '', 'min' => -30, 'max' => 60, 'cada_moment' => false],
        'humitat' => ['nom' => 'Humitat relativa', 'unitat' => '%', 'norma' => '< 65', 'min' => 0, 'max' => 100, 'cada_moment' => false],
        'terbolesa' => ['nom' => 'Terbolesa', 'unitat' => 'UNF', 'norma' => '< 5', 'min' => 0, 'max' => 9999, 'cada_moment' => false],
        'recirculacio' => ['nom' => 'Recirculació (comptador)', 'unitat' => '', 'norma' => '', 'min' => 0, 'max' => 999999999999, 'cada_moment' => false],
        'aigua_nova' => ['nom' => 'Aigua nova (comptador)', 'unitat' => '', 'norma' => '', 'min' => 0, 'max' => 999999999999, 'cada_moment' => false],
    ];

    /** Camps que es mesuren a cada moment (clor i pH). */
    public static function campsCadaMoment(): array
    {
        return array_keys(array_filter(self::PARAMETRES, static fn(array $p): bool => $p['cada_moment']));
    }

    /** Camps d'un cop al dia (CO2, temperatures, humitat, terbolesa, comptadors). */
    public static function campsDiaris(): array
    {
        return array_keys(array_filter(self::PARAMETRES, static fn(array $p): bool => !$p['cada_moment']));
    }

    /**
     * Llegeix un número escrit a mà: coma o punt decimal, separador de milers
     * («1.190.180», «1.190.180,50») i unitats al final («27,8º», «62 %»).
     * Buit = null; si no és un número, false.
     */
    public static function numero(mixed $value): float|false|null
    {
        $text = preg_replace('/\s+/u', '', trim((string)$value));
        $text = preg_replace('/(ºc|°c|º|°|%|ppm|mg\/l|unf)$/iu', '', $text);
        if ($text === '') {
            return null;
        }
        $comes = substr_count($text, ',');
        $punts = substr_count($text, '.');
        if ($comes > 0 && $punts > 0) {
            // L'últim separador és el decimal; l'altre, el de milers.
            $text = strrpos($text, ',') > strrpos($text, '.')
                ? str_replace(['.', ','], ['', '.'], $text)
                : str_replace(',', '', $text);
        } elseif ($comes > 1) {
            $text = str_replace(',', '', $text);
        } elseif ($punts > 1) {
            $text = str_replace('.', '', $text);
        } else {
            $text = str_replace(',', '.', $text);
        }
        return is_numeric($text) ? round((float)$text, 2) : false;
    }

    /**
     * Valors del formulari nets i errors (camp => missatge) dels que no es poden desar.
     * El clor combinat es calcula (total − lliure) si no s'escriu.
     * Sense $ambDiaris només es llegeixen el clor i el pH: els d'un cop al dia
     * ja s'han desat en un altre moment i no es toquen.
     */
    public static function valorsDesDePost(array $post, bool $ambDiaris = true): array
    {
        $valors = [];
        $errors = [];
        $camps = $ambDiaris ? self::PARAMETRES : array_intersect_key(self::PARAMETRES, array_flip(self::campsCadaMoment()));
        foreach ($camps as $camp => $p) {
            $n = self::numero($post[$camp] ?? null);
            if ($n === false) {
                $errors[$camp] = $p['nom'] . ': «' . trim((string)$post[$camp]) . '» no és un número';
                $n = null;
            } elseif ($n !== null && ($n < $p['min'] || $n > $p['max'])) {
                $errors[$camp] = $p['nom'] . ': ' . str_replace('.', ',', (string)$n) . ' no és un valor possible';
                $n = null;
            }
            $valors[$camp] = $n;
        }
        if ($valors['clor_combinat'] === null && !isset($errors['clor_combinat'])
            && $valors['clor_total'] !== null && $valors['clor_lliure'] !== null) {
            $valors['clor_combinat'] = max(0.0, round($valors['clor_total'] - $valors['clor_lliure'], 2));
        }
        return [$valors, $errors];
    }

    public static function teAlgunValor(array $valors): bool
    {
        foreach (array_keys($valors) as $camp) {
            if (isset(self::PARAMETRES[$camp]) && $valors[$camp] !== null) {
                return true;
            }
        }
        return false;
    }

    /**
     * Estat de cada paràmetre: OK, FORA (fora de norma) o TANCAR (cal tancar el vas),
     * segons les capçaleres del full de paràmetres. Els buits no surten.
     */
    public static function avaluar(array $valors): array
    {
        $v = static fn(string $camp): ?float => isset($valors[$camp]) && $valors[$camp] !== '' ? (float)$valors[$camp] : null;
        $estats = [];

        if (($x = $v('clor_lliure')) !== null) {
            $estats['clor_lliure'] = ($x <= 0 || $x > 5) ? self::TANCAR : (($x < 0.5 || $x > 2) ? self::FORA : self::OK);
        }
        if (($x = $v('clor_combinat')) !== null) {
            $estats['clor_combinat'] = $x > 3 ? self::TANCAR : ($x >= 0.6 ? self::FORA : self::OK);
        }
        if (($x = $v('ph')) !== null) {
            $estats['ph'] = ($x < 6 || $x > 9) ? self::TANCAR : (($x < 7.2 || $x > 8) ? self::FORA : self::OK);
        }
        if (($x = $v('co2_interior')) !== null && ($ext = $v('co2_exterior')) !== null) {
            $estats['co2_interior'] = $x >= $ext + 500 ? self::FORA : self::OK;
        }
        if (($x = $v('humitat')) !== null) {
            $estats['humitat'] = $x >= 65 ? self::FORA : self::OK;
        }
        if (($x = $v('terbolesa')) !== null) {
            $estats['terbolesa'] = $x > 20 ? self::TANCAR : ($x >= 5 ? self::FORA : self::OK);
        }

        return $estats;
    }

    /** Noms dels paràmetres amb un estat concret, per als avisos. */
    public static function nomsAmbEstat(array $estats, string $estat): array
    {
        $noms = [];
        foreach ($estats as $camp => $e) {
            if ($e === $estat) {
                $noms[] = self::PARAMETRES[$camp]['nom'];
            }
        }
        return $noms;
    }

    /**
     * Moment en què toca mesurar segons l'hora (no cal que sigui exacta): fins a
     * les 12 h, inici; fins a les 17 h, el de les 14 h; després, final. Si aquest
     * ja està mesurat, el primer que falta. Pur, per poder-ho provar.
     */
    public static function momentProposat(array $mesuresDelDia, int $hora): string
    {
        $perHora = $hora < 12 ? 'inici' : ($hora < 17 ? 'migdia' : 'final');
        if (!isset($mesuresDelDia[$perHora])) {
            return $perHora;
        }
        foreach (array_keys(self::MOMENTS) as $moment) {
            if (!isset($mesuresDelDia[$moment])) {
                return $moment;
            }
        }
        return $perHora;
    }

    /**
     * Moment del dia on ja hi ha els paràmetres d'un cop al dia (CO2,
     * temperatures...), o null. $excepte = el moment que s'està desant.
     */
    public static function momentAmbDiaris(array $mesuresDelDia, ?string $excepte = null): ?string
    {
        foreach (self::MOMENTS as $moment => $nom) {
            if ($moment === $excepte || !isset($mesuresDelDia[$moment])) {
                continue;
            }
            foreach (self::campsDiaris() as $camp) {
                if (($mesuresDelDia[$moment][$camp] ?? null) !== null) {
                    return $moment;
                }
            }
        }
        return null;
    }

    /** Mesures d'un dia per a diverses tasques: [tasca_pla_id][moment] => fila. */
    public static function delDia(int $instalacioId, array $tascaPlaIds, string $data): array
    {
        $tascaPlaIds = array_values(array_unique(array_filter(array_map('intval', $tascaPlaIds))));
        if ($tascaPlaIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($tascaPlaIds), '?'));
        $rows = static::query(
            "SELECT * FROM mesures_piscina
             WHERE instalacio_id = ? AND data = ? AND tasca_pla_id IN ({$placeholders})",
            array_merge([$instalacioId, $data], $tascaPlaIds)
        );
        $resultat = [];
        foreach ($rows as $row) {
            $resultat[(int)$row['tasca_pla_id']][$row['moment']] = $row;
        }
        return $resultat;
    }

    /**
     * Desa (o corregeix) la mesura d'un moment del dia. Només toca els camps de
     * $valors: si no hi ha els d'un cop al dia, els que hi hagués es mantenen.
     * Una fila per tasca, dia i moment (clau única), així dues persones que
     * desen alhora no la dupliquen.
     */
    public static function desar(int $instalacioId, int $tascaPlaId, string $data, string $moment, ?int $usuariId, array $valors, ?string $observacions): void
    {
        $fila = $valors + ['usuari_id' => $usuariId, 'observacions' => $observacions];
        $camps = array_keys($fila);
        $columnes = array_merge(['instalacio_id', 'tasca_pla_id', 'data', 'moment'], $camps);
        $actualitzar = implode(', ', array_map(static fn(string $c): string => "`{$c}` = VALUES(`{$c}`)", $camps));
        static::execute(
            'INSERT INTO mesures_piscina (`' . implode('`, `', $columnes) . '`)
             VALUES (' . implode(', ', array_fill(0, count($columnes), '?')) . ')
             ON DUPLICATE KEY UPDATE ' . $actualitzar,
            array_merge([$instalacioId, $tascaPlaId, $data, $moment], array_values($fila))
        );
    }

    /** Si la instal·lació té alguna tasca de mesura al pla (per mostrar l'apartat). */
    public static function instalacioEnTe(int $instalacioId): bool
    {
        $rows = static::query(
            'SELECT 1 FROM tasques_pla tp
             JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
             WHERE tp.instalacio_id = ? AND tc.mesura_piscina = 1
             LIMIT 1',
            [$instalacioId]
        );
        return !empty($rows) || static::count(['instalacio_id' => $instalacioId]) > 0;
    }

    /** Tasques de mesura de la instal·lació (un vas cadascuna), per al filtre. */
    public static function tasquesDeMesura(int $instalacioId): array
    {
        return static::query(
            'SELECT tp.id, COALESCE(NULLIF(tp.codi, \'\'), tc.codi) AS tasca_codi, tc.nom AS tasca_nom, es.nom AS espai_nom
             FROM tasques_pla tp
             JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
             LEFT JOIN espais es ON es.id = tp.espai_id
             WHERE tp.instalacio_id = ? AND tc.mesura_piscina = 1
             ORDER BY es.nom ASC, tc.nom ASC',
            [$instalacioId]
        );
    }

    /** Historial entre dues dates, del més recent al més antic. */
    public static function historial(int $instalacioId, string $desde, string $fins, ?int $tascaPlaId = null): array
    {
        $sql = 'SELECT mp.*, COALESCE(NULLIF(tp.codi, \'\'), tc.codi) AS tasca_codi, tc.nom AS tasca_nom,
                       es.nom AS espai_nom, u.nom AS usuari_nom, u.cognoms AS usuari_cognoms
                FROM mesures_piscina mp
                JOIN tasques_pla tp ON tp.id = mp.tasca_pla_id
                JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
                LEFT JOIN espais es ON es.id = tp.espai_id
                LEFT JOIN usuaris u ON u.id = mp.usuari_id
                WHERE mp.instalacio_id = ? AND mp.data BETWEEN ? AND ?';
        $params = [$instalacioId, $desde, $fins];
        if ($tascaPlaId) {
            $sql .= ' AND mp.tasca_pla_id = ?';
            $params[] = $tascaPlaId;
        }
        $sql .= " ORDER BY mp.data DESC, es.nom ASC, tc.nom ASC, mp.tasca_pla_id ASC, FIELD(mp.moment, 'inici', 'migdia', 'final')";

        return static::query($sql, $params);
    }
}
