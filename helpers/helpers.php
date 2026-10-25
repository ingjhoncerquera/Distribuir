<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| ESCAPE HTML
|--------------------------------------------------------------------------
*/

function e(mixed $value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/*
|--------------------------------------------------------------------------
| CONSTRUCCIÓN DE URL
|--------------------------------------------------------------------------
|
| Prioridad:
| 1. APP_URL, si está definida en la configuración.
| 2. El dominio actual + BASE_URL, como alternativa.
|
| Ejemplo local:
| http://localhost/trazabilidadaprendices
|
| Ejemplo producción:
| https://midominio.com/trazabilidadaprendices
|
*/

function url(string $path = ''): string
{
    $path = trim($path);

    /*
    |----------------------------------------------------------------------
    | Normalizar la ruta solicitada
    |----------------------------------------------------------------------
    */

    if ($path === '' || $path === '/') {
        $ruta = '';
    } else {
        $ruta = '/' . ltrim($path, '/');
    }

    /*
    |----------------------------------------------------------------------
    | Opción recomendada: URL base configurada
    |----------------------------------------------------------------------
    */

    if (defined('APP_URL')) {
        $appUrl = rtrim(trim((string) APP_URL), '/');

        if ($appUrl !== '') {
            return $appUrl . $ruta . (
                $ruta === '' ? '/' : ''
            );
        }
    }

    /*
    |----------------------------------------------------------------------
    | Compatibilidad con la configuración actual
    |----------------------------------------------------------------------
    */

    $baseUrl = defined('BASE_URL')
        ? '/' . trim((string) BASE_URL, '/')
        : '';

    if ($baseUrl === '/') {
        $baseUrl = '';
    }

    /*
    |----------------------------------------------------------------------
    | Detectar el protocolo
    |----------------------------------------------------------------------
    */

    $https = $_SERVER['HTTPS'] ?? '';

    $esHttps = !empty($https)
        && strtolower((string) $https) !== 'off';

    $protocolo = $esHttps
        ? 'https://'
        : 'http://';

    /*
    |----------------------------------------------------------------------
    | Obtener y validar el dominio
    |----------------------------------------------------------------------
    */

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    if (
        !is_string($host)
        || !preg_match(
            '/^[a-zA-Z0-9.-]+(?::[0-9]{1,5})?$/',
            $host
        )
    ) {
        $host = 'localhost';
    }

    return $protocolo
        . $host
        . $baseUrl
        . $ruta
        . ($ruta === '' ? '/' : '');
}

/*
|--------------------------------------------------------------------------
| REDIRECCIONES
|--------------------------------------------------------------------------
*/

function redirect(string $path): never
{
    header('Location: ' . url($path));

    exit;
}

/*
|--------------------------------------------------------------------------
| MÉTODOS HTTP
|--------------------------------------------------------------------------
*/

function isPost(): bool
{
    return strtoupper(
        $_SERVER['REQUEST_METHOD'] ?? 'GET'
    ) === 'POST';
}

function isGet(): bool
{
    return strtoupper(
        $_SERVER['REQUEST_METHOD'] ?? 'GET'
    ) === 'GET';
}

/*
|--------------------------------------------------------------------------
| OBTENER DATOS DE POST Y GET
|--------------------------------------------------------------------------
*/

function post(
    string $key,
    mixed $default = null
): mixed {
    return $_POST[$key] ?? $default;
}

function get(
    string $key,
    mixed $default = null
): mixed {
    return $_GET[$key] ?? $default;
}

/*
|--------------------------------------------------------------------------
| RUTA ACTUAL
|--------------------------------------------------------------------------
*/

function rutaActual(): string
{
    $uri = parse_url(
        $_SERVER['REQUEST_URI'] ?? '/',
        PHP_URL_PATH
    );

    $uri = is_string($uri) ? $uri : '/';

    $baseUrl = defined('BASE_URL')
        ? '/' . trim((string) BASE_URL, '/')
        : '';

    if ($baseUrl === '/') {
        $baseUrl = '';
    }

    if (
        $baseUrl !== ''
        && (
            $uri === $baseUrl
            || str_starts_with($uri, $baseUrl . '/')
        )
    ) {
        $uri = substr(
            $uri,
            strlen($baseUrl)
        );
    }

    if ($uri === '' || $uri === '/') {
        return '/';
    }

    return '/' . trim($uri, '/');
}

/*
|--------------------------------------------------------------------------
| COMPROBAR RUTA
|--------------------------------------------------------------------------
*/

function rutaEs(string $ruta): bool
{
    return rutaActual() === '/'
        . trim($ruta, '/');
}

/*
|--------------------------------------------------------------------------
| PROTECCIÓN CSRF
|--------------------------------------------------------------------------
*/

function csrfToken(): string
{
    if (
        !isset($_SESSION['csrf_token'])
        || !is_string($_SESSION['csrf_token'])
    ) {
        $_SESSION['csrf_token'] = bin2hex(
            random_bytes(32)
        );
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . e(csrfToken())
        . '">';
}

function verificarCsrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    $tokenSesion = $_SESSION['csrf_token'] ?? '';

    $sesionActiva = session_status() === PHP_SESSION_ACTIVE;

    $coinciden =
        $sesionActiva
        && is_string($token)
        && is_string($tokenSesion)
        && $token !== ''
        && $tokenSesion !== ''
        && hash_equals($tokenSesion, $token);

    // Diagnóstico temporal: nunca registrar los tokens reales.
    error_log(
        '[CSRF SITA] ' . json_encode(
            [
                'metodo' => $_SERVER['REQUEST_METHOD'] ?? '',
                'sesion_activa' => $sesionActiva,
                'session_id_presente' => session_id() !== '',
                'token_post_recibido' =>
                    is_string($token) && $token !== '',
                'token_sesion_disponible' =>
                    is_string($tokenSesion) && $tokenSesion !== '',
                'coinciden' => $coinciden,
            ],
            JSON_UNESCAPED_UNICODE
        )
    );

    return $coinciden;
}


function exigirCsrf(): void
{
    if (!verificarCsrf()) {
        http_response_code(419);

        die(
            'La solicitud no es válida o ha expirado.'
        );
    }
}

/*
|--------------------------------------------------------------------------
| MENSAJES TEMPORALES (FLASH)
|--------------------------------------------------------------------------
*/

function flash(
    string $key,
    ?string $message = null
): ?string {
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;

        return null;
    }

    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }

    $message = $_SESSION['_flash'][$key];

    unset($_SESSION['_flash'][$key]);

    return is_string($message)
        ? $message
        : null;
}

