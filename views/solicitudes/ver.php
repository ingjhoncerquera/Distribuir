<?php

declare(strict_types=1);

require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';
require_once ROOT_PATH . '/core/Auth.php';

$solicitud = is_array($solicitud ?? null) ? $solicitud : [];
$detalles = is_array($detalles ?? null) ? $detalles : [];

$idSolicitud = (int) ($solicitud['id'] ?? 0);
$estado = (string) ($solicitud['estado'] ?? '');
$rutaVolver = (string) ($rutaVolver ?? '/materiales/solicitudes');

$esc = static fn($v): string =>
    htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$fechaLegible = static function (?string $f): string {
    if (!$f) {
        return '—';
    }

    $t = strtotime($f);

    return $t === false ? $f : date('d/m/Y H:i', $t);
};

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;

unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$estadoTexto = match ($estado) {
    'borrador'       => 'Borrador',
    'pendiente_aval' => 'Pendiente de aval',
    'devuelta'       => 'Devuelta',
    'avalada'        => 'Avalada',
    'rechazada'      => 'Rechazada',
    'cancelada'      => 'Cancelada',
    default          => ucfirst(str_replace('_', ' ', $estado)),
};

$estadoClase = match ($estado) {
    'borrador'       => 'estado-borrador',
    'pendiente_aval' => 'estado-pendiente',
    'devuelta'       => 'estado-devuelta',
    'avalada'        => 'estado-avalada',
    'rechazada'      => 'estado-rechazada',
    'cancelada'      => 'estado-cancelada',
    default          => 'estado-borrador',
};

$clasificaciones = [
    'es_consumo'    => 'Consumo',
    'es_devolutivo' => 'Devolutivo',
    'es_software'   => 'Software',
    'es_epp'        => 'EPP',
];

$totalProductos = count($detalles);
?>

<style>
/* =========================================================
   CONTENEDOR GENERAL
   ========================================================= */

.solicitud-ver {
    width: 100%;
    max-width: 1800px;
    margin: 0 auto;
    color: #263445;
    font-size: 13px;
}

.solicitud-ver,
.solicitud-ver * {
    box-sizing: border-box;
}

.solicitud-ver .page-title {
    color: #1f2937;
    font-size: 1.45rem;
    font-weight: 750;
    margin-bottom: 5px;
}

.solicitud-ver .page-subtitle {
    color: #6b7280;
    font-size: 12px;
}

.solicitud-ver .card-solicitud {
    margin-bottom: 18px;
    border: 1px solid #dfe5ec;
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 2px 7px rgba(15, 23, 42, .045);
    overflow: hidden;
}

.solicitud-ver .card-solicitud > .card-header {
    padding: 12px 16px;
    background: #f5f7fa;
    border-bottom: 1px solid #dfe5ec;
}

.solicitud-ver .card-solicitud > .card-header h5 {
    margin: 0;
    color: #253448;
    font-size: 14px;
    font-weight: 750;
}

.solicitud-ver .card-solicitud > .card-body {
    padding: 15px;
}

/* =========================================================
   ESTADO DE LA SOLICITUD
   ========================================================= */

.solicitud-ver .estado {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 750;
    white-space: nowrap;
}

.solicitud-ver .estado-borrador {
    background: #e9ecef;
    color: #495057;
}

.solicitud-ver .estado-pendiente {
    background: #fff0c2;
    color: #805900;
}

.solicitud-ver .estado-devuelta {
    background: #dbeafe;
    color: #1d4ed8;
}

.solicitud-ver .estado-avalada {
    background: #d1fae5;
    color: #065f46;
}

.solicitud-ver .estado-rechazada {
    background: #fee2e2;
    color: #991b1b;
}

.solicitud-ver .estado-cancelada {
    background: #e5e7eb;
    color: #374151;
}

/* =========================================================
   DATOS GENERALES
   ========================================================= */

.solicitud-ver .dato-caja {
    height: 100%;
    padding: 10px 12px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 7px;
    overflow-wrap: anywhere;
}

.solicitud-ver .dato-label {
    display: block;
    margin-bottom: 4px;
    color: #64748b;
    font-size: 10px;
    font-weight: 750;
    letter-spacing: .035em;
    text-transform: uppercase;
}

.solicitud-ver .dato-valor {
    color: #253448;
    font-size: 12px;
    line-height: 1.5;
}

.solicitud-ver .dato-tecnico {
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    word-break: normal;
    line-height: 1.5;
}

