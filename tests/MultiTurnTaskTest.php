<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Controllers\TascaPlaController;
use App\Models\TascaPla;

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
$migrationPath = $root . '/database/migrations/2026-09-11_tasca_pla_torns.sql';
$planModel = file_get_contents($root . '/app/Models/TascaPla.php');
$planController = file_get_contents($root . '/app/Controllers/TascaPlaController.php');
$planForm = file_get_contents($root . '/app/views/pla/form.php');
$registerModel = file_get_contents($root . '/app/Models/RegistreTasca.php');

$assertContains('CREATE TABLE `tasca_pla_torn`', $schema, 'El esquema debe incluir la relación de tareas y turnos.');
$assertSame(true, is_file($migrationPath), 'Debe existir una migración para instalaciones actuales.');
if (is_file($migrationPath)) {
    $migration = file_get_contents($migrationPath);
    $assertContains('CREATE TABLE IF NOT EXISTS `tasca_pla_torn`', $migration, 'La migración debe ser idempotente.');
    $assertContains('INSERT IGNORE INTO `tasca_pla_torn`', $migration, 'La migración debe conservar los turnos actuales.');
}
$assertContains('name="torn_ids[]"', $planForm, 'El formulario debe enviar varios turnos.');
$assertContains('Tots els torns', $planForm, 'El formulario debe permitir seleccionar todos los turnos.');
$assertContains('Una única execució compartida', $planForm, 'La interfaz debe explicar el comportamiento compartido.');
$assertContains('TascaPla::syncTorns', $planController, 'El alta y la edición deben sincronizar la selección múltiple.');
$assertContains('GROUP_CONCAT(t_multi.nom', $planModel, 'Las vistas deben mostrar todos los turnos asignados.');
$assertContains('EXISTS (SELECT 1 FROM tasca_pla_torn tpt_filter', $planModel, 'Las vistas operativas deben filtrar por cualquiera de los turnos.');
$assertContains('EXISTS (SELECT 1 FROM tasca_pla_torn tpt_filter', $registerModel, 'El registro debe filtrar tareas compartidas por turno.');

$_POST = ['torn_ids' => ['7', '3', '7', '0', 'text']];
$controller = (new ReflectionClass(TascaPlaController::class))->newInstanceWithoutConstructor();
$method = new ReflectionMethod(TascaPlaController::class, 'getTornIds');
$method->setAccessible(true);
$assertSame([7, 3], $method->invoke($controller), 'El formulario debe normalizar y deduplicar los turnos seleccionados.');
$_POST = [];

$filterMethod = new ReflectionMethod(TascaPla::class, 'tornFilterClause');
$filterMethod->setAccessible(true);
$params = [];
$clause = $filterMethod->invokeArgs(null, [4, &$params]);
$assertContains('tasca_pla_torn', $clause, 'El filtro explícito debe consultar la relación múltiple.');
$assertSame([4, 4], $params, 'El filtro debe conservar compatibilidad con torn_id.');

if ($failures !== []) {
    fwrite(STDERR, "MultiTurnTaskTest failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "MultiTurnTaskTest passed\n";
