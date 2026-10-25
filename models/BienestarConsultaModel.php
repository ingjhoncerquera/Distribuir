<?php

declare(strict_types=1);

class BienestarConsultaModel
{
    private PDO $db;

    private const PERMISOS_COMPONENTES = [
        'CS' => 'bienestar.caracterizacion.ver',
        'PP' => 'bienestar.prevencion.ver',
        'HB' => 'bienestar.habilidades_blandas.ver',
        'AF' => 'bienestar.actividad_fisica.ver',
        'HA' => 'bienestar.habilidades_artisticas.ver',
    ];

    public function __construct()
    {
        $this->db = (new Database())->conectar();
    }

    /**
     * Componentes autorizados para el usuario actual.
     */
    private function obtenerComponentesPermitidos(): array
    {
        $permitidos = [];

        foreach (self::PERMISOS_COMPONENTES as $codigo => $permiso) {
            if (Auth::can($permiso)) {
                $permitidos[] = $codigo;
            }
        }

        return $permitidos;
    }

    /**
     * Identifica el componente al que pertenece una pregunta.
     */
    private function obtenerComponentePregunta(string $pregunta): ?string
    {
        $pregunta = strtoupper(
            str_replace('_', '-', trim($pregunta))
        );

        if (
            preg_match(
                '/^(CS|PP|HB|AF|HA)(?:-|$)/',
                $pregunta,
                $coincidencias
            )
        ) {
            return $coincidencias[1];
        }

        return null;
    }

    /**
     * Normaliza un código de pregunta.
     */
    private function normalizarCodigoPregunta(string $codigo): string
    {
        return strtoupper(
            str_replace('_', '-', trim($codigo))
        );
    }

    /**
     * Obtiene la respuesta de una pregunta guardada como estructura.
     */
    private function extraerValorRespuesta(mixed $valor): mixed
    {
        if (!is_array($valor)) {
            if (is_string($valor)) {
                $texto = trim($valor);

                if (
                    $texto !== ''
                    && (
                        str_starts_with($texto, '[')
                        || str_starts_with($texto, '{')
                    )
                ) {
                    $decodificado = json_decode($texto, true);

                    if (
                        json_last_error() === JSON_ERROR_NONE
                        && is_array($decodificado)
                    ) {
                        return $this->extraerValorRespuesta(
                            $decodificado
                        );
                    }
                }
            }

            return $valor;
        }

        /*
         * Formatos frecuentes utilizados por los formularios:
         * {"respuesta":"Sí"}
         * {"valor":"Sí"}
         * {"opcion":"Sí"}
         * {"respuesta_json":"[...]"}
         */
        foreach (
            [
                'respuesta',
                'valor',
                'respuesta_texto',
                'opcion',
                'respuesta_json',
                'opciones',
                'answer',
                'answers',
            ] as $campo
        ) {
            if (array_key_exists($campo, $valor)) {
                return $this->extraerValorRespuesta($valor[$campo]);
            }
        }

        /*
         * Si es una lista de opciones, se conserva la lista.
         */
        return $valor;
    }

    /**
     * Extrae preguntas y respuestas de diferentes estructuras JSON.
     *
     * Admite:
     * - {"PP-01":"Respuesta"}
     * - {"respuestas":{"PP-01":"Respuesta"}}
     * - {"data":{"respuestas":{"PP-01":"Respuesta"}}}
     * - [{"codigo":"PP-01","respuesta":"Respuesta"}]
     * - {"PP-01":{"valor":"Respuesta"}}
     *
     * Conserva también los demás componentes autorizados.
     */
    private function normalizarRespuestasCuestionario(
        array $respuestas
    ): array {
        $resultado = [];

        $recorrer = function (array $elementos) use (
            &$recorrer,
            &$resultado
        ): void {
            foreach ($elementos as $clave => $valor) {
                $codigoClave = is_string($clave)
                    ? $this->normalizarCodigoPregunta($clave)
                    : '';

                /*
                 * Pregunta identificada directamente por su código.
                 */
                if (
                    $codigoClave !== ''
                    && $this->obtenerComponentePregunta($codigoClave) !== null
                ) {
                    $resultado[$codigoClave] =
                        $this->extraerValorRespuesta($valor);

                    continue;
                }

                if (!is_array($valor)) {
                    continue;
                }

                /*
                 * Pregunta guardada como objeto dentro de una lista.
                 */
                $codigoInterno = $valor['codigo']
                    ?? $valor['codigo_pregunta']
                    ?? $valor['pregunta_codigo']
                    ?? $valor['id_pregunta_codigo']
                    ?? null;

                if (
                    $codigoInterno !== null
                    && trim((string) $codigoInterno) !== ''
                ) {
                    $codigoInterno = $this->normalizarCodigoPregunta(
                        (string) $codigoInterno
                    );

                    if (
                        $this->obtenerComponentePregunta(
                            $codigoInterno
                        ) !== null
                    ) {
                        $resultado[$codigoInterno] =
                            $this->extraerValorRespuesta($valor);

                        continue;
                    }
                }

                /*
                 * Contenedores de respuestas de distintos formularios.
                 */
                $recorrer($valor);
            }
        };

        $recorrer($respuestas);

        return $resultado;
    }

