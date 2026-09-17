<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\UsuariImportParser;

$failures = [];

$assertSame = static function (mixed $expected, mixed $actual, string $message) use (&$failures): void {
    if ($expected !== $actual) {
        $failures[] = $message . ' Expected: ' . json_encode($expected, JSON_UNESCAPED_UNICODE) . '; actual: ' . json_encode($actual, JSON_UNESCAPED_UNICODE);
    }
};

$pick = static function (array $rows, array $fields): array {
    return array_map(static fn(array $row) => array_intersect_key($row, array_flip($fields)), $rows);
};

// 1. Llista real rebuda per correu: punt, espais variables i una línia en blanc.
$text = "Raul Duran.    raulduran@ebone.es\n"
    . "David Pino.     davidpino@ebone.es\n"
    . "Juan Garcia.    juangarcia@ebone.es\n"
    . "Jordi Guerrero. jordiguerrero@ebone.es\n"
    . "Juan Chacon.   juanchacon@ebone.es\n"
    . "Jordi Naranjo.   jordinaranjo@ebone.es\n"
    . "\n"
    . "\n"
    . "Alfons Carmona. acarmonap@santjoandespi.cat\n";
$result = UsuariImportParser::parseText($text);
$assertSame(7, count($result['files']), 'La llista real ha de donar 7 persones.');
$assertSame(
    ['nom' => 'Raul', 'cognoms' => 'Duran', 'email' => 'raulduran@ebone.es'],
    $pick($result['files'], ['nom', 'cognoms', 'email'])[0],
    'Ha de separar nom, cognoms i email sense el punt final.'
);
$assertSame(
    ['nom' => 'Alfons', 'cognoms' => 'Carmona', 'email' => 'acarmonap@santjoandespi.cat'],
    $pick($result['files'], ['nom', 'cognoms', 'email'])[6],
    'La persona després de les línies en blanc també s\'ha d\'importar.'
);
$assertSame([], $result['avisos'], 'La llista real no ha de generar avisos.');

// 2. Columnes enganxades d'Excel amb capçalera en castellà.
$tsv = "Nombre\tApellidos\tCorreo\tPuesto\tTurnos\tInstalación\n"
    . "Marta\tSoler Vidal\tMSoler@ebone.es\tTécnico\tMañana, Tarde\tCEM Sant Joan\n"
    . "Pere\tRius\tpere.rius@ebone.es\tJefe de mantenimiento\tTodos\tCEM Sant Joan\n";
$result = UsuariImportParser::parseText($tsv);
$assertSame(2, count($result['files']), 'El TSV amb capçalera ha de donar 2 files.');
$assertSame(
    [
        'nom' => 'Marta',
        'cognoms' => 'Soler Vidal',
        'email' => 'msoler@ebone.es',
        'rol' => 'Técnico',
        'torns' => 'Mañana, Tarde',
        'instalacio' => 'CEM Sant Joan',
    ],
    $pick($result['files'], ['nom', 'cognoms', 'email', 'rol', 'torns', 'instalacio'])[0],
    'Ha de mapar les capçaleres en castellà i passar l\'email a minúscules.'
);

// 3. Dues columnes de cognoms es concatenen; una columna de nom complet es parteix.
$tsv = "Nom i cognoms\tPrimer cognom\tSegon cognom\tCorreu electrònic\n"
    . "Anna\tPuig\tFerrer\tanna@ebone.es\n";
$result = UsuariImportParser::parseText($tsv);
$assertSame('Puig Ferrer', $result['files'][0]['cognoms'] ?? null, 'Primer i segon cognom s\'han d\'unir.');

$tsv = "Trabajador\tEmail\tDNI\n"
    . "Luis Martín Gómez\tluis@ebone.es\t12345678Z\n";
$result = UsuariImportParser::parseText($tsv);
$assertSame(
    ['nom' => 'Luis', 'cognoms' => 'Martín Gómez'],
    $pick($result['files'], ['nom', 'cognoms'])[0],
    'La columna de nom complet s\'ha de partir en nom i cognoms.'
);
$assertSame(['Columnes ignorades: DNI'], $result['avisos'], 'Ha d\'avisar de les columnes que no fa servir.');

