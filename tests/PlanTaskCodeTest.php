<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Controllers\ImportController;
use App\Services\TaskMatcher;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

$failures = [];
$assertContains = static function (string $needle, string $haystack, string $message) use (&$failures): void {
    if (!str_contains($haystack, $needle)) {
        $failures[] = $message;
    }
};
$assertSame = static function (mixed $expected, mixed $actual, string $message) use (&$failures): void {
    if ($expected !== $actual) {
        $failures[] = $message . ' Expected: ' . json_encode($expected) . '; actual: ' . json_encode($actual);
    }
};

$root = dirname(__DIR__);
$schema = file_get_contents($root . '/database/schema.sql');
$migrationPath = $root . '/database/migrations/2026-09-03_tasques_pla_codi.sql';
$importController = file_get_contents($root . '/app/Controllers/ImportController.php');
$planController = file_get_contents($root . '/app/Controllers/TascaPlaController.php');
$planModel = file_get_contents($root . '/app/Models/TascaPla.php');
$planForm = file_get_contents($root . '/app/views/pla/form.php');

$assertContains('`codi` VARCHAR(50) DEFAULT NULL', $schema, 'El esquema debe guardar un código propio en tasques_pla.');
$assertSame(true, is_file($migrationPath), 'Debe existir una migración idempotente para el código del plan.');
if (is_file($migrationPath)) {
    $migration = file_get_contents($migrationPath);
    $assertContains("COLUMN_NAME = 'codi'", $migration, 'La migración debe poder ejecutarse sin duplicar la columna.');
    $assertContains('ALTER TABLE `tasques_pla` ADD COLUMN `codi`', $migration, 'La migración debe añadir el código al plan.');
}
$assertContains('$codiTasca = mb_substr($this->cellString($sheet->getCell("A{$row}"))', $importController, 'La importación completa debe leer codi tasca desde la columna A.');
$assertContains('UPDATE tasques_pla SET codi = ?', $importController, 'La reimportación debe recuperar códigos ausentes.');
$assertContains("'codi' => mb_substr(trim(\$this->post('codi', ''))", $planController, 'El CRUD debe guardar el código del plan.');
$assertContains('name="codi"', $planForm, 'El formulario debe permitir editar el código del plan.');
$assertContains("COALESCE(NULLIF(tp.codi, \\'\\'), tc.codi) AS tasca_codi", $planModel, 'Las vistas deben priorizar el código propio del plan.');
$assertContains('tp.codi LIKE ?', $planModel, 'El buscador debe encontrar el código propio del plan.');

$workbookPath = '/Users/dvdgp/Downloads/Copia de Plantilla GMAO.xlsx';
if (is_file($workbookPath)) {
    $workbook = IOFactory::load($workbookPath);
    $sheet = $workbook->getSheetByName('TASQUES PLA_M');
    $codes = [];
    $taskRows = 0;
    for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
        $task = trim((string)$sheet->getCell("B{$row}")->getCalculatedValue());
        if ($task === '') {
            continue;
        }
        $taskRows++;
        $code = trim((string)$sheet->getCell("A{$row}")->getCalculatedValue());
        if ($code !== '') {
            $codes[$code] = true;
        }
    }
    $assertSame(53, $taskRows, 'El archivo real debe conservar sus 53 filas de plan.');
    $assertSame(53, count($codes), 'Los 53 códigos del plan real deben ser únicos y recuperables.');
}

// Prova de comportament sense tocar MySQL: una reimportació recupera el codi
// d'una fila existent i una fila nova desa el seu propi codi.
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->exec('CREATE TABLE tasques_cataleg (id INTEGER PRIMARY KEY, nom TEXT NOT NULL)');
$db->exec('CREATE TABLE tasques_pla (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    instalacio_id INTEGER NOT NULL,
    codi TEXT NULL,
    tasca_cataleg_id INTEGER NOT NULL,
    equip_id INTEGER NULL,
    espai_id INTEGER NULL,
    torn_id INTEGER NULL,
    periodicitat_id INTEGER NULL,
    observacions TEXT NULL,
    data_darrera_realitzacio TEXT NULL,
    data_propera_realitzacio TEXT NULL,
    en_curs INTEGER NOT NULL,
    comentaris TEXT NULL
)');
$db->exec("INSERT INTO tasques_cataleg (id, nom) VALUES (1, 'Revisar filtres'), (2, 'Netejar sala')");
$db->exec('INSERT INTO tasques_pla (id, instalacio_id, codi, tasca_cataleg_id, espai_id, torn_id, en_curs) VALUES (10, 1, NULL, 1, 5, 9, 1)');

$sheetBook = new Spreadsheet();
$sheet = $sheetBook->getActiveSheet();
$sheet->setCellValue('A2', '7')->setCellValue('B2', 'Revisar filtres')->setCellValue('D2', 'Sala tècnica')->setCellValue('J2', 'diària')->setCellValue('P2', 'Matí');
$sheet->setCellValue('A3', '8')->setCellValue('B3', 'Netejar sala')->setCellValue('D3', 'Sala tècnica')->setCellValue('J3', 'diària')->setCellValue('P3', 'Tarda');

$controller = (new ReflectionClass(ImportController::class))->newInstanceWithoutConstructor();
$method = new ReflectionMethod(ImportController::class, 'importCompletePla');
$method->setAccessible(true);
$catalogMap = [
    TaskMatcher::normalize('Revisar filtres') => 1,
    TaskMatcher::normalize('Netejar sala') => 2,
];
$result = $method->invoke(
    $controller,
    $db,
    $sheet,
    1,
    ['sala tècnica' => 5],
    ['matí' => 9, 'tarda' => 10],
    $catalogMap,
    ['diària' => 3]
);
$savedCodes = $db->query('SELECT tasca_cataleg_id, codi FROM tasques_pla ORDER BY tasca_cataleg_id')->fetchAll(PDO::FETCH_KEY_PAIR);
$assertSame(2, $result['imported'], 'Debe contar tanto el código recuperado como la nueva fila importada.');
$assertSame('7', $savedCodes[1] ?? null, 'La reimportación debe rellenar el código que estaba vacío.');
$assertSame('8', $savedCodes[2] ?? null, 'Una fila nueva debe guardar su código propio.');

if ($failures !== []) {
    fwrite(STDERR, "PlanTaskCodeTest failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PlanTaskCodeTest passed\n";
