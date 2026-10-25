<?php

declare(strict_types=1);

if (!isset($titulo)) {
    $titulo = 'Programas';
}

$buscar = (string) ($buscar ?? ($_GET['buscar'] ?? ''));

$pagina = (int) ($pagina ?? 1);

$porPagina = (int) ($porPagina ?? 20);

$total = (int) ($total ?? 0);

$totalPaginas = (int) ($totalPaginas ?? 1);

$programas = $programas ?? [];

$opcionesPorPagina = $opcionesPorPagina ?? [
    10,
    20,
    50,
    100,
];

$mensajeExito = $_SESSION['flash_success'] ?? null;

$mensajeError = $_SESSION['flash_error'] ?? null;

unset(
    $_SESSION['flash_success'],
    $_SESSION['flash_error']
);

require_once ROOT_PATH . '/views/layout/header.php';
?>

<style>
    .sita-page {
        padding: 30px;
        background: #f5f7fa;
        min-height: calc(100vh - 70px);
    }

    .sita-page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 25px;
        flex-wrap: wrap;
    }

    .sita-page-title {
        margin: 0;
        font-size: 28px;
        font-weight: 700;
        color: #172033;
    }

    .sita-page-subtitle {
        margin: 6px 0 0;
        color: #6b7280;
    }

    .sita-page-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .sita-panel {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 5px 20px rgba(0, 0, 0, .04);
        overflow: hidden;
    }

    .sita-panel-header {
        padding: 20px 24px;
        border-bottom: 1px solid #edf0f3;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .sita-search {
        display: flex;
        gap: 10px;
        flex: 1;
        max-width: 650px;
    }

    .sita-search input {
        flex: 1;
        min-width: 180px;
        border: 1px solid #d8dde5;
        border-radius: 9px;
        padding: 10px 13px;
        outline: none;
        font-size: 14px;
    }

    .sita-search input:focus {
        border-color: #198754;
        box-shadow: 0 0 0 3px rgba(25, 135, 84, .10);
    }

    .sita-page-size {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #6b7280;
        font-size: 13px;
        white-space: nowrap;
    }

    .sita-page-size select {
        border: 1px solid #d8dde5;
        border-radius: 8px;
        padding: 8px 10px;
        background: #fff;
        color: #374151;
        outline: none;
    }

    .sita-btn {
        border: 0;
        border-radius: 9px;
        padding: 10px 17px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        cursor: pointer;
        font-size: 14px;
        white-space: nowrap;
    }

    .sita-btn-primary {
        background: #198754;
        color: #fff;
    }

    .sita-btn-primary:hover {
        background: #157347;
        color: #fff;
    }

    .sita-btn-secondary {
        background: #eef1f4;
        color: #374151;
    }

    .sita-btn-secondary:hover {
        background: #e2e6ea;
        color: #374151;
    }

    .sita-btn-danger {
        background: #dc3545;
        color: #fff;
    }

    .sita-btn-danger:hover {
        background: #bb2d3b;
        color: #fff;
    }

    .sita-btn-warning {
        background: #fff3cd;
        color: #664d03;
    }

    .sita-btn-warning:hover {
        background: #ffecb5;
        color: #664d03;
    }

    .sita-table-wrapper {
        overflow-x: auto;
    }

    .sita-table {
        width: 100%;
        border-collapse: collapse;
    }

    .sita-table th {
        background: #f8fafc;
        color: #4b5563;
        font-size: 13px;
        text-transform: uppercase;
        padding: 14px 18px;
        text-align: left;
        white-space: nowrap;
    }

    .sita-table td {
        padding: 15px 18px;
        border-top: 1px solid #edf0f3;
        color: #374151;
        vertical-align: middle;
    }

    .sita-table tbody tr:hover {
        background: #fafcfb;
    }

    .sita-codigo {
        font-weight: 700;
        color: #172033;
    }

    .sita-programa {
        font-size: 14px;
        color: #172033;
        font-weight: 600;
    }

    .sita-programa-secundario {
        font-size: 12px;
        color: #6b7280;
        margin-top: 3px;
    }

    .sita-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .sita-badge-activo {
        background: #dcfce7;
        color: #166534;
    }

    .sita-badge-inactivo {
        background: #fee2e2;
        color: #991b1b;
    }

    .sita-action-group {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .sita-action {
        color: #198754;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        border: 0;
        background: transparent;
        padding: 0;
        cursor: pointer;
    }

    .sita-action:hover {
        text-decoration: underline;
    }

    .sita-action-danger {
        color: #dc3545;
    }

    .sita-action-success {
        color: #198754;
    }

    .sita-empty {
        padding: 50px 20px;
        text-align: center;
        color: #6b7280;
    }

    .sita-pagination {
        padding: 18px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
        border-top: 1px solid #edf0f3;
    }

    .sita-pagination-info {
        color: #6b7280;
        font-size: 14px;
    }

    .sita-pagination-links {
        display: flex;
        gap: 6px;
    }

    .sita-page-link {
        min-width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #f1f5f9;
        color: #374151;
        text-decoration: none;
        font-size: 13px;
    }

    .sita-page-link:hover {
        background: #e2e8f0;
        color: #374151;
    }

    .sita-page-link.active {
        background: #198754;
        color: #fff;
    }

    .sita-page-link.disabled {
        opacity: .45;
        pointer-events: none;
    }

    /*
    |--------------------------------------------------------------------------
    | MENSAJES
    |--------------------------------------------------------------------------
    */

    .sita-alert {
        margin-bottom: 20px;
        padding: 14px 18px;
        border-radius: 10px;
        font-size: 14px;
        border: 1px solid transparent;
    }

    .sita-alert-success {
        background: #ecfdf3;
        color: #166534;
        border-color: #bbf7d0;
    }

    .sita-alert-error {
        background: #fef2f2;
        color: #991b1b;
        border-color: #fecaca;
    }

    /*
    |--------------------------------------------------------------------------
    | MODAL
    |--------------------------------------------------------------------------
    */

    .sita-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, .55);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        z-index: 9999;
    }

    .sita-modal-backdrop.is-open {
        display: flex;
    }

    .sita-modal {
        width: min(760px, 100%);
        max-height: 90vh;
        overflow-y: auto;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .20);
    }

    .sita-modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid #edf0f3;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }

    .sita-modal-title {
        margin: 0;
        font-size: 21px;
        font-weight: 700;
        color: #172033;
    }

    .sita-modal-close {
        width: 36px;
        height: 36px;
        border: 0;
        border-radius: 8px;
        background: #f1f5f9;
        color: #374151;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
    }

    .sita-modal-close:hover {
        background: #e2e8f0;
    }

    .sita-modal-body {
        padding: 24px;
    }

    .sita-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 17px;
    }

    .sita-form-group {
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    .sita-form-group-full {
        grid-column: 1 / -1;
    }

    .sita-form-label {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }

    .sita-form-control {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid #d8dde5;
        border-radius: 9px;
        padding: 10px 12px;
        background: #fff;
        color: #172033;
        outline: none;
        font-size: 14px;
    }

    .sita-form-control:focus {
        border-color: #198754;
        box-shadow: 0 0 0 3px rgba(25, 135, 84, .10);
    }

    .sita-form-help {
        font-size: 12px;
        color: #6b7280;
        margin: 0;
    }

    .sita-modal-footer {
        padding: 18px 24px;
        border-top: 1px solid #edf0f3;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    /*
    |--------------------------------------------------------------------------
    | MODAL CONFIRMACIÓN ESTADO
    |--------------------------------------------------------------------------
    */

    .sita-confirm-text {
        color: #4b5563;
        line-height: 1.6;
        margin: 0;
    }

    .sita-confirm-programa {
        margin-top: 12px;
        padding: 12px 15px;
        border-radius: 9px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
    }

    .sita-confirm-programa strong {
        color: #172033;
    }

    /*
    |--------------------------------------------------------------------------
    | INFORMACIÓN DEL PROGRAMA EN MODAL EDITAR
    |--------------------------------------------------------------------------
    */

    .sita-edit-info {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        padding: 12px 15px;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        color: #4b5563;
        font-size: 13px;
    }

    .sita-edit-info strong {
        color: #172033;
    }

    @media (max-width: 900px) {

        .sita-table th:nth-child(5),
        .sita-table td:nth-child(5) {
            display: none;
        }

    }

    @media (max-width: 700px) {

        .sita-page {
            padding: 20px;
        }

        .sita-form-grid {
            grid-template-columns: 1fr;
        }

        .sita-form-group-full {
            grid-column: auto;
        }

        .sita-search {
            max-width: none;
            width: 100%;
            flex-wrap: wrap;
        }

        .sita-search input {
            width: 100%;
        }

        .sita-page-actions {
            width: 100%;
        }

        .sita-page-actions .sita-btn {
            flex: 1;
        }

        .sita-panel-header {
            align-items: stretch;
        }

        .sita-page-size {
            justify-content: space-between;
        }

        .sita-pagination {
            justify-content: center;
        }

    }
</style>


<main class="sita-page">

    <div class="sita-page-header">

        <div>

            <h1 class="sita-page-title">
                Programas
            </h1>

            <p class="sita-page-subtitle">
                Administración de los programas de formación registrados en el sistema.
            </p>

        </div>

        <div class="sita-page-actions">

            <?php if (Auth::can('programas.crear')): ?>

                <button
                    type="button"
                    class="sita-btn sita-btn-primary"
                    id="btnNuevoPrograma"
                >
                    + Nuevo programa
                </button>

            <?php endif; ?>

            <a
                href="<?= e(url('/dashboard')) ?>"
                class="sita-btn sita-btn-secondary"
            >
                ← Dashboard
            </a>

        </div>

    </div>


    <?php if (!empty($mensajeExito)): ?>

        <div class="sita-alert sita-alert-success">
            <?= e($mensajeExito) ?>
        </div>

    <?php endif; ?>


    <?php if (!empty($mensajeError)): ?>

        <div class="sita-alert sita-alert-error">
            <?= e($mensajeError) ?>
        </div>

    <?php endif; ?>


    <section class="sita-panel">

        <div class="sita-panel-header">

            <form
                method="GET"
                action="<?= e(url('/programas')) ?>"
                class="sita-search"
            >

                <input
                    type="text"
                    name="buscar"
                    value="<?= e($buscar) ?>"
                    placeholder="Buscar por código, nombre o nivel..."
                >

                <input
                    type="hidden"
                    name="por_pagina"
                    value="<?= e($porPagina) ?>"
                >

                <button
                    type="submit"
                    class="sita-btn sita-btn-primary"
                >
                    Buscar
                </button>

                <?php if ($buscar !== ''): ?>

                    <a
                        href="<?= e(url('/programas')) ?>"
                        class="sita-btn sita-btn-secondary"
                    >
                        Limpiar
                    </a>

                <?php endif; ?>

            </form>


            <div class="sita-page-size">

                <span>
                    Mostrar
                </span>

                <select
                    id="porPagina"
                    aria-label="Programas por página"
                >

                    <?php foreach ($opcionesPorPagina as $opcion): ?>

                        <option
                            value="<?= e($opcion) ?>"
                            <?= $opcion === $porPagina ? 'selected' : '' ?>
                        >
                            <?= e($opcion) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <span>
                    de <?= e($total) ?> programa(s)
                </span>

            </div>

        </div>


        <?php if (empty($programas)): ?>

            <div class="sita-empty">

                <?php if ($buscar !== ''): ?>

                    No se encontraron programas para
                    <strong><?= e($buscar) ?></strong>.

                <?php else: ?>

                    No hay programas registrados.

                <?php endif; ?>

            </div>

        <?php else: ?>

            <div class="sita-table-wrapper">

                <table class="sita-table">

                    <thead>

                        <tr>

                            <th>
                                Código
                            </th>

                            <th>
                                Programa
                            </th>

                            <th>
                                Nivel
                            </th>

                            <th>
                                Duración
                            </th>

                            <th>
                                Registro
                            </th>

                            <th>
                                Estado
                            </th>

                            <th>
                                Acciones
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($programas as $programa): ?>

                            <?php
                            $estado = strtolower(
                                trim(
                                    (string) (
                                        $programa['estado'] ?? ''
                                    )
                                )
                            );

                            $estadoTexto =
                                $estado === 'activo'
                                    ? 'Activo'
                                    : 'Inactivo';

                            $nombrePrograma =
                                (string) (
                                    $programa['nombre'] ?? ''
                                );

                            $codigoPrograma =
                                (string) (
                                    $programa['codigo'] ?? ''
                                );

                            $nivelPrograma =
                                (string) (
                                    $programa['nivel'] ?? ''
                                );

                            $duracionPrograma =
                                $programa['duracion_meses']
                                    ?? '';

                            $idPrograma =
                                (int) (
                                    $programa['id'] ?? 0
                                );
                            ?>

                            <tr>

                                <td>

                                    <div class="sita-codigo">
                                        <?= e($codigoPrograma) ?>
                                    </div>

                                </td>


                                <td>

                                    <div class="sita-programa">
                                        <?= e($nombrePrograma) ?>
                                    </div>

                                </td>


                                <td>

                                    <?= e(
                                        $nivelPrograma !== ''
                                            ? $nivelPrograma
                                            : '—'
                                    ) ?>

                                </td>


                                <td>

                                    <?php
                                    $duracion =
                                        $programa['duracion_meses']
                                            ?? null;
                                    ?>

                                    <?php if (
                                        $duracion !== null
                                        && $duracion !== ''
                                    ): ?>

                                        <?= e($duracion) ?>
                                        <?= (int) $duracion === 1
                                            ? 'mes'
                                            : 'meses' ?>

                                    <?php else: ?>

                                        —

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php
                                    $fechaRegistro =
                                        $programa['created_at']
                                            ?? '';
                                    ?>

                                    <?php if (
                                        $fechaRegistro !== ''
                                    ): ?>

                                        <?= e(
                                            fecha(
                                                substr(
                                                    (string) $fechaRegistro,
                                                    0,
                                                    10
                                                )
                                            )
                                        ) ?>

                                    <?php else: ?>

                                        —

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <span
                                        class="sita-badge sita-badge-<?= e($estado) ?>"
                                    >
                                        <?= e($estadoTexto) ?>
                                    </span>

                                </td>


                                <td>

                                    <div class="sita-action-group">

                                        <?php if (
                                            Auth::can(
                                                'programas.editar'
                                            )
                                        ): ?>

                                            <button
                                                type="button"
                                                class="sita-action btnEditarPrograma"
                                                data-id="<?= e($idPrograma) ?>"
                                                data-codigo="<?= e($codigoPrograma) ?>"
                                                data-nombre="<?= e($nombrePrograma) ?>"
                                                data-nivel="<?= e($nivelPrograma) ?>"
                                                data-duracion="<?= e((string) $duracionPrograma) ?>"
                                                data-estado="<?= e($estado) ?>"
                                            >
                                                Editar
                                            </button>

                                        <?php endif; ?>


                                        <?php if (
                                            Auth::can(
                                                'programas.estado'
                                            )
                                        ): ?>

                                            <?php if (
                                                $estado === 'activo'
                                            ): ?>

                                                <button
                                                    type="button"
                                                    class="sita-action sita-action-danger btnCambiarEstado"
                                                    data-id="<?= e($idPrograma) ?>"
                                                    data-estado="inactivo"
                                                    data-codigo="<?= e($codigoPrograma) ?>"
                                                    data-nombre="<?= e($nombrePrograma) ?>"
                                                    data-accion="inactivar"
                                                >
                                                    Inactivar
                                                </button>

                                            <?php else: ?>

                                                <button
                                                    type="button"
                                                    class="sita-action sita-action-success btnCambiarEstado"
                                                    data-id="<?= e($idPrograma) ?>"
                                                    data-estado="activo"
                                                    data-codigo="<?= e($codigoPrograma) ?>"
                                                    data-nombre="<?= e($nombrePrograma) ?>"
                                                    data-accion="activar"
                                                >
                                                    Activar
                                                </button>

                                            <?php endif; ?>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>


        <?php if ($totalPaginas > 1): ?>

            <div class="sita-pagination">

                <div class="sita-pagination-info">

                    Página
                    <?= e($pagina) ?>
                    de
                    <?= e($totalPaginas) ?>

                </div>


                <div class="sita-pagination-links">

                    <?php if ($pagina > 1): ?>

                        <a
                            class="sita-page-link"
                            href="<?= e(
                                url(
                                    '/programas?pagina='
                                    . ($pagina - 1)
                                    . '&buscar='
                                    . urlencode($buscar)
                                    . '&por_pagina='
                                    . $porPagina
                                )
                            ) ?>"
                        >
                            ‹
                        </a>

                    <?php endif; ?>


                    <?php for (
                        $i = max(1, $pagina - 2);
                        $i <= min(
                            $totalPaginas,
                            $pagina + 2
                        );
                        $i++
                    ): ?>

                        <a
                            class="sita-page-link <?= $i === $pagina ? 'active' : '' ?>"
                            href="<?= e(
                                url(
                                    '/programas?pagina='
                                    . $i
                                    . '&buscar='
                                    . urlencode($buscar)
                                    . '&por_pagina='
                                    . $porPagina
                                )
                            ) ?>"
                        >
                            <?= e($i) ?>
                        </a>

                    <?php endfor; ?>


                    <?php if ($pagina < $totalPaginas): ?>

                        <a
                            class="sita-page-link"
                            href="<?= e(
                                url(
                                    '/programas?pagina='
                                    . ($pagina + 1)
                                    . '&buscar='
                                    . urlencode($buscar)
                                    . '&por_pagina='
                                    . $porPagina
                                )
                            ) ?>"
                        >
                            ›
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>

    </section>

