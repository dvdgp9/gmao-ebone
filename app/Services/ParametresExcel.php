<?php

namespace App\Services;

use App\Models\MesuraPiscina;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel de «Paràmetres piscina» per a inspeccions: un full per vas, com el full de paràmetres
 * en paper (una fila per dia; clor i pH a cada moment; la resta un cop al dia), amb els valors
 * fora de norma en groc i els de tancar el vas en vermell.
 */
class ParametresExcel
{
    public const GROC = 'FEF3C7';
    public const VERMELL = 'FECACA';
    private const GRIS = 'F3F4F6';
    private const FILA_CAPCALERA = 5;

    /**
     * @param array $files    Files de MesuraPiscina::agrupar()
     * @param array $info     ['instalacio' => nom, 'desde' => Y-m-d, 'fins' => Y-m-d]
     * @param bool  $ambTecnic Mostra la columna «Tècnic» (els tècnics no la veuen a la pantalla)
     */
    public static function build(array $files, array $info, bool $ambTecnic = true): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setTitle('Paràmetres piscina')->setCreator('GMAO');
        $spreadsheet->removeSheetByIndex(0);

        // Un full per vas, amb els dies en ordre ascendent (com el full en paper).
        $perVas = [];
        foreach ($files as $f) {
            $perVas[$f['tasca_pla_id']][] = $f;
        }
        uasort($perVas, static fn(array $a, array $b): int => strcasecmp($a[0]['vas'], $b[0]['vas']));

        $noms = [];
        foreach ($perVas as $filesVas) {
            usort($filesVas, static fn(array $a, array $b): int => strcmp($a['data'], $b['data']));
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(self::nomFull($filesVas[0]['vas'], $noms));
            self::omplirFull($sheet, $filesVas, $info, $ambTecnic);
        }

        if (!$perVas) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle('Sense mesures');
            $sheet->setCellValue('A1', 'No hi ha mesures de paràmetres en aquestes dates.');
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /** Columnes del full, en ordre: [camp, moment|null, títol]. */
    public static function columnes(bool $ambTecnic = true): array
    {
        $cols = [['data', null, 'Data']];
        foreach (MesuraPiscina::campsCadaMoment() as $camp) {
            $n = 0;
            foreach (MesuraPiscina::MOMENTS as $moment => $nomMoment) {
                $cols[] = [$camp, $moment, (string)++$n];
            }
        }
        foreach (MesuraPiscina::campsDiaris() as $camp) {
            $cols[] = [$camp, null, MesuraPiscina::PARAMETRES[$camp]['nom']];
        }
        if ($ambTecnic) {
            $cols[] = ['tecnic', null, 'Tècnic'];
        }
        $cols[] = ['observacions', null, 'Observacions'];

        return $cols;
    }

    private static function omplirFull(Worksheet $sheet, array $files, array $info, bool $ambTecnic): void
    {
        $p = MesuraPiscina::PARAMETRES;
        $cols = self::columnes($ambTecnic);
        $ultima = Coordinate::stringFromColumnIndex(count($cols));
        $vas = $files[0]['vas'] . ($files[0]['tasca_codi'] ? ' · ' . $files[0]['tasca_codi'] : '');

        // Capçalera del document
        $sheet->setCellValue('A1', 'Control de paràmetres de l\'aigua — ' . $vas);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A2', 'Instal·lació: ' . ($info['instalacio'] ?? '') . '   ·   Període: '
            . self::data($info['desde']) . ' – ' . self::data($info['fins'])
            . '   ·   Descarregat el ' . date('d/m/Y H:i'));
        $sheet->getStyle('A2')->getFont()->setSize(9)->getColor()->setRGB('6B7280');
        // Llegenda: moments i colors
        $sheet->setCellValue('A3', 'Groc');
        $sheet->setCellValue('B3', 'Fora de norma (R.D. 742/2013)');
        $sheet->setCellValue('A4', 'Vermell');
        $sheet->setCellValue('B4', 'Cal tancar el vas');
        self::pintar($sheet, 'A3', self::GROC);
        self::pintar($sheet, 'A4', self::VERMELL);
        $sheet->setCellValue('K3', 'Clor i pH: 1 = inici de jornada · 2 = manteniment 14 h · 3 = final de jornada');
        $sheet->setCellValue('K4', 'La resta de paràmetres, un cop al dia');
        $sheet->getStyle('A3:K4')->getFont()->setSize(9);
        foreach (["A1:{$ultima}1", "A2:{$ultima}2", 'B3:J3', 'B4:J4', "K3:{$ultima}3", "K4:{$ultima}4"] as $rang) {
            $sheet->mergeCells($rang);
        }
        $sheet->getRowDimension(1)->setRowHeight(22);

