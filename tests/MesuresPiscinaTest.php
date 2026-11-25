<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Models\MesuraPiscina as M;
use App\Services\PlantillaBuilder;

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

// Números del formulario: coma decimal, buits i text.
$assertSame(1.5, M::numero('1,5'), 'Ha d\'acceptar la coma decimal.');
$assertSame(7.45, M::numero(' 7.45 '), 'Ha d\'acceptar el punt decimal.');
$assertSame(null, M::numero(''), 'Un camp buit no és un valor.');
$assertSame(false, M::numero('abc'), 'Un text no és un número.');
$assertSame(false, M::numero('7,4.5.'), 'Uns separadors sense sentit no són un número.');
$assertSame(1190180.0, M::numero('1.190.180'), 'Ha d\'acceptar el punt de milers del comptador.');
$assertSame(1190180.5, M::numero('1.190.180,50'), 'Milers amb punt i decimals amb coma.');
$assertSame(1190180.0, M::numero('1,190,180.00'), 'Format anglès de l\'Excel.');
$assertSame(27.8, M::numero('27,8º'), 'Ha d\'acceptar el símbol de grau.');
$assertSame(62.0, M::numero('62 %'), 'Ha d\'acceptar el símbol de percentatge.');

// Clor combinat calculat si no s'escriu.
[$v] = M::valorsDesDePost(['clor_lliure' => '1,5', 'clor_total' => '2,1']);
$assertSame(0.6, $v['clor_combinat'], 'El clor combinat és total − lliure.');
[$v] = M::valorsDesDePost(['clor_lliure' => '1,5', 'clor_total' => '2,1', 'clor_combinat' => '0,4']);
$assertSame(0.4, $v['clor_combinat'], 'Si s\'escriu el clor combinat, es respecta.');
[$v, $errors] = M::valorsDesDePost([]);
$assertSame(false, M::teAlgunValor($v), 'Sense cap valor no hi ha mesura.');
$assertSame([], $errors, 'Un formulari buit no té errors de format.');

// Valors impossibles: no es desen (abans trencaven la pàgina per desbordament de la columna).
[$v, $errors] = M::valorsDesDePost(['ph' => '745', 'humitat' => '120', 'clor_lliure' => 'molt', 'temp_aigua' => '27,8']);
$assertSame(['clor_lliure', 'ph', 'humitat'], array_keys($errors), 'pH 745, humitat 120 % i un text han de donar error.');
$assertSame(27.8, $v['temp_aigua'], 'Els valors bons es continuen llegint.');
$assertSame(null, $v['ph'], 'Un valor impossible no es desa.');

// Normes del full Param_Aigua_Piscina.
$e = M::avaluar(['clor_lliure' => 1.2, 'ph' => 7.5, 'clor_combinat' => 0.3, 'terbolesa' => 1.5, 'humitat' => 60, 'co2_interior' => 450, 'co2_exterior' => 400]);
$assertSame(['clor_lliure' => 'ok', 'clor_combinat' => 'ok', 'ph' => 'ok', 'co2_interior' => 'ok', 'humitat' => 'ok', 'terbolesa' => 'ok'], $e, 'Valors normals.');
$e = M::avaluar(['clor_lliure' => 2.4, 'ph' => 8.2, 'clor_combinat' => 0.7, 'terbolesa' => 6, 'humitat' => 70, 'co2_interior' => 950, 'co2_exterior' => 400]);
$assertSame(['clor_lliure' => 'fora', 'clor_combinat' => 'fora', 'ph' => 'fora', 'co2_interior' => 'fora', 'humitat' => 'fora', 'terbolesa' => 'fora'], $e, 'Valors fora de norma.');
$e = M::avaluar(['clor_lliure' => 0, 'ph' => 9.5, 'clor_combinat' => 3.5, 'terbolesa' => 25]);
$assertSame(['clor_lliure' => 'tancar', 'clor_combinat' => 'tancar', 'ph' => 'tancar', 'terbolesa' => 'tancar'], $e, 'Valors que obliguen a tancar el vas.');
$assertSame('tancar', M::avaluar(['clor_lliure' => 5.5])['clor_lliure'], 'Clor lliure > 5 obliga a tancar.');
$assertSame([], M::avaluar(['co2_interior' => 900]), 'Sense CO2 exterior no es pot comparar.');

