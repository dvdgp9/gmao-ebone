<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * Guia d'ús: explica cada apartat de l'aplicació amb captures, marcat per rol.
 * Tothom que ha iniciat sessió la pot veure; per defecte es filtra pel rol de l'usuari.
 */
class GuiaController extends Controller
{
    /** Rols de la guia (filtre) a partir del rol de l'aplicació. */
    public const PERFILS = [
        'tecnic' => 'tecnic',
        'lectura' => 'tecnic',
        'cap_manteniment' => 'cap',
        'admin_instalacio' => 'admin',
        'superadmin' => 'admin',
    ];

    public function index(): void
    {
        $this->requireAuth();

        $perfil = !empty($_SESSION['is_superadmin'])
            ? 'admin'
            : (self::PERFILS[$this->currentRole()] ?? 'tots');

        $this->view('guia.index', [
            'perfilInicial' => $perfil,
        ]);
    }
}
