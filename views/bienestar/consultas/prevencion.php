<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SITA - Consulta de Promoción y Prevención
| Archivo: views/bienestar/consultas/prevencion.php
|--------------------------------------------------------------------------
*/

if (!function_exists('pypEsc')) {
    function pypEsc(mixed $valor): string
    {
        if (is_array($valor) || is_object($valor)) {
            $valor = json_encode($valor, JSON_UNESCAPED_UNICODE);
        }

        return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('pypFecha')) {
    function pypFecha(mixed $fecha): string
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

if (!function_exists('pypValorLegible')) {
    function pypValorLegible(mixed $valor): string
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
            $esLista = $valor === array_values($valor);

            foreach ($valor as $clave => $elemento) {
                $prefijo = (!$esLista && is_string($clave) && trim($clave) !== '')
                    ? trim($clave) . ': '
                    : '';

                if (is_array($elemento)) {
                    $texto = pypValorLegible($elemento);

                    if ($texto !== 'Sin respuesta') {
                        $partes[] = $prefijo . $texto;
                    }
                } elseif (is_bool($elemento)) {
                    $partes[] = $prefijo . ($elemento ? 'Sí' : 'No');
                } elseif (is_scalar($elemento) && trim((string) $elemento) !== '') {
                    $partes[] = $prefijo . (string) $elemento;
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
                return pypValorLegible($decodificado);
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

$tituloPagina = $tituloPagina ?? 'Consulta de Promoción y Prevención';
$titulo = $titulo ?? $tituloPagina;

$busqueda = (string) ($busqueda ?? '');

/* Se guarda aquí: más abajo los bucles de las gráficas reutilizan $textoRespuesta. */
$filtroTextoRespuesta = (string) ($textoRespuesta ?? '');
$idFicha = (int) ($idFicha ?? 0);
$idAprendiz = (int) ($idAprendiz ?? 0);

$estadoFiltro = strtolower(trim((string) (
    $_GET['estado_cuestionario']
    ?? $estadoCuestionario
    ?? 'todos'
)));

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

$caracterizacion = is_array($caracterizacion ?? null)
    ? $caracterizacion
    : [];

$urlConsulta = url('/bienestar/consultas/prevencion');

/*
|--------------------------------------------------------------------------
| Permisos: consulta exclusivamente Promoción y Prevención
|--------------------------------------------------------------------------
*/

$puedeConsultarPP = class_exists('Auth')
    && method_exists('Auth', 'can')
    && Auth::can('bienestar.prevencion.ver');

/*
|--------------------------------------------------------------------------
| Catálogo de preguntas de Promoción y Prevención
|--------------------------------------------------------------------------
*/

$preguntasPyp = [
    'PP-01' => '¿Tiene alguna condición de salud diagnosticada por un profesional que considere importante comunicar para recibir orientación o apoyo durante su formación?',
    'PP-02' => 'Durante los últimos 30 días, ¿cuáles de las siguientes sustancias ha consumido?',
    'PP-02-FRECUENCIA' => 'Frecuencia de consumo por categoría (últimos 30 días)',
    'PP-03' => '¿Qué tan informado(a) se siente sobre métodos anticonceptivos, prevención de infecciones de transmisión sexual y derechos sexuales y reproductivos?',
    'PP-04' => '¿Qué hábitos de su vida cotidiana le gustaría mejorar para cuidar su salud?',
    'PP-05' => 'En general, ¿cómo califica su estado de salud actual? (escala de 1 a 10)',
    'PP-06' => '¿Hay algún tema de promoción de la salud o prevención sobre el que le gustaría recibir información u orientación?',
];

$normalizarCodigoPP = static function (string $codigo): string {
    return strtoupper(str_replace('_', '-', trim($codigo)));
};

$obtenerComponente = static function (string $codigo) use ($normalizarCodigoPP): ?string {
    $codigo = $normalizarCodigoPP($codigo);

    return preg_match('/^PP(?:-|$)/', $codigo) ? 'PP' : null;
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
        '/^(?:pregunta\s*)?(?:pp[-_\s]*)?\d+(?:[-_\s]*frecuencia)?$/iu',
        $texto
    ) === 1;
};

/*
|--------------------------------------------------------------------------
| Obtener el nombre real o el nombre de referencia de una pregunta
|--------------------------------------------------------------------------
*/

$nombrePregunta = static function (
    string $codigo,
    ?string $textoOriginal = null
) use (
    $preguntasPyp,
    $normalizarCodigoPP,
    $esTextoGenericoPregunta
): string {
    $normalizado = $normalizarCodigoPP($codigo);

    if (isset($preguntasPyp[$normalizado])) {
        return $preguntasPyp[$normalizado];
    }

    if (
        $textoOriginal !== null
        && trim($textoOriginal) !== ''
        && !$esTextoGenericoPregunta($textoOriginal)
    ) {
        return trim($textoOriginal);
    }

    if (isset($preguntasPyp[$normalizado])) {
        return $preguntasPyp[$normalizado];
    }

    return $normalizado;
};

/*
|--------------------------------------------------------------------------
| Resolver el enunciado desde los datos entregados por el controlador
|--------------------------------------------------------------------------
*/

$resolverTextoPregunta = static function (
    array $datos,
    string $codigo
) use (
    $nombrePregunta,
    $esTextoGenericoPregunta,
    $preguntasPyp,
    $normalizarCodigoPP
): string {
    $codigoNormalizado = $normalizarCodigoPP($codigo);

    if (isset($preguntasPyp[$codigoNormalizado])) {
        return $preguntasPyp[$codigoNormalizado];
    }

    $camposTexto = [
        'texto',
        'enunciado',
        'nombre',
        'pregunta_texto',
        'texto_pregunta',
        'enunciado_pregunta',
        'descripcion_pregunta',
        'pregunta_nombre',
        'titulo_pregunta',
        'descripcion',
        'titulo',
        'label',
        'etiqueta',
    ];

    foreach ($camposTexto as $campo) {
        if (
            isset($datos[$campo])
            && is_scalar($datos[$campo])
        ) {
            $texto = trim((string) $datos[$campo]);

            if (
                $texto !== ''
                && !$esTextoGenericoPregunta($texto)
            ) {
                return $texto;
            }
        }
    }

    if (isset($datos['pregunta'])) {
        if (is_string($datos['pregunta'])) {
            $texto = trim($datos['pregunta']);

            if (
                $texto !== ''
                && !$esTextoGenericoPregunta($texto)
            ) {
                return $texto;
            }
        } elseif (is_array($datos['pregunta'])) {
            foreach ($camposTexto as $campo) {
                if (
                    isset($datos['pregunta'][$campo])
                    && is_scalar($datos['pregunta'][$campo])
                ) {
                    $texto = trim((string) $datos['pregunta'][$campo]);

                    if (
                        $texto !== ''
                        && !$esTextoGenericoPregunta($texto)
                    ) {
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
| Normalización de respuestas individuales: únicamente códigos PP
|--------------------------------------------------------------------------
*/

$normalizarRespuestasPyp = static function (mixed $datos): array {
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
                    $codigo = strtoupper(
                        str_replace('_', '-', trim((string) $codigo))
                    );

                    if (preg_match('/^PP(?:-|$)/', $codigo)) {
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

                $claveNormalizada = strtoupper(
                    str_replace('_', '-', $claveTexto)
                );

                if (
                    $claveTexto !== ''
                    && preg_match('/^PP(?:-|$)/', $claveNormalizada)
                ) {
                    $resultado[$claveNormalizada] = $valor;
                    continue;
                }

                $recorrer($valor);
                continue;
            }

            $claveNormalizada = strtoupper(
                str_replace('_', '-', $claveTexto)
            );

            if (
                $claveTexto !== ''
                && preg_match('/^PP(?:-|$)/', $claveNormalizada)
            ) {
                $resultado[$claveNormalizada] = $valor;
            }
        }
    };

    $recorrer($datos);

    return $resultado;
};

/*
|--------------------------------------------------------------------------
| Filtro de estadísticas: exclusivamente preguntas PP
|--------------------------------------------------------------------------
*/

$estadisticasPP = [];

foreach ($estadisticas as $clave => $estadistica) {
    if (!is_array($estadistica)) {
        continue;
    }

    $codigoPregunta = (string) (
        $estadistica['codigo']
        ?? $estadistica['pregunta_codigo']
        ?? $estadistica['codigo_pregunta']
        ?? $estadistica['id_pregunta_codigo']
        ?? (is_string($clave) ? $clave : '')
    );

    $codigoPregunta = $normalizarCodigoPP($codigoPregunta);

    if (
        $codigoPregunta === ''
        || !preg_match('/^PP(?:-|$)/', $codigoPregunta)
    ) {
        continue;
    }

    $estadistica['codigo'] = $codigoPregunta;
    $estadisticasPP[$codigoPregunta] = $estadistica;
}

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
| Preparación de datos para las gráficas
|--------------------------------------------------------------------------
*/

$graficaEstados = [
    'Completados' => max(0, $totalCompletados),
    'En proceso' => max(0, $totalBorradores),
    'Pendientes' => max(0, $totalPendientes),
];

$graficaPreguntas = [];

foreach ($estadisticasPP as $codigo => $estadistica) {
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
                ?? $datoRespuesta['etiqueta']
                ?? $datoRespuesta['opcion']
                ?? (string) $claveRespuesta;
        } else {
            $cantidadRespuesta = (int) $datoRespuesta;
            $textoRespuesta = (string) $claveRespuesta;
        }

        $etiquetasGrafica[] = pypValorLegible($textoRespuesta);
        $cantidadesGrafica[] = max(0, $cantidadRespuesta);
    }

    $textoPregunta = $resolverTextoPregunta(
        $estadistica,
        (string) $codigo
    );

    $graficaPreguntas[] = [
        'codigo' => (string) $codigo,
        'pregunta' => $textoPregunta,
        'etiquetas' => $etiquetasGrafica,
        'cantidades' => $cantidadesGrafica,
    ];
}

$graficaEstadosJson = json_encode(
    $graficaEstados,
    JSON_UNESCAPED_UNICODE
        | JSON_HEX_TAG
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
        | JSON_HEX_AMP
);

$graficaPreguntasJson = json_encode(
    $graficaPreguntas,
    JSON_UNESCAPED_UNICODE
        | JSON_HEX_TAG
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
        | JSON_HEX_AMP
);

/*
|--------------------------------------------------------------------------
| Datos individuales del aprendiz
|--------------------------------------------------------------------------
*/

$datosIndividuales = $aprendizSeleccionado ?? [];

/*
 * Busca el primer valor disponible entre los nombres de campo conocidos.
 * Esto permite presentar los datos aunque el modelo utilice alias distintos.
 */
$obtenerPrimerValor = static function (
    array $datos,
    array $campos,
    mixed $predeterminado = null
): mixed {
    foreach ($campos as $campo) {
        if (
            array_key_exists($campo, $datos)
            && $datos[$campo] !== null
            && trim((string) $datos[$campo]) !== ''
        ) {
            return $datos[$campo];
        }
    }

    return $predeterminado;
};

$nombreIndividual = $obtenerPrimerValor(
    $datosIndividuales,
    [
        'nombre_completo',
        'nombre_aprendiz',
        'nombre_persona',
        'nombre',
    ],
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
    [
        'documento',
        'numero_documento',
        'identificacion',
        'numero_identificacion',
    ],
    'No registrado'
);

$numeroFichaIndividual = $obtenerPrimerValor(
    $datosIndividuales,
    [
        'numero_ficha',
        'ficha',
        'ficha_numero',
    ]
);

$programaIndividual = $obtenerPrimerValor(
    $datosIndividuales,
    [
        'programa',
        'nombre_programa',
        'programa_formacion',
        'nombre_programa_formacion',
    ]
);

$correoIndividual = $obtenerPrimerValor(
    $datosIndividuales,
    [
        'correo',
        'email',
        'correo_electronico',
        'correo_personal',
    ]
);

/*
 * Prioriza el estado formativo del aprendiz.
 * Si no está disponible, utiliza el estado de la matrícula.
 */
$estadoAprendiz = $obtenerPrimerValor(
    $datosIndividuales,
    [
        'estado_aprendiz',
        'estado_formacion',
        'estado',
        'estado_matricula',
    ],
    'No registrado'
);

/*
|--------------------------------------------------------------------------
| Búsqueda por respuestas de Promoción y Prevención
|--------------------------------------------------------------------------
*/

$catalogoPP = is_array($catalogoPP ?? null) ? $catalogoPP : [];
$filtrosRapidos = is_array($filtrosRapidos ?? null) ? $filtrosRapidos : [];
$ppPregunta = (string) ($ppPregunta ?? '');
$ppRespuesta = (string) ($ppRespuesta ?? '');
$hayFiltrosRespuesta = (bool) ($hayFiltrosRespuesta ?? false);
$aprendicesFiltrados = is_array($aprendicesFiltrados ?? null)
    ? $aprendicesFiltrados
    : [];
$totalConRespuestasPP = (int) ($totalConRespuestasPP ?? 0);
$maxFilasVista = (int) ($maxFilasVista ?? 500);

$opcionesJsonPP = JSON_UNESCAPED_UNICODE
    | JSON_HEX_TAG
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
    | JSON_HEX_AMP;

/* Filtros rápidos que se muestran como lista desplegable. */
$filtrosRapidosConfig = [
    'PP-06' => 'Tema sobre el que desea orientación',
    'PP-04' => 'Hábito que desea mejorar',
    'PP-02' => 'Sustancias consumidas (últimos 30 días)',
    'PP-02-FRECUENCIA' => 'Frecuencia de consumo por categoría',
    'PP-01' => 'Condición de salud para comunicar',
    'PP-03' => 'Nivel de información en salud sexual y reproductiva',
    'PP-05' => 'Calificación de su estado de salud (1 a 10)',
];

$minusculasPP = static function (string $texto): string {
    $texto = trim($texto);

    $texto = function_exists('mb_strtolower')
        ? mb_strtolower($texto, 'UTF-8')
        : strtolower($texto);

    return strtr($texto, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o',
        'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
    ]);
};

$mismoTexto = static function (string $a, string $b) use ($minusculasPP): bool {
    return $minusculasPP($a) === $minusculasPP($b);
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
    'pp' => $filtrosRapidos,
    'pp_pregunta' => $ppPregunta,
    'pp_respuesta' => $ppRespuesta,
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
$etiquetasPP = static function (array $fila, string $codigo): string {
    $opciones = $fila['respuestas_componente'][$codigo] ?? [];

    if ($opciones === []) {
        return '<span class="pyp-muted">—</span>';
    }

    $html = '';

    foreach ($opciones as $opcion) {
        $html .= '<span class="pyp-tag">' . pypEsc($opcion) . '</span>';
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

if ($ppPregunta !== '') {
    $filtrosActivosTexto[] = $ppPregunta . ': '
        . ($ppRespuesta !== '' ? $ppRespuesta : 'cualquier respuesta');
}

if ($filtroTextoRespuesta !== '') {
    $filtrosActivosTexto[] = 'Contiene «' . $filtroTextoRespuesta . '»'
        . ($ppPregunta !== '' ? ' en ' . $ppPregunta : ' en cualquier respuesta');
}

/* Catálogo para la lista dependiente (pregunta → respuestas) en JavaScript. */
$catalogoJs = [];

foreach ($catalogoPP as $codigoCatalogo => $datosCatalogo) {
    $nombresOpciones = array_column($datosCatalogo['opciones'] ?? [], 'nombre');
    natcasesort($nombresOpciones);

    $catalogoJs[$codigoCatalogo] = [
        'libre' => !empty($datosCatalogo['libre']),
        'opciones' => array_values($nombresOpciones),
    ];
}

$catalogoJsJson = json_encode($catalogoJs, $opcionesJsonPP);
$ppRespuestaJson = json_encode($ppRespuesta, $opcionesJsonPP);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= pypEsc($tituloPagina) ?> | SITA</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --pyp-primary: #174a78;
            --pyp-primary-dark: #103653;
            --pyp-secondary: #2676a8;
            --pyp-bg: #f3f6fa;
            --pyp-card: #ffffff;
            --pyp-text: #263445;
            --pyp-muted: #64748b;
            --pyp-border: #dfe7ef;
            --pyp-green: #16835d;
            --pyp-orange: #bd7614;
            --pyp-red: #b83c48;
            --pyp-radius: 14px;
        }

        .pyp-page {
            background: var(--pyp-bg);
            min-height: 75vh;
            padding: 24px 0 34px;
            color: var(--pyp-text);
            font-family: Arial, Helvetica, sans-serif;
        }

        .pyp-container {
            width: min(1450px, calc(100% - 32px));
            margin: 0 auto;
        }

        .pyp-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .pyp-header h1 {
            margin: 0 0 7px;
            font-size: clamp(23px, 3vw, 31px);
            color: var(--pyp-primary-dark);
        }

        .pyp-header p {
            margin: 0;
            color: var(--pyp-muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .pyp-card {
            background: var(--pyp-card);
            border: 1px solid var(--pyp-border);
            border-radius: var(--pyp-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(20, 52, 80, .045);
            min-width: 0;
        }

        .pyp-card h2 {
            color: var(--pyp-primary-dark);
            font-size: 19px;
            margin: 0 0 16px;
        }

        .pyp-card h3 {
            color: var(--pyp-primary);
            font-size: 16px;
            margin: 0 0 12px;
        }

        .pyp-summary-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .pyp-stat {
            background: #ffffff;
            border: 1px solid var(--pyp-border);
            border-radius: 13px;
            padding: 17px;
            min-width: 0;
        }

        .pyp-stat-label {
            color: var(--pyp-muted);
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 8px;
        }

        .pyp-stat-value {
            color: var(--pyp-primary-dark);
            font-size: 29px;
            line-height: 1.15;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .pyp-stat.completed .pyp-stat-value { color: var(--pyp-green); }
        .pyp-stat.draft .pyp-stat-value { color: var(--pyp-orange); }
        .pyp-stat.pending .pyp-stat-value { color: var(--pyp-red); }

        .pyp-progress {
            height: 10px;
            background: #e7edf4;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 12px;
        }

        .pyp-progress > span {
            display: block;
            height: 100%;
            width: <?= number_format($porcentajeAvance, 2, '.', '') ?>%;
            background: var(--pyp-green);
            border-radius: inherit;
        }

        .pyp-form-grid {
            display: grid;
            grid-template-columns: 2fr 1.1fr 1fr auto;
            gap: 13px;
            align-items: end;
        }

        .pyp-field label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 7px;
            color: #334155;
        }

        .pyp-field input,
        .pyp-field select {
            box-sizing: border-box;
            width: 100%;
            min-height: 43px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            color: var(--pyp-text);
            font-size: 14px;
        }

        .pyp-button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
            min-height: 42px;
            padding: 10px 16px;
            border: 0;
            border-radius: 8px;
            background: var(--pyp-primary);
            color: #ffffff !important;
            text-decoration: none !important;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
        }

        .pyp-button:hover {
            background: var(--pyp-primary-dark);
        }

        .pyp-button.secondary {
            background: #e8eef5;
            color: var(--pyp-primary-dark) !important;
        }

        .pyp-button.small {
            min-height: 32px;
            padding: 7px 10px;
            font-size: 12px;
        }

        .pyp-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .pyp-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .pyp-table th,
        .pyp-table td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid var(--pyp-border);
            vertical-align: middle;
        }

        .pyp-table th {
            background: #f3f7fb;
            color: #334155;
            font-weight: 800;
            white-space: nowrap;
        }

        .pyp-table tbody tr:hover {
            background: #f8fbfe;
        }

        .pyp-badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .pyp-badge.completed {
            background: #d9f4e7;
            color: #116444;
        }

        .pyp-badge.draft {
            background: #fff0d2;
            color: #925400;
        }

        .pyp-badge.pending {
            background: #fde2e4;
            color: #a12835;
        }

        .pyp-muted {
            color: var(--pyp-muted);
        }

        .pyp-empty {
            padding: 25px 14px;
            text-align: center;
            color: var(--pyp-muted);
            line-height: 1.6;
        }

        .pyp-charts-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-top: 18px;
            align-items: stretch;
        }

        .pyp-chart-card {
            background: #ffffff;
            border: 1px solid var(--pyp-border);
            border-radius: 12px;
            padding: 18px;
            min-width: 0;
            box-shadow: 0 3px 12px rgba(20, 52, 80, .04);
        }

        .pyp-chart-card h3 {
            color: var(--pyp-primary-dark);
            font-size: 16px;
            margin: 0 0 8px;
        }

        .pyp-chart-description {
            color: var(--pyp-muted);
            font-size: 13px;
            line-height: 1.6;
            margin: 0 0 16px;
        }

        .pyp-chart-container {
            position: relative;
            width: 100%;
            height: 310px;
        }

        .pyp-chart-container.pyp-chart-doughnut {
            max-width: 390px;
            margin: 0 auto;
        }

        .pyp-chart-filter {
            margin-bottom: 16px;
        }

        .pyp-chart-filter label {
            display: block;
            color: #334155;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .pyp-chart-filter select {
            width: 100%;
            min-height: 42px;
            box-sizing: border-box;
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            color: var(--pyp-text);
            font-size: 14px;
        }

        
.pyp-chart-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 180px;
    padding: 15px;
    text-align: center;
    color: var(--pyp-muted);
    font-size: 13px;
    line-height: 1.6;
}

/* Ocultar correctamente los mensajes cuando no corresponden */
.pyp-chart-empty[hidden] {
    display: none !important;
}

        .pyp-chart-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .pyp-chart-summary-item {
            border: 1px solid var(--pyp-border);
            border-radius: 9px;
            padding: 11px;
            background: #f8fafc;
            min-width: 0;
        }

        .pyp-chart-summary-item span {
            display: block;
            color: var(--pyp-muted);
            font-size: 11px;
            margin-bottom: 6px;
            line-height: 1.4;
        }

        .pyp-chart-summary-item strong {
            display: block;
            font-size: 20px;
            color: var(--pyp-primary-dark);
            overflow-wrap: anywhere;
        }

        .pyp-section {
            border: 1px solid var(--pyp-border);
            border-radius: 11px;
            margin: 14px 0;
            overflow: hidden;
            background: #ffffff;
        }

        .pyp-section-title {
            padding: 14px 16px;
            background: #edf4fa;
            color: var(--pyp-primary-dark);
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .pyp-section-body {
            padding: 14px;
        }

        .pyp-question {
            padding: 16px;
            border-top: 1px solid var(--pyp-border);
        }

        .pyp-question:first-child {
            border-top: 0;
        }

        .pyp-question-title {
            font-weight: 700;
            margin-bottom: 11px;
            line-height: 1.5;
        }

        .pyp-response-row {
            display: grid;
            grid-template-columns: minmax(150px, 1.5fr) minmax(80px, .35fr) minmax(100px, 1fr);
            gap: 12px;
            align-items: center;
            padding: 8px 0;
            border-top: 1px solid #edf1f5;
            font-size: 13px;
        }

        .pyp-bar {
            height: 8px;
            background: #e9eef4;
            border-radius: 999px;
            overflow: hidden;
        }

        .pyp-bar span {
            display: block;
            height: 100%;
            background: var(--pyp-secondary);
            border-radius: inherit;
        }

        .pyp-profile-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .pyp-profile-item {
            padding: 12px;
            border: 1px solid var(--pyp-border);
            border-radius: 9px;
            min-width: 0;
            background: #ffffff;
        }

        .pyp-profile-item .label {
            color: var(--pyp-muted);
            font-size: 12px;
            margin-bottom: 5px;
        }

        .pyp-profile-item .value {
            font-weight: 700;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .pyp-answer {
            padding: 13px 14px;
            border: 1px solid var(--pyp-border);
            border-radius: 9px;
            margin-bottom: 10px;
            background: #ffffff;
        }

        .pyp-answer:last-child {
            margin-bottom: 0;
        }

        .pyp-answer .question {
            font-weight: 700;
            margin-bottom: 7px;
            color: var(--pyp-primary-dark);
            line-height: 1.5;
        }

        .pyp-answer .value {
            font-size: 14px;
            line-height: 1.7;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        .pyp-note {
            border-left: 4px solid #d6a13b;
            background: #fff9e9;
            color: #654b16;
            padding: 12px 14px;
            border-radius: 6px;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .pyp-detail-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin: 24px 0 10px;
        }

        .pyp-footer {
            text-align: center;
            padding: 14px 10px;
            color: #64748b;
            font-size: 12px;
        }

        /* ---------- Búsqueda por respuestas ---------- */
        .pyp-filters-extra {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px dashed var(--pyp-border);
        }

        .pyp-filter-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 13px;
        }

        .pyp-filter-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .pyp-field .pyp-hint {
            display: block;
            margin-top: 5px;
            font-size: 11px;
            color: var(--pyp-muted);
            line-height: 1.4;
        }

        .pyp-field select:disabled {
            background: #f1f5f9;
            color: #94a3b8;
        }

        .pyp-tag {
            display: inline-block;
            padding: 3px 9px;
            margin: 2px 4px 2px 0;
            border-radius: 999px;
            background: #e8f1f9;
            color: var(--pyp-primary);
            font-size: 12px;
            font-weight: 700;
            line-height: 1.4;
        }

        .pyp-tag.match {
            background: #d9f4e7;
            color: #116444;
        }

        .pyp-chip-block + .pyp-chip-block {
            margin-top: 18px;
        }

        .pyp-chip-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .pyp-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 12px;
            border: 1px solid var(--pyp-border);
            border-radius: 999px;
            background: #ffffff;
            color: var(--pyp-primary-dark) !important;
            text-decoration: none !important;
            font-size: 13px;
            font-weight: 700;
        }

        .pyp-chip:hover {
            border-color: var(--pyp-secondary);
            background: #f3f8fc;
        }

        .pyp-chip span {
            padding: 1px 7px;
            border-radius: 999px;
            background: #e8eef5;
            font-size: 11px;
        }

        .pyp-chip.active {
            background: var(--pyp-primary);
            border-color: var(--pyp-primary);
            color: #ffffff !important;
        }

        .pyp-chip.active span {
            background: rgba(255, 255, 255, .22);
        }

        .pyp-results-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .pyp-results-head h2 {
            margin-bottom: 6px;
        }

        .pyp-active-filters {
            margin: 0 0 14px;
            padding: 10px 12px;
            background: #f3f8fc;
            border: 1px solid var(--pyp-border);
            border-radius: 9px;
            font-size: 13px;
            line-height: 1.7;
        }

        .pyp-sensitive-note {
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
            .pyp-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .pyp-filter-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1100px) {
            .pyp-summary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .pyp-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 800px) {
            .pyp-charts-grid {
                grid-template-columns: 1fr;
            }

            .pyp-chart-container {
                height: 280px;
            }
        }

        @media (max-width: 700px) {
            .pyp-summary-grid,
            .pyp-profile-grid,
            .pyp-form-grid {
                grid-template-columns: 1fr;
            }

            .pyp-response-row {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            .pyp-card {
                padding: 15px;
            }

            .pyp-container {
                width: calc(100% - 20px);
            }

            .pyp-chart-summary {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 420px) {
            .pyp-summary-grid {
                grid-template-columns: 1fr;
            }

            .pyp-chart-card {
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

<main class="pyp-page">
    <div class="pyp-container">

        <header class="pyp-header">
            <div>
                <h1><?= pypEsc($titulo) ?></h1>
                <p>
                    Consulta de Promoción y Prevención de los aprendices,
                    con estadísticas y detalle individual del componente PP.
                </p>
            </div>

            <a class="pyp-button secondary" href="<?= pypEsc($urlConsulta) ?>">
                Limpiar filtros
            </a>
        </header>

        <?php if (!$puedeConsultarPP): ?>

            <section class="pyp-card">
                <div class="pyp-empty">
                    <strong>No tiene permiso para consultar Promoción y Prevención.</strong>
                    <br>
                    Solicite al administrador la asignación del permiso
                    <code>bienestar.prevencion.ver</code>.
                </div>
            </section>

        <?php else: ?>

            <section class="pyp-summary-grid" aria-label="Resumen de cuestionarios">

                <div class="pyp-stat">
                    <div class="pyp-stat-label">Aprendices activos</div>
                    <div class="pyp-stat-value">
                        <?= number_format($totalAprendices, 0, ',', '.') ?>
                    </div>
                    <div class="pyp-muted" style="font-size:12px;margin-top:6px">
                        Según los datos recibidos
                    </div>
                </div>

                <div class="pyp-stat completed">
                    <div class="pyp-stat-label">Cuestionarios completados</div>
                    <div class="pyp-stat-value">
                        <?= number_format($totalCompletados, 0, ',', '.') ?>
                    </div>
                    <div class="pyp-muted" style="font-size:12px;margin-top:6px">
                        Registro finalizado
                    </div>
                </div>

                <div class="pyp-stat draft">
                    <div class="pyp-stat-label">En proceso</div>
                    <div class="pyp-stat-value">
                        <?= number_format($totalBorradores, 0, ',', '.') ?>
                    </div>
                    <div class="pyp-muted" style="font-size:12px;margin-top:6px">
                        Cuestionarios en borrador
                    </div>
                </div>

                <div class="pyp-stat pending">
                    <div class="pyp-stat-label">Pendientes</div>
                    <div class="pyp-stat-value">
                        <?= number_format($totalPendientes, 0, ',', '.') ?>
                    </div>
                    <div class="pyp-muted" style="font-size:12px;margin-top:6px">
                        Sin completar
                    </div>
                </div>

                <div class="pyp-stat">
                    <div class="pyp-stat-label">Avance de diligenciamiento</div>
                    <div class="pyp-stat-value">
                        <?= number_format($porcentajeAvance, 1, ',', '.') ?>%
                    </div>
                    <div
                        class="pyp-progress"
                        role="progressbar"
                        aria-valuenow="<?= pypEsc((string) round($porcentajeAvance, 1)) ?>"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <span></span>
                    </div>
                </div>

            </section>

            <section class="pyp-card">
                <h2>Buscar y filtrar aprendices</h2>

                <form method="GET" action="<?= pypEsc($urlConsulta) ?>">
                    <div class="pyp-form-grid">

                        <div class="pyp-field">
                            <label for="busqueda">
                                Documento, nombre, apellido o ficha
                            </label>
                            <input
                                type="text"
                                name="busqueda"
                                id="busqueda"
                                value="<?= pypEsc($busqueda) ?>"
                                placeholder="Ingrese el dato que desea consultar"
                            >
                        </div>

                        <div class="pyp-field">
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
                                        <?= pypEsc(
                                            $fichaNumero .
                                            ($programa !== '' ? ' - ' . $programa : '')
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="pyp-field">
                            <label for="estado_cuestionario">
                                Estado del cuestionario
                            </label>
                            <select
                                name="estado_cuestionario"
                                id="estado_cuestionario"
                            >
                                <option
                                    value="todos"
                                    <?= $estadoFiltro === 'todos' ? 'selected' : '' ?>
                                >
                                    Todos los estados
                                </option>
                                <option
                                    value="pendiente"
                                    <?= $estadoFiltro === 'pendiente' ? 'selected' : '' ?>
                                >
                                    Pendiente
                                </option>
                                <option
                                    value="en_proceso"
                                    <?= $estadoFiltro === 'en_proceso' ? 'selected' : '' ?>
                                >
                                    En proceso
                                </option>
                                <option
                                    value="completado"
                                    <?= $estadoFiltro === 'completado' ? 'selected' : '' ?>
                                >
                                    Completado
                                </option>
                            </select>
                        </div>

                        <div>
                            <button
                                type="submit"
                                class="pyp-button"
                                style="width:100%"
                            >
                                Consultar
                            </button>
                        </div>

                    </div>

                    <div class="pyp-filters-extra">
                        <h3>Buscar por respuestas de Promoción y Prevención</h3>

                        <p
                            class="pyp-muted"
                            style="font-size:13px;line-height:1.6;margin:-4px 0 14px"
                        >
                            Combine los filtros para saber quién desea recibir
                            orientación sobre un tema, qué hábitos quiere mejorar
                            o qué respondió en cada pregunta. Los filtros se
                            suman entre sí.
                        </p>

                        <div class="pyp-filter-grid">
                            <?php foreach ($filtrosRapidosConfig as $codigoFiltro => $etiquetaFiltro): ?>
                                <?php
                                $datosFiltro = $catalogoPP[$codigoFiltro] ?? null;

                                if ($datosFiltro === null || empty($datosFiltro['opciones'])) {
                                    continue;
                                }

                                $valorActual = (string) ($filtrosRapidos[$codigoFiltro] ?? '');
                                $idCampo = 'pp_' . strtolower(str_replace('-', '_', $codigoFiltro));
                                ?>

                                <div class="pyp-field">
                                    <label for="<?= pypEsc($idCampo) ?>">
                                        <?= pypEsc($etiquetaFiltro) ?>
                                        <span class="pyp-muted">(<?= pypEsc($codigoFiltro) ?>)</span>
                                    </label>

                                    <select
                                        name="pp[<?= pypEsc($codigoFiltro) ?>]"
                                        id="<?= pypEsc($idCampo) ?>"
                                    >
                                        <option value="">Todas</option>

                                        <?php foreach ($ordenarOpciones($datosFiltro) as $opcionFiltro): ?>
                                            <option
                                                value="<?= pypEsc($opcionFiltro['nombre']) ?>"
                                                <?= ($valorActual !== '' && $mismoTexto($valorActual, (string) $opcionFiltro['nombre'])) ? 'selected' : '' ?>
                                            >
                                                <?= pypEsc($opcionFiltro['nombre']) ?>
                                                (<?= (int) $opcionFiltro['cantidad'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="pyp-filter-grid" style="margin-top:14px">

                            <div class="pyp-field">
                                <label for="pp_pregunta">Cualquier pregunta PP</label>
                                <select name="pp_pregunta" id="pp_pregunta">
                                    <option value="">Seleccione una pregunta</option>

                                    <?php foreach ($catalogoPP as $codigoPregunta => $datosPregunta): ?>
                                        <option
                                            value="<?= pypEsc($codigoPregunta) ?>"
                                            <?= $ppPregunta === $codigoPregunta ? 'selected' : '' ?>
                                        >
                                            <?= pypEsc(
                                                $codigoPregunta . ' - '
                                                . $nombrePregunta((string) $codigoPregunta)
                                            ) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="pyp-field">
                                <label for="pp_respuesta">Respuesta de esa pregunta</label>
                                <select name="pp_respuesta" id="pp_respuesta" disabled>
                                    <option value="">Seleccione primero una pregunta</option>
                                </select>
                                <span class="pyp-hint">
                                    Si deja «Cualquier respuesta», se listan quienes
                                    contestaron la pregunta.
                                </span>
                            </div>

                            <div class="pyp-field">
                                <label for="texto_respuesta">
                                    Palabra dentro de las respuestas
                                </label>
                                <input
                                    type="text"
                                    name="texto_respuesta"
                                    id="texto_respuesta"
                                    value="<?= pypEsc($filtroTextoRespuesta) ?>"
                                    placeholder="Ej: sueño, alimentación, estrés..."
                                >
                                <span class="pyp-hint">
                                    Ignora tildes y mayúsculas. Si eligió una pregunta,
                                    solo busca en esa; si no, en todas las respuestas PP.
                                </span>
                            </div>
                        </div>

                        <div class="pyp-filter-actions">
                            <button type="submit" class="pyp-button">
                                Aplicar filtros
                            </button>

                            <a class="pyp-button secondary" href="<?= pypEsc($urlConsulta) ?>">
                                Limpiar todo
                            </a>
                        </div>
                    </div>
                </form>
            </section>

            <?php
            $bloquesChips = [
                'PP-06' => 'Temas sobre los que desean información u orientación',
                'PP-04' => 'Hábitos que desean mejorar',
            ];

            $hayChips = false;

            foreach ($bloquesChips as $codigoChip => $tituloChip) {
                if (!empty($catalogoPP[$codigoChip]['opciones'])) {
                    $hayChips = true;
                    break;
                }
            }
            ?>

            <?php if ($hayChips): ?>
                <section class="pyp-card">
                    <h2>Temas e intereses más frecuentes</h2>

                    <p
                        class="pyp-muted"
                        style="font-size:13px;line-height:1.6;margin-top:-8px"
                    >
                        Haga clic en una opción para ver qué aprendices la
                        eligieron. Vuelva a hacer clic en la opción activa
                        para quitar el filtro.
                    </p>

                    <?php foreach ($bloquesChips as $codigoChip => $tituloChip): ?>
                        <?php
                        $opcionesChip = $catalogoPP[$codigoChip]['opciones'] ?? [];

                        if ($opcionesChip === []) {
                            continue;
                        }

                        $valorChipActivo = (string) ($filtrosRapidos[$codigoChip] ?? '');
                        $totalOpcionesChip = count($opcionesChip);
                        ?>

                        <div class="pyp-chip-block">
                            <h3>
                                <?= pypEsc($tituloChip) ?>
                                <span class="pyp-muted" style="font-weight:400;font-size:12px">
                                    (<?= pypEsc($codigoChip) ?> ·
                                    <?= (int) $catalogoPP[$codigoChip]['respondieron'] ?>
                                    respuestas)
                                </span>
                            </h3>

                            <div class="pyp-chip-list">
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
                                        class="pyp-chip <?= $activo ? 'active' : '' ?>"
                                        href="<?= pypEsc($construirUrl(['pp' => $filtrosEnlace]) . '#resultados-respuestas') ?>"
                                    >
                                        <?= pypEsc($opcionChip['nombre']) ?>
                                        <span><?= (int) $opcionChip['cantidad'] ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($totalOpcionesChip > 15): ?>
                                <p class="pyp-muted" style="font-size:12px;margin:8px 0 0">
                                    Se muestran las 15 más frecuentes de
                                    <?= (int) $totalOpcionesChip ?>. Use los filtros
                                    de arriba para ver el resto.
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <section class="pyp-card" id="resultados-respuestas">
                <div class="pyp-results-head">
                    <div>
                        <h2>Aprendices por respuestas de Promoción y Prevención</h2>

                        <p class="pyp-muted" style="font-size:13px;line-height:1.6;margin:0">
                            <strong><?= number_format(count($aprendicesFiltrados), 0, ',', '.') ?></strong>
                            <?= count($aprendicesFiltrados) === 1 ? 'aprendiz' : 'aprendices' ?>
                            <?= $hayFiltrosRespuesta ? 'cumplen los filtros' : 'con respuestas de Promoción y Prevención' ?>
                            (de <?= number_format($totalConRespuestasPP, 0, ',', '.') ?>
                            con respuestas PP registradas).
                        </p>
                    </div>

                    <?php if ($aprendicesFiltrados !== []): ?>
                        <a
                            class="pyp-button secondary"
                            href="<?= pypEsc($construirUrl(['exportar' => 'csv'])) ?>"
                        >
                            Exportar a CSV
                        </a>
                    <?php endif; ?>
                </div>

                <div class="pyp-sensitive-note">
                    Estos resultados pueden incluir datos de salud, consumo de
                    sustancias y salud sexual. Úselos únicamente para orientación
                    y apoyo institucional autorizado. El CSV no incluye las
                    preguntas más sensibles (PP-01, PP-02 y PP-03).
                </div>

                <?php if ($filtrosActivosTexto !== []): ?>
                    <div class="pyp-active-filters">
                        <strong>Filtros activos:</strong>
                        <?php foreach ($filtrosActivosTexto as $textoActivo): ?>
                            <span class="pyp-tag"><?= pypEsc($textoActivo) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($aprendicesFiltrados === []): ?>

                    <div class="pyp-empty">
                        <strong>No hay aprendices con esos criterios.</strong>
                        <br>
                        Pruebe con menos filtros, otra ficha o una palabra más corta.
                    </div>

                <?php else: ?>

                    <div class="pyp-table-wrap">
                        <table class="pyp-table">
                            <thead>
                                <tr>
                                    <th>Documento</th>
                                    <th>Aprendiz</th>
                                    <th>Ficha</th>
                                    <th>Estado</th>
                                    <th>Hábitos que desea mejorar</th>
                                    <th>Temas de interés</th>
                                    <?php if ($hayFiltrosRespuesta): ?>
                                        <th>Respuestas que coinciden</th>
                                    <?php endif; ?>
                                    <th>Consulta</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php foreach (array_slice($aprendicesFiltrados, 0, $maxFilasVista) as $filaPP): ?>
                                <?php
                                $idAprendizPP = (int) ($filaPP['id_aprendiz'] ?? 0);
                                $nombrePP = $obtenerNombreAprendiz($filaPP);
                                $estadoPP = $obtenerEstado($filaPP);
                                $clasePP = $obtenerClaseEstado($estadoPP);
                                ?>

                                <tr>
                                    <td><?= pypEsc($filaPP['documento'] ?? 'No registrado') ?></td>

                                    <td>
                                        <strong>
                                            <?= pypEsc($nombrePP !== '' ? $nombrePP : 'Nombre no disponible') ?>
                                        </strong>
                                    </td>

                                    <td><?= pypEsc($filaPP['numero_ficha'] ?? '—') ?></td>

                                    <td>
                                        <span class="pyp-badge <?= pypEsc($clasePP) ?>">
                                            <?= pypEsc($estadoPP) ?>
                                        </span>
                                    </td>

                                    <td><?= $etiquetasPP($filaPP, 'PP-04') ?></td>
                                    <td><?= $etiquetasPP($filaPP, 'PP-06') ?></td>

                                    <?php if ($hayFiltrosRespuesta): ?>
                                        <td>
                                            <?php foreach (($filaPP['coincidencias'] ?? []) as $coincidencia): ?>
                                                <span class="pyp-tag match"><?= pypEsc($coincidencia) ?></span>
                                            <?php endforeach; ?>
                                        </td>
                                    <?php endif; ?>

                                    <td>
                                        <a
                                            class="pyp-button small"
                                            href="<?= pypEsc($construirUrl(['id_aprendiz' => $idAprendizPP]) . '#detalle-aprendiz') ?>"
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
                        <p class="pyp-muted" style="font-size:12px;margin-top:12px">
                            Se muestran los primeros
                            <?= (int) $maxFilasVista ?> resultados. Use «Exportar a CSV»
                            para obtener la lista completa.
                        </p>
                    <?php endif; ?>

                <?php endif; ?>
            </section>

            <section class="pyp-card">
                <h2>Listado general de aprendices (estado del cuestionario)</h2>

                <p
                    class="pyp-muted"
                    style="font-size:13px;margin-top:-8px;margin-bottom:15px"
                >
                    Se muestran aprendices que coinciden con los filtros
                    seleccionados.
                </p>

                <?php if ($aprendices === []): ?>

                    <div class="pyp-empty">
                        <strong>No se encontraron aprendices.</strong>
                        <br>
                        Verifique los datos ingresados o seleccione otros filtros.
                    </div>

                <?php else: ?>

                    <div class="pyp-table-wrap">
                        <table class="pyp-table">
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
                                        <?= pypEsc(
                                            $fila['documento']
                                            ?? $fila['numero_documento']
                                            ?? 'No registrado'
                                        ) ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= pypEsc(
                                                $nombre !== ''
                                                    ? $nombre
                                                    : 'Nombre no disponible'
                                            ) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= pypEsc(
                                            $fila['numero_ficha']
                                            ?? $fila['ficha']
                                            ?? '—'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= pypEsc(
                                            $fila['programa']
                                            ?? $fila['nombre_programa']
                                            ?? '—'
                                        ) ?>
                                    </td>

                                    <td>
                                        <span class="pyp-badge <?= pypEsc($estadoClase) ?>">
                                            <?= pypEsc($estadoTexto) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= pypEsc(pypFecha(
                                            $fila['fecha_actualizacion']
                                            ?? $fila['fecha_completado']
                                            ?? null
                                        )) ?>
                                    </td>

                                    <td>
                                        <a
                                            class="pyp-button small"
                                            href="<?= pypEsc($urlDetalle) ?>"
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
                        <p class="pyp-muted" style="font-size:12px;margin-top:12px">
                            Se muestran hasta 200 registros. Use los filtros
                            para precisar la búsqueda.
                        </p>
                    <?php endif; ?>

                <?php endif; ?>
            </section>

            <section class="pyp-card" id="graficas-prevencion">
                <h2>Panel gráfico de Promoción y Prevención</h2>

                <p
                    class="pyp-muted"
                    style="font-size:13px;line-height:1.6"
                >
                    Consulte el estado de los cuestionarios y explore la
                    distribución de respuestas por pregunta del componente PP.
                    No se incluyen estadísticas de otros componentes.
                </p>

                <div class="pyp-charts-grid">

                    <article class="pyp-chart-card">
                        <h3>Estado de los cuestionarios</h3>

                        <p class="pyp-chart-description">
                            Distribución de cuestionarios completados,
                            en proceso y pendientes.
                        </p>

                        <div
                            class="pyp-chart-container pyp-chart-doughnut"
                            id="pypContenedorEstados"
                        >
                            <canvas
                                id="pypChartEstados"
                                role="img"
                                aria-label="Gráfica circular del estado de los cuestionarios"
                            ></canvas>
                        </div>

                        <div
                            id="pypChartEstadosVacio"
                            class="pyp-chart-empty"
                            hidden
                        >
                            No hay datos suficientes para mostrar esta gráfica.
                        </div>

                        <div class="pyp-chart-summary">
                            <div class="pyp-chart-summary-item">
                                <span>Completados</span>
                                <strong>
                                    <?= number_format($totalCompletados, 0, ',', '.') ?>
                                </strong>
                            </div>

                            <div class="pyp-chart-summary-item">
                                <span>En proceso</span>
                                <strong>
                                    <?= number_format($totalBorradores, 0, ',', '.') ?>
                                </strong>
                            </div>

                            <div class="pyp-chart-summary-item">
                                <span>Pendientes</span>
                                <strong>
                                    <?= number_format($totalPendientes, 0, ',', '.') ?>
                                </strong>
                            </div>
                        </div>
                    </article>

                    <article class="pyp-chart-card">
                        <h3>Respuestas por pregunta</h3>

                        <p class="pyp-chart-description">
                            Seleccione una pregunta de Promoción y Prevención
                            para comparar las cantidades registradas en sus
                            diferentes opciones de respuesta.
                        </p>

                        <div class="pyp-chart-filter">
                            <label for="pypPreguntaGrafica">
                                Pregunta de Promoción y Prevención
                            </label>

                            <select id="pypPreguntaGrafica">
                                <option value="">Seleccione una pregunta</option>

                                <?php foreach ($graficaPreguntas as $indice => $preguntaGrafica): ?>
                                    <option value="<?= (int) $indice ?>">
                                        <?= pypEsc(
                                            $preguntaGrafica['codigo']
                                            . ' - '
                                            . $preguntaGrafica['pregunta']
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div
                            class="pyp-chart-container"
                            id="pypContenedorRespuestas"
                        >
                            <canvas
                                id="pypChartRespuestas"
                                role="img"
                                aria-label="Gráfica de barras de respuestas por pregunta"
                            ></canvas>
                        </div>

                        <div
                            id="pypChartRespuestasVacio"
                            class="pyp-chart-empty"
                            hidden
                        >
                            Seleccione una pregunta con respuestas registradas
                            para visualizar su distribución.
                        </div>
                    </article>

                </div>
            </section>

            <section class="pyp-card">
                <h2>Estadísticas de Promoción y Prevención</h2>

                <p
                    class="pyp-muted"
                    style="font-size:13px;line-height:1.6"
                >
                    Las estadísticas de esta sección corresponden únicamente
                    a las preguntas identificadas con el código PP.
                </p>

                <?php if ($estadisticasPP === []): ?>

                    <div class="pyp-empty">
                        Todavía no hay respuestas disponibles para generar
                        estadísticas de Promoción y Prevención.
                    </div>

                <?php else: ?>

                    <div class="pyp-section">
                        <div class="pyp-section-title">
                            <span>Promoción y Prevención</span>
                            <span><?= count($estadisticasPP) ?> preguntas</span>
                        </div>

                        <div class="pyp-section-body">

                            <?php foreach ($estadisticasPP as $codigo => $estadistica): ?>
                                <?php
                                $totalRespuestas = (int) (
                                    $estadistica['total_respuestas']
                                    ?? $estadistica['total']
                                    ?? 0
                                );

                                $respuestas = $estadistica['respuestas'] ?? [];

                                if (is_string($respuestas)) {
                                    $decodificadas = json_decode($respuestas, true);
                                    $respuestas = is_array($decodificadas)
                                        ? $decodificadas
                                        : [];
                                }

                                if (!is_array($respuestas)) {
                                    $respuestas = [];
                                }

                                $textoPregunta = $resolverTextoPregunta(
                                    $estadistica,
                                    (string) $codigo
                                );

                                $denominador = $totalRespuestas > 0
                                    ? $totalRespuestas
                                    : array_sum(array_map(
                                        static fn($dato) => is_array($dato)
                                            ? (int) (
                                                $dato['cantidad']
                                                ?? $dato['total']
                                                ?? 0
                                            )
                                            : (int) $dato,
                                        $respuestas
                                    ));
                                ?>

                                <div class="pyp-question">
                                    <div class="pyp-question-title">
                                        <?= pypEsc($textoPregunta) ?>

                                        <span
                                            class="pyp-muted"
                                            style="font-weight:400;font-size:12px"
                                        >
                                            (<?= pypEsc((string) $codigo) ?>)
                                        </span>
                                    </div>

                                    <?php if ($respuestas === []): ?>

                                        <div
                                            class="pyp-muted"
                                            style="font-size:13px"
                                        >
                                            No hay respuestas registradas
                                            para esta pregunta.
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
                                                ? min(
                                                    100,
                                                    ($cantidad / $denominador) * 100
                                                )
                                                : 0;
                                            ?>

                                            <div class="pyp-response-row">
                                                <div>
                                                    <?= pypEsc(
                                                        pypValorLegible($textoRespuesta)
                                                    ) ?>
                                                </div>

                                                <div>
                                                    <strong>
                                                        <?= number_format(
                                                            $cantidad,
                                                            0,
                                                            ',',
                                                            '.'
                                                        ) ?>
                                                    </strong>

                                                    <span class="pyp-muted">
                                                        (<?= number_format(
                                                            $porcentaje,
                                                            1,
                                                            ',',
                                                            '.'
                                                        ) ?>%)
                                                    </span>
                                                </div>

                                                <div class="pyp-bar">
                                                    <span
                                                        style="width:<?= number_format(
                                                            $porcentaje,
                                                            2,
                                                            '.',
                                                            ''
                                                        ) ?>%"
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

                <section class="pyp-card" id="detalle-aprendiz">
                    <h2>Detalle individual del aprendiz</h2>

                    <?php if ($aprendizSeleccionado === null): ?>

                        <div class="pyp-empty">
                            No fue posible encontrar la información del aprendiz
                            solicitado. Verifique los filtros y la matrícula.
                        </div>

                    <?php else: ?>

                        <div class="pyp-note">
                            La información individual puede contener datos
                            personales y de bienestar. Consulte estos datos
                            únicamente para fines institucionales autorizados.
                        </div>

                        <div class="pyp-profile-grid">

                            <div class="pyp-profile-item">
                                <div class="label">Nombre completo</div>
                                <div class="value">
                                    <?= pypEsc($nombreIndividual) ?>
                                </div>
                            </div>

                            <div class="pyp-profile-item">
                                <div class="label">Documento</div>
                                <div class="value">
                                    <?= pypEsc($documentoIndividual) ?>
                                </div>
                            </div>

                            <div class="pyp-profile-item">
                                <div class="label">Ficha</div>
                                <div class="value">
                                    <?= pypEsc(
                                        ($numeroFichaIndividual !== null
                                            && $numeroFichaIndividual !== '')
                                            ? $numeroFichaIndividual
                                            : 'No registrada'
                                    ) ?>
                                </div>
                            </div>

                            <div class="pyp-profile-item">
                                <div class="label">Programa de formación</div>
                                <div class="value">
                                    <?= pypEsc(
                                        ($programaIndividual !== null
                                            && $programaIndividual !== '')
                                            ? $programaIndividual
                                            : 'No registrado'
                                    ) ?>
                                </div>
                            </div>

                            <div class="pyp-profile-item">
                                <div class="label">Correo electrónico</div>
                                <div class="value">
                                    <?= pypEsc(
                                        ($correoIndividual !== null
                                            && $correoIndividual !== '')
                                            ? $correoIndividual
                                            : 'No registrado'
                                    ) ?>
                                </div>
                            </div>

                            <div class="pyp-profile-item">
                                <div class="label">Estado del aprendiz</div>
                                <div class="value">
                                    <?= pypEsc($estadoAprendiz) ?>
                                </div>
                            </div>

                        </div>

                        <div class="pyp-detail-heading">
                            <h3 style="margin:0">
                                Cuestionarios y respuestas de Promoción y Prevención
                            </h3>
                        </div>

                        <?php if ($cuestionarios === []): ?>

                            <div class="pyp-empty">
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
                                        in_array(
                                            $estadoIndividual,
                                            ['borrador', 'en_proceso'],
                                            true
                                        )
                                            ? 'En proceso'
                                            : 'Pendiente'
                                    );

                                $claseIndividual = $obtenerClaseEstado(
                                    $estadoIndividualTexto
                                );

                                $fuenteRespuestas = $cuestionario['respuestas']
                                    ?? $cuestionario['respuestas_json']
                                    ?? $cuestionario['datos_respuestas']
                                    ?? $cuestionario['respuesta_json']
                                    ?? [];

                                $respuestasIndividuales = $normalizarRespuestasPyp(
                                    $fuenteRespuestas
                                );
                                ?>

                                <div class="pyp-section">
                                    <div class="pyp-section-title">
                                        <span>
                                            Cuestionario <?= (int) ($indice + 1) ?>
                                        </span>

                                        <span class="pyp-badge <?= pypEsc($claseIndividual) ?>">
                                            <?= pypEsc($estadoIndividualTexto) ?>
                                        </span>
                                    </div>

                                    <div class="pyp-section-body">
                                        <p
                                            class="pyp-muted"
                                            style="font-size:12px;line-height:1.8"
                                        >
                                            <strong>Inicio:</strong>
                                            <?= pypEsc(pypFecha(
                                                $cuestionario['fecha_inicio'] ?? null
                                            )) ?>

                                            &nbsp; | &nbsp;

                                            <strong>Última actualización:</strong>
                                            <?= pypEsc(pypFecha(
                                                $cuestionario['fecha_actualizacion'] ?? null
                                            )) ?>

                                            &nbsp; | &nbsp;

                                            <strong>Finalización:</strong>
                                            <?= pypEsc(pypFecha(
                                                $cuestionario['fecha_completado'] ?? null
                                            )) ?>
                                        </p>

                                        <?php if ($respuestasIndividuales === []): ?>

                                            <div class="pyp-empty">
                                                No hay respuestas identificadas
                                                para el componente PP en este
                                                cuestionario.
                                            </div>

                                        <?php else: ?>

                                            <?php foreach ($respuestasIndividuales as $codigo => $valor): ?>
                                                <div class="pyp-answer">
                                                    <div class="question">
                                                        <?= pypEsc(
                                                            $nombrePregunta($codigo)
                                                        ) ?>

                                                        <span
                                                            class="pyp-muted"
                                                            style="font-size:11px;font-weight:400"
                                                        >
                                                            (<?= pypEsc($codigo) ?>)
                                                        </span>
                                                    </div>

                                                    <div class="value">
                                                        <?= pypEsc(
                                                            pypValorLegible($valor)
                                                        ) ?>
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
                            class="pyp-button secondary"
                            href="<?= pypEsc($construirUrl() . '#resultados-respuestas') ?>"
                        >
                            Volver al listado
                        </a>
                    </div>
                </section>

            <?php endif; ?>

        <?php endif; ?>

    </div>
</main>

<footer class="pyp-footer">
    SITA · Sistema de Trazabilidad de Aprendices
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const estados = <?= $graficaEstadosJson ?: '{}' ?>;
    const preguntas = <?= $graficaPreguntasJson ?: '[]' ?>;

    const canvasEstados = document.getElementById('pypChartEstados');
    const contenedorEstados = document.getElementById('pypContenedorEstados');
    const vacioEstados = document.getElementById('pypChartEstadosVacio');

    const selectorPregunta = document.getElementById('pypPreguntaGrafica');
    const canvasRespuestas = document.getElementById('pypChartRespuestas');
    const contenedorRespuestas = document.getElementById('pypContenedorRespuestas');
    const vacioRespuestas = document.getElementById('pypChartRespuestasVacio');

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
/* Lista dependiente: pregunta PP -> respuestas disponibles. */
(function () {
    'use strict';

    const catalogo = <?= $catalogoJsJson ?: '{}' ?>;
    const respuestaInicial = <?= $ppRespuestaJson ?: '""' ?>;

    const selectorPregunta = document.getElementById('pp_pregunta');
    const selectorRespuesta = document.getElementById('pp_respuesta');

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