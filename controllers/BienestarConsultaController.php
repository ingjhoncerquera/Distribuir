<?php
declare(strict_types=1);

class BienestarConsultaController extends Controller
{
    /**
     * Preguntas abiertas (texto libre). No se listan sus respuestas como
     * opciones; se consultan con el buscador de palabras.
     * Además, toda pregunta con más de MAX_OPCIONES_LISTA respuestas
     * distintas se trata automáticamente como abierta.
     */
    private const PREGUNTAS_TEXTO_LIBRE = [];
    private const MAX_OPCIONES_LISTA = 40;

    /** Máximo de filas que se pintan en pantalla (el CSV exporta todas). */
    private const MAX_FILAS_VISTA = 500;

    /**
     * Preguntas con datos de salud, consumo o sexualidad que NO se
     * incluyen en el CSV. Déjelo vacío ([]) para exportarlas también.
     */
    private const CODIGOS_NO_EXPORTAR = [
        'PP-01',
        'PP-02',
        'PP-02-FRECUENCIA',
        'PP-03',
    ];

    private BienestarConsultaModel $model;

    public function __construct()
    {
        $this->model = new BienestarConsultaModel();
    }

    public function prevencion(): void
    {
        Auth::requirePermission('bienestar.prevencion.ver');

        $busqueda = trim(
            (string) $this->get('busqueda', '')
        );

        $idFicha = max(
            0,
            (int) $this->get('id_ficha', 0)
        );

        $idAprendiz = max(
            0,
            (int) $this->get('id_aprendiz', 0)
        );

        $estadoCuestionario = trim(
            (string) $this->get('estado_cuestionario', '')
        );

        $estadosPermitidos = [
            '',
            'pendiente',
            'borrador',
            'completado'
        ];

        if (!in_array(
            $estadoCuestionario,
            $estadosPermitidos,
            true
        )) {
            $estadoCuestionario = '';
        }

        /*
         * Filtros por respuestas de Promoción y Prevención.
         *
         *  - pp[PP-06]=Salud mental ... filtros rápidos por pregunta
         *  - pp_pregunta / pp_respuesta ... cualquier pregunta PP y su opción
         *  - texto_respuesta ......... palabra o frase dentro de las respuestas
         */
        $filtrosRapidos = $this->leerFiltrosRapidos($_GET['pp'] ?? []);

        $ppPregunta = $this->normalizarCodigo(
            (string) ($_GET['pp_pregunta'] ?? '')
        );

        if (preg_match('/^PP-\d{1,3}(?:-[A-Z]+)?$/', $ppPregunta) !== 1) {
            $ppPregunta = '';
        }

        $ppRespuesta = $ppPregunta !== ''
            ? $this->limpiarTexto($_GET['pp_respuesta'] ?? '')
            : '';

        $textoRespuesta = $this->limpiarTexto($_GET['texto_respuesta'] ?? '');

        /*
         * El filtro de estado de la vista usa "en_proceso"; en base de
         * datos ese estado se guarda como "borrador".
         */
        $estadoSolicitado = strtolower(
            trim((string) ($_GET['estado_cuestionario'] ?? ''))
        );

        $estadoRespuestas = match ($estadoSolicitado) {
            'en_proceso', 'borrador' => 'borrador',
            'completado' => 'completado',
            default => '',
        };

        /*
         * Buscador de aprendices con matrícula activa.
         * Incluye quienes todavía no tienen cuestionario.
         */
        $aprendices = $this->model->buscarAprendices(
            $busqueda,
            $idFicha,
            $estadoCuestionario
        );

        /*
         * Resumen general o por ficha.
         */
        $resumen = $this->model->obtenerResumenPorFicha(
            $idFicha
        );

        /*
         * Estadísticas de respuestas de cuestionarios
         * completados en las matrículas activas.
         */
        $estadisticas = $this->model
            ->obtenerEstadisticasRespuestas($idFicha);

        /*
         * Todas las respuestas PP de cada aprendiz
         * (cuestionario más reciente por aprendiz y ficha).
         */
        $filasPP = $this->model->obtenerRespuestasPorComponente(
            'PP',
            $idFicha
        );

        $catalogoPP = $this->construirCatalogo($filasPP);

        $totalConRespuestasPP = 0;

        foreach ($filasPP as $filaPP) {
            if (($filaPP['respuestas_componente'] ?? []) !== []) {
                $totalConRespuestasPP++;
            }
        }

        $criterios = [];

        foreach ($filtrosRapidos as $codigo => $valor) {
            $criterios[] = ['codigo' => $codigo, 'valor' => $valor];
        }

        if ($ppPregunta !== '') {
            $criterios[] = ['codigo' => $ppPregunta, 'valor' => $ppRespuesta];
        }

        $hayFiltrosRespuesta = $criterios !== [] || $textoRespuesta !== '';

        $busquedaNormalizada = $this->normalizarTexto($busqueda);

        $aprendicesFiltrados = [];

        foreach ($filasPP as $fila) {
            $respuestasPP = $fila['respuestas_componente'] ?? [];

            // Sin filtros se listan quienes ya contestaron alguna pregunta PP.
            if (!$hayFiltrosRespuesta && $respuestasPP === []) {
                continue;
            }

            if ($busqueda !== '') {
                $nombre = $this->normalizarTexto(
                    (string) ($fila['nombre_completo'] ?? '')
                );
                $documento = $this->normalizarTexto(
                    (string) ($fila['documento'] ?? '')
                );
                $ficha = $this->normalizarTexto(
                    (string) ($fila['numero_ficha'] ?? '')
                );

                if (
                    !str_contains($nombre, $busquedaNormalizada)
                    && !str_contains($documento, $busquedaNormalizada)
                    && !str_contains($ficha, $busquedaNormalizada)
                ) {
                    continue;
                }
            }

            if (
                $estadoRespuestas !== ''
                && strtolower((string) ($fila['estado_cuestionario'] ?? ''))
                    !== $estadoRespuestas
            ) {
                continue;
            }

            $coincidencias = $this->evaluarFiltros(
                $fila,
                $criterios,
                $textoRespuesta,
                $ppPregunta
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
            $codigosExportables = array_values(array_diff(
                array_keys($catalogoPP),
                self::CODIGOS_NO_EXPORTAR
            ));

            $this->exportarCsv($aprendicesFiltrados, $codigosExportables);
            return;
        }

        /*
         * Detalle individual del aprendiz seleccionado.
         */
        $caracterizacion = null;
        $aprendizSeleccionado = null;
        $cuestionarios = [];

        if ($idAprendiz > 0) {
            $caracterizacion =
                $this->model->obtenerCaracterizacion(
                    $idAprendiz
                );

            if ($caracterizacion !== null) {
                $aprendizSeleccionado = $caracterizacion;

                $cuestionarios =
                    $this->model->obtenerCuestionarios(
                        $idAprendiz
                    );
            }
        }

        $this->view(
            'bienestar/consultas/prevencion',
            [
                'tituloPagina' =>
                    'Consulta de Promoción y Prevención',

                'titulo' =>
                    'Consulta de Promoción y Prevención',

                'busqueda' => $busqueda,
                'idFicha' => $idFicha,
                'idAprendiz' => $idAprendiz,

                'estadoCuestionario' =>
                    $estadoCuestionario,

                'fichas' =>
                    $this->model->obtenerFichas(),

                'aprendices' => $aprendices,

                'resumen' => $resumen,

                'estadisticas' => $estadisticas,

                'caracterizacion' => $caracterizacion,

                // CORRECCIÓN: enviar el aprendiz seleccionado a la vista.
                'aprendizSeleccionado' => $aprendizSeleccionado,

                'cuestionarios' => $cuestionarios,

                // Búsqueda por respuestas
                'catalogoPP' => $catalogoPP,
                'filtrosRapidos' => $filtrosRapidos,
                'ppPregunta' => $ppPregunta,
                'ppRespuesta' => $ppRespuesta,
                'textoRespuesta' => $textoRespuesta,
                'hayFiltrosRespuesta' => $hayFiltrosRespuesta,
                'aprendicesFiltrados' => $aprendicesFiltrados,
                'totalConRespuestasPP' => $totalConRespuestasPP,
                'maxFilasVista' => self::MAX_FILAS_VISTA,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Utilidades de búsqueda por respuestas
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
     * Minúsculas y sin tildes, para comparar "Salud mental" con
     * "salud mental" o "Sexualidad" con "sexualidád".
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
     * Valida el arreglo pp[PP-xx]=valor recibido por GET.
     */
    private function leerFiltrosRapidos(mixed $entrada): array
    {
        $filtros = [];

        if (!is_array($entrada)) {
            return $filtros;
        }

        foreach ($entrada as $codigo => $valor) {
            $codigo = $this->normalizarCodigo((string) $codigo);

            if (preg_match('/^PP-\d{1,3}(?:-[A-Z]+)?$/', $codigo) !== 1) {
                continue;
            }

            $valor = $this->limpiarTexto($valor);

            if ($valor !== '') {
                $filtros[$codigo] = $valor;
            }
        }

        return $filtros;
    }

    /**
     * Catálogo por pregunta con las opciones encontradas y cuántos
     * aprendices eligieron cada una.
     */
    private function construirCatalogo(array $filasPP): array
    {
        $catalogo = [];

        foreach ($filasPP as $fila) {
            foreach (($fila['respuestas_componente'] ?? []) as $codigo => $opciones) {
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

        foreach ($catalogo as &$datos) {
            // Demasiadas respuestas distintas: es una pregunta abierta.
            if (count($datos['opciones']) > self::MAX_OPCIONES_LISTA) {
                $datos['libre'] = true;
                $datos['opciones'] = [];
                continue;
            }

            // Más elegidas primero.
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
        $respuestas = $fila['respuestas_componente'] ?? [];
        $coincidencias = [];

        foreach ($criterios as $criterio) {
            $opciones = $respuestas[$criterio['codigo']] ?? [];

            if ($opciones === []) {
                return null;
            }

            // Solo se pidió "quienes respondieron esta pregunta".
            if ($criterio['valor'] === '') {
                // Si además hay una palabra, las coincidencias las aporta ella.
                if ($texto === '') {
                    $coincidencias[] = $criterio['codigo'] . ': '
                        . implode(', ', $opciones);
                }

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
        $nombreArchivo = 'prevencion_' . date('Ymd_His') . '.csv';

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
                    $fila['respuestas_componente'][$codigo] ?? []
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