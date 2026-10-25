<?php
declare(strict_types=1);

/**
 * SITA - Modelo de estadísticas del panel principal.
 * Archivo: models/DashboardModel.php
 */
class DashboardModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->conectar();
    }

    private function consultarUnaFila(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($fila) ? $fila : [];
    }

    private function consultarLista(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Indicadores principales del sistema.
     * Los conteos se calculan con los estados definidos en el SQL de SITA.
     */
    public function obtenerIndicadores(): array
    {
        $aprendices = $this->consultarUnaFila("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN estado = 'EN FORMACION' THEN 1 ELSE 0 END), 0) AS en_formacion,
                COALESCE(SUM(CASE WHEN estado = 'CANCELADO' THEN 1 ELSE 0 END), 0) AS cancelados,
                COALESCE(SUM(CASE WHEN estado = 'RETIRO VOLUNTARIO' THEN 1 ELSE 0 END), 0) AS retiros,
                COALESCE(SUM(CASE WHEN estado = 'APLAZADO' THEN 1 ELSE 0 END), 0) AS aplazados
            FROM aprendices
        ");

        $fichas = $this->consultarUnaFila("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN estado = 'activa' THEN 1 ELSE 0 END), 0) AS activas
            FROM fichas
        ");

        $alertas = $this->consultarUnaFila("
            SELECT COUNT(*) AS total
            FROM alertas_acompanamiento
            WHERE estado = 'vigente'
              AND fecha_inicio <= CURRENT_DATE
              AND (fecha_fin IS NULL OR fecha_fin >= CURRENT_DATE)
        ");

        $alertasPorVencer = $this->consultarUnaFila("
            SELECT COUNT(*) AS total
            FROM alertas_acompanamiento
            WHERE estado = 'vigente'
              AND fecha_inicio <= CURRENT_DATE
              AND fecha_fin IS NOT NULL
              AND fecha_fin BETWEEN CURRENT_DATE AND (CURRENT_DATE + INTERVAL 7 DAY)
        ");

        $resultados = $this->consultarUnaFila("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN juicio = 'aprobado' THEN 1 ELSE 0 END), 0) AS aprobados,
                COALESCE(SUM(CASE WHEN juicio = 'no_aprobado' THEN 1 ELSE 0 END), 0) AS no_aprobados,
                COALESCE(SUM(CASE WHEN juicio = 'pendiente' THEN 1 ELSE 0 END), 0) AS pendientes
            FROM resultados_aprendiz
        ");

        $caracterizacion = $this->consultarUnaFila("
            SELECT
                COALESCE(SUM(CASE WHEN estado = 'actualizado' THEN 1 ELSE 0 END), 0) AS actualizados,
                COALESCE(SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END), 0) AS pendientes
            FROM actualizaciones_caracterizacion
        ");

        return [
            'aprendices' => (int) ($aprendices['total'] ?? 0),
            'aprendices_en_formacion' => (int) ($aprendices['en_formacion'] ?? 0),
            'aprendices_cancelados' => (int) ($aprendices['cancelados'] ?? 0),
            'aprendices_retiro' => (int) ($aprendices['retiros'] ?? 0),
            'aprendices_aplazados' => (int) ($aprendices['aplazados'] ?? 0),
            'fichas' => (int) ($fichas['total'] ?? 0),
            'fichas_activas' => (int) ($fichas['activas'] ?? 0),
            'alertas_activas' => (int) ($alertas['total'] ?? 0),
            'alertas_por_vencer' => (int) ($alertasPorVencer['total'] ?? 0),
            'resultados_total' => (int) ($resultados['total'] ?? 0),
            'resultados_aprobados' => (int) ($resultados['aprobados'] ?? 0),
            'resultados_no_aprobados' => (int) ($resultados['no_aprobados'] ?? 0),
            'resultados_pendientes' => (int) ($resultados['pendientes'] ?? 0),
            'caracterizaciones_actualizadas' => (int) ($caracterizacion['actualizados'] ?? 0),
            'caracterizaciones_pendientes' => (int) ($caracterizacion['pendientes'] ?? 0),
        ];
    }

    public function obtenerEstadosAprendices(): array
    {
        return $this->consultarLista("
            SELECT estado AS etiqueta, COUNT(*) AS total
            FROM aprendices
            GROUP BY estado
            ORDER BY total DESC, etiqueta ASC
        ");
    }

    public function obtenerDistribucionSexo(): array
    {
        return $this->consultarLista("
            SELECT
                CASE
                    WHEN sexo IS NULL OR TRIM(sexo) = '' THEN 'Sin registrar'
                    ELSE TRIM(sexo)
                END AS etiqueta,
                COUNT(*) AS total
            FROM aprendices a
            INNER JOIN personas p ON p.id = a.id_persona
            GROUP BY CASE
                WHEN sexo IS NULL OR TRIM(sexo) = '' THEN 'Sin registrar'
                ELSE TRIM(sexo)
            END
            ORDER BY total DESC, etiqueta ASC
        ");
    }

    public function obtenerAprendicesPorMunicipio(): array
    {
        return $this->consultarLista("
            SELECT
                CASE
                    WHEN p.municipio IS NULL OR TRIM(p.municipio) = '' THEN 'Sin registrar'
                    ELSE TRIM(p.municipio)
                END AS etiqueta,
                COUNT(*) AS total
            FROM aprendices a
            INNER JOIN personas p ON p.id = a.id_persona
            GROUP BY CASE
                WHEN p.municipio IS NULL OR TRIM(p.municipio) = '' THEN 'Sin registrar'
                ELSE TRIM(p.municipio)
            END
            ORDER BY total DESC, etiqueta ASC
            LIMIT 8
        ");
    }

    public function obtenerEstadosResultados(): array
    {
        return $this->consultarLista("
            SELECT
                CASE juicio
                    WHEN 'aprobado' THEN 'Aprobado'
                    WHEN 'no_aprobado' THEN 'No aprobado'
                    WHEN 'pendiente' THEN 'Pendiente'
                    ELSE 'Sin clasificar'
                END AS etiqueta,
                COUNT(*) AS total
            FROM resultados_aprendiz
            GROUP BY juicio
            ORDER BY total DESC
        ");
    }

    public function obtenerAlertasPorTipo(): array
    {
        return $this->consultarLista("
            SELECT
                CASE tipo_alerta
                    WHEN 'academica' THEN 'Académica'
                    WHEN 'salud_fisica' THEN 'Salud física'
                    WHEN 'salud_mental' THEN 'Salud mental'
                    WHEN 'nutricional' THEN 'Nutricional'
                    WHEN 'salud_sexual_reproductiva' THEN 'Salud sexual y reproductiva'
                    WHEN 'actividad_fisica' THEN 'Actividad física'
                    WHEN 'apoyo_pedagogico' THEN 'Apoyo pedagógico'
                    ELSE 'Otra'
                END AS etiqueta,
                COUNT(*) AS total
            FROM alertas_acompanamiento
            WHERE estado = 'vigente'
              AND fecha_inicio <= CURRENT_DATE
              AND (fecha_fin IS NULL OR fecha_fin >= CURRENT_DATE)
            GROUP BY tipo_alerta
            ORDER BY total DESC, etiqueta ASC
        ");
    }

    public function obtenerCalidadDatos(): array
    {
        return $this->consultarUnaFila("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN p.telefono IS NULL OR TRIM(p.telefono) = '' THEN 1 ELSE 0 END), 0) AS sin_telefono,
                COALESCE(SUM(CASE WHEN p.correo IS NULL OR TRIM(p.correo) = '' THEN 1 ELSE 0 END), 0) AS sin_correo,
                COALESCE(SUM(CASE WHEN p.direccion IS NULL OR TRIM(p.direccion) = '' THEN 1 ELSE 0 END), 0) AS sin_direccion,
                COALESCE(SUM(CASE WHEN p.municipio IS NULL OR TRIM(p.municipio) = '' THEN 1 ELSE 0 END), 0) AS sin_municipio,
                COALESCE(SUM(CASE WHEN p.departamento IS NULL OR TRIM(p.departamento) = '' THEN 1 ELSE 0 END), 0) AS sin_departamento,
                COALESCE(SUM(CASE WHEN a.id_eps IS NULL THEN 1 ELSE 0 END), 0) AS sin_eps
            FROM aprendices a
            INNER JOIN personas p ON p.id = a.id_persona
        ");
    }

    public function obtenerDistribucionPoblacion(): array
    {
        return $this->consultarLista("
            SELECT tp.nombre AS etiqueta, COUNT(DISTINCT ap.id_aprendiz) AS total
            FROM aprendiz_poblacion ap
            INNER JOIN tipos_poblacion tp ON tp.id = ap.id_tipo_poblacion
            WHERE ap.fecha_inicio <= CURRENT_DATE
              AND (ap.fecha_fin IS NULL OR ap.fecha_fin >= CURRENT_DATE)
              AND tp.estado = 'activo'
            GROUP BY tp.id, tp.nombre
            ORDER BY total DESC, etiqueta ASC
            LIMIT 8
        ");
    }

    /**
     * Resumen de los cuestionarios de Bienestar (caracterización por componentes).
     */
    public function obtenerResumenBienestar(): array
    {
        return $this->consultarUnaFila("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END), 0) AS completados,
                COALESCE(SUM(CASE WHEN estado = 'borrador' THEN 1 ELSE 0 END), 0) AS borradores,
                COUNT(DISTINCT CASE WHEN estado = 'completado' THEN id_aprendiz END) AS aprendices_completados,
                COALESCE(SUM(CASE WHEN fecha_actualizacion >= (CURRENT_DATE - INTERVAL 30 DAY) THEN 1 ELSE 0 END), 0) AS actualizados_30_dias
            FROM cuestionarios_bienestar
        ");
    }

    /**
     * Cuestionarios completados por mes (últimos 6 meses, incluido el actual).
     * Los meses sin registros se completan en la vista.
     */
    public function obtenerEvolucionCuestionarios(): array
    {
        return $this->consultarLista("
            SELECT
                DATE_FORMAT(fecha_completado, '%Y-%m') AS periodo,
                COUNT(*) AS total
            FROM cuestionarios_bienestar
            WHERE estado = 'completado'
              AND fecha_completado IS NOT NULL
              AND fecha_completado >= DATE_FORMAT(CURRENT_DATE - INTERVAL 5 MONTH, '%Y-%m-01')
            GROUP BY DATE_FORMAT(fecha_completado, '%Y-%m')
            ORDER BY periodo ASC
        ");
    }
}