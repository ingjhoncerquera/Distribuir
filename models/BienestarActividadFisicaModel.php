<?php
declare(strict_types=1);

/**
 * SITA - Modelo de consulta de Actividad Física.
 */
class BienestarActividadFisicaModel
{
    private BienestarConsultaModel $consulta;
    private PDO $db;

    public function __construct()
    {
        $this->consulta = new BienestarConsultaModel();
        $this->db = (new Database())->conectar();
    }

    public function obtenerFichas(): array
    {
        return $this->consulta->obtenerFichas();
    }

    public function buscarAprendices(
        string $busqueda = '',
        int $idFicha = 0,
        string $estadoCuestionario = ''
    ): array {
        return $this->consulta->buscarAprendices(
            $busqueda,
            $idFicha,
            $estadoCuestionario
        );
    }

    public function obtenerCaracterizacion(int $idAprendiz): ?array
    {
        return $this->consulta->obtenerCaracterizacion($idAprendiz);
    }

    public function obtenerCuestionarios(int $idAprendiz): array
    {
        $cuestionarios = $this->consulta->obtenerCuestionarios($idAprendiz);

        foreach ($cuestionarios as &$cuestionario) {
            $respuestas = $cuestionario['respuestas'] ?? [];

            if (is_string($respuestas)) {
                $decodificadas = json_decode($respuestas, true);
                $respuestas = is_array($decodificadas)
                    ? $decodificadas
                    : [];
            }

            $filtradas = [];

            foreach ($respuestas as $codigo => $respuesta) {
                $codigoNormalizado = strtoupper(
                    str_replace('_', '-', trim((string) $codigo))
                );

                if (preg_match('/^AF(?:-|$)/', $codigoNormalizado)) {
                    $filtradas[$codigoNormalizado] = $respuesta;
                }
            }

            $cuestionario['respuestas'] = $filtradas;
        }

        unset($cuestionario);

        return $cuestionarios;
    }

    public function obtenerResumenPorFicha(int $idFicha = 0): array
    {
        return $this->consulta->obtenerResumenPorFicha($idFicha);
    }

    public function obtenerEstadisticasRespuestas(int $idFicha = 0): array
    {
        $estadisticas = $this->consulta->obtenerEstadisticasRespuestas($idFicha);

        return array_values(array_filter(
            $estadisticas,
            static function ($fila): bool {
                $codigo = strtoupper(str_replace(
                    '_',
                    '-',
                    trim((string) (
                        $fila['codigo_pregunta']
                        ?? $fila['codigo']
                        ?? ''
                    ))
                ));

                return preg_match('/^AF(?:-|$)/', $codigo) === 1;
            }
        ));
    }

    /**
     * Obtiene los aprendices que tienen intereses registrados en AF-03.
     * Se utiliza el cuestionario más reciente por aprendiz y ficha.
     * Solo considera matrículas activas.
     */
    public function obtenerInteresesActividadFisica(
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
            WHERE m.estado = 'activa'
        ";

        $parametros = [];

        if ($idFicha > 0) {
            $sql .= ' AND f.id = :id_ficha ';
            $parametros[':id_ficha'] = $idFicha;
        }

        $sql .= "
            ORDER BY p.apellidos ASC, p.nombres ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $resultado = [];

        foreach ($filas as $fila) {
            $respuestas = json_decode(
                (string) ($fila['respuestas'] ?? ''),
                true
            );

            if (!is_array($respuestas)) {
                continue;
            }

            $respuestaAF03 = $this->buscarRespuestaAF03($respuestas);

            if ($respuestaAF03 === null) {
                continue;
            }

            $intereses = $this->convertirEnOpciones($respuestaAF03);

            if ($intereses === []) {
                continue;
            }

            $fila['nombre_completo'] = trim(
                (string) ($fila['nombres'] ?? '') . ' ' .
                (string) ($fila['apellidos'] ?? '')
            );

            $fila['intereses'] = $intereses;
            unset($fila['respuestas']);

            $resultado[] = $fila;
        }

        return $resultado;
    }

    /**
     * Obtiene TODAS las respuestas AF (AF-01, AF-02, ... AF-10) de cada
     * aprendiz con matrícula activa, usando el cuestionario más reciente
     * por aprendiz y ficha.
     *
     * Cada fila incluye los datos del aprendiz y la clave:
     *   'respuestas_af' => [
     *       'AF-02' => ['Fútbol', 'Natación'],
     *       'AF-09' => ['Noche'],
     *       ...
     *   ]
     *
     * Los aprendices sin respuestas AF se devuelven con 'respuestas_af' vacío.
     * Sirve para buscar por deporte, horario, dificultad, etc.
     */
    public function obtenerRespuestasActividadFisica(
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
            WHERE m.estado = 'activa'
        ";

        $parametros = [];

        if ($idFicha > 0) {
            $sql .= ' AND f.id = :id_ficha ';
            $parametros[':id_ficha'] = $idFicha;
        }

        $sql .= "
            ORDER BY p.apellidos ASC, p.nombres ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $resultado = [];

        foreach ($filas as $fila) {
            $respuestas = json_decode(
                (string) ($fila['respuestas'] ?? ''),
                true
            );

            $respuestasAF = [];

            if (is_array($respuestas)) {
                $this->recolectarRespuestasAF($respuestas, $respuestasAF);
            }

            ksort($respuestasAF, SORT_NATURAL);

            $fila['nombre_completo'] = trim(
                (string) ($fila['nombres'] ?? '') . ' ' .
                (string) ($fila['apellidos'] ?? '')
            );

            $fila['respuestas_af'] = $respuestasAF;
            unset($fila['respuestas']);

            $resultado[] = $fila;
        }

        return $resultado;
    }

