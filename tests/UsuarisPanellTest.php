<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\UsuariLlista;
use App\Services\UsuariMassiu;

$failures = [];
$assertSame = static function (mixed $expected, mixed $actual, string $message) use (&$failures): void {
    if ($expected !== $actual) {
        $failures[] = $message . ' Expected: ' . json_encode($expected, JSON_UNESCAPED_UNICODE) . '; actual: ' . json_encode($actual, JSON_UNESCAPED_UNICODE);
    }
};
$assertContains = static function (string $needle, string $haystack, string $message) use (&$failures): void {
    if (!str_contains($haystack, $needle)) {
        $failures[] = $message;
    }
};
$expectException = static function (callable $fn, string $message) use (&$failures): void {
    try {
        $fn();
        $failures[] = $message;
    } catch (InvalidArgumentException $e) {
    }
};

// ---------------------------------------------------------------
// UsuariLlista: files per al panell
// ---------------------------------------------------------------
$usuaris = [
    [
        'id' => '20', 'nom' => 'Raul', 'cognoms' => 'Duran', 'email' => 'raulduran@ebone.es', 'actiu' => '1',
        'is_superadmin' => '0', 'created_at' => '2026-09-17 11:55:00',
        'assignacions' => [
            ['instalacio_id' => '2', 'instalacio_nom' => 'Piscina Cornellà', 'rol_nom' => 'lectura'],
            ['instalacio_id' => '1', 'instalacio_nom' => 'CEM Sant Joan', 'rol_nom' => 'tecnic'],
        ],
    ],
    [
        'id' => '1', 'nom' => 'IT', 'cognoms' => null, 'email' => 'it@ebone.es', 'actiu' => '0',
        'is_superadmin' => '1', 'created_at' => '2026-02-10 09:00:00', 'assignacions' => [],
    ],
];
$files = UsuariLlista::files(
    $usuaris,
    [20 => [1 => ['Matí', 'Tarda']]],
    [20 => 'pendent'],
    [20 => true],
    1
);

$assertSame([
    'id' => 20,
    'nom' => 'Raul',
    'cognoms' => 'Duran',
    'email' => 'raulduran@ebone.es',
    'actiu' => true,
    'superadmin' => false,
    'creat' => '2026-09-17',
    'activacio' => 'pendent',
    'bloquejat' => true,
    'propi' => false,
    'assignacions' => [
        ['instalacio_id' => 1, 'instalacio' => 'CEM Sant Joan', 'rol' => 'tecnic', 'rol_etiqueta' => 'Tècnic', 'torns' => ['Matí', 'Tarda']],
        ['instalacio_id' => 2, 'instalacio' => 'Piscina Cornellà', 'rol' => 'lectura', 'rol_etiqueta' => 'Lectura', 'torns' => []],
    ],
], $files[0], 'Cada fila porta el rol i els torns de cada instal·lació, ordenades per nom.');

$assertSame(
    ['id' => 1, 'cognoms' => '', 'actiu' => false, 'superadmin' => true, 'activacio' => null, 'bloquejat' => false, 'propi' => true, 'assignacions' => []],
    array_intersect_key($files[1], array_flip(['id', 'cognoms', 'actiu', 'superadmin', 'activacio', 'bloquejat', 'propi', 'assignacions'])),
    'Tipus normalitzats (bool/int) i compte propi detectat.'
);

// ---------------------------------------------------------------
// UsuariMassiu: ids
// ---------------------------------------------------------------
$assertSame([3, 1, 7], UsuariMassiu::parseIds('3, 1,abc,3,,0,-2, 7'), 'Ids enters positius, únics i en ordre.');
$assertSame([], UsuariMassiu::parseIds(''), 'Sense ids, llista buida.');

// ---------------------------------------------------------------
// UsuariMassiu: qui es veu afectat
// ---------------------------------------------------------------
$afectats = [
    ['id' => 1, 'actiu' => 1, 'is_superadmin' => 1, 'instalacio_ids' => []],   // editor (superadmin)
    ['id' => 2, 'actiu' => 1, 'is_superadmin' => 0, 'instalacio_ids' => [5]],
    ['id' => 3, 'actiu' => 0, 'is_superadmin' => 0, 'instalacio_ids' => [5, 6]],
    ['id' => 4, 'actiu' => 1, 'is_superadmin' => 1, 'instalacio_ids' => []],   // un altre superadmin
];