// Moments fixos: clor i pH a cada moment; la resta, un cop al dia.
$assertSame(['inici', 'migdia', 'final'], array_keys(M::MOMENTS), 'Tres moments fixos: inici, 14 h i final.');
$assertSame(['clor_lliure', 'clor_total', 'clor_combinat', 'ph'], M::campsCadaMoment(), 'El clor i el pH es mesuren a cada moment.');
$assertSame(true, in_array('aigua_nova', M::campsDiaris(), true), "L'aigua nova és un paràmetre d'un cop al dia.");
$assertSame(true, in_array('terbolesa', M::campsDiaris(), true), "La terbolesa és d'un cop al dia.");

// Sense els d'un cop al dia (ja desats en un altre moment) no es llegeixen ni es toquen.
[$v] = M::valorsDesDePost(['ph' => '7,4', 'terbolesa' => '0,3', 'aigua_nova' => '12'], false);
$assertSame(['clor_lliure', 'clor_total', 'clor_combinat', 'ph'], array_keys($v), 'Només el clor i el pH.');
[$v] = M::valorsDesDePost(['aigua_nova' => '1.250.300']);
$assertSame(1250300.0, $v['aigua_nova'], "Es llegeix el comptador d'aigua nova.");
[$v] = M::valorsDesDePost(['terbolesa' => '0,3'], false);
$assertSame(false, M::teAlgunValor($v), 'Si només hi ha diaris que no toquen, no hi ha mesura.');

// Moment proposat segons l'hora; si ja està fet, el primer que falta.
$assertSame('inici', M::momentProposat([], 8), 'Al matí, inici.');
$assertSame('migdia', M::momentProposat([], 14), 'A les 14 h, el del migdia.');
$assertSame('final', M::momentProposat([], 20), 'Al vespre, final.');
$assertSame('migdia', M::momentProposat(['inici' => []], 9), "Inici ja fet al matí: el següent.");
$assertSame('inici', M::momentProposat(['final' => []], 21), 'Final ja fet: el primer que falta.');

// On són els paràmetres d'un cop al dia.
$dia = ['inici' => ['ph' => 7.4, 'terbolesa' => null], 'final' => ['ph' => 7.3, 'terbolesa' => 0.2]];
$assertSame('final', M::momentAmbDiaris($dia), 'La terbolesa es va desar al final.');
$assertSame(null, M::momentAmbDiaris($dia, 'final'), 'Corregint el final, els diaris es tornen a demanar.');
$assertSame(null, M::momentAmbDiaris(['inici' => ['ph' => 7.4]]), 'Sense diaris encara.');

// Codi: tasques amb l'espai tancat, formulari a la Vista Diària i importació.
$root = dirname(__DIR__);
$tascaPla = file_get_contents($root . '/app/Models/TascaPla.php');
$dashboard = file_get_contents($root . '/app/Controllers/DashboardController.php');
$assertContains('tp.fer_amb_espai_tancat = 1', $tascaPla, 'Les tasques marcades han de sortir amb l\'espai tancat.');
$assertSame(0, substr_count($tascaPla . $dashboard, '(tp.espai_id IS NULL OR es.actiu = 1)'), 'Cap consulta ha d\'amagar les tasques marcades per fer amb l\'espai tancat.');
$diaView = file_get_contents($root . '/app/views/dia/index.php');
$assertContains("isset(\$mesures[\$t['id']])", $diaView, 'La Vista Diària ha de mostrar el botó de paràmetres a les tasques de mesura.');
$assertContains("'mesura piscina'", file_get_contents($root . '/app/Controllers/ImportController.php'), 'La importació ha de llegir la columna MESURA PISCINA.');
$assertSame(true, in_array('MESURA PISCINA', PlantillaBuilder::tasquesColumns(['espais']), true), 'La plantilla ha de tenir la columna MESURA PISCINA.');
$routes = file_get_contents($root . '/app/Config/routes.php');
$assertContains("'parametres/store'", $routes, 'Ha d\'existir la ruta per desar els paràmetres.');
$assertContains("'parametres/excel'", $routes, 'Ha d\'existir la ruta per descarregar l\'Excel de paràmetres.');

