<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\ImportWorkbookInspector;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

$failures = [];
$assertSame = static function (mixed $expected, mixed $actual, string $message) use (&$failures): void {
    if ($expected !== $actual) {
        $failures[] = $message . ' Expected: ' . json_encode($expected, JSON_UNESCAPED_UNICODE) . '; actual: ' . json_encode($actual, JSON_UNESCAPED_UNICODE);
    }
};

/**
 * Llibre complet mínim (mateixes capçaleres que els Excel reals) amb l'INVENTARI indicat.
 */
$llibre = static function (array $inventari): Spreadsheet {
    $book = new Spreadsheet();
    $llistes = $book->getActiveSheet();
    $llistes->setTitle('LLISTES');
    $llistes->fromArray([
        ['EQUIPAMENT', null, 'ESPAIS', 'CODI', 'PLANTA'],
        [null, null, 'Sala de Màquines', 'SM', 'P-1'],
        [null, null, 'Coberta P0', 'COB', 'P1'],
    ]);
    $book->createSheet()->setTitle('INVENTARI')->fromArray($inventari);
    $book->createSheet()->setTitle('BD TASQUES')->fromArray([
        ['CODI', 'TIPUS', 'EQUIP', 'TASQUES'],
        ['CL', 'P', 'Deshumectadora', 'Revisar filtres'],
    ]);
    $book->createSheet()->setTitle('TASQUES PLA_M')->fromArray([
        ['codi tasca', 'TASCA', 'EQUIPAMENT', 'ESPAI', 'Codi Espai', 'Planta', 'Data Darrera', 'Data Propera', 'Periodicitat Norma', 'Periodicitat'],
        ['CL-1', 'Revisar filtres', 'CEM', 'Sala de Màquines', 'SM', 'P-1', null, null, 'Mensual', 'Mensual'],
    ]);
    $book->createSheet()->setTitle('REGISTRE TASQUES')->fromArray([
        ['CODI TASCA', 'TASCA', 'EQUIPAMENT', 'ESPAI'],
    ]);
    return $book;
};

// ---------------------------------------------------------------
// 1. INVENTARI sense CODI ESPAI (com l'Excel del CEM Maria Reverter)
// ---------------------------------------------------------------
$book = $llibre([
    ['EQUIP ', 'MODEL', 'UBICACIÓ', 'PLANTA', null, null],
    ['CL - DESHUMECTADORA', 'CIATESA - BCP 440', 'Coberta P0', 'P1', null, '1. PAV-Paviments'],
    ['CL - REFREDADORA', 'CLIMAVENETA FOCS', 'Sala de Màquines', 'P-1', null, 'PAV-SAU Sauló'],
    [null, null, null, null, null, 'fila sense equip'],
]);
$resultat = ImportWorkbookInspector::inspect($book, ['CL']);
$assertSame('completa_instalacio', $resultat['type'], 'Ha de continuar sent un Excel complet.');
$assertSame([], $resultat['errors'], 'Sense CODI ESPAI no s\'ha de bloquejar la importació.');
$assertSame('simple', $resultat['inventory_format'], 'Format alternatiu d\'INVENTARI.');
$assertSame(
    [true, null],
    [array_key_exists('codi_espai', $resultat['inventory_columns']), $resultat['inventory_columns']['codi_espai'] ?? null],
    'La columna CODI ESPAI queda com a opcional (clau present amb valor null).'
);
$assertSame(2, $resultat['counts']['equips'], 'Compta els equips amb nom i ignora files buides.');
$assertSame(
    ['S\'ha detectat el format alternatiu d\'INVENTARI sense CODI ESPAI; es maparan EQUIP, MODEL, UBICACIÓ i PLANTA, i cada equip s\'enllaçarà amb l\'espai que tingui el mateix nom que la UBICACIÓ.'],
    $resultat['warnings'],
    'Ha d\'explicar com s\'enllaçaran els equips amb els espais.'
);

