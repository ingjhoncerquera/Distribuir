<?php
declare(strict_types=1);

/** SITA - Consulta estadística de ficha de caracterización. */
class FichaCaracterizacionModel
{
    private PDO $db;
    private ?string $columnaEstrato = null;
    private ?string $tablaEstrato = null;

    public function __construct()
    {
        $this->db = (new Database())->conectar();
        $this->detectarColumnaEstrato();
    }

    private function detectarColumnaEstrato(): void
    {
        // Se detecta únicamente entre columnas conocidas de tablas existentes.
        // Si no hay campo guardado, la consulta muestra “No registrado” sin fallar.
        foreach (['personas', 'aprendices'] as $tabla) {
            foreach (['estrato_social', 'estrato'] as $columna) {
                try {
                    $stmt = $this->db->prepare(
                        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
                    );
                    $stmt->execute([$tabla, $columna]);
                    if ((int)$stmt->fetchColumn() > 0) {
                        $this->tablaEstrato = $tabla;
                        $this->columnaEstrato = $columna;
                        return;
                    }
                } catch (Throwable $e) {
                    // No interrumpir la consulta si el usuario de BD no permite consultar metadatos.
                }
            }
        }
    }

    private function expresionEstrato(): string
    {
        if ($this->columnaEstrato === null || $this->tablaEstrato === null) {
            return "NULL AS estrato_social";
        }
        $alias = $this->tablaEstrato === 'personas' ? 'p' : 'a';
        $columna = str_replace('`', '', $this->columnaEstrato);
        return "{$alias}.`{$columna}` AS estrato_social";
    }

    private function joins(): string
    {
        return "
            FROM matriculas m
            INNER JOIN aprendices a ON a.id = m.id_aprendiz
            INNER JOIN personas p ON p.id = a.id_persona
            INNER JOIN fichas f ON f.id = m.id_ficha
            INNER JOIN programas pr ON pr.id = f.id_programa
            LEFT JOIN eps e ON e.id = a.id_eps
            LEFT JOIN aprendiz_poblacion ap ON ap.id_aprendiz = a.id AND ap.fecha_fin IS NULL
            LEFT JOIN tipos_poblacion tp ON tp.id = ap.id_tipo_poblacion
            LEFT JOIN actualizaciones_caracterizacion ac ON ac.id_aprendiz = a.id AND ac.id_ficha = m.id_ficha
        ";
    }

    public function listarFichas(): array
    {
        $sql = "SELECT DISTINCT f.id, f.numero_ficha, f.estado, pr.codigo AS codigo_programa, pr.nombre AS nombre_programa
                FROM fichas f INNER JOIN programas pr ON pr.id = f.id_programa
                ORDER BY f.numero_ficha DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function opcionesFiltro(string $campo): array
    {
        $permitidos = [
            'sexo' => 'p.sexo',
            'municipio' => 'p.municipio',
            'departamento' => 'p.departamento',
            'eps' => 'e.nombre',
            'poblacion' => 'tp.nombre',
        ];
        if (!isset($permitidos[$campo])) return [];
        $expresion = $permitidos[$campo];
        $sql = "SELECT DISTINCT {$expresion} AS valor " . $this->joins() .
               " WHERE {$expresion} IS NOT NULL AND TRIM(CAST({$expresion} AS CHAR)) <> '' ORDER BY valor ASC";
        try {
            return array_values(array_filter(array_map(
                static fn(array $r): string => trim((string)$r['valor']),
                $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC)
            ), static fn(string $v): bool => $v !== ''));
        } catch (Throwable $e) {
            return [];
        }
    }

