<?php
declare(strict_types=1);

require_once ROOT_PATH . '/helpers/helpers.php';
require_once ROOT_PATH . '/core/Auth.php';

$materiales = $materiales ?? [];
$buscar = $buscar ?? '';
$pagina = (int) ($pagina ?? 1);
$porPagina = (int) ($porPagina ?? 20);
$total = (int) ($total ?? 0);
$totalPaginas = (int) ($totalPaginas ?? 1);

$flashSuccess = flash('success');
$flashError = flash('error');

$puedeCambiarEstado = Auth::can(
    'materiales.catalogo.estado'
);

$inicio = $total > 0
    ? (($pagina - 1) * $porPagina) + 1
    : 0;

$fin = min(
    $pagina * $porPagina,
    $total
);
?>

<?php require ROOT_PATH . '/views/layout/header.php'; ?>


<main class="sita-page">

    <div class="sita-page-header">

        <div>

            <h1>
                Catálogo de materiales
            </h1>

            <p>
                Catálogo institucional de bienes y materiales de formación.
            </p>

        </div>

    </div>


    <?php if ($flashSuccess !== null): ?>

        <div class="sita-alert sita-alert-success">

            <div class="sita-alert-icon">
                ✓
            </div>

            <div>
                <?= e($flashSuccess) ?>
            </div>

        </div>

    <?php endif; ?>


    <?php if ($flashError !== null): ?>

        <div class="sita-alert sita-alert-error">

            <div class="sita-alert-icon">
                !
            </div>

            <div>
                <?= e($flashError) ?>
            </div>

        </div>

    <?php endif; ?>


    <section class="sita-panel">

        <div class="sita-panel-header">

            <div>

                <h2>
                    Materiales registrados
                </h2>

                <p>
                    <?= number_format($total, 0, ',', '.') ?>
                    material<?= $total === 1 ? '' : 'es' ?>
                    encontrado<?= $total === 1 ? '' : 's' ?>.
                </p>

            </div>

        </div>


        <form
            method="get"
            action="<?= e(url('/materiales')) ?>"
            class="sita-search-form"
        >

            <div class="sita-search-field">

                <label for="buscar">
                    Buscar material
                </label>

                <input
                    type="text"
                    id="buscar"
                    name="buscar"
                    value="<?= e($buscar) ?>"
                    placeholder="Código UNSPSC o nombre del producto"
                    autocomplete="off"
                >

            </div>


            <div class="sita-search-actions">

                <button
                    type="submit"
                    class="sita-btn sita-btn-primary"
                >
                    Buscar
                </button>

                <?php if ($buscar !== ''): ?>

                    <a
                        href="<?= e(url('/materiales')) ?>"
                        class="sita-btn sita-btn-secondary"
                    >
                        Limpiar
                    </a>

                <?php endif; ?>

            </div>

        </form>


        <div class="sita-table-toolbar">

            <div class="sita-table-info">

                <?php if ($total > 0): ?>

                    Mostrando
                    <strong><?= $inicio ?></strong>
                    -
                    <strong><?= $fin ?></strong>
                    de
                    <strong><?= $total ?></strong>

                <?php else: ?>

                    No hay materiales para mostrar.

                <?php endif; ?>

            </div>


            <div class="sita-per-page">

                <label for="por_pagina">
                    Mostrar
                </label>

                <select
                    id="por_pagina"
                    onchange="cambiarPorPaginaMateriales(this.value)"
                >

                    <?php foreach ([10, 20, 50, 100] as $cantidad): ?>

                        <option
                            value="<?= $cantidad ?>"
                            <?= $porPagina === $cantidad ? 'selected' : '' ?>
                        >
                            <?= $cantidad ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <span>
                    por página
                </span>

            </div>

        </div>


        <div class="sita-table-wrapper">

            <table class="sita-table">

                <thead>

                    <tr>

                        <th>
                            Código UNSPSC
                        </th>

                        <th>
                            Descripción del bien
                        </th>

                        <th>
                            Vigencia
                        </th>

                        <th>
                            Estado
                        </th>

                        <th class="sita-table-actions-header">
                            Acciones
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (empty($materiales)): ?>

                        <tr>

                            <td
                                colspan="5"
                                class="sita-table-empty"
                            >

                                <div class="sita-empty-state">

                                    <div class="sita-empty-icon">
                                        📦
                                    </div>

                                    <h3>
                                        No hay materiales registrados
                                    </h3>

                                    <p>

                                        <?php if ($buscar !== ''): ?>

                                            No se encontraron materiales
                                            que coincidan con la búsqueda.

                                        <?php else: ?>

                                            Aún no existen materiales
                                            registrados en el catálogo.

                                        <?php endif; ?>

                                    </p>

                                </div>

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($materiales as $material): ?>

                            <?php
                            $idMaterial = (int) (
                                $material['id'] ?? 0
                            );

                            $estadoMaterial = (string) (
                                $material['estado'] ?? ''
                            );

                            $codigoMaterial = (string) (
                                $material['codigo_unspsc'] ?? ''
                            );

                            $descripcionMaterial = (string) (
                                $material['descripcion'] ?? ''
                            );


                            $fechaInicio = (string) (
                                $material[
                                    'fecha_inicio_vigencia'
                                ] ?? ''
                            );

                            $fechaFin = (string) (
                                $material[
                                    'fecha_fin_vigencia'
                                ] ?? ''
                            );
                            ?>

                            <tr>

                                <td>

                                    <strong class="sita-code">
                                        <?= e($codigoMaterial) ?>
                                    </strong>

                                </td>


                                <td>

                                    <div class="sita-material-description">

                                        <?= nl2br(
                                            e(
                                                $descripcionMaterial
                                            )
                                        ) ?>

                                    </div>

                                </td>


                                <td>

                                    <?php if (
                                        $fechaInicio !== ''
                                        || $fechaFin !== ''
                                    ): ?>

                                        <div class="sita-validity">

                                            <?php if ($fechaInicio !== ''): ?>

                                                <span>
                                                    Desde:
                                                    <?= e(
                                                        fecha(
                                                            $fechaInicio
                                                        )
                                                    ) ?>
                                                </span>

                                            <?php endif; ?>


                                            <?php if ($fechaFin !== ''): ?>

                                                <span>
                                                    Hasta:
                                                    <?= e(
                                                        fecha(
                                                            $fechaFin
                                                        )
                                                    ) ?>
                                                </span>

                                            <?php endif; ?>

                                        </div>

                                    <?php else: ?>

                                        <span class="sita-muted">
                                            Sin fecha definida
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php if (
                                        $estadoMaterial === 'activo'
                                    ): ?>

                                        <span class="sita-status sita-status-active">
                                            Activo
                                        </span>

                                    <?php else: ?>

                                        <span class="sita-status sita-status-inactive">
                                            Inactivo
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <div class="sita-row-actions">

                                        


                                        <?php if (
                                            $puedeCambiarEstado
                                        ): ?>

                                            <?php if (
                                                $estadoMaterial === 'activo'
                                            ): ?>

                                                <button
                                                    type="button"
                                                    class="sita-action-btn sita-action-danger"
                                                    title="Inactivar material"
                                                    onclick="abrirEstadoMaterial(
                                                        <?= $idMaterial ?>,
                                                        'inactivo',
                                                        <?= htmlspecialchars(
                                                            json_encode(
                                                                $codigoMaterial,
                                                                JSON_UNESCAPED_UNICODE |
                                                                JSON_HEX_TAG |
                                                                JSON_HEX_APOS |
                                                                JSON_HEX_QUOT |
                                                                JSON_HEX_AMP
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>
                                                    )"
                                                >
                                                    ⏸
                                                </button>

                                            <?php else: ?>

                                                <button
                                                    type="button"
                                                    class="sita-action-btn sita-action-success"
                                                    title="Activar material"
                                                    onclick="abrirEstadoMaterial(
                                                        <?= $idMaterial ?>,
                                                        'activo',
                                                        <?= htmlspecialchars(
                                                            json_encode(
                                                                $codigoMaterial,
                                                                JSON_UNESCAPED_UNICODE |
                                                                JSON_HEX_TAG |
                                                                JSON_HEX_APOS |
                                                                JSON_HEX_QUOT |
                                                                JSON_HEX_AMP
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>
                                                    )"
                                                >
                                                    ▶
                                                </button>

                                            <?php endif; ?>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>


        <?php if ($totalPaginas > 1): ?>

            <div class="sita-pagination">

                <div class="sita-pagination-info">

                    Página
                    <strong>
                        <?= $pagina ?>
                    </strong>
                    de
                    <strong>
                        <?= $totalPaginas ?>
                    </strong>

                </div>


                <div class="sita-pagination-links">

                    <?php
                    $parametrosBase = [
                        'buscar' => $buscar,
                        'por_pagina' => $porPagina
                    ];
                    ?>


                    <?php if ($pagina > 1): ?>

                        <?php
                        $parametrosAnterior =
                            $parametrosBase;

                        $parametrosAnterior['pagina'] =
                            $pagina - 1;
                        ?>

                        <a
                            href="<?= e(
                                url(
                                    '/materiales?'
                                    . http_build_query(
                                        $parametrosAnterior
                                    )
                                )
                            ) ?>"
                            class="sita-pagination-link"
                        >
                            ‹ Anterior
                        </a>

                    <?php endif; ?>


                    <?php

                    $rangoInicio = max(
                        1,
                        $pagina - 2
                    );

                    $rangoFin = min(
                        $totalPaginas,
                        $pagina + 2
                    );

                    for (
                        $numero = $rangoInicio;
                        $numero <= $rangoFin;
                        $numero++
                    ):
                    ?>

                        <?php
                        $parametrosPagina =
                            $parametrosBase;

                        $parametrosPagina['pagina'] =
                            $numero;
                        ?>

                        <a
                            href="<?= e(
                                url(
                                    '/materiales?'
                                    . http_build_query(
                                        $parametrosPagina
                                    )
                                )
                            ) ?>"
                            class="sita-pagination-link<?= $numero === $pagina
                                ? ' sita-pagination-link-active'
                                : '' ?>"
                        >
                            <?= $numero ?>
                        </a>

                    <?php endfor; ?>


                    <?php if ($pagina < $totalPaginas): ?>

                        <?php
                        $parametrosSiguiente =
                            $parametrosBase;

                        $parametrosSiguiente['pagina'] =
                            $pagina + 1;
                        ?>

                        <a
                            href="<?= e(
                                url(
                                    '/materiales?'
                                    . http_build_query(
                                        $parametrosSiguiente
                                    )
                                )
                            ) ?>"
                            class="sita-pagination-link"
                        >
                            Siguiente ›
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>

    </section>

