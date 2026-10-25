<?php
declare(strict_types=1);

require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';

$solicitud  = is_array($solicitud ?? null) ? $solicitud : [];
$detalles   = is_array($detalles ?? null) ? $detalles : [];
$materiales = is_array($materiales ?? null) ? $materiales : [];
$destinos   = is_array($destinos ?? null) ? $destinos : [];
$fichasDestino = is_array($fichasDestino ?? null) ? $fichasDestino : [];
$puedeGestionar = (bool) ($puedeGestionar ?? false);

$idSolicitud = (int) ($solicitud['id'] ?? 0);
$estado = (string) ($solicitud['estado'] ?? '');

$observacionRevision = trim(
    (string) ($solicitud['observacion_revision'] ?? '')
);

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

$unidades = [
    'Unidad', 'Metro', 'Metro Cuadrado', 'Metro Cúbico',
    'Centímetro', 'Centímetro Cuadrado', 'Milímetro',
    'Kilogramo', 'Gramo', 'Miligramo', 'Litro', 'Mililitro',
    'Galón', 'Libra', 'Onza', 'Tonelada', 'Pulgada',
    'Pie', 'Yarda', 'Otro'
];

$clasificaciones = [
    'es_consumo'    => 'Consumo',
    'es_devolutivo' => 'Devolutivo',
    'es_software'   => 'Software',
    'es_epp'        => 'EPP'
];

$estadoTexto = match ($estado) {
    'borrador'       => 'Borrador',
    'pendiente_aval' => 'Pendiente de aval',
    'devuelta'       => 'Devuelta',
    'avalada'        => 'Avalada',
    'rechazada'      => 'Rechazada',
    'cancelada'      => 'Cancelada',
    default          => ucfirst(str_replace('_', ' ', $estado))
};
?>

<style>
.solicitud-editar-wrapper {
    max-width: 1800px;
    margin: 0 auto;
    font-size: .82rem;
    color: #1f2937;
}

.page-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: #1f2937;
}

.page-subtitle {
    color: #6b7280;
    font-size: .82rem;
}

.card-solicitud {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    box-shadow: 0 2px 8px #00000008;
    margin-bottom: 16px;
    overflow: hidden;
}

.card-solicitud > .card-header {
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    padding: 12px 16px;
}

.card-solicitud > .card-header h5 {
    margin: 0;
    font-size: .95rem;
    font-weight: 700;
}

.card-solicitud > .card-body {
    padding: 16px;
}

.dato-label {
    display: block;
    font-size: .70rem;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.texto-ayuda {
    color: #6b7280;
    font-size: .76rem;
}

.cabecera-no-editable {
    height: 100%;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 7px;
    padding: 9px 11px;
    overflow-wrap: anywhere;
}

.form-control,
.form-select {
    font-size: .79rem;
}

.form-control,
.form-select,
.btn {
    border-radius: 6px;
}

.required-mark {
    color: #dc3545;
}

/* TABLA DE PRODUCTOS */
.tabla-materiales-contenedor {
    width: 100%;
    overflow-x: auto;
    border: 1px solid #dfe4ea;
    border-radius: 7px;
}

.tabla-materiales {
    width: 100%;
    min-width: 1450px;
    margin-bottom: 0;
    font-size: .76rem;
    vertical-align: top;
}

.tabla-materiales thead th {
    background: #eef2f7;
    color: #374151;
    font-size: .70rem;
    font-weight: 700;
    text-align: center;
    vertical-align: middle;
    padding: 9px 7px;
    border: 1px solid #dce2e9;
    white-space: normal;
}

.tabla-materiales tbody td {
    padding: 8px 7px;
    border: 1px solid #e5e7eb;
    vertical-align: top;
}

.tabla-materiales tbody tr:nth-child(even) {
    background: #fafbfc;
}

.tabla-materiales tbody tr:hover {
    background: #f1f7ff;
}

.tabla-materiales .col-item {
    width: 42px;
    text-align: center;
}

.tabla-materiales .col-producto {
    min-width: 190px;
    width: 220px;
}

.tabla-materiales .col-cantidad {
    min-width: 90px;
    width: 95px;
}

.tabla-materiales .col-unidad {
    min-width: 135px;
    width: 145px;
}

.tabla-materiales .col-tecnica {
    min-width: 250px;
    width: 290px;
}

.tabla-materiales .col-observaciones {
    min-width: 170px;
    width: 190px;
}

.tabla-materiales .col-clasificacion {
    min-width: 75px;
    width: 82px;
    text-align: center;
}

.tabla-materiales .col-destinos {
    min-width: 170px;
    width: 190px;
}

.tabla-materiales .col-acciones {
    min-width: 55px;
    width: 60px;
    text-align: center;
}

.tabla-materiales textarea {
    min-width: 100%;
    width: 100%;
    min-height: 76px;
    resize: vertical;
    line-height: 1.35;
    font-size: .75rem;
    padding: 7px;
}

.tabla-materiales input[type="number"],
.tabla-materiales select {
    min-width: 70px;
    font-size: .76rem;
    padding: 6px;
}

.producto-nombre {
    font-weight: 700;
    overflow-wrap: anywhere;
    margin-bottom: 5px;
}

.producto-codigo {
    color: #6b7280;
    font-size: .71rem;
    overflow-wrap: anywhere;
}

.item-numero {
    font-weight: 700;
    color: #475569;
}

.check-clasificacion {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}

.check-clasificacion .form-check-input {
    float: none;
    margin: 0;
    width: 15px;
    height: 15px;
    cursor: pointer;
}

.check-clasificacion small {
    font-size: .65rem;
    color: #6b7280;
}

.destino-ficha-opcion { padding-left: 1.5rem; }
.destino-cantidad-wrap { max-width: 125px; }
.destino-cantidad-wrap[hidden] { display: none !important; }
.destinos-compactos {
    font-size: .72rem;
    line-height: 1.45;
    overflow-wrap: anywhere;
}

.destino-linea {
    padding: 4px 0;
    border-bottom: 1px solid #e5e7eb;
}

.destino-linea:last-child {
    border-bottom: 0;
}

.nuevo-producto-panel {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 9px;
    padding: 16px;
}

.nuevo-producto-panel h6 {
    font-size: .91rem;
    font-weight: 700;
}

.nuevo-producto-panel .form-label {
    font-size: .76rem;
    font-weight: 600;
    margin-bottom: 5px;
}

.clasificacion-nueva {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 18px;
    padding: 9px 11px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
}

.clasificacion-nueva .form-check {
    margin: 0;
}

.clasificacion-nueva .form-check-label {
    font-size: .76rem;
}

.acciones-finales {
    position: sticky;
    bottom: 0;
    z-index: 20;
    background: #fffffffa;
    border-top: 1px solid #e5e7eb;
    padding: 12px 0;
    margin-top: 15px;
}

#buscar_material {
    margin-bottom: 5px;
}

