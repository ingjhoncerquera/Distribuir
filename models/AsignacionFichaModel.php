<?php

declare(strict_types=1);

class AsignacionFichaModel
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();

        $this->db = $database->conectar();
    }


    /*
    |--------------------------------------------------------------------------
    | LISTAR ASIGNACIONES
    |--------------------------------------------------------------------------
    */

    public function listar(
        string $buscar = '',
        array $idsFichas = []
    ): array {

        $sql = "
            SELECT

                af.id,
                af.id_ficha,
                af.id_profesional,
                af.tipo_asignacion,
                af.fecha_inicio,
                af.fecha_fin,
                af.estado,
                af.observaciones,
                af.created_at,

                f.numero_ficha,
                f.fecha_inicio AS fecha_inicio_ficha,
                f.fecha_fin AS fecha_fin_ficha,
                f.estado AS estado_ficha,

                p.codigo AS codigo_programa,
                p.nombre AS nombre_programa,

                prof.tipo_profesional,
                prof.registro_profesional,
                prof.especialidad,

                per.tipo_documento,
                per.documento,
                per.nombres,
                per.apellidos,

                TRIM(
                    CONCAT(
                        COALESCE(per.nombres, ''),
                        ' ',
                        COALESCE(per.apellidos, '')
                    )
                ) AS nombre_profesional

            FROM asignaciones_ficha af

            INNER JOIN fichas f
                ON f.id = af.id_ficha

            INNER JOIN programas p
                ON p.id = f.id_programa

            INNER JOIN profesionales prof
                ON prof.id = af.id_profesional

            INNER JOIN personas per
                ON per.id = prof.id_persona

            WHERE 1 = 1
        ";

        $parametros = [];

        $buscar = trim($buscar);

        if ($buscar !== '') {

            $sql .= "
                AND (
                    f.numero_ficha LIKE :buscar_ficha
                    OR p.codigo LIKE :buscar_codigo
                    OR p.nombre LIKE :buscar_programa
                    OR per.documento LIKE :buscar_documento
                    OR per.nombres LIKE :buscar_nombres
                    OR per.apellidos LIKE :buscar_apellidos
                    OR prof.tipo_profesional LIKE :buscar_tipo_profesional
                    OR af.tipo_asignacion LIKE :buscar_tipo_asignacion
                )
            ";

            $valorBuscar = '%' . $buscar . '%';

            $parametros = [
                ':buscar_ficha' =>
                    $valorBuscar,

                ':buscar_codigo' =>
                    $valorBuscar,

                ':buscar_programa' =>
                    $valorBuscar,

                ':buscar_documento' =>
                    $valorBuscar,

                ':buscar_nombres' =>
                    $valorBuscar,

                ':buscar_apellidos' =>
                    $valorBuscar,

                ':buscar_tipo_profesional' =>
                    $valorBuscar,

                ':buscar_tipo_asignacion' =>
                    $valorBuscar,
            ];
        }

        /*
         * Limita el resultado a ciertas fichas (se usa para mostrar el
         * resumen completo de responsables de las fichas encontradas).
         */
        if (!empty($idsFichas)) {

            $marcadores = [];

            foreach (array_values($idsFichas) as $i => $idFicha) {

                $marcador = ':id_ficha_' . $i;

                $marcadores[] = $marcador;

                $parametros[$marcador] = (int) $idFicha;
            }

            $sql .= "
                AND af.id_ficha IN (" . implode(', ', $marcadores) . ")
            ";
        }

        $sql .= "
            ORDER BY
                CASE
                    WHEN af.estado = 'activa'
                    THEN 0

                    WHEN af.estado = 'finalizada'
                    THEN 1

                    ELSE 2
                END,

                af.fecha_inicio DESC,

                f.numero_ficha ASC,

                per.apellidos ASC,

                per.nombres ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($parametros);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FICHAS DISPONIBLES
    |--------------------------------------------------------------------------
    */

    public function fichasDisponibles(): array
    {
        $sql = "
            SELECT
                f.id,
                f.numero_ficha,
                f.id_programa,
                f.fecha_inicio,
                f.fecha_fin,
                f.estado,

                p.codigo AS codigo_programa,
                p.nombre AS nombre_programa

            FROM fichas f

            INNER JOIN programas p
                ON p.id = f.id_programa

            WHERE f.estado = 'activa'

            ORDER BY
                f.numero_ficha ASC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PROFESIONALES DISPONIBLES
    |--------------------------------------------------------------------------
    */

    public function profesionalesDisponibles(): array
    {
        $sql = "
            SELECT

                prof.id,
                prof.id_persona,
                prof.tipo_profesional,
                prof.registro_profesional,
                prof.especialidad,
                prof.estado,

                per.tipo_documento,
                per.documento,
                per.nombres,
                per.apellidos,

                TRIM(
                    CONCAT(
                        COALESCE(per.nombres, ''),
                        ' ',
                        COALESCE(per.apellidos, '')
                    )
                ) AS nombre_profesional

            FROM profesionales prof

            INNER JOIN personas per
                ON per.id = prof.id_persona

            WHERE prof.estado = 'activo'
              AND per.estado = 'activo'

            ORDER BY
                per.apellidos ASC,
                per.nombres ASC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | OBTENER UNA ASIGNACIÓN
    |--------------------------------------------------------------------------
    */

    public function obtener(
        int $id
    ): ?array {

        $sql = "
            SELECT

                af.*,

                f.numero_ficha,

                p.codigo AS codigo_programa,
                p.nombre AS nombre_programa,

                prof.tipo_profesional,
                prof.registro_profesional,
                prof.especialidad,

                per.tipo_documento,
                per.documento,
                per.nombres,
                per.apellidos,

                TRIM(
                    CONCAT(
                        COALESCE(per.nombres, ''),
                        ' ',
                        COALESCE(per.apellidos, '')
                    )
                ) AS nombre_profesional

            FROM asignaciones_ficha af

            INNER JOIN fichas f
                ON f.id = af.id_ficha

            INNER JOIN programas p
                ON p.id = f.id_programa

            INNER JOIN profesionales prof
                ON prof.id = af.id_profesional

            INNER JOIN personas per
                ON per.id = prof.id_persona

            WHERE af.id = :id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        $resultado = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        return $resultado !== false
            ? $resultado
            : null;
    }


    /*
    |--------------------------------------------------------------------------
    | CREAR ASIGNACIÓN
    |--------------------------------------------------------------------------
    */

    public function crear(
        array $datos
    ): int {

        $sql = "
            INSERT INTO asignaciones_ficha
            (
                id_ficha,
                id_profesional,
                tipo_asignacion,
                fecha_inicio,
                fecha_fin,
                estado,
                observaciones
            )
            VALUES
            (
                :id_ficha,
                :id_profesional,
                :tipo_asignacion,
                :fecha_inicio,
                :fecha_fin,
                'activa',
                :observaciones
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_ficha' =>
                (int) $datos['id_ficha'],

            ':id_profesional' =>
                (int) $datos['id_profesional'],

            ':tipo_asignacion' =>
                trim(
                    (string)
                    $datos['tipo_asignacion']
                ),

            ':fecha_inicio' =>
                $datos['fecha_inicio'],

            ':fecha_fin' =>
                !empty($datos['fecha_fin'])
                    ? $datos['fecha_fin']
                    : null,

            ':observaciones' =>
                !empty($datos['observaciones'])
                    ? trim(
                        (string)
                        $datos['observaciones']
                    )
                    : null,
        ]);

        return (int)
            $this->db->lastInsertId();
    }


    /*
    |--------------------------------------------------------------------------
    | FINALIZAR ASIGNACIÓN
    |--------------------------------------------------------------------------
    */

    public function finalizar(
        int $id,
        string $fechaFin
    ): bool {

        $sql = "
            UPDATE asignaciones_ficha

            SET
                fecha_fin = :fecha_fin,
                estado = 'finalizada'

            WHERE id = :id
              AND estado = 'activa'
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':fecha_fin' => $fechaFin,
            ':id' => $id,
        ]);

        return $stmt->rowCount() > 0;
    }


    /*
    |--------------------------------------------------------------------------
    | CREAR VARIAS ASIGNACIONES (TRANSACCIÓN)
    |--------------------------------------------------------------------------
    |
    | Registra todas las asignaciones recibidas o ninguna.
    | Si una falla, se revierte todo para no dejar la ficha a medias.
    |
    | Cada elemento debe tener la misma estructura que crear().
    |
    */

    public function crearVarias(
        array $asignaciones,
        array $reemplazos = []
    ): int {

        $total = 0;

        $this->db->beginTransaction();

        try {

            /*
             * Antes de crear, se finalizan las asignaciones que serán
             * reemplazadas. Cada elemento: ['id' => int, 'fecha_fin' => 'Y-m-d'].
             */
            foreach ($reemplazos as $reemplazo) {

                $stmt = $this->db->prepare("
                    UPDATE asignaciones_ficha

                    SET
                        fecha_fin = :fecha_fin,
                        estado = 'finalizada'

                    WHERE id = :id
                      AND estado = 'activa'
                ");

                $stmt->execute([
                    ':fecha_fin' => $reemplazo['fecha_fin'],
                    ':id' => (int) $reemplazo['id'],
                ]);
            }

            foreach ($asignaciones as $datos) {

                $this->crear($datos);

                $total++;
            }

            $this->db->commit();

            return $total;

        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDACIONES PREVIAS
    |--------------------------------------------------------------------------
    */

    public function fichaActiva(
        int $idFicha
    ): bool {

        $stmt = $this->db->prepare("
            SELECT 1
            FROM fichas
            WHERE id = :id
              AND estado = 'activa'
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $idFicha
        ]);

        return $stmt->fetchColumn() !== false;
    }


    public function profesionalActivo(
        int $idProfesional
    ): bool {

        $stmt = $this->db->prepare("
            SELECT 1
            FROM profesionales prof
            INNER JOIN personas per
                ON per.id = prof.id_persona
            WHERE prof.id = :id
              AND prof.estado = 'activo'
              AND per.estado = 'activo'
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $idProfesional
        ]);

        return $stmt->fetchColumn() !== false;
    }


    /*
    |--------------------------------------------------------------------------
    | ¿YA EXISTE UNA ASIGNACIÓN ACTIVA QUE SE CRUCE EN FECHAS?
    |--------------------------------------------------------------------------
    |
    | - Sin $idProfesional: busca cualquier responsable activo de ese tipo
    |   (se usa para Líder de ficha y Coordinador, que son únicos por ficha).
    | - Con $idProfesional: busca esa misma persona con ese mismo tipo.
    |
    | Hay cruce cuando los períodos se solapan. Una fecha_fin vacía
    | significa que la asignación sigue vigente.
    |
    */

    public function existeAsignacionActiva(
        int $idFicha,
        string $tipoAsignacion,
        string $fechaInicio,
        ?string $fechaFin = null,
        ?int $idProfesional = null
    ): bool {

        return !empty(
            $this->asignacionesActivasCruzadas(
                $idFicha,
                $tipoAsignacion,
                $fechaInicio,
                $fechaFin,
                $idProfesional
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ASIGNACIONES ACTIVAS QUE SE CRUZAN CON EL PERÍODO INDICADO
    |--------------------------------------------------------------------------
    |
    | Devuelve quién es el titular actual (nombre, fechas e id), para poder
    | informarlo al usuario o reemplazarlo.
    |
    */

    public function asignacionesActivasCruzadas(
        int $idFicha,
        string $tipoAsignacion,
        string $fechaInicio,
        ?string $fechaFin = null,
        ?int $idProfesional = null
    ): array {

        $fechaFin = ($fechaFin !== null && trim($fechaFin) !== '')
            ? $fechaFin
            : null;

        $sql = "
            SELECT

                af.id,
                af.id_profesional,
                af.fecha_inicio,
                af.fecha_fin,

                TRIM(
                    CONCAT(
                        COALESCE(per.nombres, ''),
                        ' ',
                        COALESCE(per.apellidos, '')
                    )
                ) AS nombre_profesional

            FROM asignaciones_ficha af

            LEFT JOIN profesionales prof
                ON prof.id = af.id_profesional

            LEFT JOIN personas per
                ON per.id = prof.id_persona

            WHERE af.id_ficha = :id_ficha
              AND af.tipo_asignacion = :tipo
              AND af.estado = 'activa'

              AND (
                  af.fecha_fin IS NULL
                  OR af.fecha_fin >= :fecha_inicio
              )

              AND (
                  :fecha_fin_nula IS NULL
                  OR af.fecha_inicio <= :fecha_fin_comparar
              )
        ";

        $parametros = [
            ':id_ficha' => $idFicha,
            ':tipo' => $tipoAsignacion,
            ':fecha_inicio' => $fechaInicio,
            ':fecha_fin_nula' => $fechaFin,
            ':fecha_fin_comparar' => $fechaFin,
        ];

        if ($idProfesional !== null) {

            $sql .= "
              AND af.id_profesional = :id_profesional
            ";

            $parametros[':id_profesional'] = $idProfesional;
        }

        $sql .= "
            ORDER BY af.fecha_inicio ASC, af.id ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($parametros);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}