<?php

declare(strict_types=1);
set_time_limit(180);

require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';

$importacion = is_array($importacion ?? null) ? $importacion : [];
$detalles = is_array($detalles ?? null) ? $detalles : [];

$mensajeExito = flash('success');
$mensajeError = flash('error');

$idImportacion = (int) ($importacion['id'] ?? 0);

$estado = strtolower(
    trim((string) ($importacion['estado'] ?? ''))
);

$nombreArchivo = (string) (
    $importacion['nombre_archivo'] ?? 'Sin nombre'
);

$anioReferencia = $importacion['anio_referencia'] ?? null;

$totalFilas = (int) (
    $importacion['total_filas'] ?? 0
);

/*
 * La cantidad de hojas procesadas ahora se obtiene directamente
 * de materiales_importaciones.
 *
 * Esto evita depender de que cada detalle tenga almacenado
 * el nombre de la hoja.
 */
$totalHojas = (int) (
    $importacion['hojas_procesadas'] ?? 0
);

$nuevos = (int) (
    $importacion['registros_nuevos'] ?? 0
);

$modificados = (int) (
    $importacion['registros_modificados'] ?? 0
);

$sinCambios = (int) (
    $importacion['registros_sin_cambios'] ?? 0
);

$inactivados = (int) (
    $importacion['registros_inactivados'] ?? 0
);

$errores = (int) (
    $importacion['registros_con_error'] ?? 0
);

$puedeAplicar = (
    $estado === 'validada'
    && $errores === 0
    && $idImportacion > 0
);

$claseEstado = match ($estado) {
    'aplicada' => 'success',
    'validada' => 'primary',
    'lectura' => 'warning',
    'cancelada' => 'secondary',
    'error' => 'danger',
    default => 'secondary',
};

$textoEstado = match ($estado) {
    'aplicada' => 'Aplicada',
    'validada' => 'Validada',
    'lectura' => 'Lectura',
    'cancelada' => 'Cancelada',
    'error' => 'Error',
    default => ucfirst($estado),
};

/*
 * Obtener los nombres de las hojas únicamente para mostrarlos
 * visualmente cuando estén disponibles en los detalles.
 *
 * La cantidad total de hojas YA NO depende de este arreglo.
 */
$hojas = [];

foreach ($detalles as $detalle) {

    $hoja = trim((string) ($detalle['hoja'] ?? ''));

    if ($hoja !== '' && !in_array($hoja, $hojas, true)) {
        $hojas[] = $hoja;
    }
}


/*
 * Contadores de acciones calculados también desde el detalle.
 * Sirven como respaldo visual si algún valor del encabezado
 * no estuviera disponible.
 */
$conteoAcciones = [
    'nuevo' => 0,
    'modificado' => 0,
    'sin_cambios' => 0,
    'inactivado' => 0,
    'error' => 0,
];

foreach ($detalles as $detalle) {

    $accion = strtolower(
        trim((string) ($detalle['accion'] ?? ''))
    );

    if (array_key_exists($accion, $conteoAcciones)) {
        $conteoAcciones[$accion]++;
    }
}


/*
 * Si existen detalles, utilizamos sus cantidades para mostrar
 * información consistente con la previsualización.
 */
if (!empty($detalles)) {

    $nuevos = $conteoAcciones['nuevo'];
    $modificados = $conteoAcciones['modificado'];
    $sinCambios = $conteoAcciones['sin_cambios'];
    $inactivados = $conteoAcciones['inactivado'];
    $errores = $conteoAcciones['error'];
}

$puedeAplicar = (
    $estado === 'validada'
    && $errores === 0
    && $idImportacion > 0
);

?>

