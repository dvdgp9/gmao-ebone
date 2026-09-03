<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportWorkbookInspector
{
    private const COMPLETE_SHEETS = [
        'LLISTES',
        'INVENTARI',
        'BD TASQUES',
        'TASQUES PLA_M',
        'REGISTRE TASQUES',
    ];

    private const COUNT_KEYS = [
        'espais',
        'torns',
        'equips',
        'sistemes',
        'tasques_cataleg',
        'tasques_pla',
        'registre',
    ];

    public static function inspect(Spreadsheet $spreadsheet, array $existingSystemCodes = []): array
    {
        $sheetNames = array_map(
            static fn(Worksheet $sheet): string => $sheet->getTitle(),
            $spreadsheet->getAllSheets()
        );

        if (count(array_intersect(self::COMPLETE_SHEETS, $sheetNames)) === count(self::COMPLETE_SHEETS)) {
            return self::inspectComplete($spreadsheet, $existingSystemCodes);
        }

        if (in_array('INSTRUCCIONS', $sheetNames, true)
            || count(array_intersect(['ESPAIS', 'TORNS', 'EQUIPS'], $sheetNames)) > 0
        ) {
            return self::inspectTemplate($spreadsheet);
        }

        return self::inspectSingleSheet($spreadsheet->getActiveSheet());
    }

    public static function detectInventoryLayout(Worksheet $sheet): array
    {
        for ($row = 1; $row <= min(10, $sheet->getHighestRow()); $row++) {
            $headers = self::headersByName($sheet, $row);

            $simple = self::resolveColumns($headers, [
                'nom_equip' => ['equip', 'nom equip'],
                'model' => ['model'],
                'ubicacio' => ['ubicacio', 'ubicacion'],
                'codi_espai' => ['codi espai', 'codigo espacio'],
                'planta' => ['planta'],
            ]);
            if (!in_array(null, $simple, true)) {
                return ['format' => 'simple', 'header_row' => $row, 'columns' => $simple];
            }

            // El lector clàssic conserva les columnes fixes del llibre històric i,
            // per tant, la seva capçalera només és segura a la primera fila.
            if ($row === 1) {
                $classic = self::resolveColumns($headers, [
                    'sistema' => ['codi', 'codi sistema', 'sistema'],
                    'tipus' => ['tipus', 'tipo'],
                    'numero' => ['numero', 'número', 'num', 'n'],
                    'nom_mn' => ['nom equip mn', 'codi equip', 'codigo equipo'],
                    'nom_equip' => ['equip', 'nom equip'],
                ]);
                if (!in_array(null, $classic, true)) {
                    return ['format' => 'classic', 'header_row' => $row, 'columns' => $classic];
                }
            }
        }

        return ['format' => 'unknown', 'header_row' => null, 'columns' => []];
    }

    public static function extractSimpleInventoryRows(Worksheet $sheet): array
    {
        $layout = self::detectInventoryLayout($sheet);
        if ($layout['format'] !== 'simple') {
            return [];
        }

        $rows = [];
        for ($row = $layout['header_row'] + 1; $row <= $sheet->getHighestRow(); $row++) {
            $name = self::cellString($sheet->getCellByColumnAndRow($layout['columns']['nom_equip'], $row));
            if ($name === '') {
                continue;
            }
            $rows[] = [
                'row' => $row,
                'nom_equip' => $name,
                'model' => self::cellString($sheet->getCellByColumnAndRow($layout['columns']['model'], $row)),
                'ubicacio' => self::cellString($sheet->getCellByColumnAndRow($layout['columns']['ubicacio'], $row)),
                'codi_espai' => self::cellString($sheet->getCellByColumnAndRow($layout['columns']['codi_espai'], $row)),
                'planta' => self::cellString($sheet->getCellByColumnAndRow($layout['columns']['planta'], $row)),
            ];
        }
        return $rows;
    }

    private static function inspectComplete(Spreadsheet $spreadsheet, array $existingSystemCodes): array
    {
        $counts = self::emptyCounts();
        $errors = [];
        $warnings = [];

        $llistes = $spreadsheet->getSheetByName('LLISTES');
        $inventory = $spreadsheet->getSheetByName('INVENTARI');
        $catalog = $spreadsheet->getSheetByName('BD TASQUES');
        $plan = $spreadsheet->getSheetByName('TASQUES PLA_M');
        $register = $spreadsheet->getSheetByName('REGISTRE TASQUES');

        if (!self::rowHasHeaders($llistes, 1, ['espais', 'codi', 'planta'])) {
            $errors[] = 'La fulla LLISTES no té les capçaleres ESPAIS, CODI i PLANTA esperades.';
        }
        if (!self::rowHasHeaders($catalog, 1, ['codi', 'tasques'])) {
            $errors[] = 'La fulla BD TASQUES no té les capçaleres CODI i TASQUES esperades.';
        }
        if (!self::rowHasHeaders($plan, 1, ['tasca', 'espai', 'periodicitat'])) {
            $errors[] = 'La fulla TASQUES PLA_M no té les capçaleres TASCA, ESPAI i PERIODICITAT esperades.';
        }
        if (!self::rowHasHeaders($register, 1, ['tasca'])) {
            $errors[] = 'La fulla REGISTRE TASQUES no té la capçalera TASCA esperada.';
        }

        $inventoryLayout = self::detectInventoryLayout($inventory);
        if ($inventoryLayout['format'] === 'unknown') {
            $errors[] = 'No es reconeixen les capçaleres de la fulla INVENTARI.';
        } elseif ($inventoryLayout['format'] === 'simple') {
            $warnings[] = 'S\'ha detectat el format alternatiu d\'INVENTARI; es maparan EQUIP, MODEL, UBICACIÓ, CODI ESPAI i PLANTA.';
        }

        for ($row = 2; $row <= $llistes->getHighestRow(); $row++) {
            if (self::cellString($llistes->getCell("C{$row}")) !== '') {
                $counts['espais']++;
            }
        }

        if ($inventoryLayout['format'] !== 'unknown') {
            $nameColumn = $inventoryLayout['columns']['nom_equip'];
            for ($row = $inventoryLayout['header_row'] + 1; $row <= $inventory->getHighestRow(); $row++) {
                if (self::cellString($inventory->getCellByColumnAndRow($nameColumn, $row)) !== '') {
                    $counts['equips']++;
                }
            }
        }

        $systemCodes = [];
        $lastSystem = '';
        for ($row = 2; $row <= $catalog->getHighestRow(); $row++) {
            $code = self::cellString($catalog->getCell("A{$row}"));
            if ($code !== '') {
                $lastSystem = $code;
            }
            if (self::cellString($catalog->getCell("D{$row}")) === '') {
                continue;
            }
            $counts['tasques_cataleg']++;
            if ($lastSystem !== '') {
                $systemCodes[self::normalize($lastSystem)] = $lastSystem;
            }
        }

        $turns = [];
        for ($row = 2; $row <= $plan->getHighestRow(); $row++) {
            if (self::cellString($plan->getCell("B{$row}")) === '') {
                continue;
            }
            $counts['tasques_pla']++;
            $turn = self::cellString($plan->getCell("P{$row}"));
            if ($turn !== '') {
                $turns[self::normalize($turn)] = true;
            }
        }
        $counts['torns'] = count($turns);

        for ($row = 2; $row <= $register->getHighestRow(); $row++) {
            if (self::cellString($register->getCell("B{$row}")) !== '') {
                $counts['registre']++;
            }
        }

        $counts['sistemes'] = count($systemCodes);
        $existing = array_fill_keys(array_map([self::class, 'normalize'], $existingSystemCodes), true);
        $newSystemCodes = [];
        foreach ($systemCodes as $normalized => $code) {
            if (!isset($existing[$normalized])) {
                $newSystemCodes[] = $code;
            }
        }
        sort($newSystemCodes, SORT_NATURAL | SORT_FLAG_CASE);
        if ($newSystemCodes !== []) {
            $warnings[] = 'Es crearan ' . count($newSystemCodes) . ' sistemes nous: ' . implode(', ', $newSystemCodes) . '.';
        }

        return self::result(
            'completa_instalacio',
            'Excel complet',
            $counts,
            $warnings,
            $errors,
            [
                'inventory_format' => $inventoryLayout['format'],
                'inventory_header_row' => $inventoryLayout['header_row'],
                'inventory_columns' => $inventoryLayout['columns'],
                'new_system_codes' => $newSystemCodes,
            ]
        );
    }

    private static function inspectTemplate(Spreadsheet $spreadsheet): array
    {
        $counts = self::emptyCounts();
        $errors = [];
        $sheetRules = [
            'ESPAIS' => ['required' => ['espai'], 'count_key' => 'espais'],
            'TORNS' => ['required' => ['nom', 'dies'], 'count_key' => 'torns'],
            'EQUIPS' => ['required' => ['nom equip'], 'count_key' => 'equips'],
            'TASQUES' => ['required' => ['tasca', 'periodicitat'], 'count_key' => 'tasques_pla'],
        ];

        $foundDataSheet = false;
        foreach ($sheetRules as $sheetName => $rule) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (!$sheet) {
                continue;
            }
            $foundDataSheet = true;
            if (!self::rowHasHeaders($sheet, 1, $rule['required'])) {
                $errors[] = "La fulla {$sheetName} no té les capçaleres esperades: " . implode(', ', $rule['required']) . '.';
                continue;
            }
            for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
                $first = self::cellString($sheet->getCell("A{$row}"));
                if ($first !== '' && !str_starts_with(self::normalize($first), 'exemple')) {
                    $counts[$rule['count_key']]++;
                }
            }
        }

        if (!$foundDataSheet) {
            $errors[] = 'No s\'ha trobat cap fulla de dades de la plantilla.';
        }

        return self::result('plantilla', 'Plantilla de configuració', $counts, [], $errors);
    }

    private static function inspectSingleSheet(Worksheet $sheet): array
    {
        $counts = self::emptyCounts();
        $headers = self::headersByName($sheet, 1);
        $taskColumn = self::firstColumn($headers, ['tasca', 'tarea', 'nom tasca', 'nombre tarea', 'mantenimiento', 'manteniment']);
        $periodColumn = self::firstColumn($headers, ['periodicitat', 'periodicidad', 'frecuencia']);

        if ($taskColumn !== null && $periodColumn !== null) {
            for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
                if (self::cellString($sheet->getCellByColumnAndRow($taskColumn, $row)) !== '') {
                    $counts['tasques_pla']++;
                }
            }
            return self::result('pla_rapid', 'Llista de tasques', $counts, [], []);
        }

        $catalogColumn = self::firstColumn($headers, ['tasques', 'nom tasca', 'nombre tarea']);
        if ($catalogColumn !== null) {
            for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
                if (self::cellString($sheet->getCellByColumnAndRow($catalogColumn, $row)) !== '') {
                    $counts['tasques_cataleg']++;
                }
            }
            return self::result('tasques_cataleg', 'Catàleg de tasques', $counts, [], []);
        }

        return self::result(
            'unknown',
            'Format no reconegut',
            $counts,
            [],
            ['No s\'ha pogut identificar el format. Revisa els noms de les fulles i les capçaleres.']
        );
    }

    private static function result(
        string $type,
        string $label,
        array $counts,
        array $warnings,
        array $errors,
        array $extra = []
    ): array {
        return array_merge([
            'type' => $type,
            'label' => $label,
            'counts' => $counts,
            'total_rows' => array_sum($counts),
            'warnings' => $warnings,
            'errors' => $errors,
            'inventory_format' => null,
            'inventory_header_row' => null,
            'inventory_columns' => [],
            'new_system_codes' => [],
        ], $extra);
    }

    private static function emptyCounts(): array
    {
        return array_fill_keys(self::COUNT_KEYS, 0);
    }

    private static function headersByName(Worksheet $sheet, int $row): array
    {
        $headers = [];
        $highestColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
        for ($column = 1; $column <= $highestColumn; $column++) {
            $value = self::cellString($sheet->getCellByColumnAndRow($column, $row));
            if ($value !== '') {
                $headers[self::normalize($value)] = $column;
            }
        }
        return $headers;
    }

    private static function resolveColumns(array $headers, array $aliasesByField): array
    {
        $columns = [];
        foreach ($aliasesByField as $field => $aliases) {
            $columns[$field] = self::firstColumn($headers, $aliases);
        }
        return $columns;
    }

    private static function firstColumn(array $headers, array $aliases): ?int
    {
        foreach ($aliases as $alias) {
            $key = self::normalize($alias);
            if (isset($headers[$key])) {
                return (int)$headers[$key];
            }
        }
        return null;
    }

    private static function rowHasHeaders(Worksheet $sheet, int $row, array $required): bool
    {
        $headers = self::headersByName($sheet, $row);
        foreach ($required as $header) {
            if (self::firstColumn($headers, [$header]) === null) {
                return false;
            }
        }
        return true;
    }

    private static function cellString($cell): string
    {
        try {
            $value = $cell->getCalculatedValue();
        } catch (\Throwable $e) {
            $value = $cell->getValue();
        }

        if ($value === null || is_bool($value)) {
            return '';
        }
        $value = trim((string)$value);
        return $value !== '' && $value[0] === '#' ? '' : $value;
    }

    private static function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ]);
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value) ?? $value;
        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
