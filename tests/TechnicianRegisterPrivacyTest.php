<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Models\RegistreTasca;

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
$controller = file_get_contents($root . '/app/Controllers/RegistreController.php');
$model = file_get_contents($root . '/app/Models/RegistreTasca.php');
$view = file_get_contents($root . '/app/views/registre/index.php');

$assertContains("\$scopeFilters['usuari_id'] = \$this->currentUserId();", $controller, 'El técnico debe quedar limitado al usuario de su sesión.');
$assertContains("\$isTecnic ? \$this->currentUserId() : null", $controller, 'Las opciones de filtro también deben respetar la privacidad del técnico.');
$assertContains("'title' => \$isTecnic ? 'El meu registre'", $controller, 'La pantalla privada debe identificarse como un registro personal.');
$assertNotContains('Torn::supportsUsuariTorn()', $controller, 'La privacidad no debe depender de que exista una asignación de turnos.');
$assertContains("\$conditions[] = 'rt.usuari_id = ?';", $model, 'La restricción debe aplicarse dentro de la consulta SQL.');
$assertContains("\$scopeSql .= ' AND rt.usuari_id = ?';", $model, 'Los desplegables deben consultar solo registros del usuario.');
$assertContains("<?php if (!\$isTecnic): ?>", $view, 'La columna de técnico debe ocultarse en la vista personal.');
$assertContains('Historial de les execucions que has registrat', $view, 'La interfaz debe explicar el alcance personal del registro.');

$whereMethod = new ReflectionMethod(RegistreTasca::class, 'buildFilterWhere');
$whereMethod->setAccessible(true);
[$where, $params] = $whereMethod->invoke(null, 12, ['usuari_id' => 47]);
$assertContains('rt.instalacio_id = ?', $where, 'El filtro debe conservar el aislamiento por instalación.');
$assertContains('rt.usuari_id = ?', $where, 'El filtro debe aislar por técnico.');
$assertSame([12, 47], $params, 'La consulta debe usar los identificadores de instalación y técnico.');

if ($failures !== []) {
    fwrite(STDERR, "TechnicianRegisterPrivacyTest failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "TechnicianRegisterPrivacyTest passed\n";
