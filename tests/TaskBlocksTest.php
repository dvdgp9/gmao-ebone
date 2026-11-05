<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Models\TascaCataleg;

$failures = [];
$assertContains = static function (string $needle, string $haystack, string $message) use (&$failures): void {
    if (!str_contains($haystack, $needle)) {
        $failures[] = $message;
    }
};
$assertSame = static function (mixed $expected, mixed $actual, string $message) use (&$failures): void {
    if ($expected !== $actual) {
        $failures[] = $message . ' Expected: ' . json_encode($expected) . '; actual: ' . json_encode($actual);
    }
};

$root = dirname(__DIR__);
$schema = file_get_contents($root . '/database/schema.sql');
$migrationPath = $root . '/database/migrations/2026-11-03_tasques_cataleg_tipus.sql';
$planModel = file_get_contents($root . '/app/Models/TascaPla.php');
$planController = file_get_contents($root . '/app/Controllers/TascaPlaController.php');
$catalogController = file_get_contents($root . '/app/Controllers/TascaCatalegController.php');
$importController = file_get_contents($root . '/app/Controllers/ImportController.php');
$dayView = file_get_contents($root . '/app/views/dia/index.php');
$planForm = file_get_contents($root . '/app/views/pla/form.php');
$catalogForm = file_get_contents($root . '/app/views/tasques_cataleg/form.php');
$quickForm = file_get_contents($root . '/app/views/pla/alta_rapida.php');

// L'ordre de la jornada: obertura → normatives → preventiu → tancament.
$assertSame(['obertura', 'normativa', 'preventiu', 'tancament'], array_keys(TascaCataleg::TIPUS), 'Els blocs han de seguir l\'ordre de la jornada.');

$assertContains('`tipus` ENUM(\'obertura\', \'normativa\', \'preventiu\', \'tancament\') DEFAULT NULL', $schema, 'L\'esquema ha d\'incloure el tipus de tasca.');
$assertSame(true, is_file($migrationPath), 'Hi ha d\'haver una migració per a les instal·lacions actuals.');
if (is_file($migrationPath)) {
    $assertContains('information_schema.COLUMNS', file_get_contents($migrationPath), 'La migració ha de ser idempotent.');
}

// El tipus es posa sempre a mà: sense tipus, la tasca va a «Sense bloc» (mai es classifica sola).
$assertSame("COALESCE(tc.tipus, 'sense_bloc')", TascaCataleg::tipusSql(), 'Sense tipus, la tasca no es classifica sola.');
$assertSame(['obertura', 'normativa', 'preventiu', 'tancament', 'sense_bloc'], array_keys(TascaCataleg::blocs()), '«Sense bloc» va al final, després de tancament.');

$assertSame('tancament', TascaCataleg::tipusValid('tancament'), 'Un tipus vàlid es desa.');
$assertSame(null, TascaCataleg::tipusValid(''), 'Buit és sense tipus.');
$assertSame(null, TascaCataleg::tipusValid('sense_bloc'), '«Sense bloc» no és un tipus que es pugui desar.');
$assertSame(null, TascaCataleg::tipusValid('obertura\' OR 1=1'), 'Un valor desconegut no es desa.');

$assertSame('obertura', TascaCataleg::tipusDesDeText('Apertura'), 'L\'Excel accepta "Apertura".');
$assertSame('obertura', TascaCataleg::tipusDesDeText('Posada en marxa'), 'L\'Excel accepta "Posada en marxa".');
$assertSame('tancament', TascaCataleg::tipusDesDeText('CIERRE'), 'L\'Excel accepta "Cierre".');
$assertSame('normativa', TascaCataleg::tipusDesDeText('Normatiu'), 'L\'Excel accepta "Normatiu".');
$assertSame('preventiu', TascaCataleg::tipusDesDeText('Preventivo'), 'L\'Excel accepta "Preventivo".');
$assertSame(null, TascaCataleg::tipusDesDeText(''), 'Una cel·la buida queda sense tipus.');

// Els blocs surten sols quan alguna tasca del pla de la instal·lació té tipus; si cap en té, la llista de sempre.
$assertContains('function usaBlocsJornada(int $instalacioId)', $planModel, 'Els blocs depenen de la instal·lació, no d\'un botó.');
$assertContains('tc.tipus IS NOT NULL', $planModel, 'Només compten les tasques amb tipus posat a mà.');
$assertContains('TascaPla::usaBlocsJornada($instalacioId)', $planController, 'La Vista Diària mira el pla de la instal·lació.');
$assertSame(false, str_contains($dayView, 'dia/blocs'), 'No hi ha interruptor a la Vista Diària.');
$assertContains('<?php if ($usaBlocs): ?>', $dayView, 'Sense tipus no surten les pestanyes.');
$assertContains("'No hi ha tasques pendents per a aquest dia.'", $dayView, 'Sense blocs es manté el missatge de sempre.');

// Amb els blocs activats, la Vista Diària separa les tasques i avança al següent en acabar-ne un.
$assertContains("TascaCataleg::tipusSql() . ' AS tipus", $planModel, 'La consulta del dia ha de retornar el tipus.');
$assertContains('function fetesPerTipus', $planModel, 'Cal comptar les tasques fetes per bloc.');
$assertContains('function resolveBloc', $planController, 'El controlador ha de triar el bloc visible.');
$assertContains("\$this->get('continuar'", $planController, 'Després de registrar es continua al bloc o al següent.');
$assertContains('role="tablist"', $dayView, 'La vista ha de mostrar els blocs com a pestanyes.');
$assertContains("\$continuarBloc = \$usaBlocs ? '&continuar=' . e(\$blocActual) : '';", $dayView, 'Els registres han de tornar al bloc on s\'estava, només amb blocs.');
$assertSame(4, substr_count($dayView, '<?= $continuarBloc ?>'), 'Tots els formularis de registre han de tornar al bloc.');
$assertContains('Següent bloc', $dayView, 'Un bloc acabat ha d\'indicar el següent.');

// L'administrador pot indicar el tipus a tots els punts d'alta.
$assertContains('name="tipus"', $planForm, 'El formulari de tasca ha de tenir el tipus.');
$assertContains('name="tipus"', $catalogForm, 'El formulari del repositori ha de tenir el tipus.');
$assertContains('name="tipus[]"', $quickForm, 'L\'alta ràpida ha de tenir el tipus.');
$assertContains("'tipus' => TascaCataleg::tipusValid", $planController, 'El pla ha de desar el tipus validat.');
$assertContains("'tipus' => TascaCataleg::tipusValid", $catalogController, 'El repositori ha de desar el tipus validat.');
$assertContains("'tipus tasca'", $importController, 'La importació ha de llegir la columna TIPUS TASCA.');
$assertContains("['tipus', 'tipo', 'tipus equip', 'tipo equipo']", $importController, 'TIPUS sol continua sent el tipus d\'equip.');

if ($failures !== []) {
    fwrite(STDERR, "TaskBlocksTest failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "TaskBlocksTest passed\n";
