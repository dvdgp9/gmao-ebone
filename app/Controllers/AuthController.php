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

        // Auto-login via remember token cookie
        if (isset($_COOKIE['remember_token'])) {
            $user = self::loginFromRememberToken($_COOKIE['remember_token']);
            if ($user) {
                self::buildSession($user);
                $this->redirect('dashboard');
            } else {
                setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            }
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

        self::buildSession($user);

        // Remember me
        if (!empty($_POST['remember'])) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 60 * 86400); // 60 days
            $db->prepare('INSERT INTO remember_tokens (usuari_id, token, expires_at) VALUES (?, ?, ?)')->execute([$user['id'], $token, $expires]);
            setcookie('remember_token', $token, time() + 60 * 86400, '/', '', true, true);
        }

        $this->redirect('dashboard');
    }

    public function logout(): void
    {
        $db = Database::getInstance();

        // Delete remember token
        if (isset($_COOKIE['remember_token'])) {
            $db->prepare('DELETE FROM remember_tokens WHERE token = ?')->execute([$_COOKIE['remember_token']]);
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }

        // Cleanup expired tokens
        $db->exec('DELETE FROM remember_tokens WHERE expires_at < NOW()');

        session_destroy();
        $this->redirect('login');
    }

    private static function loginFromRememberToken(string $token): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('
            SELECT u.* FROM remember_tokens rt
            JOIN usuaris u ON u.id = rt.usuari_id AND u.actiu = 1
            WHERE rt.token = ? AND rt.expires_at > NOW()
            LIMIT 1
        ');
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    private static function buildSession(array $user): void
    {
        $db = Database::getInstance();
        $isSuperadmin = !empty($user['is_superadmin']);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_nom'] = $user['nom'] . ' ' . ($user['cognoms'] ?? '');
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_superadmin'] = $isSuperadmin;

        if ($isSuperadmin) {
            // Superadmin: accés a TOTES les instal·lacions actives
            $stmt = $db->query('SELECT id AS instalacio_id, nom AS instalacio_nom FROM instalacions WHERE activa = 1 ORDER BY nom');
            $allInstalacions = $stmt->fetchAll();

            $assignacions = [];
            foreach ($allInstalacions as $inst) {
                $assignacions[] = [
                    'instalacio_id' => $inst['instalacio_id'],
                    'instalacio_nom' => $inst['instalacio_nom'],
                    'rol_nom' => 'superadmin',
                ];
            }

            $_SESSION['assignacions'] = $assignacions;
            $_SESSION['current_role'] = 'superadmin';
            $_SESSION['instalacio_id'] = null;
            $_SESSION['instalacio_nom'] = 'Totes les instal·lacions';
        } else {
            // Usuari normal: assignacions via usuari_instalacio
            $stmt = $db->prepare('
                SELECT ui.instalacio_id, i.nom AS instalacio_nom, r.nom AS rol_nom
                FROM usuari_instalacio ui
                JOIN instalacions i ON i.id = ui.instalacio_id AND i.activa = 1
                JOIN rols r ON r.id = ui.rol_id
                WHERE ui.usuari_id = ?
                ORDER BY i.nom
            ');
            $stmt->execute([$user['id']]);
            $assignacions = $stmt->fetchAll();

            $_SESSION['assignacions'] = $assignacions;

            if (!empty($assignacions)) {
                $_SESSION['instalacio_id'] = (int)$assignacions[0]['instalacio_id'];
                $_SESSION['instalacio_nom'] = $assignacions[0]['instalacio_nom'];
                $_SESSION['current_role'] = $assignacions[0]['rol_nom'];
            } else {
                $_SESSION['instalacio_id'] = null;
                $_SESSION['instalacio_nom'] = '';
                $_SESSION['current_role'] = '';
            }
        }
    }
}
