<?php

declare(strict_types=1);

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Auth.php';
require_once ROOT_PATH . '/models/MaterialesModel.php';

class MaterialesController extends Controller
{
    private MaterialesModel $model;

    public function __construct()
    {
        $this->model = new MaterialesModel();
    }

    /**
     * Listado principal del catálogo.
     */
    public function index(): void
    {
        Auth::requirePermission('materiales.catalogo.ver');

        $buscar = trim(
            (string) ($_GET['buscar'] ?? '')
        );

        $pagina = max(
            1,
            (int) ($_GET['pagina'] ?? 1)
        );

        $porPaginaPermitidos = [
            10,
            20,
            50,
            100
        ];

        $porPagina = (int) (
            $_GET['por_pagina'] ?? 20
        );

        if (
            !in_array(
                $porPagina,
                $porPaginaPermitidos,
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

        $materiales = $this->model->listar(
            $buscar,
            $porPagina,
            $offset
        );

        $this->view(
            'materiales/index',
            [
                'materiales' => $materiales,
                'buscar' => $buscar,
                'pagina' => $pagina,
                'porPagina' => $porPagina,
                'total' => $total,
                'totalPaginas' => $totalPaginas
            ]
        );
    }

    /**
     * Muestra el formulario de creación.
     *
     * Actualmente la vista principal utiliza modal,
     * pero se conserva esta acción para mantener una
     * estructura flexible de rutas.
     */
    public function crear(): void
    {
        // El catálogo solo se modifica mediante la importación oficial de Excel.
        flash('error', 'Los materiales del catálogo solo se registran o actualizan mediante la importación del archivo Excel.');
        redirect('/materiales');
        return;

        Auth::requirePermission(
            'materiales.catalogo.crear'
        );

        $this->view(
            'materiales/crear',
            [
                'material' => null
            ]
        );
    }

    /**
     * Guarda un nuevo material.
     */
    public function guardar(): void
    {
        // El catálogo solo se modifica mediante la importación oficial de Excel.
        flash('error', 'Los materiales del catálogo solo se registran o actualizan mediante la importación del archivo Excel.');
        redirect('/materiales');
        return;

        Auth::requirePermission(
            'materiales.catalogo.crear'
        );

        if (
            strtoupper(
                $_SERVER['REQUEST_METHOD'] ?? 'GET'
            ) !== 'POST'
        ) {
            redirect('/materiales');
        }

        if (
            !verificarCsrf()
        ) {
            http_response_code(419);

            die(
                'La solicitud no es válida o ha expirado.'
            );
        }

        $codigo = trim(
            (string) (
                $_POST['codigo_unspsc'] ?? ''
            )
        );

        $descripcion = trim(
            (string) (
                $_POST['descripcion'] ?? ''
            )
        );


        $estado = trim(
            (string) (
                $_POST['estado'] ?? 'activo'
            )
        );

        $fechaInicioVigencia = trim(
            (string) (
                $_POST['fecha_inicio_vigencia'] ?? ''
            )
        );

        $fechaFinVigencia = trim(
            (string) (
                $_POST['fecha_fin_vigencia'] ?? ''
            )
        );

        $errores = [];

        if ($codigo === '') {
            $errores[] =
                'El código UNSPSC es obligatorio.';
        } elseif (mb_strlen($codigo) > 30) {
            $errores[] =
                'El código UNSPSC no puede superar los 30 caracteres.';
        }

        if ($descripcion === '') {
            $errores[] =
                'La descripción del bien es obligatoria.';
        }


        if (
            !in_array(
                $estado,
                ['activo', 'inactivo'],
                true
            )
        ) {
            $errores[] =
                'El estado seleccionado no es válido.';
        }

        if (
            $fechaInicioVigencia !== ''
            && !$this->fechaValida(
                $fechaInicioVigencia
            )
        ) {
            $errores[] =
                'La fecha de inicio de vigencia no es válida.';
        }

        if (
            $fechaFinVigencia !== ''
            && !$this->fechaValida(
                $fechaFinVigencia
            )
        ) {
            $errores[] =
                'La fecha de fin de vigencia no es válida.';
        }

        if (
            $fechaInicioVigencia !== ''
            && $fechaFinVigencia !== ''
            && $fechaFinVigencia < $fechaInicioVigencia
        ) {
            $errores[] =
                'La fecha de fin de vigencia no puede ser anterior a la fecha de inicio.';
        }

        if (
            $this->model->existeCodigo($codigo)
        ) {
            $errores[] =
                'Ya existe un material registrado con ese código UNSPSC.';
        }

        if (!empty($errores)) {
            flash(
                'error',
                implode(' ', $errores)
            );

            $_SESSION['old_material'] = [
                'codigo_unspsc' => $codigo,
                'descripcion' => $descripcion,
                'estado' => $estado,
                'fecha_inicio_vigencia' =>
                    $fechaInicioVigencia,
                'fecha_fin_vigencia' =>
                    $fechaFinVigencia
            ];

            redirect('/materiales');
        }

        try {
            $id = $this->model->crear(
                [
                    'codigo_unspsc' => $codigo,
                    'descripcion' => $descripcion,
                        'estado' => $estado,
                    'fecha_inicio_vigencia' =>
                        $fechaInicioVigencia !== ''
                            ? $fechaInicioVigencia
                            : null,
                    'fecha_fin_vigencia' =>
                        $fechaFinVigencia !== ''
                            ? $fechaFinVigencia
                            : null
                ]
            );

            unset(
                $_SESSION['old_material']
            );

            flash(
                'success',
                'El material fue creado correctamente.'
            );

            redirect('/materiales');
        } catch (Throwable $e) {
            flash(
                'error',
                'No fue posible crear el material. Verifique la información e inténtelo nuevamente.'
            );

            $_SESSION['old_material'] = [
                'codigo_unspsc' => $codigo,
                'descripcion' => $descripcion,
                'estado' => $estado,
                'fecha_inicio_vigencia' =>
                    $fechaInicioVigencia,
                'fecha_fin_vigencia' =>
                    $fechaFinVigencia
            ];

            redirect('/materiales');
        }
    }

    /**
     * Muestra un material para edición.
     */
    public function editar(): void
    {
        // El catálogo solo se modifica mediante la importación oficial de Excel.
        flash('error', 'Los materiales del catálogo solo se registran o actualizan mediante la importación del archivo Excel.');
        redirect('/materiales');
        return;

        Auth::requirePermission(
            'materiales.catalogo.editar'
        );

        $id = (int) (
            $_GET['id'] ?? 0
        );

        if ($id <= 0) {
            flash(
                'error',
                'El material solicitado no es válido.'
            );

            redirect('/materiales');
        }

        $material = $this->model->obtener(
            $id
        );

        if ($material === null) {
            flash(
                'error',
                'El material solicitado no existe.'
            );

            redirect('/materiales');
        }

        $this->view(
            'materiales/editar',
            [
                'material' => $material
            ]
        );
    }

    /**
     * Actualiza un material existente.
     */
    public function actualizar(): void
    {
        // El catálogo solo se modifica mediante la importación oficial de Excel.
        flash('error', 'Los materiales del catálogo solo se registran o actualizan mediante la importación del archivo Excel.');
        redirect('/materiales');
        return;

        Auth::requirePermission(
            'materiales.catalogo.editar'
        );

        if (
            strtoupper(
                $_SERVER['REQUEST_METHOD'] ?? 'GET'
            ) !== 'POST'
        ) {
            redirect('/materiales');
        }

        if (
            !verificarCsrf()
        ) {
            http_response_code(419);

            die(
                'La solicitud no es válida o ha expirado.'
            );
        }

        $id = (int) (
            $_POST['id'] ?? 0
        );

        if ($id <= 0) {
            flash(
                'error',
                'El material seleccionado no es válido.'
            );

            redirect('/materiales');
        }

        $materialActual = $this->model->obtener(
            $id
        );

        if ($materialActual === null) {
            flash(
                'error',
                'El material seleccionado no existe.'
            );

            redirect('/materiales');
        }

        $codigo = trim(
            (string) (
                $_POST['codigo_unspsc'] ?? ''
            )
        );

        $descripcion = trim(
            (string) (
                $_POST['descripcion'] ?? ''
            )
        );


        $estado = trim(
            (string) (
                $_POST['estado'] ?? 'activo'
            )
        );

        $fechaInicioVigencia = trim(
            (string) (
                $_POST['fecha_inicio_vigencia'] ?? ''
            )
        );

        $fechaFinVigencia = trim(
            (string) (
                $_POST['fecha_fin_vigencia'] ?? ''
            )
        );

        $errores = [];

        if ($codigo === '') {
            $errores[] =
                'El código UNSPSC es obligatorio.';
        } elseif (mb_strlen($codigo) > 30) {
            $errores[] =
                'El código UNSPSC no puede superar los 30 caracteres.';
        }

        if ($descripcion === '') {
            $errores[] =
                'La descripción del bien es obligatoria.';
        }


        if (
            !in_array(
                $estado,
                ['activo', 'inactivo'],
                true
            )
        ) {
            $errores[] =
                'El estado seleccionado no es válido.';
        }

        if (
            $fechaInicioVigencia !== ''
            && !$this->fechaValida(
                $fechaInicioVigencia
            )
        ) {
            $errores[] =
                'La fecha de inicio de vigencia no es válida.';
        }

        if (
            $fechaFinVigencia !== ''
            && !$this->fechaValida(
                $fechaFinVigencia
            )
        ) {
            $errores[] =
                'La fecha de fin de vigencia no es válida.';
        }

        if (
            $fechaInicioVigencia !== ''
            && $fechaFinVigencia !== ''
            && $fechaFinVigencia < $fechaInicioVigencia
        ) {
            $errores[] =
                'La fecha de fin de vigencia no puede ser anterior a la fecha de inicio.';
        }

        if (
            $this->model->existeCodigo(
                $codigo,
                $id
            )
        ) {
            $errores[] =
                'Ya existe otro material registrado con ese código UNSPSC.';
        }

        if (!empty($errores)) {
            flash(
                'error',
                implode(' ', $errores)
            );

            $_SESSION['old_material'] = [
                'id' => $id,
                'codigo_unspsc' => $codigo,
                'descripcion' => $descripcion,
                'estado' => $estado,
                'fecha_inicio_vigencia' =>
                    $fechaInicioVigencia,
                'fecha_fin_vigencia' =>
                    $fechaFinVigencia
            ];

            redirect(
                '/materiales/editar?id='
                . $id
            );
        }

        try {
            $actualizado = $this->model->actualizar(
                $id,
                [
                    'codigo_unspsc' => $codigo,
                    'descripcion' => $descripcion,
                        'estado' => $estado,
                    'fecha_inicio_vigencia' =>
                        $fechaInicioVigencia !== ''
                            ? $fechaInicioVigencia
                            : null,
                    'fecha_fin_vigencia' =>
                        $fechaFinVigencia !== ''
                            ? $fechaFinVigencia
                            : null
                ]
            );

            unset(
                $_SESSION['old_material']
            );

            flash(
                'success',
                $actualizado
                    ? 'El material fue actualizado correctamente.'
                    : 'El material no presentó cambios.'
            );

            redirect('/materiales');
        } catch (Throwable $e) {
            flash(
                'error',
                'No fue posible actualizar el material. Verifique la información e inténtelo nuevamente.'
            );

            $_SESSION['old_material'] = [
                'id' => $id,
                'codigo_unspsc' => $codigo,
                'descripcion' => $descripcion,
                'estado' => $estado,
                'fecha_inicio_vigencia' =>
                    $fechaInicioVigencia,
                'fecha_fin_vigencia' =>
                    $fechaFinVigencia
            ];

            redirect(
                '/materiales/editar?id='
                . $id
            );
        }
    }

    /**
     * Cambia el estado activo/inactivo.
     */
    public function cambiarEstado(): void
    {
        Auth::requirePermission(
            'materiales.catalogo.estado'
        );

        if (
            strtoupper(
                $_SERVER['REQUEST_METHOD'] ?? 'GET'
            ) !== 'POST'
        ) {
            redirect('/materiales');
        }

        if (
            !verificarCsrf()
        ) {
            http_response_code(419);

            die(
                'La solicitud no es válida o ha expirado.'
            );
        }

        $id = (int) (
            $_POST['id'] ?? 0
        );

        $estado = trim(
            (string) (
                $_POST['estado'] ?? ''
            )
        );

        if ($id <= 0) {
            flash(
                'error',
                'El material seleccionado no es válido.'
            );

            redirect('/materiales');
        }

        if (
            !in_array(
                $estado,
                ['activo', 'inactivo'],
                true
            )
        ) {
            flash(
                'error',
                'El estado seleccionado no es válido.'
            );

            redirect('/materiales');
        }

        $material = $this->model->obtener(
            $id
        );

        if ($material === null) {
            flash(
                'error',
                'El material seleccionado no existe.'
            );

            redirect('/materiales');
        }

        $estadoActual = (string) (
            $material['estado'] ?? ''
        );

        if ($estadoActual === $estado) {
            flash(
                'error',
                'El material ya se encuentra en el estado seleccionado.'
            );

            redirect('/materiales');
        }

        try {
            $actualizado =
                $this->model->cambiarEstado(
                    $id,
                    $estado
                );

            if ($actualizado) {
                $mensaje = $estado === 'activo'
                    ? 'El material fue activado correctamente.'
                    : 'El material fue inactivado correctamente.';

                flash(
                    'success',
                    $mensaje
                );
            } else {
                flash(
                    'error',
                    'No fue posible cambiar el estado del material.'
                );
            }
        } catch (Throwable $e) {
            flash(
                'error',
                'No fue posible cambiar el estado del material.'
            );
        }

        redirect('/materiales');
    }

    /**
     * Valida una fecha en formato Y-m-d.
     */
    private function fechaValida(
        string $fecha
    ): bool {
        $objeto = DateTime::createFromFormat(
            'Y-m-d',
            $fecha
        );

        return $objeto !== false
            && $objeto->format('Y-m-d') === $fecha;
    }
}