</main>


<?php if (
    Auth::can('programas.crear')
): ?>

    <!-- ============================================================
         MODAL NUEVO PROGRAMA
         ============================================================ -->

    <div
        class="sita-modal-backdrop"
        id="modalNuevoPrograma"
        aria-hidden="true"
    >

        <div
            class="sita-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="tituloModalNuevoPrograma"
        >

            <div class="sita-modal-header">

                <h2
                    class="sita-modal-title"
                    id="tituloModalNuevoPrograma"
                >
                    Nuevo programa
                </h2>

                <button
                    type="button"
                    class="sita-modal-close"
                    id="btnCerrarNuevoPrograma"
                    aria-label="Cerrar"
                >
                    ×
                </button>

            </div>


            <form
                method="POST"
                action="<?= e(url('/programas/crear')) ?>"
            >

                <?= csrfField() ?>


                <div class="sita-modal-body">

                    <div class="sita-form-grid">

                        <div class="sita-form-group">

                            <label
                                for="codigo"
                                class="sita-form-label"
                            >
                                Código del programa *
                            </label>

                            <input
                                type="text"
                                id="codigo"
                                name="codigo"
                                class="sita-form-control"
                                maxlength="50"
                                required
                                autocomplete="off"
                                placeholder="Ej. 228106"
                            >

                        </div>


                        <div class="sita-form-group">

                            <label
                                for="nivel"
                                class="sita-form-label"
                            >
                                Nivel
                            </label>

                            <select
    id="nivel"
    name="nivel"
    class="sita-form-control"
