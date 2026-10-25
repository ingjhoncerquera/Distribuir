<?php

declare(strict_types=1);

class MaterialesProcesoController extends Controller
{
    private MaterialesProcesoModel $modelo;

    public function __construct()
    {
        Auth::requireLogin();

        $this->modelo = new MaterialesProcesoModel();
    }

    /**
     * Listado de procesos.
     */
    public function index(): void
    {
        $this->exigirAcceso();

        $procesos = $this->modelo->listar();

        $resumenes = [];

        foreach ($procesos as $proceso) {
            $id = (int) ($proceso['id'] ?? 0);

            $resumenes[$id] = $this->modelo->obtenerResumen($id);
        }

        $this->view('materiales/procesos/index', [
            'procesos' => $procesos,
            'resumenes' => $resumenes,
        ]);
    }

    /**
     * Formulario para crear un proceso.
     */
    public function crear(): void
    {
        $this->exigirCrear();

        $coordinadores = $this->modelo->listarCoordinadores();

        $this->view('materiales/procesos/crear', [
            'coordinadores' => $coordinadores,
        ]);
    }

    /**
     * Guardar un nuevo proceso.
     */
    public function guardar(): void
    {
        $this->exigirCrear();
        exigirCsrf();

        if (!isPost()) {
            redirect('/materiales/procesos/crear');
        }

        $anio = (int) post('anio', date('Y'));
        $nombre = trim((string) post('nombre', ''));
        $descripcion = trim((string) post('descripcion', ''));
        $fechaInicio = trim((string) post('fecha_inicio', ''));
        $fechaCierre = trim((string) post('fecha_cierre', ''));
        $estado = trim((string) post('estado', 'borrador'));
        $idCoordinador = post('id_coordinador', '');

        $errores = $this->validarDatos(
            $anio,
            $nombre,
            $fechaInicio,
            $fechaCierre,
            $estado
        );

        if ($this->modelo->existeNombreAnio($nombre, $anio)) {
            $errores[] = 'Ya existe un proceso con ese nombre para el año seleccionado.';
        }

        if ($errores !== []) {
            flash(
                'error',
                implode('<br>', array_map('e', $errores))
            );

            $_SESSION['materiales_proceso_form'] = [
                'anio' => $anio,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'fecha_inicio' => $fechaInicio,
                'fecha_cierre' => $fechaCierre,
                'estado' => $estado,
                'id_coordinador' => $idCoordinador,
            ];

            redirect('/materiales/procesos/crear');
        }

        try {
            $id = $this->modelo->crear([
                'anio' => $anio,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'fecha_inicio' => $fechaInicio !== ''
                    ? $fechaInicio
                    : null,
                'fecha_cierre' => $fechaCierre !== ''
                    ? $fechaCierre
                    : null,
                'estado' => $estado,
                'id_coordinador' => $idCoordinador,
            ]);

            unset($_SESSION['materiales_proceso_form']);

            flash(
                'success',
                'El proceso de materiales fue creado correctamente.'
            );

            /*
             * Después de crear el proceso regresamos al listado.
             */
            redirect('/materiales/procesos');

        } catch (Throwable $e) {
            flash(
                'error',
                APP_DEBUG
                    ? 'No fue posible crear el proceso: ' . $e->getMessage()
                    : 'No fue posible crear el proceso.'
            );

            redirect('/materiales/procesos/crear');
        }
    }

    /**
     * Formulario para editar un proceso.
     */
    public function editar(): void
    {
        $this->exigirCrear();

        $id = (int) get('id', 0);

        if ($id <= 0) {
            flash(
                'error',
                'El proceso indicado no es válido.'
            );

            redirect('/materiales/procesos');
        }

        $proceso = $this->modelo->obtenerPorId($id);

        if (!$proceso) {
            flash(
                'error',
                'El proceso no existe.'
            );

            redirect('/materiales/procesos');
        }

        $coordinadores = $this->modelo->listarCoordinadores();
        $resumen = $this->modelo->obtenerResumen($id);

        $this->view('materiales/procesos/editar', [
            'proceso' => $proceso,
            'coordinadores' => $coordinadores,
            'resumen' => $resumen,
        ]);
    }

