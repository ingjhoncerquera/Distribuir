<?php
declare(strict_types=1);

require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';

$buscar = (string) ($buscar ?? '');
$pagina = (int) ($pagina ?? 1);
$porPagina = (int) ($porPagina ?? 20);
$total = (int) ($total ?? 0);
$totalPaginas = (int) ($totalPaginas ?? 1);
$aprendices = $aprendices ?? [];

?>

<style>
    .sita-page {
        padding: 30px;
        background: #f5f7fa;
        min-height: calc(100vh - 140px);
    }

    .sita-page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 25px;
    }

    .sita-page-title {
        margin: 0;
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
    }

    .sita-page-subtitle {
        margin: 6px 0 0;
        color: #6b7280;
        font-size: 14px;
    }

    .sita-panel {
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    .sita-panel-header {
        padding: 20px 22px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .sita-panel-title {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .sita-panel-title strong {
        font-size: 15px;
        color: #1f2937;
    }

    .sita-resultados {
        font-size: 13px;
        color: #6b7280;
    }

    /*
     * BUSCADOR
     */

    .sita-search-form {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        max-width: 560px;
    }

    .sita-search {
        position: relative;
        flex: 1;
    }

    .sita-search input {
        width: 100%;
        height: 44px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        padding: 0 15px;
        font-size: 14px;
        color: #374151;
        background: #ffffff;
        outline: none;
        transition: all 0.2s ease;
        box-sizing: border-box;
    }

    .sita-search input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
    }

    .sita-search input::placeholder {
        color: #9ca3af;
    }

    .sita-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        height: 42px;
        padding: 0 16px;
        border-radius: 9px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .sita-btn-primary {
        background: #2563eb;
        color: #ffffff;
    }

    .sita-btn-primary:hover {
        background: #1d4ed8;
        color: #ffffff;
    }

    .sita-btn-secondary {
        background: #f3f4f6;
        color: #374151;
    }

    .sita-btn-secondary:hover {
        background: #e5e7eb;
        color: #111827;
    }

    .sita-table-wrapper {
        width: 100%;
        overflow-x: auto;
    }

    .sita-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
    }

    .sita-table thead th {
        background: #f9fafb;
        color: #4b5563;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 14px 18px;
        border-bottom: 1px solid #e5e7eb;
        text-align: left;
        white-space: nowrap;
    }

    .sita-table tbody td {
        padding: 16px 18px;
        border-bottom: 1px solid #f0f2f5;
        color: #374151;
        font-size: 14px;
        vertical-align: middle;
    }

    .sita-table tbody tr {
        transition: background 0.15s ease;
    }

    .sita-table tbody tr:hover {
        background: #f9fafb;
    }

    .sita-table tbody tr:last-child td {
        border-bottom: none;
    }

    .sita-documento {
        font-weight: 600;
        color: #111827;
    }

    .sita-aprendiz {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .sita-aprendiz-nombre {
        font-weight: 600;
        color: #1f2937;
    }

    .sita-aprendiz-correo {
        font-size: 12px;
        color: #9ca3af;
    }

    .sita-fichas {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .sita-ficha {
        display: inline-flex;
        align-items: center;
        background: #eef2ff;
        color: #3730a3;
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 12px;
        font-weight: 600;
    }

    .sita-sin-dato {
        color: #9ca3af;
        font-size: 13px;
    }

    /*
     * ESTADOS
     */

    .sita-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 28px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
        line-height: 1.2;
    }

    .estado-formacion {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .estado-cancelado {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .estado-retiro {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }

    .estado-aplazado {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }

    .estado-default {
        background: #e5e7eb;
        color: #374151;
        border: 1px solid #d1d5db;
    }

    /*
     * ACCIONES
     */

    .sita-actions {
        display: flex;
        align-items: center;
        gap: 7px;
        flex-wrap: nowrap;
    }

    .sita-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: #eff6ff;
        color: #2563eb;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .sita-action:hover {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .sita-action-caracterizacion {
        background: #f0fdf4;
        color: #15803d;
    }

    .sita-action-caracterizacion:hover {
        background: #dcfce7;
        color: #166534;
    }

    /*
     * VACÍO
     */

    .sita-empty {
        padding: 55px 20px;
        text-align: center;
        color: #6b7280;
    }

    .sita-empty-icon {
        font-size: 38px;
        margin-bottom: 12px;
    }

    .sita-empty-title {
        margin: 0 0 6px;
        font-size: 17px;
        font-weight: 700;
        color: #374151;
    }

    .sita-empty-text {
        margin: 0;
        font-size: 14px;
        color: #9ca3af;
    }

    /*
     * PAGINACIÓN
     */

    .sita-pagination-wrapper {
        padding: 18px 22px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .sita-pagination-info {
        font-size: 13px;
        color: #6b7280;
    }

    .sita-pagination {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .sita-pagination a,
    .sita-pagination span {
        min-width: 34px;
        height: 34px;
        padding: 0 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 7px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        box-sizing: border-box;
    }

    .sita-pagination a {
        background: #f3f4f6;
        color: #374151;
    }

    .sita-pagination a:hover {
        background: #e5e7eb;
    }

    .sita-pagination .active {
        background: #2563eb;
        color: #ffffff;
    }

    .sita-pagination .disabled {
        background: #f9fafb;
        color: #d1d5db;
        cursor: not-allowed;
    }

    /*
     * RESPONSIVE
     */

    @media (max-width: 900px) {

        .sita-panel-header {
            align-items: stretch;
            flex-direction: column;
        }

        .sita-search-form {
            max-width: none;
        }
    }

    @media (max-width: 768px) {

        .sita-page {
            padding: 20px 15px;
        }

        .sita-page-header {
            flex-direction: column;
        }

        .sita-page-title {
            font-size: 24px;
        }

        .sita-search-form {
            flex-direction: column;
            align-items: stretch;
        }

        .sita-btn {
            width: 100%;
        }

        .sita-pagination-wrapper {
            flex-direction: column;
            align-items: stretch;
        }

        .sita-pagination {
            justify-content: center;
        }
    }
</style>


<div class="sita-page">

    <!-- ENCABEZADO -->

    <div class="sita-page-header">

        <div>

            <h1 class="sita-page-title">
                Aprendices
            </h1>

            <p class="sita-page-subtitle">
                Consulta y seguimiento de los aprendices registrados en el sistema.
            </p>

        </div>

    </div>


    <!-- PANEL PRINCIPAL -->

    <div class="sita-panel">

        <!-- CABECERA DEL LISTADO -->

        <div class="sita-panel-header">

            <div class="sita-panel-title">

                <strong>
                    Listado de aprendices
                </strong>

                <div class="sita-resultados">

                    <?= number_format($total, 0, ',', '.') ?>

                    aprendic<?= $total === 1 ? '' : 'es' ?>

                    <?php if ($buscar !== ''): ?>

                        encontrados para:

                        <strong>
                            <?= e($buscar) ?>
                        </strong>

                    <?php endif; ?>

                </div>

            </div>


            <!-- BUSCADOR -->

            <form
                method="GET"
                action="<?= e(url('/aprendices')) ?>"
                class="sita-search-form"
            >

                <div class="sita-search">

                    <input
                        type="text"
                        name="buscar"
                        value="<?= e($buscar) ?>"
                        placeholder="Buscar por documento, nombres o apellidos..."
                        autocomplete="off"
                    >

                </div>


                <button
                    type="submit"
                    class="sita-btn sita-btn-primary"
                >
                    🔎 Buscar
                </button>


                <?php if ($buscar !== ''): ?>

                    <a
                        href="<?= e(url('/aprendices')) ?>"
                        class="sita-btn sita-btn-secondary"
                    >
                        Limpiar
                    </a>

                <?php endif; ?>

            </form>

        </div>


        <!-- RESULTADOS -->

        <?php if (empty($aprendices)): ?>

            <div class="sita-empty">

                <div class="sita-empty-icon">
                    🔎
                </div>

                <h3 class="sita-empty-title">
                    No se encontraron aprendices
                </h3>

                <p class="sita-empty-text">

                    <?php if ($buscar !== ''): ?>

                        No hay resultados para
                        "<strong><?= e($buscar) ?></strong>".

                    <?php else: ?>

                        No hay aprendices registrados actualmente.

                    <?php endif; ?>

                </p>

            </div>

        <?php else: ?>

            <div class="sita-table-wrapper">

                <table class="sita-table">

                    <thead>

                        <tr>

                            <th>
                                Documento
                            </th>

                            <th>
                                Aprendiz
                            </th>

                            <th>
                                Fichas
                            </th>

                            <th>
                                Estado
                            </th>

                            <th>
                                Acción
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($aprendices as $aprendiz): ?>

                            <?php

                            $estado = strtoupper(
                                trim(
                                    (string) (
                                        $aprendiz['estado_aprendiz'] ?? ''
                                    )
                                )
                            );

                            $claseEstado = match ($estado) {

                                'EN FORMACION' =>
                                    'sita-badge estado-formacion',

                                'CANCELADO' =>
                                    'sita-badge estado-cancelado',

                                'RETIRO VOLUNTARIO' =>
                                    'sita-badge estado-retiro',

                                'APLAZADO' =>
                                    'sita-badge estado-aplazado',

                                default =>
                                    'sita-badge estado-default',
                            };

                            $nombreCompleto = trim(
                                (string) (
                                    ($aprendiz['nombres'] ?? '') .
                                    ' ' .
                                    ($aprendiz['apellidos'] ?? '')
                                )
                            );

                            ?>

                            <tr>

                                <!-- DOCUMENTO -->

                                <td>

                                    <span class="sita-documento">

                                        <?= e(
                                            (string) (
                                                $aprendiz['tipo_documento'] ?? ''
                                            )
                                        ) ?>

                                        <?= e(
                                            (string) (
                                                $aprendiz['documento'] ?? ''
                                            )
                                        ) ?>

                                    </span>

                                </td>


                                <!-- APRENDIZ -->

                                <td>

                                    <div class="sita-aprendiz">

                                        <span class="sita-aprendiz-nombre">

                                            <?= e(
                                                $nombreCompleto
                                            ) ?>

                                        </span>


                                        <?php if (!empty($aprendiz['correo'])): ?>

                                            <span class="sita-aprendiz-correo">

                                                <?= e(
                                                    (string) $aprendiz['correo']
                                                ) ?>

                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </td>


                                <!-- FICHAS -->

                                <td>

                                    <?php if (!empty($aprendiz['fichas'])): ?>

                                        <div class="sita-fichas">

                                            <?php

                                            $fichas = array_filter(
                                                array_map(
                                                    'trim',
                                                    explode(
                                                        ',',
                                                        (string) $aprendiz['fichas']
                                                    )
                                                )
                                            );

                                            ?>

                                            <?php foreach ($fichas as $ficha): ?>

                                                <span class="sita-ficha">

                                                    <?= e($ficha) ?>

                                                </span>

                                            <?php endforeach; ?>

                                        </div>

                                    <?php else: ?>

                                        <span class="sita-sin-dato">
                                            Sin ficha
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ESTADO -->

                                <td>

                                    <span class="<?= e($claseEstado) ?>">

                                        <?= e(
                                            $estado !== ''
                                                ? $estado
                                                : 'SIN ESTADO'
                                        ) ?>

                                    </span>

                                </td>


                                <!-- ACCIONES -->

                                <td>

                                    <div class="sita-actions">

                                        <!-- VER DETALLE -->

                                        <a
                                            href="<?= e(
                                                url(
                                                    '/aprendices/detalle?id=' .
                                                    (int) $aprendiz['id']
                                                )
                                            ) ?>"
                                            class="sita-action"
                                            title="Ver detalle"
                                            aria-label="Ver detalle"
                                        >
                                            👁
                                        </a>


                                        <!-- CARACTERIZACIÓN -->

                                        <a
                                            href="<?= e(
                                                url(
                                                    '/aprendices/caracterizacion?id=' .
                                                    (int) $aprendiz['id']
                                                )
                                            ) ?>"
                                            class="sita-action sita-action-caracterizacion"
                                            title="Actualizar caracterización"
                                            aria-label="Actualizar caracterización"
                                        >
                                            ✏️
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <!-- PAGINACIÓN -->

            <?php if ($totalPaginas > 1): ?>

                <div class="sita-pagination-wrapper">

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


                    <div class="sita-pagination">

                        <!-- ANTERIOR -->

                        <?php if ($pagina > 1): ?>

                            <a
                                href="<?= e(
                                    url(
                                        '/aprendices?pagina=' .
                                        ($pagina - 1) .
                                        (
                                            $buscar !== ''
                                                ? '&buscar=' .
                                                urlencode($buscar)
                                                : ''
                                        )
                                    )
                                ) ?>"
                                title="Página anterior"
                            >
                                ‹
                            </a>

                        <?php else: ?>

                            <span class="disabled">
                                ‹
                            </span>

                        <?php endif; ?>


                        <!-- NÚMEROS -->

                        <?php

                        $inicio = max(
                            1,
                            $pagina - 2
                        );

                        $fin = min(
                            $totalPaginas,
                            $pagina + 2
                        );

                        for (
                            $i = $inicio;
                            $i <= $fin;
                            $i++
                        ):

                        ?>

                            <?php if ($i === $pagina): ?>

                                <span class="active">
                                    <?= $i ?>
                                </span>

                            <?php else: ?>

                                <a
                                    href="<?= e(
                                        url(
                                            '/aprendices?pagina=' .
                                            $i .
                                            (
                                                $buscar !== ''
                                                    ? '&buscar=' .
                                                    urlencode($buscar)
                                                    : ''
                                            )
                                        )
                                    ) ?>"
                                >
                                    <?= $i ?>
                                </a>

                            <?php endif; ?>

                        <?php endfor; ?>


                        <!-- SIGUIENTE -->

                        <?php if ($pagina < $totalPaginas): ?>

                            <a
                                href="<?= e(
                                    url(
                                        '/aprendices?pagina=' .
                                        ($pagina + 1) .
                                        (
                                            $buscar !== ''
                                                ? '&buscar=' .
                                                urlencode($buscar)
                                                : ''
                                        )
                                    )
                                ) ?>"
                                title="Página siguiente"
                            >
                                ›
                            </a>

                        <?php else: ?>

                            <span class="disabled">
                                ›
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endif; ?>

        <?php endif; ?>

    </div>

</div>


<?php

require_once ROOT_PATH . '/views/layout/footer.php';

?>
