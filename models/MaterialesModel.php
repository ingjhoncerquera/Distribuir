<?php

declare(strict_types=1);

require_once ROOT_PATH . '/config/Database.php';

class MaterialesModel
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
     * Cuenta los materiales del catálogo.
     *
     * La búsqueda se realiza sobre código UNSPSC y descripción.
     */
    public function contar(string $buscar = ''): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM materiales_catalogo
            WHERE 1 = 1
        ";

        $parametros = [];

        $buscar = trim($buscar);

        if ($buscar !== '') {
            $sql .= "
                AND (
                    codigo_unspsc LIKE :buscar_codigo
                    OR descripcion LIKE :buscar_descripcion
                )
            ";

            $valorBuscar = '%' . $buscar . '%';

            $parametros[':buscar_codigo'] = $valorBuscar;
            $parametros[':buscar_descripcion'] = $valorBuscar;
        }

        $stmt = $this->db->prepare($sql);

        foreach ($parametros as $parametro => $valor) {
            $stmt->bindValue(
                $parametro,
                $valor,
                PDO::PARAM_STR
            );
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Lista materiales con búsqueda y paginación.
     */
    public function listar(
        string $buscar = '',
        int $limite = 20,
        int $offset = 0
    ): array {
        $limite = max(1, $limite);
        $offset = max(0, $offset);

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
            WHERE 1 = 1
        ";

        $parametros = [];

        $buscar = trim($buscar);

        if ($buscar !== '') {
            $sql .= "
                AND (
                    codigo_unspsc LIKE :buscar_codigo
                    OR descripcion LIKE :buscar_descripcion
                )
            ";

            $valorBuscar = '%' . $buscar . '%';

            $parametros[':buscar_codigo'] = $valorBuscar;
            $parametros[':buscar_descripcion'] = $valorBuscar;
        }

        $sql .= "
            ORDER BY
                CASE
                    WHEN estado = 'activo' THEN 0
                    ELSE 1
                END,
                codigo_unspsc ASC,
                id DESC
            LIMIT :limite
            OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);

        foreach ($parametros as $parametro => $valor) {
            $stmt->bindValue(
                $parametro,
                $valor,
                PDO::PARAM_STR
            );
        }

        $stmt->bindValue(
            ':limite',
            $limite,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':offset',
            $offset,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un material por ID.
     */
    public function obtener(int $id): ?array
    {
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
            WHERE id = :id
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

    /**
     * Verifica si existe un código UNSPSC.
     *
     * El parámetro $idExcluir permite utilizar esta función
     * durante la edición sin que el propio registro sea considerado
     * duplicado.
     */
    public function existeCodigo(
        string $codigo,
        ?int $idExcluir = null
    ): bool {
        $sql = "
            SELECT COUNT(*)
            FROM materiales_catalogo
            WHERE codigo_unspsc = :codigo
        ";

        $parametros = [
            ':codigo' => $codigo
        ];

        if ($idExcluir !== null) {
            $sql .= "
                AND id <> :id_excluir
            ";

            $parametros[':id_excluir'] = $idExcluir;
        }

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':codigo',
            $codigo,
            PDO::PARAM_STR
        );

        if ($idExcluir !== null) {
            $stmt->bindValue(
                ':id_excluir',
                $idExcluir,
                PDO::PARAM_INT
            );
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Crea un nuevo material en el catálogo.
     */
    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO materiales_catalogo (
                codigo_unspsc,
                descripcion,
                estado,
                fecha_inicio_vigencia,
                fecha_fin_vigencia
            ) VALUES (
                :codigo_unspsc,
                :descripcion,
                :estado,
                :fecha_inicio_vigencia,
                :fecha_fin_vigencia
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':codigo_unspsc',
            $datos['codigo_unspsc'],
            PDO::PARAM_STR
        );

        $stmt->bindValue(
            ':descripcion',
            $datos['descripcion'],
            PDO::PARAM_STR
        );


        $stmt->bindValue(
            ':estado',
            $datos['estado'],
            PDO::PARAM_STR
        );

        if (
            isset($datos['fecha_inicio_vigencia'])
            && $datos['fecha_inicio_vigencia'] !== ''
        ) {
            $stmt->bindValue(
                ':fecha_inicio_vigencia',
                $datos['fecha_inicio_vigencia'],
                PDO::PARAM_STR
            );
        } else {
            $stmt->bindValue(
                ':fecha_inicio_vigencia',
                null,
                PDO::PARAM_NULL
            );
        }

        if (
            isset($datos['fecha_fin_vigencia'])
            && $datos['fecha_fin_vigencia'] !== ''
        ) {
            $stmt->bindValue(
                ':fecha_fin_vigencia',
                $datos['fecha_fin_vigencia'],
                PDO::PARAM_STR
            );
        } else {
            $stmt->bindValue(
                ':fecha_fin_vigencia',
                null,
                PDO::PARAM_NULL
            );
        }

        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza un material existente.
     */
    public function actualizar(
        int $id,
        array $datos
    ): bool {
        $sql = "
            UPDATE materiales_catalogo
            SET
                codigo_unspsc = :codigo_unspsc,
                descripcion = :descripcion,
                estado = :estado,
                fecha_inicio_vigencia = :fecha_inicio_vigencia,
                fecha_fin_vigencia = :fecha_fin_vigencia
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':codigo_unspsc',
            $datos['codigo_unspsc'],
            PDO::PARAM_STR
        );

        $stmt->bindValue(
            ':descripcion',
            $datos['descripcion'],
            PDO::PARAM_STR
        );


        $stmt->bindValue(
            ':estado',
            $datos['estado'],
            PDO::PARAM_STR
        );

        if (
            isset($datos['fecha_inicio_vigencia'])
            && $datos['fecha_inicio_vigencia'] !== ''
        ) {
            $stmt->bindValue(
                ':fecha_inicio_vigencia',
                $datos['fecha_inicio_vigencia'],
                PDO::PARAM_STR
            );
        } else {
            $stmt->bindValue(
                ':fecha_inicio_vigencia',
                null,
                PDO::PARAM_NULL
            );
        }

        if (
            isset($datos['fecha_fin_vigencia'])
            && $datos['fecha_fin_vigencia'] !== ''
        ) {
            $stmt->bindValue(
                ':fecha_fin_vigencia',
                $datos['fecha_fin_vigencia'],
                PDO::PARAM_STR
            );
        } else {
            $stmt->bindValue(
                ':fecha_fin_vigencia',
                null,
                PDO::PARAM_NULL
            );
        }

        $stmt->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Cambia el estado del material.
     */
    public function cambiarEstado(
        int $id,
        string $estado
    ): bool {
        $sql = "
            UPDATE materiales_catalogo
            SET
                estado = :estado
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':estado',
            $estado,
            PDO::PARAM_STR
        );

        $stmt->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}