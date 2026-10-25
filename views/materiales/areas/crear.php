<?php

declare(strict_types=1);

require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';

$proceso = $proceso ?? [];
$profesionales = $profesionales ?? [];

$idProceso = (int) ($proceso['id'] ?? 0);
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">
                Nueva área
            </h2>

            <p class="text-muted mb-0">
                Configure un área para el proceso seleccionado.
            </p>

        </div>

        <a
            href="<?= e(
                url(
                    '/materiales/areas?id_proceso=' .
                    $idProceso
                )
            ) ?>"
            class="btn btn-outline-secondary"
        >
            ← Volver
        </a>

    </div>


    <div class="card shadow-sm border-0">

        <div class="card-body p-4">

            <div class="alert alert-info">

                <strong>
                    Proceso:
                </strong>

                <?= e(
                    (string) $proceso['nombre']
                ) ?>

                — Año
                <?= e(
                    (string) $proceso['anio']
                ) ?>

            </div>


            <form
                method="post"
                action="<?= e(
                    url('/materiales/areas/guardar')
                ) ?>"
            >

                <input
                    type="hidden"
                    name="id_proceso"
                    value="<?= $idProceso ?>"
                >


                <div class="row g-4">

                    <div class="col-md-8">

                        <label
                            for="nombre_area"
                            class="form-label fw-semibold"
                        >
                            Nombre del área
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            id="nombre_area"
                            name="nombre_area"
                            class="form-control"
                            maxlength="150"
                            required
                            placeholder="Ejemplo: ADSO"
                        >

                    </div>


                    <div class="col-md-4">

                        <label
                            for="id_profesional"
                            class="form-label fw-semibold"
                        >
                            Responsable
                        </label>

                        <select
                            id="id_profesional"
                            name="id_profesional"
                            class="form-select"
                        >

                            <option value="">
                                Sin responsable por ahora
                            </option>

                            <?php foreach ($profesionales as $profesional): ?>

                                <option
                                    value="<?= (int) $profesional['id'] ?>"
                                >
                                    <?= e(
                                        (string) $profesional['nombre_completo']
                                    ) ?>

                                    —
                                    <?= e(
                                        (string) $profesional['tipo_profesional']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <small class="text-muted">
                            Puede asignarlo ahora o posteriormente.
                        </small>

                    </div>


                    <div class="col-12">

                        <label
                            for="observaciones"
                            class="form-label fw-semibold"
                        >
                            Observaciones
                        </label>

                        <textarea
                            id="observaciones"
                            name="observaciones"
                            class="form-control"
                            rows="4"
                            placeholder="Observaciones sobre el área..."
                        ></textarea>

                    </div>

                </div>


                <div class="d-flex justify-content-end gap-2 mt-4">

                    <a
                        href="<?= e(
                            url(
                                '/materiales/areas?id_proceso=' .
                                $idProceso
                            )
                        ) ?>"
                        class="btn btn-outline-secondary"
                    >
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Guardar área
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>