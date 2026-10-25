<?php

declare(strict_types=1);

class ProgramaController extends Controller
{
    private ProgramaModel $model;

    public function __construct()
    {
        $this->model = new ProgramaModel();
    }

    /**
     * Lista de programas.
     */
    public function index(): void
    {
        Auth::requirePermission('programas.ver');

        $buscar = trim(
            (string) ($_GET['buscar'] ?? '')
        );

        $pagina = max(
            1,
            (int) ($_GET['pagina'] ?? 1)
        );

        $porPagina = (int) (
            $_GET['por_pagina'] ?? 20
        );

        $opcionesPorPagina = [
            10,
            20,
            50,
            100,
        ];

        if (
            !in_array(
                $porPagina,
                $opcionesPorPagina,
                true
            )
        ) {
            $porPagina = 20;
        }

        $total = $this->model->contar(
            $buscar
        );

        $totalPaginas = max(
            1,
            (int) ceil(
                $total / $porPagina
            )
        );

        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }

        $offset = (
            $pagina - 1
        ) * $porPagina;

        $programas = $this->model->listar(
            $buscar,
            $porPagina,
            $offset
        );

        $this->view(
            'programas/index',
            [
                'programas' => $programas,
                'buscar' => $buscar,
                'pagina' => $pagina,
                'porPagina' => $porPagina,
                'total' => $total,
                'totalPaginas' => $totalPaginas,
                'opcionesPorPagina' =>
                    $opcionesPorPagina,
            ]
        );
    }

    /**
     * Formulario para crear un programa.
     */
    public function crear(): void
    {
        Auth::requirePermission(
            'programas.crear'
        );

        $this->view(
            'programas/crear',
            [
                'programa' => null,
                'modo' => 'crear',
            ]
        );
    }

    /**
     * Guarda un nuevo programa.
     */
    public function guardar(): void
    {
        Auth::requirePermission(
            'programas.crear'
        );

        if (
            $_SERVER['REQUEST_METHOD'] !==
            'POST'
        ) {
            redirect('/programas');
        }

        $this->exigirCsrf();

        $codigo = trim(
            (string) (
                $_POST['codigo'] ?? ''
            )
        );

        $nombre = trim(
            (string) (
                $_POST['nombre'] ?? ''
            )
        );

        $nivel = trim(
            (string) (
                $_POST['nivel'] ?? ''
            )
        );

        $duracionMeses = trim(
            (string) (
                $_POST['duracion_meses'] ?? ''
            )
        );

        $estado = trim(
            (string) (
                $_POST['estado'] ?? 'activo'
            )
        );

        $errores = [];

        if ($codigo === '') {
            $errores[] =
                'El código del programa es obligatorio.';
        }

        if (
            strlen($codigo) > 50
        ) {
            $errores[] =
                'El código del programa no puede superar los 50 caracteres.';
        }

        if ($nombre === '') {
            $errores[] =
                'El nombre del programa es obligatorio.';
        }

        if (
            strlen($nombre) > 200
        ) {
            $errores[] =
                'El nombre del programa no puede superar los 200 caracteres.';
        }

        if (
            strlen($nivel) > 100
        ) {
            $errores[] =
                'El nivel no puede superar los 100 caracteres.';
        }

        $duracion = null;

        if ($duracionMeses !== '') {

            if (
                !ctype_digit(
                    $duracionMeses
                )
            ) {
                $errores[] =
                    'La duración debe ser un número entero positivo.';
            } else {

                $duracion = (int) $duracionMeses;

                if ($duracion <= 0) {
                    $errores[] =
                        'La duración debe ser mayor que cero.';
                }
            }
        }

        if (
            !in_array(
                $estado,
                [
                    'activo',
                    'inactivo',
                ],
                true
            )
        ) {
            $errores[] =
                'El estado seleccionado no es válido.';
        }

        if (
            empty($errores)
            && $this->model->existeCodigo(
                $codigo
            )
        ) {
            $errores[] =
                'Ya existe un programa registrado con ese código.';
        }

        if (!empty($errores)) {

            $_SESSION['flash_error'] =
                implode(' ', $errores);

            $_SESSION['old_programa'] = [
                'codigo' =>
                    $codigo,
                'nombre' =>
                    $nombre,
                'nivel' =>
                    $nivel,
                'duracion_meses' =>
                    $duracionMeses,
                'estado' =>
                    $estado,
            ];

            redirect('/programas/crear');
        }

        try {

            $this->model->crear([
                'codigo' =>
                    $codigo,
                'nombre' =>
                    $nombre,
                'nivel' =>
                    $nivel !== ''
                        ? $nivel
                        : null,
                'duracion_meses' =>
                    $duracion,
                'estado' =>
                    $estado,
            ]);

            unset(
                $_SESSION['old_programa']
            );

            $_SESSION['flash_success'] =
                'Programa creado correctamente.';

        } catch (PDOException $e) {

            $_SESSION['flash_error'] =
                'No fue posible crear el programa. Verifique los datos e inténtelo nuevamente.';
        }

        redirect('/programas');
    }

    /**
     * Formulario para editar un programa.
     */
    public function editar(): void
    {
        Auth::requirePermission(
            'programas.editar'
        );

        $id = (int) (
            $_GET['id'] ?? 0
        );

        if ($id <= 0) {

            $_SESSION['flash_error'] =
                'El programa solicitado no es válido.';

            redirect('/programas');
        }

        $programa = $this->model->obtener(
            $id
        );

        if (!$programa) {

            $_SESSION['flash_error'] =
                'El programa solicitado no existe.';

            redirect('/programas');
        }

        $this->view(
            'programas/editar',
            [
                'programa' => $programa,
                'modo' => 'editar',
            ]
        );
    }

    /**
     * Actualiza un programa existente.
     */
    public function actualizar(): void
    {
        Auth::requirePermission(
            'programas.editar'
        );

        if (
            $_SERVER['REQUEST_METHOD'] !==
            'POST'
        ) {
            redirect('/programas');
        }

        $this->exigirCsrf();

        $id = (int) (
            $_POST['id'] ?? 0
        );

        if ($id <= 0) {

            $_SESSION['flash_error'] =
                'El programa seleccionado no es válido.';

            redirect('/programas');
        }

        $programaActual =
            $this->model->obtener($id);

        if (!$programaActual) {

            $_SESSION['flash_error'] =
                'El programa seleccionado no existe.';

            redirect('/programas');
        }

        $codigo = trim(
            (string) (
                $_POST['codigo'] ?? ''
            )
        );

        $nombre = trim(
            (string) (
                $_POST['nombre'] ?? ''
            )
        );

        $nivel = trim(
            (string) (
                $_POST['nivel'] ?? ''
            )
        );

        $duracionMeses = trim(
            (string) (
                $_POST['duracion_meses'] ?? ''
            )
        );

        $errores = [];

        if ($codigo === '') {
            $errores[] =
                'El código del programa es obligatorio.';
        }

        if (
            strlen($codigo) > 50
        ) {
            $errores[] =
                'El código del programa no puede superar los 50 caracteres.';
        }

        if ($nombre === '') {
            $errores[] =
                'El nombre del programa es obligatorio.';
        }

        if (
            strlen($nombre) > 200
        ) {
            $errores[] =
                'El nombre del programa no puede superar los 200 caracteres.';
        }

        if (
            strlen($nivel) > 100
        ) {
            $errores[] =
                'El nivel no puede superar los 100 caracteres.';
        }

        $duracion = null;

        if ($duracionMeses !== '') {

            if (
                !ctype_digit(
                    $duracionMeses
                )
            ) {
                $errores[] =
                    'La duración debe ser un número entero positivo.';
            } else {

                $duracion = (int) $duracionMeses;

                if ($duracion <= 0) {
                    $errores[] =
                        'La duración debe ser mayor que cero.';
                }
            }
        }

        if (
            empty($errores)
            && $this->model->existeCodigo(
                $codigo,
                $id
            )
        ) {
            $errores[] =
                'Ya existe otro programa registrado con ese código.';
        }

        if (!empty($errores)) {

            $_SESSION['flash_error'] =
                implode(' ', $errores);

            $_SESSION['old_programa'] = [
                'id' =>
                    $id,
                'codigo' =>
                    $codigo,
                'nombre' =>
                    $nombre,
                'nivel' =>
                    $nivel,
                'duracion_meses' =>
                    $duracionMeses,
                'estado' =>
                    $programaActual['estado'],
            ];

            redirect(
                '/programas/editar?id='
                . $id
            );
        }

        try {

            $resultado =
                $this->model->actualizar(
                    $id,
                    [
                        'codigo' =>
                            $codigo,
                        'nombre' =>
                            $nombre,
                        'nivel' =>
                            $nivel !== ''
                                ? $nivel
                                : null,
                        'duracion_meses' =>
                            $duracion,
                    ]
                );

            if (!$resultado) {

                $_SESSION['flash_error'] =
                    'No fue posible actualizar el programa.';

                redirect(
                    '/programas/editar?id='
                    . $id
                );
            }

            unset(
                $_SESSION['old_programa']
            );

            $_SESSION['flash_success'] =
                'Programa actualizado correctamente.';

        } catch (PDOException $e) {

            $_SESSION['flash_error'] =
                'No fue posible actualizar el programa. Verifique los datos e inténtelo nuevamente.';
        }

        redirect('/programas');
    }

    /**
     * Activa o inactiva un programa.
     */
    public function cambiarEstado(): void
    {
        Auth::requirePermission(
            'programas.estado'
        );

        if (
            $_SERVER['REQUEST_METHOD'] !==
            'POST'
        ) {
            redirect('/programas');
        }

        $this->exigirCsrf();

        $id = (int) (
            $_POST['id'] ?? 0
        );

        $estado = trim(
            (string) (
                $_POST['estado'] ?? ''
            )
        );

        if ($id <= 0) {

            $_SESSION['flash_error'] =
                'El programa seleccionado no es válido.';

            redirect('/programas');
        }

        if (
            !in_array(
                $estado,
                [
                    'activo',
                    'inactivo',
                ],
                true
            )
        ) {

            $_SESSION['flash_error'] =
                'El estado seleccionado no es válido.';

            redirect('/programas');
        }

        $programa =
            $this->model->obtener($id);

        if (!$programa) {

            $_SESSION['flash_error'] =
                'El programa seleccionado no existe.';

            redirect('/programas');
        }

        /*
         * Si ya tiene el estado solicitado,
         * no realizamos ninguna modificación.
         */
        if (
            $programa['estado'] ===
            $estado
        ) {

            $_SESSION['flash_success'] =
                'El programa ya se encontraba en ese estado.';

            redirect('/programas');
        }

        try {

            $resultado =
                $this->model->cambiarEstado(
                    $id,
                    $estado
                );

            if (!$resultado) {

                $_SESSION['flash_error'] =
                    'No fue posible cambiar el estado del programa.';

                redirect('/programas');
            }

            if (
                $estado === 'activo'
            ) {

                $_SESSION['flash_success'] =
                    'Programa activado correctamente.';

            } else {

                $_SESSION['flash_success'] =
                    'Programa inactivado correctamente.';
            }

        } catch (PDOException $e) {

            $_SESSION['flash_error'] =
                'No fue posible cambiar el estado del programa.';

        }

        redirect('/programas');
    }

    /**
     * Exige token CSRF válido.
     */
    private function exigirCsrf(): void
    {
        $tokenFormulario = (string) (
            $_POST['csrf_token'] ?? ''
        );

        $tokenSesion = (string) (
            $_SESSION['csrf_token'] ?? ''
        );

        if (
            $tokenFormulario === ''
            || $tokenSesion === ''
            || !hash_equals(
                $tokenSesion,
                $tokenFormulario
            )
        ) {

            $_SESSION['flash_error'] =
                'La sesión de seguridad ha expirado. Recargue la página e inténtelo nuevamente.';

            redirect('/programas');
        }
    }
}