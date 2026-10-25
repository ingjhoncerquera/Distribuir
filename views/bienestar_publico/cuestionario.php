<?php
/*
|--------------------------------------------------------------------------
| CUESTIONARIO PÚBLICO DE BIENESTAR - SITA
|--------------------------------------------------------------------------
| - Cinco componentes.
| - Listas desplegables para selección única y escalas.
| - Casillas de verificación para selección múltiple.
| - Pregunta PP-02-FRECUENCIA condicional a PP-02.
| - Guardar borrador sin exigir todas las respuestas.
| - Finalizar únicamente desde el componente 5 y con las respuestas
|   obligatorias completas.
|--------------------------------------------------------------------------
*/

$e = static function ($valor): string {
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
};

$respuestas = is_array($respuestas ?? null) ? $respuestas : [];
$secciones = is_array($secciones ?? null) ? $secciones : [];

$valorRespuesta = static function (string $id) use ($respuestas) {
    return $respuestas[$id] ?? $respuestas[(int) $id] ?? null;
};

$seleccionada = static function ($valor, $opcion): bool {
    if (is_array($valor)) {
        return in_array((string) $opcion, array_map('strval', $valor), true);
    }

    return (string) $valor === (string) $opcion;
};

$totalPreguntas = 0;

foreach ($secciones as $seccion) {
    $preguntas = $seccion['preguntas'] ?? [];

    if (is_array($preguntas)) {
        $totalPreguntas += count($preguntas);
    }
}

$grupos = array_fill(0, 5, []);
$cantidadSecciones = count($secciones);

if ($cantidadSecciones > 0) {
    foreach (array_values($secciones) as $indice => $seccion) {
        $grupo = min(4, (int) floor($indice * 5 / $cantidadSecciones));
        $grupos[$grupo][] = $seccion;
    }
}

$nombresPestanas = [
    'Componente 1',
    'Componente 2',
    'Componente 3',
    'Componente 4',
    'Componente 5',
];

$descripcionesComponentes = [
    'Información sobre su situación socioeconómica y algunas condiciones de vida.',
    'Preguntas relacionadas con hábitos, promoción de la salud y prevención.',
    'Reconocimiento de habilidades personales, sociales y emocionales.',
    'Preguntas sobre sus prácticas, intereses y hábitos de actividad física.',
    'Exploración de sus intereses y participación en actividades artísticas y culturales.',
];