</main>


<?php if ($puedeCambiarEstado): ?>

    <!-- =========================================================
         MODAL CAMBIO DE ESTADO
         ========================================================= -->

    <div
        id="modalEstadoMaterial"
        class="sita-modal"
        aria-hidden="true"
    >

        <div class="sita-modal-overlay"></div>

        <div
            class="sita-modal-dialog sita-modal-dialog-small"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modalEstadoMaterialTitulo"
        >

            <div class="sita-modal-header">

                <div>

                    <h2 id="modalEstadoMaterialTitulo">
                        Cambiar estado
                    </h2>

                    <p id="modalEstadoMaterialTexto">
                        Confirme el cambio de estado del material.
                    </p>

                </div>

                <button
                    type="button"
                    class="sita-modal-close"
                    onclick="cerrarModalMaterial('modalEstadoMaterial')"
                    aria-label="Cerrar"
                >
                    ×
                </button>

            </div>


            <form
                method="post"
                action="<?= e(
                    url('/materiales/estado')
                ) ?>"
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
                    id="estado_valor"
                    value=""
                >


                <div class="sita-modal-body">

                    <div class="sita-confirmation">

                        <div
                            class="sita-confirmation-icon"
                            id="estado_icono"
                        >
                            !
                        </div>

                        <p id="estado_descripcion">
                            ¿Desea cambiar el estado de este material?
                        </p>

                    </div>

                </div>


                <div class="sita-modal-footer">

                    <button
                        type="button"
                        class="sita-btn sita-btn-secondary"
                        onclick="cerrarModalMaterial('modalEstadoMaterial')"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="sita-btn sita-btn-primary"
                        id="estado_boton"
                    >
                        Confirmar
                    </button>

                </div>

            </form>

        </div>

    </div>

