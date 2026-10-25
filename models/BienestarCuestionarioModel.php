<?php
declare(strict_types=1);

class BienestarCuestionarioModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->conectar();
    }

    /*
    |--------------------------------------------------------------------------
    | OBTENER CUESTIONARIO DE UN APRENDIZ
    |--------------------------------------------------------------------------
    */

    public function obtenerCuestionario(
        int $idAprendiz,
        int $idFicha
    ): ?array {
        $sql = "
            SELECT
                id,
                id_aprendiz,
                id_ficha,
                respuestas,
                estado,
                fecha_inicio,
                fecha_actualizacion,
                fecha_completado
            FROM cuestionarios_bienestar
            WHERE id_aprendiz = :id_aprendiz
              AND id_ficha = :id_ficha
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_aprendiz' => $idAprendiz,
            ':id_ficha' => $idFicha,
        ]);

        $cuestionario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cuestionario) {
            return null;
        }

        $respuestas = json_decode(
            (string) $cuestionario['respuestas'],
            true
        );

        $cuestionario['respuestas'] = is_array($respuestas)
            ? $respuestas
            : [];

        return $cuestionario;
    }

    /*
    |--------------------------------------------------------------------------
    | GUARDAR BORRADOR
    |--------------------------------------------------------------------------
    */

    public function guardarBorrador(
        int $idAprendiz,
        int $idFicha,
        array $respuestas
    ): bool {
        return $this->guardar(
            $idAprendiz,
            $idFicha,
            $respuestas,
            false
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FINALIZAR CUESTIONARIO
    |--------------------------------------------------------------------------
    */

    public function finalizarCuestionario(
        int $idAprendiz,
        int $idFicha,
        array $respuestas
    ): bool {
        return $this->guardar(
            $idAprendiz,
            $idFicha,
            $respuestas,
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDAR RESPUESTAS
    |--------------------------------------------------------------------------
    |
    | Una respuesta vacía no cuenta como contestada.
    | Los valores "0" y 0 sí cuentan como respuestas.
    |
    */

    private function tieneRespuesta($valor): bool
    {
        if (is_array($valor)) {
            foreach ($valor as $elemento) {
                if ($this->tieneRespuesta($elemento)) {
                    return true;
                }
            }

            return false;
        }

        if ($valor === null || is_object($valor)) {
            return false;
        }

        return trim((string) $valor) !== '';
    }

    /*
    |--------------------------------------------------------------------------
    | GUARDADO INTERNO
    |--------------------------------------------------------------------------
    */

    private function guardar(
        int $idAprendiz,
        int $idFicha,
        array $respuestas,
        bool $finalizar
    ): bool {
        if ($idAprendiz <= 0 || $idFicha <= 0) {
            throw new InvalidArgumentException(
                'El aprendiz y la ficha deben ser válidos.'
            );
        }

        /*
         * No aceptar claves de respuestas vacías como contenido.
         */
        foreach ($respuestas as $clave => $valor) {
            if (!is_string($clave) && !is_int($clave)) {
                unset($respuestas[$clave]);
                continue;
            }

            if (!$this->tieneRespuesta($valor)) {
                unset($respuestas[$clave]);
            }
        }

        /*
         * Si se intenta finalizar, comprobar que haya respuestas.
         * La validación específica de preguntas obligatorias también
         * debe ejecutarse en el controlador con su catálogo de preguntas.
         */
        if ($finalizar && count($respuestas) === 0) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            /*
             * Bloquear el registro existente para evitar que dos
             * solicitudes simultáneas lo cambien de estado.
             */
            $sqlActual = "
                SELECT id, estado
                FROM cuestionarios_bienestar
                WHERE id_aprendiz = :id_aprendiz
                  AND id_ficha = :id_ficha
                LIMIT 1
                FOR UPDATE
            ";

            $stmtActual = $this->db->prepare($sqlActual);
            $stmtActual->execute([
                ':id_aprendiz' => $idAprendiz,
                ':id_ficha' => $idFicha,
            ]);

            $actual = $stmtActual->fetch(PDO::FETCH_ASSOC);

            /*
             * Un cuestionario completado no se modifica desde
             * el formulario público.
             */
            if (
                $actual
                && $actual['estado'] === 'completado'
            ) {
                $this->db->rollBack();
                return false;
            }

            $json = json_encode(
                $respuestas,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
            );

            $estado = $finalizar ? 'completado' : 'borrador';

            if ($actual) {
                /*
                 * Actualizar el registro sin reemplazar su fecha
                 * de inicio ni modificar otros datos.
                 */
                $sql = "
                    UPDATE cuestionarios_bienestar
                    SET respuestas = :respuestas,
                        estado = :estado,
                        fecha_actualizacion = CURRENT_TIMESTAMP,
                        fecha_completado = :fecha_completado
                    WHERE id = :id
                ";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':respuestas' => $json,
                    ':estado' => $estado,
                    ':fecha_completado' => $finalizar
                        ? date('Y-m-d H:i:s')
                        : null,
                    ':id' => (int) $actual['id'],
                ]);
            } else {
                /*
                 * Crear el cuestionario únicamente cuando no existe.
                 */
                $sql = "
                    INSERT INTO cuestionarios_bienestar (
                        id_aprendiz,
                        id_ficha,
                        respuestas,
                        estado,
                        fecha_inicio,
                        fecha_actualizacion,
                        fecha_completado
                    ) VALUES (
                        :id_aprendiz,
                        :id_ficha,
                        :respuestas,
                        :estado,
                        CURRENT_TIMESTAMP,
                        CURRENT_TIMESTAMP,
                        :fecha_completado
                    )
                ";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':id_aprendiz' => $idAprendiz,
                    ':id_ficha' => $idFicha,
                    ':respuestas' => $json,
                    ':estado' => $estado,
                    ':fecha_completado' => $finalizar
                        ? date('Y-m-d H:i:s')
                        : null,
                ]);
            }

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LISTAR CUESTIONARIOS REGISTRADOS DE UNA FICHA
    |--------------------------------------------------------------------------
    */

    public function listarCuestionariosPorFicha(
        int $idFicha
    ): array {
        $sql = "
            SELECT
                id,
                id_aprendiz,
                id_ficha,
                estado,
                fecha_inicio,
                fecha_actualizacion,
                fecha_completado
            FROM cuestionarios_bienestar
            WHERE id_ficha = :id_ficha
            ORDER BY fecha_actualizacion DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_ficha' => $idFicha,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | RESUMEN DE CUESTIONARIOS POR FICHA
    |--------------------------------------------------------------------------
    */

    public function obtenerResumenPorFicha(
        int $idFicha
    ): array {
        $sql = "
            SELECT
                COUNT(*) AS total_con_respuestas,
                COALESCE(
                    SUM(
                        CASE
                            WHEN estado = 'completado' THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS completados,
                COALESCE(
                    SUM(
                        CASE
                            WHEN estado = 'borrador' THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS en_proceso
            FROM cuestionarios_bienestar
            WHERE id_ficha = :id_ficha
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_ficha' => $idFicha,
        ]);

        $resumen = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resumen ?: [
            'total_con_respuestas' => 0,
            'completados' => 0,
            'en_proceso' => 0,
        ];
    }
}

