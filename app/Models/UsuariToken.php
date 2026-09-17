<?php

namespace App\Models;

use App\Core\Model;
use Throwable;

/**
 * Enllaços d'accés d'un sol ús: activació d'un compte nou o canvi de contrasenya.
 * El token només existeix a l'enllaç; a la BD es guarda el seu SHA-256.
 * Les dates es calculen en PHP per no dependre de la zona horària de MySQL.
 */
class UsuariToken extends Model
{
    protected static string $table = 'usuari_tokens';

    public const TIPUS_ACTIVACIO = 'activacio';
    public const TIPUS_RESET = 'reset';
    public const DIES_VALIDESA = 7;

    public static function generarToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function formatValid(string $token): bool
    {
        return preg_match('/^[a-f0-9]{64}$/', $token) === 1;
    }

    public static function caducitat(?int $ara = null): string
    {
        return date('Y-m-d H:i:s', ($ara ?? time()) + self::DIES_VALIDESA * 86400);
    }

    /**
     * Hash per a comptes pendents d'activar: correspon a un secret aleatori que no es guarda
     * enlloc, així que cap contrasenya hi pot entrar. El cost baix no resta seguretat
     * (el secret té 256 bits) i evita segons d'espera en importacions grans.
     */
    public static function hashInutilitzable(): string
    {
        return password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT, ['cost' => 4]);
    }

    public static function supported(): bool
    {
        static $supported = null;

        if ($supported !== null) {
            return $supported;
        }

        try {
            $stmt = static::db()->query("SHOW TABLES LIKE 'usuari_tokens'");
            $supported = (bool)$stmt->fetch();
        } catch (Throwable $e) {
            $supported = false;
        }

        return $supported;
    }

    /**
     * Crea un enllaç nou i invalida els pendents de l'usuari.
     *
     * @return array{token: string, expires_at: string}
     */
    public static function crear(int $usuariId, string $tipus, ?int $creatPer): array
    {
        $tipus = $tipus === self::TIPUS_RESET ? self::TIPUS_RESET : self::TIPUS_ACTIVACIO;
        $token = self::generarToken();
        $expiresAt = self::caducitat();

        static::execute('DELETE FROM `usuari_tokens` WHERE usuari_id = ? AND used_at IS NULL', [$usuariId]);
        static::execute(
            'INSERT INTO `usuari_tokens` (usuari_id, token_hash, tipus, expires_at, created_by) VALUES (?, ?, ?, ?, ?)',
            [$usuariId, self::hash($token), $tipus, $expiresAt, $creatPer]
        );

        return ['token' => $token, 'expires_at' => $expiresAt];
    }

    /**
     * Retorna l'usuari (camps de `usuaris`) + token_id i tipus si l'enllaç encara serveix.
     */
    public static function trobarValid(string $token): ?array
    {
        if (!self::formatValid($token)) {
            return null;
        }

        $rows = static::query('
            SELECT u.*, t.id AS token_id, t.tipus AS token_tipus, t.expires_at AS token_expires_at
            FROM `usuari_tokens` t
            JOIN `usuaris` u ON u.id = t.usuari_id AND u.actiu = 1
            WHERE t.token_hash = ? AND t.used_at IS NULL AND t.expires_at > ?
            LIMIT 1
        ', [self::hash($token), date('Y-m-d H:i:s')]);

        return $rows[0] ?? null;
    }

    /**
     * Marca l'enllaç com a usat. Retorna false si algú l'ha fet servir just abans.
     */
    public static function consumir(int $tokenId, int $usuariId): bool
    {
        $stmt = static::db()->prepare('UPDATE `usuari_tokens` SET used_at = ? WHERE id = ? AND used_at IS NULL');
        $stmt->execute([date('Y-m-d H:i:s'), $tokenId]);
        if ($stmt->rowCount() !== 1) {
            return false;
        }

        static::execute('DELETE FROM `usuari_tokens` WHERE usuari_id = ? AND used_at IS NULL', [$usuariId]);

        return true;
    }

    /**
     * Un usuari que encara no ha activat el compte rep un altre enllaç d'activació;
     * la resta, un de canvi de contrasenya.
     */
    public static function tipusPerNouEnllac(int $usuariId): string
    {
        $rows = static::query(
            'SELECT tipus, used_at FROM `usuari_tokens` WHERE usuari_id = ? ORDER BY id DESC LIMIT 1',
            [$usuariId]
        );
        $ultim = $rows[0] ?? null;

        return $ultim && $ultim['tipus'] === self::TIPUS_ACTIVACIO && $ultim['used_at'] === null
            ? self::TIPUS_ACTIVACIO
            : self::TIPUS_RESET;
    }

    /**
     * @return array<int, string> usuariId => 'pendent' | 'caducat' (només comptes sense activar)
     */
    public static function estatsActivacio(array $usuariIds): array
    {
        $usuariIds = array_values(array_unique(array_filter(array_map('intval', $usuariIds))));
        if ($usuariIds === [] || !self::supported()) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($usuariIds), '?'));
        $rows = static::query("
            SELECT t.usuari_id, t.tipus, t.used_at, t.expires_at
            FROM `usuari_tokens` t
            JOIN (
                SELECT usuari_id, MAX(id) AS id FROM `usuari_tokens`
                WHERE usuari_id IN ({$placeholders})
                GROUP BY usuari_id
            ) ultim ON ultim.id = t.id
        ", $usuariIds);

        $ara = date('Y-m-d H:i:s');
        $estats = [];
        foreach ($rows as $row) {
            if ($row['tipus'] === self::TIPUS_ACTIVACIO && $row['used_at'] === null) {
                $estats[(int)$row['usuari_id']] = $row['expires_at'] > $ara ? 'pendent' : 'caducat';
            }
        }

        return $estats;
    }
}
