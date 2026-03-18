<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Database;
use App\Models\Equip;
use App\Models\RegistreTasca;
use App\Models\TascaCataleg;
use App\Models\TascaPla;
use App\Models\Espai;
use App\Models\Torn;
use App\Models\Periodicitat;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        $this->view('import.index', [
            'title' => 'Importar Excel',
            'returnTo' => $this->getReturnTo(),
            'currentInstalacioId' => $this->currentInstalacioId(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function upload(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('import');
        }

        $file = $_FILES['excel_file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->setFlash('error', 'Error en pujar el fitxer.');
            $this->redirect('import');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'])) {
            $this->setFlash('error', 'Format no vàlid. Utilitza .xlsx o .xls');
            $this->redirect('import');
        }

        $tmpPath = $file['tmp_name'];
        $tipus = $this->post('import_type', 'tasques_cataleg');

        try {
            $spreadsheet = IOFactory::load($tmpPath);
            if ($tipus === 'completa_instalacio') {
                if (!$this->currentInstalacioId()) {
                    $this->setFlash('error', 'Cal tenir una instal·lació activa per fer una importació completa.');
                    $this->redirect('import');
                }

                $summary = $this->buildCompleteImportPreview($spreadsheet);
                $headers = [1 => 'full', 2 => 'sheet', 3 => 'rows'];
                $previewData = [];
                foreach ($summary['sheets'] as $sheetName => $rowCount) {
                    $previewData[] = [
                        1 => 'Sí',
                        2 => $sheetName,
                        3 => (string)$rowCount,
                    ];
                }
                $highestRow = $summary['total_rows'] + 1;
            } else {
                $sheet = $spreadsheet->getActiveSheet();
                $highestRow = $sheet->getHighestRow();

                if ($highestRow < 2) {
                    $this->setFlash('error', 'El fitxer no conté dades (mínim 2 files: capçalera + dades).');
                    $this->redirect('import');
                }

                $headers = [];
                $highestCol = $sheet->getHighestColumn();
                $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
                for ($col = 1; $col <= $highestColIndex; $col++) {
                    $val = trim((string)$sheet->getCellByColumnAndRow($col, 1)->getValue());
                    $headers[$col] = mb_strtolower($val);
                }

                $previewData = [];
                $maxPreview = min($highestRow, 20);
                for ($row = 2; $row <= $maxPreview; $row++) {
                    $rowData = [];
                    for ($col = 1; $col <= $highestColIndex; $col++) {
                        $rowData[$col] = trim((string)$sheet->getCellByColumnAndRow($col, $row)->getValue());
                    }
                    if (array_filter($rowData)) {
                        $previewData[] = $rowData;
                    }
                }
            }

            $storagePath = dirname(__DIR__, 2) . '/storage/';
            $tmpFile = $storagePath . 'import_' . session_id() . '.' . $ext;
            move_uploaded_file($tmpPath, $tmpFile);

            $_SESSION['import'] = [
                'file' => $tmpFile,
                'type' => $tipus,
                'headers' => $headers,
                'total_rows' => $highestRow - 1,
                'return_to' => $this->getReturnTo('', true),
            ];

            $this->view('import.preview', [
                'title' => 'Vista prèvia importació',
                'headers' => $headers,
                'preview' => $previewData,
                'totalRows' => $highestRow - 1,
                'importType' => $tipus,
                'returnTo' => $this->getReturnTo('', true),
                'isWorkbookSummary' => $tipus === 'completa_instalacio',
                'flash' => $this->getFlash(),
            ]);

        } catch (\Exception $e) {
            $this->setFlash('error', 'Error processant el fitxer: ' . $e->getMessage());
            $this->redirect('import');
        }
    }

    public function process(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('import');
        }

        $importData = $_SESSION['import'] ?? null;
        if (!$importData || !file_exists($importData['file'])) {
            $this->setFlash('error', 'No hi ha cap fitxer pendent d\'importar.');
            $this->redirect('import');
        }

        $tipus = $importData['type'];
        $filePath = $importData['file'];

        try {
            $spreadsheet = IOFactory::load($filePath);

            $result = match ($tipus) {
                'tasques_cataleg' => $this->importTasquesCataleg($spreadsheet->getActiveSheet()),
                'tasques_pla' => $this->importTasquesPla($spreadsheet->getActiveSheet()),
                'completa_instalacio' => $this->importCompleteInstalacio($spreadsheet),
                default => ['imported' => 0, 'skipped' => 0, 'errors' => ['Tipus d\'importació no vàlid.']],
            };

            // Netejar
            @unlink($filePath);
            unset($_SESSION['import']);

            $msg = "Importació completada: {$result['imported']} registres importats, {$result['skipped']} omesos.";
            if (!empty($result['errors'])) {
                $msg .= ' Errors: ' . implode('; ', array_slice($result['errors'], 0, 5));
            }

            $this->setFlash($result['imported'] > 0 ? 'success' : 'error', $msg);
            $returnTo = (string)($importData['return_to'] ?? '');
            if ($returnTo !== '' && str_starts_with($returnTo, 'instalacions/onboarding/')) {
                $this->redirect($returnTo);
            }
            if ($tipus === 'tasques_cataleg') {
                $this->redirect('tasques-cataleg');
            }
            $this->redirect('pla');

        } catch (\Exception $e) {
            $this->setFlash('error', 'Error durant la importació: ' . $e->getMessage());
            $this->redirect('import');
        }
    }

    private function importTasquesCataleg($sheet): array
    {
        $db = Database::getInstance();
        $sistemaMap = $this->buildMap('SELECT id, codi FROM sistemes');
        $tipusMap = $this->buildMap('SELECT id, codi FROM tipus_equip');
        $periodicitatMap = $this->buildMap('SELECT id, nom FROM periodicitats');

        $stmt = $db->prepare('
            INSERT INTO tasques_cataleg (codi, sistema_id, tipus_equip_id, nom, periodicitat_normativa_id, empresa_responsable, activa)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ');

        $imported = 0;
        $skipped = 0;
        $errors = [];

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $nom = trim((string)$sheet->getCell("B{$row}")->getValue());
            if (empty($nom)) {
                // Intentar otra columna
                $nom = trim((string)$sheet->getCell("D{$row}")->getValue());
                if (empty($nom)) { $skipped++; continue; }
            }

            $codi = trim((string)$sheet->getCell("A{$row}")->getValue());
            $codiTipus = trim((string)$sheet->getCell("C{$row}")->getValue());
            $periodicitat = trim((string)$sheet->getCell("E{$row}")->getValue());
            $empresa = trim((string)$sheet->getCell("F{$row}")->getValue());

            $sistemaId = $sistemaMap[mb_strtolower($codi)] ?? null;
            $tipusId = $tipusMap[mb_strtolower($codiTipus)] ?? null;
            $periodicitatId = $periodicitatMap[mb_strtolower($periodicitat)] ?? null;

            try {
                $stmt->execute([$codi ?: null, $sistemaId, $tipusId, $nom, $periodicitatId, $empresa ?: null]);
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
                if (count($errors) < 10) {
                    $errors[] = "Fila {$row}: " . $e->getMessage();
                }
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function importTasquesPla($sheet): array
    {
        $db = Database::getInstance();
        $instalacioId = $this->currentInstalacioId();

        // Mapa de tasques catàleg per nom
        $stmtCat = $db->query('SELECT id, nom FROM tasques_cataleg WHERE activa = 1');
        $catalegMap = [];
        foreach ($stmtCat->fetchAll() as $r) {
            $catalegMap[mb_strtolower(mb_substr($r['nom'], 0, 80))] = (int)$r['id'];
        }

        $espaiMap = [];
        foreach (Espai::allByInstalacio($instalacioId) as $e) {
            $espaiMap[mb_strtolower($e['nom'])] = (int)$e['id'];
        }

        $tornMap = [];
        foreach (Torn::allByInstalacio($instalacioId) as $t) {
            $tornMap[mb_strtolower($t['nom'])] = (int)$t['id'];
        }

        $periodicitatMap = $this->buildMap('SELECT id, nom FROM periodicitats');

        $stmt = $db->prepare('
            INSERT INTO tasques_pla (instalacio_id, tasca_cataleg_id, espai_id, torn_id, periodicitat_id, en_curs)
            VALUES (?, ?, ?, ?, ?, 1)
        ');

        $imported = 0;
        $skipped = 0;
        $errors = [];

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $nomTasca = trim((string)$sheet->getCell("B{$row}")->getValue());
            if (empty($nomTasca)) { $skipped++; continue; }

            // Buscar al catàleg
            $catalegId = $catalegMap[mb_strtolower(mb_substr($nomTasca, 0, 80))] ?? null;
            if (!$catalegId) {
                $nomLow = mb_strtolower($nomTasca);
                foreach ($catalegMap as $key => $cId) {
                    if (str_contains($nomLow, mb_substr($key, 0, 40)) || str_contains($key, mb_substr($nomLow, 0, 40))) {
                        $catalegId = $cId;
                        break;
                    }
                }
            }

            if (!$catalegId) {
                $skipped++;
                if (count($errors) < 10) {
                    $errors[] = "Fila {$row}: tasca '{$nomTasca}' no trobada al catàleg";
                }
                continue;
            }

            $espaiNom = trim((string)$sheet->getCell("D{$row}")->getValue());
            $tornNom = trim((string)$sheet->getCell("F{$row}")->getValue());
            $periodicitat = trim((string)$sheet->getCell("E{$row}")->getValue());

            $espaiId = $espaiMap[mb_strtolower($espaiNom)] ?? null;
            $tornId = $tornMap[mb_strtolower($tornNom)] ?? null;
            $periodicitatId = $periodicitatMap[mb_strtolower($periodicitat)] ?? null;

            try {
                $stmt->execute([$instalacioId, $catalegId, $espaiId, $tornId, $periodicitatId]);
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
                if (count($errors) < 10) {
                    $errors[] = "Fila {$row}: " . $e->getMessage();
                }
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function buildCompleteImportPreview($spreadsheet): array
    {
        $requiredSheets = ['LLISTES', 'INVENTARI', 'BD TASQUES', 'TASQUES PLA_M', 'REGISTRE TASQUES'];
        $sheets = [];

        foreach ($requiredSheets as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (!$sheet) {
                throw new \RuntimeException("Falta la fulla requerida: {$sheetName}");
            }

            $count = 0;
            for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
                $rowValues = $sheet->rangeToArray("A{$row}:Q{$row}", null, true, false)[0] ?? [];
                if (array_filter(array_map(static fn($value) => trim((string)$value), $rowValues))) {
                    $count++;
                }
            }

            $sheets[$sheetName] = $count;
        }

        return [
            'sheets' => $sheets,
            'total_rows' => array_sum($sheets),
        ];
    }

    private function importCompleteInstalacio($spreadsheet): array
    {
        $instalacioId = $this->currentInstalacioId();
        if (!$instalacioId) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['No hi ha una instal·lació activa.']];
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $summary = $this->buildCompleteImportPreview($spreadsheet);

            $tornMap = $this->importCompleteTorns($db, $instalacioId);
            $espaiData = $this->importCompleteEspais($db, $spreadsheet->getSheetByName('LLISTES'), $instalacioId);
            $catalogMaps = $this->getCatalogMaps($db);
            $equipData = $this->importCompleteEquips($db, $spreadsheet->getSheetByName('INVENTARI'), $instalacioId, $catalogMaps);
            $tascaCatalegData = $this->importCompleteTasquesCataleg($db, $spreadsheet->getSheetByName('BD TASQUES'), $catalogMaps);
            $plaData = $this->importCompletePla($db, $spreadsheet->getSheetByName('TASQUES PLA_M'), $instalacioId, $espaiData['map'], $tornMap, $tascaCatalegData['map'], $catalogMaps['periodicitatMap']);
            $registreData = $this->importCompleteRegistre($db, $spreadsheet->getSheetByName('REGISTRE TASQUES'), $instalacioId, $plaData['map']);

            $db->exec('UPDATE tasques_pla tp
                JOIN periodicitats p ON p.id = tp.periodicitat_id
                SET tp.data_propera_realitzacio = DATE_ADD(tp.data_darrera_realitzacio, INTERVAL p.dies_interval DAY)
                WHERE tp.data_darrera_realitzacio IS NOT NULL
                  AND tp.periodicitat_id IS NOT NULL
                  AND tp.data_propera_realitzacio IS NULL
                  AND tp.instalacio_id = ' . (int)$instalacioId);

            $db->commit();

            $errors = array_merge(
                $espaiData['errors'],
                $equipData['errors'],
                $tascaCatalegData['errors'],
                $plaData['errors'],
                $registreData['errors']
            );

            return [
                'imported' => $espaiData['imported'] + $equipData['imported'] + $tascaCatalegData['imported'] + $plaData['imported'] + $registreData['imported'] + count($tornMap),
                'skipped' => $espaiData['skipped'] + $equipData['skipped'] + $tascaCatalegData['skipped'] + $plaData['skipped'] + $registreData['skipped'],
                'errors' => array_slice($errors, 0, 10),
            ];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            return ['imported' => 0, 'skipped' => 0, 'errors' => [$e->getMessage()]];
        }
    }

    private function importCompleteTorns($db, int $instalacioId): array
    {
        $existing = [];
        foreach (Torn::allByInstalacio($instalacioId) as $torn) {
            $existing[mb_strtolower($torn['nom'])] = (int)$torn['id'];
        }

        $defaults = [
            ['Cap Manteniment', '["dll","dm","dx","dj","dv"]'],
            ['Matí', '["dll","dm","dx","dj","dv"]'],
            ['Tarda', '["dll","dm","dx","dj","dv"]'],
            ['Cap de Setmana', '["ds","dg"]'],
        ];

        $stmt = $db->prepare('INSERT INTO torns (instalacio_id, nom, dies_setmana, actiu) VALUES (?, ?, ?, 1)');
        foreach ($defaults as [$nom, $dies]) {
            $key = mb_strtolower($nom);
            if (isset($existing[$key])) {
                continue;
            }

            $stmt->execute([$instalacioId, $nom, $dies]);
            $existing[$key] = (int)$db->lastInsertId();
        }

        return $existing;
    }

    private function importCompleteEspais($db, $sheet, int $instalacioId): array
    {
        $map = [];
        foreach (Espai::allByInstalacio($instalacioId) as $espai) {
            $map[mb_strtolower($espai['nom'])] = (int)$espai['id'];
        }

        $stmt = $db->prepare('INSERT INTO espais (instalacio_id, codi, nom, planta, actiu) VALUES (?, ?, ?, ?, 1)');
        $imported = 0;
        $skipped = 0;
        $errors = [];

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $nom = trim((string)$sheet->getCell("C{$row}")->getValue());
            $codi = trim((string)$sheet->getCell("D{$row}")->getValue());
            $planta = trim((string)$sheet->getCell("E{$row}")->getValue());

            if ($nom === '') {
                $skipped++;
                continue;
            }

            $key = mb_strtolower($nom);
            if (isset($map[$key])) {
                $skipped++;
                continue;
            }

            try {
                $stmt->execute([$instalacioId, $codi ?: null, $nom, $planta ?: null]);
                $map[$key] = (int)$db->lastInsertId();
                $imported++;
            } catch (\Throwable $e) {
                $skipped++;
                if (count($errors) < 10) {
                    $errors[] = "Espais fila {$row}: " . $e->getMessage();
                }
            }
        }

        return ['map' => $map, 'imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function getCatalogMaps($db): array
    {
        return [
            'sistemaMap' => $this->buildMap('SELECT id, codi FROM sistemes'),
            'tipusMap' => $this->buildMap('SELECT id, codi FROM tipus_equip'),
            'estatMap' => $this->buildMap('SELECT id, nom FROM estats_equip'),
            'periodicitatMap' => $this->buildMap('SELECT id, nom FROM periodicitats'),
            'normativaMap' => $this->buildMap('SELECT id, nom FROM normatives'),
        ];
    }

    private function importCompleteEquips($db, $sheet, int $instalacioId, array $catalogMaps): array
    {
        $equipMap = [];
        foreach (Equip::allByInstalacio($instalacioId) as $equip) {
            if (!empty($equip['nom_mn'])) {
                $equipMap[mb_strtolower($equip['nom_mn'])] = (int)$equip['id'];
            }
        }

        $stmt = $db->prepare('INSERT INTO equips (instalacio_id, sistema_id, tipus_equip_id, numero, nom_mn, nom_equip, notes, model, dona_servei_a, equipament, planta, empresa_mantenedora, data_installacio, fi_garantia, estat_id, actiu) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $lastSistema = null;

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $nomEquip = trim((string)$sheet->getCell("E{$row}")->getValue());
            if ($nomEquip === '') {
                $skipped++;
                continue;
            }

            $codiSistema = trim((string)$sheet->getCell("A{$row}")->getValue());
            if ($codiSistema !== '') {
                $lastSistema = $codiSistema;
            }

            $nomMn = trim((string)$sheet->getCell("D{$row}")->getValue());
            $key = mb_strtolower($nomMn);
            if ($nomMn !== '' && isset($equipMap[$key])) {
                $skipped++;
                continue;
            }

            $codiTipus = trim((string)$sheet->getCell("B{$row}")->getValue());
            $numero = $sheet->getCell("C{$row}")->getValue();
            $notes = trim((string)$sheet->getCell("F{$row}")->getValue());
            $model = trim((string)$sheet->getCell("G{$row}")->getValue());
            $donaServei = trim((string)$sheet->getCell("H{$row}")->getValue());
            $equipament = trim((string)$sheet->getCell("I{$row}")->getValue());
            $planta = trim((string)$sheet->getCell("K{$row}")->getValue());
            $empresa = trim((string)$sheet->getCell("L{$row}")->getValue());
            $estat = trim((string)$sheet->getCell("O{$row}")->getValue());

            try {
                $stmt->execute([
                    $instalacioId,
                    $catalogMaps['sistemaMap'][mb_strtolower($lastSistema ?? '')] ?? null,
                    $catalogMaps['tipusMap'][mb_strtolower($codiTipus)] ?? null,
                    $numero ? (int)$numero : null,
                    $nomMn ?: null,
                    $nomEquip,
                    $notes ?: null,
                    $model ?: null,
                    $donaServei ?: null,
                    $equipament ?: null,
                    $planta ?: null,
                    $empresa ?: null,
                    $this->parseExcelDateValue($sheet->getCell("M{$row}")),
                    $this->parseExcelDateValue($sheet->getCell("N{$row}")),
                    $catalogMaps['estatMap'][mb_strtolower($estat)] ?? null,
                ]);
                if ($nomMn !== '') {
                    $equipMap[$key] = (int)$db->lastInsertId();
                }
                $imported++;
            } catch (\Throwable $e) {
                $skipped++;
                if (count($errors) < 10) {
                    $errors[] = "Equips fila {$row}: " . $e->getMessage();
                }
            }
        }

        return ['map' => $equipMap, 'imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function importCompleteTasquesCataleg($db, $sheet, array $catalogMaps): array
    {
        $existing = [];
        foreach ($db->query('SELECT id, nom FROM tasques_cataleg WHERE activa = 1')->fetchAll() as $row) {
            $existing[mb_strtolower(mb_substr($row['nom'], 0, 80))] = (int)$row['id'];
        }

        $stmt = $db->prepare('INSERT INTO tasques_cataleg (codi, sistema_id, tipus_equip_id, nom, periodicitat_normativa_id, normativa_id, empresa_responsable, activa) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $lastSistema = null;

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $nom = trim((string)$sheet->getCell("D{$row}")->getValue());
            if ($nom === '') {
                $skipped++;
                continue;
            }

            $codi = trim((string)$sheet->getCell("A{$row}")->getValue());
            if ($codi !== '') {
                $lastSistema = $codi;
            }

            $key = mb_strtolower(mb_substr($nom, 0, 80));
            if (isset($existing[$key])) {
                $skipped++;
                continue;
            }

            $codiTipus = trim((string)$sheet->getCell("B{$row}")->getValue());
            $periodicitat = trim((string)$sheet->getCell("E{$row}")->getValue());
            $normativa = trim((string)$sheet->getCell("F{$row}")->getValue());
            $empresa = trim((string)$sheet->getCell("H{$row}")->getValue());

            try {
                $stmt->execute([
                    $lastSistema,
                    $catalogMaps['sistemaMap'][mb_strtolower($lastSistema ?? '')] ?? null,
                    $catalogMaps['tipusMap'][mb_strtolower($codiTipus)] ?? null,
                    $nom,
                    $catalogMaps['periodicitatMap'][mb_strtolower($periodicitat)] ?? null,
                    $this->resolveNormativaId($normativa, $catalogMaps['normativaMap']),
                    $empresa ?: null,
                ]);
                $existing[$key] = (int)$db->lastInsertId();
                $imported++;
            } catch (\Throwable $e) {
                $skipped++;
                if (count($errors) < 10) {
                    $errors[] = "Catàleg fila {$row}: " . $e->getMessage();
                }
            }
        }

        return ['map' => $existing, 'imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function importCompletePla($db, $sheet, int $instalacioId, array $espaiMap, array $tornMap, array $tascaCatalegMap, array $periodicitatMap): array
    {
        $existing = [];
        foreach ($db->query('SELECT tp.id, tc.nom AS tasca_nom FROM tasques_pla tp JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id WHERE tp.instalacio_id = ' . (int)$instalacioId)->fetchAll() as $row) {
            $existing[mb_strtolower(mb_substr($row['tasca_nom'], 0, 50))] = (int)$row['id'];
        }

        $stmt = $db->prepare('INSERT INTO tasques_pla (instalacio_id, tasca_cataleg_id, equip_id, espai_id, torn_id, periodicitat_id, observacions, data_darrera_realitzacio, data_propera_realitzacio, en_curs, comentaris) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $imported = 0;
        $skipped = 0;
        $errors = [];

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $nomTasca = trim((string)$sheet->getCell("B{$row}")->getValue());
            if ($nomTasca === '') {
                $skipped++;
                continue;
            }

            $key = mb_strtolower(mb_substr($nomTasca, 0, 50));
            if (isset($existing[$key])) {
                $skipped++;
                continue;
            }

            $tascaCatalegId = $this->resolveTaskId($nomTasca, $tascaCatalegMap);
            if (!$tascaCatalegId) {
                $skipped++;
                if (count($errors) < 10) {
                    $errors[] = "Pla fila {$row}: tasca '{$nomTasca}' no trobada al catàleg";
                }
                continue;
            }

            $espaiNom = trim((string)$sheet->getCell("D{$row}")->getValue());
            $periodicitat = trim((string)$sheet->getCell("J{$row}")->getValue());
            $torn = trim((string)$sheet->getCell("P{$row}")->getValue());
            $observacions = trim((string)$sheet->getCell("Q{$row}")->getValue());
            $comentaris = trim((string)$sheet->getCell("U{$row}")->getValue());
            $periodicitatId = $periodicitatMap[mb_strtolower($periodicitat)] ?? null;

            if (!$periodicitatId) {
                $stmtFallback = $db->prepare('SELECT periodicitat_normativa_id FROM tasques_cataleg WHERE id = ?');
                $stmtFallback->execute([$tascaCatalegId]);
                $fallback = $stmtFallback->fetch();
                $periodicitatId = (int)($fallback['periodicitat_normativa_id'] ?? 0) ?: null;
            }

            try {
                $stmt->execute([
                    $instalacioId,
                    $tascaCatalegId,
                    null,
                    $espaiMap[mb_strtolower($espaiNom)] ?? null,
                    $tornMap[mb_strtolower($torn)] ?? null,
                    $periodicitatId,
                    $observacions ?: null,
                    $this->parseExcelDateValue($sheet->getCell("G{$row}")),
                    $this->parseExcelDateValue($sheet->getCell("H{$row}")),
                    $this->parseBooleanCell($sheet->getCell("O{$row}")),
                    $comentaris ?: null,
                ]);
                $existing[$key] = (int)$db->lastInsertId();
                $imported++;
            } catch (\Throwable $e) {
                $skipped++;
                if (count($errors) < 10) {
                    $errors[] = "Pla fila {$row}: " . $e->getMessage();
                }
            }
        }

        return ['map' => $existing, 'imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function importCompleteRegistre($db, $sheet, int $instalacioId, array $plaMap): array
    {
        $stmt = $db->prepare('INSERT INTO registre_tasques (instalacio_id, tasca_pla_id, data_execucio, realitzada, comentaris) VALUES (?, ?, ?, ?, ?)');
        $imported = 0;
        $skipped = 0;
        $errors = [];

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $nomTasca = trim((string)$sheet->getCell("B{$row}")->getValue());
            if ($nomTasca === '') {
                $skipped++;
                continue;
            }

            $tascaPlaId = $this->resolveTaskId($nomTasca, $plaMap, 50, 30);
            if (!$tascaPlaId) {
                $skipped++;
                continue;
            }

            $comentaris = trim((string)$sheet->getCell("G{$row}")->getValue());
            $dataRealitzada = $this->parseExcelDateValue($sheet->getCell("E{$row}"));
            $dataNoRealitzada = $this->parseExcelDateValue($sheet->getCell("F{$row}"));
            $realitzada = $dataRealitzada !== null ? 1 : 0;
            $dataExecucio = $dataRealitzada ?? $dataNoRealitzada;

            if (!$dataExecucio) {
                $skipped++;
                continue;
            }

            try {
                $stmt->execute([$instalacioId, $tascaPlaId, $dataExecucio, $realitzada, $comentaris ?: null]);
                $imported++;
            } catch (\Throwable $e) {
                $skipped++;
                if (count($errors) < 10) {
                    $errors[] = "Registre fila {$row}: " . $e->getMessage();
                }
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function resolveNormativaId(string $normativa, array $normativaMap): ?int
    {
        if ($normativa === '') {
            return null;
        }

        $normLow = mb_strtolower($normativa);
        foreach ($normativaMap as $nom => $id) {
            $normaBase = explode(',', $nom)[0] ?? $nom;
            if (str_contains($normLow, $normaBase) || str_contains($nom, explode(',', $normLow)[0] ?? $normLow)) {
                return $id;
            }
        }

        return null;
    }

    private function resolveTaskId(string $name, array $map, int $keyLength = 80, int $matchLength = 40): ?int
    {
        $key = mb_strtolower(mb_substr($name, 0, $keyLength));
        if (isset($map[$key])) {
            return (int)$map[$key];
        }

        $nameLow = mb_strtolower($name);
        foreach ($map as $candidate => $id) {
            if (str_contains($nameLow, mb_substr($candidate, 0, $matchLength)) || str_contains($candidate, mb_substr($nameLow, 0, $matchLength))) {
                return (int)$id;
            }
        }

        return null;
    }

    private function parseExcelDateValue($cell): ?string
    {
        try {
            $value = $cell->getCalculatedValue();
        } catch (\Throwable $e) {
            $value = $cell->getValue();
        }

        if ($value === null || $value === '' || $value === 0) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value) && (float)$value > 1000) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value)->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }

        $stringValue = trim((string)$value);
        if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{2,4})$#', $stringValue, $matches)) {
            $day = (int)$matches[1];
            $month = (int)$matches[2];
            $year = (int)$matches[3];
            if ($year < 100) {
                $year += 2000;
            }
            if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        return null;
    }

    private function parseBooleanCell($cell): int
    {
        try {
            $value = $cell->getCalculatedValue();
        } catch (\Throwable $e) {
            $value = $cell->getValue();
        }

        return ($value === false || $value === 'FALSE' || $value === 0 || $value === '0') ? 0 : 1;
    }

    private function buildMap(string $sql): array
    {
        $db = Database::getInstance();
        $map = [];
        foreach ($db->query($sql)->fetchAll() as $r) {
            $keys = array_keys($r);
            $map[mb_strtolower($r[$keys[1]])] = (int)$r[$keys[0]];
        }
        return $map;
    }

    private function getReturnTo(string $default = '', bool $fromPost = false): string
    {
        $returnTo = $fromPost ? (string)$this->post('return_to', '') : (string)$this->get('return_to', '');

        if ($returnTo !== '' && str_starts_with($returnTo, 'instalacions/onboarding/')) {
            return $returnTo;
        }

        return $default;
    }
}
