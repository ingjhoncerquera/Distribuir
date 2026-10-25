<?php

declare(strict_types=1);

require_once ROOT_PATH . '/models/MaterialesProcesoAreaModel.php';
require_once ROOT_PATH . '/models/MaterialesProcesoModel.php';

class MaterialesProcesoAreaController
{
    private MaterialesProcesoAreaModel $model;
    private MaterialesProcesoModel $procesoModel;

    public function __construct()
    {
        $this->model = new MaterialesProcesoAreaModel();
        $this->procesoModel = new MaterialesProcesoModel();
    }

    /**
     * Listado general.
     */
    public function index(): void
    {
        $this->exigirAcceso();

        $procesos = $this->model->listarProcesos();

        $idProceso = isset($_GET['id_proceso'])
            ? (int) $_GET['id_proceso']
            : 0;

        $proceso = null;
        $areas = [];

        if ($idProceso > 0) {
            $proceso = $this->model->obtenerProceso($idProceso);

            if ($proceso !== null) {
                $areas = $this->model->listarPorProceso($idProceso);
            }
        }

        require ROOT_PATH . '/views/materiales/areas/index.php';
    }

    /**
     * Formulario para crear área.
     */
    public function crear(): void
    {
        $this->exigirCrear();

        $idProceso = isset($_GET['id_proceso'])
            ? (int) $_GET['id_proceso']
            : 0;

        if ($idProceso <= 0) {
            redirect('/materiales/areas');
            return;
        }

        $proceso = $this->model->obtenerProceso($idProceso);

        if ($proceso === null) {
            http_response_code(404);
            die('El proceso seleccionado no existe.');
        }

        $profesionales =
            $this->model->listarProfesionalesResponsables();

        require ROOT_PATH . '/views/materiales/areas/crear.php';
    }

    /**
     * Guarda un área nueva.
     *
     * Toda área nueva se crea automáticamente como ACTIVA.
     */
    public function guardar(): void
    {
        $this->exigirCrear();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/materiales/areas');
            return;
        }

        $idProceso = (int) (
            $_POST['id_proceso'] ?? 0
        );

        $nombreArea = trim(
            (string) (
                $_POST['nombre_area'] ?? ''
            )
        );

        $observaciones = trim(
            (string) (
                $_POST['observaciones'] ?? ''
            )
        );

        $idProfesional = (int) (
            $_POST['id_profesional'] ?? 0
        );

        if ($idProceso <= 0) {
            $this->error(
                'Debe seleccionar un proceso.'
            );
            return;
        }

        $proceso =
            $this->model->obtenerProceso($idProceso);

        if ($proceso === null) {
            $this->error(
                'El proceso seleccionado no existe.'
            );
            return;
        }

        if ($nombreArea === '') {
            $this->error(
                'Debe indicar el nombre del área.'
            );
            return;
        }

        if (mb_strlen($nombreArea) > 150) {
            $this->error(
                'El nombre del área no puede superar los 150 caracteres.'
            );
            return;
        }

        if (
            $this->model->existeNombre(
                $idProceso,
                $nombreArea
            )
        ) {
            $this->error(
                'Ya existe un área con ese nombre dentro del proceso.'
            );
            return;
        }

