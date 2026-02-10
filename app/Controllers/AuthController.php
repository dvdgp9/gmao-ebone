<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Database;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (!empty($_SESSION['user_id'])) {
            $this->redirect('dashboard');
        }
        $this->view('auth.login', ['flash' => $this->getFlash()]);
    }

    public function login(): void
    {
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('login');
        }

        $email = trim($this->post('email', ''));
        $password = $this->post('password', '');

        if (empty($email) || empty($password)) {
            $this->setFlash('error', 'Introdueix email i contrasenya.');
            $this->redirect('login');
        }

        $db = Database::getInstance();

        $stmt = $db->prepare('SELECT * FROM usuaris WHERE email = ? AND actiu = 1 LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->setFlash('error', 'Credencials incorrectes.');
            $this->redirect('login');
        }

        // Obtener instalaciones y roles del usuario
        $stmt = $db->prepare('
            SELECT ui.instalacio_id, ui.rol_id, i.nom AS instalacio_nom, r.nom AS rol_nom
            FROM usuari_instalacio ui
            JOIN instalacions i ON i.id = ui.instalacio_id AND i.activa = 1
            JOIN rols r ON r.id = ui.rol_id
            WHERE ui.usuari_id = ?
            ORDER BY i.nom
        ');
        $stmt->execute([$user['id']]);
        $assignacions = $stmt->fetchAll();

        // Comprobar si es superadmin (puede no tener asignaciones)
        $isSuperadmin = false;
        $stmt = $db->prepare('
            SELECT 1 FROM usuari_instalacio ui
            JOIN rols r ON r.id = ui.rol_id
            WHERE ui.usuari_id = ? AND r.nom = "superadmin"
            LIMIT 1
        ');
        $stmt->execute([$user['id']]);
        if ($stmt->fetch()) {
            $isSuperadmin = true;
        }

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_nom'] = $user['nom'] . ' ' . ($user['cognoms'] ?? '');
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_superadmin'] = $isSuperadmin;
        $_SESSION['assignacions'] = $assignacions;

        // Si tiene asignaciones, establecer la primera como activa
        if (!empty($assignacions)) {
            $_SESSION['instalacio_id'] = (int)$assignacions[0]['instalacio_id'];
            $_SESSION['instalacio_nom'] = $assignacions[0]['instalacio_nom'];
            $_SESSION['current_role'] = $assignacions[0]['rol_nom'];
        } elseif ($isSuperadmin) {
            $_SESSION['instalacio_id'] = null;
            $_SESSION['instalacio_nom'] = 'Totes';
            $_SESSION['current_role'] = 'superadmin';
        }

        $this->redirect('dashboard');
    }

    public function logout(): void
    {
        session_destroy();
        $this->redirect('login');
    }
}
