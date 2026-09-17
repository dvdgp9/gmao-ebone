<?php

namespace App\Services;

use App\Models\Usuari;

/**
 * Prepara les files del panell d'usuaris (JSON per a Alpine). Sense BD.
 */
class UsuariLlista
{
    /**
     * @param array $usuaris          files d'Usuari::allWithRoles()
     * @param array $torns            usuariId => instalacioId => list<nom de torn>
     * @param array $estatsActivacio  usuariId => 'pendent'|'caducat'
     * @param array $bloquejats       usuariId => true (comptes compartits que no pot gestionar qui mira)
     */
    public static function files(array $usuaris, array $torns, array $estatsActivacio, array $bloquejats, int $editorId): array
    {
        return array_map(static function (array $u) use ($torns, $estatsActivacio, $bloquejats, $editorId): array {
            $id = (int)$u['id'];

            $assignacions = array_map(static fn(array $a) => [
                'instalacio_id' => (int)$a['instalacio_id'],
                'instalacio' => (string)$a['instalacio_nom'],
                'rol' => (string)$a['rol_nom'],
                'rol_etiqueta' => Usuari::etiquetaRol((string)$a['rol_nom']),
                'torns' => array_values($torns[$id][(int)$a['instalacio_id']] ?? []),
            ], $u['assignacions'] ?? []);
            usort($assignacions, static fn($a, $b) => strcoll($a['instalacio'], $b['instalacio']));

            return [
                'id' => $id,
                'nom' => (string)$u['nom'],
                'cognoms' => (string)($u['cognoms'] ?? ''),
                'email' => (string)$u['email'],
                'actiu' => !empty($u['actiu']),
                'superadmin' => !empty($u['is_superadmin']),
                'creat' => substr((string)($u['created_at'] ?? ''), 0, 10),
                'activacio' => $estatsActivacio[$id] ?? null,
                'bloquejat' => !empty($bloquejats[$id]),
                'propi' => $id === $editorId,
                'assignacions' => $assignacions,
            ];
        }, array_values($usuaris));
    }
}
