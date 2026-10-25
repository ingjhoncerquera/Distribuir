<?php
declare(strict_types=1);
$tituloPagina = $tituloPagina ?? 'Consolidar materiales';
?>
<?php require ROOT_PATH . '/views/layout/header.php'; ?>

<main class="container" style="max-width:1400px;margin:28px auto;padding:0 16px;">
    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px;margin-bottom:18px;">
        <div>
            <h1 style="margin:0 0 6px;">Consolidar materiales</h1>
            <p style="margin:0;color:#5b6470;">Seleccione las solicitudes que ya fueron avaladas para generar una sola Lista Maestra GFPI-F-186.</p>
        </div>
        <a class="btn btn-secondary" href="<?= e(url('/materiales/solicitudes/aval')) ?>">
            <span aria-hidden="true">↩️</span> Volver al aval
        </a>
    </div>

    <?php if (empty($solicitudes)): ?>
        <div class="alert alert-info">No hay solicitudes avaladas disponibles para consolidar en los procesos asignados a su coordinación.</div>
    <?php else: ?>
        <form method="post" action="<?= e(url('/materiales/solicitudes/exportar-consolidado-gfpi')) ?>" onsubmit="return confirm('¿Generar el GFPI-F-186 consolidado con las solicitudes seleccionadas?');">
            <?= csrfField() ?>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;">
                <button type="button" class="btn btn-outline-primary" onclick="document.querySelectorAll('.solicitud-consolidar').forEach(x=>x.checked=true)">
                    <span aria-hidden="true">☑️</span> Seleccionar todas
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="document.querySelectorAll('.solicitud-consolidar').forEach(x=>x.checked=false)">
                    <span aria-hidden="true">🧹</span> Limpiar selección
                </button>
                <button type="submit" class="btn btn-primary">
                    <span aria-hidden="true">📄</span> Generar GFPI-F-186 consolidado
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width:48px;"><input type="checkbox" aria-label="Seleccionar todas" onclick="document.querySelectorAll('.solicitud-consolidar').forEach(x=>x.checked=this.checked)"></th>
                            <th>N.º solicitud</th>
                            <th>Título</th>
                            <th>Área</th>
                            <th>Proceso</th>
                            <th>Solicitante</th>
                            <th>Fecha de aval</th>
                            <th>Materiales</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($solicitudes as $s): ?>
                        <tr>
                            <td><input class="solicitud-consolidar" type="checkbox" name="ids_solicitud[]" value="<?= (int) $s['id'] ?>" aria-label="Seleccionar solicitud"></td>
                            <td><?= e((string)($s['numero_solicitud'] ?? $s['id'])) ?></td>
                            <td><?= e((string)($s['titulo'] ?? '')) ?></td>
                            <td><?= e((string)($s['nombre_area'] ?? '')) ?></td>
                            <td><?= e((string)($s['nombre_proceso'] ?? '')) ?><?= !empty($s['anio']) ? ' (' . (int)$s['anio'] . ')' : '' ?></td>
                            <td><?= e((string)($s['nombre_solicitante'] ?? '')) ?></td>
                            <td><?= e((string)($s['fecha_revision'] ?? '')) ?></td>
                            <td><?= (int)($s['total_materiales'] ?? 0) ?></td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/materiales/solicitudes/ver?id=' . (int)$s['id'])) ?>" title="Ver detalle de la solicitud" aria-label="Ver solicitud">
                                    <span aria-hidden="true">👁️</span> Ver solicitud
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p style="color:#5b6470;font-size:.92rem;">Los materiales solo se agrupan cuando coinciden el código UNSPSC, la descripción, la descripción técnica, la unidad de medida y la clasificación. Las observaciones y los números de solicitudes de origen quedan registrados en el consolidado.</p>

            <button type="submit" class="btn btn-primary">
                <span aria-hidden="true">📄</span> Generar GFPI-F-186 consolidado
            </button>
        </form>
    <?php endif; ?>
</main>
<?php require ROOT_PATH . '/views/layout/footer.php'; ?>
