<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\UsuariImportResolver;

$failures = [];

$assertSame = static function (mixed $expected, mixed $actual, string $message) use (&$failures): void {
    if ($expected !== $actual) {
        $failures[] = $message . ' Expected: ' . json_encode($expected, JSON_UNESCAPED_UNICODE) . '; actual: ' . json_encode($actual, JSON_UNESCAPED_UNICODE);
    }
};

$raw = static fn(array $values): array => array_merge([
    'nom' => '', 'cognoms' => '', 'email' => '', 'rol' => '', 'torns' => '', 'instalacio' => '', 'extra' => [],
], $values);

$ctx = [
    'instalacions' => [
        ['id' => 1, 'nom' => 'CEM Sant Joan'],
        ['id' => 2, 'nom' => 'Piscina Municipal de Cornellà'],
    ],
    'rols' => [
        ['id' => 2, 'nom' => 'admin_instalacio'],
        ['id' => 3, 'nom' => 'cap_manteniment'],
        ['id' => 4, 'nom' => 'tecnic'],
        ['id' => 5, 'nom' => 'lectura'],
    ],
    'torns' => [
        1 => [['id' => 10, 'nom' => 'Matí (6-14h)'], ['id' => 11, 'nom' => 'Tarda'], ['id' => 12, 'nom' => 'Cap de setmana']],
        2 => [['id' => 20, 'nom' => 'Mañana'], ['id' => 21, 'nom' => 'Tarde']],
    ],
    'default_instalacio_id' => null,
    'default_rol_id' => 4,
];

// ---------------------------------------------------------------
// resolve()
// ---------------------------------------------------------------

$rows = UsuariImportResolver::resolve([
    $raw(['nom' => 'Marta', 'email' => 'marta@ebone.es', 'rol' => 'Técnico', 'torns' => 'Mañana, Tarde', 'instalacio' => 'piscina municipal de cornella']),
    $raw(['nom' => 'Pere', 'email' => 'pere@ebone.es', 'rol' => 'Jefe de mantenimiento', 'torns' => 'Todos', 'instalacio' => 'CEM Sant Joan']),
    $raw(['nom' => 'Laia', 'email' => 'laia@ebone.es', 'rol' => 'Responsable', 'torns' => 'Matí i Nit', 'instalacio' => 'Sant Joan']),
    $raw(['nom' => 'Joan', 'email' => 'joan@ebone.es']),
], $ctx);

$assertSame(2, $rows[0]['instalacio_id'], 'Ha de reconèixer la instal·lació sense accents ni majúscules.');
$assertSame(4, $rows[0]['rol_id'], '«Técnico» és el rol tècnic.');
$assertSame([20, 21], $rows[0]['torn_ids'], 'Ha de reconèixer els torns pel nom dins de la seva instal·lació.');
$assertSame([], $rows[0]['avisos'], 'Una fila ben resolta no té avisos.');

$assertSame(3, $rows[1]['rol_id'], '«Jefe de mantenimiento» és cap de manteniment.');
$assertSame([10, 11, 12], $rows[1]['torn_ids'], '«Todos» assigna tots els torns de la instal·lació.');

$assertSame(1, $rows[2]['instalacio_id'], 'Una coincidència parcial única també val.');
$assertSame(4, $rows[2]['rol_id'], 'Un rol no reconegut agafa el rol per defecte.');
$assertSame('Rol no reconegut: «Responsable». S\'ha posat el rol per defecte.', $rows[2]['avisos']['rol'] ?? null, 'Ha d\'avisar del rol no reconegut.');
$assertSame([10], $rows[2]['torn_ids'], '«Matí» casa amb «Matí (6-14h)» pel començament.');
$assertSame('Torns no reconeguts: «Nit»', $rows[2]['avisos']['torns'] ?? null, 'Ha d\'avisar del torn no reconegut.');

$assertSame(null, $rows[3]['instalacio_id'], 'Sense instal·lació ni valor per defecte queda buida.');
$assertSame(4, $rows[3]['rol_id'], 'Sense rol agafa el per defecte.');
$assertSame([], $rows[3]['torn_ids'], 'Sense torns queda buit.');

// Paraules de la instal·lació en ordre però incompletes: «Piscina Cornellà».
$rows = UsuariImportResolver::resolve([$raw(['nom' => 'Marta', 'email' => 'marta@ebone.es', 'instalacio' => 'Piscina Cornellà'])], $ctx);
$assertSame(2, $rows[0]['instalacio_id'], 'Totes les paraules escrites dins el nom de la instal·lació també casen.');

// Dades extra sense capçalera: rol, instal·lació i torns.
$rows = UsuariImportResolver::resolve([
    $raw(['nom' => 'Joan', 'email' => 'joan@ebone.es', 'extra' => ['Lectura', 'CEM Sant Joan', 'tots els torns', 'Carnet B']]),
], $ctx);
$assertSame(5, $rows[0]['rol_id'], 'Extra: rol.');
$assertSame(1, $rows[0]['instalacio_id'], 'Extra: instal·lació.');
$assertSame([10, 11, 12], $rows[0]['torn_ids'], 'Extra: tots els torns.');
$assertSame('Dades no reconegudes: «Carnet B»', $rows[0]['avisos']['extra'] ?? null, 'Extra: el que no s\'entén s\'avisa.');

// Instal·lació per defecte (admin d'instal·lació).
$rows = UsuariImportResolver::resolve([$raw(['nom' => 'Joan', 'email' => 'joan@ebone.es', 'torns' => 'Tarda'])], array_merge($ctx, ['default_instalacio_id' => 1]));
$assertSame(1, $rows[0]['instalacio_id'], 'Ha de fer servir la instal·lació per defecte.');
$assertSame([11], $rows[0]['torn_ids'], 'Els torns es resolen dins la instal·lació per defecte.');

