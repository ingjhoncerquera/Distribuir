<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SITA - Consulta de Habilidades Blandas
| Archivo: views/bienestar/consultas/habilidades_blandas.php
|--------------------------------------------------------------------------
*/

if (!function_exists('hbEsc')) {
    function hbEsc(mixed $valor): string
    {
        if (is_array($valor) || is_object($valor)) {
            $valor = json_encode($valor, JSON_UNESCAPED_UNICODE);
        }

        return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('hbFecha')) {
    function hbFecha(mixed $fecha): string
    {
        if (empty($fecha)) {
            return 'No registrada';
        }

        try {
            return (new DateTime((string) $fecha))->format('d/m/Y H:i');
        } catch (Throwable $e) {
            return (string) $fecha;
        }
    }
}

if (!function_exists('hbValorLegible')) {
    function hbValorLegible(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return 'Sin respuesta';
        }

        if (is_bool($valor)) {
            return $valor ? 'Sí' : 'No';
        }

        if (is_array($valor)) {
            if ($valor === []) {
                return 'Sin respuesta';
            }

            $partes = [];

            foreach ($valor as $elemento) {
                if (is_array($elemento)) {
                    $texto = hbValorLegible($elemento);

                    if ($texto !== 'Sin respuesta') {
                        $partes[] = $texto;
                    }
                } elseif (is_bool($elemento)) {
                    $partes[] = $elemento ? 'Sí' : 'No';
                } elseif (is_scalar($elemento) && trim((string) $elemento) !== '') {
                    $partes[] = (string) $elemento;
                }
            }

            return $partes !== []
                ? implode(', ', array_unique($partes))
                : 'Sin respuesta';
        }

        $texto = trim((string) $valor);

        if ($texto === '') {
            return 'Sin respuesta';
        }

        if ($texto[0] === '[' || $texto[0] === '{') {
            $decodificado = json_decode($texto, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decodificado)) {
                return hbValorLegible($decodificado);
            }
        }

        return $texto;
    }
}

/*
|--------------------------------------------------------------------------
| Variables recibidas del controlador
|--------------------------------------------------------------------------
*/

$tituloPagina = $tituloPagina ?? 'Consulta de Habilidades Blandas';
$titulo = $titulo ?? $tituloPagina;

$busqueda = (string) ($busqueda ?? '');

/* Se guarda aquí: más abajo los bucles de las gráficas reutilizan $textoRespuesta. */
$filtroTextoRespuesta = (string) ($textoRespuesta ?? '');
$idFicha = (int) ($idFicha ?? 0);
$idAprendiz = (int) ($idAprendiz ?? 0);

$estadoFiltro = strtolower(trim((string) ($_GET['estado_cuestionario'] ?? 'todos')));

if (!in_array($estadoFiltro, ['todos', 'pendiente', 'en_proceso', 'completado'], true)) {
    $estadoFiltro = 'todos';
}

$fichas = is_array($fichas ?? null) ? $fichas : [];
$aprendices = is_array($aprendices ?? null) ? $aprendices : [];
$resumen = is_array($resumen ?? null) ? $resumen : [];
$estadisticas = is_array($estadisticas ?? null) ? $estadisticas : [];
$cuestionarios = is_array($cuestionarios ?? null) ? $cuestionarios : [];

$aprendizSeleccionado = is_array($aprendizSeleccionado ?? null)
    ? $aprendizSeleccionado
    : null;

$urlConsulta = url('/bienestar/consultas/habilidades-blandas');

/*
|--------------------------------------------------------------------------
| Preguntas conocidas de Habilidades Blandas
|--------------------------------------------------------------------------
*/

$preguntasHB = [
    'HB-01' => '¿Cuáles de las siguientes situaciones le generan estrés o preocupación durante su proceso de formación?',
    'HB-02' => '¿Con quién cuenta cuando atraviesa una situación difícil o necesita apoyo?',
    'HB-03' => 'Si estuviera atravesando una situación difícil, ¿qué tan fácil le resultaría pedir ayuda?',
    'HB-04' => '¿Cómo califica su capacidad para expresar sus ideas, escuchar a los demás y comunicarse respetuosamente? (escala de 1 a 5)',
    'HB-05' => '¿Qué tan cómodo(a) se siente trabajando en equipo con personas que tienen ideas y formas de trabajar diferentes a las suyas? (escala de 1 a 5)',
    'HB-06' => 'Cuando tiene un desacuerdo con un compañero, ¿cómo suele actuar?',
    'HB-07' => '¿Qué habilidades personales le gustaría fortalecer para afrontar mejor los retos de la formación y la vida cotidiana?',
    'HB-08' => '¿Cómo considera que organiza su tiempo para cumplir con las actividades de formación y sus responsabilidades personales?',
    'HB-09' => '¿En cuáles de los siguientes talleres o actividades le gustaría participar?',
];

$nombrePregunta = static function (string $codigo) use ($preguntasHB): string {
    $normalizado = strtoupper(str_replace('_', '-', trim($codigo)));

    return $preguntasHB[$normalizado]
        ?? ucfirst(str_replace(['_', '-'], ' ', $codigo));
};

$textoPreguntaEstadistica = static function (
    array $estadistica,
    string $codigo
) use ($preguntasHB, $nombrePregunta): string {
    $normalizado = strtoupper(str_replace('_', '-', trim($codigo)));

    if (isset($preguntasHB[$normalizado])) {
        return $preguntasHB[$normalizado];
    }

    return (string) (
        $estadistica['texto']
        ?? $estadistica['enunciado']
        ?? $estadistica['nombre']
        ?? $nombrePregunta($codigo)
    );
};

/*
|--------------------------------------------------------------------------
| Funciones de presentación
|--------------------------------------------------------------------------
*/

$obtenerNombreAprendiz = static function (array $fila): string {
    $nombre = trim((string) ($fila['nombre_completo'] ?? ''));

    if ($nombre !== '') {
        return $nombre;
    }

    return trim(
        (string) ($fila['nombres'] ?? '') . ' ' .
        (string) ($fila['apellidos'] ?? '')
    );
};

$obtenerEstado = static function (array $fila): string {
    $estado = strtolower(trim((string) (
        $fila['estado_cuestionario'] ?? $fila['estado'] ?? ''
    )));

    if ($estado === 'completado') {
        return 'Completado';
    }

    if (in_array($estado, ['borrador', 'en_proceso'], true)) {
        return 'En proceso';
    }

    return 'Pendiente';
};

$obtenerClaseEstado = static function (string $estado): string {
    return match ($estado) {
        'Completado' => 'completed',
        'En proceso' => 'draft',
        default => 'pending',
    };
};

/*
|--------------------------------------------------------------------------
| Filtro por estado del cuestionario
|--------------------------------------------------------------------------
*/

if ($estadoFiltro !== 'todos') {
    $aprendices = array_values(array_filter(
        $aprendices,
        static function (array $fila) use ($obtenerEstado, $estadoFiltro): bool {
            $estado = $obtenerEstado($fila);

            return match ($estadoFiltro) {
                'completado' => $estado === 'Completado',
                'en_proceso' => $estado === 'En proceso',
                'pendiente' => $estado === 'Pendiente',
                default => true,
            };
        }
    ));
}

/*
|--------------------------------------------------------------------------
| Normalización de respuestas
|--------------------------------------------------------------------------
*/

$normalizarRespuestasHB = static function (mixed $datos): array {
    if (is_string($datos)) {
        $datos = trim($datos);

        if ($datos === '') {
            return [];
        }

        $decodificado = json_decode($datos, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decodificado)) {
            return [];
        }

        $datos = $decodificado;
    }

    if (!is_array($datos)) {
        return [];
    }

    foreach (['respuestas', 'answers', 'datos', 'data'] as $contenedor) {
        if (isset($datos[$contenedor]) && is_array($datos[$contenedor])) {
            $datos = $datos[$contenedor];
            break;
        }
    }

    $resultado = [];

    $recorrer = static function (array $elementos) use (&$recorrer, &$resultado): void {
        foreach ($elementos as $clave => $valor) {
            $claveTexto = is_string($clave) ? trim($clave) : '';

            if (is_array($valor)) {
                $codigo = $valor['codigo']
                    ?? $valor['codigo_pregunta']
                    ?? $valor['pregunta_codigo']
                    ?? $valor['id_pregunta_codigo']
                    ?? null;

                if ($codigo !== null && trim((string) $codigo) !== '') {
                    $codigo = strtoupper(str_replace('_', '-', trim((string) $codigo)));

                    if (preg_match('/^HB(?:-|$)/', $codigo)) {
                        $respuesta = $valor['respuesta']
                            ?? $valor['valor']
                            ?? $valor['respuesta_texto']
                            ?? $valor['opcion']
                            ?? $valor['respuesta_json']
                            ?? null;

                        $resultado[$codigo] = $respuesta;
                    }

                    continue;
                }

                $claveNormalizada = strtoupper(str_replace('_', '-', $claveTexto));

                if ($claveTexto !== '' && preg_match('/^HB(?:-|$)/', $claveNormalizada)) {
                    $resultado[$claveNormalizada] = $valor;
                    continue;
                }

                $recorrer($valor);
                continue;
            }

            $claveNormalizada = strtoupper(str_replace('_', '-', $claveTexto));

            if ($claveTexto !== '' && preg_match('/^HB(?:-|$)/', $claveNormalizada)) {
                $resultado[$claveNormalizada] = $valor;
            }
        }
    };

    $recorrer($datos);

    return $resultado;
};

