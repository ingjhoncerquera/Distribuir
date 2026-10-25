<?php

declare(strict_types=1);

class ProfesionalController extends Controller
{
    private ProfesionalModel $model;

    public function __construct()
    {
        $this->model = new ProfesionalModel();
    }

    public function index(): void
    {
        Auth::requireLogin();

        Auth::requirePermission(
            'profesionales.ver'
        );

        $tituloPagina = 'Profesionales';

        $buscar = trim(
            (string) $this->get(
                'buscar',
                ''
            )
        );

        $pagina = max(
            1,
            (int) $this->get(
                'pagina',
                1
            )
        );

        $porPagina = 20;

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

        $profesionales = $this->model->listar(
            $buscar,
            $porPagina,
            $offset
        );

        $this->view(
            'profesionales/index',
            [
                'tituloPagina'  => $tituloPagina,
                'titulo'        => 'Profesionales',
                'buscar'        => $buscar,
                'pagina'        => $pagina,
                'porPagina'     => $porPagina,
                'total'         => $total,
                'totalPaginas'  => $totalPaginas,
                'profesionales' => $profesionales,
            ]
        );
    }

    public function detalle(): void
    {
        Auth::requireLogin();

        Auth::requirePermission(
            'profesionales.ver'
        );

        $id = (int) $this->get(
            'id',
            0
        );

        if ($id <= 0) {
            $this->redirect(
                '/profesionales'
            );

            return;
        }

        $profesional = $this->model->obtener(
            $id
        );

        if ($profesional === null) {
            $this->redirect(
                '/profesionales'
            );

            return;
        }

        $roles = $this->model->obtenerRoles(
            $id
        );

        /*
        |--------------------------------------------------------------------------
        | Roles activos disponibles para administración
        |--------------------------------------------------------------------------
        |
        | Se cargan únicamente si el usuario actual tiene permiso para
        | consultar roles. Esto permite que la vista pueda construir
        | el modal de asignación sin cambiar el funcionamiento actual.
        |
        */

        $rolesDisponibles = [];

        if (Auth::can('roles.ver')) {
            $rolesDisponibles = $this->model->listarRolesActivos();
        }

        $permisos = $this->model->obtenerPermisos(
            $id
        );

        $usuario = $this->model->obtenerUsuario(
            $id
        );

        $this->view(
            'profesionales/detalle',
            [
                'tituloPagina'      => 'Detalle del profesional',
                'titulo'            => 'Detalle del profesional',
                'profesional'       => $profesional,
                'usuario'           => $usuario,
                'roles'             => $roles,
                'rolesDisponibles'  => $rolesDisponibles,
                'permisos'          => $permisos,
            ]
        );
    }

    public function crear(): void
    {
        Auth::requireLogin();

        Auth::requirePermission(
            'profesionales.crear'
        );

        $tiposProfesional = [
            'Instructor',
            'Psicologo',
            'Enfermero',
            'Nutricionista',
            'Trabajador Social',
            'Bienestar',
            'Coordinador',
			'Promotor de recreacion y deportes',
			'Instructor artistico',
            'Otro',
        ];

        $tiposDocumento = [
            'CC',
            'TI',
            'CE',
            'Pasaporte',
        ];

        $this->view(
            'profesionales/crear',
            [
                'tituloPagina'     => 'Nuevo profesional',
                'titulo'           => 'Nuevo profesional',
                'tiposProfesional' => $tiposProfesional,
                'tiposDocumento'   => $tiposDocumento,
            ]
        );
    }

