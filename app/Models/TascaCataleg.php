<?php

namespace App\Models;

use App\Core\Model;

class TascaCataleg extends Model
{
    protected static string $table = 'tasques_cataleg';

    /** Blocs de la jornada de l'operari, en l'ordre en què es fan. */
    public const TIPUS = [
        'obertura' => 'Obertura',
        'normativa' => 'Normatives',
        'preventiu' => 'Preventiu',
        'tancament' => 'Tancament',
    ];

    /** Bloc de les tasques sense tipus: el tipus es posa sempre a mà, mai sol. */
    public const SENSE_BLOC = 'sense_bloc';

    /** Pestanya de la Vista Diària amb totes les tasques del dia, agrupades per bloc. És la primera i la que s'obre per defecte. */
    public const TOTES = 'totes';

    /** Pestanyes de la Vista Diària per blocs: els quatre blocs i, al final, les tasques sense tipus. */
    public static function blocs(): array
    {
        return self::TIPUS + [self::SENSE_BLOC => 'Sense bloc'];
    }

    /** Bloc d'una tasca del pla: el tipus que s'ha posat a mà o, si no en té, «sense bloc». */
    public static function tipusSql(string $catalegAlias = 'tc'): string
    {
        return "COALESCE({$catalegAlias}.tipus, '" . self::SENSE_BLOC . "')";
    }

    /** Valor vàlid per desar a la columna tipus; null = sense tipus. */
    public static function tipusValid(mixed $value): ?string
    {
        $value = (string)$value;
        return isset(self::TIPUS[$value]) ? $value : null;
    }

    /** Interpreta el tipus escrit a l'Excel (català o castellà). Null si està buit o no s'entén. */
    public static function tipusDesDeText(string $text): ?string
    {
        $text = mb_strtolower(trim($text));
        if ($text === '') {
            return null;
        }
        $patterns = [
            'obertura' => '/obertura|apertura|posada en marxa|puesta en marcha|inici|inicio/u',
            'tancament' => '/tancament|cierre|tancar|cerrar/u',
            'normativa' => '/normati|legal/u',
            'preventiu' => '/preventi/u',
        ];
        foreach ($patterns as $tipus => $pattern) {
            if (preg_match($pattern, $text)) {
                return $tipus;
            }
        }
        return null;
    }

    public static function allWithRelations(int $instalacioId, string $orderBy = 'codi ASC, nom ASC'): array
    {
        return static::query('
            SELECT tc.*, s.codi AS sistema_codi, s.nom AS sistema_nom,
                   te.codi AS tipus_codi, te.nom AS tipus_nom,
                   p.nom AS periodicitat_nom, n.nom AS normativa_nom
            FROM tasques_cataleg tc
            LEFT JOIN sistemes s ON s.id = tc.sistema_id
            LEFT JOIN tipus_equip te ON te.id = tc.tipus_equip_id
            LEFT JOIN periodicitats p ON p.id = tc.periodicitat_normativa_id
            LEFT JOIN normatives n ON n.id = tc.normativa_id
            WHERE tc.activa = 1 AND tc.instalacio_id = ?
            ORDER BY ' . $orderBy,
            [$instalacioId]
        );
    }

    public static function search(int $instalacioId, string $term): array
    {
        return static::query('
            SELECT tc.*, s.codi AS sistema_codi, s.nom AS sistema_nom
            FROM tasques_cataleg tc
            LEFT JOIN sistemes s ON s.id = tc.sistema_id
            WHERE tc.activa = 1 AND tc.instalacio_id = ?
              AND (tc.nom LIKE ? OR tc.codi LIKE ? OR s.codi LIKE ?)
            ORDER BY tc.codi ASC, tc.nom ASC
        ', [$instalacioId, "%{$term}%", "%{$term}%", "%{$term}%"]);
    }

    /**
     * Repositori complet de tasques, incloses les arxivades i les que ja no
     * formen part del pla. Els comptadors permeten oferir l'acció adequada
     * sense perdre l'històric de programació.
     */
    public static function repository(int $instalacioId, string $term = ''): array
    {
        $sql = '
            SELECT tc.*, s.codi AS sistema_codi, s.nom AS sistema_nom,
                   te.codi AS tipus_codi, te.nom AS tipus_nom,
                   p.nom AS periodicitat_nom, n.nom AS normativa_nom,
                   (SELECT COUNT(*) FROM tasques_pla tp
                    WHERE tp.tasca_cataleg_id = tc.id AND tp.instalacio_id = tc.instalacio_id
                      AND tp.en_curs = 1) AS active_plan_count,
                   (SELECT COUNT(*) FROM tasques_pla tp
                    WHERE tp.tasca_cataleg_id = tc.id AND tp.instalacio_id = tc.instalacio_id
                      AND tp.en_curs = 0) AS inactive_plan_count,
                   (SELECT MAX(tp.id) FROM tasques_pla tp
                    WHERE tp.tasca_cataleg_id = tc.id AND tp.instalacio_id = tc.instalacio_id
                      AND tp.en_curs = 0) AS reactivable_plan_id
            FROM tasques_cataleg tc
            LEFT JOIN sistemes s ON s.id = tc.sistema_id
            LEFT JOIN tipus_equip te ON te.id = tc.tipus_equip_id
            LEFT JOIN periodicitats p ON p.id = tc.periodicitat_normativa_id
            LEFT JOIN normatives n ON n.id = tc.normativa_id
            WHERE tc.instalacio_id = ?';
        $params = [$instalacioId];

        if ($term !== '') {
            $sql .= ' AND (tc.nom LIKE ? OR tc.codi LIKE ? OR s.codi LIKE ?)';
            $like = "%{$term}%";
            array_push($params, $like, $like, $like);
        }

        $sql .= ' ORDER BY (active_plan_count > 0) DESC, tc.activa DESC, tc.codi ASC, tc.nom ASC';

        return static::query($sql, $params);
    }

    public static function belongsToInstalacio(int $id, int $instalacioId): bool
    {
        $rows = static::query(
            'SELECT 1 FROM tasques_cataleg WHERE id = ? AND instalacio_id = ? LIMIT 1',
            [$id, $instalacioId]
        );
        return !empty($rows);
    }

    public static function hasActivePlan(int $id, int $instalacioId): bool
    {
        $rows = static::query(
            'SELECT 1 FROM tasques_pla WHERE tasca_cataleg_id = ? AND instalacio_id = ? AND en_curs = 1 LIMIT 1',
            [$id, $instalacioId]
        );
        return !empty($rows);
    }
}
