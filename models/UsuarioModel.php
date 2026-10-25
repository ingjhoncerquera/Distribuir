<?php

declare(strict_types=1);

class UsuarioModel
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

    public function buscarParaLogin(string $login): ?array
    {
        $sql = "
            SELECT
                u.id,
                u.id_persona,
                u.username,
                u.password,
                u.correo AS usuario_correo,
                u.estado AS usuario_estado,

                p.tipo_documento,
                p.documento,
                p.nombres,
                p.apellidos,
                p.correo AS persona_correo,
                p.telefono,
                p.estado AS persona_estado

            FROM usuarios u

            INNER JOIN personas p
                ON p.id = u.id_persona

            WHERE
                (
                    u.username = :login_username
                    OR u.correo = :login_correo
                    OR p.correo = :login_persona_correo
                    OR p.documento = :login_documento
                )

                AND u.estado = 'activo'
                AND p.estado = 'activo'

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':login_username' => $login,
            ':login_correo' => $login,
            ':login_persona_correo' => $login,
            ':login_documento' => $login,
        ]);

        $usuario = $stmt->fetch();

        if (!$usuario) {
            return null;
        }

        return $usuario;
    }

    public function obtenerRoles(int $usuarioId): array
    {
        $sql = "
            SELECT
                r.id,
                r.nombre,
                r.descripcion

            FROM usuario_roles ur

            INNER JOIN roles r
                ON r.id = ur.id_rol

            WHERE
                ur.id_usuario = :id_usuario
                AND r.estado = 'activo'

            ORDER BY r.nombre ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_usuario' => $usuarioId,
        ]);

        return $stmt->fetchAll();
    }

    public function obtenerPermisos(int $usuarioId): array
    {
        $sql = "
            SELECT DISTINCT
                p.id,
                p.nombre,
                p.descripcion

            FROM usuario_roles ur

            INNER JOIN rol_permisos rp
                ON rp.id_rol = ur.id_rol

            INNER JOIN permisos p
                ON p.id = rp.id_permiso

            INNER JOIN roles r
                ON r.id = ur.id_rol

            WHERE
                ur.id_usuario = :id_usuario
                AND r.estado = 'activo'

            ORDER BY p.nombre ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_usuario' => $usuarioId,
        ]);

        return $stmt->fetchAll();
    }

    public function actualizarUltimoAcceso(int $usuarioId): bool
    {
        $sql = "
            UPDATE usuarios
            SET ultimo_acceso = NOW()
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $usuarioId,
        ]);
    }
}