$files = ImportWorkbookInspector::extractSimpleInventoryRows($book->getSheetByName('INVENTARI'));
$assertSame(
    [
        ['row' => 2, 'nom_equip' => 'CL - DESHUMECTADORA', 'model' => 'CIATESA - BCP 440', 'ubicacio' => 'Coberta P0', 'codi_espai' => '', 'planta' => 'P1'],
        ['row' => 3, 'nom_equip' => 'CL - REFREDADORA', 'model' => 'CLIMAVENETA FOCS', 'ubicacio' => 'Sala de Màquines', 'codi_espai' => '', 'planta' => 'P-1'],
    ],
    $files,
    'Les files d\'equips porten codi_espai buit perquè la importació busqui l\'espai per UBICACIÓ.'
);

// ---------------------------------------------------------------
// 2. INVENTARI amb CODI ESPAI (capçalera a la fila 2): tot igual que abans
// ---------------------------------------------------------------
$book = $llibre([
    ['Inventari d\'equips'],
    ['EQUIP', 'MODEL', 'UBICACIÓ', 'CODI ESPAI', 'PLANTA'],
    ['Bomba 1', 'Grundfos', 'Sala de Màquines', 'SM', 'P-1'],
]);
$resultat = ImportWorkbookInspector::inspect($book, ['CL']);
$assertSame([], $resultat['errors'], 'Amb CODI ESPAI continua sense errors.');
$assertSame(['simple', 2, 4], [$resultat['inventory_format'], $resultat['inventory_header_row'], $resultat['inventory_columns']['codi_espai'] ?? null], 'Detecta la capçalera a la fila 2 i la columna CODI ESPAI.');
$assertSame(
    ['S\'ha detectat el format alternatiu d\'INVENTARI; es maparan EQUIP, MODEL, UBICACIÓ, CODI ESPAI i PLANTA.'],
    $resultat['warnings'],
    'Avís del format alternatiu complet.'
);
$assertSame('SM', ImportWorkbookInspector::extractSimpleInventoryRows($book->getSheetByName('INVENTARI'))[0]['codi_espai'] ?? null, 'Llegeix el CODI ESPAI quan hi és.');

// ---------------------------------------------------------------
// 3. INVENTARI clàssic: també té EQUIP, MODEL, UBICACIÓ i PLANTA, però ha de continuar sent clàssic
// ---------------------------------------------------------------
$book = $llibre([
    ['CODI', 'TIPUS', 'Nº', 'NOM EQUIP MN', 'EQUIP', 'NOTES MN', 'MODEL', 'DÒNA SERVEI A:', 'EQUIPAMENT', 'UBICACIÓ', 'PLANTA'],
    ['CL', 'DH', 1, 'CL-DH-1', 'Deshumectadora', null, 'BCP 440', 'Piscina', 'CEM', 'Coberta P0', 'P1'],
]);
$resultat = ImportWorkbookInspector::inspect($book, ['CL']);
$assertSame('classic', $resultat['inventory_format'], 'Un INVENTARI clàssic no pot passar a detectar-se com a simple.');
$assertSame([], $resultat['errors'], 'El clàssic continua sense errors.');

// ---------------------------------------------------------------
// 4. Sense UBICACIÓ ni PLANTA no n'hi ha prou: continua bloquejant
// ---------------------------------------------------------------
$book = $llibre([
    ['EQUIP', 'MODEL'],
    ['Bomba 1', 'Grundfos'],
]);
$resultat = ImportWorkbookInspector::inspect($book, ['CL']);
$assertSame(['No es reconeixen les capçaleres de la fulla INVENTARI.'], $resultat['errors'], 'Un INVENTARI incomplet s\'ha de continuar bloquejant.');

if ($failures !== []) {
    fwrite(STDERR, "InventariSenseCodiEspaiTest failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "InventariSenseCodiEspaiTest passed\n";
