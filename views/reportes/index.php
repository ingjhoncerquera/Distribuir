<?php
declare(strict_types=1);

require_once ROOT_PATH . '/views/layout/header.php';

$tipo = (string)($tipo ?? 'fichas');
$filtros = $filtros ?? [];
$datos = $datos ?? [];
$error = (string)($error ?? '');
$catalogos = $catalogos ?? [
    'fichas' => [],
    'programas' => [],
    'jornadas' => [],
    'poblaciones' => []
];
$tiposReporte = $tiposReporte ?? [];

$valorATexto = static function ($valor) use (&$valorATexto): string {
    if ($valor === null) {
        return '';
    }

    if (is_bool($valor)) {
        return $valor ? 'Sí' : 'No';
    }

    if (is_scalar($valor)) {
        return (string)$valor;
    }

    if (is_object($valor)) {
        $valor = method_exists($valor, '__toString')
            ? (string)$valor
            : get_object_vars($valor);
    }

    if (is_array($valor)) {
        $partes = [];

        foreach ($valor as $clave => $elemento) {
            $texto = $valorATexto($elemento);

            if ($texto === '') {
                continue;
            }

            $partes[] = is_string($clave)
                ? $clave . ': ' . $texto
                : $texto;
        }

        return implode('; ', $partes);
    }

    return '';
};

$escape = static fn($v): string => htmlspecialchars(
    $valorATexto($v),
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);

$esIndividual = $tipo === 'aprendiz';
$esCaracterizacion = $tipo === 'caracterizacion';
?>