    /**
     * Filtra respuestas según los permisos del usuario.
     */
    private function filtrarRespuestasAutorizadas(
        array $respuestas
    ): array {
        $permitidos = $this->obtenerComponentesPermitidos();

        $tieneAccesoTotal = count($permitidos)
            === count(self::PERMISOS_COMPONENTES);

        $filtradas = [];

        foreach ($respuestas as $pregunta => $respuesta) {
            if (!is_string($pregunta)) {
                continue;
            }

            $componente = $this->obtenerComponentePregunta(
                $pregunta
            );

            if ($componente !== null) {
                if (in_array($componente, $permitidos, true)) {
                    $filtradas[$pregunta] = $respuesta;
                }

                continue;
            }

            if ($tieneAccesoTotal) {
                $filtradas[$pregunta] = $respuesta;
            }
        }

        return $filtradas;
    }

    /**
     * Busca aprendices con matrícula activa.
     * Selecciona el cuestionario más reciente por aprendiz y ficha
     * para evitar duplicar aprendices en el listado.
     */
    public function buscarAprendices(
        string $busqueda = '',
        int $idFicha = 0,
        string $estadoCuestionario = ''
    ): array {
        $sql = "
            SELECT
                a.id AS id_aprendiz,
                p.documento,
                p.tipo_documento,
                p.nombres,
                p.apellidos,
                f.id AS id_ficha,
                f.numero_ficha,
                pr.nombre AS programa,
                cb.id AS id_cuestionario,
                cb.estado AS estado_cuestionario,
                cb.fecha_inicio,
                cb.fecha_actualizacion,
                cb.fecha_completado,
                CASE
                    WHEN cb.id IS NULL THEN 'pendiente'
                    WHEN cb.estado = 'borrador' THEN 'borrador'
                    WHEN cb.estado = 'completado' THEN 'completado'
                    ELSE 'pendiente'
                END AS estado_consulta
            FROM matriculas m
            INNER JOIN aprendices a
                ON a.id = m.id_aprendiz
            INNER JOIN personas p
                ON p.id = a.id_persona
            INNER JOIN fichas f
                ON f.id = m.id_ficha
            LEFT JOIN programas pr
                ON pr.id = f.id_programa
            LEFT JOIN cuestionarios_bienestar cb
                ON cb.id = (
                    SELECT MAX(cb2.id)
                    FROM cuestionarios_bienestar cb2
                    WHERE cb2.id_aprendiz = a.id
                      AND cb2.id_ficha = f.id
                )
            WHERE m.estado = 'activa'
        ";

        $params = [];

        if ($busqueda !== '') {
            $sql .= "
                AND (
                    p.documento LIKE :documento
                    OR p.nombres LIKE :nombres
                    OR p.apellidos LIKE :apellidos
                    OR CONCAT_WS(
                        ' ',
                        p.nombres,
                        p.apellidos
                    ) LIKE :nombre_completo
                )
            ";

            $valor = '%' . $busqueda . '%';

            $params[':documento'] = $valor;
            $params[':nombres'] = $valor;
            $params[':apellidos'] = $valor;
            $params[':nombre_completo'] = $valor;
        }

        if ($idFicha > 0) {
            $sql .= ' AND f.id = :id_ficha ';
            $params[':id_ficha'] = $idFicha;
        }

        if ($estadoCuestionario === 'pendiente') {
            $sql .= ' AND cb.id IS NULL ';
        } elseif (
            in_array(
                $estadoCuestionario,
                ['borrador', 'completado'],
                true
            )
        ) {
            $sql .= ' AND cb.estado = :estado_cuestionario ';
            $params[':estado_cuestionario'] = $estadoCuestionario;
        }

        $sql .= "
            ORDER BY p.apellidos ASC, p.nombres ASC
            LIMIT 200
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista fichas con matrícula activa.
     */
    public function obtenerFichas(): array
    {
        $sql = "
            SELECT DISTINCT
                f.id,
                f.numero_ficha,
                pr.nombre AS programa
            FROM fichas f
            INNER JOIN matriculas m
                ON m.id_ficha = f.id
            LEFT JOIN programas pr
                ON pr.id = f.id_programa
            WHERE m.estado = 'activa'
            ORDER BY f.numero_ficha DESC
        ";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Información personal, ficha y matrícula activa.
     */
    public function obtenerCaracterizacion(
        int $idAprendiz
    ): ?array {
        $sql = "
            SELECT
                a.*,
                p.*,
                f.id AS id_ficha,
                f.numero_ficha,
                pr.nombre AS programa,
                m.estado AS estado_matricula
            FROM aprendices a
            INNER JOIN personas p
                ON p.id = a.id_persona
            LEFT JOIN matriculas m
                ON m.id = (
                    SELECT MAX(m2.id)
                    FROM matriculas m2
                    WHERE m2.id_aprendiz = a.id
                      AND m2.estado = 'activa'
                )
            LEFT JOIN fichas f
                ON f.id = m.id_ficha
            LEFT JOIN programas pr
                ON pr.id = f.id_programa
            WHERE a.id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idAprendiz]);

        $datos = $stmt->fetch(PDO::FETCH_ASSOC);

        return $datos ?: null;
    }

