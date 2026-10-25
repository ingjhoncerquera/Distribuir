<?php
declare(strict_types=1);
$h=static fn($v):string=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$ok=flash('success');$error=flash('error');
?>
<div class="container-fluid py-4">
 <div class="mb-4"><h1 class="h3">Recepción y distribución de materiales</h1><p class="text-muted">Solo solicitudes avaladas. Registre recepciones parciales y distribuya manualmente las cantidades efectivamente recibidas.</p></div>
 <?php if($ok):?><div class="alert alert-success"><?=$h($ok)?></div><?php endif;?>
 <?php if($error):?><div class="alert alert-danger"><?=$h($error)?></div><?php endif;?>
 <?php if(!$solicitudes):?><div class="alert alert-info">No hay solicitudes avaladas disponibles en su ámbito.</div><?php endif;?>
 <?php foreach($solicitudes as $s):?>
 <div class="card shadow-sm mb-4">
  <div class="card-header"><strong><?=$h($s['numero_solicitud'])?> — <?=$h($s['titulo'])?></strong><div class="small text-muted"><?=$h($s['nombre_proceso'])?> (<?=$h($s['anio'])?>) · <?=$h($s['nombre_area'])?> · <?=$h($s['solicitante'])?></div></div>
  <div class="card-body">
  <?php foreach($s['detalles'] as $d):$sol=(float)$d['cantidad_solicitada'];$rec=(float)$d['recibido'];$dist=(float)$d['distribuido'];$pend=max(0,$sol-$rec);$disp=max(0,$rec-$dist);?>
   <div class="border rounded p-3 mb-3">
    <div class="row g-2 mb-3">
     <div class="col-lg-4"><strong><?=$h($d['descripcion']??'Material')?></strong><div class="small text-muted">Código: <?=$h($d['codigo_unspsc']??'—')?> · Unidad: <?=$h($d['unidad_medida'])?></div></div>
     <div class="col-6 col-lg"><small class="text-muted d-block">Solicitado</small><strong><?=$h($sol)?></strong></div>
     <div class="col-6 col-lg"><small class="text-muted d-block">Recibido</small><strong><?=$h($rec)?></strong></div>
     <div class="col-6 col-lg"><small class="text-muted d-block">Pendiente recibir</small><strong><?=$h($pend)?></strong></div>
     <div class="col-6 col-lg"><small class="text-muted d-block">Disponible distribuir</small><strong><?=$h($disp)?></strong></div>
    </div>
    <div class="row g-3">
     <div class="col-xl-5"><h2 class="h6">Registrar recepción real</h2>
      <form method="post" action="<?=$h(url('/materiales/recepcion-distribucion/recibir'))?>" class="row g-2">
       <?=csrfField()?><input type="hidden" name="id_detalle" value="<?=(int)$d['id_detalle']?>">
       <div class="col-md-4"><label class="form-label small">Cantidad recibida</label><input class="form-control" type="number" name="cantidad" min="0.01" max="<?=$h($pend)?>" step="0.01" required <?=$pend<=0?'disabled':''?>></div>
       <div class="col-md-4"><label class="form-label small">Fecha</label><input class="form-control" type="date" name="fecha" value="<?=date('Y-m-d')?>" required <?=$pend<=0?'disabled':''?>></div>
       <div class="col-md-4"><label class="form-label small">Remisión / soporte</label><input class="form-control" name="remision" maxlength="100" placeholder="Número"></div>
       <div class="col-12"><label class="form-label small">Observaciones</label><input class="form-control" name="observaciones" maxlength="1000"></div>
       <div class="col-12"><button class="btn btn-primary btn-sm" <?=$pend<=0?'disabled':''?>>Guardar recepción</button></div>
      </form>
     </div>
     <div class="col-xl-7"><h2 class="h6">Distribuir material recibido</h2>
      <form method="post" action="<?=$h(url('/materiales/recepcion-distribucion/distribuir'))?>" class="row g-2">
       <?=csrfField()?><input type="hidden" name="id_detalle" value="<?=(int)$d['id_detalle']?>">
       <div class="col-md-5"><label class="form-label small">Ficha destino</label><select class="form-select" name="id_ficha" required <?=$disp<=0||!$d['fichas']?'disabled':''?>><option value="">Seleccione ficha</option>
       <?php foreach($d['fichas'] as $f):?><option value="<?=(int)$f['id_ficha']?>"><?=$h($f['numero_ficha'])?><?=$f['cantidad_destinada']!==null?' (destinado: '.$h($f['cantidad_destinada']).')':''?></option><?php endforeach;?></select><?php if(!$d['fichas']):?><small class="text-muted">La línea no tiene fichas destino configuradas.</small><?php endif;?></div>
       <div class="col-md-3"><label class="form-label small">Cantidad</label><input class="form-control" type="number" name="cantidad" min="0.01" max="<?=$h($disp)?>" step="0.01" required <?=$disp<=0||!$d['fichas']?'disabled':''?>></div>
       <div class="col-md-4"><label class="form-label small">Observaciones</label><input class="form-control" name="observaciones" maxlength="1000"></div>
       <div class="col-12"><button class="btn btn-success btn-sm" <?=$disp<=0||!$d['fichas']?'disabled':''?>>Guardar distribución</button></div>
      </form>
     </div>
    </div>
   </div>
  <?php endforeach;?>
  </div>
 </div>
 <?php endforeach;?>
 <div class="card mt-4"><div class="card-header"><h2 class="h5 mb-0">Trazabilidad de movimientos recientes</h2></div><div class="table-responsive"><table class="table table-striped table-sm mb-0"><thead><tr><th>Tipo</th><th>Fecha</th><th>Solicitud</th><th>Material</th><th>Ficha</th><th>Cantidad</th><th>Usuario</th><th>Observaciones</th></tr></thead><tbody>
 <?php if(!$historial):?><tr><td colspan="8" class="text-center text-muted">No hay movimientos registrados.</td></tr><?php else:foreach($historial as $m):?><tr><td><?=$h($m['tipo'])?></td><td><?=$h($m['fecha'])?></td><td><?=$h($m['numero_solicitud'])?></td><td><?=$h($m['material']??'')?></td><td><?=$h($m['numero_ficha']??'—')?></td><td><?=$h($m['cantidad'])?></td><td><?=$h($m['usuario']??'—')?></td><td><?=nl2br($h($m['observaciones']??''))?></td></tr><?php endforeach;endif;?>
 </tbody></table></div></div>
</div>
