<?php

declare(strict_types=1);

class RolModel
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
     * Lista los roles activos junto con la cantidad de permisos asignados.
     */
    public function listarActivos(): array
    {
        $sql = "
            SELECT
                r.id,
                r.nombre,
                r.descripcion,
                r.estado,
                COUNT(rp.id_permiso) AS total_permisos
            FROM roles r
            LEFT JOIN rol_permisos rp
                ON rp.id_rol = r.id
            WHERE r.estado = 'activo'
            GROUP BY
                r.id,
                r.nombre,
                r.descripcion,
                r.estado
            ORDER BY r.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un rol por su ID.
     */
    public function obtener(int $id): ?array
    {
        $sql = "
            SELECT
                r.id,
                r.nombre,
                r.descripcion,
                r.estado
            FROM roles r
            WHERE r.id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
        ]);

        $rol = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rol) {
            return null;
        }

        return $rol;
    }

    /**
     * Verifica si ya existe un rol con el nombre indicado.
     */
    public function existeNombre(
        string $nombre,
        ?int $idExcluir = null
    ): bool {
        $sql = "
            SELECT COUNT(*)
            FROM roles
            WHERE LOWER(nombre) = LOWER(:nombre)
        ";

        $parametros = [
            ':nombre' => $nombre,
        ];

        if ($idExcluir !== null) {
            $sql .= "
                AND id <> :id_excluir
            ";

            $parametros[':id_excluir'] = $idExcluir;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Crea un nuevo rol.
     *
     * Retorna el ID creado o null si la operación falla.
     */
    public function crear(
        string $nombre,
        ?string $descripcion,
        string $estado = 'activo'
    ): ?int {
        $sql = "
            INSERT INTO roles (
                nombre,
                descripcion,
                estado
            )
            VALUES (
                :nombre,
                :descripcion,
                :estado
            )
        ";

        try {

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                ':nombre' => $nombre,
                ':descripcion' => $descripcion,
                ':estado' => $estado,
            ]);

            return (int) $this->db->lastInsertId();

        } catch (Throwable $e) {

            return null;
        }
    }

    /**
     * Lista todos los permisos existentes.
     *
     * Los permisos son administrados desde la base de datos.
     * Desde la interfaz únicamente se asignan o desasignan
     * permisos existentes.
     */
    public function listarPermisos(): array
    {
        $sql = "
            SELECT
                p.id,
                p.nombre,
                p.modulo,
                p.descripcion
            FROM permisos p
            ORDER BY
                p.modulo ASC,
                p.nombre ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los permisos asignados a un rol.
     */
    public function obtenerPermisos(int $idRol): array
    {
        $sql = "
            SELECT
                p.id,
                p.nombre,
                p.modulo,
                p.descripcion
            FROM rol_permisos rp
            INNER JOIN permisos p
                ON p.id = rp.id_permiso
            WHERE rp.id_rol = :id_rol
            ORDER BY
                p.modulo ASC,
                p.nombre ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_rol' => $idRol,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene únicamente los IDs de permisos asignados a un rol.
     */
    public function obtenerIdsPermisos(int $idRol): array
    {
        $sql = "
            SELECT
                id_permiso
            FROM rol_permisos
            WHERE id_rol = :id_rol
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_rol' => $idRol,
        ]);

        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            static function (array $fila): int {
                return (int) $fila['id_permiso'];
            },
            $resultados
        );
    }

    /**
     * Guarda los permisos de un rol.
     *
     * La operación reemplaza la asignación actual del rol
     * por la selección enviada desde el formulario.
     */
    public function guardarPermisos(
        int $idRol,
        array $idsPermisos
    ): bool {
        $rol = $this->obtener($idRol);

        if (!$rol) {
            return false;
        }

        $idsPermisos = array_map(
            'intval',
            $idsPermisos
        );

        $idsPermisos = array_values(
            array_unique(
                array_filter(
                    $idsPermisos,
                    static function (int $id): bool {
                        return $id > 0;
                    }
                )
            )
        );

        $this->db->beginTransaction();

        try {

            /*
             * Validamos que todos los permisos recibidos
             * realmente existan en la tabla permisos.
             */
            if (!empty($idsPermisos)) {

                $placeholders = implode(
                    ',',
                    array_fill(
                        0,
                        count($idsPermisos),
                        '?'
                    )
                );

                $sqlValidar = "
                    SELECT COUNT(*)
                    FROM permisos
                    WHERE id IN ($placeholders)
                ";

                $stmtValidar = $this->db->prepare(
                    $sqlValidar
                );

                $stmtValidar->execute(
                    $idsPermisos
                );

                $totalValidos = (int) $stmtValidar->fetchColumn();

                if (
                    $totalValidos !==
                    count($idsPermisos)
                ) {
                    $this->db->rollBack();

                    return false;
                }
            }

            /*
             * Eliminamos las asignaciones actuales
             * del rol.
             */
            $sqlEliminar = "
                DELETE FROM rol_permisos
                WHERE id_rol = :id_rol
            ";

            $stmtEliminar = $this->db->prepare(
                $sqlEliminar
            );

            $stmtEliminar->execute([
                ':id_rol' => $idRol,
            ]);

            /*
             * Insertamos nuevamente los permisos
             * seleccionados.
             */
            if (!empty($idsPermisos)) {

                $sqlInsertar = "
                    INSERT INTO rol_permisos (
                        id_rol,
                        id_permiso
                    )
                    VALUES (
                        :id_rol,
                        :id_permiso
                    )
                ";

                $stmtInsertar = $this->db->prepare(
                    $sqlInsertar
                );

                foreach ($idsPermisos as $idPermiso) {

                    $stmtInsertar->execute([
                        ':id_rol' => $idRol,
                        ':id_permiso' => $idPermiso,
                    ]);
                }
            }

            $this->db->commit();

            return true;

        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return false;
        }
    }
}