    /**
     * Actualizar un proceso.
     */
    public function actualizar(): void
    {
        $this->exigirCrear();
        exigirCsrf();

        if (!isPost()) {
            redirect('/materiales/procesos');
        }

        $id = (int) post('id', 0);

        if ($id <= 0) {
            flash(
                'error',
                'El proceso indicado no es válido.'
            );

            redirect('/materiales/procesos');
        }

        $procesoActual = $this->modelo->obtenerPorId($id);

        if (!$procesoActual) {
            flash(
                'error',
                'El proceso no existe.'
            );

            redirect('/materiales/procesos');
        }

        $anio = (int) post('anio', date('Y'));
        $nombre = trim((string) post('nombre', ''));
        $descripcion = trim((string) post('descripcion', ''));
        $fechaInicio = trim((string) post('fecha_inicio', ''));
        $fechaCierre = trim((string) post('fecha_cierre', ''));
        $estado = trim((string) post('estado', 'borrador'));
        $idCoordinador = post('id_coordinador', '');

        $errores = $this->validarDatos(
            $anio,
            $nombre,
            $fechaInicio,
            $fechaCierre,
            $estado
        );

        if (
            $this->modelo->existeNombreAnio(
                $nombre,
                $anio,
                $id
            )
        ) {
            $errores[] = 'Ya existe otro proceso con ese nombre para el año seleccionado.';
        }

        if ($errores !== []) {
            flash(
                'error',
                implode('<br>', array_map('e', $errores))
            );

            /*
             * Si hay errores, permanecemos en edición
             * para que el usuario pueda corregirlos.
             */
            redirect('/materiales/procesos/editar?id=' . $id);
        }

        try {
            $actualizado = $this->modelo->actualizar(
                $id,
                [
                    'anio' => $anio,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'fecha_inicio' => $fechaInicio !== ''
                        ? $fechaInicio
                        : null,
                    'fecha_cierre' => $fechaCierre !== ''
                        ? $fechaCierre
                        : null,
                    'estado' => $estado,
                    'id_coordinador' => $idCoordinador,
                ]
            );

            if (!$actualizado) {
                throw new RuntimeException(
                    'No se pudo actualizar el proceso.'
                );
            }

            flash(
                'success',
                'El proceso fue actualizado correctamente.'
            );

        } catch (Throwable $e) {
            flash(
                'error',
                APP_DEBUG
                    ? 'No fue posible actualizar el proceso: ' . $e->getMessage()
                    : 'No fue posible actualizar el proceso.'
            );

            /*
             * Si ocurre un error de base de datos u otro error
             * durante la actualización, permanecemos en edición.
             */
            redirect('/materiales/procesos/editar?id=' . $id);
        }

        /*
         * Actualización correcta:
         * regresamos al listado de procesos.
         */
        redirect('/materiales/procesos');
    }

    /**
     * Cambiar el estado del proceso.
     */
    public function estado(): void
    {
        $this->exigirCrear();
        exigirCsrf();

        if (!isPost()) {
            redirect('/materiales/procesos');
        }

        $id = (int) post('id', 0);
        $estado = trim((string) post('estado', ''));

        if ($id <= 0) {
            flash(
                'error',
                'El proceso indicado no es válido.'
            );

            redirect('/materiales/procesos');
        }

        if (!$this->modelo->existe($id)) {
            flash(
                'error',
                'El proceso no existe.'
            );

            redirect('/materiales/procesos');
        }

        try {
            $resultado = $this->modelo->cambiarEstado(
                $id,
                $estado
            );

            if (!$resultado) {
                throw new RuntimeException(
                    'Estado no válido.'
                );
            }

            flash(
                'success',
                'El estado del proceso fue actualizado correctamente.'
            );

        } catch (Throwable $e) {
            flash(
                'error',
                APP_DEBUG
                    ? 'No fue posible cambiar el estado: ' . $e->getMessage()
                    : 'No fue posible cambiar el estado del proceso.'
            );
        }

        redirect('/materiales/procesos');
    }

