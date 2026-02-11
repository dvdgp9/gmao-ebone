<?php
/**
 * Wrapper web per executar import_excel.php des del navegador.
 * ELIMINAR DESPRÉS D'USAR!
 * 
 * Accedir a: https://gmao.ebone.es/run_import.php
 */

// Protecció bàsica amb token
$token = $_GET['token'] ?? '';
if ($token !== 'ebone2026import') {
    http_response_code(403);
    die('Accés denegat. Afegeix ?token=ebone2026import a la URL.');
}

set_time_limit(300); // 5 minuts màxim
ini_set('memory_limit', '256M');

header('Content-Type: text/plain; charset=utf-8');

echo "=== Iniciant importació Excel ===\n\n";
ob_flush();
flush();

// Capturar la sortida del script
ob_start();
try {
    require __DIR__ . '/../database/import_excel.php';
} catch (\Throwable $e) {
    echo "\n\nERROR: " . $e->getMessage() . "\n";
    echo "Fitxer: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
$output = ob_get_clean();

echo $output;
echo "\n\n=== Fi ===\n";
echo "IMPORTANT: Elimina aquest fitxer (run_import.php) del servidor!\n";
