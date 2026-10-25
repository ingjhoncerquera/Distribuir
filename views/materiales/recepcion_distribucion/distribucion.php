<?php
declare(strict_types=1);
$tituloPagina = 'Distribución de materiales';
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
                'solicitado' => 0.0, 'recibido' => 0.0, 'distribuido' => 0.0, 'disponible' => 0.0, 'lineas' => []
            ];
        }
        $detalle['solicitud'] = $solicitud;
        $productos[$key]['solicitado'] += (float)($detalle['cantidad_solicitada'] ?? 0);
        $productos[$key]['recibido'] += (float)($detalle['recibido'] ?? 0);
        $productos[$key]['distribuido'] += (float)($detalle['distribuido'] ?? 0);
        $productos[$key]['disponible'] += (float)($detalle['disponible'] ?? 0);
        $productos[$key]['lineas'][] = $detalle;
    }
}
?>
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-box-arrow-up-right me-2"></i>Distribución de materiales</h2>
            <p class="text-muted mb-0">Productos agrupados; las asignaciones se registran dentro de cada solicitud y ficha destino.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="<?= e(url('/materiales/recepcion-distribucion/recepcion')) ?>"><i class="bi bi-box-arrow-in-down me-1"></i>Ir a recepción</a>
            <a class="btn btn-outline-secondary" href="<?= e(url('/materiales/solicitudes')) ?>">Solicitudes</a>
        </div>
    </div>
    <?php if ($success): ?><div class="alert alert-success"><?= e((string)$success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= e((string)$error) ?></div><?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-table me-2"></i>Saldo disponible por producto</strong>
            <span class="small text-muted">No se permite distribuir más de lo recibido ni superar lo destinado a la ficha</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th style="min-width:260px">Producto</th><th class="text-end">Solicitado</th><th class="text-end">Recibido</th><th class="text-end">Distribuido</th><th class="text-end">Sin distribuir</th><th style="width:130px">Detalle</th></tr></thead>
                <tbody>
                <?php if (!$productos): ?>
                    <tr><td colspan="6" class="text-center text-muted py-5">No hay solicitudes avaladas para su coordinación.</td></tr>
                <?php else: foreach ($productos as $i => $producto): ?>
                    <tr>
                        <td><div class="fw-semibold"><?= e((string)$producto['descripcion']) ?></div><small class="text-muted"><?= e((string)$producto['codigo']) ?> · <?= e((string)$producto['unidad']) ?></small></td>
                        <td class="text-end"><?= e(number_format($producto['solicitado'], 2, ',', '.')) ?></td>
                        <td class="text-end text-success fw-semibold"><?= e(number_format($producto['recibido'], 2, ',', '.')) ?></td>
                        <td class="text-end"><?= e(number_format($producto['distribuido'], 2, ',', '.')) ?></td>
                        <td class="text-end"><span class="badge text-bg-primary"><?= e(number_format($producto['disponible'], 2, ',', '.')) ?></span></td>
                        <td><button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#producto-distribucion-<?= (int)$i ?>">Ver fichas</button></td>
                    </tr>
                    <tr class="collapse" id="producto-distribucion-<?= (int)$i ?>">
                        <td colspan="6" class="bg-light p-3">
                            <?php foreach ($producto['lineas'] as $linea): $s = $linea['solicitud']; $disp = (float)$linea['disponible']; $fichas = $linea['fichas'] ?? []; ?>
                                <div class="border rounded bg-white p-3 mb-2">
                                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                        <div><strong><?= e((string)($s['numero_solicitud'] ?? 'Solicitud')) ?></strong> — <?= e((string)($s['nombre_area'] ?? '')) ?><div class="small text-muted"><?= e((string)($s['nombre_proceso'] ?? '')) ?> · <?= e((string)($s['solicitante'] ?? '')) ?></div></div>
                                        <div class="small">Recibido: <strong><?= e(number_format((float)$linea['recibido'], 2, ',', '.')) ?></strong> · Distribuido: <strong><?= e(number_format((float)$linea['distribuido'], 2, ',', '.')) ?></strong> · Saldo: <strong><?= e(number_format($disp, 2, ',', '.')) ?></strong></div>
                                    </div>
                                    <?php if (!$fichas): ?>
                                        <div class="alert alert-warning py-2 mb-0 small">Esta línea no tiene fichas destino asociadas en la solicitud. No se habilita la distribución hasta corregir el destino.</div>
                                    <?php else: ?>
                                        <form method="post" action="<?= e(url('/materiales/recepcion-distribucion/distribuir')) ?>" class="row g-2 align-items-end">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id_detalle" value="<?= (int)$linea['id_detalle'] ?>">
                                            <div class="col-12 col-md-4"><label class="form-label small">Ficha destino</label><select class="form-select form-select-sm" name="id_ficha" required <?= $disp <= 0 ? 'disabled' : '' ?>><option value="">Seleccione ficha</option><?php foreach ($fichas as $f): ?><option value="<?= (int)$f['id_ficha'] ?>"><?= e((string)$f['numero_ficha']) ?><?= $f['cantidad_destinada'] !== null ? ' · máximo ' . e(number_format((float)$f['cantidad_destinada'], 2, ',', '.')) : '' ?></option><?php endforeach; ?></select></div>
                                            <div class="col-6 col-md-2"><label class="form-label small">Cantidad</label><input class="form-control form-control-sm" type="number" name="cantidad" min="0.01" max="<?= e((string)$disp) ?>" step="0.01" required <?= $disp <= 0 ? 'disabled' : '' ?>></div>
                                            <div class="col-12 col-md-4"><label class="form-label small">Observación</label><input class="form-control form-control-sm" name="observaciones" maxlength="1000" placeholder="Opcional" <?= $disp <= 0 ? 'disabled' : '' ?>></div>
                                            <div class="col-6 col-md-2"><button class="btn btn-sm btn-success w-100" type="submit" <?= $disp <= 0 ? 'disabled' : '' ?>>Asignar</button></div>
                                        </form>
                                    <?php endif; ?>
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
