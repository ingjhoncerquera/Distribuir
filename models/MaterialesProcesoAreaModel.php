<?php

declare(strict_types=1);

require_once ROOT_PATH . '/config/Database.php';

class MaterialesProcesoAreaModel
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();

        $this->db = $database->conectar();
    }

    /**
     * Lista las áreas de un proceso.
     */
    public function listarPorProceso(int $idProceso): array
    {
        $sql = "
            SELECT
                a.id,
                a.id_proceso,
                a.nombre_area,
                a.estado,
                a.observaciones,
                a.created_at,

                /* Responsable activo */
                r.id AS id_responsable,
                r.id_profesional,
                r.id_usuario_designa,
                r.fecha_designacion,
                r.fecha_fin,
                r.estado AS estado_responsable,
                r.observaciones AS observaciones_responsable,

                /* Información del profesional */
                p.tipo_profesional,
                p.registro_profesional,
                p.especialidad,

                /* Información de la persona */
                per.nombres,
                per.apellidos,

                CONCAT(
                    TRIM(per.nombres),
                    ' ',
                    TRIM(per.apellidos)
                ) AS nombre_responsable,

                /* Cantidad de responsables activos */
                (
                    SELECT COUNT(*)
                    FROM materiales_area_responsables r2
                    WHERE r2.id_proceso_area = a.id
                      AND r2.estado = 'activo'
                      AND r2.fecha_fin IS NULL
                ) AS total_responsables,

                /* Cantidad de solicitudes */
                (
                    SELECT COUNT(*)
                    FROM materiales_solicitudes s
                    WHERE s.id_proceso_area = a.id
                ) AS total_solicitudes

            FROM materiales_proceso_areas a

            LEFT JOIN materiales_area_responsables r
                ON r.id_proceso_area = a.id
                AND r.estado = 'activo'
                AND r.fecha_fin IS NULL

            LEFT JOIN profesionales p
                ON p.id = r.id_profesional

            LEFT JOIN personas per
                ON per.id = p.id_persona

            WHERE a.id_proceso = :id_proceso

            ORDER BY a.nombre_area ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_proceso' => $idProceso
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un área por su ID.
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql = "
            SELECT
                a.id,
                a.id_proceso,
                a.nombre_area,
                a.estado,
                a.observaciones,
                a.created_at,

                p.id AS id_profesional,
                p.tipo_profesional,
                p.registro_profesional,
                p.especialidad,
                p.estado AS estado_profesional,

                per.nombres,
                per.apellidos,

                CONCAT(
                    TRIM(per.nombres),
                    ' ',
                    TRIM(per.apellidos)
                ) AS nombre_responsable

            FROM materiales_proceso_areas a

            LEFT JOIN materiales_area_responsables r
                ON r.id_proceso_area = a.id
                AND r.estado = 'activo'
                AND r.fecha_fin IS NULL

            LEFT JOIN profesionales p
                ON p.id = r.id_profesional

            LEFT JOIN personas per
                ON per.id = p.id_persona

            WHERE a.id = :id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado !== false ? $resultado : null;
    }

    /**
     * Verifica si existe un área con el mismo nombre
     * dentro del mismo proceso.
     */
    public function existe(
        int $idProceso,
        string $nombreArea,
        ?int $idExcluir = null
    ): bool {
        $sql = "
            SELECT COUNT(*)
            FROM materiales_proceso_areas
            WHERE id_proceso = :id_proceso
              AND LOWER(TRIM(nombre_area)) = LOWER(TRIM(:nombre_area))
        ";

        $params = [
            ':id_proceso' => $idProceso,
            ':nombre_area' => $nombreArea
        ];

        if ($idExcluir !== null) {
            $sql .= "
                AND id <> :id_excluir
            ";

            $params[':id_excluir'] = $idExcluir;
        }

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Método utilizado por el controlador para verificar
     * si ya existe un área con determinado nombre.
     */
    public function existeNombre(
        int $idProceso,
        string $nombreArea,
        ?int $idExcluir = null
    ): bool {
        return $this->existe(
            $idProceso,
            $nombreArea,
            $idExcluir
        );
    }

    /**
     * Crea una nueva área.
     */
    public function crear(
        int $idProceso,
        string $nombreArea,
        string $estado = 'activa',
        ?string $observaciones = null
    ): int {
        $sql = "
            INSERT INTO materiales_proceso_areas
            (
                id_proceso,
                nombre_area,
                estado,
                observaciones
            )
            VALUES
            (
                :id_proceso,
                :nombre_area,
                :estado,
                :observaciones
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_proceso' => $idProceso,
            ':nombre_area' => trim($nombreArea),
            ':estado' => $estado,
            ':observaciones' => $observaciones !== null
                ? trim($observaciones)
                : null
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza un área.
     */
    public function actualizar(
        int $id,
        string $nombreArea,
        string $observaciones,
        string $estado = 'activa'
    ): bool {
        $sql = "
            UPDATE materiales_proceso_areas
            SET
                nombre_area = :nombre_area,
                estado = :estado,
                observaciones = :observaciones
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':nombre_area' => trim($nombreArea),
            ':estado' => $estado,
            ':observaciones' => $observaciones !== null
                ? trim($observaciones)
                : null
        ]);
    }

    /**
     * Cambia el estado de un área.
     */
    public function cambiarEstado(
        int $id,
        string $estado
    ): bool {
        $sql = "
            UPDATE materiales_proceso_areas
            SET
                estado = :estado
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':estado' => $estado
        ]);
    }

    /**
     * Lista los profesionales activos que pueden
     * ser responsables.
     */
    public function listarProfesionalesResponsables(): array
    {
        $sql = "
            SELECT
                p.id,
                p.id_persona,
                p.tipo_profesional,
                p.registro_profesional,
                p.especialidad,

                per.nombres,
                per.apellidos,

                CONCAT(
                    TRIM(per.nombres),
                    ' ',
                    TRIM(per.apellidos)
                ) AS nombre_completo

            FROM profesionales p

            INNER JOIN personas per
                ON per.id = p.id_persona

            WHERE p.estado = 'activo'

            ORDER BY
                per.nombres ASC,
                per.apellidos ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el responsable activo de un área.
     */
    public function obtenerResponsableActivo(
        int $idProcesoArea
    ): ?array {
        $sql = "
            SELECT
                r.id,
                r.id_proceso_area,
                r.id_profesional,
                r.id_usuario_designa,
                r.fecha_designacion,
                r.fecha_fin,
                r.estado,
                r.observaciones,

                p.tipo_profesional,
                p.registro_profesional,
                p.especialidad,

                per.nombres,
                per.apellidos,

                CONCAT(
                    TRIM(per.nombres),
                    ' ',
                    TRIM(per.apellidos)
                ) AS nombre_completo

            FROM materiales_area_responsables r

            INNER JOIN profesionales p
                ON p.id = r.id_profesional

            INNER JOIN personas per
                ON per.id = p.id_persona

            WHERE r.id_proceso_area = :id_proceso_area
              AND r.estado = 'activo'
              AND r.fecha_fin IS NULL

            ORDER BY r.id DESC

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_proceso_area' => $idProcesoArea
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado !== false ? $resultado : null;
    }

    /**
     * Asigna o reemplaza el responsable de un área.
     */
    public function asignarResponsable(
        int $idProcesoArea,
        int $idProfesional,
        ?int $idUsuarioDesigna = null,
        ?string $observaciones = null
    ): bool {
        try {

            $this->db->beginTransaction();

            /**
             * Cerrar responsable activo anterior.
             */
            $sqlCerrar = "
                UPDATE materiales_area_responsables
                SET
                    estado = 'reemplazado',
                    fecha_fin = NOW()
                WHERE id_proceso_area = :id_proceso_area
                  AND estado = 'activo'
                  AND fecha_fin IS NULL
            ";

            $stmtCerrar = $this->db->prepare($sqlCerrar);

            $stmtCerrar->execute([
                ':id_proceso_area' => $idProcesoArea
            ]);

            /**
             * Registrar nuevo responsable.
             */
            $sqlInsertar = "
                INSERT INTO materiales_area_responsables
                (
                    id_proceso_area,
                    id_profesional,
                    id_usuario_designa,
                    fecha_designacion,
                    estado,
                    observaciones
                )
                VALUES
                (
                    :id_proceso_area,
                    :id_profesional,
                    :id_usuario_designa,
                    NOW(),
                    'activo',
                    :observaciones
                )
            ";

            $stmtInsertar = $this->db->prepare($sqlInsertar);

            $stmtInsertar->execute([
                ':id_proceso_area' => $idProcesoArea,
                ':id_profesional' => $idProfesional,
                ':id_usuario_designa' => $idUsuarioDesigna,
                ':observaciones' => $observaciones !== null
                    ? trim($observaciones)
                    : null
            ]);

            $this->db->commit();

            return true;

        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Retira el responsable activo de un área.
     */
    public function retirarResponsable(
        int $idProcesoArea,
        ?string $observaciones = null
    ): bool {
        $sql = "
            UPDATE materiales_area_responsables
            SET
                estado = 'retirado',
                fecha_fin = NOW(),
                observaciones = :observaciones
            WHERE id_proceso_area = :id_proceso_area
              AND estado = 'activo'
              AND fecha_fin IS NULL
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id_proceso_area' => $idProcesoArea,
            ':observaciones' => $observaciones !== null
                ? trim($observaciones)
                : null
        ]);
    }

    /**
     * Lista los procesos.
     */
    public function listarProcesos(): array
    {
        $sql = "
            SELECT
                id,
                anio,
                nombre,
                descripcion,
                fecha_inicio,
                fecha_cierre,
                estado,
                id_coordinador,
                created_at,
                updated_at

            FROM materiales_procesos

            ORDER BY
                anio DESC,
                nombre ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene información básica de un proceso.
     */
    public function obtenerProceso(
        int $idProceso
    ): ?array {
        $sql = "
            SELECT
                id,
                anio,
                nombre,
                descripcion,
                fecha_inicio,
                fecha_cierre,
                estado,
                id_coordinador,
                created_at,
                updated_at

            FROM materiales_procesos

            WHERE id = :id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $idProceso
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado !== false ? $resultado : null;
    }
}