/*
|--------------------------------------------------------------------------
| Tarjetas de resumen
|--------------------------------------------------------------------------
*/

$totalAprendices = (int) (
    $resumen['total_aprendices']
    ?? $resumen['total_matriculados']
    ?? $resumen['total']
    ?? 0
);

$totalCompletados = (int) (
    $resumen['completados']
    ?? $resumen['total_completados']
    ?? 0
);

$totalBorradores = (int) (
    $resumen['borradores']
    ?? $resumen['en_proceso']
    ?? 0
);

$totalPendientes = (int) (
    $resumen['pendientes']
    ?? $resumen['sin_iniciar']
    ?? 0
);

$porcentajeAvance = (float) (
    $resumen['porcentaje_completado']
    ?? $resumen['porcentaje']
    ?? 0
);

if (
    $totalAprendices > 0
    && !isset($resumen['porcentaje_completado'])
    && !isset($resumen['porcentaje'])
) {
    $porcentajeAvance = ($totalCompletados / $totalAprendices) * 100;
}

$porcentajeAvance = max(0, min(100, $porcentajeAvance));

/*
|--------------------------------------------------------------------------
| Estadísticas: exclusivamente Habilidades Blandas
|--------------------------------------------------------------------------
*/

$estadisticasHB = [];

foreach ($estadisticas as $codigo => $estadistica) {
    if (!is_array($estadistica)) {
        continue;
    }

    $codigoPregunta = (string) (
        $estadistica['codigo']
        ?? $estadistica['id_pregunta']
        ?? $estadistica['pregunta_codigo']
        ?? (is_string($codigo) ? $codigo : '')
    );

    $codigoPregunta = strtoupper(str_replace('_', '-', trim($codigoPregunta)));

    if ($codigoPregunta === '' || !preg_match('/^HB(?:-|$)/', $codigoPregunta)) {
        continue;
    }

    $estadisticasHB[$codigoPregunta] = $estadistica;
}

/*
|--------------------------------------------------------------------------
| Preparación de datos para las gráficas
|--------------------------------------------------------------------------
*/

$hbGraficaEstados = [
    'Completados' => max(0, $totalCompletados),
    'En proceso' => max(0, $totalBorradores),
    'Pendientes' => max(0, $totalPendientes),
];

$hbGraficaPreguntas = [];

foreach ($estadisticasHB as $codigo => $estadistica) {
    $respuestasGrafica = $estadistica['respuestas'] ?? [];

    if (is_string($respuestasGrafica)) {
        $decodificadas = json_decode($respuestasGrafica, true);
        $respuestasGrafica = is_array($decodificadas) ? $decodificadas : [];
    }

    if (!is_array($respuestasGrafica)) {
        $respuestasGrafica = [];
    }

    $etiquetasGrafica = [];
    $cantidadesGrafica = [];

    foreach ($respuestasGrafica as $claveRespuesta => $datoRespuesta) {
        if (is_array($datoRespuesta)) {
            $cantidadRespuesta = (int) (
                $datoRespuesta['cantidad']
                ?? $datoRespuesta['total']
                ?? 0
            );

            $textoRespuesta = $datoRespuesta['valor']
                ?? $datoRespuesta['respuesta']
                ?? (string) $claveRespuesta;
        } else {
            $cantidadRespuesta = (int) $datoRespuesta;
            $textoRespuesta = (string) $claveRespuesta;
        }

        $etiquetasGrafica[] = hbValorLegible($textoRespuesta);
        $cantidadesGrafica[] = max(0, $cantidadRespuesta);
    }

    $textoPreguntaGrafica = $textoPreguntaEstadistica($estadistica, (string) $codigo);

    $hbGraficaPreguntas[] = [
        'codigo' => (string) $codigo,
        'pregunta' => (string) $textoPreguntaGrafica,
        'etiquetas' => $etiquetasGrafica,
        'cantidades' => $cantidadesGrafica,
    ];
}

$hbGraficaEstadosJson = json_encode(
    $hbGraficaEstados,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);

$hbGraficaPreguntasJson = json_encode(
    $hbGraficaPreguntas,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);

/*
|--------------------------------------------------------------------------
| Datos individuales
|--------------------------------------------------------------------------
*/

$datosIndividuales = $aprendizSeleccionado ?? [];

$nombreIndividual = $obtenerNombreAprendiz($datosIndividuales);

if ($nombreIndividual === '') {
    $nombreIndividual = 'Nombre no disponible';
}

$documentoIndividual = $datosIndividuales['documento']
    ?? $datosIndividuales['numero_documento']
    ?? 'No registrado';

$numeroFichaIndividual = $datosIndividuales['numero_ficha']
    ?? $datosIndividuales['ficha']
    ?? null;

$programaIndividual = $datosIndividuales['programa']
    ?? $datosIndividuales['nombre_programa']
    ?? null;

$correoIndividual = $datosIndividuales['correo']
    ?? $datosIndividuales['email']
    ?? $datosIndividuales['correo_electronico']
    ?? null;

$estadoAprendiz = $datosIndividuales['estado'] ?? 'No registrado';

/*
|--------------------------------------------------------------------------
| Búsqueda por respuestas de Habilidades Blandas
|--------------------------------------------------------------------------
*/

$catalogoHB = is_array($catalogoHB ?? null) ? $catalogoHB : [];
$filtrosRapidos = is_array($filtrosRapidos ?? null) ? $filtrosRapidos : [];
$hbPregunta = (string) ($hbPregunta ?? '');
$hbRespuesta = (string) ($hbRespuesta ?? '');
$hayFiltrosRespuesta = (bool) ($hayFiltrosRespuesta ?? false);
$aprendicesFiltrados = is_array($aprendicesFiltrados ?? null)
    ? $aprendicesFiltrados
    : [];
$totalConRespuestasHB = (int) ($totalConRespuestasHB ?? 0);
$maxFilasVista = (int) ($maxFilasVista ?? 500);

$opcionesJsonHB = JSON_UNESCAPED_UNICODE
    | JSON_HEX_TAG
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
    | JSON_HEX_AMP;

/* Filtros rápidos que se muestran como lista desplegable. */
$filtrosRapidosConfig = [
    'HB-09' => 'Taller o actividad en la que desea participar',
    'HB-07' => 'Habilidad que desea fortalecer',
    'HB-01' => 'Situación que le genera estrés',
    'HB-02' => 'Persona con quien cuenta para pedir apoyo',
    'HB-03' => 'Facilidad para pedir ayuda',
    'HB-04' => 'Comunicación y escucha (1 a 5)',
    'HB-05' => 'Comodidad trabajando en equipo (1 a 5)',
    'HB-06' => 'Forma de actuar ante un desacuerdo',
    'HB-08' => 'Organización del tiempo',
];

$minusculasHB = static function (string $texto): string {
    $texto = trim($texto);

    $texto = function_exists('mb_strtolower')
        ? mb_strtolower($texto, 'UTF-8')
        : strtolower($texto);

    return strtr($texto, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o',
        'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
    ]);
};

$mismoTexto = static function (string $a, string $b) use ($minusculasHB): bool {
    return $minusculasHB($a) === $minusculasHB($b);
};

$ordenarOpciones = static function (array $datos): array {
    $opciones = $datos['opciones'] ?? [];

    // Orden natural: 1, 2, ... 10 (y no 1, 10, 2).
    uasort(
        $opciones,
        static fn(array $a, array $b): int =>
            strnatcasecmp((string) $a['nombre'], (string) $b['nombre'])
    );

    return $opciones;
};

/* Parámetros que se conservan en los enlaces de la página. */
$parametrosBase = [
    'busqueda' => $busqueda,
    'id_ficha' => $idFicha,
    'estado_cuestionario' => $estadoFiltro,
    'hb' => $filtrosRapidos,
    'hb_pregunta' => $hbPregunta,
    'hb_respuesta' => $hbRespuesta,
    'texto_respuesta' => $filtroTextoRespuesta,
];

$construirUrl = static function (
    array $extra = []
) use ($urlConsulta, $parametrosBase): string {
    $parametros = array_merge($parametrosBase, $extra);

    $parametros = array_filter(
        $parametros,
        static fn($valor): bool =>
            !($valor === '' || $valor === [] || $valor === 0 || $valor === null)
    );

    $consulta = http_build_query($parametros);

    return $urlConsulta . ($consulta !== '' ? '?' . $consulta : '');
};