>
    <option value="">Seleccione un nivel</option>

    <option value="AUXILIAR">
        AUXILIAR
    </option>

    <option value="OPERARIO">
        OPERARIO
    </option>

    <option value="TÉCNICO">
        TÉCNICO
    </option>

    <option value="TECNÓLOGO">
        TECNÓLOGO
    </option>
</select>

                        </div>


                        <div class="sita-form-group sita-form-group-full">

                            <label
                                for="nombre"
                                class="sita-form-label"
                            >
                                Nombre del programa *
                            </label>

                            <input
                                type="text"
                                id="nombre"
                                name="nombre"
                                class="sita-form-control"
                                maxlength="200"
                                required
                                autocomplete="off"
                                placeholder="Nombre completo del programa de formación"
                            >

                        </div>


                        <div class="sita-form-group">

                            <label
                                for="duracion_meses"
                                class="sita-form-label"
                            >
                                Duración en meses
                            </label>

                            <input
                                type="number"
                                id="duracion_meses"
                                name="duracion_meses"
                                class="sita-form-control"
                                min="1"
                                step="1"
                                placeholder="Ej. 27"
                            >

                        </div>


                        <div class="sita-form-group">

                            <label
                                for="estado"
                                class="sita-form-label"
                            >
                                Estado *
                            </label>

                            <select
                                id="estado"
                                name="estado"
                                class="sita-form-control"
                                required
                            >

                                <option
                                    value="activo"
                                    selected
                                >
                                    Activo
                                </option>

                                <option
                                    value="inactivo"
                                >
                                    Inactivo
                                </option>

                            </select>

                        </div>


                        <div class="sita-form-group sita-form-group-full">

                            <p class="sita-form-help">
                                El código del programa debe ser único.
                                Los programas inactivos podrán conservarse
                                para mantener la trazabilidad histórica.
                            </p>

                        </div>

                    </div>

                </div>


                <div class="sita-modal-footer">

                    <button
                        type="button"
                        class="sita-btn sita-btn-secondary"
                        id="btnCancelarNuevoPrograma"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="sita-btn sita-btn-primary"
                    >
                        Crear programa
                    </button>

                </div>

            </form>

        </div>

    </div>

