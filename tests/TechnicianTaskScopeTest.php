<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Models\TascaPla;

$failures = [];
$assertContains = static function (string $needle, string $haystack, string $message) use (&$failures): void {
    if (!str_contains($haystack, $needle)) {
        $failures[] = $message;
    }
};
$assertNotContains = static function (string $needle, string $haystack, string $message) use (&$failures): void {
    if (str_contains($haystack, $needle)) {
        $failures[] = $message;
    }
};
$assertSame = static function (mixed $expected, mixed $actual, string $message) use (&$failures): void {
    if ($expected !== $actual) {
        $failures[] = $message . ' Expected: ' . json_encode($expected) . '; actual: ' . json_encode($actual);
    }
};

$root = dirname(__DIR__);
$dashboardController = file_get_contents($root . '/app/Controllers/DashboardController.php');
$technicianDashboard = file_get_contents($root . '/app/views/dashboard/tecnic.php');
$planController = file_get_contents($root . '/app/Controllers/TascaPlaController.php');

$assertContains("\$this->currentRole() === 'tecnic'", $dashboardController, 'El dashboard debe separar la experiencia del técnico.');
$assertContains('Torn::tornIdsByUsuariInstalacio', $dashboardController, 'El dashboard técnico debe limitar las tareas a sus turnos.');
$assertContains('TascaPla::properesByInstalacio($instalacioId, $tornIds)', $dashboardController, 'La cola del técnico debe estar filtrada por sus turnos.');
$assertContains('$this->currentUserId()', $dashboardController, 'Los contadores de ejecuciones deben pertenecer al técnico conectado.');
$assertContains('La meva cua de treball', $technicianDashboard, 'El inicio técnico debe priorizar su cola de trabajo.');
$assertContains('Registrades per tu', $technicianDashboard, 'Las ejecuciones mostradas deben identificarse como personales.');
$assertNotContains('% Acompliment', $technicianDashboard, 'El técnico no debe ver el cumplimiento general.');
$assertNotContains('Equips actius', $technicianDashboard, 'El técnico no debe ver indicadores generales de la instalación.');
$assertNotContains('Per sistema', $technicianDashboard, 'El técnico no debe ver el reparto general por sistema.');
$assertContains('Torn::tornIdsByUsuariInstalacio', $planController, 'El plan del técnico debe obtener únicamente sus turnos.');
$assertContains('TascaPla::searchByInstalacio($instalacioId, $search,', $planController, 'La búsqueda del plan debe conservar el filtro del técnico.');

$filterMethod = new ReflectionMethod(TascaPla::class, 'tornFilterClause');
$filterMethod->setAccessible(true);

$params = [12];
$clause = $filterMethod->invokeArgs(null, [[4, 7], &$params]);
$assertContains('tpt_filter.torn_id IN (?,?)', $clause, 'Una tarea compartida debe ser visible desde cualquiera de los turnos del técnico.');
$assertNotContains('tp.torn_id IS NULL', $clause, 'Las tareas sin turno no deben aparecer como si fueran del técnico.');
$assertSame([12, 4, 7, 4, 7], $params, 'El filtro debe aplicar todos los turnos autorizados.');

$params = [12];
$clause = $filterMethod->invokeArgs(null, [[], &$params]);
$assertContains('1 = 0', $clause, 'Un técnico sin turnos debe obtener una lista vacía.');
$assertSame([12], $params, 'El filtro vacío no debe alterar los parámetros de la consulta.');

if ($failures !== []) {
    fwrite(STDERR, "TechnicianTaskScopeTest failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "TechnicianTaskScopeTest passed\n";
