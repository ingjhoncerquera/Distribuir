<?php
declare(strict_types=1);

/**
 * Modelo propio del módulo Entregas.
 * Consulta solicitudes avaladas, destinos y cantidades distribuidas sin
 * depender de MaterialesSolicitudModel.
 */
class EntregasModel
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

    public function profesionalUsuario(int $idUsuario): ?int
    {
        $sql = "SELECT pr.id
                FROM usuarios u
                JOIN personas p ON p.id = u.id_persona
                JOIN profesionales pr ON pr.id_persona = p.id
                WHERE u.id = :usuario
                  AND u.estado = 'activo'
                  AND p.estado = 'activo'
                  AND pr.estado = 'activo'
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':usuario' => $idUsuario]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function solicitudesAvaladas(int $coordinador, bool $admin): array
    {
        $filtro = $admin ? '' : ' AND pr.id_coordinador = :coordinador';
        $sql = "SELECT s.id AS id_solicitud, s.numero_solicitud, s.titulo,
                       s.estado, s.fecha_envio, pa.nombre_area,
                       pr.anio, pr.nombre AS nombre_proceso,
                       CONCAT(p.nombres, ' ', p.apellidos) AS solicitante
                FROM materiales_solicitudes s
                JOIN materiales_proceso_areas pa ON pa.id = s.id_proceso_area
                JOIN materiales_procesos pr ON pr.id = pa.id_proceso
                JOIN profesionales pro ON pro.id = s.id_profesional_solicitante
                JOIN personas p ON p.id = pro.id_persona
                WHERE s.estado = 'avalada' {$filtro}
                ORDER BY pr.anio DESC, s.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($admin ? [] : [':coordinador' => $coordinador]);
        $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($solicitudes as &$solicitud) {
            $solicitud['detalles'] = $this->detallesParaListado((int) $solicitud['id_solicitud']);
        }
        unset($solicitud);
        return $solicitudes;
    }

    public function obtenerSolicitud(int $idSolicitud): ?array
    {
        $sql = "SELECT s.id, s.id_proceso_area, s.id_profesional_solicitante,
                       s.numero_solicitud, s.titulo, s.justificacion, s.estado,
                       s.fecha_envio, s.created_at, s.updated_at,
                       pa.nombre_area, pr.id AS id_proceso, pr.anio,
                       pr.nombre AS nombre_proceso, pr.estado AS estado_proceso,
                       pr.id_coordinador, prof.id AS profesional_id,
                       prof.tipo_profesional, per.nombres, per.apellidos,
                       per.documento,
                       CONCAT(COALESCE(pc.nombres, ''), ' ', COALESCE(pc.apellidos, '')) AS nombre_coordinador
                FROM materiales_solicitudes s
                JOIN materiales_proceso_areas pa ON pa.id = s.id_proceso_area
                JOIN materiales_procesos pr ON pr.id = pa.id_proceso
                JOIN profesionales prof ON prof.id = s.id_profesional_solicitante
                JOIN personas per ON per.id = prof.id_persona
                LEFT JOIN profesionales profc ON profc.id = pr.id_coordinador
                LEFT JOIN personas pc ON pc.id = profc.id_persona
                WHERE s.id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idSolicitud]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listarDetalles(int $idSolicitud): array
    {
        $sql = "SELECT d.id, d.id_solicitud, d.id_material,
                       d.cantidad_solicitada, d.observaciones,
                       d.unidad_medida, d.descripcion_tecnica,
                       mc.codigo_unspsc, mc.descripcion
                FROM materiales_solicitud_detalle d
                LEFT JOIN materiales_catalogo mc ON mc.id = d.id_material
                WHERE d.id_solicitud = :id
                ORDER BY d.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idSolicitud]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function detallesParaListado(int $idSolicitud): array
    {
        $sql = "SELECT d.id AS id_detalle, d.id_material, d.cantidad_solicitada,
                       d.unidad_medida, d.observaciones, mc.codigo_unspsc,
                       mc.descripcion,
                       COALESCE((SELECT SUM(dd.cantidad_distribuida)
                                 FROM materiales_distribucion_detalle dd
                                 WHERE dd.id_detalle_solicitud = d.id), 0) AS distribuido
                FROM materiales_solicitud_detalle d
                LEFT JOIN materiales_catalogo mc ON mc.id = d.id_material
                WHERE d.id_solicitud = :id
                ORDER BY d.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idSolicitud]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarDestinosPorDetalle(int $idDetalle): array
    {
        $sql = "SELECT d.id, d.id_detalle_solicitud, d.tipo_destino, d.id_ficha,
                       d.cantidad_destinada, d.observaciones,
                       f.numero_ficha, f.regional, f.centro, f.id_programa,
                       p.nombre AS nombre_programa
                FROM materiales_solicitud_destinos d
                LEFT JOIN fichas f ON f.id = d.id_ficha
                LEFT JOIN programas p ON p.id = f.id_programa
                WHERE d.id_detalle_solicitud = :id
                ORDER BY d.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idDetalle]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarFichasDestinoPorSolicitud(int $idSolicitud): array
    {
        $sql = "SELECT DISTINCT COALESCE(f.id, d.id_ficha) AS id_ficha,
                       COALESCE(f.numero_ficha, CAST(d.id_ficha AS CHAR)) AS numero_ficha,
                       f.regional, f.centro, f.id_programa, p.nombre AS nombre_programa
                FROM materiales_solicitud_detalle sd
                JOIN materiales_solicitud_destinos d ON d.id_detalle_solicitud = sd.id
                LEFT JOIN fichas f ON f.id = d.id_ficha
                    OR CAST(f.numero_ficha AS CHAR) = CAST(d.id_ficha AS CHAR)
                LEFT JOIN programas p ON p.id = f.id_programa
                WHERE sd.id_solicitud = :id AND d.id_ficha IS NOT NULL AND d.id_ficha > 0
                ORDER BY numero_ficha ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idSolicitud]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerDatosFichaFormato(int $idSolicitud): array
    {
        $sql = "SELECT f.id AS id_ficha, f.numero_ficha, f.regional, f.centro,
                       f.id_programa, p.nombre AS nombre_programa
                FROM materiales_solicitud_detalle sd
                JOIN materiales_solicitud_destinos d ON d.id_detalle_solicitud = sd.id
                JOIN fichas f ON f.id = d.id_ficha
                LEFT JOIN programas p ON p.id = f.id_programa
                WHERE sd.id_solicitud = :id
                ORDER BY d.id ASC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idSolicitud]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: [];
    }

    public function cantidadDistribuidaPorDetalle(int $idDetalle, int $idFicha = 0): float
    {
        if ($idDetalle <= 0) {
            return 0.0;
        }
        $sql = "SELECT COALESCE(SUM(dd.cantidad_distribuida), 0)
                FROM materiales_distribucion_detalle dd
                WHERE dd.id_detalle_solicitud = :detalle";
        $params = [':detalle' => $idDetalle];
        if ($idFicha > 0) {
            // Admitir tanto el ID interno como el número visible de ficha.
            $sql .= " AND (
                CAST(dd.id_ficha AS CHAR) = CAST(:ficha_id AS CHAR)
                OR CAST(dd.id_ficha AS CHAR) = (
                    SELECT CAST(f.numero_ficha AS CHAR)
                    FROM fichas f WHERE f.id = :ficha_lookup LIMIT 1
                )
            )";
            $params[':ficha_id'] = $idFicha;
            $params[':ficha_lookup'] = $idFicha;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }
}