#resultado_busqueda_material {
    min-height: 18px;
}

/* BUSCADOR AJAX */
.buscador-select {
    position: relative;
}

.buscador-select-lista {
    position: fixed;
    z-index: 1080;
    margin: 0;
    padding: 4px;
    list-style: none;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    box-shadow: 0 10px 28px rgba(15, 23, 42, .18);
    overflow-y: auto;
    font-size: .78rem;
}

.buscador-select-lista li {
    padding: 7px 9px;
    border-radius: 6px;
    cursor: pointer;
    line-height: 1.3;
}

.buscador-select-lista li:hover,
.buscador-select-lista li.activa {
    background: #e3eeff;
}

.buscador-select-lista li[aria-selected="true"] {
    font-weight: 600;
}

.buscador-select-codigo {
    display: inline-block;
    min-width: 74px;
    margin-right: 6px;
    font-weight: 700;
    color: #1e3a5f;
}

.buscador-select-vacio,
.buscador-select-nota {
    cursor: default !important;
    color: #64748b;
    background: transparent !important;
}

.buscador-select-nota {
    font-size: .72rem;
    text-align: center;
}

@media (max-width: 768px) {
    .solicitud-editar-wrapper {
        font-size: .79rem;
    }

    .page-title {
        font-size: 1.15rem;
    }

    .card-solicitud > .card-body {
        padding: 12px;
    }

    .acciones-finales {
        position: static;
    }
}

@media print {
    .acciones-finales,
    .nuevo-producto-panel,
    .btn,
    .page-subtitle {
        display: none !important;
    }

    .tabla-materiales-contenedor {
        overflow: visible;
    }
}
</style>

<div class="container-fluid py-3">
<div class="solicitud-editar-wrapper">

    <!-- ENCABEZADO -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h2 class="page-title mb-1">
                <i class="bi bi-pencil-square me-2"></i>
                Editar solicitud de materiales
            </h2>

            <p class="page-subtitle mb-0">
                Edite los productos existentes o agregue nuevos materiales.
            </p>
        </div>

        <a
            href="<?= e(url('/materiales/solicitudes/ver?id=' . $idSolicitud)) ?>"
            class="btn btn-sm btn-outline-secondary"
        >
            <i class="bi bi-arrow-left me-1"></i>
            Volver a la solicitud
        </a>
    </div>

    <!-- MENSAJES -->
    <?php if ($flashSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show py-2">
            <?= $esc($flashSuccess) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2">
            <?= $esc($flashError) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!$puedeGestionar): ?>

        <div class="alert alert-warning">
            <i class="bi bi-lock me-2"></i>
            Esta solicitud no puede ser modificada.
        </div>

        <?php if ($estado === 'devuelta'): ?>
            <div class="card card-solicitud">
                <div class="card-header">
                    <h5>Observación de la devolución</h5>
                </div>

                <div class="card-body">
                    <?= $observacionRevision !== ''
                        ? nl2br($esc($observacionRevision))
                        : 'No se registró una observación para esta devolución.' ?>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <!-- INFORMACIÓN GENERAL -->
        <section class="card card-solicitud">
            <div class="card-header">
                <h5>
                    <i class="bi bi-info-circle me-2"></i>
                    Información general de la solicitud
                </h5>
            </div>

            <div class="card-body">

                <?php if ($estado === 'devuelta'): ?>
                    <div class="alert alert-info py-2">
                        <strong>
                            <i class="bi bi-chat-left-text me-1"></i>
                            Solicitud devuelta para corrección
                        </strong>

                        <div class="mt-1">
                            <?= $observacionRevision !== ''
                                ? nl2br($esc($observacionRevision))
                                : 'No se registró una observación.' ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light border py-2 mb-3">
                        Los datos generales son de solo lectura. Puede editar
                        los productos y sus características en la tabla.
                    </div>
                <?php endif; ?>

                <div class="row g-2">

                    <?php
                    $cabecera = [
                        'Número de solicitud' =>
                            $solicitud['numero_solicitud'] ?? 'Sin número',

                        'Estado' => $estadoTexto,

                        'Fecha de creación' =>
                            $fechaLegible((string) ($solicitud['created_at'] ?? '')),

                        'Proceso' => trim(
                            (string) ($solicitud['nombre_proceso'] ?? 'Sin proceso')
                            . ' '
                            . (string) ($solicitud['anio'] ?? '')
                        ),

                        'Área' => $solicitud['nombre_area'] ?? 'Sin área',

                        'Profesional solicitante' => trim(
                            (string) ($solicitud['nombres'] ?? '')
                            . ' '
                            . (string) ($solicitud['apellidos'] ?? '')
                        ) ?: 'Sin información',

                        'Última actualización' =>
                            $fechaLegible((string) ($solicitud['updated_at'] ?? ''))
                    ];

                    foreach ($cabecera as $k => $v):
                    ?>
                        <div class="col-6 col-lg-3">
                            <div class="cabecera-no-editable">
                                <span class="dato-label"><?= $esc($k) ?></span>
                                <div><?= $esc($v) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="col-12 col-lg-6">
                        <label class="dato-label">Título</label>
                        <input
                            class="form-control form-control-sm"
                            readonly
                            value="<?= $esc($solicitud['titulo'] ?? '') ?>"
                        >
                    </div>

                    <div class="col-12 col-lg-6">
                        <label class="dato-label">Justificación</label>
                        <textarea
                            class="form-control form-control-sm"
                            rows="2"
                            readonly
                        ><?= $esc($solicitud['justificacion'] ?? '') ?></textarea>
                    </div>

                </div>
            </div>
        </section>

    <?php if ($puedeGestionar): ?>

        <!-- FORMULARIO PRINCIPAL -->
        <form
            method="POST"
            action="<?= e(url('/materiales/solicitudes/actualizar-borrador')) ?>"
            id="formEditarSolicitud"
        >
            <?= csrfField() ?>

            <input
                type="hidden"
                name="id_solicitud"
                value="<?= $idSolicitud ?>"
            >

            <!-- TABLA DE PRODUCTOS -->
            <section class="card card-solicitud">

                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5>
                        <i class="bi bi-table me-2"></i>
                        Productos y materiales solicitados
                    </h5>

                    <span class="badge text-bg-secondary" id="contadorMateriales">
                        <?= count($detalles) ?>
                        <?= count($detalles) === 1 ? 'producto' : 'productos' ?>
                    </span>
                </div>

                <div class="card-body">

                    <p class="texto-ayuda mb-3">
                        Edite directamente las celdas de la tabla. Los campos
                        marcados con <span class="required-mark">*</span> son obligatorios.
                        Puede desplazarse horizontalmente para ver todas las columnas.
                    </p>

                    <div class="tabla-materiales-contenedor">
                        <table class="table table-sm table-hover tabla-materiales">
                            <thead>
                                <tr>
                                    <th class="col-item">Ítem</th>
                                    <th class="col-producto">Código UNSPSC / Producto</th>
                                    <th class="col-cantidad">Cantidad <span class="required-mark">*</span></th>
                                    <th class="col-unidad">Unidad de medida <span class="required-mark">*</span></th>
                                    <th class="col-tecnica">Descripción técnica requerida <span class="required-mark">*</span></th>
                                    <th class="col-observaciones">Observaciones</th>
                                    <th class="col-clasificacion">Consumo</th>
                                    <th class="col-clasificacion">Devolutivo</th>
                                    <th class="col-clasificacion">Software</th>
                                    <th class="col-clasificacion">EPP</th>
                                    <th class="col-destinos">Destinos del material</th>
                                    <th class="col-acciones">Acciones</th>
                                </tr>
                            </thead>

                            <tbody id="listaMateriales">

                            <?php foreach ($detalles as $i => $d):

                                $idDet = (int) ($d['id'] ?? 0);
                                $idMat = (int) ($d['id_material'] ?? 0);

                                $unidad = (string) ($d['unidad_medida'] ?? 'Unidad');

                                if (!in_array($unidad, $unidades, true)) {
                                    $unidad = 'Unidad';
                                }

                                $destinosFila = $destinos[$idDet] ?? ($d['destinos'] ?? []);