<div class="container-fluid py-4">

    <!-- ============================================================
         ENCABEZADO
         ============================================================ -->

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>

            <div class="d-flex align-items-center gap-2 mb-1">

                <h2 class="fw-bold mb-0">
                    <i class="bi bi-search me-2"></i>
                    Vista previa de importación
                </h2>

                <span class="badge text-bg-<?= e($claseEstado) ?>">
                    <?= e($textoEstado) ?>
                </span>

            </div>

            <p class="text-muted mb-0">
                Revise los registros encontrados antes de modificar el catálogo institucional.
            </p>

        </div>

        <div>

            <a
                href="<?= e(url('materiales/importacion')) ?>"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-1"></i>
                Volver a importaciones
            </a>

        </div>

    </div>


    <!-- ============================================================
         MENSAJES
         ============================================================ -->

    <?php if (!empty($mensajeExito)): ?>

        <div
            class="alert alert-success alert-dismissible fade show shadow-sm"
            role="alert"
        >

            <i class="bi bi-check-circle-fill me-2"></i>

            <?= e($mensajeExito) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Cerrar"
            ></button>

        </div>

    <?php endif; ?>


    <?php if (!empty($mensajeError)): ?>

        <div
            class="alert alert-danger alert-dismissible fade show shadow-sm"
            role="alert"
        >

            <i class="bi bi-exclamation-triangle-fill me-2"></i>

            <?= e($mensajeError) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Cerrar"
            ></button>

        </div>

    <?php endif; ?>


    <!-- ============================================================
         INFORMACIÓN DEL ARCHIVO
         ============================================================ -->

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-header bg-white border-bottom py-3">

            <div class="d-flex align-items-center">

                <div
                    class="me-3"
                    style="
                        width:44px;
                        height:44px;
                        border-radius:12px;
                        background:#e8f5e9;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                    "
                >

                    <i class="bi bi-file-earmark-excel text-success fs-4"></i>

                </div>

                <div>

                    <h5 class="fw-bold mb-1">
                        Información del archivo
                    </h5>

                    <small class="text-muted">
                        Datos generales de la lectura realizada.
                    </small>

                </div>

            </div>

        </div>


        <div class="card-body">

            <div class="row g-4">

                <div class="col-md-6 col-lg-4">

                    <div class="text-muted small mb-1">
                        Archivo
                    </div>

                    <div class="fw-semibold text-break">
                        <i class="bi bi-file-earmark-excel text-success me-1"></i>
                        <?= e($nombreArchivo) ?>
                    </div>

                </div>


                <div class="col-md-6 col-lg-2">

                    <div class="text-muted small mb-1">
                        Año de referencia
                    </div>

                    <div class="fw-semibold">

                        <?php if ($anioReferencia !== null && $anioReferencia !== ''): ?>

                            <?= e((string) $anioReferencia) ?>

                        <?php else: ?>

                            <span class="text-muted">
                                No definido
                            </span>

                        <?php endif; ?>

                    </div>

                </div>


                <div class="col-md-6 col-lg-2">

                    <div class="text-muted small mb-1">
                        Hojas procesadas
                    </div>

                    <div class="fw-semibold">
                        <?= entero($totalHojas) ?>
                    </div>

                </div>


                <div class="col-md-6 col-lg-2">

                    <div class="text-muted small mb-1">
                        Total de filas
                    </div>

                    <div class="fw-semibold">
                        <?= entero($totalFilas) ?>
                    </div>

                </div>


                <div class="col-md-6 col-lg-2">

                    <div class="text-muted small mb-1">
                        Estado
                    </div>

                    <div>

                        <span class="badge text-bg-<?= e($claseEstado) ?>">
                            <?= e($textoEstado) ?>
                        </span>

                    </div>

                </div>

            </div>


            <?php if (!empty($hojas)): ?>

                <hr class="my-4">

                <div class="text-muted small mb-2">
                    Hojas encontradas en el archivo
                </div>

                <div class="d-flex flex-wrap gap-2">

                    <?php foreach ($hojas as $indice => $hoja): ?>

                        <span class="badge bg-light text-dark border">

                            <i class="bi bi-file-earmark-spreadsheet me-1"></i>

                            <?= e($hoja) ?>

                        </span>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- ============================================================
         RESUMEN
         ============================================================ -->

    <div class="row g-3 mb-4">

        <!-- NUEVOS -->

        <div class="col-6 col-xl-2">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <div class="text-muted small">
                                Nuevos
                            </div>

                            <div class="fs-3 fw-bold text-success">
                                <?= entero($nuevos) ?>
                            </div>

                        </div>

                        <i class="bi bi-plus-circle text-success fs-3"></i>

                    </div>

                    <small class="text-muted">
                        Se crearán en el catálogo
                    </small>

                </div>

            </div>

        </div>


        <!-- MODIFICADOS -->

        <div class="col-6 col-xl-2">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <div class="text-muted small">
                                Modificados
                            </div>

                            <div class="fs-3 fw-bold text-primary">
                                <?= entero($modificados) ?>
                            </div>

                        </div>

                        <i class="bi bi-pencil-square text-primary fs-3"></i>

                    </div>

                    <small class="text-muted">
                        Se conserva el historial de importaciones
                    </small>

                </div>

            </div>

        </div>


        <!-- SIN CAMBIOS -->

        <div class="col-6 col-xl-2">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <div class="text-muted small">
                                Sin cambios
                            </div>

                            <div class="fs-3 fw-bold text-secondary">
                                <?= entero($sinCambios) ?>
                            </div>

                        </div>

                        <i class="bi bi-check2-circle text-secondary fs-3"></i>

                    </div>

                    <small class="text-muted">
                        No requieren modificación
                    </small>

                </div>

            </div>

        </div>


        <!-- INACTIVADOS -->

        <div class="col-6 col-xl-2">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <div class="text-muted small">
                                Inactivados
                            </div>

                            <div class="fs-3 fw-bold text-secondary">
                                <?= entero($inactivados) ?>
                            </div>

                        </div>

                        <i class="bi bi-pause-circle text-secondary fs-3"></i>

                    </div>

                    <small class="text-muted">
                        No se inactivan automáticamente
                    </small>

                </div>

            </div>

        </div>


        <!-- ERRORES -->

        <div class="col-6 col-xl-2">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <div class="text-muted small">
                                Errores
                            </div>

                            <div class="fs-3 fw-bold <?= $errores > 0 ? 'text-danger' : 'text-success' ?>">
                                <?= entero($errores) ?>
                            </div>

                        </div>

                        <i class="bi bi-exclamation-circle <?= $errores > 0 ? 'text-danger' : 'text-success' ?> fs-3"></i>

                    </div>

                    <small class="text-muted">
                        Registros que requieren revisión
                    </small>

                </div>

            </div>

        </div>


        <!-- TOTAL -->

        <div class="col-6 col-xl-2">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <div class="text-muted small">
                                Total
                            </div>

                            <div class="fs-3 fw-bold">
                                <?= entero($totalFilas) ?>
                            </div>

                        </div>

                        <i class="bi bi-list-check text-dark fs-3"></i>

                    </div>

                    <small class="text-muted">
                        Filas procesadas
                    </small>

                </div>

            </div>

        </div>

    </div>


    <!-- ============================================================
         MENSAJE DE VALIDACIÓN
         ============================================================ -->

    <?php if ($errores > 0): ?>

        <div class="alert alert-danger shadow-sm">

            <div class="d-flex align-items-start">

                <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>

                <div>

                    <strong>No es posible aplicar esta importación.</strong>

                    <div class="mt-1">
                        Se encontraron
                        <strong><?= entero($errores) ?></strong>
                        registro<?= $errores === 1 ? '' : 's' ?>
                        con error.
                    </div>

                    <div class="small mt-1">
                        Revise el detalle de los registros y corrija el archivo
                        antes de realizar una nueva importación.
                    </div>

                </div>

            </div>

        </div>

    <?php elseif ($estado === 'validada'): ?>

        <div class="alert alert-success shadow-sm">

            <div class="d-flex align-items-start">

                <i class="bi bi-check-circle-fill fs-4 me-3"></i>

                <div>

                    <strong>Importación validada correctamente.</strong>

                    <div class="mt-1">
                        No se encontraron errores en los registros.
                    </div>

                    <div class="small mt-1">
                        Puede aplicar la importación para actualizar el catálogo institucional.
                    </div>

                </div>

            </div>

        </div>

    <?php elseif ($estado === 'aplicada'): ?>

        <div class="alert alert-info shadow-sm">

            <div class="d-flex align-items-start">

                <i class="bi bi-info-circle-fill fs-4 me-3"></i>

                <div>

                    <strong>Esta importación ya fue aplicada.</strong>

                    <div class="small mt-1">
                        El detalle se conserva como historial de la operación realizada.
                    </div>

                </div>

            </div>

        </div>

    <?php endif; ?>


    <!-- ============================================================
         DETALLE DE REGISTROS
         ============================================================ -->

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-header bg-white border-bottom py-3">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

                <div>

                    <h5 class="fw-bold mb-1">
                        <i class="bi bi-table me-2"></i>
                        Detalle de la importación
                    </h5>

                    <small class="text-muted">
                        Cada fila representa un registro leído desde el archivo Excel.
                    </small>

                </div>

                <span class="badge bg-light text-dark border">
                    <?= entero(count($detalles)) ?> registros
                </span>

            </div>

        </div>


        <div class="card-body p-0">

            <?php if (empty($detalles)): ?>

                <div class="text-center py-5 px-3">

                    <i
                        class="bi bi-table text-muted"
                        style="font-size:3rem;"
                    ></i>

                    <h6 class="fw-bold mt-3">
                        No hay registros para mostrar
                    </h6>

                    <p class="text-muted mb-0">
                        La importación no contiene detalles registrados.
                    </p>

                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">

                            <tr>

                                <th class="ps-4 text-center">
                                    Fila
                                </th>

                                <th>
                                    Hoja
                                </th>

                                <th>
                                    Código UNSPSC
                                </th>

                                <th>
                                    Descripción
                                </th>

                                <th class="text-center">
                                    Acción
                                </th>

                                <th>
                                    Observación
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($detalles as $detalle): ?>

                                <?php

                                $accion = strtolower(
                                    trim((string) ($detalle['accion'] ?? ''))
                                );

                                $claseAccion = match ($accion) {
                                    'nuevo' => 'success',
                                    'modificado' => 'primary',
                                    'sin_cambios' => 'secondary',
                                    'inactivado' => 'warning',
                                    'error' => 'danger',
                                    default => 'secondary',
                                };

                                $textoAccion = match ($accion) {
                                    'nuevo' => 'Nuevo',
                                    'modificado' => 'Modificado',
                                    'sin_cambios' => 'Sin cambios',
                                    'inactivado' => 'Inactivado',
                                    'error' => 'Error',
                                    default => ucfirst($accion),
                                };

                                $filaExcel = (int) (
                                    $detalle['fila_excel'] ?? 0
                                );

                                ?>

                                <tr>

                                    <td class="ps-4 text-center">

                                        <span class="badge bg-light text-dark border">
                                            <?= entero($filaExcel) ?>
                                        </span>

                                    </td>


                                    <td>

                                        <small>
                                            <?= e(
                                                (string) (
                                                    $detalle['hoja']
                                                    ?? '—'
                                                )
                                            ) ?>
                                        </small>

                                    </td>


                                    <td>

                                        <?php if (!empty($detalle['codigo_unspsc'])): ?>

                                            <code>
                                                <?= e(
                                                    (string) $detalle['codigo_unspsc']
                                                ) ?>
                                            </code>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                —
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td style="min-width:320px;">

                                        <?php if (!empty($detalle['descripcion'])): ?>

                                            <div class="text-break">
                                                <?= e(
                                                    (string) $detalle['descripcion']
                                                ) ?>
                                            </div>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                —
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td class="text-center">

                                        <span class="badge text-bg-<?= e($claseAccion) ?>">
                                            <?= e($textoAccion) ?>
                                        </span>

                                    </td>


                                    <td style="min-width:280px;">

                                        <?php if (!empty($detalle['mensaje'])): ?>

                                            <span class="<?= $accion === 'error' ? 'text-danger' : 'text-muted' ?>">

                                                <?= e(
                                                    (string) $detalle['mensaje']
                                                ) ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                —
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- ============================================================
         CONFIRMACIÓN / ACCIONES
         ============================================================ -->

    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">

                <div>

                    <?php if ($puedeAplicar): ?>

                        <div class="fw-semibold">
                            <i class="bi bi-shield-check text-success me-1"></i>
                            Importación lista para aplicar
                        </div>

                        <div class="small text-muted">
                            La operación actualizará únicamente los registros
                            identificados como nuevos o modificados.
                        </div>

                    <?php elseif ($estado === 'aplicada'): ?>

                        <div class="fw-semibold">
                            <i class="bi bi-check-circle text-success me-1"></i>
                            Importación aplicada
                        </div>

                        <div class="small text-muted">
                            Esta operación ya fue ejecutada y forma parte del historial.
                        </div>

                    <?php elseif ($errores > 0): ?>

                        <div class="fw-semibold text-danger">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Importación no disponible para aplicar
                        </div>

                        <div class="small text-muted">
                            Debe corregir los errores y realizar una nueva lectura.
                        </div>

                    <?php else: ?>

                        <div class="fw-semibold">
                            <i class="bi bi-info-circle me-1"></i>
                            Importación pendiente de validación
                        </div>

                        <div class="small text-muted">
                            El catálogo no ha sido modificado por esta operación.
                        </div>

                    <?php endif; ?>

                </div>


                <div class="d-flex flex-wrap gap-2">

                    <a
                        href="<?= e(url('materiales/importacion')) ?>"
                        class="btn btn-outline-secondary"
                    >
                        <i class="bi bi-arrow-left me-1"></i>
                        Volver
                    </a>


                    <?php if ($puedeAplicar): ?>

                        <form
                            method="POST"
                            action="<?= e(url('materiales/importacion/aplicar')) ?>"
                            id="formAplicarImportacion"
                            class="d-inline"
                        >

                            <?= csrfField() ?>

                            <input
                                type="hidden"
                                name="id"
                                value="<?= $idImportacion ?>"
                            >

                            <button
                                type="submit"
                                class="btn btn-success"
                                id="btnAplicar"
                            >

                                <i class="bi bi-check2-circle me-1"></i>
                                Aplicar importación

                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>