<?php endif; ?>


<?php if (
    Auth::can('programas.editar')
): ?>

    <!-- ============================================================
         MODAL EDITAR PROGRAMA
         ============================================================ -->

    <div
        class="sita-modal-backdrop"
        id="modalEditarPrograma"
        aria-hidden="true"
    >

        <div
            class="sita-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="tituloModalEditarPrograma"
        >

            <div class="sita-modal-header">

                <h2
                    class="sita-modal-title"
                    id="tituloModalEditarPrograma"
                >
                    Editar programa
                </h2>

                <button
                    type="button"
                    class="sita-modal-close"
                    id="btnCerrarEditarPrograma"
                    aria-label="Cerrar"
                >
                    ×
                </button>

            </div>


            <form
                method="POST"
                action="<?= e(url('/programas/editar')) ?>"
                id="formEditarPrograma"
            >

                <?= csrfField() ?>


                <input
                    type="hidden"
                    name="id"
                    id="editar_id"
                    value=""
                >


                <div class="sita-modal-body">

                    <div class="sita-edit-info">

                        <span>
                            Editando el programa:
                        </span>

                        <strong id="editar_codigo_referencia">
                            —
                        </strong>

                    </div>


                    <div class="sita-form-grid">

                        <div class="sita-form-group">

                            <label
                                for="editar_codigo"
                                class="sita-form-label"
                            >
                                Código del programa *
                            </label>

                            <input
                                type="text"
                                id="editar_codigo"
                                name="codigo"
                                class="sita-form-control"
                                maxlength="50"
                                required
                                autocomplete="off"
                            >

                        </div>


                        <div class="sita-form-group">

                            <label
                                for="editar_nivel"
                                class="sita-form-label"
                            >
                                Nivel
                            </label>

                            <select
    id="editar_nivel"
    name="nivel"
    class="sita-form-control"
