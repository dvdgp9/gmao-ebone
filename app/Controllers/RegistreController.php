<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\RegistreTasca;
use App\Models\TascaPla;

class RegistreController extends Controller
{
    private const UNDO_SESSION_KEY = 'registre_undo_actions';

    public function index(): void
    {
        $this->requireAuth();
        $instalacioId = $this->currentInstalacioId();
        if (!$instalacioId) {
            $this->setFlash('error', 'Selecciona una instal·lació.');
            $this->redirect('dashboard');
        }

        $page = max(1, (int)$this->get('page', 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        $total = RegistreTasca::countByInstalacio($instalacioId);
        $totalPages = max(1, (int)ceil($total / $perPage));

        $registres = RegistreTasca::allByInstalacio($instalacioId, $perPage, $offset);

        $this->view('registre.index', [
            'title' => 'Registre de Tasques',
            'registres' => $registres,
            'pagination' => [
                'total' => $total,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'per_page' => $perPage,
            ],
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment', 'tecnic']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('setmana');
        }

        $instalacioId = $this->currentInstalacioId();
        $tascaPlaId = (int)$this->post('tasca_pla_id');
        $realitzada = (bool)$this->post('realitzada', 1);
        $comentaris = trim($this->post('comentaris', '')) ?: null;
        $dataExecucio = $this->post('data_execucio', date('Y-m-d'));
        $redirect = $this->post('redirect', 'setmana');

        $tasca = TascaPla::find($tascaPlaId);
        if (!$tasca || $tasca['instalacio_id'] != $instalacioId) {
            $this->setFlash('error', 'Tasca no trobada.');
            $this->redirect('setmana');
        }

        $registreId = RegistreTasca::registrar(
            $instalacioId,
            $tascaPlaId,
            $this->currentUserId(),
            $dataExecucio,
            $realitzada,
            $comentaris
        );

        if ($realitzada) {
            $token = bin2hex(random_bytes(16));
            $_SESSION[self::UNDO_SESSION_KEY] = [
                $token => [
                    'registre_id' => $registreId,
                    'tasca_pla_id' => $tascaPlaId,
                    'instalacio_id' => $instalacioId,
                    'redirect' => $redirect,
                    'previous_state' => [
                        'data_darrera_realitzacio' => $tasca['data_darrera_realitzacio'] ?? null,
                        'data_propera_realitzacio' => $tasca['data_propera_realitzacio'] ?? null,
                        'data_darrera_no_realitzacio' => $tasca['data_darrera_no_realitzacio'] ?? null,
                    ],
                ],
            ];

            $this->setFlash('success', 'Tasca marcada com a realitzada.', [
                'action' => [
                    'label' => 'Desfer',
                    'token' => $token,
                    'redirect' => $redirect,
                ],
            ]);
        } else {
            $this->setFlash('success', 'Execució registrada correctament.');
        }

        $this->redirect($redirect);
    }

    public function undo(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment', 'tecnic']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('setmana');
        }

        $token = (string)$this->post('token', '');
        $redirect = $this->post('redirect', 'setmana');
        $undoActions = $_SESSION[self::UNDO_SESSION_KEY] ?? [];
        $undo = $undoActions[$token] ?? null;

        if (!$undo) {
            $this->setFlash('error', 'Aquesta acció ja no es pot desfer.');
            $this->redirect($redirect);
        }

        unset($undoActions[$token]);
        $_SESSION[self::UNDO_SESSION_KEY] = $undoActions;

        $instalacioId = $this->currentInstalacioId();
        if (!$instalacioId || (int)$undo['instalacio_id'] !== (int)$instalacioId) {
            $this->setFlash('error', 'No tens permís per desfer aquesta acció.');
            $this->redirect($redirect);
        }

        $registre = RegistreTasca::find((int)$undo['registre_id']);
        $tasca = TascaPla::find((int)$undo['tasca_pla_id']);

        if (!$registre || !$tasca) {
            $this->setFlash('error', 'No s\'ha pogut desfer perquè la informació ja no està disponible.');
            $this->redirect($redirect);
        }

        RegistreTasca::delete((int)$undo['registre_id']);
        TascaPla::update((int)$undo['tasca_pla_id'], $undo['previous_state']);

        $this->setFlash('success', 'Acció desfeta correctament.');
        $this->redirect($redirect);
    }
}
