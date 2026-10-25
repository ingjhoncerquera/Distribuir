<?php

declare(strict_types=1);

class Controller
{
    protected function view(
        string $view,
        array $data = []
    ): void {
        $viewPath = ROOT_PATH
            . '/views/'
            . trim($view, '/')
            . '.php';

        if (!file_exists($viewPath)) {
            http_response_code(500);

            die(
                'La vista no existe: '
                . htmlspecialchars(
                    $view,
                    ENT_QUOTES,
                    'UTF-8'
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Información del usuario autenticado
        |--------------------------------------------------------------------------
        |
        | Estas variables son utilizadas por navbar.php.
        | Se preparan aquí para que todas las vistas del sistema
        | tengan disponible la misma información.
        |
        | Se utiliza $usuarioSesion para evitar conflictos con
        | variables llamadas $usuario que sean enviadas por
        | controladores específicos.
        |
        */

        $usuarioSesion = Auth::user() ?? [];

        /*
        | Nombre del usuario
        |
        | Se contemplan los nombres que normalmente puede traer
        | el registro almacenado en sesión.
        */

        $nombreUsuario = '';

        if (is_array($usuarioSesion)) {

            $nombreUsuario = trim(
                (string) (
                    $usuarioSesion['nombre']
                    ?? $usuarioSesion['nombres']
                    ?? $usuarioSesion['nombre_completo']
                    ?? $usuarioSesion['usuario']
                    ?? $usuarioSesion['username']
                    ?? ''
                )
            );
        }

        if ($nombreUsuario === '') {
            $nombreUsuario = 'Usuario';
        }

        /*
        |--------------------------------------------------------------------------
        | Rol principal
        |--------------------------------------------------------------------------
        */

        $rolPrincipal = 'Usuario';

        /*
        | Importante:
        | Esta variable se llama $rolesSesion y no $roles para evitar
        | conflicto con variables enviadas por los controladores
        | a las vistas.
        |
        | Por ejemplo, RolesController envía:
        |
        |     'roles' => $roles
        |
        | Si aquí utilizáramos también $roles, extract(..., EXTR_SKIP)
        | impediría que la variable de la vista fuera creada correctamente.
        */

        $rolesSesion = Auth::roles();

        if (!empty($rolesSesion)) {

            $primerRol = $rolesSesion[0] ?? null;

            if (is_array($primerRol)) {

                $rolPrincipal = trim(
                    (string) (
                        $primerRol['nombre']
                        ?? $primerRol['rol']
                        ?? ''
                    )
                );

            } else {

                $rolPrincipal = trim(
                    (string) $primerRol
                );
            }
        }

        if ($rolPrincipal === '') {
            $rolPrincipal = 'Usuario';
        }

        /*
        |--------------------------------------------------------------------------
        | Administrador
        |--------------------------------------------------------------------------
        |
        | El menú Administración se muestra cuando el usuario
        | posee alguno de los roles administrativos.
        |
        */

        $esAdministrador = Auth::hasAnyRole([
            'Administrador',
            'administrador',
            'Superadministrador',
            'superadministrador',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Variables comunes disponibles para todas las vistas
        |--------------------------------------------------------------------------
        */

        $datosComunes = [
            'nombreUsuario'    => $nombreUsuario,
            'rolPrincipal'     => $rolPrincipal,
            'esAdministrador' => $esAdministrador,
        ];

        /*
        | Los datos enviados específicamente por el controlador
        | tienen prioridad sobre los datos comunes.
        */

        $data = array_merge(
            $datosComunes,
            $data
        );

        extract(
            $data,
            EXTR_SKIP
        );

        require $viewPath;
    }

    protected function redirect(
        string $url
    ): never {
        header(
            'Location: '
            . url($url)
        );

        exit;
    }

    protected function database(): PDO
    {
        $database = new Database();

        return $database->conectar();
    }

    protected function post(
        string $key,
        mixed $default = null
    ): mixed {
        return $_POST[$key] ?? $default;
    }

    protected function get(
        string $key,
        mixed $default = null
    ): mixed {
        return $_GET[$key] ?? $default;
    }

    protected function isPost(): bool
    {
        return strtoupper(
            $_SERVER['REQUEST_METHOD'] ?? 'GET'
        ) === 'POST';
    }

    protected function isGet(): bool
    {
        return strtoupper(
            $_SERVER['REQUEST_METHOD'] ?? 'GET'
        ) === 'GET';
    }
}

