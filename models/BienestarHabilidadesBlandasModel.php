<?php

declare(strict_types=1);

class BienestarHabilidadesBlandasModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->conectar();
    }

    /**
     * Listado de aprendices con matrícula activa.
     *
     * Filtros:
     * - Nombre o documento.
     * - Número de ficha.
     * - Estado del cuestionario.
     */
    public function buscarAprendices(
        string $busqueda = '',
        int $idFicha = 0,
        string $estadoCuestionario = 'todos'
    ): array {
        $estadoCuestionario = strtolower(trim($estadoCuestionario));

        $estadosPermitidos = [
            'todos',
            'pendiente',
            'en_proceso',
            'completado',
        ];

        if (!in_array($estadoCuestionario, $estadosPermitidos, true)) {
            $estadoCuestionario = 'todos';
        }

        $sql = "
            SELECT
                a.id AS id_aprendiz,
                a.estado AS estado_aprendiz,
                a.estado AS estado,
                p.documento,
                p.tipo_documento,
                p.nombres,
                p.apellidos,
                p.correo,
                p.correo AS email,
                f.id AS id_ficha,
                f.numero_ficha,
                pr.nombre AS programa,
                pr.nombre AS nombre_programa,
                cb.id AS id_cuestionario,
                cb.estado AS estado_cuestionario,
                cb.fecha_inicio,
                cb.fecha_actualizacion,
                cb.fecha_completado,
                CASE
                    WHEN cb.id IS NULL THEN 'pendiente'
                    WHEN LOWER(TRIM(COALESCE(cb.estado, ''))) = 'completado'
                        THEN 'completado'
                    WHEN LOWER(TRIM(COALESCE(cb.estado, ''))) IN (
                        'borrador',
                        'en_proceso',
                        'en proceso'
                    )
                        THEN 'en_proceso'
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
                ON cb.id_aprendiz = a.id
                AND cb.id_ficha = f.id
            WHERE LOWER(TRIM(COALESCE(m.estado, ''))) = 'activa'
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
                    OR CAST(f.numero_ficha AS CHAR) LIKE :numero_ficha
                )
            ";

            $valor = '%' . $busqueda . '%';

            $params[':documento'] = $valor;
            $params[':nombres'] = $valor;
            $params[':apellidos'] = $valor;
            $params[':nombre_completo'] = $valor;
            $params[':numero_ficha'] = $valor;
        }

        if ($idFicha > 0) {
            $sql .= " AND f.id = :id_ficha";
            $params[':id_ficha'] = $idFicha;
        }

        if ($estadoCuestionario === 'pendiente') {
            $sql .= "
                AND (
                    cb.id IS NULL
                    OR LOWER(TRIM(COALESCE(cb.estado, ''))) NOT IN (
                        'borrador',
                        'en_proceso',
                        'en proceso',
                        'completado'
                    )
                )
            ";
        } elseif ($estadoCuestionario === 'en_proceso') {
            $sql .= "
                AND LOWER(TRIM(COALESCE(cb.estado, ''))) IN (
                    'borrador',
                    'en_proceso',
                    'en proceso'
                )
            ";
        } elseif ($estadoCuestionario === 'completado') {
            $sql .= "
                AND LOWER(TRIM(COALESCE(cb.estado, ''))) = 'completado'
            ";
        }

        $sql .= "
            ORDER BY
                f.numero_ficha DESC,
                p.apellidos ASC,
                p.nombres ASC
            LIMIT 200
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Todas las fichas con matrículas activas.
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
            WHERE LOWER(TRIM(COALESCE(m.estado, ''))) = 'activa'
            ORDER BY f.numero_ficha DESC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los datos completos del aprendiz seleccionado.
     *
     * El aprendiz debe tener al menos una matrícula activa.
     * Se devuelve también la ficha y el programa asociados.
     */
    public function obtenerAprendizActivo(
        int $idAprendiz
    ): ?array {
        if ($idAprendiz <= 0) {
            return null;
        }

        $sql = "
            SELECT
                a.id AS id_aprendiz,
                a.id AS id,
                a.estado AS estado_aprendiz,
                a.estado AS estado,
                a.id_persona,
                p.documento,
                p.documento AS numero_documento,
                p.tipo_documento,
                p.nombres,
                p.apellidos,
                p.correo,
                p.correo AS email,
                p.correo AS correo_electronico,
                m.id_ficha,
                f.numero_ficha,
                f.numero_ficha AS ficha,
                pr.nombre AS programa,
                pr.nombre AS nombre_programa
            FROM aprendices a
            INNER JOIN personas p
                ON p.id = a.id_persona
            INNER JOIN matriculas m
                ON m.id_aprendiz = a.id
            INNER JOIN fichas f
                ON f.id = m.id_ficha
            LEFT JOIN programas pr
                ON pr.id = f.id_programa
            WHERE a.id = :id_aprendiz
              AND LOWER(TRIM(COALESCE(m.estado, ''))) = 'activa'
            ORDER BY
                m.id DESC,
                f.numero_ficha DESC
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_aprendiz' => $idAprendiz,
        ]);

        $aprendiz = $stmt->fetch(PDO::FETCH_ASSOC);

        return $aprendiz ?: null;
    }

    /**
     * Obtiene los cuestionarios vinculados a matrículas activas.
     * Solo entrega las respuestas cuyos códigos empiezan por HB.
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
            INNER JOIN matriculas m
                ON m.id_aprendiz = cb.id_aprendiz
                AND m.id_ficha = cb.id_ficha
                AND LOWER(TRIM(COALESCE(m.estado, ''))) = 'activa'
            LEFT JOIN fichas f
                ON f.id = cb.id_ficha
            LEFT JOIN programas pr
                ON pr.id = f.id_programa
            WHERE cb.id_aprendiz = :id_aprendiz
            ORDER BY cb.fecha_actualizacion DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_aprendiz' => $idAprendiz,
        ]);

        $cuestionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($cuestionarios as &$cuestionario) {
            $originales = json_decode(
                (string) ($cuestionario['respuestas'] ?? ''),
                true
            );

            $originales = is_array($originales) ? $originales : [];
            $respuestasHB = [];

            foreach ($originales as $codigo => $respuesta) {
                if (
                    is_string($codigo)
                    && preg_match('/^HB(?:[-_]|$)/i', $codigo) === 1
                ) {
                    $respuestasHB[$codigo] = $respuesta;
                }
            }

            $cuestionario['respuestas'] = $respuestasHB;
        }

        unset($cuestionario);

        return $cuestionarios;
    }

    /**
     * Resumen general del diligenciamiento por ficha.
     */
    public function obtenerResumen(
        int $idFicha = 0
    ): array {
        $sql = "
            SELECT
                COUNT(DISTINCT CONCAT(
                    m.id_aprendiz, '-', m.id_ficha
                )) AS total_matriculados,

                COUNT(DISTINCT CASE
                    WHEN LOWER(TRIM(COALESCE(cb.estado, ''))) = 'completado'
                    THEN CONCAT(m.id_aprendiz, '-', m.id_ficha)
                END) AS completados,

                COUNT(DISTINCT CASE
                    WHEN LOWER(TRIM(COALESCE(cb.estado, ''))) IN (
                        'borrador', 'en_proceso', 'en proceso'
                    )
                    THEN CONCAT(m.id_aprendiz, '-', m.id_ficha)
                END) AS borradores,

                COUNT(DISTINCT CASE
                    WHEN cb.id IS NULL
                      OR LOWER(TRIM(COALESCE(cb.estado, ''))) NOT IN (
                          'borrador',
                          'en_proceso',
                          'en proceso',
                          'completado'
                      )
                    THEN CONCAT(m.id_aprendiz, '-', m.id_ficha)
                END) AS pendientes

            FROM matriculas m
            LEFT JOIN cuestionarios_bienestar cb
                ON cb.id_aprendiz = m.id_aprendiz
                AND cb.id_ficha = m.id_ficha
            WHERE LOWER(TRIM(COALESCE(m.estado, ''))) = 'activa'
        ";

        $params = [];

        if ($idFicha > 0) {
            $sql .= " AND m.id_ficha = :id_ficha";
            $params[':id_ficha'] = $idFicha;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $resumen = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        foreach (
            ['total_matriculados', 'completados', 'borradores', 'pendientes']
            as $campo
        ) {
            $resumen[$campo] = (int) ($resumen[$campo] ?? 0);
        }

        $total = $resumen['total_matriculados'];

        $resumen['porcentaje_completado'] = $total > 0
            ? round(($resumen['completados'] / $total) * 100, 1)
            : 0;

        return $resumen;
    }

    /**
     * Obtiene TODAS las respuestas HB (HB-01 ... HB-09) de cada aprendiz con
     * matrícula activa, usando el cuestionario más reciente por aprendiz
     * y ficha.
     *
     * Cada fila incluye los datos del aprendiz y la clave:
     *   'respuestas_hb' => [
     *       'HB-07' => ['Liderazgo', 'Comunicación'],
     *       'HB-09' => ['Taller de manejo del estrés'],
     *       ...
     *   ]
     *
     * Los aprendices sin respuestas HB se devuelven con 'respuestas_hb'
     * vacío. Sirve para buscar por taller, habilidad, red de apoyo, etc.
     */
    public function obtenerRespuestasHabilidadesBlandas(
        int $idFicha = 0
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
            WHERE LOWER(TRIM(COALESCE(m.estado, ''))) = 'activa'
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

            $respuestasHB = [];

            if (is_array($respuestas)) {
                $this->recolectarRespuestasHB($respuestas, $respuestasHB);
            }

            ksort($respuestasHB, SORT_NATURAL);

            $fila['nombre_completo'] = trim(
                (string) ($fila['nombres'] ?? '') . ' ' .
                (string) ($fila['apellidos'] ?? '')
            );

            $fila['respuestas_hb'] = $respuestasHB;
            unset($fila['respuestas']);

            $resultado[] = $fila;
        }

        return $resultado;
    }

    /**
     * Recorre el JSON de respuestas (incluso anidado) y llena $salida con
     * [codigo HB => lista de opciones]. Reconoce {"HB-07": ...} y también
     * {"codigo": "HB-07", "respuesta": ...}.
     */
    private function recolectarRespuestasHB(array $datos, array &$salida): void
    {
        foreach ($datos as $clave => $valor) {
            $codigo = null;

            if (is_string($clave)) {
                $candidato = $this->normalizarCodigoHB($clave);

                if ($this->esCodigoHB($candidato)) {
                    $codigo = $candidato;
                }
            }

            if ($codigo === null && is_array($valor)) {
                $interno = $valor['codigo']
                    ?? $valor['codigo_pregunta']
                    ?? $valor['pregunta_codigo']
                    ?? $valor['id_pregunta_codigo']
                    ?? null;

                if ($interno !== null) {
                    $candidato = $this->normalizarCodigoHB((string) $interno);

                    if ($this->esCodigoHB($candidato)) {
                        $codigo = $candidato;
                    }
                }
            }

            if ($codigo !== null) {
                if (!isset($salida[$codigo])) {
                    $opciones = $this->convertirEnOpcionesHB(
                        $this->desenvolverRespuestaHB($valor)
                    );

                    if ($opciones !== []) {
                        $salida[$codigo] = $opciones;
                    }
                }

                continue;
            }

            if (is_array($valor)) {
                $this->recolectarRespuestasHB($valor, $salida);
            }
        }
    }

    private function normalizarCodigoHB(string $codigo): string
    {
        return strtoupper(str_replace('_', '-', trim($codigo)));
    }

    private function esCodigoHB(string $codigo): bool
    {
        return preg_match('/^HB-\d+(?:-[A-Z]+)?$/', $codigo) === 1;
    }

    /**
     * Desenvuelve formatos como {"respuesta":"Liderazgo"}.
     */
    private function desenvolverRespuestaHB(mixed $valor): mixed
    {
        if (is_string($valor)) {
            $texto = trim($valor);

            if (
                $texto !== ''
                && (
                    str_starts_with($texto, '{')
                    || str_starts_with($texto, '[')
                )
            ) {
                $decodificado = json_decode($texto, true);

                if (
                    json_last_error() === JSON_ERROR_NONE
                    && is_array($decodificado)
                ) {
                    return $this->desenvolverRespuestaHB($decodificado);
                }
            }

            return $valor;
        }

        if (!is_array($valor)) {
            return $valor;
        }

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
                return $this->desenvolverRespuestaHB($valor[$campo]);
            }
        }

        // Objeto que identifica la pregunta pero no trae respuesta.
        if (
            isset($valor['codigo'])
            || isset($valor['codigo_pregunta'])
            || isset($valor['pregunta_codigo'])
        ) {
            return null;
        }

        return $valor;
    }

    /**
     * Convierte una respuesta simple, múltiple o de tipo objeto en una
     * lista de textos para poder buscar y agrupar.
     *
     * - "Liderazgo"              => ['Liderazgo']
     * - ["Empatía", "Liderazgo"] => ['Empatía', 'Liderazgo']
     * - {"Familia": true}        => ['Familia']
     * - {"Familia": "Siempre"}   => ['Familia: Siempre']
     */
    private function convertirEnOpcionesHB(mixed $valor): array
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
                    return $this->convertirEnOpcionesHB($decodificado);
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
                $elemento = $this->desenvolverRespuestaHB($elemento);
            }

            if (is_array($elemento)) {
                foreach ($this->convertirEnOpcionesHB($elemento) as $sub) {
                    $opciones[] = $prefijo !== ''
                        ? $prefijo . ': ' . $sub
                        : $sub;
                }

                continue;
            }

            if (is_bool($elemento)) {
                if ($prefijo !== '') {
                    // {"Familia": true} => "Familia"; false se ignora.
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
     * Estadísticas agregadas de respuestas HB completadas.
     */
    public function obtenerEstadisticas(
        int $idFicha = 0
    ): array {
        $sql = "
            SELECT
                cb.id_aprendiz,
                cb.respuestas
            FROM cuestionarios_bienestar cb
            INNER JOIN matriculas m
                ON m.id_aprendiz = cb.id_aprendiz
                AND m.id_ficha = cb.id_ficha
                AND LOWER(TRIM(COALESCE(m.estado, ''))) = 'activa'
            WHERE LOWER(TRIM(COALESCE(cb.estado, ''))) = 'completado'
        ";

        $params = [];

        if ($idFicha > 0) {
            $sql .= " AND cb.id_ficha = :id_ficha";
            $params[':id_ficha'] = $idFicha;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $estadisticas = [];

        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $respuestas = json_decode(
                (string) ($fila['respuestas'] ?? ''),
                true
            );

            if (!is_array($respuestas)) {
                continue;
            }

            foreach ($respuestas as $codigo => $respuesta) {
                if (
                    !is_string($codigo)
                    || preg_match('/^HB(?:[-_]|$)/i', $codigo) !== 1
                ) {
                    continue;
                }

                if (!isset($estadisticas[$codigo])) {
                    $estadisticas[$codigo] = [
                        'pregunta' => $codigo,
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
                    $estadisticas[$codigo]['sin_respuesta']++;
                    continue;
                }

                $opciones = is_array($respuesta)
                    ? $respuesta
                    : [$respuesta];

                foreach ($opciones as $opcion) {
                    if (
                        !is_scalar($opcion)
                        || trim((string) $opcion) === ''
                    ) {
                        continue;
                    }

                    $valor = trim((string) $opcion);

                    if (!isset(
                        $estadisticas[$codigo]['respuestas'][$valor]
                    )) {
                        $estadisticas[$codigo]['respuestas'][$valor] = [
                            'cantidad' => 0,
                            'aprendices' => [],
                        ];
                    }

                    $estadisticas[$codigo]['respuestas'][$valor]['cantidad']++;
                    $estadisticas[$codigo]['respuestas'][$valor]['aprendices'][] =
                        (int) $fila['id_aprendiz'];

                    $estadisticas[$codigo]['total_respuestas']++;
                }
            }
        }

        ksort($estadisticas);

        return $estadisticas;
    }
}