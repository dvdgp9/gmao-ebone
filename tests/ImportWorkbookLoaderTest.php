<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\ImportWorkbookInspector;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$failures = [];
$assertSame = static function (mixed $expected, mixed $actual, string $message) use (&$failures): void {
    if ($expected !== $actual) {
        $failures[] = $message . ' Expected: ' . json_encode($expected) . '; actual: ' . json_encode($actual);
    }
};
$assertTrue = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$root = dirname(__DIR__);

// ---------------------------------------------------------------
// 1. Les cel·les buides amb format no es carreguen (és el que omplia la memòria)
// ---------------------------------------------------------------
$tmp = sys_get_temp_dir() . '/gmao-loader-' . getmypid() . '.xlsx';
$book = new Spreadsheet();
$sheet = $book->getActiveSheet();
$sheet->setTitle('BD TASQUES');
$sheet->fromArray([['CODI', 'TIPUS', 'EQUIP', 'TASQUES'], ['AE-1', 'P', 'Bomba', 'Revisar pressió']], null, 'A1');
foreach (range('A', 'T') as $columna) {
    for ($fila = 3; $fila <= 500; $fila++) {
        $sheet->getStyle("{$columna}{$fila}")->getFont()->setBold(true); // cel·la buida però amb format
    }
}
(new Xlsx($book))->save($tmp);
$book->disconnectWorksheets();

$complet = IOFactory::load($tmp);
$lleuger = ImportWorkbookInspector::loadWorkbook($tmp);
$cellesCompletes = count($complet->getActiveSheet()->getCellCollection()->getCoordinates());
$cellesLleugeres = count($lleuger->getActiveSheet()->getCellCollection()->getCoordinates());
$assertTrue($cellesCompletes > 9000, 'El fitxer de prova ha de tenir milers de cel·les buides amb format.');
$assertSame(8, $cellesLleugeres, 'Només s\'han de carregar les cel·les amb valor.');
$assertSame('Revisar pressió', $lleuger->getActiveSheet()->getCell('D2')->getValue(), 'Els valors es llegeixen igual.');
$assertSame(
    ImportWorkbookInspector::inspect($complet, []),
    ImportWorkbookInspector::inspect($lleuger, []),
    'La detecció ha de ser idèntica amb i sense cel·les buides.'
);
$complet->disconnectWorksheets();
$lleuger->disconnectWorksheets();
@unlink($tmp);

// ---------------------------------------------------------------
// 2. Excel clàssic del repositori: mateixa detecció
// ---------------------------------------------------------------
$classicPath = $root . '/6970815c3ed12_1768980828.xlsx';
if (is_file($classicPath)) {
    // Un llibre cada vegada: carregat sencer no hi cabrien tots dos al límit de la CLI.
    $complet = IOFactory::load($classicPath);
    $esperat = ImportWorkbookInspector::inspect($complet, []);
    $complet->disconnectWorksheets();
    unset($complet);
    gc_collect_cycles();

    $lleuger = ImportWorkbookInspector::loadWorkbook($classicPath);
    $obtingut = ImportWorkbookInspector::inspect($lleuger, []);
    $lleuger->disconnectWorksheets();
    unset($lleuger);

    unset($esperat['total_rows'], $obtingut['total_rows']);
    $assertSame($esperat, $obtingut, 'L\'Excel complet clàssic s\'ha de detectar igual.');
}

// ---------------------------------------------------------------
// 3. Excel real que petava a producció (180 MB): ara ha de cabre amb marge
// ---------------------------------------------------------------
$candidats = array_filter([
    getenv('GMAO_EXCEL_GRAN') ?: null,
    '/Users/dvdgp/Downloads/GMAO Reverter.xlsx',
    '/Users/dvdgp/Downloads/GMAO CEM REVERTER.xlsx',
]);
$reverterPath = array_values(array_filter($candidats, 'is_file'))[0] ?? null;
if ($reverterPath === null) {
    echo "ImportWorkbookLoaderTest: no s'ha trobat l'Excel gran (GMAO_EXCEL_GRAN); prova de memòria omesa.\n";
} else {
    $script = tempnam(sys_get_temp_dir(), 'gmao-mem') . '.php';
    file_put_contents($script, '<?php
        require ' . var_export($root . '/vendor/autoload.php', true) . ';
        $book = App\Services\ImportWorkbookInspector::loadWorkbook(' . var_export($reverterPath, true) . ');
        $res = App\Services\ImportWorkbookInspector::inspect($book, []);
        echo json_encode(["pic" => memory_get_peak_usage(true), "type" => $res["type"]]);
    ');
    // Mateix límit que el servidor.
    exec(escapeshellarg(PHP_BINARY) . ' -d memory_limit=180M ' . escapeshellarg($script) . ' 2>&1', $sortida, $codi);
    @unlink($script);
    $dades = json_decode(implode('', $sortida), true);
    $assertSame(0, $codi, 'L\'Excel REVERTER ha de carregar-se dins de 180 MB. Sortida: ' . implode(' ', $sortida));
    $assertTrue(($dades['pic'] ?? PHP_INT_MAX) < 120 * 1048576, 'Pic de memòria massa alt: ' . round(($dades['pic'] ?? 0) / 1048576) . ' MB.');
    echo 'ImportWorkbookLoaderTest: ' . basename($reverterPath) . ' → ' . round(($dades['pic'] ?? 0) / 1048576) . " MB, detectat com a «" . ($dades['type'] ?? '?') . "».\n";
}

// ---------------------------------------------------------------
// 4. Tots els punts d'entrada fan servir la càrrega lleugera
// ---------------------------------------------------------------
$import = file_get_contents($root . '/app/Controllers/ImportController.php');
$usuaris = file_get_contents($root . '/app/Controllers/UsuariImportController.php');
$assertSame(false, str_contains($import, 'IOFactory::load('), 'ImportController no ha d\'obrir l\'Excel sencer.');
$assertSame(2, substr_count($import, 'ImportWorkbookInspector::loadWorkbook('), 'La pujada i la confirmació han de fer servir la càrrega lleugera.');
$assertTrue(str_contains($usuaris, 'setReadEmptyCells(false)'), 'La importació d\'usuaris tampoc ha de carregar cel·les buides.');

if ($failures !== []) {
    fwrite(STDERR, "ImportWorkbookLoaderTest failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "ImportWorkbookLoaderTest passed\n";