    private function construirFiltros(array $filtros): array
    {
        $where = ' WHERE 1 = 1 ';
        $params = [];
        if (!empty($filtros['id_ficha'])) {
            $where .= ' AND m.id_ficha = ? ';
            $params[] = (int)$filtros['id_ficha'];
        }
        if (!empty($filtros['buscar'])) {
            $where .= " AND (p.documento LIKE ? OR p.nombres LIKE ? OR p.apellidos LIKE ? OR CONCAT(p.nombres, ' ', p.apellidos) LIKE ? OR f.numero_ficha LIKE ? OR pr.nombre LIKE ?) ";
            $like = '%' . trim((string)$filtros['buscar']) . '%';
            array_push($params, $like, $like, $like, $like, $like, $like);
        }
        if (!empty($filtros['estado_caracterizacion'])) {
            if ($filtros['estado_caracterizacion'] === 'actualizado') {
                $where .= " AND ac.estado = 'actualizado' ";
            } elseif ($filtros['estado_caracterizacion'] === 'pendiente') {
                $where .= " AND (ac.estado IS NULL OR ac.estado = 'pendiente') ";
            }
        }
        foreach (['sexo' => 'p.sexo', 'municipio' => 'p.municipio', 'departamento' => 'p.departamento', 'eps' => 'e.nombre', 'poblacion' => 'tp.nombre'] as $clave => $columna) {
            if (isset($filtros[$clave]) && trim((string)$filtros[$clave]) !== '') {
                $where .= " AND {$columna} = ? ";
                $params[] = trim((string)$filtros[$clave]);
            }
        }
        if (isset($filtros['estrato']) && trim((string)$filtros['estrato']) !== '' && $this->columnaEstrato !== null) {
            $alias = $this->tablaEstrato === 'personas' ? 'p' : 'a';
            $col = str_replace('`', '', $this->columnaEstrato);
            $where .= " AND CAST({$alias}.`{$col}` AS CHAR) = ? ";
            $params[] = trim((string)$filtros['estrato']);
        }
        return [$where, $params];
    }

    public function contarConsulta(array $filtros = []): int
    {
        [$where, $params] = $this->construirFiltros($filtros);
        $stmt = $this->db->prepare('SELECT COUNT(DISTINCT CONCAT(m.id_aprendiz, \'-\', m.id_ficha)) ' . $this->joins() . $where);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function listarConsulta(array $filtros = [], int $limite = 20, int $offset = 0): array
    {
        [$where, $params] = $this->construirFiltros($filtros);
        $sql = "SELECT a.id AS id_aprendiz, p.tipo_documento, p.documento, p.nombres, p.apellidos,
                       p.telefono, p.correo, p.direccion, p.sexo, p.municipio, p.departamento,
                       e.nombre AS nombre_eps, {$this->expresionEstrato()},
                       a.estado AS estado_aprendiz, m.id_ficha, f.numero_ficha, f.estado AS estado_ficha,
                       pr.codigo AS codigo_programa, pr.nombre AS nombre_programa, tp.nombre AS tipo_poblacion,
                       COALESCE(ac.estado, 'pendiente') AS estado_actualizacion, ac.fecha_actualizacion, ac.campos_actualizados
                " . $this->joins() . $where . "
                ORDER BY f.numero_ficha ASC, p.apellidos ASC, p.nombres ASC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $i = 1;
        foreach ($params as $valor) {
            $stmt->bindValue($i++, is_int($valor) ? $valor : (string)$valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue($i++, max(1, $limite), PDO::PARAM_INT);
        $stmt->bindValue($i, max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerEstadisticas(array $filtros = []): array
    {
        [$where, $params] = $this->construirFiltros($filtros);
        $campos = [
            'sexo' => "COALESCE(NULLIF(TRIM(p.sexo), ''), 'No registrado')",
            'municipio' => "COALESCE(NULLIF(TRIM(p.municipio), ''), 'No registrado')",
            'departamento' => "COALESCE(NULLIF(TRIM(p.departamento), ''), 'No registrado')",
            'eps' => "COALESCE(NULLIF(TRIM(e.nombre), ''), 'No registrada')",
            'poblacion' => "COALESCE(NULLIF(TRIM(tp.nombre), ''), 'Sin clasificación')",
            'estrato' => $this->columnaEstrato !== null
                ? "COALESCE(NULLIF(TRIM(CAST(" . ($this->tablaEstrato === 'personas' ? 'p' : 'a') . '.`' . $this->columnaEstrato . "` AS CHAR)), ''), 'No registrado')"
                : "'No disponible en la base de datos'",
        ];
        $resultado = [];
        foreach ($campos as $clave => $expresion) {
            try {
                $sql = "SELECT {$expresion} AS etiqueta, COUNT(DISTINCT CONCAT(m.id_aprendiz, '-', m.id_ficha)) AS cantidad " . $this->joins() . $where . " GROUP BY etiqueta ORDER BY cantidad DESC, etiqueta ASC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $resultado[$clave] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                $resultado[$clave] = [];
            }
        }
        try {
            $estadoExpr = "COALESCE(NULLIF(TRIM(ac.estado), ''), 'pendiente')";
            $sql = "SELECT {$estadoExpr} AS etiqueta, COUNT(DISTINCT CONCAT(m.id_aprendiz, '-', m.id_ficha)) AS cantidad " . $this->joins() . $where . " GROUP BY etiqueta ORDER BY cantidad DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $resultado['actualizacion'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $resultado['actualizacion'] = [];
        }
        $resultado['estrato_disponible'] = $this->columnaEstrato !== null;
        return $resultado;
    }
}
