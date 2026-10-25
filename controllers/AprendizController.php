<?php

declare(strict_types=1);

class AprendizController extends Controller
{
    private AprendizModel $model;

    public function __construct()
    {
        $this->model = new AprendizModel();
    }

    /*
    |--------------------------------------------------------------------------
    | LISTADO
    |--------------------------------------------------------------------------
    */

    public function index(): void
    {
        Auth::requirePermission(
            'aprendices.ver'
        );

        $tituloPagina = 'Aprendices';

        $buscar = trim(
            (string) $this->get('buscar', '')
        );

        $pagina = max(
            1,
            (int) $this->get('pagina', 1)
        );

        $porPagina = 20;

        /*
        |--------------------------------------------------------------------------
        | SEGURIDAD / FILTRO POR ASIGNACIÓN DE FICHA
        |--------------------------------------------------------------------------
        |
        | Administrador y los demás roles sin filtro conservan
        | el listado general.
        |
        | Coordinador: únicamente fichas con asignación COORDINADOR.
        | Líder de Ficha: únicamente fichas con asignación LIDER_FICHA.
        |
        | Si el usuario no tiene profesional asociado, se envía el ID 0
        | para que el modelo no muestre aprendices por falta de filtro.
        */

        $roles = Auth::roles();

        $esAdministrador = $this->tieneRol(
            $roles,
            'Administrador'
        );

        $esCoordinador = !$esAdministrador
            && $this->tieneRol(
                $roles,
                'Coordinador'
            );

        $esLider = !$esAdministrador
            && !$esCoordinador
            && (
                $this->tieneRol(
                    $roles,
                    'Lider de Ficha'
                ) || $this->tieneRol(
                    $roles,
                    'Líder de Ficha'
                )
            );

        $idProfesionalFiltro = null;
        $tipoAsignacionFiltro = null;

        if ($esCoordinador || $esLider) {
            $tipoAsignacionFiltro = $esCoordinador
                ? 'COORDINADOR'
                : 'LIDER_FICHA';

            $usuario = Auth::user() ?? [];
            $idUsuario = (int) ($usuario['id'] ?? 0);

            $idProfesional = $idUsuario > 0
                ? $this->model->obtenerIdProfesionalPorUsuario($idUsuario)
                : null;

            $idProfesionalFiltro = (
                $idProfesional !== null
                && $idProfesional > 0
            ) ? $idProfesional : 0;
        }

        /*
        |--------------------------------------------------------------------------
        | TOTAL
        |--------------------------------------------------------------------------
        */

        $total = $this->model->contar(
            $buscar,
            $idProfesionalFiltro,
            $tipoAsignacionFiltro
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

        /*
        |--------------------------------------------------------------------------
        | LISTADO
        |--------------------------------------------------------------------------
        */

        $aprendices = $this->model->listar(
            $buscar,
            $porPagina,
            $offset,
            $idProfesionalFiltro,
            $tipoAsignacionFiltro
        );

        $this->view(
            'aprendices/index',
            [
                'tituloPagina' => $tituloPagina,
                'titulo'       => 'Aprendices',
                'buscar'       => $buscar,
                'pagina'       => $pagina,
                'porPagina'    => $porPagina,
                'total'        => $total,
                'totalPaginas' => $totalPaginas,
                'aprendices'   => $aprendices,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DETALLE
    |--------------------------------------------------------------------------
    */

    public function detalle(): void
    {
        Auth::requirePermission(
            'aprendices.ver'
        );

        $id = (int) $this->get(
            'id',
            0
        );

        if ($id <= 0) {
            $this->redirect('/aprendices');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | SEGURIDAD
        |--------------------------------------------------------------------------
        |
        | El listado ya restringe al Líder de Ficha.
        |
        | Aquí hacemos una segunda validación en servidor para evitar
        | que pueda cambiar manualmente el id en la URL y consultar
        | información de un aprendiz que no pertenece a sus fichas.
        |
        */

        if (
            !$this->usuarioPuedeVerDetalle(
                $id
            )
        ) {
            $this->redirect('/aprendices');
            return;
        }

        $aprendiz = $this->model->obtener(
            $id
        );

        if ($aprendiz === null) {
            $this->redirect('/aprendices');
            return;
        }

        $matriculas = $this->model->obtenerMatriculas(
            $id
        );

        $competenciasRAP = $this->model->obtenerCompetenciasRAP(
            $id
        );

        $this->view(
            'aprendices/detalle',
            [
                'tituloPagina'    => 'Detalle del aprendiz',
                'titulo'          => 'Detalle del aprendiz',
                'aprendiz'        => $aprendiz,
                'matriculas'      => $matriculas,
                'competenciasRAP' => $competenciasRAP,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CARACTERIZACIÓN
    |--------------------------------------------------------------------------
    */

    public function caracterizacion(): void
    {
        Auth::requirePermission(
            'aprendices.ver'
        );

        $idAprendiz = (int) $this->get(
            'id',
            0
        );

        if ($idAprendiz <= 0) {
            $this->redirect('/aprendices');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Verificar que exista el aprendiz
        |--------------------------------------------------------------------------
        */

        $caracterizacion = $this->model->obtenerCaracterizacion(
            $idAprendiz
        );

        if ($caracterizacion === null) {
            $this->redirect('/aprendices');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Seguridad
        |--------------------------------------------------------------------------
        */

        if (
            !$this->usuarioPuedeCaracterizar(
                $idAprendiz
            )
        ) {
            $this->redirect(
                '/aprendices/detalle?id=' . $idAprendiz
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Catálogos
        |--------------------------------------------------------------------------
        */

        $eps = $this->model->listarEpsActivas();

        $tiposPoblacion =
            $this->model->listarTiposPoblacionActivos();

        /*
        |--------------------------------------------------------------------------
        | Historial
        |--------------------------------------------------------------------------
        */

        $historial =
            $this->model->obtenerHistorialPoblacion(
                $idAprendiz
            );

        /*
        |--------------------------------------------------------------------------
        | IMPORTANTE
        |
        | La vista caracterizacion.php trabaja con:
        |
        | $aprendiz
        | $eps
        | $tiposPoblacion
        | $historial
        |
        | Antes el controlador enviaba:
        | $caracterizacion
        | $historialPoblacion
        |
        | Esa era la causa principal de que la vista apareciera vacía.
        |--------------------------------------------------------------------------
        */

        $this->view(
            'aprendices/caracterizacion',
            [
                'tituloPagina' =>
                    'Caracterización del aprendiz',

                'titulo' =>
                    'Caracterización del aprendiz',

                'aprendiz' =>
                    $caracterizacion,

                'eps' =>
                    $eps,

                'tiposPoblacion' =>
                    $tiposPoblacion,

                'historial' =>
                    $historial,

                'idAprendiz' =>
                    $idAprendiz,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR CARACTERIZACIÓN
    |--------------------------------------------------------------------------
    */

    public function actualizarCaracterizacion(): void
    {
        Auth::requirePermission(
            'aprendices.ver'
        );

        /*
        | Solo POST.
        */

        if (
            $_SERVER['REQUEST_METHOD'] !== 'POST'
        ) {
            $this->redirect('/aprendices');
            return;
        }

        /*
        | CSRF.
        |
        | exigirCsrf() es una función global definida
        | en helpers/helpers.php, no un método de Controller.
        */

        exigirCsrf();

        /*
        |--------------------------------------------------------------------------
        | ID DEL APRENDIZ
        |--------------------------------------------------------------------------
        */

        $idAprendiz = (int) (
            $_POST['id_aprendiz']
            ?? 0
        );

        if ($idAprendiz <= 0) {
            $this->redirect('/aprendices');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Seguridad de servidor
        |--------------------------------------------------------------------------
        */

        if (
            !$this->usuarioPuedeCaracterizar(
                $idAprendiz
            )
        ) {
            $this->redirect(
                '/aprendices/detalle?id=' . $idAprendiz
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Verificar aprendiz
        |--------------------------------------------------------------------------
        */

        $caracterizacion =
            $this->model->obtenerCaracterizacion(
                $idAprendiz
            );

        if ($caracterizacion === null) {
            $this->redirect('/aprendices');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | ID PERSONA
        |
        | Se conserva porque el modelo actual lo utiliza.
        |--------------------------------------------------------------------------
        */

        $idPersona = (int) (
            $_POST['id_persona']
            ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | DATOS
        |--------------------------------------------------------------------------
        */

        /*
        | Fecha de nacimiento.
        */

        $fechaNacimiento = trim(
            (string) (
                $_POST['fecha_nacimiento']
                ?? ''
            )
        );

        /*
        | Validamos únicamente cuando viene diligenciada.
        | Si viene vacía, el modelo podrá guardarla como NULL.
        */

        if ($fechaNacimiento !== '') {

            $fechaNacimientoValida =
                DateTime::createFromFormat(
                    'Y-m-d',
                    $fechaNacimiento
                );

            if (
                !$fechaNacimientoValida
                || $fechaNacimientoValida->format('Y-m-d')
                    !== $fechaNacimiento
            ) {
                $this->redirect(
                    '/aprendices/caracterizacion?id='
                    . $idAprendiz
                    . '&error=fecha_nacimiento'
                );

                return;
            }
        }

        /*
        | Sexo.
        */

        $sexo = trim(
            (string) (
                $_POST['sexo']
                ?? ''
            )
        );

        /*
        | EPS.
        */

        $idEps = trim(
            (string) (
                $_POST['id_eps']
                ?? ''
            )
        );

        /*
        | Tipo de población.
        */

        $idTipoPoblacion = (int) (
            $_POST['id_tipo_poblacion']
            ?? 0
        );

        /*
        | Fecha de población.
        */

        $fechaPoblacion = trim(
            (string) (
                $_POST['fecha_inicio_poblacion']
                ?? ''
            )
        );

        if ($fechaPoblacion === '') {
            $fechaPoblacion = date('Y-m-d');
        }

        /*
        |--------------------------------------------------------------------------
        | Validar fecha
        |--------------------------------------------------------------------------
        */

        $fechaValida = DateTime::createFromFormat(
            'Y-m-d',
            $fechaPoblacion
        );

        if (
            !$fechaValida
            || $fechaValida->format('Y-m-d')
                !== $fechaPoblacion
        ) {
            $fechaPoblacion = date('Y-m-d');
        }

        /*
        |--------------------------------------------------------------------------
        | Datos permitidos para actualizar
        |--------------------------------------------------------------------------
        */

        $datos = [

            'id_persona' =>
                $idPersona,

            /*
            | Datos personales editables.
            */

            'fecha_nacimiento' =>
                $fechaNacimiento,

            'sexo' =>
                $sexo,

            'id_eps' =>
                $idEps,

            'telefono' =>
                trim(
                    (string) (
                        $_POST['telefono']
                        ?? ''
                    )
                ),

            'correo' =>
                trim(
                    (string) (
                        $_POST['correo']
                        ?? ''
                    )
                ),

            'direccion' =>
                trim(
                    (string) (
                        $_POST['direccion']
                        ?? ''
                    )
                ),

            'municipio' =>
                trim(
                    (string) (
                        $_POST['municipio']
                        ?? ''
                    )
                ),

            'departamento' =>
                trim(
                    (string) (
                        $_POST['departamento']
                        ?? ''
                    )
                ),

            'contacto_emergencia_nombre' =>
                trim(
                    (string) (
                        $_POST[
                            'contacto_emergencia_nombre'
                        ]
                        ?? ''
                    )
                ),

            'contacto_emergencia_telefono' =>
                trim(
                    (string) (
                        $_POST[
                            'contacto_emergencia_telefono'
                        ]
                        ?? ''
                    )
                ),

            'contacto_emergencia_parentesco' =>
                trim(
                    (string) (
                        $_POST[
                            'contacto_emergencia_parentesco'
                        ]
                        ?? ''
                    )
                ),

            'id_tipo_poblacion' =>
                $idTipoPoblacion,

            'fecha_inicio_poblacion' =>
                $fechaPoblacion,

            'observacion_poblacion' =>
                trim(
                    (string) (
                        $_POST[
                            'observacion_poblacion'
                        ]
                        ?? ''
                    )
                ),
        ];

        /*
        |--------------------------------------------------------------------------
        | Validaciones
        |--------------------------------------------------------------------------
        */

        if ($idPersona <= 0) {
            $this->redirect(
                '/aprendices/caracterizacion?id='
                . $idAprendiz
                . '&error=persona'
            );

            return;
        }

        if ($idTipoPoblacion <= 0) {
            $this->redirect(
                '/aprendices/caracterizacion?id='
                . $idAprendiz
                . '&error=poblacion'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Actualizar
        |--------------------------------------------------------------------------
        */

        try {

            $resultado =
                $this->model->actualizarCaracterizacion(
                    $idAprendiz,
                    $datos
                );

            if (!$resultado) {
                $this->redirect(
                    '/aprendices/caracterizacion?id='
                    . $idAprendiz
                    . '&error=actualizacion'
                );

                return;
            }

        } catch (Throwable $e) {

            if (APP_DEBUG) {
                throw $e;
            }

            $this->redirect(
                '/aprendices/caracterizacion?id='
                . $idAprendiz
                . '&error=actualizacion'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Confirmación
        |--------------------------------------------------------------------------
        */

        $this->redirect(
            '/aprendices/caracterizacion?id='
            . $idAprendiz
            . '&actualizado=1'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | AUTORIZACIÓN / VER DETALLE
    |--------------------------------------------------------------------------
    */

    /**
     * Determina si el usuario actual puede consultar
     * el detalle de un aprendiz.
     *
     * Administrador, Coordinador y demás usuarios que ya
     * poseen el permiso continúan con el comportamiento
     * anterior.
     *
     * Para Líder de Ficha se valida que el aprendiz
     * pertenezca a una ficha donde el profesional tenga
     * actualmente una asignación activa como LIDER_FICHA.
     */
    private function usuarioPuedeVerDetalle(
        int $idAprendiz
    ): bool {
        $usuario = Auth::user() ?? [];

        $roles = Auth::roles();

        if ($this->tieneRol($roles, 'Administrador')) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Coordinador
        |--------------------------------------------------------------------------
        */

        if ($this->tieneRol($roles, 'Coordinador')) {
            $idUsuario = (int) ($usuario['id'] ?? 0);

            if ($idUsuario <= 0) {
                return false;
            }

            $idProfesional = $this->model->obtenerIdProfesionalPorUsuario(
                $idUsuario
            );

            if ($idProfesional === null || $idProfesional <= 0) {
                return false;
            }

            return $this->model->puedeVerComoCoordinador(
                $idAprendiz,
                $idProfesional
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Líder de Ficha
        |--------------------------------------------------------------------------
        */

        if (
            $this->tieneRol(
                $roles,
                'Lider de Ficha'
            )
            || $this->tieneRol(
                $roles,
                'Líder de Ficha'
            )
        ) {

            $idUsuario = (int) (
                $usuario['id']
                ?? 0
            );

            if ($idUsuario <= 0) {
                return false;
            }

            $idProfesional =
                $this->model
                    ->obtenerIdProfesionalPorUsuario(
                        $idUsuario
                    );

            if (
                $idProfesional === null
                || $idProfesional <= 0
            ) {
                return false;
            }

            return $this->model
                ->puedeVerComoLiderFicha(
                    $idAprendiz,
                    $idProfesional
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Los demás usuarios mantienen el comportamiento actual.
        |--------------------------------------------------------------------------
        */

        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | AUTORIZACIÓN / CARACTERIZACIÓN
    |--------------------------------------------------------------------------
    */

    private function usuarioPuedeCaracterizar(
        int $idAprendiz
    ): bool {
        $usuario = Auth::user() ?? [];

        $roles = Auth::roles();

        /*
        | Administrador.
        */

        if (
            $this->tieneRol(
                $roles,
                'Administrador'
            )
        ) {
            return true;
        }

        /*
        | Coordinador: solo puede caracterizar aprendices
        | matriculados en fichas que tiene asignadas.
        */

        if ($this->tieneRol($roles, 'Coordinador')) {
            $idUsuario = (int) ($usuario['id'] ?? 0);

            if ($idUsuario <= 0) {
                return false;
            }

            $idProfesional = $this->model->obtenerIdProfesionalPorUsuario(
                $idUsuario
            );

            if ($idProfesional === null || $idProfesional <= 0) {
                return false;
            }

            return $this->model->puedeVerComoCoordinador(
                $idAprendiz,
                $idProfesional
            );
        }

        /*
        | Líder de Ficha.
        */

        if (
            $this->tieneRol(
                $roles,
                'Lider de Ficha'
            )
            || $this->tieneRol(
                $roles,
                'Líder de Ficha'
            )
        ) {

            $idUsuario = (int) (
                $usuario['id']
                ?? 0
            );

            if ($idUsuario <= 0) {
                return false;
            }

            $idProfesional =
                $this->model
                    ->obtenerIdProfesionalPorUsuario(
                        $idUsuario
                    );

            if (
                $idProfesional === null
                || $idProfesional <= 0
            ) {
                return false;
            }

            return $this->model
                ->puedeCaracterizarComoLiderFicha(
                    $idAprendiz,
                    $idProfesional
                );
        }

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | OBTENER PROFESIONAL DEL LÍDER DE FICHA
    |--------------------------------------------------------------------------
    */

    /**
     * Si el usuario actual es Líder de Ficha,
     * devuelve su id_profesional.
     *
     * Para los demás roles devuelve null.
     *
     * Esto permite que el listado general de aprendices
     * siga funcionando normalmente para Administrador,
     * Coordinador y demás usuarios.
     */
    private function obtenerIdProfesionalComoLider(): ?int
    {
        $usuario = Auth::user() ?? [];

        $roles = Auth::roles();

        $esLider =
            $this->tieneRol(
                $roles,
                'Lider de Ficha'
            )
            || $this->tieneRol(
                $roles,
                'Líder de Ficha'
            );

        if (!$esLider) {
            return null;
        }

        $idUsuario = (int) (
            $usuario['id']
            ?? 0
        );

        if ($idUsuario <= 0) {
            return null;
        }

        $idProfesional =
            $this->model
                ->obtenerIdProfesionalPorUsuario(
                    $idUsuario
                );

        if (
            $idProfesional === null
            || $idProfesional <= 0
        ) {
            return null;
        }

        return $idProfesional;
    }


    /*
    |--------------------------------------------------------------------------
    | COMPROBAR ROL
    |--------------------------------------------------------------------------
    */

    private function tieneRol(
        array $roles,
        string $rolBuscado
    ): bool {
        $rolBuscado =
            $this->normalizarRol(
                $rolBuscado
            );

        foreach ($roles as $rol) {

            if (is_array($rol)) {

                $nombreRol =
                    $rol['nombre']
                    ?? '';

            } else {

                $nombreRol =
                    (string) $rol;
            }

            if (
                $this->normalizarRol(
                    $nombreRol
                ) === $rolBuscado
            ) {
                return true;
            }
        }

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZAR ROL
    |--------------------------------------------------------------------------
    */

    private function normalizarRol(
        string $rol
    ): string {
        $rol = trim(
            mb_strtolower($rol)
        );

        $buscar = [
            'á',
            'é',
            'í',
            'ó',
            'ú',
            'ü',
        ];

        $reemplazar = [
            'a',
            'e',
            'i',
            'o',
            'u',
            'u',
        ];

        return str_replace(
            $buscar,
            $reemplazar,
            $rol
        );
    }
}

