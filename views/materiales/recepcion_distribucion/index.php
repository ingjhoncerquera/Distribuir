<?php
declare(strict_types=1);

$tituloPagina = 'Recepción y distribución de materiales';
require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';

$solicitudes = is_array($solicitudes ?? null) ? $solicitudes : [];
$historial = is_array($historial ?? null) ? $historial : [];
$mensajeSuccess = $_SESSION['flash']['success'] ?? null;
$mensajeError = $_SESSION['flash']['error'] ?? null;
unset($_SESSION['flash']['success'], $_SESSION['flash']['error']);

$totalLineas = 0;
$totalPendiente = 0.0;
$totalDisponible = 0.0;
foreach ($solicitudes as $solResumen) {
    foreach (($solResumen['detalles'] ?? []) as $detResumen) {
        $totalLineas++;
        $totalPendiente += (float)($detResumen['pendiente'] ?? 0);
        $totalDisponible += (float)($detResumen['disponible'] ?? 0);
    }
}
?>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-box-seam me-2"></i>Recepción y distribución</h2>
            <p class="text-muted mb-0">Registre cantidades directamente en la tabla, como en una hoja de cálculo.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= e(url('/materiales/solicitudes')) ?>">
            <i class="bi bi-arrow-left me-1"></i>Volver a solicitudes
        </a>
    </div>

    <?php if ($mensajeSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= e((string)$mensajeSuccess) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>
    <?php if ($mensajeError): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= e((string)$mensajeError) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body py-3"><div class="small text-muted">Solicitudes avaladas</div><div class="fs-4 fw-bold"><?= count($solicitudes) ?></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body py-3"><div class="small text-muted">Materiales</div><div class="fs-4 fw-bold"><?= $totalLineas ?></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body py-3"><div class="small text-muted">Pendiente por recibir</div><div class="fs-4 fw-bold text-warning"><?= e(number_format($totalPendiente, 2, ',', '.')) ?></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body py-3"><div class="small text-muted">Recibido sin distribuir</div><div class="fs-4 fw-bold text-primary"><?= e(number_format($totalDisponible, 2, ',', '.')) ?></div></div></div></div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0 fw-semibold"><i class="bi bi-table me-2"></i>Control de materiales</h5>
            <span class="small text-muted">Solo aparecen solicitudes avaladas de su ámbito de acceso.</span>
        </div>
        <?php if (!$solicitudes): ?>
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox display-5 text-muted"></i>
                <h5 class="mt-3">No hay solicitudes avaladas</h5>
                <p class="text-muted mb-0">Cuando haya solicitudes avaladas, aparecerán aquí para registrar sus movimientos.</p>
            </div>
        <?php else: ?>
            <?php foreach ($solicitudes as $s): ?>
                <div class="px-3 px-lg-4 pt-3">
                    <div class="d-flex flex-wrap justify-content-between gap-2 border-start border-4 border-primary ps-3 py-1">
                        <div>
                            <div class="fw-bold"><?= e((string)($s['numero_solicitud'] ?? ('Solicitud #' . $s['id_solicitud']))) ?> <span class="fw-normal text-muted">— <?= e((string)($s['titulo'] ?? '')) ?></span></div>
                            <div class="small text-muted"><?= e((string)($s['nombre_proceso'] ?? '')) ?><?= !empty($s['anio']) ? ' · ' . (int)$s['anio'] : '' ?> · <?= e((string)($s['nombre_area'] ?? '')) ?> · <?= e((string)($s['solicitante'] ?? '')) ?></div>
                        </div>
                        <span class="badge text-bg-success align-self-start">Avalada</span>
                    </div>
                </div>
                <div class="table-responsive px-0 pb-3">
                    <table class="table table-sm table-hover align-middle mb-0" style="min-width:1100px">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="min-width:220px">Material</th>
                                <th class="text-end">Solicitado</th>
                                <th class="text-end">Recibido</th>
                                <th class="text-end">Pendiente</th>
                                <th class="text-end">Sin distribuir</th>
                                <th style="min-width:220px">Registrar recepción</th>
                                <th style="min-width:310px">Distribuir a ficha</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach (($s['detalles'] ?? []) as $d): ?>
                            <?php
                            $idDetalle = (int)$d['id_detalle'];
                            $sol = (float)($d['cantidad_solicitada'] ?? 0);
                            $rec = (float)($d['recibido'] ?? 0);
                            $dist = (float)($d['distribuido'] ?? 0);
                            $pend = max(0, $sol - $rec);
                            $disp = max(0, $rec - $dist);
                            $fichas = $d['fichas'] ?? [];
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold"><?= e((string)($d['descripcion'] ?? 'Material')) ?></div>
                                    <div class="small text-muted"><?= e((string)($d['codigo_unspsc'] ?? 'Sin código')) ?> · <?= e((string)($d['unidad_medida'] ?? 'Unidad')) ?></div>
                                    <?php if (!empty($d['observaciones'])): ?><div class="small text-muted"><?= e((string)$d['observaciones']) ?></div><?php endif; ?>
                                </td>
                                <td class="text-end text-nowrap"><?= e(number_format($sol, 2, ',', '.')) ?></td>
                                <td class="text-end text-nowrap fw-semibold text-success"><?= e(number_format($rec, 2, ',', '.')) ?></td>
                                <td class="text-end text-nowrap"><span class="badge <?= $pend > 0 ? 'text-bg-warning' : 'text-bg-light border text-dark' ?>"><?= e(number_format($pend, 2, ',', '.')) ?></span></td>
                                <td class="text-end text-nowrap fw-semibold text-primary"><?= e(number_format($disp, 2, ',', '.')) ?></td>
                                <td>
                                    <form method="post" action="<?= e(url('/materiales/recepcion-distribucion/recibir')) ?>" class="d-flex align-items-center gap-1">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id_detalle" value="<?= $idDetalle ?>">
                                        <input class="form-control form-control-sm" style="max-width:95px" type="number" name="cantidad" min="0.01" max="<?= e((string)$pend) ?>" step="0.01" placeholder="Cantidad" aria-label="Cantidad recibida" required <?= $pend <= 0 ? 'disabled' : '' ?>>
                                        <input type="hidden" name="fecha" value="<?= date('Y-m-d') ?>">
                                        <button class="btn btn-sm btn-primary text-nowrap" type="submit" title="Guardar recepción" <?= $pend <= 0 ? 'disabled' : '' ?>><i class="bi bi-plus-lg me-1"></i>Recibir</button>
                                    </form>
                                    <details class="mt-1">
                                        <summary class="small text-muted" style="cursor:pointer">Remisión / observación</summary>
                                        <form method="post" action="<?= e(url('/materiales/recepcion-distribucion/recibir')) ?>" class="mt-2">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id_detalle" value="<?= $idDetalle ?>">
                                            <input type="hidden" name="cantidad" value="0">
                                            <input type="hidden" name="fecha" value="<?= date('Y-m-d') ?>">
                                            <input class="form-control form-control-sm mb-1" name="remision" maxlength="100" placeholder="N.º remisión (opcional)" <?= $pend <= 0 ? 'disabled' : '' ?>>
                                            <input class="form-control form-control-sm" name="observaciones" maxlength="1000" placeholder="Observación (opcional)" <?= $pend <= 0 ? 'disabled' : '' ?>>
                                        </form>
                                    </details>
                                </td>
                                <td>
                                    <?php if (!$fichas): ?>
                                        <span class="small text-muted">Sin ficha destino asociada</span>
                                    <?php else: ?>
                                        <form method="post" action="<?= e(url('/materiales/recepcion-distribucion/distribuir')) ?>" class="d-flex align-items-center gap-1">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id_detalle" value="<?= $idDetalle ?>">
                                            <select class="form-select form-select-sm" style="max-width:185px" name="id_ficha" aria-label="Ficha destino" required <?= $disp <= 0 ? 'disabled' : '' ?>>
                                                <option value="">Seleccione ficha</option>
                                                <?php foreach ($fichas as $f): ?>
                                                    <option value="<?= (int)$f['id_ficha'] ?>"><?= e((string)$f['numero_ficha']) ?><?= $f['cantidad_destinada'] !== null ? ' · máx. ' . e((string)$f['cantidad_destinada']) : '' ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input class="form-control form-control-sm" style="max-width:90px" type="number" name="cantidad" min="0.01" max="<?= e((string)$disp) ?>" step="0.01" placeholder="Cant." aria-label="Cantidad a distribuir" required <?= $disp <= 0 ? 'disabled' : '' ?>>
                                            <button class="btn btn-sm btn-success text-nowrap" type="submit" <?= $disp <= 0 ? 'disabled' : '' ?>><i class="bi bi-check2 me-1"></i>Asignar</button>
                                        </form>
                                        <details class="mt-1">
                                            <summary class="small text-muted" style="cursor:pointer">Agregar observación</summary>
                                            <p class="small text-muted mb-0 mt-1">Para registrar observaciones de distribución, use el historial de movimientos o contacte al administrador.</p>
                                        </details>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Historial reciente</h5>
        </div>
        <div class="card-body p-0">
            <details class="p-3">
                <summary class="fw-semibold" style="cursor:pointer">Ver movimientos registrados (<?= count($historial) ?>)</summary>
                <div class="table-responsive mt-3">
                    <table class="table table-sm table-striped table-hover align-middle mb-0">
                        <thead><tr><th>Tipo</th><th>Fecha</th><th>Solicitud</th><th>Material</th><th>Ficha</th><th>Cantidad</th><th>Usuario</th><th>Observaciones</th></tr></thead>
                        <tbody>
                        <?php if (!$historial): ?>
                            <tr><td colspan="8" class="text-center text-muted py-3">No hay movimientos registrados.</td></tr>
                        <?php else: foreach ($historial as $m): ?>
                            <tr>
                                <td><span class="badge <?= ($m['tipo'] ?? '') === 'Recepción' ? 'text-bg-primary' : 'text-bg-success' ?>"><?= e((string)($m['tipo'] ?? '')) ?></span></td>
                                <td class="text-nowrap"><?= e((string)($m['fecha'] ?? '')) ?></td>
                                <td><?= e((string)($m['numero_solicitud'] ?? '')) ?></td>
                                <td><?= e((string)($m['material'] ?? '')) ?></td>
                                <td><?= e((string)($m['numero_ficha'] ?? '—')) ?></td>
                                <td class="fw-semibold"><?= e((string)($m['cantidad'] ?? '')) ?></td>
                                <td><?= e((string)($m['usuario'] ?? '—')) ?></td>
                                <td class="small"><?= nl2br(e((string)($m['observaciones'] ?? ''))) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </details>
        </div>
    </div>
</div>