>
    <option value="">Seleccione un nivel</option>

    <option value="AUXILIAR">
        AUXILIAR
    </option>

    <option value="OPERARIO">
        OPERARIO
    </option>

    <option value="TÉCNICO">
        TÉCNICO
    </option>

    <option value="TECNÓLOGO">
        TECNÓLOGO
    </option>
</select>

                        </div>


                        <div class="sita-form-group sita-form-group-full">

                            <label
                                for="editar_nombre"
                                class="sita-form-label"
                            >
                                Nombre del programa *
                            </label>

                            <input
                                type="text"
                                id="editar_nombre"
                                name="nombre"
                                class="sita-form-control"
                                maxlength="200"
                                required
                                autocomplete="off"
                            >

                        </div>


                        <div class="sita-form-group">

                            <label
                                for="editar_duracion_meses"
                                class="sita-form-label"
                            >
                                Duración en meses
                            </label>

                            <input
                                type="number"
                                id="editar_duracion_meses"
                                name="duracion_meses"
                                class="sita-form-control"
                                min="1"
                                step="1"
                            >

                        </div>


                        <div class="sita-form-group">

                            <label
                                for="editar_estado"
                                class="sita-form-label"
                            >
                                Estado *
                            </label>

                            <select
                                id="editar_estado"
                                name="estado"
                                class="sita-form-control"
                                required
                            >

                                <option
                                    value="activo"
                                >
                                    Activo
                                </option>

                                <option
                                    value="inactivo"
                                >
                                    Inactivo
                                </option>

                            </select>

                        </div>


                        <div class="sita-form-group sita-form-group-full">

                            <p class="sita-form-help">
                                Modifique únicamente la información que
                                necesite actualizar. El código debe continuar
                                siendo único dentro de los programas.
                            </p>

                        </div>

                    </div>

                </div>


                <div class="sita-modal-footer">

                    <button
                        type="button"
                        class="sita-btn sita-btn-secondary"
                        id="btnCancelarEditarPrograma"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="sita-btn sita-btn-primary"
                    >
                        Guardar cambios
                    </button>

                </div>

            </form>

        </div>

    </div>

