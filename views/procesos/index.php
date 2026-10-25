<?php

declare(strict_types=1);

require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';

$procesos = is_array($procesos ?? null)
    ? $procesos
    : [];

$resumenes = is_array($resumenes ?? null)
    ? $resumenes
    : [];

$estados = [
    'borrador' => 'Borrador',
    'abierto' => 'Abierto',
    'en_solicitud' => 'En solicitud',
    'en_aval' => 'En aval',
    'en_compra' => 'En compra',
    'en_recepcion' => 'En recepción',
    'en_distribucion' => 'En distribución',
    'cerrado' => 'Cerrado',
    'cancelado' => 'Cancelado',
];

$clasesEstado = [
    'borrador' => 'secondary',
    'abierto' => 'success',
    'en_solicitud' => 'primary',
    'en_aval' => 'warning',
    'en_compra' => 'info',
    'en_recepcion' => 'info',
    'en_distribucion' => 'primary',
    'cerrado' => 'dark',
    'cancelado' => 'danger',
];

$puedeCrear =
    Auth::hasRole('Administrador')
    || Auth::can('materiales.procesos.crear');

$puedeEstado =
    Auth::hasRole('Administrador')
    || Auth::can('materiales.procesos.estado');

$puedeEliminar =
    Auth::hasRole('Administrador')
    || Auth::can('materiales.procesos.estado');
?>