<?php endif; ?>


<style>

    .sita-page {
        background: #f5f7fa;
        min-height: calc(100vh - 80px);
        padding: 30px;
    }


    .sita-page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 24px;
    }


    .sita-page-header h1 {
        margin: 0 0 6px;
        color: #172b4d;
        font-size: 28px;
        font-weight: 700;
    }


    .sita-page-header p {
        margin: 0;
        color: #6b778c;
        font-size: 14px;
    }


    .sita-panel {
        background: #ffffff;
        border: 1px solid #e6eaf0;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(23, 43, 77, 0.05);
        overflow: hidden;
    }


    .sita-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 22px 24px;
        border-bottom: 1px solid #edf0f4;
    }


    .sita-panel-header h2 {
        margin: 0 0 5px;
        color: #172b4d;
        font-size: 18px;
        font-weight: 700;
    }


    .sita-panel-header p {
        margin: 0;
        color: #7a869a;
        font-size: 13px;
    }


    .sita-search-form {
        display: flex;
        align-items: flex-end;
        gap: 14px;
        padding: 20px 24px;
        background: #fafbfc;
        border-bottom: 1px solid #edf0f4;
    }


    .sita-search-field {
        flex: 1;
    }


    .sita-search-field label,
    .sita-form-group label {
        display: block;
        margin-bottom: 7px;
        color: #344563;
        font-size: 13px;
        font-weight: 600;
    }


    .sita-search-field input,
    .sita-form-group input,
    .sita-form-group textarea,
    .sita-form-group select,
    .sita-per-page select {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid #d8dee8;
        border-radius: 8px;
        background: #ffffff;
        color: #172b4d;
        font-family: inherit;
        font-size: 14px;
        outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }


    .sita-search-field input,
    .sita-form-group input,
    .sita-form-group select,
    .sita-per-page select {
        min-height: 42px;
        padding: 9px 12px;
    }


    .sita-form-group textarea {
        min-height: 120px;
        padding: 11px 12px;
        resize: vertical;
    }


    .sita-search-field input:focus,
    .sita-form-group input:focus,
    .sita-form-group textarea:focus,
    .sita-form-group select:focus,
    .sita-per-page select:focus {
        border-color: #00a6a6;
        box-shadow: 0 0 0 3px rgba(0, 166, 166, 0.10);
    }


    .sita-search-actions {
        display: flex;
        gap: 8px;
    }


    .sita-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 40px;
        padding: 9px 16px;
        border: 1px solid transparent;
        border-radius: 8px;
        font-family: inherit;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all .15s ease;
        box-sizing: border-box;
    }


    .sita-btn-primary {
        background: #0056b3;
        border-color: #0056b3;
        color: #ffffff;
    }


    .sita-btn-primary:hover {
        background: #004494;
        border-color: #004494;
    }


    .sita-btn-secondary {
        background: #ffffff;
        border-color: #d8dee8;
        color: #344563;
    }


    .sita-btn-secondary:hover {
        background: #f5f7fa;
    }


    .sita-table-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 15px 24px;
        border-bottom: 1px solid #edf0f4;
    }


    .sita-table-info {
        color: #6b778c;
        font-size: 13px;
    }


    .sita-table-info strong {
        color: #172b4d;
    }


    .sita-per-page {
        display: flex;
        align-items: center;
        gap: 7px;
        color: #6b778c;
        font-size: 13px;
    }


    .sita-per-page select {
        width: auto;
        min-height: 36px;
        padding: 6px 30px 6px 9px;
    }


    .sita-table-wrapper {
        width: 100%;
        overflow-x: auto;
    }


    .sita-table {
        width: 100%;
        min-width: 1050px;
        border-collapse: collapse;
    }


    .sita-table th {
        padding: 13px 18px;
        background: #f8fafc;
        border-bottom: 1px solid #e5e9ef;
        color: #6b778c;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .04em;
        text-align: left;
        text-transform: uppercase;
        white-space: nowrap;
    }


    .sita-table td {
        padding: 15px 18px;
        border-bottom: 1px solid #edf0f4;
        color: #344563;
        font-size: 13px;
        vertical-align: middle;
    }


    .sita-table tbody tr:hover {
        background: #fbfcfe;
    }


    .sita-table tbody tr:last-child td {
        border-bottom: none;
    }


    .sita-table-actions-header {
        text-align: right !important;
    }


    .sita-code {
        color: #172b4d;
        font-size: 13px;
        font-weight: 700;
        white-space: nowrap;
    }


    .sita-material-description {
        max-width: 540px;
        line-height: 1.5;
    }


    .sita-unit-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 9px;
        border-radius: 6px;
        background: #f1f5f9;
        color: #475569;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }


    .sita-validity {
        display: flex;
        flex-direction: column;
        gap: 4px;
        color: #64748b;
        font-size: 11px;
        line-height: 1.4;
        white-space: nowrap;
    }


    .sita-muted {
        color: #94a3b8;
        font-size: 12px;
    }


    .sita-status {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
    }


    .sita-status-active {
        background: #e8f7ef;
        color: #16804b;
    }


    .sita-status-inactive {
        background: #f3f4f6;
        color: #6b7280;
    }


    .sita-row-actions {
        display: flex;
        justify-content: flex-end;
        gap: 6px;
    }


    .sita-action-btn {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #dce2ea;
        border-radius: 7px;
        background: #ffffff;
        color: #475569;
        cursor: pointer;
        font-size: 14px;
        transition: all .15s ease;
    }


    .sita-action-btn:hover {
        background: #f8fafc;
    }


    .sita-action-edit:hover {
        border-color: #0056b3;
        color: #0056b3;
    }


    .sita-action-danger:hover {
        border-color: #dc3545;
        color: #dc3545;
    }


    .sita-action-success:hover {
        border-color: #198754;
        color: #198754;
    }


    .sita-table-empty {
        padding: 50px 20px !important;
        text-align: center !important;
    }


    .sita-empty-state {
        padding: 20px;
    }


    .sita-empty-icon {
        margin-bottom: 12px;
        font-size: 34px;
    }


    .sita-empty-state h3 {
        margin: 0 0 7px;
        color: #344563;
        font-size: 16px;
    }


    .sita-empty-state p {
        margin: 0;
        color: #7a869a;
        font-size: 13px;
    }


    .sita-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        padding: 18px 24px;
        border-top: 1px solid #edf0f4;
    }


    .sita-pagination-info {
        color: #6b778c;
        font-size: 13px;
    }


    .sita-pagination-info strong {
        color: #172b4d;
    }


    .sita-pagination-links {
        display: flex;
        align-items: center;
        gap: 5px;
    }


    .sita-pagination-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        height: 34px;
        padding: 0 9px;
        border: 1px solid #dce2ea;
        border-radius: 7px;
        background: #ffffff;
        color: #475569;
        font-size: 12px;
        text-decoration: none;
    }


    .sita-pagination-link:hover {
        background: #f5f7fa;
    }


    .sita-pagination-link-active {
        background: #0056b3;
        border-color: #0056b3;
        color: #ffffff;
    }


    .sita-alert {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
        padding: 13px 16px;
        border-radius: 9px;
        font-size: 13px;
    }


    .sita-alert-success {
        background: #eaf8f0;
        border: 1px solid #ccebd9;
        color: #176b43;
    }


    .sita-alert-error {
        background: #fff1f2;
        border: 1px solid #fecdd3;
        color: #a61b2b;
    }


    .sita-alert-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 23px;
        height: 23px;
        border-radius: 50%;
        background: rgba(0, 0, 0, .05);
        font-weight: 700;
        flex-shrink: 0;
    }


    .sita-modal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }


    .sita-modal.sita-modal-open {
        display: flex;
    }


    .sita-modal-overlay {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, .55);
        backdrop-filter: blur(2px);
    }


    .sita-modal-dialog {
        position: relative;
        z-index: 1;
        width: min(760px, 100%);
        max-height: calc(100vh - 40px);
        overflow-y: auto;
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 25px 60px rgba(15, 23, 42, .25);
    }


    .sita-modal-dialog-small {
        width: min(480px, 100%);
    }


    .sita-modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
        padding: 22px 24px;
        border-bottom: 1px solid #edf0f4;
    }


    .sita-modal-header h2 {
        margin: 0 0 5px;
        color: #172b4d;
        font-size: 19px;
    }


    .sita-modal-header p {
        margin: 0;
        color: #7a869a;
        font-size: 12px;
        line-height: 1.5;
    }


    .sita-modal-close {
        width: 34px;
        height: 34px;
        border: 0;
        border-radius: 7px;
        background: #f5f7fa;
        color: #64748b;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
        flex-shrink: 0;
    }


    .sita-modal-close:hover {
        background: #e9edf2;
    }


    .sita-modal-body {
        padding: 24px;
    }


    .sita-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }


    .sita-form-group-full {
        grid-column: 1 / -1;
    }


    .sita-form-group label span {
        color: #dc3545;
    }


    .sita-form-group small {
        display: block;
        margin-top: 6px;
        color: #94a3b8;
        font-size: 11px;
    }


    .sita-modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 9px;
        padding: 16px 24px;
        border-top: 1px solid #edf0f4;
        background: #fafbfc;
    }


    .sita-confirmation {
        text-align: center;
        padding: 12px 10px;
    }


    .sita-confirmation-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 52px;
        height: 52px;
        margin: 0 auto 15px;
        border-radius: 50%;
        background: #fff4e5;
        color: #b26a00;
        font-size: 22px;
        font-weight: 700;
    }


    .sita-confirmation p {
        margin: 0;
        color: #475569;
        font-size: 14px;
        line-height: 1.6;
    }


    @media (max-width: 900px) {

        .sita-page {
            padding: 20px;
        }


        .sita-search-form {
            flex-direction: column;
            align-items: stretch;
        }


        .sita-search-actions {
            width: 100%;
        }


        .sita-search-actions .sita-btn {
            flex: 1;
        }


        .sita-page-header {
            flex-direction: column;
        }


        .sita-page-header > .sita-btn {
            width: 100%;
        }

    }


    @media (max-width: 700px) {

        .sita-table-toolbar {
            align-items: flex-start;
            flex-direction: column;
        }


        .sita-pagination {
            align-items: flex-start;
            flex-direction: column;
        }


        .sita-pagination-links {
            flex-wrap: wrap;
        }


        .sita-form-grid {
            grid-template-columns: 1fr;
        }


        .sita-form-group-full {
            grid-column: auto;
        }

    }


    @media (max-width: 520px) {

        .sita-page {
            padding: 15px;
        }


        .sita-panel-header,
        .sita-search-form,
        .sita-table-toolbar,
        .sita-pagination {
            padding-left: 16px;
            padding-right: 16px;
        }


        .sita-modal {
            padding: 10px;
        }


        .sita-modal-body {
            padding: 18px;
        }


        .sita-modal-header,
        .sita-modal-footer {
            padding-left: 18px;
            padding-right: 18px;
        }

    }

