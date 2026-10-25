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

?>

<div class="container-fluid py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="bi bi-box-seam me-2"></i>
                Recepción y distribución de materiales
            </h2>
            <p class="text-muted mb-0">
                Control de materiales recibidos, asignaciones por ficha y trazabilidad de los movimientos.
            </p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= e(url('/materiales/solicitudes')) ?>">
            <i class="bi bi-arrow-left me-1"></i> Volver a solicitudes
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

    <div class="row g-3 mb-4">
        <?php
        $totalSolicitudes = count($solicitudes);
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
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3"><i class="bi bi-clipboard-check fs-4"></i></div>
                <div><div class="text-muted small">Solicitudes avaladas</div><div class="fs-4 fw-bold"><?= $totalSolicitudes ?></div></div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info bg-opacity-10 text-info p-3"><i class="bi bi-list-check fs-4"></i></div>
                <div><div class="text-muted small">Líneas de material</div><div class="fs-4 fw-bold"><?= $totalLineas ?></div></div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-3"><i class="bi bi-hourglass-split fs-4"></i></div>
                <div><div class="text-muted small">Pendiente por recibir</div><div class="fs-4 fw-bold"><?= e(number_format($totalPendiente, 2, ',', '.')) ?></div></div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 text-success p-3"><i class="bi bi-boxes fs-4"></i></div>
                <div><div class="text-muted small">Disponible para distribuir</div><div class="fs-4 fw-bold"><?= e(number_format($totalDisponible, 2, ',', '.')) ?></div></div>
            </div></div>
        </div>
    </div>

    <?php if (!$solicitudes): ?>
        <div class="card border-0 shadow-sm"><div class="card-body p-4 text-center">
            <i class="bi bi-inbox display-5 text-muted"></i>
            <h3 class="h5 mt-3">No hay solicitudes avaladas</h3>
            <p class="text-muted mb-0">Cuando existan solicitudes avaladas dentro de su ámbito de acceso, aparecerán en esta sección.</p>
        </div></div>
    <?php endif; ?>

    <?php foreach ($solicitudes as $s): ?>
        <section class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <h3 class="h5 fw-bold mb-1">
                            <i class="bi bi-file-earmark-text me-2 text-primary"></i>
                            <?= e((string)($s['numero_solicitud'] ?? ('Solicitud #' . $s['id_solicitud']))) ?>
                            <span class="text-muted fw-normal">— <?= e((string)($s['titulo'] ?? '')) ?></span>
                        </h3>
                        <div class="small text-muted">
                            <i class="bi bi-diagram-3 me-1"></i><?= e((string)($s['nombre_proceso'] ?? '')) ?>
                            <?php if (!empty($s['anio'])): ?> (<?= (int)$s['anio'] ?>)<?php endif; ?>
                            <span class="mx-1">·</span><?= e((string)($s['nombre_area'] ?? '')) ?>
                            <span class="mx-1">·</span><i class="bi bi-person me-1"></i><?= e((string)($s['solicitante'] ?? '')) ?>
                        </div>
                    </div>
                    <span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>Avalada</span>
                </div>
            </div>
            <div class="card-body p-3 p-lg-4">
                <?php foreach (($s['detalles'] ?? []) as $d): ?>
                    <?php
                    $sol = (float)($d['cantidad_solicitada'] ?? 0);
                    $rec = (float)($d['recibido'] ?? 0);
                    $dist = (float)($d['distribuido'] ?? 0);
                    $pend = max(0, $sol - $rec);
                    $disp = max(0, $rec - $dist);
                    ?>
                    <article class="border rounded-3 p-3 p-lg-4 mb-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <h4 class="h6 fw-bold mb-1"><?= e((string)($d['descripcion'] ?? 'Material')) ?></h4>
                                <div class="small text-muted">
                                    Código: <?= e((string)($d['codigo_unspsc'] ?? '—')) ?>
                                    <span class="mx-1">·</span>Unidad: <?= e((string)($d['unidad_medida'] ?? 'Unidad')) ?>
                                </div>
                            </div>
                            <span class="badge text-bg-light border text-dark">Línea #<?= (int)$d['id_detalle'] ?></span>
                        </div>

                        <div class="row g-2 mb-4">
                            <div class="col-6 col-lg-3"><div class="rounded-3 bg-light p-3 h-100"><div class="small text-muted mb-1">Solicitado</div><div class="fs-5 fw-semibold"><?= e(number_format($sol, 2, ',', '.')) ?></div></div></div>
                            <div class="col-6 col-lg-3"><div class="rounded-3 bg-success bg-opacity-10 p-3 h-100"><div class="small text-muted mb-1">Recibido</div><div class="fs-5 fw-semibold text-success"><?= e(number_format($rec, 2, ',', '.')) ?></div></div></div>
                            <div class="col-6 col-lg-3"><div class="rounded-3 bg-warning bg-opacity-10 p-3 h-100"><div class="small text-muted mb-1">Pendiente recibir</div><div class="fs-5 fw-semibold"><?= e(number_format($pend, 2, ',', '.')) ?></div></div></div>
                            <div class="col-6 col-lg-3"><div class="rounded-3 bg-primary bg-opacity-10 p-3 h-100"><div class="small text-muted mb-1">Disponible distribuir</div><div class="fs-5 fw-semibold text-primary"><?= e(number_format($disp, 2, ',', '.')) ?></div></div></div>
                        </div>

                        <div class="row g-4">
                            <div class="col-xl-5">
                                <div class="h-100 rounded-3 border p-3">
                                    <h5 class="h6 fw-bold mb-3"><i class="bi bi-box-arrow-in-down me-2 text-primary"></i>Registrar recepción</h5>
                                    <form method="post" action="<?= e(url('/materiales/recepcion-distribucion/recibir')) ?>">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id_detalle" value="<?= (int)$d['id_detalle'] ?>">
                                        <div class="row g-3">
                                            <div class="col-md-6"><label class="form-label" for="rec-cant-<?= (int)$d['id_detalle'] ?>">Cantidad recibida</label><input id="rec-cant-<?= (int)$d['id_detalle'] ?>" class="form-control" type="number" name="cantidad" min="0.01" max="<?= e((string)$pend) ?>" step="0.01" required <?= $pend <= 0 ? 'disabled' : '' ?>></div>
                                            <div class="col-md-6"><label class="form-label" for="rec-fecha-<?= (int)$d['id_detalle'] ?>">Fecha</label><input id="rec-fecha-<?= (int)$d['id_detalle'] ?>" class="form-control" type="date" name="fecha" value="<?= date('Y-m-d') ?>" required <?= $pend <= 0 ? 'disabled' : '' ?>></div>
                                            <div class="col-12"><label class="form-label" for="rec-rem-<?= (int)$d['id_detalle'] ?>">Remisión o soporte</label><input id="rec-rem-<?= (int)$d['id_detalle'] ?>" class="form-control" name="remision" maxlength="100" placeholder="Número de remisión (opcional)" <?= $pend <= 0 ? 'disabled' : '' ?>></div>
                                            <div class="col-12"><label class="form-label" for="rec-obs-<?= (int)$d['id_detalle'] ?>">Observaciones</label><textarea id="rec-obs-<?= (int)$d['id_detalle'] ?>" class="form-control" name="observaciones" rows="2" maxlength="1000" placeholder="Detalle de la recepción" <?= $pend <= 0 ? 'disabled' : '' ?>></textarea></div>
                                            <div class="col-12"><button class="btn btn-primary" type="submit" <?= $pend <= 0 ? 'disabled' : '' ?>><i class="bi bi-save me-1"></i>Guardar recepción</button></div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="col-xl-7">
                                <div class="h-100 rounded-3 border p-3">
                                    <h5 class="h6 fw-bold mb-3"><i class="bi bi-box-arrow-up-right me-2 text-success"></i>Distribuir a una ficha</h5>
                                    <form method="post" action="<?= e(url('/materiales/recepcion-distribucion/distribuir')) ?>">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id_detalle" value="<?= (int)$d['id_detalle'] ?>">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label" for="dist-ficha-<?= (int)$d['id_detalle'] ?>">Ficha destino</label>
                                                <select id="dist-ficha-<?= (int)$d['id_detalle'] ?>" class="form-select" name="id_ficha" required <?= $disp <= 0 || empty($d['fichas']) ? 'disabled' : '' ?>>
                                                    <option value="">Seleccione una ficha</option>
                                                    <?php foreach (($d['fichas'] ?? []) as $f): ?>
                                                        <option value="<?= (int)$f['id_ficha'] ?>"><?= e((string)$f['numero_ficha']) ?><?= $f['cantidad_destinada'] !== null ? ' — asignado: ' . e((string)$f['cantidad_destinada']) : '' ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <?php if (empty($d['fichas'])): ?><div class="form-text">Esta línea no tiene fichas destino asociadas en la solicitud.</div><?php endif; ?>
                                            </div>
                                            <div class="col-md-6"><label class="form-label" for="dist-cant-<?= (int)$d['id_detalle'] ?>">Cantidad a distribuir</label><input id="dist-cant-<?= (int)$d['id_detalle'] ?>" class="form-control" type="number" name="cantidad" min="0.01" max="<?= e((string)$disp) ?>" step="0.01" required <?= $disp <= 0 || empty($d['fichas']) ? 'disabled' : '' ?>></div>
                                            <div class="col-12"><label class="form-label" for="dist-obs-<?= (int)$d['id_detalle'] ?>">Observaciones</label><textarea id="dist-obs-<?= (int)$d['id_detalle'] ?>" class="form-control" name="observaciones" rows="2" maxlength="1000" placeholder="Detalle de la distribución"></textarea></div>
                                            <div class="col-12"><button class="btn btn-success" type="submit" <?= $disp <= 0 || empty($d['fichas']) ? 'disabled' : '' ?>><i class="bi bi-check2-circle me-1"></i>Guardar distribución</button></div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <section class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white py-3">
            <h3 class="h5 fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>Trazabilidad de movimientos recientes</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead><tr><th>Tipo</th><th>Fecha</th><th>Solicitud</th><th>Material</th><th>Ficha</th><th>Cantidad</th><th>Usuario</th><th>Observaciones</th></tr></thead>
                <tbody>
                <?php if (!$historial): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4"><i class="bi bi-inbox me-2"></i>No hay movimientos registrados.</td></tr>
                <?php else: foreach ($historial as $m): ?>
                    <tr>
                        <td><span class="badge <?= ($m['tipo'] ?? '') === 'Recepción' ? 'text-bg-primary' : 'text-bg-success' ?>"><?= e((string)$m['tipo']) ?></span></td>
                        <td class="text-nowrap"><?= e((string)$m['fecha']) ?></td>
                        <td><?= e((string)($m['numero_solicitud'] ?? '')) ?></td>
                        <td><?= e((string)($m['material'] ?? '')) ?></td>
                        <td><?= e((string)($m['numero_ficha'] ?? '—')) ?></td>
                        <td class="fw-semibold"><?= e((string)$m['cantidad']) ?></td>
                        <td><?= e((string)($m['usuario'] ?? '—')) ?></td>
                        <td class="small"><?= nl2br(e((string)($m['observaciones'] ?? ''))) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>