<div class="container-fluid py-4">

    <!-- =========================================================
         ENCABEZADO
         ========================================================= -->

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">

        <div>
            <h2 class="mb-1">
                <i class="bi bi-calendar3 me-2"></i>
                Procesos de materiales
            </h2>

            <p class="text-muted mb-0">
                Administración de los procesos para la gestión de
                solicitudes de materiales de formación.
            </p>
        </div>

        <?php if ($puedeCrear): ?>

            <a
                href="<?= e(url('/materiales/procesos/crear')) ?>"
                class="btn btn-primary"
            >
                <i class="bi bi-plus-circle me-1"></i>
                Nuevo proceso
            </a>

        <?php endif; ?>

    </div>

    <!-- =========================================================
         MENSAJES
         ========================================================= -->

    <?php if (!empty($_SESSION['flash'])): ?>

        <?php
        $flash = $_SESSION['flash'];

        if (is_array($flash)) {
            $tipoFlash = $flash['tipo'] ?? 'info';
            $mensajeFlash = $flash['mensaje'] ?? '';
        } else {
            $tipoFlash = 'info';
            $mensajeFlash = (string) $flash;
        }

        unset($_SESSION['flash']);

        $alertas = [
            'success' => 'success',
            'error' => 'danger',
            'warning' => 'warning',
            'info' => 'info',
        ];

        $tipoAlerta = $alertas[$tipoFlash] ?? 'info';
        ?>

        <?php if ($mensajeFlash !== ''): ?>

            <div
                class="alert alert-<?= e($tipoAlerta) ?> alert-dismissible fade show"
                role="alert"
            >
                <?= $mensajeFlash ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Cerrar"
                ></button>
            </div>

        <?php endif; ?>

    <?php endif; ?>

    <!-- =========================================================
         TARJETA PRINCIPAL
         ========================================================= -->

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white py-3">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

                <div>
                    <h5 class="mb-1">
                        Listado de procesos
                    </h5>

                    <small class="text-muted">
                        <?= count($procesos) ?>
                        proceso<?= count($procesos) === 1 ? '' : 's' ?>
                        registrado<?= count($procesos) === 1 ? '' : 's' ?>
                    </small>
                </div>

            </div>

        </div>

        <div class="card-body p-0">

            <?php if ($procesos === []): ?>

                <!-- =================================================
                     SIN REGISTROS
                     ================================================= -->

                <div class="text-center py-5 px-3">

                    <div
                        class="mb-3"
                        style="font-size: 3rem;"
                    >
                        📅
                    </div>

                    <h5 class="mb-2">
                        No hay procesos registrados
                    </h5>

                    <p class="text-muted mb-4">
                        Actualmente no existen procesos de materiales
                        configurados en el sistema.
                    </p>

                    <?php if ($puedeCrear): ?>

                        <a
                            href="<?= e(url('/materiales/procesos/crear')) ?>"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-plus-circle me-1"></i>
                            Crear primer proceso
                        </a>

                    <?php endif; ?>

                </div>

            <?php else: ?>

                <!-- =================================================
                     TABLA
                     ================================================= -->

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">

                            <tr>

                                <th class="px-3">
                                    Año
                                </th>

                                <th>
                                    Proceso
                                </th>

                                <th>
                                    Vigencia
                                </th>

                                <th>
                                    Coordinador
                                </th>

                                <th class="text-center">
                                    Áreas
                                </th>

                                <th class="text-center">
                                    Solicitudes
                                </th>

                                <th class="text-center">
                                    Estado
                                </th>

                                <th
                                    class="text-end px-3"
                                    style="min-width: 190px;"
                                >
                                    Acciones
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($procesos as $proceso): ?>

                                <?php
                                $id = (int) ($proceso['id'] ?? 0);
                                $anio = (int) ($proceso['anio'] ?? 0);

                                $nombre = (string) (
                                    $proceso['nombre'] ?? ''
                                );

                                $descripcion = trim(
                                    (string) (
                                        $proceso['descripcion'] ?? ''
                                    )
                                );

                                $fechaInicio = (string) (
                                    $proceso['fecha_inicio'] ?? ''
                                );

                                $fechaCierre = (string) (
                                    $proceso['fecha_cierre'] ?? ''
                                );

                                $estado = (string) (
                                    $proceso['estado'] ?? 'borrador'
                                );

                                $coordinador = trim(
                                    (string) (
                                        $proceso['coordinador'] ?? ''
                                    )
                                );

                                $resumen = $resumenes[$id] ?? [];

                                $cantidadAreas = (int) (
                                    $resumen['areas'] ?? 0
                                );

                                $cantidadSolicitudes = (int) (
                                    $resumen['solicitudes'] ?? 0
                                );

                                $textoEstado =
                                    $estados[$estado]
                                    ?? ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $estado
                                        )
                                    );

                                $claseEstado =
                                    $clasesEstado[$estado]
                                    ?? 'secondary';
                                ?>

                                <tr>

                                    <!-- AÑO -->

                                    <td class="px-3 fw-semibold">
                                        <?= e((string) $anio) ?>
                                    </td>

                                    <!-- PROCESO -->

                                    <td>

                                        <div class="fw-semibold">
                                            <?= e($nombre) ?>
                                        </div>

                                        <?php if ($descripcion !== ''): ?>

                                            <div
                                                class="text-muted small mt-1"
                                                style="max-width: 350px;"
                                            >
                                                <?= e($descripcion) ?>
                                            </div>

                                        <?php endif; ?>

                                    </td>

                                    <!-- FECHAS -->

                                    <td>

                                        <?php if ($fechaInicio !== ''): ?>

                                            <div class="small">
                                                <strong>Inicio:</strong>
                                                <?= e(fecha($fechaInicio)) ?>
                                            </div>

                                        <?php endif; ?>

                                        <?php if ($fechaCierre !== ''): ?>

                                            <div class="small">
                                                <strong>Cierre:</strong>
                                                <?= e(fecha($fechaCierre)) ?>
                                            </div>

                                        <?php endif; ?>

                                        <?php if (
                                            $fechaInicio === ''
                                            && $fechaCierre === ''
                                        ): ?>

                                            <span class="text-muted">
                                                Sin fechas
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <!-- COORDINADOR -->

                                    <td>

                                        <?php if ($coordinador !== ''): ?>

                                            <?= e($coordinador) ?>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                Sin asignar
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <!-- ÁREAS -->

                                    <td class="text-center">

                                        <span class="badge text-bg-light border">
                                            <?= e((string) $cantidadAreas) ?>
                                        </span>

                                    </td>

                                    <!-- SOLICITUDES -->

                                    <td class="text-center">

                                        <span class="badge text-bg-light border">
                                            <?= e((string) $cantidadSolicitudes) ?>
                                        </span>

                                    </td>

                                    <!-- ESTADO -->

                                    <td class="text-center">

                                        <span
                                            class="badge text-bg-<?= e($claseEstado) ?>"
                                        >
                                            <?= e($textoEstado) ?>
                                        </span>

                                    </td>

                                    <!-- ACCIONES -->

                                    <td class="text-end px-3">

                                        <div
                                            class="d-flex justify-content-end gap-1"
                                        >

                                            <?php if ($puedeCrear): ?>

                                                <a
                                                    href="<?= e(
                                                        url(
                                                            '/materiales/procesos/editar?id='
                                                            . $id
                                                        )
                                                    ) ?>"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="Editar proceso"
                                                >
                                                    <i class="bi bi-pencil"></i>
                                                </a>

                                            <?php endif; ?>

                                            <?php if ($puedeEstado): ?>

                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    title="Cambiar estado"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEstado<?= $id ?>"
                                                >
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>

                                            <?php endif; ?>

                                            <?php if (
                                                $puedeEliminar
                                                && $cantidadAreas === 0
                                                && $cantidadSolicitudes === 0
                                            ): ?>

                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Eliminar proceso"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEliminar<?= $id ?>"
                                                >
                                                    <i class="bi bi-trash"></i>
                                                </button>

                                            <?php endif; ?>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>


