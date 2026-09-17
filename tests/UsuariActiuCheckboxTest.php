<?php

require dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/app/Helpers/functions.php';

$failures = [];
$assertSame = static function (mixed $expected, mixed $actual, string $message) use (&$failures): void {
    if ($expected !== $actual) {
        $failures[] = $message . ' Expected: ' . json_encode($expected) . '; actual: ' . json_encode($actual);
    }
};

$root = dirname(__DIR__);
$_ENV['APP_URL'] = 'http://localhost';
$_SESSION = ['user_id' => 10, 'is_superadmin' => false];

// Formulari + layout. Sense instal·lació activa a la sessió, el layout no toca la BD.
$renderForm = static function (bool $compteEditable, int $actiu) use ($root): string {
    $vars = [
        'usuari' => ['id' => 20, 'nom' => 'Raul', 'cognoms' => 'Duran', 'email' => 'raul@ebone.es', 'actiu' => $actiu],
        'compteEditable' => $compteEditable,
        'assignacions' => [],
        'instalacions' => [],
        'rols' => [],
        'tornsPerInstalacio' => [],
        'tornsAssignats' => [],
        'tornsAssignatsPerInst' => [],
        'isSuperadmin' => false,
    ];
    $html = (static function () use ($vars, $root) {
        extract($vars);
        ob_start();
        require $root . '/app/views/usuaris/form.php';
        return ob_get_clean();
    })();

    return $html;
};

$campsActiu = static function (string $html): array {
    preg_match_all('/<input[^>]*name="actiu"[^>]*>/', $html, $m);
    return array_map(static function (string $tag) {
        $senseClasses = preg_replace('/class="[^"]*"/', '', $tag);
        preg_match('/type="([^"]+)"/', $tag, $type);
        preg_match('/value="([^"]*)"/', $tag, $value);
        return [
            'type' => $type[1] ?? '',
            'value' => $value[1] ?? '',
            'checked' => preg_match('/\schecked(\s|\/?>)/', $senseClasses) === 1,
            'disabled' => preg_match('/\sdisabled(\s|\/?>)/', $senseClasses) === 1,
        ];
    }, $m[0]);
};

// 1. Desmarcar el checkbox ha d'enviar actiu=0: camp ocult abans del checkbox (PHP es queda l'últim valor).
$camps = $campsActiu($renderForm(true, 1));
$assertSame(
    [
        ['type' => 'hidden', 'value' => '0', 'checked' => false, 'disabled' => false],
        ['type' => 'checkbox', 'value' => '1', 'checked' => true, 'disabled' => false],
    ],
    $camps,
    'Cal un camp ocult actiu=0 just abans del checkbox.'
);

$camps = $campsActiu($renderForm(true, 0));
$assertSame(false, $camps[1]['checked'] ?? null, 'Un usuari inactiu es mostra amb el checkbox desmarcat.');

// 2. Compte compartit: no s'envia res de l'estat.
$camps = $campsActiu($renderForm(false, 1));
$assertSame([true, true], array_column($camps, 'disabled'), 'En comptes compartits el camp ocult també va bloquejat.');

// 3. Controlador
$controller = file_get_contents($root . '/app/Controllers/UsuariController.php');
preg_match('/public function update\(.*?\n    }\n/s', $controller, $update);
$assertSame(false, str_contains($controller, "post('actiu', 1)"), 'Un checkbox desmarcat no es pot interpretar com a actiu.');
$assertSame(true, str_contains($update[0] ?? '', 'No pots desactivar el teu propi compte.'), 'Ningú es pot desactivar a si mateix des del formulari.');
$assertSame(true, str_contains($update[0] ?? '', "\$usuari['actiu']"), 'Si el camp no arriba, l\'edició conserva l\'estat actual.');

if ($failures !== []) {
    fwrite(STDERR, "UsuariActiuCheckboxTest failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "UsuariActiuCheckboxTest passed\n";
