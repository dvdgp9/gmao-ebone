<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Models\Usuari;

$failures = [];

$assertSame = static function (mixed $expected, mixed $actual, string $message) use (&$failures): void {
    if ($expected !== $actual) {
        $failures[] = $message . ' Expected: ' . json_encode($expected) . '; actual: ' . json_encode($actual);
    }
};
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

// ---------------------------------------------------------------
// Regla pura: qui pot canviar les dades del compte (nom, email, contrasenya, estat, enllaços)
// Editor: admin (id 10) de les instal·lacions 1 i 3.
// ---------------------------------------------------------------
$tecnic = ['id' => 20, 'is_superadmin' => 0];
$adminInstalacions = [1, 3];

$assertSame(true, Usuari::compteGestionable(true, 1, $tecnic, [1, 2], []), 'Un superadmin ho gestiona tot.');
$assertSame(false, Usuari::compteGestionable(false, 10, ['id' => 1, 'is_superadmin' => 1], [1], $adminInstalacions), 'Un admin no toca mai un superadmin.');
$assertSame(true, Usuari::compteGestionable(false, 10, ['id' => 10, 'is_superadmin' => 0], [1, 2], $adminInstalacions), 'Tothom pot canviar les dades del seu propi compte.');
$assertSame(true, Usuari::compteGestionable(false, 10, $tecnic, [1], $adminInstalacions), 'Usuari només de la seva instal·lació: gestionable.');
$assertSame(false, Usuari::compteGestionable(false, 10, $tecnic, [1, 2], $adminInstalacions), 'Usuari compartit amb una instal·lació on no és admin: només rol i torns.');
$assertSame(true, Usuari::compteGestionable(false, 10, $tecnic, [1, 3], $adminInstalacions), 'Compartit només entre instal·lacions on és admin: gestionable.');
$assertSame(false, Usuari::compteGestionable(false, 10, $tecnic, [], $adminInstalacions), 'Un usuari sense instal·lacions no és seu.');
$assertSame(true, Usuari::compteGestionable(false, 10, $tecnic, ['1'], ['1', '3']), 'Els ids que arriben de la BD com a text també valen.');

// ---------------------------------------------------------------
// Guardes al controlador, model i vistes
// ---------------------------------------------------------------
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/app/Controllers/UsuariController.php');
$model = file_get_contents($root . '/app/Models/Usuari.php');
$form = file_get_contents($root . '/app/views/usuaris/form.php');
$index = file_get_contents($root . '/app/views/usuaris/index.php');

preg_match('/public function update\(.*?\n    }\n/s', $controller, $update);
preg_match('/public function toggle\(.*?\n    }\n/s', $controller, $toggle);
preg_match('/public function enllac\(.*?\n    }\n/s', $controller, $enllac);
preg_match('/public function edit\(.*?\n    }\n/s', $controller, $edit);

$assertContains('potGestionarCompte', $update[0] ?? '', 'update() ha d\'aplicar la regla abans de tocar dades del compte.');
$assertContains('potGestionarCompte', $toggle[0] ?? '', 'toggle() ha d\'aplicar la regla.');
$assertContains('potGestionarCompte', $enllac[0] ?? '', 'enllac() ha d\'aplicar la regla.');
$assertContains('Usuari::findByEmail', $update[0] ?? '', 'update() ha de comprovar que l\'email nou no és d\'un altre usuari.');
$assertContains('currentInstalacioId()', $edit[0] ?? '', 'edit() només ha de mostrar a un admin l\'assignació de la seva instal·lació.');
$assertContains("'compteEditable'", $edit[0] ?? '', 'edit() ha de dir a la vista si les dades del compte són editables.');
$assertContains('u.is_superadmin = 0', $model, 'La llista d\'una instal·lació no ha de mostrar superadmins.');
$assertContains('$compteEditable', $form, 'El formulari ha de bloquejar les dades del compte quan no són editables.');
$assertContains('Compartit', $index, 'La llista ha d\'indicar els usuaris compartits amb altres instal·lacions.');
$assertNotContains('hasOtherInstalacions', $controller, 'La regla antiga queda substituïda per compteGestionable.');

if ($failures !== []) {
    fwrite(STDERR, "UsuariAccessScopeTest failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "UsuariAccessScopeTest passed\n";