// Agrupació com el full de paràmetres i Excel per a inspeccions.
$buit = array_fill_keys(array_keys(M::PARAMETRES), null);
$mesura = static fn(string $data, int $tp, string $vas, string $moment, array $valors, string $obs = '', string $nom = 'Anna') => array_merge($buit, $valors, [
    'data' => $data, 'tasca_pla_id' => $tp, 'espai_nom' => $vas, 'tasca_nom' => 'Mesura', 'tasca_codi' => 'TE-1',
    'moment' => $moment, 'observacions' => $obs, 'usuari_nom' => $nom, 'usuari_cognoms' => 'Puig',
]);
$mesures = [
    $mesura('2026-11-24', 1, 'Vas gran', 'migdia', ['clor_lliure' => 2.5, 'ph' => 7.4], 'Aigua tèrbola', 'Joan'),
    $mesura('2026-11-24', 1, 'Vas gran', 'inici', ['clor_lliure' => 1.2, 'ph' => 9.5, 'temp_aigua' => 27.8, 'humitat' => 70]),
    $mesura('2026-11-23', 1, 'Vas gran', 'inici', ['clor_lliure' => 1.0, 'ph' => 7.5]),
    $mesura('2026-11-24', 2, 'Vas petit', 'inici', ['clor_lliure' => 0.8, 'ph' => 7.3, 'temp_aigua' => 30]),
];
$files = M::agrupar($mesures);
$assertSame(3, count($files), 'Una fila per dia i vas.');
$assertSame(['migdia', 'inici'], array_keys($files[0]['moments']), 'Els moments del mateix dia i vas van a la mateixa fila.');
$assertSame(27.8, $files[0]['diaris']['temp_aigua'], 'Els paràmetres diaris surten a la fila del dia.');
$assertSame(M::FORA, $files[0]['estatsDiaris']['humitat'] ?? null, 'La humitat alta és fora de norma.');
$assertSame(['Joan', 'Anna'], array_map(static fn($t) => explode(' ', $t)[0], $files[0]['tecnics']), 'Surten tots els que han mesurat.');
$assertSame(['Mant. 14 h: Aigua tèrbola'], $files[0]['observacions'], 'Les observacions porten el moment.');

$xlsx = \App\Services\ParametresExcel::build($files, ['instalacio' => 'CEM Prova', 'desde' => '2026-11-23', 'fins' => '2026-11-24']);
$assertSame(['Vas gran', 'Vas petit'], $xlsx->getSheetNames(), 'Un full per vas, en ordre alfabètic.');
$full = $xlsx->getSheetByName('Vas gran');
$cols = \App\Services\ParametresExcel::columnes(true, M::campsDiarisVisibles($files));
$col = static function (string $camp, ?string $moment = null) use ($cols): string {
    foreach ($cols as $i => [$c, $m]) {
        if ($c === $camp && $m === $moment) {
            return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
        }
    }
    return '?';
};
$assertSame('23/11/2026', $full->getCell('A7')->getValue(), 'Els dies van en ordre ascendent, com el full en paper.');
$assertSame('24/11/2026', $full->getCell('A8')->getValue(), 'El segon dia va a la fila següent.');
$assertSame(1.2, $full->getCell($col('clor_lliure', 'inici') . '8')->getValue(), 'El clor de l\'inici va a la columna 1.');
$assertSame(2.5, $full->getCell($col('clor_lliure', 'migdia') . '8')->getValue(), 'El clor de les 14 h va a la columna 2.');
$assertSame(null, $full->getCell($col('clor_lliure', 'final') . '8')->getValue(), 'Sense mesura del final, la cel·la queda buida.');
$color = static fn(string $cel): string => $full->getStyle($cel)->getFill()->getFillType() === 'solid' ? $full->getStyle($cel)->getFill()->getStartColor()->getRGB() : '';
$assertSame(\App\Services\ParametresExcel::GROC, $color($col('clor_lliure', 'migdia') . '8'), 'El clor fora de norma surt en groc.');
$assertSame(\App\Services\ParametresExcel::VERMELL, $color($col('ph', 'inici') . '8'), 'El pH de tancar el vas surt en vermell.');
$assertSame('', $color($col('clor_lliure', 'inici') . '8'), 'Un valor correcte no té color.');
$assertSame(\App\Services\ParametresExcel::GROC, $color($col('humitat') . '8'), 'Els paràmetres diaris també es marquen.');
$assertContains('Aigua tèrbola', (string)$full->getCell($col('observacions') . '8')->getValue(), 'Les observacions surten a l\'Excel.');
$assertContains('CEM Prova', (string)$full->getCell('A2')->getValue(), 'La capçalera porta la instal·lació.');
$assertSame('?', (function () { foreach (\App\Services\ParametresExcel::columnes(false) as [$c]) { if ($c === 'tecnic') return 'tecnic'; } return '?'; })(), 'Els tècnics no tenen la columna Tècnic.');
$assertSame(['Sense mesures'], \App\Services\ParametresExcel::build([], ['desde' => '2026-11-01', 'fins' => '2026-11-02'])->getSheetNames(), 'Sense mesures surt un full que ho explica.');
$llargs = M::agrupar([$mesura('2026-11-24', 3, 'Piscina coberta: vas d\'aprenentatge [nou]', 'inici', []), $mesura('2026-11-24', 4, 'Piscina coberta: vas d\'aprenentatge [nou] 2', 'inici', [])]);
$noms = \App\Services\ParametresExcel::build($llargs, ['desde' => '2026-11-24', 'fins' => '2026-11-24'])->getSheetNames();
$assertSame(2, count(array_unique($noms)), 'Noms de pestanya únics encara que es tallin.');

