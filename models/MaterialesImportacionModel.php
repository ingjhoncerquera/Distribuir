<?php

declare(strict_types=1);

require_once ROOT_PATH . '/config/Database.php';

class MaterialesImportacionModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? (new Database())->conectar();
    }

    // ============================================================
    // CONEXIÓN
    // ============================================================

    public function getConnection(): PDO
    {
        return $this->db;
    }

    // ============================================================
    // IMPORTACIONES
    // ============================================================

    public function crearImportacion(
        string $nombreArchivo,
        ?int $anioReferencia,
        int $totalFilas,
        ?int $idUsuario,
        int $hojasProcesadas = 0
    ): int {
        $sql = "
            INSERT INTO materiales_importaciones (
                nombre_archivo,
                anio_referencia,
                total_filas,
                hojas_procesadas,
                id_usuario,
                estado
            ) VALUES (
                :nombre_archivo,
                :anio_referencia,
                :total_filas,
                :hojas_procesadas,
                :id_usuario,
                'lectura'
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':nombre_archivo',
            $nombreArchivo,
            PDO::PARAM_STR
        );

        if ($anioReferencia === null) {
            $stmt->bindValue(
                ':anio_referencia',
                null,
                PDO::PARAM_NULL
            );
        } else {
            $stmt->bindValue(
                ':anio_referencia',
                $anioReferencia,
                PDO::PARAM_INT
            );
        }

        $stmt->bindValue(
            ':total_filas',
            $totalFilas,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':hojas_procesadas',
            max(0, $hojasProcesadas),
            PDO::PARAM_INT
        );

        if ($idUsuario === null) {
            $stmt->bindValue(
                ':id_usuario',
                null,
                PDO::PARAM_NULL
            );
        } else {
            $stmt->bindValue(
                ':id_usuario',
                $idUsuario,
                PDO::PARAM_INT
            );
        }

        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function obtenerImportacion(int $id): ?array
    {
        $sql = "
            SELECT
                mi.id,
                mi.nombre_archivo,
                mi.anio_referencia,
                mi.total_filas,
                mi.hojas_procesadas,
                mi.registros_nuevos,
                mi.registros_modificados,
                mi.registros_sin_cambios,
                mi.registros_inactivados,
                mi.registros_con_error,
                mi.estado,
                mi.observaciones,
                mi.id_usuario,
                mi.created_at,
                mi.applied_at,
                CONCAT_WS(
                    ' ',
                    p.nombres,
                    p.apellidos
                ) AS usuario_nombre,
                u.username AS usuario_username
            FROM materiales_importaciones mi
            LEFT JOIN usuarios u
                ON u.id = mi.id_usuario
            LEFT JOIN personas p
                ON p.id = u.id_persona
            WHERE mi.id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado !== false
            ? $resultado
            : null;
    }

    public function listarImportaciones(
        int $limite = 20,
        int $offset = 0
    ): array {
        $limite = max(1, $limite);
        $offset = max(0, $offset);

        $sql = "
            SELECT
                mi.id,
                mi.nombre_archivo,
                mi.anio_referencia,
                mi.total_filas,
                mi.hojas_procesadas,
                mi.registros_nuevos,
                mi.registros_modificados,
                mi.registros_sin_cambios,
                mi.registros_inactivados,
                mi.registros_con_error,
                mi.estado,
                mi.observaciones,
                mi.id_usuario,
                mi.created_at,
                mi.applied_at,
                CONCAT_WS(
                    ' ',
                    p.nombres,
                    p.apellidos
                ) AS usuario_nombre,
                u.username AS usuario_username
            FROM materiales_importaciones mi
            LEFT JOIN usuarios u
                ON u.id = mi.id_usuario
            LEFT JOIN personas p
                ON p.id = u.id_persona
            ORDER BY mi.id DESC
            LIMIT {$limite} OFFSET {$offset}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarImportaciones(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM materiales_importaciones
        ";

        $stmt = $this->db->query($sql);

        return (int) $stmt->fetchColumn();
    }

    // ============================================================
    // DETALLES
    // ============================================================

    public function crearDetalle(
        int $idImportacion,
        int $filaExcel,
        ?string $codigoUnspsc,
        ?string $descripcion,
        ?string $unidadMedida,
        string $accion,
        ?string $mensaje = null,
        ?int $idMaterial = null
    ): int {
        $sql = "
            INSERT INTO materiales_importacion_detalle (
                id_importacion,
                fila_excel,
                codigo_unspsc,
                descripcion,
                unidad_medida,
                accion,
                mensaje,
                id_material
            ) VALUES (
                :id_importacion,
                :fila_excel,
                :codigo_unspsc,
                :descripcion,
                :unidad_medida,
                :accion,
                :mensaje,
                :id_material
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':id_importacion',
            $idImportacion,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':fila_excel',
            $filaExcel,
            PDO::PARAM_INT
        );

        if ($codigoUnspsc === null) {
            $stmt->bindValue(
                ':codigo_unspsc',
                null,
                PDO::PARAM_NULL
            );
        } else {
            $stmt->bindValue(
                ':codigo_unspsc',
                $codigoUnspsc,
                PDO::PARAM_STR
            );
        }

        if ($descripcion === null) {
            $stmt->bindValue(
                ':descripcion',
                null,
                PDO::PARAM_NULL
            );
        } else {
            $stmt->bindValue(
                ':descripcion',
                $descripcion,
                PDO::PARAM_STR
            );
        }

        if ($unidadMedida === null) {
            $stmt->bindValue(
                ':unidad_medida',
                null,
                PDO::PARAM_NULL
            );
        } else {
            $stmt->bindValue(
                ':unidad_medida',
                $unidadMedida,
                PDO::PARAM_STR
            );
        }

        $stmt->bindValue(
            ':accion',
            $accion,
            PDO::PARAM_STR
        );

        if ($mensaje === null) {
            $stmt->bindValue(
                ':mensaje',
                null,
                PDO::PARAM_NULL
            );
        } else {
            $stmt->bindValue(
                ':mensaje',
                $mensaje,
                PDO::PARAM_STR
            );
        }

        if ($idMaterial === null) {
            $stmt->bindValue(
                ':id_material',
                null,
                PDO::PARAM_NULL
            );
        } else {
            $stmt->bindValue(
                ':id_material',
                $idMaterial,
                PDO::PARAM_INT
            );
        }

        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function listarDetalles(
        int $idImportacion
    ): array {
        $sql = "
            SELECT
                mid.id,
                mid.id_importacion,
                mid.fila_excel,
                mid.codigo_unspsc,
                mid.descripcion,
                mid.unidad_medida,
                mid.accion,
                mid.mensaje,
                mid.id_material,
                mc.estado AS estado_material
            FROM materiales_importacion_detalle mid
            LEFT JOIN materiales_catalogo mc
                ON mc.id = mid.id_material
            WHERE mid.id_importacion = :id_importacion
            ORDER BY
                mid.fila_excel ASC,
                mid.id ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':id_importacion',
            $idImportacion,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarDetalles(
        int $idImportacion
    ): int {
        $sql = "
            SELECT COUNT(*)
            FROM materiales_importacion_detalle
            WHERE id_importacion = :id_importacion
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':id_importacion',
            $idImportacion,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    // ============================================================
    // CATÁLOGO
    // ============================================================

    /**
     * Busca el primer material por código UNSPSC.
     *
     * Se conserva este método porque puede ser utilizado
     * por otras funciones del sistema.
     *
     * IMPORTANTE:
     * El código UNSPSC NO es único en el catálogo.
     */
    public function obtenerMaterialPorCodigo(
        string $codigoUnspsc
    ): ?array {
        $sql = "
            SELECT
                id,
                codigo_unspsc,
                descripcion,
                estado,
                fecha_inicio_vigencia,
                fecha_fin_vigencia,
                created_at,
                updated_at
            FROM materiales_catalogo
            WHERE codigo_unspsc = :codigo_unspsc
            ORDER BY id ASC
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':codigo_unspsc',
            $codigoUnspsc,
            PDO::PARAM_STR
        );

        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado !== false
            ? $resultado
            : null;
    }

    /**
     * Carga el catálogo en memoria para evitar una consulta SQL por cada
     * fila de Excel. Es importante para archivos de decenas de miles de filas.
     * La clave utiliza código UNSPSC + descripción normalizada.
     */
    public function obtenerIndiceMaterialesCatalogo(): array
    {
        $sql = "
            SELECT id, codigo_unspsc, descripcion, estado,
                   fecha_inicio_vigencia, fecha_fin_vigencia,
                   created_at, updated_at
            FROM materiales_catalogo
            ORDER BY id ASC
        ";

        $stmt = $this->db->query($sql);
        $indice = [];

        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $codigo = trim((string) ($fila['codigo_unspsc'] ?? ''));
            $descripcion = preg_replace(
                '/\s+/u',
                ' ',
                trim((string) ($fila['descripcion'] ?? ''))
            ) ?? trim((string) ($fila['descripcion'] ?? ''));
            $clave = mb_strtolower($codigo, 'UTF-8') . '|' . mb_strtolower($descripcion, 'UTF-8');

            if (!isset($indice[$clave])) {
                $indice[$clave] = $fila;
            }
        }

        return $indice;
    }

    /**
     * Busca un material por la combinación completa:
     *
     * codigo_unspsc + descripcion
     *
     * Esta es la búsqueda correcta para la importación.
     */
    public function obtenerMaterialPorDatosCompletos(
        string $codigoUnspsc,
        string $descripcion
    ): ?array {
        $sql = "
            SELECT
                id, codigo_unspsc, descripcion, estado,
                fecha_inicio_vigencia, fecha_fin_vigencia,
                created_at, updated_at
            FROM materiales_catalogo
            WHERE codigo_unspsc = :codigo_unspsc
              AND TRIM(descripcion) = TRIM(:descripcion)
            ORDER BY id ASC
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':codigo_unspsc' => $codigoUnspsc,
            ':descripcion' => $descripcion,
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado !== false ? $resultado : null;
    }

    /**
     * Cuenta todos los materiales que utilizan un UNSPSC.
     *
     * Se conserva porque un mismo UNSPSC puede pertenecer
     * a varios materiales.
     */
    public function contarMaterialesPorCodigo(
        string $codigoUnspsc
    ): int {
        $sql = "
            SELECT COUNT(*)
            FROM materiales_catalogo
            WHERE codigo_unspsc = :codigo_unspsc
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':codigo_unspsc',
            $codigoUnspsc,
            PDO::PARAM_STR
        );

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Cuenta coincidencias exactas del material.
     */
    public function contarMaterialesPorDatosCompletos(
        string $codigoUnspsc,
        string $descripcion
    ): int {
        $sql = "
            SELECT COUNT(*)
            FROM materiales_catalogo
            WHERE codigo_unspsc = :codigo_unspsc
              AND TRIM(descripcion) = TRIM(:descripcion)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':codigo_unspsc' => $codigoUnspsc,
            ':descripcion' => $descripcion,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function crearMaterialDesdeImportacion(
        string $codigoUnspsc,
        string $descripcion
    ): int {
        $sql = "
            INSERT INTO materiales_catalogo (
                codigo_unspsc, descripcion, estado,
                fecha_inicio_vigencia, fecha_fin_vigencia
            ) VALUES (
                :codigo_unspsc, :descripcion, 'activo', NULL, NULL
            )
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':codigo_unspsc' => $codigoUnspsc,
            ':descripcion' => $descripcion,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizarMaterialDesdeImportacion(
        int $idMaterial,
        string $descripcion
    ): bool {
        $sql = "
            UPDATE materiales_catalogo
            SET descripcion = :descripcion
            WHERE id = :id
        ";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':descripcion' => $descripcion,
            ':id' => $idMaterial,
        ]);
    }

    // ============================================================
    // RESUMEN DE IMPORTACIÓN
    // ============================================================

    public function actualizarResumen(
        int $idImportacion,
        int $nuevos,
        int $modificados,
        int $sinCambios,
        int $inactivados,
        int $errores
    ): bool {
        $sql = "
            UPDATE materiales_importaciones
            SET
                registros_nuevos = :nuevos,
                registros_modificados = :modificados,
                registros_sin_cambios = :sin_cambios,
                registros_inactivados = :inactivados,
                registros_con_error = :errores
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':nuevos',
            $nuevos,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':modificados',
            $modificados,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':sin_cambios',
            $sinCambios,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':inactivados',
            $inactivados,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':errores',
            $errores,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':id',
            $idImportacion,
            PDO::PARAM_INT
        );

        return $stmt->execute();
    }

    // ============================================================
    // ESTADO DE IMPORTACIÓN
    // ============================================================

    public function cambiarEstado(
        int $idImportacion,
        string $estado,
        ?string $observaciones = null
    ): bool {
        $estadosPermitidos = [
            'lectura',
            'validada',
            'aplicada',
            'cancelada',
            'error'
        ];

        if (!in_array($estado, $estadosPermitidos, true)) {
            throw new InvalidArgumentException(
                'Estado de importación no válido.'
            );
        }

        $appliedAt = $estado === 'aplicada'
            ? date('Y-m-d H:i:s')
            : null;

        $sql = "
            UPDATE materiales_importaciones
            SET
                estado = :estado,
                observaciones = :observaciones,
                applied_at = :applied_at
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':estado',
            $estado,
            PDO::PARAM_STR
        );

        if ($observaciones === null) {
            $stmt->bindValue(
                ':observaciones',
                null,
                PDO::PARAM_NULL
            );
        } else {
            $stmt->bindValue(
                ':observaciones',
                $observaciones,
                PDO::PARAM_STR
            );
        }

        if ($appliedAt === null) {
            $stmt->bindValue(
                ':applied_at',
                null,
                PDO::PARAM_NULL
            );
        } else {
            $stmt->bindValue(
                ':applied_at',
                $appliedAt,
                PDO::PARAM_STR
            );
        }

        $stmt->bindValue(
            ':id',
            $idImportacion,
            PDO::PARAM_INT
        );

        return $stmt->execute();
    }
}