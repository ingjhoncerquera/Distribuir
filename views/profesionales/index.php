<?php

declare(strict_types=1);

require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';

$buscar = (string) ($buscar ?? '');
$pagina = (int) ($pagina ?? 1);
$porPagina = (int) ($porPagina ?? 20);
$total = (int) ($total ?? 0);
$totalPaginas = (int) ($totalPaginas ?? 1);
$profesionales = $profesionales ?? [];

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

    .sita-page-header-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex-shrink: 0;
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

    /*
     * TABLA
     */

    .sita-table-wrapper {
        width: 100%;
        overflow-x: auto;
    }

    .sita-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1050px;
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

    /*
     * DOCUMENTO
     */

    .sita-documento {
        font-weight: 600;
        color: #111827;
    }

    /*
     * PROFESIONAL
     */

    .sita-profesional {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .sita-profesional-nombre {
        font-weight: 600;
        color: #1f2937;
    }

    .sita-profesional-correo {
        font-size: 12px;
        color: #9ca3af;
    }

    /*
     * TIPO PROFESIONAL
     */

    .sita-tipo-profesional {
        display: inline-flex;
        align-items: center;
        background: #eef2ff;
        color: #3730a3;
        border-radius: 6px;
        padding: 5px 9px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    /*
     * ROLES
     */

    .sita-roles {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .sita-rol {
        display: inline-flex;
        align-items: center;
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #e5e7eb;
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

    .estado-activo {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .estado-inactivo {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .estado-bloqueado {
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
     * USUARIO
     */

    .sita-usuario {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .sita-usuario-nombre {
        font-weight: 600;
        color: #374151;
    }

    .sita-usuario-estado {
        font-size: 12px;
        color: #9ca3af;
    }

    /*
     * ACCIONES
     */

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

        .sita-page-header-actions {
            width: 100%;
        }

        .sita-page-header-actions .sita-btn {
            width: 100%;
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
                Profesionales
            </h1>

            <p class="sita-page-subtitle">
                Consulta y gestión de los profesionales registrados en el sistema.
            </p>

        </div>

        <?php if (Auth::can('profesionales.crear')): ?>

            <div class="sita-page-header-actions">

                <a
                    href="<?= e(url('/profesionales/crear')) ?>"
                    class="sita-btn sita-btn-primary"
                >
                    ＋ Nuevo profesional
                </a>

            </div>

        <?php endif; ?>

    </div>


    <!-- PANEL PRINCIPAL -->

    <div class="sita-panel">

        <!-- CABECERA DEL LISTADO -->

        <div class="sita-panel-header">

            <div class="sita-panel-title">

                <strong>
                    Listado de profesionales
                </strong>

                <div class="sita-resultados">

                    <?= number_format($total, 0, ',', '.') ?>

                    profesional<?= $total === 1 ? '' : 'es' ?>

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
                action="<?= e(url('/profesionales')) ?>"
                class="sita-search-form"
            >

                <div class="sita-search">

                    <input
                        type="text"
                        name="buscar"
                        value="<?= e($buscar) ?>"
                        placeholder="Buscar por documento, nombres, apellidos, tipo o usuario..."
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
                        href="<?= e(url('/profesionales')) ?>"
                        class="sita-btn sita-btn-secondary"
                    >
                        Limpiar
                    </a>

                <?php endif; ?>

            </form>

        </div>


        <!-- RESULTADOS -->

        <?php if (empty($profesionales)): ?>

            <div class="sita-empty">

                <div class="sita-empty-icon">
                    👤
                </div>

                <h3 class="sita-empty-title">
                    No se encontraron profesionales
                </h3>

                <p class="sita-empty-text">

                    <?php if ($buscar !== ''): ?>

                        No hay resultados para
                        "<strong><?= e($buscar) ?></strong>".

                    <?php else: ?>

                        No hay profesionales registrados actualmente.

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
                                Profesional
                            </th>

                            <th>
                                Tipo
                            </th>

                            <th>
                                Usuario
                            </th>

                            <th>
                                Roles
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

                        <?php foreach ($profesionales as $profesional): ?>

                            <?php

                            $estadoProfesional = strtolower(
                                trim(
                                    (string) (
                                        $profesional['estado_profesional']
                                        ?? ''
                                    )
                                )
                            );

                            $claseEstado = match ($estadoProfesional) {

                                'activo' =>
                                    'sita-badge estado-activo',

                                'inactivo' =>
                                    'sita-badge estado-inactivo',

                                default =>
                                    'sita-badge estado-default',
                            };

                            $nombreCompleto = trim(
                                (string) (
                                    ($profesional['nombres'] ?? '') .
                                    ' ' .
                                    ($profesional['apellidos'] ?? '')
                                )
                            );

                            $estadoUsuario = strtolower(
                                trim(
                                    (string) (
                                        $profesional['estado_usuario']
                                        ?? ''
                                    )
                                )
                            );

                            ?>

                            <tr>

                                <!-- DOCUMENTO -->

                                <td>

                                    <span class="sita-documento">

                                        <?= e(
                                            (string) (
                                                $profesional['tipo_documento']
                                                ?? ''
                                            )
                                        ) ?>

                                        <?= e(
                                            (string) (
                                                $profesional['documento']
                                                ?? ''
                                            )
                                        ) ?>

                                    </span>

                                </td>


                                <!-- PROFESIONAL -->

                                <td>

                                    <div class="sita-profesional">

                                        <span class="sita-profesional-nombre">

                                            <?= e(
                                                $nombreCompleto
                                            ) ?>

                                        </span>


                                        <?php if (!empty($profesional['correo'])): ?>

                                            <span class="sita-profesional-correo">

                                                <?= e(
                                                    (string) $profesional['correo']
                                                ) ?>

                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </td>


                                <!-- TIPO -->

                                <td>

                                    <?php if (!empty($profesional['tipo_profesional'])): ?>

                                        <span class="sita-tipo-profesional">

                                            <?= e(
                                                (string) $profesional['tipo_profesional']
                                            ) ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="sita-sin-dato">
                                            Sin tipo
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- USUARIO -->

                                <td>

                                    <?php if (!empty($profesional['username'])): ?>

                                        <div class="sita-usuario">

                                            <span class="sita-usuario-nombre">

                                                <?= e(
                                                    (string) $profesional['username']
                                                ) ?>

                                            </span>

                                            <?php if ($estadoUsuario !== ''): ?>

                                                <span class="sita-usuario-estado">

                                                    <?= e(
                                                        strtoupper(
                                                            $estadoUsuario
                                                        )
                                                    ) ?>

                                                </span>

                                            <?php endif; ?>

                                        </div>

                                    <?php else: ?>

                                        <span class="sita-sin-dato">
                                            Sin usuario
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ROLES -->

                                <td>

                                    <?php if (!empty($profesional['roles'])): ?>

                                        <div class="sita-roles">

                                            <?php

                                            $roles = array_filter(
                                                array_map(
                                                    'trim',
                                                    explode(
                                                        ',',
                                                        (string) $profesional['roles']
                                                    )
                                                )
                                            );

                                            ?>

                                            <?php foreach ($roles as $rol): ?>

                                                <span class="sita-rol">

                                                    <?= e($rol) ?>

                                                </span>

                                            <?php endforeach; ?>

                                        </div>

                                    <?php else: ?>

                                        <span class="sita-sin-dato">
                                            Sin roles
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ESTADO -->

                                <td>

                                    <span class="<?= e($claseEstado) ?>">

                                        <?= e(
                                            $estadoProfesional !== ''
                                                ? strtoupper($estadoProfesional)
                                                : 'SIN ESTADO'
                                        ) ?>

                                    </span>

                                </td>


                                <!-- ACCIÓN -->

                                <td>

                                    <a
                                        href="<?= e(
                                            url(
                                                '/profesionales/detalle?id=' .
                                                (int) $profesional['id']
                                            )
                                        ) ?>"
                                        class="sita-action"
                                        title="Ver detalle"
                                        aria-label="Ver detalle"
                                    >
                                        👁
                                    </a>

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
                                        '/profesionales?pagina=' .
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
                                            '/profesionales?pagina=' .
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
                                        '/profesionales?pagina=' .
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