<?php

declare(strict_types=1);

class AprendizModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->conectar();
    }

    /*
    |--------------------------------------------------------------------------
    | LISTADO
    |--------------------------------------------------------------------------
    */

    public function contar(
        string $buscar = '',
        ?int $idProfesionalLider = null,
        ?string $tipoAsignacion = null
    ): int {
        $sql = "
            SELECT COUNT(DISTINCT a.id)
            FROM aprendices a

            INNER JOIN personas p
                ON p.id = a.id_persona

            LEFT JOIN matriculas m
                ON m.id_aprendiz = a.id

            LEFT JOIN fichas f
                ON f.id = m.id_ficha

            WHERE 1 = 1
        ";

        $params = [];

        /*
        |--------------------------------------------------------------------------
        | FILTRO LÍDER DE FICHA
        |--------------------------------------------------------------------------
        |
        | Si se recibe un id_profesional, significa que el usuario
        | es Líder de Ficha y solamente debe contabilizar aprendices
        | pertenecientes a las fichas que tiene asignadas actualmente.
        |
        */

        if (
            $tipoAsignacion !== null
            || (
                $idProfesionalLider !== null
                && $idProfesionalLider > 0
            )
        ) {
            $tipoAsignacionEfectiva = $tipoAsignacion ?? 'LIDER_FICHA';

            if (
                $idProfesionalLider === null
                || $idProfesionalLider <= 0
            ) {
                // Seguridad: sin profesional asociado, no mostrar registros.
                $sql .= " AND 1 = 0 ";
            } else {
                $sql .= "
                    AND EXISTS (
                        SELECT 1
                        FROM asignaciones_ficha af
                        WHERE af.id_ficha = f.id
                          AND af.id_profesional = ?
                          AND af.tipo_asignacion = ?
                          AND af.estado = 'activa'
                          AND af.fecha_inicio <= CURRENT_DATE
                          AND (
                                af.fecha_fin IS NULL
                                OR af.fecha_fin >= CURRENT_DATE
                          )
                    )
                ";

                $params[] = $idProfesionalLider;
                $params[] = $tipoAsignacionEfectiva;
            }
        }

        if ($buscar !== '') {

            $sql .= "
                AND (
                    p.documento LIKE ?
                    OR p.nombres LIKE ?
                    OR p.apellidos LIKE ?
                    OR f.numero_ficha LIKE ?
                )
            ";

            $termino = '%' . $buscar . '%';

            $params[] = $termino;
            $params[] = $termino;
            $params[] = $termino;
            $params[] = $termino;
        }

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }


    public function listar(
        string $buscar = '',
        int $limite = 20,
        int $offset = 0,
        ?int $idProfesionalLider = null,
        ?string $tipoAsignacion = null
    ): array {
        $sql = "
            SELECT
                a.id,
                a.id_persona,
                a.estado AS estado_aprendiz,

                p.tipo_documento,
                p.documento,
                p.nombres,
                p.apellidos,
                p.telefono,
                p.correo,

                COUNT(DISTINCT m.id) AS cantidad_matriculas,

                GROUP_CONCAT(
                    DISTINCT f.numero_ficha
                    ORDER BY f.numero_ficha
                    SEPARATOR ', '
                ) AS fichas

            FROM aprendices a

            INNER JOIN personas p
                ON p.id = a.id_persona

            LEFT JOIN matriculas m
                ON m.id_aprendiz = a.id

            LEFT JOIN fichas f
                ON f.id = m.id_ficha

            WHERE 1 = 1
        ";

        $params = [];

        /*
        |--------------------------------------------------------------------------
        | FILTRO LÍDER DE FICHA
        |--------------------------------------------------------------------------
        |
        | Cuando el usuario es Líder de Ficha, solamente se muestran
        | los aprendices que pertenecen a una ficha donde dicho
        | profesional tiene una asignación activa como LÍDER_FICHA.
        |
        */

        if (
            $tipoAsignacion !== null
            || (
                $idProfesionalLider !== null
                && $idProfesionalLider > 0
            )
        ) {
            $tipoAsignacionEfectiva = $tipoAsignacion ?? 'LIDER_FICHA';

            if (
                $idProfesionalLider === null
                || $idProfesionalLider <= 0
            ) {
                // Seguridad: sin profesional asociado, no mostrar registros.
                $sql .= " AND 1 = 0 ";
            } else {
                $sql .= "
                    AND EXISTS (
                        SELECT 1
                        FROM asignaciones_ficha af
                        WHERE af.id_ficha = f.id
                          AND af.id_profesional = ?
                          AND af.tipo_asignacion = ?
                          AND af.estado = 'activa'
                          AND af.fecha_inicio <= CURRENT_DATE
                          AND (
                                af.fecha_fin IS NULL
                                OR af.fecha_fin >= CURRENT_DATE
                          )
                    )
                ";

                $params[] = $idProfesionalLider;
                $params[] = $tipoAsignacionEfectiva;
            }
        }

        if ($buscar !== '') {

            $sql .= "
                AND (
                    p.documento LIKE ?
                    OR p.nombres LIKE ?
                    OR p.apellidos LIKE ?
                    OR f.numero_ficha LIKE ?
                )
            ";

            $termino = '%' . $buscar . '%';

            $params[] = $termino;
            $params[] = $termino;
            $params[] = $termino;
            $params[] = $termino;
        }

        $sql .= "
            GROUP BY
                a.id,
                a.id_persona,
                a.estado,
                p.tipo_documento,
                p.documento,
                p.nombres,
                p.apellidos,
                p.telefono,
                p.correo

            ORDER BY
                p.apellidos ASC,
                p.nombres ASC

            LIMIT ? OFFSET ?
        ";

        $stmt = $this->db->prepare($sql);

        $index = 1;

        foreach ($params as $value) {

            if (is_int($value)) {

                $stmt->bindValue(
                    $index,
                    $value,
                    PDO::PARAM_INT
                );

            } else {

                $stmt->bindValue(
                    $index,
                    $value,
                    PDO::PARAM_STR
                );
            }

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


    /*
    |--------------------------------------------------------------------------
    | DETALLE
    |--------------------------------------------------------------------------
    */

    public function obtener(int $id): ?array
    {
        $sql = "
            SELECT
                a.id,
                a.id_persona,
                a.id_eps,

                a.contacto_emergencia_nombre,
                a.contacto_emergencia_telefono,
                a.contacto_emergencia_parentesco,

                a.estado AS estado_aprendiz,

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
                p.departamento

            FROM aprendices a

            INNER JOIN personas p
                ON p.id = a.id_persona

            WHERE a.id = ?

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$id]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }


    public function obtenerMatriculas(int $idAprendiz): array
    {
        $sql = "
            SELECT
                m.id,
                m.fecha_inicio,
                m.fecha_fin,
                m.estado AS estado_matricula,
                m.observaciones,

                f.id AS id_ficha,
                f.numero_ficha,
                f.version_programa,
                f.modalidad,
                f.regional,
                f.centro,
                f.fecha_inicio AS ficha_fecha_inicio,
                f.fecha_fin AS ficha_fecha_fin,
                f.estado AS estado_ficha,

                pr.codigo AS codigo_programa,
                pr.nombre AS nombre_programa,
                pr.nivel

            FROM matriculas m

            INNER JOIN fichas f
                ON f.id = m.id_ficha

            INNER JOIN programas pr
                ON pr.id = f.id_programa

            WHERE m.id_aprendiz = ?

            ORDER BY
                m.fecha_inicio DESC,
                f.numero_ficha ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$idAprendiz]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function obtenerCompetenciasRAP(int $idAprendiz): array
    {
        $sql = "
            SELECT
                f.id AS id_ficha,
                f.numero_ficha,
                f.version_programa,
                f.estado AS estado_ficha,

                pr.codigo AS codigo_programa,
                pr.nombre AS nombre_programa,
                pr.nivel,

                c.id AS id_competencia,
                c.codigo AS codigo_competencia,
                c.nombre AS nombre_competencia,
                c.descripcion AS descripcion_competencia,

                r.id AS id_rap,
                r.codigo AS codigo_rap,
                r.nombre AS nombre_rap,
                r.descripcion AS descripcion_rap,
                r.horas,

                ra.id AS id_resultado,
                ra.id_instructor,

                TRIM(
                    CONCAT(
                        COALESCE(pi.nombres, ''),
                        ' ',
                        COALESCE(pi.apellidos, '')
                    )
                ) AS nombre_instructor,

                prof.tipo_profesional,
                prof.registro_profesional,
                prof.especialidad,

                ra.fecha_evaluacion,
                ra.fecha_hora_evaluacion,
                ra.juicio,
                ra.juicio_sena,
                ra.calificacion,
                ra.observaciones

            FROM fichas f

            INNER JOIN programas pr
                ON pr.id = f.id_programa

            INNER JOIN competencias c
                ON c.id_programa = pr.id

            INNER JOIN rap r
                ON r.id_competencia = c.id

            LEFT JOIN resultados_aprendiz ra
                ON ra.id = (
                    SELECT ra2.id
                    FROM resultados_aprendiz ra2
                    WHERE ra2.id_aprendiz = ?
                      AND ra2.id_ficha = f.id
                      AND ra2.id_rap = r.id
                    ORDER BY
                        COALESCE(
                            ra2.fecha_hora_evaluacion,
                            CONCAT(
                                ra2.fecha_evaluacion,
                                ' 00:00:00'
                            )
                        ) DESC,
                        ra2.id DESC
                    LIMIT 1
                )

            LEFT JOIN profesionales prof
                ON prof.id = ra.id_instructor

            LEFT JOIN personas pi
                ON pi.id = prof.id_persona

            WHERE EXISTS (
                SELECT 1
                FROM matriculas m
                WHERE m.id_aprendiz = ?
                  AND m.id_ficha = f.id
            )

            AND c.estado = 'activo'
            AND r.estado = 'activo'

            ORDER BY
                f.numero_ficha ASC,
                c.codigo ASC,
                r.codigo ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $idAprendiz,
            $idAprendiz
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | CARACTERIZACIÓN
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene la información completa que utilizará
     * el formulario individual de caracterización.
     */
    public function obtenerCaracterizacion(int $idAprendiz): ?array
    {
        $sql = "
            SELECT
                a.id,
                a.id_persona,
                a.id_eps,

                a.contacto_emergencia_nombre,
                a.contacto_emergencia_telefono,
                a.contacto_emergencia_parentesco,
                a.estado AS estado_aprendiz,

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

                e.nombre AS nombre_eps,

                ap.id AS id_poblacion,
                ap.id_tipo_poblacion,
                tp.nombre AS tipo_poblacion,
                ap.fecha_inicio AS fecha_inicio_poblacion,
                ap.fecha_fin AS fecha_fin_poblacion,
                ap.observacion AS observacion_poblacion

            FROM aprendices a

            INNER JOIN personas p
                ON p.id = a.id_persona

            LEFT JOIN eps e
                ON e.id = a.id_eps

            LEFT JOIN aprendiz_poblacion ap
                ON ap.id_aprendiz = a.id
                AND ap.fecha_fin IS NULL

            LEFT JOIN tipos_poblacion tp
                ON tp.id = ap.id_tipo_poblacion

            WHERE a.id = ?

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$idAprendiz]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }


    /**
     * Lista las EPS activas.
     */
    public function listarEpsActivas(): array
    {
        $sql = "
            SELECT
                id,
                nombre
            FROM eps
            WHERE estado = 'activo'
            ORDER BY nombre ASC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Lista los tipos de población activos.
     */
    public function listarTiposPoblacionActivos(): array
    {
        $sql = "
            SELECT
                id,
                nombre
            FROM tipos_poblacion
            WHERE estado = 'activo'
            ORDER BY
                CASE
                    WHEN nombre = 'NINGUNA' THEN 1
                    ELSE 0
                END,
                nombre ASC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Obtiene el historial completo de caracterización
     * por tipo de población.
     */
    public function obtenerHistorialPoblacion(int $idAprendiz): array
    {
        $sql = "
            SELECT
                ap.id,
                ap.id_tipo_poblacion,
                tp.nombre AS tipo_poblacion,
                ap.fecha_inicio,
                ap.fecha_fin,
                ap.observacion,
                ap.created_at

            FROM aprendiz_poblacion ap

            INNER JOIN tipos_poblacion tp
                ON tp.id = ap.id_tipo_poblacion

            WHERE ap.id_aprendiz = ?

            ORDER BY
                ap.fecha_inicio DESC,
                ap.id DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$idAprendiz]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZACIÓN DE CARACTERIZACIÓN
    |--------------------------------------------------------------------------
    */

    /**
     * Actualiza los datos generales permitidos del aprendiz
     * y registra el cambio de población conservando historial.
     */
    public function actualizarCaracterizacion(
        int $idAprendiz,
        array $datos
    ): bool {
        $this->db->beginTransaction();

        try {

            $idPersona = (int) ($datos['id_persona'] ?? 0);

            if ($idPersona <= 0) {
                throw new RuntimeException(
                    'No se encontró la persona asociada al aprendiz.'
                );
            }

            $idEps = null;

            if (
                isset($datos['id_eps'])
                && $datos['id_eps'] !== ''
                && $datos['id_eps'] !== null
            ) {
                $idEps = (int) $datos['id_eps'];
            }

            /*
            |--------------------------------------------------------------------------
            | PERSONA
            |--------------------------------------------------------------------------
            */

            $sqlPersona = "
                UPDATE personas
                SET
                    fecha_nacimiento = ?,
                    sexo = ?,
                    telefono = ?,
                    correo = ?,
                    direccion = ?,
                    municipio = ?,
                    departamento = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ";

            $stmtPersona = $this->db->prepare($sqlPersona);

            $stmtPersona->execute([
                $this->valorNulo(
                    $datos['fecha_nacimiento'] ?? null
                ),

                $this->valorNulo(
                    $datos['sexo'] ?? null
                ),

                $this->valorNulo(
                    $datos['telefono'] ?? null
                ),

                $this->valorNulo(
                    $datos['correo'] ?? null
                ),

                $this->valorNulo(
                    $datos['direccion'] ?? null
                ),

                $this->valorNulo(
                    $datos['municipio'] ?? null
                ),

                $this->valorNulo(
                    $datos['departamento'] ?? null
                ),

                $idPersona
            ]);

            /*
            |--------------------------------------------------------------------------
            | APRENDIZ
            |--------------------------------------------------------------------------
            */

            $sqlAprendiz = "
                UPDATE aprendices
                SET
                    id_eps = ?,
                    contacto_emergencia_nombre = ?,
                    contacto_emergencia_telefono = ?,
                    contacto_emergencia_parentesco = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ";

            $stmtAprendiz = $this->db->prepare($sqlAprendiz);

            $stmtAprendiz->execute([
                $idEps,

                $this->valorNulo(
                    $datos['contacto_emergencia_nombre'] ?? null
                ),

                $this->valorNulo(
                    $datos['contacto_emergencia_telefono'] ?? null
                ),

                $this->valorNulo(
                    $datos['contacto_emergencia_parentesco'] ?? null
                ),

                $idAprendiz
            ]);

            /*
            |--------------------------------------------------------------------------
            | POBLACIÓN
            |--------------------------------------------------------------------------
            */

            $idTipoPoblacion = (int) (
                $datos['id_tipo_poblacion'] ?? 0
            );

            if ($idTipoPoblacion <= 0) {
                throw new RuntimeException(
                    'Debe seleccionar un tipo de población.'
                );
            }

            /*
            | Verificamos que el tipo de población exista
            | y esté activo.
            */

            $stmtTipo = $this->db->prepare("
                SELECT id
                FROM tipos_poblacion
                WHERE id = ?
                  AND estado = 'activo'
                LIMIT 1
            ");

            $stmtTipo->execute([
                $idTipoPoblacion
            ]);

            if (!$stmtTipo->fetchColumn()) {
                throw new RuntimeException(
                    'El tipo de población seleccionado no es válido.'
                );
            }

            /*
            | Buscamos la caracterización actualmente vigente.
            */

            $stmtActual = $this->db->prepare("
                SELECT
                    id,
                    id_tipo_poblacion
                FROM aprendiz_poblacion
                WHERE id_aprendiz = ?
                  AND fecha_fin IS NULL
                ORDER BY
                    fecha_inicio DESC,
                    id DESC
                LIMIT 1
            ");

            $stmtActual->execute([
                $idAprendiz
            ]);

            $actual = $stmtActual->fetch(PDO::FETCH_ASSOC);

            $fechaInicio = trim(
                (string) (
                    $datos['fecha_inicio_poblacion']
                    ?? date('Y-m-d')
                )
            );

            if ($fechaInicio === '') {
                $fechaInicio = date('Y-m-d');
            }

            /*
            | Si no existe población actual, creamos el primer registro.
            */

            if (!$actual) {

                $stmtInsert = $this->db->prepare("
                    INSERT INTO aprendiz_poblacion (
                        id_aprendiz,
                        id_tipo_poblacion,
                        fecha_inicio,
                        fecha_fin,
                        observacion
                    ) VALUES (
                        ?,
                        ?,
                        ?,
                        NULL,
                        ?
                    )
                ");

                $stmtInsert->execute([
                    $idAprendiz,
                    $idTipoPoblacion,
                    $fechaInicio,
                    $this->valorNulo(
                        $datos['observacion_poblacion'] ?? null
                    )
                ]);

            } elseif (
                (int) $actual['id_tipo_poblacion']
                !== $idTipoPoblacion
            ) {

                /*
                | Cerramos el registro anterior.
                */

                $fechaFinAnterior = date(
                    'Y-m-d',
                    strtotime($fechaInicio . ' -1 day')
                );

                $stmtCerrar = $this->db->prepare("
                    UPDATE aprendiz_poblacion
                    SET
                        fecha_fin = ?,
                        observacion = COALESCE(observacion, '')
                    WHERE id = ?
                ");

                $stmtCerrar->execute([
                    $fechaFinAnterior,
                    (int) $actual['id']
                ]);

                /*
                | Creamos el nuevo registro vigente.
                */

                $stmtInsert = $this->db->prepare("
                    INSERT INTO aprendiz_poblacion (
                        id_aprendiz,
                        id_tipo_poblacion,
                        fecha_inicio,
                        fecha_fin,
                        observacion
                    ) VALUES (
                        ?,
                        ?,
                        ?,
                        NULL,
                        ?
                    )
                ");

                $stmtInsert->execute([
                    $idAprendiz,
                    $idTipoPoblacion,
                    $fechaInicio,
                    $this->valorNulo(
                        $datos['observacion_poblacion'] ?? null
                    )
                ]);

            } else {

                /*
                | Si continúa siendo el mismo tipo de población,
                | actualizamos la fecha y observación sin crear
                | registros innecesarios.
                */

                $stmtActualizarPoblacion = $this->db->prepare("
                    UPDATE aprendiz_poblacion
                    SET
                        fecha_inicio = ?,
                        observacion = ?
                    WHERE id = ?
                ");

                $stmtActualizarPoblacion->execute([
                    $fechaInicio,
                    $this->valorNulo(
                        $datos['observacion_poblacion'] ?? null
                    ),
                    (int) $actual['id']
                ]);
            }

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
    | SEGURIDAD / LÍDER DE FICHA
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene el id_profesional asociado a un usuario.
     */
    public function obtenerIdProfesionalPorUsuario(
        int $idUsuario
    ): ?int {
        $sql = "
            SELECT
                pr.id

            FROM usuarios u

            INNER JOIN profesionales pr
                ON pr.id_persona = u.id_persona

            WHERE u.id = ?
              AND u.estado = 'activo'
              AND pr.estado = 'activo'

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $idUsuario
        ]);

        $id = $stmt->fetchColumn();

        return $id !== false
            ? (int) $id
            : null;
    }


    /**
     * Determina si un coordinador puede consultar o caracterizar
     * un aprendiz matriculado en una ficha que tiene asignada.
     */
    public function puedeVerComoCoordinador(
        int $idAprendiz,
        int $idProfesional
    ): bool {
        $sql = "
            SELECT 1
            FROM matriculas m
            INNER JOIN asignaciones_ficha af
                ON af.id_ficha = m.id_ficha
            WHERE m.id_aprendiz = ?
              AND af.id_profesional = ?
              AND af.tipo_asignacion = 'COORDINADOR'
              AND af.estado = 'activa'
              AND af.fecha_inicio <= CURRENT_DATE
              AND (
                    af.fecha_fin IS NULL
                    OR af.fecha_fin >= CURRENT_DATE
              )
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $idAprendiz,
            $idProfesional
        ]);

        return (bool) $stmt->fetchColumn();
    }


    /**
     * Determina si un profesional puede consultar
     * el detalle de un aprendiz como Líder de Ficha.
     *
     * El aprendiz debe estar matriculado en una ficha
     * donde el profesional tenga actualmente una asignación
     * activa como LIDER_FICHA.
     */
    public function puedeVerComoLiderFicha(
        int $idAprendiz,
        int $idProfesional
    ): bool {
        $sql = "
            SELECT 1

            FROM matriculas m

            INNER JOIN asignaciones_ficha af
                ON af.id_ficha = m.id_ficha

            WHERE m.id_aprendiz = ?

              AND af.id_profesional = ?

              AND af.tipo_asignacion = 'LIDER_FICHA'

              AND af.estado = 'activa'

              AND af.fecha_inicio <= CURRENT_DATE

              AND (
                    af.fecha_fin IS NULL
                    OR af.fecha_fin >= CURRENT_DATE
              )

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $idAprendiz,
            $idProfesional
        ]);

        return (bool) $stmt->fetchColumn();
    }


    /**
     * Determina si un profesional es Líder de Ficha
     * activo de alguna ficha donde esté matriculado
     * el aprendiz.
     */
    public function puedeCaracterizarComoLiderFicha(
        int $idAprendiz,
        int $idProfesional
    ): bool {
        $sql = "
            SELECT 1

            FROM matriculas m

            INNER JOIN asignaciones_ficha af
                ON af.id_ficha = m.id_ficha

            WHERE m.id_aprendiz = ?

              AND af.id_profesional = ?

              AND af.tipo_asignacion = 'LIDER_FICHA'

              AND af.estado = 'activa'

              AND af.fecha_inicio <= CURRENT_DATE

              AND (
                    af.fecha_fin IS NULL
                    OR af.fecha_fin >= CURRENT_DATE
              )

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $idAprendiz,
            $idProfesional
        ]);

        return (bool) $stmt->fetchColumn();
    }


    /**
     * Obtiene las fichas donde un profesional es
     * Líder de Ficha activo.
     */
    public function obtenerFichasComoLider(
        int $idProfesional
    ): array {
        $sql = "
            SELECT
                f.id,
                f.numero_ficha,
                f.id_programa,
                f.estado,
                af.fecha_inicio,
                af.fecha_fin

            FROM asignaciones_ficha af

            INNER JOIN fichas f
                ON f.id = af.id_ficha

            WHERE af.id_profesional = ?

              AND af.tipo_asignacion = 'LIDER_FICHA'

              AND af.estado = 'activa'

              AND af.fecha_inicio <= CURRENT_DATE

              AND (
                    af.fecha_fin IS NULL
                    OR af.fecha_fin >= CURRENT_DATE
              )

            ORDER BY
                f.numero_ficha ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $idProfesional
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Devuelve los aprendices pertenecientes a las fichas
     * donde el profesional es Líder de Ficha.
     *
     * Este método también podrá utilizarse para
     * controlar el estado de actualización por ficha.
     */
    public function listarAprendicesComoLider(
        int $idProfesional,
        string $buscar = ''
    ): array {
        $sql = "
            SELECT DISTINCT
                a.id,
                a.id_persona,

                p.tipo_documento,
                p.documento,
                p.nombres,
                p.apellidos,
                p.telefono,
                p.correo,

                f.id AS id_ficha,
                f.numero_ficha

            FROM aprendices a

            INNER JOIN personas p
                ON p.id = a.id_persona

            INNER JOIN matriculas m
                ON m.id_aprendiz = a.id

            INNER JOIN fichas f
                ON f.id = m.id_ficha

            INNER JOIN asignaciones_ficha af
                ON af.id_ficha = f.id

            WHERE af.id_profesional = ?

              AND af.tipo_asignacion = 'LIDER_FICHA'

              AND af.estado = 'activa'

              AND af.fecha_inicio <= CURRENT_DATE

              AND (
                    af.fecha_fin IS NULL
                    OR af.fecha_fin >= CURRENT_DATE
              )
        ";

        $params = [
            $idProfesional
        ];

        if ($buscar !== '') {

            $sql .= "
                AND (
                    p.documento LIKE ?
                    OR p.nombres LIKE ?
                    OR p.apellidos LIKE ?
                    OR f.numero_ficha LIKE ?
                )
            ";

            $termino = '%' . $buscar . '%';

            $params[] = $termino;
            $params[] = $termino;
            $params[] = $termino;
            $params[] = $termino;
        }

        $sql .= "
            ORDER BY
                f.numero_ficha ASC,
                p.apellidos ASC,
                p.nombres ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZACIÓN PÚBLICA POR FICHA
    |--------------------------------------------------------------------------
    |
    | Estos métodos pertenecen exclusivamente al nuevo proceso
    | mediante un enlace público único por ficha.
    |
    */


    /**
     * Obtiene una ficha mediante su número de ficha.
     */
    public function obtenerFichaPorNumero(
        string $numeroFicha
    ): ?array {
        $sql = "
            SELECT
                f.id,
                f.numero_ficha,
                f.id_programa,
                f.estado,

                pr.codigo AS codigo_programa,
                pr.nombre AS nombre_programa,
                pr.nivel

            FROM fichas f

            INNER JOIN programas pr
                ON pr.id = f.id_programa

            WHERE f.numero_ficha = ?

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            trim($numeroFicha)
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }


    /**
     * Obtiene una ficha por su id.
     */
    public function obtenerFichaPorId(
        int $idFicha
    ): ?array {
        $sql = "
            SELECT
                f.id,
                f.numero_ficha,
                f.id_programa,
                f.estado,

                pr.codigo AS codigo_programa,
                pr.nombre AS nombre_programa,
                pr.nivel

            FROM fichas f

            INNER JOIN programas pr
                ON pr.id = f.id_programa

            WHERE f.id = ?

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $idFicha
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }


    /**
     * Obtiene o crea el enlace público de una ficha.
     *
     * Existe un único enlace activo por ficha.
     */
    public function obtenerOcrearEnlaceFicha(
        int $idFicha
    ): array {
        $stmt = $this->db->prepare("
            SELECT
                id,
                id_ficha,
                token,
                estado,
                fecha_creacion,
                fecha_expiracion
            FROM enlaces_actualizacion_ficha
            WHERE id_ficha = ?
              AND estado = 'activo'
            LIMIT 1
        ");

        $stmt->execute([
            $idFicha
        ]);

        $existente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            return $existente;
        }

        $token = bin2hex(
            random_bytes(32)
        );

        $stmtInsert = $this->db->prepare("
            INSERT INTO enlaces_actualizacion_ficha (
                id_ficha,
                token,
                estado
            ) VALUES (
                ?,
                ?,
                'activo'
            )
        ");

        $stmtInsert->execute([
            $idFicha,
            $token
        ]);

        $id = (int) $this->db->lastInsertId();

        $stmtNuevo = $this->db->prepare("
            SELECT
                id,
                id_ficha,
                token,
                estado,
                fecha_creacion,
                fecha_expiracion
            FROM enlaces_actualizacion_ficha
            WHERE id = ?
            LIMIT 1
        ");

        $stmtNuevo->execute([
            $id
        ]);

        $resultado = $stmtNuevo->fetch(PDO::FETCH_ASSOC);

        if (!$resultado) {
            throw new RuntimeException(
                'No fue posible crear el enlace de actualización.'
            );
        }

        return $resultado;
    }


    /**
     * Obtiene un enlace público mediante su token.
     *
     * La validación del estado y de la fecha de expiración
     * se realiza directamente en la consulta.
     */
    public function obtenerEnlaceFichaPorToken(
        string $token
    ): ?array {
        $sql = "
            SELECT
                eaf.id,
                eaf.id_ficha,
                eaf.token,
                eaf.estado,
                eaf.fecha_creacion,
                eaf.fecha_expiracion,

                f.numero_ficha,
                f.id_programa,
                f.estado AS estado_ficha,

                pr.codigo AS codigo_programa,
                pr.nombre AS nombre_programa,
                pr.nivel

            FROM enlaces_actualizacion_ficha eaf

            INNER JOIN fichas f
                ON f.id = eaf.id_ficha

            INNER JOIN programas pr
                ON pr.id = f.id_programa

            WHERE eaf.token = ?

              AND eaf.estado = 'activo'

              AND (
                    eaf.fecha_expiracion IS NULL
                    OR eaf.fecha_expiracion >= CURRENT_TIMESTAMP
              )

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            trim($token)
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }


    /**
     * Valida un aprendiz mediante documento y,
     * cuando exista en la base de datos, fecha de nacimiento.
     *
     * MUY IMPORTANTE:
     *
     * La pertenencia a la ficha siempre se valida.
     *
     * La fecha de nacimiento solamente se exige
     * cuando ya está registrada en SITA.
     */
    public function validarAprendizParaFicha(
        int $idFicha,
        string $documento,
        ?string $fechaNacimiento
    ): ?array {
        $documento = trim($documento);

        if ($idFicha <= 0 || $documento === '') {
            return null;
        }

        $fecha = $fechaNacimiento !== null
            ? trim($fechaNacimiento)
            : null;

        if ($fecha === '') {
            $fecha = null;
        }

        $sql = "
            SELECT
                a.id,
                a.id_persona,
                a.id_eps,

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

                a.contacto_emergencia_nombre,
                a.contacto_emergencia_telefono,
                a.contacto_emergencia_parentesco,

                f.id AS id_ficha,
                f.numero_ficha,

                e.nombre AS nombre_eps,

                ap.id_tipo_poblacion,
                tp.nombre AS tipo_poblacion,
                ap.fecha_inicio AS fecha_inicio_poblacion,
                ap.observacion AS observacion_poblacion

            FROM aprendices a

            INNER JOIN personas p
                ON p.id = a.id_persona

            INNER JOIN matriculas m
                ON m.id_aprendiz = a.id

            INNER JOIN fichas f
                ON f.id = m.id_ficha

            LEFT JOIN eps e
                ON e.id = a.id_eps

            LEFT JOIN aprendiz_poblacion ap
                ON ap.id_aprendiz = a.id
                AND ap.fecha_fin IS NULL

            LEFT JOIN tipos_poblacion tp
                ON tp.id = ap.id_tipo_poblacion

            WHERE f.id = ?

              AND TRIM(p.documento) = ?

              AND (
                    ? IS NULL
                    OR p.fecha_nacimiento = ?
              )

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $idFicha,
            $documento,
            $fecha,
            $fecha
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /**
     * Obtiene el estado de actualización de un aprendiz
     * para una ficha determinada.
     */
    public function obtenerEstadoActualizacion(
        int $idAprendiz,
        int $idFicha
    ): ?array {
        $sql = "
            SELECT
                id,
                id_aprendiz,
                id_ficha,
                estado,
                fecha_actualizacion,
                origen,
                campos_actualizados
            FROM actualizaciones_caracterizacion
            WHERE id_aprendiz = ?
              AND id_ficha = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $idAprendiz,
            $idFicha
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }


    /**
     * Registra o actualiza el estado del formulario público.
     *
     * Se utiliza después de guardar correctamente
     * la información del aprendiz.
     */
    public function registrarActualizacionPublica(
        int $idAprendiz,
        int $idFicha,
        array $camposActualizados = []
    ): bool {
        $campos = [];

        foreach ($camposActualizados as $campo) {

            $campo = trim((string) $campo);

            if ($campo !== '') {
                $campos[] = $campo;
            }
        }

        $camposTexto = !empty($campos)
            ? implode(', ', array_unique($campos))
            : null;

        $sql = "
            INSERT INTO actualizaciones_caracterizacion (
                id_aprendiz,
                id_ficha,
                estado,
                fecha_actualizacion,
                origen,
                campos_actualizados
            ) VALUES (
                ?,
                ?,
                'actualizado',
                CURRENT_TIMESTAMP,
                'Formulario de actualización',
                ?
            )

            ON DUPLICATE KEY UPDATE
                estado = 'actualizado',
                fecha_actualizacion = CURRENT_TIMESTAMP,
                origen = 'Formulario de actualización',
                campos_actualizados = VALUES(campos_actualizados)
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $idAprendiz,
            $idFicha,
            $camposTexto
        ]);
    }


    /**
     * Crea los registros de estado pendiente para todos
     * los aprendices matriculados en una ficha.
     *
     * Esto permitirá que el Líder vea desde el comienzo
     * quién está pendiente y quién ya actualizó.
     */
    public function inicializarEstadosActualizacionFicha(
        int $idFicha
    ): bool {
        $sql = "
            INSERT INTO actualizaciones_caracterizacion (
                id_aprendiz,
                id_ficha,
                estado
            )

            SELECT DISTINCT
                m.id_aprendiz,
                m.id_ficha,
                'pendiente'

            FROM matriculas m

            WHERE m.id_ficha = ?

            ON DUPLICATE KEY UPDATE
                id_aprendiz = VALUES(id_aprendiz)
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $idFicha
        ]);
    }


    /**
     * Obtiene el resumen general de actualización
     * de una ficha.
     */
    public function obtenerResumenActualizacionFicha(
        int $idFicha
    ): array {
        $sql = "
            SELECT
                COUNT(DISTINCT m.id_aprendiz) AS total,

                COUNT(
                    DISTINCT CASE
                        WHEN ac.estado = 'actualizado'
                        THEN m.id_aprendiz
                    END
                ) AS actualizados,

                COUNT(
                    DISTINCT CASE
                        WHEN ac.estado IS NULL
                          OR ac.estado = 'pendiente'
                        THEN m.id_aprendiz
                    END
                ) AS pendientes

            FROM matriculas m

            LEFT JOIN actualizaciones_caracterizacion ac
                ON ac.id_aprendiz = m.id_aprendiz
                AND ac.id_ficha = m.id_ficha

            WHERE m.id_ficha = ?
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $idFicha
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resultado) {
            return [
                'total' => 0,
                'actualizados' => 0,
                'pendientes' => 0,
                'porcentaje' => 0
            ];
        }

        $total = (int) $resultado['total'];

        $actualizados = (int) $resultado['actualizados'];

        $pendientes = (int) $resultado['pendientes'];

        $porcentaje = $total > 0
            ? round(
                ($actualizados / $total) * 100,
                2
            )
            : 0;

        return [
            'total' => $total,
            'actualizados' => $actualizados,
            'pendientes' => $pendientes,
            'porcentaje' => $porcentaje
        ];
    }


    /**
     * Obtiene el listado de aprendices de una ficha
     * junto con su estado de actualización.
     */
    public function listarEstadoActualizacionFicha(
        int $idFicha,
        string $buscar = ''
    ): array {
        $sql = "
            SELECT
                a.id AS id_aprendiz,

                p.tipo_documento,
                p.documento,
                p.nombres,
                p.apellidos,

                m.id_ficha,

                f.numero_ficha,

                COALESCE(
                    ac.estado,
                    'pendiente'
                ) AS estado_actualizacion,

                ac.fecha_actualizacion,
                ac.campos_actualizados

            FROM matriculas m

            INNER JOIN aprendices a
                ON a.id = m.id_aprendiz

            INNER JOIN personas p
                ON p.id = a.id_persona

            INNER JOIN fichas f
                ON f.id = m.id_ficha

            LEFT JOIN actualizaciones_caracterizacion ac
                ON ac.id_aprendiz = a.id
                AND ac.id_ficha = m.id_ficha

            WHERE m.id_ficha = ?
        ";

        $params = [
            $idFicha
        ];

        if ($buscar !== '') {

            $sql .= "
                AND (
                    p.documento LIKE ?
                    OR p.nombres LIKE ?
                    OR p.apellidos LIKE ?
                )
            ";

            $termino = '%' . $buscar . '%';

            $params[] = $termino;
            $params[] = $termino;
            $params[] = $termino;
        }

        $sql .= "
            ORDER BY
                CASE
                    WHEN COALESCE(
                        ac.estado,
                        'pendiente'
                    ) = 'pendiente'
                    THEN 0
                    ELSE 1
                END,

                p.apellidos ASC,
                p.nombres ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | UTILIDADES
    |--------------------------------------------------------------------------
    */

    private function valorNulo(
        mixed $valor
    ): ?string {
        if ($valor === null) {
            return null;
        }

        $valor = trim((string) $valor);

        return $valor === ''
            ? null
            : $valor;
    }
}