        // Dues files de capçalera: nom del paràmetre (amb norma) i 1/2/3 per als moments
        $f1 = self::FILA_CAPCALERA;
        $f2 = $f1 + 1;
        $i = 1;
        while ($i <= count($cols)) {
            [$camp, $moment, $titol] = $cols[$i - 1];
            $c = Coordinate::stringFromColumnIndex($i);
            if ($moment !== null) {
                $fi = Coordinate::stringFromColumnIndex($i + 2);
                $sheet->setCellValue($c . $f1, self::titolParametre($camp));
                $sheet->mergeCells("{$c}{$f1}:{$fi}{$f1}");
                for ($k = 0; $k < 3; $k++) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + $k) . $f2, $cols[$i - 1 + $k][2]);
                }
                $i += 3;
                continue;
            }
            $sheet->setCellValue($c . $f1, isset($p[$camp]) ? self::titolParametre($camp) : $titol);
            $sheet->mergeCells("{$c}{$f1}:{$c}{$f2}");
            $i++;
        }
        $capcalera = $sheet->getStyle("A{$f1}:{$ultima}{$f2}");
        $capcalera->getFont()->setBold(true)->setSize(9);
        $capcalera->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $capcalera->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::GRIS);
        $sheet->getRowDimension($f1)->setRowHeight(42);

        // Una fila per dia
        $fila = $f2 + 1;
        foreach ($files as $f) {
            $estatsMoment = [];
            foreach ($f['moments'] as $moment => $m) {
                $estatsMoment[$moment] = MesuraPiscina::avaluar($m);
            }
            foreach ($cols as $idx => [$camp, $moment, $titol]) {
                $cel = Coordinate::stringFromColumnIndex($idx + 1) . $fila;
                if ($camp === 'data') {
                    $sheet->setCellValue($cel, self::data($f['data']));
                    continue;
                }
                if ($camp === 'tecnic') {
                    $sheet->setCellValue($cel, implode(', ', $f['tecnics']));
                    continue;
                }
                if ($camp === 'observacions') {
                    $sheet->setCellValue($cel, implode("\n", $f['observacions']));
                    continue;
                }
                $valor = $moment !== null ? ($f['moments'][$moment][$camp] ?? null) : $f['diaris'][$camp];
                if ($valor === null || $valor === '') {
                    continue;
                }
                $sheet->setCellValue($cel, (float)$valor);
                $sheet->getStyle($cel)->getNumberFormat()->setFormatCode(self::formatNumero($camp));
                $estat = $moment !== null ? ($estatsMoment[$moment][$camp] ?? null) : ($f['estatsDiaris'][$camp] ?? null);
                if ($estat === MesuraPiscina::TANCAR) {
                    self::pintar($sheet, $cel, self::VERMELL);
                    $sheet->getStyle($cel)->getFont()->setBold(true);
                } elseif ($estat === MesuraPiscina::FORA) {
                    self::pintar($sheet, $cel, self::GROC);
                }
            }
            $fila++;
        }

        // Format general
        $darrera = $fila - 1;
        $sheet->getStyle("A{$f1}:{$ultima}{$darrera}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');
        if ($darrera > $f2) {
            $sheet->getStyle("A" . ($f2 + 1) . ":{$ultima}{$darrera}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle("{$ultima}" . ($f2 + 1) . ":{$ultima}{$darrera}")->getAlignment()->setWrapText(true);
        }
        $sheet->getColumnDimension('A')->setWidth(11);
        foreach ($cols as $idx => [$camp, $moment]) {
            if ($idx === 0) {
                continue;
            }
            $amplada = match ($camp) {
                'observacions' => 40,
                'tecnic' => 22,
                'recirculacio', 'aigua_nova' => 14,
                default => $moment !== null ? 6.5 : 11,
            };
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($idx + 1))->setWidth($amplada);
        }
        $sheet->freezePane('B' . ($f2 + 1));

        // Impressió: horitzontal, ajustat a l'amplada d'un full, capçaleres a cada pàgina
        $setup = $sheet->getPageSetup();
        $setup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4);
        $setup->setFitToWidth(1)->setFitToHeight(0);
        $setup->setRowsToRepeatAtTopByStartAndEnd($f1, $f2);
        $sheet->getPageMargins()->setLeft(0.4)->setRight(0.4)->setTop(0.5)->setBottom(0.5);
        $sheet->getHeaderFooter()->setOddFooter('&L' . self::netejarCapcalera($vas) . '&RPàgina &P de &N');
    }

    private static function titolParametre(string $camp): string
    {
        $p = MesuraPiscina::PARAMETRES[$camp];
        $titol = $p['nom'] . ($p['unitat'] ? ' (' . $p['unitat'] . ')' : '');

        return $p['norma'] ? $titol . "\n" . $p['norma'] : $titol;
    }

    private static function formatNumero(string $camp): string
    {
        return in_array($camp, ['co2_interior', 'co2_exterior', 'humitat', 'recirculacio', 'aigua_nova'], true) ? '#,##0' : '0.0#';
    }

    private static function pintar(Worksheet $sheet, string $cel, string $rgb): void
    {
        $sheet->getStyle($cel)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);
    }

    private static function data(?string $data): string
    {
        return $data ? date('d/m/Y', strtotime($data)) : '';
    }

    /** Nom de pestanya vàlid per a Excel (31 caràcters, sense []:*?/\) i únic. */
    private static function nomFull(string $vas, array &$usats): string
    {
        $base = trim(preg_replace('/[\[\]:*?\/\\\\]+/u', ' ', $vas)) ?: 'Vas';
        $base = mb_substr($base, 0, 31);
        $nom = $base;
        $n = 2;
        while (in_array(mb_strtolower($nom), $usats, true)) {
            $sufix = ' (' . $n++ . ')';
            $nom = mb_substr($base, 0, 31 - mb_strlen($sufix)) . $sufix;
        }
        $usats[] = mb_strtolower($nom);

        return $nom;
    }

    private static function netejarCapcalera(string $text): string
    {
        return str_replace('&', '&&', $text);
    }
}
