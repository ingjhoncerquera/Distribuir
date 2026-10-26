<?php

declare(strict_types=1);

class MaterialesSolicitudModel
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
     * =========================================================
     * PROFESIONAL ASOCIADO AL USUARIO
     * =========================================================
     */
    public function obtenerProfesionalPorUsuario(
        int $idUsuario
    ): ?array {
        $sql = "
            SELECT
                pr.id,
                pr.id_persona,
                pr.tipo_profesional,
                pr.registro_profesional,
                pr.especialidad,
                pr.estado,

                u.id AS id_usuario,
                u.username,

                p.tipo_documento,
                p.documento,
                p.nombres,
                p.apellidos,

                CONCAT(
                    TRIM(p.nombres),
                    ' ',
                    TRIM(p.apellidos)
                ) AS nombre_completo,

                p.correo,
                p.telefono

            FROM usuarios u

            INNER JOIN personas p
                ON p.id = u.id_persona

            INNER JOIN profesionales pr
                ON pr.id_persona = p.id

            WHERE
                u.id = :id_usuario
                AND u.estado = 'activo'
                AND p.estado = 'activo'
                AND pr.estado = 'activo'

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_usuario' => $idUsuario,
        ]);

        $profesional = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        return $profesional ?: null;
    }

    /**
     * =========================================================
     * PROCESOS DISPONIBLES
     * =========================================================
     */
    public function listarProcesosDisponibles(): array
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

            WHERE estado IN (
                'abierto',
                'en_solicitud'
            )

            ORDER BY
                anio DESC,
                id DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /**
     * =========================================================
     * OBTENER PROCESO
     * =========================================================
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
            ':id' => $idProceso,
        ]);

        $proceso = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        return $proceso ?: null;
    }

    /**
     * =========================================================
     * ÁREAS DISPONIBLES PARA UN PROFESIONAL
     * =========================================================
     */
    public function listarAreasPorProceso(
        int $idProceso,
        int $idProfesional
    ): array {
        $sql = "
            SELECT DISTINCT
                pa.id,
                pa.id_proceso,
                pa.nombre_area,
                pa.estado,
                pa.observaciones,
                pa.created_at,

                r.id AS id_responsable,
                r.id_profesional,
                r.fecha_designacion,
                r.fecha_fin,
                r.estado AS estado_responsable,

                prof.tipo_profesional,

                per.nombres,
                per.apellidos,

                CONCAT(
                    TRIM(per.nombres),
                    ' ',
                    TRIM(per.apellidos)
                ) AS nombre_responsable

            FROM materiales_proceso_areas pa

            INNER JOIN materiales_area_responsables r
                ON r.id_proceso_area = pa.id

            INNER JOIN profesionales prof
                ON prof.id = r.id_profesional

            INNER JOIN personas per
                ON per.id = prof.id_persona

            WHERE
                pa.id_proceso = :id_proceso
                AND pa.estado = 'activa'

                AND r.id_profesional = :id_profesional
                AND r.estado = 'activo'
                AND r.fecha_fin IS NULL

                AND prof.estado = 'activo'

            ORDER BY
                pa.nombre_area ASC,
                pa.id ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_proceso' => $idProceso,
            ':id_profesional' => $idProfesional,
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /**
     * =========================================================
     * OBTENER ÁREA ACTIVA
     * =========================================================
     */
    public function obtenerArea(
        int $idProcesoArea
    ): ?array {
        $sql = "
            SELECT
                pa.id,
                pa.id_proceso,
                pa.nombre_area,
                pa.estado,
                pa.observaciones,

                pr.anio,
                pr.nombre AS nombre_proceso,
                pr.estado AS estado_proceso

            FROM materiales_proceso_areas pa

            INNER JOIN materiales_procesos pr
                ON pr.id = pa.id_proceso

            WHERE
                pa.id = :id
                AND pa.estado = 'activa'

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $idProcesoArea,
        ]);

        $area = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        return $area ?: null;
    }

    /**
     * =========================================================
     * VERIFICAR RESPONSABLE DEL ÁREA
     * =========================================================
     */
    public function profesionalEsResponsableArea(
        int $idProcesoArea,
        int $idProfesional
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM materiales_proceso_areas pa

            INNER JOIN materiales_area_responsables r
                ON r.id_proceso_area = pa.id

            INNER JOIN profesionales prof
                ON prof.id = r.id_profesional

            WHERE
                pa.id = :id_area
                AND pa.estado = 'activa'

                AND r.id_profesional = :id_profesional
                AND r.estado = 'activo'
                AND r.fecha_fin IS NULL

                AND prof.estado = 'activo'
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_area' => $idProcesoArea,
            ':id_profesional' => $idProfesional,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * =========================================================
     * MATERIALES ACTIVOS
     * =========================================================
     */
    public function listarMaterialesActivos(
        string $buscar = '',
        int $limite = 50,
        int $offset = 0
    ): array {
        $limite = max(
            1,
            min($limite, 100)
        );

        $offset = max(
            0,
            $offset
        );

        $buscar = trim($buscar);

        $where = "
            WHERE mc.estado = 'activo'
        ";

        $params = [];

        if ($buscar !== '') {

            $where .= "
                AND (
                    mc.codigo_unspsc LIKE :buscar_codigo
                    OR mc.descripcion LIKE :buscar_descripcion
                )
            ";

            $termino = '%' . $buscar . '%';

            $params[':buscar_codigo'] = $termino;
            $params[':buscar_descripcion'] = $termino;
        }

        $sql = "
            SELECT
                mc.id,
                mc.codigo_unspsc,
                mc.descripcion,
                '' AS unidad_medida,
                mc.estado,
                mc.fecha_inicio_vigencia,
                mc.fecha_fin_vigencia

            FROM materiales_catalogo mc

            {$where}

            ORDER BY
                mc.descripcion ASC,
                mc.id ASC

            LIMIT {$limite}
            OFFSET {$offset}
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /**
     * =========================================================
     * CONTAR MATERIALES ACTIVOS
     * =========================================================
     */
    public function contarMaterialesActivos(
        string $buscar = ''
    ): int {
        $buscar = trim($buscar);

        $where = "
            WHERE mc.estado = 'activo'
        ";

        $params = [];

        if ($buscar !== '') {

            $where .= "
                AND (
                    mc.codigo_unspsc LIKE :buscar_codigo
                    OR mc.descripcion LIKE :buscar_descripcion
                )
            ";

            $termino = '%' . $buscar . '%';

            $params[':buscar_codigo'] = $termino;
            $params[':buscar_descripcion'] = $termino;
        }

        $sql = "
            SELECT COUNT(*)

            FROM materiales_catalogo mc

            {$where}
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * =========================================================
     * MATERIAL ACTIVO
     * =========================================================
     */
    public function obtenerMaterialActivo(
        int $idMaterial
    ): ?array {
        $sql = "
            SELECT
                id,
                codigo_unspsc,
                descripcion,
                '' AS unidad_medida,
                estado,
                fecha_inicio_vigencia,
                fecha_fin_vigencia

            FROM materiales_catalogo

            WHERE
                id = :id
                AND estado = 'activo'

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $idMaterial,
        ]);

        $material = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        return $material ?: null;
    }

    /**
     * =========================================================
     * SOLICITUDES DEL PROFESIONAL
     * =========================================================
     */
    public function listarPorProfesional(
        int $idProfesional
    ): array {
        $sql = "
            SELECT
                s.id,
                s.id_proceso_area,
                s.id_profesional_solicitante,
                s.numero_solicitud,
                s.titulo,
                s.justificacion,
                s.estado,
                s.fecha_envio,
                s.id_usuario_revision,
                s.fecha_revision,
                s.observacion_revision,
                s.created_at,
                s.updated_at,

                pa.nombre_area,

                pr.id AS id_proceso,
                pr.anio,
                pr.nombre AS nombre_proceso,

                per.nombres,
                per.apellidos

            FROM materiales_solicitudes s

            INNER JOIN materiales_proceso_areas pa
                ON pa.id = s.id_proceso_area

            INNER JOIN materiales_procesos pr
                ON pr.id = pa.id_proceso

            INNER JOIN profesionales prof
                ON prof.id = s.id_profesional_solicitante

            INNER JOIN personas per
                ON per.id = prof.id_persona

            WHERE
                s.id_profesional_solicitante = :id_profesional

            ORDER BY
                s.created_at DESC,
                s.id DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_profesional' => $idProfesional,
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /**
     * =========================================================
     * OBTENER SOLICITUD
     * =========================================================
     */
    public function obtenerSolicitud(
    int $idSolicitud
): ?array {
    $sql = "
        SELECT
            s.id,
            s.id_proceso_area,
            s.id_profesional_solicitante,
            s.numero_solicitud,
            s.titulo,
            s.justificacion,
            s.estado,
            s.fecha_envio,
            s.id_usuario_revision,
            s.fecha_revision,
            s.observacion_revision,
            s.created_at,
            s.updated_at,

            pa.nombre_area,

            pr.id AS id_proceso,
            pr.anio,
            pr.nombre AS nombre_proceso,
            pr.estado AS estado_proceso,
            pr.id_coordinador,

            prof.id AS profesional_id,
            prof.tipo_profesional,

            per.nombres,
            per.apellidos,
            per.documento,

            CONCAT(
                COALESCE(per_coord.nombres, ''),
                ' ',
                COALESCE(per_coord.apellidos, '')
            ) AS nombre_coordinador

        FROM materiales_solicitudes s

        INNER JOIN materiales_proceso_areas pa
            ON pa.id = s.id_proceso_area

        INNER JOIN materiales_procesos pr
            ON pr.id = pa.id_proceso

        INNER JOIN profesionales prof
            ON prof.id = s.id_profesional_solicitante

        INNER JOIN personas per
            ON per.id = prof.id_persona

        LEFT JOIN profesionales prof_coord
            ON prof_coord.id = pr.id_coordinador

        LEFT JOIN personas per_coord
            ON per_coord.id = prof_coord.id_persona

        WHERE s.id = :id

        LIMIT 1
    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([
        ':id' => $idSolicitud,
    ]);

    $solicitud = $stmt->fetch(
        PDO::FETCH_ASSOC
    );

    return $solicitud ?: null;
}

    /**
     * =========================================================
     * LISTAR SOLICITUDES PENDIENTES DE AVAL
     * =========================================================
     *
     * Si $idCoordinador es NULL:
     *     devuelve todas las solicitudes pendientes.
     *
     * Si contiene un profesional:
     *     devuelve solamente solicitudes de procesos cuyo
     *     id_coordinador corresponde a ese profesional.
     */
    public function listarPendientesAval(
        ?int $idCoordinador = null
    ): array {
        $where = "
            WHERE s.estado = 'pendiente_aval'
        ";

        $params = [];

        if ($idCoordinador !== null) {

            $where .= "
                AND pr.id_coordinador = :id_coordinador
            ";

            $params[':id_coordinador'] =
                $idCoordinador;
        }

        $sql = "
            SELECT
                s.id,
                s.id_proceso_area,
                s.id_profesional_solicitante,
                s.numero_solicitud,
                s.titulo,
                s.justificacion,
                s.estado,
                s.fecha_envio,
                s.id_usuario_revision,
                s.fecha_revision,
                s.observacion_revision,
                s.created_at,
                s.updated_at,

                pa.nombre_area,

                pr.id AS id_proceso,
                pr.anio,
                pr.nombre AS nombre_proceso,
                pr.estado AS estado_proceso,
                pr.id_coordinador,

                prof.tipo_profesional,

                per.nombres,
                per.apellidos,

                CONCAT(
                    TRIM(per.nombres),
                    ' ',
                    TRIM(per.apellidos)
                ) AS nombre_solicitante

            FROM materiales_solicitudes s

            INNER JOIN materiales_proceso_areas pa
                ON pa.id = s.id_proceso_area

            INNER JOIN materiales_procesos pr
                ON pr.id = pa.id_proceso

            INNER JOIN profesionales prof
                ON prof.id = s.id_profesional_solicitante

            INNER JOIN personas per
                ON per.id = prof.id_persona

            {$where}

            ORDER BY
                s.fecha_envio ASC,
                s.id ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /**
     * =========================================================
     * COORDINADOR PUEDE REVISAR SOLICITUD
     * =========================================================
     *
     * La autorización se determina por el proceso al cual
     * pertenece la solicitud.
     *
     * materiales_solicitudes
     *      ↓
     * materiales_proceso_areas
     *      ↓
     * materiales_procesos
     *      ↓
     * id_coordinador
     */
    
public function coordinadorPuedeRevisarSolicitud(
    int $idSolicitud,
    int $idCoordinador
): bool {
    $sql = "
        SELECT COUNT(*)
        FROM materiales_solicitudes s
        INNER JOIN materiales_proceso_areas pa
            ON pa.id = s.id_proceso_area
        INNER JOIN materiales_procesos pr
            ON pr.id = pa.id_proceso
        WHERE
            s.id = :id_solicitud
            AND pr.id_coordinador = :id_coordinador
    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([
        ':id_solicitud' => $idSolicitud,
        ':id_coordinador' => $idCoordinador,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}


    /**
     * =========================================================
     * DETALLES
     * =========================================================
     */
    public function listarDetalles(
        int $idSolicitud
    ): array {
        $sql = "
            SELECT
                d.id,
                d.id_solicitud,
                d.id_material,
                d.cantidad_solicitada,
                d.observaciones,
                d.unidad_medida AS unidad_medida,
                d.descripcion_tecnica,
                d.es_consumo,
                d.es_devolutivo,
                d.es_software,
                d.es_epp,

                mc.codigo_unspsc,
                mc.descripcion,
                NULL AS unidad_medida_catalogo

            FROM materiales_solicitud_detalle d

            LEFT JOIN materiales_catalogo mc
                ON mc.id = d.id_material

            WHERE
                d.id_solicitud = :id_solicitud

            ORDER BY
                d.id ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_solicitud' => $idSolicitud,
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /**
     * Cantidad efectivamente distribuida para una línea de solicitud.
     * Si se indica ficha, devuelve solo lo entregado a esa ficha.
     */
    public function cantidadDistribuidaPorDetalle(int $idDetalle, int $idFicha = 0): float
    {
        if ($idDetalle <= 0) {
            return 0.0;
        }

        $sql = "SELECT COALESCE(SUM(cantidad_distribuida), 0)
                FROM materiales_distribucion_detalle
                WHERE id_detalle_solicitud = :detalle";
        $params = [':detalle' => $idDetalle];

        if ($idFicha > 0) {
            $sql .= " AND id_ficha = :ficha";
            $params[':ficha'] = $idFicha;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    /**
     * =========================================================
     * DESTINOS
     * =========================================================
     */
    public function listarDestinosPorDetalle(
        int $idDetalle
    ): array {
        $sql = "
            SELECT
                d.id,
                d.id_detalle_solicitud,
                d.tipo_destino,
                d.id_ficha,
                d.cantidad_destinada,
                d.observaciones,

                f.numero_ficha,
                f.regional,
                f.centro,
                f.id_programa,
                p.nombre AS nombre_programa

            FROM materiales_solicitud_destinos d

            LEFT JOIN fichas f
                ON f.id = d.id_ficha

            LEFT JOIN programas p
                ON p.id = f.id_programa

            WHERE
                d.id_detalle_solicitud = :id_detalle

            ORDER BY
                d.id ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_detalle' => $idDetalle,
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /**
     * =========================================================
     * DATOS DE FICHA PARA FORMATO GIL-F-014
     * =========================================================
     * Obtiene regional y centro directamente desde la ficha destino
     * asociada a los detalles de la solicitud. No depende de que el
     * controlador reconstruya estos datos desde arreglos intermedios.
     */
    /**
     * Lista todas las fichas destino asociadas a los detalles de una solicitud.
     * Se consulta directamente la relación de destinos para que la exportación
     * no dependa de los productos que actualmente existan en el catálogo.
     */
    public function listarFichasDestinoPorSolicitud(int $idSolicitud): array
    {
        $sql = "
            SELECT DISTINCT
                COALESCE(f.id, d.id_ficha) AS id_ficha,
                COALESCE(f.numero_ficha, CAST(d.id_ficha AS CHAR)) AS numero_ficha,
                f.regional,
                f.centro,
                f.id_programa,
                p.nombre AS nombre_programa
            FROM materiales_solicitud_detalle sd
            INNER JOIN materiales_solicitud_destinos d
                ON d.id_detalle_solicitud = sd.id
            LEFT JOIN fichas f
                ON f.id = d.id_ficha
                OR CAST(f.numero_ficha AS CHAR) = CAST(d.id_ficha AS CHAR)
            LEFT JOIN programas p
                ON p.id = f.id_programa
            WHERE sd.id_solicitud = :id_solicitud
              AND d.id_ficha IS NOT NULL
              AND d.id_ficha > 0
            ORDER BY numero_ficha ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_solicitud' => $idSolicitud]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerDatosFichaFormato(int $idSolicitud): array
    {
        $sql = "
            SELECT
                f.id AS id_ficha,
                f.numero_ficha,
                f.regional,
                f.centro,
                f.id_programa,
                p.nombre AS nombre_programa
            FROM materiales_solicitud_detalle sd
            INNER JOIN materiales_solicitud_destinos d
                ON d.id_detalle_solicitud = sd.id
            INNER JOIN fichas f
                ON f.id = d.id_ficha
            LEFT JOIN programas p
                ON p.id = f.id_programa
            WHERE sd.id_solicitud = :id_solicitud
            ORDER BY d.id ASC
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_solicitud' => $idSolicitud]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($datos) ? $datos : [];
    }

    /**
     * =========================================================
     * NÚMERO DE SOLICITUD
     * =========================================================
     */
    public function generarNumeroSolicitud(): string
    {
        do {

            $numero = 'SOL-MAT-'
                . date('Ymd-His')
                . '-'
                . random_int(100, 999);

            $sql = "
                SELECT COUNT(*)
                FROM materiales_solicitudes
                WHERE numero_solicitud = :numero
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                ':numero' => $numero,
            ]);

            $existe =
                (int) $stmt->fetchColumn() > 0;

        } while ($existe);

        return $numero;
    }

    /**
     * =========================================================
     * CREAR BORRADOR
     * =========================================================
     */
    public function crearBorrador(
        int $idProcesoArea,
        int $idProfesional,
        string $titulo,
        ?string $justificacion
    ): int {
        $numeroSolicitud =
            $this->generarNumeroSolicitud();

        $sql = "
            INSERT INTO materiales_solicitudes (
                id_proceso_area,
                id_profesional_solicitante,
                numero_solicitud,
                titulo,
                justificacion,
                estado
            )
            VALUES (
                :id_proceso_area,
                :id_profesional,
                :numero_solicitud,
                :titulo,
                :justificacion,
                'borrador'
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_proceso_area' => $idProcesoArea,
            ':id_profesional' => $idProfesional,
            ':numero_solicitud' => $numeroSolicitud,
            ':titulo' => $titulo,
            ':justificacion' => $justificacion,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * =========================================================
     * AGREGAR DETALLE
     * =========================================================
     */
    public function agregarDetalle(
        int $idSolicitud,
        int $idMaterial,
        float $cantidad,
        ?string $observaciones = null,
        string $unidadMedida = 'Unidad',
        string $descripcionTecnica = '',
        int $esConsumo = 0,
        int $esDevolutivo = 0,
        int $esSoftware = 0,
        int $esEpp = 0
    ): int {
        $sql = "
            INSERT INTO materiales_solicitud_detalle (
                id_solicitud,
                id_material,
                cantidad_solicitada,
                observaciones,
                unidad_medida,
                descripcion_tecnica,
                es_consumo,
                es_devolutivo,
                es_software,
                es_epp
            )
            VALUES (
                :id_solicitud,
                :id_material,
                :cantidad,
                :observaciones,
                :unidad_medida,
                :descripcion_tecnica,
                :es_consumo,
                :es_devolutivo,
                :es_software,
                :es_epp
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_solicitud' => $idSolicitud,
            ':id_material' => $idMaterial,
            ':cantidad' => $cantidad,
            ':observaciones' => $observaciones,
            ':unidad_medida' => trim($unidadMedida) !== ''
                ? trim($unidadMedida)
                : 'Unidad',
            ':descripcion_tecnica' => trim($descripcionTecnica),
            ':es_consumo' => $esConsumo ? 1 : 0,
            ':es_devolutivo' => $esDevolutivo ? 1 : 0,
            ':es_software' => $esSoftware ? 1 : 0,
            ':es_epp' => $esEpp ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * =========================================================
     * ACTUALIZAR DETALLE
     * =========================================================
     */
    public function actualizarDetalle(
        int $idDetalle,
        float $cantidad,
        ?string $observaciones = null
    ): bool {
        $sql = "
            UPDATE materiales_solicitud_detalle
            SET
                cantidad_solicitada = :cantidad,
                observaciones = :observaciones
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':cantidad' => $cantidad,
            ':observaciones' => $observaciones,
            ':id' => $idDetalle,
        ]);
    }

    /**
     * =========================================================
     * ELIMINAR DETALLE
     * =========================================================
     */
    public function eliminarDetalle(
        int $idDetalle
    ): bool {
        $sql = "
            DELETE FROM materiales_solicitud_detalle
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $idDetalle,
        ]);
    }

    /**
     * =========================================================
     * DETALLE PERTENECE A SOLICITUD
     * =========================================================
     */
    public function detallePerteneceSolicitud(
        int $idDetalle,
        int $idSolicitud
    ): bool {
        $sql = "
            SELECT COUNT(*)
            FROM materiales_solicitud_detalle
            WHERE
                id = :id_detalle
                AND id_solicitud = :id_solicitud
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_detalle' => $idDetalle,
            ':id_solicitud' => $idSolicitud,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * =========================================================
     * AGREGAR DESTINO
     * =========================================================
     */
    public function agregarDestino(
        int $idDetalle,
        string $tipoDestino,
        ?int $idFicha,
        float $cantidad,
        ?string $observaciones = null
    ): int {
        $sql = "
            INSERT INTO materiales_solicitud_destinos (
                id_detalle_solicitud,
                tipo_destino,
                id_ficha,
                cantidad_destinada,
                observaciones
            )
            VALUES (
                :id_detalle,
                :tipo_destino,
                :id_ficha,
                :cantidad,
                :observaciones
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_detalle' => $idDetalle,
            ':tipo_destino' => $tipoDestino,
            ':id_ficha' => $idFicha,
            ':cantidad' => $cantidad,
            ':observaciones' => $observaciones,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * =========================================================
     * CANTIDAD DEL DETALLE
     * =========================================================
     */
    public function obtenerCantidadDetalle(
        int $idDetalle
    ): ?float {
        $sql = "
            SELECT cantidad_solicitada
            FROM materiales_solicitud_detalle
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $idDetalle,
        ]);

        $valor = $stmt->fetchColumn();

        if ($valor === false) {
            return null;
        }

        return (float) $valor;
    }

    /**
     * =========================================================
     * TOTAL DESTINADO
     * =========================================================
     */
    public function obtenerTotalDestinado(
        int $idDetalle
    ): float {
        $sql = "
            SELECT
                COALESCE(
                    SUM(cantidad_destinada),
                    0
                )

            FROM materiales_solicitud_destinos

            WHERE id_detalle_solicitud = :id_detalle
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_detalle' => $idDetalle,
        ]);

        return (float) $stmt->fetchColumn();
    }

    /**
     * =========================================================
     * CAMBIAR ESTADO
     * =========================================================
     */
    public function cambiarEstado(
        int $idSolicitud,
        string $nuevoEstado,
        ?int $idUsuario,
        ?string $observacion = null
    ): bool {
        $estadosPermitidos = [
            'borrador',
            'pendiente_aval',
            'devuelta',
            'avalada',
            'rechazada',
            'cancelada',
        ];

        if (
            !in_array(
                $nuevoEstado,
                $estadosPermitidos,
                true
            )
        ) {
            return false;
        }

        $this->db->beginTransaction();

        try {

            $sqlActual = "
                SELECT estado
                FROM materiales_solicitudes
                WHERE id = :id
                FOR UPDATE
            ";

            $stmtActual = $this->db->prepare(
                $sqlActual
            );

            $stmtActual->execute([
                ':id' => $idSolicitud,
            ]);

            $estadoAnterior =
                $stmtActual->fetchColumn();

            if ($estadoAnterior === false) {

                $this->db->rollBack();

                return false;
            }

            $sqlActualizar = "
                UPDATE materiales_solicitudes
                SET
                    estado = :estado,
                    fecha_envio =
                        CASE
                            WHEN :estado_envio =
                                'pendiente_aval'
                                AND fecha_envio IS NULL
                            THEN NOW()
                            ELSE fecha_envio
                        END,
                    updated_at = NOW()
                WHERE id = :id
            ";

            $stmtActualizar =
                $this->db->prepare(
                    $sqlActualizar
                );

            $stmtActualizar->execute([
                ':estado' => $nuevoEstado,
                ':estado_envio' => $nuevoEstado,
                ':id' => $idSolicitud,
            ]);

            $sqlHistorial = "
                INSERT INTO materiales_solicitud_historial (
                    id_solicitud,
                    estado_anterior,
                    estado_nuevo,
                    id_usuario,
                    observacion
                )
                VALUES (
                    :id_solicitud,
                    :estado_anterior,
                    :estado_nuevo,
                    :id_usuario,
                    :observacion
                )
            ";

            $stmtHistorial =
                $this->db->prepare(
                    $sqlHistorial
                );

            $stmtHistorial->execute([
                ':id_solicitud' => $idSolicitud,
                ':estado_anterior' =>
                    $estadoAnterior,
                ':estado_nuevo' =>
                    $nuevoEstado,
                ':id_usuario' => $idUsuario,
                ':observacion' =>
                    $observacion,
            ]);

            $this->db->commit();

            return true;

        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return false;
        }
    }

    /**
     * =========================================================
     * REGISTRAR DECISIÓN DE AVAL
     * =========================================================
     *
     * Estados permitidos:
     *
     * pendiente_aval → avalada
     * pendiente_aval → devuelta
     *
     * La operación se ejecuta dentro de una transacción y
     * bloquea la solicitud para evitar doble revisión.
     */
    public function registrarDecisionAval(
        int $idSolicitud,
        string $nuevoEstado,
        int $idUsuario,
        ?string $observacion = null
    ): bool {
        if (
            !in_array(
                $nuevoEstado,
                [
                    'avalada',
                    'devuelta',
                ],
                true
            )
        ) {
            return false;
        }

        if (
            $nuevoEstado === 'devuelta'
            &&
            trim(
                (string) $observacion
            ) === ''
        ) {
            return false;
        }

        $this->db->beginTransaction();

        try {

            /*
             * =====================================================
             * BLOQUEAR SOLICITUD
             * =====================================================
             */
            $sqlSolicitud = "
                SELECT
                    id,
                    estado
                FROM materiales_solicitudes
                WHERE id = :id
                FOR UPDATE
            ";

            $stmtSolicitud =
                $this->db->prepare(
                    $sqlSolicitud
                );

            $stmtSolicitud->execute([
                ':id' => $idSolicitud,
            ]);

            $solicitud =
                $stmtSolicitud->fetch(
                    PDO::FETCH_ASSOC
                );

            if (
                !$solicitud
                ||
                ($solicitud['estado'] ?? '')
                    !== 'pendiente_aval'
            ) {

                $this->db->rollBack();

                return false;
            }

            /*
             * =====================================================
             * ACTUALIZAR DECISIÓN
             * =====================================================
             */
            $sqlActualizar = "
                UPDATE materiales_solicitudes
                SET
                    estado = :estado,
                    id_usuario_revision = :id_usuario,
                    fecha_revision = NOW(),
                    observacion_revision = :observacion,
                    updated_at = NOW()
                WHERE
                    id = :id
                    AND estado = 'pendiente_aval'
            ";

            $stmtActualizar =
                $this->db->prepare(
                    $sqlActualizar
                );

            $stmtActualizar->execute([
                ':estado' =>
                    $nuevoEstado,

                ':id_usuario' =>
                    $idUsuario,

                ':observacion' =>
                    $observacion !== null
                        ? trim($observacion)
                        : null,

                ':id' =>
                    $idSolicitud,
            ]);

            if (
                $stmtActualizar->rowCount() !== 1
            ) {

                $this->db->rollBack();

                return false;
            }

            /*
             * =====================================================
             * REGISTRAR HISTORIAL
             * =====================================================
             */
            $sqlHistorial = "
                INSERT INTO materiales_solicitud_historial (
                    id_solicitud,
                    estado_anterior,
                    estado_nuevo,
                    id_usuario,
                    observacion
                )
                VALUES (
                    :id_solicitud,
                    'pendiente_aval',
                    :estado_nuevo,
                    :id_usuario,
                    :observacion
                )
            ";

            $stmtHistorial =
                $this->db->prepare(
                    $sqlHistorial
                );

            $stmtHistorial->execute([
                ':id_solicitud' =>
                    $idSolicitud,

                ':estado_nuevo' =>
                    $nuevoEstado,

                ':id_usuario' =>
                    $idUsuario,

                ':observacion' =>
                    $observacion !== null
                        ? trim($observacion)
                        : null,
            ]);

            $this->db->commit();

            return true;

        } catch (Throwable $e) {

            if (
                $this->db->inTransaction()
            ) {
                $this->db->rollBack();
            }

            return false;
        }
    }

    /**
     * =========================================================
     * REGISTRAR HISTORIAL
     * =========================================================
     */
    public function registrarHistorial(
        int $idSolicitud,
        ?string $estadoAnterior,
        string $estadoNuevo,
        ?int $idUsuario,
        ?string $observacion = null
    ): bool {
        $sql = "
            INSERT INTO materiales_solicitud_historial
            (
                id_solicitud,
                estado_anterior,
                estado_nuevo,
                id_usuario,
                observacion
            )
            VALUES
            (
                :id_solicitud,
                :estado_anterior,
                :estado_nuevo,
                :id_usuario,
                :observacion
            )
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id_solicitud' => $idSolicitud,
            ':estado_anterior' => $estadoAnterior,
            ':estado_nuevo' => $estadoNuevo,
            ':id_usuario' => $idUsuario,
            ':observacion' => $observacion,
        ]);
    }

    /**
     * =========================================================
     * FICHAS DISPONIBLES PARA DESTINOS
     * =========================================================
     */
    public function listarFichasDisponibles(): array
    {
        $sql = "
            SELECT
                id,
                numero_ficha
            FROM fichas
            ORDER BY
                numero_ficha ASC,
                id ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /**
     * Fichas activas asignadas al profesional como líder de ficha,
     * con asignación activa y vigente en la fecha actual.
     */
    public function listarFichasDestinoPorProfesional(
        int $idProfesional
    ): array {
        if ($idProfesional <= 0) {
            return [];
        }

        $sql = "
            SELECT DISTINCT
                f.id,
                f.numero_ficha
            FROM fichas f
            INNER JOIN asignaciones_ficha af
                ON af.id_ficha = f.id
            WHERE af.id_profesional = :id_profesional
              AND af.tipo_asignacion = 'LIDER_FICHA'
              AND LOWER(COALESCE(af.estado, '')) = 'activa'
              AND (af.fecha_inicio IS NULL OR af.fecha_inicio <= CURDATE())
              AND (af.fecha_fin IS NULL OR af.fecha_fin >= CURDATE())
              AND LOWER(COALESCE(f.estado, 'activa')) = 'activa'
            ORDER BY
                f.numero_ficha ASC,
                f.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_profesional' => $idProfesional,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fichas activas asignadas al profesional para actividades de formación.
     * Incluye líder de ficha e instructores técnicos/transversales.
     */
    public function listarFichasAsignadasPorProfesional(int $idProfesional): array
    {
        if ($idProfesional <= 0) {
            return [];
        }

        $sql = "
            SELECT DISTINCT
                f.id,
                f.numero_ficha
            FROM fichas f
            INNER JOIN asignaciones_ficha af
                ON af.id_ficha = f.id
            WHERE af.id_profesional = :id_profesional
              AND af.tipo_asignacion IN ('LIDER_FICHA', 'INSTRUCTOR_TECNICO', 'INSTRUCTOR_TRANSVERSAL')
              AND LOWER(COALESCE(af.estado, '')) = 'activa'
              AND (af.fecha_inicio IS NULL OR af.fecha_inicio <= CURDATE())
              AND (af.fecha_fin IS NULL OR af.fecha_fin >= CURDATE())
              AND LOWER(COALESCE(f.estado, 'activa')) = 'activa'
            ORDER BY f.numero_ficha ASC, f.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_profesional' => $idProfesional]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * =========================================================
     * ACTUALIZAR BORRADOR COMPLETO
     * =========================================================
     */
    public function actualizarBorradorCompleto(
        int $idSolicitud,
        string $titulo,
        ?string $justificacion,
        array $detalles
    ): bool {
        if (empty($detalles)) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            $stmtSolicitud = $this->db->prepare(
                "SELECT id, estado FROM materiales_solicitudes WHERE id = :id FOR UPDATE"
            );
            $stmtSolicitud->execute([':id' => $idSolicitud]);
            $solicitud = $stmtSolicitud->fetch(PDO::FETCH_ASSOC);

            if (!$solicitud || !in_array((string) ($solicitud['estado'] ?? ''), ['borrador', 'devuelta'], true)) {
                $this->db->rollBack();
                return false;
            }

            $stmtActualizarSolicitud = $this->db->prepare(
                "UPDATE materiales_solicitudes
                 SET titulo = :titulo, justificacion = :justificacion, updated_at = NOW()
                 WHERE id = :id AND estado IN ('borrador', 'devuelta')"
            );
            $stmtActualizarSolicitud->execute([
                ':titulo' => $titulo,
                ':justificacion' => $justificacion,
                ':id' => $idSolicitud,
            ]);

            $stmtActuales = $this->db->prepare(
                "SELECT id FROM materiales_solicitud_detalle WHERE id_solicitud = :id_solicitud FOR UPDATE"
            );
            $stmtActuales->execute([':id_solicitud' => $idSolicitud]);
            $idsActuales = array_map('intval', $stmtActuales->fetchAll(PDO::FETCH_COLUMN));
            $idsConservados = [];

            $stmtExisteDetalle = $this->db->prepare(
                "SELECT id FROM materiales_solicitud_detalle
                 WHERE id = :id_detalle AND id_solicitud = :id_solicitud LIMIT 1"
            );
            $stmtMaterial = $this->db->prepare(
                "SELECT COUNT(*) FROM materiales_catalogo WHERE id = :id AND estado = 'activo'"
            );
            $stmtTotalDestinado = $this->db->prepare(
                "SELECT COALESCE(SUM(cantidad_destinada), 0)
                 FROM materiales_solicitud_destinos WHERE id_detalle_solicitud = :id_detalle"
            );
            $stmtActualizarDetalle = $this->db->prepare(
                "UPDATE materiales_solicitud_detalle
                 SET id_material = :id_material,
                     cantidad_solicitada = :cantidad,
                     observaciones = :observaciones,
                     unidad_medida = :unidad_medida,
                     descripcion_tecnica = :descripcion_tecnica,
                     es_consumo = :es_consumo,
                     es_devolutivo = :es_devolutivo,
                     es_software = :es_software,
                     es_epp = :es_epp
                 WHERE id = :id_detalle AND id_solicitud = :id_solicitud"
            );
            $stmtInsertarDetalle = $this->db->prepare(
                "INSERT INTO materiales_solicitud_detalle
                 (id_solicitud, id_material, cantidad_solicitada, observaciones,
                  unidad_medida, descripcion_tecnica, es_consumo, es_devolutivo, es_software, es_epp)
                 VALUES
                 (:id_solicitud, :id_material, :cantidad, :observaciones,
                  :unidad_medida, :descripcion_tecnica, :es_consumo, :es_devolutivo, :es_software, :es_epp)"
            );
            $stmtEliminarDestinos = $this->db->prepare(
                "DELETE FROM materiales_solicitud_destinos WHERE id_detalle_solicitud = :id_detalle"
            );
            $stmtInsertarDestino = $this->db->prepare(
                "INSERT INTO materiales_solicitud_destinos
                 (id_detalle_solicitud, tipo_destino, id_ficha, cantidad_destinada, observaciones)
                 VALUES (:id_detalle, :tipo_destino, :id_ficha, :cantidad, :observaciones)"
            );

            foreach ($detalles as $detalle) {
                if (!is_array($detalle)) {
                    throw new RuntimeException('Detalle de material inválido.');
                }

                $idDetalle = (int) ($detalle['id_detalle'] ?? 0);
                $idMaterial = (int) ($detalle['id_material'] ?? 0);
                $cantidad = (float) ($detalle['cantidad'] ?? 0);
                $observaciones = trim((string) ($detalle['observaciones'] ?? ''));
                $unidadMedida = trim((string) ($detalle['unidad_medida'] ?? 'Unidad'));
                $descripcionTecnica = trim((string) ($detalle['descripcion_tecnica'] ?? ''));
                $esConsumo = !empty($detalle['es_consumo']) ? 1 : 0;
                $esDevolutivo = !empty($detalle['es_devolutivo']) ? 1 : 0;
                $esSoftware = !empty($detalle['es_software']) ? 1 : 0;
                $esEpp = !empty($detalle['es_epp']) ? 1 : 0;

                if ($idMaterial <= 0 || $cantidad <= 0 || $descripcionTecnica === '') {
                    throw new RuntimeException('Falta información obligatoria en uno de los materiales.');
                }

                $stmtMaterial->execute([':id' => $idMaterial]);
                if ((int) $stmtMaterial->fetchColumn() <= 0) {
                    throw new RuntimeException('Uno de los materiales ya no está activo.');
                }

                if ($idDetalle > 0) {
                    $stmtExisteDetalle->execute([
                        ':id_detalle' => $idDetalle,
                        ':id_solicitud' => $idSolicitud,
                    ]);
                    if (!$stmtExisteDetalle->fetchColumn()) {
                        throw new RuntimeException('Una línea no pertenece a la solicitud que se está editando.');
                    }

                    $stmtTotalDestinado->execute([':id_detalle' => $idDetalle]);
                    $totalDestinoActual = (float) $stmtTotalDestinado->fetchColumn();
                    if (!array_key_exists('destinos', $detalle) && $totalDestinoActual > $cantidad) {
                        throw new RuntimeException('La cantidad solicitada no puede ser inferior a la cantidad ya destinada a fichas o áreas.');
                    }

                    $stmtActualizarDetalle->execute([
                        ':id_material' => $idMaterial,
                        ':cantidad' => $cantidad,
                        ':observaciones' => $observaciones !== '' ? $observaciones : null,
                        ':unidad_medida' => $unidadMedida !== '' ? $unidadMedida : 'Unidad',
                        ':descripcion_tecnica' => $descripcionTecnica,
                        ':es_consumo' => $esConsumo,
                        ':es_devolutivo' => $esDevolutivo,
                        ':es_software' => $esSoftware,
                        ':es_epp' => $esEpp,
                        ':id_detalle' => $idDetalle,
                        ':id_solicitud' => $idSolicitud,
                    ]);
                    $idsConservados[] = $idDetalle;
                } else {
                    $stmtInsertarDetalle->execute([
                        ':id_solicitud' => $idSolicitud,
                        ':id_material' => $idMaterial,
                        ':cantidad' => $cantidad,
                        ':observaciones' => $observaciones !== '' ? $observaciones : null,
                        ':unidad_medida' => $unidadMedida !== '' ? $unidadMedida : 'Unidad',
                        ':descripcion_tecnica' => $descripcionTecnica,
                        ':es_consumo' => $esConsumo,
                        ':es_devolutivo' => $esDevolutivo,
                        ':es_software' => $esSoftware,
                        ':es_epp' => $esEpp,
                    ]);
                    $idDetalle = (int) $this->db->lastInsertId();
                    $idsConservados[] = $idDetalle;
                }

                // Si destinos no venía en el POST, se conservan intactos.
                // Si sí venía, se reemplazan transaccionalmente por la lista validada.
                if (array_key_exists('destinos', $detalle)) {
                    $stmtEliminarDestinos->execute([':id_detalle' => $idDetalle]);
                    $destinos = $detalle['destinos'];
                    if (!is_array($destinos)) {
                        throw new RuntimeException('La información de destinos no es válida.');
                    }

                    $totalDestinado = 0.0;
                    foreach ($destinos as $destino) {
                        if (!is_array($destino)) {
                            continue;
                        }

                        $tipoDestino = trim((string) ($destino['tipo_destino'] ?? ''));
                        $cantidadDestino = (float) ($destino['cantidad_destinada'] ?? 0);
                        $idFicha = isset($destino['id_ficha']) && (int) $destino['id_ficha'] > 0
                            ? (int) $destino['id_ficha']
                            : null;
                        $observacionDestino = trim((string) ($destino['observaciones'] ?? ''));

                        if ($cantidadDestino <= 0 || !in_array($tipoDestino, ['ficha', 'general_area'], true)) {
                            throw new RuntimeException('Uno de los destinos de material no es válido.');
                        }
                        if ($tipoDestino === 'ficha' && $idFicha === null) {
                            throw new RuntimeException('Debe seleccionar una ficha para cada destino por ficha.');
                        }
                        if ($tipoDestino === 'general_area') {
                            $idFicha = null;
                        }

                        $totalDestinado += $cantidadDestino;
                        if ($totalDestinado > $cantidad) {
                            throw new RuntimeException('La cantidad destinada supera la cantidad solicitada.');
                        }

                        $stmtInsertarDestino->execute([
                            ':id_detalle' => $idDetalle,
                            ':tipo_destino' => $tipoDestino,
                            ':id_ficha' => $idFicha,
                            ':cantidad' => $cantidadDestino,
                            ':observaciones' => $observacionDestino !== '' ? $observacionDestino : null,
                        ]);
                    }
                }
            }

            // Eliminar únicamente las líneas que ya no fueron enviadas por el formulario.
            $idsAEliminar = array_values(array_diff($idsActuales, $idsConservados));
            foreach ($idsAEliminar as $idEliminar) {
                $stmtEliminarDestinos->execute([':id_detalle' => (int) $idEliminar]);
                $stmtBorrarDetalle = $this->db->prepare(
                    "DELETE FROM materiales_solicitud_detalle WHERE id = :id AND id_solicitud = :id_solicitud"
                );
                $stmtBorrarDetalle->execute([
                    ':id' => (int) $idEliminar,
                    ':id_solicitud' => $idSolicitud,
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[SITA materiales] Error en actualizarBorradorCompleto: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * =========================================================
     * ELIMINAR BORRADOR COMPLETO
     * =========================================================
     */
    public function eliminarBorrador(
        int $idSolicitud
    ): bool {
        $this->db->beginTransaction();

        try {

            $sqlSolicitud = "
                SELECT
                    id,
                    estado
                FROM materiales_solicitudes
                WHERE id = :id
                FOR UPDATE
            ";

            $stmtSolicitud =
                $this->db->prepare(
                    $sqlSolicitud
                );

            $stmtSolicitud->execute([
                ':id' => $idSolicitud,
            ]);

            $solicitud =
                $stmtSolicitud->fetch(
                    PDO::FETCH_ASSOC
                );

            if (
                !$solicitud
                ||
                ($solicitud['estado'] ?? '') !== 'borrador'
            ) {
                $this->db->rollBack();

                return false;
            }

            $sqlDetalles = "
                SELECT id
                FROM materiales_solicitud_detalle
                WHERE id_solicitud = :id
            ";

            $stmtDetalles =
                $this->db->prepare(
                    $sqlDetalles
                );

            $stmtDetalles->execute([
                ':id' => $idSolicitud,
            ]);

            $detalles =
                $stmtDetalles->fetchAll(
                    PDO::FETCH_COLUMN
                );

            if (!empty($detalles)) {

                $placeholders = implode(
                    ',',
                    array_fill(
                        0,
                        count($detalles),
                        '?'
                    )
                );

                $sqlDestinos = "
                    DELETE FROM materiales_solicitud_destinos
                    WHERE id_detalle_solicitud IN (
                        {$placeholders}
                    )
                ";

                $stmtDestinos =
                    $this->db->prepare(
                        $sqlDestinos
                    );

                $stmtDestinos->execute(
                    array_map(
                        'intval',
                        $detalles
                    )
                );
            }

            $sqlEliminarDetalles = "
                DELETE FROM materiales_solicitud_detalle
                WHERE id_solicitud = :id
            ";

            $stmtEliminarDetalles =
                $this->db->prepare(
                    $sqlEliminarDetalles
                );

            $stmtEliminarDetalles->execute([
                ':id' => $idSolicitud,
            ]);

            $sqlHistorial = "
                DELETE FROM materiales_solicitud_historial
                WHERE id_solicitud = :id
            ";

            $stmtHistorial =
                $this->db->prepare(
                    $sqlHistorial
                );

            $stmtHistorial->execute([
                ':id' => $idSolicitud,
            ]);

            $sqlSolicitudDelete = "
                DELETE FROM materiales_solicitudes
                WHERE
                    id = :id
                    AND estado = 'borrador'
            ";

            $stmtSolicitudDelete =
                $this->db->prepare(
                    $sqlSolicitudDelete
                );

            $stmtSolicitudDelete->execute([
                ':id' => $idSolicitud,
            ]);

            if (
                $stmtSolicitudDelete->rowCount() !== 1
            ) {
                throw new RuntimeException(
                    'No fue posible eliminar la solicitud.'
                );
            }

            $this->db->commit();

            return true;

        } catch (Throwable $e) {

            if (
                $this->db->inTransaction()
            ) {
                $this->db->rollBack();
            }

            return false;
        }
    }

    /**
     * Solicitudes avaladas disponibles para consolidación.
     * Un coordinador solo consulta los procesos que tiene asignados.
     */
    public function listarAvaladasParaConsolidar(int $idCoordinador): array
    {
        $sql = "
            SELECT
                s.id, s.id_proceso_area, s.id_profesional_solicitante,
                s.numero_solicitud, s.titulo, s.estado, s.fecha_envio,
                s.fecha_revision, pa.nombre_area, pr.id AS id_proceso,
                pr.anio, pr.nombre AS nombre_proceso, pr.id_coordinador,
                CONCAT(TRIM(per.nombres), ' ', TRIM(per.apellidos)) AS nombre_solicitante,
                COUNT(d.id) AS total_materiales
            FROM materiales_solicitudes s
            INNER JOIN materiales_proceso_areas pa ON pa.id = s.id_proceso_area
            INNER JOIN materiales_procesos pr ON pr.id = pa.id_proceso
            INNER JOIN profesionales prof ON prof.id = s.id_profesional_solicitante
            INNER JOIN personas per ON per.id = prof.id_persona
            LEFT JOIN materiales_solicitud_detalle d ON d.id_solicitud = s.id
            WHERE s.estado = 'avalada'
              AND pr.id_coordinador = :id_coordinador
            GROUP BY s.id, s.id_proceso_area, s.id_profesional_solicitante,
                s.numero_solicitud, s.titulo, s.estado, s.fecha_envio,
                s.fecha_revision, pa.nombre_area, pr.id, pr.anio,
                pr.nombre, pr.id_coordinador, per.nombres, per.apellidos
            ORDER BY s.fecha_revision DESC, s.id DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_coordinador' => $idCoordinador]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}