<?php

namespace App\Services;

use App\Models\Database;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Genera la plantilla Excel del onboarding, adaptada als mòduls actius
 * de la instal·lació. Una fulla per mòdul + INSTRUCCIONS + TASQUES.
 *
 * Les files d'exemple comencen per "EXEMPLE — " a la primera columna
 * i la importació les ignora automàticament.
 */
class PlantillaBuilder
{
    public const EXAMPLE_PREFIX = 'EXEMPLE — ';

    public const SHEET_ESPAIS = 'ESPAIS';
    public const SHEET_TORNS = 'TORNS';
    public const SHEET_EQUIPS = 'EQUIPS';
    public const SHEET_TASQUES = 'TASQUES';

    public static function isExampleRow(string $firstCell): bool
    {
        return str_starts_with(trim($firstCell), 'EXEMPLE');
    }

    /** @param array $moduls Mòduls actius, p.ex. ['espais','torns'] */
    public static function build(array $moduls, string $instalacioNom): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setTitle('Plantilla GMAO — ' . $instalacioNom);

        $periodicitats = self::periodicitatsDisponibles();

        self::buildInstruccions($spreadsheet->getActiveSheet(), $moduls, $instalacioNom, $periodicitats);

        if (in_array('espais', $moduls, true)) {
            self::buildEspais($spreadsheet->createSheet());
        }
        if (in_array('torns', $moduls, true)) {
            self::buildTorns($spreadsheet->createSheet());
        }
        if (in_array('equips', $moduls, true)) {
            self::buildEquips($spreadsheet->createSheet());
        }

        self::buildTasques($spreadsheet->createSheet(), $moduls, $periodicitats);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private static function periodicitatsDisponibles(): array
    {
        try {
            $rows = Database::getInstance()->query('SELECT nom FROM periodicitats ORDER BY dies_interval')->fetchAll();
            return array_map(static fn(array $r) => (string)$r['nom'], $rows);
        } catch (\Throwable $e) {
            return ['Diària', 'Setmanal', 'Mensual', 'Trimestral', 'Anual'];
        }
    }

    private static function buildInstruccions(Worksheet $sheet, array $moduls, string $instalacioNom, array $periodicitats): void
    {
        $sheet->setTitle('INSTRUCCIONS');
        $sheet->getColumnDimension('A')->setWidth(110);

        $lines = [
            ['Plantilla de configuració — ' . $instalacioNom, true],
            ['', false],
            ['COM FUNCIONA', true],
            ['1. Aquesta plantilla té una fulla per cada bloc que has activat per a la teva instal·lació.', false],
            ['2. Omple cada fulla amb les teves dades, una fila per element. No canviïs els noms de les fulles ni de les capçaleres.', false],
            ['3. Les files que comencen per "EXEMPLE" són mostres de com omplir-ho: pots esborrar-les o deixar-les, la importació les ignora.', false],
            ['4. Quan acabis, desa el fitxer i puja\'l des de la pantalla d\'importació (tipus "Plantilla de configuració").', false],
            ['5. Abans d\'aplicar res veuràs una previsualització per confirmar que tot quadra.', false],
            ['', false],
        ];

        if (in_array('espais', $moduls, true)) {
            $lines = array_merge($lines, [
                ['FULLA ESPAIS — Les zones de la instal·lació', true],
                ['Què has de recollir: una llista de les zones on es fan tasques (sales, plantes, piscines, vestuaris...).', false],
                ['ESPAI és obligatori. CODI i PLANTA són opcionals però ajuden a identificar la zona.', false],
                ['', false],
            ]);
        }

        if (in_array('torns', $moduls, true)) {
            $lines = array_merge($lines, [
                ['FULLA TORNS — Els horaris de treball', true],
                ['Què has de recollir: els torns en què s\'organitza el personal (p.ex. Matí, Tarda, Cap de Setmana).', false],
                ['NOM és obligatori. DIES s\'omple amb els codis separats per comes: dll, dm, dx, dj, dv, ds, dg.', false],
                ['HORA INICI i HORA FI són opcionals, en format 08:00.', false],
                ['', false],
            ]);
        }

        if (in_array('equips', $moduls, true)) {
            $lines = array_merge($lines, [
                ['FULLA EQUIPS — La maquinària i instal·lacions tècniques', true],
                ['Què has de recollir: la llista d\'equips que reben manteniment (calderes, bombes, climatitzadors...).', false],
                ['Consell: fes una volta per la instal·lació amb el mòbil i fotografia les plaques de característiques.', false],
                ['NOM EQUIP és obligatori. CODI és un identificador curt si en feu servir (p.ex. ACS-CAL-1).', false],
                ['', false],
            ]);
        }

        $tasquesCols = self::tasquesColumns($moduls);
        $lines = array_merge($lines, [
            ['FULLA TASQUES — El pla de manteniment (la més important)', true],
            ['Què has de recollir: totes les tasques periòdiques que es fan (o s\'haurien de fer), amb quina freqüència.', false],
            ['Bones fonts: contractes de manteniment, llibres de manteniment, normativa aplicable, i el coneixement del personal.', false],
            ['TASCA i PERIODICITAT són obligatoris. La resta de columnes (' . implode(', ', array_slice($tasquesCols, 2)) . ') són opcionals.', false],
            ['Si has omplert les altres fulles, escriu els noms exactament igual perquè es vinculin automàticament.', false],
            ['', false],
            ['PERIODICITATS VÀLIDES (escriu-les exactament així):', true],
            [implode(' | ', $periodicitats), false],
        ]);

        foreach ($lines as $i => [$text, $bold]) {
            $cell = 'A' . ($i + 1);
            $sheet->setCellValue($cell, $text);
            $sheet->getStyle($cell)->getAlignment()->setWrapText(true);
            if ($bold) {
                $sheet->getStyle($cell)->getFont()->setBold(true);
            }
        }
    }