$assertSame(['aplicar' => [3], 'omesos' => [1 => 'ja_actiu', 2 => 'ja_actiu', 4 => 'ja_actiu']], UsuariMassiu::planificar('activar', $afectats, 1), 'Activar: només els inactius.');
$assertSame(['aplicar' => [2, 4], 'omesos' => [1 => 'propi', 3 => 'ja_inactiu']], UsuariMassiu::planificar('desactivar', $afectats, 1), 'Desactivar: mai el propi compte.');
$assertSame(['aplicar' => [2, 3], 'omesos' => [1 => 'superadmin', 4 => 'superadmin']], UsuariMassiu::planificar('assignar', $afectats, 1, 5), 'Assignar: els superadmins no s\'assignen.');
$assertSame(['aplicar' => [2, 3], 'omesos' => [1 => 'no_assignat', 4 => 'no_assignat']], UsuariMassiu::planificar('treure', $afectats, 1, 5), 'Treure: només els que hi són.');
$assertSame(['aplicar' => [3], 'omesos' => [1 => 'no_assignat', 2 => 'no_assignat', 4 => 'no_assignat']], UsuariMassiu::planificar('treure', $afectats, 1, 6), 'Treure d\'una altra instal·lació.');
$assertSame(['aplicar' => [1, 2, 4], 'omesos' => [3 => 'inactiu']], UsuariMassiu::planificar('enllacos', $afectats, 1), 'Enllaços: només comptes actius.');

$expectException(static fn() => UsuariMassiu::planificar('esborrar', $afectats, 1), 'Una acció desconeguda ha de fallar.');
$expectException(static fn() => UsuariMassiu::planificar('assignar', $afectats, 1), 'Assignar sense instal·lació ha de fallar.');

// ---------------------------------------------------------------
// UsuariMassiu: missatge de resum
// ---------------------------------------------------------------
$assertSame(
    '2 usuaris desactivats. No s\'han tocat: el teu propi compte (1), ja estaven desactivats (1).',
    UsuariMassiu::missatge('desactivar', ['aplicar' => [2, 4], 'omesos' => [1 => 'propi', 3 => 'ja_inactiu']]),
    'Resum amb omesos agrupats per motiu.'
);
$assertSame('1 usuari assignat a CEM Sant Joan.', UsuariMassiu::missatge('assignar', ['aplicar' => [2], 'omesos' => []], 'CEM Sant Joan'), 'Singular.');
$assertSame('3 usuaris trets de CEM Sant Joan.', UsuariMassiu::missatge('treure', ['aplicar' => [2, 3, 4], 'omesos' => []], 'CEM Sant Joan'), 'Plural.');
$assertSame('Cap usuari modificat. No s\'han tocat: ja estaven actius (2).', UsuariMassiu::missatge('activar', ['aplicar' => [], 'omesos' => [1 => 'ja_actiu', 2 => 'ja_actiu']]), 'Sense canvis.');

// ---------------------------------------------------------------
// Guardes: rutes, permisos i vista
// ---------------------------------------------------------------
$root = dirname(__DIR__);
$routes = file_get_contents($root . '/app/Config/routes.php');
$massiu = file_get_contents($root . '/app/Controllers/UsuariMassiuController.php');
$index = file_get_contents($root . '/app/views/usuaris/index.php');

$assertContains("\$router->post('usuaris/massiu', UsuariMassiuController::class, 'executar');", $routes, 'Falta la ruta d\'accions en bloc.');
$assertContains("\$router->post('usuaris/exportar', UsuariMassiuController::class, 'exportar');", $routes, 'Falta la ruta d\'exportació.');
$assertContains("empty(\$_SESSION['is_superadmin'])", $massiu, 'Les accions en bloc són només per a superadmin.');
$assertContains('verify_csrf()', $massiu, 'Les accions en bloc validen el CSRF.');
$assertContains('beginTransaction', $massiu, 'Les accions en bloc són tot o res.');
$assertContains('UsuariMassiu::parseIds', $massiu, 'Els ids arriben en una sola cadena (max_input_vars).');
$assertContains("nom <> 'superadmin'", $massiu, 'No es pot assignar el rol superadmin en bloc.');
$assertContains('name="ids"', $index, 'El formulari d\'accions envia els ids en un sol camp.');
$assertContains("url('usuaris/importar')", $index, 'La llista continua enllaçant la importació.');
$assertContains('Pendent d\'activar', $index, 'La llista continua mostrant qui no ha activat.');
$assertContains('Compartit', $index, 'La llista continua marcant els comptes compartits.');
$assertContains('Sense instal·lació', $index, 'Hi ha d\'haver el filtre d\'usuaris sense instal·lació.');

if ($failures !== []) {
    fwrite(STDERR, "UsuarisPanellTest failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "UsuarisPanellTest passed\n";