    /**
     * Cuestionarios del aprendiz con respuestas filtradas
     * según los permisos del usuario.
     */
    public function obtenerCuestionarios(
        int $idAprendiz
    ): array {
        $sql = "
            SELECT
                cb.id,
                cb.id_aprendiz,
                cb.id_ficha,
                cb.respuestas,
                cb.estado,
                cb.fecha_inicio,
                cb.fecha_actualizacion,
                cb.fecha_completado,
                f.numero_ficha,
                pr.nombre AS programa
            FROM cuestionarios_bienestar cb
            LEFT JOIN fichas f
                ON f.id = cb.id_ficha
            LEFT JOIN programas pr
                ON pr.id = f.id_programa
            WHERE cb.id_aprendiz = :id
            ORDER BY cb.fecha_actualizacion DESC, cb.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idAprendiz]);

        $cuestionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($cuestionarios as &$cuestionario) {
            $respuestas = json_decode(
                (string) ($cuestionario['respuestas'] ?? ''),
                true
            );

            if (!is_array($respuestas)) {
                $respuestas = [];
            }

            $respuestas = $this->normalizarRespuestasCuestionario(
                $respuestas
            );

            $cuestionario['respuestas'] =
                $this->filtrarRespuestasAutorizadas($respuestas);
        }

        unset($cuestionario);

        return $cuestionarios;
    }

    /**
     * Resumen general o por ficha.
     *
     * Cada matrícula activa se cuenta una sola vez y su estado
     * corresponde al cuestionario de mayor ID de esa ficha.
     */
    public function obtenerResumenPorFicha(
        int $idFicha = 0
    ): array {
        $sql = "
            SELECT
                COUNT(*) AS total_matriculados,
                COALESCE(
                    SUM(
                        CASE
                            WHEN cb.estado = 'completado' THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS completados,
                COALESCE(
                    SUM(
                        CASE
                            WHEN cb.estado = 'borrador' THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS borradores,
                COALESCE(
                    SUM(
                        CASE
                            WHEN cb.id IS NULL THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS pendientes
            FROM matriculas m
            LEFT JOIN cuestionarios_bienestar cb
                ON cb.id = (
                    SELECT MAX(cb2.id)
                    FROM cuestionarios_bienestar cb2
                    WHERE cb2.id_aprendiz = m.id_aprendiz
                      AND cb2.id_ficha = m.id_ficha
                )
            WHERE m.estado = 'activa'
        ";

        $params = [];

        if ($idFicha > 0) {
            $sql .= ' AND m.id_ficha = :id_ficha ';
            $params[':id_ficha'] = $idFicha;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $resumen = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        foreach (
            [
                'total_matriculados',
                'completados',
                'borradores',
                'pendientes',
            ] as $campo
        ) {
            $resumen[$campo] = (int) ($resumen[$campo] ?? 0);
        }

        /*
         * Alias para vistas que utilicen otros nombres.
         */
        $resumen['total_aprendices'] =
            $resumen['total_matriculados'];

        $resumen['total_completados'] =
            $resumen['completados'];

        $resumen['en_proceso'] =
            $resumen['borradores'];

        $resumen['sin_iniciar'] =
            $resumen['pendientes'];

        $total = $resumen['total_matriculados'];

        $resumen['porcentaje_completado'] = $total > 0
            ? round(
                ($resumen['completados'] / $total) * 100,
                1
            )
            : 0;

        return $resumen;
    }

    /**
     * Respuestas de TODAS las preguntas de un componente (PP, AF, HB, ...)
     * de cada aprendiz con matrícula activa, usando el cuestionario más
     * reciente por aprendiz y ficha.
     *
     * Respeta los permisos: si el usuario no puede consultar el componente
     * devuelve un arreglo vacío.
     *
     * Cada fila incluye los datos del aprendiz y:
     *   'respuestas_componente' => [
     *       'PP-02' => ['Alcohol', 'Tabaco'],
     *       'PP-02-FRECUENCIA' => ['Alcohol: Semanal'],
     *       'PP-06' => ['Salud mental'],
     *   ]
     *
     * Los aprendices sin respuestas del componente se devuelven con
     * 'respuestas_componente' vacío.
     */
    public function obtenerRespuestasPorComponente(
        string $componente,
        int $idFicha = 0
    ): array {
        $componente = strtoupper(trim($componente));

        if (
            !isset(self::PERMISOS_COMPONENTES[$componente])
            || !in_array(
                $componente,
                $this->obtenerComponentesPermitidos(),
                true
            )
        ) {
            return [];
        }

        $sql = "
            SELECT
                a.id AS id_aprendiz,
                p.documento,
                p.tipo_documento,
                p.nombres,
                p.apellidos,
                f.id AS id_ficha,
                f.numero_ficha,
                pr.nombre AS programa,
                cb.id AS id_cuestionario,
                cb.estado AS estado_cuestionario,
                cb.fecha_actualizacion,
                cb.respuestas
            FROM matriculas m
            INNER JOIN aprendices a
                ON a.id = m.id_aprendiz
            INNER JOIN personas p
                ON p.id = a.id_persona
            INNER JOIN fichas f
                ON f.id = m.id_ficha
            LEFT JOIN programas pr
                ON pr.id = f.id_programa
            INNER JOIN cuestionarios_bienestar cb
                ON cb.id = (
                    SELECT MAX(cb2.id)
                    FROM cuestionarios_bienestar cb2
                    WHERE cb2.id_aprendiz = a.id
                      AND cb2.id_ficha = f.id
                )
            WHERE m.estado = 'activa'
        ";

        $params = [];

        if ($idFicha > 0) {
            $sql .= ' AND f.id = :id_ficha ';
            $params[':id_ficha'] = $idFicha;
        }

        $sql .= ' ORDER BY p.apellidos ASC, p.nombres ASC ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $resultado = [];

        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $respuestas = json_decode(
                (string) ($fila['respuestas'] ?? ''),
                true
            );

            $porCodigo = [];

            if (is_array($respuestas)) {
                $normalizadas = $this->normalizarRespuestasCuestionario(
                    $respuestas
                );

                foreach ($normalizadas as $codigo => $valor) {
                    if (
                        $this->obtenerComponentePregunta((string) $codigo)
                        !== $componente
                    ) {
                        continue;
                    }

                    $opciones = $this->convertirRespuestaEnOpciones($valor);

                    if ($opciones !== []) {
                        $porCodigo[(string) $codigo] = $opciones;
                    }
                }
            }

            ksort($porCodigo, SORT_NATURAL);

            $fila['nombre_completo'] = trim(
                (string) ($fila['nombres'] ?? '') . ' ' .
                (string) ($fila['apellidos'] ?? '')
            );

            $fila['respuestas_componente'] = $porCodigo;
            unset($fila['respuestas']);

            $resultado[] = $fila;
        }

        return $resultado;
    }

    /**
     * Convierte una respuesta (simple, múltiple o de tipo objeto) en una
     * lista de textos para poder buscar y agrupar.
     *
     * - "Sí"                          => ['Sí']
     * - ["Alcohol", "Tabaco"]         => ['Alcohol', 'Tabaco']
     * - {"Alcohol": "Semanal"}        => ['Alcohol: Semanal']
     * - {"Alcohol": true}             => ['Alcohol']
     */
    private function convertirRespuestaEnOpciones(mixed $valor): array
    {
        if (is_bool($valor)) {
            return [$valor ? 'Sí' : 'No'];
        }

        if (is_string($valor)) {
            $texto = trim($valor);

            if (
                $texto !== ''
                && (
                    str_starts_with($texto, '[')
                    || str_starts_with($texto, '{')
                )
            ) {
                $decodificado = json_decode($texto, true);

                if (
                    json_last_error() === JSON_ERROR_NONE
                    && is_array($decodificado)
                ) {
                    return $this->convertirRespuestaEnOpciones(
                        $decodificado
                    );
                }
            }

            return $texto === '' ? [] : [$texto];
        }

        if (is_scalar($valor)) {
            $texto = trim((string) $valor);

            return $texto === '' ? [] : [$texto];
        }

        if (!is_array($valor)) {
            return [];
        }

        $esLista = $valor === array_values($valor);
        $opciones = [];

        foreach ($valor as $clave => $elemento) {
            $prefijo = (!$esLista && is_string($clave) && trim($clave) !== '')
                ? trim($clave)
                : '';

            if (is_array($elemento)) {
                $elemento = $this->extraerValorRespuesta($elemento);
            }

            if (is_array($elemento)) {
                foreach (
                    $this->convertirRespuestaEnOpciones($elemento) as $sub
                ) {
                    $opciones[] = $prefijo !== ''
                        ? $prefijo . ': ' . $sub
                        : $sub;
                }

                continue;
            }

            if (is_bool($elemento)) {
                if ($prefijo !== '') {
                    // {"Alcohol": true} => "Alcohol"; false se ignora.
                    if ($elemento) {
                        $opciones[] = $prefijo;
                    }
                } else {
                    $opciones[] = $elemento ? 'Sí' : 'No';
                }

                continue;
            }

            if (!is_scalar($elemento)) {
                continue;
            }

            $texto = trim((string) $elemento);

            if ($texto === '') {
                continue;
            }

            $opciones[] = $prefijo !== ''
                ? $prefijo . ': ' . $texto
                : $texto;
        }

        // Sin repetir la misma opción dentro de una respuesta.
        $unicas = [];

        foreach ($opciones as $opcion) {
            $clave = function_exists('mb_strtolower')
                ? mb_strtolower($opcion, 'UTF-8')
                : strtolower($opcion);

            if (!isset($unicas[$clave])) {
                $unicas[$clave] = $opcion;
            }
        }

        return array_values($unicas);
    }

    /**
     * Estadísticas de respuestas de cuestionarios completados
     * asociados a matrículas activas.
     *
     * Normaliza las respuestas antes de agruparlas por opción.
     * La estructura resultante es compatible con las vistas:
     *
     * [
     *   'PP-01' => [
     *     'pregunta' => 'PP-01',
     *     'total_respuestas' => 2,
     *     'sin_respuesta' => 0,
     *     'respuestas' => [
     *       'Sí' => [
     *         'cantidad' => 2,
     *         'aprendices' => [1, 2]
     *       ]
     *     ]
     *   ]
     * ]
     */
    public function obtenerEstadisticasRespuestas(
        int $idFicha = 0
    ): array {
        $componentesPermitidos =
            $this->obtenerComponentesPermitidos();

        if ($componentesPermitidos === []) {
            return [];
        }

        $sql = "
            SELECT
                cb.id,
                cb.id_aprendiz,
                cb.respuestas
            FROM cuestionarios_bienestar cb
            INNER JOIN matriculas m
                ON m.id_aprendiz = cb.id_aprendiz
                AND m.id_ficha = cb.id_ficha
                AND m.estado = 'activa'
            WHERE cb.estado = 'completado'
        ";

        $params = [];

        if ($idFicha > 0) {
            $sql .= ' AND cb.id_ficha = :id_ficha ';
            $params[':id_ficha'] = $idFicha;
        }

        $sql .= ' ORDER BY cb.id ASC ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $estadisticas = [];

        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $respuestasOriginales = json_decode(
                (string) ($fila['respuestas'] ?? ''),
                true
            );

            if (!is_array($respuestasOriginales)) {
                continue;
            }

            $respuestasNormalizadas =
                $this->normalizarRespuestasCuestionario(
                    $respuestasOriginales
                );

            foreach (
                $respuestasNormalizadas as $pregunta => $respuesta
            ) {
                $componente = $this->obtenerComponentePregunta(
                    (string) $pregunta
                );

                if (
                    $componente === null
                    || !in_array(
                        $componente,
                        $componentesPermitidos,
                        true
                    )
                ) {
                    continue;
                }

                if (!isset($estadisticas[$pregunta])) {
                    $estadisticas[$pregunta] = [
                        'codigo' => $pregunta,
                        'pregunta' => $pregunta,
                        'total_respuestas' => 0,
                        'sin_respuesta' => 0,
                        'respuestas' => [],
                    ];
                }

                if (
                    $respuesta === null
                    || $respuesta === ''
                    || $respuesta === []
                ) {
                    $estadisticas[$pregunta]['sin_respuesta']++;
                    continue;
                }

                /*
                 * Una pregunta puede tener una respuesta simple
                 * o varias opciones seleccionadas.
                 */
                $opciones = is_array($respuesta)
                    ? $respuesta
                    : [$respuesta];

                $opcionesValidas = [];

                foreach ($opciones as $opcion) {
                    if (is_array($opcion)) {
                        $opcion = $this->extraerValorRespuesta(
                            $opcion
                        );
                    }

                    if (!is_scalar($opcion)) {
                        continue;
                    }

                    $valor = trim((string) $opcion);

                    if ($valor === '') {
                        continue;
                    }

                    $opcionesValidas[] = $valor;
                }

                /*
                 * Evita contar dos veces la misma opción cuando
                 * viene repetida dentro de una respuesta múltiple.
                 */
                $opcionesValidas = array_values(
                    array_unique($opcionesValidas)
                );

                if ($opcionesValidas === []) {
                    $estadisticas[$pregunta]['sin_respuesta']++;
                    continue;
                }

                foreach ($opcionesValidas as $valor) {
                    if (
                        !isset(
                            $estadisticas[$pregunta]
                                ['respuestas'][$valor]
                        )
                    ) {
                        $estadisticas[$pregunta]['respuestas'][$valor] = [
                            'cantidad' => 0,
                            'valor' => $valor,
                            'aprendices' => [],
                        ];
                    }

                    $estadisticas[$pregunta]
                        ['respuestas'][$valor]['cantidad']++;

                    $estadisticas[$pregunta]
                        ['respuestas'][$valor]['aprendices'][] =
                            (int) $fila['id_aprendiz'];

                    $estadisticas[$pregunta]['total_respuestas']++;
                }
            }
        }

        /*
         * Orden consistente para las preguntas y sus opciones.
         */
        ksort($estadisticas, SORT_NATURAL);

        foreach ($estadisticas as &$estadistica) {
            ksort(
                $estadistica['respuestas'],
                SORT_NATURAL
            );
        }

        unset($estadistica);

        return $estadisticas;
    }
}