/*
|--------------------------------------------------------------------------
| FORMATO DE FECHAS
|--------------------------------------------------------------------------
*/

function fecha(
    ?string $date,
    string $format = 'd/m/Y'
): string {
    if (
        empty($date)
        || $date === '0000-00-00'
    ) {
        return '';
    }

    try {
        $dateObject = new DateTime($date);

        return $dateObject->format($format);
    } catch (Throwable) {
        return '';
    }
}

function fechaHora(?string $date): string
{
    if (empty($date)) {
        return '';
    }

    return fecha(
        $date,
        'd/m/Y H:i'
    );
}

/*
|--------------------------------------------------------------------------
| FORMATO DE DINERO
|--------------------------------------------------------------------------
*/

function dinero(mixed $value): string
{
    return '$ '
        . number_format(
            (float) $value,
            0,
            ',',
            '.'
        );
}

/*
|--------------------------------------------------------------------------
| CONVERSIÓN DE TIPOS
|--------------------------------------------------------------------------
*/

function entero(mixed $value): int
{
    return (int) $value;
}

function booleano(mixed $value): bool
{
    return filter_var(
        $value,
        FILTER_VALIDATE_BOOLEAN
    );
}

/*
|--------------------------------------------------------------------------
| INFORMACIÓN DE LA APLICACIÓN
|--------------------------------------------------------------------------
*/

function nombreAplicacion(): string
{
    return 'Sistema de Trazabilidad de Aprendices';
}

function versionAplicacion(): string
{
    return '1.0.0';
}