<!-- ================================================================
     MODAL DE CONFIRMACIÓN
     ================================================================ -->

<?php if ($puedeAplicar): ?>

    <div
        class="modal fade"
        id="modalConfirmarImportacion"
        tabindex="-1"
        aria-labelledby="modalConfirmarImportacionLabel"
        aria-hidden="true"
    >

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <div class="modal-header">

                    <h5
                        class="modal-title fw-bold"
                        id="modalConfirmarImportacionLabel"
                    >
                        Confirmar importación
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar"
                    ></button>

                </div>


                <div class="modal-body">

                    <div class="text-center mb-3">

                        <i
                            class="bi bi-file-earmark-check text-success"
                            style="font-size:3rem;"
                        ></i>

                    </div>

                    <p class="text-center mb-3">

                        Está a punto de aplicar la importación:

                        <br>

                        <strong class="text-break">
                            <?= e($nombreArchivo) ?>
                        </strong>

                    </p>


                    <div class="alert alert-warning mb-0">

                        <div class="small">

                            <strong>Antes de continuar:</strong>

                            <ul class="mb-0 mt-2">

                                <li>
                                    <?= entero($nuevos) ?>
                                    registro<?= $nuevos === 1 ? '' : 's' ?>
                                    nuevo<?= $nuevos === 1 ? '' : 's' ?>
                                    se incorporará<?= $nuevos === 1 ? '' : 'n' ?>
                                    al catálogo.
                                </li>

                                <li>
                                    <?= entero($modificados) ?>
                                    registro<?= $modificados === 1 ? '' : 's' ?>
                                    será<?= $modificados === 1 ? '' : 'n' ?>
                                    actualizado<?= $modificados === 1 ? '' : 's' ?>.
                                </li>

                                <li>
                                    Los registros sin cambios no serán modificados.
                                </li>

                                <li>
                                    Los materiales que no estén en el archivo
                                    no serán inactivados.
                                </li>

                            </ul>

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancelar
                    </button>

                    <button
                        type="button"
                        class="btn btn-success"
                        id="btnConfirmarAplicacion"
                    >
                        <i class="bi bi-check2-circle me-1"></i>
                        Sí, aplicar importación
                    </button>

                </div>

            </div>

        </div>

    </div>

<?php endif; ?>


<!-- ================================================================
     JAVASCRIPT
     ================================================================ -->

<?php if ($puedeAplicar): ?>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const formulario = document.getElementById('formAplicarImportacion');
    const botonAplicar = document.getElementById('btnAplicar');
    const botonConfirmar = document.getElementById('btnConfirmarAplicacion');

    const modalElemento = document.getElementById(
        'modalConfirmarImportacion'
    );

    if (
        !formulario ||
        !botonAplicar ||
        !botonConfirmar ||
        !modalElemento
    ) {
        return;
    }

    const modal = new bootstrap.Modal(modalElemento);

    botonAplicar.addEventListener('click', function (event) {

        event.preventDefault();

        modal.show();

    });


    botonConfirmar.addEventListener('click', function () {

        botonConfirmar.disabled = true;

        botonConfirmar.innerHTML =
            '<span class="spinner-border spinner-border-sm me-1" ' +
            'role="status" aria-hidden="true"></span>' +
            'Aplicando...';

        formulario.submit();

    });

});
</script>

<?php endif; ?>


<?php
require_once ROOT_PATH . '/views/layout/footer.php';
?>