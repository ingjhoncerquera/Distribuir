<?php

declare(strict_types=1);

$carrito = is_array($carrito ?? null) ? $carrito : [];
$fichas = is_array($fichas ?? null) ? $fichas : [];

$unidadesMedida = [
    'Unidad', 'Metro', 'Metro Cuadrado', 'Metro Cúbico', 'Centímetro',
    'Centímetro Cuadrado', 'Milímetro', 'Kilogramo', 'Gramo', 'Miligramo',
    'Litro', 'Mililitro', 'Galón', 'Libra', 'Onza', 'Tonelada', 'Pulgada',
    'Pie', 'Yarda', 'Otro',
];

$clasificaciones = [
    'es_consumo'    => 'Consumo',
    'es_devolutivo' => 'Devolutivo',
    'es_software'   => 'Software',
    'es_epp'        => 'EPP',
];
?>

<style>
    .carrito-materiales {
        font-size: .82rem;
    }

    .carrito-materiales .card-header {
        padding: .8rem 1rem;
    }

    .carrito-materiales .card-body {
        padding: 1rem;
    }

    .carrito-materiales .tabla-carrito {
        margin-bottom: .75rem;
        vertical-align: middle;
    }

    .carrito-materiales .tabla-carrito th {
        font-size: .76rem;
        font-weight: 700;
        white-space: nowrap;
        padding: .65rem .55rem;
        background: #f5f7fa;
        color: #344054;
        border-bottom: 1px solid #dee2e6;
    }

    .carrito-materiales .tabla-carrito td {
        padding: .65rem .55rem;
    }

    .carrito-materiales .codigo-unspsc {
        font-size: .78rem;
        white-space: nowrap;
        color: #344054;
    }

    .carrito-materiales .nombre-material {
        min-width: 180px;
        max-width: 260px;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .carrito-materiales .form-control,
    .carrito-materiales .form-select {
        font-size: .8rem;
        min-width: 105px;
    }

    .carrito-materiales .campo-tecnico {
        min-width: 220px;
        resize: vertical;
    }

    .carrito-materiales .campo-observaciones {
        min-width: 160px;
        resize: vertical;
    }

    .carrito-materiales .col-clasificacion {
        min-width: 125px;
    }

    .carrito-materiales .form-check {
        min-height: 1.3rem;
    }

    .carrito-materiales .form-check-label {
        font-size: .78rem;
    }

    .carrito-materiales .destino-ficha-opcion {
        padding-left: 1.5rem;
    }

    .carrito-materiales .destino-cantidad-wrap {
        max-width: 125px;
    }

    .carrito-materiales .destino-cantidad-wrap[hidden] {
        display: none !important;
    }

    .carrito-materiales .acciones-carrito {
        min-width: 78px;
        white-space: nowrap;
    }

    .carrito-materiales .tabla-carrito tbody tr:hover {
        background-color: #f8fafc;
    }

    @media (max-width: 768px) {
        .carrito-materiales .card-body {
            padding: .65rem;
        }

        .carrito-materiales .tabla-carrito th,
        .carrito-materiales .tabla-carrito td {
            padding: .5rem;
        }
    }
</style>

<div class="card border-0 shadow-sm carrito-materiales">

    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">

            <div>
                <h6 class="mb-1 fw-bold">
                    <i class="bi bi-cart3 me-2"></i>
                    Materiales seleccionados
                </h6>

                <small class="text-muted">
                    Complete los datos particulares de cada línea.
                    Puede repetir un código UNSPSC con diferentes especificaciones.
                </small>
            </div>

            <?php if (!empty($carrito)): ?>
                <form
                    method="POST"
                    action="<?= e(url('/materiales/solicitudes/carrito/vaciar')) ?>"
                    onsubmit="return confirm('¿Está seguro de vaciar todos los materiales seleccionados?');"
                >
                    <?= csrfField() ?>

                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-trash me-1"></i>
                        Vaciar selección
                    </button>
                </form>
            <?php endif; ?>

        </div>
    </div>

    <div class="card-body">

        <?php if (empty($carrito)): ?>

            <div class="text-center py-4 text-muted">
                <i class="bi bi-cart-x" style="font-size:2rem;"></i>
                <p class="mb-0 mt-2">
                    Todavía no ha agregado materiales a la solicitud.
                </p>
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-hover tabla-carrito">

                    <thead>
                        <tr>
                            <th>UNSPSC</th>
                            <th>Material</th>
                            <th>Fichas de destino y cantidades</th>
                            <th>Unidad</th>
                            <th>Descripción técnica requerida</th>
                            <th>Clasificación</th>
                            <th style="min-width:105px;">Cantidad</th>
                            <th>Observaciones</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($carrito as $claveLinea => $item): ?>

                        <?php
                        $idLinea = (string) (
                            $item['id_linea'] ?? $claveLinea
                        );

                        $idMaterial = (int) (
                            $item['id_material']
                            ?? $item['id']
                            ?? 0
                        );

                        $sufijo = substr(
                            hash('sha256', $idLinea),
                            0,
                            16
                        );

                        $formActualizar =
                            'formActualizarMaterial_' . $sufijo;

                        $cantidadActual = (string) (
                            $item['cantidad'] ?? 1
                        );

                        $observacionesActuales = (string) (
                            $item['observaciones'] ?? ''
                        );

                        $unidadActual = (string) (
                            $item['unidad_medida'] ?? ''
                        );

                        $descripcionTecnicaActual = (string) (
                            $item['descripcion_tecnica'] ?? ''
                        );
                        ?>

                        <tr
                            class="fila-material-carrito"
                            data-id-linea="<?= e($idLinea) ?>"
                            data-id-material="<?= $idMaterial ?>"
                        >

                            <td>
                                <span class="codigo-unspsc">
                                    <?= e((string) (
                                        $item['codigo_unspsc'] ?? ''
                                    )) ?>
                                </span>
                            </td>

                            <td>
                                <div class="nombre-material">
                                    <?= e((string) (
                                        $item['descripcion'] ?? ''
                                    )) ?>
                                </div>
                            </td>

                            <td style="min-width:230px">
                                <?php
                                $destinosActuales = is_array($item['destinos'] ?? null) ? array_values($item['destinos']) : [];
                                $cantidadesPorFicha = [];
                                foreach ($destinosActuales as $destinoActual) {
                                    $idFichaDestino = (int) ($destinoActual['id_ficha'] ?? 0);
                                    if ($idFichaDestino > 0) {
                                        $cantidadesPorFicha[$idFichaDestino] = (float) ($destinoActual['cantidad_destinada'] ?? 0);
                                    }
                                }
                                if (!$cantidadesPorFicha && count($fichas) === 1) {
                                    $cantidadesPorFicha[(int) $fichas[0]['id']] = (float) ($item['cantidad'] ?? 1);
                                } elseif (!$cantidadesPorFicha && count($fichas) > 1 && !empty($item['id_ficha'])) {
                                    $cantidadesPorFicha[(int) $item['id_ficha']] = (float) ($item['cantidad'] ?? 1);
                                }
                                if (!$cantidadesPorFicha && count($fichas) === 1) {
                                    $cantidadesPorFicha[(int) $fichas[0]['id']] = (float) ($item['cantidad'] ?? 1);
                                }
                                ?>
                                <?php if (count($fichas) === 0): ?>
                                    <span class="text-danger small">Sin fichas asignadas</span>
                                <?php else: ?>
                                    <div class="destinos-carrito" data-id-linea="<?= e($idLinea) ?>">
                                        <?php foreach ($fichas as $ficha): ?>
                                            <?php
                                            $idFichaCheckbox = (int) ($ficha['id'] ?? 0);
                                            $seleccionada = isset($cantidadesPorFicha[$idFichaCheckbox]) || count($fichas) === 1;
                                            $cantidadFichaActual = count($fichas) === 1
                                                ? (float) ($item['cantidad'] ?? 1)
                                                : (float) ($cantidadesPorFicha[$idFichaCheckbox] ?? 0);
                                            ?>
                                            <div class="form-check destino-ficha-opcion mb-1" data-id-ficha="<?= $idFichaCheckbox ?>">
                                                <input class="form-check-input campo-destino-ficha" type="checkbox" value="<?= $idFichaCheckbox ?>" <?= $seleccionada ? 'checked' : '' ?> <?= count($fichas) === 1 ? 'disabled' : '' ?> id="destino_<?= e($sufijo) ?>_<?= $idFichaCheckbox ?>">
                                                <label class="form-check-label" for="destino_<?= e($sufijo) ?>_<?= $idFichaCheckbox ?>">
                                                    <?= e((string) ($ficha['numero_ficha'] ?? '')) ?><?= !empty($ficha['nombre_programa']) ? ' - ' . e((string) $ficha['nombre_programa']) : '' ?>
                                                </label>
                                                <div class="destino-cantidad-wrap ms-4 mt-1" <?= $seleccionada && count($cantidadesPorFicha) > 1 ? '' : 'hidden' ?>>
                                                    <input type="number" min="0.01" step="1" class="form-control form-control-sm campo-destino-cantidad" value="<?= $cantidadFichaActual > 0 ? e((string) $cantidadFichaActual) : '' ?>" placeholder="Cantidad para esta ficha" aria-label="Cantidad para ficha <?= e((string) ($ficha['numero_ficha'] ?? '')) ?>">
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if (count($fichas) === 1): ?>
                                        <div class="small text-muted">Recibe automáticamente la cantidad total.</div>
                                    <?php else: ?>
                                        <div class="small text-muted mt-1">Marque las fichas destino. Si marca varias, indique la cantidad para cada una.</div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>

                            <td>
                                <select
                                    name="unidad_medida"
                                    form="<?= e($formActualizar) ?>"
                                    class="form-select form-select-sm campo-unidad-medida-carrito"
                                    data-id-linea="<?= e($idLinea) ?>"
                                    data-id-material="<?= $idMaterial ?>"
                                    required
                                >
                                    <option value="">Seleccione...</option>

                                    <?php foreach ($unidadesMedida as $unidad): ?>
                                        <option
                                            value="<?= e($unidad) ?>"
                                            <?= $unidadActual === $unidad
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            <?= e($unidad) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>

                            <td>
                                <textarea
                                    name="descripcion_tecnica"
                                    form="<?= e($formActualizar) ?>"
                                    class="form-control form-control-sm campo-tecnico campo-descripcion-tecnica-carrito"
                                    data-id-linea="<?= e($idLinea) ?>"
                                    data-id-material="<?= $idMaterial ?>"
                                    rows="2"
                                    maxlength="2000"
                                    placeholder="Color, tipo, material, modelo, presentación..."
                                    required
                                ><?= e($descripcionTecnicaActual) ?></textarea>
                            </td>

                            <td class="col-clasificacion">
                                <div class="d-flex flex-column gap-1">

                                    <?php foreach ($clasificaciones as $campo => $etiqueta): ?>
                                        <label class="form-check mb-0">

                                            <input
                                                class="form-check-input campo-clasificacion-carrito"
                                                type="checkbox"
                                                name="<?= e($campo) ?>"
                                                value="1"
                                                form="<?= e($formActualizar) ?>"
                                                data-id-linea="<?= e($idLinea) ?>"
                                                data-id-material="<?= $idMaterial ?>"
                                                data-campo="<?= e($campo) ?>"
                                                <?= !empty($item[$campo])
                                                    ? 'checked'
                                                    : '' ?>
                                            >

                                            <span class="form-check-label">
                                                <?= e($etiqueta) ?>
                                            </span>

                                        </label>
                                    <?php endforeach; ?>

                                </div>
                            </td>

                            <td>
                                <input
                                    type="number"
                                    name="cantidad"
                                    form="<?= e($formActualizar) ?>"
                                    class="form-control form-control-sm campo-cantidad-carrito"
                                    data-id-linea="<?= e($idLinea) ?>"
                                    data-id-material="<?= $idMaterial ?>"
                                    min="0.01"
                                    step="0.01"
                                    value="<?= e($cantidadActual) ?>"
                                    required
                                >
                            </td>

                            <td>
                                <textarea
                                    name="observaciones"
                                    form="<?= e($formActualizar) ?>"
                                    class="form-control form-control-sm campo-observaciones campo-observaciones-carrito"
                                    data-id-linea="<?= e($idLinea) ?>"
                                    data-id-material="<?= $idMaterial ?>"
                                    rows="2"
                                    placeholder="Opcionales"
                                ><?= e($observacionesActuales) ?></textarea>
                            </td>

                            <td class="text-center acciones-carrito">

                                <form
                                    method="POST"
                                    action="<?= e(url('/materiales/solicitudes/carrito/actualizar')) ?>"
                                    id="<?= e($formActualizar) ?>"
                                    class="mb-1"
                                >
                                    <?= csrfField() ?>

                                    <input
                                        type="hidden"
                                        name="id_linea"
                                        value="<?= e($idLinea) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="id_material"
                                        value="<?= $idMaterial ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Actualizar esta línea"
                                        aria-label="Actualizar esta línea"
                                    >
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>

                                <form
                                    method="POST"
                                    action="<?= e(url('/materiales/solicitudes/carrito/quitar')) ?>"
                                    onsubmit="return confirm('¿Desea retirar únicamente esta línea de la solicitud?');"
                                >
                                    <?= csrfField() ?>

                                    <input
                                        type="hidden"
                                        name="id_linea"
                                        value="<?= e($idLinea) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="id_material"
                                        value="<?= $idMaterial ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Retirar esta línea"
                                        aria-label="Retirar esta línea"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>

                            </td>
                        </tr>

                    <?php endforeach; ?>

                    </tbody>
                </table>
            </div>

            <div class="alert alert-light border py-2 px-3 mb-0 small">
                <i class="bi bi-info-circle me-2"></i>

                <strong><?= count($carrito) ?></strong>
                línea(s) seleccionada(s).

                <span class="text-muted">
                    Cada línea puede tener su propia descripción técnica,
                    unidad, clasificación y cantidad.
                </span>
            </div>

        <?php endif; ?>

    </div>
</div>
