<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\IncidenciaTasca;

class IncidenciaController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment']);
        $instalacioId = $this->currentInstalacioId();
        if (!$instalacioId) {
            $this->setFlash('error', 'Selecciona una instal·lació.');
            $this->redirect('dashboard');
        }

        $this->view('incidencies.index', [
            'title' => 'Incidències',
            'incidencies' => IncidenciaTasca::obertesByInstalacio($instalacioId),
            'flash' => $this->getFlash(),
        ]);
    }

    public function vista(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('incidencies');
        }

        $incidencia = IncidenciaTasca::find((int)$id);
        if (!$incidencia || $incidencia['instalacio_id'] != $this->currentInstalacioId()) {
            $this->setFlash('error', 'Incidència no trobada.');
            $this->redirect('incidencies');
        }

        IncidenciaTasca::marcarVista((int)$id, $this->currentUserId());
        $this->setFlash('success', 'Incidència marcada com a vista.');
        $this->redirect('incidencies');
    }
}
