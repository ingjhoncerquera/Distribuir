<?php

declare(strict_types=1);

require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';

$solicitudes = is_array($solicitudes ?? null)
    ? $solicitudes
    : [];

?>

<style>
    .aval-container {
        max-width: 1500px;
        margin: 0 auto;
        padding: 24px;
    }

    .aval-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .aval-title {
        margin: 0;
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
    }

    .aval-subtitle {
        margin: 6px 0 0;
        color: #6b7280;
        font-size: 14px;
    }

    .aval-card {
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.07);
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    .aval-card-header {
        padding: 18px 22px;
        border-bottom: 1px solid #e5e7eb;
        background: #f8fafc;
    }

    .aval-card-header h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
    }

    .aval-table-wrapper {
        width: 100%;
        overflow-x: auto;
    }

    .aval-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1050px;
    }

    .aval-table th {
        background: #f8fafc;
        color: #374151;
        font-size: 13px;
        font-weight: 700;
        text-align: left;
        padding: 14px 16px;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
    }

    .aval-table td {
        padding: 14px 16px;
        border-bottom: 1px solid #eef2f7;
        color: #374151;
        font-size: 14px;
        vertical-align: middle;
    }

    .aval-table tbody tr:hover {
        background: #f9fafb;
    }

    .solicitud-numero {
        font-weight: 700;
        color: #111827;
        white-space: nowrap;
    }

    .solicitud-titulo {
        font-weight: 600;
        color: #1f2937;
    }

    .solicitud-proceso {
        font-weight: 600;
        color: #374151;
    }

    .solicitud-area {
        color: #6b7280;
    }

    .solicitante {
        font-weight: 600;
        color: #374151;
    }

    .fecha-envio {
        white-space: nowrap;
        color: #6b7280;
    }

    .estado-pendiente {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border-radius: 999px;
        background: #fff7ed;
        color: #c2410c;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .acciones {
        display: flex;
        gap: 7px;
        align-items: center;
        flex-wrap: wrap;
    }

    .btn-aval {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border: 0;
        border-radius: 7px;
        padding: 8px 12px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: 0.2s ease;
    }

    .btn-ver {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .btn-ver:hover {
        background: #dbeafe;
    }

    .btn-avalar {
        background: #ecfdf5;
        color: #047857;
    }

    .btn-avalar:hover {
        background: #d1fae5;
    }

    .btn-devolver {
        background: #fff7ed;
        color: #c2410c;
    }

    .btn-devolver:hover {
        background: #ffedd5;
    }

    .aval-empty {
        padding: 55px 25px;
        text-align: center;
        color: #6b7280;
    }

    .aval-empty-icon {
        font-size: 42px;
        margin-bottom: 12px;
    }

    .aval-empty-title {
        margin: 0 0 7px;
        color: #374151;
        font-size: 18px;
        font-weight: 700;
    }

    .aval-empty-text {
        margin: 0;
        font-size: 14px;
    }

    .aval-modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .aval-modal-backdrop.active {
        display: flex;
    }

    .aval-modal {
        width: 100%;
        max-width: 560px;
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
        overflow: hidden;
    }

    .aval-modal-header {
        padding: 18px 22px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
    }

    .aval-modal-header h3 {
        margin: 0;
        font-size: 19px;
        color: #1f2937;
    }

    .aval-modal-close {
        border: 0;
        background: transparent;
        font-size: 25px;
        color: #6b7280;
        cursor: pointer;
        line-height: 1;
    }

    .aval-modal-body {
        padding: 22px;
    }

    .aval-modal-body label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 700;
        color: #374151;
    }

    .aval-modal-body textarea {
        width: 100%;
        min-height: 130px;
        resize: vertical;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 11px 12px;
        font-family: inherit;
        font-size: 14px;
        box-sizing: border-box;
    }

    .aval-modal-body textarea:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
    }

    .aval-modal-footer {
        padding: 16px 22px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .btn-modal {
        border: 0;
        border-radius: 7px;
        padding: 9px 16px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-modal-cancelar {
        background: #f3f4f6;
        color: #374151;
    }

    .btn-modal-confirmar {
        background: #ea580c;
        color: #ffffff;
    }

    .btn-modal-confirmar:hover {
        background: #c2410c;
    }

    @media (max-width: 768px) {
        .aval-container {
            padding: 15px;
        }

        .aval-title {
            font-size: 23px;
        }
    }
</style>

<div class="aval-container">


<div class="aval-header">

    <div>
        <h1 class="aval-title">
            Aval de solicitudes de materiales
        </h1>

        <p class="aval-subtitle">
            Revise las solicitudes pendientes de aval de los procesos que tiene asignados.
        </p>
    </div>

</div>


<div class="aval-card">

    <div class="aval-card-header">
        <h2>
            Solicitudes pendientes de revisión
        </h2>
    </div>


    <?php if (empty($solicitudes)): ?>

        <div class="aval-empty">

            <div class="aval-empty-icon">
                📋
            </div>

            <h3 class="aval-empty-title">
                No hay solicitudes pendientes
            </h3>

            <p class="aval-empty-text">
                Actualmente no existen solicitudes pendientes de aval para revisar.
            </p>

        </div>

    <?php else: ?>

        <div class="aval-table-wrapper">

            <table class="aval-table">

                <thead>

                    <tr>
                        <th>
                            Solicitud
                        </th>

                        <th>
                            Proceso
                        </th>

                        <th>
                            Área
                        </th>

                        <th>
                            Solicitante
                        </th>

                        <th>
                            Título
                        </th>

                        <th>
                            Fecha envío
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

                    <?php foreach ($solicitudes as $solicitud): ?>

                        <?php
                        $idSolicitud =
                            (int) (
                                $solicitud['id']
                                ?? 0
                            );

                        $numeroSolicitud =
                            (string) (
                                $solicitud['numero_solicitud']
                                ?? ''
                            );

                        $nombreProceso =
                            (string) (
                                $solicitud['nombre_proceso']
                                ?? ''
                            );

                        $nombreArea =
                            (string) (
                                $solicitud['nombre_area']
                                ?? ''
                            );

                        $nombres =
                            trim(
                                (string) (
                                    $solicitud['nombres']
                                    ?? ''
                                )
                            );

                        $apellidos =
                            trim(
                                (string) (
                                    $solicitud['apellidos']
                                    ?? ''
                                )
                            );

                        $solicitante =
                            trim(
                                $nombres . ' ' . $apellidos
                            );

                        $titulo =
                            (string) (
                                $solicitud['titulo']
                                ?? ''
                            );

                        $fechaEnvio =
                            (string) (
                                $solicitud['fecha_envio']
                                ?? ''
                            );

                        $estado =
                            (string) (
                                $solicitud['estado']
                                ?? ''
                            );
                        ?>

                        <tr>

                            <td>
                                <span class="solicitud-numero">
                                    <?= e($numeroSolicitud) ?>
                                </span>
                            </td>

                            <td>
                                <span class="solicitud-proceso">
                                    <?= e($nombreProceso) ?>
                                </span>
                            </td>

                            <td>
                                <span class="solicitud-area">
                                    <?= e($nombreArea) ?>
                                </span>
                            </td>

                            <td>
                                <span class="solicitante">
                                    <?= e($solicitante) ?>
                                </span>
                            </td>

                            <td>
                                <span class="solicitud-titulo">
                                    <?= e($titulo) ?>
                                </span>
                            </td>

                            <td>
                                <span class="fecha-envio">
                                    <?= e($fechaEnvio) ?>
                                </span>
                            </td>

                            <td>

                                <span class="estado-pendiente">
                                    Pendiente de aval
                                </span>

                            </td>

                            <td>

                                <div class="acciones">

                                    <a
                                        href="<?= e(
                                            url(
                                                '/materiales/solicitudes/ver?id='
                                                . $idSolicitud
                                            )
                                        ) ?>"
                                        class="btn-aval btn-ver"
                                    >
                                        👁 Ver
                                    </a>


                                    <form
                                        method="POST"
                                        action="<?= e(
                                            url(
                                                '/materiales/solicitudes/avalar'
                                            )
                                        ) ?>"
                                        onsubmit="return confirm('¿Está seguro de avalar esta solicitud?');"
                                    >

                                        <?= csrfField() ?>

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= $idSolicitud ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn-aval btn-avalar"
                                        >
                                            ✓ Avalar
                                        </button>

                                    </form>


                                    <button
                                        type="button"
                                        class="btn-aval btn-devolver"
                                        onclick="abrirModalDevolver(<?= $idSolicitud ?>)"
                                    >
                                        ↩ Devolver
                                    </button>

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

