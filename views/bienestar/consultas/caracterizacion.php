<?php
declare(strict_types=1);

/* SITA - Consulta de ficha de caracterización. Diseño alineado con Promoción y Prevención. */
if (!function_exists('fcConsultaEsc')) {
    function fcConsultaEsc(mixed $valor): string { return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8'); }
}
$aprendices = is_array($aprendices ?? null) ? $aprendices : [];
$fichas = is_array($fichas ?? null) ? $fichas : [];
$filtros = is_array($filtros ?? null) ? $filtros : [];
$opciones = is_array($opciones ?? null) ? $opciones : [];
$estadisticas = is_array($estadisticas ?? null) ? $estadisticas : [];
$pagina = max(1, (int)($pagina ?? 1));
$total = max(0, (int)($total ?? 0));
$totalPaginas = max(1, (int)($totalPaginas ?? 1));
$urlConsulta = url('/bienestar/consultas/caracterizacion');
$baseQuery = array_filter($filtros, static fn($v) => $v !== null && $v !== '');
$urlPagina = static function(int $n) use ($urlConsulta, $baseQuery): string { $q=$baseQuery; $q['pagina']=$n; return $urlConsulta.'?'.http_build_query($q); };
$contar = static function(string $clave) use ($estadisticas): int { $s=0; foreach (($estadisticas[$clave] ?? []) as $r) $s += (int)($r['cantidad'] ?? 0); return $s; };
$totalActualizados = 0;
foreach (($estadisticas['actualizacion'] ?? []) as $estadoFila) {
    if (strtolower(trim((string)($estadoFila['etiqueta'] ?? ''))) === 'actualizado') {
        $totalActualizados += (int)($estadoFila['cantidad'] ?? 0);
    }
}
$totalPoblacionClasificada = 0;
foreach (($estadisticas['poblacion'] ?? []) as $poblacionFila) {
    if (strtolower(trim((string)($poblacionFila['etiqueta'] ?? ''))) !== 'sin clasificación') {
        $totalPoblacionClasificada += (int)($poblacionFila['cantidad'] ?? 0);
    }
}
$sexoJson = json_encode($estadisticas['sexo'] ?? [], JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
$municipioJson = json_encode(array_slice($estadisticas['municipio'] ?? [],0,12), JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
$estratoJson = json_encode($estadisticas['estrato'] ?? [], JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= fcConsultaEsc($tituloPagina ?? 'Consulta de Ficha de Caracterización') ?> | SITA</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root{--fc-primary:#174a78;
--fc-dark:#103653;
--fc-secondary:#2676a8;
--fc-bg:#f3f6fa;
--fc-card:#fff;
--fc-text:#263445;
--fc-muted:#64748b;
--fc-border:#dfe7ef;
--fc-green:#16835d;
--fc-orange:#bd7614;
--fc-red:#b83c48;
--fc-radius:14px}
.fc-page{background:var(--fc-bg);
 font-size: 14px;
  line-height: 1.5;
  min-height:75vh;
padding:12px 0 34px;
color:var(--fc-text);
font-family:Arial,Helvetica,sans-serif}

.fc-container{
width:min(1450px,calc(100% - 32px));
margin:0 auto;
min-width:0
}


.fc-header{
display:flex;
justify-content:space-between;
align-items:center;
gap:16px;
flex-wrap:wrap;
margin-bottom:22px}


.fc-header h1{
margin:0 0 7px;
font-size:clamp(23px,3vw,31px);

color:var(--pyp-primary-dark)}


.fc-header p{
margin:0;
color:var(--fc-muted);
font-size:14px;
line-height:1.5}

.fc-card{background:var(--fc-card);
border:1px solid var(--fc-border);
border-radius:var(--fc-radius);
padding:20px;margin-bottom:20px;
box-shadow:0 3px 12px rgba(20,52,80,.045);
min-width:0}.fc-card h2{color:var(--fc-dark);
font-size:19px;margin:0 0 16px}
.fc-card h3{color:
var(--fc-primary);font-size:16px;margin:0 0 12px}
.fc-summary{display:grid;
grid-template-columns:repeat(4,minmax(0,1fr));
gap:14px;margin-bottom:20px}
.fc-stat{background:#fff;border:1px solid var(--fc-border);border-radius:13px;padding:17px;min-width:0}.fc-stat-label{color:var(--fc-muted);font-size:13px;line-height:1.4;margin-bottom:8px}.fc-stat-value{font-size:clamp(24px,2.2vw,31px);font-weight:800;color:var(--fc-primary-dark,var(--fc-dark));overflow-wrap:anywhere}.fc-stat.green .fc-stat-value{color:var(--fc-green)}.fc-stat.orange .fc-stat-value{color:var(--fc-orange)}.fc-stat.red .fc-stat-value{color:var(--fc-red)}.fc-form-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;align-items:end}.fc-field label{display:block;font-size:13px;font-weight:700;color:#34465b;margin-bottom:6px}.fc-field input,.fc-field select{width:100%;min-height:43px;padding:10px 11px;border:1px solid #cbd6e2;border-radius:9px;background:#fff;color:var(--fc-text);font:inherit;font-size:14px;box-sizing:border-box}.fc-actions{display:flex;gap:9px;flex-wrap:wrap;align-items:center}.fc-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:10px 15px;border:1px solid transparent;border-radius:9px;text-decoration:none;font-size:14px;font-weight:700;cursor:pointer;white-space:nowrap}.fc-btn-primary{background:var(--fc-primary);color:#fff}.fc-btn-primary:hover{background:var(--fc-dark);color:#fff}.fc-btn-secondary{background:#fff;color:var(--fc-primary);border-color:#cbd6e2}.fc-note{padding:12px 14px;border-radius:9px;background:#eef6fc;border:1px solid #d6e8f6;color:#35536c;font-size:13px;line-height:1.55;margin-bottom:16px}.fc-charts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.fc-chart-box{height:310px;position:relative}.fc-chart-empty{font-size:13px;color:var(--fc-muted);padding:16px;border:1px dashed var(--fc-border);border-radius:10px}.fc-table-wrap{width:100%;overflow-x:auto;border:1px solid var(--fc-border);border-radius:10px}.fc-table{width:100%;border-collapse:collapse;font-size:13px;min-width:1000px}.fc-table th,.fc-table td{padding:12px 13px;border-bottom:1px solid #e7edf3;text-align:left;vertical-align:top}.fc-table th{background:#edf3f8;color:var(--fc-dark);font-size:12px;text-transform:uppercase;letter-spacing:.035em;white-space:nowrap}.fc-table tbody tr:hover{background:#f8fbfd}.fc-secondary{font-size:12px;color:var(--fc-muted);margin-top:4px;line-height:1.45}.fc-name{font-weight:700;color:#1e3449}.fc-badge{display:inline-flex;padding:5px 9px;border-radius:999px;font-size:11px;font-weight:800;white-space:nowrap}.fc-ok{background:#e4f5ec;color:#176b49}.fc-pending{background:#fff2db;color:#89570b}.fc-neutral{background:#eaf0f6;color:#43566b}.fc-empty{padding:32px 16px;text-align:center;color:var(--fc-muted);line-height:1.7}.fc-footer{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding-top:16px;color:var(--fc-muted);font-size:13px}.fc-pagination{display:flex;gap:5px;align-items:center}.fc-pagination a,.fc-pagination span{min-width:34px;height:34px;display:inline-flex;justify-content:center;align-items:center;border:1px solid var(--fc-border);border-radius:8px;text-decoration:none;color:var(--fc-primary);background:#fff;padding:0 9px}.fc-pagination .current{background:var(--fc-primary);border-color:var(--fc-primary);color:#fff;font-weight:800}
@media(max-width:1050px){.fc-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.fc-form-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:680px){.fc-container{width:calc(100% - 20px)}.fc-card{padding:15px}.fc-summary,.fc-form-grid,.fc-charts{grid-template-columns:1fr}.fc-chart-box{height:270px}
.fc-header{align-items:flex-start}}
</style>
</head>
<body>
<?php
/* El menú se carga dentro del body, igual que en actividad_fisica.php. */
$fcNavbarPath = defined('ROOT_PATH') ? ROOT_PATH . '/views/layout/navbar.php' : null;
if ($fcNavbarPath !== null && is_file($fcNavbarPath)) {
    require_once $fcNavbarPath;
}
?>
<main class="fc-page"><div class="fc-container">
    <header class="fc-header"><div><h1><?= fcConsultaEsc($tituloPagina ?? 'Consulta de Ficha de Caracterización') ?></h1><p>Consulta y analiza la información sociodemográfica y poblacional de los aprendices matriculados.</p></div><div class="fc-actions"><a class="fc-btn fc-btn-secondary" href="<?= fcConsultaEsc($urlConsulta) ?>">Limpiar filtros</a></div></header>

    <section class="fc-summary" aria-label="Resumen de caracterización">
        <div class="fc-stat"><div class="fc-stat-label">Registros encontrados</div><div class="fc-stat-value"><?= number_format($total,0,',','.') ?></div></div>
        <div class="fc-stat green"><div class="fc-stat-label">Con caracterización actualizada</div><div class="fc-stat-value"><?= number_format($totalActualizados,0,',','.') ?></div></div>
        <div class="fc-stat orange"><div class="fc-stat-label">Clasificación poblacional registrada</div><div class="fc-stat-value"><?= number_format($totalPoblacionClasificada,0,',','.') ?></div></div>
        <div class="fc-stat red"><div class="fc-stat-label">Sin clasificación poblacional</div><div class="fc-stat-value"><?= number_format(max(0,$total-$contar('poblacion')),0,',','.') ?></div></div>
    </section>

    <section class="fc-card"><h2>Filtros de consulta</h2><form method="get" action="<?= fcConsultaEsc($urlConsulta) ?>"><div class="fc-form-grid">
        <div class="fc-field"><label for="fc-buscar">Buscar aprendiz</label><input id="fc-buscar" name="buscar" value="<?= fcConsultaEsc($filtros['buscar'] ?? '') ?>" placeholder="Documento, nombre, ficha o programa"></div>
        <div class="fc-field"><label for="fc-ficha">Ficha de formación</label><select id="fc-ficha" name="id_ficha"><option value="">Todas las fichas</option><?php foreach($fichas as $f): ?><option value="<?= (int)$f['id'] ?>" <?= (string)($filtros['id_ficha'] ?? '') === (string)$f['id'] ? 'selected' : '' ?>><?= fcConsultaEsc($f['numero_ficha'] ?? '') ?> — <?= fcConsultaEsc($f['nombre_programa'] ?? '') ?></option><?php endforeach; ?></select></div>
        <div class="fc-field"><label for="fc-sexo">Género / sexo</label><select id="fc-sexo" name="sexo"><option value="">Todos</option><?php foreach(($opciones['sexo'] ?? []) as $v): ?><option value="<?= fcConsultaEsc($v) ?>" <?= ($filtros['sexo'] ?? '') === $v ? 'selected' : '' ?>><?= fcConsultaEsc($v) ?></option><?php endforeach; ?></select></div>
        <div class="fc-field"><label for="fc-departamento">Departamento</label><select id="fc-departamento" name="departamento"><option value="">Todos</option><?php foreach(($opciones['departamento'] ?? []) as $v): ?><option value="<?= fcConsultaEsc($v) ?>" <?= ($filtros['departamento'] ?? '') === $v ? 'selected' : '' ?>><?= fcConsultaEsc($v) ?></option><?php endforeach; ?></select></div>
        <div class="fc-field"><label for="fc-municipio">Municipio</label><select id="fc-municipio" name="municipio"><option value="">Todos</option><?php foreach(($opciones['municipio'] ?? []) as $v): ?><option value="<?= fcConsultaEsc($v) ?>" <?= ($filtros['municipio'] ?? '') === $v ? 'selected' : '' ?>><?= fcConsultaEsc($v) ?></option><?php endforeach; ?></select></div>
        <div class="fc-field"><label for="fc-eps">EPS</label><select id="fc-eps" name="eps"><option value="">Todas</option><?php foreach(($opciones['eps'] ?? []) as $v): ?><option value="<?= fcConsultaEsc($v) ?>" <?= ($filtros['eps'] ?? '') === $v ? 'selected' : '' ?>><?= fcConsultaEsc($v) ?></option><?php endforeach; ?></select></div>
        <div class="fc-field"><label for="fc-poblacion">Clasificación poblacional</label><select id="fc-poblacion" name="poblacion"><option value="">Todas</option><?php foreach(($opciones['poblacion'] ?? []) as $v): ?><option value="<?= fcConsultaEsc($v) ?>" <?= ($filtros['poblacion'] ?? '') === $v ? 'selected' : '' ?>><?= fcConsultaEsc($v) ?></option><?php endforeach; ?></select></div>
        <div class="fc-field"><label for="fc-estado">Estado de caracterización</label><select id="fc-estado" name="estado_caracterizacion"><option value="">Todos</option><option value="actualizado" <?= ($filtros['estado_caracterizacion'] ?? '') === 'actualizado' ? 'selected' : '' ?>>Actualizada</option><option value="pendiente" <?= ($filtros['estado_caracterizacion'] ?? '') === 'pendiente' ? 'selected' : '' ?>>Pendiente</option></select></div>
        <?php if (!empty($estratoDisponible)): ?><div class="fc-field"><label for="fc-estrato">Estrato socioeconómico</label><select id="fc-estrato" name="estrato"><option value="">Todos</option><?php foreach(['1','2','3','4','5','6','No registrado'] as $v): ?><option value="<?= fcConsultaEsc($v) ?>" <?= ($filtros['estrato'] ?? '') === $v ? 'selected' : '' ?>><?= fcConsultaEsc($v) ?></option><?php endforeach; ?></select></div><?php endif; ?>
        <div class="fc-actions"><button class="fc-btn fc-btn-primary" type="submit">Consultar</button><a class="fc-btn fc-btn-secondary" href="<?= fcConsultaEsc($urlConsulta) ?>">Restablecer</a></div>
    </div></form></section>

    <section class="fc-card"><h2>Indicadores de caracterización</h2><div class="fc-charts"><div><h3>Distribución por género / sexo</h3><?php if(!empty($estadisticas['sexo'])): ?><div class="fc-chart-box"><canvas id="fcChartSexo"></canvas></div><?php else: ?><div class="fc-chart-empty">No hay datos disponibles para graficar.</div><?php endif; ?></div><div><h3>Aprendices por municipio</h3><?php if(!empty($estadisticas['municipio'])): ?><div class="fc-chart-box"><canvas id="fcChartMunicipio"></canvas></div><?php else: ?><div class="fc-chart-empty">No hay datos disponibles para graficar.</div><?php endif; ?></div><div><h3>Clasificación poblacional</h3><?php if(!empty($estadisticas['poblacion'])): ?><div class="fc-chart-box"><canvas id="fcChartPoblacion"></canvas></div><?php else: ?><div class="fc-chart-empty">No hay clasificaciones poblacionales registradas.</div><?php endif; ?></div><div><h3>Distribución por estrato social</h3><?php if(!empty($estratoDisponible) && !empty($estadisticas['estrato'])): ?><div class="fc-chart-box"><canvas id="fcChartEstrato"></canvas></div><?php else: ?><div class="fc-chart-empty">El estrato social aún no está disponible como campo confirmado en la base de datos. Cuando esté almacenado, la consulta podrá representarlo aquí.</div><?php endif; ?></div></div></section>

    <section class="fc-card"><h2>Listado de aprendices</h2><p style="color:var(--fc-muted);font-size:13px;margin-top:-6px;margin-bottom:15px">Se encontraron <strong><?= number_format($total,0,',','.') ?></strong> registros con los filtros seleccionados.</p>
    <?php if($aprendices === []): ?><div class="fc-empty"><strong>No se encontraron aprendices</strong><br>Ajusta los filtros e intenta nuevamente.</div><?php else: ?><div class="fc-table-wrap"><table class="fc-table"><thead><tr><th>Ficha / programa</th><th>Identificación</th><th>Aprendiz</th><th>Sexo</th><th>Ubicación</th><th>Contacto</th><th>EPS</th><th>Estrato</th><th>Clasificación poblacional</th><th>Actualización</th><th>Estado aprendiz</th></tr></thead><tbody>
    <?php foreach($aprendices as $fila): $actualizado=($fila['estado_actualizacion'] ?? 'pendiente')==='actualizado'; $nombre=trim((string)($fila['nombres'] ?? '').' '.(string)($fila['apellidos'] ?? '')); ?><tr><td><strong><?= fcConsultaEsc($fila['numero_ficha'] ?? '') ?></strong><div class="fc-secondary"><?= fcConsultaEsc($fila['codigo_programa'] ?? '') ?><?= !empty($fila['nombre_programa']) ? ' — '.fcConsultaEsc($fila['nombre_programa']) : '' ?></div></td><td><?= fcConsultaEsc($fila['tipo_documento'] ?? '') ?><div class="fc-secondary"><?= fcConsultaEsc($fila['documento'] ?? '') ?></div></td><td><span class="fc-name"><?= fcConsultaEsc($nombre) ?></span></td><td><?= fcConsultaEsc($fila['sexo'] ?? '') ?: 'No registrado' ?></td><td><?= fcConsultaEsc($fila['municipio'] ?? '') ?: 'Sin municipio' ?><div class="fc-secondary"><?= fcConsultaEsc($fila['departamento'] ?? '') ?></div></td><td><?= fcConsultaEsc($fila['telefono'] ?? '') ?: 'Sin teléfono' ?><div class="fc-secondary"><?= fcConsultaEsc($fila['correo'] ?? '') ?: 'Sin correo' ?></div></td><td><?= fcConsultaEsc($fila['nombre_eps'] ?? '') ?: 'No registrada' ?></td><td><?= fcConsultaEsc($fila['estrato_social'] ?? '') ?: 'No registrado' ?></td><td><?= fcConsultaEsc($fila['tipo_poblacion'] ?? '') ?: 'Sin caracterizar' ?></td><td><span class="fc-badge <?= $actualizado ? 'fc-ok' : 'fc-pending' ?>"><?= $actualizado ? 'Actualizada' : 'Pendiente' ?></span><?php if(!empty($fila['fecha_actualizacion'])): ?><div class="fc-secondary"><?= fcConsultaEsc($fila['fecha_actualizacion']) ?></div><?php endif; ?></td><td><span class="fc-badge fc-neutral"><?= fcConsultaEsc($fila['estado_aprendiz'] ?? 'Sin estado') ?></span></td></tr><?php endforeach; ?>
    </tbody></table></div><div class="fc-footer"><span>Página <?= $pagina ?> de <?= $totalPaginas ?></span><nav class="fc-pagination" aria-label="Paginación"><?php if($pagina>1): ?><a href="<?= fcConsultaEsc($urlPagina($pagina-1)) ?>" aria-label="Página anterior">‹</a><?php endif; ?><?php for($n=max(1,$pagina-2),$fin=min($totalPaginas,$pagina+2);$n<=$fin;$n++): ?><?php if($n===$pagina): ?><span class="current" aria-current="page"><?= $n ?></span><?php else: ?><a href="<?= fcConsultaEsc($urlPagina($n)) ?>"><?= $n ?></a><?php endif; ?><?php endfor; ?><?php if($pagina<$totalPaginas): ?><a href="<?= fcConsultaEsc($urlPagina($pagina+1)) ?>" aria-label="Página siguiente">›</a><?php endif; ?></nav></div><?php endif; ?></section>
    <div style="text-align:center;color:var(--fc-muted);font-size:12px;padding:3px 0 10px">SITA · Sistema de Trazabilidad de Aprendices</div>
</div></main>
<script>
document.addEventListener('DOMContentLoaded',function(){
 const sexo=<?= $sexoJson ?: '[]' ?>, municipios=<?= $municipioJson ?: '[]' ?>, estratos=<?= $estratoJson ?: '[]' ?>;
 function grafico(id,datos,tipo,apilado){const el=document.getElementById(id);if(!el||typeof Chart==='undefined'||!Array.isArray(datos)||!datos.length)return;const labels=datos.map(x=>x.etiqueta||'No registrado'),values=datos.map(x=>Number(x.cantidad)||0);new Chart(el,{type:tipo,data:{labels:labels,datasets:[{label:'Cantidad de aprendices',data:values,backgroundColor:['#174a78','#2676a8','#16835d','#bd7614','#b83c48','#6d5aa6','#3b8f9c','#8b6a45','#718096','#c06c84','#4d7c0f','#64748b'],borderColor:'#fff',borderWidth:2,borderRadius:tipo==='bar'?5:0}]},options:{responsive:true,maintainAspectRatio:false,indexAxis:apilado?'y':'x',plugins:{legend:{display:tipo==='doughnut',position:'bottom',labels:{usePointStyle:true,padding:14}},tooltip:{callbacks:{label:c=>`${c.label}: ${(Number(c.raw)||0).toLocaleString('es-CO')}`}}},scales:tipo==='bar'?{x:{beginAtZero:true,ticks:{precision:0}},y:{grid:{display:false}}}:{}}});}
 grafico('fcChartSexo',sexo,'doughnut',false);grafico('fcChartMunicipio',municipios,'bar',true);grafico('fcChartPoblacion',<?= json_encode($estadisticas['poblacion'] ?? [], JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?: '[]' ?>,'doughnut',false);grafico('fcChartEstrato',estratos,'bar',true);
});
</script>


</body>
</html>