$nombreAprendiz = $nombreAprendiz ?? 'Aprendiz';
$nombreFicha = $nombreFicha ?? '';
$csrf = $csrf ?? '';
$tokenFormulario = $_GET['token'] ?? ($token ?? '');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuestionario de Bienestar | SITA</title>

    <style>
        * { box-sizing: border-box; }

        :root {
            --azul: #174a7e;
            --azul-oscuro: #10365d;
            --azul-claro: #eaf2fa;
            --borde: #dce4ec;
            --texto: #253447;
            --gris: #64748b;
            --fondo: #f3f6fa;
            --blanco: #fff;
            --verde: #16804a;
            --rojo: #b42318;
        }

        body {
            margin: 0;
            padding: 18px 12px;
            background: var(--fondo);
            color: var(--texto);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            line-height: 1.4;
        }

        .contenedor {
            width: 100%;
            max-width: 1050px;
            margin: 0 auto;
        }

        .encabezado {
            padding: 19px 22px;
            margin-bottom: 14px;
            color: #fff;
            background: linear-gradient(135deg, var(--azul-oscuro), var(--azul));
            border-radius: 12px;
        }

        .encabezado h1 { margin: 0 0 7px; font-size: 23px; }
        .encabezado p { margin: 4px 0; font-size: 13px; }

        .datos {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }

        .dato {
            padding: 11px 13px;
            background: var(--blanco);
            border: 1px solid var(--borde);
            border-radius: 9px;
            overflow-wrap: anywhere;
        }

        .dato strong {
            display: block;
            margin-bottom: 3px;
            color: var(--gris);
            font-size: 11px;
            text-transform: uppercase;
        }

        .dato span { font-weight: 700; }

        .tarjeta {
            padding: 17px;
            background: var(--blanco);
            border: 1px solid var(--borde);
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(15, 23, 42, .04);
        }

        .instruccion {
            margin: 0 0 13px;
            color: var(--gris);
            font-size: 13px;
        }

        .barra {
            width: 100%;
            height: 7px;
            margin-bottom: 14px;
            overflow: hidden;
            background: #e7edf4;
            border-radius: 10px;
        }

        .barra-progreso {
            width: 20%;
            height: 100%;
            background: var(--azul);
            border-radius: 10px;
            transition: width .2s ease;
        }

        .pestanas {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 7px;
            margin-bottom: 16px;
        }

        .pestana {
            min-height: 43px;
            padding: 9px 5px;
            border: 1px solid var(--borde);
            border-radius: 7px;
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .pestana.activa {
            color: #fff;
            background: var(--azul);
            border-color: var(--azul);
        }

        .pestana.completada {
            border-color: #86c8a5;
            background: #edf9f1;
            color: #17643a;
        }

        .contenido-pestana[hidden] { display: none !important; }

        .seccion {
            margin-bottom: 15px;
            padding: 12px;
            border: 1px solid var(--borde);
            border-radius: 9px;
            background: #fff;
        }

        .seccion h2 {
            margin: 0 0 5px;
            padding-bottom: 7px;
            color: var(--azul);
            border-bottom: 1px solid #e8edf3;
            font-size: 16px;
        }

        .descripcion-componente {
            margin: 0 0 13px;
            color: var(--gris);
            font-size: 12px;
            line-height: 1.5;
        }

        .pregunta {
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid #edf1f5;
        }

        .pregunta:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .pregunta-titulo {
            display: block;
            margin-bottom: 5px;
            font-weight: 700;
            line-height: 1.35;
        }

        .obligatoria { color: var(--rojo); }

        .ayuda {
            margin: 0 0 6px;
            color: var(--gris);
            font-size: 12px;
        }

        .opciones {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 5px 12px;
        }

        .opcion {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            padding: 5px 7px;
            border: 1px solid #e7edf3;
            border-radius: 6px;
            background: #fbfcfe;
            cursor: pointer;
            overflow-wrap: anywhere;
        }

        .opcion:hover { background: var(--azul-claro); }

        .opcion input {
            flex: 0 0 auto;
            margin: 3px 0 0;
            accent-color: var(--azul);
        }

        .campo-texto,
        .campo-select {
            display: block;
            width: 100%;
            min-height: 42px;
            padding: 9px 10px;
            color: var(--texto);
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font: inherit;
        }

        .campo-texto { min-height: 65px; resize: vertical; }

        .campo-texto:focus,
        .campo-select:focus {
            outline: 2px solid #bfdbfe;
            border-color: var(--azul);
        }

        .navegacion {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
            padding-top: 13px;
            border-top: 1px solid var(--borde);
        }

        .botones {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 8px;
        }

        .boton {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 39px;
            padding: 9px 14px;
            border: 1px solid transparent;
            border-radius: 7px;
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .anterior {
            color: #334155;
            background: #f1f5f9;
            border-color: #dbe3ec;
        }

        .siguiente { color: #fff; background: var(--azul); }

        .guardar {
            color: #174a7e;
            background: #eaf2fa;
            border-color: #c7d9ed;
        }

        /* Finalizar permanece azul claro, no verde. */
        .finalizar {
            color: #174a7e;
            background: #eaf2fa;
            border: 1px solid #c7d9ed;
        }

        .boton:hover:not(:disabled) { filter: brightness(.96); }

        .boton:disabled {
            opacity: .45;
            cursor: not-allowed;
            filter: grayscale(.2);
        }

        .estado-pestana { color: var(--gris); font-size: 12px; }

        .mensaje {
            display: none;
            margin: 0 0 13px;
            padding: 10px 12px;
            color: #842029;
            background: #f8d7da;
            border: 1px solid #f1aeb5;
            border-radius: 7px;
        }

        .mensaje.visible { display: block; }

        .pregunta-condicional[hidden] { display: none !important; }

        .pie {
            margin-top: 15px;
            color: var(--gris);
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 650px) {
            body { padding: 10px 7px; }
            .encabezado { padding: 15px; }
            .encabezado h1 { font-size: 20px; }
            .datos { grid-template-columns: 1fr; gap: 7px; }
            .tarjeta { padding: 11px; }
            .pestanas { gap: 4px; }
            .pestana { padding: 8px 2px; font-size: 10px; }
            .opciones { grid-template-columns: 1fr; }
            .botones { width: 100%; }
            .boton { flex: 1 1 auto; }
        }
    </style>
</head>

<body>
<div class="contenedor">

    <header class="encabezado">
        <h1>SITA · Cuestionario de Bienestar</h1>
        <p>Este formulario permite registrar su información para el acompañamiento de Bienestar al Aprendiz.</p>
        <p>Responda con sinceridad. Puede guardar un borrador y continuar después, si el sistema lo permite.</p>
    </header>

    <div class="datos">
        <div class="dato">
            <strong>Aprendiz</strong>
            <span><?= $e($nombreAprendiz) ?></span>
        </div>
        <div class="dato">
            <strong>Ficha</strong>
            <span><?= $e($nombreFicha) ?></span>
        </div>
        <div class="dato">
            <strong>Total de preguntas</strong>
            <span><?= (int) $totalPreguntas ?></span>
        </div>
    </div>

    <main class="tarjeta">
        <p class="instruccion">
            Complete los cinco componentes. Las preguntas marcadas con
            <span class="obligatoria">*</span> son obligatorias.
        </p>

        <div id="mensajeValidacion" class="mensaje" role="alert" aria-live="polite"></div>

        <div class="barra" role="progressbar" aria-label="Progreso del cuestionario"
             aria-valuemin="1" aria-valuemax="5" aria-valuenow="1" id="barraContenedor">
            <div class="barra-progreso" id="barraProgreso"></div>
        </div>

        <nav class="pestanas" aria-label="Pestañas del cuestionario">
            <?php foreach ($grupos as $indice => $grupo): ?>
                <button
                    type="button"
                    class="pestana <?= $indice === 0 ? 'activa' : '' ?>"
                    data-pestana="<?= (int) $indice ?>"
                    aria-selected="<?= $indice === 0 ? 'true' : 'false' ?>"
                ><?= $e($nombresPestanas[$indice]) ?></button>
            <?php endforeach; ?>
        </nav>

        <form id="formBienestar" method="POST"
              action="<?= $e(url('/bienestar/cuestionario/guardar')) ?>">

            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <input type="hidden" name="token" value="<?= $e($tokenFormulario) ?>">

            <?php foreach ($grupos as $indiceGrupo => $grupo): ?>
                <section
                    class="contenido-pestana"
                    data-contenido="<?= (int) $indiceGrupo ?>"
                    <?= $indiceGrupo !== 0 ? 'hidden' : '' ?>
                >
                    <?php if (empty($grupo)): ?>
                        <div class="seccion">
                            <h2><?= $e($nombresPestanas[$indiceGrupo]) ?></h2>
                            <p class="instruccion">No hay preguntas asignadas a este componente.</p>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($grupo as $seccion): ?>
                        <div class="seccion">
                            <h2><?= $e($seccion['nombre'] ?? $seccion['titulo'] ?? 'Sección') ?></h2>

                            <p class="descripcion-componente">
                                <?= $e(
                                    $seccion['descripcion']
                                    ?? $descripcionesComponentes[$indiceGrupo]
                                    ?? 'Responda las preguntas de este componente.'
                                ) ?>
                            </p>

                            <?php $preguntas = $seccion['preguntas'] ?? []; ?>

                            <?php foreach ($preguntas as $pregunta): ?>
                                <?php
                                $idPregunta = (string) ($pregunta['id'] ?? '');
                                $tipo = strtolower((string) ($pregunta['tipo'] ?? 'texto'));
                                $opciones = $pregunta['opciones'] ?? [];
                                $obligatoria = !empty($pregunta['obligatoria']);
                                $valor = $valorRespuesta($idPregunta);
                                $nombreCampo = 'respuestas[' . $idPregunta . ']';

                                $esMultiple = in_array(
                                    $tipo,
                                    ['checkbox', 'seleccion_multiple', 'opcion_multiple'],
                                    true
                                );

                                $esUnica = in_array(
                                    $tipo,
                                    ['radio', 'seleccion_unica', 'opcion_unica', 'escala', 'rating'],
                                    true
                                );

                                if ($esMultiple) {
                                    $nombreCampo .= '[]';
                                }

                                $esPreguntaFrecuencia = strtoupper($idPregunta) === 'PP-02-FRECUENCIA';
                                ?>

                                <div
                                    class="pregunta <?= $esPreguntaFrecuencia ? 'pregunta-condicional' : '' ?>"
                                    data-pregunta="<?= $e($idPregunta) ?>"
                                    <?= $esPreguntaFrecuencia ? 'data-condicional="PP-02" hidden' : '' ?>
                                >
                                    <label class="pregunta-titulo">
                                        <?= $e($pregunta['texto'] ?? '') ?>
                                        <?php if ($obligatoria): ?>
                                            <span class="obligatoria">*</span>
                                        <?php endif; ?>
                                    </label>

                                    <?php if (!empty($pregunta['ayuda'])): ?>
                                        <p class="ayuda"><?= $e($pregunta['ayuda']) ?></p>
                                    <?php endif; ?>

                                    <?php if ($esUnica): ?>
                                        <?php
                                        $opcionesUnicas = !empty($opciones)
                                            ? $opciones
                                            : ($tipo === 'escala' || $tipo === 'rating'
                                                ? [1, 2, 3, 4, 5]
                                                : []);
                                        ?>

                                        <select
                                            class="campo-select"
                                            name="<?= $e($nombreCampo) ?>"
                                            <?= $obligatoria ? 'required' : '' ?>
                                        >
                                            <option value="">Seleccione una opción...</option>
                                            <?php foreach ($opcionesUnicas as $opcion): ?>
                                                <?php
                                                $valorOpcion = is_array($opcion)
                                                    ? ($opcion['valor'] ?? $opcion['value'] ?? $opcion['texto'] ?? '')
                                                    : $opcion;

                                                $textoOpcion = is_array($opcion)
                                                    ? ($opcion['texto'] ?? $opcion['label'] ?? $valorOpcion)
                                                    : $opcion;
                                                ?>
                                                <option
                                                    value="<?= $e($valorOpcion) ?>"
                                                    <?= $seleccionada($valor, $valorOpcion) ? 'selected' : '' ?>
                                                ><?= $e($textoOpcion) ?></option>
                                            <?php endforeach; ?>
                                        </select>

                                    <?php elseif ($esMultiple): ?>
                                        <div class="opciones">
                                            <?php foreach ($opciones as $opcion): ?>
                                                <?php
                                                $valorOpcion = is_array($opcion)
                                                    ? ($opcion['valor'] ?? $opcion['value'] ?? $opcion['texto'] ?? '')
                                                    : $opcion;

                                                $textoOpcion = is_array($opcion)
                                                    ? ($opcion['texto'] ?? $opcion['label'] ?? $valorOpcion)
                                                    : $opcion;
                                                ?>
                                                <label class="opcion">
                                                    <input
                                                        type="checkbox"
                                                        name="respuestas[<?= $e($idPregunta) ?>][]"
                                                        value="<?= $e($valorOpcion) ?>"
                                                        <?= $seleccionada($valor, $valorOpcion) ? 'checked' : '' ?>
                                                    >
                                                    <span><?= $e($textoOpcion) ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>

                                        <?php if ($obligatoria): ?>
                                            <input
                                                type="text"
                                                class="validador-checkbox"
                                                data-grupo-checkbox="<?= $e($idPregunta) ?>"
                                                tabindex="-1"
                                                aria-hidden="true"
                                                required
                                                style="position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;"
                                            >
                                        <?php endif; ?>

                                    <?php elseif ($tipo === 'fecha'): ?>
                                        <input
                                            class="campo-texto"
                                            type="date"
                                            name="<?= $e($nombreCampo) ?>"
                                            value="<?= $e($valor) ?>"
                                            <?= $obligatoria ? 'required' : '' ?>
                                        >

                                    <?php elseif (in_array($tipo, ['numero', 'number'], true)): ?>
                                        <input
                                            class="campo-texto"
                                            type="number"
                                            name="<?= $e($nombreCampo) ?>"
                                            value="<?= $e($valor) ?>"
                                            <?= $obligatoria ? 'required' : '' ?>
                                        >

                                    <?php else: ?>
                                        <textarea
                                            class="campo-texto"
                                            name="<?= $e($nombreCampo) ?>"
                                            <?= $obligatoria ? 'required' : '' ?>
                                            rows="2"
                                        ><?= $e($valor) ?></textarea>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>

            <div class="navegacion">
                <span class="estado-pestana" id="estadoPestana">Componente 1 de 5</span>

                <div class="botones">
                    <button type="button" class="boton anterior" id="btnAnterior" disabled>
                        Anterior
                    </button>

                    <button type="button" class="boton siguiente" id="btnSiguiente">
                        Siguiente
                    </button>

                    <button
                        type="submit"
                        class="boton guardar"
                        formaction="<?= $e(url('/bienestar/cuestionario/guardar')) ?>"
                        formnovalidate
                    >
                        Guardar borrador
                    </button>

                    <button
                        type="submit"
                        class="boton finalizar"
                        id="btnFinalizar"
                        formaction="<?= $e(url('/bienestar/cuestionario/finalizar')) ?>"
                        hidden
                        disabled
                    >
                        Finalizar cuestionario
                    </button>
                </div>
            </div>
        </form>
    </main>

    <footer class="pie">SITA · Sistema de Trazabilidad de Aprendices</footer>
</div>

<script>
(function () {
    'use strict';

    const formulario = document.getElementById('formBienestar');
    const pestanas = Array.from(document.querySelectorAll('[data-pestana]'));
    const contenidos = Array.from(document.querySelectorAll('[data-contenido]'));
    const btnAnterior = document.getElementById('btnAnterior');
    const btnSiguiente = document.getElementById('btnSiguiente');
    const btnFinalizar = document.getElementById('btnFinalizar');
    const estadoPestana = document.getElementById('estadoPestana');
    const barraProgreso = document.getElementById('barraProgreso');
    const barraContenedor = document.getElementById('barraContenedor');
    const mensaje = document.getElementById('mensajeValidacion');

    let pestanaActual = 0;
    const totalPestanas = contenidos.length;

    function mostrarMensaje(texto) {
        mensaje.textContent = texto;
        mensaje.classList.add('visible');
        mensaje.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function ocultarMensaje() {
        mensaje.textContent = '';
        mensaje.classList.remove('visible');
    }

    function esAfirmativo(valor) {
        const normalizado = String(valor ?? '')
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');

        return ['si', 'yes', 'true', '1', 'de acuerdo'].includes(normalizado);
    }

    /*
     * Mostrar u ocultar la pregunta condicional PP-02-FRECUENCIA.
     */
    function actualizarPreguntasCondicionales() {
        document.querySelectorAll('[data-condicional]').forEach(function (dependiente) {
            const idControl = dependiente.dataset.condicional;
            const control = document.querySelector(
                '[data-pregunta="' + CSS.escape(idControl) + '"]'
            );

            if (!control) {
                dependiente.hidden = false;
                dependiente.querySelectorAll('input, select, textarea').forEach(function (campo) {
                    campo.disabled = false;
                });
                return;
            }

            const controles = Array.from(
                control.querySelectorAll('input, select, textarea')
            );

            let mostrar = false;

            const selector = controles.find(function (campo) {
                return campo.tagName === 'SELECT';
            });

            if (selector) {
                mostrar = esAfirmativo(selector.value);
            } else {
                const radios = controles.filter(function (campo) {
                    return campo.type === 'radio';
                });

                if (radios.length) {
                    const radioMarcado = radios.find(function (radio) {
                        return radio.checked;
                    });
                    mostrar = radioMarcado ? esAfirmativo(radioMarcado.value) : false;
                } else {
                    const casillas = controles.filter(function (campo) {
                        return campo.type === 'checkbox';
                    });

                    if (casillas.length) {
                        const marcadas = casillas.filter(function (casilla) {
                            return casilla.checked;
                        });

                        const seleccionNo = marcadas.some(function (casilla) {
                            const valor = String(casilla.value).trim().toLowerCase();
                            return [
                                'no',
                                'ninguno',
                                'ninguna',
                                'ninguna de las anteriores'
                            ].includes(valor);
                        });

                        mostrar = marcadas.length > 0 && !seleccionNo;
                    } else {
                        const campoTexto = controles.find(function (campo) {
                            return campo.type !== 'hidden';
                        });
                        mostrar = campoTexto ? esAfirmativo(campoTexto.value) : false;
                    }
                }
            }

            dependiente.hidden = !mostrar;

            dependiente.querySelectorAll('input, select, textarea').forEach(function (campo) {
                campo.disabled = !mostrar;
            });
        });
    }

    /*
     * Comprueba todas las preguntas obligatorias sin cambiar de pestaña
     * ni mostrar mensajes. Se usa para habilitar/deshabilitar Finalizar.
     */
    function todasLasObligatoriasRespondidas() {
        actualizarPreguntasCondicionales();

        for (const pregunta of document.querySelectorAll('.pregunta')) {
            if (pregunta.hidden) {
                continue;
            }

            const controles = Array.from(
                pregunta.querySelectorAll('input, select, textarea')
            ).filter(function (campo) {
                return !campo.disabled;
            });

            for (const campo of controles) {
                if (!campo.required) {
                    continue;
                }

                if (campo.hasAttribute('data-grupo-checkbox')) {
                    const haySeleccion = Array.from(
                        pregunta.querySelectorAll('input[type="checkbox"]:not(:disabled)')
                    ).some(function (casilla) {
                        return casilla.checked;
                    });

                    if (!haySeleccion) {
                        return false;
                    }

                    continue;
                }

                if (campo.type === 'checkbox') {
                    if (!campo.checked) {
                        return false;
                    }
                    continue;
                }

                if (campo.type === 'radio') {
                    const radios = controles.filter(function (otro) {
                        return otro.type === 'radio' && otro.name === campo.name;
                    });

                    if (!radios.some(function (radio) {
                        return radio.checked;
                    })) {
                        return false;
                    }

                    continue;
                }

                if (!String(campo.value ?? '').trim()) {
                    return false;
                }
            }
        }

        return true;
    }

    /*
     * Finalizar solo se habilita en el componente 5 y cuando
     * todas las preguntas obligatorias tienen respuesta.
     */
    function actualizarEstadoBotonFinalizar() {
        const estaEnUltimoComponente = pestanaActual === totalPestanas - 1;

        btnFinalizar.hidden = !estaEnUltimoComponente;

        if (!estaEnUltimoComponente) {
            btnFinalizar.disabled = true;
            return;
        }

        btnFinalizar.disabled = !todasLasObligatoriasRespondidas();

        btnFinalizar.title = btnFinalizar.disabled
            ? 'Complete todas las preguntas obligatorias para finalizar.'
            : 'Puede finalizar el cuestionario.';
    }

    function mostrarPestana(indice) {
        if (indice < 0 || indice >= totalPestanas) {
            return;
        }

        pestanaActual = indice;

        contenidos.forEach(function (contenido, i) {
            contenido.hidden = i !== indice;
        });

        pestanas.forEach(function (pestana, i) {
            pestana.classList.toggle('activa', i === indice);
            pestana.setAttribute('aria-selected', i === indice ? 'true' : 'false');
        });

        btnAnterior.disabled = indice === 0;
        btnSiguiente.hidden = indice === totalPestanas - 1;

        estadoPestana.textContent =
            'Componente ' + (indice + 1) + ' de ' + totalPestanas;

        barraProgreso.style.width = (((indice + 1) / totalPestanas) * 100) + '%';
        barraContenedor.setAttribute('aria-valuenow', String(indice + 1));

        ocultarMensaje();
        actualizarEstadoBotonFinalizar();

        window.scrollTo({
            top: formulario.getBoundingClientRect().top + window.scrollY - 15,
            behavior: 'smooth'
        });
    }

    function validarPestana(indice) {
        const contenido = contenidos[indice];

        if (!contenido) {
            return true;
        }

        const preguntas = Array.from(contenido.querySelectorAll('.pregunta'));

        for (const pregunta of preguntas) {
            if (pregunta.hidden) {
                continue;
            }

            const controles = Array.from(
                pregunta.querySelectorAll('input, textarea, select')
            ).filter(function (control) {
                return !control.disabled;
            });

            const requerido = controles.some(function (control) {
                return control.required;
            });

            if (!requerido) {
                continue;
            }

            const radios = controles.filter(function (control) {
                return control.type === 'radio';
            });

            if (radios.length && !radios.some(function (radio) {
                return radio.checked;
            })) {
                mostrarMensaje(
                    'Por favor, responda las preguntas obligatorias antes de continuar.'
                );
                pregunta.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }

            const selects = controles.filter(function (control) {
                return control.tagName === 'SELECT' && control.required;
            });

            if (selects.some(function (select) {
                return !select.value.trim();
            })) {
                mostrarMensaje(
                    'Seleccione una opción en cada pregunta obligatoria.'
                );
                pregunta.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }

            const validadorCheckbox = pregunta.querySelector('[data-grupo-checkbox]');

            if (validadorCheckbox) {
                const haySeleccion = Array.from(
                    pregunta.querySelectorAll('input[type="checkbox"]:not(:disabled)')
                ).some(function (casilla) {
                    return casilla.checked;
                });

                if (!haySeleccion) {
                    mostrarMensaje(
                        'Seleccione al menos una opción en cada pregunta obligatoria de selección múltiple.'
                    );
                    pregunta.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }

                continue;
            }

            const camposTexto = controles.filter(function (control) {
                return control.required &&
                    control.type !== 'radio' &&
                    control.type !== 'checkbox' &&
                    control.type !== 'hidden' &&
                    control.tagName !== 'SELECT';
            });

            for (const campo of camposTexto) {
                if (!String(campo.value ?? '').trim()) {
                    mostrarMensaje(
                        'Por favor, complete las preguntas obligatorias antes de continuar.'
                    );
                    campo.focus({ preventScroll: true });
                    pregunta.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
            }
        }

        return true;
    }

    btnAnterior.addEventListener('click', function () {
        mostrarPestana(pestanaActual - 1);
    });

    btnSiguiente.addEventListener('click', function () {
        actualizarPreguntasCondicionales();

        if (!validarPestana(pestanaActual)) {
            actualizarEstadoBotonFinalizar();
            return;
        }

        mostrarPestana(pestanaActual + 1);
    });

    pestanas.forEach(function (pestana, indice) {
        pestana.addEventListener('click', function () {
            if (indice <= pestanaActual) {
                mostrarPestana(indice);
                return;
            }

            actualizarPreguntasCondicionales();

            if (!validarPestana(pestanaActual)) {
                actualizarEstadoBotonFinalizar();
                return;
            }

            mostrarPestana(indice);
        });
    });

    formulario.addEventListener('input', function () {
        actualizarEstadoBotonFinalizar();
        ocultarMensaje();
    });

    formulario.addEventListener('change', function () {
        actualizarPreguntasCondicionales();
        actualizarEstadoBotonFinalizar();
        ocultarMensaje();
    });

    formulario.addEventListener('submit', function (evento) {
        actualizarPreguntasCondicionales();

        const boton = evento.submitter;
        const esFinalizar = boton && boton.id === 'btnFinalizar';

        /*
         * Guardar borrador no exige responder todo.
         */
        if (!esFinalizar) {
            return;
        }

        /*
         * Bloqueo adicional: no finalizar si hay preguntas obligatorias
         * pendientes, aunque se intente enviar el formulario.
         */
        if (!todasLasObligatoriasRespondidas()) {
            evento.preventDefault();

            for (let i = 0; i < totalPestanas; i++) {
                if (!validarPestana(i)) {
                    mostrarPestana(i);
                    return;
                }
            }

            mostrarMensaje(
                'Complete todas las preguntas obligatorias antes de finalizar.'
            );
            actualizarEstadoBotonFinalizar();
            return;
        }

        if (!confirm(
            '¿Desea finalizar el cuestionario? Después de finalizarlo, no podrá modificar las respuestas desde este formulario.'
        )) {
            evento.preventDefault();
        }
    });

    /*
     * Inicializar los validadores de selección múltiple.
     */
    document.querySelectorAll('[data-grupo-checkbox]').forEach(function (validador) {
        const pregunta = validador.closest('.pregunta');

        if (!pregunta) {
            return;
        }

        const haySeleccion = Array.from(
            pregunta.querySelectorAll('input[type="checkbox"]')
        ).some(function (casilla) {
            return casilla.checked;
        });

        validador.value = haySeleccion ? 'seleccionado' : '';
    });

    actualizarPreguntasCondicionales();
    mostrarPestana(0);
    actualizarEstadoBotonFinalizar();
})();
</script>
</body>
</html>