// 4. Format d'Outlook en una sola línia.
$result = UsuariImportParser::parseText('Raul Duran <raulduran@ebone.es>; "Pino, David" <davidpino@ebone.es>');
$assertSame(
    [
        ['nom' => 'Raul', 'cognoms' => 'Duran', 'email' => 'raulduran@ebone.es'],
        ['nom' => 'David', 'cognoms' => 'Pino', 'email' => 'davidpino@ebone.es'],
    ],
    $pick($result['files'], ['nom', 'cognoms', 'email']),
    'Ha d\'entendre el format d\'Outlook, inclòs «Cognom, Nom».'
);

// 5. CSV amb punt i coma sense capçalera: les columnes sobrants van a «extra».
$result = UsuariImportParser::parseText("Joan;Serra Puig;joan@ebone.cat;Tècnic\n");
$assertSame(
    ['nom' => 'Joan', 'cognoms' => 'Serra Puig', 'email' => 'joan@ebone.cat', 'extra' => ['Tècnic']],
    $pick($result['files'], ['nom', 'cognoms', 'email', 'extra'])[0],
    'El CSV sense capçalera ha de posar el que sobra a extra.'
);

// 6. Majúscules, numeració i guions en text lliure.
$result = UsuariImportParser::parseText("1. JOSE LUIS GARCIA LOPEZ - Tècnic - jlgarcia@ebone.es\n");
$assertSame(
    ['nom' => 'Jose Luis', 'cognoms' => 'Garcia Lopez', 'email' => 'jlgarcia@ebone.es', 'extra' => ['Tècnic']],
    $pick($result['files'], ['nom', 'cognoms', 'email', 'extra'])[0],
    'Ha de treure la numeració, passar a majúscula inicial i separar dades extra.'
);

// 7. Línies sense email en text lliure: s'ignoren amb avís.
$result = UsuariImportParser::parseText("Treballadors del CEM:\nRaul Duran raulduran@ebone.es\n");
$assertSame(1, count($result['files']), 'La línia de títol no és cap persona.');
$assertSame(['S\'ha ignorat 1 línia sense email: «Treballadors del CEM:»'], $result['avisos'], 'Ha d\'avisar de la línia ignorada.');

// 8. Taula amb capçalera: una fila sense email es manté perquè l'usuari la completi.
$result = UsuariImportParser::parseTable([
    ['Nom', 'Cognoms', 'Email'],
    ['Laia', 'Costa', ''],
    ['', '', ''],
]);
$assertSame(1, count($result['files']), 'Les files buides s\'ignoren però la fila sense email es manté.');
$assertSame('', $result['files'][0]['email'] ?? null, 'L\'email buit es manté buit.');

// 8b. Un email mal escrit a la columna d'email es conserva perquè es vegi i es corregeixi.
$result = UsuariImportParser::parseTable([
    ['Nom', 'Correo'],
    ['Pere Rius', 'pere.rius@ebone'],
]);
$assertSame('pere.rius@ebone', $result['files'][0]['email'] ?? null, 'L\'email invàlid no s\'ha de perdre.');

// 9. Noms compostos.
$assertSame(['Juan', 'García López'], UsuariImportParser::splitFullName('Juan García López'), 'Tres paraules: nom + dos cognoms.');
$assertSame(['Raül', 'Duran Pérez'], UsuariImportParser::splitFullName('Duran Pérez, Raül'), 'Format «Cognoms, Nom».');
$assertSame(['Carmona', ''], UsuariImportParser::splitFullName('Carmona'), 'Una sola paraula és el nom.');

// 10. Normalització sense dependre d'iconv.
$assertSame('instalacio', UsuariImportParser::normalize('Instal·lació'), 'Ha de normalitzar la ela geminada.');
$assertSame('correo electronico', UsuariImportParser::normalize(' Correo-Electrónico '), 'Ha de treure accents i signes.');

// 11. Límit de files.
$molts = '';
for ($i = 0; $i < UsuariImportParser::MAX_FILES + 5; $i++) {
    $molts .= "Persona {$i} p{$i}@ebone.es\n";
}
$result = UsuariImportParser::parseText($molts);
$assertSame(UsuariImportParser::MAX_FILES, count($result['files']), 'No ha de superar el màxim de files.');

if ($failures !== []) {
    fwrite(STDERR, "UsuariImportParserTest failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "UsuariImportParserTest passed\n";