<!-- =========================================================
     MODAL DEVOLVER SOLICITUD
     ========================================================= -->

<div
    id="modalDevolver"
    class="aval-modal-backdrop"
>


<div class="aval-modal">

    <div class="aval-modal-header">

        <h3>
            Devolver solicitud
        </h3>

        <button
            type="button"
            class="aval-modal-close"
            onclick="cerrarModalDevolver()"
        >
            ×
        </button>

    </div>


    <form
        method="POST"
        action="<?= e(
            url(
                '/materiales/solicitudes/devolver'
            )
        ) ?>"
    >

        <div class="aval-modal-body">

            <?= csrfField() ?>

            <input
                type="hidden"
                name="id"
                id="idSolicitudDevolver"
                value=""
            >

            <label for="observacion_devolucion">
                Observación de devolución
            </label>

            <textarea
                name="observacion"
                id="observacion_devolucion"
                required
                maxlength="2000"
                placeholder="Indique al profesional solicitante qué debe corregir o complementar..."
            ></textarea>

        </div>


        <div class="aval-modal-footer">

            <button
                type="button"
                class="btn-modal btn-modal-cancelar"
                onclick="cerrarModalDevolver()"
            >
                Cancelar
            </button>

            <button
                type="submit"
                class="btn-modal btn-modal-confirmar"
            >
                Devolver solicitud
            </button>

        </div>

    </form>

</div>


</div>

<script>

function abrirModalDevolver(idSolicitud) {

    const modal =
        document.getElementById('modalDevolver');

    const campoId =
        document.getElementById('idSolicitudDevolver');

    const observacion =
        document.getElementById('observacion_devolucion');

    if (!modal || !campoId) {
        return;
    }

    campoId.value = idSolicitud;

    if (observacion) {
        observacion.value = '';
    }

    modal.classList.add('active');

    if (observacion) {
        setTimeout(function () {
            observacion.focus();
        }, 100);
    }
}


function cerrarModalDevolver() {

    const modal =
        document.getElementById('modalDevolver');

    if (!modal) {
        return;
    }

    modal.classList.remove('active');
}


document.addEventListener(
    'keydown',
    function (event) {

        if (event.key === 'Escape') {
            cerrarModalDevolver();
        }

    }
);


document.getElementById('modalDevolver')
    ?.addEventListener(
        'click',
        function (event) {

            if (event.target === this) {
                cerrarModalDevolver();
            }

        }
    );

</script>

<?php
require_once ROOT_PATH . '/views/layout/footer.php';
?>