    /**
     * Recorre el JSON de respuestas (incluso anidado) y llena $salida con
     * [codigo AF => lista de opciones]. Reconoce tanto {"AF-03": ...}
     * como {"codigo": "AF-03", "respuesta": ...}.
     */
    private function recolectarRespuestasAF(array $datos, array &$salida): void
    {
        foreach ($datos as $clave => $valor) {
            $codigo = null;

            if (is_string($clave)) {
                $candidato = $this->normalizarCodigoAF($clave);

                if ($this->esCodigoAF($candidato)) {
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
                    $candidato = $this->normalizarCodigoAF((string) $interno);

                    if ($this->esCodigoAF($candidato)) {
                        $codigo = $candidato;
                    }
                }
            }

            if ($codigo !== null) {
                if (!isset($salida[$codigo])) {
                    $opciones = $this->convertirEnOpciones(
                        $this->desenvolverRespuesta($valor)
                    );

                    if ($opciones !== []) {
                        $salida[$codigo] = $opciones;
                    }
                }

                continue;
            }

            if (is_array($valor)) {
                $this->recolectarRespuestasAF($valor, $salida);
            }
        }
    }

    private function normalizarCodigoAF(string $codigo): string
    {
        return strtoupper(str_replace('_', '-', trim($codigo)));
    }

    private function esCodigoAF(string $codigo): bool
    {
        return preg_match('/^AF-\d+$/', $codigo) === 1;
    }

    /**
     * Busca AF-03 en respuestas JSON, incluyendo estructuras anidadas.
     */
    private function buscarRespuestaAF03(array $datos): mixed
    {
        foreach ($datos as $clave => $valor) {
            if (is_string($clave)) {
                $codigo = strtoupper(
                    str_replace('_', '-', trim($clave))
                );

                if ($codigo === 'AF-03') {
                    return $this->desenvolverRespuesta($valor);
                }
            }

            if (is_array($valor)) {
                $codigoInterno = $valor['codigo']
                    ?? $valor['codigo_pregunta']
                    ?? $valor['pregunta_codigo']
                    ?? $valor['id_pregunta_codigo']
                    ?? null;

                if (
                    $codigoInterno !== null
                    && strtoupper(
                        str_replace('_', '-', trim((string) $codigoInterno))
                    ) === 'AF-03'
                ) {
                    return $this->desenvolverRespuesta($valor);
                }
            }
        }

        foreach ($datos as $valor) {
            if (is_array($valor)) {
                $encontrada = $this->buscarRespuestaAF03($valor);

                if ($encontrada !== null) {
                    return $encontrada;
                }
            }
        }

        return null;
    }

    /**
     * Desenvuelve formatos como {"respuesta":"Fútbol"} o {"valor":"Fútbol"}.
     */
    private function desenvolverRespuesta(mixed $valor): mixed
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
                    return $this->desenvolverRespuesta($decodificado);
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
                return $this->desenvolverRespuesta($valor[$campo]);
            }
        }

        /*
         * Objeto que identifica la pregunta: extraer campos de respuesta.
         */
        if (
            isset($valor['codigo'])
            || isset($valor['codigo_pregunta'])
            || isset($valor['pregunta_codigo'])
        ) {
            foreach (
                ['respuesta', 'valor', 'respuesta_texto', 'opcion', 'respuesta_json']
                as $campo
            ) {
                if (array_key_exists($campo, $valor)) {
                    return $this->desenvolverRespuesta($valor[$campo]);
                }
            }

            return null;
        }

        return $valor;
    }

    /**
     * Convierte una respuesta simple o múltiple en una lista de opciones.
     */
    private function convertirEnOpciones(mixed $respuesta): array
    {
        /*
         * Respuestas booleanas (true/false) se muestran como Sí/No.
         */
        if (is_bool($respuesta)) {
            return [$respuesta ? 'Sí' : 'No'];
        }

        if (is_string($respuesta)) {
            $texto = trim($respuesta);

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
                    return $this->convertirEnOpciones($decodificado);
                }
            }

            return $texto === '' ? [] : [$texto];
        }

        if (is_scalar($respuesta)) {
            $texto = trim((string) $respuesta);

            return $texto === '' ? [] : [$texto];
        }

        if (!is_array($respuesta)) {
            return [];
        }

        $opciones = [];

        foreach ($respuesta as $elemento) {
            if (is_array($elemento)) {
                $elemento = $this->desenvolverRespuesta($elemento);
            }

            if (is_bool($elemento)) {
                $elemento = $elemento ? 'Sí' : 'No';
            }

            if (is_array($elemento)) {
                foreach ($this->convertirEnOpciones($elemento) as $opcion) {
                    $opciones[] = $opcion;
                }

                continue;
            }

            if (!is_scalar($elemento)) {
                continue;
            }

            $texto = trim((string) $elemento);

            if ($texto !== '') {
                $opciones[] = $texto;
            }
        }

        /*
         * Evitar duplicar la misma actividad en un cuestionario.
         */
        $unicas = [];

        foreach ($opciones as $opcion) {
            $clave = function_exists('mb_strtolower')
                ? mb_strtolower($opcion, 'UTF-8')
                : strtolower($opcion);

            $unicas[$clave] = $opcion;
        }

        return array_values($unicas);
    }
}