<?php endif; ?>


<?php if (
    Auth::can('programas.estado')
): ?>

    <!-- ============================================================
         MODAL CAMBIAR ESTADO
         ============================================================ -->

    <div
        class="sita-modal-backdrop"
        id="modalCambiarEstado"
        aria-hidden="true"
    >

        <div
            class="sita-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="tituloModalEstado"
        >

            <div class="sita-modal-header">

                <h2
                    class="sita-modal-title"
                    id="tituloModalEstado"
                >
                    Cambiar estado
                </h2>

                <button
                    type="button"
                    class="sita-modal-close"
                    id="btnCerrarEstado"
                    aria-label="Cerrar"
                >
                    ×
                </button>

            </div>


            <form
                method="POST"
                action="<?= e(url('/programas/estado')) ?>"
            >

                <?= csrfField() ?>


                <input
                    type="hidden"
                    name="id"
                    id="estado_id"
                    value=""
                >

                <input
                    type="hidden"
                    name="estado"
                    id="estado_nuevo"
                    value=""
                >


                <div class="sita-modal-body">

                    <p
                        class="sita-confirm-text"
                        id="textoConfirmacionEstado"
                    >
                        ¿Desea cambiar el estado del programa?
                    </p>


                    <div
                        class="sita-confirm-programa"
                        id="datosProgramaEstado"
                    >

                        <strong id="estado_codigo">
                            —
                        </strong>

                        <div
                            class="sita-programa-secundario"
                            id="estado_nombre"
                        >
                            —
                        </div>

                    </div>

                </div>


                <div class="sita-modal-footer">

                    <button
                        type="button"
                        class="sita-btn sita-btn-secondary"
                        id="btnCancelarEstado"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="sita-btn sita-btn-primary"
                        id="btnConfirmarEstado"
                    >
                        Confirmar
                    </button>

                </div>

            </form>

        </div>

    </div>

<?php endif; ?>


