<?php

declare(strict_types=1);

class ProfesionalModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->conectar();
    }

    public function contar(string $buscar = ''): int
    {
        $sql = "
            SELECT COUNT(DISTINCT pr.id)
            FROM profesionales pr

            INNER JOIN personas p
                ON p.id = pr.id_persona

            LEFT JOIN usuarios u
                ON u.id_persona = p.id

            WHERE 1 = 1
        ";

        $params = [];

        if ($buscar !== '') {

            $sql .= "
                AND (
                    p.documento LIKE ?
                    OR p.nombres LIKE ?
                    OR p.apellidos LIKE ?
                    OR pr.tipo_profesional LIKE ?
                    OR u.username LIKE ?
                )
            ";

            $termino = '%' . $buscar . '%';

            $params = [
                $termino,
                $termino,
                $termino,
                $termino,
                $termino
            ];
        }

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function listar(
        string $buscar = '',
        int $limite = 20,
        int $offset = 0
    ): array {
        $sql = "
            SELECT
                pr.id,
                pr.id_persona,
                pr.tipo_profesional,
                pr.registro_profesional,
                pr.especialidad,
                pr.estado AS estado_profesional,

                p.tipo_documento,
                p.documento,
                p.nombres,
                p.apellidos,
                p.telefono,
                p.correo,
                p.estado AS estado_persona,

                u.id AS id_usuario,
                u.username,
                u.estado AS estado_usuario,

                GROUP_CONCAT(
                    DISTINCT r.nombre
                    ORDER BY r.nombre ASC
                    SEPARATOR ', '
                ) AS roles

            FROM profesionales pr

            INNER JOIN personas p
                ON p.id = pr.id_persona

            LEFT JOIN usuarios u
                ON u.id_persona = p.id

            LEFT JOIN usuario_roles ur
                ON ur.id_usuario = u.id

            LEFT JOIN roles r
                ON r.id = ur.id_rol

            WHERE 1 = 1
        ";

        $params = [];

        if ($buscar !== '') {

            $sql .= "
                AND (
                    p.documento LIKE ?
                    OR p.nombres LIKE ?
                    OR p.apellidos LIKE ?
                    OR pr.tipo_profesional LIKE ?
                    OR u.username LIKE ?
                )
            ";

            $termino = '%' . $buscar . '%';

            $params = [
                $termino,
                $termino,
                $termino,
                $termino,
                $termino
            ];
        }

        $sql .= "
            GROUP BY
                pr.id,
                pr.id_persona,
                pr.tipo_profesional,
                pr.registro_profesional,
                pr.especialidad,
                pr.estado,
                p.tipo_documento,
                p.documento,
                p.nombres,
                p.apellidos,
                p.telefono,
                p.correo,
                p.estado,
                u.id,
                u.username,
                u.estado

            ORDER BY
                p.apellidos ASC,
                p.nombres ASC

            LIMIT ? OFFSET ?
        ";

        $stmt = $this->db->prepare($sql);

        $index = 1;

        foreach ($params as $value) {

            $stmt->bindValue(
                $index,
                $value,
                PDO::PARAM_STR
            );

            $index++;
        }

        $stmt->bindValue(
            $index++,
            $limite,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            $index,
            $offset,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtener(int $id): ?array
    {
        $sql = "
            SELECT
                pr.id,
                pr.id_persona,
                pr.tipo_profesional,
                pr.registro_profesional,
                pr.especialidad,
                pr.estado AS estado_profesional,

                p.tipo_documento,
                p.documento,
                p.nombres,
                p.apellidos,
                p.fecha_nacimiento,
                p.sexo,
                p.telefono,
                p.correo,
                p.direccion,
                p.municipio,
                p.departamento,
                p.estado AS estado_persona,

                u.id AS id_usuario,
                u.username,
                u.correo AS correo_usuario,
                u.ultimo_acceso,
                u.estado AS estado_usuario

            FROM profesionales pr

            INNER JOIN personas p
                ON p.id = pr.id_persona

            LEFT JOIN usuarios u
                ON u.id_persona = p.id

            WHERE pr.id = ?

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$id]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function obtenerRoles(int $idProfesional): array
    {
        $sql = "
            SELECT
                r.id,
                r.nombre,
                r.descripcion,
                r.estado

            FROM profesionales pr

            INNER JOIN usuarios u
                ON u.id_persona = pr.id_persona

            INNER JOIN usuario_roles ur
                ON ur.id_usuario = u.id

            INNER JOIN roles r
                ON r.id = ur.id_rol

            WHERE pr.id = ?

            ORDER BY
                r.nombre ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$idProfesional]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPermisos(int $idProfesional): array
    {
        $sql = "
            SELECT DISTINCT
                p.id,
                p.nombre,
                p.modulo,
                p.descripcion,
                p.estado

            FROM profesionales pr

            INNER JOIN usuarios u
                ON u.id_persona = pr.id_persona

            INNER JOIN usuario_roles ur
                ON ur.id_usuario = u.id

            INNER JOIN roles r
                ON r.id = ur.id_rol

            INNER JOIN rol_permisos rp
                ON rp.id_rol = r.id

            INNER JOIN permisos p
                ON p.id = rp.id_permiso

            WHERE pr.id = ?

              AND r.estado = 'activo'

              AND p.estado = 'activo'

            ORDER BY
                p.modulo ASC,
                p.nombre ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$idProfesional]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerUsuario(int $idProfesional): ?array
    {
        $sql = "
            SELECT
                u.id,
                u.id_persona,
                u.username,
                u.correo,
                u.ultimo_acceso,
                u.estado,
                u.created_at,
                u.updated_at

            FROM profesionales pr

            INNER JOIN usuarios u
                ON u.id_persona = pr.id_persona

            WHERE pr.id = ?

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$idProfesional]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function existePorPersona(
        int $idPersona
    ): bool {
        $sql = "
            SELECT COUNT(*)
            FROM profesionales
            WHERE id_persona = ?
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$idPersona]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function existeUsuarioPorPersona(
        int $idPersona
    ): bool {
        $sql = "
            SELECT COUNT(*)
            FROM usuarios
            WHERE id_persona = ?
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$idPersona]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function listarRolesActivos(): array
    {
        $sql = "
            SELECT
                id,
                nombre,
                descripcion

            FROM roles

            WHERE estado = 'activo'

            ORDER BY
                nombre ASC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Guardar roles de un profesional
    |--------------------------------------------------------------------------
    */

    public function guardarRoles(
        int $idProfesional,
        array $idsRoles
    ): bool {
        try {

            $this->db->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | Obtener el usuario asociado al profesional
            |--------------------------------------------------------------------------
            */

            $sql = "
                SELECT
                    u.id
                FROM profesionales pr

                INNER JOIN usuarios u
                    ON u.id_persona = pr.id_persona

                WHERE pr.id = ?

                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                $idProfesional,
            ]);

            $idUsuario = $stmt->fetchColumn();

            if ($idUsuario === false) {

                throw new RuntimeException(
                    'El profesional no tiene un usuario asociado.'
                );
            }

            $idUsuario = (int) $idUsuario;

            if ($idUsuario <= 0) {

                throw new RuntimeException(
                    'El usuario asociado al profesional no es válido.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Normalizar IDs de roles
            |--------------------------------------------------------------------------
            */

            $idsRolesLimpios = [];

            foreach ($idsRoles as $idRol) {

                $idRol = (int) $idRol;

                if ($idRol > 0) {
                    $idsRolesLimpios[] = $idRol;
                }
            }

            $idsRolesLimpios = array_values(
                array_unique($idsRolesLimpios)
            );

            /*
            |--------------------------------------------------------------------------
            | Validar que los roles existan y estén activos
            |--------------------------------------------------------------------------
            */

            if (!empty($idsRolesLimpios)) {

                $placeholders = implode(
                    ',',
                    array_fill(
                        0,
                        count($idsRolesLimpios),
                        '?'
                    )
                );

                $sql = "
                    SELECT
                        id
                    FROM roles
                    WHERE estado = 'activo'
                      AND id IN ($placeholders)
                ";

                $stmt = $this->db->prepare($sql);

                $stmt->execute($idsRolesLimpios);

                $rolesValidos = $stmt->fetchAll(
                    PDO::FETCH_COLUMN
                );

                $rolesValidos = array_map(
                    'intval',
                    $rolesValidos
                );

                sort($rolesValidos);
                $rolesSolicitados = $idsRolesLimpios;
                sort($rolesSolicitados);

                if ($rolesValidos !== $rolesSolicitados) {

                    throw new RuntimeException(
                        'Uno o más roles seleccionados no existen o están inactivos.'
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Eliminar roles actuales
            |--------------------------------------------------------------------------
            */

            $sql = "
                DELETE FROM usuario_roles
                WHERE id_usuario = ?
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                $idUsuario,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Insertar nuevos roles
            |--------------------------------------------------------------------------
            */

            if (!empty($idsRolesLimpios)) {

                $sql = "
                    INSERT INTO usuario_roles (
                        id_usuario,
                        id_rol
                    )
                    VALUES (
                        ?,
                        ?
                    )
                ";

                $stmt = $this->db->prepare($sql);

                foreach ($idsRolesLimpios as $idRol) {

                    $stmt->execute([
                        $idUsuario,
                        $idRol,
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Confirmar transacción
            |--------------------------------------------------------------------------
            */

            $this->db->commit();

            return true;

        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Crear profesional
    |--------------------------------------------------------------------------
    */

    public function crear(
        array $datosPersona,
        array $datosProfesional
    ): int {
        try {

            $this->db->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | Verificar documento existente
            |--------------------------------------------------------------------------
            */

            $sql = "
                SELECT
                    id
                FROM personas
                WHERE
                    tipo_documento = ?
                    AND documento = ?
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                $datosPersona['tipo_documento'],
                $datosPersona['documento'],
            ]);

            $personaExistente = $stmt->fetchColumn();

            if ($personaExistente !== false) {

                throw new RuntimeException(
                    'Ya existe una persona registrada con el tipo y número de documento ingresados.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Crear persona
            |--------------------------------------------------------------------------
            */

            $sql = "
                INSERT INTO personas (
                    tipo_documento,
                    documento,
                    nombres,
                    apellidos,
                    fecha_nacimiento,
                    sexo,
                    telefono,
                    correo,
                    direccion,
                    municipio,
                    departamento,
                    estado
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                $datosPersona['tipo_documento'],
                $datosPersona['documento'],
                $datosPersona['nombres'],
                $datosPersona['apellidos'],
                $datosPersona['fecha_nacimiento'],
                $datosPersona['sexo'],
                $datosPersona['telefono'],
                $datosPersona['correo'],
                $datosPersona['direccion'],
                $datosPersona['municipio'],
                $datosPersona['departamento'],
                $datosPersona['estado'],
            ]);

            $idPersona = (int) $this->db->lastInsertId();

            if ($idPersona <= 0) {

                throw new RuntimeException(
                    'No fue posible obtener el identificador de la persona creada.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Crear profesional
            |--------------------------------------------------------------------------
            */

            $sql = "
                INSERT INTO profesionales (
                    id_persona,
                    tipo_profesional,
                    registro_profesional,
                    especialidad,
                    estado
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                $idPersona,
                $datosProfesional['tipo_profesional'],
                $datosProfesional['registro_profesional'],
                $datosProfesional['especialidad'],
                $datosProfesional['estado'],
            ]);

            $idProfesional = (int) $this->db->lastInsertId();

            if ($idProfesional <= 0) {

                throw new RuntimeException(
                    'No fue posible crear el registro profesional.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Confirmar transacción
            |--------------------------------------------------------------------------
            */

            $this->db->commit();

            return $idProfesional;

        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Crear usuario para un profesional
    |--------------------------------------------------------------------------
    */

    public function crearUsuario(
        int $idProfesional,
        string $username,
        string $password,
        ?string $correo = null
    ): int {
        try {

            $this->db->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | Verificar que el profesional exista
            |--------------------------------------------------------------------------
            */

            $sql = "
                SELECT
                    id_persona
                FROM profesionales
                WHERE id = ?
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                $idProfesional,
            ]);

            $idPersona = $stmt->fetchColumn();

            if ($idPersona === false) {

                throw new RuntimeException(
                    'El profesional seleccionado no existe.'
                );
            }

            $idPersona = (int) $idPersona;

            if ($idPersona <= 0) {

                throw new RuntimeException(
                    'El profesional no tiene una persona asociada válida.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Verificar si la persona ya tiene usuario
            |--------------------------------------------------------------------------
            */

            $sql = "
                SELECT
                    id
                FROM usuarios
                WHERE id_persona = ?
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                $idPersona,
            ]);

            $usuarioExistente = $stmt->fetchColumn();

            if ($usuarioExistente !== false) {

                throw new RuntimeException(
                    'Este profesional ya tiene un usuario creado.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Verificar username existente
            |--------------------------------------------------------------------------
            */

            $sql = "
                SELECT
                    id
                FROM usuarios
                WHERE username = ?
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                $username,
            ]);

            $usernameExistente = $stmt->fetchColumn();

            if ($usernameExistente !== false) {

                throw new RuntimeException(
                    'El nombre de usuario ingresado ya está registrado. Utilice otro.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Crear usuario
            |--------------------------------------------------------------------------
            */

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            if (
                $passwordHash === false
                || $passwordHash === ''
            ) {

                throw new RuntimeException(
                    'No fue posible proteger la contraseña.'
                );
            }

            $sql = "
                INSERT INTO usuarios (
                    id_persona,
                    username,
                    password,
                    correo,
                    estado
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    'activo'
                )
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                $idPersona,
                $username,
                $passwordHash,
                $correo,
            ]);

            $idUsuario = (int) $this->db->lastInsertId();

            if ($idUsuario <= 0) {

                throw new RuntimeException(
                    'No fue posible crear el usuario.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Confirmar transacción
            |--------------------------------------------------------------------------
            */

            $this->db->commit();

            return $idUsuario;

        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }
}