</style>


<script>

    function abrirModalMaterial(id) {

        const modal = document.getElementById(id);

        if (!modal) {
            return;
        }

        modal.classList.add(
            'sita-modal-open'
        );

        modal.setAttribute(
            'aria-hidden',
            'false'
        );

        document.body.style.overflow = 'hidden';

        const primerCampo =
            modal.querySelector(
                'input:not([type="hidden"]), textarea, select'
            );

        if (primerCampo) {
            setTimeout(
                function () {
                    primerCampo.focus();
                },
                100
            );
        }
    }


    function cerrarModalMaterial(id) {

        const modal = document.getElementById(id);

        if (!modal) {
            return;
        }

        modal.classList.remove(
            'sita-modal-open'
        );

        modal.setAttribute(
            'aria-hidden',
            'true'
        );

        const existeModalAbierto =
            document.querySelector(
                '.sita-modal.sita-modal-open'
            );

        if (!existeModalAbierto) {
            document.body.style.overflow = '';
        }
    }


    function abrirEstadoMaterial(
        id,
        estado,
        codigo
    ) {

        const campoId =
            document.getElementById(
                'estado_id'
            );

        const campoEstado =
            document.getElementById(
                'estado_valor'
            );

        const texto =
            document.getElementById(
                'modalEstadoMaterialTexto'
            );

        const descripcion =
            document.getElementById(
                'estado_descripcion'
            );

        const icono =
            document.getElementById(
                'estado_icono'
            );

        const boton =
            document.getElementById(
                'estado_boton'
            );

        if (
            !campoId
            || !campoEstado
            || !texto
            || !descripcion
            || !icono
            || !boton
        ) {
            return;
        }

        campoId.value = id;
        campoEstado.value = estado;

        if (estado === 'activo') {

            texto.textContent =
                'Activar material';

            descripcion.textContent =
                '¿Desea activar nuevamente el material con código UNSPSC '
                + codigo
                + '?';

            icono.textContent = '✓';

            boton.textContent =
                'Activar material';

        } else {

            texto.textContent =
                'Inactivar material';

            descripcion.textContent =
                '¿Desea inactivar el material con código UNSPSC '
                + codigo
                + '?';

            icono.textContent = '!';

            boton.textContent =
                'Inactivar material';
        }

        abrirModalMaterial(
            'modalEstadoMaterial'
        );
    }


    function cambiarPorPaginaMateriales(
        cantidad
    ) {

        const parametros =
            new URLSearchParams(
                window.location.search
            );

        parametros.set(
            'por_pagina',
            cantidad
        );

        parametros.set(
            'pagina',
            '1'
        );

        window.location.href =
            '<?= e(url('/materiales')) ?>'
            + '?'
            + parametros.toString();
    }


    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key !== 'Escape') {
                return;
            }

            const modal =
                document.querySelector(
                    '.sita-modal.sita-modal-open'
                );

            if (modal) {
                cerrarModalMaterial(
                    modal.id
                );
            }

        }
    );


    document.addEventListener(
        'click',
        function (event) {

            const overlay =
                event.target.closest(
                    '.sita-modal-overlay'
                );

            if (!overlay) {
                return;
            }

            const modal =
                overlay.closest(
                    '.sita-modal'
                );

            if (modal) {
                cerrarModalMaterial(
                    modal.id
                );
            }

        }
    );

</script>


<?php require ROOT_PATH . '/views/layout/footer.php'; ?>

