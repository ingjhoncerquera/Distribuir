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
        if (!isset($productos[$key])) $productos[$key] = ['descripcion'=>$detalle['descripcion'] ?? 'Material','codigo'=>$detalle['codigo_unspsc'] ?? '','unidad'=>$detalle['unidad_medida'] ?? 'Unidad','solicitado'=>0.0,'recibido'=>0.0,'pendiente'=>0.0,'lineas'=>[]];
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
  <div><h2 class="fw-bold mb-1"><i class="bi bi-box-arrow-in-down me-2"></i>Recepción de materiales</h2><p class="text-muted mb-0">Todas las solicitudes están visibles directamente, agrupadas por producto.</p></div>
  <div class="d-flex gap-2"><a class="btn btn-success" href="<?= e(url('/materiales/recepcion-distribucion/distribucion')) ?>"><i class="bi bi-box-arrow-up-right me-1"></i>Ir a distribución</a><a class="btn btn-outline-secondary" href="<?= e(url('/materiales/solicitudes')) ?>">Solicitudes</a></div>
 </div>
 <?php if ($success): ?><div class="alert alert-success"><?= e((string)$success) ?></div><?php endif; ?>
 <?php if ($error): ?><div class="alert alert-danger"><?= e((string)$error) ?></div><?php endif; ?>
 <form method="post" action="<?= e(url('/materiales/recepcion-distribucion/recibir-multiple')) ?>" id="form-recepcion-lote" class="card border-primary shadow-sm mb-3">
  <?= csrfField() ?>
  <div class="card-header bg-primary text-white"><strong><i class="bi bi-clipboard-check me-2"></i>Registrar varias recepciones</strong></div>
  <div class="card-body"><div class="row g-2 align-items-end">
   <div class="col-6 col-md-2"><label class="form-label" for="fecha-lote">Fecha</label><input class="form-control form-control-sm" type="date" id="fecha-lote" name="fecha" value="<?= e(date('Y-m-d')) ?>" required></div>
   <div class="col-6 col-md-3"><label class="form-label" for="remision-lote">Remisión general</label><input class="form-control form-control-sm" id="remision-lote" name="remision" maxlength="100" placeholder="N.º remisión"></div>
   <div class="col-12 col-md-5"><label class="form-label" for="obs-lote">Observaciones generales</label><input class="form-control form-control-sm" id="obs-lote" name="observaciones" maxlength="1000" placeholder="Opcional"></div>
   <div class="col-12 col-md-2 d-grid"><button class="btn btn-primary btn-sm" id="btn-lote" type="submit"><i class="bi bi-save me-1"></i>Guardar lote</button></div>
  </div><small class="text-muted d-block mt-2">Diligencie las cantidades recibidas en las líneas correspondientes. Las líneas vacías se omiten.</small></div>
 </form>
 <div class="card border-0 shadow-sm"><div class="card-header bg-white d-flex justify-content-between align-items-center"><strong><i class="bi bi-table me-2"></i>Productos y solicitudes</strong><span class="small text-muted">Solo solicitudes avaladas</span></div>
 <div class="table-responsive"><table class="table table-hover align-middle mb-0">
 <thead class="table-light"><tr><th style="min-width:250px">Producto / solicitud</th><th class="text-end">Solicitado</th><th class="text-end">Recibido</th><th class="text-end">Pendiente</th><th style="min-width:135px">Cantidad a recibir</th><th style="min-width:130px">Remisión individual</th><th style="min-width:160px">Observación individual</th><th>Acción</th></tr></thead><tbody>
 <?php if (!$productos): ?><tr><td colspan="8" class="text-center text-muted py-5">No hay solicitudes avaladas para su coordinación.</td></tr>
 <?php else: foreach ($productos as $producto): ?>
 <tr class="table-light"><td><strong><?= e((string)$producto['descripcion']) ?></strong><br><small class="text-muted"><?= e((string)$producto['codigo']) ?> · <?= e((string)$producto['unidad']) ?></small></td><td class="text-end fw-semibold"><?= e(number_format($producto['solicitado'],2,',','.')) ?></td><td class="text-end text-success fw-semibold"><?= e(number_format($producto['recibido'],2,',','.')) ?></td><td class="text-end"><span class="badge <?= $producto['pendiente']>0?'text-bg-warning':'text-bg-light border text-dark' ?>"><?= e(number_format($producto['pendiente'],2,',','.')) ?></span></td><td colspan="4" class="small text-muted"><?= count($producto['lineas']) ?> solicitud(es) visible(s) debajo</td></tr>
 <?php foreach ($producto['lineas'] as $linea): $s=$linea['solicitud']; $pend=(float)$linea['pendiente']; ?>
 <tr><td class="ps-4"><strong><?= e((string)($s['numero_solicitud'] ?? 'Solicitud')) ?> — <?= e((string)($s['nombre_area'] ?? '')) ?></strong><div class="small text-muted"><?= e((string)($s['nombre_proceso'] ?? '')) ?> · <?= e((string)($s['solicitante'] ?? '')) ?></div></td>
 <td class="text-end"><?= e(number_format((float)$linea['cantidad_solicitada'],2,',','.')) ?></td><td class="text-end text-success"><?= e(number_format((float)$linea['recibido'],2,',','.')) ?></td><td class="text-end"><span class="badge <?= $pend>0?'text-bg-warning':'text-bg-light border text-dark' ?>"><?= e(number_format($pend,2,',','.')) ?></span></td>
 <td><input class="form-control form-control-sm cantidad-lote" type="number" data-id="<?= (int)$linea['id_detalle'] ?>" data-max="<?= e((string)$pend) ?>" min="0" max="<?= e((string)$pend) ?>" step="0.01" placeholder="0,00" <?= $pend<=0?'disabled':'' ?>></td>
 <td><input class="form-control form-control-sm remision-linea" maxlength="100" placeholder="Opcional" <?= $pend<=0?'disabled':'' ?>></td><td><input class="form-control form-control-sm obs-linea" maxlength="1000" placeholder="Opcional" <?= $pend<=0?'disabled':'' ?>></td>
 <td><form method="post" action="<?= e(url('/materiales/recepcion-distribucion/recibir')) ?>" class="form-individual"><?= csrfField() ?><input type="hidden" name="id_detalle" value="<?= (int)$linea['id_detalle'] ?>"><input type="hidden" name="fecha" value="<?= e(date('Y-m-d')) ?>"><input type="hidden" name="cantidad"><input type="hidden" name="remision"><input type="hidden" name="observaciones"><button class="btn btn-sm btn-outline-primary" type="submit" <?= $pend<=0?'disabled':'' ?>>Registrar</button></form><?php if($pend<=0): ?><small class="text-success">Completa</small><?php endif; ?></td></tr>
 <?php endforeach; endforeach; endif; ?>
 </tbody></table></div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
 const lote=document.getElementById('form-recepcion-lote');
 lote.addEventListener('submit', function(e) {
  lote.querySelectorAll('input[name^="recepciones["]').forEach(x=>x.remove());
  const filas=Array.from(document.querySelectorAll('.cantidad-lote:not(:disabled)'));
  const elegidas=[]; let invalido=null;
  filas.forEach(c=>{if(c.value.trim()==='')return;const n=Number(c.value.replace(',','.'));const max=Number(c.dataset.max);if(!Number.isFinite(n)||n<=0||n>max+0.00001){invalido=c;return;}const tr=c.closest('tr');elegidas.push({id:c.dataset.id,cantidad:String(n),remision:tr.querySelector('.remision-linea').value.trim(),observaciones:tr.querySelector('.obs-linea').value.trim()});});
  if(invalido){e.preventDefault();alert('La cantidad debe ser mayor que cero y no puede superar el pendiente.');invalido.focus();return;}
  if(!elegidas.length){e.preventDefault();alert('Ingrese al menos una cantidad para registrar.');return;}
  elegidas.forEach((item,i)=>{Object.keys({id_detalle:item.id,cantidad:item.cantidad,remision:item.remision,observaciones:item.observaciones}).forEach(k=>{const h=document.createElement('input');h.type='hidden';h.name='recepciones['+i+']['+k+']';h.value=({id_detalle:item.id,cantidad:item.cantidad,remision:item.remision,observaciones:item.observaciones})[k];lote.appendChild(h);});});
  if(!confirm('¿Confirma registrar las recepciones de '+elegidas.length+' línea(s)?')){e.preventDefault();lote.querySelectorAll('input[name^="recepciones["]').forEach(x=>x.remove());}
 });
 document.querySelectorAll('.form-individual').forEach(form=>form.addEventListener('submit',function(e){const tr=form.closest('tr');const q=tr.querySelector('.cantidad-lote');const n=Number(q.value.replace(',','.'));if(!q.value.trim()||!Number.isFinite(n)||n<=0||n>Number(q.dataset.max)+0.00001){e.preventDefault();alert('Ingrese una cantidad mayor que cero y no superior al pendiente.');q.focus();return;}form.querySelector('[name="cantidad"]').value=String(n);form.querySelector('[name="remision"]').value=tr.querySelector('.remision-linea').value;form.querySelector('[name="observaciones"]').value=tr.querySelector('.obs-linea').value;}));
});
</script>
</body></html>
