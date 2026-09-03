<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\ImportWorkbookInspector;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

$failures = [];

$assertSame = static function (mixed $expected, mixed $actual, string $message) use (&$failures): void {
    if ($expected !== $actual) {
        $failures[] = $message . ' Expected: ' . json_encode($expected) . '; actual: ' . json_encode($actual);
    }
};

$assertContains = static function (mixed $needle, array $haystack, string $message) use (&$failures): void {
    if (!in_array($needle, $haystack, true)) {
        $failures[] = $message . ' Missing: ' . json_encode($needle);
    }
};

$legacyPath = '/Users/dvdgp/Downloads/Copia de Plantilla GMAO.xlsx';
if (is_file($legacyPath)) {
    $legacy = IOFactory::load($legacyPath);
    $knownSystems = ['OCA', 'AE', 'EEXT', 'ACS', 'HP', 'BT', 'CL', 'NE', 'LE', 'AL', 'WLL', 'MQ', 'AFCH', 'CI', 'GN', 'EL', 'SE', 'PAV', 'EE'];
    $inspection = ImportWorkbookInspector::inspect($legacy, $knownSystems);

    $assertSame('completa_instalacio', $inspection['type'], 'Debe detectar el Excel completo legado.');
    $assertSame('simple', $inspection['inventory_format'], 'Debe detectar INVENTARI por cabeceras en la fila 2.');
    $assertSame(2, $inspection['inventory_header_row'], 'La cabecera del INVENTARI alternativo está en la fila 2.');
    $assertSame(82, $inspection['counts']['espais'], 'Debe contar los espacios importables.');
    $assertSame(4, $inspection['counts']['equips'], 'Debe contar los cuatro equipos reales, no la cabecera.');
    $assertSame(636, $inspection['counts']['tasques_cataleg'], 'Debe contar las tareas de catálogo.');
    $assertSame(53, $inspection['counts']['tasques_pla'], 'Debe contar las tareas del plan.');
    $assertSame(0, $inspection['counts']['registre'], 'El registro vacío debe contabilizarse como cero.');
    $assertContains('AV', $inspection['new_system_codes'], 'Debe avisar de sistemas que aún no existen.');
    $assertSame([], $inspection['errors'], 'El Excel legado compatible no debe quedar bloqueado.');

    $inventoryRows = ImportWorkbookInspector::extractSimpleInventoryRows($legacy->getSheetByName('INVENTARI'));
    $assertSame(4, count($inventoryRows), 'Debe extraer cuatro equipos del INVENTARI alternativo.');
    $assertSame('CL - DESHUMECTADORA  (Piscina Gran)', $inventoryRows[0]['nom_equip'], 'Debe mapear EQUIP como nombre.');
    $assertSame('CIATESA - BCP  440', $inventoryRows[0]['model'], 'Debe mapear MODEL sin alterar el valor.');
    $legacy->disconnectWorksheets();
    unset($legacy);
}

$classicPath = dirname(__DIR__) . '/6970815c3ed12_1768980828.xlsx';
if (is_file($classicPath)) {
    $classic = IOFactory::load($classicPath);
    $inspection = ImportWorkbookInspector::inspect($classic, []);
    $assertSame('completa_instalacio', $inspection['type'], 'Debe conservar la compatibilidad con el Excel completo clásico.');
    $assertSame('classic', $inspection['inventory_format'], 'Debe reconocer el INVENTARI clásico.');
    $assertSame([], $inspection['errors'], 'El Excel completo clásico no debe quedar bloqueado.');
    $classic->disconnectWorksheets();
    unset($classic);
}

$template = new Spreadsheet();
$template->getActiveSheet()->setTitle('INSTRUCCIONS');
$espais = $template->createSheet();
$espais->setTitle('ESPAIS');
$espais->fromArray([
    ['ESPAI', 'CODI', 'PLANTA'],
    ['EXEMPLE — Piscina', 'PIS', 'P-1'],
    ['Sala tècnica', 'ST', 'P-1'],
]);
$tasques = $template->createSheet();
$tasques->setTitle('TASQUES');
$tasques->fromArray([
    ['TASCA', 'PERIODICITAT', 'ESPAI'],
    ['Revisar bombes', 'mensual', 'Sala tècnica'],
]);
$inspection = ImportWorkbookInspector::inspect($template, []);
$assertSame('plantilla', $inspection['type'], 'Debe detectar la plantilla modular actual.');
$assertSame(1, $inspection['counts']['espais'], 'Debe ignorar filas de ejemplo.');
$assertSame(1, $inspection['counts']['tasques_pla'], 'Debe contar las tareas de la plantilla.');
$assertSame([], $inspection['errors'], 'La plantilla válida no debe quedar bloqueada.');

$quick = new Spreadsheet();
$quick->getActiveSheet()->fromArray([
    ['Tarea', 'Periodicidad'],
    ['Revisar filtros', 'mensual'],
]);
$inspection = ImportWorkbookInspector::inspect($quick, []);
$assertSame('pla_rapid', $inspection['type'], 'Debe detectar una lista sencilla de tareas.');
$assertSame(1, $inspection['counts']['tasques_pla'], 'Debe contar el plan sencillo.');

$invalidTemplate = new Spreadsheet();
$invalidTemplate->getActiveSheet()->setTitle('INSTRUCCIONS');
$invalidTasks = $invalidTemplate->createSheet();
$invalidTasks->setTitle('TASQUES');
$invalidTasks->fromArray([
    ['DESCRIPCIÓ', 'QUAN'],
    ['Revisar filtres', 'mensual'],
]);
$inspection = ImportWorkbookInspector::inspect($invalidTemplate, []);
$assertSame('plantilla', $inspection['type'], 'Debe reconocer que el archivo intenta ser una plantilla.');
$assertSame(false, $inspection['errors'] === [], 'Debe bloquear una plantilla con cabeceras incorrectas.');

if ($failures !== []) {
    fwrite(STDERR, "ImportWorkbookInspectorTest failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "ImportWorkbookInspectorTest passed\n";
