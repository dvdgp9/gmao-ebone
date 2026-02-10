<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\InstalacioController;
use App\Controllers\EquipController;
use App\Controllers\EspaiController;
use App\Controllers\TascaCatalegController;
use App\Controllers\TascaPlaController;
use App\Controllers\RegistreController;
use App\Controllers\UsuariController;
use App\Controllers\ImportController;
use App\Controllers\TornController;

/** @var \App\Core\Router $router */

// Auth
$router->get('login', AuthController::class, 'loginForm');
$router->post('login', AuthController::class, 'login');
$router->get('logout', AuthController::class, 'logout');

// Dashboard
$router->get('', DashboardController::class, 'index');
$router->get('dashboard', DashboardController::class, 'index');

// Switch instal·lació
$router->post('switch-instalacio', InstalacioController::class, 'switchInstalacio');

// Instal·lacions (superadmin)
$router->get('instalacions', InstalacioController::class, 'index');
$router->get('instalacions/create', InstalacioController::class, 'create');
$router->post('instalacions/store', InstalacioController::class, 'store');
$router->get('instalacions/edit/{id}', InstalacioController::class, 'edit');
$router->post('instalacions/update/{id}', InstalacioController::class, 'update');

// Equips
$router->get('equips', EquipController::class, 'index');
$router->get('equips/create', EquipController::class, 'create');
$router->post('equips/store', EquipController::class, 'store');
$router->get('equips/edit/{id}', EquipController::class, 'edit');
$router->post('equips/update/{id}', EquipController::class, 'update');
$router->post('equips/delete/{id}', EquipController::class, 'delete');

// Espais
$router->get('espais', EspaiController::class, 'index');
$router->get('espais/create', EspaiController::class, 'create');
$router->post('espais/store', EspaiController::class, 'store');
$router->get('espais/edit/{id}', EspaiController::class, 'edit');
$router->post('espais/update/{id}', EspaiController::class, 'update');
$router->post('espais/delete/{id}', EspaiController::class, 'delete');

// Catàleg de Tasques
$router->get('tasques-cataleg', TascaCatalegController::class, 'index');
$router->get('tasques-cataleg/create', TascaCatalegController::class, 'create');
$router->post('tasques-cataleg/store', TascaCatalegController::class, 'store');
$router->get('tasques-cataleg/edit/{id}', TascaCatalegController::class, 'edit');
$router->post('tasques-cataleg/update/{id}', TascaCatalegController::class, 'update');
$router->post('tasques-cataleg/delete/{id}', TascaCatalegController::class, 'delete');

// Pla de Manteniment
$router->get('pla', TascaPlaController::class, 'index');
$router->get('pla/create', TascaPlaController::class, 'create');
$router->post('pla/store', TascaPlaController::class, 'store');
$router->get('pla/edit/{id}', TascaPlaController::class, 'edit');
$router->post('pla/update/{id}', TascaPlaController::class, 'update');
$router->post('pla/delete/{id}', TascaPlaController::class, 'delete');

// Vista Setmanal
$router->get('setmana', TascaPlaController::class, 'setmana');

// Registre de Tasques
$router->get('registre', RegistreController::class, 'index');
$router->post('registre/store', RegistreController::class, 'store');

// Usuaris
$router->get('usuaris', UsuariController::class, 'index');
$router->get('usuaris/create', UsuariController::class, 'create');
$router->post('usuaris/store', UsuariController::class, 'store');
$router->get('usuaris/edit/{id}', UsuariController::class, 'edit');
$router->post('usuaris/update/{id}', UsuariController::class, 'update');
$router->post('usuaris/toggle/{id}', UsuariController::class, 'toggle');

// Importar Excel
$router->get('import', ImportController::class, 'index');
$router->post('import/upload', ImportController::class, 'upload');
$router->post('import/process', ImportController::class, 'process');

// Torns
$router->get('torns', TornController::class, 'index');
$router->get('torns/create', TornController::class, 'create');
$router->post('torns/store', TornController::class, 'store');
$router->get('torns/edit/{id}', TornController::class, 'edit');
$router->post('torns/update/{id}', TornController::class, 'update');
$router->post('torns/delete/{id}', TornController::class, 'delete');
