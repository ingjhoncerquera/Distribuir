<?php
declare(strict_types=1);

/** SITA - Acceso a datos del seguimiento físico PyP. */
class SeguimientoFisicoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->conectar();
    }

    public function listarFichasDisponibles(int $idUsuario, bool $esAdministrador): array
    {
        if ($esAdministrador) {
            $sql = "SELECT id, numero_ficha FROM fichas WHERE estado <> 'cancelada' ORDER BY numero_ficha DESC";
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }

        $sql = "
            SELECT DISTINCT f.id, f.numero_ficha
            FROM fichas f
            INNER JOIN asignaciones_ficha af ON af.id_ficha = f.id
            INNER JOIN profesionales pr ON pr.id = af.id_profesional
            INNER JOIN personas pp ON pp.id = pr.id_persona
            INNER JOIN usuarios u ON u.id_persona = pp.id
            WHERE u.id = ?
              AND pr.estado = 'activo'
              AND af.estado = 'activa'
              AND af.fecha_inicio <= CURRENT_DATE
              AND (af.fecha_fin IS NULL OR af.fecha_fin >= CURRENT_DATE)
              AND af.tipo_asignacion IN ('INSTRUCTOR_TRANSVERSAL','SALUD_FISICA','LIDER_FICHA','BIENESTAR')
              AND f.estado <> 'cancelada'
            ORDER BY f.numero_ficha DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idUsuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function usuarioPuedeGestionarFicha(int $idUsuario, int $idFicha, bool $esAdministrador): bool
    {
        if ($esAdministrador) return true;
        $sql = "
            SELECT COUNT(*)
            FROM asignaciones_ficha af
            INNER JOIN profesionales pr ON pr.id = af.id_profesional
            INNER JOIN personas pp ON pp.id = pr.id_persona
            INNER JOIN usuarios u ON u.id_persona = pp.id
            WHERE u.id = ? AND af.id_ficha = ?
              AND pr.estado = 'activo' AND af.estado = 'activa'
              AND af.fecha_inicio <= CURRENT_DATE
              AND (af.fecha_fin IS NULL OR af.fecha_fin >= CURRENT_DATE)
              AND af.tipo_asignacion IN ('INSTRUCTOR_TRANSVERSAL','SALUD_FISICA','LIDER_FICHA','BIENESTAR')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idUsuario, $idFicha]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function listarAprendices(int $idFicha, string $buscar = ''): array
    {
        $sql = "
            SELECT a.id AS id_aprendiz, p.tipo_documento, p.documento,
                   p.nombres, p.apellidos, a.estado AS estado_aprendiz,
                   MAX(sf.fecha_toma) AS ultima_toma,
                   (SELECT sf2.proxima_fecha FROM seguimientos_fisicos sf2
                    WHERE sf2.id_aprendiz = a.id AND sf2.id_ficha = ?
                    ORDER BY sf2.fecha_toma DESC, sf2.id DESC LIMIT 1) AS proxima_fecha
            FROM matriculas m
            INNER JOIN aprendices a ON a.id = m.id_aprendiz
            INNER JOIN personas p ON p.id = a.id_persona
            LEFT JOIN seguimientos_fisicos sf ON sf.id_aprendiz = a.id AND sf.id_ficha = m.id_ficha
            WHERE m.id_ficha = ?";
        $params = [$idFicha, $idFicha];
        if ($buscar !== '') {
            $sql .= " AND (p.documento LIKE ? OR p.nombres LIKE ? OR p.apellidos LIKE ?)";
            $like = '%' . $buscar . '%';
            array_push($params, $like, $like, $like);
        }
        $sql .= " GROUP BY a.id, p.tipo_documento, p.documento, p.nombres, p.apellidos, a.estado ORDER BY p.apellidos, p.nombres";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerAprendizDeFicha(int $idAprendiz, int $idFicha): ?array
    {
        $sql = "SELECT a.id AS id_aprendiz, p.tipo_documento, p.documento, p.nombres, p.apellidos,
                       f.numero_ficha
                FROM matriculas m
                INNER JOIN aprendices a ON a.id = m.id_aprendiz
                INNER JOIN personas p ON p.id = a.id_persona
                INNER JOIN fichas f ON f.id = m.id_ficha
                WHERE a.id = ? AND m.id_ficha = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idAprendiz, $idFicha]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function historial(int $idAprendiz, int $idFicha): array
    {
        $sql = "SELECT sf.*, u.username AS usuario_registro,
                       CONCAT(COALESCE(pu.nombres,''), ' ', COALESCE(pu.apellidos,'')) AS responsable
                FROM seguimientos_fisicos sf
                LEFT JOIN usuarios u ON u.id = sf.id_usuario
                LEFT JOIN personas pu ON pu.id = u.id_persona
                WHERE sf.id_aprendiz = ? AND sf.id_ficha = ?
                ORDER BY sf.fecha_toma DESC, sf.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idAprendiz, $idFicha]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear(array $datos): int
    {
        $sql = "INSERT INTO seguimientos_fisicos
                (id_aprendiz, id_ficha, id_usuario, fecha_toma, peso, talla, imc,
                 presion_sistolica, presion_diastolica, frecuencia_cardiaca,
                 frecuencia_respiratoria, temperatura, saturacion_oxigeno,
                 observaciones, proxima_fecha)
                VALUES
                (:id_aprendiz, :id_ficha, :id_usuario, :fecha_toma, :peso, :talla, :imc,
                 :presion_sistolica, :presion_diastolica, :frecuencia_cardiaca,
                 :frecuencia_respiratoria, :temperatura, :saturacion_oxigeno,
                 :observaciones, :proxima_fecha)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($datos);
        return (int) $this->db->lastInsertId();
    }
}
