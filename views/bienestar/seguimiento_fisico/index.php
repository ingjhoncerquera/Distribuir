<?php
declare(strict_types=1);

/* SITA - Vista de Seguimiento físico PyP. Ubicación sugerida: views/bienestar/seguimiento_fisico/index.php */
if (!function_exists('sfEsc')) {
    function sfEsc(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
}
$tituloPagina = $tituloPagina ?? 'Seguimiento Físico PyP';
$fichas = is_array($fichas ?? null) ? $fichas : [];
$aprendices = is_array($aprendices ?? null) ? $aprendices : [];
$historial = is_array($historial ?? null) ? $historial : [];
$idFicha = (int)($idFicha ?? 0);
$idAprendiz = (int)($idAprendiz ?? 0);
$buscar = (string)($buscar ?? '');
$aprendizSeleccionado = is_array($aprendizSeleccionado ?? null) ? $aprendizSeleccionado : null;
$puedeRegistrar = (bool)($puedeRegistrar ?? false);
$mensaje = (string)($mensaje ?? '');
$error = (string)($error ?? '');
$baseUrl = url('/bienestar/seguimiento-fisico');
$tokenCsrf = (string)($_SESSION['csrf_token'] ?? '');
?>
<?php
$navbarPath = defined('ROOT_PATH') ? ROOT_PATH . '/views/layout/navbar.php' : null;
if ($navbarPath && is_file($navbarPath)) require $navbarPath;
?>
<style>
.sf-page{max-width:1500px;margin:0 auto;padding:28px 22px 45px;color:#203244;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif}
.sf-head{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:22px}.sf-head h1{margin:0;font-size:27px;color:#18364b}.sf-head p{margin:7px 0 0;color:#657786}.sf-card{background:#fff;border:1px solid #e1e8ed;border-radius:14px;padding:22px;margin-bottom:20px;box-shadow:0 4px 15px #18364b0a}.sf-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px}.sf-field{display:flex;flex-direction:column;gap:6px}.sf-field label{font-size:13px;font-weight:700;color:#415466}.sf-field input,.sf-field select,.sf-field textarea{width:100%;box-sizing:border-box;border:1px solid #cfd9e0;border-radius:8px;padding:11px 12px;font:inherit;background:#fff;color:#243746}.sf-field textarea{min-height:82px;resize:vertical}.sf-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}.sf-btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:8px;padding:11px 16px;background:#176b50;color:white;text-decoration:none;font-weight:700;cursor:pointer}.sf-btn.secondary{background:#edf3f6;color:#244457}.sf-msg{padding:12px 15px;border-radius:8px;margin-bottom:16px}.sf-ok{background:#e8f5ed;color:#205f3e}.sf-error{background:#fff0ed;color:#9c3528}.sf-table-wrap{overflow-x:auto}.sf-table{width:100%;border-collapse:collapse;font-size:13px}.sf-table th,.sf-table td{padding:12px 10px;border-bottom:1px solid #e8edf0;text-align:left;white-space:nowrap}.sf-table th{background:#f5f8fa;color:#3b5262}.sf-table a{color:#176b50;font-weight:700;text-decoration:none}.sf-note{font-size:12px;line-height:1.6;color:#667887;background:#f5f8fa;border-left:3px solid #d7a443;padding:11px 13px;border-radius:5px;margin-top:14px}.sf-person{background:#eef7f1;border:1px solid #d5e9dc;border-radius:10px;padding:15px;margin-bottom:18px}.sf-person strong{font-size:17px}.sf-subtitle{font-size:17px;margin:0 0 17px;color:#21475b}@media(max-width:850px){.sf-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.sf-page{padding:18px 12px}.sf-grid{grid-template-columns:1fr}.sf-head h1{font-size:23px}.sf-card{padding:15px}}
</style>
<main class="sf-page">
    <header class="sf-head"><div><h1><?= sfEsc($tituloPagina) ?></h1><p>Registro trimestral de signos vitales, peso, talla e IMC. Historial individual del aprendiz.</p></div></header>
    <?php if ($mensaje !== ''): ?><div class="sf-msg sf-ok"><?= sfEsc($mensaje) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="sf-msg sf-error"><?= sfEsc($error) ?></div><?php endif; ?>

    <section class="sf-card">
        <h2 class="sf-subtitle">1. Buscar ficha y aprendiz</h2>
        <form method="get" action="<?= sfEsc($baseUrl) ?>">
            <div class="sf-grid">
                <div class="sf-field"><label for="id_ficha">Ficha asignada</label><select name="id_ficha" id="id_ficha" required><option value="">Seleccione una ficha</option><?php foreach ($fichas as $f): ?><option value="<?= (int)$f['id'] ?>" <?= $idFicha === (int)$f['id'] ? 'selected' : '' ?>><?= sfEsc($f['numero_ficha']) ?></option><?php endforeach; ?></select></div>
                <div class="sf-field"><label for="buscar">Documento o nombre</label><input id="buscar" name="buscar" value="<?= sfEsc($buscar) ?>" placeholder="Buscar aprendiz"></div>
                <div class="sf-field" style="justify-content:end"><button class="sf-btn" type="submit">Consultar aprendices</button></div>
            </div>
        </form>
        <?php if ($fichas === []): ?><p class="sf-note">No hay fichas asignadas disponibles para su usuario. Solicite al administrador que verifique su asignación en SITA.</p><?php endif; ?>
        <?php if ($idFicha > 0): ?>
        <div class="sf-table-wrap" style="margin-top:18px"><table class="sf-table"><thead><tr><th>Documento</th><th>Aprendiz</th><th>Última toma</th><th>Próximo control</th><th>Acción</th></tr></thead><tbody>
        <?php foreach ($aprendices as $a): $proxima = $a['proxima_fecha'] ?? null; $vencido = $proxima && $proxima < date('Y-m-d'); ?>
        <tr><td><?= sfEsc(($a['tipo_documento'] ?? '') . ' ' . ($a['documento'] ?? '')) ?></td><td><?= sfEsc(trim(($a['nombres'] ?? '') . ' ' . ($a['apellidos'] ?? ''))) ?></td><td><?= !empty($a['ultima_toma']) ? sfEsc(date('d/m/Y', strtotime($a['ultima_toma']))) : 'Sin registro' ?></td><td><?= $proxima ? sfEsc(date('d/m/Y', strtotime($proxima))) : 'Pendiente de registro' ?><?= $vencido ? ' · Vencido' : '' ?></td><td><a href="<?= sfEsc($baseUrl . '?id_ficha=' . $idFicha . '&id_aprendiz=' . (int)$a['id_aprendiz']) ?>">Abrir historial</a></td></tr>
        <?php endforeach; ?>
        <?php if ($aprendices === []): ?><tr><td colspan="5">No se encontraron aprendices para los criterios seleccionados.</td></tr><?php endif; ?>
        </tbody></table></div>
        <?php endif; ?>
    </section>

    <?php if ($aprendizSeleccionado): ?>
    <section class="sf-card">
        <div class="sf-person"><strong><?= sfEsc(trim($aprendizSeleccionado['nombres'] . ' ' . $aprendizSeleccionado['apellidos'])) ?></strong><br><span><?= sfEsc($aprendizSeleccionado['tipo_documento'] . ' ' . $aprendizSeleccionado['documento']) ?> · Ficha <?= sfEsc($aprendizSeleccionado['numero_ficha']) ?></span></div>
        <?php if ($puedeRegistrar): ?>
        <h2 class="sf-subtitle">2. Registrar nueva toma</h2>
        <form method="post" action="<?= sfEsc(url('/bienestar/seguimiento-fisico/guardar')) ?>">
            <input type="hidden" name="csrf_token" value="<?= sfEsc($tokenCsrf) ?>"><input type="hidden" name="id_ficha" value="<?= $idFicha ?>"><input type="hidden" name="id_aprendiz" value="<?= $idAprendiz ?>">
            <div class="sf-grid">
                <div class="sf-field"><label for="fecha_toma">Fecha y hora de toma *</label><input type="datetime-local" id="fecha_toma" name="fecha_toma" value="<?= sfEsc(date('Y-m-d\TH:i')) ?>" max="<?= sfEsc(date('Y-m-d\TH:i')) ?>" required></div>
                <div class="sf-field"><label for="peso">Peso (kg) *</label><input type="number" id="peso" name="peso" min="1" max="500" step="0.01" placeholder="Ej. 68.50" required></div>
                <div class="sf-field"><label for="talla">Talla (metros) *</label><input type="number" id="talla" name="talla" min="0.3" max="2.5" step="0.01" placeholder="Ej. 1.72" required></div>
                <div class="sf-field"><label for="presion_sistolica">Presión sistólica (mmHg)</label><input type="number" id="presion_sistolica" name="presion_sistolica" min="40" max="300"></div>
                <div class="sf-field"><label for="presion_diastolica">Presión diastólica (mmHg)</label><input type="number" id="presion_diastolica" name="presion_diastolica" min="20" max="200"></div>
                <div class="sf-field"><label for="frecuencia_cardiaca">Frecuencia cardíaca (lpm)</label><input type="number" id="frecuencia_cardiaca" name="frecuencia_cardiaca" min="1" max="300"></div>
                <div class="sf-field"><label for="frecuencia_respiratoria">Frecuencia respiratoria (rpm)</label><input type="number" id="frecuencia_respiratoria" name="frecuencia_respiratoria" min="1" max="100"></div>
                <div class="sf-field"><label for="temperatura">Temperatura (°C)</label><input type="number" id="temperatura" name="temperatura" min="25" max="45" step="0.1"></div>
                <div class="sf-field"><label for="saturacion_oxigeno">Saturación de oxígeno (%)</label><input type="number" id="saturacion_oxigeno" name="saturacion_oxigeno" min="0" max="100" step="0.1"></div>
                <div class="sf-field" style="grid-column:1/-1"><label for="observaciones">Observaciones educativas</label><textarea id="observaciones" name="observaciones" maxlength="3000" placeholder="Registre hechos pertinentes a la actividad; evite diagnósticos clínicos."></textarea></div>
            </div>
            <p class="sf-note">El IMC se calcula automáticamente al guardar: peso (kg) / talla² (m). Para menores de 18 años, su interpretación requiere referencias de crecimiento por edad y sexo; esta pantalla no genera diagnósticos clínicos. Si identifica una medición preocupante, aplique el protocolo institucional.</p>
            <div class="sf-actions"><button type="submit" class="sf-btn">Guardar seguimiento</button><a class="sf-btn secondary" href="<?= sfEsc($baseUrl . '?id_ficha=' . $idFicha) ?>">Volver al listado</a></div>
        </form>
        <?php else: ?><p class="sf-note">Su perfil tiene permiso de consulta, pero no de registro.</p><?php endif; ?>
    </section>

    <section class="sf-card"><h2 class="sf-subtitle">3. Historial de seguimiento</h2><div class="sf-table-wrap"><table class="sf-table"><thead><tr><th>Fecha</th><th>Peso</th><th>Talla</th><th>IMC</th><th>Presión arterial</th><th>FC</th><th>FR</th><th>Temperatura</th><th>SpO₂</th><th>Próximo control</th><th>Responsable</th></tr></thead><tbody>
    <?php foreach ($historial as $h): ?><tr><td><?= sfEsc(date('d/m/Y H:i', strtotime($h['fecha_toma']))) ?></td><td><?= $h['peso'] !== null ? sfEsc(number_format((float)$h['peso'], 2, ',', '.')) . ' kg' : '—' ?></td><td><?= $h['talla'] !== null ? sfEsc(number_format((float)$h['talla'], 2, ',', '.')) . ' m' : '—' ?></td><td><?= $h['imc'] !== null ? sfEsc(number_format((float)$h['imc'], 2, ',', '.')) : '—' ?></td><td><?= $h['presion_sistolica'] !== null ? sfEsc($h['presion_sistolica'] . '/' . $h['presion_diastolica']) : '—' ?></td><td><?= sfEsc($h['frecuencia_cardiaca'] ?? '—') ?></td><td><?= sfEsc($h['frecuencia_respiratoria'] ?? '—') ?></td><td><?= $h['temperatura'] !== null ? sfEsc($h['temperatura']) . ' °C' : '—' ?></td><td><?= $h['saturacion_oxigeno'] !== null ? sfEsc($h['saturacion_oxigeno']) . '%' : '—' ?></td><td><?= !empty($h['proxima_fecha']) ? sfEsc(date('d/m/Y', strtotime($h['proxima_fecha']))) : '—' ?></td><td><?= sfEsc(trim(($h['responsable'] ?? '') !== '' ? $h['responsable'] : ($h['usuario_registro'] ?? 'Usuario SITA'))) ?></td></tr>
    <?php endforeach; ?><?php if ($historial === []): ?><tr><td colspan="11">Este aprendiz aún no tiene seguimientos registrados.</td></tr><?php endif; ?></tbody></table></div>
    <p class="sf-note">Cada toma se almacena como un registro independiente. El sistema propone el próximo control tres meses después de la fecha de toma; las modificaciones o reprogramaciones futuras deben conservar su auditoría.</p></section>
    <?php endif; ?>
</main>
