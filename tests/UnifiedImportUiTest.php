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

$controller = file_get_contents(dirname(__DIR__) . '/app/Controllers/ImportController.php');
$index = file_get_contents(dirname(__DIR__) . '/app/views/import/index.php');
$preview = file_get_contents(dirname(__DIR__) . '/app/views/import/preview.php');

$assertContains('ImportWorkbookInspector::inspect', $controller, 'La subida debe usar el detector automático.');
$assertContains("'inspection' => \$importSummary", $controller, 'El diagnóstico debe conservarse hasta la confirmación.');
$assertContains("importCompleteSystems", $controller, 'La importación completa debe dar de alta sistemas desconocidos.');
$assertContains("extractSimpleInventoryRows", $controller, 'La importación debe soportar INVENTARI alternativo.');
$assertNotContains('name="import_type"', $index, 'La pantalla no debe pedir un tipo de importación.');
$assertContains('El format es detectarà automàticament', $index, 'La pantalla debe explicar la detección automática.');
$assertContains('$hasDiagnosticErrors', $preview, 'Los errores de formato deben bloquear la confirmación.');
$assertContains("\$diagnostic['warnings']", $preview, 'La preview debe mostrar avisos.');
$assertNotContains('recommended=plantilla', file_get_contents(dirname(__DIR__) . '/app/views/instalacions/onboarding.php'), 'El onboarding no debe forzar un tipo antiguo.');

if ($failures !== []) {
    fwrite(STDERR, "UnifiedImportUiTest failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "UnifiedImportUiTest passed\n";
