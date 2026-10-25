<?php

declare(strict_types=1);

if (!isset($titulo)) {
    $titulo = 'Fichas';
}

$buscar = (string) ($_GET['buscar'] ?? '');

$pagina = (int) ($pagina ?? 1);

$total = (int) ($total ?? 0);

$totalPaginas = (int) ($totalPaginas ?? 1);

$programas = $programas ?? [];

$jornadas = $jornadas ?? [];

$mensajeExito = $mensajeExito ?? null;

$mensajeError = $mensajeError ?? null;

$esAdministrador = $esAdministrador ?? false;

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
        border: 1px solid #d8dde5;
        border-radius: 9px;
        padding: 10px 13px;
        outline: none;
    }

    .sita-search input:focus {
        border-color: #198754;
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

    .sita-ficha {
        font-weight: 700;
        color: #172033;
    }

    .sita-programa {
        font-size: 13px;
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

    .sita-badge-activa {
        background: #dcfce7;
        color: #166534;
    }

    .sita-badge-finalizada {
        background: #e0e7ff;
        color: #3730a3;
    }

    .sita-badge-cancelada {
        background: #fee2e2;
        color: #991b1b;
    }

    .sita-action {
        color: #198754;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
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

    .sita-page-link.active {
        background: #198754;
        color: #fff;
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

    .sita-form-fixed {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 9px;
        padding: 10px 12px;
        color: #4b5563;
        font-size: 14px;
        min-height: 20px;
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
        }

        .sita-page-actions {
            width: 100%;
        }

        .sita-page-actions .sita-btn {
            flex: 1;
        }
    }
</style>

<main class="sita-page">

    <div class="sita-page-header">

        <div>

            <h1 class="sita-page-title">
                Fichas
            </h1>

            <p class="sita-page-subtitle">
                Consulta de fichas de formación y sus aprendices matriculados.
            </p>

        </div>

        <div class="sita-page-actions">

            <?php if ($esAdministrador): ?>

                <button
                    type="button"
                    class="sita-btn sita-btn-primary"
                    id="btnNuevaFicha"
                >
                    + Nueva ficha
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
                action="<?= e(url('/fichas')) ?>"
                class="sita-search"
            >

                <input
                    type="text"
                    name="buscar"
                    value="<?= e($buscar) ?>"
                    placeholder="Buscar ficha, programa, centro..."
                >

                <button
                    type="submit"
                    class="sita-btn sita-btn-primary"
                >
                    Buscar
                </button>

                <?php if ($buscar !== ''): ?>

                    <a
                        href="<?= e(url('/fichas')) ?>"
                        class="sita-btn sita-btn-secondary"
                    >
                        Limpiar
                    </a>

                <?php endif; ?>

            </form>

            <div>
                <strong><?= e($total) ?></strong>
                ficha(s)
            </div>

        </div>


        <?php if (empty($fichas)): ?>

            <div class="sita-empty">
                No se encontraron fichas.
            </div>

        <?php else: ?>

            <div class="sita-table-wrapper">

                <table class="sita-table">

                    <thead>

                        <tr>
                            <th>Ficha</th>
                            <th>Programa</th>
                            <th>Modalidad</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Aprendices</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($fichas as $ficha): ?>

                            <?php
                            $estado = strtolower(
                                trim(
                                    (string) $ficha['estado']
                                )
                            );
                            ?>

                            <tr>

                                <td>
                                    <div class="sita-ficha">
                                        <?= e($ficha['numero_ficha']) ?>
                                    </div>
                                </td>

                                <td>
                                    <strong><?= e($ficha['codigo_programa']) ?></strong>

                                    <div class="sita-programa">
                                        <?= e($ficha['nombre_programa']) ?>
                                    </div>
                                </td>

                                <td>
                                    <?= e($ficha['modalidad'] ?: '—') ?>
                                </td>

                                <td>
                                    <?= e(fecha($ficha['fecha_inicio'])) ?>
                                </td>

                                <td>
                                    <?= e(fecha($ficha['fecha_fin']) ?: '—') ?>
                                </td>

                                <td>
                                    <?= e($ficha['cantidad_aprendices']) ?>
                                </td>

                                <td>

                                    <span class="sita-badge sita-badge-<?= e($estado) ?>">
                                        <?= e(ucfirst($estado)) ?>
                                    </span>

                                </td>

                                <td>

                                    <a
                                        class="sita-action"
                                        href="<?= e(url('/fichas/detalle?id=' . (int) $ficha['id'])) ?>"
                                    >
                                        Ver detalle →
                                    </a>

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
                    Página <?= e($pagina) ?>
                    de <?= e($totalPaginas) ?>
                </div>

                <div class="sita-pagination-links">

                    <?php if ($pagina > 1): ?>

                        <a
                            class="sita-page-link"
                            href="<?= e(url('/fichas?pagina=' . ($pagina - 1) . '&buscar=' . urlencode($buscar))) ?>"
                        >
                            ‹
                        </a>

                    <?php endif; ?>


                    <?php for (
                        $i = max(1, $pagina - 2);
                        $i <= min($totalPaginas, $pagina + 2);
                        $i++
                    ): ?>

                        <a
                            class="sita-page-link <?= $i === $pagina ? 'active' : '' ?>"
                            href="<?= e(url('/fichas?pagina=' . $i . '&buscar=' . urlencode($buscar))) ?>"
                        >
                            <?= e($i) ?>
                        </a>

                    <?php endfor; ?>


                    <?php if ($pagina < $totalPaginas): ?>

                        <a
                            class="sita-page-link"
                            href="<?= e(url('/fichas?pagina=' . ($pagina + 1) . '&buscar=' . urlencode($buscar))) ?>"
                        >
                            ›
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>

    </section>

</main>


<?php if ($esAdministrador): ?>

    <!-- ============================================================
         MODAL NUEVA FICHA
         ============================================================ -->

    <div
        class="sita-modal-backdrop"
        id="modalNuevaFicha"
        aria-hidden="true"
    >

        <div
            class="sita-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="tituloModalNuevaFicha"
        >

            <div class="sita-modal-header">

                <h2
                    class="sita-modal-title"
                    id="tituloModalNuevaFicha"
                >
                    Nueva ficha
                </h2>

                <button
                    type="button"
                    class="sita-modal-close"
                    id="btnCerrarNuevaFicha"
                    aria-label="Cerrar"
                >
                    ×
                </button>

            </div>


            <form
                method="POST"
                action="<?= e(url('/fichas/crear')) ?>"
            >

                <?= csrfField() ?>

                <div class="sita-modal-body">

                    <div class="sita-form-grid">

                        <div class="sita-form-group">

                            <label
                                for="numero_ficha"
                                class="sita-form-label"
                            >
                                Número de ficha *
                            </label>

                            <input
                                type="text"
                                id="numero_ficha"
                                name="numero_ficha"
                                class="sita-form-control"
                                maxlength="30"
                                required
                            >

                        </div>


                        <div class="sita-form-group">

                            <label
                                for="id_programa"
                                class="sita-form-label"
                            >
                                Programa *
                            </label>

                            <select
                                id="id_programa"
                                name="id_programa"
                                class="sita-form-control"
                                required
                            >

                                <option value="">
                                    Seleccione un programa
                                </option>

                                <?php foreach ($programas as $programa): ?>

                                    <option
                                        value="<?= e($programa['id']) ?>"
                                    >
                                        <?= e($programa['codigo']) ?>
                                        -
                                        <?= e($programa['nombre']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="sita-form-group">

                            <label
                                for="version_programa"
                                class="sita-form-label"
                            >
                                Versión del programa
                            </label>

                            <input
                                type="text"
                                id="version_programa"
                                name="version_programa"
                                class="sita-form-control"
                                maxlength="20"
                                placeholder="Ej. 1"
                            >

                        </div>


                        <div class="sita-form-group">

                            <label
                                for="modalidad"
                                class="sita-form-label"
                            >
                                Modalidad *
                            </label>

                            <select
                                id="modalidad"
                                name="modalidad"
                                class="sita-form-control"
                                required
                            >

                                <option value="">
                                    Seleccione una modalidad
                                </option>

                                <option value="VIRTUAL">
                                    VIRTUAL
                                </option>

                                <option value="PRESENCIAL">
                                    PRESENCIAL
                                </option>

                                <option value="A DISTANCIA">
                                    A DISTANCIA
                                </option>

                                <option value="MIXTA">
                                    MIXTA
                                </option>

                            </select>

                        </div>


                        <div class="sita-form-group">

                            <label class="sita-form-label">
                                Regional
                            </label>

                            <div class="sita-form-fixed">
                                41 - REGIONAL HUILA
                            </div>

                        </div>


                        <div class="sita-form-group">

                            <label class="sita-form-label">
                                Centro
                            </label>

                            <div class="sita-form-fixed">
                                9528 - CENTRO DE GESTION Y DESARROLLO SOSTENIBLE SURCOLOMBIANO
                            </div>

                        </div>


                        <div class="sita-form-group">

                            <label
                                for="id_jornada"
                                class="sita-form-label"
                            >
                                Jornada *
                            </label>

                            <select
                                id="id_jornada"
                                name="id_jornada"
                                class="sita-form-control"
                                required
                            >

                                <option value="">
                                    Seleccione una jornada
                                </option>

                                <?php foreach ($jornadas as $jornada): ?>

                                    <option
                                        value="<?= e($jornada['id']) ?>"
                                    >
                                        <?= e($jornada['nombre']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

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

                                <option value="activa">
                                    Activa
                                </option>

                                <option value="finalizada">
                                    Finalizada
                                </option>

                                <option value="cancelada">
                                    Cancelada
                                </option>

                            </select>

                        </div>


                        <div class="sita-form-group">

                            <label
                                for="fecha_inicio"
                                class="sita-form-label"
                            >
                                Fecha de inicio *
                            </label>

                            <input
                                type="date"
                                id="fecha_inicio"
                                name="fecha_inicio"
                                class="sita-form-control"
                                required
                            >

                        </div>


                        <div class="sita-form-group">

                            <label
                                for="fecha_fin"
                                class="sita-form-label"
                            >
                                Fecha de finalización
                            </label>

                            <input
                                type="date"
                                id="fecha_fin"
                                name="fecha_fin"
                                class="sita-form-control"
                            >

                        </div>


                        <div class="sita-form-group sita-form-group-full">

                            <p class="sita-form-help">
                                La jornada es administrada por el centro.
                                Regional y centro se registran automáticamente
                                con los datos correspondientes al C.G.D.S.S.
                            </p>

                        </div>

                    </div>

                </div>


                <div class="sita-modal-footer">

                    <button
                        type="button"
                        class="sita-btn sita-btn-secondary"
                        id="btnCancelarNuevaFicha"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="sita-btn sita-btn-primary"
                    >
                        Crear ficha
                    </button>

                </div>

            </form>

        </div>

    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const modal =
                document.getElementById('modalNuevaFicha');

            const btnAbrir =
                document.getElementById('btnNuevaFicha');

            const btnCerrar =
                document.getElementById('btnCerrarNuevaFicha');

            const btnCancelar =
                document.getElementById('btnCancelarNuevaFicha');


            function abrirModal() {

                if (!modal) {
                    return;
                }

                modal.classList.add('is-open');

                modal.setAttribute(
                    'aria-hidden',
                    'false'
                );

                document.body.style.overflow = 'hidden';

                const campoFicha =
                    document.getElementById('numero_ficha');

                if (campoFicha) {
                    setTimeout(function () {
                        campoFicha.focus();
                    }, 100);
                }
            }


            function cerrarModal() {

                if (!modal) {
                    return;
                }

                modal.classList.remove('is-open');

                modal.setAttribute(
                    'aria-hidden',
                    'true'
                );

                document.body.style.overflow = '';
            }


            if (btnAbrir) {
                btnAbrir.addEventListener(
                    'click',
                    abrirModal
                );
            }


            if (btnCerrar) {
                btnCerrar.addEventListener(
                    'click',
                    cerrarModal
                );
            }


            if (btnCancelar) {
                btnCancelar.addEventListener(
                    'click',
                    cerrarModal
                );
            }


            if (modal) {

                modal.addEventListener(
                    'click',
                    function (event) {

                        if (
                            event.target === modal
                        ) {
                            cerrarModal();
                        }

                    }
                );

            }


            document.addEventListener(
                'keydown',
                function (event) {

                    if (
                        event.key === 'Escape'
                        && modal
                        && modal.classList.contains('is-open')
                    ) {
                        cerrarModal();
                    }

                }
            );

        });
    </script>

<?php endif; ?>


<?php
require_once ROOT_PATH . '/views/layout/footer.php';
?>