<!-- =============================================================
     MODALES DE CAMBIO DE ESTADO
     ============================================================= -->

<?php foreach ($procesos as $proceso): ?>

    <?php
    $id = (int) ($proceso['id'] ?? 0);
    $estadoActual = (string) (
        $proceso['estado'] ?? 'borrador'
    );
    ?>

    <?php if ($puedeEstado): ?>

        <div
            class="modal fade"
            id="modalEstado<?= $id ?>"
            tabindex="-1"
            aria-hidden="true"
        >

            <div class="modal-dialog modal-dialog-centered">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title">
                            Cambiar estado del proceso
                        </h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Cerrar"
                        ></button>

                    </div>

                    <form
                        method="post"
                        action="<?= e(
                            url('/materiales/procesos/estado')
                        ) ?>"
                    >

                        <?= csrfField() ?>

                        <input
                            type="hidden"
                            name="id"
                            value="<?= e((string) $id) ?>"
                        >

                        <div class="modal-body">

                            <p class="text-muted">
                                Seleccione el nuevo estado del proceso.
                            </p>

                            <label
                                for="estado<?= $id ?>"
                                class="form-label"
                            >
                                Estado
                            </label>

                            <select
                                name="estado"
                                id="estado<?= $id ?>"
                                class="form-select"
                                required
                            >

                                <?php foreach (
                                    $estados as $valor => $texto
                                ): ?>

                                    <option
                                        value="<?= e($valor) ?>"
                                        <?= $estadoActual === $valor
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= e($texto) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="modal-footer">

                            <button
                                type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal"
                            >
                                Cancelar
                            </button>

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                Guardar estado
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    <?php endif; ?>

<?php endforeach; ?>


<!-- =============================================================
     MODALES DE ELIMINACIÓN
     ============================================================= -->

<?php foreach ($procesos as $proceso): ?>

    <?php
    $id = (int) ($proceso['id'] ?? 0);
    $nombre = (string) ($proceso['nombre'] ?? '');
    ?>

    <?php if ($puedeEliminar): ?>

        <div
            class="modal fade"
            id="modalEliminar<?= $id ?>"
            tabindex="-1"
            aria-hidden="true"
        >

            <div class="modal-dialog modal-dialog-centered">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title">
                            Eliminar proceso
                        </h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Cerrar"
                        ></button>

                    </div>

                    <form
                        method="post"
                        action="<?= e(
                            url('/materiales/procesos/eliminar')
                        ) ?>"
                    >

                        <?= csrfField() ?>

                        <input
                            type="hidden"
                            name="id"
                            value="<?= e((string) $id) ?>"
                        >

                        <div class="modal-body">

                            <p>
                                ¿Está seguro de eliminar el proceso
                                <strong>
                                    <?= e($nombre) ?>
                                </strong>?
                            </p>

                            <div class="alert alert-warning mb-0">

                                <i class="bi bi-exclamation-triangle me-1"></i>

                                Esta acción solamente está disponible
                                para procesos que todavía no tienen áreas
                                ni solicitudes relacionadas.

                            </div>

                        </div>

                        <div class="modal-footer">

                            <button
                                type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal"
                            >
                                Cancelar
                            </button>

                            <button
                                type="submit"
                                class="btn btn-danger"
                            >
                                <i class="bi bi-trash me-1"></i>
                                Eliminar
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    <?php endif; ?>

<?php endforeach; ?>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>