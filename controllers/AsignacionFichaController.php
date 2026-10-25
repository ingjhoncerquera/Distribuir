<?php

declare(strict_types=1);

class AsignacionFichaController extends Controller
{
    private AsignacionFichaModel $model;

    public function __construct()
    {
        $this->model = new AsignacionFichaModel();
    }

    /*
    |--------------------------------------------------------------------------
    | LISTADO
    |--------------------------------------------------------------------------
    */

    public function index(): void
    {
        Auth::requirePermission(
            'asignaciones_ficha.ver'
        );

        $buscar = trim(
            (string) $this->get('buscar', '')
        );

        $asignaciones = $this->model->listar(
            $buscar
        );

        /*
         * Resumen de responsables por ficha. Si hay búsqueda, se cargan
         * todas las asignaciones de las fichas encontradas, para que el
         * resumen no muestre "Sin asignar" por haber filtrado a la persona.
         */
        $asignacionesResumen = $asignaciones;

        if ($buscar !== '' && !empty($asignaciones)) {

            $idsFichas = array_values(
                array_unique(
                    array_map(
                        static fn (array $fila): int =>
                            (int) $fila['id_ficha'],
                        $asignaciones
                    )
                )
            );

            $asignacionesResumen = $this->model->listar(
                '',
                $idsFichas
            );
        }

        $fichas = $this->model->fichasDisponibles();

        $profesionales = $this->model->profesionalesDisponibles();

        $this->view(
            'asignaciones_ficha/index',
            [
                'tituloPagina' => 'Asignaciones de ficha',
                'titulo' => 'Asignaciones de ficha',
                'buscar' => $buscar,
                'asignaciones' => $asignaciones,
                'asignacionesResumen' => $asignacionesResumen,
                'fichas' => $fichas,
                'profesionales' => $profesionales,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR
    |--------------------------------------------------------------------------
    |
    | El formulario permite registrar en un solo envío:
    |
    |   - Líder de ficha          (id_lider_ficha)
    |   - Coordinador académico   (id_coordinador)
    |   - Otro profesional        (id_profesional + tipo_asignacion)
    |
    | Los tres son opcionales entre sí, pero debe venir al menos uno.
    | Todas las asignaciones comparten ficha, fechas y observaciones,
    | y se guardan en una sola transacción.
    |
    */

    public function crear(): void
    {
        Auth::requirePermission(
            'asignaciones_ficha.crear'
        );

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/asignaciones-ficha');
        }

        $idFicha = (int) (
            $_POST['id_ficha'] ?? 0
        );

        $idLider = (int) (
            $_POST['id_lider_ficha'] ?? 0
        );

        $idCoordinador = (int) (
            $_POST['id_coordinador'] ?? 0
        );

        $idOtroProfesional = (int) (
            $_POST['id_profesional'] ?? 0
        );

        $tipoOtro = trim(
            (string) (
                $_POST['tipo_asignacion'] ?? ''
            )
        );

        $fechaInicio = trim(
            (string) (
                $_POST['fecha_inicio'] ?? ''
            )
        );

        $fechaFin = trim(
            (string) (
                $_POST['fecha_fin'] ?? ''
            )
        );

        $observaciones = trim(
            (string) (
                $_POST['observaciones'] ?? ''
            )
        );

        // Casilla "Reemplazar al titular vigente"
        $reemplazar = (
            ($_POST['reemplazar'] ?? '') === '1'
        );

        /*
         * Tipos que se pueden elegir en "Otro profesional".
         * Líder y coordinador tienen sus propios campos.
         */
        $tiposAdicionales = [
            'INSTRUCTOR_TECNICO',
            'INSTRUCTOR_TRANSVERSAL',
            'BIENESTAR',
            'SALUD_FISICA',
            'SALUD_MENTAL',
            'NUTRICION',
        ];

        /*
         * Validaciones básicas
         */

        if ($idFicha <= 0) {
            $this->fallar('Debe seleccionar una ficha.');
            return;
        }

        if ($fechaInicio === '') {
            $this->fallar('Debe indicar la fecha de inicio.');
            return;
        }

        if (!$this->fechaValida($fechaInicio)) {
            $this->fallar('La fecha de inicio no es válida.');
            return;
        }

        if (
            $fechaFin !== ''
            && !$this->fechaValida($fechaFin)
        ) {
            $this->fallar('La fecha de finalización no es válida.');
            return;
        }

        if (
            $fechaFin !== ''
            && $fechaFin < $fechaInicio
        ) {
            $this->fallar(
                'La fecha final no puede ser anterior a la fecha inicial.'
            );
            return;
        }

        /*
         * Lista de asignaciones a crear
         */

        $solicitadas = [];

        if ($idLider > 0) {

            $solicitadas[] = [
                'tipo' => 'LIDER_FICHA',
                'rol' => 'líder de ficha',
                'id_profesional' => $idLider,
                'unico' => true,
            ];
        }

        if ($idCoordinador > 0) {

            $solicitadas[] = [
                'tipo' => 'COORDINADOR',
                'rol' => 'coordinador académico',
                'id_profesional' => $idCoordinador,
                'unico' => true,
            ];
        }

        if ($idOtroProfesional > 0 || $tipoOtro !== '') {

            if ($idOtroProfesional <= 0) {
                $this->fallar(
                    'Seleccione el profesional adicional o quite el tipo de asignación.'
                );
                return;
            }

            if (
                !in_array(
                    $tipoOtro,
                    $tiposAdicionales,
                    true
                )
            ) {
                $this->fallar(
                    'El tipo de asignación del profesional adicional no es válido.'
                );
                return;
            }

            $solicitadas[] = [
                'tipo' => $tipoOtro,
                'rol' => 'profesional adicional',
                'id_profesional' => $idOtroProfesional,
                'unico' => false,
            ];
        }

        if (empty($solicitadas)) {
            $this->fallar(
                'Seleccione al menos un responsable: líder de ficha, coordinador académico u otro profesional.'
            );
            return;
        }

        /*
         * Validaciones contra la base de datos y registro.
         *
         * El mensaje de error se guarda en $errorBD y se muestra al final,
         * fuera del try, para que ninguna redirección quede dentro del catch.
         */

        $errorBD = null;

        try {

            if (!$this->model->fichaActiva($idFicha)) {

                $errorBD =
                    'La ficha seleccionada no existe o no está activa.';
            }

            $datosCrear = [];

            $reemplazos = [];

            if ($errorBD === null) {

                foreach ($solicitadas as $item) {

                    if (
                        !$this->model->profesionalActivo(
                            $item['id_profesional']
                        )
                    ) {

                        $errorBD =
                            'El ' . $item['rol']
                            . ' seleccionado no existe o no está activo.';

                        break;
                    }

                    /*
                     * Líder y coordinador son únicos por ficha. Si ya hay
                     * un titular vigente en esas fechas:
                     *   - sin la casilla "Reemplazar": se informa quién es;
                     *   - con la casilla: se finaliza al anterior el día
                     *     previo al inicio de la nueva asignación.
                     * Para los demás tipos solo se evita repetir a la
                     * misma persona con el mismo tipo.
                     */
                    $cruzadas = $this->model->asignacionesActivasCruzadas(
                        $idFicha,
                        $item['tipo'],
                        $fechaInicio,
                        $fechaFin,
                        $item['unico']
                            ? null
                            : $item['id_profesional']
                    );

                    if (!empty($cruzadas) && !$item['unico']) {

                        $errorBD =
                            'Ese profesional ya tiene asignado ese tipo '
                            . 'en la ficha durante ese período.';

                        break;
                    }

                    if (!empty($cruzadas)) {

                        $actual = $cruzadas[0];

                        $nombreActual = trim(
                            (string) ($actual['nombre_profesional'] ?? '')
                        );

                        $nombreActual = $nombreActual !== ''
                            ? $nombreActual
                            : 'otro profesional';

                        if (
                            (int) $actual['id_profesional']
                            === (int) $item['id_profesional']
                        ) {

                            $errorBD =
                                $nombreActual . ' ya es el '
                                . $item['rol']
                                . ' vigente de esta ficha.';

                            break;
                        }

                        if (!$reemplazar) {

                            $errorBD =
                                'La ficha ya tiene un ' . $item['rol']
                                . ' vigente: ' . $nombreActual
                                . ' (desde ' . $actual['fecha_inicio']
                                . ($actual['fecha_fin']
                                    ? ' hasta ' . $actual['fecha_fin']
                                    : ', sin fecha de finalización')
                                . '). Marque «Reemplazar al titular vigente» '
                                . 'para finalizarlo y registrar el nuevo, '
                                . 'o finalice esa asignación desde el listado.';

                            break;
                        }

                        $finAnterior = (new DateTimeImmutable($fechaInicio))
                            ->modify('-1 day')
                            ->format('Y-m-d');

                        foreach ($cruzadas as $anterior) {

                            if (
                                (string) $anterior['fecha_inicio']
                                >= $fechaInicio
                            ) {

                                $errorBD =
                                    'No se puede reemplazar: ' . $nombreActual
                                    . ' inicia el ' . $anterior['fecha_inicio']
                                    . ', en la misma fecha o después de la '
                                    . 'nueva asignación. Finalícela desde el '
                                    . 'listado o elija otra fecha de inicio.';

                                break 2;
                            }

                            $reemplazos[(int) $anterior['id']] = [
                                'id' => (int) $anterior['id'],
                                'fecha_fin' => $finAnterior,
                            ];
                        }
                    }

                    $datosCrear[] = [
                        'id_ficha' => $idFicha,
                        'id_profesional' => $item['id_profesional'],
                        'tipo_asignacion' => $item['tipo'],
                        'fecha_inicio' => $fechaInicio,
                        'fecha_fin' => $fechaFin,
                        'estado' => 'activa',
                        'observaciones' => $observaciones,
                    ];
                }
            }

            if ($errorBD === null) {

                $total = $this->model->crearVarias(
                    $datosCrear,
                    array_values($reemplazos)
                );

                $mensaje = $total === 1
                    ? 'La asignación fue registrada correctamente.'
                    : 'Se registraron ' . $total
                        . ' asignaciones correctamente.';

                if (!empty($reemplazos)) {

                    $mensaje .= count($reemplazos) === 1
                        ? ' Se finalizó 1 asignación anterior.'
                        : ' Se finalizaron ' . count($reemplazos)
                            . ' asignaciones anteriores.';
                }

                $_SESSION['success'] = $mensaje;
            }

        } catch (Throwable $e) {

            $errorBD =
                'No fue posible registrar las asignaciones. No se guardó ningún cambio.';
        }

        if ($errorBD !== null) {

            $this->fallar($errorBD);

            return;
        }

        $this->redirect(
            '/asignaciones-ficha'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ERROR Y REGRESO AL LISTADO
    |--------------------------------------------------------------------------
    */

    private function fechaValida(
        string $fecha
    ): bool {

        $f = DateTimeImmutable::createFromFormat(
            'Y-m-d',
            $fecha
        );

        return $f !== false
            && $f->format('Y-m-d') === $fecha;
    }


    private function fallar(
        string $mensaje
    ): void {

        $_SESSION['error'] = $mensaje;

        $this->redirect(
            '/asignaciones-ficha'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FINALIZAR (QUITAR)
    |--------------------------------------------------------------------------
    |
    | Finaliza la asignación indicada. Si la fecha de fin sería anterior al
    | inicio de la asignación, se usa la fecha de inicio. Así, el líder o
    | coordinador queda libre y se puede asignar uno nuevo.
    |
    */

    public function finalizar(): void
    {
        Auth::requirePermission(
            'asignaciones_ficha.editar'
        );

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/asignaciones-ficha');
        }

        $id = (int) (
            $_POST['id'] ?? 0
        );

        $fechaFin = trim(
            (string) (
                $_POST['fecha_fin'] ?? date('Y-m-d')
            )
        );

        if ($id <= 0) {

            $this->fallar(
                'La asignación indicada no es válida.'
            );

            return;
        }

        if (!$this->fechaValida($fechaFin)) {
            $fechaFin = date('Y-m-d');
        }

        $errorBD = null;

        try {

            $asignacion = $this->model->obtener($id);

            if ($asignacion === null) {

                $errorBD = 'La asignación indicada no existe.';

            } else {

                if (
                    $fechaFin < (string) $asignacion['fecha_inicio']
                ) {
                    $fechaFin = (string) $asignacion['fecha_inicio'];
                }

                $actualizado = $this->model->finalizar(
                    $id,
                    $fechaFin
                );

                if ($actualizado) {

                    $roles = [
                        'LIDER_FICHA' => 'líder de ficha',
                        'COORDINADOR' => 'coordinador académico',
                    ];

                    $rol = $roles[
                        (string) $asignacion['tipo_asignacion']
                    ] ?? null;

                    $_SESSION['success'] = $rol !== null
                        ? 'Se quitó a '
                            . ($asignacion['nombre_profesional'] ?: 'el profesional')
                            . ' como ' . $rol
                            . ' de la ficha '
                            . $asignacion['numero_ficha']
                            . '. Ya puede asignar uno nuevo.'
                        : 'La asignación fue finalizada correctamente.';

                } else {

                    $errorBD =
                        'La asignación no pudo ser finalizada. Puede que ya no esté activa.';
                }
            }

        } catch (Throwable $e) {

            $errorBD =
                'No fue posible finalizar la asignación.';
        }

        if ($errorBD !== null) {

            $this->fallar($errorBD);

            return;
        }

        $this->redirect(
            '/asignaciones-ficha'
        );
    }
}