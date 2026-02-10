<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Database;
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
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            if ($highestRow < 2) {
                $this->setFlash('error', 'El fitxer no conté dades (mínim 2 files: capçalera + dades).');
                $this->redirect('import');
            }

            // Leer cabeceras
            $headers = [];
            $highestCol = $sheet->getHighestColumn();
            $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
            for ($col = 1; $col <= $highestColIndex; $col++) {
                $val = trim((string)$sheet->getCellByColumnAndRow($col, 1)->getValue());
                $headers[$col] = mb_strtolower($val);
            }

            // Guardar en sesión para preview
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

            // Guardar archivo temporal para procesamiento posterior
            $storagePath = dirname(__DIR__, 2) . '/storage/';
            $tmpFile = $storagePath . 'import_' . session_id() . '.' . $ext;
            move_uploaded_file($tmpPath, $tmpFile);

            $_SESSION['import'] = [
                'file' => $tmpFile,
                'type' => $tipus,
                'headers' => $headers,
                'total_rows' => $highestRow - 1,
            ];

            $this->view('import.preview', [
                'title' => 'Vista prèvia importació',
                'headers' => $headers,
                'preview' => $previewData,
                'totalRows' => $highestRow - 1,
                'importType' => $tipus,
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
            $sheet = $spreadsheet->getActiveSheet();

            $result = match ($tipus) {
                'tasques_cataleg' => $this->importTasquesCataleg($sheet),
                'tasques_pla' => $this->importTasquesPla($sheet),
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
            $this->redirect($tipus === 'tasques_cataleg' ? 'tasques-cataleg' : 'pla');

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
}
