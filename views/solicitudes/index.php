<?php

declare(strict_types=1);

require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';

$solicitudes = is_array($solicitudes ?? null)
    ? $solicitudes
    : [];

$profesional = is_array($profesional ?? null)
    ? $profesional
    : [];

function claseEstadoSolicitud(string $estado): string
{
    return match ($estado) {
        'borrador' => 'secondary',
        'pendiente_aval' => 'warning',
        'devuelta' => 'info',
        'avalada' => 'success',
        'rechazada' => 'danger',
        'cancelada' => 'dark',
        default => 'secondary'
    };
}

function nombreEstadoSolicitud(string $estado): string
{
    return match ($estado) {
        'borrador' => 'Borrador',
        'pendiente_aval' => 'Pendiente de aval',
        'devuelta' => 'Devuelta',
        'avalada' => 'Avalada',
        'rechazada' => 'Rechazada',
        'cancelada' => 'Cancelada',
        default => ucfirst(str_replace('_', ' ', $estado))
    };
}
?>

<div class="container-fluid py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>
            <h2 class="fw-bold mb-1">
                <i class="bi bi-clipboard-check me-2"></i>
                Solicitudes de materiales
            </h2>

            <p class="text-muted mb-0">
                Gestión de solicitudes de materiales de formación.
            </p>
        </div>

        <?php if (Auth::can('materiales.solicitudes.crear')): ?>
            <a
                href="<?= e(url('/materiales/solicitudes/crear')) ?>"
                class="btn btn-primary"
            >
                <i class="bi bi-plus-circle me-1"></i>
                Nueva solicitud
            </a>
        <?php endif; ?>

    </div>

    <?php if (function_exists('flash')): ?>

        <?php
        $mensajeSuccess = $_SESSION['flash']['success'] ?? null;
        $mensajeError = $_SESSION['flash']['error'] ?? null;
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

    <?php endif; ?>


    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-4">

                    <div class="border rounded-3 p-3 h-100">

                        <div class="text-muted small mb-1">
                            Profesional solicitante
                        </div>

                        <div class="fw-semibold">
                            <?= e(
                                trim(
                                    (string) ($profesional['nombre_completo'] ?? '')
                                )
                            ) ?>
                        </div>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="border rounded-3 p-3 h-100">

                        <div class="text-muted small mb-1">
                            Tipo de profesional
                        </div>

                        <div class="fw-semibold">
                            <?= e(
                                (string) ($profesional['tipo_profesional'] ?? '')
                            ) ?>
                        </div>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="border rounded-3 p-3 h-100">

                        <div class="text-muted small mb-1">
                            Total de solicitudes
                        </div>

                        <div class="fw-semibold fs-5">
                            <?= count($solicitudes) ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white border-0 py-3">

            <h5 class="mb-0 fw-semibold">
                <i class="bi bi-list-check me-2"></i>
                Mis solicitudes
            </h5>

        </div>

        <div class="card-body">

            <?php if (empty($solicitudes)): ?>

                <div class="text-center py-5">

                    <div class="mb-3">
                        <i
                            class="bi bi-inbox"
                            style="font-size: 3rem;"
                        ></i>
                    </div>

                    <h5 class="fw-semibold">
                        No hay solicitudes registradas
                    </h5>

                    <p class="text-muted mb-4">
                        Puede crear una nueva solicitud seleccionando
                        los materiales que necesita.
                    </p>

                    <?php if (Auth::can('materiales.solicitudes.crear')): ?>

                        <a
                            href="<?= e(url('/materiales/solicitudes/crear')) ?>"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-plus-circle me-1"></i>
                            Crear primera solicitud
                        </a>

                    <?php endif; ?>

                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead>

                            <tr>

                                <th>
                                    Número
                                </th>

                                <th>
                                    Título
                                </th>

                                <th>
                                    Área
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th>
                                    Fecha
                                </th>

                                <th class="text-end">
                                    Acción
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($solicitudes as $solicitud): ?>

                            <?php
                            $estado = (string) (
                                $solicitud['estado'] ?? ''
                            );

                            $idSolicitud = (int) (
                                $solicitud['id'] ?? 0
                            );

                            $nombreEstado =
                                nombreEstadoSolicitud($estado);

                            $claseEstado =
                                claseEstadoSolicitud($estado);
                            ?>

                            <tr>

                                <td class="fw-semibold">
                                    <?= e(
                                        (string) (
                                            $solicitud['numero_solicitud']
                                            ?? ''
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <?= e(
                                        (string) (
                                            $solicitud['titulo']
                                            ?? ''
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <?= e(
                                        (string) (
                                            $solicitud['nombre_area']
                                            ?? ''
                                        )
                                    ) ?>
                                </td>

                                <td>

                                    <span
                                        class="badge text-bg-<?= e($claseEstado) ?>"
                                    >
                                        <?= e($nombreEstado) ?>
                                    </span>

                                </td>

                                <td>
                                    <?= e(
                                        (string) (
                                            $solicitud['created_at']
                                            ?? ''
                                        )
                                    ) ?>
                                </td>

                                <td class="text-end">

                                    <div class="d-flex justify-content-end flex-wrap gap-1">

                                        <!-- VER -->
                                        <a
                                            href="<?= e(
                                                url(
                                                    '/materiales/solicitudes/ver?id=' .
                                                    $idSolicitud
                                                )
                                            ) ?>"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Ver solicitud"
                                        >
                                            <i class="bi bi-eye me-1"></i>
                                            Ver
                                        </a>

                                        <?php if ($estado === 'avalada'): ?>

                                            <!-- DESCARGAR EXCEL GFPI-F-186 -->
                                            <a
                                                href="<?= e(
                                                    url(
                                                        '/materiales/solicitudes/exportar-gfpi?id=' .
                                                        $idSolicitud
                                                    )
                                                ) ?>"
                                                class="btn btn-sm btn-success"
                                                title="Descargar solicitud avalada en Excel GFPI-F-186"
                                            >
                                                <i class="bi bi-file-earmark-excel me-1"></i>
                                                F-186
                                            </a>

                                        <?php endif; ?>
										
				<!-- DESCARGAR FORMATO INDIVIDUAL GIL-F-014 -->
<a
    href="<?= e(
        url(
            '/materiales/solicitudes/exportar-individual?id=' .
            $idSolicitud
        )
    ) ?>"
    class="btn btn-sm btn-outline-success"
    title="Descargar formato individual GIL-F-014"
>
    <i class="bi bi-file-earmark-excel me-1"></i>
    F-14
</a>


                                        <?php if ($estado === 'borrador'): ?>

                                            <!-- EDITAR -->
                                            <a
                                                href="<?= e(
                                                    url(
                                                        '/materiales/solicitudes/editar?id=' .
                                                        $idSolicitud
                                                    )
                                                ) ?>"
                                                class="btn btn-sm btn-outline-warning"
                                                title="Editar borrador"
                                            >
                                                <i class="bi bi-pencil-square me-1"></i>
                                                Editar
                                            </a>


                                            <!-- ENVIAR A AVAL -->
                                            <form
                                                method="post"
                                                action="<?= e(
                                                    url(
                                                        '/materiales/solicitudes/enviar'
                                                    )
                                                ) ?>"
                                                class="d-inline"
                                            >

                                                <?= csrfField() ?>

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= $idSolicitud ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-success"
                                                    title="Enviar solicitud a aval"
                                                    onclick="return confirm('¿Está seguro de enviar esta solicitud a aval? Después de enviarla ya no podrá editarla ni eliminarla como borrador.');"
                                                >
                                                    <i class="bi bi-send me-1"></i>
                                                    Enviar
                                                </button>

                                            </form>


                                            <!-- ELIMINAR -->
                                            <form
                                                method="post"
                                                action="<?= e(
                                                    url(
                                                        '/materiales/solicitudes/eliminar-borrador'
                                                    )
                                                ) ?>"
                                                class="d-inline"
                                            >

                                                <?= csrfField() ?>

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= $idSolicitud ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Eliminar borrador"
                                                    onclick="return confirm('¿Está seguro de eliminar este borrador? Esta acción no se puede deshacer.');"
                                                >
                                                    <i class="bi bi-trash me-1"></i>
                                                    Eliminar
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

</div>