.solicitud-ver .resumen-principal {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 13px;
    margin-bottom: 14px;
}

.solicitud-ver .resumen-numero {
    font-size: 15px;
    font-weight: 800;
    color: #1e3a5f;
    overflow-wrap: anywhere;
}

/* =========================================================
   TABLA DE MATERIALES
   ========================================================= */

.solicitud-ver .tabla-materiales-contenedor {
    width: 100%;
    overflow-x: auto;
    border: 1px solid #d9e1ea;
    border-radius: 7px;
}

.solicitud-ver .tabla-materiales {
    width: 100%;
    min-width: 1550px;
    margin: 0;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 11px;
}

.solicitud-ver .tabla-materiales th,
.solicitud-ver .tabla-materiales td {
    padding: 9px 8px;
    border: 1px solid #d9e1ea;
    vertical-align: top;
    overflow-wrap: anywhere;
}

.solicitud-ver .tabla-materiales thead th {
    background: #eaf0f6;
    color: #26384d;
    font-size: 10px;
    font-weight: 800;
    text-align: center;
    vertical-align: middle;
    line-height: 1.4;
}

.solicitud-ver .tabla-materiales tbody td {
    color: #374151;
    line-height: 1.5;
}

.solicitud-ver .tabla-materiales tbody tr:nth-child(even) {
    background: #fafbfd;
}

.solicitud-ver .tabla-materiales tbody tr:hover {
    background: #f1f7ff;
}

.solicitud-ver .col-item {
    width: 48px;
    text-align: center;
}

.solicitud-ver .col-unspsc {
    width: 115px;
}

.solicitud-ver .col-producto {
    width: 190px;
}

.solicitud-ver .col-tecnica {
    width: 340px;
}

.solicitud-ver .col-unidad {
    width: 105px;
}

.solicitud-ver .col-cantidad {
    width: 105px;
}

.solicitud-ver .col-observaciones {
    width: 190px;
}

.solicitud-ver .col-clasificacion {
    width: 88px;
}

.solicitud-ver .item-numero {
    display: inline-flex;
    width: 25px;
    height: 25px;
    align-items: center;
    justify-content: center;
    background: #edf2f7;
    border-radius: 5px;
    color: #334155;
    font-size: 11px;
    font-weight: 800;
}

.solicitud-ver .producto-nombre {
    color: #1e3a5f;
    font-size: 11px;
    font-weight: 800;
}

.solicitud-ver .codigo-unspsc {
    font-family: Consolas, monospace;
    color: #475569;
    font-size: 11px;
    white-space: nowrap;
}

.solicitud-ver .cantidad-valor {
    display: inline-block;
    min-width: 35px;
    padding: 4px 7px;
    border-radius: 5px;
    background: #edf4ff;
    color: #1d4ed8;
    font-weight: 800;
    text-align: center;
}

.solicitud-ver .unidad-valor {
    font-weight: 600;
}

.solicitud-ver .clasificacion-col {
    text-align: center;
    vertical-align: middle !important;
}

.solicitud-ver .marca-x {
    display: inline-flex;
    width: 25px;
    height: 25px;
    align-items: center;
    justify-content: center;
    border: 1px solid #a7d7bd;
    border-radius: 5px;
    background: #e8f7ee;
    color: #087443;
    font-size: 14px;
    font-weight: 900;
}

.solicitud-ver .marca-vacia {
    color: #cbd5e1;
    font-size: 15px;
}

.solicitud-ver .detalle-sin-dato {
    color: #94a3b8;
    font-style: italic;
}

.solicitud-ver .fila-destinos td {
    background: #f8fafc !important;
    padding: 10px 14px;
}

.solicitud-ver .destinos-titulo {
    margin-bottom: 7px;
    color: #475569;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
}

.solicitud-ver .destinos-lista {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
}

.solicitud-ver .destino-etiqueta {
    display: inline-block;
    padding: 5px 8px;
    background: #fff;
    border: 1px solid #dbe3ec;
    border-radius: 5px;
    color: #475569;
    font-size: 10px;
}

.solicitud-ver .destino-etiqueta strong {
    color: #1e3a5f;
}

/* =========================================================
   JUSTIFICACIÓN Y OBSERVACIONES
   ========================================================= */

.solicitud-ver .bloque-texto {
    padding: 11px 13px;
    border: 1px solid #e2e8f0;
    border-radius: 7px;
    background: #fff;
    line-height: 1.6;
    overflow-wrap: anywhere;
}

