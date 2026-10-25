<?php

declare(strict_types=1);

class FichaController
{
    private FichaModel $model;

    public function __construct()
    {
        $this->model = new FichaModel();
    }

    public function index(): void
    {
        Auth::requirePermission('fichas.ver');

        /*
        |--------------------------------------------------------------------------
        | FILTRO DE FICHAS PARA LÍDER Y COORDINADOR
        |--------------------------------------------------------------------------
        */

        $usuarioIdFiltro = null;

        if (
            Auth::hasRole('Lider de Ficha')
            || Auth::hasRole('Coordinador')
        ) {
            $usuarioIdFiltro = Auth::id();
        }

        $buscar = trim(
            (string) ($_GET['buscar'] ?? '')
        );

        $pagina = max(
            1,
            (int) ($_GET['pagina'] ?? 1)
        );

        $limite = 20;

        $total = $this->model->contar(
            $buscar,
            $usuarioIdFiltro
        );

        $totalPaginas = max(
            1,
            (int) ceil($total / $limite)
        );

        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }

        $offset = (
            $pagina - 1
        ) * $limite;

        $fichas = $this->model->listar(
            $buscar,
            $limite,
            $offset,
            $usuarioIdFiltro
        );

        /*
        |--------------------------------------------------------------------------
        | CATÁLOGOS PARA CREACIÓN MANUAL
        |--------------------------------------------------------------------------
        */

        $programas = [];

        $jornadas = [];

        $esAdministrador = Auth::hasRole(
            'Administrador'
        );

        if ($esAdministrador) {
            $programas =
                $this->model->obtenerProgramasActivos();

            $jornadas =
                $this->model->obtenerJornadasActivas();
        }

        /*
        |--------------------------------------------------------------------------
        | MENSAJES
        |--------------------------------------------------------------------------
        */

        $mensajeExito = flash('success');

        $mensajeError = flash('error');

        $titulo = 'Fichas';