<script>
    document.addEventListener(
        'DOMContentLoaded',
        function () {

            /*
             * ========================================================
             * MODAL NUEVO PROGRAMA
             * ========================================================
             */

            const modalNuevo =
                document.getElementById(
                    'modalNuevoPrograma'
                );

            const btnNuevo =
                document.getElementById(
                    'btnNuevoPrograma'
                );

            const btnCerrarNuevo =
                document.getElementById(
                    'btnCerrarNuevoPrograma'
                );

            const btnCancelarNuevo =
                document.getElementById(
                    'btnCancelarNuevoPrograma'
                );


            function abrirNuevoPrograma() {

                if (!modalNuevo) {
                    return;
                }

                modalNuevo.classList.add(
                    'is-open'
                );

                modalNuevo.setAttribute(
                    'aria-hidden',
                    'false'
                );

                document.body.style.overflow =
                    'hidden';

                const campoCodigo =
                    document.getElementById(
                        'codigo'
                    );

                if (campoCodigo) {

                    setTimeout(
                        function () {
                            campoCodigo.focus();
                        },
                        100
                    );

                }
            }


            function cerrarNuevoPrograma() {

                if (!modalNuevo) {
                    return;
                }

                modalNuevo.classList.remove(
                    'is-open'
                );

                modalNuevo.setAttribute(
                    'aria-hidden',
                    'true'
                );

                document.body.style.overflow =
                    '';
            }


            if (btnNuevo) {

                btnNuevo.addEventListener(
                    'click',
                    abrirNuevoPrograma
                );

            }


            if (btnCerrarNuevo) {

                btnCerrarNuevo.addEventListener(
                    'click',
                    cerrarNuevoPrograma
                );

            }


            if (btnCancelarNuevo) {

                btnCancelarNuevo.addEventListener(
                    'click',
                    cerrarNuevoPrograma
                );

            }


            if (modalNuevo) {

                modalNuevo.addEventListener(
                    'click',
                    function (event) {

                        if (
                            event.target ===
                            modalNuevo
                        ) {
                            cerrarNuevoPrograma();
                        }

                    }
                );

            }


            /*
             * ========================================================
             * MODAL EDITAR PROGRAMA
             * ========================================================
             */

            const modalEditar =
                document.getElementById(
                    'modalEditarPrograma'
                );

            const btnCerrarEditar =
                document.getElementById(
                    'btnCerrarEditarPrograma'
                );

            const btnCancelarEditar =
                document.getElementById(
                    'btnCancelarEditarPrograma'
                );

            const editarId =
                document.getElementById(
                    'editar_id'
                );

            const editarCodigo =
                document.getElementById(
                    'editar_codigo'
                );

            const editarCodigoReferencia =
                document.getElementById(
                    'editar_codigo_referencia'
                );

            const editarNombre =
                document.getElementById(
                    'editar_nombre'
                );

            const editarNivel =
                document.getElementById(
                    'editar_nivel'
                );

            const editarDuracion =
                document.getElementById(
                    'editar_duracion_meses'
                );

            const editarEstado =
                document.getElementById(
                    'editar_estado'
                );


            function abrirEditarPrograma(
                boton
            ) {

                if (
                    !modalEditar
                    || !boton
                ) {
                    return;
                }

                const id =
                    boton.getAttribute(
                        'data-id'
                    ) || '';

                const codigo =
                    boton.getAttribute(
                        'data-codigo'
                    ) || '';

                const nombre =
                    boton.getAttribute(
                        'data-nombre'
                    ) || '';

                const nivel =
                    boton.getAttribute(
                        'data-nivel'
                    ) || '';

                const duracion =
                    boton.getAttribute(
                        'data-duracion'
                    ) || '';

                const estado =
                    boton.getAttribute(
                        'data-estado'
                    ) || 'activo';


                if (editarId) {
                    editarId.value = id;
                }

                if (editarCodigo) {
                    editarCodigo.value = codigo;
                }

                if (editarCodigoReferencia) {
                    editarCodigoReferencia.textContent =
                        codigo || '—';
                }

                if (editarNombre) {
                    editarNombre.value = nombre;
                }

                if (editarNivel) {
                    editarNivel.value = nivel;
                }

                if (editarDuracion) {
                    editarDuracion.value = duracion;
                }

                if (editarEstado) {
                    editarEstado.value =
                        estado === 'inactivo'
                            ? 'inactivo'
                            : 'activo';
                }


                modalEditar.classList.add(
                    'is-open'
                );

                modalEditar.setAttribute(
                    'aria-hidden',
                    'false'
                );

                document.body.style.overflow =
                    'hidden';


                if (editarCodigo) {

                    setTimeout(
                        function () {
                            editarCodigo.focus();
                            editarCodigo.select();
                        },
                        100
                    );

                }

            }


            function cerrarEditarPrograma() {

                if (!modalEditar) {
                    return;
                }

                modalEditar.classList.remove(
                    'is-open'
                );

                modalEditar.setAttribute(
                    'aria-hidden',
                    'true'
                );

                document.body.style.overflow =
                    '';

            }


            document.querySelectorAll(
                '.btnEditarPrograma'
            ).forEach(
                function (boton) {

                    boton.addEventListener(
                        'click',
                        function () {

                            abrirEditarPrograma(
                                boton
                            );

                        }
                    );

                }
            );


            if (btnCerrarEditar) {

                btnCerrarEditar.addEventListener(
                    'click',
                    cerrarEditarPrograma
                );

            }


            if (btnCancelarEditar) {

                btnCancelarEditar.addEventListener(
                    'click',
                    cerrarEditarPrograma
                );

            }


            if (modalEditar) {

                modalEditar.addEventListener(
                    'click',
                    function (event) {

                        if (
                            event.target ===
                            modalEditar
                        ) {
                            cerrarEditarPrograma();
                        }

                    }
                );

            }


            /*
             * ========================================================
             * MODAL CAMBIAR ESTADO
             * ========================================================
             */

            const modalEstado =
                document.getElementById(
                    'modalCambiarEstado'
                );

            const btnCerrarEstado =
                document.getElementById(
                    'btnCerrarEstado'
                );

            const btnCancelarEstado =
                document.getElementById(
                    'btnCancelarEstado'
                );

            const estadoId =
                document.getElementById(
                    'estado_id'
                );

            const estadoNuevo =
                document.getElementById(
                    'estado_nuevo'
                );

            const estadoCodigo =
                document.getElementById(
                    'estado_codigo'
                );

            const estadoNombre =
                document.getElementById(
                    'estado_nombre'
                );

            const textoConfirmacionEstado =
                document.getElementById(
                    'textoConfirmacionEstado'
                );

            const btnConfirmarEstado =
                document.getElementById(
                    'btnConfirmarEstado'
                );


            function abrirCambioEstado(
                boton
            ) {

                if (
                    !modalEstado
                    || !boton
                ) {
                    return;
                }

                const id =
                    boton.getAttribute(
                        'data-id'
                    );

                const nuevoEstado =
                    boton.getAttribute(
                        'data-estado'
                    );

                const codigo =
                    boton.getAttribute(
                        'data-codigo'
                    ) || '';

                const nombre =
                    boton.getAttribute(
                        'data-nombre'
                    ) || '';

                const accion =
                    boton.getAttribute(
                        'data-accion'
                    ) || 'cambiar';


                if (estadoId) {
                    estadoId.value =
                        id || '';
                }

                if (estadoNuevo) {
                    estadoNuevo.value =
                        nuevoEstado || '';
                }

                if (estadoCodigo) {
                    estadoCodigo.textContent =
                        codigo || '—';
                }

                if (estadoNombre) {
                    estadoNombre.textContent =
                        nombre || '—';
                }


                if (
                    textoConfirmacionEstado
                ) {

                    if (
                        accion ===
                        'inactivar'
                    ) {

                        textoConfirmacionEstado.textContent =
                            '¿Desea inactivar este programa? El programa se conservará en el sistema para mantener su trazabilidad.';

                    } else {

                        textoConfirmacionEstado.textContent =
                            '¿Desea activar este programa?';

                    }

                }


                if (btnConfirmarEstado) {

                    btnConfirmarEstado.textContent =
                        accion === 'inactivar'
                            ? 'Inactivar programa'
                            : 'Activar programa';

                }


                if (btnConfirmarEstado) {

                    if (
                        accion ===
                        'inactivar'
                    ) {

                        btnConfirmarEstado.classList.remove(
                            'sita-btn-primary'
                        );

                        btnConfirmarEstado.classList.add(
                            'sita-btn-danger'
                        );

                    } else {

                        btnConfirmarEstado.classList.remove(
                            'sita-btn-danger'
                        );

                        btnConfirmarEstado.classList.add(
                            'sita-btn-primary'
                        );

                    }

                }


                modalEstado.classList.add(
                    'is-open'
                );

                modalEstado.setAttribute(
                    'aria-hidden',
                    'false'
                );

                document.body.style.overflow =
                    'hidden';

            }


            function cerrarCambioEstado() {

                if (!modalEstado) {
                    return;
                }

                modalEstado.classList.remove(
                    'is-open'
                );

                modalEstado.setAttribute(
                    'aria-hidden',
                    'true'
                );

                document.body.style.overflow =
                    '';

            }


            document.querySelectorAll(
                '.btnCambiarEstado'
            ).forEach(
                function (boton) {

                    boton.addEventListener(
                        'click',
                        function () {

                            abrirCambioEstado(
                                boton
                            );

                        }
                    );

                }
            );


            if (btnCerrarEstado) {

                btnCerrarEstado.addEventListener(
                    'click',
                    cerrarCambioEstado
                );

            }


            if (btnCancelarEstado) {

                btnCancelarEstado.addEventListener(
                    'click',
                    cerrarCambioEstado
                );

            }


            if (modalEstado) {

                modalEstado.addEventListener(
                    'click',
                    function (event) {

                        if (
                            event.target ===
                            modalEstado
                        ) {
                            cerrarCambioEstado();
                        }

                    }
                );

            }


            /*
             * ========================================================
             * ESC PARA CERRAR MODALES
             * ========================================================
             */

            document.addEventListener(
                'keydown',
                function (event) {

                    if (
                        event.key !==
                        'Escape'
                    ) {
                        return;
                    }


                    if (
                        modalNuevo
                        && modalNuevo.classList.contains(
                            'is-open'
                        )
                    ) {

                        cerrarNuevoPrograma();

                        return;
                    }


                    if (
                        modalEditar
                        && modalEditar.classList.contains(
                            'is-open'
                        )
                    ) {

                        cerrarEditarPrograma();

                        return;
                    }


                    if (
                        modalEstado
                        && modalEstado.classList.contains(
                            'is-open'
                        )
                    ) {

                        cerrarCambioEstado();

                    }

                }
            );


            /*
             * ========================================================
             * POR PÁGINA
             * ========================================================
             */

            const selectorPorPagina =
                document.getElementById(
                    'porPagina'
                );


            if (selectorPorPagina) {

                selectorPorPagina.addEventListener(
                    'change',
                    function () {

                        const valor =
                            this.value;

                        const urlActual =
                            new URL(
                                window.location.href
                            );

                        urlActual.searchParams.set(
                            'por_pagina',
                            valor
                        );

                        urlActual.searchParams.set(
                            'pagina',
                            '1'
                        );

                        window.location.href =
                            urlActual.toString();

                    }
                );

            }

        }
    );
</script>


<?php
require_once ROOT_PATH . '/views/layout/footer.php';
?>