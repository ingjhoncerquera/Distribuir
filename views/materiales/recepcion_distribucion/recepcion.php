<?php
declare(strict_types=1);
$tituloPagina = 'Recepción de materiales';
require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';

$solicitudes = is_array($solicitudes ?? null) ? $solicitudes : [];
$success = $_SESSION['flash']['success'] ?? null;
$error = $_SESSION['flash']['error'] ?? null;
unset($_SESSION['flash']['success'], $_SESSION['flash']['error']);

$productos = [];
foreach ($solicitudes as $solicitud) {
    foreach (($solicitud['detalles'] ?? []) as $detalle) {
        $key = (string)($detalle['id_material'] ?? 0) . '|' . (string)($detalle['unidad_medida'] ?? 'Unidad');
        if (!isset($productos[$key])) {
            $productos[$key] = [
                'descripcion' => $detalle['descripcion'] ?? 'Material',
                'codigo' => $detalle['codigo_unspsc'] ?? '',
                'unidad' => $detalle['unidad_medida'] ?? 'Unidad',
                'solicitado' => 0.0, 'recibido' => 0.0, 'pendiente' => 0.0, 'lineas' => []
            ];
        }
        $detalle['solicitud'] = $solicitud;
        $productos[$key]['solicitado'] += (float)($detalle['cantidad_solicitada'] ?? 0);
        $productos[$key]['recibido'] += (float)($detalle['recibido'] ?? 0);
        $productos[$key]['pendiente'] += (float)($detalle['pendiente'] ?? 0);
        $productos[$key]['lineas'][] = $detalle;
    }
}
?>
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-box-arrow-in-down me-2"></i>Recepción de materiales</h2>
            <p class="text-muted mb-0">Vista agrupada por producto. Abra cada producto para ver las solicitudes que lo componen.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-success" href="<?= e(url('/materiales/recepcion-distribucion/distribucion')) ?>"><i class="bi bi-box-arrow-up-right me-1"></i>Ir a distribución</a>
            <a class="btn btn-outline-secondary" href="<?= e(url('/materiales/solicitudes')) ?>">Solicitudes</a>
        </div>
    </div>
    <?php if ($success): ?><div class="alert alert-success"><?= e((string)$success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= e((string)$error) ?></div><?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-table me-2"></i>Productos solicitados y recepción acumulada</strong>
            <span class="small text-muted">Solo solicitudes avaladas</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th style="min-width:260px">Producto</th><th class="text-end">Total solicitado</th><th class="text-end">Total recibido</th><th class="text-end">Pendiente</th><th style="width:130px">Detalle</th></tr></thead>
                <tbody>
                <?php if (!$productos): ?>
                    <tr><td colspan="5" class="text-center text-muted py-5">No hay solicitudes avaladas para su coordinación.</td></tr>
                <?php else: foreach ($productos as $i => $producto): ?>
                    <tr>
                        <td><div class="fw-semibold"><?= e((string)$producto['descripcion']) ?></div><small class="text-muted"><?= e((string)$producto['codigo']) ?> · <?= e((string)$producto['unidad']) ?></small></td>
                        <td class="text-end"><?= e(number_format($producto['solicitado'], 2, ',', '.')) ?></td>
                        <td class="text-end fw-semibold text-success"><?= e(number_format($producto['recibido'], 2, ',', '.')) ?></td>
                        <td class="text-end"><span class="badge <?= $producto['pendiente'] > 0 ? 'text-bg-warning' : 'text-bg-light border text-dark' ?>"><?= e(number_format($producto['pendiente'], 2, ',', '.')) ?></span></td>
                        <td><button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#producto-recepcion-<?= (int)$i ?>">Ver solicitudes (<?= count($producto['lineas']) ?>)</button></td>
                    </tr>
                    <tr class="collapse" id="producto-recepcion-<?= (int)$i ?>">
                        <td colspan="5" class="bg-light p-3">
                            <?php foreach ($producto['lineas'] as $linea): $s = $linea['solicitud']; $pend = (float)$linea['pendiente']; ?>
                                <div class="border rounded bg-white p-3 mb-2">
                                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                        <div><strong><?= e((string)($s['numero_solicitud'] ?? 'Solicitud')) ?></strong> — <?= e((string)($s['nombre_area'] ?? '')) ?><div class="small text-muted"><?= e((string)($s['nombre_proceso'] ?? '')) ?> · <?= e((string)($s['solicitante'] ?? '')) ?></div></div>
                                        <div class="small">Solicitado: <strong><?= e(number_format((float)$linea['cantidad_solicitada'], 2, ',', '.')) ?></strong> · Recibido: <strong><?= e(number_format((float)$linea['recibido'], 2, ',', '.')) ?></strong></div>
                                    </div>
                                    <form method="post" action="<?= e(url('/materiales/recepcion-distribucion/recibir')) ?>" class="row g-2 align-items-end">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id_detalle" value="<?= (int)$linea['id_detalle'] ?>">
                                        <input type="hidden" name="fecha" value="<?= date('Y-m-d') ?>">
                                        <div class="col-6 col-md-2"><label class="form-label small">Cantidad recibida</label><input class="form-control form-control-sm" type="number" name="cantidad" min="0.01" max="<?= e((string)$pend) ?>" step="0.01" required <?= $pend <= 0 ? 'disabled' : '' ?>></div>
                                        <div class="col-6 col-md-2"><label class="form-label small">Remisión</label><input class="form-control form-control-sm" name="remision" maxlength="100" placeholder="N.º remisión" <?= $pend <= 0 ? 'disabled' : '' ?>></div>
                                        <div class="col-12 col-md-6"><label class="form-label small">Observación</label><input class="form-control form-control-sm" name="observaciones" maxlength="1000" placeholder="Opcional" <?= $pend <= 0 ? 'disabled' : '' ?>></div>
                                        <div class="col-12 col-md-2"><button class="btn btn-sm btn-primary w-100" type="submit" <?= $pend <= 0 ? 'disabled' : '' ?>>Registrar</button></div>
                                    </form>
                                    <?php if ($pend <= 0): ?><small class="text-success">Esta línea ya fue recibida en su totalidad.</small><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
