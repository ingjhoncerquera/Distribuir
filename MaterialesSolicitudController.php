<?php

declare(strict_types=1);

if (!class_exists('MaterialesSolicitudController', false)) {

    class MaterialesSolicitudController
    {
        private MaterialesSolicitudModel $model;

        private string $sessionCartKey =
            'materiales_solicitud_carrito';

        /**
         * Datos temporales del encabezado de la solicitud.
         *
         * Se mantienen en sesión mientras el usuario construye
         * la solicitud y navega por el catálogo.
         */
        private string $sessionFormKey =
            'materiales_solicitud_formulario';

        public function __construct()
        {
            Auth::requireLogin();

            $this->model =
                new MaterialesSolicitudModel();

            /*
             * =====================================================
             * INICIALIZAR CARRITO
             * =====================================================
             */
            if (
                !isset(
                    $_SESSION[
                        $this->sessionCartKey
                    ]
                )
                ||
                !is_array(
                    $_SESSION[
                        $this->sessionCartKey
                    ]
                )
            ) {
                $_SESSION[
                    $this->sessionCartKey
                ] = [];
            }

            /*
             * =====================================================
             * INICIALIZAR FORMULARIO TEMPORAL
             * =====================================================
             */
            if (
                !isset(
                    $_SESSION[
                        $this->sessionFormKey
                    ]
                )
                ||
                !is_array(
                    $_SESSION[
                        $this->sessionFormKey
                    ]
                )
            ) {
                $_SESSION[
                    $this->sessionFormKey
                ] = [
                    'id_proceso' =>
                        0,

                    'id_proceso_area' =>
                        0,

                    'titulo' =>
                        '',

                    'justificacion' =>
                        '',
                ];
            }
        }

        /**
         * =========================================================
         * LISTADO DE SOLICITUDES PROPIAS
         * =========================================================
         */
        public function index(): void
        {
            $this->exigirPermiso(
                'materiales.solicitudes.crear'
            );

            $profesional =
                $this->profesionalActual();

            if (!$profesional) {

                /*
                 * Un Administrador sin profesional asociado no tiene
                 * "solicitudes propias": se le envía a la bandeja
                 * de gestión (aval).
                 */
                if (Auth::hasRole('Administrador')) {

                    $this->redirect(
                        '/materiales/solicitudes/aval'
                    );
                }

                $this->error(
                    'El usuario actual no tiene un profesional activo asociado.'
                );
            }

            $idProfesional =
                (int) $profesional['id'];

            $solicitudes =
                $this->model->listarPorProfesional(
                    $idProfesional
                );

            require ROOT_PATH .
                '/views/solicitudes/index.php';
        }

        /**
         * =========================================================
         * CREAR SOLICITUD
         * =========================================================
         */
        public function crear(): void
        {
            $this->exigirPermiso(
                'materiales.solicitudes.crear'
            );

            $profesional =
                $this->exigirProfesional();

            $idProfesional =
                (int) $profesional['id'];

            $carrito =
                $this->obtenerCarrito();

            // Fichas vigentes asignadas al profesional actual.
            $fichas =
                $this->model->listarFichasAsignadasPorProfesional(
                    $idProfesional
                );

            $idsFichasPermitidas = array_map(
                static fn(array $ficha): int => (int) $ficha['id'],
                $fichas
            );

            // Si solo tiene una ficha, se asigna automáticamente a cada línea.
            foreach ($carrito as $idLinea => $itemCarrito) {
                $idFichaActual = (int) ($itemCarrito['id_ficha'] ?? 0);
                if (count($fichas) === 1) {
                    $carrito[$idLinea]['id_ficha'] = (int) $fichas[0]['id'];
                } elseif (!in_array($idFichaActual, $idsFichasPermitidas, true)) {
                    $carrito[$idLinea]['id_ficha'] = 0;
                }
            }
            $this->guardarCarrito($carrito);

            $procesos =
                $this->model->listarProcesosDisponibles();

            /*
             * =====================================================
             * RECUPERAR DATOS TEMPORALES DEL ENCABEZADO
             * =====================================================
             */
            $formulario =
                $this->obtenerFormularioTemporal();

            /*
             * =====================================================
             * PROCESO DESDE GET
             * =====================================================
             */
            if (isset($_GET['id_proceso'])) {

                $idProcesoRecibido =
                    (int) $_GET['id_proceso'];

                $idProcesoAnterior =
                    (int) (
                        $formulario['id_proceso']
                        ?? 0
                    );

                $formulario['id_proceso'] =
                    $idProcesoRecibido;

                if (
                    $idProcesoRecibido !==
                    $idProcesoAnterior
                ) {
                    $formulario['id_proceso_area'] =
                        0;
                }
            }

            $idProceso =
                (int) (
                    $formulario['id_proceso']
                    ?? 0
                );

            /*
             * =====================================================
             * DATOS RECIBIDOS DESDE GET
             * =====================================================
             */
            if (isset($_GET['id_proceso_area'])) {

                $formulario['id_proceso_area'] =
                    (int) $_GET['id_proceso_area'];
            }

            if (isset($_GET['titulo'])) {

                $formulario['titulo'] =
                    trim(
                        (string) $_GET['titulo']
                    );
            }

            if (isset($_GET['justificacion'])) {

                $formulario['justificacion'] =
                    trim(
                        (string) $_GET['justificacion']
                    );
            }

            /*
             * =====================================================
             * DATOS RECIBIDOS DESDE POST
             * =====================================================
             */
            if (isset($_POST['id_proceso'])) {

                $formulario['id_proceso'] =
                    (int) $_POST['id_proceso'];

                $idProceso =
                    (int) $formulario['id_proceso'];
            }

            if (isset($_POST['id_proceso_area'])) {

                $formulario['id_proceso_area'] =
                    (int) $_POST['id_proceso_area'];
            }

            if (isset($_POST['titulo'])) {

                $formulario['titulo'] =
                    trim(
                        (string) $_POST['titulo']
                    );
            }

            if (isset($_POST['justificacion'])) {

                $formulario['justificacion'] =
                    trim(
                        (string) $_POST['justificacion']
                    );
            }

            /*
             * =====================================================
             * GUARDAR DATOS TEMPORALES
             * =====================================================
             */
            $this->guardarFormularioTemporal(
                $formulario
            );

            /*
             * =====================================================
             * VARIABLES PARA LA VISTA
             * =====================================================
             */
            $idProcesoArea =
                (int) (
                    $formulario['id_proceso_area']
                    ?? 0
                );

            $tituloSolicitud =
                (string) (
                    $formulario['titulo']
                    ?? ''
                );

            $justificacionSolicitud =
                (string) (
                    $formulario['justificacion']
                    ?? ''
                );

            /*
             * =====================================================
             * ÁREAS ASIGNADAS AL PROFESIONAL
             * =====================================================
             */
            $areas = [];

            if ($idProceso > 0) {

                $areas =
                    $this->model->listarAreasPorProceso(
                        $idProceso,
                        $idProfesional
                    );
            }

            /*
             * =====================================================
             * BUSCADOR
             * =====================================================
             */
            $buscar =
                trim(
                    (string) (
                        $_GET['buscar']
                        ?? $_POST['buscar']
                        ?? ''
                    )
                );

            /*
             * =====================================================
             * PAGINACIÓN
             * =====================================================
             */
            $pagina =
                max(
                    1,
                    (int) (
                        $_GET['pagina']
                        ?? $_POST['pagina']
                        ?? 1
                    )
                );

            $porPagina = 20;

            $total =
                $this->model->contarMaterialesActivos(
                    $buscar
                );

            $totalPaginas =
                max(
                    1,
                    (int) ceil(
                        $total / $porPagina
                    )
                );

            if ($pagina > $totalPaginas) {

                $pagina =
                    $totalPaginas;
            }

            $offset =
                ($pagina - 1) * $porPagina;

            /*
             * =====================================================
             * MATERIALES
             * =====================================================
             */
            $materiales =
                $this->model->listarMaterialesActivos(
                    $buscar,
                    $porPagina,
                    $offset
                );

            /*
             * =====================================================
             * VISTA
             * =====================================================
             */
            require ROOT_PATH .
                '/views/solicitudes/crear.php';
        }

        /**
         * =========================================================
         * CARGAR ÁREAS POR AJAX
         * =========================================================
         */
        public function cargarAreas(): void
        {
            $this->exigirPermiso(
                'materiales.solicitudes.crear'
            );

            header(
                'Content-Type: application/json; charset=utf-8'
            );

            try {

                $profesional =
                    $this->exigirProfesional();

                $idProfesional =
                    (int) $profesional['id'];

                $idProceso =
                    isset($_GET['id_proceso'])
                        ? (int) $_GET['id_proceso']
                        : 0;

                if ($idProceso <= 0) {

                    echo json_encode(
                        [
                            'ok' => false,
                            'areas' => [],
                            'mensaje' =>
                                'Debe seleccionar un proceso.'
                        ],
                        JSON_UNESCAPED_UNICODE
                    );

                    return;
                }

                $proceso =
                    $this->model->obtenerProceso(
                        $idProceso
                    );

                if (!$proceso) {

                    echo json_encode(
                        [
                            'ok' => false,
                            'areas' => [],
                            'mensaje' =>
                                'El proceso seleccionado no existe.'
                        ],
                        JSON_UNESCAPED_UNICODE
                    );

                    return;
                }

                $formulario =
                    $this->obtenerFormularioTemporal();

                $formulario['id_proceso'] =
                    $idProceso;

                $formulario['id_proceso_area'] =
                    0;

                $this->guardarFormularioTemporal(
                    $formulario
                );

                $areas =
                    $this->model->listarAreasPorProceso(
                        $idProceso,
                        $idProfesional
                    );

                echo json_encode(
                    [
                        'ok' => true,
                        'areas' => $areas,
                    ],
                    JSON_UNESCAPED_UNICODE
                );

            } catch (Throwable $e) {

                echo json_encode(
                    [
                        'ok' => false,
                        'areas' => [],
                        'mensaje' =>
                            'No fue posible cargar las áreas.'
                    ],
                    JSON_UNESCAPED_UNICODE
                );
            }
        }

        /**
         * =========================================================
         * AGREGAR MATERIAL AL CARRITO
         * =========================================================
         */
        public function agregarMaterial(): void
        {
            $this->exigirPermiso(
                'materiales.solicitudes.crear'
            );

            $this->validarPost();
            $this->actualizarFormularioDesdePost();

            $idMaterial = (int) ($_POST['id_material'] ?? 0);
            $cantidad = (float) ($_POST['cantidad'] ?? 1);
            $buscar = trim((string) ($_POST['buscar'] ?? ''));
            $pagina = max(1, (int) ($_POST['pagina'] ?? 1));

            if ($idMaterial <= 0) {
                $this->flashError('El material seleccionado no es válido.');
                $this->redirectCrear($buscar, $pagina);
            }

            if ($cantidad <= 0) {
                $cantidad = 1;
            }

            $material = $this->model->obtenerMaterialActivo($idMaterial);

            if (!$material) {
                $this->flashError('El material seleccionado no está disponible.');
                $this->redirectCrear($buscar, $pagina);
            }

            $carrito = $this->obtenerCarrito();

            // Cada clic crea una línea independiente, incluso si el id_material ya existe.
            $idLinea = 'linea_' . bin2hex(random_bytes(8));

            $carrito[$idLinea] = [
                'id_linea' => $idLinea,
                'id' => $idMaterial,
                'id_material' => $idMaterial,
                'codigo_unspsc' => (string) ($material['codigo_unspsc'] ?? ''),
                'descripcion' => (string) ($material['descripcion'] ?? ''),
                'unidad_medida' => (string) ($material['unidad_medida'] ?? ''),
                'descripcion_tecnica' => '',
                'es_consumo' => 0,
                'es_devolutivo' => 0,
                'es_software' => 0,
                'es_epp' => 0,
                'cantidad' => $cantidad,
                'observaciones' => '',
            ];

            $this->guardarCarrito($carrito);
            $this->flashSuccess('Material agregado a la solicitud. Puede agregar el mismo código nuevamente.');
            $this->redirectCrear($buscar, $pagina);
        }
        /**
         * =========================================================
         * ACTUALIZAR CARRITO
         * =========================================================
         */
        public function actualizarCarrito(): void
        {
            $this->exigirPermiso('materiales.solicitudes.crear');
            $this->validarPost();
            $this->actualizarFormularioDesdePost();

            $carrito = $this->obtenerCarrito();
            $idLinea = trim((string) ($_POST['id_linea'] ?? ''));
            $idMaterial = (int) ($_POST['id_material'] ?? 0);

            // Compatibilidad temporal con formularios antiguos que enviaban id_material.
            if ($idLinea === '' && $idMaterial > 0) {
                foreach ($carrito as $clave => $item) {
                    if ((int) ($item['id_material'] ?? $item['id'] ?? 0) === $idMaterial) {
                        $idLinea = (string) $clave;
                        break;
                    }
                }
            }

            if ($idLinea === '' || !isset($carrito[$idLinea]) || !is_array($carrito[$idLinea])) {
                $this->flashError('La línea seleccionada no se encuentra en el carrito.');
                $this->redirect('/materiales/solicitudes/crear');
            }

            $item = $carrito[$idLinea];
            $cantidadRecibida = $_POST['cantidad'] ?? ($item['cantidad'] ?? 1);
            $cantidad = is_array($cantidadRecibida)
                ? (float) ($cantidadRecibida[$idLinea] ?? 0)
                : (float) $cantidadRecibida;

            if ($cantidad <= 0) {
                $this->flashError('La cantidad debe ser mayor que cero.');
                $this->redirect('/materiales/solicitudes/crear');
            }

            $carrito[$idLinea]['cantidad'] = $cantidad;

            $observaciones = $_POST['observaciones'] ?? null;
            if ($observaciones !== null) {
                $carrito[$idLinea]['observaciones'] = trim((string) (is_array($observaciones)
                    ? ($observaciones[$idLinea] ?? '')
                    : $observaciones));
            }

            foreach (['unidad_medida', 'descripcion_tecnica'] as $campo) {
                if (isset($_POST[$campo])) {
                    $valor = $_POST[$campo];
                    $carrito[$idLinea][$campo] = trim((string) (is_array($valor)
                        ? ($valor[$idLinea] ?? '')
                        : $valor));
                }
            }

            foreach (['es_consumo', 'es_devolutivo', 'es_software', 'es_epp'] as $campo) {
                if (isset($_POST[$campo])) {
                    $valor = $_POST[$campo];
                    $carrito[$idLinea][$campo] = (int) ((is_array($valor)
                        ? ($valor[$idLinea] ?? 0)
                        : $valor) ? 1 : 0);
                }
            }

            $this->guardarCarrito($carrito);
            $this->flashSuccess('La línea del material fue actualizada correctamente.');
            $this->redirect('/materiales/solicitudes/crear');
        }
        /**
         * =========================================================
         * QUITAR MATERIAL DEL CARRITO
         * =========================================================
         */
        public function quitarMaterial(): void
        {
            $this->exigirPermiso('materiales.solicitudes.crear');
            $this->validarPost();
            $this->actualizarFormularioDesdePost();

            $carrito = $this->obtenerCarrito();
            $idLinea = trim((string) ($_POST['id_linea'] ?? ''));
            $idMaterial = (int) ($_POST['id_material'] ?? 0);

            if ($idLinea !== '' && isset($carrito[$idLinea])) {
                unset($carrito[$idLinea]);
                $this->guardarCarrito($carrito);
            } elseif ($idMaterial > 0) {
                // Compatibilidad con una vista antigua: retirar solo la primera línea de ese material.
                foreach ($carrito as $clave => $item) {
                    if ((int) ($item['id_material'] ?? $item['id'] ?? 0) === $idMaterial) {
                        unset($carrito[$clave]);
                        $this->guardarCarrito($carrito);
                        break;
                    }
                }
            }

            $this->flashSuccess('La línea del material fue retirada de la solicitud.');
            $this->redirect('/materiales/solicitudes/crear');
        }
        /**
         * =========================================================
         * VACIAR CARRITO
         * =========================================================
         */
        public function vaciarCarrito(): void
        {
            $this->exigirPermiso(
                'materiales.solicitudes.crear'
            );

            $this->validarPost();

            $this->guardarCarrito([]);

            $this->flashSuccess(
                'El carrito fue vaciado.'
            );

            $this->redirect(
                '/materiales/solicitudes/crear'
            );
        }

        /**
         * =========================================================
         * GUARDAR BORRADOR
         * =========================================================
         */
        public function guardarBorrador(): void
        {
            $this->exigirPermiso(
                'materiales.solicitudes.crear'
            );

            $this->validarPost();

            $this->actualizarFormularioDesdePost();

            $materialesPost = $_POST['materiales'] ?? [];

            if (is_array($materialesPost) && !empty($materialesPost)) {
                $carrito = $this->obtenerCarrito();

                foreach ($materialesPost as $idLineaPost => $materialPost) {
                    $idLineaPost = (string) $idLineaPost;

                    if (!is_array($materialPost) || !isset($carrito[$idLineaPost])) {
                        continue;
                    }

                    $cantidadPost = (float) ($materialPost['cantidad'] ?? 0);
                    if ($cantidadPost > 0) {
                        $carrito[$idLineaPost]['cantidad'] = $cantidadPost;
                    }

                    foreach (['unidad_medida', 'descripcion_tecnica', 'observaciones'] as $campo) {
                        if (array_key_exists($campo, $materialPost)) {
                            $carrito[$idLineaPost][$campo] = trim((string) ($materialPost[$campo] ?? ''));
                        }
                    }

                    if (isset($materialPost['destinos']) && is_array($materialPost['destinos'])) {
                        $carrito[$idLineaPost]['destinos'] = array_values(array_filter(
                            $materialPost['destinos'],
                            static fn($destino): bool => is_array($destino)
                                && (int) ($destino['id_ficha'] ?? 0) > 0
                        ));
                    } elseif (array_key_exists('id_ficha', $materialPost)) {
                        $carrito[$idLineaPost]['id_ficha'] = (int) $materialPost['id_ficha'];
                    }

                    foreach (['es_consumo', 'es_devolutivo', 'es_software', 'es_epp'] as $campo) {
                        if (array_key_exists($campo, $materialPost)) {
                            $carrito[$idLineaPost][$campo] = (int) ($materialPost[$campo] ? 1 : 0);
                        }
                    }
                }

                $this->guardarCarrito($carrito);
            }

            $profesional =
                $this->exigirProfesional();

            $idProfesional =
                (int) $profesional['id'];

            $idProcesoArea =
                (int) (
                    $_POST['id_proceso_area']
                    ?? 0
                );

            $titulo =
                trim(
                    (string) (
                        $_POST['titulo']
                        ?? ''
                    )
                );

            $justificacion =
                trim(
                    (string) (
                        $_POST['justificacion']
                        ?? ''
                    )
                );

            if ($idProcesoArea <= 0) {

                $this->flashError(
                    'Debe seleccionar un área responsable.'
                );

                $this->redirect(
                    '/materiales/solicitudes/crear'
                );
            }

            if ($titulo === '') {

                $this->flashError(
                    'Debe ingresar el título de la solicitud.'
                );

                $this->redirect(
                    '/materiales/solicitudes/crear'
                );
            }

            $area =
                $this->model->obtenerArea(
                    $idProcesoArea
                );

            if (
                !$area
                ||
                ($area['estado'] ?? '') !== 'activa'
            ) {

                $this->flashError(
                    'El área seleccionada no existe o no está activa.'
                );

                $this->redirect(
                    '/materiales/solicitudes/crear'
                );
            }

            $esResponsable =
                $this->model->profesionalEsResponsableArea(
                    $idProcesoArea,
                    $idProfesional
                );

            if (!$esResponsable) {

                $this->flashError(
                    'No está autorizado para realizar solicitudes en el área seleccionada.'
                );

                $this->redirect(
                    '/materiales/solicitudes/crear'
                );
            }

            $carrito =
                $this->obtenerCarrito();

            if (empty($carrito)) {

                $this->flashError(
                    'Debe agregar al menos un material a la solicitud.'
                );

                $this->redirect(
                    '/materiales/solicitudes/crear'
                );
            }

            $fichasPermitidas =
                $this->model->listarFichasAsignadasPorProfesional(
                    $idProfesional
                );

            $idsFichasPermitidas = array_map(
                static fn(array $ficha): int => (int) $ficha['id'],
                $fichasPermitidas
            );

            $detalles = [];

            foreach (
                $carrito as $idLinea => $item
            ) {

                $idMaterial =
                    (int) ($item['id_material'] ?? $item['id'] ?? 0);

                if ($idMaterial <= 0) {
                    continue;
                }

                $material =
                    $this->model->obtenerMaterialActivo(
                        $idMaterial
                    );

                if (!$material) {

                    $this->flashError(
                        'Uno de los materiales seleccionados ya no está activo. Revise el carrito.'
                    );

                    $this->redirect(
                        '/materiales/solicitudes/crear'
                    );
                }

                $cantidad = (float) ($item['cantidad'] ?? 0);

                $destinosItem = is_array($item['destinos'] ?? null)
                    ? array_values($item['destinos'])
                    : [];
                if (!$destinosItem && (int) ($item['id_ficha'] ?? 0) > 0) {
                    $destinosItem[] = [
                        'tipo_destino' => 'ficha',
                        'id_ficha' => (int) $item['id_ficha'],
                        'cantidad_destinada' => $cantidad,
                        'observaciones' => '',
                    ];
                }
                if (!$destinosItem && count($fichasPermitidas) === 1) {
                    $destinosItem[] = [
                        'tipo_destino' => 'ficha',
                        'id_ficha' => (int) $fichasPermitidas[0]['id'],
                        'cantidad_destinada' => $cantidad,
                        'observaciones' => '',
                    ];
                }
                if ($cantidad <= 0) {

                    $this->flashError(
                        'Todas las cantidades solicitadas deben ser mayores que cero.'
                    );

                    $this->redirect(
                        '/materiales/solicitudes/crear'
                    );
                }

                if (empty($destinosItem)) {
                    $this->flashError('Seleccione al menos una ficha de destino para cada material.');
                    $this->redirect('/materiales/solicitudes/crear');
                }

                $destinosValidados = [];
                $fichasUsadas = [];
                $sumaDestinos = 0.0;
                foreach ($destinosItem as $destino) {
                    if (!is_array($destino)) continue;
                    $idFichaDestino = (int) ($destino['id_ficha'] ?? 0);
                    $cantidadDestino = (float) ($destino['cantidad_destinada'] ?? 0);
                    if ($idFichaDestino <= 0 || !in_array($idFichaDestino, $idsFichasPermitidas, true)) {
                        $this->flashError('Una ficha de destino no es válida o ya no está asignada a su usuario.');
                        $this->redirect('/materiales/solicitudes/crear');
                    }
                    if (isset($fichasUsadas[$idFichaDestino])) {
                        $this->flashError('No puede repetir la misma ficha para un producto.');
                        $this->redirect('/materiales/solicitudes/crear');
                    }
                    if ($cantidadDestino <= 0) {
                        $this->flashError('La cantidad destinada a cada ficha debe ser mayor que cero.');
                        $this->redirect('/materiales/solicitudes/crear');
                    }
                    $fichasUsadas[$idFichaDestino] = true;
                    $sumaDestinos += $cantidadDestino;
                    $destinosValidados[] = [
                        'tipo_destino' => 'ficha',
                        'id_ficha' => $idFichaDestino,
                        'cantidad_destinada' => $cantidadDestino,
                        'observaciones' => trim((string) ($destino['observaciones'] ?? '')),
                    ];
                }
                if (abs($sumaDestinos - $cantidad) > 0.00001) {
                    $this->flashError('La suma de las cantidades por ficha debe ser exactamente igual a la cantidad total solicitada del material.');
                    $this->redirect('/materiales/solicitudes/crear');
                }
                $idFicha = (int) ($destinosValidados[0]['id_ficha'] ?? 0);

                $observaciones =
                    trim(
                        (string) (
                            $item['observaciones']
                            ?? ''
                        )
                    );

                // Datos específicos diligenciados para esta línea del carrito.
                $descripcionTecnica = trim(
                    (string) ($item['descripcion_tecnica'] ?? '')
                );

                $unidadMedida = trim(
                    (string) ($item['unidad_medida'] ?? '')
                );

                $detalles[] = [
                    'id_material' => $idMaterial,
                    'id_ficha' => $idFicha,
                    'destinos' => $destinosValidados,
                    'cantidad' => $cantidad,
                    'observaciones' => $observaciones,
                    'descripcion_tecnica' => $descripcionTecnica,
                    'unidad_medida' => $unidadMedida,
                    'es_consumo' => (int) ($item['es_consumo'] ?? 0),
                    'es_devolutivo' => (int) ($item['es_devolutivo'] ?? 0),
                    'es_software' => (int) ($item['es_software'] ?? 0),
                    'es_epp' => (int) ($item['es_epp'] ?? 0),
                ];
            }

            if (empty($detalles)) {

                $this->flashError(
                    'No hay materiales válidos para guardar.'
                );

                $this->redirect(
                    '/materiales/solicitudes/crear'
                );
            }

            try {

                $idSolicitud =
                    $this->model->crearBorrador(
                        $idProcesoArea,
                        $idProfesional,
                        $titulo,
                        $justificacion !== ''
                            ? $justificacion
                            : null
                    );

                foreach ($detalles as $detalle) {

                    $idDetalleNuevo = $this->model->agregarDetalle(
                        $idSolicitud,
                        (int) $detalle['id_material'],
                        (float) $detalle['cantidad'],
                        $detalle['observaciones'] !== ''
                            ? $detalle['observaciones']
                            : null,
                        $detalle['unidad_medida'] !== ''
                            ? $detalle['unidad_medida']
                            : 'Unidad',
                        $detalle['descripcion_tecnica'],
                        (int) $detalle['es_consumo'],
                        (int) $detalle['es_devolutivo'],
                        (int) $detalle['es_software'],
                        (int) $detalle['es_epp'],
                        (int) $detalle['id_ficha']
                    );

                    foreach (($detalle['destinos'] ?? []) as $destino) {
                        $this->model->agregarDestino(
                            $idDetalleNuevo,
                            'ficha',
                            (int) $destino['id_ficha'],
                            (float) $destino['cantidad_destinada'],
                            trim((string) ($destino['observaciones'] ?? '')) !== ''
                                ? trim((string) $destino['observaciones'])
                                : null
                        );
                    }
                }

                $idUsuario =
                    $this->obtenerUsuarioActualId();

                $this->model->registrarHistorial(
                    $idSolicitud,
                    null,
                    'borrador',
                    $idUsuario,
                    'Solicitud creada como borrador.'
                );

                $this->guardarCarrito([]);

                $this->limpiarFormularioTemporal();

                $this->flashSuccess(
                    'La solicitud fue guardada correctamente como borrador.'
                );

                $this->redirect(
                    '/materiales/solicitudes'
                );

            } catch (Throwable $e) {

                $this->flashError(
                    'No fue posible guardar la solicitud. Verifique la información e inténtelo nuevamente.'
                );

                $this->redirect(
                    '/materiales/solicitudes/crear'
                );
            }
        }

        /**
         * =========================================================
         * VER SOLICITUD
         * =========================================================
         */
        public function ver(): void
        {
            $idSolicitud =
                (int) (
                    $_GET['id']
                    ?? $_GET['id_solicitud']
                    ?? 0
                );

            if ($idSolicitud <= 0) {

                $this->error(
                    'Solicitud no válida.'
                );
            }

            $solicitud =
                $this->model->obtenerSolicitud(
                    $idSolicitud
                );

            if (!$solicitud) {

                $this->error(
                    'La solicitud no existe.'
                );
            }

            $profesional =
                $this->profesionalActual();

            $idProfesionalActual =
                $profesional
                    ? (int) $profesional['id']
                    : 0;

            $esPropietario =
                $idProfesionalActual > 0
                &&
                (int) (
                    $solicitud[
                        'id_profesional_solicitante'
                    ] ?? 0
                ) === $idProfesionalActual;

            $esAdministrador =
                Auth::hasRole('Administrador');

            $puedeRevisar =
                false;

            if (
                !$esPropietario
                &&
                !$esAdministrador
                &&
                $idProfesionalActual > 0
            ) {

                $puedeRevisar =
                    $this->model->coordinadorPuedeRevisarSolicitud(
                        $idSolicitud,
                        $idProfesionalActual
                    );
            }

            if (
                !$esPropietario
                &&
                !$esAdministrador
                &&
                !$puedeRevisar
            ) {

                $this->error(
                    'No tiene autorización para consultar esta solicitud.'
                );
            }

            /*
             * =====================================================
             * RUTA DEL BOTÓN VOLVER
             * =====================================================
             *
             * El solicitante vuelve a "Mis solicitudes".
             * El Administrador o Coordinador que revisa
             * vuelve a la bandeja de aval, porque no siempre
             * tienen un profesional activo asociado.
             */
            $rutaVolver =
                $esPropietario
                    ? '/materiales/solicitudes'
                    : '/materiales/solicitudes/aval';

            $detalles =
                $this->model->listarDetalles(
                    $idSolicitud
                );

            foreach ($detalles as &$detalle) {

                $idDetalle =
                    (int) (
                        $detalle['id']
                        ?? $detalle['id_detalle']
                        ?? 0
                    );

                $detalle['destinos'] = [];

                if ($idDetalle > 0) {

                    $detalle['destinos'] =
                        $this->model->listarDestinosPorDetalle(
                            $idDetalle
                        );
                }
            }

            unset($detalle);

            require ROOT_PATH .
                '/views/solicitudes/ver.php';
        }

        /**
         * =========================================================
         * ENVIAR SOLICITUD A AVAL
         * =========================================================
         */
        public function enviar(): void
        {
            $this->exigirPermiso(
                'materiales.solicitudes.crear'
            );

            $this->validarPost();

            $profesional =
                $this->exigirProfesional();

            $idProfesional =
                (int) $profesional['id'];

            $idSolicitud =
                (int) (
                    $_POST['id_solicitud']
                    ?? $_POST['id']
                    ?? $_POST['solicitud_id']
                    ?? 0
                );

            if ($idSolicitud <= 0) {

                $this->flashError(
                    'Solicitud no válida.'
                );

                $this->redirect(
                    '/materiales/solicitudes'
                );
            }

            $solicitud =
                $this->model->obtenerSolicitud(
                    $idSolicitud
                );

            if (!$solicitud) {

                $this->flashError(
                    'La solicitud no existe.'
                );

                $this->redirect(
                    '/materiales/solicitudes'
                );
            }

            if (
                (int) (
                    $solicitud[
                        'id_profesional_solicitante'
                    ] ?? 0
                ) !== $idProfesional
            ) {

                $this->flashError(
                    'No está autorizado para enviar esta solicitud.'
                );

                $this->redirect(
                    '/materiales/solicitudes'
                );
            }

            $estadoActual =
                (string) (
                    $solicitud['estado']
                    ?? ''
                );

            if (
                !in_array(
                    $estadoActual,
                    [
                        'borrador',
                        'devuelta',
                    ],
                    true
                )
            ) {

                $this->flashError(
                    'La solicitud no puede ser enviada en su estado actual.'
                );

                $this->redirect(
                    '/materiales/solicitudes/ver?id=' .
                    $idSolicitud
                );
            }

            $detalles =
                $this->model->listarDetalles(
                    $idSolicitud
                );

            if (empty($detalles)) {

                $this->flashError(
                    'No se puede enviar una solicitud sin materiales.'
                );

                $this->redirect(
                    '/materiales/solicitudes/ver?id=' .
                    $idSolicitud
                );
            }

            foreach ($detalles as $detalle) {

                $cantidad =
                    (float) (
                        $detalle[
                            'cantidad_solicitada'
                        ]
                        ?? 0
                    );

                if ($cantidad <= 0) {

                    $this->flashError(
                        'Todas las cantidades solicitadas deben ser mayores que cero.'
                    );

                    $this->redirect(
                        '/materiales/solicitudes/ver?id=' .
                        $idSolicitud
                    );
                }

                $idMaterial =
                    (int) (
                        $detalle['id_material']
                        ?? 0
                    );

                if (
                    $idMaterial <= 0
                    ||
                    !$this->model->obtenerMaterialActivo(
                        $idMaterial
                    )
                ) {

                    $this->flashError(
                        'Uno de los materiales de la solicitud ya no está activo.'
                    );

                    $this->redirect(
                        '/materiales/solicitudes/ver?id=' .
                        $idSolicitud
                    );
                }
            }

            $idUsuario =
                $this->obtenerUsuarioActualId();

            $observacion =
                $estadoActual === 'devuelta'
                    ? 'Solicitud corregida y reenviada para aval.'
                    : 'Solicitud enviada para aval.';

            $resultado =
                $this->model->cambiarEstado(
                    $idSolicitud,
                    'pendiente_aval',
                    $idUsuario,
                    $observacion
                );

            if (!$resultado) {

                $this->flashError(
                    'No fue posible enviar la solicitud para aval.'
                );

                $this->redirect(
                    '/materiales/solicitudes/ver?id=' .
                    $idSolicitud
                );
            }

            $this->flashSuccess(
                'La solicitud fue enviada correctamente y quedó pendiente de aval.'
            );

            $this->redirect(
                '/materiales/solicitudes'
            );
        }

        /**
         * =========================================================
         * EDITAR BORRADOR O SOLICITUD DEVUELTA
         * =========================================================
         */
        public function editar(): void
        {
            $this->exigirPermiso(
                'materiales.solicitudes.crear'
            );

            $idSolicitud =
                (int) (
                    $_GET['id']
                    ?? $_GET['id_solicitud']
                    ?? 0
                );

            if ($idSolicitud <= 0) {

                $this->error(
                    'Solicitud no válida.'
                );
            }

            $solicitud =
                $this->model->obtenerSolicitud(
                    $idSolicitud
                );

            if (!$solicitud) {

                $this->error(
                    'La solicitud no existe.'
                );
            }

            /*
             * =====================================================
             * SOLO BORRADORES O DEVUELTAS
             * =====================================================
             */
            if (
                !in_array(
                    ($solicitud['estado'] ?? ''),
                    [
                        'borrador',
                        'devuelta',
                    ],
                    true
                )
            ) {

                $this->error(
                    'Solo se pueden editar solicitudes en estado borrador o devuelta.'
                );
            }

            /*
             * =====================================================
             * AUTORIZACIÓN
             * =====================================================
             *
             * El Coordinador NO edita solicitudes de terceros.
             * Solamente el profesional solicitante o Administrador.
             */
            $profesional =
                $this->profesionalActual();

            $idProfesionalActual =
                $profesional
                    ? (int) $profesional['id']
                    : 0;

            $esPropietario =
                $idProfesionalActual > 0
                &&
                (int) (
                    $solicitud[
                        'id_profesional_solicitante'
                    ] ?? 0
                ) === $idProfesionalActual;

            $esAdministrador =
                Auth::hasRole('Administrador');

            if (
                !$esPropietario
                &&
                !$esAdministrador
            ) {

                $this->error(
                    'No tiene autorización para editar esta solicitud.'
                );
            }

            $detalles =
                $this->model->listarDetalles(
                    $idSolicitud
                );

            foreach ($detalles as &$detalle) {

                $idDetalle =
                    (int) (
                        $detalle['id']
                        ?? $detalle['id_detalle']
                        ?? 0
                    );

                $detalle['destinos'] = [];

                if ($idDetalle > 0) {

                    $detalle['destinos'] =
                        $this->model->listarDestinosPorDetalle(
                            $idDetalle
                        );
                }
            }

            unset($detalle);

            $materiales =
                $this->model->listarMaterialesActivos(
                    '',
                    100,
                    0
                );

            // Administrador puede ver todas las fichas activas.
            // Los demás profesionales solo ven las fichas que tienen
            // asignadas como LIDER_FICHA y cuya asignación está vigente.
            $fichasDestino = $esAdministrador
                ? $this->model->listarFichasDisponibles()
                : $this->model->listarFichasDestinoPorProfesional(
                    $idProfesionalActual
                );

            $fichas = $fichasDestino;

            $fichasDestinoIds = array_map(
                static fn($f): int => (int) ($f['id'] ?? 0),
                $fichasDestino
            );

            $puedeGestionar = true;

            require ROOT_PATH .
                '/views/solicitudes/editar.php';
        }

        /**
         * =========================================================
         * ACTUALIZAR BORRADOR O SOLICITUD DEVUELTA
         * =========================================================
         */
        public function actualizarBorrador(): void
        {
            $this->exigirPermiso(
                'materiales.solicitudes.crear'
            );

            $this->validarPost();

            $idSolicitud =
                (int) (
                    $_POST['id_solicitud']
                    ?? $_POST['id']
                    ?? $_POST['solicitud_id']
                    ?? 0
                );

            if ($idSolicitud <= 0) {

                $this->flashError(
                    'Solicitud no válida.'
                );

                $this->redirect(
                    '/materiales/solicitudes'
                );
            }

            $solicitud =
                $this->model->obtenerSolicitud(
                    $idSolicitud
                );

            if (!$solicitud) {

                $this->flashError(
                    'La solicitud no existe.'
                );

                $this->redirect(
                    '/materiales/solicitudes'
                );
            }

            if (
                !in_array(
                    ($solicitud['estado'] ?? ''),
                    [
                        'borrador',
                        'devuelta',
                    ],
                    true
                )
            ) {

                $this->flashError(
                    'La solicitud ya no puede ser modificada.'
                );

                $this->redirect(
                    '/materiales/solicitudes/ver?id=' .
                    $idSolicitud
                );
            }

            $profesional =
                $this->profesionalActual();

            $idProfesionalActual =
                $profesional
                    ? (int) $profesional['id']
                    : 0;

            $esPropietario =
                $idProfesionalActual > 0
                &&
                (int) (
                    $solicitud[
                        'id_profesional_solicitante'
                    ] ?? 0
                ) === $idProfesionalActual;

            $esAdministrador =
                Auth::hasRole('Administrador');

            if (
                !$esPropietario
                &&
                !$esAdministrador
            ) {

                $this->flashError(
                    'No está autorizado para modificar esta solicitud.'
                );

                $this->redirect(
                    '/materiales/solicitudes'
                );
            }

            // Validar los destinos con el mismo filtro que se muestra en edición.
            $fichasPermitidasDestino = $esAdministrador
                ? $this->model->listarFichasDisponibles()
                : $this->model->listarFichasDestinoPorProfesional(
                    $idProfesionalActual
                );
            $fichasDestinoIds = array_map(
                static fn($f): int => (int) ($f['id'] ?? 0),
                $fichasPermitidasDestino
            );

            $tituloOriginal =
                trim(
                    (string) (
                        $solicitud['titulo']
                        ?? ''
                    )
                );

            $justificacionOriginal =
                trim(
                    (string) (
                        $solicitud['justificacion']
                        ?? ''
                    )
                );

            $materialesPost =
                $_POST['materiales']
                ?? [];

            if (
                !is_array($materialesPost)
                ||
                empty($materialesPost)
            ) {

                $this->flashError(
                    'Debe tener al menos un material en la solicitud.'
                );

                $this->redirect(
                    '/materiales/solicitudes/editar?id=' .
                    $idSolicitud
                );
            }

            $detalles = [];

            foreach (
                $materialesPost as $materialPost
            ) {

                if (
                    !is_array($materialPost)
                ) {
                    continue;
                }

                $eliminar =
                    isset(
                        $materialPost['eliminar']
                    )
                    &&
                    (
                        $materialPost['eliminar'] === '1'
                        ||
                        $materialPost['eliminar'] === 'on'
                    );

                if ($eliminar) {
                    continue;
                }

                $idMaterial =
                    (int) (
                        $materialPost['id_material']
                        ?? 0
                    );

                $cantidad =
                    (float) (
                        $materialPost['cantidad']
                        ?? 0
                    );

                $observaciones =
                    trim(
                        (string) (
                            $materialPost['observaciones']
                            ?? ''
                        )
                    );

                if (
                    $idMaterial <= 0
                    ||
                    $cantidad <= 0
                ) {

                    $this->flashError(
                        'Todos los materiales deben tener una cantidad mayor que cero.'
                    );

                    $this->redirect(
                        '/materiales/solicitudes/editar?id=' .
                        $idSolicitud
                    );
                }

                $material =
                    $this->model->obtenerMaterialActivo(
                        $idMaterial
                    );

                if (!$material) {

                    $this->flashError(
                        'Uno de los materiales seleccionados ya no está activo.'
                    );

                    $this->redirect(
                        '/materiales/solicitudes/editar?id=' .
                        $idSolicitud
                    );
                }

                $tieneDestinosPost = array_key_exists('destinos', $materialPost);
                $destinosPost = $tieneDestinosPost
                    ? $materialPost['destinos']
                    : null;

                if ($tieneDestinosPost && !is_array($destinosPost)) {
                    $this->flashError('La información de destinos no es válida.');
                    $this->redirect('/materiales/solicitudes/editar?id=' . $idSolicitud);
                }

                $destinos = [];
                $fichasUsadasEnFila = [];

                $totalDestinado = 0.0;

                foreach (
                    ($tieneDestinosPost ? $destinosPost : []) as $destinoPost
                ) {

                    if (
                        !is_array($destinoPost)
                    ) {
                        continue;
                    }

                    $cantidadDestino =
                        (float) (
                            $destinoPost[
                                'cantidad_destinada'
                            ]
                            ?? 0
                        );

                    if (
                        $cantidadDestino <= 0
                    ) {
                        continue;
                    }

                    $tipoDestino =
                        trim(
                            (string) (
                                $destinoPost[
                                    'tipo_destino'
                                ]
                                ?? ''
                            )
                        );

                    if (
                        !in_array(
                            $tipoDestino,
                            [
                                'ficha',
                                'general_area',
                            ],
                            true
                        )
                    ) {

                        $this->flashError(
                            'Existe un destino con tipo no válido.'
                        );

                        $this->redirect(
                            '/materiales/solicitudes/editar?id=' .
                            $idSolicitud
                        );
                    }

                    $idFicha = null;

                    if (
                        $tipoDestino === 'ficha'
                    ) {

                        $idFicha =
                            (int) (
                                $destinoPost[
                                    'id_ficha'
                                ]
                                ?? 0
                            );

                        if (
                            $idFicha <= 0
                            || !in_array($idFicha, $fichasDestinoIds, true)
                        ) {

                            $this->flashError(
                                'Debe seleccionar una ficha autorizada para cada destino.'
                            );

                            $this->redirect(
                                '/materiales/solicitudes/editar?id=' .
                                $idSolicitud
                            );
                        }

                        if (isset($fichasUsadasEnFila[$idFicha])) {
                            $this->flashError(
                                'No puede repetir la misma ficha en los destinos de un producto. Distribuya la cantidad en una sola línea por ficha.'
                            );
                            $this->redirect(
                                '/materiales/solicitudes/editar?id=' . $idSolicitud
                            );
                        }
                        $fichasUsadasEnFila[$idFicha] = true;
                    }

                    $totalDestinado +=
                        $cantidadDestino;

                    if (
                        $totalDestinado - $cantidad > 0.00001
                    ) {

                        $this->flashError(
                            'La suma de las cantidades por ficha no puede superar la cantidad total solicitada para el material.'
                        );

                        $this->redirect(
                            '/materiales/solicitudes/editar?id=' .
                            $idSolicitud
                        );
                    }

                    $destinos[] = [
                        'tipo_destino' =>
                            $tipoDestino,

                        'id_ficha' =>
                            $idFicha,

                        'cantidad_destinada' =>
                            $cantidadDestino,

                        'observaciones' =>
                            trim(
                                (string) (
                                    $destinoPost[
                                        'observaciones'
                                    ]
                                    ?? ''
                                )
                            ),
                    ];
                }

                if (!empty($destinos) && abs($totalDestinado - $cantidad) > 0.00001) {
                    $this->flashError(
                        'La suma de las cantidades por ficha debe ser exactamente igual a la cantidad total solicitada para el material.'
                    );
                    $this->redirect('/materiales/solicitudes/editar?id=' . $idSolicitud);
                }

                if (empty($destinos)) {
                    $this->flashError(
                        'Debe asignar al menos una ficha de destino a cada producto.'
                    );
                    $this->redirect(
                        '/materiales/solicitudes/editar?id=' . $idSolicitud
                    );
                }

                // Conservar el ID de la línea para actualizar el detalle existente.
                // Los campos técnicos y de clasificación son obligatorios para el modelo.
                $idDetalle = (int) (
                    $materialPost['id_detalle']
                    ?? 0
                );

                $unidadMedida = trim(
                    (string) (
                        $materialPost['unidad_medida']
                        ?? 'Unidad'
                    )
                );

                $descripcionTecnica = trim(
                    (string) (
                        $materialPost['descripcion_tecnica']
                        ?? ''
                    )
                );

                if ($descripcionTecnica === '') {
                    $this->flashError(
                        'La descripción técnica es obligatoria para cada material.'
                    );

                    $this->redirect(
                        '/materiales/solicitudes/editar?id=' .
                        $idSolicitud
                    );
                }

                $detalles[] = [
                    'id_detalle' => $idDetalle,
                    'id_material' => $idMaterial,
                    'cantidad' => $cantidad,
                    'observaciones' => $observaciones,
                    'unidad_medida' => $unidadMedida !== '' ? $unidadMedida : 'Unidad',
                    'descripcion_tecnica' => $descripcionTecnica,
                    'es_consumo' => !empty($materialPost['es_consumo']) ? 1 : 0,
                    'es_devolutivo' => !empty($materialPost['es_devolutivo']) ? 1 : 0,
                    'es_software' => !empty($materialPost['es_software']) ? 1 : 0,
                    'es_epp' => !empty($materialPost['es_epp']) ? 1 : 0,
                ];

                // Si la vista no envió destinos, el modelo los conserva sin borrarlos.
                if ($tieneDestinosPost) {
                    $detalles[count($detalles) - 1]['destinos'] = $destinos;
                }
            }

            if (empty($detalles)) {

                $this->flashError(
                    'La solicitud debe conservar al menos un material.'
                );

                $this->redirect(
                    '/materiales/solicitudes/editar?id=' .
                    $idSolicitud
                );
            }

            $resultado =
                $this->model->actualizarBorradorCompleto(
                    $idSolicitud,
                    $tituloOriginal,
                    $justificacionOriginal !== ''
                        ? $justificacionOriginal
                        : null,
                    $detalles
                );

            if (!$resultado) {

                $this->flashError(
                    'No fue posible actualizar el borrador. Verifique la información e inténtelo nuevamente.'
                );

                $this->redirect(
                    '/materiales/solicitudes/editar?id=' .
                    $idSolicitud
                );
            }

            /*
             * Si la solicitud estaba devuelta, conservamos
             * el estado devuelta hasta que el solicitante
             * decida enviarla nuevamente.
             */
            $this->flashSuccess(
                'Los materiales de la solicitud fueron actualizados correctamente.'
            );

            $this->redirect(
                '/materiales/solicitudes/ver?id=' .
                $idSolicitud
            );
        }

        /**
         * =========================================================
         * ELIMINAR BORRADOR
         * =========================================================
         */
        public function eliminarBorrador(): void
        {
            $this->exigirPermiso(
                'materiales.solicitudes.crear'
            );

            $this->validarPost();

            $idSolicitud =
                (int) (
                    $_POST['id_solicitud']
                    ?? $_POST['id']
                    ?? $_POST['solicitud_id']
                    ?? $_POST['idSolicitud']
                    ?? $_GET['id_solicitud']
                    ?? $_GET['id']
                    ?? 0
                );

            if ($idSolicitud <= 0) {

                $this->flashError(
                    'Solicitud no válida.'
                );

                $this->redirect(
                    '/materiales/solicitudes'
                );
            }

            $solicitud =
                $this->model->obtenerSolicitud(
                    $idSolicitud
                );

            if (!$solicitud) {

                $this->flashError(
                    'La solicitud no existe.'
                );

                $this->redirect(
                    '/materiales/solicitudes'
                );
            }

            if (
                ($solicitud['estado'] ?? '')
                !== 'borrador'
            ) {

                $this->flashError(
                    'Solo se pueden eliminar solicitudes en estado borrador.'
                );

                $this->redirect(
                    '/materiales/solicitudes/ver?id=' .
                    $idSolicitud
                );
            }

            $profesional =
                $this->profesionalActual();

            $idProfesionalActual =
                $profesional
                    ? (int) (
                        $profesional['id']
                        ?? 0
                    )
                    : 0;

            $idProfesionalSolicitante =
                (int) (
                    $solicitud[
                        'id_profesional_solicitante'
                    ]
                    ??
                    $solicitud[
                        'id_profesional'
                    ]
                    ??
                    0
                );

            $esPropietario =
                $idProfesionalActual > 0
                &&
                $idProfesionalSolicitante ===
                    $idProfesionalActual;

            $esAdministrador =
                Auth::hasRole('Administrador');

            if (
                !$esPropietario
                &&
                !$esAdministrador
            ) {

                $this->flashError(
                    'No está autorizado para eliminar esta solicitud.'
                );

                $this->redirect(
                    '/materiales/solicitudes'
                );
            }

            $resultado =
                $this->model->eliminarBorrador(
                    $idSolicitud
                );

            if (!$resultado) {

                $this->flashError(
                    'No fue posible eliminar el borrador.'
                );

                $this->redirect(
                    '/materiales/solicitudes/ver?id=' .
                    $idSolicitud
                );
            }

            $this->flashSuccess(
                'La solicitud fue eliminada correctamente.'
            );

            $this->redirect(
                '/materiales/solicitudes'
            );
        }

        /**
         * =========================================================
         * BANDEJA DE SOLICITUDES PENDIENTES DE AVAL
         * =========================================================
         */
        public function aval(): void
        {
            $this->exigirPermiso(
                'materiales.solicitudes.gestionar'
            );

            $esAdministrador =
                Auth::hasRole('Administrador');

            $idProfesional = 0;

            /*
             * El Administrador puede consultar todas las
             * solicitudes pendientes y no necesita tener
             * un profesional activo asociado.
             *
             * El Coordinador solamente puede consultar las
             * solicitudes pertenecientes a procesos donde él
             * está configurado como coordinador.
             */
            if (!$esAdministrador) {

                $profesional =
                    $this->profesionalActual();

                $idProfesional =
                    $profesional
                        ? (int) $profesional['id']
                        : 0;

                if ($idProfesional <= 0) {
                    $this->error(
                        'El usuario actual no tiene un profesional activo asociado.'
                    );
                }
            }

            $solicitudes =
                $this->model->listarPendientesAval(
                    $esAdministrador
                        ? null
                        : $idProfesional
                );

            $tituloPagina =
                'Aval de solicitudes';

            require ROOT_PATH .
                '/views/solicitudes/aval.php';
        }

        /**
         * =========================================================
         * AVALAR SOLICITUD
         * =========================================================
         */
        public function avalar(): void
        {
            $this->exigirPermiso(
                'materiales.solicitudes.gestionar'
            );

            $this->validarPost();

            $idSolicitud =
                (int) (
                    $_POST['id_solicitud']
                    ?? $_POST['id']
                    ?? 0
                );

            $observacion =
                trim(
                    (string) (
                        $_POST['observacion']
                        ?? ''
                    )
                );

            if ($idSolicitud <= 0) {

                $this->flashError(
                    'Solicitud no válida.'
                );

                $this->redirect(
                    '/materiales/solicitudes/aval'
                );
            }

            $profesional =
                $this->profesionalActual();

            $idProfesional =
                $profesional
                    ? (int) $profesional['id']
                    : 0;

            $esAdministrador =
                Auth::hasRole('Administrador');

            if (
                !$esAdministrador
                &&
                $idProfesional <= 0
            ) {
                $this->error(
                    'El usuario actual no tiene un profesional activo asociado.'
                );
            }

            if (
                !$esAdministrador
                &&
                !$this->model->coordinadorPuedeRevisarSolicitud(
                    $idSolicitud,
                    $idProfesional
                )
            ) {

                $this->flashError(
                    'No está autorizado para avalar esta solicitud.'
                );

                $this->redirect(
                    '/materiales/solicitudes/aval'
                );
            }

            $idUsuario =
                $this->obtenerUsuarioActualId();

            $resultado =
                $this->model->registrarDecisionAval(
                    $idSolicitud,
                    'avalada',
                    $idUsuario,
                    $observacion !== ''
                        ? $observacion
                        : null
                );

            if (!$resultado) {

                $this->flashError(
                    'No fue posible avalar la solicitud. Es posible que ya haya sido revisada.'
                );

                $this->redirect(
                    '/materiales/solicitudes/aval'
                );
            }

            $this->flashSuccess(
                'La solicitud fue avalada correctamente.'
            );

            $this->redirect(
                '/materiales/solicitudes/aval'
            );
        }

        /**
         * =========================================================
         * DEVOLVER SOLICITUD
         * =========================================================
         */
        public function devolver(): void
        {
            $this->exigirPermiso(
                'materiales.solicitudes.gestionar'
            );

            $this->validarPost();

            $idSolicitud =
                (int) (
                    $_POST['id_solicitud']
                    ?? $_POST['id']
                    ?? 0
                );

            $observacion =
                trim(
                    (string) (
                        $_POST['observacion']
                        ?? ''
                    )
                );

            if ($idSolicitud <= 0) {

                $this->flashError(
                    'Solicitud no válida.'
                );

                $this->redirect(
                    '/materiales/solicitudes/aval'
                );
            }

            if ($observacion === '') {

                $this->flashError(
                    'Debe indicar la observación o motivo de la devolución.'
                );

                $this->redirect(
                    '/materiales/solicitudes/aval'
                );
            }

            $profesional =
                $this->profesionalActual();

            $idProfesional =
                $profesional
                    ? (int) $profesional['id']
                    : 0;

            $esAdministrador =
                Auth::hasRole('Administrador');

            if (
                !$esAdministrador
                &&
                $idProfesional <= 0
            ) {
                $this->error(
                    'El usuario actual no tiene un profesional activo asociado.'
                );
            }

            if (
                !$esAdministrador
                &&
                !$this->model->coordinadorPuedeRevisarSolicitud(
                    $idSolicitud,
                    $idProfesional
                )
            ) {

                $this->flashError(
                    'No está autorizado para devolver esta solicitud.'
                );

                $this->redirect(
                    '/materiales/solicitudes/aval'
                );
            }

            $idUsuario =
                $this->obtenerUsuarioActualId();

            $resultado =
                $this->model->registrarDecisionAval(
                    $idSolicitud,
                    'devuelta',
                    $idUsuario,
                    $observacion
                );

            if (!$resultado) {

                $this->flashError(
                    'No fue posible devolver la solicitud. Es posible que ya haya sido revisada.'
                );

                $this->redirect(
                    '/materiales/solicitudes/aval'
                );
            }

            $this->flashSuccess(
                'La solicitud fue devuelta al solicitante para su corrección.'
            );

            $this->redirect(
                '/materiales/solicitudes/aval'
            );
        }

        /**
         * =========================================================
         * EXPORTAR SOLICITUD APROBADA AL FORMATO GFPI-F-186
         * =========================================================
         * Solo permite exportar solicitudes con estado "avalada".
         * Conserva cada línea del detalle sin agrupar códigos UNSPSC.
         */
        

public function exportarGFPI(): void
{
    $idSolicitud = (int) (
        $_GET['id']
        ?? $_GET['id_solicitud']
        ?? 0
    );

    if ($idSolicitud <= 0) {
        $this->error('La solicitud indicada no es válida.');
    }

    $solicitud = $this->model->obtenerSolicitud($idSolicitud);

    if (!$solicitud) {
        $this->error('La solicitud solicitada no existe.');
    }

    // La exportación solo se permite después del aval.
    if (
        strtolower(trim((string) ($solicitud['estado'] ?? '')))
        !== 'avalada'
    ) {
        http_response_code(403);
        exit(
            'El formato GFPI-F-186 solo se puede generar cuando '
            . 'Coordinación Académica haya aprobado la solicitud.'
        );
    }

    // Validar los permisos del usuario.
    $profesional = $this->profesionalActual();

    $idProfesionalActual = $profesional
        ? (int) ($profesional['id'] ?? 0)
        : 0;

    $esAdministrador = Auth::hasRole('Administrador');

    $esPropietario = $idProfesionalActual > 0
        && $idProfesionalActual
            === (int) (
                $solicitud['id_profesional_solicitante'] ?? 0
            );

    $esCoordinadorAsignado = $idProfesionalActual > 0
        && $idProfesionalActual
            === (int) ($solicitud['id_coordinador'] ?? 0);

    if (
        !$esAdministrador
        && !$esPropietario
        && !$esCoordinadorAsignado
    ) {
        $this->error(
            'No tiene autorización para descargar el formato '
            . 'de esta solicitud.'
        );
    }

    // Cargar PhpSpreadsheet.
    $autoload = ROOT_PATH . '/vendor/autoload.php';

    if (!is_file($autoload)) {
        http_response_code(500);
        exit('No se encontró vendor/autoload.php.');
    }

    require_once $autoload;

    if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
        http_response_code(500);
        exit('PhpSpreadsheet no está disponible.');
    }

    // Obtener los materiales de la solicitud.
    $detalles = $this->model->listarDetalles($idSolicitud);

    if (!$detalles) {
        $this->error(
            'La solicitud no tiene materiales para exportar.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Cargar la plantilla institucional original
    |--------------------------------------------------------------------------
    | Se conservan sus encabezados, combinaciones de celdas,
    | instrucciones, estilos y formato de impresión.
    */

    $rutaPlantilla = ROOT_PATH
        . '/Copia de GFPI-F-186 Formato Lista Maestra Archivo 2026.xlsx';

    if (!is_file($rutaPlantilla)) {
        http_response_code(500);
        exit(
            'No se encontró la plantilla GFPI-F-186. '
            . 'Verifique que el archivo esté en la raíz del proyecto.'
        );
    }

    try {
        $libro = \PhpOffice\PhpSpreadsheet\IOFactory::load(
            $rutaPlantilla
        );

        $hoja = $libro->getActiveSheet();

        /*
        |--------------------------------------------------------------------------
        | Ubicación real de las columnas según la plantilla
        |--------------------------------------------------------------------------
        | Encabezados: fila 11.
        | Instrucciones: fila 12.
        | Primera fila de materiales: fila 13.
        */

        $filaInicial = 13;
        $fila = $filaInicial;

        foreach ($detalles as $indice => $detalle) {

            // C: ÍTEM.
            $hoja->setCellValue(
                'C' . $fila,
                $indice + 1
            );

            // D: CÓDIGO UNSPSC.
            // Se conserva como texto para evitar alterar el código.
            $hoja->setCellValueExplicit(
                'D' . $fila,
                (string) ($detalle['codigo_unspsc'] ?? ''),
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );

            // E: PRODUCTO.
            $hoja->setCellValue(
                'E' . $fila,
                (string) ($detalle['descripcion'] ?? '')
            );

            // F: DESCRIPCIÓN TÉCNICA REQUERIDA DEL BIEN.
            $hoja->setCellValue(
                'F' . $fila,
                (string) ($detalle['descripcion_tecnica'] ?? '')
            );

            // G: UNIDAD DE MEDIDA.
            $hoja->setCellValue(
                'G' . $fila,
                (string) ($detalle['unidad_medida'] ?? '')
            );

            // H: CANTIDAD REQUERIDA.
            $hoja->setCellValue(
                'H' . $fila,
                (float) ($detalle['cantidad_solicitada'] ?? 0)
            );

            // I: OBSERVACIONES.
            $hoja->setCellValue(
                'I' . $fila,
                (string) ($detalle['observaciones'] ?? '')
            );

            // J: columna separadora; no se escribe ningún dato.

            // K: CONSUMO.
            $hoja->setCellValue(
                'K' . $fila,
                !empty($detalle['es_consumo']) ? 'X' : ''
            );

            // L: DEVOLUTIVO.
            $hoja->setCellValue(
                'L' . $fila,
                !empty($detalle['es_devolutivo']) ? 'X' : ''
            );

            // M: SOFTWARE.
            $hoja->setCellValue(
                'M' . $fila,
                !empty($detalle['es_software']) ? 'X' : ''
            );

            // N: EPP.
            $hoja->setCellValue(
                'N' . $fila,
                !empty($detalle['es_epp']) ? 'X' : ''
            );

            $fila++;
        }

        /*
        |--------------------------------------------------------------------------
        | Presentación de las filas de materiales
        |--------------------------------------------------------------------------
        | Se aplican ajustes únicamente a las celdas de datos.
        | No se reconstruyen los encabezados ni los estilos de la plantilla.
        */

        $ultimaFila = $fila - 1;

        $hoja->getStyle(
            'C' . $filaInicial . ':N' . $ultimaFila
        )->getAlignment()
            ->setWrapText(true)
            ->setVertical(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP
            );

        $hoja->getStyle(
            'C' . $filaInicial . ':C' . $ultimaFila
        )->getAlignment()
            ->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );

        $hoja->getStyle(
            'H' . $filaInicial . ':N' . $ultimaFila
        )->getAlignment()
            ->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );

        /*
        |--------------------------------------------------------------------------
        | Generar archivo descargable
        |--------------------------------------------------------------------------
        */

        $numero = preg_replace(
            '/[^A-Za-z0-9_-]/',
            '_',
            (string) (
                $solicitud['numero_solicitud']
                ?? $solicitud['id']
                ?? $idSolicitud
            )
        );

        if ($numero === '') {
            $numero = (string) $idSolicitud;
        }

        $nombreArchivo = 'GFPI-F-186_Solicitud_'
            . $numero
            . '.xlsx';

        // Limpiar cualquier salida previa que pueda dañar el Excel.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header(
            'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        header(
            'Content-Disposition: attachment; filename="'
            . $nombreArchivo
            . '"'
        );

        header('Cache-Control: max-age=0');
        header('Pragma: public');

        $escritor = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx(
            $libro
        );

        $escritor->save('php://output');

        $libro->disconnectWorksheets();

        exit;

    } catch (\Throwable $e) {
        http_response_code(500);

        exit(
            'Error al generar el formato GFPI-F-186: '
            . htmlspecialchars(
                $e->getMessage(),
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }
}





        /**
         * Bandeja de solicitudes avaladas para consolidación de materiales.
         */
        public function consolidar(): void
        {
            $this->exigirPermiso('materiales.solicitudes.gestionar');
            $profesional = $this->profesionalActual();
            $idProfesional = $profesional ? (int) ($profesional['id'] ?? 0) : 0;
            if ($idProfesional <= 0) {
                $this->error('El usuario actual no tiene un profesional activo asociado.');
            }
            $solicitudes = $this->model->listarAvaladasParaConsolidar($idProfesional);
            $tituloPagina = 'Consolidar materiales';
            require ROOT_PATH . '/views/solicitudes/consolidar.php';
        }

        /**
         * Genera un único GFPI-F-186 a partir de varias solicitudes avaladas.
         * Conserva cada detalle como una fila para no omitir productos.
         */
        public function exportarConsolidadoGFPI(): void
        {
            $this->exigirPermiso('materiales.solicitudes.gestionar');
            $this->validarPost();

            $profesional = $this->profesionalActual();
            $idProfesional = $profesional ? (int) ($profesional['id'] ?? 0) : 0;

            if ($idProfesional <= 0) {
                $this->error('El usuario actual no tiene un profesional activo asociado.');
            }

            $idsRecibidos = $_POST['ids_solicitud'] ?? [];
            if (!is_array($idsRecibidos)) {
                $idsRecibidos = [];
            }

            $ids = [];
            foreach ($idsRecibidos as $idRecibido) {
                $id = filter_var($idRecibido, FILTER_VALIDATE_INT);
                if ($id !== false && $id > 0) {
                    $ids[(int) $id] = (int) $id;
                }
            }
            $ids = array_values($ids);

            if (!$ids) {
                $this->flashError('Seleccione al menos una solicitud avalada para consolidar.');
                $this->redirect('/materiales/solicitudes/consolidar');
            }

            $solicitudesValidas = [];
            $detallesPorSolicitud = [];

            /*
             * Consultar individualmente cada solicitud seleccionada.
             * No se reutiliza el detalle de la primera solicitud para las demás.
             */
            foreach ($ids as $idSolicitud) {
                $solicitud = $this->model->obtenerSolicitud($idSolicitud);

                if (
                    !$solicitud
                    || strtolower(trim((string) ($solicitud['estado'] ?? ''))) !== 'avalada'
                    || (int) ($solicitud['id_coordinador'] ?? 0) !== $idProfesional
                ) {
                    $this->error('Una de las solicitudes seleccionadas no está avalada o no pertenece a su coordinación.');
                }

                $detallesSolicitud = $this->model->listarDetalles((int) $idSolicitud);
                $solicitudesValidas[(int) $idSolicitud] = $solicitud;
                $detallesPorSolicitud[(int) $idSolicitud] = is_array($detallesSolicitud)
                    ? $detallesSolicitud
                    : [];
            }

            /*
             * Conservar cada línea de detalle de cada solicitud. La clave única
             * por solicitud y detalle evita que cualquier producto desaparezca.
             */
            $grupos = [];

            foreach ($solicitudesValidas as $idSolicitud => $solicitud) {
                $numeroSolicitud = trim((string) ($solicitud['numero_solicitud'] ?? $idSolicitud));

                foreach ($detallesPorSolicitud[$idSolicitud] as $detalle) {
                    if (!is_array($detalle)) {
                        continue;
                    }

                    $clasificacion = [
                        (int) !empty($detalle['es_consumo']),
                        (int) !empty($detalle['es_devolutivo']),
                        (int) !empty($detalle['es_software']),
                        (int) !empty($detalle['es_epp']),
                    ];

                    $codigo = trim((string) ($detalle['codigo_unspsc'] ?? ''));
                    $nombre = trim((string) ($detalle['descripcion'] ?? ''));
                    $tecnica = trim((string) ($detalle['descripcion_tecnica'] ?? ''));
                    $unidad = trim((string) ($detalle['unidad_medida'] ?? ''));

                    $normalizar = static function (string $valor): string {
                        $valor = preg_replace('/\s+/u', ' ', trim($valor)) ?? trim($valor);
                        return function_exists('mb_strtolower')
                            ? mb_strtolower($valor, 'UTF-8')
                            : strtolower($valor);
                    };

                    /*
                     * Cada detalle de cada solicitud debe conservarse como
                     * una línea independiente. No agrupar por material porque
                     * eso puede ocultar productos cuando el catálogo comparte
                     * IDs o tiene datos incompletos.
                     */
                    $idDetalle = (int) ($detalle['id'] ?? 0);
                    $claveDatos = [
                        'solicitud' => (int) $idSolicitud,
                        'detalle' => $idDetalle,
                    ];

                    $claveJson = json_encode($claveDatos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $clave = hash('sha256', $claveJson === false ? serialize($claveDatos) : $claveJson);

                    if (!isset($grupos[$clave])) {
                        $grupos[$clave] = $detalle;
                        $grupos[$clave]['cantidad_solicitada'] = 0.0;
                        $grupos[$clave]['observaciones'] = [];
                        $grupos[$clave]['solicitudes_origen'] = [];
                    }

                    $cantidadRaw = $detalle['cantidad_solicitada'] ?? 0;
                    if (is_string($cantidadRaw)) {
                        $cantidadRaw = trim($cantidadRaw);
                        if (str_contains($cantidadRaw, ',') && !str_contains($cantidadRaw, '.')) {
                            $cantidadRaw = str_replace(',', '.', $cantidadRaw);
                        } else {
                            $cantidadRaw = str_replace(',', '', $cantidadRaw);
                        }
                    }
                    $grupos[$clave]['cantidad_solicitada'] += (float) $cantidadRaw;

                    $observacion = trim((string) ($detalle['observaciones'] ?? ''));
                    if (
                        $observacion !== ''
                        && !in_array($observacion, $grupos[$clave]['observaciones'], true)
                    ) {
                        $grupos[$clave]['observaciones'][] = $observacion;
                    }

                    if (!in_array($numeroSolicitud, $grupos[$clave]['solicitudes_origen'], true)) {
                        $grupos[$clave]['solicitudes_origen'][] = $numeroSolicitud;
                    }
                }
            }

            if (!$grupos) {
                $this->flashError('Las solicitudes seleccionadas no contienen materiales para consolidar.');
                $this->redirect('/materiales/solicitudes/consolidar');
            }

            $autoload = ROOT_PATH . '/vendor/autoload.php';
            if (!is_file($autoload)) {
                http_response_code(500);
                exit('No se encontró vendor/autoload.php.');
            }

            require_once $autoload;

            if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                http_response_code(500);
                exit('PhpSpreadsheet no está disponible.');
            }

            $rutaPlantilla = ROOT_PATH . '/Copia de GFPI-F-186 Formato Lista Maestra Archivo 2026.xlsx';
            if (!is_file($rutaPlantilla)) {
                http_response_code(500);
                exit('No se encontró la plantilla GFPI-F-186 en la raíz del proyecto.');
            }

            try {
                $libro = \PhpOffice\PhpSpreadsheet\IOFactory::load($rutaPlantilla);
                $hoja = $libro->getActiveSheet();
                $filaInicial = 13;
                $fila = $filaInicial;
                $detallesConsolidados = array_values($grupos);

                foreach ($detallesConsolidados as $indice => $detalle) {
                    /*
                     * La plantilla dispone de cuatro filas para productos (13 a 16).
                     * A partir del quinto producto se inserta una fila en la posición
                     * actual, desplazando el contenido posterior y conservando estilos.
                     */
                    if ($indice >= 4) {
                        $filaAnterior = max($filaInicial, $fila - 1);
                        $hoja->insertNewRowBefore($fila, 1);
                        $hoja->duplicateStyle(
                            $hoja->getStyle('C' . $filaAnterior . ':N' . $filaAnterior),
                            'C' . $fila . ':N' . $fila
                        );
                        $altoAnterior = $hoja->getRowDimension($filaAnterior)->getRowHeight();
                        if ($altoAnterior !== null) {
                            $hoja->getRowDimension($fila)->setRowHeight($altoAnterior);
                        }
                    }

                    $observaciones = $detalle['observaciones'] ?? [];
                    if (!is_array($observaciones)) {
                        $observaciones = [(string) $observaciones];
                    }
                    $origenes = $detalle['solicitudes_origen'] ?? [];
                    if (!is_array($origenes)) {
                        $origenes = [(string) $origenes];
                    }
                    $textoObservaciones = array_values(array_filter(array_map(
                        static fn($valor): string => trim((string) $valor),
                        $observaciones
                    )));
                    $textoObservaciones[] = 'Solicitudes origen: ' . implode(', ', $origenes);

                    $hoja->setCellValue('C' . $fila, $indice + 1);
                    $hoja->setCellValueExplicit(
                        'D' . $fila,
                        (string) ($detalle['codigo_unspsc'] ?? ''),
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );
                    $descripcionProducto = trim((string) ($detalle['descripcion'] ?? ''));
                    $descripcionTecnica = trim((string) ($detalle['descripcion_tecnica'] ?? ''));
                    $capitalizarInicialUtf8 = static function (string $texto): string {
                        $texto = trim($texto);
                        if ($texto === '') {
                            return '';
                        }
                        if (function_exists('mb_strtolower') && function_exists('mb_strtoupper') && function_exists('mb_substr')) {
                            $texto = mb_strtolower($texto, 'UTF-8');
                            return mb_strtoupper(mb_substr($texto, 0, 1, 'UTF-8'), 'UTF-8')
                                . mb_substr($texto, 1, null, 'UTF-8');
                        }
                        // Respaldo para instalaciones sin la extensión mbstring.
                        $texto = strtolower($texto);
                        return strtoupper(substr($texto, 0, 1)) . substr($texto, 1);
                    };
                    $descripcionProducto = $capitalizarInicialUtf8($descripcionProducto);
                    $descripcionTecnica = $capitalizarInicialUtf8($descripcionTecnica);
                    $hoja->setCellValue('E' . $fila, $descripcionProducto);
                    $hoja->setCellValue('F' . $fila, $descripcionTecnica);
                    $hoja->setCellValue('G' . $fila, (string) ($detalle['unidad_medida'] ?? ''));
                    $hoja->setCellValue('H' . $fila, (float) ($detalle['cantidad_solicitada'] ?? 0));
                    $hoja->setCellValue('I' . $fila, implode(' | ', $textoObservaciones));
                    $hoja->setCellValue('K' . $fila, !empty($detalle['es_consumo']) ? 'X' : '');
                    $hoja->setCellValue('L' . $fila, !empty($detalle['es_devolutivo']) ? 'X' : '');
                    $hoja->setCellValue('M' . $fila, !empty($detalle['es_software']) ? 'X' : '');
                    $hoja->setCellValue('N' . $fila, !empty($detalle['es_epp']) ? 'X' : '');

                    $fila++;
                }

                $ultimaFila = $fila - 1;
                if ($ultimaFila >= $filaInicial) {
                    $hoja->getStyle('C' . $filaInicial . ':N' . $ultimaFila)
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

                    $hoja->getStyle('C' . $filaInicial . ':C' . $ultimaFila)
                        ->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    $hoja->getStyle('D' . $filaInicial . ':D' . $ultimaFila)
                        ->getFont()
                        ->setBold(true);
                    $hoja->getStyle('D' . $filaInicial . ':D' . $ultimaFila)
                        ->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    $hoja->getStyle('E' . $filaInicial . ':F' . $ultimaFila)
                        ->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

                    $hoja->getStyle('H' . $filaInicial . ':N' . $ultimaFila)
                        ->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }

                $nombreArchivo = 'GFPI-F-186_Consolidado_' . date('Ymd_His') . '.xlsx';

                while (ob_get_level() > 0) {
                    ob_end_clean();
                }

                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
                header('Cache-Control: max-age=0');
                header('Pragma: public');

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($libro);
                $writer->save('php://output');
                $libro->disconnectWorksheets();
                exit;
            } catch (\Throwable $e) {
                http_response_code(500);
                exit(
                    'Error al generar el consolidado GFPI-F-186: '
                    . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
                );
            }
        }

        /**
         * =========================================================
         * OBTENER PROFESIONAL ACTUAL
         * =========================================================
         */
        private function profesionalActual(): ?array
        {
            $idUsuario =
                $this->obtenerUsuarioActualId();

            if ($idUsuario <= 0) {
                return null;
            }

            return $this->model->obtenerProfesionalPorUsuario(
                $idUsuario
            );
        }

        /**
         * =========================================================
         * EXIGIR PROFESIONAL
         * =========================================================
         */
        private function exigirProfesional(): array
        {
            $profesional =
                $this->profesionalActual();

            if (!$profesional) {

                $this->error(
                    'El usuario actual no tiene un profesional activo asociado.'
                );
            }

            return $profesional;
        }

        /**
         * =========================================================
         * EXIGIR PERMISO
         * =========================================================
         */
        private function exigirPermiso(
            string $permiso
        ): void {
            if (!Auth::can($permiso)) {

                $this->error(
                    'No tiene permisos para realizar esta acción.'
                );
            }
        }

        /**
         * =========================================================
         * OBTENER USUARIO ACTUAL
         * =========================================================
         */
        private function obtenerUsuarioActualId(): int
        {
            if (
                isset(
                    $_SESSION['usuario_id']
                )
            ) {
                return (int) $_SESSION[
                    'usuario_id'
                ];
            }

            if (
                isset(
                    $_SESSION['id_usuario']
                )
            ) {
                return (int) $_SESSION[
                    'id_usuario'
                ];
            }

            if (
                isset(
                    $_SESSION['usuario']
                )
                &&
                is_array(
                    $_SESSION['usuario']
                )
                &&
                isset(
                    $_SESSION['usuario']['id']
                )
            ) {
                return (int) $_SESSION[
                    'usuario'
                ]['id'];
            }

            return 0;
        }

        /**
         * =========================================================
         * OBTENER CARRITO
         * =========================================================
         */
        private function obtenerCarrito(): array
        {
            if (!isset($_SESSION[$this->sessionCartKey]) || !is_array($_SESSION[$this->sessionCartKey])) {
                $_SESSION[$this->sessionCartKey] = [];
            }

            $carritoOriginal = $_SESSION[$this->sessionCartKey];
            $carritoNormalizado = [];
            $cambio = false;

            foreach ($carritoOriginal as $clave => $item) {
                if (!is_array($item)) {
                    $cambio = true;
                    continue;
                }

                $idMaterial = (int) ($item['id_material'] ?? $item['id'] ?? (is_numeric($clave) ? $clave : 0));
                if ($idMaterial <= 0) {
                    $cambio = true;
                    continue;
                }

                $idLinea = trim((string) ($item['id_linea'] ?? ''));
                if ($idLinea === '') {
                    // Clave numérica antigua equivale al id_material; migrarla a clave única.
                    $idLinea = 'linea_' . bin2hex(random_bytes(8));
                    $cambio = true;
                }

                // Evitar colisiones de claves, preservando cada línea.
                while (isset($carritoNormalizado[$idLinea])) {
                    $idLinea = 'linea_' . bin2hex(random_bytes(8));
                    $cambio = true;
                }

                $item['id_linea'] = $idLinea;
                $item['id'] = $idMaterial;
                $item['id_material'] = $idMaterial;
                $item['descripcion_tecnica'] = (string) ($item['descripcion_tecnica'] ?? '');
                $item['unidad_medida'] = (string) ($item['unidad_medida'] ?? '');
                $item['observaciones'] = (string) ($item['observaciones'] ?? '');
                foreach (['es_consumo', 'es_devolutivo', 'es_software', 'es_epp'] as $campo) {
                    $item[$campo] = (int) ($item[$campo] ?? 0);
                }

                $carritoNormalizado[$idLinea] = $item;
                if ((string) $clave !== $idLinea || !isset($item['id_linea'])) {
                    $cambio = true;
                }
            }

            if ($cambio || $carritoOriginal !== $carritoNormalizado) {
                $_SESSION[$this->sessionCartKey] = $carritoNormalizado;
            }

            return $carritoNormalizado;
        }
        /**
         * =========================================================
         * GUARDAR CARRITO
         * =========================================================
         */
        private function guardarCarrito(
            array $carrito
        ): void {
            $_SESSION[
                $this->sessionCartKey
            ] = $carrito;
        }

        /**
         * =========================================================
         * OBTENER FORMULARIO TEMPORAL
         * =========================================================
         */
        private function obtenerFormularioTemporal(): array
        {
            if (
                !isset(
                    $_SESSION[
                        $this->sessionFormKey
                    ]
                )
                ||
                !is_array(
                    $_SESSION[
                        $this->sessionFormKey
                    ]
                )
            ) {
                $_SESSION[
                    $this->sessionFormKey
                ] = [
                    'id_proceso' =>
                        0,

                    'id_proceso_area' =>
                        0,

                    'titulo' =>
                        '',

                    'justificacion' =>
                        '',
                ];
            }

            return [
                'id_proceso' =>
                    (int) (
                        $_SESSION[
                            $this->sessionFormKey
                        ]['id_proceso']
                        ?? 0
                    ),

                'id_proceso_area' =>
                    (int) (
                        $_SESSION[
                            $this->sessionFormKey
                        ]['id_proceso_area']
                        ?? 0
                    ),

                'titulo' =>
                    (string) (
                        $_SESSION[
                            $this->sessionFormKey
                        ]['titulo']
                        ?? ''
                    ),

                'justificacion' =>
                    (string) (
                        $_SESSION[
                            $this->sessionFormKey
                        ]['justificacion']
                        ?? ''
                    ),
            ];
        }

        /**
         * =========================================================
         * GUARDAR FORMULARIO TEMPORAL
         * =========================================================
         */
        private function guardarFormularioTemporal(
            array $formulario
        ): void {
            $_SESSION[
                $this->sessionFormKey
            ] = [
                'id_proceso' =>
                    (int) (
                        $formulario['id_proceso']
                        ?? 0
                    ),

                'id_proceso_area' =>
                    (int) (
                        $formulario['id_proceso_area']
                        ?? 0
                    ),

                'titulo' =>
                    trim(
                        (string) (
                            $formulario['titulo']
                            ?? ''
                        )
                    ),

                'justificacion' =>
                    trim(
                        (string) (
                            $formulario['justificacion']
                            ?? ''
                        )
                    ),
            ];
        }

        /**
         * =========================================================
         * ACTUALIZAR FORMULARIO DESDE POST
         * =========================================================
         */
        private function actualizarFormularioDesdePost(): void
        {
            $formulario =
                $this->obtenerFormularioTemporal();

            if (isset($_POST['id_proceso'])) {

                $formulario['id_proceso'] =
                    (int) $_POST['id_proceso'];
            }

            if (isset($_POST['id_proceso_area'])) {

                $formulario['id_proceso_area'] =
                    (int) $_POST['id_proceso_area'];
            }

            if (isset($_POST['titulo'])) {

                $formulario['titulo'] =
                    trim(
                        (string) $_POST['titulo']
                    );
            }

            if (isset($_POST['justificacion'])) {

                $formulario['justificacion'] =
                    trim(
                        (string) $_POST['justificacion']
                    );
            }

            $this->guardarFormularioTemporal(
                $formulario
            );
        }

        /**
         * =========================================================
         * LIMPIAR FORMULARIO TEMPORAL
         * =========================================================
         */
        private function limpiarFormularioTemporal(): void
        {
            $_SESSION[
                $this->sessionFormKey
            ] = [
                'id_proceso' =>
                    0,

                'id_proceso_area' =>
                    0,

                'titulo' =>
                    '',

                'justificacion' =>
                    '',
            ];
        }

        /**
         * =========================================================
         * VALIDAR POST
         * =========================================================
         */
        private function validarPost(): void
        {
            if (
                ($_SERVER['REQUEST_METHOD'] ?? '')
                !== 'POST'
            ) {
                $this->error(
                    'Método de solicitud no permitido.'
                );
            }

            exigirCsrf();
        }

        /**
         * =========================================================
         * FLASH SUCCESS
         * =========================================================
         */
        private function flashSuccess(
            string $mensaje
        ): void {
            $_SESSION['flash']['success'] =
                $mensaje;
        }

        /**
         * =========================================================
         * FLASH ERROR
         * =========================================================
         */
        private function flashError(
            string $mensaje
        ): void {
            $_SESSION['flash']['error'] =
                $mensaje;
        }

        /**
         * =========================================================
         * REDIRECCIÓN A CREAR
         * =========================================================
         */
        private function redirectCrear(
            string $buscar = '',
            int $pagina = 1
        ): void {

            $formulario =
                $this->obtenerFormularioTemporal();

            $parametros = [];

            if (
                (int) (
                    $formulario['id_proceso']
                    ?? 0
                ) > 0
            ) {
                $parametros['id_proceso'] =
                    (int) $formulario[
                        'id_proceso'
                    ];
            }

            if (
                (int) (
                    $formulario['id_proceso_area']
                    ?? 0
                ) > 0
            ) {
                $parametros['id_proceso_area'] =
                    (int) $formulario[
                        'id_proceso_area'
                    ];
            }

            if (
                trim($buscar) !== ''
            ) {
                $parametros['buscar'] =
                    trim($buscar);
            }

            if ($pagina > 1) {

                $parametros['pagina'] =
                    $pagina;
            }

            if (
                trim(
                    (string) (
                        $formulario['titulo']
                        ?? ''
                    )
                ) !== ''
            ) {
                $parametros['titulo'] =
                    (string) $formulario[
                        'titulo'
                    ];
            }

            if (
                trim(
                    (string) (
                        $formulario['justificacion']
                        ?? ''
                    )
                ) !== ''
            ) {
                $parametros['justificacion'] =
                    (string) $formulario[
                        'justificacion'
                    ];
            }

            $url =
                '/materiales/solicitudes/crear';

            if (!empty($parametros)) {

                $url .= '?' .
                    http_build_query(
                        $parametros
                    );
            }

            $this->redirect(
                $url
            );
        }

        /**
         * =========================================================
         * REDIRECT
         * =========================================================
         */
        private function redirect(
            string $ruta
        ): void {
            header(
                'Location: ' .
                url($ruta)
            );

            exit;
        }

        /**
         * =========================================================
         * ERROR
         * =========================================================
         */
        private function error(
            string $mensaje
        ): void {
            http_response_code(403);

            echo '<!DOCTYPE html>';
            echo '<html lang="es">';
            echo '<head>';
            echo '<meta charset="UTF-8">';
            echo '<title>Error</title>';
            echo '</head>';
            echo '<body style="font-family:Arial,sans-serif;padding:40px;">';
            echo '<h2>Error</h2>';
            echo '<p>' .
                e($mensaje) .
                '</p>';
            echo '</body>';
            echo '</html>';

            exit;
        }
		
		
/**
 * =========================================================
 * EXPORTAR SOLICITUD INDIVIDUAL AL FORMATO GIL-F-014
 * =========================================================
 * No modifica el proceso ni el consolidado GFPI-F-186.
 */
public function exportarIndividual(): void
{
    $idSolicitud = (int) ($_GET['id'] ?? $_GET['id_solicitud'] ?? 0);
    if ($idSolicitud <= 0) $this->error('La solicitud indicada no es válida.');

    $solicitud = $this->model->obtenerSolicitud($idSolicitud);
    if (!$solicitud) $this->error('La solicitud no existe.');

    $profesional = $this->profesionalActual();
    $idProfesionalActual = $profesional ? (int) ($profesional['id'] ?? 0) : 0;
    $esAdministrador = Auth::hasRole('Administrador');
    $esPropietario = $idProfesionalActual > 0
        && $idProfesionalActual === (int) ($solicitud['id_profesional_solicitante'] ?? 0);

    $puedeRevisar = false;
    if (!$esPropietario && !$esAdministrador && $idProfesionalActual > 0) {
        $puedeRevisar = $this->model->coordinadorPuedeRevisarSolicitud($idSolicitud, $idProfesionalActual);
    }
    if (!$esPropietario && !$esAdministrador && !$puedeRevisar) {
        $this->error('No tiene autorización para descargar esta solicitud.');
    }

    $detalles = $this->model->listarDetalles($idSolicitud);
    if (!$detalles) $this->error('La solicitud no tiene productos para exportar.');

    $fichas = [];
    if (method_exists($this->model, 'listarFichasDestinoPorSolicitud')) {
        foreach ($this->model->listarFichasDestinoPorSolicitud($idSolicitud) as $f) {
            $idFicha = (int) ($f['id_ficha'] ?? $f['id'] ?? 0);
            if ($idFicha > 0) {
                $fichas[$idFicha] = [
                    'id' => $idFicha,
                    'numero_ficha' => (string) ($f['numero_ficha'] ?? $idFicha),
                ];
            }
        }
    }

    if (!$fichas) {
        foreach ($detalles as $detalle) {
            $idDetalle = (int) ($detalle['id'] ?? $detalle['id_detalle'] ?? 0);
            if ($idDetalle <= 0) continue;
            foreach ($this->model->listarDestinosPorDetalle($idDetalle) as $destino) {
                $idFicha = (int) ($destino['id_ficha'] ?? 0);
                if ($idFicha > 0) {
                    $fichas[$idFicha] = [
                        'id' => $idFicha,
                        'numero_ficha' => (string) ($destino['numero_ficha'] ?? $idFicha),
                    ];
                }
            }
        }
    }

    if (!$fichas) {
        $this->error('La solicitud no tiene fichas destino registradas.');
    }

    $numeroSolicitud = preg_replace(
        '/[^A-Za-z0-9_-]/',
        '_',
        (string) ($solicitud['numero_solicitud'] ?? $solicitud['numero'] ?? $idSolicitud)
    );

    // Una sola ficha: descargar un único Excel con los productos destinados a esa ficha.
    if (count($fichas) === 1) {
        $ficha = reset($fichas);
        $idFicha = (int) $ficha['id'];
        $numeroFicha = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $ficha['numero_ficha']);
        $tmp = tempnam(sys_get_temp_dir(), 'sita_f14_');
        if ($tmp === false) $this->error('No fue posible preparar la descarga del formato F-14.');

        try {
            $this->generarF14ParaFicha($idSolicitud, $solicitud, $idFicha, $tmp);
            while (ob_get_level() > 0) ob_end_clean();
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="GIL-F-014_Solicitud_' . $numeroSolicitud . '_Ficha_' . $numeroFicha . '.xlsx"');
            header('Content-Length: ' . filesize($tmp));
            header('Cache-Control: max-age=0, must-revalidate');
            header('Pragma: public');
            readfile($tmp);
            @unlink($tmp);
            exit;
        } catch (\Throwable $e) {
            @unlink($tmp);
            error_log('[SITA GIL-F-014] Error generando Excel de solicitud ' . $idSolicitud . ': ' . $e->getMessage());
            $this->error('No fue posible generar el formato GIL-F-014.');
        }
    }

    // Dos o más fichas: generar un Excel independiente por ficha y entregarlos en un ZIP.
    if (!class_exists('ZipArchive')) {
        $this->error('La extensión ZipArchive de PHP no está habilitada.');
    }

    $directorio = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sita_f14_' . bin2hex(random_bytes(8));
    if (!mkdir($directorio, 0700, true) && !is_dir($directorio)) {
        $this->error('No fue posible preparar los archivos F-14.');
    }

    $rutaZip = $directorio . DIRECTORY_SEPARATOR . 'GIL-F-014_Solicitud_' . $numeroSolicitud . '.zip';
    $zip = new \ZipArchive();
    $zipAbierto = false;

    try {
        if ($zip->open($rutaZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No fue posible crear el archivo ZIP.');
        }
        $zipAbierto = true;

        foreach ($fichas as $ficha) {
            $idFicha = (int) $ficha['id'];
            $numeroFicha = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $ficha['numero_ficha']);
            $rutaExcel = $directorio . DIRECTORY_SEPARATOR . 'GIL-F-014_Ficha_' . $numeroFicha . '.xlsx';
            $this->generarF14ParaFicha($idSolicitud, $solicitud, $idFicha, $rutaExcel);
            if (!$zip->addFile($rutaExcel, basename($rutaExcel))) {
                throw new \RuntimeException('No fue posible agregar el formato de la ficha ' . $numeroFicha . ' al ZIP.');
            }
        }

        if (!$zip->close()) {
            throw new \RuntimeException('No fue posible finalizar el archivo ZIP.');
        }
        $zipAbierto = false;

        if (!is_file($rutaZip) || filesize($rutaZip) === 0) {
            throw new \RuntimeException('El archivo ZIP generado está vacío.');
        }

        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="GIL-F-014_Solicitud_' . $numeroSolicitud . '.zip"');
        header('Content-Length: ' . filesize($rutaZip));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: public');
        readfile($rutaZip);
        exit;
    } catch (\Throwable $e) {
        error_log('[SITA GIL-F-014] Error generando ZIP de solicitud ' . $idSolicitud . ': ' . $e->getMessage());
        $this->error('No fue posible generar el ZIP de formatos GIL-F-014.');
    } finally {
        if ($zipAbierto) {
            try { $zip->close(); } catch (\Throwable $ignorado) {}
        }
        foreach (glob($directorio . DIRECTORY_SEPARATOR . '*') ?: [] as $archivo) {
            @unlink($archivo);
        }
        @rmdir($directorio);
    }
}

private function generarF14ParaFicha(int $idSolicitud, array $solicitud, int $idFichaFiltro, string $rutaSalida): void
{
    /*
     * UBICACIÓN DE LA PLANTILLA.
     *
     * Guarda el archivo original en:
     * storage/plantillas/GIL-F-014FormatoSolicitudbienesparausocuentadantes.xlsx
     */
    $rutasPlantilla = [
    ROOT_PATH . '/GIL-F-014FormatoSolicitudbienesparausocuentadantes.xlsx',
    ROOT_PATH . '/storage/plantillas/GIL-F-014FormatoSolicitudbienesparausocuentadantes.xlsx',
    ROOT_PATH . '/uploads/plantillas/GIL-F-014FormatoSolicitudbienesparausocuentadantes.xlsx',
];

$rutaPlantilla = null;

foreach ($rutasPlantilla as $ruta) {
    if (is_file($ruta) && is_readable($ruta)) {
        $rutaPlantilla = $ruta;
        break;
    }
}

if ($rutaPlantilla === null) {
    throw new \RuntimeException('No se encontró la plantilla GIL-F-014 en las rutas configuradas.');
}

    /*
     * Verificar que PhpSpreadsheet esté disponible.
     */
    if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
        throw new \RuntimeException('PhpSpreadsheet no está disponible. Verifique vendor/autoload.php.');
    }

    $detallesOriginales = $this->model->listarDetalles($idSolicitud);
    $detalles = [];

    if ($idFichaFiltro <= 0) {
        // Exportación individual: incluir cada producto una sola vez,
        // aunque no tenga distribución por ficha registrada.
        $detalles = $detallesOriginales;
    } else {
        foreach ($detallesOriginales as $detalleOriginal) {
            $idDetalleOriginal = (int) ($detalleOriginal['id'] ?? $detalleOriginal['id_detalle'] ?? 0);
            if ($idDetalleOriginal <= 0) continue;
            foreach ($this->model->listarDestinosPorDetalle($idDetalleOriginal) as $destinoFicha) {
                if ((int) ($destinoFicha['id_ficha'] ?? 0) !== $idFichaFiltro) continue;
                $detalleFicha = $detalleOriginal;
                $detalleFicha['cantidad_solicitada'] = (float) ($destinoFicha['cantidad_destinada'] ?? 0);
                $detalleFicha['_destino_ficha'] = $destinoFicha;
                $detalles[] = $detalleFicha;
            }
        }
    }

    if (empty($detalles)) {
        $this->error(
            'La solicitud no tiene materiales para exportar.'
        );
    }

    try {
        $documento = \PhpOffice\PhpSpreadsheet\IOFactory::load(
            $rutaPlantilla
        );

        $hoja = $documento->getSheetByName('FORMATO GIL-F-014');

        if ($hoja === null) {
            throw new \RuntimeException(
                'La plantilla no contiene la hoja FORMATO GIL-F-014.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | DATOS GENERALES — FORMATO INDIVIDUAL GIL-F-014
        |--------------------------------------------------------------------------
        */

        $fecha = $solicitud['fecha_solicitud']
            ?? $solicitud['fecha_envio']
            ?? $solicitud['created_at']
            ?? $solicitud['fecha_creacion']
            ?? null;

        $hoja->setCellValue(
            'E10',
            $fecha ? date('d/m/Y', strtotime((string) $fecha)) : date('d/m/Y')
        );

        $hoja->setCellValue('K10', (string) ($solicitud['titulo'] ?? ''));

        // Nombre y documento del instructor solicitante.
        $nombreSolicitante = trim(
            (string) ($solicitud['nombres'] ?? '') . ' ' .
            (string) ($solicitud['apellidos'] ?? '')
        );

        $documentoSolicitante = trim(
            (string) ($solicitud['documento'] ?? '')
        );

        $hoja->setCellValue('H15', $nombreSolicitante);
        $hoja->setCellValueExplicit(
            'M15',
            $documentoSolicitante,
            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
        );

        // Nombre del coordinador asignado al proceso.
        $hoja->setCellValue(
            'H13',
            (string) ($solicitud['nombre_coordinador'] ?? '')
        );

        // Recuperar los datos reales de las fichas destino.
        $destinos = [];
        $fichas = [];
        $programas = [];
        $regionales = [];
        $centros = [];

        foreach ($detalles as $detalle) {
            $idDetalle = (int) (
                $detalle['id']
                ?? $detalle['id_detalle']
                ?? 0
            );

            if ($idDetalle <= 0) {
                continue;
            }

            foreach ($this->model->listarDestinosPorDetalle($idDetalle) as $destino) {
                if ($idFichaFiltro > 0 && (int) ($destino['id_ficha'] ?? 0) !== $idFichaFiltro) continue;
                $tipoDestino = trim((string) ($destino['tipo_destino'] ?? ''));
                if ($tipoDestino !== '') {
                    $destinos[$tipoDestino] = $tipoDestino;
                }

                $numeroFicha = trim((string) ($destino['numero_ficha'] ?? ''));
                $nombrePrograma = trim((string) ($destino['nombre_programa'] ?? ''));

                if ($numeroFicha !== '') {
                    $fichas[$numeroFicha] = $numeroFicha;
                    $fichaPrograma = $numeroFicha;
                    if ($nombrePrograma !== '') {
                        $fichaPrograma .= ' - ' . $nombrePrograma;
                    }
                    $programas[$fichaPrograma] = $fichaPrograma;
                }

                $regional = trim((string) ($destino['regional'] ?? ''));
                if ($regional !== '') {
                    $regionales[$regional] = $regional;
                }

                $centro = trim((string) ($destino['centro'] ?? ''));
                if ($centro !== '') {
                    $centros[$centro] = $centro;
                }
            }
        }

        // Consultar regional y centro directamente desde la ficha destino
        // de la solicitud, además de conservar los destinos y programas.
        $datosFichaFormato = $this->model->obtenerDatosFichaFormato($idSolicitud);

        $partirCodigoNombre = static function ($valor): array {
            $valor = trim((string) $valor);
            if (preg_match('/^\s*(\d+)\s*-\s*(.*?)\s*$/u', $valor, $m)) {
                return [trim($m[1]), trim($m[2])];
            }
            return ['', $valor];
        };

        [$codigoRegional, $nombreRegional] = $partirCodigoNombre(
            $regionales ? reset($regionales) : ($datosFichaFormato['regional'] ?? '')
        );
        [$codigoCentro, $nombreCentro] = $partirCodigoNombre(
            $centros ? reset($centros) : ($datosFichaFormato['centro'] ?? '')
        );

        // Celdas de la plantilla GIL-F-014: código regional E11,
        // código de centro E12 y nombre del centro L12.
        $hoja->setCellValueExplicit(
            'E11',
            $codigoRegional,
            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
        );
        $hoja->setCellValueExplicit(
            'E12',
            $codigoCentro,
            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
        );
        $hoja->setCellValue('L12', $nombreCentro);

        // Destino y ficha/programa de formación.
		 $hoja->setCellValue('H14', 'UNIPERSONAL');
        $hoja->setCellValue('H22', 'Formación');

        // H23 contiene el código de ficha y el nombre del programa.
        // El rótulo original se conserva en B23 de la plantilla.
        $textoFichaPrograma = implode(' / ', array_values($programas));
        if ($textoFichaPrograma === '' && !empty($datosFichaFormato['numero_ficha'])) {
            $textoFichaPrograma = trim(
                (string) $datosFichaFormato['numero_ficha'] .
                (!empty($datosFichaFormato['nombre_programa'])
                    ? ' - ' . (string) $datosFichaFormato['nombre_programa']
                    : '')
            );
        }
        $hoja->setCellValue('H23', $textoFichaPrograma);
		$hoja->setCellValue('E9', 'X');
        // La plantilla tiene seis filas de productos (26 a 31).
        // Si hay más productos, insertar filas antes del pie del formato.
        $filaInicialProductos = 26;
        $filasProductosPlantilla = 6;
        $cantidadProductos = count($detalles);
        $filasAdicionales = max(0, $cantidadProductos - $filasProductosPlantilla);

        if ($filasAdicionales > 0) {
            // Insertar antes de la fila 32 para desplazar autorización y notas.
            $hoja->insertNewRowBefore(32, $filasAdicionales);

            // Copiar el formato de la última fila original de productos.
            $filaModelo = 31;
            for ($nuevaFila = 32; $nuevaFila < 32 + $filasAdicionales; $nuevaFila++) {
                $hoja->duplicateStyle(
                    $hoja->getStyle('A' . $filaModelo . ':M' . $filaModelo),
                    'A' . $nuevaFila . ':M' . $nuevaFila
                );
                $alturaModelo = $hoja->getRowDimension($filaModelo)->getRowHeight();
                if ($alturaModelo > 0) {
                    $hoja->getRowDimension($nuevaFila)->setRowHeight($alturaModelo);
                }
                foreach (range('A', 'M') as $columna) {
                    $celdaModelo = $columna . $filaModelo;
                    $celdaNueva = $columna . $nuevaFila;
                    $hoja->getCell($celdaNueva)->setXfIndex(
                        $hoja->getCell($celdaModelo)->getXfIndex()
                    );
                }
                // Combinar las celdas de cada fila adicional igual que en la plantilla.
                foreach ([
                    'C' . $nuevaFila . ':D' . $nuevaFila,
                    'E' . $nuevaFila . ':G' . $nuevaFila,
                    'K' . $nuevaFila . ':M' . $nuevaFila,
                ] as $rangoCombinar) {
                    $celdaInicio = explode(':', $rangoCombinar)[0];
                    if (!$hoja->getCell($celdaInicio)->isInMergeRange()) {
                        $hoja->mergeCells($rangoCombinar);
                    }
                }
            }
        }

        $fila = $filaInicialProductos;
        $item = 1;

        foreach ($detalles as $detalle) {
            $hoja->setCellValue('B' . $fila, $item++);

            $hoja->setCellValueExplicit(
                'C' . $fila,
                (string) ($detalle['codigo_unspsc'] ?? ''),
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );

            $nombreProducto = trim((string) ($detalle['descripcion'] ?? ''));
            $descripcionTecnica = trim((string) ($detalle['descripcion_tecnica'] ?? ''));

            // Capitalizar la primera letra de cada texto sin alterar el resto.
            $capitalizarInicial = static function (string $texto): string {
                $texto = trim($texto);
                if ($texto === '') {
                    return '';
                }
                if (function_exists('mb_substr') && function_exists('mb_strtoupper') && function_exists('mb_strtolower')) {
                    $texto = mb_strtolower($texto, 'UTF-8');
                    return mb_strtoupper(mb_substr($texto, 0, 1, 'UTF-8'), 'UTF-8')
                        . mb_substr($texto, 1, null, 'UTF-8');
                }
                $texto = strtolower($texto);
                return strtoupper(substr($texto, 0, 1)) . substr($texto, 1);
            };

            $nombreProducto = $capitalizarInicial($nombreProducto);
            $descripcionTecnica = $capitalizarInicial($descripcionTecnica);
            $descripcionCompleta = $nombreProducto;

            if ($descripcionTecnica !== '') {
                $descripcionCompleta .= ' | ' . $descripcionTecnica;
            }

            $hoja->setCellValue('E' . $fila, $descripcionCompleta);
            $hoja->setCellValue('H' . $fila, (string) ($detalle['unidad_medida'] ?? ''));
            $hoja->setCellValue('I' . $fila, (float) ($detalle['cantidad_solicitada'] ?? 0));
            $hoja->setCellValue('J' . $fila, '');
            $hoja->setCellValue('K' . $fila, (string) ($detalle['observaciones'] ?? ''));
            $fila++;
        }

        // Mantener la firma en la fila 33 cuando hay hasta seis productos.
        // Si se insertan filas por productos adicionales, el formato desplaza la firma.
        $filaCoordinadora = 33 + $filasAdicionales;
        $hoja->setCellValue(
            'E' . $filaCoordinadora,
            (string) ($solicitud['nombre_coordinador'] ?? '')
        );
        $hoja->setCellValue('K' . $filaCoordinadora, '');
        $hoja->setCellValue('E' . ($filaCoordinadora + 1), '');

        $nombreArchivo = 'GIL-F-014_Solicitud_'
            . preg_replace(
                '/[^A-Za-z0-9_-]/',
                '_',
                (string) (
                    $solicitud['numero_solicitud']
                    ?? $solicitud['numero']
                    ?? $idSolicitud
                )
            )
            . '.xlsx';

        $escritor = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($documento);
        $escritor->save($rutaSalida);
        return;

    } catch (\Throwable $e) {
        error_log(
            '[SITA GIL-F-014] Error: ' . $e->getMessage()
            . ' | Archivo: ' . $e->getFile()
            . ' | Línea: ' . $e->getLine()
        );

        throw new \RuntimeException(
            'No fue posible generar el formato GIL-F-014. Revise el registro de errores de PHP.',
            0,
            $e
        );
    }
}

/**
 * Buscar materiales activos mediante AJAX.
 * Utilizado por el formulario de edición de solicitudes.
 */
public function buscarMateriales(): void
{
    $this->exigirPermiso('materiales.solicitudes.crear');

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    try {
        // Verificar que el usuario tenga un profesional asociado,
        // igual que en el proceso habitual de solicitudes.
        $this->exigirProfesional();

        $buscar = trim((string) ($_GET['buscar'] ?? ''));

        if (mb_strlen($buscar) < 2) {
            echo json_encode([
                'ok' => true,
                'materiales' => []
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        // Limitar la búsqueda para no cargar todo el catálogo.
        $materiales = $this->model->listarMaterialesActivos(
            $buscar,
            20,
            0
        );

        $resultado = [];

        foreach ($materiales as $material) {
            $id = (int) ($material['id'] ?? $material['id_material'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            $resultado[] = [
                'id' => $id,
                'codigo_unspsc' => (string) ($material['codigo_unspsc'] ?? ''),
                'descripcion' => (string) ($material['descripcion'] ?? '')
            ];
        }

        echo json_encode([
            'ok' => true,
            'materiales' => $resultado
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    } catch (Throwable $e) {
        http_response_code(500);

        error_log(
            'Error buscando materiales para editar solicitud: '
            . $e->getMessage()
        );

        echo json_encode([
            'ok' => false,
            'materiales' => [],
            'mensaje' => 'No fue posible consultar el catálogo.'
        ], JSON_UNESCAPED_UNICODE);
    }
}

    }
}