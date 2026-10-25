<?php

declare(strict_types=1);

class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['usuario_id'])
            && (int) $_SESSION['usuario_id'] > 0;
    }

    public static function estaAutenticado(): bool
    {
        return self::check();
    }

    public static function id(): ?int
    {
        if (!self::check()) {
            return null;
        }

        return (int) $_SESSION['usuario_id'];
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return $_SESSION['usuario'] ?? null;
    }

    public static function login(
        int $usuarioId,
        array $usuario = []
    ): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $usuarioId;
        $_SESSION['usuario'] = $usuario;
        $_SESSION['autenticado'] = true;
    }

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('/login');
        }
    }

    public static function setRoles(array $roles): void
    {
        $_SESSION['roles'] = $roles;
    }

    public static function roles(): array
    {
        return $_SESSION['roles'] ?? [];
    }

    public static function hasRole(string $role): bool
    {
        $role = mb_strtolower(trim($role));

        foreach (self::roles() as $userRole) {
            if (is_array($userRole)) {
                $nombre = $userRole['nombre'] ?? '';
            } else {
                $nombre = (string) $userRole;
            }

            if (
                mb_strtolower(trim($nombre))
                === $role
            ) {
                return true;
            }
        }

        return false;
    }

    public static function hasAnyRole(
        array $roles
    ): bool {
        foreach ($roles as $role) {
            if (
                self::hasRole((string) $role)
            ) {
                return true;
            }
        }

        return false;
    }

    public static function setPermissions(
        array $permissions
    ): void {
        $_SESSION['permissions'] = $permissions;
    }

    public static function permissions(): array
    {
        return $_SESSION['permissions'] ?? [];
    }

    public static function can(
        string $permission
    ): bool {
        $permission = mb_strtolower(
            trim($permission)
        );

        foreach (
            self::permissions()
            as $userPermission
        ) {
            if (is_array($userPermission)) {
                $nombre = $userPermission['nombre']
                    ?? $userPermission['codigo']
                    ?? '';
            } else {
                $nombre = (string) $userPermission;
            }

            if (
                mb_strtolower(trim($nombre))
                === $permission
            ) {
                return true;
            }
        }

        return false;
    }

    public static function requirePermission(
        string $permission
    ): void {
        self::requireLogin();

        if (!self::can($permission)) {
            http_response_code(403);

            die(
                'No tiene permisos para realizar esta acción.'
            );
        }
    }
}