.solicitud-ver .ayuda-tabla {
    color: #64748b;
    font-size: 10px;
}

.solicitud-ver .acciones-inferiores {
    padding-bottom: 18px;
}

@media (max-width: 768px) {
    .solicitud-ver .page-title {
        font-size: 1.2rem;
    }

    .solicitud-ver .card-solicitud > .card-body {
        padding: 11px;
    }

    .solicitud-ver .card-solicitud > .card-header {
        padding: 11px 12px;
    }

    .solicitud-ver .tabla-materiales {
        min-width: 1550px;
    }
}
</style>

<main class="container-fluid py-4">
<div class="solicitud-ver">

    <!-- ENCABEZADO -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">

        <div>
            <h2 class="page-title">
                <i class="bi bi-file-earmark-text me-2"></i>
                Consulta de solicitud de materiales
            </h2>

            <p class="page-subtitle mb-0">
                Información general y detalle de los productos solicitados.
            </p>
        </div>

        <a
            href="<?= e(url($rutaVolver)) ?>"
            class="btn btn-sm btn-outline-secondary"
        >
            <i class="bi bi-arrow-left me-1"></i>
            Volver
        </a>

    </div>

    <!-- MENSAJES -->
    <?php if ($flashSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show py-2">
            <?= $esc($flashSuccess) ?>
            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>
        </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2">
            <?= $esc($flashError) ?>
            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>
        </div>
    <?php endif; ?>

    <?php if ($idSolicitud <= 0): ?>

        <div class="alert alert-danger">
            No se pudo identificar la solicitud.
        </div>

    <?php else: ?>

        <!-- =================================================
             INFORMACIÓN GENERAL
             ================================================= -->

        <section class="card-solicitud">

            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5>
                    <i class="bi bi-info-circle me-2"></i>
                    Información general de la solicitud
                </h5>

                <span class="estado <?= $esc($estadoClase) ?>">
                    <i class="bi bi-circle-fill" style="font-size:7px"></i>
                    <?= $esc($estadoTexto) ?>
                </span>
            </div>

            <div class="card-body">

                <div class="resumen-principal">

                    <div class="row g-3 align-items-center">

                        <div class="col-lg-5">
                            <span class="dato-label">Número de solicitud</span>
                            <div class="resumen-numero">
                                <?= $esc($solicitud['numero_solicitud'] ?? 'Sin número') ?>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <span class="dato-label">Título de la solicitud</span>
                            <div class="dato-valor fw-semibold">
                                <?= $esc($solicitud['titulo'] ?? 'Sin título') ?>
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <span class="dato-label">Productos registrados</span>
                            <div class="dato-valor fw-bold">
                                <?= $totalProductos ?>
                                <?= $totalProductos === 1 ? 'producto' : 'productos' ?>
                            </div>
                        </div>

                    </div>

                </div>

                <div class="row g-2">

                    <?php
                    $datosGenerales = [
                        'Área solicitante' =>
                            $solicitud['nombre_area'] ?? 'Sin área',

                        'Proceso' =>
                            trim(
                                (string) ($solicitud['nombre_proceso'] ?? 'Sin proceso')
                                . (
                                    ($solicitud['anio'] ?? '') !== ''
                                        ? ' (' . $solicitud['anio'] . ')'
                                        : ''
                                )
                            ),

                        'Profesional solicitante' =>
                            trim(
                                (string) ($solicitud['nombres'] ?? '')
                                . ' '
                                . (string) ($solicitud['apellidos'] ?? '')
                            ) ?: 'Sin información',

                        'Fecha de creación' =>
                            $fechaLegible((string) ($solicitud['created_at'] ?? '')),

                        'Última actualización' =>
                            $fechaLegible((string) ($solicitud['updated_at'] ?? '')),
                    ];
                    ?>

                    <?php foreach ($datosGenerales as $etiqueta => $valor): ?>

                        <div class="col-sm-6 col-lg-4">
                            <div class="dato-caja">
                                <span class="dato-label">
                                    <?= $esc($etiqueta) ?>
                                </span>

                                <div class="dato-valor">
                                    <?= $esc($valor) ?>
                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>

                    <div class="col-12 mt-3">
                        <span class="dato-label">Justificación de la solicitud</span>

                        <div class="bloque-texto dato-tecnico">
                            <?php if (trim((string) ($solicitud['justificacion'] ?? '')) !== ''): ?>
                                <?= nl2br($esc($solicitud['justificacion'])) ?>
                            <?php else: ?>
                                <span class="detalle-sin-dato">
                                    Sin justificación registrada.
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php
                    $observacionRevision = trim(
                        (string) ($solicitud['observacion_revision'] ?? '')
                    );
                    ?>

                    <?php if (
                        in_array($estado, ['devuelta', 'rechazada'], true)
                        && $observacionRevision !== ''
                    ): ?>

                        <div class="col-12 mt-3">
                            <div class="alert alert-info mb-0 py-2">

                                <div class="fw-bold mb-1">
                                    <i class="bi bi-chat-left-text me-1"></i>
                                    Observación de revisión
                                </div>

                                <div>
                                    <?= nl2br($esc($observacionRevision)) ?>
                                </div>

                            </div>
                        </div>

                    <?php endif; ?>

                </div>

            </div>
        </section>


        <!-- =================================================
             TABLA DE MATERIALES
             ================================================= -->

        <section class="card-solicitud">

            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">

                <h5>
                    <i class="bi bi-box-seam me-2"></i>
                    Detalle de materiales solicitados
                </h5>

                <span class="badge text-bg-secondary">
                    <?= $totalProductos ?>
                    <?= $totalProductos === 1 ? 'ítem' : 'ítems' ?>
                </span>

            </div>

            <div class="card-body">

                <?php if (empty($detalles)): ?>

                    <div class="alert alert-light border mb-0">
                        Esta solicitud no tiene materiales registrados.
                    </div>

                <?php else: ?>

                    <p class="ayuda-tabla mb-2">
                        Desplace la tabla horizontalmente para consultar todas
                        las columnas. Las marcas X identifican la clasificación
                        seleccionada para cada material.
                    </p>

                    <div class="tabla-materiales-contenedor">

                        <table class="tabla-materiales">

                            <colgroup>
                                <col class="col-item">
                                <col class="col-unspsc">
                                <col class="col-producto">
                                <col class="col-tecnica">
                                <col class="col-unidad">
                                <col class="col-cantidad">
                                <col class="col-observaciones">

                                <?php foreach ($clasificaciones as $nombre): ?>
                                    <col class="col-clasificacion">
                                <?php endforeach; ?>
                            </colgroup>

                            <thead>
                                <tr>
                                    <th>ÍTEM</th>
                                    <th>CÓDIGO UNSPSC</th>
                                    <th>PRODUCTO</th>
                                    <th>DESCRIPCIÓN TÉCNICA REQUERIDA DEL BIEN</th>
                                    <th>UNIDAD DE MEDIDA</th>
                                    <th>CANTIDAD REQUERIDA</th>
                                    <th>OBSERVACIONES</th>

                                    <?php foreach ($clasificaciones as $nombre): ?>
                                        <th><?= $esc(strtoupper($nombre)) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($detalles as $i => $detalle): ?>

                                    <?php
                                    $unidad = trim(
                                        (string) ($detalle['unidad_medida'] ?? '')
                                    );

                                    if ($unidad === '') {
                                        $unidad = 'Unidad';
                                    }

                                    $cantidad = $detalle['cantidad_solicitada'] ?? 0;

                                    $descripcionTecnica = trim(
                                        (string) ($detalle['descripcion_tecnica'] ?? '')
                                    );

                                    $observaciones = trim(
                                        (string) ($detalle['observaciones'] ?? '')
                                    );

                                    $destinosDetalle = is_array($detalle['destinos'] ?? null)
                                        ? $detalle['destinos']
                                        : [];
                                    ?>

                                    <tr>

                                        <!-- ÍTEM -->
                                        <td class="text-center">
                                            <span class="item-numero">
                                                <?= $i + 1 ?>
                                            </span>
                                        </td>

                                        <!-- CÓDIGO UNSPSC -->
                                        <td>
                                            <span class="codigo-unspsc">
                                                <?= $esc($detalle['codigo_unspsc'] ?? '—') ?>
                                            </span>
                                        </td>

                                        <!-- PRODUCTO -->
                                        <td>
                                            <div class="producto-nombre">
                                                <?= $esc($detalle['descripcion'] ?? 'Producto sin nombre') ?>
                                            </div>
                                        </td>

                                        <!-- DESCRIPCIÓN TÉCNICA -->
                                        <td>
                                            <?php if ($descripcionTecnica !== ''): ?>
                                                <div class="dato-tecnico">
                                                    <?= nl2br($esc($descripcionTecnica)) ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="detalle-sin-dato">
                                                    Sin descripción técnica registrada
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- UNIDAD -->
                                        <td>
                                            <span class="unidad-valor">
                                                <?= $esc($unidad) ?>
                                            </span>
                                        </td>

                                        <!-- CANTIDAD -->
                                        <td class="text-center">
                                            <span class="cantidad-valor">
                                                <?= $esc($cantidad) ?>
                                            </span>
                                        </td>

                                        <!-- OBSERVACIONES -->
                                        <td>
                                            <?php if ($observaciones !== ''): ?>
                                                <div class="dato-tecnico">
                                                    <?= nl2br($esc($observaciones)) ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="detalle-sin-dato">
                                                    Sin observaciones
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- CLASIFICACIONES -->
                                        <?php foreach ($clasificaciones as $campo => $nombre): ?>

                                            <td class="clasificacion-col">

                                                <?php if (!empty($detalle[$campo])): ?>

                                                    <span
                                                        class="marca-x"
                                                        title="<?= $esc($nombre) ?>"
                                                    >X</span>

                                                <?php else: ?>

                                                    <span class="marca-vacia">—</span>

                                                <?php endif; ?>

                                            </td>

                                        <?php endforeach; ?>

                                    </tr>

                                    <!-- DESTINOS DEL MATERIAL -->
                                    <?php if (!empty($destinosDetalle)): ?>

                                        <tr class="fila-destinos">

                                            <td colspan="11">

                                                <div class="destinos-titulo">
                                                    <i class="bi bi-geo-alt me-1"></i>
                                                    Destinos y distribución del ítem
                                                    <?= $i + 1 ?>
                                                </div>

                                                <div class="destinos-lista">

                                                    <?php foreach ($destinosDetalle as $destino): ?>

                                                        <?php
                                                        $tipoDestino = (string) (
                                                            $destino['tipo_destino'] ?? ''
                                                        );

                                                        $esFicha = $tipoDestino === 'ficha';

                                                        $numeroFicha = trim(
                                                            (string) ($destino['numero_ficha'] ?? '')
                                                        );

                                                        $cantidadDestino =
                                                            $destino['cantidad_destinada'] ?? 0;

                                                        $observacionDestino = trim(
                                                            (string) ($destino['observaciones'] ?? '')
                                                        );
                                                        ?>

                                                        <span class="destino-etiqueta">

                                                            <?php if ($esFicha): ?>

                                                                <i class="bi bi-people me-1"></i>

                                                                Ficha
                                                                <?= $esc($numeroFicha !== '' ? $numeroFicha : 'Sin número') ?>

                                                            <?php else: ?>

                                                                <i class="bi bi-building me-1"></i>
                                                                Área general

                                                            <?php endif; ?>

                                                            <span class="mx-1">|</span>

                                                            Cantidad:
                                                            <strong>
                                                                <?= $esc($cantidadDestino) ?>
                                                            </strong>

                                                            <?php if ($observacionDestino !== ''): ?>
                                                                <span class="mx-1">|</span>
                                                                <?= $esc($observacionDestino) ?>
                                                            <?php endif; ?>

                                                        </span>

                                                    <?php endforeach; ?>

                                                </div>

                                            </td>

                                        </tr>

                                    <?php endif; ?>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>
        </section>


        <!-- =================================================
             ACCIONES
             ================================================= -->

        <?php if (!empty($puedeEditar) || !empty($puedeEnviar)): ?>

            <div class="acciones-inferiores d-flex flex-wrap justify-content-end gap-2">

                <?php if (!empty($puedeEditar)): ?>

                    <a
                        class="btn btn-sm btn-outline-primary"
                        href="<?= e(url('/materiales/solicitudes/editar?id=' . $idSolicitud)) ?>"
                    >
                        <i class="bi bi-pencil-square me-1"></i>
                        Editar materiales
                    </a>

                <?php endif; ?>

                <?php if (!empty($puedeEnviar)): ?>

                    <form
                        method="POST"
                        action="<?= e(url('/materiales/solicitudes/enviar')) ?>"
                        onsubmit="return confirm('¿Desea enviar esta solicitud para aval?');"
                    >

                        <?= csrfField() ?>

                        <input
                            type="hidden"
                            name="id_solicitud"
                            value="<?= (int) $idSolicitud ?>"
                        >

                        <button class="btn btn-sm btn-primary" type="submit">
                            <i class="bi bi-send me-1"></i>
                            Enviar para aval
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    <?php endif; ?>

</div>
</main>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>
