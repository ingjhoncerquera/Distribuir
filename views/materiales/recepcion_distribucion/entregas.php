<?php
declare(strict_types=1);
$tituloPagina = 'Entregas de materiales';
require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';

$solicitudes = is_array($solicitudes ?? null) ? $solicitudes : [];
?>
<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="fw-bold mb-1"><i class="bi bi-file-earmark-check me-2"></i>Entregas de materiales</h2>
        <p class="text-muted mb-0">Solicitudes avaladas por instructor. Descargue el F-14 y el acta con las cantidades distribuidas registradas.</p>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3"><strong>Solicitudes avaladas</strong></div>
        <?php if (!$solicitudes): ?>
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox display-5 text-muted"></i>
                <h5 class="mt-3">No hay solicitudes avaladas</h5>
                <p class="text-muted mb-0">Cuando una solicitud sea avalada, aparecerá aquí con los formatos para descargar.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Solicitud</th><th>Instructor solicitante</th><th>Área / proceso</th><th class="text-center">Materiales</th><th class="text-end">Distribuido</th><th style="min-width:290px">Formatos</th></tr></thead>
                    <tbody>
                    <?php foreach ($solicitudes as $s): ?>
                        <?php
                        $detalles = is_array($s['detalles'] ?? null) ? $s['detalles'] : [];
                        $totalDistribuido = 0.0;
                        foreach ($detalles as $d) $totalDistribuido += (float)($d['distribuido'] ?? 0);
                        $idSolicitud = (int)($s['id_solicitud'] ?? 0);
                        ?>
                        <tr>
                            <td><div class="fw-semibold"><?= e((string)($s['numero_solicitud'] ?? ('Solicitud #' . $idSolicitud))) ?></div><small class="text-muted"><?= e((string)($s['titulo'] ?? '')) ?></small></td>
                            <td><?= e((string)($s['solicitante'] ?? 'Instructor')) ?></td>
                            <td><?= e((string)($s['nombre_area'] ?? '')) ?><div class="small text-muted"><?= e((string)($s['nombre_proceso'] ?? '')) ?></div></td>
                            <td class="text-center"><?= count($detalles) ?></td>
                            <td class="text-end fw-semibold"><?= e(number_format($totalDistribuido, 2, ',', '.')) ?></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/materiales/solicitudes/exportar-individual?id=' . $idSolicitud)) ?>"><i class="bi bi-file-earmark-excel me-1"></i>Descargar F-14</a>
                                    <a class="btn btn-sm btn-outline-success" href="<?= e(url('/materiales/recepcion-distribucion/acta?id_solicitud=' . $idSolicitud)) ?>"><i class="bi bi-file-earmark-word me-1"></i>Descargar acta</a>
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
<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>
