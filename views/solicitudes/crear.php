<?php

declare(strict_types=1);

require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';

$profesional = is_array($profesional ?? null)
    ? $profesional
    : [];

$procesos = is_array($procesos ?? null)
    ? $procesos
    : [];

$areas = is_array($areas ?? null)
    ? $areas
    : [];

$materiales = is_array($materiales ?? null)
    ? $materiales
    : [];

$carrito = is_array($carrito ?? null)
    ? $carrito
    : [];

$buscar = (string) ($buscar ?? '');

$pagina = (int) ($pagina ?? 1);

$totalPaginas = (int) (
    $totalPaginas ?? 1
);

$idProceso = (int) (
    $idProceso ?? 0
);


/*
 * ============================================================
 * DATOS TEMPORALES DEL FORMULARIO
 * ============================================================
 */

$idProcesoArea = (int) (
    $_GET['id_proceso_area']
    ?? $_POST['id_proceso_area']
    ?? 0
);

$tituloSolicitud = (string) (
    $_GET['titulo']
    ?? $_POST['titulo']
    ?? ''
);

$justificacionSolicitud = (string) (
    $_GET['justificacion']
    ?? $_POST['justificacion']
    ?? ''
);

?>

<div class="container-fluid py-4">

    <!-- =====================================================
         ENCABEZADO
         ===================================================== -->

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>

            <h2 class="fw-bold mb-1">
                <i class="bi bi-cart-plus me-2"></i>
                Nueva solicitud de materiales
            </h2>

            <p class="text-muted mb-0">
                Seleccione los materiales que requiere y guarde la
                solicitud como borrador.
            </p>

        </div>

        <a
            href="<?= e(url('/materiales/solicitudes')) ?>"
            class="btn btn-outline-secondary"
        >
            <i class="bi bi-arrow-left me-1"></i>
            Volver a solicitudes
        </a>

    </div>


    <!-- =====================================================
         MENSAJES
         ===================================================== -->

    <?php
    $mensajeSuccess =
        $_SESSION['flash']['success']
        ?? null;

    $mensajeError =
        $_SESSION['flash']['error']
        ?? null;
    ?>

    <?php if ($mensajeSuccess): ?>

        <div class="alert alert-success alert-dismissible fade show">

            <i class="bi bi-check-circle me-2"></i>

            <?= e((string) $mensajeSuccess) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <?php if ($mensajeError): ?>

        <div class="alert alert-danger alert-dismissible fade show">

            <i class="bi bi-exclamation-triangle me-2"></i>

            <?= e((string) $mensajeError) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>

    <?php
    unset(
        $_SESSION['flash']['success'],
        $_SESSION['flash']['error']
    );
    ?>


    <!-- =====================================================
         DATOS DE LA SOLICITUD
         ===================================================== -->

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-header bg-white py-3">

            <h5 class="mb-0 fw-semibold">
                <i class="bi bi-file-earmark-text me-2"></i>
                Información de la solicitud
            </h5>

        </div>

        <div class="card-body">

            <div class="row g-3 mb-3">

                <!-- PROFESIONAL -->

                <div class="col-lg-4">

                    <label class="form-label fw-semibold">
                        Profesional solicitante
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        value="<?= e(
                            (string) (
                                $profesional['nombre_completo']
                                ?? ''
                            )
                        ) ?>"
                        readonly
                    >

                </div>


                <!-- TIPO PROFESIONAL -->

                <div class="col-lg-4">

                    <label class="form-label fw-semibold">
                        Tipo de profesional
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        value="<?= e(
                            (string) (
                                $profesional['tipo_profesional']
                                ?? ''
                            )
                        ) ?>"
                        readonly
                    >

                </div>


                <!-- PROCESO -->

                <div class="col-lg-4">

                    <label
                        for="id_proceso"
                        class="form-label fw-semibold"
                    >
                        Proceso
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        name="id_proceso"
                        id="id_proceso"
                        class="form-select"
                        form="formGuardarSolicitud"
                        required
                    >

                        <option value="">
                            Seleccione un proceso
                        </option>

                        <?php foreach ($procesos as $proceso): ?>

                            <?php
                            $idProcesoItem =
                                (int) (
                                    $proceso['id']
                                    ?? 0
                                );

                            $seleccionado =
                                $idProceso ===
                                $idProcesoItem;
                            ?>

                            <option
                                value="<?= $idProcesoItem ?>"
                                <?= $seleccionado
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= e(
                                    (string) (
                                        $proceso['anio']
                                        ?? ''
                                    )
                                ) ?>
                                -
                                <?= e(
                                    (string) (
                                        $proceso['nombre']
                                        ?? ''
                                    )
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>


            <div class="row g-3 mb-3">

                <!-- ÁREA -->

                <div class="col-lg-6">

                    <label
                        for="id_proceso_area"
                        class="form-label fw-semibold"
                    >
                        Área responsable
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        name="id_proceso_area"
                        id="id_proceso_area"
                        class="form-select"
                        form="formGuardarSolicitud"
                        required
                    >

                        <?php if (
                            $idProceso > 0 &&
                            !empty($areas)
                        ): ?>

                            <option value="">
                                Seleccione un área
                            </option>

                            <?php foreach ($areas as $area): ?>

                                <?php
                                $idAreaItem =
                                    (int) (
                                        $area['id']
                                        ?? 0
                                    );

                                $areaSeleccionada =
                                    $idProcesoArea ===
                                    $idAreaItem;
                                ?>

                                <option
                                    value="<?= $idAreaItem ?>"
                                    <?= $areaSeleccionada
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= e(
                                        (string) (
                                            $area['nombre_area']
                                            ?? ''
                                        )
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <option value="">
                                Seleccione primero un proceso
                            </option>

                        <?php endif; ?>

                    </select>

                    <div class="form-text">
                        El área corresponde al proceso seleccionado.
                    </div>

                </div>


                <!-- TÍTULO -->

                <div class="col-lg-6">

                    <label
                        for="titulo"
                        class="form-label fw-semibold"
                    >
                        Título de la solicitud
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        name="titulo"
                        id="titulo"
                        class="form-control"
                        form="formGuardarSolicitud"
                        maxlength="200"
                        placeholder="Ej.: Materiales para actividades de formación"
                        value="<?= e($tituloSolicitud) ?>"
                        required
                    >

                </div>

            </div>


            <!-- JUSTIFICACIÓN -->

            <div class="mb-0">

                <label
                    for="justificacion"
                    class="form-label fw-semibold"
                >
                    Justificación
                </label>

                <textarea
                    name="justificacion"
                    id="justificacion"
                    class="form-control"
                    form="formGuardarSolicitud"
                    rows="3"
                    placeholder="Indique brevemente la necesidad de los materiales."
                ><?= e($justificacionSolicitud) ?></textarea>

            </div>

        </div>

    </div>


    <!-- =====================================================
         CATÁLOGO
         ===================================================== -->

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-header bg-white py-3">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

                <div>

                    <h5 class="mb-1 fw-semibold">
                        <i class="bi bi-box-seam me-2"></i>
                        Catálogo de materiales
                    </h5>

                    <small class="text-muted">
                        Seleccione los materiales que desea incluir.
                    </small>

                </div>

                <span class="badge text-bg-primary">
                    <?= count($carrito) ?> seleccionados
                </span>

            </div>

        </div>


        <div class="card-body">

            <!-- =================================================
                 BUSCADOR
                 ================================================= -->

            <form
                method="GET"
                action="<?= e(
                    url('/materiales/solicitudes/crear')
                ) ?>"
                class="row g-2 mb-4"
                id="formBuscarCatalogo"
            >

                <?php if ($idProceso > 0): ?>

                    <input
                        type="hidden"
                        name="id_proceso"
                        value="<?= $idProceso ?>"
                    >

                <?php endif; ?>


                <?php if ($idProcesoArea > 0): ?>

                    <input
                        type="hidden"
                        name="id_proceso_area"
                        value="<?= $idProcesoArea ?>"
                    >

                <?php endif; ?>


                <input
                    type="hidden"
                    name="titulo"
                    id="buscar_titulo"
                    value="<?= e($tituloSolicitud) ?>"
                >


                <input
                    type="hidden"
                    name="justificacion"
                    id="buscar_justificacion"
                    value="<?= e($justificacionSolicitud) ?>"
                >


                <div class="col-md-10">

                    <input
                        type="search"
                        name="buscar"
                        id="buscar"
                        class="form-control"
                        value="<?= e($buscar) ?>"
                        placeholder="Buscar por código UNSPSC, descripción o unidad de medida..."
                    >

                </div>


                <div class="col-md-2 d-grid">

                    <button
                        type="submit"
                        class="btn btn-outline-primary"
                    >
                        <i class="bi bi-search me-1"></i>
                        Buscar
                    </button>

                </div>

            </form>


            <?php if (empty($materiales)): ?>

                <div class="alert alert-info mb-0">

                    <i class="bi bi-info-circle me-2"></i>

                    No se encontraron materiales activos con los
                    criterios de búsqueda.

                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead>

                            <tr>

                                <th>
                                    UNSPSC
                                </th>

                                <th>
                                    Descripción
                                </th>

                                

                                <th
                                    class="text-end"
                                    style="width: 150px;"
                                >
                                    Acción
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($materiales as $material): ?>

                            <?php
                            $idMaterial =
                                (int) (
                                    $material['id']
                                    ?? 0
                                );

                            ?>

                            <tr>

                                <td>

                                    <span class="fw-semibold">

                                        <?= e(
                                            (string) (
                                                $material[
                                                    'codigo_unspsc'
                                                ]
                                                ?? ''
                                            )
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <div class="fw-semibold">

                                        <?= e(
                                            (string) (
                                                $material[
                                                    'descripcion'
                                                ]
                                                ?? ''
                                            )
                                        ) ?>

                                    </div>

                                </td>


                                


                                <td class="text-end">

                                    <!--
                                        Se permite agregar el mismo material
                                        varias veces. Cada agregado debe ser
                                        una línea independiente del carrito.
                                    -->
                                    <form
                                            method="POST"
                                            action="<?= e(
                                                url(
                                                    '/materiales/solicitudes/carrito/agregar'
                                                )
                                            ) ?>"
                                            class="d-inline formularioAgregarMaterial"
                                        >

                                            <?= csrfField() ?>


                                            <input
                                                type="hidden"
                                                name="id_material"
                                                value="<?= $idMaterial ?>"
                                            >


                                            <!--
                                                Los materiales nuevos
                                                comienzan con cantidad 1.
                                            -->
                                            <input
                                                type="hidden"
                                                name="cantidad"
                                                value="1"
                                            >


                                            <input
                                                type="hidden"
                                                name="buscar"
                                                value="<?= e($buscar) ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="pagina"
                                                value="<?= $pagina ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="id_proceso"
                                                value="<?= $idProceso ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="id_proceso_area"
                                                value="<?= $idProcesoArea ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="titulo"
                                                value="<?= e(
                                                    $tituloSolicitud
                                                ) ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="justificacion"
                                                value="<?= e(
                                                    $justificacionSolicitud
                                                ) ?>"
                                            >


                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-primary"
                                            >

                                                <i class="bi bi-plus-circle me-1"></i>

                                                Agregar

                                            </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


                <!-- =================================================
                     PAGINACIÓN
                     ================================================= -->

                <?php if ($totalPaginas > 1): ?>

                    <nav class="mt-4">

                        <ul class="pagination justify-content-center">

                            <?php for (
                                $i = 1;
                                $i <= $totalPaginas;
                                $i++
                            ): ?>

                                <?php
                                $parametrosPaginacion = [
                                    'buscar' =>
                                        $buscar,

                                    'pagina' =>
                                        $i,

                                    'id_proceso' =>
                                        $idProceso,

                                    'id_proceso_area' =>
                                        $idProcesoArea,

                                    'titulo' =>
                                        $tituloSolicitud,

                                    'justificacion' =>
                                        $justificacionSolicitud
                                ];
                                ?>

                                <li
                                    class="page-item <?= $i === $pagina
                                        ? 'active'
                                        : '' ?>"
                                >

                                    <a
                                        class="page-link"
                                        href="<?= e(
                                            url(
                                                '/materiales/solicitudes/crear?' .
                                                http_build_query(
                                                    $parametrosPaginacion
                                                )
                                            )
                                        ) ?>"
                                    >
                                        <?= $i ?>
                                    </a>

                                </li>

                            <?php endfor; ?>

                        </ul>

                    </nav>

                <?php endif; ?>

            <?php endif; ?>

        </div>

    </div>


    <!-- =====================================================
         CARRITO
         ===================================================== -->

    <?php
    require ROOT_PATH .
        '/views/solicitudes/carrito.php';
    ?>


    <!-- =====================================================
         GUARDAR SOLICITUD
         ===================================================== -->

    <div class="card border-0 shadow-sm mt-4">

        <div class="card-body">

            <form
                method="POST"
                action="<?= e(
                    url('/materiales/solicitudes/guardar')
                ) ?>"
                id="formGuardarSolicitud"
            >

                <?= csrfField() ?>

                <!--
                 * IMPORTANTE:
                 * NO se agregan aquí inputs hidden para
                 * id_proceso ni id_proceso_area.
                 *
                 * Ambos SELECT están asociados directamente
                 * a este formulario mediante:
                 *
                 * form="formGuardarSolicitud"
                 *
                 * De esta manera cada campo se envía una sola vez.
                 -->


                <input
                    type="hidden"
                    name="titulo"
                    value="<?= e($tituloSolicitud) ?>"
                    id="guardar_titulo"
                >


                <input
                    type="hidden"
                    name="justificacion"
                    value="<?= e(
                        $justificacionSolicitud
                    ) ?>"
                    id="guardar_justificacion"
                >


                <!--
                    Los campos materiales se agregan dinámicamente
                    mediante JavaScript justo antes de enviar este
                    formulario.

                    De esta manera se envían las cantidades y
                    observaciones que el usuario tiene actualmente
                    visibles en el carrito.
                -->
                <div id="materialesGuardarContainer"></div>


                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">

                    <div>

                        <div class="fw-semibold">
                            Guardar solicitud
                        </div>

                        <div class="text-muted small">
                            La solicitud se guardará inicialmente como
                            <strong>borrador</strong>.
                        </div>

                    </div>


                    <div>

                        <a
                            href="<?= e(
                                url('/materiales/solicitudes')
                            ) ?>"
                            class="btn btn-outline-secondary me-2"
                        >
                            Cancelar
                        </a>


                        <button
                            type="submit"
                            class="btn btn-primary"
                            <?= empty($carrito)
                                ? 'disabled'
                                : '' ?>
                        >

                            <i class="bi bi-save me-1"></i>

                            Guardar como borrador

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