<style>
.reportes-page{padding:26px;background:#f5f7fa;min-height:calc(100vh - 140px);color:#1f2937}
.reportes-head{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;flex-wrap:wrap;margin-bottom:22px}
.reportes-title{font-size:28px;font-weight:750;margin:0;color:#123b5d}
.reportes-subtitle{margin:7px 0 0;color:#64748b;font-size:14px}
.reportes-panel{background:#fff;border:1px solid #e4eaf0;border-radius:15px;padding:22px;margin-bottom:20px;box-shadow:0 5px 20px rgba(15,39,64,.04)}
.reportes-grid{display:grid;grid-template-columns:repeat(4,minmax(180px,1fr));gap:15px}
.reportes-field label{display:block;font-size:13px;font-weight:700;margin-bottom:7px;color:#334155}
.reportes-field input,.reportes-field select{width:100%;min-height:42px;border:1px solid #cbd5e1;border-radius:9px;padding:9px 11px;background:#fff;color:#1e293b}
.reportes-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}
.reportes-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:9px;padding:11px 16px;font-weight:700;text-decoration:none;cursor:pointer}
.reportes-btn-primary{background:#123b5d;color:#fff}
.reportes-btn-excel{background:#147d50;color:#fff}
.reportes-btn-light{background:#edf2f7;color:#334155}
.reportes-btn:hover{filter:brightness(.96);color:inherit}
.reportes-help{font-size:13px;color:#64748b;margin-top:8px}
.reportes-error{background:#fff0f0;border:1px solid #fecaca;color:#991b1b;border-radius:10px;padding:13px 15px;margin-bottom:18px}
.reportes-summary{display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;margin-bottom:15px}
.reportes-count{font-size:14px;color:#475569}
.reportes-count strong{font-size:21px;color:#123b5d;margin-right:5px}
.reportes-table-wrap{overflow:auto;border:1px solid #e2e8f0;border-radius:10px}
.reportes-table{border-collapse:collapse;width:100%;font-size:13px;background:white}
.reportes-table th{background:#123b5d;color:white;text-align:left;padding:12px 13px;white-space:nowrap}
.reportes-table td{padding:11px 13px;border-bottom:1px solid #edf1f5;vertical-align:top;max-width:360px}
.reportes-table tr:nth-child(even) td{background:#f8fafc}
.reportes-sheet{margin-top:22px}
.reportes-sheet h3{font-size:17px;color:#123b5d;font-weight:750;margin-bottom:12px}
.reportes-empty{padding:25px;text-align:center;color:#64748b;background:#f8fafc;border-radius:10px}
.reportes-privacy{font-size:12px;color:#64748b;margin-top:10px}
@media(max-width:1000px){.reportes-grid{grid-template-columns:repeat(2,minmax(160px,1fr))}}
@media(max-width:600px){.reportes-page{padding:15px}.reportes-panel{padding:15px}.reportes-grid{grid-template-columns:1fr}.reportes-title{font-size:23px}}
</style>

<div class="reportes-page">
    <div class="reportes-head">
        <div>
            <h1 class="reportes-title">Módulo de reportes</h1>
            <p class="reportes-subtitle">Consulta, filtra y exporta información de SITA a Excel (.xls).</p>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="reportes-error"><?= $escape($error) ?></div>
    <?php endif; ?>

    <section class="reportes-panel">
        <form method="get" action="<?= $escape(url('/reportes')) ?>" id="formReportes">
            <div class="reportes-grid">

                <div class="reportes-field">
                    <label for="tipo">Tipo de reporte</label>
                    <select name="tipo" id="tipo" onchange="document.getElementById('formReportes').submit()">
                        <?php foreach ($tiposReporte as $clave => $nombre): ?>
                            <option value="<?= $escape($clave) ?>" <?= $tipo === $clave ? 'selected' : '' ?>>
                                <?= $escape($nombre) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($esIndividual): ?>
                    <div class="reportes-field">
                        <label for="documento">Documento del aprendiz</label>
                        <input
                            type="text"
                            name="documento"
                            id="documento"
                            value="<?= $escape($filtros['documento'] ?? $filtros['buscar'] ?? '') ?>"
                            placeholder="Ingrese el documento"
                            required
                        >
                    </div>
                <?php else: ?>
                    <div class="reportes-field">
                        <label for="buscar">Buscar</label>
                        <input
                            type="search"
                            name="buscar"
                            id="buscar"
                            value="<?= $escape($filtros['buscar'] ?? '') ?>"
                            placeholder="Documento, nombre, ficha..."
                        >
                    </div>
                <?php endif; ?>

                <?php if (in_array($tipo, ['fichas','aprendices_ficha','caracterizacion','resultados'], true)): ?>
                    <div class="reportes-field">
                        <label for="id_ficha">Ficha</label>
                        <select name="id_ficha" id="id_ficha">
                            <option value="0">Todas las fichas</option>

                            <?php foreach (($catalogos['fichas'] ?? []) as $ficha): ?>
                                <option
                                    value="<?= (int)$ficha['id'] ?>"
                                    data-programa="<?= (int)($ficha['id_programa'] ?? 0) ?>"
                                    <?= (int)($filtros['id_ficha'] ?? 0) === (int)$ficha['id'] ? 'selected' : '' ?>
                                >
                                    <?= $escape($ficha['numero_ficha']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (in_array($tipo, ['fichas','aprendices_ficha','caracterizacion'], true)): ?>
                    <div class="reportes-field">
                        <label for="id_programa">Programa</label>
                        <select name="id_programa" id="id_programa">
                            <option value="0">Todos los programas</option>

                            <?php foreach (($catalogos['programas'] ?? []) as $programa): ?>
                                <option
                                    value="<?= (int)$programa['id'] ?>"
                                    <?= (int)($filtros['id_programa'] ?? 0) === (int)$programa['id'] ? 'selected' : '' ?>
                                >
                                    <?= $escape($programa['codigo'] . ' - ' . $programa['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (in_array($tipo, ['fichas','aprendices_ficha'], true)): ?>
                    <div class="reportes-field">
                        <label for="id_jornada">Jornada</label>
                        <select name="id_jornada" id="id_jornada">
                            <option value="0">Todas las jornadas</option>

                            <?php foreach (($catalogos['jornadas'] ?? []) as $jornada): ?>
                                <option
                                    value="<?= (int)$jornada['id'] ?>"
                                    <?= (int)($filtros['id_jornada'] ?? 0) === (int)$jornada['id'] ? 'selected' : '' ?>
                                >
                                    <?= $escape($jornada['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if ($esCaracterizacion): ?>
                    <div class="reportes-field">
                        <label for="id_poblacion">Tipo de población</label>
                        <select name="id_poblacion" id="id_poblacion">
                            <option value="0">Todos los tipos</option>

                            <?php foreach (($catalogos['poblaciones'] ?? []) as $poblacion): ?>
                                <option
                                    value="<?= (int)$poblacion['id'] ?>"
                                    <?= (int)($filtros['id_poblacion'] ?? 0) === (int)$poblacion['id'] ? 'selected' : '' ?>
                                >
                                    <?= $escape($poblacion['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (in_array($tipo, ['fichas','aprendices_ficha','caracterizacion'], true)): ?>
                    <div class="reportes-field">
                        <label for="estado">Estado</label>
                        <select name="estado" id="estado">
                            <option value="">Todos</option>

                            <?php
                            $estados = $tipo === 'fichas'
                                ? [
                                    'activa' => 'Activa',
                                    'finalizada' => 'Finalizada',
                                    'cancelada' => 'Cancelada'
                                ]
                                : [
                                    'EN FORMACION' => 'EN FORMACIÓN',
                                    'CANCELADO' => 'CANCELADO',
                                    'RETIRO VOLUNTARIO' => 'RETIRO VOLUNTARIO',
                                    'APLAZADO' => 'APLAZADO'
                                ];

                            foreach ($estados as $valor => $etiqueta):
                            ?>
                                <option
                                    value="<?= $escape($valor) ?>"
                                    <?= ($filtros['estado'] ?? '') === $valor ? 'selected' : '' ?>
                                >
                                    <?= $escape($etiqueta) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php elseif (in_array($tipo, ['resultados','alertas','remisiones','solicitudes'], true)): ?>
                    <div class="reportes-field">
                        <label for="estado">Estado / juicio</label>
                        <input
                            type="text"
                            name="estado"
                            id="estado"
                            value="<?= $escape($filtros['estado'] ?? '') ?>"
                            placeholder="<?= $tipo === 'resultados' ? 'aprobado / no_aprobado / pendiente' : 'Estado exacto (opcional)' ?>"
                        >
                    </div>
                <?php endif; ?>

                <?php if (in_array($tipo, ['resultados','alertas','remisiones','solicitudes','salud_fisica','nutricion'], true)): ?>
                    <div class="reportes-field">
                        <label for="desde">Fecha desde</label>
                        <input type="date" name="desde" id="desde" value="<?= $escape($filtros['desde'] ?? '') ?>">
                    </div>

                    <div class="reportes-field">
                        <label for="hasta">Fecha hasta</label>
                        <input type="date" name="hasta" id="hasta" value="<?= $escape($filtros['hasta'] ?? '') ?>">
                    </div>
                <?php endif; ?>

            </div>

            <div class="reportes-actions">
                <button class="reportes-btn reportes-btn-primary" type="submit" name="consultar" value="1">
                    Consultar reporte
                </button>

                <?php if (isset($_GET['consultar'])): ?>
                    <a
                        class="reportes-btn reportes-btn-excel"
                        href="<?= $escape(url('/reportes/exportar') . '?' . http_build_query(array_merge($_GET, ['tipo' => $tipo]))) ?>"
                    >
                        Descargar Excel (.xls)
                    </a>
                <?php endif; ?>

                <a class="reportes-btn reportes-btn-light" href="<?= $escape(url('/reportes')) ?>">
                    Limpiar filtros
                </a>
            </div>

            <p class="reportes-help">
                La exportación incluye todos los registros encontrados con los filtros seleccionados, no solo las filas visibles en pantalla.
            </p>

            <?php if ($esIndividual): ?>
                <p class="reportes-privacy">
                    El informe integral reúne datos personales, fichas, caracterización, respuestas completas de los cinco componentes del cuestionario, actualizaciones, formación académica y seguimientos registrados. Los registros clínicos restringidos solo se exportan a perfiles autorizados.
                </p>
            <?php endif; ?>
        </form>
    </section>

    <?php if (isset($_GET['consultar']) && $error === ''): ?>

        <?php if ($esIndividual): ?>

            <?php if (!$datos): ?>
                <div class="reportes-panel">
                    <div class="reportes-empty">No se encontraron datos para el documento indicado.</div>
                </div>
            <?php else: ?>

                <?php foreach ($datos as $nombreHoja => $filas): ?>
                    <section class="reportes-panel reportes-sheet">
                        <div class="reportes-summary">
                            <h3 style="margin:0"><?= $escape($nombreHoja) ?></h3>
                            <span class="reportes-count">
                                <strong><?= count($filas) ?></strong> registros
                            </span>
                        </div>

                        <?php if (!$filas): ?>
                            <div class="reportes-empty">Sin registros.</div>
                        <?php else: ?>
                            <div class="reportes-table-wrap">
                                <table class="reportes-table">
                                    <thead>
                                        <tr>
                                            <?php foreach (array_keys($filas[0]) as $columna): ?>
                                                <th><?= $escape($columna) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($filas as $fila): ?>
                                            <tr>
                                                <?php foreach ($fila as $valor): ?>
                                                    <td><?= nl2br($escape($valor)) ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>

            <?php endif; ?>

        <?php else: ?>

            <section class="reportes-panel">
                <div class="reportes-summary">
                    <div>
                        <h2 style="margin:0;font-size:20px;color:#123b5d">
                            <?= $escape($tiposReporte[$tipo] ?? 'Reporte') ?>
                        </h2>
                        <p class="reportes-help" style="margin-bottom:0">Vista previa de los resultados</p>
                    </div>

                    <div class="reportes-count">
                        <strong><?= count($datos) ?></strong> registros encontrados
                    </div>
                </div>

                <?php if (!$datos): ?>
                    <div class="reportes-empty">
                        No hay registros que coincidan con los filtros seleccionados.
                    </div>
                <?php else: ?>

                    <div class="reportes-table-wrap">
                        <table class="reportes-table">
                            <thead>
                                <tr>
                                    <?php foreach (array_keys($datos[0]) as $columna): ?>
                                        <th><?= $escape($columna) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($datos, 0, 100) as $fila): ?>
                                    <tr>
                                        <?php foreach ($fila as $valor): ?>
                                            <td><?= nl2br($escape($valor)) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (count($datos) > 100): ?>
                        <p class="reportes-help">
                            Vista previa limitada a 100 filas. El archivo Excel contendrá los <?= count($datos) ?> registros completos.
                        </p>
                    <?php endif; ?>

                <?php endif; ?>
            </section>

        <?php endif; ?>

    <?php endif; ?>
</div>

<script>
(function () {
    'use strict';

    function inicializarFiltrosFichaPrograma() {
        const fichaSelect = document.getElementById('id_ficha');
        const programaSelect = document.getElementById('id_programa');

        if (!fichaSelect || !programaSelect) {
            return;
        }

        /*
         * Conservamos las opciones originales. Así podemos reconstruir
         * ambos selectores sin perder fichas ni programas.
         */
        const fichasOriginales = Array.from(fichaSelect.options).map(function (opcion) {
            return {
                value: opcion.value,
                text: opcion.textContent,
                programa: opcion.getAttribute('data-programa') || '0'
            };
        });

        const programasOriginales = Array.from(programaSelect.options).map(function (opcion) {
            return {
                value: opcion.value,
                text: opcion.textContent
            };
        });

        function obtenerProgramaDeFicha(idFicha) {
            const ficha = fichasOriginales.find(function (opcion) {
                return opcion.value === String(idFicha);
            });

            return ficha ? ficha.programa : '0';
        }

        function reconstruirProgramas(idPrograma) {
            programaSelect.replaceChildren();

            programasOriginales.forEach(function (programa) {
                /*
                 * Se conserva "Todos los programas" únicamente cuando
                 * no existe una ficha/programa específico seleccionado.
                 */
                if (idPrograma === '0' || programa.value === idPrograma) {
                    const opcion = document.createElement('option');
                    opcion.value = programa.value;
                    opcion.textContent = programa.text;
                    programaSelect.appendChild(opcion);
                }
            });

            programaSelect.value = idPrograma;

            if (programaSelect.value !== idPrograma) {
                programaSelect.value = '0';
            }
        }

        function reconstruirFichas(idPrograma, idFichaSeleccionada) {
            fichaSelect.replaceChildren();

            fichasOriginales.forEach(function (ficha) {
                const esOpcionTodas = ficha.value === '0';
                const perteneceAlPrograma = ficha.programa === idPrograma;

                if (
                    esOpcionTodas ||
                    idPrograma === '0' ||
                    perteneceAlPrograma
                ) {
                    const opcion = document.createElement('option');
                    opcion.value = ficha.value;
                    opcion.textContent = ficha.text;
                    opcion.setAttribute('data-programa', ficha.programa);
                    fichaSelect.appendChild(opcion);
                }
            });

            const fichaDisponible = Array.from(fichaSelect.options).some(function (opcion) {
                return opcion.value === String(idFichaSeleccionada);
            });

            fichaSelect.value = fichaDisponible ? String(idFichaSeleccionada) : '0';
        }

        /*
         * FICHA -> PROGRAMA
         * Una ficha seleccionada determina el único programa disponible.
         */
        fichaSelect.addEventListener('change', function () {
            const idFicha = fichaSelect.value;

            if (idFicha === '0' || idFicha === '') {
                reconstruirProgramas('0');
                reconstruirFichas('0', '0');
                return;
            }

            const idPrograma = obtenerProgramaDeFicha(idFicha);

            if (idPrograma === '0') {
                alert(
                    'No se pudo identificar el programa de esta ficha. ' +
                    'Verifique que ReporteModel::catalogos() incluya id_programa.'
                );
                reconstruirProgramas('0');
                return;
            }

            reconstruirProgramas(idPrograma);
            reconstruirFichas(idPrograma, idFicha);
        });

        /*
         * PROGRAMA -> FICHAS
         * Si se selecciona un programa, solo se muestran sus fichas.
         */
        programaSelect.addEventListener('change', function () {
            const idPrograma = programaSelect.value;
            const fichaActual = fichaSelect.value;

            if (idPrograma === '0' || idPrograma === '') {
                reconstruirProgramas('0');
                reconstruirFichas('0', '0');
                return;
            }

            const programaDeFicha = obtenerProgramaDeFicha(fichaActual);
            const fichaConservada =
                fichaActual !== '0' && programaDeFicha === idPrograma
                    ? fichaActual
                    : '0';

            reconstruirFichas(idPrograma, fichaConservada);
            reconstruirProgramas(idPrograma);
        });

        /*
         * Estado inicial:
         * 1. Si llega una ficha seleccionada, su programa tiene prioridad.
         * 2. Si solo llega un programa, se filtran sus fichas.
         * 3. Si no hay ninguno, se muestran todos.
         */
        const fichaInicial = fichaSelect.value;
        const programaInicial = programaSelect.value;

        if (fichaInicial !== '0' && fichaInicial !== '') {
            const programaDeFicha = obtenerProgramaDeFicha(fichaInicial);

            if (programaDeFicha !== '0') {
                reconstruirProgramas(programaDeFicha);
                reconstruirFichas(programaDeFicha, fichaInicial);
            }
        } else if (programaInicial !== '0' && programaInicial !== '') {
            reconstruirProgramas(programaInicial);
            reconstruirFichas(programaInicial, '0');
        } else {
            reconstruirProgramas('0');
            reconstruirFichas('0', '0');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializarFiltrosFichaPrograma);
    } else {
        inicializarFiltrosFichaPrograma();
    }
})();
</script>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>