        try {

            /**
             * IMPORTANTE:
             *
             * La firma del modelo es:
             *
             * crear(
             *     idProceso,
             *     nombreArea,
             *     estado,
             *     observaciones
             * )
             *
             * Por eso el estado se establece explícitamente
             * como "activa".
             */
            $idArea = $this->model->crear(
                $idProceso,
                $nombreArea,
                'activa',
                $observaciones
            );

            /**
             * Asignar responsable si fue seleccionado.
             */
            if ($idProfesional > 0) {

                $this->model->asignarResponsable(
                    $idArea,
                    $idProfesional,
                    $this->obtenerUsuarioActualId()
                );
            }

            redirect(
                '/materiales/areas?id_proceso=' .
                $idProceso
            );

        } catch (Throwable $e) {

            http_response_code(500);

            die(
                'No fue posible crear el área. ' .
                htmlspecialchars(
                    $e->getMessage(),
                    ENT_QUOTES,
                    'UTF-8'
                )
            );
        }
    }

    /**
     * Formulario para editar un área.
     */
    public function editar(): void
    {
        $this->exigirCrear();

        $idArea = isset($_GET['id'])
            ? (int) $_GET['id']
            : 0;

        if ($idArea <= 0) {
            redirect('/materiales/areas');
            return;
        }

        $area =
            $this->model->obtenerPorId($idArea);

        if ($area === null) {
            http_response_code(404);
            die('El área no existe.');
        }

        $proceso =
            $this->model->obtenerProceso(
                (int) $area['id_proceso']
            );

        if ($proceso === null) {
            http_response_code(404);
            die('El proceso asociado no existe.');
        }

        $profesionales =
            $this->model->listarProfesionalesResponsables();

        $responsable =
            $this->model->obtenerResponsableActivo(
                $idArea
            );

        require ROOT_PATH . '/views/materiales/areas/editar.php';
    }

    /**
     * Actualiza un área.
     */
    public function actualizar(): void
    {
        $this->exigirCrear();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/materiales/areas');
            return;
        }

        $idArea = (int) (
            $_POST['id'] ?? 0
        );

        $nombreArea = trim(
            (string) (
                $_POST['nombre_area'] ?? ''
            )
        );

        $observaciones = trim(
            (string) (
                $_POST['observaciones'] ?? ''
            )
        );

        $estado = strtolower(
            trim(
                (string) (
                    $_POST['estado'] ?? ''
                )
            )
        );

        $idProfesional = (int) (
            $_POST['id_profesional'] ?? 0
        );

        if ($idArea <= 0) {
            $this->error(
                'El área seleccionada no es válida.'
            );
            return;
        }

        $area =
            $this->model->obtenerPorId($idArea);

        if ($area === null) {
            $this->error(
                'El área no existe.'
            );
            return;
        }

        $idProceso = (int) $area['id_proceso'];

        if ($nombreArea === '') {
            $this->error(
                'Debe indicar el nombre del área.'
            );
            return;
        }

        if (mb_strlen($nombreArea) > 150) {
            $this->error(
                'El nombre del área no puede superar los 150 caracteres.'
            );
            return;
        }

        /**
         * Estados permitidos.
         */
        $estadosPermitidos = [
            'activa',
            'inactiva',
            'cerrada'
        ];

        /**
         * Si por alguna razón el formulario no envía
         * el estado, conserva el estado actual.
         */
        if (
            !in_array(
                $estado,
                $estadosPermitidos,
                true
            )
        ) {
            $estado = strtolower(
                trim(
                    (string) (
                        $area['estado'] ?? 'activa'
                    )
                )
            );

            if (
                !in_array(
                    $estado,
                    $estadosPermitidos,
                    true
                )
            ) {
                $estado = 'activa';
            }
        }

        if (
            $this->model->existeNombre(
                $idProceso,
                $nombreArea,
                $idArea
            )
        ) {
            $this->error(
                'Ya existe otra área con ese nombre dentro del proceso.'
            );
            return;
        }

        try {

            /**
             * Actualizar datos del área incluyendo
             * el estado seleccionado.
             */
            $this->model->actualizar(
                $idArea,
                $nombreArea,
                $observaciones,
                $estado
            );

            /**
             * Gestionar responsable.
             */
            if ($idProfesional > 0) {

                $responsableActual =
                    $this->model->obtenerResponsableActivo(
                        $idArea
                    );

                $cambioResponsable =
                    $responsableActual === null
                    || (int) $responsableActual['id_profesional']
                        !== $idProfesional;

                if ($cambioResponsable) {

                    $this->model->asignarResponsable(
                        $idArea,
                        $idProfesional,
                        $this->obtenerUsuarioActualId()
                    );
                }

            } else {

                $this->model->retirarResponsable(
                    $idArea
                );
            }

            redirect(
                '/materiales/areas?id_proceso=' .
                $idProceso
            );

        } catch (Throwable $e) {

            http_response_code(500);

            die(
                'No fue posible actualizar el área. ' .
                htmlspecialchars(
                    $e->getMessage(),
                    ENT_QUOTES,
                    'UTF-8'
                )
            );
        }
    }

    /**
     * Cambia el estado del área.
     */
    public function estado(): void
    {
        $this->exigirCrear();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/materiales/areas');
            return;
        }

        $idArea = (int) (
            $_POST['id'] ?? 0
        );

        $estado = strtolower(
            trim(
                (string) (
                    $_POST['estado'] ?? ''
                )
            )
        );

        if ($idArea <= 0) {
            $this->error(
                'Área no válida.'
            );
            return;
        }

        $area =
            $this->model->obtenerPorId($idArea);

        if ($area === null) {
            $this->error(
                'El área no existe.'
            );
            return;
        }

        $estadosPermitidos = [
            'activa',
            'inactiva',
            'cerrada'
        ];

        if (
            !in_array(
                $estado,
                $estadosPermitidos,
                true
            )
        ) {
            $this->error(
                'Estado no válido.'
            );
            return;
        }

        try {

            $this->model->cambiarEstado(
                $idArea,
                $estado
            );

            redirect(
                '/materiales/areas?id_proceso=' .
                (int) $area['id_proceso']
            );

        } catch (Throwable $e) {

            http_response_code(500);

            die(
                'No fue posible cambiar el estado del área. ' .
                htmlspecialchars(
                    $e->getMessage(),
                    ENT_QUOTES,
                    'UTF-8'
                )
            );
        }
    }

    /**
     * Asigna/reemplaza responsable.
     */
    public function responsable(): void
    {
        $this->exigirCrear();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/materiales/areas');
            return;
        }

        $idArea = (int) (
            $_POST['id_area'] ?? 0
        );

        $idProfesional = (int) (
            $_POST['id_profesional'] ?? 0
        );

        $observaciones = trim(
            (string) (
                $_POST['observaciones'] ?? ''
            )
        );

        if (
            $idArea <= 0
            || $idProfesional <= 0
        ) {
            $this->error(
                'Debe seleccionar el área y el responsable.'
            );
            return;
        }

        $area =
            $this->model->obtenerPorId($idArea);

        if ($area === null) {
            $this->error(
                'El área no existe.'
            );
            return;
        }

        try {

            $this->model->asignarResponsable(
                $idArea,
                $idProfesional,
                $this->obtenerUsuarioActualId(),
                $observaciones
            );

            redirect(
                '/materiales/areas?id_proceso=' .
                (int) $area['id_proceso']
            );

        } catch (Throwable $e) {

            http_response_code(500);

            die(
                'No fue posible asignar el responsable. ' .
                htmlspecialchars(
                    $e->getMessage(),
                    ENT_QUOTES,
                    'UTF-8'
                )
            );
        }
    }

    /**
     * Retira el responsable.
     */
    public function retirarResponsable(): void
    {
        $this->exigirCrear();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/materiales/areas');
            return;
        }

        $idArea = (int) (
            $_POST['id_area'] ?? 0
        );

        if ($idArea <= 0) {
            $this->error(
                'Área no válida.'
            );
            return;
        }

        $area =
            $this->model->obtenerPorId($idArea);

        if ($area === null) {
            $this->error(
                'El área no existe.'
            );
            return;
        }

        try {

            $this->model->retirarResponsable(
                $idArea
            );

            redirect(
                '/materiales/areas?id_proceso=' .
                (int) $area['id_proceso']
            );

        } catch (Throwable $e) {

            http_response_code(500);

            die(
                'No fue posible retirar el responsable. ' .
                htmlspecialchars(
                    $e->getMessage(),
                    ENT_QUOTES,
                    'UTF-8'
                )
            );
        }
    }

    /**
     * Control de acceso.
     *
     * Solo Administrador y Coordinador administran
     * áreas y responsables.
     */
    private function exigirAcceso(): void
    {
        if (
            Auth::hasRole('Administrador')
            || Auth::hasRole('Coordinador')
        ) {
            return;
        }

        http_response_code(403);

        die(
            'No tiene permisos para consultar las áreas y responsables de materiales.'
        );
    }

    /**
     * Control de creación/edición.
     */
    private function exigirCrear(): void
    {
        if (
            Auth::hasRole('Administrador')
            || Auth::hasRole('Coordinador')
        ) {
            return;
        }

        http_response_code(403);

        die(
            'No tiene permisos para gestionar las áreas y responsables de materiales.'
        );
    }

    /**
     * Obtiene el ID del usuario autenticado.
     */
    private function obtenerUsuarioActualId(): ?int
    {
        if (isset($_SESSION['usuario_id'])) {
            return (int) $_SESSION['usuario_id'];
        }

        if (isset($_SESSION['id_usuario'])) {
            return (int) $_SESSION['id_usuario'];
        }

        if (isset($_SESSION['usuario']['id'])) {
            return (int) $_SESSION['usuario']['id'];
        }

        return null;
    }

    /**
     * Mensaje de error sencillo.
     */
    private function error(string $mensaje): void
    {
        http_response_code(400);

        die(
            htmlspecialchars(
                $mensaje,
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }
}