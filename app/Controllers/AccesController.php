<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Database;
use App\Models\Usuari;
use App\Models\UsuariToken;
use Throwable;

/**
 * Pàgina pública dels enllaços d'accés: la persona tria la seva contrasenya i entra.
 */
class AccesController extends Controller
{
    private const MIN_LLARGADA = 8;

    public function form(string $token): void
    {
        $acces = UsuariToken::supported() ? UsuariToken::trobarValid($token) : null;

        $sessioOberta = null;
        if ($acces && !empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] !== (int)$acces['id']) {
            $sessioOberta = trim($_SESSION['user_nom'] ?? '');
        }

        header('Cache-Control: no-store');
        $this->view('auth.acces', [
            'token' => $token,
            'acces' => $acces,
            'sessioOberta' => $sessioOberta,
            'minLlargada' => self::MIN_LLARGADA,
            'flash' => $this->getFlash(),
        ]);
    }

    public function desar(string $token): void
    {
        if (!UsuariToken::formatValid($token)) {
            $this->redirect('login');
        }
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid. Torna-ho a provar.');
            $this->redirect('acces/' . $token);
        }

        $acces = UsuariToken::supported() ? UsuariToken::trobarValid($token) : null;
        if (!$acces) {
            $this->redirect('acces/' . $token);
        }

        $password = (string)$this->post('password', '');
        $confirmacio = (string)$this->post('password_confirmacio', '');
        if (mb_strlen($password) < self::MIN_LLARGADA) {
            $this->setFlash('error', 'La contrasenya ha de tenir almenys ' . self::MIN_LLARGADA . ' caràcters.');
            $this->redirect('acces/' . $token);
        }
        if ($password !== $confirmacio) {
            $this->setFlash('error', 'Les dues contrasenyes no coincideixen.');
            $this->redirect('acces/' . $token);
        }

        $usuariId = (int)$acces['id'];
        $db = Database::getInstance();
        try {
            $db->beginTransaction();
            if (!UsuariToken::consumir((int)$acces['token_id'], $usuariId)) {
                $db->rollBack();
                $this->redirect('acces/' . $token);
            }
            Usuari::update($usuariId, ['password_hash' => password_hash($password, PASSWORD_BCRYPT)]);
            // Una contrasenya nova tanca els «Recorda'm» que hi pogués haver en altres dispositius.
            $db->prepare('DELETE FROM remember_tokens WHERE usuari_id = ?')->execute([$usuariId]);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[acces] ' . $e->getMessage());
            $this->setFlash('error', 'No s\'ha pogut desar la contrasenya. Torna-ho a provar.');
            $this->redirect('acces/' . $token);
        }

        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        session_regenerate_id(true);
        AuthController::buildSession(Usuari::find($usuariId));

        $this->setFlash('success', 'Contrasenya desada. Benvingut/da, ' . $acces['nom'] . '!');
        $this->redirect('dashboard');
    }
}
