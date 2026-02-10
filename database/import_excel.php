<?php
/**
 * Script d'importació inicial del Excel GMAO
 * Execució: php database/import_excel.php
 * 
 * Importa: Instal·lació, Espais, Equips, Tasques Catàleg, Torns,
 *          Pla de Manteniment, Registre de Tasques
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use PhpOffice\PhpSpreadsheet\IOFactory;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$db = $_ENV['DB_DATABASE'] ?? 'gmao_ebone';
$user = $_ENV['DB_USERNAME'] ?? 'root';
$pass = $_ENV['DB_PASSWORD'] ?? '';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$db};charset={$charset}",
    $user, $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$excelPath = __DIR__ . '/../6970815c3ed12_1768980828.xlsx';
if (!file_exists($excelPath)) {
    die("ERROR: No es troba el fitxer Excel a: {$excelPath}\n");
}

echo "Carregant Excel...\n";
$spreadsheet = IOFactory::load($excelPath);
echo "Excel carregat correctament.\n\n";

// =====================================================================
// 1. Crear instal·lació inicial
// =====================================================================
echo "=== 1. Creant instal·lació ===\n";
$stmt = $pdo->prepare('INSERT INTO instalacions (nom, adreca, activa) VALUES (?, ?, 1)');
$stmt->execute(['CEMCERVERA', 'Cem Cervera']);
$instalacioId = (int)$pdo->lastInsertId();
echo "  Instal·lació creada: ID={$instalacioId}\n";

// Assignar superadmin (usuari ID=1) a la instal·lació
$stmtRol = $pdo->prepare('SELECT id FROM rols WHERE nom = "superadmin" LIMIT 1');
$stmtRol->execute();
$rolSuperadmin = $stmtRol->fetch();
if ($rolSuperadmin) {
    $pdo->prepare('INSERT IGNORE INTO usuari_instalacio (usuari_id, instalacio_id, rol_id) VALUES (1, ?, ?)')
        ->execute([$instalacioId, $rolSuperadmin['id']]);
    echo "  Superadmin assignat a la instal·lació\n";
}

// =====================================================================
// 2. Crear Torns
// =====================================================================
echo "\n=== 2. Creant torns ===\n";
$tornsData = [
    ['Cap Manteniment', '["dll","dm","dx","dj","dv"]'],
    ['Matí', '["dll","dm","dx","dj","dv"]'],
    ['Tarda', '["dll","dm","dx","dj","dv"]'],
    ['Cap de Setmana', '["ds","dg"]'],
];
$tornMap = [];
$stmtTorn = $pdo->prepare('INSERT INTO torns (instalacio_id, nom, dies_setmana, actiu) VALUES (?, ?, ?, 1)');
foreach ($tornsData as $t) {
    $stmtTorn->execute([$instalacioId, $t[0], $t[1]]);
    $tornMap[$t[0]] = (int)$pdo->lastInsertId();
    echo "  Torn: {$t[0]} -> ID={$tornMap[$t[0]]}\n";
}

// =====================================================================
// 3. Importar Espais (de la hoja LLISTES)
// =====================================================================
echo "\n=== 3. Important espais ===\n";
$wsLlistes = $spreadsheet->getSheetByName('LLISTES');
$espaiMap = [];
$stmtEspai = $pdo->prepare('INSERT INTO espais (instalacio_id, codi, nom, planta, actiu) VALUES (?, ?, ?, ?, 1)');
$espaiCount = 0;

for ($row = 2; $row <= $wsLlistes->getHighestRow(); $row++) {
    $nom = trim((string)$wsLlistes->getCell("C{$row}")->getValue());
    $codi = trim((string)$wsLlistes->getCell("D{$row}")->getValue());
    $planta = trim((string)$wsLlistes->getCell("E{$row}")->getValue());
    
    if (empty($nom)) continue;
    
    $stmtEspai->execute([$instalacioId, $codi ?: null, $nom, $planta ?: null]);
    $id = (int)$pdo->lastInsertId();
    $espaiMap[mb_strtolower($nom)] = $id;
    $espaiCount++;
}
echo "  {$espaiCount} espais importats\n";

// =====================================================================
// 4. Carregar mapes de catàlegs existents (sistemes, tipus_equip, etc.)
// =====================================================================
echo "\n=== 4. Carregant catàlegs ===\n";
$sistemaMap = [];
foreach ($pdo->query('SELECT id, codi FROM sistemes')->fetchAll() as $r) {
    $sistemaMap[mb_strtolower($r['codi'])] = (int)$r['id'];
}
echo "  " . count($sistemaMap) . " sistemes carregats\n";

$tipusMap = [];
foreach ($pdo->query('SELECT id, codi FROM tipus_equip')->fetchAll() as $r) {
    $tipusMap[mb_strtolower($r['codi'])] = (int)$r['id'];
}
echo "  " . count($tipusMap) . " tipus equip carregats\n";

$estatMap = [];
foreach ($pdo->query('SELECT id, nom FROM estats_equip')->fetchAll() as $r) {
    $estatMap[mb_strtolower($r['nom'])] = (int)$r['id'];
}

$periodicitatMap = [];
foreach ($pdo->query('SELECT id, nom FROM periodicitats')->fetchAll() as $r) {
    $periodicitatMap[mb_strtolower($r['nom'])] = (int)$r['id'];
}

$normativaMap = [];
foreach ($pdo->query('SELECT id, nom FROM normatives')->fetchAll() as $r) {
    $normativaMap[mb_strtolower($r['nom'])] = (int)$r['id'];
}

// =====================================================================
// 5. Importar Equips (INVENTARI)
// =====================================================================
echo "\n=== 5. Important equips (INVENTARI) ===\n";
$wsInv = $spreadsheet->getSheetByName('INVENTARI');
$equipMap = []; // nom_mn -> id
$stmtEquip = $pdo->prepare('
    INSERT INTO equips (instalacio_id, sistema_id, tipus_equip_id, numero, nom_mn, nom_equip, 
                        notes, model, dona_servei_a, equipament, planta, empresa_mantenedora,
                        data_installacio, fi_garantia, estat_id, actiu)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
');
$equipCount = 0;
$lastSistema = null;

for ($row = 2; $row <= $wsInv->getHighestRow(); $row++) {
    $nomEquip = trim((string)$wsInv->getCell("E{$row}")->getValue());
    if (empty($nomEquip)) continue;
    
    $codiSistema = trim((string)$wsInv->getCell("A{$row}")->getValue());
    if ($codiSistema) $lastSistema = $codiSistema;
    
    $codiTipus = trim((string)$wsInv->getCell("B{$row}")->getValue());
    $numero = $wsInv->getCell("C{$row}")->getValue();
    $nomMn = trim((string)$wsInv->getCell("D{$row}")->getValue());
    $notes = trim((string)$wsInv->getCell("F{$row}")->getValue());
    $model = trim((string)$wsInv->getCell("G{$row}")->getValue());
    $donaServei = trim((string)$wsInv->getCell("H{$row}")->getValue());
    $equipament = trim((string)$wsInv->getCell("I{$row}")->getValue());
    $planta = trim((string)$wsInv->getCell("K{$row}")->getValue());
    $empresa = trim((string)$wsInv->getCell("L{$row}")->getValue());
    
    $dataInst = $wsInv->getCell("M{$row}")->getValue();
    $fiGar = $wsInv->getCell("N{$row}")->getValue();
    $estat = trim((string)$wsInv->getCell("O{$row}")->getValue());
    
    // Convertir dates
    $dataInstStr = null;
    if ($dataInst) {
        if (is_numeric($dataInst)) {
            $dataInstStr = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$dataInst)->format('Y-m-d');
        } elseif ($dataInst instanceof \DateTimeInterface) {
            $dataInstStr = $dataInst->format('Y-m-d');
        }
    }
    $fiGarStr = null;
    if ($fiGar) {
        if (is_numeric($fiGar)) {
            $fiGarStr = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$fiGar)->format('Y-m-d');
        } elseif ($fiGar instanceof \DateTimeInterface) {
            $fiGarStr = $fiGar->format('Y-m-d');
        }
    }
    
    $sistemaId = $sistemaMap[mb_strtolower($lastSistema ?? '')] ?? null;
    $tipusId = $tipusMap[mb_strtolower($codiTipus)] ?? null;
    $estatId = $estatMap[mb_strtolower($estat)] ?? null;
    
    $stmtEquip->execute([
        $instalacioId, $sistemaId, $tipusId,
        $numero ? (int)$numero : null,
        $nomMn ?: null, $nomEquip,
        $notes ?: null, $model ?: null, $donaServei ?: null, $equipament ?: null,
        $planta ?: null, $empresa ?: null,
        $dataInstStr, $fiGarStr, $estatId
    ]);
    
    $id = (int)$pdo->lastInsertId();
    if ($nomMn) {
        $equipMap[mb_strtolower($nomMn)] = $id;
    }
    $equipCount++;
}
echo "  {$equipCount} equips importats\n";

// =====================================================================
// 6. Importar Tasques Catàleg (BD TASQUES)
// =====================================================================
echo "\n=== 6. Important tasques catàleg (BD TASQUES) ===\n";
$wsBd = $spreadsheet->getSheetByName('BD TASQUES');
$tascaCatalegMap = []; // nom_tasca -> id
$stmtTC = $pdo->prepare('
    INSERT INTO tasques_cataleg (codi, sistema_id, tipus_equip_id, nom, periodicitat_normativa_id, 
                                 normativa_id, empresa_responsable, activa)
    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
');
$tcCount = 0;
$lastCodiSistema = null;

for ($row = 2; $row <= $wsBd->getHighestRow(); $row++) {
    $nom = trim((string)$wsBd->getCell("D{$row}")->getValue());
    if (empty($nom)) continue;
    
    $codi = trim((string)$wsBd->getCell("A{$row}")->getValue());
    if ($codi) $lastCodiSistema = $codi;
    
    $codiTipus = trim((string)$wsBd->getCell("B{$row}")->getValue());
    $periodicitat = trim((string)$wsBd->getCell("E{$row}")->getValue());
    $normativa = trim((string)$wsBd->getCell("F{$row}")->getValue());
    $empresa = trim((string)$wsBd->getCell("H{$row}")->getValue());
    
    $sistemaId = $sistemaMap[mb_strtolower($lastCodiSistema ?? '')] ?? null;
    $tipusId = $tipusMap[mb_strtolower($codiTipus)] ?? null;
    $periodicitatId = $periodicitatMap[mb_strtolower($periodicitat)] ?? null;
    
    // Buscar normativa por nombre parcial
    $normativaId = null;
    if ($normativa) {
        $normLow = mb_strtolower($normativa);
        foreach ($normativaMap as $nNom => $nId) {
            if (str_contains($normLow, mb_strtolower(explode(',', $nNom)[0])) || 
                str_contains(mb_strtolower($nNom), explode(',', $normLow)[0])) {
                $normativaId = $nId;
                break;
            }
        }
    }
    
    $stmtTC->execute([
        $lastCodiSistema, $sistemaId, $tipusId, $nom, $periodicitatId, $normativaId, $empresa ?: null
    ]);
    
    $id = (int)$pdo->lastInsertId();
    $tascaCatalegMap[mb_strtolower(mb_substr($nom, 0, 80))] = $id;
    $tcCount++;
}
echo "  {$tcCount} tasques catàleg importades\n";

// =====================================================================
// 7. Importar Pla de Manteniment (TASQUES PLA_M)
// =====================================================================
echo "\n=== 7. Important pla de manteniment (TASQUES PLA_M) ===\n";
$wsPla = $spreadsheet->getSheetByName('TASQUES PLA_M');
$stmtPla = $pdo->prepare('
    INSERT INTO tasques_pla (instalacio_id, tasca_cataleg_id, equip_id, espai_id, torn_id,
                             periodicitat_id, observacions, data_darrera_realitzacio, 
                             data_propera_realitzacio, en_curs, comentaris)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
');
$plaCount = 0;
$plaSkipped = 0;

for ($row = 2; $row <= $wsPla->getHighestRow(); $row++) {
    $nomTasca = trim((string)$wsPla->getCell("B{$row}")->getValue());
    if (empty($nomTasca)) continue;
    
    // Buscar tasca al catàleg
    $tascaCatalegId = $tascaCatalegMap[mb_strtolower(mb_substr($nomTasca, 0, 80))] ?? null;
    if (!$tascaCatalegId) {
        // Busqueda parcial
        $nomLow = mb_strtolower($nomTasca);
        foreach ($tascaCatalegMap as $key => $tcId) {
            if (str_contains($nomLow, mb_substr($key, 0, 40)) || str_contains($key, mb_substr($nomLow, 0, 40))) {
                $tascaCatalegId = $tcId;
                break;
            }
        }
    }
    if (!$tascaCatalegId) {
        $plaSkipped++;
        continue;
    }
    
    $equipament = trim((string)$wsPla->getCell("C{$row}")->getValue());
    $espaiNom = trim((string)$wsPla->getCell("D{$row}")->getValue());
    $periodicitat = trim((string)$wsPla->getCell("J{$row}")->getValue());
    $torn = trim((string)$wsPla->getCell("P{$row}")->getValue());
    $observacions = trim((string)$wsPla->getCell("Q{$row}")->getValue());
    $enCurs = $wsPla->getCell("O{$row}")->getValue();
    $comentaris = trim((string)$wsPla->getCell("U{$row}")->getValue());
    
    // Dates
    $dataDarrera = $wsPla->getCell("G{$row}")->getValue();
    $dataPropera = $wsPla->getCell("H{$row}")->getValue();
    
    $dataDarreraStr = null;
    if ($dataDarrera) {
        if (is_numeric($dataDarrera)) {
            $dataDarreraStr = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$dataDarrera)->format('Y-m-d');
        } elseif ($dataDarrera instanceof \DateTimeInterface) {
            $dataDarreraStr = $dataDarrera->format('Y-m-d');
        }
    }
    $dataProperaStr = null;
    if ($dataPropera) {
        if (is_numeric($dataPropera)) {
            $dataProperaStr = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$dataPropera)->format('Y-m-d');
        } elseif ($dataPropera instanceof \DateTimeInterface) {
            $dataProperaStr = $dataPropera->format('Y-m-d');
        }
    }
    
    $espaiId = $espaiMap[mb_strtolower($espaiNom)] ?? null;
    $periodicitatId = $periodicitatMap[mb_strtolower($periodicitat)] ?? null;
    $tornId = $tornMap[$torn] ?? null;
    
    $stmtPla->execute([
        $instalacioId, $tascaCatalegId, null, $espaiId, $tornId,
        $periodicitatId, $observacions ?: null,
        $dataDarreraStr, $dataProperaStr,
        ($enCurs === true || $enCurs === 'TRUE' || $enCurs === 1) ? 1 : 0,
        $comentaris ?: null
    ]);
    $plaCount++;
}
echo "  {$plaCount} tasques al pla importades ({$plaSkipped} omeses per no trobar catàleg)\n";

// =====================================================================
// 8. Importar Registre de Tasques
// =====================================================================
echo "\n=== 8. Important registre de tasques ===\n";
$wsReg = $spreadsheet->getSheetByName('REGISTRE TASQUES');

// Construir mapa de tasques_pla per poder vincular
$stmtPlaAll = $pdo->query("SELECT tp.id, tc.nom AS tasca_nom FROM tasques_pla tp JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id WHERE tp.instalacio_id = {$instalacioId}");
$plaMap = [];
foreach ($stmtPlaAll->fetchAll() as $r) {
    $plaMap[mb_strtolower(mb_substr($r['tasca_nom'], 0, 50))] = (int)$r['id'];
}

$stmtReg = $pdo->prepare('
    INSERT INTO registre_tasques (instalacio_id, tasca_pla_id, data_execucio, realitzada, comentaris)
    VALUES (?, ?, ?, ?, ?)
');
$regCount = 0;
$regSkipped = 0;
$batchSize = 500;
$batch = 0;

$pdo->beginTransaction();

for ($row = 2; $row <= $wsReg->getHighestRow(); $row++) {
    $nomTasca = trim((string)$wsReg->getCell("B{$row}")->getValue());
    if (empty($nomTasca)) continue;
    
    $dataExec = $wsReg->getCell("E{$row}")->getValue();
    $dataNoExec = $wsReg->getCell("F{$row}")->getValue();
    $comentaris = trim((string)$wsReg->getCell("G{$row}")->getValue());
    
    // Buscar tasca_pla
    $tascaPlaId = $plaMap[mb_strtolower(mb_substr($nomTasca, 0, 50))] ?? null;
    if (!$tascaPlaId) {
        $nomLow = mb_strtolower($nomTasca);
        foreach ($plaMap as $key => $pId) {
            if (str_contains($nomLow, mb_substr($key, 0, 30)) || str_contains($key, mb_substr($nomLow, 0, 30))) {
                $tascaPlaId = $pId;
                break;
            }
        }
    }
    
    if (!$tascaPlaId) {
        $regSkipped++;
        continue;
    }
    
    $realitzada = 1;
    $dataStr = null;
    
    if ($dataExec) {
        if (is_numeric($dataExec)) {
            $dataStr = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$dataExec)->format('Y-m-d');
        } elseif ($dataExec instanceof \DateTimeInterface) {
            $dataStr = $dataExec->format('Y-m-d');
        }
    }
    
    if (!$dataStr && $dataNoExec) {
        $realitzada = 0;
        if (is_numeric($dataNoExec)) {
            $dataStr = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$dataNoExec)->format('Y-m-d');
        } elseif ($dataNoExec instanceof \DateTimeInterface) {
            $dataStr = $dataNoExec->format('Y-m-d');
        }
    }
    
    if (!$dataStr) {
        $regSkipped++;
        continue;
    }
    
    $stmtReg->execute([
        $instalacioId, $tascaPlaId, $dataStr, $realitzada, $comentaris ?: null
    ]);
    $regCount++;
    $batch++;
    
    if ($batch >= $batchSize) {
        $pdo->commit();
        $pdo->beginTransaction();
        $batch = 0;
        echo "  ... {$regCount} registres importats\n";
    }
}

$pdo->commit();
echo "  {$regCount} registres importats ({$regSkipped} omesos)\n";

// =====================================================================
// Resum
// =====================================================================
echo "\n========================================\n";
echo "IMPORTACIÓ COMPLETADA\n";
echo "========================================\n";
echo "  Instal·lació: CEMCERVERA (ID={$instalacioId})\n";
echo "  Torns: " . count($tornMap) . "\n";
echo "  Espais: {$espaiCount}\n";
echo "  Equips: {$equipCount}\n";
echo "  Tasques catàleg: {$tcCount}\n";
echo "  Pla manteniment: {$plaCount}\n";
echo "  Registre execucions: {$regCount}\n";
echo "\n  Usuari admin: admin@gmao.local / admin123\n";
echo "========================================\n";
