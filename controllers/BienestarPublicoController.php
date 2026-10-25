<?php

declare(strict_types=1);

class BienestarPublicoController extends Controller
{
    private AprendizModel $aprendizModel;
    private BienestarCuestionarioModel $cuestionarioModel;

    public function __construct()
    {
        $this->aprendizModel = new AprendizModel();
        $this->cuestionarioModel = new BienestarCuestionarioModel();
    }

    /**
     * Entrada al cuestionario.
     * La caracterización debe estar actualizada antes de responder.
     */
    public function index(): void
    {
        $token = trim((string)($_GET['token'] ?? $_SESSION['bienestar_token'] ?? ''));
        $enlace = $this->obtenerEnlaceValido($token);

        if ($enlace === null) {
            $this->mostrarIdentificacion('', 'El enlace no es válido, está vencido o fue desactivado.');
            return;
        }

        $_SESSION['bienestar_token'] = $token;

        $idFicha = (int)($enlace['id_ficha'] ?? 0);
        $idAprendiz = (int)($_SESSION['bienestar_id_aprendiz'] ?? 0);
        $fichaSesion = (int)($_SESSION['bienestar_id_ficha'] ?? 0);
        $tokenSesion = (string)($_SESSION['bienestar_token'] ?? '');

        if (
            $idAprendiz > 0
            && $idFicha > 0
            && $fichaSesion === $idFicha
            && hash_equals($token, $tokenSesion)
        ) {
            if (!$this->caracterizacionActualizada($idAprendiz, $idFicha)) {
                $aprendiz = $_SESSION['bienestar_aprendiz'] ?? [];
                $this->mostrarCaracterizacionPublica(
                    $token,
                    $enlace,
                    is_array($aprendiz) ? $aprendiz : [],
                    'Antes de responder el cuestionario, complete y guarde su caracterización.'
                );
                return;
            }

            $cuestionario = $this->cuestionarioModel
                ->obtenerCuestionario($idAprendiz, $idFicha);

            if ($cuestionario !== null) {
                if (($cuestionario['estado'] ?? '') === 'completado') {
                    $this->mostrarIdentificacion($token, 'Este cuestionario ya fue finalizado.');
                    return;
                }

                $aprendiz = $_SESSION['bienestar_aprendiz'] ?? [];

                $this->mostrarFormulario(
                    $token,
                    $enlace,
                    is_array($aprendiz) ? $aprendiz : [],
                    $cuestionario['respuestas'] ?? [],
                    ''
                );
                return;
            }

            $aprendiz = $_SESSION['bienestar_aprendiz'] ?? [];

            $this->mostrarFormulario(
                $token,
                $enlace,
                is_array($aprendiz) ? $aprendiz : [],
                [],
                ''
            );
            return;
        }

        $this->mostrarIdentificacion($token, '');
    }

    /**
     * Valida el documento del aprendiz contra la ficha del enlace.
     * Si la caracterización está pendiente, abre su formulario público.
     */
    public function validar(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->mostrarIdentificacion('', 'Solicitud no válida.');
            return;
        }

        if (!$this->validarCSRF()) {
            $this->mostrarIdentificacion(
                (string)($_POST['token'] ?? ''),
                'La sesión del formulario venció. Intente nuevamente.'
            );
            return;
        }

        $token = trim((string)($_POST['token'] ?? ''));
        $documento = trim((string)($_POST['documento'] ?? ''));

        if ($token === '' || $documento === '') {
            $this->mostrarIdentificacion($token, 'Ingrese el documento solicitado.');
            return;
        }

        $enlace = $this->obtenerEnlaceValido($token);

        if ($enlace === null) {
            $this->mostrarIdentificacion('', 'El enlace no es válido o ya no está activo.');
            return;
        }

        $idFicha = (int)($enlace['id_ficha'] ?? 0);

        if ($idFicha <= 0) {
            $this->mostrarIdentificacion('', 'No fue posible identificar la ficha del enlace.');
            return;
        }

        try {
            $aprendiz = $this->aprendizModel->validarAprendizParaFicha(
                $idFicha,
                $documento,
                null
            );
        } catch (Throwable $e) {
            error_log('Error validando aprendiz para Bienestar: ' . $e->getMessage());
            $aprendiz = null;
        }

        if (!is_array($aprendiz) || empty($aprendiz)) {
            $this->mostrarIdentificacion(
                $token,
                'El documento no se pudo validar para esta ficha. Verifique los datos o contacte al responsable de la ficha.'
            );
            return;
        }