if (!is_array($destinosFila)) {
    $destinosFila = [];
}

// Asegurar que solo se procesen destinos estructurados.
$destinosFila = array_values(array_filter(
    $destinosFila,
    static fn($dest): bool =>
        is_array($dest)
        && (
            isset($dest['tipo_destino'])
            || isset($dest['cantidad_destinada'])
            || isset($dest['numero_ficha'])
        )
));
// Si el instructor solo tiene una ficha autorizada, asignarla automáticamente.
if (count($fichasDestino) === 1 && empty($destinosFila)) {
    $destinosFila[] = [
        'tipo_destino' => 'ficha',
        'id_ficha' => (int) ($fichasDestino[0]['id'] ?? 0),
        'cantidad_destinada' => max(1, (int) ($d['cantidad_solicitada'] ?? 1)),
        'observaciones' => ''
    ];
}
                            ?>

                                <tr class="material-row">
                                    <td class="col-item">
                                        <input
                                            type="hidden"
                                            name="materiales[<?= $i ?>][id_detalle]"
                                            value="<?= $idDet ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="materiales[<?= $i ?>][id_material]"
                                            value="<?= $idMat ?>"
                                        >

                                        <span class="item-numero"><?= $i + 1 ?></span>
                                    </td>

                                    <td class="col-producto">
                                        <div class="producto-nombre">
                                            <?= $esc($d['descripcion'] ?? 'Producto sin nombre') ?>
                                        </div>

                                        <div class="producto-codigo">
                                            Código UNSPSC:<br>
                                            <strong><?= $esc($d['codigo_unspsc'] ?? '—') ?></strong>
                                        </div>
                                    </td>

                                    <td class="col-cantidad">
                                        <input
                                            type="number"
                                            class="form-control form-control-sm"
                                            name="materiales[<?= $i ?>][cantidad]"
                                            min="1"
                                            step="1"
                                            value="<?= $esc(max(1, (int) ($d['cantidad_solicitada'] ?? 1))) ?>"
                                            required
                                        >
                                    </td>

                                    <td class="col-unidad">
                                        <select
                                            class="form-select form-select-sm"
                                            name="materiales[<?= $i ?>][unidad_medida]"
                                            required
                                        >
                                            <?php foreach ($unidades as $u): ?>
                                                <option
                                                    value="<?= $esc($u) ?>"
                                                    <?= $unidad === $u ? 'selected' : '' ?>
                                                >
                                                    <?= $esc($u) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>

                                    <td class="col-tecnica">
                                        <textarea
                                            class="form-control form-control-sm"
                                            name="materiales[<?= $i ?>][descripcion_tecnica]"
                                            rows="4"
                                            maxlength="2000"
                                            required
                                            placeholder="Características, material, color, modelo, presentación..."
                                        ><?= $esc($d['descripcion_tecnica'] ?? '') ?></textarea>
                                    </td>

                                    <td class="col-observaciones">
                                        <textarea
                                            class="form-control form-control-sm"
                                            name="materiales[<?= $i ?>][observaciones]"
                                            rows="4"
                                            maxlength="1000"
                                            placeholder="Observaciones adicionales"
                                        ><?= $esc($d['observaciones'] ?? '') ?></textarea>
                                    </td>

                                    <?php foreach ($clasificaciones as $campo => $nombre): ?>
                                        <td class="col-clasificacion">
                                            <div class="check-clasificacion">
                                                <input
                                                    type="hidden"
                                                    name="materiales[<?= $i ?>][<?= $campo ?>]"
                                                    value="0"
                                                >

                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    id="<?= $campo ?>_<?= $i ?>"
                                                    name="materiales[<?= $i ?>][<?= $campo ?>]"
                                                    value="1"
                                                    <?= !empty($d[$campo]) ? 'checked' : '' ?>
                                                    aria-label="<?= $esc($nombre) ?>"
                                                >

                                                <small>
                                                    <?= !empty($d[$campo]) ? 'Sí' : 'No' ?>
                                                </small>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>

                                    <td class="col-destinos">
                                        <?php
                                        $cantidadesDestinoFila = [];
                                        foreach ($destinosFila as $destinoExistente) {
                                            $idDestinoExistente = (int) ($destinoExistente['id_ficha'] ?? 0);
                                            if ($idDestinoExistente > 0) {
                                                $cantidadesDestinoFila[$idDestinoExistente] = (float) ($destinoExistente['cantidad_destinada'] ?? 0);
                                            }
                                        }
                                        if (!$cantidadesDestinoFila && count($fichasDestino) === 1) {
                                            $cantidadesDestinoFila[(int) $fichasDestino[0]['id']] = (float) ($d['cantidad_solicitada'] ?? 1);
                                        }
                                        ?>
                                        <div class="destinos-compactos destino-lista" data-indice="<?= $i ?>">
                                            <input type="hidden" name="materiales[<?= $i ?>][destinos][__vacio][cantidad_destinada]" value="0">
                                            <?php foreach ($fichasDestino as $fichaDestino): ?>
                                                <?php
                                                $idFichaDestino = (int) ($fichaDestino['id'] ?? 0);
                                                $seleccionada = isset($cantidadesDestinoFila[$idFichaDestino]) || count($fichasDestino) === 1;
                                                $cantidadFicha = count($fichasDestino) === 1
                                                    ? (float) ($d['cantidad_solicitada'] ?? 1)
                                                    : (float) ($cantidadesDestinoFila[$idFichaDestino] ?? 0);
                                                $mostrarCantidad = $seleccionada && count($cantidadesDestinoFila) > 1;
                                                ?>
                                                <div class="form-check destino-ficha-opcion mb-1" data-id-ficha="<?= $idFichaDestino ?>">
                                                    <input class="form-check-input destino-ficha-check" type="checkbox" value="<?= $idFichaDestino ?>" <?= $seleccionada ? 'checked' : '' ?> <?= count($fichasDestino) === 1 ? 'disabled' : '' ?> id="destino_<?= $i ?>_<?= $idFichaDestino ?>">
                                                    <label class="form-check-label" for="destino_<?= $i ?>_<?= $idFichaDestino ?>">
                                                        <?= $esc($fichaDestino['numero_ficha'] ?? '') ?><?= !empty($fichaDestino['nombre_programa']) ? ' - ' . $esc($fichaDestino['nombre_programa']) : '' ?>
                                                    </label>
                                                    <input type="hidden" class="destino-tipo" name="materiales[<?= $i ?>][destinos][<?= $idFichaDestino ?>][tipo_destino]" value="ficha" <?= $seleccionada ? '' : 'disabled' ?>>
                                                    <input type="hidden" class="destino-ficha-select" name="materiales[<?= $i ?>][destinos][<?= $idFichaDestino ?>][id_ficha]" value="<?= $idFichaDestino ?>" <?= $seleccionada ? '' : 'disabled' ?>>
                                                    <div class="destino-cantidad-wrap ms-4 mt-1" <?= $mostrarCantidad ? '' : 'hidden' ?>>
                                                        <input type="number" min="1" step="1" class="form-control form-control-sm destino-cantidad" name="materiales[<?= $i ?>][destinos][<?= $idFichaDestino ?>][cantidad_destinada]" value="<?= $cantidadFicha > 0 ? $esc((string) $cantidadFicha) : '' ?>" placeholder="Cantidad para esta ficha" <?= $seleccionada ? '' : 'disabled' ?>>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if (count($fichasDestino) === 1): ?>
                                                <div class="small text-muted">Recibe automáticamente la cantidad total.</div>
                                            <?php else: ?>
                                                <div class="small text-muted mt-1">Marque las fichas destino. Si marca varias, indique la cantidad para cada una.</div>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="col-acciones">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger btn-eliminar-material"
                                            title="Eliminar producto"
                                            aria-label="Eliminar producto"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>

                    <div class="texto-ayuda mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Las clasificaciones se marcan con una casilla. Puede asignar cada producto a varias fichas; la suma de las cantidades destinadas no puede superar la cantidad solicitada.
                    </div>

                    <!-- AGREGAR PRODUCTO -->
                    <div class="nuevo-producto-panel mt-4">

                        <h6>
                            <i class="bi bi-plus-circle me-2"></i>
                            Agregar un nuevo producto
                        </h6>

                        <p class="texto-ayuda mb-3">
                            Busque por código UNSPSC o descripción, seleccione el producto
                            y complete sus datos antes de agregarlo a la tabla.
                        </p>

                        <div class="row g-3">

                            <div class="col-12 col-xl-5">
                                <label class="form-label" for="buscar_material">
                                    Buscar producto por código UNSPSC o descripción
                                </label>

                                <div class="buscador-select">
                                    <input
                                        type="search"
                                        class="form-control form-control-sm"
                                        id="buscar_material"
                                        placeholder="Escriba al menos dos caracteres..."
                                        autocomplete="off"
                                        role="combobox"
                                        aria-autocomplete="list"
                                        aria-expanded="false"
                                        aria-controls="lista_material"
                                    >

                                    <ul
                                        class="buscador-select-lista d-none"
                                        id="lista_material"
                                        role="listbox"
                                        aria-label="Productos del catálogo"
                                    ></ul>
                                </div>

                                <small
                                    id="resultado_busqueda_material"
                                    class="texto-ayuda d-block mb-2"
                                    aria-live="polite"
                                >
                                    Escriba al menos dos caracteres para buscar en el catálogo.
                                </small>

                                <!-- SELECT OCULTO: CONTIENE SOLO EL PRODUCTO SELECCIONADO -->
                                <select
                                    class="d-none"
                                    id="nuevo_material"
                                    tabindex="-1"
                                    aria-hidden="true"
                                >
                                    <option value="">Seleccione un producto...</option>
                                </select>
                            </div>

                            <div class="col-6 col-md-3 col-xl-2">
                                <label class="form-label" for="nueva_cantidad">
                                    Cantidad <span class="required-mark">*</span>
                                </label>

                                <input
                                    class="form-control form-control-sm"
                                    type="number"
                                    id="nueva_cantidad"
                                    min="1"
                                    step="1"
                                    value="1"
                                >
                            </div>

                            <div class="col-6 col-md-4 col-xl-2">
                                <label class="form-label" for="nueva_unidad">
                                    Unidad de medida
                                </label>

                                <select
                                    class="form-select form-select-sm"
                                    id="nueva_unidad"
                                >
                                    <?php foreach ($unidades as $u): ?>
                                        <option value="<?= $esc($u) ?>">
                                            <?= $esc($u) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-xl-3">
                                <label class="form-label" for="nueva_observacion">
                                    Observaciones
                                </label>

                                <input
                                    class="form-control form-control-sm"
                                    id="nueva_observacion"
                                    maxlength="1000"
                                    placeholder="Opcional"
                                >
                            </div>

                            <div class="col-12">
                                <label
                                    class="form-label"
                                    for="nueva_descripcion_tecnica"
                                >
                                    Descripción técnica <span class="required-mark">*</span>
                                </label>

                                <textarea
                                    class="form-control form-control-sm"
                                    id="nueva_descripcion_tecnica"
                                    rows="3"
                                    maxlength="2000"
                                    placeholder="Especifique las características técnicas, material, color, modelo o presentación."
                                ></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label">
                                    Clasificación del producto
                                </label>

                                <div class="clasificacion-nueva">
                                    <?php foreach ($clasificaciones as $campo => $nombre): ?>
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                id="nuevo_<?= $campo ?>"
                                            >

                                            <label
                                                class="form-check-label"
                                                for="nuevo_<?= $campo ?>"
                                            >
                                                <?= $esc($nombre) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="col-12">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    id="btnAgregarMaterial"
                                >
                                    <i class="bi bi-plus-lg me-1"></i>
                                    Agregar producto a la tabla
                                </button>
                            </div>

                        </div>
                    </div>

                </div>
            </section>

            <!-- BOTONES FINALES -->
            <div class="acciones-finales">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

                    <a
                        href="<?= e(url('/materiales/solicitudes/ver?id=' . $idSolicitud)) ?>"
                        class="btn btn-sm btn-outline-secondary"
                    >
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="btn btn-sm btn-primary"
                    >
                        <i class="bi bi-save me-1"></i>
                        Guardar cambios
                    </button>

                </div>
            </div>

        </form>

    <?php endif; ?>

