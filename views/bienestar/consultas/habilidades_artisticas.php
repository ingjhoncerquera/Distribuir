<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SITA - Consulta de Habilidades Artísticas
| Archivo: views/bienestar/consultas/habilidades_artisticas.php
|--------------------------------------------------------------------------
*/

if (!function_exists('haEsc')) {
    function haEsc(mixed $valor): string
    {
        if (is_array($valor) || is_object($valor)) {
            $valor = json_encode($valor, JSON_UNESCAPED_UNICODE);
        }

        return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('haFecha')) {
    function haFecha(mixed $fecha): string
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

if (!function_exists('haLegible')) {
    function haLegible(mixed $valor): string
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
                    $texto = haLegible($elemento);

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
                return haLegible($decodificado);
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

$tituloPagina = $tituloPagina ?? 'Consulta de Habilidades Artísticas';
$titulo = $titulo ?? $tituloPagina;

$busqueda = (string) ($busqueda ?? '');

/* Se guarda aquí: más abajo hay bucles que reutilizan $textoRespuesta. */
$filtroTextoRespuesta = (string) ($textoRespuesta ?? '');
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

$urlConsulta = url('/bienestar/consultas/habilidades-artisticas');

/*
|--------------------------------------------------------------------------
| Catálogo de preguntas de Habilidades Artísticas
|--------------------------------------------------------------------------
*/

$preguntasHA = [
    'HA-01' => '¿Tiene algún talento, habilidad o interés artístico o cultural que le gustaría compartir?',
    'HA-02' => '¿En cuáles de las siguientes actividades artísticas o culturales tiene habilidades o experiencia?',
    'HA-03' => '¿Toca actualmente algún instrumento musical?',
    'HA-04' => '¿Qué instrumento o instrumentos musicales toca?',
    'HA-05' => '¿En cuál de estas actividades artísticas le gustaría aprender o mejorar sus habilidades?',
    'HA-06' => '¿Ha participado en grupos, presentaciones, concursos o eventos artísticos y culturales?',
    'HA-07' => '¿Le gustaría participar en grupos o actividades artísticas y culturales organizadas por el SENA?',
    'HA-08' => '¿Qué dificultades tendría para participar en actividades artísticas o culturales?',
    'HA-09' => '¿En qué horarios preferiría participar en actividades artísticas o culturales?',
    'HA-10' => '¿Qué talento, actividad o iniciativa artística le gustaría desarrollar o proponer en el SENA?',
];

$normalizarCodigoHA = static function (string $codigo): string {
    return strtoupper(str_replace('_', '-', trim($codigo)));
};

$esCodigoHA = static function (string $codigo): bool {
    return preg_match('/^HA(?:-|$)/', $codigo) === 1;
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
        '/^(?:pregunta\s*)?(?:ha[-_\s]*)?\d+(?:[-_\s]*frecuencia)?$/iu',
        $texto
    ) === 1;
};

/*
|--------------------------------------------------------------------------
| Nombre de referencia de una pregunta
|--------------------------------------------------------------------------
*/

$nombrePregunta = static function (string $codigo) use (
    $preguntasHA,
    $normalizarCodigoHA
): string {
    $codigo = $normalizarCodigoHA($codigo);

    return $preguntasHA[$codigo] ?? $codigo;
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
| Normalización de respuestas individuales: únicamente códigos HA
|--------------------------------------------------------------------------
*/

$normalizarRespuestasHA = static function (mixed $datos) use (
    $normalizarCodigoHA,
    $esCodigoHA
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
        $normalizarCodigoHA,
        $esCodigoHA
    ): void {
        foreach ($elementos as $clave => $valor) {
            $claveTexto = is_string($clave) ? trim($clave) : '';
            $claveNormalizada = $normalizarCodigoHA($claveTexto);

            if (is_array($valor)) {
                $codigo = $valor['codigo']
                    ?? $valor['codigo_pregunta']
                    ?? $valor['pregunta_codigo']
                    ?? $valor['id_pregunta_codigo']
                    ?? null;

                if ($codigo !== null && trim((string) $codigo) !== '') {
                    $codigo = $normalizarCodigoHA((string) $codigo);

                    if ($esCodigoHA($codigo)) {
                        $resultado[$codigo] = $valor['respuesta']
                            ?? $valor['valor']
                            ?? $valor['respuesta_texto']
                            ?? $valor['opcion']
                            ?? $valor['respuesta_json']
                            ?? null;
                    }

                    continue;
                }

                if ($claveTexto !== '' && $esCodigoHA($claveNormalizada)) {
                    $resultado[$claveNormalizada] = $valor;
                    continue;
                }

                $recorrer($valor);
                continue;
            }

            if ($claveTexto !== '' && $esCodigoHA($claveNormalizada)) {
                $resultado[$claveNormalizada] = $valor;
            }
        }
    };

    $recorrer($datos);

    return $resultado;
};

/*
|--------------------------------------------------------------------------
| Estadísticas: exclusivamente preguntas HA
|--------------------------------------------------------------------------
|
| El controlador entrega filas planas (una por pregunta y respuesta) con
| código, respuesta y total. Aquí se agrupan por pregunta para mostrarlas
| igual que en Promoción y Prevención. También se acepta el formato ya
| agrupado (clave "respuestas").
*/

$estadisticasHA = [];

foreach ($estadisticas as $clave => $estadistica) {
    if (!is_array($estadistica)) {
        continue;
    }

    $codigoPregunta = $normalizarCodigoHA((string) (
        $estadistica['codigo_pregunta']
        ?? $estadistica['codigo']
        ?? $estadistica['pregunta_codigo']
        ?? $estadistica['id_pregunta_codigo']
        ?? (is_string($clave) ? $clave : '')
    ));

    if ($codigoPregunta === '' || !$esCodigoHA($codigoPregunta)) {
        continue;
    }

    if (!isset($estadisticasHA[$codigoPregunta])) {
        $estadisticasHA[$codigoPregunta] = [
            'codigo' => $codigoPregunta,
            'texto' => null,
            'respuestas' => [],
            'total_declarado' => 0,
            'sin_respuesta' => 0,
        ];
    }

    $textoDetectado = $resolverTextoPregunta($estadistica, $codigoPregunta);

    if (
        $estadisticasHA[$codigoPregunta]['texto'] === null
        && $textoDetectado !== $nombrePregunta($codigoPregunta)
    ) {
        $estadisticasHA[$codigoPregunta]['texto'] = $textoDetectado;
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

            $etiqueta = haLegible($valorRespuesta);

            $estadisticasHA[$codigoPregunta]['respuestas'][$etiqueta] =
                ($estadisticasHA[$codigoPregunta]['respuestas'][$etiqueta] ?? 0)
                + max(0, $cantidadRespuesta);
        }

        $estadisticasHA[$codigoPregunta]['total_declarado'] +=
            (int) ($estadistica['total_respuestas'] ?? 0);

        $estadisticasHA[$codigoPregunta]['sin_respuesta'] +=
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

    $etiqueta = haLegible($valorRespuesta);

    $estadisticasHA[$codigoPregunta]['respuestas'][$etiqueta] =
        ($estadisticasHA[$codigoPregunta]['respuestas'][$etiqueta] ?? 0)
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

foreach ($estadisticasHA as $codigo => $estadistica) {
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
| Búsqueda por respuestas de Habilidades Artísticas
|--------------------------------------------------------------------------
*/

$catalogoHA = is_array($catalogoHA ?? null) ? $catalogoHA : [];
$filtrosRapidos = is_array($filtrosRapidos ?? null) ? $filtrosRapidos : [];
$haPregunta = (string) ($haPregunta ?? '');
$haRespuesta = (string) ($haRespuesta ?? '');
$hayFiltrosRespuesta = (bool) ($hayFiltrosRespuesta ?? false);
$aprendicesFiltrados = is_array($aprendicesFiltrados ?? null)
    ? $aprendicesFiltrados
    : [];
$totalConRespuestasHA = (int) ($totalConRespuestasHA ?? 0);
$maxFilasVista = (int) ($maxFilasVista ?? 500);

$opcionesJsonHA = JSON_UNESCAPED_UNICODE
    | JSON_HEX_TAG
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
    | JSON_HEX_AMP;

/* Filtros rápidos que se muestran como lista desplegable. */
$filtrosRapidosConfig = [
    'HA-05' => 'Actividad que le gustaría aprender o mejorar',
    'HA-02' => 'Actividad en la que tiene habilidad o experiencia',
    'HA-04' => 'Instrumento que toca',
    'HA-03' => 'Toca actualmente un instrumento',
    'HA-07' => 'Participaría en grupos o actividades del SENA',
    'HA-06' => 'Ha participado en grupos, concursos o eventos',
    'HA-09' => 'Horario preferido',
    'HA-08' => 'Dificultades para participar',
    'HA-01' => 'Talento o interés artístico para compartir',
];

$minusculasHA = static function (string $texto): string {
    $texto = trim($texto);

    $texto = function_exists('mb_strtolower')
        ? mb_strtolower($texto, 'UTF-8')
        : strtolower($texto);

    return strtr($texto, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o',
        'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
    ]);
};

$mismoTexto = static function (string $a, string $b) use ($minusculasHA): bool {
    return $minusculasHA($a) === $minusculasHA($b);
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
    'estado' => $estadoCuestionario,
    'ha' => $filtrosRapidos,
    'ha_pregunta' => $haPregunta,
    'ha_respuesta' => $haRespuesta,
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
$etiquetasHA = static function (array $fila, string $codigo): string {
    $opciones = $fila['respuestas_ha'][$codigo] ?? [];

    if ($opciones === []) {
        return '<span class="ha-muted">—</span>';
    }

    $html = '';

    foreach ($opciones as $opcion) {
        $html .= '<span class="ha-tag">' . haEsc($opcion) . '</span>';
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

if ($haPregunta !== '') {
    $filtrosActivosTexto[] = $haPregunta . ': '
        . ($haRespuesta !== '' ? $haRespuesta : 'cualquier respuesta');
}

if ($filtroTextoRespuesta !== '') {
    $filtrosActivosTexto[] = 'Contiene «' . $filtroTextoRespuesta . '»'
        . ($haPregunta !== '' ? ' en ' . $haPregunta : ' en cualquier respuesta');
}

/* Catálogo para la lista dependiente (pregunta → respuestas) en JavaScript. */
$catalogoJs = [];

foreach ($catalogoHA as $codigoCatalogo => $datosCatalogo) {
    $nombresOpciones = array_column($datosCatalogo['opciones'] ?? [], 'nombre');
    natcasesort($nombresOpciones);

    $catalogoJs[$codigoCatalogo] = [
        'libre' => !empty($datosCatalogo['libre']),
        'opciones' => array_values($nombresOpciones),
    ];
}

$catalogoJsJson = json_encode($catalogoJs, $opcionesJsonHA);
$haRespuestaJson = json_encode($haRespuesta, $opcionesJsonHA);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= haEsc($tituloPagina) ?> | SITA</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --ha-primary: #174a78;
            --ha-primary-dark: #103653;
            --ha-secondary: #2676a8;
            --ha-bg: #f3f6fa;
            --ha-card: #ffffff;
            --ha-text: #263445;
            --ha-muted: #64748b;
            --ha-border: #dfe7ef;
            --ha-green: #16835d;
            --ha-orange: #bd7614;
            --ha-red: #b83c48;
            --ha-radius: 14px;
        }

        .ha-page {
            background: var(--ha-bg);
            min-height: 75vh;
            padding: 24px 0 34px;
            color: var(--ha-text);
            font-family: Arial, Helvetica, sans-serif;
        }

        .ha-container {
            width: min(1450px, calc(100% - 32px));
            margin: 0 auto;
        }

        .ha-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .ha-header h1 {
            margin: 0 0 7px;
            font-size: clamp(23px, 3vw, 31px);
            color: var(--ha-primary-dark);
        }

        .ha-header p {
            margin: 0;
            color: var(--ha-muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .ha-card {
            background: var(--ha-card);
            border: 1px solid var(--ha-border);
            border-radius: var(--ha-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(20, 52, 80, .045);
            min-width: 0;
        }

        .ha-card h2 {
            color: var(--ha-primary-dark);
            font-size: 19px;
            margin: 0 0 16px;
        }

        .ha-card h3 {
            color: var(--ha-primary);
            font-size: 16px;
            margin: 0 0 12px;
        }

        .ha-summary-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .ha-stat {
            background: #ffffff;
            border: 1px solid var(--ha-border);
            border-radius: 13px;
            padding: 17px;
            min-width: 0;
        }

        .ha-stat-label {
            color: var(--ha-muted);
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 8px;
        }

        .ha-stat-value {
            color: var(--ha-primary-dark);
            font-size: 29px;
            line-height: 1.15;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .ha-stat.completed .ha-stat-value { color: var(--ha-green); }
        .ha-stat.draft .ha-stat-value { color: var(--ha-orange); }
        .ha-stat.pending .ha-stat-value { color: var(--ha-red); }

        .ha-progress {
            height: 10px;
            background: #e7edf4;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 12px;
        }

        .ha-progress > span {
            display: block;
            height: 100%;
            width: <?= number_format($porcentajeAvance, 2, '.', '') ?>%;
            background: var(--ha-green);
            border-radius: inherit;
        }

        .ha-form-grid {
            display: grid;
            grid-template-columns: 2fr 1.1fr 1fr auto;
            gap: 13px;
            align-items: end;
        }

        .ha-field label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 7px;
            color: #334155;
        }

        .ha-field input,
        .ha-field select {
            box-sizing: border-box;
            width: 100%;
            min-height: 43px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            color: var(--ha-text);
            font-size: 14px;
        }

        .ha-button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
            min-height: 42px;
            padding: 10px 16px;
            border: 0;
            border-radius: 8px;
            background: var(--ha-primary);
            color: #ffffff !important;
            text-decoration: none !important;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
        }

        .ha-button:hover {
            background: var(--ha-primary-dark);
        }

        .ha-button.secondary {
            background: #e8eef5;
            color: var(--ha-primary-dark) !important;
        }

        .ha-button.small {
            min-height: 32px;
            padding: 7px 10px;
            font-size: 12px;
        }

        .ha-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .ha-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .ha-table th,
        .ha-table td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid var(--ha-border);
            vertical-align: middle;
        }

        .ha-table th {
            background: #f3f7fb;
            color: #334155;
            font-weight: 800;
            white-space: nowrap;
        }

        .ha-table tbody tr:hover {
            background: #f8fbfe;
        }

        .ha-badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .ha-badge.completed {
            background: #d9f4e7;
            color: #116444;
        }

        .ha-badge.draft {
            background: #fff0d2;
            color: #925400;
        }

        .ha-badge.pending {
            background: #fde2e4;
            color: #a12835;
        }

        .ha-muted {
            color: var(--ha-muted);
        }

        .ha-empty {
            padding: 25px 14px;
            text-align: center;
            color: var(--ha-muted);
            line-height: 1.6;
        }

        .ha-charts-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-top: 18px;
            align-items: stretch;
        }

        .ha-chart-card {
            background: #ffffff;
            border: 1px solid var(--ha-border);
            border-radius: 12px;
            padding: 18px;
            min-width: 0;
            box-shadow: 0 3px 12px rgba(20, 52, 80, .04);
        }

        .ha-chart-card h3 {
            color: var(--ha-primary-dark);
            font-size: 16px;
            margin: 0 0 8px;
        }

        .ha-chart-description {
            color: var(--ha-muted);
            font-size: 13px;
            line-height: 1.6;
            margin: 0 0 16px;
        }

        .ha-chart-container {
            position: relative;
            width: 100%;
            height: 310px;
        }

        .ha-chart-container.ha-chart-doughnut {
            max-width: 390px;
            margin: 0 auto;
        }

        .ha-chart-filter {
            margin-bottom: 16px;
        }

        .ha-chart-filter label {
            display: block;
            color: #334155;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .ha-chart-filter select {
            width: 100%;
            min-height: 42px;
            box-sizing: border-box;
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            color: var(--ha-text);
            font-size: 14px;
        }

        
        .ha-chart-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 180px;
    padding: 15px;
    text-align: center;
    color: var(--ha-muted);
    font-size: 13px;
    line-height: 1.6;
}

        /* Ocultar correctamente los mensajes cuando no corresponden */
        .ha-chart-empty[hidden] {
    display: none !important;
}

        .ha-chart-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .ha-chart-summary-item {
            border: 1px solid var(--ha-border);
            border-radius: 9px;
            padding: 11px;
            background: #f8fafc;
            min-width: 0;
        }

        .ha-chart-summary-item span {
            display: block;
            color: var(--ha-muted);
            font-size: 11px;
            margin-bottom: 6px;
            line-height: 1.4;
        }

        .ha-chart-summary-item strong {
            display: block;
            font-size: 20px;
            color: var(--ha-primary-dark);
            overflow-wrap: anywhere;
        }

        .ha-section {
            border: 1px solid var(--ha-border);
            border-radius: 11px;
            margin: 14px 0;
            overflow: hidden;
            background: #ffffff;
        }

        .ha-section-title {
            padding: 14px 16px;
            background: #edf4fa;
            color: var(--ha-primary-dark);
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .ha-section-body {
            padding: 14px;
        }

        .ha-question {
            padding: 16px;
            border-top: 1px solid var(--ha-border);
        }

        .ha-question:first-child {
            border-top: 0;
        }

        .ha-question-title {
            font-weight: 700;
            margin-bottom: 11px;
            line-height: 1.5;
        }

        .ha-response-row {
            display: grid;
            grid-template-columns: minmax(150px, 1.5fr) minmax(80px, .35fr) minmax(100px, 1fr);
            gap: 12px;
            align-items: center;
            padding: 8px 0;
            border-top: 1px solid #edf1f5;
            font-size: 13px;
        }

        .ha-bar {
            height: 8px;
            background: #e9eef4;
            border-radius: 999px;
            overflow: hidden;
        }

        .ha-bar span {
            display: block;
            height: 100%;
            background: var(--ha-secondary);
            border-radius: inherit;
        }

        .ha-profile-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .ha-profile-item {
            padding: 12px;
            border: 1px solid var(--ha-border);
            border-radius: 9px;
            min-width: 0;
            background: #ffffff;
        }

        .ha-profile-item .label {
            color: var(--ha-muted);
            font-size: 12px;
            margin-bottom: 5px;
        }

        .ha-profile-item .value {
            font-weight: 700;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .ha-answer {
            padding: 13px 14px;
            border: 1px solid var(--ha-border);
            border-radius: 9px;
            margin-bottom: 10px;
            background: #ffffff;
        }

        .ha-answer:last-child {
            margin-bottom: 0;
        }

        .ha-answer .question {
            font-weight: 700;
            margin-bottom: 7px;
            color: var(--ha-primary-dark);
            line-height: 1.5;
        }

        .ha-answer .value {
            font-size: 14px;
            line-height: 1.7;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        .ha-note {
            border-left: 4px solid #d6a13b;
            background: #fff9e9;
            color: #654b16;
            padding: 12px 14px;
            border-radius: 6px;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .ha-detail-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin: 24px 0 10px;
        }

        .ha-footer {
            text-align: center;
            padding: 14px 10px;
            color: #64748b;
            font-size: 12px;
        }

        /* ---------- Búsqueda por respuestas ---------- */
        .ha-filters-extra {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px dashed var(--ha-border);
        }

        .ha-filter-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 13px;
        }

        .ha-filter-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .ha-field .ha-hint {
            display: block;
            margin-top: 5px;
            font-size: 11px;
            color: var(--ha-muted);
            line-height: 1.4;
        }

        .ha-field select:disabled {
            background: #f1f5f9;
            color: #94a3b8;
        }

        .ha-tag {
            display: inline-block;
            padding: 3px 9px;
            margin: 2px 4px 2px 0;
            border-radius: 999px;
            background: #e8f1f9;
            color: var(--ha-primary);
            font-size: 12px;
            font-weight: 700;
            line-height: 1.4;
        }

        .ha-tag.match {
            background: #d9f4e7;
            color: #116444;
        }

        .ha-chip-block + .ha-chip-block {
            margin-top: 18px;
        }

        .ha-chip-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .ha-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 12px;
            border: 1px solid var(--ha-border);
            border-radius: 999px;
            background: #ffffff;
            color: var(--ha-primary-dark) !important;
            text-decoration: none !important;
            font-size: 13px;
            font-weight: 700;
        }

        .ha-chip:hover {
            border-color: var(--ha-secondary);
            background: #f3f8fc;
        }

        .ha-chip span {
            padding: 1px 7px;
            border-radius: 999px;
            background: #e8eef5;
            font-size: 11px;
        }

        .ha-chip.active {
            background: var(--ha-primary);
            border-color: var(--ha-primary);
            color: #ffffff !important;
        }

        .ha-chip.active span {
            background: rgba(255, 255, 255, .22);
        }

        .ha-results-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .ha-results-head h2 {
            margin-bottom: 6px;
        }

        .ha-active-filters {
            margin: 0 0 14px;
            padding: 10px 12px;
            background: #f3f8fc;
            border: 1px solid var(--ha-border);
            border-radius: 9px;
            font-size: 13px;
            line-height: 1.7;
        }

        @media (max-width: 1100px) {
            .ha-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .ha-filter-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1100px) {
            .ha-summary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .ha-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 800px) {
            .ha-charts-grid {
                grid-template-columns: 1fr;
            }

            .ha-chart-container {
                height: 280px;
            }
        }

        @media (max-width: 700px) {
            .ha-summary-grid,
            .ha-profile-grid,
            .ha-form-grid {
                grid-template-columns: 1fr;
            }

            .ha-response-row {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            .ha-card {
                padding: 15px;
            }

            .ha-container {
                width: calc(100% - 20px);
            }

            .ha-chart-summary {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 420px) {
            .ha-summary-grid {
                grid-template-columns: 1fr;
            }

            .ha-chart-card {
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

<main class="ha-page">
    <div class="ha-container">

        <header class="ha-header">
            <div>
                <h1><?= haEsc($titulo) ?></h1>
                <p>
                    Consulta de Habilidades Artísticas de los aprendices,
                    con estadísticas y detalle individual del componente HA.
                </p>
            </div>

            <a class="ha-button secondary" href="<?= haEsc($urlConsulta) ?>">
                Limpiar filtros
            </a>
        </header>

        <section class="ha-summary-grid" aria-label="Resumen de cuestionarios">

            <div class="ha-stat">
                <div class="ha-stat-label">Total de aprendices</div>
                <div class="ha-stat-value">
                    <?= number_format($totalAprendices, 0, ',', '.') ?>
                </div>
                <div class="ha-muted" style="font-size:12px;margin-top:6px">
                    Según los datos recibidos
                </div>
            </div>

            <div class="ha-stat completed">
                <div class="ha-stat-label">Cuestionarios completados</div>
                <div class="ha-stat-value">
                    <?= number_format($totalCompletados, 0, ',', '.') ?>
                </div>
                <div class="ha-muted" style="font-size:12px;margin-top:6px">
                    Registro finalizado
                </div>
            </div>

            <div class="ha-stat draft">
                <div class="ha-stat-label">En proceso</div>
                <div class="ha-stat-value">
                    <?= number_format($totalBorradores, 0, ',', '.') ?>
                </div>
                <div class="ha-muted" style="font-size:12px;margin-top:6px">
                    Cuestionarios en borrador
                </div>
            </div>

            <div class="ha-stat pending">
                <div class="ha-stat-label">Sin iniciar</div>
                <div class="ha-stat-value">
                    <?= number_format($totalPendientes, 0, ',', '.') ?>
                </div>
                <div class="ha-muted" style="font-size:12px;margin-top:6px">
                    Sin completar
                </div>
            </div>

            <div class="ha-stat">
                <div class="ha-stat-label">Avance de diligenciamiento</div>
                <div class="ha-stat-value">
                    <?= number_format($porcentajeAvance, 1, ',', '.') ?>%
                </div>
                <div
                    class="ha-progress"
                    role="progressbar"
                    aria-valuenow="<?= haEsc((string) round($porcentajeAvance, 1)) ?>"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >
                    <span></span>
                </div>
            </div>

        </section>

        <section class="ha-card">
            <h2>Buscar y filtrar aprendices</h2>

            <form method="GET" action="<?= haEsc($urlConsulta) ?>">
                <div class="ha-form-grid">

                    <div class="ha-field">
                        <label for="ha_busqueda">Nombre o documento</label>
                        <input
                            type="text"
                            name="busqueda"
                            id="ha_busqueda"
                            value="<?= haEsc($busqueda) ?>"
                            placeholder="Escriba para buscar..."
                        >
                    </div>

                    <div class="ha-field">
                        <label for="ha_ficha">Ficha</label>
                        <select name="id_ficha" id="ha_ficha">
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
                                    <?= haEsc(
                                        $fichaNumero .
                                        ($programa !== '' ? ' - ' . $programa : '')
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="ha-field">
                        <label for="ha_estado">Estado del cuestionario</label>
                        <select name="estado" id="ha_estado">
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
                        <button type="submit" class="ha-button" style="width:100%">
                            Consultar
                        </button>
                    </div>

                </div>

                <div class="ha-filters-extra">
                    <h3>Buscar por respuestas de Habilidades Artísticas</h3>

                    <p
                        class="ha-muted"
                        style="font-size:13px;line-height:1.6;margin:-4px 0 14px"
                    >
                        Combine los filtros para saber quién toca un instrumento, qué actividad artística desea aprender
                        o qué respondió en cada pregunta. Los filtros se
                        suman entre sí.
                    </p>

                    <div class="ha-filter-grid">
                        <?php foreach ($filtrosRapidosConfig as $codigoFiltro => $etiquetaFiltro): ?>
                            <?php
                            $datosFiltro = $catalogoHA[$codigoFiltro] ?? null;

                            if ($datosFiltro === null || empty($datosFiltro['opciones'])) {
                                continue;
                            }

                            $valorActual = (string) ($filtrosRapidos[$codigoFiltro] ?? '');
                            $idCampo = 'ha_' . strtolower(str_replace('-', '_', $codigoFiltro));
                            ?>

                            <div class="ha-field">
                                <label for="<?= haEsc($idCampo) ?>">
                                    <?= haEsc($etiquetaFiltro) ?>
                                    <span class="ha-muted">(<?= haEsc($codigoFiltro) ?>)</span>
                                </label>

                                <select
                                    name="ha[<?= haEsc($codigoFiltro) ?>]"
                                    id="<?= haEsc($idCampo) ?>"
                                >
                                    <option value="">Todas</option>

                                    <?php foreach ($ordenarOpciones($datosFiltro) as $opcionFiltro): ?>
                                        <option
                                            value="<?= haEsc($opcionFiltro['nombre']) ?>"
                                            <?= ($valorActual !== '' && $mismoTexto($valorActual, (string) $opcionFiltro['nombre'])) ? 'selected' : '' ?>
                                        >
                                            <?= haEsc($opcionFiltro['nombre']) ?>
                                            (<?= (int) $opcionFiltro['cantidad'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="ha-filter-grid" style="margin-top:14px">

                        <div class="ha-field">
                            <label for="ha_pregunta">Cualquier pregunta HA</label>
                            <select name="ha_pregunta" id="ha_pregunta">
                                <option value="">Seleccione una pregunta</option>

                                <?php foreach ($catalogoHA as $codigoPregunta => $datosPregunta): ?>
                                    <option
                                        value="<?= haEsc($codigoPregunta) ?>"
                                        <?= $haPregunta === $codigoPregunta ? 'selected' : '' ?>
                                    >
                                        <?= haEsc(
                                            $codigoPregunta . ' - '
                                            . $nombrePregunta((string) $codigoPregunta)
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="ha-field">
                            <label for="ha_respuesta">Respuesta de esa pregunta</label>
                            <select name="ha_respuesta" id="ha_respuesta" disabled>
                                <option value="">Seleccione primero una pregunta</option>
                            </select>
                            <span class="ha-hint">
                                Si deja «Cualquier respuesta», se listan quienes
                                contestaron la pregunta.
                            </span>
                        </div>

                        <div class="ha-field">
                            <label for="texto_respuesta">
                                Palabra dentro de las respuestas
                            </label>
                            <input
                                type="text"
                                name="texto_respuesta"
                                id="texto_respuesta"
                                value="<?= haEsc($filtroTextoRespuesta) ?>"
                                placeholder="Ej: guitarra, danza, teatro, tarde..."
                            >
                            <span class="ha-hint">
                                Ignora tildes y mayúsculas. Si eligió una pregunta,
                                solo busca en esa; si no, en todas las respuestas HA.
                            </span>
                        </div>
                    </div>

                    <div class="ha-filter-actions">
                        <button type="submit" class="ha-button">
                            Aplicar filtros
                        </button>

                        <a class="ha-button secondary" href="<?= haEsc($urlConsulta) ?>">
                            Limpiar todo
                        </a>
                    </div>
                </div>
            </form>
        </section>

        <?php
        $bloquesChips = [
            'HA-05' => 'Actividades que les gustaría aprender o mejorar',
            'HA-02' => 'Actividades en las que tienen habilidad o experiencia',
            'HA-04' => 'Instrumentos que tocan',
            'HA-09' => 'Horarios preferidos',
        ];

        $hayChips = false;

        foreach ($bloquesChips as $codigoChip => $tituloChip) {
            if (!empty($catalogoHA[$codigoChip]['opciones'])) {
                $hayChips = true;
                break;
            }
        }
        ?>

        <?php if ($hayChips): ?>
            <section class="ha-card">
                <h2>Intereses más frecuentes</h2>

                <p
                    class="ha-muted"
                    style="font-size:13px;line-height:1.6;margin-top:-8px"
                >
                    Haga clic en una opción para ver qué aprendices la
                    eligieron. Vuelva a hacer clic en la opción activa
                    para quitar el filtro.
                </p>

                <?php foreach ($bloquesChips as $codigoChip => $tituloChip): ?>
                    <?php
                    $opcionesChip = $catalogoHA[$codigoChip]['opciones'] ?? [];

                    if ($opcionesChip === []) {
                        continue;
                    }

                    $valorChipActivo = (string) ($filtrosRapidos[$codigoChip] ?? '');
                    $totalOpcionesChip = count($opcionesChip);
                    ?>

                    <div class="ha-chip-block">
                        <h3>
                            <?= haEsc($tituloChip) ?>
                            <span class="ha-muted" style="font-weight:400;font-size:12px">
                                (<?= haEsc($codigoChip) ?> ·
                                <?= (int) $catalogoHA[$codigoChip]['respondieron'] ?>
                                respuestas)
                            </span>
                        </h3>

                        <div class="ha-chip-list">
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
                                    class="ha-chip <?= $activo ? 'active' : '' ?>"
                                    href="<?= haEsc($construirUrl(['ha' => $filtrosEnlace]) . '#resultados-respuestas') ?>"
                                >
                                    <?= haEsc($opcionChip['nombre']) ?>
                                    <span><?= (int) $opcionChip['cantidad'] ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($totalOpcionesChip > 15): ?>
                            <p class="ha-muted" style="font-size:12px;margin:8px 0 0">
                                Se muestran las 15 más frecuentes de
                                <?= (int) $totalOpcionesChip ?>. Use los filtros
                                de arriba para ver el resto.
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <section class="ha-card" id="resultados-respuestas">
            <div class="ha-results-head">
                <div>
                    <h2>Aprendices por respuestas de Habilidades Artísticas</h2>

                    <p class="ha-muted" style="font-size:13px;line-height:1.6;margin:0">
                        <strong><?= number_format(count($aprendicesFiltrados), 0, ',', '.') ?></strong>
                        <?= count($aprendicesFiltrados) === 1 ? 'aprendiz' : 'aprendices' ?>
                        <?= $hayFiltrosRespuesta ? 'cumplen los filtros' : 'con respuestas de Habilidades Artísticas' ?>
                        (de <?= number_format($totalConRespuestasHA, 0, ',', '.') ?>
                        con respuestas HA registradas).
                    </p>
                </div>

                <?php if ($aprendicesFiltrados !== []): ?>
                    <a
                        class="ha-button secondary"
                        href="<?= haEsc($construirUrl(['exportar' => 'csv'])) ?>"
                    >
                        Exportar a CSV
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($filtrosActivosTexto !== []): ?>
                <div class="ha-active-filters">
                    <strong>Filtros activos:</strong>
                    <?php foreach ($filtrosActivosTexto as $textoActivo): ?>
                        <span class="ha-tag"><?= haEsc($textoActivo) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($aprendicesFiltrados === []): ?>

                <div class="ha-empty">
                    <strong>No hay aprendices con esos criterios.</strong>
                    <br>
                    Pruebe con menos filtros, otra ficha o una palabra más corta.
                </div>

            <?php else: ?>

                <div class="ha-table-wrap">
                    <table class="ha-table">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Aprendiz</th>
                                <th>Ficha</th>
                                <th>Estado</th>
                                <th>Tiene experiencia en</th>
                                <th>Instrumentos</th>
                                <th>Le gustaría aprender</th>
                                <th>Horarios</th>
                                <?php if ($hayFiltrosRespuesta): ?>
                                    <th>Respuestas que coinciden</th>
                                <?php endif; ?>
                                <th>Consulta</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php foreach (array_slice($aprendicesFiltrados, 0, $maxFilasVista) as $filaHA): ?>
                            <?php
                            $idAprendizFila = (int) ($filaHA['id_aprendiz'] ?? 0);
                            $nombreFila = $obtenerNombreAprendiz($filaHA);
                            $estadoFila = $obtenerEstado($filaHA);
                            $claseFila = $obtenerClaseEstado($estadoFila);
                            ?>

                            <tr>
                                <td><?= haEsc($filaHA['documento'] ?? 'No registrado') ?></td>

                                <td>
                                    <strong>
                                        <?= haEsc($nombreFila !== '' ? $nombreFila : 'Nombre no disponible') ?>
                                    </strong>
                                </td>

                                <td><?= haEsc($filaHA['numero_ficha'] ?? '—') ?></td>

                                <td>
                                    <span class="ha-badge <?= haEsc($claseFila) ?>">
                                        <?= haEsc($estadoFila) ?>
                                    </span>
                                </td>

                                <td><?= $etiquetasHA($filaHA, 'HA-02') ?></td>
                                <td><?= $etiquetasHA($filaHA, 'HA-04') ?></td>
                                <td><?= $etiquetasHA($filaHA, 'HA-05') ?></td>
                                <td><?= $etiquetasHA($filaHA, 'HA-09') ?></td>

                                <?php if ($hayFiltrosRespuesta): ?>
                                    <td>
                                        <?php foreach (($filaHA['coincidencias'] ?? []) as $coincidencia): ?>
                                            <span class="ha-tag match"><?= haEsc($coincidencia) ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                <?php endif; ?>

                                <td>
                                    <a
                                        class="ha-button small"
                                        href="<?= haEsc($construirUrl(['id_aprendiz' => $idAprendizFila]) . '#detalle-aprendiz') ?>"
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
                    <p class="ha-muted" style="font-size:12px;margin-top:12px">
                        Se muestran los primeros
                        <?= (int) $maxFilasVista ?> resultados. Use «Exportar a CSV»
                        para obtener la lista completa.
                    </p>
                <?php endif; ?>

            <?php endif; ?>
        </section>

        <section class="ha-card">
            <h2>Listado general de aprendices (estado del cuestionario)</h2>

            <p
                class="ha-muted"
                style="font-size:13px;margin-top:-8px;margin-bottom:15px"
            >
                Se muestran aprendices que coinciden con los filtros
                seleccionados.
            </p>

            <?php if ($aprendices === []): ?>

                <div class="ha-empty">
                    <strong>No se encontraron aprendices.</strong>
                    <br>
                    Verifique los datos ingresados o seleccione otros filtros.
                </div>

            <?php else: ?>

                <div class="ha-table-wrap">
                    <table class="ha-table">
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
                                    <?= haEsc(
                                        $fila['documento']
                                        ?? $fila['numero_documento']
                                        ?? 'No registrado'
                                    ) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= haEsc(
                                            $nombre !== ''
                                                ? $nombre
                                                : 'Nombre no disponible'
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= haEsc(
                                        $fila['numero_ficha']
                                        ?? $fila['ficha']
                                        ?? '—'
                                    ) ?>
                                </td>

                                <td>
                                    <?= haEsc(
                                        $fila['programa']
                                        ?? $fila['nombre_programa']
                                        ?? '—'
                                    ) ?>
                                </td>

                                <td>
                                    <span class="ha-badge <?= haEsc($estadoClase) ?>">
                                        <?= haEsc($estadoTexto) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= haEsc(haFecha(
                                        $fila['fecha_actualizacion']
                                        ?? $fila['fecha_completado']
                                        ?? null
                                    )) ?>
                                </td>

                                <td>
                                    <a
                                        class="ha-button small"
                                        href="<?= haEsc($urlDetalle) ?>"
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
                    <p class="ha-muted" style="font-size:12px;margin-top:12px">
                        Se muestran hasta 200 registros. Use los filtros
                        para precisar la búsqueda.
                    </p>
                <?php endif; ?>

            <?php endif; ?>
        </section>

        <section class="ha-card" id="graficas-habilidades-artisticas">
            <h2>Panel gráfico de Habilidades Artísticas</h2>

            <p class="ha-muted" style="font-size:13px;line-height:1.6">
                Consulte el estado de los cuestionarios y explore la
                distribución de respuestas por pregunta del componente HA.
            </p>

            <div class="ha-charts-grid">

                <article class="ha-chart-card">
                    <h3>Estado de los cuestionarios</h3>

                    <p class="ha-chart-description">
                        Distribución de cuestionarios completados,
                        en proceso y sin iniciar.
                    </p>

                    <div
                        class="ha-chart-container ha-chart-doughnut"
                        id="haContenedorEstados"
                    >
                        <canvas
                            id="haChartEstados"
                            role="img"
                            aria-label="Gráfica circular del estado de los cuestionarios"
                        ></canvas>
                    </div>

                    <div id="haChartEstadosVacio" class="ha-chart-empty" hidden>
                        No hay datos suficientes para mostrar esta gráfica.
                    </div>

                    <div class="ha-chart-summary">
                        <div class="ha-chart-summary-item">
                            <span>Completados</span>
                            <strong>
                                <?= number_format($totalCompletados, 0, ',', '.') ?>
                            </strong>
                        </div>

                        <div class="ha-chart-summary-item">
                            <span>En proceso</span>
                            <strong>
                                <?= number_format($totalBorradores, 0, ',', '.') ?>
                            </strong>
                        </div>

                        <div class="ha-chart-summary-item">
                            <span>Sin iniciar</span>
                            <strong>
                                <?= number_format($totalPendientes, 0, ',', '.') ?>
                            </strong>
                        </div>
                    </div>
                </article>

                <article class="ha-chart-card">
                    <h3>Respuestas por pregunta</h3>

                    <p class="ha-chart-description">
                        Seleccione una pregunta de Habilidades Artísticas
                        para comparar las cantidades registradas en sus
                        diferentes opciones de respuesta.
                    </p>

                    <div class="ha-chart-filter">
                        <label for="haPreguntaGrafica">
                            Pregunta de Habilidades Artísticas
                        </label>

                        <select id="haPreguntaGrafica">
                            <option value="">Seleccione una pregunta</option>

                            <?php foreach ($graficaPreguntas as $indice => $preguntaGrafica): ?>
                                <option value="<?= (int) $indice ?>">
                                    <?= haEsc(
                                        $preguntaGrafica['codigo']
                                        . ' - '
                                        . $preguntaGrafica['pregunta']
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="ha-chart-container" id="haContenedorRespuestas">
                        <canvas
                            id="haChartRespuestas"
                            role="img"
                            aria-label="Gráfica de barras de respuestas por pregunta"
                        ></canvas>
                    </div>

                    <div id="haChartRespuestasVacio" class="ha-chart-empty" hidden>
                        Seleccione una pregunta con respuestas registradas
                        para visualizar su distribución.
                    </div>
                </article>

            </div>
        </section>

        <section class="ha-card">
            <h2>Estadísticas de Habilidades Artísticas</h2>

            <p class="ha-muted" style="font-size:13px;line-height:1.6">
                Las estadísticas de esta sección corresponden únicamente
                a las preguntas identificadas con el código HA.
            </p>

            <?php if ($estadisticasHA === []): ?>

                <div class="ha-empty">
                    Todavía no hay respuestas disponibles para generar
                    estadísticas de Habilidades Artísticas con los permisos
                    y filtros actuales.
                </div>

            <?php else: ?>

                <div class="ha-section">
                    <div class="ha-section-title">
                        <span>Habilidades Artísticas</span>
                        <span><?= count($estadisticasHA) ?> preguntas</span>
                    </div>

                    <div class="ha-section-body">

                        <?php foreach ($estadisticasHA as $codigo => $estadistica): ?>
                            <?php
                            $respuestas = $estadistica['respuestas'];

                            $sumaRespuestas = array_sum($respuestas);

                            $denominador = $estadistica['total_declarado'] > 0
                                ? $estadistica['total_declarado']
                                : $sumaRespuestas;

                            $textoPregunta = $estadistica['texto']
                                ?? $nombrePregunta((string) $codigo);
                            ?>

                            <div class="ha-question">
                                <div class="ha-question-title">
                                    <?= haEsc($textoPregunta) ?>

                                    <span
                                        class="ha-muted"
                                        style="font-weight:400;font-size:12px"
                                    >
                                        (<?= haEsc((string) $codigo) ?>)
                                    </span>
                                </div>

                                <?php if ($respuestas === []): ?>

                                    <div class="ha-muted" style="font-size:13px">
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

                                        <div class="ha-response-row">
                                            <div>
                                                <?= haEsc((string) $textoRespuesta) ?>
                                            </div>

                                            <div>
                                                <strong>
                                                    <?= number_format($cantidad, 0, ',', '.') ?>
                                                </strong>

                                                <span class="ha-muted">
                                                    (<?= number_format($porcentaje, 1, ',', '.') ?>%)
                                                </span>
                                            </div>

                                            <div class="ha-bar">
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

            <section class="ha-card" id="detalle-aprendiz">
                <h2>Detalle individual del aprendiz</h2>

                <?php if ($aprendizSeleccionado === null): ?>

                    <div class="ha-empty">
                        No fue posible encontrar la información del aprendiz
                        solicitado. Verifique los filtros y la matrícula.
                    </div>

                <?php else: ?>

                    <div class="ha-note">
                        La información individual puede contener datos
                        personales y de bienestar. Consulte estos datos
                        únicamente para fines institucionales autorizados.
                    </div>

                    <div class="ha-profile-grid">

                        <div class="ha-profile-item">
                            <div class="label">Nombre completo</div>
                            <div class="value"><?= haEsc($nombreIndividual) ?></div>
                        </div>

                        <div class="ha-profile-item">
                            <div class="label">Documento</div>
                            <div class="value"><?= haEsc($documentoIndividual) ?></div>
                        </div>

                        <div class="ha-profile-item">
                            <div class="label">Ficha</div>
                            <div class="value">
                                <?= haEsc(
                                    ($numeroFichaIndividual !== null
                                        && $numeroFichaIndividual !== '')
                                        ? $numeroFichaIndividual
                                        : 'No registrada'
                                ) ?>
                            </div>
                        </div>

                        <div class="ha-profile-item">
                            <div class="label">Programa de formación</div>
                            <div class="value">
                                <?= haEsc(
                                    ($programaIndividual !== null
                                        && $programaIndividual !== '')
                                        ? $programaIndividual
                                        : 'No registrado'
                                ) ?>
                            </div>
                        </div>

                        <div class="ha-profile-item">
                            <div class="label">Correo electrónico</div>
                            <div class="value">
                                <?= haEsc(
                                    ($correoIndividual !== null
                                        && $correoIndividual !== '')
                                        ? $correoIndividual
                                        : 'No registrado'
                                ) ?>
                            </div>
                        </div>

                        <div class="ha-profile-item">
                            <div class="label">Estado del aprendiz</div>
                            <div class="value"><?= haEsc($estadoAprendiz) ?></div>
                        </div>

                    </div>

                    <div class="ha-detail-heading">
                        <h3 style="margin:0">
                            Cuestionarios y respuestas de Habilidades Artísticas
                        </h3>
                    </div>

                    <?php if ($cuestionarios === []): ?>

                        <div class="ha-empty">
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

                            $respuestasIndividuales = $normalizarRespuestasHA(
                                $fuenteRespuestas
                            );
                            ?>

                            <div class="ha-section">
                                <div class="ha-section-title">
                                    <span>Cuestionario <?= (int) ($indice + 1) ?></span>

                                    <span class="ha-badge <?= haEsc($claseIndividual) ?>">
                                        <?= haEsc($estadoIndividualTexto) ?>
                                    </span>
                                </div>

                                <div class="ha-section-body">
                                    <p
                                        class="ha-muted"
                                        style="font-size:12px;line-height:1.8"
                                    >
                                        <strong>Inicio:</strong>
                                        <?= haEsc(haFecha(
                                            $cuestionario['fecha_inicio'] ?? null
                                        )) ?>

                                        &nbsp; | &nbsp;

                                        <strong>Última actualización:</strong>
                                        <?= haEsc(haFecha(
                                            $cuestionario['fecha_actualizacion'] ?? null
                                        )) ?>

                                        &nbsp; | &nbsp;

                                        <strong>Finalización:</strong>
                                        <?= haEsc(haFecha(
                                            $cuestionario['fecha_completado'] ?? null
                                        )) ?>
                                    </p>

                                    <?php if ($respuestasIndividuales === []): ?>

                                        <div class="ha-empty">
                                            No hay respuestas identificadas
                                            para el componente HA en este
                                            cuestionario.
                                        </div>

                                    <?php else: ?>

                                        <?php foreach ($respuestasIndividuales as $codigo => $valor): ?>
                                            <div class="ha-answer">
                                                <div class="question">
                                                    <?= haEsc($nombrePregunta((string) $codigo)) ?>

                                                    <span
                                                        class="ha-muted"
                                                        style="font-size:11px;font-weight:400"
                                                    >
                                                        (<?= haEsc((string) $codigo) ?>)
                                                    </span>
                                                </div>

                                                <div class="value">
                                                    <?= haEsc(haLegible($valor)) ?>
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
                        class="ha-button secondary"
                        href="<?= haEsc($construirUrl() . '#resultados-respuestas') ?>"
                    >
                        Volver al listado
                    </a>
                </div>
            </section>

        <?php endif; ?>

    </div>
</main>

<footer class="ha-footer">
    SITA · Sistema de Trazabilidad de Aprendices
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const estados = <?= $graficaEstadosJson ?: '{}' ?>;
    const preguntas = <?= $graficaPreguntasJson ?: '[]' ?>;

    const canvasEstados = document.getElementById('haChartEstados');
    const contenedorEstados = document.getElementById('haContenedorEstados');
    const vacioEstados = document.getElementById('haChartEstadosVacio');

    const selectorPregunta = document.getElementById('haPreguntaGrafica');
    const canvasRespuestas = document.getElementById('haChartRespuestas');
    const contenedorRespuestas = document.getElementById('haContenedorRespuestas');
    const vacioRespuestas = document.getElementById('haChartRespuestasVacio');

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
/* Lista dependiente: pregunta HA -> respuestas disponibles. */
(function () {
    'use strict';

    const catalogo = <?= $catalogoJsJson ?: '{}' ?>;
    const respuestaInicial = <?= $haRespuestaJson ?: '""' ?>;

    const selectorPregunta = document.getElementById('ha_pregunta');
    const selectorRespuesta = document.getElementById('ha_respuesta');

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