<?php

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

$root = dirname(__DIR__);
$planController = file_get_contents($root . '/app/Controllers/TascaPlaController.php');
$catalogController = file_get_contents($root . '/app/Controllers/TascaCatalegController.php');
$catalogModel = file_get_contents($root . '/app/Models/TascaCataleg.php');
$planForm = file_get_contents($root . '/app/views/pla/form.php');
$planIndex = file_get_contents($root . '/app/views/pla/index.php');
$catalogIndex = file_get_contents($root . '/app/views/tasques_cataleg/index.php');
$routes = file_get_contents($root . '/app/Config/routes.php');

$assertContains("'title' => 'Nova tasca'", $planController, 'El alta compartida debe llamarse Nova tasca.');
$assertContains("redirect('pla/create?origen=cataleg')", $catalogController, 'El repositorio debe usar el mismo alta que el plan.');
$assertContains('name="afegir_al_pla"', $planForm, 'El formulario compartido debe permitir decidir si se añade al plan.');
$assertContains('name="nom"', $planForm, 'El formulario del plan debe incluir los datos comunes de la tarea.');
$assertContains('name="empresa_responsable"', $planForm, 'El formulario del plan debe incluir los mismos datos del antiguo catálogo.');
$assertContains('TascaCataleg::create($catalogData)', $planController, 'Crear desde el plan debe guardar primero la tarea en el repositorio.');
$assertContains("TascaPla::update((int)\$id, ['en_curs' => 0])", $planController, 'Quitar del plan debe conservar la tarea como inactiva.');
$assertNotContains('TascaPla::delete((int)$id)', $planController, 'Quitar del plan no debe borrar el histórico.');
$assertContains("post('pla/reactivate/{id}'", $routes, 'Debe existir una ruta de reactivación.');
$assertContains('reactivable_plan_id', $catalogModel, 'El repositorio debe localizar tareas desactivadas recuperables.');
$assertContains('Reactivar', $catalogIndex, 'El repositorio debe ofrecer la reactivación.');
$assertContains('Nova tasca', $catalogIndex, 'El botón del repositorio debe decir Nova tasca.');
$assertContains('Nova tasca', $planIndex, 'El botón del plan debe decir Nova tasca.');

if ($failures !== []) {
    fwrite(STDERR, "TaskLifecycleTest failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "TaskLifecycleTest passed\n";