    public function guardar(): void
    {
        Auth::requireLogin();

        Auth::requirePermission(
            'profesionales.crear'
        );

        if (!$this->isPost()) {
            $this->redirect(
                '/profesionales/crear'
            );

            return;
        }

        exigirCsrf();

        /*
        |--------------------------------------------------------------------------
        | Datos personales
        |--------------------------------------------------------------------------
        */

        $tipoDocumento = trim(
            (string) $this->post(
                'tipo_documento',
                ''
            )
        );

        $documento = trim(
            (string) $this->post(
                'documento',
                ''
            )
        );

        $nombres = trim(
            (string) $this->post(
                'nombres',
                ''
            )
        );

        $apellidos = trim(
            (string) $this->post(
                'apellidos',
                ''
            )
        );

        $fechaNacimiento = trim(
            (string) $this->post(
                'fecha_nacimiento',
                ''
            )
        );

        $sexo = trim(
            (string) $this->post(
                'sexo',
                ''
            )
        );

        $telefono = trim(
            (string) $this->post(
                'telefono',
                ''
            )
        );

        $correo = trim(
            (string) $this->post(
                'correo',
                ''
            )
        );

        $direccion = trim(
            (string) $this->post(
                'direccion',
                ''
            )
        );

        $municipio = trim(
            (string) $this->post(
                'municipio',
                ''
            )
        );

        $departamento = trim(
            (string) $this->post(
                'departamento',
                ''
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Datos profesionales
        |--------------------------------------------------------------------------
        */

        $tipoProfesional = trim(
            (string) $this->post(
                'tipo_profesional',
                ''
            )
        );

        $registroProfesional = trim(
            (string) $this->post(
                'registro_profesional',
                ''
            )
        );

        $especialidad = trim(
            (string) $this->post(
                'especialidad',
                ''
            )
        );

        $estado = trim(
            (string) $this->post(
                'estado',
                'activo'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Validación básica
        |--------------------------------------------------------------------------
        */

        if (
            $tipoDocumento === ''
            || $documento === ''
            || $nombres === ''
            || $apellidos === ''
            || $tipoProfesional === ''
        ) {
            flash(
                'error',
                'Complete todos los campos obligatorios.'
            );

            redirect(
                '/profesionales/crear'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Validar tipo de documento
        |--------------------------------------------------------------------------
        */

        $tiposDocumentoPermitidos = [
            'CC',
            'TI',
            'CE',
            'Pasaporte',
        ];

        if (
            !in_array(
                $tipoDocumento,
                $tiposDocumentoPermitidos,
                true
            )
        ) {
            flash(
                'error',
                'El tipo de documento seleccionado no es válido.'
            );

            redirect(
                '/profesionales/crear'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Validar tipo de profesional
        |--------------------------------------------------------------------------
        */

        $tiposProfesionalPermitidos = [
            'Instructor',
            'Psicologo',
            'Enfermero',
            'Nutricionista',
            'Trabajador Social',
            'Bienestar',
            'Coordinador',
			'Promotor de recreacion y deportes',
			'Instructor artistico',
            'Otro',
        ];

        if (
            !in_array(
                $tipoProfesional,
                $tiposProfesionalPermitidos,
                true
            )
        ) {
            flash(
                'error',
                'El tipo de profesional seleccionado no es válido.'
            );

            redirect(
                '/profesionales/crear'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Validar estado
        |--------------------------------------------------------------------------
        */

        $estadosPermitidos = [
            'activo',
            'inactivo',
        ];

        if (
            !in_array(
                $estado,
                $estadosPermitidos,
                true
            )
        ) {
            flash(
                'error',
                'El estado seleccionado no es válido.'
            );

            redirect(
                '/profesionales/crear'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Validar correo
        |--------------------------------------------------------------------------
        */

        if (
            $correo !== ''
            && !filter_var(
                $correo,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            flash(
                'error',
                'El correo electrónico ingresado no es válido.'
            );

            redirect(
                '/profesionales/crear'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Normalizar campos opcionales
        |--------------------------------------------------------------------------
        */

        $fechaNacimiento = $fechaNacimiento !== ''
            ? $fechaNacimiento
            : null;

        $sexo = $sexo !== ''
            ? $sexo
            : null;

        $telefono = $telefono !== ''
            ? $telefono
            : null;

        $correo = $correo !== ''
            ? $correo
            : null;

        $direccion = $direccion !== ''
            ? $direccion
            : null;

        $municipio = $municipio !== ''
            ? $municipio
            : null;

        $departamento = $departamento !== ''
            ? $departamento
            : null;

        $registroProfesional = $registroProfesional !== ''
            ? $registroProfesional
            : null;

        $especialidad = $especialidad !== ''
            ? $especialidad
            : null;

        /*
        |--------------------------------------------------------------------------
        | Crear persona + profesional
        |--------------------------------------------------------------------------
        */

        try {

            $idProfesional = $this->model->crear(
                [
                    'tipo_documento'   => $tipoDocumento,
                    'documento'        => $documento,
                    'nombres'          => $nombres,
                    'apellidos'        => $apellidos,
                    'fecha_nacimiento' => $fechaNacimiento,
                    'sexo'             => $sexo,
                    'telefono'         => $telefono,
                    'correo'           => $correo,
                    'direccion'        => $direccion,
                    'municipio'        => $municipio,
                    'departamento'     => $departamento,
                    'estado'           => $estado,
                ],
                [
                    'tipo_profesional'     => $tipoProfesional,
                    'registro_profesional' => $registroProfesional,
                    'especialidad'         => $especialidad,
                    'estado'               => $estado,
                ]
            );

            flash(
                'success',
                'El profesional fue creado correctamente.'
            );

            redirect(
                '/profesionales/detalle?id=' .
                $idProfesional
            );

            return;

        } catch (RuntimeException $e) {

            flash(
                'error',
                $e->getMessage()
            );

            redirect(
                '/profesionales/crear'
            );

            return;

        } catch (Throwable $e) {

            flash(
                'error',
                'No fue posible crear el profesional. Verifique la información e inténtelo nuevamente.'
            );

            redirect(
                '/profesionales/crear'
            );

            return;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Crear acceso al sistema
    |--------------------------------------------------------------------------
    */

    public function crearAcceso(): void
    {
        Auth::requireLogin();

        Auth::requirePermission(
            'usuarios.crear'
        );

        $idProfesional = (int) $this->get(
            'id',
            0
        );

        if ($idProfesional <= 0) {
            $this->redirect(
                '/profesionales'
            );

            return;
        }

        $profesional = $this->model->obtener(
            $idProfesional
        );

        if ($profesional === null) {
            flash(
                'error',
                'El profesional seleccionado no existe.'
            );

            $this->redirect(
                '/profesionales'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Verificar si ya tiene usuario
        |--------------------------------------------------------------------------
        */

        $usuario = $this->model->obtenerUsuario(
            $idProfesional
        );

        if ($usuario !== null) {
            flash(
                'error',
                'Este profesional ya tiene un acceso creado al sistema.'
            );

            $this->redirect(
                '/profesionales/detalle?id=' .
                $idProfesional
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Verificar estado del profesional
        |--------------------------------------------------------------------------
        */

        if (
            isset($profesional['estado_profesional'])
            && $profesional['estado_profesional'] !== 'activo'
        ) {
            flash(
                'error',
                'No se puede crear un acceso para un profesional inactivo.'
            );

            $this->redirect(
                '/profesionales/detalle?id=' .
                $idProfesional
            );

            return;
        }

        $this->view(
            'profesionales/acceso',
            [
                'tituloPagina' => 'Crear acceso al sistema',
                'titulo'       => 'Crear acceso al sistema',
                'profesional'  => $profesional,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Guardar acceso al sistema
    |--------------------------------------------------------------------------
    */

    public function guardarAcceso(): void
    {
        Auth::requireLogin();

        Auth::requirePermission(
            'usuarios.crear'
        );

        if (!$this->isPost()) {
            $this->redirect(
                '/profesionales'
            );

            return;
        }

        exigirCsrf();

        /*
        |--------------------------------------------------------------------------
        | Datos recibidos
        |--------------------------------------------------------------------------
        */

        $idProfesional = (int) $this->post(
            'id_profesional',
            0
        );

        $username = trim(
            (string) $this->post(
                'username',
                ''
            )
        );

        $password = (string) $this->post(
            'password',
            ''
        );

        $passwordConfirmacion = (string) $this->post(
            'password_confirmacion',
            ''
        );

        $correo = trim(
            (string) $this->post(
                'correo',
                ''
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Validar profesional
        |--------------------------------------------------------------------------
        */

        if ($idProfesional <= 0) {
            flash(
                'error',
                'El profesional seleccionado no es válido.'
            );

            redirect(
                '/profesionales'
            );

            return;
        }

        $profesional = $this->model->obtener(
            $idProfesional
        );

        if ($profesional === null) {
            flash(
                'error',
                'El profesional seleccionado no existe.'
            );

            redirect(
                '/profesionales'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Validar estado del profesional
        |--------------------------------------------------------------------------
        */

        if (
            isset($profesional['estado_profesional'])
            && $profesional['estado_profesional'] !== 'activo'
        ) {
            flash(
                'error',
                'No se puede crear un acceso para un profesional inactivo.'
            );

            redirect(
                '/profesionales/detalle?id=' .
                $idProfesional
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Verificar usuario existente
        |--------------------------------------------------------------------------
        */

        $usuarioExistente = $this->model->obtenerUsuario(
            $idProfesional
        );

        if ($usuarioExistente !== null) {
            flash(
                'error',
                'Este profesional ya tiene un acceso creado al sistema.'
            );

            redirect(
                '/profesionales/detalle?id=' .
                $idProfesional
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Validar username
        |--------------------------------------------------------------------------
        */

        if ($username === '') {
            flash(
                'error',
                'Debe ingresar un nombre de usuario.'
            );

            redirect(
                '/profesionales/acceso?id=' .
                $idProfesional
            );

            return;
        }

        if (strlen($username) < 4) {
            flash(
                'error',
                'El nombre de usuario debe tener mínimo 4 caracteres.'
            );

            redirect(
                '/profesionales/acceso?id=' .
                $idProfesional
            );

            return;
        }

        if (strlen($username) > 80) {
            flash(
                'error',
                'El nombre de usuario no puede superar los 80 caracteres.'
            );

            redirect(
                '/profesionales/acceso?id=' .
                $idProfesional
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Validar contraseña
        |--------------------------------------------------------------------------
        */

        if ($password === '') {
            flash(
                'error',
                'Debe ingresar una contraseña.'
            );

            redirect(
                '/profesionales/acceso?id=' .
                $idProfesional
            );

            return;
        }

        if (strlen($password) < 8) {
            flash(
                'error',
                'La contraseña debe tener mínimo 8 caracteres.'
            );

            redirect(
                '/profesionales/acceso?id=' .
                $idProfesional
            );

            return;
        }

        if ($password !== $passwordConfirmacion) {
            flash(
                'error',
                'Las contraseñas no coinciden.'
            );

            redirect(
                '/profesionales/acceso?id=' .
                $idProfesional
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Validar correo
        |--------------------------------------------------------------------------
        */

        if (
            $correo !== ''
            && !filter_var(
                $correo,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            flash(
                'error',
                'El correo electrónico ingresado no es válido.'
            );

            redirect(
                '/profesionales/acceso?id=' .
                $idProfesional
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Normalizar correo
        |--------------------------------------------------------------------------
        */

        $correo = $correo !== ''
            ? $correo
            : null;

        /*
        |--------------------------------------------------------------------------
        | Crear usuario
        |--------------------------------------------------------------------------
        */

        try {

            $idUsuario = $this->model->crearUsuario(
                $idProfesional,
                $username,
                $password,
                $correo
            );

            if ($idUsuario <= 0) {
                throw new RuntimeException(
                    'No fue posible crear el acceso al sistema.'
                );
            }

            flash(
                'success',
                'El acceso al sistema fue creado correctamente. Ahora puede asignarle un rol.'
            );

            redirect(
                '/profesionales/detalle?id=' .
                $idProfesional
            );

            return;

        } catch (RuntimeException $e) {

            flash(
                'error',
                $e->getMessage()
            );

            redirect(
                '/profesionales/acceso?id=' .
                $idProfesional
            );

            return;

        } catch (Throwable $e) {

            flash(
                'error',
                'No fue posible crear el acceso al sistema. Verifique la información e inténtelo nuevamente.'
            );

            redirect(
                '/profesionales/acceso?id=' .
                $idProfesional
            );

            return;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Mostrar roles del profesional
    |--------------------------------------------------------------------------
    */

    public function roles(): void
    {
        Auth::requireLogin();

        Auth::requirePermission(
            'roles.ver'
        );

        $idProfesional = (int) $this->get(
            'id',
            0
        );

        if ($idProfesional <= 0) {
            flash(
                'error',
                'El profesional seleccionado no es válido.'
            );

            $this->redirect(
                '/profesionales'
            );

            return;
        }

        $profesional = $this->model->obtener(
            $idProfesional
        );

        if ($profesional === null) {
            flash(
                'error',
                'El profesional seleccionado no existe.'
            );

            $this->redirect(
                '/profesionales'
            );

            return;
        }

        $usuario = $this->model->obtenerUsuario(
            $idProfesional
        );

        if ($usuario === null) {
            flash(
                'error',
                'Este profesional todavía no tiene un usuario creado.'
            );

            $this->redirect(
                '/profesionales/detalle?id=' .
                $idProfesional
            );

            return;
        }

        $roles = $this->model->listarRolesActivos();

        $rolesAsignados = $this->model->obtenerRoles(
            $idProfesional
        );

        $idsRolesAsignados = [];

        foreach ($rolesAsignados as $rol) {

            if (isset($rol['id'])) {
                $idsRolesAsignados[] = (int) $rol['id'];
            }
        }

        $this->view(
            'profesionales/roles',
            [
                'tituloPagina'      => 'Roles del profesional',
                'titulo'            => 'Roles del profesional',
                'profesional'       => $profesional,
                'usuario'           => $usuario,
                'roles'             => $roles,
                'rolesAsignados'    => $rolesAsignados,
                'idsRolesAsignados' => $idsRolesAsignados,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Guardar roles del profesional
    |--------------------------------------------------------------------------
    */

    public function guardarRoles(): void
    {
        Auth::requireLogin();

        Auth::requirePermission(
            'roles.editar'
        );

        if (!$this->isPost()) {
            $this->redirect(
                '/profesionales'
            );

            return;
        }

        exigirCsrf();

        $idProfesional = (int) $this->post(
            'id_profesional',
            0
        );

        $idsRoles = $this->post(
            'roles',
            []
        );

        if ($idProfesional <= 0) {
            flash(
                'error',
                'El profesional seleccionado no es válido.'
            );

            $this->redirect(
                '/profesionales'
            );

            return;
        }

        if (!is_array($idsRoles)) {
            $idsRoles = [];
        }

        $profesional = $this->model->obtener(
            $idProfesional
        );

        if ($profesional === null) {
            flash(
                'error',
                'El profesional seleccionado no existe.'
            );

            $this->redirect(
                '/profesionales'
            );

            return;
        }

        $usuario = $this->model->obtenerUsuario(
            $idProfesional
        );

        if ($usuario === null) {
            flash(
                'error',
                'Este profesional no tiene un usuario creado. Primero debe crearle el acceso al sistema.'
            );

            $this->redirect(
                '/profesionales/detalle?id=' .
                $idProfesional
            );

            return;
        }

        try {

            $guardado = $this->model->guardarRoles(
                $idProfesional,
                $idsRoles
            );

            if (!$guardado) {
                throw new RuntimeException(
                    'No fue posible guardar los roles del profesional.'
                );
            }

            flash(
                'success',
                'Los roles del profesional fueron actualizados correctamente.'
            );

            /*
            |--------------------------------------------------------------------------
            | Regresar al detalle del profesional
            |--------------------------------------------------------------------------
            |
            | El rol ya fue guardado. Volvemos al detalle para que el
            | administrador pueda comprobar inmediatamente los roles y
            | permisos efectivos del profesional.
            |
            */

            $this->redirect(
                '/profesionales/detalle?id=' .
                $idProfesional
            );

            return;

        } catch (RuntimeException $e) {

            flash(
                'error',
                $e->getMessage()
            );

            $this->redirect(
                '/profesionales/detalle?id=' .
                $idProfesional
            );

            return;

        } catch (Throwable $e) {

            flash(
                'error',
                'No fue posible actualizar los roles del profesional.'
            );

            $this->redirect(
                '/profesionales/detalle?id=' .
                $idProfesional
            );

            return;
        }
    }
}