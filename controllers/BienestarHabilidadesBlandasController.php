<?php

declare(strict_types=1);

class BienestarHabilidadesBlandasController extends Controller
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
     * Preguntas sobre estrés, redes de apoyo y facilidad para pedir ayuda
     * que NO se incluyen en el CSV. Déjelo vacío ([]) para exportarlas.
     */
    private const CODIGOS_NO_EXPORTAR = [
        'HB-01',
        'HB-02',
        'HB-03',
    ];

    private BienestarHabilidadesBlandasModel $model;

    public function __construct()
    {
        $this->model = new BienestarHabilidadesBlandasModel();
    }

    /**
     * Consulta de Habilidades Blandas.
     * Solo lectura.
     */
    public function index(): void
    {
        Auth::requireLogin();

        // Bienestar puede consultar todos los componentes.
        // Los demás perfiles necesitan el permiso específico.
        if (
            !Auth::hasRole('Bienestar')
            && !Auth::can('bienestar.habilidades_blandas.ver')
        ) {
            http_response_code(403);
            die('No tiene permisos para consultar Habilidades Blandas.');
        }

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

        $estadoCuestionario = strtolower(
            trim((string) $this->get('estado_cuestionario', 'todos'))
        );

        $estadosPermitidos = [
            'todos',
            'pendiente',
            'en_proceso',
            'completado',
        ];

        if (!in_array(
            $estadoCuestionario,
            $estadosPermitidos,
            true
        )) {
            $estadoCuestionario = 'todos';
        }

        /*
         * Filtros por respuestas de Habilidades Blandas.
         *
         *  - hb[HB-09]=Taller ........ filtros rápidos por pregunta
         *  - hb_pregunta / hb_respuesta ... cualquier pregunta HB y su opción
         *  - texto_respuesta ......... palabra o frase dentro de las respuestas
         */
        $filtrosRapidos = $this->leerFiltrosRapidos($_GET['hb'] ?? []);

        $hbPregunta = $this->normalizarCodigo(
            (string) ($_GET['hb_pregunta'] ?? '')
        );

        if (preg_match('/^HB-\d{1,3}(?:-[A-Z]+)?$/', $hbPregunta) !== 1) {
            $hbPregunta = '';
        }

        $hbRespuesta = $hbPregunta !== ''
            ? $this->limpiarTexto($_GET['hb_respuesta'] ?? '')
            : '';

        $textoRespuesta = $this->limpiarTexto($_GET['texto_respuesta'] ?? '');

        $aprendices = $this->model->buscarAprendices(
            $busqueda,
            $idFicha,
            $estadoCuestionario
        );

        $fichas = $this->model->obtenerFichas();

        $resumen = $this->model->obtenerResumen($idFicha);

        $estadisticas = $this->model->obtenerEstadisticas($idFicha);

        /*
         * Todas las respuestas HB de cada aprendiz
         * (cuestionario más reciente por aprendiz y ficha).
         */
        $filasHB = $this->model->obtenerRespuestasHabilidadesBlandas($idFicha);

        $catalogoHB = $this->construirCatalogo($filasHB);

        $totalConRespuestasHB = 0;

        foreach ($filasHB as $filaHB) {
            if (($filaHB['respuestas_hb'] ?? []) !== []) {
                $totalConRespuestasHB++;
            }
        }

        $criterios = [];

        foreach ($filtrosRapidos as $codigo => $valor) {
            $criterios[] = ['codigo' => $codigo, 'valor' => $valor];
        }

        if ($hbPregunta !== '') {
            $criterios[] = ['codigo' => $hbPregunta, 'valor' => $hbRespuesta];
        }

        $hayFiltrosRespuesta = $criterios !== [] || $textoRespuesta !== '';

        $busquedaNormalizada = $this->normalizarTexto($busqueda);

        $aprendicesFiltrados = [];

        foreach ($filasHB as $fila) {
            $respuestasHB = $fila['respuestas_hb'] ?? [];

            // Sin filtros se listan quienes ya contestaron alguna pregunta HB.
            if (!$hayFiltrosRespuesta && $respuestasHB === []) {
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
                $estadoCuestionario !== 'todos'
                && $this->estadoNormalizado(
                    (string) ($fila['estado_cuestionario'] ?? '')
                ) !== $estadoCuestionario
            ) {
                continue;
            }

            $coincidencias = $this->evaluarFiltros(
                $fila,
                $criterios,
                $textoRespuesta,
                $hbPregunta
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
                array_keys($catalogoHB),
                self::CODIGOS_NO_EXPORTAR
            ));

            $this->exportarCsv($aprendicesFiltrados, $codigosExportables);
            return;
        }

        $aprendizSeleccionado = null;
        $cuestionarios = [];

        if ($idAprendiz > 0) {
            $aprendizSeleccionado =
                $this->model->obtenerAprendizActivo($idAprendiz);

            // Consultar únicamente aprendices con matrícula activa.
            if ($aprendizSeleccionado !== null) {
                $cuestionarios = $this->model->obtenerCuestionarios(
                    $idAprendiz
                );
            } else {
                $idAprendiz = 0;
            }
        }

        $this->view(
            'bienestar/consultas/habilidades_blandas',
            [
                'tituloPagina' => 'Consulta de Habilidades Blandas',
                'titulo' => 'Consulta de Habilidades Blandas',

                'busqueda' => $busqueda,
                'idFicha' => $idFicha,
                'idAprendiz' => $idAprendiz,
                'estadoCuestionario' => $estadoCuestionario,

                'fichas' => $fichas,
                'aprendices' => $aprendices,
                'resumen' => $resumen,
                'estadisticas' => $estadisticas,

                'aprendizSeleccionado' => $aprendizSeleccionado,
                'cuestionarios' => $cuestionarios,

                // Búsqueda por respuestas
                'catalogoHB' => $catalogoHB,
                'filtrosRapidos' => $filtrosRapidos,
                'hbPregunta' => $hbPregunta,
                'hbRespuesta' => $hbRespuesta,
                'textoRespuesta' => $textoRespuesta,
                'hayFiltrosRespuesta' => $hayFiltrosRespuesta,
                'aprendicesFiltrados' => $aprendicesFiltrados,
                'totalConRespuestasHB' => $totalConRespuestasHB,
                'maxFilasVista' => self::MAX_FILAS_VISTA,
            ]
        );
    }

    /**
     * Estado del cuestionario como lo maneja esta consulta:
     * pendiente, en_proceso o completado.
     */
    private function estadoNormalizado(string $estado): string
    {
        $estado = strtolower(trim($estado));

        if ($estado === 'completado') {
            return 'completado';
        }

        if (in_array($estado, ['borrador', 'en_proceso', 'en proceso'], true)) {
            return 'en_proceso';
        }

        return 'pendiente';
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
     * Minúsculas y sin tildes, para comparar "Comunicación" con
     * "comunicacion" o "Empatía" con "EMPATIA".
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
     * Valida el arreglo hb[HB-xx]=valor recibido por GET.
     */
    private function leerFiltrosRapidos(mixed $entrada): array
    {
        $filtros = [];

        if (!is_array($entrada)) {
            return $filtros;
        }

        foreach ($entrada as $codigo => $valor) {
            $codigo = $this->normalizarCodigo((string) $codigo);

            if (preg_match('/^HB-\d{1,3}(?:-[A-Z]+)?$/', $codigo) !== 1) {
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
    private function construirCatalogo(array $filasHB): array
    {
        $catalogo = [];

        foreach ($filasHB as $fila) {
            foreach (($fila['respuestas_hb'] ?? []) as $codigo => $opciones) {
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
        $respuestas = $fila['respuestas_hb'] ?? [];
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
        $nombreArchivo = 'habilidades_blandas_' . date('Ymd_His') . '.csv';

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
                    $fila['respuestas_hb'][$codigo] ?? []
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