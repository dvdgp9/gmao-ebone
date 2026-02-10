<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\RegistreTasca;
use App\Models\TascaPla;

class RegistreController extends Controller
{
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

        $tasca = TascaPla::find($tascaPlaId);
        if (!$tasca || $tasca['instalacio_id'] != $instalacioId) {
            $this->setFlash('error', 'Tasca no trobada.');
            $this->redirect('setmana');
        }

        RegistreTasca::registrar(
            $instalacioId,
            $tascaPlaId,
            $this->currentUserId(),
            $dataExecucio,
            $realitzada,
            $comentaris
        );

        $this->setFlash('success', 'Execució registrada correctament.');

        $redirect = $this->post('redirect', 'setmana');
        $this->redirect($redirect);
    }
}