        require_once __DIR__ . '/../views/fichas/index.php';
    }

    public function guardar(): void
    {
        Auth::requireLogin();

        /*
        |--------------------------------------------------------------------------
        | SOLO ADMINISTRADOR
        |--------------------------------------------------------------------------
        */

        if (!Auth::hasRole('Administrador')) {
            http_response_code(403);

            die(
                'No tiene permisos para crear fichas.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SOLO POST
        |--------------------------------------------------------------------------
        */

        if (
            strtoupper(
                $_SERVER['REQUEST_METHOD'] ?? 'GET'
            ) !== 'POST'
        ) {
            redirect('/fichas');
        }

        exigirCsrf();

        /*
        |--------------------------------------------------------------------------
        | DATOS DEL FORMULARIO
        |--------------------------------------------------------------------------
        */

        $numeroFicha = trim(
            (string) (
                $_POST['numero_ficha'] ?? ''
            )
        );

        $idPrograma = (int) (
            $_POST['id_programa'] ?? 0
        );

        $versionPrograma = trim(
            (string) (
                $_POST['version_programa'] ?? ''
            )
        );

        $modalidad = trim(
            (string) (
                $_POST['modalidad'] ?? ''
            )
        );

        $idJornada = (int) (
            $_POST['id_jornada'] ?? 0
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

        $estado = trim(
            (string) (
                $_POST['estado'] ?? 'activa'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | DATOS FIJOS DEL CENTRO
        |--------------------------------------------------------------------------
        */

        $regional =
            '41 - REGIONAL HUILA';

        $centro =
            '9528 - CENTRO DE GESTION Y DESARROLLO SOSTENIBLE SURCOLOMBIANO';

        /*
        |--------------------------------------------------------------------------
        | VALIDACIONES BÁSICAS
        |--------------------------------------------------------------------------
        */

        if ($numeroFicha === '') {
            flash(
                'error',
                'Debe ingresar el número de ficha.'
            );

            redirect('/fichas');
        }

        if (mb_strlen($numeroFicha) > 30) {
            flash(
                'error',
                'El número de ficha no puede superar los 30 caracteres.'
            );

            redirect('/fichas');
        }

        if ($idPrograma <= 0) {
            flash(
                'error',
                'Debe seleccionar un programa.'
            );

            redirect('/fichas');
        }

        if (mb_strlen($versionPrograma) > 20) {
            flash(
                'error',
                'La versión del programa no puede superar los 20 caracteres.'
            );

            redirect('/fichas');
        }

        if ($modalidad === '') {
            flash(
                'error',
                'Debe indicar la modalidad.'
            );

            redirect('/fichas');
        }

        if (mb_strlen($modalidad) > 50) {
            flash(
                'error',
                'La modalidad no puede superar los 50 caracteres.'
            );

            redirect('/fichas');
        }

        if ($idJornada <= 0) {
            flash(
                'error',
                'Debe seleccionar una jornada.'
            );

            redirect('/fichas');
        }

        if ($fechaInicio === '') {
            flash(
                'error',
                'Debe indicar la fecha de inicio.'
            );

            redirect('/fichas');
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDAR FECHAS
        |--------------------------------------------------------------------------
        */

        $inicio = \DateTime::createFromFormat(
            'Y-m-d',
            $fechaInicio
        );

        $inicioValido =
            $inicio !== false
            && $inicio->format('Y-m-d') === $fechaInicio;

        if (!$inicioValido) {
            flash(
                'error',
                'La fecha de inicio no tiene un formato válido.'
            );

            redirect('/fichas');
        }

        if ($fechaFin !== '') {
            $fin = \DateTime::createFromFormat(
                'Y-m-d',
                $fechaFin
            );

            $finValido =
                $fin !== false
                && $fin->format('Y-m-d') === $fechaFin;

            if (!$finValido) {
                flash(
                    'error',
                    'La fecha de finalización no tiene un formato válido.'
                );

                redirect('/fichas');
            }

            if ($fechaFin < $fechaInicio) {
                flash(
                    'error',
                    'La fecha de finalización no puede ser anterior a la fecha de inicio.'
                );

                redirect('/fichas');
            }
        } else {
            $fechaFin = null;
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDAR ESTADO
        |--------------------------------------------------------------------------
        */

        $estadosPermitidos = [
            'activa',
            'finalizada',
            'cancelada',
        ];

        if (!in_array(
            $estado,
            $estadosPermitidos,
            true
        )) {
            flash(
                'error',
                'El estado seleccionado no es válido.'
            );

            redirect('/fichas');
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDAR PROGRAMA ACTIVO
        |--------------------------------------------------------------------------
        */

        $programas =
            $this->model->obtenerProgramasActivos();

        $programaValido = false;

        foreach ($programas as $programa) {
            if (
                (int) $programa['id']
                === $idPrograma
            ) {
                $programaValido = true;
                break;
            }
        }

        if (!$programaValido) {
            flash(
                'error',
                'El programa seleccionado no está disponible.'
            );

            redirect('/fichas');
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDAR JORNADA ACTIVA
        |--------------------------------------------------------------------------
        */

        $jornadas =
            $this->model->obtenerJornadasActivas();

        $jornadaValida = false;

        foreach ($jornadas as $jornada) {
            if (
                (int) $jornada['id']
                === $idJornada
            ) {
                $jornadaValida = true;
                break;
            }
        }

        if (!$jornadaValida) {
            flash(
                'error',
                'La jornada seleccionada no está disponible.'
            );

            redirect('/fichas');
        }

        /*
        |--------------------------------------------------------------------------
        | EVITAR DUPLICADOS
        |--------------------------------------------------------------------------
        */

        if (
            $this->model->existeNumeroFicha(
                $numeroFicha
            )
        ) {
            flash(
                'error',
                'La ficha ' . $numeroFicha . ' ya existe en el sistema.'
            );

            redirect('/fichas');
        }

        /*
        |--------------------------------------------------------------------------
        | CREAR FICHA
        |--------------------------------------------------------------------------
        */

        try {
            $idFicha = $this->model->crear([
                'numero_ficha' => $numeroFicha,
                'id_programa' => $idPrograma,
                'version_programa' =>
                    $versionPrograma !== ''
                        ? $versionPrograma
                        : null,
                'modalidad' =>
                    $modalidad !== ''
                        ? $modalidad
                        : null,
                'regional' => $regional,
                'centro' => $centro,
                'id_jornada' => $idJornada,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'estado' => $estado,
            ]);

            flash(
                'success',
                'La ficha ' . $numeroFicha .
                ' fue creada correctamente.'
            );

            redirect('/fichas');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                flash(
                    'error',
                    'La ficha ' . $numeroFicha .
                    ' ya existe en el sistema.'
                );

                redirect('/fichas');
            }

            if (
                defined('APP_DEBUG')
                && APP_DEBUG === true
            ) {
                throw $e;
            }

            flash(
                'error',
                'No fue posible crear la ficha.'
            );

            redirect('/fichas');
        }
    }

    public function detalle(): void
    {
        Auth::requirePermission('fichas.ver');

        /*
        |--------------------------------------------------------------------------
        | FILTRO DE FICHAS PARA LÍDER Y COORDINADOR
        |--------------------------------------------------------------------------
        */

        $usuarioIdFiltro = null;

        if (
            Auth::hasRole('Lider de Ficha')
            || Auth::hasRole('Coordinador')
        ) {
            $usuarioIdFiltro = Auth::id();
        }

        $id = (int) (
            $_GET['id'] ?? 0
        );

        if ($id <= 0) {
            redirect('/fichas');
        }

        /*
        |--------------------------------------------------------------------------
        | OBTENER FICHA
        |--------------------------------------------------------------------------
        */

        $ficha = $this->model->obtener(
            $id,
            $usuarioIdFiltro
        );

        if (!$ficha) {
            redirect('/fichas');
        }

        /*
        |--------------------------------------------------------------------------
        | INFORMACIÓN GENERAL DE LA FICHA
        |--------------------------------------------------------------------------
        */

        $aprendices = $this->model->obtenerAprendices(
            $id
        );

        $resumenAprendices =
            $this->model->obtenerResumenAprendices(
                $id
            );

        /*
        |--------------------------------------------------------------------------
        | AVANCE ACADÉMICO INDIVIDUAL
        |--------------------------------------------------------------------------
        */

        $avanceAprendices =
            $this->model->obtenerAvanceAprendices(
                $id
            );

        /*
        |--------------------------------------------------------------------------
        | RESUMEN ACADÉMICO
        |--------------------------------------------------------------------------
        */

        $resumenAcademico =
            $this->model->obtenerResumenAcademico(
                $id
            );

        /*
        |--------------------------------------------------------------------------
        | RAP LECTIVOS PARA LAS BARRAS COMPARATIVAS
        |--------------------------------------------------------------------------
        */

        $resumenRapsLectivos =
            $this->model->obtenerResumenRapsLectivos(
                $id
            );

        /*
        |--------------------------------------------------------------------------
        | PROFESIONALES
        |--------------------------------------------------------------------------
        */

        $profesionalesAsignados =
            $this->model->obtenerProfesionalesAsignados(
                $id
            );

        $liderFicha =
            $this->model->obtenerLiderFicha(
                $id
            );

        $instructoresEvaluadores =
            $this->model->obtenerInstructoresEvaluadores(
                $id
            );

        /*
        |--------------------------------------------------------------------------
        | ENLACE DEL CUESTIONARIO DE BIENESTAR
        |--------------------------------------------------------------------------
        |
        | El token se recibe después de generar u obtener el enlace.
        | Se verifica que pertenezca a la ficha que se está consultando.
        |
        */

        $enlaceBienestar = null;

        $tokenBienestar = trim(
            (string) (
                $_GET['token_bienestar'] ?? ''
            )
        );

        if ($tokenBienestar !== '') {
            $aprendizModel = new AprendizModel();

            $enlaceEncontrado =
                $aprendizModel->obtenerEnlaceFichaPorToken(
                    $tokenBienestar
                );

            if (
                $enlaceEncontrado !== null
                && (int) (
                    $enlaceEncontrado['id_ficha'] ?? 0
                ) === $id
            ) {
                $enlaceBienestar = url(
                    '/bienestar/cuestionario?token='
                    . urlencode($tokenBienestar)
                );
            }
        }

        $titulo = 'Detalle de la ficha';

        require_once __DIR__ . '/../views/fichas/detalle.php';
    }

    public function generarEnlaceBienestar(): void
    {
        Auth::requirePermission('fichas.ver');

        /*
        |--------------------------------------------------------------------------
        | SOLO POST
        |--------------------------------------------------------------------------
        */

        if (
            strtoupper(
                $_SERVER['REQUEST_METHOD'] ?? 'GET'
            ) !== 'POST'
        ) {
            redirect('/fichas');
        }

        exigirCsrf();

        $idFicha = (int) (
            $_POST['id_ficha'] ?? 0
        );

        if ($idFicha <= 0) {
            flash(
                'error',
                'La ficha seleccionada no es válida.'
            );

            redirect('/fichas');
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDAR ACCESO A LA FICHA
        |--------------------------------------------------------------------------
        */

        $usuarioIdFiltro = null;

        if (
            Auth::hasRole('Lider de Ficha')
            || Auth::hasRole('Coordinador')
        ) {
            $usuarioIdFiltro = Auth::id();
        }

        $ficha = $this->model->obtener(
            $idFicha,
            $usuarioIdFiltro
        );

        if (!$ficha) {
            http_response_code(403);

            die(
                'No tiene autorización para generar el enlace de esta ficha.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | OBTENER O CREAR ENLACE ÚNICO
        |--------------------------------------------------------------------------
        */

        try {
            $aprendizModel = new AprendizModel();

            $enlace = $aprendizModel->obtenerOcrearEnlaceFicha(
                $idFicha
            );

            $token = trim(
                (string) (
                    $enlace['token'] ?? ''
                )
            );

            if ($token === '') {
                throw new RuntimeException(
                    'El sistema no devolvió un token válido para la ficha.'
                );
            }

            /*
             * Se envía el token a detalle(), donde se construye
             * y se presenta la URL pública del cuestionario.
             */
            redirect(
                '/fichas/detalle?id='
                . $idFicha
                . '&token_bienestar='
                . urlencode($token)
            );
        } catch (Throwable $e) {
            if (
                defined('APP_DEBUG')
                && APP_DEBUG === true
            ) {
                throw $e;
            }

            flash(
                'error',
                'No fue posible generar u obtener el enlace del cuestionario de Bienestar.'
            );

            redirect(
                '/fichas/detalle?id=' . $idFicha
            );
        }
    }
}