    private static function buildEspais(Worksheet $sheet): void
    {
        $sheet->setTitle(self::SHEET_ESPAIS);
        self::fillSheet($sheet, ['ESPAI', 'CODI', 'PLANTA'], [
            [self::EXAMPLE_PREFIX . 'Piscina gran', 'PG', 'Planta -1'],
            [self::EXAMPLE_PREFIX . 'Sala fitness', 'SF', 'Planta 1'],
        ]);
    }

    private static function buildTorns(Worksheet $sheet): void
    {
        $sheet->setTitle(self::SHEET_TORNS);
        self::fillSheet($sheet, ['NOM', 'DIES', 'HORA INICI', 'HORA FI'], [
            [self::EXAMPLE_PREFIX . 'Matí', 'dll,dm,dx,dj,dv', '06:00', '14:00'],
            [self::EXAMPLE_PREFIX . 'Cap de Setmana', 'ds,dg', '08:00', '20:00'],
        ]);
    }

    private static function buildEquips(Worksheet $sheet): void
    {
        $sheet->setTitle(self::SHEET_EQUIPS);
        self::fillSheet($sheet, ['NOM EQUIP', 'CODI', 'MODEL', 'PLANTA', 'EMPRESA MANTENIDORA'], [
            [self::EXAMPLE_PREFIX . 'Caldera ACS principal', 'ACS-CAL-1', 'Viessmann Vitoplex 200', 'Planta -1', 'Tècnics Calor SL'],
            [self::EXAMPLE_PREFIX . 'Bomba recirculació piscina', 'PIS-BOM-1', 'Grundfos NB 65', 'Planta -1', ''],
        ]);
    }

    public static function tasquesColumns(array $moduls): array
    {
        $cols = ['TASCA', 'PERIODICITAT'];
        if (in_array('espais', $moduls, true)) {
            $cols[] = 'ESPAI';
        }
        if (in_array('torns', $moduls, true)) {
            $cols[] = 'TORN';
        }
        if (in_array('equips', $moduls, true)) {
            $cols[] = 'EQUIP';
        }
        $cols[] = 'NORMATIVA';

        return $cols;
    }

    private static function buildTasques(Worksheet $sheet, array $moduls, array $periodicitats): void
    {
        $sheet->setTitle(self::SHEET_TASQUES);
        $cols = self::tasquesColumns($moduls);

        $exemple1 = [self::EXAMPLE_PREFIX . 'Revisió temperatura i clor de l\'aigua', $periodicitats[0] ?? 'Diària'];
        $exemple2 = [self::EXAMPLE_PREFIX . 'Neteja de filtres de climatització', $periodicitats[2] ?? 'Mensual'];
        foreach (array_slice($cols, 2) as $col) {
            $exemple1[] = match ($col) {
                'ESPAI' => 'Piscina gran',
                'TORN' => 'Matí',
                'EQUIP' => '',
                'NORMATIVA' => '',
                default => '',
            };
            $exemple2[] = match ($col) {
                'ESPAI' => 'Sala fitness',
                'TORN' => '',
                'EQUIP' => 'Caldera ACS principal',
                'NORMATIVA' => '',
                default => '',
            };
        }

        self::fillSheet($sheet, $cols, [$exemple1, $exemple2]);
    }

    private static function fillSheet(Worksheet $sheet, array $headers, array $exampleRows): void
    {
        foreach ($headers as $i => $header) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $header);
            $sheet->getColumnDimensionByColumn($i + 1)->setWidth(max(18, mb_strlen($header) + 6));
        }
        $sheet->getStyle('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1')
            ->getFont()->setBold(true);

        foreach ($exampleRows as $r => $row) {
            foreach ($row as $c => $value) {
                $sheet->setCellValueByColumnAndRow($c + 1, $r + 2, $value);
            }
        }
    }
}
