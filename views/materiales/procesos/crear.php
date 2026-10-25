<?php

declare(strict_types=1);

require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';

$coordinadores = is_array($coordinadores ?? null)
    ? $coordinadores
    : [];

$form = $_SESSION['materiales_proceso_form'] ?? [];

unset($_SESSION['materiales_proceso_form']);

$anio = (int) (
    $form['anio']
    ?? date('Y')
);

$nombre = (string) (
    $form['nombre']
    ?? ''
);

$descripcion = (string) (
    $form['descripcion']
    ?? ''
);

$fechaInicio = (string) (
    $form['fecha_inicio']
    ?? ''
);

$fechaCierre = (string) (
    $form['fecha_cierre']
    ?? ''
);

$estado = (string) (
    $form['estado']
    ?? 'borrador'
);

$idCoordinador = (string) (
    $form['id_coordinador']
    ?? ''
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
                <i class="bi bi-calendar-plus me-2"></i>
                Crear proceso de materiales
            </h2>

            <p class="text-muted mb-0">
                Configure la información básica del proceso para
                habilitar posteriormente las solicitudes de materiales.
            </p>

        </div>

        <div>

            <a
                href="<?= e(url('/materiales/procesos')) ?>"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-1"></i>
                Volver a procesos
            </a>

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
                Complete los datos requeridos para registrar el proceso.
            </small>

        </div>

        <div class="card-body">

            <form
                method="post"
                action="<?= e(
                    url('/materiales/procesos/guardar')
                ) ?>"
                autocomplete="off"
            >

                <?= csrfField() ?>


                <!-- =================================================
                     DATOS GENERALES
                     ================================================= -->

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

                        <div class="form-text">
                            Año al que corresponde el proceso.
                        </div>

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
                            placeholder="Ejemplo: Proceso de adquisición de materiales de formación 2026"
                            required
                        >

                        <div class="form-text">
                            Utilice un nombre claro que permita identificar
                            el proceso.
                        </div>

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
                            placeholder="Describa el objetivo, alcance o características principales del proceso."
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

                        <div class="form-text">
                            Para permitir solicitudes, el proceso debe
                            estar en estado <strong>Abierto</strong> o
                            <strong>En solicitud</strong>.
                        </div>

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
                                $id = (int) (
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

                                <?php if ($id > 0): ?>

                                    <option
                                        value="<?= e((string) $id) ?>"
                                        <?= $idCoordinador === (string) $id
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

                        <div class="form-text">
                            Puede asignar posteriormente el responsable
                            correspondiente al proceso.
                        </div>

                    </div>

                </div>


                <!-- =================================================
                     INFORMACIÓN IMPORTANTE
                     ================================================= -->

                <div class="alert alert-info mt-4 mb-0">

                    <div class="d-flex">

                        <div class="me-3 fs-4">
                            <i class="bi bi-info-circle"></i>
                        </div>

                        <div>

                            <strong>
                                Importante
                            </strong>

                            <p class="mb-0 mt-1">
                                Una vez creado el proceso, deberá
                                configurarse posteriormente sus áreas y
                                responsables. Los profesionales podrán
                                seleccionar el proceso para elaborar sus
                                solicitudes cuando se encuentre habilitado
                                para solicitud.
                            </p>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     BOTONES
                     ================================================= -->

                <div class="d-flex justify-content-end gap-2 mt-4">

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
                        Crear proceso
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =============================================================
     VALIDACIÓN VISUAL DE FECHAS
     ============================================================= -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaCierre = document.getElementById('fecha_cierre');

    if (!fechaInicio || !fechaCierre) {
        return;
    }

    function validarFechas() {

        if (
            fechaInicio.value !== ''
            && fechaCierre.value !== ''
        ) {

            fechaCierre.min = fechaInicio.value;

            if (fechaCierre.value < fechaInicio.value) {
                fechaCierre.value = '';
            }

        } else if (fechaInicio.value !== '') {

            fechaCierre.min = fechaInicio.value;

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