/* Etiquetas (chips) con las respuestas de un aprendiz para una pregunta. */
$etiquetasHB = static function (array $fila, string $codigo): string {
    $opciones = $fila['respuestas_hb'][$codigo] ?? [];

    if ($opciones === []) {
        return '<span class="hb-muted">—</span>';
    }

    $html = '';

    foreach ($opciones as $opcion) {
        $html .= '<span class="hb-tag">' . hbEsc($opcion) . '</span>';
    }

    return $html;
};

/* Descripción de los filtros activos. */
$filtrosActivosTexto = [];

foreach ($filtrosRapidos as $codigoActivo => $valorActivo) {
    $filtrosActivosTexto[] = (
        $filtrosRapidosConfig[$codigoActivo]
        ?? $nombrePregunta((string) $codigoActivo)
    ) . ': ' . $valorActivo;
}

if ($hbPregunta !== '') {
    $filtrosActivosTexto[] = $hbPregunta . ': '
        . ($hbRespuesta !== '' ? $hbRespuesta : 'cualquier respuesta');
}

if ($filtroTextoRespuesta !== '') {
    $filtrosActivosTexto[] = 'Contiene «' . $filtroTextoRespuesta . '»'
        . ($hbPregunta !== '' ? ' en ' . $hbPregunta : ' en cualquier respuesta');
}

/* Catálogo para la lista dependiente (pregunta → respuestas) en JavaScript. */
$catalogoJs = [];

foreach ($catalogoHB as $codigoCatalogo => $datosCatalogo) {
    $nombresOpciones = array_column($datosCatalogo['opciones'] ?? [], 'nombre');
    natcasesort($nombresOpciones);

    $catalogoJs[$codigoCatalogo] = [
        'libre' => !empty($datosCatalogo['libre']),
        'opciones' => array_values($nombresOpciones),
    ];
}