        $idAprendiz = (int)($aprendiz['id'] ?? $aprendiz['id_aprendiz'] ?? 0);

        if ($idAprendiz <= 0) {
            $this->mostrarIdentificacion($token, 'No fue posible identificar el registro del aprendiz.');
            return;
        }

        session_regenerate_id(true);

        $_SESSION['bienestar_token'] = $token;
        $_SESSION['bienestar_id_ficha'] = $idFicha;
        $_SESSION['bienestar_id_aprendiz'] = $idAprendiz;
        $_SESSION['bienestar_aprendiz'] = $aprendiz;

        if (!$this->caracterizacionActualizada($idAprendiz, $idFicha)) {
            $this->mostrarCaracterizacionPublica(
                $token,
                $enlace,
                $aprendiz,
                'Su caracterización está pendiente. Complete y guarde sus datos para continuar.'
            );
            return;
        }

        $cuestionario = $this->cuestionarioModel
            ->obtenerCuestionario($idAprendiz, $idFicha);

        if (
            $cuestionario !== null
            && ($cuestionario['estado'] ?? '') === 'completado'
        ) {
            $this->mostrarIdentificacion($token, 'Este aprendiz ya finalizó el cuestionario de esta ficha.');
            return;
        }

        $this->mostrarFormulario(
            $token,
            $enlace,
            $aprendiz,
            $cuestionario['respuestas'] ?? [],
            ''
        );
    }

    /**
     * Presenta la caracterización pública para el aprendiz identificado.
     */
    public function caracterizacionPublica(): void
    {
        $token = trim((string)($_GET['token'] ?? $_SESSION['bienestar_token'] ?? ''));
        $enlace = $this->obtenerEnlaceValido($token);

        if ($enlace === null) {
            $this->mostrarIdentificacion('', 'El enlace no es válido, está vencido o fue desactivado.');
            return;
        }

        $idFicha = (int)($enlace['id_ficha'] ?? 0);
        $idAprendiz = (int)($_SESSION['bienestar_id_aprendiz'] ?? 0);
        $fichaSesion = (int)($_SESSION['bienestar_id_ficha'] ?? 0);
        $tokenSesion = (string)($_SESSION['bienestar_token'] ?? '');

        if (
            $idAprendiz <= 0
            || $idFicha <= 0
            || $fichaSesion !== $idFicha
            || !hash_equals($token, $tokenSesion)
        ) {
            $this->mostrarIdentificacion(
                $token,
                'Identifíquese con su documento para actualizar la caracterización.'
            );
            return;
        }

        if ($this->caracterizacionActualizada($idAprendiz, $idFicha)) {
            $this->index();
            return;
        }

        $aprendiz = $_SESSION['bienestar_aprendiz'] ?? [];

        $this->mostrarCaracterizacionPublica(
            $token,
            $enlace,
            is_array($aprendiz) ? $aprendiz : [],
            ''
        );
    }

    /**
     * Guarda la caracterización pública.
     * Los identificadores se toman de la sesión, no de los campos ocultos.
     */
    public function actualizarCaracterizacionPublica(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->mostrarIdentificacion('', 'Solicitud no válida.');
            return;
        }

        $token = trim((string)($_POST['token'] ?? ''));

        if (!$this->validarCSRF()) {
            $this->mostrarIdentificacion(
                $token,
                'La sesión del formulario venció. Identifíquese nuevamente.'
            );
            return;
        }

        $enlace = $this->obtenerEnlaceValido($token);
        $idAprendiz = (int)($_SESSION['bienestar_id_aprendiz'] ?? 0);
        $idFicha = (int)($_SESSION['bienestar_id_ficha'] ?? 0);
        $tokenSesion = (string)($_SESSION['bienestar_token'] ?? '');

        if (
            $enlace === null
            || $idAprendiz <= 0
            || $idFicha <= 0
            || $idFicha !== (int)($enlace['id_ficha'] ?? 0)
            || !hash_equals($tokenSesion, $token)
        ) {
            $this->mostrarIdentificacion(
                $token,
                'La sesión no corresponde al enlace de esta ficha. Identifíquese nuevamente.'
            );
            return;
        }

        if ($this->caracterizacionActualizada($idAprendiz, $idFicha)) {
            $this->index();
            return;
        }

        try {
            $caracterizacion = $this->aprendizModel->obtenerCaracterizacion($idAprendiz);

            if (!is_array($caracterizacion) || empty($caracterizacion)) {
                $caracterizacion = $_SESSION['bienestar_aprendiz'] ?? [];
            }

            if (!is_array($caracterizacion)) {
                $caracterizacion = [];
            }

            $idPersona = (int)(
                $caracterizacion['id_persona']
                ?? ($_SESSION['bienestar_aprendiz']['id_persona'] ?? 0)
            );

            if ($idPersona <= 0) {
                throw new RuntimeException('No se pudo identificar la persona asociada al aprendiz.');
            }

            $fechaNacimiento = trim((string)($_POST['fecha_nacimiento'] ?? ''));
            $sexo = trim((string)($_POST['sexo'] ?? ''));
            $telefono = trim((string)($_POST['telefono'] ?? ''));
            $correo = trim((string)($_POST['correo'] ?? ''));
            $direccion = trim((string)($_POST['direccion'] ?? ''));
            $departamento = trim((string)($_POST['departamento'] ?? ''));
            $municipio = trim((string)($_POST['municipio'] ?? ''));
            $contactoEmergencia = trim((string)($_POST['contacto_emergencia'] ?? ''));
            $telefonoEmergencia = trim((string)($_POST['telefono_emergencia'] ?? ''));
            $observacion = trim((string)($_POST['observacion'] ?? ''));
            $idEps = (int)($_POST['id_eps'] ?? 0);
            $idTipoPoblacion = (int)($_POST['id_tipo_poblacion'] ?? 0);

            if (
                $fechaNacimiento !== ''
                && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaNacimiento)
            ) {
                throw new InvalidArgumentException('La fecha de nacimiento no tiene un formato válido.');
            }

            if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Ingrese un correo electrónico válido.');
            }

            if (mb_strlen($telefono) > 30 || mb_strlen($telefonoEmergencia) > 30) {
                throw new InvalidArgumentException('Los teléfonos no pueden superar 30 caracteres.');
            }

            $datos = [
                'id_persona' => $idPersona,
                'fecha_nacimiento' => $fechaNacimiento,
                'sexo' => $sexo,
                'telefono' => $telefono,
                'correo' => $correo,
                'direccion' => $direccion,
                'departamento' => $departamento,
                'municipio' => $municipio,
                'id_eps' => $idEps > 0 ? $idEps : null,
                'id_tipo_poblacion' => $idTipoPoblacion > 0 ? $idTipoPoblacion : null,
                'contacto_emergencia' => $contactoEmergencia,
                'telefono_emergencia' => $telefonoEmergencia,
                'observacion' => $observacion,
            ];

            $actualizado = $this->aprendizModel->actualizarCaracterizacion(
                $idAprendiz,
                $datos
            );

            if (!$actualizado) {
                throw new RuntimeException('No fue posible guardar los datos de caracterización.');
            }

            $camposActualizados = array_keys($datos);
            $registrado = $this->aprendizModel->registrarActualizacionPublica(
                $idAprendiz,
                $idFicha,
                $camposActualizados
            );

            if (!$registrado) {
                throw new RuntimeException('Los datos se guardaron, pero no fue posible registrar el estado de actualización.');
            }

            $_SESSION['bienestar_token'] = $token;
            $_SESSION['bienestar_id_ficha'] = $idFicha;
            $_SESSION['bienestar_id_aprendiz'] = $idAprendiz;

            $aprendizActualizado = $this->aprendizModel->validarAprendizParaFicha(
                $idFicha,
                (string)($_SESSION['bienestar_aprendiz']['documento']
                    ?? $_SESSION['bienestar_aprendiz']['numero_documento']
                    ?? ''),
                null
            );

            if (is_array($aprendizActualizado) && !empty($aprendizActualizado)) {
                $_SESSION['bienestar_aprendiz'] = $aprendizActualizado;
            }

            // index() vuelve a comprobar el estado antes de mostrar el cuestionario.
            $this->index();
            return;
        } catch (Throwable $e) {
            error_log('Error actualizando caracterización pública: ' . $e->getMessage());

            $aprendiz = $_SESSION['bienestar_aprendiz'] ?? [];

            $mensaje = $e instanceof InvalidArgumentException
                ? $e->getMessage()
                : 'No fue posible completar la actualización. Verifique los datos e intente nuevamente.';

            $this->mostrarCaracterizacionPublica(
                $token,
                $enlace,
                is_array($aprendiz) ? $aprendiz : [],
                $mensaje
            );
        }
    }

    /**
     * Guarda las respuestas como borrador.
     */
    public function guardar(): void
    {
        $this->procesarRespuestas(false);
    }

    /**
     * Guarda las respuestas y marca el cuestionario como completado.
     */
    public function finalizar(): void
    {
        $this->procesarRespuestas(true);
    }

    /**
     * Procesa las respuestas y verifica de nuevo la caracterización.
     */
    private function procesarRespuestas(bool $finalizar): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->mostrarIdentificacion('', 'Solicitud no válida.');
            return;
        }

        $token = trim((string)($_POST['token'] ?? ''));

        if (!$this->validarCSRF()) {
            $this->mostrarIdentificacion($token, 'La sesión venció. Identifíquese nuevamente.');
            return;
        }

        $enlace = $this->obtenerEnlaceValido($token);
        $idFicha = (int)($_SESSION['bienestar_id_ficha'] ?? 0);
        $idAprendiz = (int)($_SESSION['bienestar_id_aprendiz'] ?? 0);

        if (
            $enlace === null
            || $idFicha <= 0
            || $idAprendiz <= 0
            || $idFicha !== (int)($enlace['id_ficha'] ?? 0)
            || !hash_equals((string)($_SESSION['bienestar_token'] ?? ''), $token)
        ) {
            $this->mostrarIdentificacion(
                $token,
                'La sesión no corresponde al enlace de esta ficha. Identifíquese nuevamente.'
            );
            return;
        }

        if (!$this->caracterizacionActualizada($idAprendiz, $idFicha)) {
            $aprendiz = $_SESSION['bienestar_aprendiz'] ?? [];

            $this->mostrarCaracterizacionPublica(
                $token,
                $enlace,
                is_array($aprendiz) ? $aprendiz : [],
                'Debe completar y guardar la caracterización antes de continuar.'
            );
            return;
        }

        $recibidas = $_POST['respuestas'] ?? [];
        if (!is_array($recibidas)) {
            $recibidas = [];
        }

        $preguntas = $this->obtenerPreguntas();
        $respuestas = [];

        foreach ($preguntas as $seccion) {
            foreach ($seccion['preguntas'] as $pregunta) {
                $clave = $pregunta['id'];

                if (!array_key_exists($clave, $recibidas)) {
                    $respuestas[$clave] = [];
                    continue;
                }

                $valor = $recibidas[$clave];

                if (is_array($valor)) {
                    $limpios = [];

                    foreach ($valor as $item) {
                        if (is_scalar($item)) {
                            $limpios[] = mb_substr(trim((string)$item), 0, 500);
                        }
                    }

                    $respuestas[$clave] = array_values(array_unique($limpios));
                } elseif (is_scalar($valor)) {
                    $respuestas[$clave] = mb_substr(trim((string)$valor), 0, 2000);
                } else {
                    $respuestas[$clave] = [];
                }
            }
        }

        if ($finalizar) {
            foreach ($preguntas as $seccion) {
                foreach ($seccion['preguntas'] as $pregunta) {
                    if (empty($pregunta['obligatoria'])) {
                        continue;
                    }

                    $valor = $respuestas[$pregunta['id']] ?? [];
                    $respondida = is_array($valor)
                        ? count($valor) > 0
                        : trim((string)$valor) !== '';

                    if (!$respondida) {
                        $aprendiz = $_SESSION['bienestar_aprendiz'] ?? [];

                        $this->mostrarFormulario(
                            $token,
                            $enlace,
                            is_array($aprendiz) ? $aprendiz : [],
                            $respuestas,
                            'Debe responder las preguntas obligatorias antes de finalizar.'
                        );
                        return;
                    }
                }
            }
        }

        try {
            $guardado = $finalizar
                ? $this->cuestionarioModel->finalizarCuestionario($idAprendiz, $idFicha, $respuestas)
                : $this->cuestionarioModel->guardarBorrador($idAprendiz, $idFicha, $respuestas);

            if (!$guardado) {
                $this->mostrarIdentificacion(
                    $token,
                    'No se pudo guardar. Es posible que el cuestionario ya esté finalizado.'
                );
                return;
            }
        } catch (Throwable $e) {
            error_log('Error guardando cuestionario de Bienestar: ' . $e->getMessage());

            $aprendiz = $_SESSION['bienestar_aprendiz'] ?? [];

            $this->mostrarFormulario(
                $token,
                $enlace,
                is_array($aprendiz) ? $aprendiz : [],
                $respuestas,
                'Ocurrió un error al guardar. Intente nuevamente.'
            );
            return;
        }

        if ($finalizar) {
            $this->mostrarIdentificacion($token, 'El cuestionario fue finalizado correctamente.');
            return;
        }

        $aprendiz = $_SESSION['bienestar_aprendiz'] ?? [];

        $this->mostrarFormulario(
            $token,
            $enlace,
            is_array($aprendiz) ? $aprendiz : [],
            $respuestas,
            'Borrador guardado correctamente. Puede continuar más tarde.'
        );
    }

    /**
     * Renderiza el formulario público de caracterización.
     */
    private function mostrarCaracterizacionPublica(
        string $token,
        array $enlace,
        array $aprendiz,
        string $mensaje
    ): void {
        $csrf = $this->prepararCSRF();

        $idAprendiz = (int)($aprendiz['id'] ?? $aprendiz['id_aprendiz'] ?? $_SESSION['bienestar_id_aprendiz'] ?? 0);

        try {
            $datosGuardados = $idAprendiz > 0
                ? $this->aprendizModel->obtenerCaracterizacion($idAprendiz)
                : null;

            if (is_array($datosGuardados)) {
                $aprendiz = array_merge($aprendiz, $datosGuardados);
            }

            $eps = $this->aprendizModel->listarEpsActivas();
            $tiposPoblacion = $this->aprendizModel->listarTiposPoblacionActivos();
        } catch (Throwable $e) {
            error_log('Error cargando caracterización pública: ' . $e->getMessage());
            $eps = [];
            $tiposPoblacion = [];
            if ($mensaje === '') {
                $mensaje = 'No fue posible cargar todos los datos. Actualice la página e intente nuevamente.';
            }
        }

        require ROOT_PATH . '/views/bienestar_publico/caracterizacion.php';
    }

    /**
     * Comprueba el estado registrado de la caracterización.
     */
    private function caracterizacionActualizada(int $idAprendiz, int $idFicha): bool
    {
        if ($idAprendiz <= 0 || $idFicha <= 0) {
            return false;
        }

        try {
            $estado = $this->aprendizModel->obtenerEstadoActualizacion($idAprendiz, $idFicha);

            return is_array($estado)
                && strtolower(trim((string)($estado['estado'] ?? ''))) === 'actualizado';
        } catch (Throwable $e) {
            error_log('Error verificando caracterización antes de Bienestar: ' . $e->getMessage());
            return false;
        }
    }

    private function obtenerEnlaceValido(string $token): ?array
    {
        if ($token === '' || strlen($token) > 200) {
            return null;
        }

        try {
            $enlace = $this->aprendizModel->obtenerEnlaceFichaPorToken($token);
        } catch (Throwable $e) {
            error_log('Error consultando enlace de Bienestar: ' . $e->getMessage());
            return null;
        }

        return is_array($enlace) ? $enlace : null;
    }

    private function validarCSRF(): bool
    {
        $recibido = (string)($_POST['_csrf'] ?? '');
        $guardado = (string)($_SESSION['_csrf_bienestar'] ?? '');

        return $recibido !== ''
            && $guardado !== ''
            && hash_equals($guardado, $recibido);
    }

    private function prepararCSRF(): string
    {
        if (empty($_SESSION['_csrf_bienestar'])) {
            $_SESSION['_csrf_bienestar'] = bin2hex(random_bytes(32));
        }

        return (string)$_SESSION['_csrf_bienestar'];
    }

    private function mostrarIdentificacion(string $token, string $mensaje): void
    {
        $csrf = $this->prepararCSRF();
        require ROOT_PATH . '/views/bienestar_publico/identificacion.php';
    }

    private function mostrarFormulario(
        string $token,
        array $enlace,
        array $aprendiz,
        array $respuestas,
        string $mensaje
    ): void {
        $csrf = $this->prepararCSRF();
        $secciones = $this->obtenerPreguntas();

        $nombreAprendiz = trim((string)($aprendiz['nombre_completo'] ?? ''));

        if ($nombreAprendiz === '') {
            $nombreAprendiz = trim(
                (string)($aprendiz['nombres'] ?? '') . ' ' .
                (string)($aprendiz['apellidos'] ?? '')
            );
        }

        if ($nombreAprendiz === '') {
            $nombreAprendiz = 'Aprendiz';
        }

        $nombreFicha = (string)(
            $enlace['numero_ficha']
            ?? $enlace['ficha']
            ?? $enlace['nombre_ficha']
            ?? ''
        );

        require ROOT_PATH . '/views/bienestar_publico/cuestionario.php';
    }

    /**
     * Catálogo centralizado de las cinco secciones.
     */
    private function obtenerPreguntas(): array
    {
        $opciones = [
            'sisben' => [
                'A1','A2','A3','A4','A5','B1','B2','B3','B4','B5','B6','B7',
                'C1','C2','C3','C4','C5','C6','C7','C8','C9','C10','C11','C12',
                'C13','C14','C15','C16','C17','C18','D1','D2','D3','D4','D5',
                'D6','D7','D8','D9','D10','D11','D12','D13','D14','D15','D16',
                'D17','D18','D19','D20','D21','No registrado','No conoce'
            ],
            'estrato' => ['1','2','3','4','5','6','Vivienda sin estrato','No conoce'],
            'zona' => ['Urbana','Rural'],
            'si_no_prefiero' => ['Sí','No','Prefiero no responder'],
            'si_no' => ['Sí','No'],
            'frecuencia' => ['Nunca','Una vez','Algunas veces','Semanalmente','Diariamente'],
            'conocimiento' => [
                'Muy informado',
                'Moderadamente informado, con algunas dudas',
                'Tengo poca información',
                'Necesito orientación',
                'Prefiero no responder'
            ],
            'habitos' => [
                'Alimentación','Hidratación','Sueño','Manejo del estrés','Higiene',
                'Actividad física','Prevención del consumo','Otro','Ninguno',
                'Prefiero no responder'
            ],
            'salud_1_10' => ['1','2','3','4','5','6','7','8','9','10'],
            'temas_salud' => [
                'Alimentación','Salud sexual y reproductiva',
                'Prevención del consumo de sustancias','Salud física',
                'Hábitos saludables','Otro','Ninguno'
            ],
            'estres' => [
                'Situaciones académicas','Situaciones económicas','Situaciones familiares',
                'Relaciones interpersonales','Adaptación al SENA','Futuro laboral',
                'Situaciones personales o de bienestar','Otra','Ninguna',
                'Prefiero no responder'
            ],
            'apoyo' => [
                'Familia','Amigos','Instructor o líder de ficha','Bienestar al Aprendiz',
                'Profesional de salud','Otra persona','No cuento con una red de apoyo',
                'Prefiero no responder'
            ],
            'pedir_ayuda' => [
                'Muy fácil','Fácil','Ni fácil ni difícil','Difícil','Muy difícil',
                'No sabe','Prefiero no responder'
            ],
            'escala_1_5' => ['1','2','3','4','5','No estoy seguro/a'],
            'equipo' => ['1 - Nada cómodo/a','2','3','4','5 - Muy cómodo/a','No estoy seguro/a'],
            'desacuerdo' => [
                'Dialogo para resolverlo','Escucho el punto de vista de la otra persona',
                'Busco una persona mediadora','Prefiero evitar el conflicto',
                'Me cuesta controlar mi reacción','Depende de la situación',
                'Prefiero no responder'
            ],
            'habilidades_blandas' => [
                'Manejo del estrés y las emociones','Comunicación','Autoestima',
                'Trabajo en equipo','Resolución de conflictos','Empatía',
                'Toma de decisiones','Adaptación','Ninguna','Otra'
            ],
            'organizacion' => [
                'Muy bien','Bien, con dificultades ocasionales','Regular',
                'Necesito mejorar mucho','No estoy seguro/a'
            ],
            'talleres' => [
                'Inteligencia emocional','Comunicación asertiva','Liderazgo',
                'Trabajo en equipo','Resolución de conflictos','Manejo del estrés',
                'Autoestima y proyecto de vida','Gestión del tiempo y hábitos de estudio',
                'Convivencia y diversidad','Otro','Ninguno'
            ],
            'deportes' => [
                'Fútbol','Microfútbol','Baloncesto','Voleibol','Atletismo','Ciclismo',
                'Natación','Gimnasio o fuerza','Baile','Caminata','Otra','Ninguna'
            ],
            'participacion' => ['Sí, actualmente','Sí, anteriormente','No'],
            'participar' => ['Sí','No','Tal vez'],
            'barreras' => [
                'Falta de tiempo','Horarios','Transporte','Costos',
                'Falta de escenarios o implementos','Motivación','Otra','Ninguna'
            ],
            'horarios' => [
                'Antes de la jornada','En espacios libres','Después de la jornada',
                'Fines de semana','Depende de la actividad','Otro'
            ],
            'talento' => [
                'Sí, tengo talento o habilidad','No actualmente','Me gustaría explorar'
            ],
            'artes' => [
                'Canto','Instrumentos musicales','Danza','Teatro','Dibujo o pintura',
                'Escritura o poesía','Artesanías','Fotografía, video o audiovisual',
                'Otra','Ninguna'
            ],
            'instrumentos' => [
                'Guitarra','Piano o teclado','Batería o percusión','Bajo',
                'Instrumentos de viento',
                'Instrumento de cuerda distinto a guitarra','Otro'
            ],
            'aprender_arte' => [
                'Canto','Instrumentos musicales','Danza','Teatro','Dibujo o pintura',
                'Escritura','Artesanías','Fotografía o video','Otra','Ninguna'
            ],
            'barreras_arte' => [
                'Falta de tiempo','Horarios','Costos de materiales',
                'Falta de instrumentos o materiales','Falta de espacios',
                'Falta de experiencia','Otra','Ninguna'
            ],
            'momento' => [
                'Antes de la jornada','En espacios libres','Después de la jornada',
                'Fines de semana','Depende de la actividad','Otro'
            ],
        ];

        $q = static function (
            string $id,
            string $texto,
            string $tipo,
            array $opciones = [],
            bool $obligatoria = false,
            string $ayuda = ''
        ): array {
            return [
                'id' => $id,
                'texto' => $texto,
                'tipo' => $tipo,
                'opciones' => $opciones,
                'obligatoria' => $obligatoria,
                'ayuda' => $ayuda,
            ];
        };

        return [
            [
                'id' => 'CS',
                'nombre' => 'Caracterización socioeconómica',
                'descripcion' => 'Información general para orientar los apoyos disponibles.',
                'preguntas' => [
                    $q('CS-01','¿Cuál es su grupo y subgrupo del SISBÉN?','radio',$opciones['sisben']),
                    $q('CS-02','¿Cuál es el estrato de su vivienda?','radio',$opciones['estrato']),
                    $q('CS-03','¿En qué zona está ubicada su vivienda?','radio',$opciones['zona']),
                    $q('CS-04','¿Tiene alguna condición especial de protección que desee comunicar para recibir orientación?','radio',$opciones['si_no_prefiero'],false,'No se solicita adjuntar certificados en este formulario.'),
                ],
            ],
            [
                'id' => 'PP',
                'nombre' => 'Promoción y prevención',
                'descripcion' => 'Responda únicamente la información que se sienta cómodo/a compartiendo.',
                'preguntas' => [
                    $q('PP-01','¿Tiene alguna condición de salud diagnosticada que desee comunicar para recibir apoyo?','radio',$opciones['si_no_prefiero'],false,'No escriba diagnósticos ni información clínica detallada aquí.'),
                    $q('PP-02','¿Qué sustancias ha consumido durante los últimos 30 días?','checkbox',['Alcohol','Cigarrillos','Vapeadores','Sustancias psicoactivas','Ninguna','Prefiero no responder'],false,'Puede seleccionar más de una opción. “Ninguna” y “Prefiero no responder” deben elegirse por separado.'),
                    $q('PP-02-FRECUENCIA','Si desea responder, indique la frecuencia de consumo de cada categoría seleccionada.','textarea',[],false,'Es opcional. No incluya detalles que no quiera compartir.'),
                    $q('PP-03','¿Cómo califica su nivel de información sobre anticonceptivos, ITS y derechos sexuales y reproductivos?','radio',$opciones['conocimiento']),
                    $q('PP-04','¿Qué hábitos le gustaría mejorar?','checkbox',$opciones['habitos']),
                    $q('PP-05','En una escala de 1 a 10, ¿cómo califica actualmente su estado de salud según su percepción?','radio',$opciones['salud_1_10'],false,'1 = muy malo; 10 = excelente. Es una percepción personal, no un diagnóstico.'),
                    $q('PP-06','¿Sobre qué tema de promoción y prevención le gustaría recibir información?','checkbox',$opciones['temas_salud']),
                ],
            ],
            [
                'id' => 'HB',
                'nombre' => 'Habilidades blandas',
                'descripcion' => 'Preguntas para identificar intereses de acompañamiento y formación.',
                'preguntas' => [
                    $q('HB-01','¿Qué situaciones le generan estrés o preocupación?','checkbox',$opciones['estres']),
                    $q('HB-02','¿Con quién cuenta cuando necesita apoyo?','checkbox',$opciones['apoyo']),
                    $q('HB-03','¿Qué tan fácil le resulta pedir ayuda cuando la necesita?','radio',$opciones['pedir_ayuda']),
                    $q('HB-04','¿Cómo califica su capacidad para comunicarse y escuchar respetuosamente?','radio',$opciones['escala_1_5'],false,'Escala de 1 a 5; puede seleccionar “No estoy seguro/a”.'),
                    $q('HB-05','¿Qué tan cómodo/a se siente trabajando en equipo con personas diferentes a usted?','radio',$opciones['equipo']),
                    $q('HB-06','¿Cómo suele actuar ante un desacuerdo?','radio',$opciones['desacuerdo']),
                    $q('HB-07','¿Qué habilidades le gustaría fortalecer?','checkbox',$opciones['habilidades_blandas']),
                    $q('HB-08','¿Cómo considera que organiza su tiempo?','radio',$opciones['organizacion']),
                    $q('HB-09','¿En cuáles talleres le gustaría participar?','checkbox',$opciones['talleres']),
                ],
            ],
            [
                'id' => 'AF',
                'nombre' => 'Actividad física',
                'descripcion' => 'Sus respuestas ayudan a planear actividades deportivas y recreativas.',
                'preguntas' => [
                    $q('AF-01','¿Practica habitualmente algún deporte o actividad física?','radio',$opciones['si_no']),
                    $q('AF-02','¿Qué deportes o actividades practica actualmente?','checkbox',$opciones['deportes'],false,'Si respondió “Sí” en AF-01, seleccione las actividades correspondientes.'),
                    $q('AF-03','¿Qué deportes o actividades le gustaría practicar?','checkbox',$opciones['deportes']),
                    $q('AF-04','¿Ha participado en equipos o torneos deportivos?','radio',$opciones['participacion']),
                    $q('AF-05','¿Le gustaría participar en actividades deportivas del SENA?','radio',$opciones['participar']),
                    $q('AF-06','¿Qué dificultades podrían impedirle participar?','checkbox',$opciones['barreras']),
                    $q('AF-07','En una escala de 0 a 10, ¿qué tan motivado/a se siente para realizar actividad física?','escala',array_map('strval',range(0,10))),
                    $q('AF-08','¿Qué apoyo o adaptación facilitaría su participación?','textarea',[],false,'Opcional. Comparta solo la información necesaria para solicitar orientación.'),
                    $q('AF-09','¿Qué horarios prefiere para las actividades físicas?','checkbox',$opciones['horarios']),
                    $q('AF-10','¿Qué actividad recreativa le gustaría que se organizara y por qué?','textarea'),
                ],
            ],
            [
                'id' => 'HA',
                'nombre' => 'Habilidades artísticas',
                'descripcion' => 'Intereses artísticos, culturales y creativos.',
                'preguntas' => [
                    $q('HA-01','¿Considera que tiene alguna habilidad o interés artístico?','radio',$opciones['talento']),
                    $q('HA-02','¿En qué actividades artísticas tiene experiencia o interés?','checkbox',$opciones['artes']),
                    $q('HA-03','¿Toca algún instrumento musical?','radio',$opciones['si_no']),
                    $q('HA-04','¿Qué instrumento o instrumentos toca?','checkbox',$opciones['instrumentos'],false,'Si respondió “Sí” en HA-03, seleccione los instrumentos correspondientes.'),
                    $q('HA-05','¿Qué actividad artística le gustaría aprender o mejorar?','checkbox',$opciones['aprender_arte']),
                    $q('HA-06','¿Ha participado en actividades artísticas o culturales?','radio',$opciones['participacion']),
                    $q('HA-07','¿Le gustaría participar en actividades artísticas del SENA?','radio',$opciones['participar']),
                    $q('HA-08','¿Qué dificultades podrían impedirle participar?','checkbox',$opciones['barreras_arte']),
                    $q('HA-09','¿Qué momento prefiere para realizar actividades artísticas?','checkbox',$opciones['momento']),
                    $q('HA-10','¿Qué actividad artística o cultural propone para el SENA?','textarea'),
                ],
            ],
        ];
    }
}