// ---------------------------------------------------------------
// validate()
// ---------------------------------------------------------------

$row = static fn(array $values): array => array_merge([
    'nom' => 'Nom', 'cognoms' => '', 'email' => 'nou@ebone.es', 'instalacio_id' => 1, 'rol_id' => 4, 'torn_ids' => [],
], $values);

$validCtx = array_merge($ctx, [
    'is_superadmin' => true,
    'current_instalacio_id' => null,
    'existing' => [
        'existent@ebone.es' => ['id' => 50, 'actiu' => 1, 'is_superadmin' => 0, 'instalacio_ids' => [2]],
        'ja@ebone.es' => ['id' => 51, 'actiu' => 0, 'is_superadmin' => 0, 'instalacio_ids' => [1]],
        'it@ebone.es' => ['id' => 1, 'actiu' => 1, 'is_superadmin' => 1, 'instalacio_ids' => []],
    ],
]);

$result = UsuariImportResolver::validate([
    $row(['email' => 'nou@ebone.es', 'torn_ids' => [10, 11]]),
    $row(['email' => 'Existent@ebone.es', 'nom' => '']),
    $row(['email' => 'ja@ebone.es']),
    $row(['email' => 'it@ebone.es']),
    $row(['email' => 'nou@ebone.es']),
    $row(['email' => 'nou@ebone.es', 'instalacio_id' => 2]),
    $row(['email' => 'no-es-un-email', 'nom' => '']),
    $row(['instalacio_id' => null, 'rol_id' => null, 'email' => 'altre@ebone.es']),
    $row(['email' => 'torns@ebone.es', 'torn_ids' => [20]]),
    $row(['email' => 'super@ebone.es', 'rol_id' => 1]),
], $validCtx);

$files = $result['files'];
$assertSame('crear', $files[0]['accio'], 'Email nou → crear.');
$assertSame([], $files[0]['errors'], 'Fila correcta sense errors.');

$assertSame('assignar', $files[1]['accio'], 'Email existent en una altra instal·lació → assignar (superadmin).');
$assertSame([], $files[1]['errors'], 'Un usuari existent no necessita nom i l\'email no distingeix majúscules.');
$assertSame(['Ja té compte: s\'afegirà a aquesta instal·lació i mantindrà la seva contrasenya.'], $files[1]['avisos'], 'Avís d\'usuari existent.');

$assertSame('actualitzar', $files[2]['accio'], 'Ja és a la instal·lació → actualitzar.');
$assertSame(
    ['Ja és a aquesta instal·lació: se n\'actualitzaran el rol i els torns.', 'L\'usuari està desactivat: no podrà entrar fins que el reactivis.'],
    $files[2]['avisos'],
    'Avisos d\'actualització i d\'usuari desactivat.'
);

$assertSame(['És un superadmin: ja té accés a totes les instal·lacions.'], $files[3]['errors'], 'Un superadmin no s\'importa.');
$assertSame(['Fila repetida: mateix email i instal·lació que la fila 1.'], $files[4]['errors'], 'Duplicat email + instal·lació.');
$assertSame([], $files[5]['errors'], 'El mateix email en una altra instal·lació és una segona assignació.');
$assertSame('crear', $files[5]['accio'], 'La segona assignació d\'un usuari nou continua sent «crear».');
$assertSame(['L\'email no és vàlid.', 'Falta el nom.'], $files[6]['errors'], 'Email invàlid i nom buit.');
$assertSame(['Tria una instal·lació.', 'Tria un rol.'], $files[7]['errors'], 'Falten instal·lació i rol.');
$assertSame(['Hi ha torns que no són d\'aquesta instal·lació.'], $files[8]['errors'], 'Torns d\'una altra instal·lació.');
$assertSame(['Tria un rol.'], $files[9]['errors'], 'Un rol fora de la llista permesa (superadmin) no és vàlid.');

$assertSame(['usuaris_nous' => 1, 'existents' => 2, 'errors' => 6, 'files' => 10], $result['resum'], 'Resum: usuaris nous únics, existents i files amb error.');

// Admin d'instal·lació: no pot incorporar comptes d'altres instal·lacions ni triar-ne una altra.
$adminCtx = array_merge($validCtx, [
    'is_superadmin' => false,
    'current_instalacio_id' => 1,
    'instalacions' => [['id' => 1, 'nom' => 'CEM Sant Joan']],
]);
$result = UsuariImportResolver::validate([
    $row(['email' => 'existent@ebone.es']),
    $row(['email' => 'ja@ebone.es']),
    $row(['email' => 'nou@ebone.es', 'instalacio_id' => 2]),
], $adminCtx);
$assertSame(
    ['Aquest email ja té compte en una altra instal·lació. Només un superadmin el pot afegir aquí.'],
    $result['files'][0]['errors'],
    'Un admin no pot afegir comptes d\'altres instal·lacions.'
);
$assertSame([], $result['files'][1]['errors'], 'Un admin sí pot actualitzar usuaris de la seva instal·lació.');
$assertSame(['No pots assignar usuaris a aquesta instal·lació.'], $result['files'][2]['errors'], 'Un admin només pot fer servir la seva instal·lació.');

// Longituds màximes.
$result = UsuariImportResolver::validate([
    $row(['nom' => str_repeat('a', 101), 'cognoms' => str_repeat('b', 201)]),
], $validCtx);
$assertSame(['El nom és massa llarg (màxim 100 caràcters).', 'Els cognoms són massa llargs (màxim 200 caràcters).'], $result['files'][0]['errors'], 'Límits de longitud de l\'esquema.');

if ($failures !== []) {
    fwrite(STDERR, "UsuariImportResolverTest failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "UsuariImportResolverTest passed\n";
