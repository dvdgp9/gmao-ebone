<?php

namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("Vista no trobada: {$view}");
        }

        require $viewFile;
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $this->url($url));
        exit;
    }

    protected function url(string $path = ''): string
    {
        $base = rtrim($_ENV['APP_URL'] ?? '', '/');
        return $base . '/' . ltrim($path, '/');
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    protected function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('login');
        }
    }

    protected function requireRole(array $allowedRoles): void
    {
        $this->requireAuth();
        if (!empty($_SESSION['is_superadmin'])) {
            return;
        }
        $userRole = $_SESSION['current_role'] ?? '';
        if (!in_array($userRole, $allowedRoles)) {
            http_response_code(403);
            $this->view('errors.403');
            exit;
        }
    }

    protected function currentInstalacioId(): ?int
    {
        return $_SESSION['instalacio_id'] ?? null;
    }

    protected function currentUserId(): int
    {
        return $_SESSION['user_id'];
    }

    protected function currentRole(): string
    {
        return $_SESSION['current_role'] ?? '';
    }

    protected function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    protected function getFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
}
