<?php
/**
 * Debug: mostra les capçaleres de cada fulla de l'Excel
 * ELIMINAR DESPRÉS D'USAR!
 */
$token = $_GET['token'] ?? '';
if ($token !== 'ebone2026import') {
    http_response_code(403);
    die('Accés denegat.');
}

set_time_limit(120);
ini_set('memory_limit', '256M');
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$excelPath = __DIR__ . '/../6970815c3ed12_1768980828.xlsx';
if (!file_exists($excelPath)) {
    die("Excel no trobat: {$excelPath}\n");
}

$spreadsheet = IOFactory::load($excelPath);

foreach ($spreadsheet->getSheetNames() as $sheetName) {
    $ws = $spreadsheet->getSheetByName($sheetName);
    $highCol = $ws->getHighestColumn();
    $highRow = $ws->getHighestRow();
    
    echo "========================================\n";
    echo "FULLA: {$sheetName} ({$highRow} files, fins columna {$highCol})\n";
    echo "========================================\n";
    
    // Capçaleres (fila 1)
    echo "CAPÇALERES:\n";
    $col = 'A';
    while ($col !== $highCol) {
        $val = trim((string)$ws->getCell("{$col}1")->getValue());
        if ($val) echo "  {$col}: {$val}\n";
        $col++;
    }
    $val = trim((string)$ws->getCell("{$highCol}1")->getValue());
    if ($val) echo "  {$highCol}: {$val}\n";
    
    // Mostra 3 files de mostra (fila 2-4)
    echo "\nMOSTRA (files 2-4):\n";
    for ($row = 2; $row <= min(4, $highRow); $row++) {
        echo "  Fila {$row}: ";
        $col = 'A';
        $parts = [];
        while ($col !== $highCol) {
            $v = $ws->getCell("{$col}{$row}")->getValue();
            if ($v !== null && $v !== '') {
                $vStr = is_numeric($v) ? $v : mb_substr((string)$v, 0, 30);
                $parts[] = "{$col}=" . $vStr;
            }
            $col++;
        }
        $v = $ws->getCell("{$highCol}{$row}")->getValue();
        if ($v !== null && $v !== '') {
            $parts[] = "{$highCol}=" . (is_numeric($v) ? $v : mb_substr((string)$v, 0, 30));
        }
        echo implode(' | ', $parts) . "\n";
    }
    echo "\n";
}
