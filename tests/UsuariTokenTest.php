<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Models\UsuariToken;

$failures = [];

$assertSame = static function (mixed $expected, mixed $actual, string $message) use (&$failures): void {
    if ($expected !== $actual) {
        $failures[] = $message . ' Expected: ' . json_encode($expected) . '; actual: ' . json_encode($actual);
    }
};

// Parts pures del model: no toquen la base de dades.
$token = UsuariToken::generarToken();
$assertSame(1, preg_match('/^[a-f0-9]{64}$/', $token), 'El token ha de tenir 64 caràcters hexadecimals.');
$assertSame(true, $token !== UsuariToken::generarToken(), 'Cada token ha de ser diferent.');
$assertSame(true, UsuariToken::formatValid($token), 'Un token generat té format vàlid.');
$assertSame(false, UsuariToken::formatValid('../../etc'), 'Un text qualsevol no és un token.');
$assertSame(false, UsuariToken::formatValid(strtoupper($token)), 'Només hexadecimal en minúscules.');

$assertSame(hash('sha256', $token), UsuariToken::hash($token), 'A la BD només es guarda el SHA-256 del token.');
$assertSame(true, UsuariToken::hash($token) !== $token, 'El hash no pot ser el token.');

$ara = strtotime('2026-09-17 10:00:00');
$assertSame('2026-09-24 10:00:00', UsuariToken::caducitat($ara), 'L\'enllaç caduca als 7 dies.');

$hashInutilitzable = UsuariToken::hashInutilitzable();
$assertSame(true, password_get_info($hashInutilitzable)['algo'] !== null, 'Ha de ser un hash de contrasenya real.');
$assertSame(false, password_verify('', $hashInutilitzable), 'Una contrasenya buida no pot entrar.');
$assertSame(false, password_verify('admin123', $hashInutilitzable), 'Cap contrasenya coneguda pot entrar.');

// Guardes de seguretat al codi que sí toca BD.
$model = file_get_contents(dirname(__DIR__) . '/app/Models/UsuariToken.php');
$assertSame(true, str_contains($model, 'used_at IS NULL'), 'Un token usat no ha de tornar a servir.');
$assertSame(true, str_contains($model, 'u.actiu = 1'), 'Un usuari desactivat no pot fer servir el seu enllaç.');

if ($failures !== []) {
    fwrite(STDERR, "UsuariTokenTest failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "UsuariTokenTest passed\n";
