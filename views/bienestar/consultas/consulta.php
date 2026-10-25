<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SITA - Consulta de ficha de caracterización
| Archivo: views/ficha_caracterizacion/consulta.php
|--------------------------------------------------------------------------
*/

if (!function_exists('fcConsultaEsc')) {
    function fcConsultaEsc(mixed $valor): string
    {
        return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$aprendices = is_array($aprendices ?? null) ? $aprendices : [];
$fichas = is_array($fichas ?? null) ? $fichas : [];
$buscar = (string) ($buscar ?? '');
$idFichaSeleccionada = $idFichaSeleccionada ?? null;
$estadoCaracterizacion = (string) ($estadoCaracterizacion ?? '');
$pagina = max(1, (int) ($pagina ?? 1));
$total = max(0, (int) ($total ?? 0));
$totalPaginas = max(1, (int) ($totalPaginas ?? 1));

$queryBase = [
    'buscar' => $buscar,
    'id_ficha' => $idFichaSeleccionada,
    'estado_caracterizacion' => $estadoCaracterizacion,
];

$urlPagina = static function (int $numeroPagina) use ($queryBase): string {
    $parametros = $queryBase;
    $parametros['pagina'] = $numeroPagina;
    $parametros = array_filter(
        $parametros,
        static fn ($valor): bool => $valor !== null && $valor !== ''
    );

    return url('/ficha-caracterizacion') . '?' . http_build_query($parametros);
};
?>

<style>
.fc-consulta { color: #243247; }
.fc-cabecera { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; margin-bottom:22px; }
.fc-titulo { margin:0 0 6px; font-size:25px; font-weight:750; color:#172b4d; }
.fc-subtitulo { margin:0; color:#64748b; font-size:14px; }
.fc-resumen { display:flex; align-items:center; gap:10px; padding:12px 16px; border:1px solid #dbe4ee; border-radius:12px; background:#fff; }
.fc-resumen strong { font-size:22px; color:#17365d; }
.fc-panel { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:20px; margin-bottom:20px; box-shadow:0 4px 14px rgba(15,23,42,.04); }
.fc-filtros { display:grid; grid-template-columns:minmax(220px,2fr) minmax(190px,1fr) minmax(190px,1fr) auto auto; gap:12px; align-items:end; }
.fc-campo label { display:block; margin-bottom:7px; font-size:13px; font-weight:700; color:#334155; }
.fc-campo input,.fc-campo select { width:100%; min-height:42px; border:1px solid #cbd5e1; border-radius:8px; padding:9px 11px; color:#1e293b; background:#fff; font:inherit; }
.fc-btn { display:inline-flex; align-items:center; justify-content:center; min-height:42px; padding:9px 15px; border-radius:8px; border:1px solid transparent; text-decoration:none; font-size:13px; font-weight:700; cursor:pointer; white-space:nowrap; }
.fc-btn-primary { background:#174a7e; color:#fff; }
.fc-btn-secondary { background:#f8fafc; border-color:#cbd5e1; color:#334155; }
.fc-tabla-contenedor { width:100%; overflow-x:auto; }
.fc-tabla { width:100%; border-collapse:collapse; min-width:1050px; }
.fc-tabla th { padding:13px 12px; background:#f1f5f9; color:#334155; text-align:left; font-size:11px; letter-spacing:.04em; text-transform:uppercase; border-bottom:1px solid #dbe3ed; }
.fc-tabla td { padding:13px 12px; border-bottom:1px solid #edf1f5; font-size:13px; vertical-align:middle; }
.fc-tabla tbody tr:hover { background:#f8fbff; }
.fc-nombre { font-weight:700; color:#1e293b; }
.fc-secundario { margin-top:4px; color:#64748b; font-size:12px; }
.fc-etiqueta { display:inline-flex; padding:5px 9px; border-radius:999px; font-size:11px; font-weight:750; white-space:nowrap; }
.fc-ok { background:#dcfce7; color:#166534; }
.fc-pendiente { background:#fef3c7; color:#92400e; }
.fc-neutro { background:#e2e8f0; color:#475569; }
.fc-vacio { padding:42px 18px; text-align:center; color:#64748b; }
.fc-vacio strong { display:block; margin-bottom:7px; color:#334155; font-size:16px; }
.fc-pie { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; padding-top:16px; color:#64748b; font-size:13px; }
.fc-paginacion { display:flex; gap:7px; align-items:center; }
.fc-paginacion a,.fc-paginacion span { display:inline-flex; min-width:36px; min-height:36px; padding:8px 11px; justify-content:center; align-items:center; border:1px solid #dbe3ed; border-radius:8px; color:#334155; text-decoration:none; background:#fff; }
.fc-paginacion .fc-actual { color:#fff; background:#174a7e; border-color:#174a7e; }
@media(max-width:1050px) { .fc-filtros { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:600px) { .fc-panel { padding:14px; } .fc-filtros { grid-template-columns:1fr; } .fc-titulo { font-size:21px; } }
</style>

<div class="fc-consulta">
    <div class="fc-cabecera">
        <div>
            <h1 class="fc-titulo">Ficha de caracterización</h1>
            <p class="fc-subtitulo">Consulta de aprendices, programa de formación y estado de actualización de la información.</p>
        </div>
        <div class="fc-resumen">
            <span aria-hidden="true">👥</span>
            <div><strong><?= number_format($total, 0, ',', '.') ?></strong><div class="fc-secundario">registros encontrados</div></div>
        </div>
    </div>

    <section class="fc-panel">
        <form method="get" action="<?= fcConsultaEsc(url('/ficha-caracterizacion')) ?>">
            <div class="fc-filtros">
                <div class="fc-campo">
                    <label for="fc-buscar">Buscar</label>
                    <input id="fc-buscar" type="search" name="buscar"
                           value="<?= fcConsultaEsc($buscar) ?>"
                           placeholder="Documento, nombre, ficha o programa">
                </div>

                <div class="fc-campo">
                    <label for="fc-ficha">Ficha de formación</label>
                    <select id="fc-ficha" name="id_ficha">
                        <option value="">Todas las fichas</option>
                        <?php foreach ($fichas as $ficha): ?>
                            <option value="<?= (int) $ficha['id'] ?>"
                                <?= (string) $idFichaSeleccionada === (string) $ficha['id'] ? 'selected' : '' ?>>
                                <?= fcConsultaEsc($ficha['numero_ficha']) ?>
                                — <?= fcConsultaEsc($ficha['codigo_programa'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="fc-campo">
                    <label for="fc-estado">Estado de caracterización</label>
                    <select id="fc-estado" name="estado_caracterizacion">
                        <option value="">Todos los estados</option>
                        <option value="actualizado" <?= $estadoCaracterizacion === 'actualizado' ? 'selected' : '' ?>>Actualizada</option>
                        <option value="pendiente" <?= $estadoCaracterizacion === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                    </select>
                </div>

                <button class="fc-btn fc-btn-primary" type="submit">Consultar</button>
                <a class="fc-btn fc-btn-secondary" href="<?= fcConsultaEsc(url('/ficha-caracterizacion')) ?>">Limpiar</a>
            </div>
        </form>
    </section>

    <section class="fc-panel">
        <?php if ($aprendices === []): ?>
            <div class="fc-vacio">
                <strong>No se encontraron aprendices</strong>
                Ajusta los filtros e intenta nuevamente.
            </div>
        <?php else: ?>
            <div class="fc-tabla-contenedor">
                <table class="fc-tabla">
                    <thead>
                    <tr>
                        <th>Ficha / programa</th>
                        <th>Identificación</th>
                        <th>Aprendiz</th>
                        <th>Contacto</th>
                        <th>Clasificación poblacional</th>
                        <th>Actualización</th>
                        <th>Estado aprendiz</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($aprendices as $fila): ?>
                        <?php
                        $actualizado = ($fila['estado_actualizacion'] ?? 'pendiente') === 'actualizado';
                        $nombre = trim((string) ($fila['nombres'] ?? '') . ' ' . (string) ($fila['apellidos'] ?? ''));
                        ?>
                        <tr>
                            <td>
                                <strong><?= fcConsultaEsc($fila['numero_ficha'] ?? '') ?></strong>
                                <div class="fc-secundario">
                                    <?= fcConsultaEsc($fila['codigo_programa'] ?? '') ?>
                                    <?= !empty($fila['nombre_programa']) ? ' — ' . fcConsultaEsc($fila['nombre_programa']) : '' ?>
                                </div>
                            </td>
                            <td>
                                <?= fcConsultaEsc($fila['tipo_documento'] ?? '') ?>
                                <div class="fc-secundario"><?= fcConsultaEsc($fila['documento'] ?? '') ?></div>
                            </td>
                            <td><span class="fc-nombre"><?= fcConsultaEsc($nombre) ?></span></td>
                            <td>
                                <?= fcConsultaEsc($fila['telefono'] ?? '') ?: 'Sin teléfono' ?>
                                <div class="fc-secundario"><?= fcConsultaEsc($fila['correo'] ?? '') ?: 'Sin correo' ?></div>
                            </td>
                            <td><?= fcConsultaEsc($fila['tipo_poblacion'] ?? '') ?: 'Sin caracterizar' ?></td>
                            <td>
                                <span class="fc-etiqueta <?= $actualizado ? 'fc-ok' : 'fc-pendiente' ?>">
                                    <?= $actualizado ? 'Actualizada' : 'Pendiente' ?>
                                </span>
                                <?php if (!empty($fila['fecha_actualizacion'])): ?>
                                    <div class="fc-secundario"><?= fcConsultaEsc($fila['fecha_actualizacion']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="fc-etiqueta fc-neutro"><?= fcConsultaEsc($fila['estado_aprendiz'] ?? 'Sin estado') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="fc-pie">
                <span>Página <?= $pagina ?> de <?= $totalPaginas ?></span>
                <nav class="fc-paginacion" aria-label="Paginación">
                    <?php if ($pagina > 1): ?>
                        <a href="<?= fcConsultaEsc($urlPagina($pagina - 1)) ?>" aria-label="Página anterior">‹</a>
                    <?php endif; ?>
                    <?php
                    $inicio = max(1, $pagina - 2);
                    $fin = min($totalPaginas, $pagina + 2);
                    for ($numero = $inicio; $numero <= $fin; $numero++):
                    ?>
                        <?php if ($numero === $pagina): ?>
                            <span class="fc-actual" aria-current="page"><?= $numero ?></span>
                        <?php else: ?>
                            <a href="<?= fcConsultaEsc($urlPagina($numero)) ?>"><?= $numero ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($pagina < $totalPaginas): ?>
                        <a href="<?= fcConsultaEsc($urlPagina($pagina + 1)) ?>" aria-label="Página siguiente">›</a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    </section>
</div>