</div>


<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {

        /*
         * =====================================================
         * ELEMENTOS
         * =====================================================
         */

        const proceso =
            document.getElementById(
                'id_proceso'
            );

        const area =
            document.getElementById(
                'id_proceso_area'
            );

        const titulo =
            document.getElementById(
                'titulo'
            );

        const justificacion =
            document.getElementById(
                'justificacion'
            );

        const buscarTitulo =
            document.getElementById(
                'buscar_titulo'
            );

        const buscarJustificacion =
            document.getElementById(
                'buscar_justificacion'
            );

        const guardarTitulo =
            document.getElementById(
                'guardar_titulo'
            );

        const guardarJustificacion =
            document.getElementById(
                'guardar_justificacion'
            );

        const formularioBusqueda =
            document.getElementById(
                'formBuscarCatalogo'
            );

        const formularioGuardar =
            document.getElementById(
                'formGuardarSolicitud'
            );

        const materialesGuardarContainer =
            document.getElementById(
                'materialesGuardarContainer'
            );


        /*
         * =====================================================
         * CLAVE PARA CONSERVAR SCROLL
         * =====================================================
         */

        const claveScroll =
            'sita_materiales_solicitud_scroll';


        /*
         * =====================================================
         * CONSERVAR DATOS Y POSICIÓN AL BUSCAR
         * =====================================================
         */

        if (formularioBusqueda) {

            formularioBusqueda.addEventListener(
                'submit',
                function () {

                    sessionStorage.setItem(
                        claveScroll,
                        String(
                            window.scrollY
                        )
                    );


                    if (
                        titulo &&
                        buscarTitulo
                    ) {

                        buscarTitulo.value =
                            titulo.value;

                    }


                    if (
                        justificacion &&
                        buscarJustificacion
                    ) {

                        buscarJustificacion.value =
                            justificacion.value;

                    }

                }
            );

        }


        /*
         * =====================================================
         * RESTAURAR POSICIÓN DESPUÉS DE LA RECARGA
         * =====================================================
         */

        const scrollGuardado =
            sessionStorage.getItem(
                claveScroll
            );

        if (
            scrollGuardado !== null
        ) {

            const posicion =
                parseInt(
                    scrollGuardado,
                    10
                );

            if (
                !Number.isNaN(
                    posicion
                )
            ) {

                window.requestAnimationFrame(
                    function () {

                        window.requestAnimationFrame(
                            function () {

                                window.scrollTo(
                                    0,
                                    posicion
                                );

                            }
                        );

                    }
                );

            }

            sessionStorage.removeItem(
                claveScroll
            );

        }


        /*
         * =====================================================
         * CONSERVAR ENCABEZADO AL AGREGAR MATERIAL
         * =====================================================
         */

        const formulariosAgregar =
            document.querySelectorAll(
                '.formularioAgregarMaterial'
            );

        formulariosAgregar.forEach(
            function (formulario) {

                formulario.addEventListener(
                    'submit',
                    function () {

                        sessionStorage.setItem(
                            claveScroll,
                            String(
                                window.scrollY
                            )
                        );


                        const campoProceso =
                            formulario.querySelector(
                                'input[name="id_proceso"]'
                            );

                        const campoArea =
                            formulario.querySelector(
                                'input[name="id_proceso_area"]'
                            );

                        const campoTitulo =
                            formulario.querySelector(
                                'input[name="titulo"]'
                            );

                        const campoJustificacion =
                            formulario.querySelector(
                                'input[name="justificacion"]'
                            );


                        if (
                            campoProceso &&
                            proceso
                        ) {

                            campoProceso.value =
                                proceso.value;

                        }


                        if (
                            campoArea &&
                            area
                        ) {

                            campoArea.value =
                                area.value;

                        }


                        if (
                            campoTitulo &&
                            titulo
                        ) {

                            campoTitulo.value =
                                titulo.value;

                        }


                        if (
                            campoJustificacion &&
                            justificacion
                        ) {

                            campoJustificacion.value =
                                justificacion.value;

                        }

                    }
                );

            }
        );


        /*
         * =====================================================
         * SINCRONIZAR CAMPOS DE GUARDAR
         * =====================================================
         */

        function sincronizarFormularioGuardar() {

            if (
                guardarTitulo &&
                titulo
            ) {

                guardarTitulo.value =
                    titulo.value;

            }


            if (
                guardarJustificacion &&
                justificacion
            ) {

                guardarJustificacion.value =
                    justificacion.value;

            }

        }


        if (titulo) {

            titulo.addEventListener(
                'input',
                sincronizarFormularioGuardar
            );

        }


        if (justificacion) {

            justificacion.addEventListener(
                'input',
                sincronizarFormularioGuardar
            );

        }


        /*
         * =====================================================
         * SINCRONIZAR MATERIALES AL GUARDAR
         * =====================================================
         *
         * Lee directamente los campos visibles del carrito.
         *
         * Ejemplo:
         *
         * cantidad = 10
         *
         * se convierte en:
         *
         * materiales[15][cantidad] = 10
         *
         * y:
         *
         * materiales[15][observaciones] = ...
         *
         * antes de enviar el formulario.
         */

        function sincronizarMaterialesParaGuardar() {

            if (!materialesGuardarContainer) {
                return;
            }

            materialesGuardarContainer.innerHTML = '';

            const filas = document.querySelectorAll(
                '.fila-material-carrito'
            );

            const agregarOculto = function (nombre, valor) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = nombre;
                input.value = valor == null ? '' : String(valor);
                materialesGuardarContainer.appendChild(input);
            };

            filas.forEach(function (fila) {

                const idLinea = fila.dataset.idLinea;
                const idMaterial = fila.dataset.idMaterial;

                if (!idLinea || !idMaterial) {
                    return;
                }

                const cantidad = fila.querySelector(
                    '.campo-cantidad-carrito'
                );
                const unidad = fila.querySelector(
                    '.campo-unidad-medida-carrito'
                );
                const tecnica = fila.querySelector(
                    '.campo-descripcion-tecnica-carrito'
                );
                const observaciones = fila.querySelector(
                    '.campo-observaciones-carrito'
                );
                const listaDestinos = fila.querySelector('.destinos-carrito');
                const seleccionadas = listaDestinos
                    ? Array.from(listaDestinos.querySelectorAll('.campo-destino-ficha:checked'))
                    : [];
                const cantidadTotal = parseFloat(cantidad ? cantidad.value : '0') || 0;
                seleccionadas.forEach(function (checkbox, indiceDestino) {
                    const envoltura = checkbox.closest('.destino-ficha-opcion');
                    const campoCantidad = envoltura ? envoltura.querySelector('.campo-destino-cantidad') : null;
                    const cantidadAsignada = seleccionadas.length === 1
                        ? cantidadTotal
                        : (parseFloat(campoCantidad ? campoCantidad.value : '0') || 0);
                    agregarOculto('materiales[' + idLinea + '][destinos][' + indiceDestino + '][tipo_destino]', 'ficha');
                    agregarOculto('materiales[' + idLinea + '][destinos][' + indiceDestino + '][id_ficha]', checkbox.value);
                    agregarOculto('materiales[' + idLinea + '][destinos][' + indiceDestino + '][cantidad_destinada]', cantidadAsignada);
                    agregarOculto('materiales[' + idLinea + '][destinos][' + indiceDestino + '][observaciones]', '');
                });

                agregarOculto(
                    'materiales[' + idLinea + '][id_linea]',
                    idLinea
                );
                agregarOculto(
                    'materiales[' + idLinea + '][id_material]',
                    idMaterial
                );
                agregarOculto(
                    'materiales[' + idLinea + '][cantidad]',
                    cantidad ? cantidad.value : '1'
                );
                agregarOculto(
                    'materiales[' + idLinea + '][unidad_medida]',
                    unidad ? unidad.value : ''
                );
                agregarOculto(
                    'materiales[' + idLinea + '][descripcion_tecnica]',
                    tecnica ? tecnica.value : ''
                );
                agregarOculto(
                    'materiales[' + idLinea + '][observaciones]',
                    observaciones ? observaciones.value : ''
                );

                [
                    'es_consumo',
                    'es_devolutivo',
                    'es_software',
                    'es_epp'
                ].forEach(function (campo) {

                    const checkbox = fila.querySelector(
                        '.campo-clasificacion-carrito[data-campo="' +
                        campo +
                        '"]'
                    );

                    agregarOculto(
                        'materiales[' + idLinea + '][' + campo + ']',
                        checkbox && checkbox.checked ? '1' : '0'
                    );
                });
            });
        }

        /* =====================================================
         * FICHAS DESTINO: CASILLAS Y CANTIDADES SOLO SI HAY VARIAS
         * ===================================================== */
        function actualizarControlesDestino(fila) {
            const lista = fila.querySelector('.destinos-carrito');
            if (!lista) return;
            const seleccionadas = Array.from(lista.querySelectorAll('.campo-destino-ficha:checked'));
            const cantidadTotal = parseFloat((fila.querySelector('.campo-cantidad-carrito') || {}).value || '0') || 0;
            lista.querySelectorAll('.destino-ficha-opcion').forEach(opcion => {
                const checkbox = opcion.querySelector('.campo-destino-ficha');
                const campo = opcion.querySelector('.campo-destino-cantidad');
                const wrapper = opcion.querySelector('.destino-cantidad-wrap');
                const activa = !!checkbox?.checked;
                const necesitaDistribucion = seleccionadas.length > 1;
                if (wrapper) wrapper.hidden = !(activa && necesitaDistribucion);
                if (campo) {
                    campo.disabled = !(activa && necesitaDistribucion);
                    if (!activa || !necesitaDistribucion) campo.value = '';
                }
            });
        }
        document.querySelectorAll('.fila-material-carrito').forEach(actualizarControlesDestino);
        document.addEventListener('change', function (evento) {
            if (evento.target.matches('.campo-destino-ficha')) {
                const fila = evento.target.closest('.fila-material-carrito');
                actualizarControlesDestino(fila);
            }
        });
        document.addEventListener('input', function (evento) {
            if (evento.target.matches('.campo-cantidad-carrito')) {
                actualizarControlesDestino(evento.target.closest('.fila-material-carrito'));
            }
        });

        /*
         * =====================================================
         * CARGAR ÁREAS AL CAMBIAR PROCESO
         * =====================================================
         */

        if (
            !proceso ||
            !area
        ) {

            return;

        }


        proceso.addEventListener(
            'change',
            function () {

                const idProceso =
                    this.value;


                /*
                 * Guardar temporalmente el estado actual
                 * del encabezado.
                 */

                if (titulo) {

                    sessionStorage.setItem(
                        'sita_materiales_titulo',
                        titulo.value
                    );

                }


                if (justificacion) {

                    sessionStorage.setItem(
                        'sita_materiales_justificacion',
                        justificacion.value
                    );

                }


                /*
                 * El área anterior deja de ser válida.
                 */

                area.innerHTML = '';


                if (!idProceso) {

                    const option =
                        document.createElement(
                            'option'
                        );

                    option.value = '';

                    option.textContent =
                        'Seleccione primero un proceso';

                    area.appendChild(
                        option
                    );

                    return;

                }


                /*
                 * Mostrar estado de carga.
                 */

                const cargando =
                    document.createElement(
                        'option'
                    );

                cargando.value = '';

                cargando.textContent =
                    'Cargando áreas...';

                area.appendChild(
                    cargando
                );


                /*
                 * AJAX
                 */

                fetch(
                    '<?= e(
                        url(
                            '/materiales/solicitudes/areas'
                        )
                    ) ?>?id_proceso=' +
                    encodeURIComponent(
                        idProceso
                    ),
                    {
                        method: 'GET',

                        headers: {
                            'Accept':
                                'application/json'
                        }
                    }
                )
                .then(
                    function (response) {

                        if (!response.ok) {

                            throw new Error(
                                'Error HTTP'
                            );

                        }

                        return response.json();

                    }
                )
                .then(
                    function (data) {

                        area.innerHTML = '';


                        const inicial =
                            document.createElement(
                                'option'
                            );

                        inicial.value = '';

                        inicial.textContent =
                            'Seleccione un área';

                        area.appendChild(
                            inicial
                        );


                        if (
                            !data.ok ||
                            !Array.isArray(
                                data.areas
                            )
                        ) {

                            return;

                        }


                        data.areas.forEach(
                            function (item) {

                                const option =
                                    document.createElement(
                                        'option'
                                    );

                                option.value =
                                    item.id;

                                option.textContent =
                                    item.nombre_area;

                                area.appendChild(
                                    option
                                );

                            }
                        );

                    }
                )
                .catch(
                    function () {

                        area.innerHTML = '';


                        const option =
                            document.createElement(
                                'option'
                            );

                        option.value = '';

                        option.textContent =
                            'No fue posible cargar las áreas';

                        area.appendChild(
                            option
                        );

                    }
                );

            }
        );


        /*
         * =====================================================
         * VALIDACIÓN FINAL
         * =====================================================
         */

        if (formularioGuardar) {

            formularioGuardar.addEventListener(
                'submit',
                function (evento) {

                    sincronizarFormularioGuardar();


                    /*
                     * ANTES DE VALIDAR Y ENVIAR:
                     *
                     * Tomar las cantidades y observaciones
                     * actuales del carrito.
                     */
                    sincronizarMaterialesParaGuardar();


                    /*
                     * PROCESO
                     */

                    if (
                        !proceso.value ||
                        parseInt(
                            proceso.value,
                            10
                        ) <= 0
                    ) {

                        evento.preventDefault();

                        proceso.focus();

                        alert(
                            'Debe seleccionar un proceso.'
                        );

                        return;

                    }


                    /*
                     * ÁREA
                     */

                    if (
                        !area.value ||
                        parseInt(
                            area.value,
                            10
                        ) <= 0
                    ) {

                        evento.preventDefault();

                        area.focus();

                        alert(
                            'Debe seleccionar un área responsable.'
                        );

                        return;

                    }


                    /*
                     * TÍTULO
                     */

                    if (
                        !titulo.value.trim()
                    ) {

                        evento.preventDefault();

                        titulo.focus();

                        alert(
                            'Debe ingresar el título de la solicitud.'
                        );

                        return;

                    }


                    /*
                     * MATERIAL
                     */

                    if (
                        <?= empty($carrito)
                            ? 'true'
                            : 'false' ?>
                    ) {

                        evento.preventDefault();

                        alert(
                            'Debe agregar al menos un material a la solicitud.'
                        );

                        return;

                    }


                    /* Validar fichas y suma exacta de cantidades destinadas. */
                    const filasMaterial = document.querySelectorAll('.fila-material-carrito');
                    for (const fila of filasMaterial) {
                        const cantidadSolicitada = parseFloat((fila.querySelector('.campo-cantidad-carrito') || {}).value || '0');
                        const lista = fila.querySelector('.destinos-carrito');
                        const seleccionadas = lista ? Array.from(lista.querySelectorAll('.campo-destino-ficha:checked')) : [];
                        if (seleccionadas.length === 0) {
                            evento.preventDefault();
                            alert('Marque al menos una ficha de destino para cada producto.');
                            return;
                        }
                        let sumaDestinada = 0;
                        for (const checkbox of seleccionadas) {
                            const envoltura = checkbox.closest('.destino-ficha-opcion');
                            const campoCantidad = envoltura ? envoltura.querySelector('.campo-destino-cantidad') : null;
                            const cantidadDestino = seleccionadas.length === 1
                                ? cantidadSolicitada
                                : parseFloat(campoCantidad ? campoCantidad.value : '0');
                            if (!Number.isFinite(cantidadDestino) || cantidadDestino <= 0) {
                                evento.preventDefault();
                                if (campoCantidad) campoCantidad.focus();
                                alert('Indique una cantidad válida para cada ficha marcada.');
                                return;
                            }
                            sumaDestinada += cantidadDestino;
                        }
                        if (Math.abs(sumaDestinada - cantidadSolicitada) > 0.00001) {
                            evento.preventDefault();
                            alert('La suma de las cantidades por ficha debe ser exactamente igual a la cantidad total solicitada.');
                            return;
                        }
                    }

                    /*
                     * VALIDAR CANTIDADES
                     *
                     * Se valida también en el navegador antes
                     * de enviar el formulario.
                     */

                    const camposCantidad =
                        document.querySelectorAll(
                            '.campo-cantidad-carrito'
                        );


                    for (
                        let i = 0;
                        i < camposCantidad.length;
                        i++
                    ) {

                        const cantidad =
                            parseFloat(
                                camposCantidad[i].value
                            );


                        if (
                            Number.isNaN(cantidad) ||
                            cantidad <= 0
                        ) {

                            evento.preventDefault();

                            camposCantidad[i].focus();

                            alert(
                                'Todas las cantidades deben ser mayores que cero.'
                            );

                            return;

                        }

                    }

                }
            );

        }

    }
);
</script>