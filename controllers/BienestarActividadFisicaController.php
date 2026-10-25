<?php
declare(strict_types=1);

/**
 * SITA - Consulta de Actividad Física.
 */
class BienestarActividadFisicaController extends Controller
{
    /**
     * Preguntas abiertas (texto libre). No se listan sus respuestas como
     * opciones de un select; se consultan con el buscador de texto.
     * Si AF-08 también es de texto libre en su cuestionario, agréguela aquí.
     */
    private const PREGUNTAS_TEXTO_LIBRE = ['AF-10'];

    /** Máximo de filas que se pintan en pantalla (el CSV exporta todas). */
    private const MAX_FILAS_VISTA = 500;

    private BienestarActividadFisicaModel $modelo;

    public function __construct()
    {
        $this->modelo = new BienestarActividadFisicaModel();
    }

    public function index(): void
    {
        if (!class_exists('Auth') || !Auth::can('bienestar.actividad_fisica.ver')) {
            http_response_code(403);
            echo 'No tiene permisos para consultar el módulo de Actividad Física.';
            return;
        }

        $busqueda = trim((string) ($_GET['busqueda'] ?? ''));
        $idFicha = max(0, (int) ($_GET['id_ficha'] ?? 0));
        $idAprendiz = max(0, (int) ($_GET['id_aprendiz'] ?? 0));

        $estadoCuestionario = strtolower(
            trim((string) ($_GET['estado'] ?? 'todos'))
        );

        if (!in_array(
            $estadoCuestionario,
            ['todos', 'pendiente', 'borrador', 'completado'],
            true
        )) {
            $estadoCuestionario = 'todos';
        }

        /*
         * Filtros por respuestas de Actividad Física.
         *
         *  - af[AF-03]=Fútbol ...... filtros rápidos (deporte, horario, etc.)
         *  - af_pregunta / af_respuesta ... cualquier pregunta AF y su opción
         *  - texto_respuesta ....... palabra o frase dentro de las respuestas
         *  - deporte_interes ....... se mantiene por compatibilidad (AF-03)
         */
        $deporteInteres = $this->limpiarTexto($_GET['deporte_interes'] ?? '');

        $filtrosRapidos = $this->leerFiltrosRapidos(
            $_GET['af'] ?? [],
            $deporteInteres
        );

        $afPregunta = $this->normalizarCodigo(
            (string) ($_GET['af_pregunta'] ?? '')
        );

        if (preg_match('/^AF-\d{1,3}$/', $afPregunta) !== 1) {
            $afPregunta = '';
        }

        $afRespuesta = $afPregunta !== ''
            ? $this->limpiarTexto($_GET['af_respuesta'] ?? '')
            : '';

        $textoRespuesta = $this->limpiarTexto($_GET['texto_respuesta'] ?? '');

        $fichas = $this->modelo->obtenerFichas();

        $aprendices = $this->modelo->buscarAprendices(
            $busqueda,
            $idFicha,
            $estadoCuestionario === 'todos' ? '' : $estadoCuestionario
        );

        $resumen = $this->modelo->obtenerResumenPorFicha($idFicha);
        $estadisticas = $this->modelo->obtenerEstadisticasRespuestas($idFicha);

        /*
         * Todas las respuestas AF de cada aprendiz (cuestionario más reciente).
         */
        $filasAF = $this->modelo->obtenerRespuestasActividadFisica($idFicha);

        $catalogoAF = $this->construirCatalogo($filasAF);

        $totalConRespuestasAF = 0;

        foreach ($filasAF as $filaAF) {
            if (($filaAF['respuestas_af'] ?? []) !== []) {
                $totalConRespuestasAF++;
            }
        }

        /*
         * Criterios de búsqueda por respuesta.
         */
        $criterios = [];

        foreach ($filtrosRapidos as $codigo => $valor) {
            $criterios[] = ['codigo' => $codigo, 'valor' => $valor];
        }

        if ($afPregunta !== '') {
            $criterios[] = ['codigo' => $afPregunta, 'valor' => $afRespuesta];
        }

        $hayFiltrosRespuesta = $criterios !== [] || $textoRespuesta !== '';

        $textoBusquedaNormalizado = $this->normalizarTexto($busqueda);

        $aprendicesFiltrados = [];

        foreach ($filasAF as $fila) {
            $respuestasAF = $fila['respuestas_af'] ?? [];

            /*
             * Sin filtros por respuesta se listan los aprendices que
             * ya contestaron al menos una pregunta AF.
             */
            if (!$hayFiltrosRespuesta && $respuestasAF === []) {
                continue;
            }

            if ($busqueda !== '') {
                $nombre = $this->normalizarTexto(
                    (string) ($fila['nombre_completo'] ?? '')
                );
                $documento = $this->normalizarTexto(
                    (string) ($fila['documento'] ?? '')
                );

                if (
                    !str_contains($nombre, $textoBusquedaNormalizado)
                    && !str_contains($documento, $textoBusquedaNormalizado)
                ) {
                    continue;
                }
            }

            if (
                $estadoCuestionario !== 'todos'
                && strtolower((string) ($fila['estado_cuestionario'] ?? ''))
                    !== $estadoCuestionario
            ) {
                continue;
            }

            $coincidencias = $this->evaluarFiltros(
                $fila,
                $criterios,
                $textoRespuesta,
                $afPregunta
            );

            if ($coincidencias === null) {
                continue;
            }

            $fila['coincidencias'] = $coincidencias;
            $aprendicesFiltrados[] = $fila;
        }

        /*
         * Exportar el resultado filtrado a CSV.
         */
        if (strtolower((string) ($_GET['exportar'] ?? '')) === 'csv') {
            $this->exportarCsv($aprendicesFiltrados, array_keys($catalogoAF));
            return;
        }

        $aprendizSeleccionado = null;
        $cuestionarios = [];

        if ($idAprendiz > 0) {
            $aprendizSeleccionado = $this->modelo->obtenerCaracterizacion(
                $idAprendiz
            );

            if ($aprendizSeleccionado) {
                $cuestionarios = $this->modelo->obtenerCuestionarios(
                    $idAprendiz
                );
            }
        }

        $this->view(
            'bienestar/consultas/actividad_fisica',
            [
                'tituloPagina' => 'Consulta de Actividad Física',
                'titulo' => 'Consulta de Actividad Física',
                'busqueda' => $busqueda,
                'idFicha' => $idFicha,
                'idAprendiz' => $idAprendiz,
                'estadoCuestionario' => $estadoCuestionario,
                'deporteInteres' => $deporteInteres,
                'fichas' => $fichas,
                'aprendices' => $aprendices,
                'resumen' => $resumen,
                'estadisticas' => $estadisticas,
                'cuestionarios' => $cuestionarios,
                'aprendizSeleccionado' => $aprendizSeleccionado,

                // Búsqueda por respuestas
                'catalogoAF' => $catalogoAF,
                'filtrosRapidos' => $filtrosRapidos,
                'afPregunta' => $afPregunta,
                'afRespuesta' => $afRespuesta,
                'textoRespuesta' => $textoRespuesta,
                'hayFiltrosRespuesta' => $hayFiltrosRespuesta,
                'aprendicesFiltrados' => $aprendicesFiltrados,
                'totalConRespuestasAF' => $totalConRespuestasAF,
                'maxFilasVista' => self::MAX_FILAS_VISTA,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Utilidades
    |--------------------------------------------------------------------------
    */

    private function limpiarTexto(mixed $valor): string
    {
        if (!is_scalar($valor)) {
            return '';
        }

        $texto = trim((string) $valor);

        return function_exists('mb_substr')
            ? mb_substr($texto, 0, 150, 'UTF-8')
            : substr($texto, 0, 150);
    }

    private function normalizarCodigo(string $codigo): string
    {
        return strtoupper(str_replace('_', '-', trim($codigo)));
    }

    /**
     * Minúsculas y sin tildes, para comparar "Fútbol" con "futbol".
     */
    private function normalizarTexto(string $texto): string
    {
        $texto = trim($texto);

        $texto = function_exists('mb_strtolower')
            ? mb_strtolower($texto, 'UTF-8')
            : strtolower($texto);

        return strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o',
            'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
    }

    /**
     * Valida el arreglo af[AF-xx]=valor recibido por GET.
     */
    private function leerFiltrosRapidos(
        mixed $entrada,
        string $deporteCompatibilidad
    ): array {
        $filtros = [];

        if (is_array($entrada)) {
            foreach ($entrada as $codigo => $valor) {
                $codigo = $this->normalizarCodigo((string) $codigo);

                if (preg_match('/^AF-\d{1,3}$/', $codigo) !== 1) {
                    continue;
                }

                $valor = $this->limpiarTexto($valor);

                if ($valor !== '') {
                    $filtros[$codigo] = $valor;
                }
            }
        }

        if ($deporteCompatibilidad !== '' && !isset($filtros['AF-03'])) {
            $filtros['AF-03'] = $deporteCompatibilidad;
        }

        return $filtros;
    }

    /**
     * Catálogo por pregunta con las opciones encontradas y cuántos
     * aprendices eligieron cada una.
     *
     * [
     *   'AF-03' => [
     *       'libre' => false,
     *       'respondieron' => 40,
     *       'opciones' => [
     *           'futbol' => ['nombre' => 'Fútbol', 'cantidad' => 12], ...
     *       ],
     *   ],
     * ]
     */
    private function construirCatalogo(array $filasAF): array
    {
        $catalogo = [];

        foreach ($filasAF as $fila) {
            foreach (($fila['respuestas_af'] ?? []) as $codigo => $opciones) {
                $codigo = (string) $codigo;

                if (!isset($catalogo[$codigo])) {
                    $catalogo[$codigo] = [
                        'libre' => in_array(
                            $codigo,
                            self::PREGUNTAS_TEXTO_LIBRE,
                            true
                        ),
                        'respondieron' => 0,
                        'opciones' => [],
                    ];
                }

                $catalogo[$codigo]['respondieron']++;

                if ($catalogo[$codigo]['libre']) {
                    continue;
                }

                foreach ($opciones as $opcion) {
                    $opcion = trim((string) $opcion);

                    if ($opcion === '') {
                        continue;
                    }

                    $clave = $this->normalizarTexto($opcion);

                    if (!isset($catalogo[$codigo]['opciones'][$clave])) {
                        $catalogo[$codigo]['opciones'][$clave] = [
                            'nombre' => $opcion,
                            'cantidad' => 0,
                        ];
                    }

                    $catalogo[$codigo]['opciones'][$clave]['cantidad']++;
                }
            }
        }

        ksort($catalogo, SORT_NATURAL);

        // Más elegidas primero.
        foreach ($catalogo as &$datos) {
            uasort(
                $datos['opciones'],
                static fn(array $a, array $b): int =>
                    [$b['cantidad'], $a['nombre']]
                    <=> [$a['cantidad'], $b['nombre']]
            );
        }

        unset($datos);

        return $catalogo;
    }

    /**
     * Evalúa si un aprendiz cumple todos los filtros por respuesta.
     * Devuelve las respuestas que coinciden o null si no cumple.
     *
     * @param array<int, array{codigo:string, valor:string}> $criterios
     */
    private function evaluarFiltros(
        array $fila,
        array $criterios,
        string $texto,
        string $codigoTexto
    ): ?array {
        $respuestas = $fila['respuestas_af'] ?? [];
        $coincidencias = [];

        foreach ($criterios as $criterio) {
            $opciones = $respuestas[$criterio['codigo']] ?? [];

            // No contestó esa pregunta.
            if ($opciones === []) {
                return null;
            }

            // Solo se pidió "quienes respondieron esta pregunta".
            if ($criterio['valor'] === '') {
                $coincidencias[] = $criterio['codigo'] . ': '
                    . implode(', ', $opciones);
                continue;
            }

            $buscado = $this->normalizarTexto($criterio['valor']);
            $hallada = null;

            foreach ($opciones as $opcion) {
                if ($this->normalizarTexto((string) $opcion) === $buscado) {
                    $hallada = $opcion;
                    break;
                }
            }

            if ($hallada === null) {
                return null;
            }

            $coincidencias[] = $criterio['codigo'] . ': ' . $hallada;
        }

        if ($texto !== '') {
            $buscado = $this->normalizarTexto($texto);
            $encontrado = false;

            foreach ($respuestas as $codigo => $opciones) {
                if ($codigoTexto !== '' && $codigo !== $codigoTexto) {
                    continue;
                }

                foreach ($opciones as $opcion) {
                    if (
                        str_contains(
                            $this->normalizarTexto((string) $opcion),
                            $buscado
                        )
                    ) {
                        $coincidencias[] = $codigo . ': ' . $opcion;
                        $encontrado = true;
                    }
                }
            }

            if (!$encontrado) {
                return null;
            }
        }

        return array_values(array_unique($coincidencias));
    }

    /**
     * Evita que Excel interprete celdas como fórmulas.
     */
    private function celdaCsv(string $valor): string
    {
        if ($valor !== '' && in_array($valor[0], ['=', '+', '-', '@'], true)) {
            return "'" . $valor;
        }

        return $valor;
    }

    private function exportarCsv(array $filas, array $codigos): void
    {
        $nombreArchivo = 'actividad_fisica_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: no-store');

        // BOM para que Excel respete las tildes.
        echo "\xEF\xBB\xBF";

        $salida = fopen('php://output', 'w');

        fputcsv(
            $salida,
            array_merge(
                [
                    'Tipo documento',
                    'Documento',
                    'Nombres',
                    'Apellidos',
                    'Ficha',
                    'Programa',
                    'Estado cuestionario',
                ],
                $codigos
            ),
            ';',
            '"',
            '\\'
        );

        foreach ($filas as $fila) {
            $linea = [
                (string) ($fila['tipo_documento'] ?? ''),
                (string) ($fila['documento'] ?? ''),
                (string) ($fila['nombres'] ?? ''),
                (string) ($fila['apellidos'] ?? ''),
                (string) ($fila['numero_ficha'] ?? ''),
                (string) ($fila['programa'] ?? ''),
                (string) ($fila['estado_cuestionario'] ?? ''),
            ];

            foreach ($codigos as $codigo) {
                $linea[] = implode(
                    ' | ',
                    $fila['respuestas_af'][$codigo] ?? []
                );
            }

            fputcsv(
                $salida,
                array_map([$this, 'celdaCsv'], $linea),
                ';',
                '"',
                '\\'
            );
        }

        fclose($salida);
        exit;
    }
}