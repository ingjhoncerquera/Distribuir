<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SITA - Consulta de Actividad Física
| Archivo: views/bienestar/consultas/actividad_fisica.php
|--------------------------------------------------------------------------
*/

if (!function_exists('afEsc')) {
    function afEsc(mixed $valor): string
    {
        if (is_array($valor) || is_object($valor)) {
            $valor = json_encode($valor, JSON_UNESCAPED_UNICODE);
        }

        return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('afFecha')) {
    function afFecha(mixed $fecha): string
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

if (!function_exists('afLegible')) {
    function afLegible(mixed $valor): string
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
                    $texto = afLegible($elemento);

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
                return afLegible($decodificado);
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

$tituloPagina = $tituloPagina ?? 'Consulta de Actividad Física';
$titulo = $titulo ?? $tituloPagina;

$busqueda = (string) ($busqueda ?? '');
$idFicha = (int) ($idFicha ?? 0);
$idAprendiz = (int) ($idAprendiz ?? 0);

/*
 * El filtro de estado lo aplica el controlador (parámetro GET "estado":
 * todos, pendiente, borrador, completado). La vista solo lo refleja.
 */
$estadoCuestionario = (string) ($estadoCuestionario ?? 'todos');

$fichas = is_array($fichas ?? null) ? $fichas : [];
$aprendices = is_array($aprendices ?? null) ? $aprendices : [];
$resumen = is_array($resumen ?? null) ? $resumen : [];
$estadisticas = is_array($estadisticas ?? null) ? $estadisticas : [];
$cuestionarios = is_array($cuestionarios ?? null) ? $cuestionarios : [];

$aprendizSeleccionado = is_array($aprendizSeleccionado ?? null)
    ? $aprendizSeleccionado
    : null;

$urlConsulta = url('/bienestar/consultas/actividad-fisica');

/*
|--------------------------------------------------------------------------
| Catálogo de preguntas de Actividad Física
|--------------------------------------------------------------------------
*/

$preguntasAF = [
    'AF-01' => '¿Actualmente practica algún deporte o actividad física de manera habitual?',
    'AF-02' => '¿Qué deportes o actividades físicas practica actualmente?',
    'AF-03' => '¿Qué deportes o actividades físicas le gustaría practicar?',
    'AF-04' => '¿Ha participado anteriormente en equipos, torneos o actividades deportivas organizadas?',
    'AF-05' => '¿Le gustaría participar en actividades deportivas organizadas por el SENA?',
    'AF-06' => '¿Qué dificultades le impiden o dificultan participar en actividades deportivas?',
    'AF-07' => 'Motivación para participar en actividades deportivas o recreativas (escala de 0 a 10)',
    'AF-08' => '¿Existe alguna adaptación o apoyo que facilitaría su participación en actividades deportivas?',
    'AF-09' => '¿En qué horarios preferiría participar en actividades deportivas o recreativas?',
    'AF-10' => '¿Qué actividad deportiva o recreativa le gustaría que el SENA promoviera y por qué?',
];

$normalizarCodigoAF = static function (string $codigo): string {
    return strtoupper(str_replace('_', '-', trim($codigo)));
};

$esCodigoAF = static function (string $codigo): bool {
    return preg_match('/^AF(?:-|$)/', $codigo) === 1;
};

/*
|--------------------------------------------------------------------------
| Detectar textos genéricos
|--------------------------------------------------------------------------
*/

$esTextoGenericoPregunta = static function (string $texto): bool {
    $texto = trim($texto);

    if ($texto === '') {
        return true;
    }

    return preg_match(
        '/^(?:pregunta\s*)?(?:af[-_\s]*)?\d+(?:[-_\s]*frecuencia)?$/iu',
        $texto
    ) === 1;
};

/*
|--------------------------------------------------------------------------
| Nombre de referencia de una pregunta
|--------------------------------------------------------------------------
*/

$nombrePregunta = static function (string $codigo) use (
    $preguntasAF,
    $normalizarCodigoAF
): string {
    $codigo = $normalizarCodigoAF($codigo);

    return $preguntasAF[$codigo]
        ?? ucfirst(str_replace(['_', '-'], ' ', $codigo));
};

/*
|--------------------------------------------------------------------------
| Resolver el enunciado desde los datos entregados por el controlador
|--------------------------------------------------------------------------
*/

$resolverTextoPregunta = static function (
    array $datos,
    string $codigo
) use ($nombrePregunta, $esTextoGenericoPregunta): string {
    $camposTexto = [
        'texto',
        'enunciado',
        'pregunta_texto',
        'texto_pregunta',
        'enunciado_pregunta',
        'descripcion_pregunta',
        'pregunta_nombre',
        'titulo_pregunta',
    ];

    foreach ($camposTexto as $campo) {
        if (isset($datos[$campo]) && is_scalar($datos[$campo])) {
            $texto = trim((string) $datos[$campo]);

            if ($texto !== '' && !$esTextoGenericoPregunta($texto)) {
                return $texto;
            }
        }
    }

    if (isset($datos['pregunta'])) {
        if (is_string($datos['pregunta'])) {
            $texto = trim($datos['pregunta']);

            if ($texto !== '' && !$esTextoGenericoPregunta($texto)) {
                return $texto;
            }
        } elseif (is_array($datos['pregunta'])) {
            foreach ($camposTexto as $campo) {
                if (
                    isset($datos['pregunta'][$campo])
                    && is_scalar($datos['pregunta'][$campo])
                ) {
                    $texto = trim((string) $datos['pregunta'][$campo]);

                    if ($texto !== '' && !$esTextoGenericoPregunta($texto)) {
                        return $texto;
                    }
                }
            }
        }
    }

    return $nombrePregunta($codigo);
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

    if (in_array($estado, ['borrador', 'en_proceso', 'en proceso'], true)) {
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
| Normalización de respuestas individuales: únicamente códigos AF
|--------------------------------------------------------------------------
*/

$normalizarRespuestasAF = static function (mixed $datos) use (
    $normalizarCodigoAF,
    $esCodigoAF
): array {
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

    $recorrer = static function (array $elementos) use (
        &$recorrer,
        &$resultado,
        $normalizarCodigoAF,
        $esCodigoAF
    ): void {
        foreach ($elementos as $clave => $valor) {
            $claveTexto = is_string($clave) ? trim($clave) : '';
            $claveNormalizada = $normalizarCodigoAF($claveTexto);

            if (is_array($valor)) {
                $codigo = $valor['codigo']
                    ?? $valor['codigo_pregunta']
                    ?? $valor['pregunta_codigo']
                    ?? $valor['id_pregunta_codigo']
                    ?? null;

                if ($codigo !== null && trim((string) $codigo) !== '') {
                    $codigo = $normalizarCodigoAF((string) $codigo);

                    if ($esCodigoAF($codigo)) {
                        $resultado[$codigo] = $valor['respuesta']
                            ?? $valor['valor']
                            ?? $valor['respuesta_texto']
                            ?? $valor['opcion']
                            ?? $valor['respuesta_json']
                            ?? null;
                    }

                    continue;
                }

                if ($claveTexto !== '' && $esCodigoAF($claveNormalizada)) {
                    $resultado[$claveNormalizada] = $valor;
                    continue;
                }

                $recorrer($valor);
                continue;
            }

            if ($claveTexto !== '' && $esCodigoAF($claveNormalizada)) {
                $resultado[$claveNormalizada] = $valor;
            }
        }
    };

    $recorrer($datos);

    return $resultado;
};

/*
|--------------------------------------------------------------------------
| Estadísticas: exclusivamente preguntas AF
|--------------------------------------------------------------------------
|
| El controlador entrega filas planas (una por pregunta y respuesta) con
| código, respuesta y total. Aquí se agrupan por pregunta para mostrarlas
| igual que en Promoción y Prevención. También se acepta el formato ya
| agrupado (clave "respuestas").
*/

$estadisticasAF = [];

foreach ($estadisticas as $clave => $estadistica) {
    if (!is_array($estadistica)) {
        continue;
    }

    $codigoPregunta = $normalizarCodigoAF((string) (
        $estadistica['codigo_pregunta']
        ?? $estadistica['codigo']
        ?? $estadistica['pregunta_codigo']
        ?? $estadistica['id_pregunta_codigo']
        ?? (is_string($clave) ? $clave : '')
    ));

    if ($codigoPregunta === '' || !$esCodigoAF($codigoPregunta)) {
        continue;
    }

    if (!isset($estadisticasAF[$codigoPregunta])) {
        $estadisticasAF[$codigoPregunta] = [
            'codigo' => $codigoPregunta,
            'texto' => null,
            'respuestas' => [],
            'total_declarado' => 0,
            'sin_respuesta' => 0,
        ];
    }

    $textoDetectado = $resolverTextoPregunta($estadistica, $codigoPregunta);

    if (
        $estadisticasAF[$codigoPregunta]['texto'] === null
        && $textoDetectado !== $nombrePregunta($codigoPregunta)
    ) {
        $estadisticasAF[$codigoPregunta]['texto'] = $textoDetectado;
    }

    $respuestasAgrupadas = $estadistica['respuestas'] ?? null;

    if (is_string($respuestasAgrupadas)) {
        $decodificadas = json_decode($respuestasAgrupadas, true);
        $respuestasAgrupadas = is_array($decodificadas) ? $decodificadas : null;
    }

    if (is_array($respuestasAgrupadas)) {
        foreach ($respuestasAgrupadas as $claveRespuesta => $datoRespuesta) {
            if (is_array($datoRespuesta)) {
                $cantidadRespuesta = (int) (
                    $datoRespuesta['cantidad']
                    ?? $datoRespuesta['total']
                    ?? 0
                );

                $valorRespuesta = $datoRespuesta['valor']
                    ?? $datoRespuesta['respuesta']
                    ?? $datoRespuesta['etiqueta']
                    ?? $datoRespuesta['opcion']
                    ?? (string) $claveRespuesta;
            } else {
                $cantidadRespuesta = (int) $datoRespuesta;
                $valorRespuesta = (string) $claveRespuesta;
            }

            $etiqueta = afLegible($valorRespuesta);

            $estadisticasAF[$codigoPregunta]['respuestas'][$etiqueta] =
                ($estadisticasAF[$codigoPregunta]['respuestas'][$etiqueta] ?? 0)
                + max(0, $cantidadRespuesta);
        }

        $estadisticasAF[$codigoPregunta]['total_declarado'] +=
            (int) ($estadistica['total_respuestas'] ?? 0);

        $estadisticasAF[$codigoPregunta]['sin_respuesta'] +=
            (int) ($estadistica['sin_respuesta'] ?? 0);

        continue;
    }

    $valorRespuesta = $estadistica['respuesta']
        ?? $estadistica['opcion']
        ?? $estadistica['valor_respuesta']
        ?? $estadistica['valor']
        ?? 'Sin respuesta';

    $cantidadRespuesta = (int) (
        $estadistica['total']
        ?? $estadistica['cantidad']
        ?? $estadistica['total_respuestas']
        ?? 0
    );

    $etiqueta = afLegible($valorRespuesta);

    $estadisticasAF[$codigoPregunta]['respuestas'][$etiqueta] =
        ($estadisticasAF[$codigoPregunta]['respuestas'][$etiqueta] ?? 0)
        + max(0, $cantidadRespuesta);
}

/*
|--------------------------------------------------------------------------
| Tarjetas de resumen
|--------------------------------------------------------------------------
*/

$totalAprendices = (int) (
    $resumen['total_aprendices']
    ?? $resumen['total_matriculados']
    ?? $resumen['total']
    ?? count($aprendices)
);

$totalCompletados = (int) (
    $resumen['total_completados']
    ?? $resumen['completados']
    ?? 0
);

$totalBorradores = (int) (
    $resumen['en_proceso']
    ?? $resumen['borradores']
    ?? 0
);

$totalPendientes = (int) (
    $resumen['sin_iniciar']
    ?? $resumen['pendientes']
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
| Preparación de datos para las gráficas
|--------------------------------------------------------------------------
*/

$graficaEstados = [
    'Completados' => max(0, $totalCompletados),
    'En proceso' => max(0, $totalBorradores),
    'Sin iniciar' => max(0, $totalPendientes),
];

$graficaPreguntas = [];

foreach ($estadisticasAF as $codigo => $estadistica) {
    $etiquetasGrafica = [];
    $cantidadesGrafica = [];

    foreach ($estadistica['respuestas'] as $etiquetaRespuesta => $cantidadRespuesta) {
        $etiquetasGrafica[] = (string) $etiquetaRespuesta;
        $cantidadesGrafica[] = max(0, (int) $cantidadRespuesta);
    }

    $graficaPreguntas[] = [
        'codigo' => (string) $codigo,
        'pregunta' => $estadistica['texto'] ?? $nombrePregunta((string) $codigo),
        'etiquetas' => $etiquetasGrafica,
        'cantidades' => $cantidadesGrafica,
    ];
}

$opcionesJson = JSON_UNESCAPED_UNICODE
    | JSON_HEX_TAG
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
    | JSON_HEX_AMP;

$graficaEstadosJson = json_encode($graficaEstados, $opcionesJson);
$graficaPreguntasJson = json_encode($graficaPreguntas, $opcionesJson);

/*
|--------------------------------------------------------------------------
| Datos individuales del aprendiz
|--------------------------------------------------------------------------
*/

$datosIndividuales = $aprendizSeleccionado ?? [];

/*
 * Busca el primer valor disponible entre los nombres de campo conocidos.
 */
$obtenerPrimerValor = static function (
    array $datos,
    array $campos,
    mixed $predeterminado = null
): mixed {
    foreach ($campos as $campo) {
        if (
            array_key_exists($campo, $datos)
            && is_scalar($datos[$campo])
            && trim((string) $datos[$campo]) !== ''
        ) {
            return $datos[$campo];
        }
    }

    return $predeterminado;
};

$nombreIndividual = $obtenerPrimerValor(
    $datosIndividuales,
    ['nombre_completo', 'nombre_aprendiz', 'nombre_persona', 'nombre'],
    ''
);

if (trim((string) $nombreIndividual) === '') {
    $nombreIndividual = trim(
        (string) ($datosIndividuales['nombres'] ?? '')
        . ' '
        . (string) ($datosIndividuales['apellidos'] ?? '')
    );
}

if (trim((string) $nombreIndividual) === '') {
    $nombreIndividual = 'Nombre no disponible';
}

$documentoIndividual = $obtenerPrimerValor(
    $datosIndividuales,
    ['documento', 'numero_documento', 'identificacion', 'numero_identificacion'],
    'No registrado'
);

$numeroFichaIndividual = $obtenerPrimerValor(
    $datosIndividuales,
    ['numero_ficha', 'ficha', 'ficha_numero']
);

$programaIndividual = $obtenerPrimerValor(
    $datosIndividuales,
    ['programa', 'nombre_programa', 'programa_formacion', 'nombre_programa_formacion']
);

$correoIndividual = $obtenerPrimerValor(
    $datosIndividuales,
    ['correo', 'email', 'correo_electronico', 'correo_personal']
);

$estadoAprendiz = $obtenerPrimerValor(
    $datosIndividuales,
    ['estado_aprendiz', 'estado_formacion', 'estado', 'estado_matricula'],
    'No registrado'
);

/*
|--------------------------------------------------------------------------
| Búsqueda por respuestas de Actividad Física
|--------------------------------------------------------------------------
*/

$catalogoAF = is_array($catalogoAF ?? null) ? $catalogoAF : [];
$filtrosRapidos = is_array($filtrosRapidos ?? null) ? $filtrosRapidos : [];
$afPregunta = (string) ($afPregunta ?? '');
$afRespuesta = (string) ($afRespuesta ?? '');
$textoRespuesta = (string) ($textoRespuesta ?? '');
$hayFiltrosRespuesta = (bool) ($hayFiltrosRespuesta ?? false);
$aprendicesFiltrados = is_array($aprendicesFiltrados ?? null)
    ? $aprendicesFiltrados
    : [];
$totalConRespuestasAF = (int) ($totalConRespuestasAF ?? 0);
$maxFilasVista = (int) ($maxFilasVista ?? 500);

/* Filtros rápidos que se muestran como lista desplegable. */
$filtrosRapidosConfig = [
    'AF-03' => 'Deporte que le gustaría practicar',
    'AF-02' => 'Deporte que practica actualmente',
    'AF-09' => 'Horario preferido',
    'AF-05' => 'Participaría en actividades del SENA',
    'AF-01' => 'Practica actividad física actualmente',
    'AF-06' => 'Dificultades para participar',
];

$minusculas = static function (string $texto): string {
    $texto = trim($texto);

    $texto = function_exists('mb_strtolower')
        ? mb_strtolower($texto, 'UTF-8')
        : strtolower($texto);

    return strtr($texto, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o',
        'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
    ]);
};

$mismoTexto = static function (string $a, string $b) use ($minusculas): bool {
    return $minusculas($a) === $minusculas($b);
};

$ordenarOpciones = static function (array $datos): array {
    $opciones = $datos['opciones'] ?? [];

    uasort(
        $opciones,
        static fn(array $a, array $b): int =>
            strcasecmp((string) $a['nombre'], (string) $b['nombre'])
    );

    return $opciones;
};

/* Parámetros que se conservan en los enlaces de la página. */
$parametrosBase = [
    'busqueda' => $busqueda,
    'id_ficha' => $idFicha,
    'estado' => $estadoCuestionario,
    'af' => $filtrosRapidos,
    'af_pregunta' => $afPregunta,
    'af_respuesta' => $afRespuesta,
    'texto_respuesta' => $textoRespuesta,
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
$etiquetasAF = static function (array $fila, string $codigo): string {
    $opciones = $fila['respuestas_af'][$codigo] ?? [];

    if ($opciones === []) {
        return '<span class="af-muted">—</span>';
    }

    $html = '';

    foreach ($opciones as $opcion) {
        $html .= '<span class="af-tag">' . afEsc($opcion) . '</span>';
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

if ($afPregunta !== '') {
    $filtrosActivosTexto[] = $afPregunta . ': '
        . ($afRespuesta !== '' ? $afRespuesta : 'cualquier respuesta');
}

if ($textoRespuesta !== '') {
    $filtrosActivosTexto[] = 'Contiene «' . $textoRespuesta . '»'
        . ($afPregunta !== '' ? ' en ' . $afPregunta : ' en cualquier respuesta');
}

/* Catálogo para la lista dependiente (pregunta → respuestas) en JavaScript. */
$catalogoJs = [];

foreach ($catalogoAF as $codigoCatalogo => $datosCatalogo) {
    $nombresOpciones = array_column($datosCatalogo['opciones'] ?? [], 'nombre');
    sort($nombresOpciones, SORT_NATURAL | SORT_FLAG_CASE);

    $catalogoJs[$codigoCatalogo] = [
        'libre' => !empty($datosCatalogo['libre']),
        'opciones' => array_values($nombresOpciones),
    ];
}

$catalogoJsJson = json_encode($catalogoJs, $opcionesJson);
$afRespuestaJson = json_encode($afRespuesta, $opcionesJson);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= afEsc($tituloPagina) ?> | SITA</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --af-primary: #174a78;
            --af-primary-dark: #103653;
            --af-secondary: #2676a8;
            --af-bg: #f3f6fa;
            --af-card: #ffffff;
            --af-text: #263445;
            --af-muted: #64748b;
            --af-border: #dfe7ef;
            --af-green: #16835d;
            --af-orange: #bd7614;
            --af-red: #b83c48;
            --af-radius: 14px;
        }

        .af-page {
            background: var(--af-bg);
            min-height: 75vh;
            padding: 24px 0 34px;
            color: var(--af-text);
            font-family: Arial, Helvetica, sans-serif;
        }

        .af-container {
            width: min(1450px, calc(100% - 32px));
            margin: 0 auto;
        }

        .af-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .af-header h1 {
            margin: 0 0 7px;
            font-size: clamp(23px, 3vw, 31px);
            color: var(--af-primary-dark);
        }

        .af-header p {
            margin: 0;
            color: var(--af-muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .af-card {
            background: var(--af-card);
            border: 1px solid var(--af-border);
            border-radius: var(--af-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(20, 52, 80, .045);
            min-width: 0;
        }

        .af-card h2 {
            color: var(--af-primary-dark);
            font-size: 19px;
            margin: 0 0 16px;
        }

        .af-card h3 {
            color: var(--af-primary);
            font-size: 16px;
            margin: 0 0 12px;
        }

        .af-summary-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .af-stat {
            background: #ffffff;
            border: 1px solid var(--af-border);
            border-radius: 13px;
            padding: 17px;
            min-width: 0;
        }

        .af-stat-label {
            color: var(--af-muted);
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 8px;
        }

        .af-stat-value {
            color: var(--af-primary-dark);
            font-size: 29px;
            line-height: 1.15;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .af-stat.completed .af-stat-value { color: var(--af-green); }
        .af-stat.draft .af-stat-value { color: var(--af-orange); }
        .af-stat.pending .af-stat-value { color: var(--af-red); }

        .af-progress {
            height: 10px;
            background: #e7edf4;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 12px;
        }

        .af-progress > span {
            display: block;
            height: 100%;
            width: <?= number_format($porcentajeAvance, 2, '.', '') ?>%;
            background: var(--af-green);
            border-radius: inherit;
        }

        .af-form-grid {
            display: grid;
            grid-template-columns: 2fr 1.1fr 1fr auto;
            gap: 13px;
            align-items: end;
        }

        .af-field label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 7px;
            color: #334155;
        }

        .af-field input,
        .af-field select {
            box-sizing: border-box;
            width: 100%;
            min-height: 43px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            color: var(--af-text);
            font-size: 14px;
        }

        .af-button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
            min-height: 42px;
            padding: 10px 16px;
            border: 0;
            border-radius: 8px;
            background: var(--af-primary);
            color: #ffffff !important;
            text-decoration: none !important;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
        }

        .af-button:hover {
            background: var(--af-primary-dark);
        }

        .af-button.secondary {
            background: #e8eef5;
            color: var(--af-primary-dark) !important;
        }

        .af-button.small {
            min-height: 32px;
            padding: 7px 10px;
            font-size: 12px;
        }

        .af-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .af-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .af-table th,
        .af-table td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid var(--af-border);
            vertical-align: middle;
        }

        .af-table th {
            background: #f3f7fb;
            color: #334155;
            font-weight: 800;
            white-space: nowrap;
        }

        .af-table tbody tr:hover {
            background: #f8fbfe;
        }

        .af-badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .af-badge.completed {
            background: #d9f4e7;
            color: #116444;
        }

        .af-badge.draft {
            background: #fff0d2;
            color: #925400;
        }

        .af-badge.pending {
            background: #fde2e4;
            color: #a12835;
        }

        .af-muted {
            color: var(--af-muted);
        }

        .af-empty {
            padding: 25px 14px;
            text-align: center;
            color: var(--af-muted);
            line-height: 1.6;
        }

        .af-charts-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-top: 18px;
            align-items: stretch;
        }

        .af-chart-card {
            background: #ffffff;
            border: 1px solid var(--af-border);
            border-radius: 12px;
            padding: 18px;
            min-width: 0;
            box-shadow: 0 3px 12px rgba(20, 52, 80, .04);
        }

        .af-chart-card h3 {
            color: var(--af-primary-dark);
            font-size: 16px;
            margin: 0 0 8px;
        }

        .af-chart-description {
            color: var(--af-muted);
            font-size: 13px;
            line-height: 1.6;
            margin: 0 0 16px;
        }

        .af-chart-container {
            position: relative;
            width: 100%;
            height: 310px;
        }

        .af-chart-container.af-chart-doughnut {
            max-width: 390px;
            margin: 0 auto;
        }

        .af-chart-filter {
            margin-bottom: 16px;
        }

        .af-chart-filter label {
            display: block;
            color: #334155;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .af-chart-filter select {
            width: 100%;
            min-height: 42px;
            box-sizing: border-box;
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            color: var(--af-text);
            font-size: 14px;
        }

        
        .af-chart-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 180px;
    padding: 15px;
    text-align: center;
    color: var(--af-muted);
    font-size: 13px;
    line-height: 1.6;
}

        /* Ocultar correctamente los mensajes cuando no corresponden */
        .af-chart-empty[hidden] {
    display: none !important;
}

        .af-chart-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .af-chart-summary-item {
            border: 1px solid var(--af-border);
            border-radius: 9px;
            padding: 11px;
            background: #f8fafc;
            min-width: 0;
        }

        .af-chart-summary-item span {
            display: block;
            color: var(--af-muted);
            font-size: 11px;
            margin-bottom: 6px;
            line-height: 1.4;
        }

        .af-chart-summary-item strong {
            display: block;
            font-size: 20px;
            color: var(--af-primary-dark);
            overflow-wrap: anywhere;
        }

        .af-section {
            border: 1px solid var(--af-border);
            border-radius: 11px;
            margin: 14px 0;
            overflow: hidden;
            background: #ffffff;
        }

        .af-section-title {
            padding: 14px 16px;
            background: #edf4fa;
            color: var(--af-primary-dark);
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .af-section-body {
            padding: 14px;
        }

        .af-question {
            padding: 16px;
            border-top: 1px solid var(--af-border);
        }

        .af-question:first-child {
            border-top: 0;
        }

        .af-question-title {
            font-weight: 700;
            margin-bottom: 11px;
            line-height: 1.5;
        }

        .af-response-row {
            display: grid;
            grid-template-columns: minmax(150px, 1.5fr) minmax(80px, .35fr) minmax(100px, 1fr);
            gap: 12px;
            align-items: center;
            padding: 8px 0;
            border-top: 1px solid #edf1f5;
            font-size: 13px;
        }

        .af-bar {
            height: 8px;
            background: #e9eef4;
            border-radius: 999px;
            overflow: hidden;
        }

        .af-bar span {
            display: block;
            height: 100%;
            background: var(--af-secondary);
            border-radius: inherit;
        }

        .af-profile-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .af-profile-item {
            padding: 12px;
            border: 1px solid var(--af-border);
            border-radius: 9px;
            min-width: 0;
            background: #ffffff;
        }

        .af-profile-item .label {
            color: var(--af-muted);
            font-size: 12px;
            margin-bottom: 5px;
        }

        .af-profile-item .value {
            font-weight: 700;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .af-answer {
            padding: 13px 14px;
            border: 1px solid var(--af-border);
            border-radius: 9px;
            margin-bottom: 10px;
            background: #ffffff;
        }

        .af-answer:last-child {
            margin-bottom: 0;
        }

        .af-answer .question {
            font-weight: 700;
            margin-bottom: 7px;
            color: var(--af-primary-dark);
            line-height: 1.5;
        }

        .af-answer .value {
            font-size: 14px;
            line-height: 1.7;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        .af-note {
            border-left: 4px solid #d6a13b;
            background: #fff9e9;
            color: #654b16;
            padding: 12px 14px;
            border-radius: 6px;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .af-detail-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin: 24px 0 10px;
        }

        .af-footer {
            text-align: center;
            padding: 14px 10px;
            color: #64748b;
            font-size: 12px;
        }

        /* ---------- Búsqueda por respuestas ---------- */
        .af-filters-extra {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px dashed var(--af-border);
        }

        .af-filter-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 13px;
        }

        .af-filter-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .af-field .af-hint {
            display: block;
            margin-top: 5px;
            font-size: 11px;
            color: var(--af-muted);
            line-height: 1.4;
        }

        .af-field select:disabled {
            background: #f1f5f9;
            color: #94a3b8;
        }

        .af-tag {
            display: inline-block;
            padding: 3px 9px;
            margin: 2px 4px 2px 0;
            border-radius: 999px;
            background: #e8f1f9;
            color: var(--af-primary);
            font-size: 12px;
            font-weight: 700;
            line-height: 1.4;
        }

        .af-tag.match {
            background: #d9f4e7;
            color: #116444;
        }

        .af-chip-block + .af-chip-block {
            margin-top: 18px;
        }

        .af-chip-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .af-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 12px;
            border: 1px solid var(--af-border);
            border-radius: 999px;
            background: #ffffff;
            color: var(--af-primary-dark) !important;
            text-decoration: none !important;
            font-size: 13px;
            font-weight: 700;
        }

        .af-chip:hover {
            border-color: var(--af-secondary);
            background: #f3f8fc;
        }

        .af-chip span {
            padding: 1px 7px;
            border-radius: 999px;
            background: #e8eef5;
            font-size: 11px;
        }

        .af-chip.active {
            background: var(--af-primary);
            border-color: var(--af-primary);
            color: #ffffff !important;
        }

        .af-chip.active span {
            background: rgba(255, 255, 255, .22);
        }

        .af-results-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .af-results-head h2 {
            margin-bottom: 6px;
        }

        .af-active-filters {
            margin: 0 0 14px;
            padding: 10px 12px;
            background: #f3f8fc;
            border: 1px solid var(--af-border);
            border-radius: 9px;
            font-size: 13px;
            line-height: 1.7;
        }

        @media (max-width: 1100px) {
            .af-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .af-filter-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1100px) {
            .af-summary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .af-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 800px) {
            .af-charts-grid {
                grid-template-columns: 1fr;
            }

            .af-chart-container {
                height: 280px;
            }
        }

        @media (max-width: 700px) {
            .af-summary-grid,
            .af-profile-grid,
            .af-form-grid {
                grid-template-columns: 1fr;
            }

            .af-response-row {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            .af-card {
                padding: 15px;
            }

            .af-container {
                width: calc(100% - 20px);
            }

            .af-chart-summary {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 420px) {
            .af-summary-grid {
                grid-template-columns: 1fr;
            }

            .af-chart-card {
                padding: 13px;
            }
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

<main class="af-page">
    <div class="af-container">

        <header class="af-header">
            <div>
                <h1><?= afEsc($titulo) ?></h1>
                <p>
                    Consulta de Actividad Física de los aprendices,
                    con estadísticas y detalle individual del componente AF.
                </p>
            </div>

            <a class="af-button secondary" href="<?= afEsc($urlConsulta) ?>">
                Limpiar filtros
            </a>
        </header>

        <section class="af-summary-grid" aria-label="Resumen de cuestionarios">

            <div class="af-stat">
                <div class="af-stat-label">Total de aprendices</div>
                <div class="af-stat-value">
                    <?= number_format($totalAprendices, 0, ',', '.') ?>
                </div>
                <div class="af-muted" style="font-size:12px;margin-top:6px">
                    Según los datos recibidos
                </div>
            </div>

            <div class="af-stat completed">
                <div class="af-stat-label">Cuestionarios completados</div>
                <div class="af-stat-value">
                    <?= number_format($totalCompletados, 0, ',', '.') ?>
                </div>
                <div class="af-muted" style="font-size:12px;margin-top:6px">
                    Registro finalizado
                </div>
            </div>

            <div class="af-stat draft">
                <div class="af-stat-label">En proceso</div>
                <div class="af-stat-value">
                    <?= number_format($totalBorradores, 0, ',', '.') ?>
                </div>
                <div class="af-muted" style="font-size:12px;margin-top:6px">
                    Cuestionarios en borrador
                </div>
            </div>

            <div class="af-stat pending">
                <div class="af-stat-label">Sin iniciar</div>
                <div class="af-stat-value">
                    <?= number_format($totalPendientes, 0, ',', '.') ?>
                </div>
                <div class="af-muted" style="font-size:12px;margin-top:6px">
                    Sin completar
                </div>
            </div>

            <div class="af-stat">
                <div class="af-stat-label">Avance de diligenciamiento</div>
                <div class="af-stat-value">
                    <?= number_format($porcentajeAvance, 1, ',', '.') ?>%
                </div>
                <div
                    class="af-progress"
                    role="progressbar"
                    aria-valuenow="<?= afEsc((string) round($porcentajeAvance, 1)) ?>"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >
                    <span></span>
                </div>
            </div>

        </section>

        <section class="af-card">
            <h2>Buscar y filtrar aprendices</h2>

            <form method="GET" action="<?= afEsc($urlConsulta) ?>">
                <div class="af-form-grid">

                    <div class="af-field">
                        <label for="af_busqueda">Nombre o documento</label>
                        <input
                            type="text"
                            name="busqueda"
                            id="af_busqueda"
                            value="<?= afEsc($busqueda) ?>"
                            placeholder="Escriba para buscar..."
                        >
                    </div>

                    <div class="af-field">
                        <label for="af_ficha">Ficha</label>
                        <select name="id_ficha" id="af_ficha">
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
                                    ?? $ficha['nombre']
                                    ?? $ficha['nombre_programa']
                                    ?? '';
                                ?>

                                <option
                                    value="<?= $fichaId ?>"
                                    <?= $idFicha === $fichaId ? 'selected' : '' ?>
                                >
                                    <?= afEsc(
                                        $fichaNumero .
                                        ($programa !== '' ? ' - ' . $programa : '')
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="af-field">
                        <label for="af_estado">Estado del cuestionario</label>
                        <select name="estado" id="af_estado">
                            <option
                                value="todos"
                                <?= $estadoCuestionario === 'todos' ? 'selected' : '' ?>
                            >
                                Todos los estados
                            </option>
                            <option
                                value="pendiente"
                                <?= $estadoCuestionario === 'pendiente' ? 'selected' : '' ?>
                            >
                                Pendiente
                            </option>
                            <option
                                value="borrador"
                                <?= $estadoCuestionario === 'borrador' ? 'selected' : '' ?>
                            >
                                En proceso
                            </option>
                            <option
                                value="completado"
                                <?= $estadoCuestionario === 'completado' ? 'selected' : '' ?>
                            >
                                Completado
                            </option>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="af-button" style="width:100%">
                            Consultar
                        </button>
                    </div>

                </div>

                <div class="af-filters-extra">
                    <h3>Buscar por respuestas de Actividad Física</h3>

                    <p
                        class="af-muted"
                        style="font-size:13px;line-height:1.6;margin:-4px 0 14px"
                    >
                        Combine los filtros para saber quién practica o quiere
                        practicar un deporte, en qué horarios puede participar
                        o qué respondió en cada pregunta. Los filtros se suman
                        entre sí.
                    </p>

                    <div class="af-filter-grid">
                        <?php foreach ($filtrosRapidosConfig as $codigoFiltro => $etiquetaFiltro): ?>
                            <?php
                            $datosFiltro = $catalogoAF[$codigoFiltro] ?? null;

                            if ($datosFiltro === null || empty($datosFiltro['opciones'])) {
                                continue;
                            }

                            $valorActual = (string) ($filtrosRapidos[$codigoFiltro] ?? '');
                            $idCampo = 'af_' . strtolower(str_replace('-', '_', $codigoFiltro));
                            ?>

                            <div class="af-field">
                                <label for="<?= afEsc($idCampo) ?>">
                                    <?= afEsc($etiquetaFiltro) ?>
                                    <span class="af-muted">(<?= afEsc($codigoFiltro) ?>)</span>
                                </label>

                                <select
                                    name="af[<?= afEsc($codigoFiltro) ?>]"
                                    id="<?= afEsc($idCampo) ?>"
                                >
                                    <option value="">Todas</option>

                                    <?php foreach ($ordenarOpciones($datosFiltro) as $opcionFiltro): ?>
                                        <option
                                            value="<?= afEsc($opcionFiltro['nombre']) ?>"
                                            <?= ($valorActual !== '' && $mismoTexto($valorActual, (string) $opcionFiltro['nombre'])) ? 'selected' : '' ?>
                                        >
                                            <?= afEsc($opcionFiltro['nombre']) ?>
                                            (<?= (int) $opcionFiltro['cantidad'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="af-filter-grid" style="margin-top:14px">

                        <div class="af-field">
                            <label for="af_pregunta">Cualquier pregunta AF</label>
                            <select name="af_pregunta" id="af_pregunta">
                                <option value="">Seleccione una pregunta</option>

                                <?php foreach ($catalogoAF as $codigoPregunta => $datosPregunta): ?>
                                    <option
                                        value="<?= afEsc($codigoPregunta) ?>"
                                        <?= $afPregunta === $codigoPregunta ? 'selected' : '' ?>
                                    >
                                        <?= afEsc(
                                            $codigoPregunta . ' - '
                                            . $nombrePregunta((string) $codigoPregunta)
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="af-field">
                            <label for="af_respuesta">Respuesta de esa pregunta</label>
                            <select name="af_respuesta" id="af_respuesta" disabled>
                                <option value="">Seleccione primero una pregunta</option>
                            </select>
                            <span class="af-hint">
                                Si deja «Cualquier respuesta», se listan quienes
                                contestaron la pregunta.
                            </span>
                        </div>

                        <div class="af-field">
                            <label for="texto_respuesta">
                                Palabra dentro de las respuestas
                            </label>
                            <input
                                type="text"
                                name="texto_respuesta"
                                id="texto_respuesta"
                                value="<?= afEsc($textoRespuesta) ?>"
                                placeholder="Ej: fútbol, noche, sábado, tiempo..."
                            >
                            <span class="af-hint">
                                Ignora tildes y mayúsculas. Si eligió una pregunta,
                                solo busca en esa; si no, en todas las respuestas AF.
                            </span>
                        </div>
                    </div>

                    <div class="af-filter-actions">
                        <button type="submit" class="af-button">
                            Aplicar filtros
                        </button>

                        <a class="af-button secondary" href="<?= afEsc($urlConsulta) ?>">
                            Limpiar todo
                        </a>
                    </div>
                </div>
            </form>
        </section>

        <?php
        $bloquesChips = [
            'AF-03' => 'Deportes que les gustaría practicar',
            'AF-02' => 'Deportes que practican actualmente',
            'AF-09' => 'Horarios preferidos',
        ];

        $hayChips = false;

        foreach ($bloquesChips as $codigoChip => $tituloChip) {
            if (!empty($catalogoAF[$codigoChip]['opciones'])) {
                $hayChips = true;
                break;
            }
        }
        ?>

        <?php if ($hayChips): ?>
            <section class="af-card">
                <h2>Intereses más frecuentes</h2>

                <p
                    class="af-muted"
                    style="font-size:13px;line-height:1.6;margin-top:-8px"
                >
                    Haga clic en una opción para ver qué aprendices la
                    eligieron. Vuelva a hacer clic en la opción activa
                    para quitar el filtro.
                </p>

                <?php foreach ($bloquesChips as $codigoChip => $tituloChip): ?>
                    <?php
                    $opcionesChip = $catalogoAF[$codigoChip]['opciones'] ?? [];

                    if ($opcionesChip === []) {
                        continue;
                    }

                    $valorChipActivo = (string) ($filtrosRapidos[$codigoChip] ?? '');
                    $totalOpcionesChip = count($opcionesChip);
                    ?>

                    <div class="af-chip-block">
                        <h3>
                            <?= afEsc($tituloChip) ?>
                            <span class="af-muted" style="font-weight:400;font-size:12px">
                                (<?= afEsc($codigoChip) ?> ·
                                <?= (int) $catalogoAF[$codigoChip]['respondieron'] ?>
                                respuestas)
                            </span>
                        </h3>

                        <div class="af-chip-list">
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
                                    class="af-chip <?= $activo ? 'active' : '' ?>"
                                    href="<?= afEsc($construirUrl(['af' => $filtrosEnlace]) . '#resultados-respuestas') ?>"
                                >
                                    <?= afEsc($opcionChip['nombre']) ?>
                                    <span><?= (int) $opcionChip['cantidad'] ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($totalOpcionesChip > 15): ?>
                            <p class="af-muted" style="font-size:12px;margin:8px 0 0">
                                Se muestran las 15 más frecuentes de
                                <?= (int) $totalOpcionesChip ?>. Use los filtros
                                de arriba para ver el resto.
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <section class="af-card" id="resultados-respuestas">
            <div class="af-results-head">
                <div>
                    <h2>Aprendices por intereses y respuestas</h2>

                    <p class="af-muted" style="font-size:13px;line-height:1.6;margin:0">
                        <strong><?= number_format(count($aprendicesFiltrados), 0, ',', '.') ?></strong>
                        <?= count($aprendicesFiltrados) === 1 ? 'aprendiz' : 'aprendices' ?>
                        <?= $hayFiltrosRespuesta ? 'cumplen los filtros' : 'con respuestas de Actividad Física' ?>
                        (de <?= number_format($totalConRespuestasAF, 0, ',', '.') ?>
                        con respuestas AF registradas).
                    </p>
                </div>

                <?php if ($aprendicesFiltrados !== []): ?>
                    <a
                        class="af-button secondary"
                        href="<?= afEsc($construirUrl(['exportar' => 'csv'])) ?>"
                    >
                        Exportar a CSV
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($filtrosActivosTexto !== []): ?>
                <div class="af-active-filters">
                    <strong>Filtros activos:</strong>
                    <?php foreach ($filtrosActivosTexto as $textoActivo): ?>
                        <span class="af-tag"><?= afEsc($textoActivo) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($aprendicesFiltrados === []): ?>

                <div class="af-empty">
                    <strong>No hay aprendices con esos criterios.</strong>
                    <br>
                    Pruebe con menos filtros, otra ficha o una palabra más corta.
                </div>

            <?php else: ?>

                <div class="af-table-wrap">
                    <table class="af-table">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Aprendiz</th>
                                <th>Ficha</th>
                                <th>Practica actualmente</th>
                                <th>Le gustaría practicar</th>
                                <th>Horarios</th>
                                <th>¿Actividades SENA?</th>
                                <?php if ($hayFiltrosRespuesta): ?>
                                    <th>Respuestas que coinciden</th>
                                <?php endif; ?>
                                <th>Consulta</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php foreach (array_slice($aprendicesFiltrados, 0, $maxFilasVista) as $filaAF): ?>
                            <?php
                            $idAprendizAF = (int) ($filaAF['id_aprendiz'] ?? 0);
                            $nombreAF = $obtenerNombreAprendiz($filaAF);
                            ?>

                            <tr>
                                <td><?= afEsc($filaAF['documento'] ?? 'No registrado') ?></td>

                                <td>
                                    <strong>
                                        <?= afEsc($nombreAF !== '' ? $nombreAF : 'Nombre no disponible') ?>
                                    </strong>
                                </td>

                                <td><?= afEsc($filaAF['numero_ficha'] ?? '—') ?></td>

                                <td><?= $etiquetasAF($filaAF, 'AF-02') ?></td>
                                <td><?= $etiquetasAF($filaAF, 'AF-03') ?></td>
                                <td><?= $etiquetasAF($filaAF, 'AF-09') ?></td>
                                <td><?= $etiquetasAF($filaAF, 'AF-05') ?></td>

                                <?php if ($hayFiltrosRespuesta): ?>
                                    <td>
                                        <?php foreach (($filaAF['coincidencias'] ?? []) as $coincidencia): ?>
                                            <span class="af-tag match"><?= afEsc($coincidencia) ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                <?php endif; ?>

                                <td>
                                    <a
                                        class="af-button small"
                                        href="<?= afEsc($construirUrl(['id_aprendiz' => $idAprendizAF]) . '#detalle-aprendiz') ?>"
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
                    <p class="af-muted" style="font-size:12px;margin-top:12px">
                        Se muestran los primeros
                        <?= (int) $maxFilasVista ?> resultados. Use «Exportar a CSV»
                        para obtener la lista completa.
                    </p>
                <?php endif; ?>

            <?php endif; ?>
        </section>

        <section class="af-card">
            <h2>Listado general de aprendices (estado del cuestionario)</h2>

            <p
                class="af-muted"
                style="font-size:13px;margin-top:-8px;margin-bottom:15px"
            >
                Se muestran aprendices que coinciden con los filtros
                seleccionados.
            </p>

            <?php if ($aprendices === []): ?>

                <div class="af-empty">
                    <strong>No se encontraron aprendices.</strong>
                    <br>
                    Verifique los datos ingresados o seleccione otros filtros.
                </div>

            <?php else: ?>

                <div class="af-table-wrap">
                    <table class="af-table">
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
                                    <?= afEsc(
                                        $fila['documento']
                                        ?? $fila['numero_documento']
                                        ?? 'No registrado'
                                    ) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= afEsc(
                                            $nombre !== ''
                                                ? $nombre
                                                : 'Nombre no disponible'
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= afEsc(
                                        $fila['numero_ficha']
                                        ?? $fila['ficha']
                                        ?? '—'
                                    ) ?>
                                </td>

                                <td>
                                    <?= afEsc(
                                        $fila['programa']
                                        ?? $fila['nombre_programa']
                                        ?? '—'
                                    ) ?>
                                </td>

                                <td>
                                    <span class="af-badge <?= afEsc($estadoClase) ?>">
                                        <?= afEsc($estadoTexto) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= afEsc(afFecha(
                                        $fila['fecha_actualizacion']
                                        ?? $fila['fecha_completado']
                                        ?? null
                                    )) ?>
                                </td>

                                <td>
                                    <a
                                        class="af-button small"
                                        href="<?= afEsc($urlDetalle) ?>"
                                    >
                                        Ver detalle
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (count($aprendices) >= 200): ?>
                    <p class="af-muted" style="font-size:12px;margin-top:12px">
                        Se muestran hasta 200 registros. Use los filtros
                        para precisar la búsqueda.
                    </p>
                <?php endif; ?>

            <?php endif; ?>
        </section>

        <section class="af-card" id="graficas-actividad-fisica">
            <h2>Panel gráfico de Actividad Física</h2>

            <p class="af-muted" style="font-size:13px;line-height:1.6">
                Consulte el estado de los cuestionarios y explore la
                distribución de respuestas por pregunta del componente AF.
            </p>

            <div class="af-charts-grid">

                <article class="af-chart-card">
                    <h3>Estado de los cuestionarios</h3>

                    <p class="af-chart-description">
                        Distribución de cuestionarios completados,
                        en proceso y sin iniciar.
                    </p>

                    <div
                        class="af-chart-container af-chart-doughnut"
                        id="afContenedorEstados"
                    >
                        <canvas
                            id="afChartEstados"
                            role="img"
                            aria-label="Gráfica circular del estado de los cuestionarios"
                        ></canvas>
                    </div>

                    <div id="afChartEstadosVacio" class="af-chart-empty" hidden>
                        No hay datos suficientes para mostrar esta gráfica.
                    </div>

                    <div class="af-chart-summary">
                        <div class="af-chart-summary-item">
                            <span>Completados</span>
                            <strong>
                                <?= number_format($totalCompletados, 0, ',', '.') ?>
                            </strong>
                        </div>

                        <div class="af-chart-summary-item">
                            <span>En proceso</span>
                            <strong>
                                <?= number_format($totalBorradores, 0, ',', '.') ?>
                            </strong>
                        </div>

                        <div class="af-chart-summary-item">
                            <span>Sin iniciar</span>
                            <strong>
                                <?= number_format($totalPendientes, 0, ',', '.') ?>
                            </strong>
                        </div>
                    </div>
                </article>

                <article class="af-chart-card">
                    <h3>Respuestas por pregunta</h3>

                    <p class="af-chart-description">
                        Seleccione una pregunta de Actividad Física
                        para comparar las cantidades registradas en sus
                        diferentes opciones de respuesta.
                    </p>

                    <div class="af-chart-filter">
                        <label for="afPreguntaGrafica">
                            Pregunta de Actividad Física
                        </label>

                        <select id="afPreguntaGrafica">
                            <option value="">Seleccione una pregunta</option>

                            <?php foreach ($graficaPreguntas as $indice => $preguntaGrafica): ?>
                                <option value="<?= (int) $indice ?>">
                                    <?= afEsc(
                                        $preguntaGrafica['codigo']
                                        . ' - '
                                        . $preguntaGrafica['pregunta']
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="af-chart-container" id="afContenedorRespuestas">
                        <canvas
                            id="afChartRespuestas"
                            role="img"
                            aria-label="Gráfica de barras de respuestas por pregunta"
                        ></canvas>
                    </div>

                    <div id="afChartRespuestasVacio" class="af-chart-empty" hidden>
                        Seleccione una pregunta con respuestas registradas
                        para visualizar su distribución.
                    </div>
                </article>

            </div>
        </section>

        <section class="af-card">
            <h2>Estadísticas de Actividad Física</h2>

            <p class="af-muted" style="font-size:13px;line-height:1.6">
                Las estadísticas de esta sección corresponden únicamente
                a las preguntas identificadas con el código AF.
            </p>

            <?php if ($estadisticasAF === []): ?>

                <div class="af-empty">
                    Todavía no hay respuestas disponibles para generar
                    estadísticas de Actividad Física con los permisos
                    y filtros actuales.
                </div>

            <?php else: ?>

                <div class="af-section">
                    <div class="af-section-title">
                        <span>Actividad Física</span>
                        <span><?= count($estadisticasAF) ?> preguntas</span>
                    </div>

                    <div class="af-section-body">

                        <?php foreach ($estadisticasAF as $codigo => $estadistica): ?>
                            <?php
                            $respuestas = $estadistica['respuestas'];

                            $sumaRespuestas = array_sum($respuestas);

                            $denominador = $estadistica['total_declarado'] > 0
                                ? $estadistica['total_declarado']
                                : $sumaRespuestas;

                            $textoPregunta = $estadistica['texto']
                                ?? $nombrePregunta((string) $codigo);
                            ?>

                            <div class="af-question">
                                <div class="af-question-title">
                                    <?= afEsc($textoPregunta) ?>

                                    <span
                                        class="af-muted"
                                        style="font-weight:400;font-size:12px"
                                    >
                                        (<?= afEsc((string) $codigo) ?>)
                                    </span>
                                </div>

                                <?php if ($respuestas === []): ?>

                                    <div class="af-muted" style="font-size:13px">
                                        No hay respuestas registradas
                                        para esta pregunta.
                                    </div>

                                <?php else: ?>

                                    <?php foreach ($respuestas as $textoRespuesta => $cantidad): ?>
                                        <?php
                                        $porcentaje = $denominador > 0
                                            ? min(100, ($cantidad / $denominador) * 100)
                                            : 0;
                                        ?>

                                        <div class="af-response-row">
                                            <div>
                                                <?= afEsc((string) $textoRespuesta) ?>
                                            </div>

                                            <div>
                                                <strong>
                                                    <?= number_format($cantidad, 0, ',', '.') ?>
                                                </strong>

                                                <span class="af-muted">
                                                    (<?= number_format($porcentaje, 1, ',', '.') ?>%)
                                                </span>
                                            </div>

                                            <div class="af-bar">
                                                <span
                                                    style="width:<?= number_format($porcentaje, 2, '.', '') ?>%"
                                                ></span>
                                            </div>
                                        </div>

                                    <?php endforeach; ?>

                                <?php endif; ?>
                            </div>

                        <?php endforeach; ?>

                    </div>
                </div>

            <?php endif; ?>
        </section>

        <?php if ($idAprendiz > 0): ?>

            <section class="af-card" id="detalle-aprendiz">
                <h2>Detalle individual del aprendiz</h2>

                <?php if ($aprendizSeleccionado === null): ?>

                    <div class="af-empty">
                        No fue posible encontrar la información del aprendiz
                        solicitado. Verifique los filtros y la matrícula.
                    </div>

                <?php else: ?>

                    <div class="af-note">
                        La información individual puede contener datos
                        personales y de bienestar. Consulte estos datos
                        únicamente para fines institucionales autorizados.
                    </div>

                    <div class="af-profile-grid">

                        <div class="af-profile-item">
                            <div class="label">Nombre completo</div>
                            <div class="value"><?= afEsc($nombreIndividual) ?></div>
                        </div>

                        <div class="af-profile-item">
                            <div class="label">Documento</div>
                            <div class="value"><?= afEsc($documentoIndividual) ?></div>
                        </div>

                        <div class="af-profile-item">
                            <div class="label">Ficha</div>
                            <div class="value">
                                <?= afEsc(
                                    ($numeroFichaIndividual !== null
                                        && $numeroFichaIndividual !== '')
                                        ? $numeroFichaIndividual
                                        : 'No registrada'
                                ) ?>
                            </div>
                        </div>

                        <div class="af-profile-item">
                            <div class="label">Programa de formación</div>
                            <div class="value">
                                <?= afEsc(
                                    ($programaIndividual !== null
                                        && $programaIndividual !== '')
                                        ? $programaIndividual
                                        : 'No registrado'
                                ) ?>
                            </div>
                        </div>

                        <div class="af-profile-item">
                            <div class="label">Correo electrónico</div>
                            <div class="value">
                                <?= afEsc(
                                    ($correoIndividual !== null
                                        && $correoIndividual !== '')
                                        ? $correoIndividual
                                        : 'No registrado'
                                ) ?>
                            </div>
                        </div>

                        <div class="af-profile-item">
                            <div class="label">Estado del aprendiz</div>
                            <div class="value"><?= afEsc($estadoAprendiz) ?></div>
                        </div>

                    </div>

                    <div class="af-detail-heading">
                        <h3 style="margin:0">
                            Cuestionarios y respuestas de Actividad Física
                        </h3>
                    </div>

                    <?php if ($cuestionarios === []): ?>

                        <div class="af-empty">
                            Este aprendiz no tiene cuestionarios registrados
                            con información disponible.
                        </div>

                    <?php else: ?>

                        <?php foreach ($cuestionarios as $indice => $cuestionario): ?>
                            <?php
                            if (!is_array($cuestionario)) {
                                continue;
                            }

                            $estadoIndividualTexto = $obtenerEstado($cuestionario);
                            $claseIndividual = $obtenerClaseEstado($estadoIndividualTexto);

                            $fuenteRespuestas = $cuestionario['respuestas']
                                ?? $cuestionario['respuestas_json']
                                ?? $cuestionario['datos_respuestas']
                                ?? $cuestionario['respuesta_json']
                                ?? [];

                            $respuestasIndividuales = $normalizarRespuestasAF(
                                $fuenteRespuestas
                            );
                            ?>

                            <div class="af-section">
                                <div class="af-section-title">
                                    <span>Cuestionario <?= (int) ($indice + 1) ?></span>

                                    <span class="af-badge <?= afEsc($claseIndividual) ?>">
                                        <?= afEsc($estadoIndividualTexto) ?>
                                    </span>
                                </div>

                                <div class="af-section-body">
                                    <p
                                        class="af-muted"
                                        style="font-size:12px;line-height:1.8"
                                    >
                                        <strong>Inicio:</strong>
                                        <?= afEsc(afFecha(
                                            $cuestionario['fecha_inicio'] ?? null
                                        )) ?>

                                        &nbsp; | &nbsp;

                                        <strong>Última actualización:</strong>
                                        <?= afEsc(afFecha(
                                            $cuestionario['fecha_actualizacion'] ?? null
                                        )) ?>

                                        &nbsp; | &nbsp;

                                        <strong>Finalización:</strong>
                                        <?= afEsc(afFecha(
                                            $cuestionario['fecha_completado'] ?? null
                                        )) ?>
                                    </p>

                                    <?php if ($respuestasIndividuales === []): ?>

                                        <div class="af-empty">
                                            No hay respuestas identificadas
                                            para el componente AF en este
                                            cuestionario.
                                        </div>

                                    <?php else: ?>

                                        <?php foreach ($respuestasIndividuales as $codigo => $valor): ?>
                                            <div class="af-answer">
                                                <div class="question">
                                                    <?= afEsc($nombrePregunta((string) $codigo)) ?>

                                                    <span
                                                        class="af-muted"
                                                        style="font-size:11px;font-weight:400"
                                                    >
                                                        (<?= afEsc((string) $codigo) ?>)
                                                    </span>
                                                </div>

                                                <div class="value">
                                                    <?= afEsc(afLegible($valor)) ?>
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
                    <a
                        class="af-button secondary"
                        href="<?= afEsc($construirUrl() . '#resultados-respuestas') ?>"
                    >
                        Volver al listado
                    </a>
                </div>
            </section>

        <?php endif; ?>

    </div>
</main>

<footer class="af-footer">
    SITA · Sistema de Trazabilidad de Aprendices
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const estados = <?= $graficaEstadosJson ?: '{}' ?>;
    const preguntas = <?= $graficaPreguntasJson ?: '[]' ?>;

    const canvasEstados = document.getElementById('afChartEstados');
    const contenedorEstados = document.getElementById('afContenedorEstados');
    const vacioEstados = document.getElementById('afChartEstadosVacio');

    const selectorPregunta = document.getElementById('afPreguntaGrafica');
    const canvasRespuestas = document.getElementById('afChartRespuestas');
    const contenedorRespuestas = document.getElementById('afContenedorRespuestas');
    const vacioRespuestas = document.getElementById('afChartRespuestasVacio');

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
            mostrarVacio(
                canvasEstados,
                contenedorEstados,
                vacioEstados,
                true
            );
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
            mostrarVacio(
                canvasEstados,
                contenedorEstados,
                vacioEstados,
                true
            );
            return;
        }

        mostrarVacio(
            canvasEstados,
            contenedorEstados,
            vacioEstados,
            false
        );

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
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (contexto) {
                                const cantidad = Number(contexto.raw) || 0;
                                const porcentaje = total > 0
                                    ? (cantidad / total) * 100
                                    : 0;

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
            mostrarVacio(
                canvasRespuestas,
                contenedorRespuestas,
                vacioRespuestas,
                true
            );
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
            mostrarVacio(
                canvasRespuestas,
                contenedorRespuestas,
                vacioRespuestas,
                true
            );
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
            mostrarVacio(
                canvasRespuestas,
                contenedorRespuestas,
                vacioRespuestas,
                true
            );
            return;
        }

        mostrarVacio(
            canvasRespuestas,
            contenedorRespuestas,
            vacioRespuestas,
            false
        );

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
                        grid: {
                            color: '#edf1f5'
                        }
                    },
                    y: {
                        ticks: {
                            autoSkip: false,
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: pregunta.codigo + ' - ' + pregunta.pregunta,
                        color: '#103653',
                        font: {
                            size: 13,
                            weight: 'bold'
                        },
                        padding: {
                            bottom: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (contexto) {
                                const cantidad = Number(contexto.raw) || 0;
                                const porcentaje = total > 0
                                    ? (cantidad / total) * 100
                                    : 0;

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
                if (
                    !Array.isArray(pregunta.cantidades)
                    || pregunta.cantidades.length === 0
                ) {
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
                mostrarVacio(
                    canvasRespuestas,
                    contenedorRespuestas,
                    vacioRespuestas,
                    true
                );
            }

            selectorPregunta.addEventListener('change', function () {
                if (this.value === '') {
                    if (graficaRespuestas) {
                        graficaRespuestas.destroy();
                        graficaRespuestas = null;
                    }

                    mostrarVacio(
                        canvasRespuestas,
                        contenedorRespuestas,
                        vacioRespuestas,
                        true
                    );
                    return;
                }

                renderizarRespuestas(this.value);
            });
        }
    } else {
        mostrarVacio(
            canvasEstados,
            contenedorEstados,
            vacioEstados,
            true
        );

        mostrarVacio(
            canvasRespuestas,
            contenedorRespuestas,
            vacioRespuestas,
            true
        );

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
/* Lista dependiente: pregunta AF -> respuestas disponibles. */
(function () {
    'use strict';

    const catalogo = <?= $catalogoJsJson ?: '{}' ?>;
    const respuestaInicial = <?= $afRespuestaJson ?: '""' ?>;

    const selectorPregunta = document.getElementById('af_pregunta');
    const selectorRespuesta = document.getElementById('af_respuesta');

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