</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formEditarSolicitud');

    if (!form) {
        return;
    }

    const sel = document.getElementById('nuevo_material');
    const buscadorMaterial = document.getElementById('buscar_material');
    const resultadoBusqueda = document.getElementById('resultado_busqueda_material');
    const listaMaterial = document.getElementById('lista_material');

    const qty = document.getElementById('nueva_cantidad');
    const unit = document.getElementById('nueva_unidad');
    const tech = document.getElementById('nueva_descripcion_tecnica');
    const obs = document.getElementById('nueva_observacion');
    const list = document.getElementById('listaMateriales');
    const contador = document.getElementById('contadorMateriales');

    /*
     * IMPORTANTE:
     * Esta ruta debe estar conectada en el Router al método
     * buscarMateriales() de MaterialesSolicitudController.
     */
    const URL_BUSCAR_MATERIALES =
        <?= json_encode(
            url('/materiales/solicitudes/buscar-materiales'),
            JSON_HEX_TAG
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
            | JSON_HEX_AMP
        ) ?>;

    const flags = [
        'es_consumo',
        'es_devolutivo',
        'es_software',
        'es_epp'
    ];

    const labels = {
        es_consumo: 'Consumo',
        es_devolutivo: 'Devolutivo',
        es_software: 'Software',
        es_epp: 'EPP'
    };

    const units = <?= json_encode(
        $unidades,
        JSON_UNESCAPED_UNICODE
        | JSON_HEX_TAG
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
        | JSON_HEX_AMP
    ) ?>;

    const fichasDestino = <?= json_encode($fichasDestino, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    const esc = value => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    function obtenerFilas() {
        return Array.from(list.querySelectorAll('.material-row'));
    }

    function configurarDestinosFila(fila) {
        const contenedor = fila.querySelector('.destino-lista');
        if (!contenedor) return;
        const checks = Array.from(contenedor.querySelectorAll('.destino-ficha-check'));
        const seleccionadas = checks.filter(c => c.checked);
        const cantidadTotal = parseFloat((fila.querySelector('input[name$="[cantidad]"]') || {}).value || '0') || 0;
        checks.forEach(check => {
            const bloque = check.closest('.destino-ficha-opcion');
            const tipo = bloque.querySelector('.destino-tipo');
            const ficha = bloque.querySelector('.destino-ficha-select');
            const campo = bloque.querySelector('.destino-cantidad');
            const wrapper = bloque.querySelector('.destino-cantidad-wrap');
            const activa = check.checked;
            const distribuir = seleccionadas.length > 1;
            if (tipo) tipo.disabled = !activa;
            if (ficha) ficha.disabled = !activa;
            if (campo) {
                campo.disabled = !activa;
                if (!distribuir && activa) campo.value = String(cantidadTotal);
                if (!activa) campo.value = '';
            }
            if (wrapper) wrapper.hidden = !(activa && distribuir);
        });
    }

    list.addEventListener('change', event => {
        if (event.target.matches('.destino-ficha-check')) {
            configurarDestinosFila(event.target.closest('.material-row'));
        }
    });
    list.addEventListener('input', event => {
        if (event.target.matches('input[name$="[cantidad]"]')) {
            configurarDestinosFila(event.target.closest('.material-row'));
        }
    });
    list.querySelectorAll('.material-row').forEach(configurarDestinosFila);

    function contarMateriales() {
        const n = obtenerFilas().length;

        if (contador) {
            contador.textContent =
                n + (n === 1 ? ' producto' : ' productos');
        }

        obtenerFilas().forEach((fila, indice) => {
            const numero = fila.querySelector('.item-numero');

            if (numero) {
                numero.textContent = String(indice + 1);
            }
        });
    }

    function siguienteIndice() {
        const indices = obtenerFilas().map(fila => {
            const input = fila.querySelector('input[name$="[id_material]"]');
            const match = input?.name.match(/materiales\[(\d+)\]/);

            return match ? Number(match[1]) : -1;
        });

        let i = 0;

        while (indices.includes(i)) {
            i++;
        }

        return i;
    }

    const MAX_RESULTADOS = 20;
    const MIN_CARACTERES = 2;
    const DEMORA_BUSQUEDA = 300;

    let seleccionId = '';
    let etiquetaSeleccion = '';
    let coincidencias = [];
    let indiceActivo = -1;
    let temporizadorBusqueda = null;
    let controladorPeticion = null;
    let numeroPeticion = 0;
    let ultimaConsulta = '';

    const normalizar = texto => String(texto || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase()
        .trim();

    const listaAbierta = () =>
        !listaMaterial.classList.contains('d-none');

    function posicionarLista() {
        const caja = buscadorMaterial.getBoundingClientRect();
        const abajo = window.innerHeight - caja.bottom - 10;
        const arriba = caja.top - 10;
        const abrirHaciaAbajo = abajo >= 180 || abajo >= arriba;

        listaMaterial.style.left = caja.left + 'px';
        listaMaterial.style.width = caja.width + 'px';

        if (abrirHaciaAbajo) {
            listaMaterial.style.top = (caja.bottom + 2) + 'px';
            listaMaterial.style.bottom = 'auto';
            listaMaterial.style.maxHeight =
                Math.max(120, Math.min(280, abajo)) + 'px';
        } else {
            listaMaterial.style.top = 'auto';
            listaMaterial.style.bottom =
                (window.innerHeight - caja.top + 2) + 'px';
            listaMaterial.style.maxHeight =
                Math.max(120, Math.min(280, arriba)) + 'px';
        }
    }

    function abrirLista() {
        listaMaterial.classList.remove('d-none');
        buscadorMaterial.setAttribute('aria-expanded', 'true');
        posicionarLista();
    }

    function cerrarLista() {
        listaMaterial.classList.add('d-none');
        buscadorMaterial.setAttribute('aria-expanded', 'false');
        buscadorMaterial.removeAttribute('aria-activedescendant');
        indiceActivo = -1;
    }

    function mostrarMensajeLista(mensaje, clase = 'buscador-select-vacio') {
        listaMaterial.textContent = '';

        const li = document.createElement('li');
        li.className = clase;
        li.setAttribute('role', 'presentation');
        li.textContent = mensaje;

        listaMaterial.appendChild(li);
        abrirLista();
    }

    function marcarActivo(indice) {
        const opciones = listaMaterial.querySelectorAll('li[data-id]');

        opciones.forEach(li => li.classList.remove('activa'));

        if (indice < 0 || indice >= opciones.length) {
            indiceActivo = -1;
            buscadorMaterial.removeAttribute('aria-activedescendant');
            return;
        }

        indiceActivo = indice;
        opciones[indice].classList.add('activa');
        opciones[indice].scrollIntoView({ block: 'nearest' });

        buscadorMaterial.setAttribute(
            'aria-activedescendant',
            opciones[indice].id
        );
    }

    function actualizarResultado(mensaje) {
        if (resultadoBusqueda) {
            resultadoBusqueda.textContent = mensaje;
        }
    }

    function limpiarSeleccion() {
        sel.replaceChildren(new Option('Seleccione un producto...', ''));
        sel.value = '';
        seleccionId = '';
        etiquetaSeleccion = '';
    }

    function cancelarPeticionAnterior() {
        if (controladorPeticion) {
            controladorPeticion.abort();
            controladorPeticion = null;
        }

        numeroPeticion++;
    }

    function renderizarResultados(materiales, consulta, peticionActual) {
        if (peticionActual !== numeroPeticion) {
            return;
        }

        coincidencias = materiales;
        listaMaterial.textContent = '';
        indiceActivo = -1;

        if (materiales.length === 0) {
            mostrarMensajeLista(
                'No se encontraron productos. Intente con otro código o descripción.'
            );

            actualizarResultado('No se encontraron productos.');
            return;
        }

        materiales.forEach((item, indice) => {
            const li = document.createElement('li');
            const codigo = document.createElement('span');
            const descripcion = document.createElement('span');

            li.id = 'opcion_material_' + item.id;
            li.setAttribute('role', 'option');
            li.setAttribute('aria-selected', 'false');
            li.dataset.id = String(item.id);
            li.dataset.indice = String(indice);

            codigo.className = 'buscador-select-codigo';
            codigo.textContent = item.codigo || 'Sin código';
            descripcion.textContent = item.descripcion;

            li.appendChild(codigo);
            li.appendChild(descripcion);
            listaMaterial.appendChild(li);
        });

        const nota = document.createElement('li');
        nota.className = 'buscador-select-nota';
        nota.setAttribute('role', 'presentation');

        nota.textContent = materiales.length === MAX_RESULTADOS
            ? 'Se muestran hasta 20 coincidencias. Escriba más caracteres para afinar la búsqueda.'
            : materiales.length + (
                materiales.length === 1
                    ? ' producto encontrado.'
                    : ' productos encontrados.'
            );

        listaMaterial.appendChild(nota);

        abrirLista();

        actualizarResultado(
            materiales.length === MAX_RESULTADOS
                ? 'Se muestran 20 coincidencias para «' + consulta + '». Escriba más caracteres para afinar.'
                : materiales.length + (
                    materiales.length === 1
                        ? ' producto encontrado.'
                        : ' productos encontrados.'
                )
        );
    }

    async function consultarCatalogo(termino) {
        const consulta = termino.trim();

        cancelarPeticionAnterior();
        const peticionActual = numeroPeticion;

        if (consulta.length < MIN_CARACTERES) {
            coincidencias = [];
            listaMaterial.textContent = '';

            if (consulta.length === 0) {
                cerrarLista();
                actualizarResultado(
                    'Escriba al menos dos caracteres para buscar en el catálogo.'
                );
            } else {
                mostrarMensajeLista(
                    'Escriba al menos dos caracteres para buscar.'
                );

                actualizarResultado(
                    'Falta un carácter para iniciar la búsqueda.'
                );
            }

            return;
        }

        if (normalizar(consulta) === normalizar(ultimaConsulta)) {
            return;
        }

        ultimaConsulta = consulta;

        if (controladorPeticion) {
            controladorPeticion.abort();
        }

        controladorPeticion = new AbortController();

        mostrarMensajeLista('Buscando productos en el catálogo...');
        actualizarResultado('Consultando el catálogo...');

        try {
            const parametros = new URLSearchParams({
                buscar: consulta
            });

            const respuesta = await fetch(
                URL_BUSCAR_MATERIALES + '?' + parametros.toString(),
                {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    signal: controladorPeticion.signal
                }
            );

            if (!respuesta.ok) {
                throw new Error(
                    'El servidor respondió con HTTP ' + respuesta.status
                );
            }

            const datos = await respuesta.json();

            if (peticionActual !== numeroPeticion) {
                return;
            }

            if (!datos || datos.ok !== true || !Array.isArray(datos.materiales)) {
                throw new Error('La respuesta del catálogo no tiene el formato esperado.');
            }

            const resultados = datos.materiales
                .filter(item => Number(item.id) > 0)
                .map(item => ({
                    id: String(item.id),
                    codigo: String(item.codigo_unspsc ?? ''),
                    descripcion: String(item.descripcion ?? '')
                }));

            renderizarResultados(
                resultados.slice(0, MAX_RESULTADOS),
                consulta,
                peticionActual
            );

        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            console.error('Error consultando el catálogo:', error);

            if (peticionActual !== numeroPeticion) {
                return;
            }

            coincidencias = [];

            mostrarMensajeLista(
                'No fue posible consultar el catálogo. Intente nuevamente.'
            );

            actualizarResultado(
                'Error al consultar el catálogo. Verifique la conexión e intente de nuevo.'
            );

        } finally {
            if (peticionActual === numeroPeticion) {
                controladorPeticion = null;
            }
        }
    }

    function programarBusqueda() {
        clearTimeout(temporizadorBusqueda);

        ultimaConsulta = '';

        if (
            seleccionId !== '' &&
            buscadorMaterial.value !== etiquetaSeleccion
        ) {
            limpiarSeleccion();
        }

        temporizadorBusqueda = setTimeout(() => {
            consultarCatalogo(buscadorMaterial.value);
        }, DEMORA_BUSQUEDA);
    }

    function seleccionarMaterial(id) {
        const item = coincidencias.find(
            material => material.id === String(id)
        );

        if (!item) {
            return;
        }

        limpiarSeleccion();

        const opcion = new Option(
            item.codigo + ' — ' + item.descripcion,
            item.id,
            true,
            true
        );

        opcion.dataset.codigo = item.codigo;
        opcion.dataset.descripcion = item.descripcion;

        sel.add(opcion);
        sel.value = item.id;

        seleccionId = item.id;
        etiquetaSeleccion = item.codigo + ' — ' + item.descripcion;
        buscadorMaterial.value = etiquetaSeleccion;

        listaMaterial.querySelectorAll('li[data-id]').forEach(li => {
            li.setAttribute(
                'aria-selected',
                li.dataset.id === seleccionId ? 'true' : 'false'
            );
        });

        cerrarLista();

        actualizarResultado(
            'Producto seleccionado: ' +
            (item.codigo || 'Sin código') +
            '.'
        );

        sel.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function reiniciarBuscadorMaterial() {
        clearTimeout(temporizadorBusqueda);
        cancelarPeticionAnterior();

        coincidencias = [];
        ultimaConsulta = '';

        limpiarSeleccion();
        buscadorMaterial.value = '';

        listaMaterial.textContent = '';
        cerrarLista();

        actualizarResultado(
            'Escriba al menos dos caracteres para buscar en el catálogo.'
        );
    }

    buscadorMaterial.addEventListener('input', programarBusqueda);

    buscadorMaterial.addEventListener('focus', () => {
        if (seleccionId !== '') {
            buscadorMaterial.select();
            return;
        }

        if (buscadorMaterial.value.trim().length >= MIN_CARACTERES) {
            consultarCatalogo(buscadorMaterial.value);
        }
    });

    buscadorMaterial.addEventListener('keydown', event => {
        const opciones = listaMaterial.querySelectorAll('li[data-id]');
        const total = opciones.length;

        if (event.key === 'ArrowDown') {
            event.preventDefault();

            if (!listaAbierta()) {
                abrirLista();
            }

            if (total > 0) {
                marcarActivo((indiceActivo + 1) % total);
            }

        } else if (event.key === 'ArrowUp') {
            event.preventDefault();

            if (total > 0) {
                marcarActivo(
                    indiceActivo <= 0 ? total - 1 : indiceActivo - 1
                );
            }

        } else if (event.key === 'Enter') {
            if (listaAbierta() && indiceActivo >= 0) {
                event.preventDefault();

                const li = listaMaterial.querySelectorAll('li[data-id]')[indiceActivo];

                if (li) {
                    seleccionarMaterial(li.dataset.id);
                }
            }

        } else if (event.key === 'Escape') {
            if (listaAbierta()) {
                event.preventDefault();
                cerrarLista();
            }

        } else if (event.key === 'Tab') {
            cerrarLista();
        }
    });

    /*
     * Mousedown evita que el campo pierda el foco antes de seleccionar.
     */
    listaMaterial.addEventListener('mousedown', event => {
        event.preventDefault();

        const li = event.target.closest('li[data-id]');

        if (li) {
            seleccionarMaterial(li.dataset.id);
        }
    });

    document.addEventListener('click', event => {
        if (
            !buscadorMaterial.contains(event.target) &&
            !listaMaterial.contains(event.target)
        ) {
            cerrarLista();
        }
    });

    window.addEventListener('resize', () => {
        if (listaAbierta()) {
            posicionarLista();
        }
    }, { passive: true });

    window.addEventListener('scroll', () => {
        if (listaAbierta()) {
            posicionarLista();
        }
    }, { passive: true, capture: true });

    // Actualizar el texto Sí/No junto a las casillas.
    list.addEventListener('change', event => {
        const checkbox = event.target;

        if (
            checkbox instanceof HTMLInputElement &&
            checkbox.type === 'checkbox' &&
            checkbox.name.includes('[')
        ) {
            const contenedor = checkbox.closest('.check-clasificacion');
            const texto = contenedor?.querySelector('small');

            if (texto) {
                texto.textContent = checkbox.checked ? 'Sí' : 'No';
            }
        }
    });

    // Eliminar producto de la tabla.
    list.addEventListener('click', event => {
        const boton = event.target.closest('.btn-eliminar-material');

        if (!boton) {
            return;
        }

        if (!confirm('¿Eliminar este producto de la solicitud?')) {
            return;
        }

        boton.closest('.material-row')?.remove();
        contarMateriales();
    });

    // Agregar producto seleccionado.
    document.getElementById('btnAgregarMaterial')
        ?.addEventListener('click', () => {

            const id = parseInt(sel.value || '0', 10);

            if (!id || !seleccionId || String(id) !== seleccionId) {
                alert('Busque y seleccione un producto del catálogo.');
                buscadorMaterial.focus();
                return;
            }

            if (
                !qty.value ||
                !Number.isInteger(Number(qty.value)) ||
                Number(qty.value) < 1
            ) {
                alert('Ingrese una cantidad entera mayor que cero.');
                qty.focus();
                return;
            }

            if (!tech.value.trim()) {
                alert('La descripción técnica es obligatoria.');
                tech.focus();
                return;
            }

            

            const indice = siguienteIndice();
            const opcion = sel.options[sel.selectedIndex];

            const codigo = opcion.dataset.codigo || '';
            const descripcion = opcion.dataset.descripcion ||
                opcion.textContent.trim();

            const cantidad = Number(qty.value);
            const unidadSeleccionada = unit.value;
            const descripcionTecnica = tech.value.trim();
            const observaciones = obs.value.trim();

            let clasificacionesHTML = '';

            for (const campo of flags) {
                const marcado =
                    document.getElementById('nuevo_' + campo).checked;

                clasificacionesHTML += `
                    <td class="col-clasificacion">
                        <div class="check-clasificacion">
                            <input
                                type="hidden"
                                name="materiales[${indice}][${campo}]"
                                value="0"
                            >

                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="${campo}_${indice}"
                                name="materiales[${indice}][${campo}]"
                                value="1"
                                ${marcado ? 'checked' : ''}
                                aria-label="${esc(labels[campo])}"
                            >

                            <small>${marcado ? 'Sí' : 'No'}</small>
                        </div>
                    </td>
                `;
            }

            let opcionesUnidad = '';

            for (const u of units) {
                opcionesUnidad += `
                    <option
                        value="${esc(u)}"
                        ${u === unidadSeleccionada ? 'selected' : ''}
                    >${esc(u)}</option>
                `;
            }

            const fila = document.createElement('tr');
            fila.className = 'material-row';

            fila.innerHTML = `
                <td class="col-item">
                    <input
                        type="hidden"
                        name="materiales[${indice}][id_detalle]"
                        value="0"
                    >

                    <input
                        type="hidden"
                        name="materiales[${indice}][id_material]"
                        value="${id}"
                    >

                    <span class="item-numero"></span>
                </td>

                <td class="col-producto">
                    <div class="producto-nombre">${esc(descripcion)}</div>

                    <div class="producto-codigo">
                        Código UNSPSC:<br>
                        <strong>${esc(codigo)}</strong>
                    </div>

                    <span class="badge text-bg-success mt-1">Nuevo</span>
                </td>

                <td class="col-cantidad">
                    <input
                        type="number"
                        class="form-control form-control-sm"
                        name="materiales[${indice}][cantidad]"
                        min="1"
                        step="1"
                        value="${cantidad}"
                        required
                    >
                </td>

                <td class="col-unidad">
                    <select
                        class="form-select form-select-sm"
                        name="materiales[${indice}][unidad_medida]"
                        required
                    >${opcionesUnidad}</select>
                </td>

                <td class="col-tecnica">
                    <textarea
                        class="form-control form-control-sm"
                        name="materiales[${indice}][descripcion_tecnica]"
                        rows="4"
                        maxlength="2000"
                        required
                    >${esc(descripcionTecnica)}</textarea>
                </td>

                <td class="col-observaciones">
                    <textarea
                        class="form-control form-control-sm"
                        name="materiales[${indice}][observaciones]"
                        rows="4"
                        maxlength="1000"
                    >${esc(observaciones)}</textarea>
                </td>

                ${clasificacionesHTML}

                <td class="col-destinos">
                    <div class="destinos-compactos destino-lista" data-indice="${indice}">
                        <input type="hidden" name="materiales[${indice}][destinos][__vacio][cantidad_destinada]" value="0">
                        ${fichasDestino.map(f => `
                            <div class="form-check destino-ficha-opcion mb-1" data-id-ficha="${Number(f.id)}">
                                <input class="form-check-input destino-ficha-check" type="checkbox" value="${Number(f.id)}" ${fichasDestino.length === 1 ? 'checked disabled' : ''} id="destino_${indice}_${Number(f.id)}">
                                <label class="form-check-label" for="destino_${indice}_${Number(f.id)}">${esc(f.numero_ficha)}${f.nombre_programa ? ' - ' + esc(f.nombre_programa) : ''}</label>
                                <input type="hidden" class="destino-tipo" name="materiales[${indice}][destinos][${Number(f.id)}][tipo_destino]" value="ficha" ${fichasDestino.length === 1 ? '' : 'disabled'}>
                                <input type="hidden" class="destino-ficha-select" name="materiales[${indice}][destinos][${Number(f.id)}][id_ficha]" value="${Number(f.id)}" ${fichasDestino.length === 1 ? '' : 'disabled'}>
                                <div class="destino-cantidad-wrap ms-4 mt-1" hidden>
                                    <input type="number" min="1" step="1" class="form-control form-control-sm destino-cantidad" name="materiales[${indice}][destinos][${Number(f.id)}][cantidad_destinada]" value="${fichasDestino.length === 1 ? cantidad : ''}" placeholder="Cantidad para esta ficha" ${fichasDestino.length === 1 ? '' : 'disabled'}>
                                </div>
                            </div>
                        `).join('')}
                        ${fichasDestino.length === 1 ? '<div class="small text-muted">Recibe automáticamente la cantidad total.</div>' : '<div class="small text-muted mt-1">Marque las fichas destino. Si marca varias, indique la cantidad para cada una.</div>'}
                    </div>
                </td>

                <td class="col-acciones">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger btn-eliminar-material"
                        title="Eliminar producto"
                        aria-label="Eliminar producto"
                    >
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;

            list.appendChild(fila);

            // Limpiar campos auxiliares.
            reiniciarBuscadorMaterial();

            qty.value = '1';
            unit.value = 'Unidad';
            tech.value = '';
            obs.value = '';

            flags.forEach(campo => {
                document.getElementById('nuevo_' + campo).checked = false;
            });

            contarMateriales();

            fila.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        });

    // Validación antes de guardar.
    form.addEventListener('submit', event => {
        const filas = obtenerFilas();

        if (filas.length === 0) {
            event.preventDefault();
            alert('Debe conservar al menos un producto en la solicitud.');
            return;
        }

        for (let i = 0; i < filas.length; i++) {
            const fila = filas[i];

            const cantidad = fila.querySelector(
                'input[name$="[cantidad]"]'
            );

            const descripcionTecnica = fila.querySelector(
                'textarea[name$="[descripcion_tecnica]"]'
            );

            const unidad = fila.querySelector(
                'select[name$="[unidad_medida]"]'
            );

            if (
                !cantidad ||
                !Number.isInteger(Number(cantidad.value)) ||
                Number(cantidad.value) < 1 ||
                !descripcionTecnica ||
                !descripcionTecnica.value.trim() ||
                !unidad ||
                !unidad.value
            ) {
                event.preventDefault();

                alert(
                    'Revise el producto ' + (i + 1) +
                    ': la cantidad, la unidad de medida y la descripción ' +
                    'técnica son obligatorias.'
                );

                fila.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                return;
            }

            const contenedorDestinos = fila.querySelector('.destino-lista');
            const checksDestino = contenedorDestinos
                ? Array.from(contenedorDestinos.querySelectorAll('.destino-ficha-check:checked'))
                : [];
            if (checksDestino.length === 0) {
                event.preventDefault();
                alert('Marque al menos una ficha de destino para el producto ' + (i + 1) + '.');
                fila.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            let totalDestino = 0;
            for (const check of checksDestino) {
                const bloque = check.closest('.destino-ficha-opcion');
                const campo = bloque ? bloque.querySelector('.destino-cantidad') : null;
                const valor = checksDestino.length === 1
                    ? Number(cantidad.value)
                    : Number(campo?.value || 0);
                if (!Number.isFinite(valor) || valor <= 0) {
                    event.preventDefault();
                    if (campo) campo.focus();
                    alert('Indique una cantidad válida para cada ficha marcada en el producto ' + (i + 1) + '.');
                    fila.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }
                if (campo && checksDestino.length === 1) campo.value = String(Number(cantidad.value));
                totalDestino += valor;
            }
            if (Math.abs(totalDestino - Number(cantidad.value)) > 0.00001) {
                event.preventDefault();
                alert('La suma de las cantidades por ficha debe ser exactamente igual a la cantidad total del producto ' + (i + 1) + '.');
                fila.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            configurarDestinosFila(fila);
        }

        if (
            !confirm(
                '¿Desea guardar los cambios realizados en los productos ' +
                'de esta solicitud?'
            )
        ) {
            event.preventDefault();
        }
    });

    contarMateriales();
});
</script>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>
