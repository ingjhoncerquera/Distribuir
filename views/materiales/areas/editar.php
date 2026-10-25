<?php

declare(strict_types=1);

require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';

$area = $area ?? [];
$proceso = $proceso ?? [];
$profesionales = $profesionales ?? [];
$responsable = $responsable ?? null;

$idArea = (int) ($area['id'] ?? 0);
$idProceso = (int) ($area['id_proceso'] ?? 0);
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">
                Editar área
            </h2>

            <p class="text-muted mb-0">
                Actualice el área y su responsable.
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
                    url('/materiales/areas/actualizar')
                ) ?>"
            >

                <input
                    type="hidden"
                    name="id"
                    value="<?= $idArea ?>"
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
                            value="<?= e(
                                (string) $area['nombre_area']
                            ) ?>"
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
                                Sin responsable
                            </option>

                            <?php foreach ($profesionales as $profesional): ?>

                                <?php
                                $idProfesional =
                                    (int) $profesional['id'];

                                $idResponsable =
                                    $responsable
                                    ? (int) $responsable['id_profesional']
                                    : 0;
                                ?>

                                <option
                                    value="<?= $idProfesional ?>"
                                    <?= $idProfesional === $idResponsable
                                        ? 'selected'
                                        : '' ?>
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
                        ><?= e(
                            (string) (
                                $area['observaciones']
                                ?? ''
                            )
                        ) ?></textarea>

                    </div>

                </div>


                <div class="d-flex justify-content-between align-items-center mt-4">

                    <div>

                        <span class="text-muted">
                            Estado actual:
                        </span>

                        <strong>
                            <?= e(
                                ucfirst(
                                    (string) $area['estado']
                                )
                            ) ?>
                        </strong>

                    </div>


                    <div class="d-flex gap-2">

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
                            Guardar cambios
                        </button>

                    </div>

                </div>

            </form>


            <?php if ($responsable !== null): ?>

                <hr class="my-4">

                <div>

                    <h5 class="fw-bold mb-3">
                        Responsable actual
                    </h5>

                    <div class="border rounded p-3">

                        <div class="fw-semibold">
                            <?= e(
                                (string) $responsable['nombre_completo']
                            ) ?>
                        </div>

                        <div class="text-muted">
                            <?= e(
                                (string) $responsable['tipo_profesional']
                            ) ?>
                        </div>

                        <?php if (!empty($responsable['especialidad'])): ?>

                            <small class="text-muted">
                                <?= e(
                                    (string) $responsable['especialidad']
                                ) ?>
                            </small>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>