    /**
     * Eliminar proceso.
     *
     * Por seguridad se evita eliminar procesos que ya tengan
     * áreas o solicitudes relacionadas.
     */
    public function eliminar(): void
    {
        $this->exigirEliminar();
        exigirCsrf();

        if (!isPost()) {
            redirect('/materiales/procesos');
        }

        $id = (int) post('id', 0);

        if ($id <= 0) {
            flash(
                'error',
                'El proceso indicado no es válido.'
            );

            redirect('/materiales/procesos');
        }

        $proceso = $this->modelo->obtenerPorId($id);

        if (!$proceso) {
            flash(
                'error',
                'El proceso no existe.'
            );

            redirect('/materiales/procesos');
        }

        $resumen = $this->modelo->obtenerResumen($id);

        if (
            (int) ($resumen['areas'] ?? 0) > 0
            || (int) ($resumen['solicitudes'] ?? 0) > 0
        ) {
            flash(
                'error',
                'No se puede eliminar el proceso porque tiene áreas o solicitudes relacionadas. Puede cambiar su estado a cancelado.'
            );

            redirect('/materiales/procesos');
        }

        try {
            $resultado = $this->modelo->eliminar($id);

            if (!$resultado) {
                throw new RuntimeException(
                    'No fue posible eliminar el proceso.'
                );
            }

            flash(
                'success',
                'El proceso fue eliminado correctamente.'
            );

        } catch (Throwable $e) {
            flash(
                'error',
                APP_DEBUG
                    ? 'No fue posible eliminar el proceso: ' . $e->getMessage()
                    : 'No fue posible eliminar el proceso.'
            );
        }

        redirect('/materiales/procesos');
    }

    /**
     * Validar información del proceso.
     */
    private function validarDatos(
        int $anio,
        string $nombre,
        string $fechaInicio,
        string $fechaCierre,
        string $estado
    ): array {
        $errores = [];

        $estadosPermitidos = [
            'borrador',
            'abierto',
            'en_solicitud',
            'en_aval',
            'en_compra',
            'en_recepcion',
            'en_distribucion',
            'cerrado',
            'cancelado',
        ];

        if ($anio < 2020 || $anio > 2100) {
            $errores[] = 'El año del proceso no es válido.';
        }

        if ($nombre === '') {
            $errores[] = 'El nombre del proceso es obligatorio.';
        } elseif (mb_strlen($nombre) > 200) {
            $errores[] = 'El nombre del proceso no puede superar los 200 caracteres.';
        }

        if (
            $fechaInicio !== ''
            && !$this->fechaValida($fechaInicio)
        ) {
            $errores[] = 'La fecha de inicio no es válida.';
        }

        if (
            $fechaCierre !== ''
            && !$this->fechaValida($fechaCierre)
        ) {
            $errores[] = 'La fecha de cierre no es válida.';
        }

        if (
            $fechaInicio !== ''
            && $fechaCierre !== ''
            && $this->fechaValida($fechaInicio)
            && $this->fechaValida($fechaCierre)
            && $fechaCierre < $fechaInicio
        ) {
            $errores[] = 'La fecha de cierre no puede ser anterior a la fecha de inicio.';
        }

        if (!in_array($estado, $estadosPermitidos, true)) {
            $errores[] = 'El estado seleccionado no es válido.';
        }

        return $errores;
    }

    /**
     * Validar fecha en formato YYYY-MM-DD.
     */
    private function fechaValida(string $fecha): bool
    {
        $objeto = DateTime::createFromFormat(
            'Y-m-d',
            $fecha
        );

        return $objeto !== false
            && $objeto->format('Y-m-d') === $fecha;
    }

    /**
     * Permisos de consulta.
     */
    private function exigirAcceso(): void
    {
        if (
            Auth::hasRole('Administrador')
            || Auth::can('materiales.procesos.ver')
            || Auth::can('materiales.procesos.crear')
            || Auth::can('materiales.solicitudes.gestionar')
        ) {
            return;
        }

        http_response_code(403);

        die(
            'No tiene permisos para consultar los procesos de materiales.'
        );
    }

    /**
     * Permisos de creación/edición.
     *
     * Mientras terminamos de implementar el módulo de procesos,
     * el Administrador puede gestionarlo directamente.
     */
    private function exigirCrear(): void
    {
        if (
            Auth::hasRole('Administrador')
            || Auth::can('materiales.procesos.crear')
        ) {
            return;
        }

        http_response_code(403);

        die(
            'No tiene permisos para gestionar los procesos de materiales.'
        );
    }

    /**
     * Permiso para eliminar.
     */
    private function exigirEliminar(): void
    {
        if (
            Auth::hasRole('Administrador')
            || Auth::can('materiales.procesos.estado')
        ) {
            return;
        }

        http_response_code(403);

        die(
            'No tiene permisos para eliminar procesos de materiales.'
        );
    }
}