<?php
declare(strict_types=1);

/**
 * SITA - Controlador de consulta de Habilidades Artísticas.
 */
class BienestarHabilidadesArtisticasController extends Controller
{
    /**
     * Preguntas abiertas (texto libre). No se listan sus respuestas como
     * opciones; se consultan con el buscador de palabras.
     * Además, toda pregunta con más de MAX_OPCIONES_LISTA respuestas
     * distintas se trata automáticamente como abierta.
     */
    private const PREGUNTAS_TEXTO_LIBRE = ['HA-10'];
    private const MAX_OPCIONES_LISTA = 40;

    /** Máximo de filas que se pintan en pantalla (el CSV exporta todas). */
    private const MAX_FILAS_VISTA = 500;

    /**
     * Preguntas que NO se incluyen en el CSV. Vacío: se exportan todas.
     */
    private const CODIGOS_NO_EXPORTAR = [];

    private BienestarHabilidadesArtisticasModel $modelo;

    public function __construct()
    {
        $this->modelo = new BienestarHabilidadesArtisticasModel();
    }

    public function index(): void
    {
        if (!class_exists('Auth') || !Auth::can('bienestar.habilidades_artisticas.ver')) {
            http_response_code(403);
            echo 'No tiene permisos para consultar el módulo de Habilidades Artísticas.';
            return;
        }

        $busqueda = trim((string)($_GET['busqueda'] ?? ''));
        $idFicha = max(0, (int)($_GET['id_ficha'] ?? 0));
        $idAprendiz = max(0, (int)($_GET['id_aprendiz'] ?? 0));

        $estadoCuestionario = strtolower(
            trim((string)($_GET['estado'] ?? 'todos'))
        );

        if (!in_array(
            $estadoCuestionario,
            ['todos', 'pendiente', 'borrador', 'completado'],
            true
        )) {
            $estadoCuestionario = 'todos';
        }

        /*
         * Filtros por respuestas de Habilidades Artísticas.
         *
         *  - ha[HA-05]=Guitarra ...... filtros rápidos por pregunta
         *  - ha_pregunta / ha_respuesta ... cualquier pregunta HA y su opción
         *  - texto_respuesta ......... palabra o frase dentro de las respuestas
         */
        $filtrosRapidos = $this->leerFiltrosRapidos($_GET['ha'] ?? []);

        $haPregunta = $this->normalizarCodigo(
            (string)($_GET['ha_pregunta'] ?? '')
        );

        if (preg_match('/^HA-\d{1,3}(?:-[A-Z]+)?$/', $haPregunta) !== 1) {
            $haPregunta = '';
        }

        $haRespuesta = $haPregunta !== ''
            ? $this->limpiarTexto($_GET['ha_respuesta'] ?? '')
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
         * Todas las respuestas HA de cada aprendiz
         * (cuestionario más reciente por aprendiz y ficha).
         */
        $filasHA = $this->modelo->obtenerRespuestasHabilidadesArtisticas(
            $idFicha
        );

        $catalogoHA = $this->construirCatalogo($filasHA);

        $totalConRespuestasHA = 0;

        foreach ($filasHA as $filaHA) {
            if (($filaHA['respuestas_ha'] ?? []) !== []) {
                $totalConRespuestasHA++;
            }
        }

        $criterios = [];

        foreach ($filtrosRapidos as $codigo => $valor) {
            $criterios[] = ['codigo' => $codigo, 'valor' => $valor];
        }

        if ($haPregunta !== '') {
            $criterios[] = ['codigo' => $haPregunta, 'valor' => $haRespuesta];
        }

        $hayFiltrosRespuesta = $criterios !== [] || $textoRespuesta !== '';

        $busquedaNormalizada = $this->normalizarTexto($busqueda);

        $aprendicesFiltrados = [];

        foreach ($filasHA as $fila) {
            $respuestasHA = $fila['respuestas_ha'] ?? [];

            // Sin filtros se listan quienes ya contestaron alguna pregunta HA.
            if (!$hayFiltrosRespuesta && $respuestasHA === []) {
                continue;
            }

            if ($busqueda !== '') {
                $nombre = $this->normalizarTexto(
                    (string)($fila['nombre_completo'] ?? '')
                );
                $documento = $this->normalizarTexto(
                    (string)($fila['documento'] ?? '')
                );

                if (
                    !str_contains($nombre, $busquedaNormalizada)
                    && !str_contains($documento, $busquedaNormalizada)
                ) {
                    continue;
                }
            }

            if (
                $estadoCuestionario !== 'todos'
                && $this->estadoNormalizado(
                    (string)($fila['estado_cuestionario'] ?? '')
                ) !== $estadoCuestionario
            ) {
                continue;
            }

            $coincidencias = $this->evaluarFiltros(
                $fila,
                $criterios,
                $textoRespuesta,
                $haPregunta
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
        if (strtolower((string)($_GET['exportar'] ?? '')) === 'csv') {
            $codigosExportables = array_values(array_diff(
                array_keys($catalogoHA),
                self::CODIGOS_NO_EXPORTAR
            ));

            $this->exportarCsv($aprendicesFiltrados, $codigosExportables);
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
            'bienestar/consultas/habilidades_artisticas',
            [
                'tituloPagina' => 'Consulta de Habilidades Artísticas',
                'titulo' => 'Consulta de Habilidades Artísticas',
                'busqueda' => $busqueda,
                'idFicha' => $idFicha,
                'idAprendiz' => $idAprendiz,
                'estadoCuestionario' => $estadoCuestionario,
                'fichas' => $fichas,
                'aprendices' => $aprendices,
                'resumen' => $resumen,
                'estadisticas' => $estadisticas,
                'cuestionarios' => $cuestionarios,
                'aprendizSeleccionado' => $aprendizSeleccionado,

                // Búsqueda por respuestas
                'catalogoHA' => $catalogoHA,
                'filtrosRapidos' => $filtrosRapidos,
                'haPregunta' => $haPregunta,
                'haRespuesta' => $haRespuesta,
                'textoRespuesta' => $textoRespuesta,
                'hayFiltrosRespuesta' => $hayFiltrosRespuesta,
                'aprendicesFiltrados' => $aprendicesFiltrados,
                'totalConRespuestasHA' => $totalConRespuestasHA,
                'maxFilasVista' => self::MAX_FILAS_VISTA,
            ]
        );
    }

    /**
     * Estado del cuestionario como lo maneja el filtro de esta vista:
     * pendiente, borrador o completado.
     */
    private function estadoNormalizado(string $estado): string
    {
        $estado = strtolower(trim($estado));

        if ($estado === 'completado') {
            return 'completado';
        }

        if (in_array($estado, ['borrador', 'en_proceso', 'en proceso'], true)) {
            return 'borrador';
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
     * Minúsculas y sin tildes, para comparar "Guitarra" con
     * "guitarra" o "Danza folclórica" con "danza folclorica".
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
     * Valida el arreglo ha[HA-xx]=valor recibido por GET.
     */
    private function leerFiltrosRapidos(mixed $entrada): array
    {
        $filtros = [];

        if (!is_array($entrada)) {
            return $filtros;
        }

        foreach ($entrada as $codigo => $valor) {
            $codigo = $this->normalizarCodigo((string) $codigo);

            if (preg_match('/^HA-\d{1,3}(?:-[A-Z]+)?$/', $codigo) !== 1) {
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
    private function construirCatalogo(array $filasHA): array
    {
        $catalogo = [];

        foreach ($filasHA as $fila) {
            foreach (($fila['respuestas_ha'] ?? []) as $codigo => $opciones) {
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
        $respuestas = $fila['respuestas_ha'] ?? [];
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
        $nombreArchivo = 'habilidades_artisticas_' . date('Ymd_His') . '.csv';

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
                    $fila['respuestas_ha'][$codigo] ?? []
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