<?php

declare(strict_types=1);

class ProgramaModel
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
     * Cuenta los programas según el criterio de búsqueda.
     */
    public function contar(
        string $buscar = ''
    ): int {
        $sql = "
            SELECT COUNT(*)
            FROM programas
            WHERE 1 = 1
        ";

        $parametros = [];

        if ($buscar !== '') {
            $sql .= "
                AND (
                    codigo LIKE :buscar
                    OR nombre LIKE :buscar
                    OR nivel LIKE :buscar
                )
            ";

            $parametros[':buscar'] =
                '%' . $buscar . '%';
        }

        $stmt = $this->db->prepare($sql);

        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Lista los programas con búsqueda y paginación.
     */
    public function listar(
        string $buscar = '',
        int $limite = 20,
        int $offset = 0
    ): array {
        $sql = "
            SELECT
                id,
                codigo,
                nombre,
                nivel,
                duracion_meses,
                estado,
                created_at
            FROM programas
            WHERE 1 = 1
        ";

        $parametros = [];

        if ($buscar !== '') {
            $sql .= "
                AND (
                    codigo LIKE :buscar
                    OR nombre LIKE :buscar
                    OR nivel LIKE :buscar
                )
            ";

            $parametros[':buscar'] =
                '%' . $buscar . '%';
        }

        $sql .= "
            ORDER BY
                CASE
                    WHEN estado = 'activo' THEN 0
                    ELSE 1
                END,
                codigo ASC,
                nombre ASC
            LIMIT :limite
            OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);

        foreach ($parametros as $nombre => $valor) {
            $stmt->bindValue(
                $nombre,
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

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /**
     * Obtiene un programa por ID.
     */
    public function obtener(
        int $id
    ): ?array {
        $sql = "
            SELECT
                id,
                codigo,
                nombre,
                nivel,
                duracion_meses,
                estado,
                created_at
            FROM programas
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
        ]);

        $programa =
            $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$programa) {
            return null;
        }

        return $programa;
    }

    /**
     * Verifica si existe un código de programa.
     *
     * Permite excluir un ID cuando se está editando.
     */
    public function existeCodigo(
        string $codigo,
        ?int $idExcluir = null
    ): bool {
        $sql = "
            SELECT COUNT(*)
            FROM programas
            WHERE codigo = :codigo
        ";

        $parametros = [
            ':codigo' => $codigo,
        ];

        if (
            $idExcluir !== null
            && $idExcluir > 0
        ) {
            $sql .= "
                AND id <> :id_excluir
            ";

            $parametros[':id_excluir'] =
                $idExcluir;
        }

        $stmt = $this->db->prepare($sql);

        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Crea un programa.
     */
    public function crear(
        array $datos
    ): int {
        $sql = "
            INSERT INTO programas
            (
                codigo,
                nombre,
                nivel,
                duracion_meses,
                estado
            )
            VALUES
            (
                :codigo,
                :nombre,
                :nivel,
                :duracion_meses,
                :estado
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':codigo' =>
                $datos['codigo'],
            ':nombre' =>
                $datos['nombre'],
            ':nivel' =>
                $datos['nivel'],
            ':duracion_meses' =>
                $datos['duracion_meses'],
            ':estado' =>
                $datos['estado'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza la información de un programa.
     *
     * El estado se maneja mediante cambiarEstado().
     */
    public function actualizar(
        int $id,
        array $datos
    ): bool {
        $sql = "
            UPDATE programas
            SET
                codigo = :codigo,
                nombre = :nombre,
                nivel = :nivel,
                duracion_meses = :duracion_meses
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':codigo' =>
                $datos['codigo'],
            ':nombre' =>
                $datos['nombre'],
            ':nivel' =>
                $datos['nivel'],
            ':duracion_meses' =>
                $datos['duracion_meses'],
            ':id' => $id,
        ]);
    }

    /**
     * Cambia el estado del programa.
     */
    public function cambiarEstado(
        int $id,
        string $estado
    ): bool {
        $estadosPermitidos = [
            'activo',
            'inactivo',
        ];

        if (
            !in_array(
                $estado,
                $estadosPermitidos,
                true
            )
        ) {
            return false;
        }

        $sql = "
            UPDATE programas
            SET estado = :estado
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':estado' => $estado,
            ':id' => $id,
        ]);

        return $stmt->rowCount() > 0;
    }
}
