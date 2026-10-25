<?php

declare(strict_types=1);

require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';

$proceso = is_array($proceso ?? null)
    ? $proceso
    : [];

$coordinadores = is_array($coordinadores ?? null)
    ? $coordinadores
    : [];

$resumen = is_array($resumen ?? null)
    ? $resumen
    : [];

$id = (int) ($proceso['id'] ?? 0);

$anio = (int) (
    $proceso['anio']
    ?? date('Y')
);

$nombre = (string) (
    $proceso['nombre']
    ?? ''
);

$descripcion = (string) (
    $proceso['descripcion']
    ?? ''
);

$fechaInicio = (string) (
    $proceso['fecha_inicio']
    ?? ''
);

$fechaCierre = (string) (
    $proceso['fecha_cierre']
    ?? ''
);

$estado = (string) (
    $proceso['estado']
    ?? 'borrador'
);

$idCoordinador = (string) (
    $proceso['id_coordinador']
    ?? ''
);

$cantidadAreas = (int) (
    $resumen['areas']
    ?? 0
);

$cantidadSolicitudes = (int) (
    $resumen['solicitudes']
    ?? 0
);

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
?>

<div class="container-fluid py-4">

    <!-- =========================================================
         ENCABEZADO
         ========================================================= -->

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">

        <div>

            <h2 class="mb-1">
                <i class="bi bi-pencil-square me-2"></i>
                Editar proceso de materiales
            </h2>

            <p class="text-muted mb-0">
                Actualice la información y el estado del proceso.
            </p>

        </div>

        <a
            href="<?= e(url('/materiales/procesos')) ?>"
            class="btn btn-outline-secondary"
        >
            <i class="bi bi-arrow-left me-1"></i>
            Volver a procesos
        </a>

    </div>


    <!-- =========================================================
         RESUMEN
         ========================================================= -->

    <div class="row g-3 mb-4">

        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="text-muted small">
                        Proceso
                    </div>

                    <div class="fs-5 fw-semibold mt-1">
                        <?= e($nombre) ?>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="text-muted small">
                        Áreas configuradas
                    </div>

                    <div class="fs-4 fw-semibold mt-1">
                        <?= e((string) $cantidadAreas) ?>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="text-muted small">
                        Solicitudes asociadas
                    </div>

                    <div class="fs-4 fw-semibold mt-1">
                        <?= e((string) $cantidadSolicitudes) ?>
                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- =========================================================
         FORMULARIO
         ========================================================= -->

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white py-3">

            <h5 class="mb-1">
                Información del proceso
            </h5>

            <small class="text-muted">
                Modifique los datos necesarios y guarde los cambios.
            </small>

        </div>

        <div class="card-body">

            <form
                method="post"
                action="<?= e(
                    url('/materiales/procesos/actualizar')
                ) ?>"
                autocomplete="off"
            >

                <?= csrfField() ?>

                <input
                    type="hidden"
                    name="id"
                    value="<?= e((string) $id) ?>"
                >


                <div class="row g-3">

                    <!-- AÑO -->

                    <div class="col-md-3">

                        <label
                            for="anio"
                            class="form-label fw-semibold"
                        >
                            Año
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="anio"
                            name="anio"
                            min="2020"
                            max="2100"
                            value="<?= e((string) $anio) ?>"
                            required
                        >

                    </div>


                    <!-- NOMBRE -->

                    <div class="col-md-9">

                        <label
                            for="nombre"
                            class="form-label fw-semibold"
                        >
                            Nombre del proceso
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="nombre"
                            name="nombre"
                            maxlength="200"
                            value="<?= e($nombre) ?>"
                            required
                        >

                    </div>


                    <!-- DESCRIPCIÓN -->

                    <div class="col-12">

                        <label
                            for="descripcion"
                            class="form-label fw-semibold"
                        >
                            Descripción
                        </label>

                        <textarea
                            class="form-control"
                            id="descripcion"
                            name="descripcion"
                            rows="4"
                        ><?= e($descripcion) ?></textarea>

                    </div>


                    <!-- FECHA INICIO -->

                    <div class="col-md-4">

                        <label
                            for="fecha_inicio"
                            class="form-label fw-semibold"
                        >
                            Fecha de inicio
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="fecha_inicio"
                            name="fecha_inicio"
                            value="<?= e($fechaInicio) ?>"
                        >

                    </div>


                    <!-- FECHA CIERRE -->

                    <div class="col-md-4">

                        <label
                            for="fecha_cierre"
                            class="form-label fw-semibold"
                        >
                            Fecha de cierre
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="fecha_cierre"
                            name="fecha_cierre"
                            value="<?= e($fechaCierre) ?>"
                        >

                    </div>


                    <!-- ESTADO -->

                    <div class="col-md-4">

                        <label
                            for="estado"
                            class="form-label fw-semibold"
                        >
                            Estado
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-select"
                            id="estado"
                            name="estado"
                            required
                        >

                            <?php foreach (
                                $estados as $valor => $texto
                            ): ?>

                                <option
                                    value="<?= e($valor) ?>"
                                    <?= $estado === $valor
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= e($texto) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- COORDINADOR -->

                    <div class="col-md-8">

                        <label
                            for="id_coordinador"
                            class="form-label fw-semibold"
                        >
                            Coordinador del proceso
                        </label>

                        <select
                            class="form-select"
                            id="id_coordinador"
                            name="id_coordinador"
                        >

                            <option value="">
                                -- Sin coordinador asignado --
                            </option>

                            <?php foreach ($coordinadores as $coordinador): ?>

                                <?php
                                $idProfesional = (int) (
                                    $coordinador['id'] ?? 0
                                );

                                $nombreCompleto = trim(
                                    (string) (
                                        $coordinador['nombre_completo']
                                        ?? ''
                                    )
                                );

                                $documento = trim(
                                    (string) (
                                        $coordinador['documento']
                                        ?? ''
                                    )
                                );

                                $tipo = trim(
                                    (string) (
                                        $coordinador['tipo_profesional']
                                        ?? ''
                                    )
                                );
                                ?>

                                <?php if ($idProfesional > 0): ?>

                                    <option
                                        value="<?= e(
                                            (string) $idProfesional
                                        ) ?>"
                                        <?= $idCoordinador ===
                                            (string) $idProfesional
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= e($nombreCompleto) ?>

                                        <?php if ($documento !== ''): ?>
                                            —
                                            <?= e($documento) ?>
                                        <?php endif; ?>

                                        <?php if ($tipo !== ''): ?>
                                            —
                                            <?= e($tipo) ?>
                                        <?php endif; ?>

                                    </option>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>


                <!-- =================================================
                     INFORMACIÓN DEL ESTADO
                     ================================================= -->

                <?php if ($estado === 'abierto'): ?>

                    <div class="alert alert-success mt-4 mb-0">

                        <i class="bi bi-check-circle me-2"></i>

                        <strong>Proceso abierto.</strong>

                        Los procesos en estado
                        <strong>Abierto</strong> pueden quedar
                        disponibles para la gestión de solicitudes
                        cuando tengan sus áreas configuradas.

                    </div>

                <?php elseif ($estado === 'en_solicitud'): ?>

                    <div class="alert alert-primary mt-4 mb-0">

                        <i class="bi bi-file-earmark-text me-2"></i>

                        <strong>Proceso en solicitud.</strong>

                        Este proceso se encuentra habilitado para
                        recibir solicitudes de materiales.

                    </div>

                <?php elseif ($estado === 'cerrado'): ?>

                    <div class="alert alert-dark mt-4 mb-0">

                        <i class="bi bi-lock me-2"></i>

                        <strong>Proceso cerrado.</strong>

                        No debería utilizarse para nuevas solicitudes.

                    </div>

                <?php elseif ($estado === 'cancelado'): ?>

                    <div class="alert alert-danger mt-4 mb-0">

                        <i class="bi bi-x-circle me-2"></i>

                        <strong>Proceso cancelado.</strong>

                        Este proceso no debe utilizarse para nuevas
                        solicitudes.

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     BOTONES
                     ================================================= -->

                <div class="d-flex justify-content-between align-items-center mt-4">

                    <a
                        href="<?= e(url('/materiales/procesos')) ?>"
                        class="btn btn-outline-secondary"
                    >
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-save me-1"></i>
                        Guardar cambios
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaCierre = document.getElementById('fecha_cierre');

    if (!fechaInicio || !fechaCierre) {
        return;
    }

    function validarFechas() {

        if (fechaInicio.value !== '') {

            fechaCierre.min = fechaInicio.value;

            if (
                fechaCierre.value !== ''
                && fechaCierre.value < fechaInicio.value
            ) {
                fechaCierre.value = '';
            }

        } else {

            fechaCierre.removeAttribute('min');

        }
    }

    fechaInicio.addEventListener(
        'change',
        validarFechas
    );

    fechaCierre.addEventListener(
        'change',
        validarFechas
    );

    validarFechas();

});
</script>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>