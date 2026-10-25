<?php

declare(strict_types=1);

class MaterialesProcesoModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        if ($db instanceof PDO) {
            $this->db = $db;
            return;
        }

        $database = new Database();
        $this->db = $database->conectar();
    }

    /**
     * Listar todos los procesos.
     */
    public function listar(): array
    {
        $sql = "
            SELECT
                mp.id,
                mp.anio,
                mp.nombre,
                mp.descripcion,
                mp.fecha_inicio,
                mp.fecha_cierre,
                mp.estado,
                mp.id_coordinador,
                mp.created_at,
                mp.updated_at,

                CASE
                    WHEN p.id IS NOT NULL THEN
                        CONCAT(
                            TRIM(p.nombres),
                            ' ',
                            TRIM(p.apellidos)
                        )
                    ELSE NULL
                END AS coordinador

            FROM materiales_procesos mp

            LEFT JOIN profesionales pr
                ON pr.id = mp.id_coordinador

            LEFT JOIN personas p
                ON p.id = pr.id_persona

            ORDER BY
                mp.anio DESC,
                mp.fecha_inicio DESC,
                mp.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Obtener un proceso por ID.
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql = "
            SELECT
                mp.id,
                mp.anio,
                mp.nombre,
                mp.descripcion,
                mp.fecha_inicio,
                mp.fecha_cierre,
                mp.estado,
                mp.id_coordinador,
                mp.created_at,
                mp.updated_at,

                CASE
                    WHEN p.id IS NOT NULL THEN
                        CONCAT(
                            TRIM(p.nombres),
                            ' ',
                            TRIM(p.apellidos)
                        )
                    ELSE NULL
                END AS coordinador

            FROM materiales_procesos mp

            LEFT JOIN profesionales pr
                ON pr.id = mp.id_coordinador

            LEFT JOIN personas p
                ON p.id = pr.id_persona

            WHERE mp.id = :id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
        ]);

        $proceso = $stmt->fetch();

        return $proceso ?: null;
    }

    /**
     * Crear un proceso.
     */
    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO materiales_procesos (
                anio,
                nombre,
                descripcion,
                fecha_inicio,
                fecha_cierre,
                estado,
                id_coordinador
            ) VALUES (
                :anio,
                :nombre,
                :descripcion,
                :fecha_inicio,
                :fecha_cierre,
                :estado,
                :id_coordinador
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':anio' => (int) $datos['anio'],
            ':nombre' => trim((string) $datos['nombre']),
            ':descripcion' => $this->valorNulo($datos['descripcion'] ?? null),
            ':fecha_inicio' => $this->valorNulo($datos['fecha_inicio'] ?? null),
            ':fecha_cierre' => $this->valorNulo($datos['fecha_cierre'] ?? null),
            ':estado' => (string) ($datos['estado'] ?? 'borrador'),
            ':id_coordinador' => $this->valorEnteroNulo(
                $datos['id_coordinador'] ?? null
            ),
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualizar un proceso.
     */
    public function actualizar(int $id, array $datos): bool
    {
        $sql = "
            UPDATE materiales_procesos
            SET
                anio = :anio,
                nombre = :nombre,
                descripcion = :descripcion,
                fecha_inicio = :fecha_inicio,
                fecha_cierre = :fecha_cierre,
                estado = :estado,
                id_coordinador = :id_coordinador
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':anio' => (int) $datos['anio'],
            ':nombre' => trim((string) $datos['nombre']),
            ':descripcion' => $this->valorNulo($datos['descripcion'] ?? null),
            ':fecha_inicio' => $this->valorNulo($datos['fecha_inicio'] ?? null),
            ':fecha_cierre' => $this->valorNulo($datos['fecha_cierre'] ?? null),
            ':estado' => (string) ($datos['estado'] ?? 'borrador'),
            ':id_coordinador' => $this->valorEnteroNulo(
                $datos['id_coordinador'] ?? null
            ),
            ':id' => $id,
        ]);
    }

    /**
     * Cambiar únicamente el estado de un proceso.
     */
    public function cambiarEstado(int $id, string $estado): bool
    {
        $estadosPermitidos = [
            'borrador',
            'abierto',
            'en_solicitud',
            'en_aval',
            'en_compra',
            'en_recepcion',
            'en_distribucion',
            'cerrado',
            'cancelado',
        ];

        if (!in_array($estado, $estadosPermitidos, true)) {
            return false;
        }

        $sql = "
            UPDATE materiales_procesos
            SET estado = :estado
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':estado' => $estado,
            ':id' => $id,
        ]);
    }

    /**
     * Eliminar un proceso.
     *
     * Se deja disponible para administración, pero el controlador
     * podrá restringir su uso cuando existan relaciones.
     */
    public function eliminar(int $id): bool
    {
        $sql = "
            DELETE FROM materiales_procesos
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
        ]);
    }

    /**
     * Procesos disponibles para crear solicitudes.
     *
     * Las solicitudes solamente pueden utilizar procesos
     * abiertos o en proceso de solicitud.
     */
    public function listarDisponiblesParaSolicitud(): array
    {
        $sql = "
            SELECT
                id,
                anio,
                nombre,
                descripcion,
                fecha_inicio,
                fecha_cierre,
                estado
            FROM materiales_procesos
            WHERE estado IN (
                'abierto',
                'en_solicitud'
            )
            ORDER BY
                anio DESC,
                fecha_inicio DESC,
                nombre ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Validar si existe un proceso.
     */
    public function existe(int $id): bool
    {
        $sql = "
            SELECT COUNT(*)
            FROM materiales_procesos
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Validar si el nombre ya existe para el mismo año.
     */
    public function existeNombreAnio(
        string $nombre,
        int $anio,
        ?int $excluirId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)
            FROM materiales_procesos
            WHERE
                anio = :anio
                AND LOWER(TRIM(nombre)) = LOWER(TRIM(:nombre))
        ";

        $params = [
            ':anio' => $anio,
            ':nombre' => $nombre,
        ];

        if ($excluirId !== null) {
            $sql .= "
                AND id <> :excluir_id
            ";

            $params[':excluir_id'] = $excluirId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Listar coordinadores/profesionales activos.
     */
    public function listarCoordinadores(): array
    {
        $sql = "
            SELECT
                pr.id,
                pr.id_persona,
                pr.tipo_profesional,
                pr.registro_profesional,
                pr.especialidad,

                p.tipo_documento,
                p.documento,
                p.nombres,
                p.apellidos,

                CONCAT(
                    TRIM(p.nombres),
                    ' ',
                    TRIM(p.apellidos)
                ) AS nombre_completo

            FROM profesionales pr

            INNER JOIN personas p
                ON p.id = pr.id_persona

            WHERE
                pr.estado = 'activo'
                AND p.estado = 'activo'

            ORDER BY
                p.nombres ASC,
                p.apellidos ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Obtener cantidad de áreas asociadas al proceso.
     */
    public function contarAreas(int $idProceso): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM materiales_proceso_areas
            WHERE id_proceso = :id_proceso
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_proceso' => $idProceso,
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Obtener cantidad de solicitudes asociadas al proceso
     * a través de sus áreas.
     */
    public function contarSolicitudes(int $idProceso): int
    {
        $sql = "
            SELECT COUNT(DISTINCT ms.id)
            FROM materiales_solicitudes ms

            INNER JOIN materiales_proceso_areas mpa
                ON mpa.id = ms.id_proceso_area

            WHERE mpa.id_proceso = :id_proceso
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_proceso' => $idProceso,
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Obtener resumen de áreas y solicitudes por proceso.
     */
    public function obtenerResumen(int $idProceso): array
    {
        return [
            'areas' => $this->contarAreas($idProceso),
            'solicitudes' => $this->contarSolicitudes($idProceso),
        ];
    }

    /**
     * Convierte valores vacíos a NULL.
     */
    private function valorNulo(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }

    /**
     * Convierte valores numéricos vacíos a NULL.
     */
    private function valorEnteroNulo(mixed $valor): ?int
    {
        if (
            $valor === null
            || $valor === ''
            || !is_numeric($valor)
        ) {
            return null;
        }

        $valor = (int) $valor;

        return $valor > 0 ? $valor : null;
    }
}