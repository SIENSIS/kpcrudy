<?php

namespace Kpcrudy\Demo\Libraries;

use Kpcrudy\Demo\Demo;
use WeakMap;

/**
 * Minimal session authentication for the demo: the user is chosen from a
 * list, without password. Never use it outside a demo; use CodeIgniter Shield.
 *
 * It has its own session key, so it does not interfere with the host
 * project's login.
 */
class Auth
{
    /**
     * Logged-in user per request object, together with the session id it
     * was loaded for. Keyed by the request (not a plain static) so that it
     * never leaks to the next request in worker mode (FrankenPHP...).
     *
     * @var WeakMap<object, array{id: mixed, user: array<string, mixed>|null}>|null
     */
    private static ?WeakMap $cache = null;

    /**
     * Logged-in user, reloaded once per request (and whenever the session
     * user changes) so that changes made from the CRUDs (role, deactivation,
     * trash, profile) take effect immediately.
     */
    public static function user(): ?array
    {
        $id      = session(Demo::SESSION_KEY);
        $request = service('request');

        self::$cache ??= new WeakMap();

        $cached = self::$cache[$request] ?? null;

        if ($cached !== null && $cached['id'] === $id) {
            return $cached['user'];
        }

        $user = $id === null ? null : db_connect()->table('kpdemo_users')
            ->where('id', $id)
            ->where('active', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        self::$cache[$request] = ['id' => $id, 'user' => $user];

        return $user;
    }

    public static function id(): ?int
    {
        return isset(self::user()['id']) ? (int) self::user()['id'] : null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? null) === 'admin';
    }

    /**
     * Users that can log in, administrators first.
     *
     * @return list<array<string, mixed>>
     */
    public static function loginable(): array
    {
        return db_connect()->table('kpdemo_users')
            ->select('id, username, first_name, last_name, avatar, role')
            ->where('active', 1)
            ->where('deleted_at', null)
            ->orderBy('role', 'asc')
            ->orderBy('last_name', 'asc')
            ->get()
            ->getResultArray();
    }

    /**
     * Logs in as the given user. Returns null if the user cannot log in.
     */
    public static function loginAs(int $id): ?array
    {
        $user = db_connect()->table('kpdemo_users')
            ->where('id', $id)
            ->where('active', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if ($user === null) {
            return null;
        }

        session()->regenerate();
        session()->set(Demo::SESSION_KEY, (int) $user['id']);
        self::forget();

        return $user;
    }

    /**
     * Logs out and gives the session a new id (the old one is destroyed).
     */
    public static function logout(): void
    {
        session()->remove(Demo::SESSION_KEY);
        session()->regenerate(true);
        self::forget();
    }

    private static function forget(): void
    {
        if (self::$cache !== null) {
            unset(self::$cache[service('request')]);
        }
    }
}
