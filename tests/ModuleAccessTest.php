<?php

$failures = [];

$assertNotContains = static function (string $needle, string $haystack, string $message) use (&$failures): void {
    if (str_contains($haystack, $needle)) {
        $failures[] = $message;
    }
};

$assertContains = static function (string $needle, string $haystack, string $message) use (&$failures): void {
    if (!str_contains($haystack, $needle)) {
        $failures[] = $message;
    }
};

$model = file_get_contents(dirname(__DIR__) . '/app/Models/Instalacio.php');
$assertContains(
    'La selecció de mòduls es conserva només per compatibilitat',
    $model,
    'El modelo debe declarar que la selección antigua ya no controla el acceso.'
);
$assertNotContains(
    "return in_array(\$modul, static::modulsActiusById(\$instalacioId), true);",
    $model,
    'El acceso directo no debe bloquearse según la selección antigua de módulos.'
);

$layout = file_get_contents(dirname(__DIR__) . '/app/views/layouts/main.php');
$assertNotContains("in_array('equips', \$__moduls, true)", $layout, 'Equips y Sistemes no deben depender de módulos.');
$assertNotContains("in_array('espais', \$__moduls, true)", $layout, 'Espais no debe depender de módulos.');
$assertNotContains("in_array('torns', \$__moduls, true)", $layout, 'Torns no debe depender de módulos.');
$assertNotContains('if ($__enConfiguracio)', $layout, 'Configuració no debe depender de que el plan esté vacío.');
$assertContains("url('instalacions/onboarding/'", $layout, 'El sidebar debe conservar el acceso a Configuració.');

$installationsView = file_get_contents(dirname(__DIR__) . '/app/views/instalacions/index.php');
$assertNotContains("empty(\$ambPla", $installationsView, 'Configurar debe estar disponible aunque la instalación ya tenga plan.');

$onboardingView = file_get_contents(dirname(__DIR__) . '/app/views/instalacions/onboarding.php');
$assertNotContains("url('instalacions/moduls/", $onboardingView, 'El onboarding no debe pedir selección de módulos.');

if ($failures !== []) {
    fwrite(STDERR, "ModuleAccessTest failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "ModuleAccessTest passed\n";
