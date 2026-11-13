<?php

declare(strict_types=1);

namespace App;

final class Auth
{
    public const DEFAULT_PASSWORD = 'password';
    public const ADMIN_LOGIN = 'admin';
    public const ADMIN_PASSWORD = 'Administrator12345';

    public function __construct(private readonly Database $db)
    {
    }

    public function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /** @return array{role: string, login: string, name: string, user_id: ?int} */
    public function me(): array
    {
        $user = $this->currentUser();
        if ($user === null) {
            return ['role' => 'guest', 'login' => '', 'name' => '', 'user_id' => null];
        }
        return [
            'role' => (string) ($user['role'] ?? 'user'),
            'login' => (string) ($user['login'] ?? ''),
            'name' => trim($user['imie'] . ' ' . $user['nazwisko']),
            'user_id' => (int) $user['id'],
        ];
    }

    public function isGuest(): bool
    {
        return $this->currentUser() === null;
    }

    public function isAdmin(): bool
    {
        $user = $this->currentUser();
        return $user !== null && ($user['role'] ?? '') === 'admin';
    }

    public function isEditor(): bool
    {
        return $this->currentUser() !== null;
    }

    public function requireEditor(): void
    {
        if (!$this->isEditor()) {
            Http::json(['error' => 'Zaloguj się, aby wprowadzać zmiany.'], 403);
        }
    }

    public function requireAdmin(): void
    {
        if (!$this->isAdmin()) {
            Http::json(['error' => 'Wymagane uprawnienia administratora.'], 403);
        }
    }

    /** @return array{ok: true, user: array<string, mixed>} */
    public function login(array $data): array
    {
        $login = Support::fold(Support::text($data['login'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        if ($login === '' || $password === '') {
            Http::json(['error' => 'Podaj login i hasło.'], 400);
        }
        $user = $this->findByLogin($login);
        if ($user === null || !is_string($user['password_hash'] ?? null) || $user['password_hash'] === '') {
            Http::json(['error' => 'Nieprawidłowy login lub hasło.'], 401);
        }
        if (!password_verify($password, (string) $user['password_hash'])) {
            Http::json(['error' => 'Nieprawidłowy login lub hasło.'], 401);
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        return ['ok' => true, 'user' => $this->me()];
    }

    /** @return array{ok: true} */
    public function logout(): array
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $this->startSession();
        return ['ok' => true];
    }

    /** @return array{ok: true} */
    public function changePassword(array $data): array
    {
        $this->requireEditor();
        $current = (string) ($data['current'] ?? '');
        $next = (string) ($data['password'] ?? '');
        if ($next === '' || strlen($next) < 6) {
            Http::json(['error' => 'Nowe hasło musi mieć co najmniej 6 znaków.'], 400);
        }
        $user = $this->currentUser();
        if ($user === null) {
            Http::json(['error' => 'Sesja wygasła.'], 401);
        }
        if (!password_verify($current, (string) ($user['password_hash'] ?? ''))) {
            Http::json(['error' => 'Aktualne hasło jest nieprawidłowe.'], 400);
        }
        $hash = password_hash($next, PASSWORD_DEFAULT);
        $this->db->exec('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?', [$hash, Support::now(), (int) $user['id']]);
        return ['ok' => true];
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /** @return ?array<string, mixed> */
    public function currentUser(): ?array
    {
        $id = (int) ($_SESSION['user_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }
        $user = $this->db->one('SELECT * FROM users WHERE id = ?', [$id]);
        return $user;
    }

    /** @return ?array<string, mixed> */
    private function findByLogin(string $login): ?array
    {
        foreach ($this->db->all('SELECT * FROM users') as $user) {
            if (Support::fold((string) ($user['login'] ?? '')) === $login) {
                return $user;
            }
        }
        return null;
    }

    public function ensureAdminAccount(): void
    {
        $admin = $this->db->one('SELECT * FROM users WHERE login = ?', [self::ADMIN_LOGIN]);
        $stamp = Support::now();
        if ($admin === null) {
            $this->db->exec(
                'INSERT INTO users (imie, nazwisko, email, dzial, login, password_hash, role, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                ['Admin', 'System', '', 'QD', self::ADMIN_LOGIN, self::hashPassword(self::ADMIN_PASSWORD), 'admin', $stamp, $stamp]
            );
            return;
        }
        if (($admin['role'] ?? '') !== 'admin' || ($admin['password_hash'] ?? '') === '') {
            $this->db->exec(
                'UPDATE users SET role = ?, password_hash = ?, updated_at = ? WHERE id = ?',
                ['admin', self::hashPassword(self::ADMIN_PASSWORD), $stamp, (int) $admin['id']]
            );
        }
    }

    public function migrateUserLogins(Register $register): void
    {
        $stamp = Support::now();
        foreach ($this->db->all(
            "SELECT * FROM users WHERE login = '' OR password_hash = '' OR role = '' OR role IS NULL ORDER BY id"
        ) as $user) {
            $login = Support::text($user['login'] ?? '');
            if ($login === '') {
                $login = $register->allocateLogin((string) $user['imie'], (string) $user['nazwisko'], (int) $user['id']);
            }
            $hash = (string) ($user['password_hash'] ?? '');
            if ($hash === '') {
                $hash = self::hashPassword(self::DEFAULT_PASSWORD);
            }
            $role = Support::text($user['role'] ?? '');
            if ($role === '') {
                $role = Support::fold($login) === Support::fold(self::ADMIN_LOGIN) ? 'admin' : 'user';
            }
            $this->db->exec(
                'UPDATE users SET login = ?, password_hash = ?, role = ?, updated_at = ? WHERE id = ?',
                [$login, $hash, $role, $stamp, (int) $user['id']]
            );
        }
    }
}