$catalogoJsJson = json_encode($catalogoJs, $opcionesJsonHB);
$hbRespuestaJson = json_encode($hbRespuesta, $opcionesJsonHB);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= hbEsc($tituloPagina) ?> | SITA</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --hb-primary: #174a78;
            --hb-primary-dark: #103653;
            --hb-secondary: #2676a8;
            --hb-bg: #f3f6fa;
            --hb-card: #fff;
            --hb-text: #263445;
            --hb-muted: #64748b;
            --hb-border: #dfe7ef;
            --hb-green: #16835d;
            --hb-orange: #bd7614;
            --hb-red: #b83c48;
            --hb-radius: 14px;
        }

        .hb-page {
            background: var(--hb-bg);
            min-height: 75vh;
            padding: 24px 0 34px;
            color: var(--hb-text);
            font-family: Arial, Helvetica, sans-serif;
        }

        .hb-container {
            width: min(1450px, calc(100% - 32px));
            margin: 0 auto;
        }

        .hb-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .hb-header h1 {
            margin: 0 0 7px;
            font-size: clamp(23px, 3vw, 31px);
            color: var(--hb-primary-dark);
        }

        .hb-header p {
            margin: 0;
            color: var(--hb-muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .hb-card {
            background: var(--hb-card);
            border: 1px solid var(--hb-border);
            border-radius: var(--hb-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(20, 52, 80, .045);
            min-width: 0;
        }

        .hb-card h2 {
            color: var(--hb-primary-dark);
            font-size: 19px;
            margin: 0 0 16px;
        }

        .hb-card h3 {
            color: var(--hb-primary);
            font-size: 16px;
            margin: 0 0 12px;
        }

        .hb-summary-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .hb-stat {
            background: #fff;
            border: 1px solid var(--hb-border);
            border-radius: 13px;
            padding: 17px;
            min-width: 0;
        }

        .hb-stat-label {
            color: var(--hb-muted);
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 8px;
        }

        .hb-stat-value {
            color: var(--hb-primary-dark);
            font-size: 29px;
            line-height: 1.15;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .hb-stat.completed .hb-stat-value { color: var(--hb-green); }
        .hb-stat.draft .hb-stat-value { color: var(--hb-orange); }
        .hb-stat.pending .hb-stat-value { color: var(--hb-red); }

        .hb-progress {
            height: 10px;
            background: #e7edf4;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 12px;
        }

        .hb-progress > span {
            display: block;
            height: 100%;
            width: <?= number_format($porcentajeAvance, 2, '.', '') ?>%;
            background: var(--hb-green);
            border-radius: inherit;
        }

        .hb-form-grid {
            display: grid;
            grid-template-columns: 2fr 1.1fr 1fr auto;
            gap: 13px;
            align-items: end;
        }

        .hb-field label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 7px;
            color: #334155;
        }

        .hb-field input,
        .hb-field select {
            box-sizing: border-box;
            width: 100%;
            min-height: 43px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #fff;
            color: var(--hb-text);
            font-size: 14px;
        }

        .hb-button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
            min-height: 42px;
            padding: 10px 16px;
            border: 0;
            border-radius: 8px;
            background: var(--hb-primary);
            color: white !important;
            text-decoration: none !important;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
        }

        .hb-button:hover { background: var(--hb-primary-dark); }

        .hb-button.secondary {
            background: #e8eef5;
            color: var(--hb-primary-dark) !important;
        }

        .hb-button.small {
            min-height: 32px;
            padding: 7px 10px;
            font-size: 12px;
        }

        .hb-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .hb-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .hb-table th,
        .hb-table td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid var(--hb-border);
            vertical-align: middle;
        }

        .hb-table th {
            background: #f3f7fb;
            color: #334155;
            font-weight: 800;
            white-space: nowrap;
        }

        .hb-table tbody tr:hover { background: #f8fbfe; }

        .hb-badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .hb-badge.completed { background: #d9f4e7; color: #116444; }
        .hb-badge.draft { background: #fff0d2; color: #925400; }
        .hb-badge.pending { background: #fde2e4; color: #a12835; }

        .hb-muted { color: var(--hb-muted); }

        .hb-empty {
            padding: 25px 14px;
            text-align: center;
            color: var(--hb-muted);
            line-height: 1.6;
        }

        .hb-section {
            border: 1px solid var(--hb-border);
            border-radius: 11px;
            margin: 14px 0;
            overflow: hidden;
            background: #fff;
        }

        .hb-section-title {
            padding: 14px 16px;
            background: #edf4fa;
            color: var(--hb-primary-dark);
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .hb-section-body { padding: 14px; }

        .hb-question {
            padding: 16px;
            border-top: 1px solid var(--hb-border);
        }

        .hb-question-title {
            font-weight: 700;
            margin-bottom: 11px;
            line-height: 1.5;
        }

        .hb-response-row {
            display: grid;
            grid-template-columns: minmax(150px, 1.5fr) minmax(80px, .35fr) minmax(100px, 1fr);
            gap: 12px;
            align-items: center;
            padding: 8px 0;
            border-top: 1px solid #edf1f5;
            font-size: 13px;
        }

        .hb-response-row:first-of-type { border-top: 0; }

        .hb-bar {
            height: 8px;
            background: #e9eef4;
            border-radius: 999px;
            overflow: hidden;
        }

        .hb-bar span {
            display: block;
            height: 100%;
            background: var(--hb-secondary);
            border-radius: inherit;
        }

        .hb-profile-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .hb-profile-item {
            padding: 12px;
            border: 1px solid var(--hb-border);
            border-radius: 9px;
            min-width: 0;
            background: #fff;
        }

        .hb-profile-item .label {
            color: var(--hb-muted);
            font-size: 12px;
            margin-bottom: 5px;
        }

        .hb-profile-item .value {
            font-weight: 700;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .hb-answer {
            padding: 13px 14px;
            border: 1px solid var(--hb-border);
            border-radius: 9px;
            margin-bottom: 10px;
            background: #fff;
        }

        .hb-answer:last-child { margin-bottom: 0; }

        .hb-answer .question {
            font-weight: 700;
            margin-bottom: 7px;
            color: var(--hb-primary-dark);
            line-height: 1.5;
        }

        .hb-answer .value {
            font-size: 14px;
            line-height: 1.7;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        .hb-note {
            border-left: 4px solid #d6a13b;
            background: #fff9e9;
            color: #654b16;
            padding: 12px 14px;
            border-radius: 6px;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .hb-detail-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin: 24px 0 10px;
        }

        .hb-footer {
            text-align: center;
            padding: 14px 10px;
            color: #64748b;
            font-size: 12px;
        }

        .hb-charts-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-top: 18px;
        }

        .hb-chart-card {
            background: #fff;
            border: 1px solid var(--hb-border);
            border-radius: 12px;
            padding: 18px;
            min-width: 0;
            box-shadow: 0 3px 12px rgba(20, 52, 80, .04);
        }

        .hb-chart-card h3 {
            color: var(--hb-primary-dark);
            font-size: 16px;
            margin: 0 0 8px;
        }

        .hb-chart-description {
            color: var(--hb-muted);
            font-size: 13px;
            line-height: 1.6;
            margin: 0 0 16px;
        }

        .hb-chart-container {
            position: relative;
            width: 100%;
            height: 310px;
        }

        .hb-chart-container.hb-chart-doughnut {
            max-width: 390px;
            margin: 0 auto;
        }

        .hb-chart-filter { margin-bottom: 16px; }

        .hb-chart-filter label {
            display: block;
            color: #334155;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .hb-chart-filter select {
            width: 100%;
            min-height: 42px;
            box-sizing: border-box;
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #fff;
            color: var(--hb-text);
            font-size: 14px;
        }

        
.hb-chart-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 180px;
    padding: 15px;
    text-align: center;
    color: var(--hb-muted);
    font-size: 13px;
    line-height: 1.6;
}

/* Ocultar correctamente los mensajes cuando no corresponden */
.hb-chart-empty[hidden] {
    display: none !important;
}

        .hb-chart-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .hb-chart-summary-item {
            border: 1px solid var(--hb-border);
            border-radius: 9px;
            padding: 11px;
            background: #f8fafc;
            min-width: 0;
        }

        .hb-chart-summary-item span {
            display: block;
            color: var(--hb-muted);
            font-size: 11px;
            margin-bottom: 6px;
            line-height: 1.4;
        }

        .hb-chart-summary-item strong {
            display: block;
            font-size: 20px;
            color: var(--hb-primary-dark);
            overflow-wrap: anywhere;
        }

        /* ---------- Búsqueda por respuestas ---------- */
        .hb-filters-extra {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px dashed var(--hb-border);
        }

        .hb-filter-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 13px;
        }

        .hb-filter-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .hb-field .hb-hint {
            display: block;
            margin-top: 5px;
            font-size: 11px;
            color: var(--hb-muted);
            line-height: 1.4;
        }

        .hb-field select:disabled {
            background: #f1f5f9;
            color: #94a3b8;
        }

        .hb-tag {
            display: inline-block;
            padding: 3px 9px;
            margin: 2px 4px 2px 0;
            border-radius: 999px;
            background: #e8f1f9;
            color: var(--hb-primary);
            font-size: 12px;
            font-weight: 700;
            line-height: 1.4;
        }

        .hb-tag.match {
            background: #d9f4e7;
            color: #116444;
        }

        .hb-chip-block + .hb-chip-block {
            margin-top: 18px;
        }

        .hb-chip-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .hb-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 12px;
            border: 1px solid var(--hb-border);
            border-radius: 999px;
            background: #ffffff;
            color: var(--hb-primary-dark) !important;
            text-decoration: none !important;
            font-size: 13px;
            font-weight: 700;
        }

        .hb-chip:hover {
            border-color: var(--hb-secondary);
            background: #f3f8fc;
        }

        .hb-chip span {
            padding: 1px 7px;
            border-radius: 999px;
            background: #e8eef5;
            font-size: 11px;
        }

        .hb-chip.active {
            background: var(--hb-primary);
            border-color: var(--hb-primary);
            color: #ffffff !important;
        }

        .hb-chip.active span {
            background: rgba(255, 255, 255, .22);
        }

        .hb-results-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .hb-results-head h2 {
            margin-bottom: 6px;
        }

        .hb-active-filters {
            margin: 0 0 14px;
            padding: 10px 12px;
            background: #f3f8fc;
            border: 1px solid var(--hb-border);
            border-radius: 9px;
            font-size: 13px;
            line-height: 1.7;
        }

        .hb-sensitive-note {
            margin: 0 0 14px;
            padding: 10px 12px;
            border-left: 4px solid #d6a13b;
            border-radius: 6px;
            background: #fff9e9;
            color: #654b16;
            font-size: 12px;
            line-height: 1.6;
        }

        @media (max-width: 1100px) {
            .hb-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .hb-filter-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1100px) {
            .hb-summary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .hb-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 800px) {
            .hb-charts-grid { grid-template-columns: 1fr; }
            .hb-chart-container { height: 280px; }
        }

        @media (max-width: 700px) {
            .hb-summary-grid,
            .hb-profile-grid,
            .hb-form-grid {
                grid-template-columns: 1fr;
            }

            .hb-response-row {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            .hb-card { padding: 15px; }
            .hb-container { width: calc(100% - 20px); }
            .hb-chart-summary { grid-template-columns: 1fr; }
        }

        @media (max-width: 420px) {
            .hb-summary-grid { grid-template-columns: 1fr; }
            .hb-chart-card { padding: 13px; }
        }
    </style>
</head>

<body>

<?php
$navbarPath = defined('ROOT_PATH')
    ? ROOT_PATH . '/views/layout/navbar.php'
    : null;

if ($navbarPath !== null && is_file($navbarPath)) {
    require $navbarPath;
}
?>

<main class="hb-page">
    <div class="hb-container">

        <header class="hb-header">
            <div>
                <h1><?= hbEsc($titulo) ?></h1>
                <p>
                    Consulta de habilidades blandas de los aprendices,
                    según los permisos asignados a su usuario.
                </p>
            </div>

            <a class="hb-button secondary" href="<?= hbEsc($urlConsulta) ?>">
                Limpiar filtros
            </a>
        </header>

        <!-- RESUMEN GENERAL -->
        <section class="hb-summary-grid" aria-label="Resumen de cuestionarios">

            <div class="hb-stat">
                <div class="hb-stat-label">Aprendices activos</div>
                <div class="hb-stat-value">
                    <?= number_format($totalAprendices, 0, ',', '.') ?>
                </div>
                <div class="hb-muted" style="font-size:12px;margin-top:6px">
                    Según los filtros aplicados
                </div>
            </div>

            <div class="hb-stat completed">
                <div class="hb-stat-label">Cuestionarios completados</div>
                <div class="hb-stat-value">
                    <?= number_format($totalCompletados, 0, ',', '.') ?>
                </div>
                <div class="hb-muted" style="font-size:12px;margin-top:6px">
                    Registro finalizado
                </div>
            </div>

            <div class="hb-stat draft">
                <div class="hb-stat-label">En proceso</div>
                <div class="hb-stat-value">
                    <?= number_format($totalBorradores, 0, ',', '.') ?>
                </div>
                <div class="hb-muted" style="font-size:12px;margin-top:6px">
                    Cuestionarios en borrador
                </div>
            </div>

            <div class="hb-stat pending">
                <div class="hb-stat-label">Pendientes</div>
                <div class="hb-stat-value">
                    <?= number_format($totalPendientes, 0, ',', '.') ?>
                </div>
                <div class="hb-muted" style="font-size:12px;margin-top:6px">
                    Sin completar
                </div>
            </div>

            <div class="hb-stat">
                <div class="hb-stat-label">Avance de diligenciamiento</div>
                <div class="hb-stat-value">
                    <?= number_format($porcentajeAvance, 1, ',', '.') ?>%
                </div>
                <div class="hb-progress"
                     role="progressbar"
                     aria-valuenow="<?= hbEsc((string) round($porcentajeAvance, 1)) ?>"
                     aria-valuemin="0"
                     aria-valuemax="100">
                    <span></span>
                </div>
            </div>

        </section>

        <!-- BÚSQUEDA Y FILTROS -->
        <section class="hb-card">
            <h2>Buscar y filtrar aprendices</h2>

            <form method="GET" action="<?= hbEsc($urlConsulta) ?>">
                <div class="hb-form-grid">

                    <div class="hb-field">
                        <label for="busqueda">
                            Documento, nombre, apellido o ficha
                        </label>
                        <input
                            type="text"
                            name="busqueda"
                            id="busqueda"
                            value="<?= hbEsc($busqueda) ?>"
                            placeholder="Ingrese el dato que desea consultar"
                        >
                    </div>

                    <div class="hb-field">
                        <label for="id_ficha">Ficha</label>
                        <select name="id_ficha" id="id_ficha">
                            <option value="0">Todas las fichas</option>

                            <?php foreach ($fichas as $ficha): ?>
                                <?php
                                $fichaId = (int) (
                                    $ficha['id']
                                    ?? $ficha['id_ficha']
                                    ?? 0
                                );

                                $fichaNumero = $ficha['numero_ficha']
                                    ?? $ficha['ficha']
                                    ?? $fichaId;

                                $programa = $ficha['programa']
                                    ?? $ficha['nombre_programa']
                                    ?? '';
                                ?>

                                <option
                                    value="<?= $fichaId ?>"
                                    <?= $idFicha === $fichaId ? 'selected' : '' ?>
                                >
                                    <?= hbEsc(
                                        $fichaNumero .
                                        ($programa !== '' ? ' - ' . $programa : '')
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="hb-field">
                        <label for="estado_cuestionario">
                            Estado del cuestionario
                        </label>
                        <select name="estado_cuestionario" id="estado_cuestionario">
                            <option value="todos"
                                <?= $estadoFiltro === 'todos' ? 'selected' : '' ?>>
                                Todos los estados
                            </option>
                            <option value="pendiente"
                                <?= $estadoFiltro === 'pendiente' ? 'selected' : '' ?>>
                                Pendiente
                            </option>
                            <option value="en_proceso"
                                <?= $estadoFiltro === 'en_proceso' ? 'selected' : '' ?>>
                                En proceso
                            </option>
                            <option value="completado"
                                <?= $estadoFiltro === 'completado' ? 'selected' : '' ?>>
                                Completado
                            </option>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="hb-button" style="width:100%">
                            Consultar
                        </button>
                    </div>

                </div>

                <div class="hb-filters-extra">
                    <h3>Buscar por respuestas de Habilidades Blandas</h3>

                    <p
                        class="hb-muted"
                        style="font-size:13px;line-height:1.6;margin:-4px 0 14px"
                    >
                        Combine los filtros para saber quién quiere participar en un taller, qué habilidades desea fortalecer
                        o qué respondió en cada pregunta. Los filtros se
                        suman entre sí.
                    </p>

                    <div class="hb-filter-grid">
                        <?php foreach ($filtrosRapidosConfig as $codigoFiltro => $etiquetaFiltro): ?>
                            <?php
                            $datosFiltro = $catalogoHB[$codigoFiltro] ?? null;

                            if ($datosFiltro === null || empty($datosFiltro['opciones'])) {
                                continue;
                            }

                            $valorActual = (string) ($filtrosRapidos[$codigoFiltro] ?? '');
                            $idCampo = 'hb_' . strtolower(str_replace('-', '_', $codigoFiltro));
                            ?>

                            <div class="hb-field">
                                <label for="<?= hbEsc($idCampo) ?>">
                                    <?= hbEsc($etiquetaFiltro) ?>
                                    <span class="hb-muted">(<?= hbEsc($codigoFiltro) ?>)</span>
                                </label>

                                <select
                                    name="hb[<?= hbEsc($codigoFiltro) ?>]"
                                    id="<?= hbEsc($idCampo) ?>"
                                >
                                    <option value="">Todas</option>

                                    <?php foreach ($ordenarOpciones($datosFiltro) as $opcionFiltro): ?>
                                        <option
                                            value="<?= hbEsc($opcionFiltro['nombre']) ?>"
                                            <?= ($valorActual !== '' && $mismoTexto($valorActual, (string) $opcionFiltro['nombre'])) ? 'selected' : '' ?>
                                        >
                                            <?= hbEsc($opcionFiltro['nombre']) ?>
                                            (<?= (int) $opcionFiltro['cantidad'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="hb-filter-grid" style="margin-top:14px">

                        <div class="hb-field">
                            <label for="hb_pregunta">Cualquier pregunta HB</label>
                            <select name="hb_pregunta" id="hb_pregunta">
                                <option value="">Seleccione una pregunta</option>

                                <?php foreach ($catalogoHB as $codigoPregunta => $datosPregunta): ?>
                                    <option
                                        value="<?= hbEsc($codigoPregunta) ?>"
                                        <?= $hbPregunta === $codigoPregunta ? 'selected' : '' ?>
                                    >
                                        <?= hbEsc(
                                            $codigoPregunta . ' - '
                                            . $nombrePregunta((string) $codigoPregunta)
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="hb-field">
                            <label for="hb_respuesta">Respuesta de esa pregunta</label>
                            <select name="hb_respuesta" id="hb_respuesta" disabled>
                                <option value="">Seleccione primero una pregunta</option>
                            </select>
                            <span class="hb-hint">
                                Si deja «Cualquier respuesta», se listan quienes
                                contestaron la pregunta.
                            </span>
                        </div>

                        <div class="hb-field">
                            <label for="texto_respuesta">
                                Palabra dentro de las respuestas
                            </label>
                            <input
                                type="text"
                                name="texto_respuesta"
                                id="texto_respuesta"
                                value="<?= hbEsc($filtroTextoRespuesta) ?>"
                                placeholder="Ej: liderazgo, comunicación, estrés, equipo..."
                            >
                            <span class="hb-hint">
                                Ignora tildes y mayúsculas. Si eligió una pregunta,
                                solo busca en esa; si no, en todas las respuestas HB.
                            </span>
                        </div>
                    </div>

                    <div class="hb-filter-actions">
                        <button type="submit" class="hb-button">
                            Aplicar filtros
                        </button>

                        <a class="hb-button secondary" href="<?= hbEsc($urlConsulta) ?>">
                            Limpiar todo
                        </a>
                    </div>
                </div>
            </form>
        </section>

        <?php
        $bloquesChips = [
            'HB-09' => 'Talleres o actividades en los que les gustaría participar',
            'HB-07' => 'Habilidades que desean fortalecer',
        ];

        $hayChips = false;

        foreach ($bloquesChips as $codigoChip => $tituloChip) {
            if (!empty($catalogoHB[$codigoChip]['opciones'])) {
                $hayChips = true;
                break;
            }
        }
        ?>

        <?php if ($hayChips): ?>
            <section class="hb-card">
                <h2>Intereses más frecuentes</h2>

                <p
                    class="hb-muted"
                    style="font-size:13px;line-height:1.6;margin-top:-8px"
                >
                    Haga clic en una opción para ver qué aprendices la
                    eligieron. Vuelva a hacer clic en la opción activa
                    para quitar el filtro.
                </p>

                <?php foreach ($bloquesChips as $codigoChip => $tituloChip): ?>
                    <?php
                    $opcionesChip = $catalogoHB[$codigoChip]['opciones'] ?? [];

                    if ($opcionesChip === []) {
                        continue;
                    }

                    $valorChipActivo = (string) ($filtrosRapidos[$codigoChip] ?? '');
                    $totalOpcionesChip = count($opcionesChip);
                    ?>

                    <div class="hb-chip-block">
                        <h3>
                            <?= hbEsc($tituloChip) ?>
                            <span class="hb-muted" style="font-weight:400;font-size:12px">
                                (<?= hbEsc($codigoChip) ?> ·
                                <?= (int) $catalogoHB[$codigoChip]['respondieron'] ?>
                                respuestas)
                            </span>
                        </h3>

                        <div class="hb-chip-list">
                            <?php foreach (array_slice($opcionesChip, 0, 15, true) as $opcionChip): ?>
                                <?php
                                $activo = $valorChipActivo !== ''
                                    && $mismoTexto($valorChipActivo, (string) $opcionChip['nombre']);

                                $filtrosEnlace = $filtrosRapidos;

                                if ($activo) {
                                    unset($filtrosEnlace[$codigoChip]);
                                } else {
                                    $filtrosEnlace[$codigoChip] = (string) $opcionChip['nombre'];
                                }
                                ?>

                                <a
                                    class="hb-chip <?= $activo ? 'active' : '' ?>"
                                    href="<?= hbEsc($construirUrl(['hb' => $filtrosEnlace]) . '#resultados-respuestas') ?>"
                                >
                                    <?= hbEsc($opcionChip['nombre']) ?>
                                    <span><?= (int) $opcionChip['cantidad'] ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($totalOpcionesChip > 15): ?>
                            <p class="hb-muted" style="font-size:12px;margin:8px 0 0">
                                Se muestran las 15 más frecuentes de
                                <?= (int) $totalOpcionesChip ?>. Use los filtros
                                de arriba para ver el resto.
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <section class="hb-card" id="resultados-respuestas">
            <div class="hb-results-head">
                <div>
                    <h2>Aprendices por respuestas de Habilidades Blandas</h2>

                    <p class="hb-muted" style="font-size:13px;line-height:1.6;margin:0">
                        <strong><?= number_format(count($aprendicesFiltrados), 0, ',', '.') ?></strong>
                        <?= count($aprendicesFiltrados) === 1 ? 'aprendiz' : 'aprendices' ?>
                        <?= $hayFiltrosRespuesta ? 'cumplen los filtros' : 'con respuestas de Habilidades Blandas' ?>
                        (de <?= number_format($totalConRespuestasHB, 0, ',', '.') ?>
                        con respuestas HB registradas).
                    </p>
                </div>

                <?php if ($aprendicesFiltrados !== []): ?>
                    <a
                        class="hb-button secondary"
                        href="<?= hbEsc($construirUrl(['exportar' => 'csv'])) ?>"
                    >
                        Exportar a CSV
                    </a>
                <?php endif; ?>
            </div>

            <div class="hb-sensitive-note">
                Estos resultados pueden incluir información sobre estrés, redes de apoyo y bienestar emocional. Úselos únicamente para orientación y apoyo institucional autorizado. El CSV no incluye las preguntas más sensibles (HB-01, HB-02 y HB-03).
            </div>

            <?php if ($filtrosActivosTexto !== []): ?>
                <div class="hb-active-filters">
                    <strong>Filtros activos:</strong>
                    <?php foreach ($filtrosActivosTexto as $textoActivo): ?>
                        <span class="hb-tag"><?= hbEsc($textoActivo) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($aprendicesFiltrados === []): ?>

                <div class="hb-empty">
                    <strong>No hay aprendices con esos criterios.</strong>
                    <br>
                    Pruebe con menos filtros, otra ficha o una palabra más corta.
                </div>

            <?php else: ?>

                <div class="hb-table-wrap">
                    <table class="hb-table">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Aprendiz</th>
                                <th>Ficha</th>
                                <th>Estado</th>
                                <th>Talleres de interés</th>
                                <th>Habilidades por fortalecer</th>
                                <?php if ($hayFiltrosRespuesta): ?>
                                    <th>Respuestas que coinciden</th>
                                <?php endif; ?>
                                <th>Consulta</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php foreach (array_slice($aprendicesFiltrados, 0, $maxFilasVista) as $filaHB): ?>
                            <?php
                            $idAprendizFila = (int) ($filaHB['id_aprendiz'] ?? 0);
                            $nombreFila = $obtenerNombreAprendiz($filaHB);
                            $estadoFila = $obtenerEstado($filaHB);
                            $claseFila = $obtenerClaseEstado($estadoFila);
                            ?>

                            <tr>
                                <td><?= hbEsc($filaHB['documento'] ?? 'No registrado') ?></td>

                                <td>
                                    <strong>
                                        <?= hbEsc($nombreFila !== '' ? $nombreFila : 'Nombre no disponible') ?>
                                    </strong>
                                </td>

                                <td><?= hbEsc($filaHB['numero_ficha'] ?? '—') ?></td>

                                <td>
                                    <span class="hb-badge <?= hbEsc($claseFila) ?>">
                                        <?= hbEsc($estadoFila) ?>
                                    </span>
                                </td>

                                <td><?= $etiquetasHB($filaHB, 'HB-09') ?></td>
                                <td><?= $etiquetasHB($filaHB, 'HB-07') ?></td>

                                <?php if ($hayFiltrosRespuesta): ?>
                                    <td>
                                        <?php foreach (($filaHB['coincidencias'] ?? []) as $coincidencia): ?>
                                            <span class="hb-tag match"><?= hbEsc($coincidencia) ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                <?php endif; ?>

                                <td>
                                    <a
                                        class="hb-button small"
                                        href="<?= hbEsc($construirUrl(['id_aprendiz' => $idAprendizFila]) . '#detalle-aprendiz') ?>"
                                    >
                                        Ver detalle
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (count($aprendicesFiltrados) > $maxFilasVista): ?>
                    <p class="hb-muted" style="font-size:12px;margin-top:12px">
                        Se muestran los primeros
                        <?= (int) $maxFilasVista ?> resultados. Use «Exportar a CSV»
                        para obtener la lista completa.
                    </p>
                <?php endif; ?>

            <?php endif; ?>
        </section>

        <section class="hb-card">
            <h2>Listado general de aprendices (estado del cuestionario)</h2>

            <p class="hb-muted" style="font-size:13px;margin-top:-8px;margin-bottom:15px">
                Se muestran aprendices con matrícula activa que coinciden
                con los filtros seleccionados.
            </p>

            <?php if ($aprendices === []): ?>

                <div class="hb-empty">
                    <strong>No se encontraron aprendices.</strong><br>
                    Verifique los datos ingresados o seleccione otros filtros.
                </div>

            <?php else: ?>

                <div class="hb-table-wrap">
                    <table class="hb-table">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Aprendiz</th>
                                <th>Ficha</th>
                                <th>Programa</th>
                                <th>Estado</th>
                                <th>Última actualización</th>
                                <th>Consulta</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($aprendices as $fila): ?>
                            <?php
                            $aprendizId = (int) (
                                $fila['id_aprendiz']
                                ?? $fila['id']
                                ?? 0
                            );

                            $nombre = $obtenerNombreAprendiz($fila);
                            $estadoTexto = $obtenerEstado($fila);
                            $estadoClase = $obtenerClaseEstado($estadoTexto);

                            $urlDetalle = $construirUrl([
                                'id_aprendiz' => $aprendizId,
                            ]) . '#detalle-aprendiz';
                            ?>

                            <tr>
                                <td>
                                    <?= hbEsc(
                                        $fila['documento']
                                        ?? $fila['numero_documento']
                                        ?? 'No registrado'
                                    ) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= hbEsc(
                                            $nombre !== ''
                                                ? $nombre
                                                : 'Nombre no disponible'
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= hbEsc(
                                        $fila['numero_ficha']
                                        ?? $fila['ficha']
                                        ?? '—'
                                    ) ?>
                                </td>

                                <td>
                                    <?= hbEsc(
                                        $fila['programa']
                                        ?? $fila['nombre_programa']
                                        ?? '—'
                                    ) ?>
                                </td>

                                <td>
                                    <span class="hb-badge <?= hbEsc($estadoClase) ?>">
                                        <?= hbEsc($estadoTexto) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= hbEsc(hbFecha(
                                        $fila['fecha_actualizacion']
                                        ?? $fila['fecha_completado']
                                        ?? null
                                    )) ?>
                                </td>

                                <td>
                                    <a class="hb-button small" href="<?= hbEsc($urlDetalle) ?>">
                                        Ver detalle
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (count($aprendices) >= 200): ?>
                    <p class="hb-muted" style="font-size:12px;margin-top:12px">
                        Se muestran hasta 200 registros. Use los filtros
                        para precisar la búsqueda.
                    </p>
                <?php endif; ?>

            <?php endif; ?>
        </section>

        <!-- GRÁFICAS DE HABILIDADES BLANDAS -->
        <section class="hb-card" id="graficas-habilidades-blandas">
            <h2>Panel gráfico de Habilidades Blandas</h2>

            <p class="hb-muted" style="font-size:13px;line-height:1.6">
                Visualice el estado general de los cuestionarios y explore
                la distribución de respuestas para cada pregunta. Las gráficas
                utilizan los datos estadísticos recibidos por esta vista.
            </p>

            <div class="hb-charts-grid">

                <article class="hb-chart-card">
                    <h3>Estado de los cuestionarios</h3>

                    <p class="hb-chart-description">
                        Distribución de aprendices según el estado del
                        diligenciamiento: completados, en proceso y pendientes.
                    </p>

                    <div class="hb-chart-container hb-chart-doughnut" id="hbContenedorEstados">
                        <canvas
                            id="hbChartEstados"
                            role="img"
                            aria-label="Gráfica circular del estado de los cuestionarios"
                        ></canvas>
                    </div>

                    <div id="hbChartEstadosVacio" class="hb-chart-empty" hidden>
                        No hay datos suficientes para mostrar esta gráfica.
                    </div>

                    <div class="hb-chart-summary">
                        <div class="hb-chart-summary-item">
                            <span>Completados</span>
                            <strong><?= number_format($totalCompletados, 0, ',', '.') ?></strong>
                        </div>

                        <div class="hb-chart-summary-item">
                            <span>En proceso</span>
                            <strong><?= number_format($totalBorradores, 0, ',', '.') ?></strong>
                        </div>

                        <div class="hb-chart-summary-item">
                            <span>Pendientes</span>
                            <strong><?= number_format($totalPendientes, 0, ',', '.') ?></strong>
                        </div>
                    </div>
                </article>

                <article class="hb-chart-card">
                    <h3>Respuestas por pregunta</h3>

                    <p class="hb-chart-description">
                        Seleccione una pregunta para comparar las cantidades
                        registradas en sus diferentes opciones de respuesta.
                    </p>

                    <div class="hb-chart-filter">
                        <label for="hbPreguntaGrafica">Pregunta de Habilidades Blandas</label>

                        <select id="hbPreguntaGrafica">
                            <option value="">Seleccione una pregunta</option>

                            <?php foreach ($hbGraficaPreguntas as $indice => $preguntaGrafica): ?>
                                <option value="<?= (int) $indice ?>">
                                    <?= hbEsc(
                                        $preguntaGrafica['codigo']
                                        . ' - '
                                        . $preguntaGrafica['pregunta']
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="hb-chart-container" id="hbContenedorRespuestas">
                        <canvas
                            id="hbChartRespuestas"
                            role="img"
                            aria-label="Gráfica de barras de respuestas por pregunta"
                        ></canvas>
                    </div>

                    <div id="hbChartRespuestasVacio" class="hb-chart-empty" hidden>
                        Seleccione una pregunta que tenga respuestas registradas
                        para visualizar su distribución.
                    </div>
                </article>

            </div>
        </section>

        <!-- ESTADÍSTICAS ORIGINALES -->
        <section class="hb-card">
            <h2>Estadísticas de Habilidades Blandas</h2>

            <p class="hb-muted" style="font-size:13px;line-height:1.6">
                Se presentan las estadísticas correspondientes a las preguntas
                identificadas con el componente HB. En preguntas de selección
                múltiple, un aprendiz puede aparecer en más de una respuesta.
            </p>

            <?php if ($estadisticasHB === []): ?>

                <div class="hb-empty">
                    Todavía no hay respuestas disponibles para generar
                    estadísticas de Habilidades Blandas con los filtros actuales.
                </div>

            <?php else: ?>

                <div class="hb-section">
                    <div class="hb-section-title">Habilidades blandas</div>

                    <?php foreach ($estadisticasHB as $codigo => $estadistica): ?>
                        <?php
                        $totalRespuestas = (int) (
                            $estadistica['total_respuestas']
                            ?? $estadistica['total']
                            ?? 0
                        );

                        $respuestas = $estadistica['respuestas'] ?? [];

                        if (is_string($respuestas)) {
                            $decodificadas = json_decode($respuestas, true);
                            $respuestas = is_array($decodificadas) ? $decodificadas : [];
                        }

                        if (!is_array($respuestas)) {
                            $respuestas = [];
                        }

                        $textoPregunta = $textoPreguntaEstadistica($estadistica, (string) $codigo);

                        $denominador = $totalRespuestas > 0
                            ? $totalRespuestas
                            : array_sum(array_map(
                                static fn($dato) => is_array($dato)
                                    ? (int) ($dato['cantidad'] ?? $dato['total'] ?? 0)
                                    : (int) $dato,
                                $respuestas
                            ));
                        ?>

                        <div class="hb-question">
                            <div class="hb-question-title">
                                <?= hbEsc($textoPregunta) ?>
                                <span class="hb-muted" style="font-weight:400;font-size:12px">
                                    (<?= hbEsc((string) $codigo) ?>)
                                </span>
                            </div>

                            <?php if ($respuestas === []): ?>

                                <div class="hb-muted" style="font-size:13px">
                                    No hay respuestas registradas para esta pregunta.
                                </div>

                            <?php else: ?>

                                <?php foreach ($respuestas as $valorRespuesta => $datoRespuesta): ?>
                                    <?php
                                    if (is_array($datoRespuesta)) {
                                        $cantidad = (int) (
                                            $datoRespuesta['cantidad']
                                            ?? $datoRespuesta['total']
                                            ?? 0
                                        );

                                        $textoRespuesta = $datoRespuesta['valor']
                                            ?? $datoRespuesta['respuesta']
                                            ?? (string) $valorRespuesta;
                                    } else {
                                        $cantidad = (int) $datoRespuesta;
                                        $textoRespuesta = (string) $valorRespuesta;
                                    }

                                    $porcentaje = $denominador > 0
                                        ? min(100, ($cantidad / $denominador) * 100)
                                        : 0;
                                    ?>

                                    <div class="hb-response-row">
                                        <div><?= hbEsc(hbValorLegible($textoRespuesta)) ?></div>

                                        <div>
                                            <strong><?= number_format($cantidad, 0, ',', '.') ?></strong>
                                            <span class="hb-muted">
                                                (<?= number_format($porcentaje, 1, ',', '.') ?>%)
                                            </span>
                                        </div>

                                        <div class="hb-bar">
                                            <span style="width:<?= number_format($porcentaje, 2, '.', '') ?>%"></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                </div>

            <?php endif; ?>
        </section>

        <!-- DETALLE INDIVIDUAL -->
        <?php if ($idAprendiz > 0): ?>

            <section class="hb-card" id="detalle-aprendiz">
                <h2>Detalle individual del aprendiz</h2>

                <?php if ($aprendizSeleccionado === null): ?>

                    <div class="hb-empty">
                        No fue posible encontrar la información del aprendiz
                        solicitado. Verifique que tenga una matrícula activa.
                    </div>

                <?php else: ?>

                    <div class="hb-note">
                        La información individual puede contener datos personales
                        y de bienestar. Consulte estos datos únicamente para
                        fines institucionales autorizados.
                    </div>

                    <div class="hb-profile-grid">

                        <div class="hb-profile-item">
                            <div class="label">Nombre completo</div>
                            <div class="value"><?= hbEsc($nombreIndividual) ?></div>
                        </div>

                        <div class="hb-profile-item">
                            <div class="label">Documento</div>
                            <div class="value"><?= hbEsc($documentoIndividual) ?></div>
                        </div>

                        <div class="hb-profile-item">
                            <div class="label">Ficha</div>
                            <div class="value">
                                <?= hbEsc(
                                    ($numeroFichaIndividual !== null && $numeroFichaIndividual !== '')
                                        ? $numeroFichaIndividual
                                        : 'No registrada'
                                ) ?>
                            </div>
                        </div>

                        <div class="hb-profile-item">
                            <div class="label">Programa de formación</div>
                            <div class="value">
                                <?= hbEsc(
                                    ($programaIndividual !== null && $programaIndividual !== '')
                                        ? $programaIndividual
                                        : 'No registrado'
                                ) ?>
                            </div>
                        </div>

                        <div class="hb-profile-item">
                            <div class="label">Correo electrónico</div>
                            <div class="value">
                                <?= hbEsc(
                                    ($correoIndividual !== null && $correoIndividual !== '')
                                        ? $correoIndividual
                                        : 'No registrado'
                                ) ?>
                            </div>
                        </div>

                        <div class="hb-profile-item">
                            <div class="label">Estado del aprendiz</div>
                            <div class="value"><?= hbEsc($estadoAprendiz) ?></div>
                        </div>

                    </div>

                    <div class="hb-detail-heading">
                        <h3 style="margin:0">
                            Cuestionarios y respuestas de Habilidades Blandas
                        </h3>
                    </div>

                    <?php if ($cuestionarios === []): ?>

                        <div class="hb-empty">
                            Este aprendiz no tiene cuestionarios registrados
                            con información disponible.
                        </div>

                    <?php else: ?>

                        <?php foreach ($cuestionarios as $indice => $cuestionario): ?>
                            <?php
                            if (!is_array($cuestionario)) {
                                continue;
                            }

                            $estadoIndividual = strtolower(trim((string) (
                                $cuestionario['estado_cuestionario']
                                ?? $cuestionario['estado']
                                ?? ''
                            )));

                            $estadoIndividualTexto = $estadoIndividual === 'completado'
                                ? 'Completado'
                                : (
                                    in_array($estadoIndividual, ['borrador', 'en_proceso'], true)
                                        ? 'En proceso'
                                        : 'Pendiente'
                                );

                            $claseIndividual = $obtenerClaseEstado($estadoIndividualTexto);

                            $fuenteRespuestas = $cuestionario['respuestas']
                                ?? $cuestionario['respuestas_json']
                                ?? $cuestionario['datos_respuestas']
                                ?? $cuestionario['respuesta_json']
                                ?? [];

                            $respuestasIndividuales = $normalizarRespuestasHB($fuenteRespuestas);
                            ?>

                            <div class="hb-section">
                                <div class="hb-section-title">
                                    <span>Cuestionario <?= (int) ($indice + 1) ?></span>

                                    <span class="hb-badge <?= hbEsc($claseIndividual) ?>">
                                        <?= hbEsc($estadoIndividualTexto) ?>
                                    </span>
                                </div>

                                <div class="hb-section-body">
                                    <p class="hb-muted" style="font-size:12px;line-height:1.8">
                                        <strong>Inicio:</strong>
                                        <?= hbEsc(hbFecha($cuestionario['fecha_inicio'] ?? null)) ?>

                                        &nbsp; | &nbsp;

                                        <strong>Última actualización:</strong>
                                        <?= hbEsc(hbFecha($cuestionario['fecha_actualizacion'] ?? null)) ?>

                                        &nbsp; | &nbsp;

                                        <strong>Finalización:</strong>
                                        <?= hbEsc(hbFecha($cuestionario['fecha_completado'] ?? null)) ?>
                                    </p>

                                    <?php if ($respuestasIndividuales === []): ?>

                                        <div class="hb-empty">
                                            No hay respuestas identificadas para
                                            Habilidades Blandas en este cuestionario.
                                        </div>

                                    <?php else: ?>

                                        <?php foreach ($respuestasIndividuales as $codigo => $valor): ?>
                                            <div class="hb-answer">
                                                <div class="question">
                                                    <?= hbEsc($nombrePregunta($codigo)) ?>
                                                    <span class="hb-muted" style="font-size:11px;font-weight:400">
                                                        (<?= hbEsc($codigo) ?>)
                                                    </span>
                                                </div>

                                                <div class="value">
    <?php
    $valorMostrar = hbValorLegible($valor);

    if (
        strtoupper(str_replace('_', '-', trim((string) $codigo))) === 'HB-04'
        && is_scalar($valor)
        && preg_match('/^[1-5]$/', trim((string) $valor))
    ) {
        $valorMostrar = trim((string) $valor) . ' de 5 (escala de 1 a 5)';
    }
    ?>
    <?= hbEsc($valorMostrar) ?>
</div>
                                            </div>
                                        <?php endforeach; ?>

                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    <?php endif; ?>

                <?php endif; ?>

                <div style="margin-top:18px">
                    <a class="hb-button secondary"
                       href="<?= hbEsc($construirUrl() . '#resultados-respuestas') ?>">
                        Volver al listado
                    </a>
                </div>
            </section>

        <?php endif; ?>

    </div>
</main>

<footer class="hb-footer">
    SITA · Sistema de Trazabilidad de Aprendices
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const estados = <?= $hbGraficaEstadosJson ?: '{}' ?>;
    const preguntas = <?= $hbGraficaPreguntasJson ?: '[]' ?>;

    const canvasEstados = document.getElementById('hbChartEstados');
    const contenedorEstados = document.getElementById('hbContenedorEstados');
    const vacioEstados = document.getElementById('hbChartEstadosVacio');

    const selectorPregunta = document.getElementById('hbPreguntaGrafica');
    const canvasRespuestas = document.getElementById('hbChartRespuestas');
    const contenedorRespuestas = document.getElementById('hbContenedorRespuestas');
    const vacioRespuestas = document.getElementById('hbChartRespuestasVacio');

    let graficaEstados = null;
    let graficaRespuestas = null;

    function mostrarVacio(canvas, contenedor, mensaje, mostrar) {
        if (contenedor) {
            contenedor.hidden = mostrar;
        }

        if (mensaje) {
            mensaje.hidden = !mostrar;
        }

        if (canvas) {
            canvas.setAttribute('aria-hidden', mostrar ? 'true' : 'false');
        }
    }

    function crearGraficaEstados() {
        if (!canvasEstados || typeof Chart === 'undefined') {
            mostrarVacio(canvasEstados, contenedorEstados, vacioEstados, true);
            return;
        }

        const etiquetas = Object.keys(estados);
        const cantidades = etiquetas.map(function (etiqueta) {
            return Math.max(0, Number(estados[etiqueta]) || 0);
        });

        const total = cantidades.reduce(function (acumulado, cantidad) {
            return acumulado + cantidad;
        }, 0);

        if (total <= 0) {
            mostrarVacio(canvasEstados, contenedorEstados, vacioEstados, true);
            return;
        }

        mostrarVacio(canvasEstados, contenedorEstados, vacioEstados, false);

        graficaEstados = new Chart(canvasEstados, {
            type: 'doughnut',
            data: {
                labels: etiquetas,
                datasets: [{
                    data: cantidades,
                    backgroundColor: ['#16835d', '#bd7614', '#b83c48'],
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 18,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (contexto) {
                                const cantidad = Number(contexto.raw) || 0;
                                const porcentaje = total > 0 ? (cantidad / total) * 100 : 0;

                                return contexto.label + ': '
                                    + cantidad.toLocaleString('es-CO')
                                    + ' ('
                                    + porcentaje.toLocaleString('es-CO', {
                                        minimumFractionDigits: 1,
                                        maximumFractionDigits: 1
                                    })
                                    + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    function renderizarRespuestas(indice) {
        if (!canvasRespuestas || typeof Chart === 'undefined') {
            mostrarVacio(canvasRespuestas, contenedorRespuestas, vacioRespuestas, true);
            return;
        }

        if (graficaRespuestas) {
            graficaRespuestas.destroy();
            graficaRespuestas = null;
        }

        const pregunta = preguntas[Number(indice)];

        if (
            !pregunta
            || !Array.isArray(pregunta.etiquetas)
            || !Array.isArray(pregunta.cantidades)
            || pregunta.etiquetas.length === 0
            || pregunta.cantidades.length === 0
        ) {
            mostrarVacio(canvasRespuestas, contenedorRespuestas, vacioRespuestas, true);
            return;
        }

        const etiquetas = pregunta.etiquetas;
        const cantidades = pregunta.cantidades.map(function (cantidad) {
            return Math.max(0, Number(cantidad) || 0);
        });

        const total = cantidades.reduce(function (acumulado, cantidad) {
            return acumulado + cantidad;
        }, 0);

        if (total <= 0) {
            mostrarVacio(canvasRespuestas, contenedorRespuestas, vacioRespuestas, true);
            return;
        }

        mostrarVacio(canvasRespuestas, contenedorRespuestas, vacioRespuestas, false);

        if (contenedorRespuestas) {
            contenedorRespuestas.style.height = Math.max(
                280,
                Math.min(650, etiquetas.length * 43 + 55)
            ) + 'px';
        }

        graficaRespuestas = new Chart(canvasRespuestas, {
            type: 'bar',
            data: {
                labels: etiquetas,
                datasets: [{
                    label: 'Cantidad de respuestas',
                    data: cantidades,
                    backgroundColor: '#2676a8',
                    borderColor: '#174a78',
                    borderWidth: 1,
                    borderRadius: 6,
                    maxBarThickness: 34
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'nearest'
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            stepSize: 1
                        },
                        title: {
                            display: true,
                            text: 'Cantidad de respuestas'
                        },
                        grid: { color: '#edf1f5' }
                    },
                    y: {
                        ticks: {
                            autoSkip: false,
                            font: { size: 11 }
                        },
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: pregunta.codigo + ' - ' + pregunta.pregunta,
                        color: '#103653',
                        font: {
                            size: 13,
                            weight: 'bold'
                        },
                        padding: { bottom: 15 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (contexto) {
                                const cantidad = Number(contexto.raw) || 0;
                                const porcentaje = total > 0 ? (cantidad / total) * 100 : 0;

                                return 'Respuestas: '
                                    + cantidad.toLocaleString('es-CO')
                                    + ' ('
                                    + porcentaje.toLocaleString('es-CO', {
                                        minimumFractionDigits: 1,
                                        maximumFractionDigits: 1
                                    })
                                    + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    if (typeof Chart !== 'undefined') {
        crearGraficaEstados();

        if (selectorPregunta) {
            const primeraConDatos = preguntas.findIndex(function (pregunta) {
                if (!Array.isArray(pregunta.cantidades) || pregunta.cantidades.length === 0) {
                    return false;
                }

                return pregunta.cantidades.some(function (cantidad) {
                    return Number(cantidad) > 0;
                });
            });

            if (primeraConDatos >= 0) {
                selectorPregunta.value = String(primeraConDatos);
                renderizarRespuestas(primeraConDatos);
            } else {
                mostrarVacio(canvasRespuestas, contenedorRespuestas, vacioRespuestas, true);
            }

            selectorPregunta.addEventListener('change', function () {
                if (this.value === '') {
                    if (graficaRespuestas) {
                        graficaRespuestas.destroy();
                        graficaRespuestas = null;
                    }

                    mostrarVacio(canvasRespuestas, contenedorRespuestas, vacioRespuestas, true);
                    return;
                }

                renderizarRespuestas(this.value);
            });
        }
    } else {
        mostrarVacio(canvasEstados, contenedorEstados, vacioEstados, true);
        mostrarVacio(canvasRespuestas, contenedorRespuestas, vacioRespuestas, true);

        if (vacioEstados) {
            vacioEstados.textContent =
                'No fue posible cargar las gráficas. Verifique su conexión a internet.';
        }

        if (vacioRespuestas) {
            vacioRespuestas.textContent =
                'No fue posible cargar las gráficas. Verifique su conexión a internet.';
        }
    }
});
</script>

<script>
/* Lista dependiente: pregunta HB -> respuestas disponibles. */
(function () {
    'use strict';

    const catalogo = <?= $catalogoJsJson ?: '{}' ?>;
    const respuestaInicial = <?= $hbRespuestaJson ?: '""' ?>;

    const selectorPregunta = document.getElementById('hb_pregunta');
    const selectorRespuesta = document.getElementById('hb_respuesta');

    if (!selectorPregunta || !selectorRespuesta) {
        return;
    }

    function agregarOpcion(valor, texto, seleccionada) {
        const opcion = document.createElement('option');
        opcion.value = valor;
        opcion.textContent = texto;
        opcion.selected = !!seleccionada;
        selectorRespuesta.appendChild(opcion);
    }

    function llenarRespuestas(codigo, seleccionada) {
        selectorRespuesta.innerHTML = '';

        const datos = codigo ? catalogo[codigo] : null;

        if (!codigo || !datos) {
            agregarOpcion('', 'Seleccione primero una pregunta', true);
            selectorRespuesta.disabled = true;
            return;
        }

        if (datos.libre) {
            agregarOpcion(
                '',
                'Pregunta abierta: use el buscador de palabras',
                true
            );
            selectorRespuesta.disabled = true;
            return;
        }

        agregarOpcion('', 'Cualquier respuesta', seleccionada === '');

        const normalizada = (seleccionada || '').trim().toLowerCase();

        datos.opciones.forEach(function (nombre) {
            agregarOpcion(
                nombre,
                nombre,
                normalizada !== ''
                    && nombre.trim().toLowerCase() === normalizada
            );
        });

        selectorRespuesta.disabled = false;
    }

    llenarRespuestas(selectorPregunta.value, respuestaInicial);

    selectorPregunta.addEventListener('change', function () {
        llenarRespuestas(this.value, '');
    });
})();
</script>

</body>
</html>