// Segon comptador de recirculació: opcional, només surt si algun vas l'ha omplert.
$assertSame(true, isset(M::PARAMETRES['recirculacio_2']) && !M::PARAMETRES['recirculacio_2']['cada_moment'], 'Hi ha d’haver un segon comptador de recirculació, un cop al dia.');
[$v, $errors] = M::valorsDesDePost(['recirculacio' => '1.190.180', 'recirculacio_2' => '845300']);
$assertSame(845300.0, $v['recirculacio_2'], 'El comptador 2 es desa.');
[$v] = M::valorsDesDePost(['recirculacio' => '845.300', 'recirculacio_2' => '12.500', 'aigua_nova' => '50.236', 'temp_aigua' => '27.800', 'clor_lliure' => '1.500']);
$assertSame(845300.0, $v['recirculacio'], 'En un comptador, «845.300» és 845300.');
$assertSame(12500.0, $v['recirculacio_2'], 'També al comptador 2.');
$assertSame(50236.0, $v['aigua_nova'], 'També al comptador d’aigua nova.');
$assertSame(1.5, $v['clor_lliure'], 'Al clor, el punt continua sent decimal.');
$assertSame(845.35, M::numero('845.35', true), 'Amb dues xifres després del punt, és decimal encara que sigui un comptador.');
$assertSame(845.3, M::numero('845.300'), 'Fora dels comptadors no canvia res.');
$assertSame([], $errors, 'Els dos comptadors són vàlids.');
$assertSame(false, in_array('recirculacio_2', M::campsDiarisVisibles($files), true), 'Sense valors, la columna del comptador 2 no surt.');
$amb2 = M::agrupar([$mesura('2026-11-24', 5, 'Vas doble', 'inici', ['recirculacio' => 100, 'recirculacio_2' => 200])]);
$assertSame(true, in_array('recirculacio_2', M::campsDiarisVisibles($amb2), true), 'Amb valors, la columna del comptador 2 surt.');
$fullAmb2 = \App\Services\ParametresExcel::build($amb2, ['desde' => '2026-11-24', 'fins' => '2026-11-24'])->getSheet(0);
$capcalera = [];
foreach ($fullAmb2->getRowIterator(5, 5) as $r) { foreach ($r->getCellIterator() as $c) { $capcalera[] = (string)$c->getValue(); } }
$assertSame(true, in_array('Recirculació (comptador 2)', $capcalera, true), 'L’Excel del vas amb 2 comptadors té la columna.');
$assertSame(false, in_array('Recirculació (comptador 2)', array_map(static fn($c) => (string)$c, iterator_to_array((function () use ($xlsx) { foreach ($xlsx->getSheet(0)->getRowIterator(5, 5) as $r) { foreach ($r->getCellIterator() as $c) { yield $c->getValue(); } } })(), false)), true), 'L’Excel d’un vas amb un comptador no té la columna 2.');
$assertContains('recirculacio_2', file_get_contents($root . '/database/migrations/2026-11-25_recirculacio_comptador_2.sql'), 'Hi ha d’haver la migració del comptador 2.');
$assertContains('recirculacio_2', file_get_contents($root . '/database/schema.sql'), 'El schema ha de tenir el comptador 2.');
$assertSame(true, max(array_map('mb_strlen', $noms)) <= 31, 'Els noms de pestanya no passen de 31 caràcters.');

if ($failures !== []) {
    fwrite(STDERR, "MesuresPiscinaTest failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "MesuresPiscinaTest passed\n";
