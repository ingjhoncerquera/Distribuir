<?php

declare(strict_types=1);

require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';

$procesos = $procesos ?? [];
$proceso = $proceso ?? null;
$areas = $areas ?? [];

$idProcesoSeleccionado = $proceso
    ? (int) $proceso['id']
    : 0;

function estadoAreaTexto(string $estado): string
{
    return match ($estado) {
        'activa' => 'Activa',
        'inactiva' => 'Inactiva',
        'cerrada' => 'Cerrada',
        default => $estado !== ''
            ? ucfirst($estado)
            : 'Activa'
    };
}

function estadoAreaClase(string $estado): string
{
    return match ($estado) {
        'activa' => 'bg-success',
        'inactiva' => 'bg-secondary',
        'cerrada' => 'bg-dark',
        default => 'bg-success'
    };
}
?>

<div class="container-fluid py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold mb-1">
                Áreas y responsables
            </h2>

            <p class="text-muted mb-0">
                Configure las áreas que participan en cada proceso
                y asigne su responsable.
            </p>
        </div>

        <?php if ($proceso !== null): ?>

            <a
                href="<?= e(url('/materiales/areas/crear?id_proceso=' . $idProcesoSeleccionado)) ?>"
                class="btn btn-primary"
            >
                <span class="me-1">+</span>
                Nueva área
            </a>

        <?php endif; ?>

    </div>


    <!-- ============================================================
         SELECCIÓN DE PROCESO
         ============================================================ -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body">

            <div class="row align-items-end">

                <div class="col-md-9">

                    <label
                        for="id_proceso"
                        class="form-label fw-semibold"
                    >
                        Proceso de materiales
                    </label>

                    <select
                        id="id_proceso"
                        class="form-select"
                        onchange="cambiarProceso()"
                    >

                        <option value="">
                            Seleccione un proceso...
                        </option>

                        <?php foreach ($procesos as $item): ?>

                            <?php
                            $id = (int) $item['id'];

                            $texto =
                                (int) $item['anio']
                                . ' - '
                                . (string) $item['nombre']
                                . ' - '
                                . ucfirst(
                                    str_replace(
                                        '_',
                                        ' ',
                                        (string) $item['estado']
                                    )
                                );
                            ?>

                            <option
                                value="<?= $id ?>"
                                <?= $id === $idProcesoSeleccionado ? 'selected' : '' ?>
                            >
                                <?= e($texto) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <?php if ($proceso !== null): ?>

                    <div class="col-md-3 mt-3 mt-md-0">

                        <div class="border rounded p-3 bg-light">

                            <small class="text-muted d-block">
                                Estado del proceso
                            </small>

                            <strong>
                                <?= e(
                                    ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            (string) $proceso['estado']
                                        )
                                    )
                                ) ?>
                            </strong>

                        </div>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>


    <?php if ($proceso === null): ?>

        <div class="card shadow-sm border-0">

            <div class="card-body text-center py-5">

                <div
                    class="mb-3"
                    style="font-size: 42px;"
                >
                    🗂️
                </div>

                <h5 class="fw-bold">
                    Seleccione un proceso
                </h5>

                <p class="text-muted mb-0">
                    Seleccione un proceso para consultar,
                    crear y administrar sus áreas y responsables.
                </p>

            </div>

        </div>

    <?php else: ?>


        <!-- ========================================================
             INFORMACIÓN DEL PROCESO
             ======================================================== -->

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-body">

                <div class="row">

                    <div class="col-md-8">

                        <small class="text-muted d-block">
                            Proceso
                        </small>

                        <h4 class="fw-bold mb-1">
                            <?= e(
                                (string) $proceso['nombre']
                            ) ?>
                        </h4>

                        <?php if (!empty($proceso['descripcion'])): ?>

                            <p class="text-muted mb-0">
                                <?= nl2br(
                                    e(
                                        (string) $proceso['descripcion']
                                    )
                                ) ?>
                            </p>

                        <?php endif; ?>

                    </div>

                    <div class="col-md-4 mt-3 mt-md-0">

                        <div class="row g-2">

                            <div class="col-6">

                                <div class="border rounded p-2">

                                    <small class="text-muted d-block">
                                        Año
                                    </small>

                                    <strong>
                                        <?= e(
                                            (string) $proceso['anio']
                                        ) ?>
                                    </strong>

                                </div>

                            </div>

                            <div class="col-6">

                                <div class="border rounded p-2">

                                    <small class="text-muted d-block">
                                        Áreas
                                    </small>

                                    <strong>
                                        <?= count($areas) ?>
                                    </strong>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ========================================================
             LISTADO DE ÁREAS
             ======================================================== -->

        <div class="card shadow-sm border-0">

            <div class="card-header bg-white border-0 py-3">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <h5 class="fw-bold mb-1">
                            Áreas configuradas
                        </h5>

                        <small class="text-muted">
                            Cada área puede tener un responsable activo.
                        </small>

                    </div>

                    <span class="badge bg-primary">
                        <?= count($areas) ?>
                    </span>

                </div>

            </div>

            <div class="card-body">

                <?php if (empty($areas)): ?>

                    <div class="text-center py-5">

                        <div
                            class="mb-3"
                            style="font-size: 40px;"
                        >
                            📋
                        </div>

                        <h5 class="fw-bold">
                            No hay áreas configuradas
                        </h5>

                        <p class="text-muted">
                            Este proceso todavía no tiene áreas.
                        </p>

                        <a
                            href="<?= e(url('/materiales/areas/crear?id_proceso=' . $idProcesoSeleccionado)) ?>"
                            class="btn btn-primary"
                        >
                            Crear primera área
                        </a>

                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table table-hover align-middle">

                            <thead>

                                <tr>

                                    <th>
                                        Área
                                    </th>

                                    <th>
                                        Responsable
                                    </th>

                                    <th>
                                        Tipo
                                    </th>

                                    <th>
                                        Estado
                                    </th>

                                    <th class="text-end">
                                        Acciones
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ($areas as $area): ?>

                                    <?php
                                    $idArea = (int) ($area['id'] ?? 0);

                                    /*
                                     * Estado del área.
                                     *
                                     * La tabla materiales_proceso_areas
                                     * utiliza:
                                     *
                                     * activa
                                     * inactiva
                                     * cerrada
                                     *
                                     * Se normaliza aquí para evitar que
                                     * un valor NULL o inexistente deje
                                     * la columna visualmente vacía.
                                     */
                                    $estadoArea = strtolower(
                                        trim(
                                            (string) (
                                                $area['estado']
                                                ?? $area['estado_area']
                                                ?? ''
                                            )
                                        )
                                    );

                                    if (
                                        $estadoArea !== 'activa'
                                        && $estadoArea !== 'inactiva'
                                        && $estadoArea !== 'cerrada'
                                    ) {
                                        $estadoArea = 'activa';
                                    }

                                    $nombreResponsable =
                                        trim(
                                            (string) (
                                                $area['nombre_responsable']
                                                ?? ''
                                            )
                                        );

                                    $tieneResponsable =
                                        $nombreResponsable !== '';
                                    ?>

                                    <tr>

                                        <td>

                                            <div class="fw-semibold">
                                                <?= e(
                                                    (string) (
                                                        $area['nombre_area']
                                                        ?? ''
                                                    )
                                                ) ?>
                                            </div>

                                            <?php if (!empty($area['observaciones'])): ?>

                                                <small class="text-muted">
                                                    <?= e(
                                                        (string) $area['observaciones']
                                                    ) ?>
                                                </small>

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <?php if ($tieneResponsable): ?>

                                                <div class="fw-semibold">
                                                    <?= e(
                                                        $nombreResponsable
                                                    ) ?>
                                                </div>

                                            <?php else: ?>

                                                <span class="text-muted">
                                                    Sin responsable asignado
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <?php if ($tieneResponsable): ?>

                                                <small>
                                                    <?= e(
                                                        (string) (
                                                            $area['tipo_profesional']
                                                            ?? 'Profesional'
                                                        )
                                                    ) ?>
                                                </small>

                                            <?php else: ?>

                                                <span class="text-muted">
                                                    —
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <span
                                                class="badge <?= e(
                                                    estadoAreaClase(
                                                        $estadoArea
                                                    )
                                                ) ?>"
                                            >
                                                <?= e(
                                                    estadoAreaTexto(
                                                        $estadoArea
                                                    )
                                                ) ?>
                                            </span>

                                        </td>


                                        <td class="text-end">

                                            <div class="d-flex justify-content-end gap-1 flex-wrap">

                                                <a
                                                    href="<?= e(
                                                        url(
                                                            '/materiales/areas/editar?id='
                                                            . $idArea
                                                        )
                                                    ) ?>"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="Editar área y responsable"
                                                >
                                                    ✏️
                                                </a>


                                                <?php if ($estadoArea === 'activa'): ?>

                                                    <form
                                                        method="post"
                                                        action="<?= e(
                                                            url(
                                                                '/materiales/areas/estado'
                                                            )
                                                        ) ?>"
                                                        class="d-inline"
                                                        onsubmit="return confirm('¿Desea inactivar esta área?');"
                                                    >

                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= $idArea ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="estado"
                                                            value="inactiva"
                                                        >

                                                        <button
                                                            type="submit"
                                                            class="btn btn-sm btn-outline-warning"
                                                            title="Inactivar"
                                                        >
                                                            ⏸️
                                                        </button>

                                                    </form>

                                                <?php elseif ($estadoArea === 'inactiva'): ?>

                                                    <form
                                                        method="post"
                                                        action="<?= e(
                                                            url(
                                                                '/materiales/areas/estado'
                                                            )
                                                        ) ?>"
                                                        class="d-inline"
                                                        onsubmit="return confirm('¿Desea activar esta área?');"
                                                    >

                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= $idArea ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="estado"
                                                            value="activa"
                                                        >

                                                        <button
                                                            type="submit"
                                                            class="btn btn-sm btn-outline-success"
                                                            title="Activar"
                                                        >
                                                            ▶️
                                                        </button>

                                                    </form>

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

    <?php endif; ?>

</div>


<script>

function cambiarProceso() {

    const select = document.getElementById('id_proceso');

    const idProceso = select.value;

    if (!idProceso) {

        window.location.href =
            "<?= e(url('/materiales/areas')) ?>";

        return;
    }

    window.location.href =
        "<?= e(url('/materiales/areas')) ?>"
        + "?id_proceso="
        + encodeURIComponent(idProceso);
}

</script>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>