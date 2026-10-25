<?php

declare(strict_types=1);

require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';

$importaciones = is_array($importaciones ?? null) ? $importaciones : [];

$mensajeExito = flash('success');
$mensajeError = flash('error');

/*
 * El sistema de permisos de la aplicación se consulta
 * directamente mediante Auth::can().
 *
 * No se debe intentar leer manualmente el arreglo de
 * permisos desde Auth::user(), ya que Auth::user()
 * contiene la información del usuario autenticado y
 * no necesariamente expone todos los permisos efectivos.
 */
$puedeImportar = Auth::can('materiales.catalogo.crear');

$anioActual = (int) date('Y');
?>

<div class="container-fluid py-4">


<!-- ============================================================
     ENCABEZADO
     ============================================================ -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

    <div>
        <h2 class="fw-bold mb-1">
            <i class="bi bi-file-earmark-spreadsheet me-2"></i>
            Importación de materiales
        </h2>

        <p class="text-muted mb-0">
            Cargue y valide el catálogo institucional de materiales desde un archivo Excel.
        </p>
    </div>

    <div>
        <a href="<?= e(url('materiales')) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>
            Volver al catálogo
        </a>
    </div>

</div>


<!-- ============================================================
     MENSAJES
     ============================================================ -->

<?php if (!empty($mensajeExito)): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?= e($mensajeExito) ?>

        <button type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Cerrar"></button>
    </div>
<?php endif; ?>


<?php if (!empty($mensajeError)): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= e($mensajeError) ?>

        <button type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Cerrar"></button>
    </div>
<?php endif; ?>


<!-- ============================================================
     BLOQUE DE IMPORTACIÓN
     ============================================================ -->

<?php if ($puedeImportar): ?>

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-header bg-white border-bottom py-3">

            <div class="d-flex align-items-center">

                <div class="me-3"
                     style="
                        width:44px;
                        height:44px;
                        border-radius:12px;
                        background:#e8f5e9;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                     ">
                    <i class="bi bi-file-earmark-excel text-success fs-4"></i>
                </div>

                <div>
                    <h5 class="fw-bold mb-1">
                        Cargar archivo Excel
                    </h5>

                    <small class="text-muted">
                        El archivo será leído y validado antes de modificar el catálogo.
                    </small>
                </div>

            </div>

        </div>


        <div class="card-body">

            <form method="POST"
                  action="<?= e(url('materiales/importacion')) ?>"
                  enctype="multipart/form-data"
                  id="formImportacion">

                <?= csrfField() ?>


                <div class="row g-4">

                    <!-- ==================================================
                         ARCHIVO
                         ================================================== -->

                    <div class="col-md-8">

                        <label for="archivo" class="form-label fw-semibold">
                            Archivo Excel
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="file"
                            name="archivo_excel"
                            id="archivo"
                            class="form-control"
                            accept=".xlsx,.xls,.csv"
                            required
                        >

                        <div class="form-text">
                            Formatos permitidos:
                            <strong>.xlsx</strong>,
                            <strong>.xls</strong> y
                            <strong>.csv</strong>.
                        </div>

                    </div>


                    <!-- ==================================================
                         AÑO
                         ================================================== -->

                    <div class="col-md-4">

                        <label for="anio_referencia" class="form-label fw-semibold">
                            Año de referencia
                        </label>

                        <select
                            name="anio_referencia"
                            id="anio_referencia"
                            class="form-select"
                        >

                            <option value="">
                                Seleccionar año
                            </option>

                            <?php for ($anio = $anioActual + 1; $anio >= $anioActual - 5; $anio--): ?>

                                <option value="<?= $anio ?>"
                                    <?= $anio === $anioActual ? 'selected' : '' ?>>
                                    <?= $anio ?>
                                </option>

                            <?php endfor; ?>

                        </select>

                        <div class="form-text">
                            Año al que corresponde el archivo institucional.
                        </div>

                    </div>

                </div>


                <!-- ==================================================
                     INFORMACIÓN DE COLUMNAS
                     ================================================== -->

                <div class="mt-4 p-3 rounded-3"
                     style="background:#f8f9fa;">

                    <div class="d-flex align-items-start">

                        <i class="bi bi-table text-primary fs-5 me-2 mt-1"></i>

                        <div>

                            <div class="fw-semibold mb-1">
                                Estructura esperada
                            </div>

                            <div class="small text-muted">

                                <span class="badge bg-light text-dark border me-1">
                                    Código UNSPSC
                                </span>

                                <span class="badge bg-light text-dark border">
                                    Nombre Producto
                                </span>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ==================================================
                     BOTONES
                     ================================================== -->

                <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">

                    <a href="<?= e(url('materiales')) ?>"
                       class="btn btn-outline-secondary">

                        <i class="bi bi-x-lg me-1"></i>
                        Cancelar

                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnLeerExcel"
                    >

                        <i class="bi bi-search me-1"></i>
                        Leer Excel

                    </button>

                </div>

            </form>

        </div>

    </div>

<?php else: ?>

    <div class="alert alert-warning shadow-sm">

        <i class="bi bi-lock-fill me-2"></i>

        No tiene permisos para realizar importaciones del catálogo.
        Puede consultar los materiales desde el catálogo institucional.

    </div>

<?php endif; ?>


<!-- ============================================================
     HISTORIAL DE IMPORTACIONES
     ============================================================ -->

