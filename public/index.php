<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Config\App;
use App\Core\Router;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Configuración global
App::init();

// Router
$router = new Router();
require_once __DIR__ . '/../app/config/routes.php';

$url = $_GET['url'] ?? '';
$router->dispatch($url);
