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
$routes = file_get_contents($root . '/app/Config/routes.php');
$import = file_get_contents($root . '/app/Controllers/UsuariImportController.php');
$acces = file_get_contents($root . '/app/Controllers/AccesController.php');
$usuaris = file_get_contents($root . '/app/Controllers/UsuariController.php');
$accesView = file_get_contents($root . '/app/views/auth/acces.php');
$panel = file_get_contents($root . '/app/views/usuaris/importar.php');
$index = file_get_contents($root . '/app/views/usuaris/index.php');
$form = file_get_contents($root . '/app/views/usuaris/form.php');

// Rutes
$assertContains("\$router->get('acces/{token}', AccesController::class, 'form');", $routes, 'Falta la pàgina pública d\'accés.');
$assertContains("\$router->post('acces/{token}', AccesController::class, 'desar');", $routes, 'Falta desar la contrasenya.');
$assertContains("\$router->post('usuaris/importar/confirmar', UsuariImportController::class, 'confirmar');", $routes, 'Falta confirmar la importació.');
$assertContains("\$router->post('usuaris/enllac/{id}', UsuariController::class, 'enllac');", $routes, 'Falta generar enllaços des de la llista.');

// Importació: accés, CSRF, revalidació i transacció
$assertContains('verify_csrf()', $import, 'Les crides del panell han de validar el CSRF.');
$assertContains("'admin_instalacio'", $import, 'Només superadmin i admin d\'instal·lació poden importar.');
$assertContains('UsuariImportResolver::validate', $import, 'La confirmació ha de revalidar al servidor.');
$assertContains('beginTransaction', $import, 'La importació ha de ser tot o res.');
$assertContains('rollBack', $import, 'Un error ha de desfer la importació.');
$assertContains("nom <> 'superadmin'", $import, 'El rol superadmin no es pot assignar des de la importació.');

// Enllaç des de la llista
$assertContains('Usuari::hasOtherInstalacions', $usuaris, 'Un admin no pot generar enllaços per a comptes d\'altres instal·lacions.');
$assertContains('canManageUser((int)$id)', $usuaris, 'Generar un enllaç requereix poder gestionar l\'usuari.');

// Pàgina pública
$assertContains('verify_csrf()', $acces, 'Desar la contrasenya ha de validar el CSRF.');
$assertContains('UsuariToken::consumir', $acces, 'L\'enllaç s\'ha de consumir en desar.');
$assertContains('session_regenerate_id(true)', $acces, 'Cal regenerar la sessió en entrar.');
$assertContains('DELETE FROM remember_tokens', $acces, 'Una contrasenya nova ha d\'invalidar els «Recorda\'m».');
$assertContains('<meta name="referrer" content="no-referrer">', $accesView, 'El token no s\'ha de filtrar pel Referer.');
$assertContains('autocomplete="new-password"', $accesView, 'Els gestors de contrasenyes han de reconèixer el formulari.');

// UI
$assertContains('aplicarTotsTorns', $panel, 'El panell ha de permetre assignar tots els torns amb un clic.');
$assertContains('application/json', $panel, 'La configuració del panell va en JSON, no en atributs.');
$assertNotContains('style="', $panel, 'Els estils van a styles.css.');
$assertContains("url('usuaris/importar')", $index, 'La llista d\'usuaris ha d\'enllaçar el panell d\'importació.');
$assertContains('Pendent d\'activar', $index, 'La llista ha de mostrar qui no ha activat el compte.');
$assertNotContains("'required'", $form, 'En l\'alta manual la contrasenya és opcional (es genera un enllaç).');

if ($failures !== []) {
    fwrite(STDERR, "UsuariImportUiTest failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "UsuariImportUiTest passed\n";
