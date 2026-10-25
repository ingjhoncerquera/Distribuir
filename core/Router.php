<?php

declare(strict_types=1);

class Router
{
    private array $getRoutes = [];

    private array $postRoutes = [];

    public function get(string $route, callable|array $action): void
    {
        $this->getRoutes[$this->normalizeRoute($route)] = $action;
    }

    public function post(string $route, callable|array $action): void
    {
        $this->postRoutes[$this->normalizeRoute($route)] = $action;
    }

    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        $uri = parse_url(
            $_SERVER['REQUEST_URI'] ?? '/',
            PHP_URL_PATH
        );

        $uri = $this->removeBaseUrl($uri);
        $uri = $this->normalizeRoute($uri);

        $routes = match ($method) {
            'GET'  => $this->getRoutes,
            'POST' => $this->postRoutes,
            default => [],
        };

        if (!isset($routes[$uri])) {
            $this->notFound();
            return;
        }

        $action = $routes[$uri];

        $this->execute($action);
    }

    private function execute(callable|array $action): void
    {
        if (is_callable($action)) {
            call_user_func($action);
            return;
        }

        if (is_array($action) && count($action) === 2) {
            [$controller, $method] = $action;

            if (!class_exists($controller)) {
                $this->serverError(
                    "El controlador {$controller} no existe."
                );

                return;
            }

            $controllerInstance = new $controller();

            if (!method_exists($controllerInstance, $method)) {
                $this->serverError(
                    "El método {$method} no existe en {$controller}."
                );

                return;
            }

            call_user_func(
                [$controllerInstance, $method]
            );

            return;
        }

        $this->serverError('Acción de ruta inválida.');
    }

    private function normalizeRoute(string $route): string
    {
        $route = trim($route);

        if ($route === '') {
            return '/';
        }

        $route = '/' . trim($route, '/');

        if ($route !== '/') {
            $route = rtrim($route, '/');
        }

        return $route;
    }

    private function removeBaseUrl(string $uri): string
    {
        $baseUrl = defined('BASE_URL')
            ? BASE_URL
            : '';

        if (
            $baseUrl !== ''
            && str_starts_with($uri, $baseUrl)
        ) {
            $uri = substr(
                $uri,
                strlen($baseUrl)
            );
        }

        return $uri === ''
            ? '/'
            : $uri;
    }

    private function notFound(): void
    {
        http_response_code(404);

        echo '<!DOCTYPE html>';
        echo '<html lang="es">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>404 - Página no encontrada</title>';
        echo '</head>';
        echo '<body>';

        echo '<h1>404</h1>';
        echo '<p>La página solicitada no existe.</p>';

        echo '</body>';
        echo '</html>';
    }

    private function serverError(string $message): void
    {
        http_response_code(500);

        echo '<!DOCTYPE html>';
        echo '<html lang="es">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>Error del servidor</title>';
        echo '</head>';
        echo '<body>';

        echo '<h1>Error del servidor</h1>';

        if (
            defined('APP_DEBUG')
            && APP_DEBUG === true
        ) {
            echo '<p>';
            echo htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            );
            echo '</p>';
        } else {
            echo '<p>';
            echo 'Ocurrió un error al procesar la solicitud.';
            echo '</p>';
        }

        echo '</body>';
        echo '</html>';
    }
}