<div class="card border-0 shadow-sm">

    <div class="card-header bg-white border-bottom py-3">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

            <div>

                <h5 class="fw-bold mb-1">
                    <i class="bi bi-clock-history me-2"></i>
                    Historial de importaciones
                </h5>

                <small class="text-muted">
                    Registro de los archivos procesados en el catálogo.
                </small>

            </div>

            <span class="badge bg-light text-dark border">
                <?= count($importaciones) ?> registro<?= count($importaciones) === 1 ? '' : 's' ?>
            </span>

        </div>

    </div>


    <div class="card-body p-0">

        <?php if (empty($importaciones)): ?>

            <div class="text-center py-5 px-3">

                <div class="mb-3">
                    <i class="bi bi-file-earmark-x text-muted"
                       style="font-size:3rem;"></i>
                </div>

                <h6 class="fw-bold">
                    No hay importaciones registradas
                </h6>

                <p class="text-muted mb-0">
                    Cuando se procese un archivo Excel aparecerá aquí su historial.
                </p>

            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                        <tr>

                            <th class="ps-4">
                                Archivo
                            </th>

                            <th>
                                Año
                            </th>

                            <th class="text-center">
                                Filas
                            </th>

                            <th class="text-center">
                                Nuevos
                            </th>

                            <th class="text-center">
                                Modificados
                            </th>

                            <th class="text-center">
                                Sin cambios
                            </th>

                            <th class="text-center">
                                Errores
                            </th>

                            <th>
                                Estado
                            </th>

                            <th>
                                Fecha
                            </th>

                            <th class="text-center pe-4">
                                Acción
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($importaciones as $importacion): ?>

                            <?php

                            $estado = strtolower(
                                trim((string) ($importacion['estado'] ?? ''))
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

                            $idImportacion = (int) (
                                $importacion['id']
                                ?? 0
                            );

                            ?>

                            <tr>

                                <td class="ps-4">

                                    <div class="d-flex align-items-center">

                                        <div class="me-2"
                                             style="
                                                width:36px;
                                                height:36px;
                                                border-radius:9px;
                                                background:#f1f8e9;
                                                display:flex;
                                                align-items:center;
                                                justify-content:center;
                                             ">

                                            <i class="bi bi-file-earmark-excel text-success"></i>

                                        </div>

                                        <div>

                                            <div class="fw-semibold">
                                                <?= e(
                                                    (string) (
                                                        $importacion['nombre_archivo']
                                                        ?? 'Sin nombre'
                                                    )
                                                ) ?>
                                            </div>

                                            <?php if (!empty($importacion['usuario_nombre'])): ?>

                                                <small class="text-muted">
                                                    Por:
                                                    <?= e(
                                                        (string) $importacion['usuario_nombre']
                                                    ) ?>
                                                </small>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                </td>


                                <td>

                                    <?php if (!empty($importacion['anio_referencia'])): ?>

                                        <span class="badge bg-light text-dark border">
                                            <?= e(
                                                (string) $importacion['anio_referencia']
                                            ) ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="text-muted">
                                            —
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td class="text-center">
                                    <?= entero(
                                        $importacion['total_filas'] ?? 0
                                    ) ?>
                                </td>


                                <td class="text-center">

                                    <span class="badge bg-success-subtle text-success">
                                        <?= entero(
                                            $importacion['registros_nuevos'] ?? 0
                                        ) ?>
                                    </span>

                                </td>


                                <td class="text-center">

                                    <span class="badge bg-primary-subtle text-primary">
                                        <?= entero(
                                            $importacion['registros_modificados'] ?? 0
                                        ) ?>
                                    </span>

                                </td>


                                <td class="text-center">

                                    <span class="badge bg-secondary-subtle text-secondary">
                                        <?= entero(
                                            $importacion['registros_sin_cambios'] ?? 0
                                        ) ?>
                                    </span>

                                </td>


                                <td class="text-center">

                                    <?php
                                    $errores = (int) (
                                        $importacion['registros_con_error']
                                        ?? 0
                                    );
                                    ?>

                                    <?php if ($errores > 0): ?>

                                        <span class="badge bg-danger">
                                            <?= entero($errores) ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="text-muted">
                                            0
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <span class="badge text-bg-<?= e($claseEstado) ?>">
                                        <?= e($textoEstado) ?>
                                    </span>

                                </td>


                                <td>

                                    <small class="text-muted">
                                        <?= !empty($importacion['created_at'])
                                            ? e(
                                                fechaHora(
                                                    (string) $importacion['created_at']
                                                )
                                            )
                                            : '—'
                                        ?>
                                    </small>

                                </td>


                                <td class="text-center pe-4">

                                    <?php if ($idImportacion > 0): ?>

                                        <a
                                            href="<?= e(
                                                url(
                                                    'materiales/importacion/preview?id='
                                                    . $idImportacion
                                                )
                                            ) ?>"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Ver detalle"
                                        >

                                            <i class="bi bi-eye"></i>

                                        </a>

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


</div>

<!-- ================================================================
     JAVASCRIPT
     ================================================================ -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    const formulario = document.getElementById('formImportacion');
    const boton = document.getElementById('btnLeerExcel');
    const archivo = document.getElementById('archivo');

    if (!formulario || !boton) {
        return;
    }

    formulario.addEventListener('submit', function () {

        if (archivo && archivo.files.length > 0) {

            boton.disabled = true;

            boton.innerHTML =
                '<span class="spinner-border spinner-border-sm me-1" ' +
                'role="status" aria-hidden="true"></span>' +
                'Leyendo Excel...';

        }

    });

});
</script>

<?php
require_once ROOT_PATH . '/views/layout/footer.php';
?>
