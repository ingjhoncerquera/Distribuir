<?php

declare(strict_types=1);


class ImportacionModel
{
    private PDO $db;

    /**
     * Constructor.
     *
     * Utiliza la clase Database existente del proyecto.
     */
    public function __construct()
    {
        $database = new Database();

        $this->db = $database->conectar();
    }

    /**
     * Devuelve la conexión PDO.
     *
     * Se mantiene privada la propiedad de conexión, pero este
     * método permite utilizarla desde métodos controlados del modelo
     * si posteriormente se requiere una transacción.
     */
    public function getConnection(): PDO
    {
        return $this->db;
    }

    /* ============================================================
     * PROGRAMAS
     * ============================================================
     */

    /**
     * Busca un programa por código.
     *
     * @param string $codigo
     * @return array|null
     */
    public function buscarProgramaPorCodigo(string $codigo): ?array
    {
        $sql = "
            SELECT
                id,
                codigo,
                nombre,
                nivel,
                duracion_meses,
                estado
            FROM programas
            WHERE codigo = :codigo
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':codigo' => trim($codigo),
        ]);

        $resultado = $stmt->fetch();

        return $resultado !== false ? $resultado : null;
    }

    /* ============================================================
     * FICHAS
     * ============================================================
     */

    /**
     * Busca una ficha por número de ficha.
     *
     * El número de ficha es único en la base de datos.
     *
     * @param string $numeroFicha
     * @return array|null
     */
    public function buscarFichaPorNumero(string $numeroFicha): ?array
    {
        $sql = "
            SELECT
                f.id,
                f.numero_ficha,
                f.id_programa,
                f.version_programa,
                f.modalidad,
                f.regional,
                f.centro,
                f.id_jornada,
                f.fecha_inicio,
                f.fecha_fin,
                f.estado
            FROM fichas f
            WHERE f.numero_ficha = :numero_ficha
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':numero_ficha' => trim($numeroFicha),
        ]);

        $resultado = $stmt->fetch();

        return $resultado !== false ? $resultado : null;
    }

    /* ============================================================
     * PERSONAS
     * ============================================================
     */

    /**
     * Busca una persona por tipo y número de documento.
     *
     * Es la forma correcta de identificar una persona.
     * No se utiliza el nombre como identificador.
     *
     * @param string $tipoDocumento
     * @param string $documento
     * @return array|null
     */
    public function buscarPersonaPorDocumento(
        string $tipoDocumento,
        string $documento
    ): ?array {
        $sql = "
            SELECT
                id,
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
            FROM personas
            WHERE tipo_documento = :tipo_documento
              AND documento = :documento
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':tipo_documento' => trim($tipoDocumento),
            ':documento'      => trim($documento),
        ]);

        $resultado = $stmt->fetch();

        return $resultado !== false ? $resultado : null;
    }

    /* ============================================================
     * APRENDICES
     * ============================================================
     */

    /**
     * Busca un aprendiz relacionado con una persona.
     *
     * @param int $idPersona
     * @return array|null
     */
    public function buscarAprendizPorPersona(int $idPersona): ?array
    {
        $sql = "
            SELECT
                id,
                id_persona,               
                contacto_emergencia_nombre,
                contacto_emergencia_telefono,
                contacto_emergencia_parentesco,
                estado
            FROM aprendices
            WHERE id_persona = :id_persona
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_persona' => $idPersona,
        ]);

        $resultado = $stmt->fetch();

        return $resultado !== false ? $resultado : null;
    }

    /**
     * Busca un aprendiz directamente por código de aprendiz.
     *
     * @param string $codigoAprendiz
     * @return array|null
     */
    

    /* ============================================================
     * COMPETENCIAS
     * ============================================================
     */

    /**
     * Busca una competencia por programa y código.
     *
     * La combinación id_programa + codigo es única.
     *
     * @param int $idPrograma
     * @param string $codigo
     * @return array|null
     */
    public function buscarCompetencia(
        int $idPrograma,
        string $codigo
    ): ?array {
        $sql = "
            SELECT
                id,
                id_programa,
                codigo,
                nombre,
                descripcion,
                estado
            FROM competencias
            WHERE id_programa = :id_programa
              AND codigo = :codigo
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_programa' => $idPrograma,
            ':codigo'      => trim($codigo),
        ]);

        $resultado = $stmt->fetch();

        return $resultado !== false ? $resultado : null;
    }

    /* ============================================================
     * RAP
     * ============================================================
     */

    /**
     * Busca un RAP por competencia y código.
     *
     * La combinación id_competencia + codigo es única.
     *
     * @param int $idCompetencia
     * @param string $codigo
     * @return array|null
     */
    public function buscarRap(
        int $idCompetencia,
        string $codigo
    ): ?array {
        $sql = "
            SELECT
                id,
                id_competencia,
                codigo,
                nombre,
                descripcion,
                horas,
                estado
            FROM rap
            WHERE id_competencia = :id_competencia
              AND codigo = :codigo
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_competencia' => $idCompetencia,
            ':codigo'         => trim($codigo),
        ]);

        $resultado = $stmt->fetch();

        return $resultado !== false ? $resultado : null;
    }

    /* ============================================================
     * PROFESIONALES / FUNCIONARIOS
     * ============================================================
     */

    /**
     * Busca un profesional asociado a una persona.
     *
     * @param int $idPersona
     * @return array|null
     */
    public function buscarProfesionalPorPersona(int $idPersona): ?array
    {
        $sql = "
            SELECT
                id,
                id_persona,
                tipo_profesional,
                registro_profesional,
                especialidad,
                estado
            FROM profesionales
            WHERE id_persona = :id_persona
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_persona' => $idPersona,
        ]);

        $resultado = $stmt->fetch();

        return $resultado !== false ? $resultado : null;
    }

    /* ============================================================
     * MATRÍCULAS
     * ============================================================
     */

    /**
     * Busca una matrícula existente para un aprendiz y una ficha.
     *
     * IMPORTANTE:
     * La tabla no tiene actualmente una llave UNIQUE para esta
     * combinación. Por eso la importación debe consultar antes
     * de crear una nueva matrícula.
     *
     * @param int $idAprendiz
     * @param int $idFicha
     * @return array|null
     */
    public function buscarMatricula(
        int $idAprendiz,
        int $idFicha
    ): ?array {
        $sql = "
            SELECT
                id,
                id_aprendiz,
                id_ficha,
                fecha_inicio,
                fecha_fin,
                estado,
                observaciones,
                created_at
            FROM matriculas
            WHERE id_aprendiz = :id_aprendiz
              AND id_ficha = :id_ficha
            ORDER BY id ASC
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_aprendiz' => $idAprendiz,
            ':id_ficha'    => $idFicha,
        ]);

        $resultado = $stmt->fetch();

        return $resultado !== false ? $resultado : null;
    }

    /* ============================================================
     * RESULTADOS
     * ============================================================
     */

    /**
     * Busca resultados existentes de un aprendiz para un RAP
     * dentro de una ficha.
     *
     * No se limita a un único resultado porque la tabla
     * resultados_aprendiz actualmente permite históricos.
     *
     * Esto es importante para preservar resultados de diferentes
     * fechas/años.
     *
     * @param int $idAprendiz
     * @param int $idFicha
     * @param int $idRap
     * @return array
     */
    public function buscarResultadosPorAprendizRapFicha(
        int $idAprendiz,
        int $idFicha,
        int $idRap
    ): array {
        $sql = "
            SELECT
                id,
                id_aprendiz,
                id_ficha,
                id_rap,
                id_instructor,
                fecha_evaluacion,
                fecha_hora_evaluacion,
                juicio,
                juicio_sena,
                calificacion,
                observaciones,
                created_at
            FROM resultados_aprendiz
            WHERE id_aprendiz = :id_aprendiz
              AND id_ficha = :id_ficha
              AND id_rap = :id_rap
            ORDER BY
                fecha_evaluacion ASC,
                fecha_hora_evaluacion ASC,
                id ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_aprendiz' => $idAprendiz,
            ':id_ficha'    => $idFicha,
            ':id_rap'      => $idRap,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Busca un resultado potencialmente igual al que viene
     * desde el Excel.
     *
     * Esta consulta NO decide todavía si debe actualizarse.
     * Solamente devuelve los resultados existentes para que el
     * controlador/modelo pueda compararlos de manera segura.
     *
     * @param int $idAprendiz
     * @param int $idFicha
     * @param int $idRap
     * @param string|null $fechaEvaluacion
     * @param string|null $juicioSena
     * @return array
     */
    public function buscarResultadosCoincidentes(
        int $idAprendiz,
        int $idFicha,
        int $idRap,
        ?string $fechaEvaluacion,
        ?string $juicioSena
    ): array {
        $sql = "
            SELECT
                id,
                id_aprendiz,
                id_ficha,
                id_rap,
                id_instructor,
                fecha_evaluacion,
                fecha_hora_evaluacion,
                juicio,
                juicio_sena,
                calificacion,
                observaciones,
                created_at
            FROM resultados_aprendiz
            WHERE id_aprendiz = :id_aprendiz
              AND id_ficha = :id_ficha
              AND id_rap = :id_rap
              AND (
                    (
                        fecha_evaluacion = :fecha_evaluacion
                        AND :fecha_evaluacion IS NOT NULL
                    )
                    OR
                    (
                        fecha_evaluacion IS NULL
                        AND :fecha_evaluacion IS NULL
                    )
                  )
              AND (
                    juicio_sena = :juicio_sena
                    OR (
                        juicio_sena IS NULL
                        AND :juicio_sena IS NULL
                    )
                  )
            ORDER BY id ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_aprendiz'     => $idAprendiz,
            ':id_ficha'        => $idFicha,
            ':id_rap'          => $idRap,
            ':fecha_evaluacion'=> $fechaEvaluacion,
            ':juicio_sena'     => $juicioSena,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Busca todos los resultados de una ficha.
     *
     * Útil para obtener un panorama general antes de una
     * importación grande.
     *
     * @param int $idFicha
     * @return array
     */
    public function obtenerResultadosDeFicha(int $idFicha): array
    {
        $sql = "
            SELECT
                ra.id,
                ra.id_aprendiz,
                ra.id_ficha,
                ra.id_rap,
                ra.id_instructor,
                ra.fecha_evaluacion,
                ra.fecha_hora_evaluacion,
                ra.juicio,
                ra.juicio_sena,
                ra.calificacion,
                ra.observaciones,
                ra.created_at
            FROM resultados_aprendiz ra
            WHERE ra.id_ficha = :id_ficha
            ORDER BY
                ra.fecha_evaluacion ASC,
                ra.fecha_hora_evaluacion ASC,
                ra.id ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_ficha' => $idFicha,
        ]);

        return $stmt->fetchAll();
    }

    /* ============================================================
     * MÉTODOS AUXILIARES DE EXISTENCIA
     * ============================================================
     */

    /**
     * Determina si existe una persona.
     */
    public function existePersona(
        string $tipoDocumento,
        string $documento
    ): bool {
        return $this->buscarPersonaPorDocumento(
            $tipoDocumento,
            $documento
        ) !== null;
    }

    /**
     * Determina si existe un programa.
     */
    public function existePrograma(string $codigo): bool
    {
        return $this->buscarProgramaPorCodigo($codigo) !== null;
    }

    /**
     * Determina si existe una ficha.
     */
    public function existeFicha(string $numeroFicha): bool
    {
        return $this->buscarFichaPorNumero($numeroFicha) !== null;
    }

    /**
     * Determina si existe un aprendiz para una persona.
     */
    public function existeAprendiz(int $idPersona): bool
    {
        return $this->buscarAprendizPorPersona($idPersona) !== null;
    }

    /**
     * Determina si existe una competencia.
     */
    public function existeCompetencia(
        int $idPrograma,
        string $codigo
    ): bool {
        return $this->buscarCompetencia(
            $idPrograma,
            $codigo
        ) !== null;
    }

    /**
     * Determina si existe un RAP.
     */
    public function existeRap(
        int $idCompetencia,
        string $codigo
    ): bool {
        return $this->buscarRap(
            $idCompetencia,
            $codigo
        ) !== null;
    }

    /**
     * Determina si existe un profesional para una persona.
     */
    public function existeProfesional(int $idPersona): bool
    {
        return $this->buscarProfesionalPorPersona($idPersona) !== null;
    }

    /**
     * Determina si existe una matrícula.
     */
    public function existeMatricula(
        int $idAprendiz,
        int $idFicha
    ): bool {
        return $this->buscarMatricula(
            $idAprendiz,
            $idFicha
        ) !== null;
    }

    /* ============================================================
     * UTILIDADES PARA COMPARACIÓN DE RESULTADOS
     * ============================================================
     */

    /**
     * Compara dos valores considerando NULL y cadenas vacías
     * como equivalentes cuando corresponda.
     *
     * Se utiliza para detectar cambios reales sin considerar
     * diferencias irrelevantes de formato.
     */
    public function valoresIguales(
        mixed $valorA,
        mixed $valorB
    ): bool {
        $a = $this->normalizarValorComparacion($valorA);
        $b = $this->normalizarValorComparacion($valorB);

        return $a === $b;
    }

    /**
     * Normaliza valores antes de compararlos.
     */
    private function normalizarValorComparacion(
        mixed $valor
    ): mixed {
        if ($valor === null) {
            return null;
        }

        if (is_string($valor)) {
            $valor = trim($valor);

            if ($valor === '') {
                return null;
            }

            return mb_strtoupper(
                $valor,
                'UTF-8'
            );
        }

        if (is_float($valor)) {
            return number_format(
                $valor,
                2,
                '.',
                ''
            );
        }

        if (is_int($valor)) {
            return (string) $valor;
        }

        return $valor;
    }

    /**
     * Compara los campos relevantes de un resultado Excel
     * contra un resultado existente.
     *
     * Devuelve true cuando no se detectan diferencias.
     *
     * @param array $excel
     * @param array $existente
     * @return bool
     */
    public function resultadoEsIgual(
        array $excel,
        array $existente
    ): bool {
        $campos = [
            'id_instructor',
            'fecha_evaluacion',
            'fecha_hora_evaluacion',
            'juicio',
            'juicio_sena',
            'calificacion',
            'observaciones',
        ];

        foreach ($campos as $campo) {

            $valorExcel = $excel[$campo] ?? null;
            $valorBD    = $existente[$campo] ?? null;

            if (
                !$this->valoresIguales(
                    $valorExcel,
                    $valorBD
                )
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determina el año de vigencia a partir de la fecha
     * real de evaluación.
     *
     * IMPORTANTE:
     * No utiliza la fecha del reporte como sustituto.
     *
     * @param string|null $fechaEvaluacion
     * @return int|null
     */
    public function obtenerVigenciaDesdeFecha(
        ?string $fechaEvaluacion
    ): ?int {
        if (
            $fechaEvaluacion === null
            || trim($fechaEvaluacion) === ''
        ) {
            return null;
        }

        $timestamp = strtotime($fechaEvaluacion);

        if ($timestamp === false) {
            return null;
        }

        return (int) date(
            'Y',
            $timestamp
        );
    }

    /* ============================================================
     * RESUMEN DE UNA FICHA
     * ============================================================
     */

    /**
     * Obtiene un resumen de resultados existentes de una ficha
     * agrupados por año.
     *
     * Esto permitirá comprobar posteriormente que los resultados
     * históricos de 2025, 2026, 2027, etc., se conservan.
     *
     * Los resultados sin fecha quedan agrupados como
     * "sin_fecha".
     *
     * @param int $idFicha
     * @return array
     */
    public function resumenResultadosPorAnio(
        int $idFicha
    ): array {
        $sql = "
            SELECT
                CASE
                    WHEN fecha_evaluacion IS NULL
                        THEN 'sin_fecha'
                    ELSE CAST(
                        YEAR(fecha_evaluacion)
                        AS CHAR
                    )
                END AS vigencia,
                COUNT(*) AS total
            FROM resultados_aprendiz
            WHERE id_ficha = :id_ficha
            GROUP BY
                CASE
                    WHEN fecha_evaluacion IS NULL
                        THEN 'sin_fecha'
                    ELSE CAST(
                        YEAR(fecha_evaluacion)
                        AS CHAR
                    )
                END
            ORDER BY
                vigencia ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_ficha' => $idFicha,
        ]);

        return $stmt->fetchAll();
    }

    /* ============================================================
     * ESCRITURA (IMPORTACIÓN)
     * ============================================================
     * Estos métodos deben ejecutarse dentro de una transacción
     * controlada por el controlador (beginTransaction / commit /
     * rollBack). Todos usan sentencias preparadas.
     * ============================================================
     */

    public function insertarPrograma(
        string $codigo,
        string $nombre
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO programas (codigo, nombre, estado)
            VALUES (:codigo, :nombre, 'activo')
        ");

        $stmt->execute([
            ':codigo' => trim($codigo),
            ':nombre' => trim($nombre) !== '' ? trim($nombre) : trim($codigo),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function insertarFicha(array $d): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO fichas (
                numero_ficha, id_programa, version_programa, modalidad,
                regional, centro, fecha_inicio, fecha_fin, estado
            ) VALUES (
                :numero_ficha, :id_programa, :version_programa, :modalidad,
                :regional, :centro, :fecha_inicio, :fecha_fin, :estado
            )
        ");

        $stmt->execute([
            ':numero_ficha'     => $d['numero_ficha'],
            ':id_programa'      => $d['id_programa'],
            ':version_programa' => $d['version_programa'] ?: null,
            ':modalidad'        => $d['modalidad'] ?: null,
            ':regional'         => $d['regional'] ?: null,
            ':centro'           => $d['centro'] ?: null,
            ':fecha_inicio'     => $d['fecha_inicio'],
            ':fecha_fin'        => $d['fecha_fin'] ?: null,
            ':estado'           => $d['estado'] ?? 'activa',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function insertarPersona(
        string $tipoDocumento,
        string $documento,
        string $nombres,
        string $apellidos
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO personas (
                tipo_documento, documento, nombres, apellidos, estado
            ) VALUES (
                :tipo_documento, :documento, :nombres, :apellidos, 'activo'
            )
        ");

        $stmt->execute([
            ':tipo_documento' => trim($tipoDocumento),
            ':documento'      => trim($documento),
            ':nombres'        => trim($nombres) !== '' ? trim($nombres) : '-',
            ':apellidos'      => trim($apellidos) !== '' ? trim($apellidos) : '-',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function insertarAprendiz(
    int $idPersona,
    string $estado
): int {
    $stmt = $this->db->prepare("
        INSERT INTO aprendices (
            id_persona,
            estado
        )
        VALUES (
            :id_persona,
            :estado
        )
    ");

    $stmt->execute([
        ':id_persona' => $idPersona,
        ':estado' => trim($estado),
    ]);

    return (int) $this->db->lastInsertId();
}


public function actualizarEstadoAprendiz(
    int $idAprendiz,
    string $estado
): void {
    $stmt = $this->db->prepare("
        UPDATE aprendices
        SET estado = :estado
        WHERE id = :id
    ");

    $stmt->execute([
        ':estado' => trim($estado),
        ':id' => $idAprendiz,
    ]);
}

    public function insertarProfesional(int $idPersona): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO profesionales (id_persona, tipo_profesional, estado)
            VALUES (:id_persona, 'Instructor', 'activo')
        ");

        $stmt->execute([':id_persona' => $idPersona]);

        return (int) $this->db->lastInsertId();
    }

    public function insertarMatricula(
        int $idAprendiz,
        int $idFicha,
        string $fechaInicio,
        ?string $fechaFin
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO matriculas (
                id_aprendiz, id_ficha, fecha_inicio, fecha_fin, estado
            ) VALUES (
                :id_aprendiz, :id_ficha, :fecha_inicio, :fecha_fin, 'activa'
            )
        ");

        $stmt->execute([
            ':id_aprendiz' => $idAprendiz,
            ':id_ficha'    => $idFicha,
            ':fecha_inicio'=> $fechaInicio,
            ':fecha_fin'   => $fechaFin ?: null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function insertarCompetencia(
        int $idPrograma,
        string $codigo,
        string $nombre
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO competencias (id_programa, codigo, nombre, estado)
            VALUES (:id_programa, :codigo, :nombre, 'activo')
        ");

        $stmt->execute([
            ':id_programa' => $idPrograma,
            ':codigo'      => trim($codigo),
            ':nombre'      => trim($nombre) !== '' ? trim($nombre) : trim($codigo),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function insertarRap(
        int $idCompetencia,
        string $codigo,
        string $nombre
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO rap (id_competencia, codigo, nombre, estado)
            VALUES (:id_competencia, :codigo, :nombre, 'activo')
        ");

        $stmt->execute([
            ':id_competencia' => $idCompetencia,
            ':codigo'         => trim($codigo),
            ':nombre'         => trim($nombre) !== '' ? trim($nombre) : trim($codigo),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function insertarResultado(array $d): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO resultados_aprendiz (
                id_aprendiz, id_ficha, id_rap, id_instructor,
                fecha_evaluacion, fecha_hora_evaluacion,
                juicio, juicio_sena
            ) VALUES (
                :id_aprendiz, :id_ficha, :id_rap, :id_instructor,
                :fecha_evaluacion, :fecha_hora_evaluacion,
                :juicio, :juicio_sena
            )
        ");

        $stmt->execute([
            ':id_aprendiz'           => $d['id_aprendiz'],
            ':id_ficha'              => $d['id_ficha'],
            ':id_rap'                => $d['id_rap'],
            ':id_instructor'         => $d['id_instructor'],
            ':fecha_evaluacion'      => $d['fecha_evaluacion'],
            ':fecha_hora_evaluacion' => $d['fecha_hora_evaluacion'],
            ':juicio'                => $d['juicio'],
            ':juicio_sena'           => $d['juicio_sena'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function actualizarResultado(int $id, array $d): void
    {
        $stmt = $this->db->prepare("
            UPDATE resultados_aprendiz SET
                id_instructor         = :id_instructor,
                fecha_evaluacion      = :fecha_evaluacion,
                fecha_hora_evaluacion = :fecha_hora_evaluacion,
                juicio                = :juicio,
                juicio_sena           = :juicio_sena
            WHERE id = :id
        ");

        $stmt->execute([
            ':id_instructor'         => $d['id_instructor'],
            ':fecha_evaluacion'      => $d['fecha_evaluacion'],
            ':fecha_hora_evaluacion' => $d['fecha_hora_evaluacion'],
            ':juicio'                => $d['juicio'],
            ':juicio_sena'           => $d['juicio_sena'],
            ':id'                    => $id,
        ]);
    }
}
