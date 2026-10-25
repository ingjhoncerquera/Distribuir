<?php

declare(strict_types=1);

class FichaModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->conectar();
    }

    /*
    |--------------------------------------------------------------------------
    | FILTRO DE ACCESO PARA LÍDER DE FICHA Y COORDINADOR
    |--------------------------------------------------------------------------
    |
    | Administrador: consulta todas las fichas cuando el filtro es null.
    | Líder de Ficha: consulta sus fichas asignadas como LIDER_FICHA.
    | Coordinador: consulta sus fichas asignadas como COORDINADOR.
    |
    | Se conserva la validación del estado y la vigencia de la asignación.
    |
    */

    private function agregarFiltroLiderFicha(
        string &$sql,
        array &$params,
        ?int $usuarioIdFiltro,
        string $tipoAsignacion = 'LIDER_FICHA'
    ): void {
        if ($usuarioIdFiltro === null) {
            return;
        }

        /*
         * Se determina el tipo según el rol autenticado para evitar que
         * el controlador aplique por defecto LIDER_FICHA al coordinador.
         */
        if (class_exists('Auth')) {
            if (Auth::hasRole('Coordinador')) {
                $tipoAsignacion = 'COORDINADOR';
            } elseif (Auth::hasRole('Lider de Ficha')) {
                $tipoAsignacion = 'LIDER_FICHA';
            }
        }

        if (!in_array(
            $tipoAsignacion,
            [
                'LIDER_FICHA',
                'COORDINADOR'
            ],
            true
        )) {
            throw new InvalidArgumentException(
                'El tipo de asignación para filtrar fichas no es válido.'
            );
        }

        $sql .= "
            AND EXISTS (
                SELECT 1
                FROM asignaciones_ficha af_filtro

                INNER JOIN profesionales pr_filtro
                    ON pr_filtro.id = af_filtro.id_profesional

                INNER JOIN usuarios u_filtro
                    ON u_filtro.id_persona = pr_filtro.id_persona

                WHERE af_filtro.id_ficha = f.id
                  AND u_filtro.id = ?
                  AND af_filtro.tipo_asignacion = ?
                  AND af_filtro.estado = 'activa'
                  AND af_filtro.fecha_inicio <= CURDATE()
                  AND (
                      af_filtro.fecha_fin IS NULL
                      OR af_filtro.fecha_fin >= CURDATE()
                  )
            )
        ";

        $params[] = $usuarioIdFiltro;
        $params[] = $tipoAsignacion;
    }

    /*
    |--------------------------------------------------------------------------
    | CONTAR FICHAS
    |--------------------------------------------------------------------------
    */

    public function contar(
        string $buscar = '',
        ?int $usuarioIdFiltro = null,
        string $tipoAsignacion = 'LIDER_FICHA'
    ): int {
        $sql = "
            SELECT COUNT(*)
            FROM fichas f

            INNER JOIN programas p
                ON p.id = f.id_programa

            WHERE 1 = 1
        ";

        $params = [];

        if ($buscar !== '') {
            $sql .= "
                AND (
                    f.numero_ficha LIKE ?
                    OR p.codigo LIKE ?
                    OR p.nombre LIKE ?
                    OR f.centro LIKE ?
                    OR f.regional LIKE ?
                )
            ";

            $termino = '%' . $buscar . '%';

            $params[] = $termino;
            $params[] = $termino;
            $params[] = $termino;
            $params[] = $termino;
            $params[] = $termino;
        }

        $this->agregarFiltroLiderFicha(
            $sql,
            $params,
            $usuarioIdFiltro,
            $tipoAsignacion
        );

        $stmt = $this->db->prepare($sql);

        $indice = 1;

        foreach ($params as $parametro) {
            $stmt->bindValue(
                $indice++,
                $parametro,
                is_int($parametro)
                    ? PDO::PARAM_INT
                    : PDO::PARAM_STR
            );
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /*
    |--------------------------------------------------------------------------
    | LISTAR FICHAS
    |--------------------------------------------------------------------------
    */

    public function listar(
        string $buscar = '',
        int $limite = 20,
        int $offset = 0,
        ?int $usuarioIdFiltro = null,
        string $tipoAsignacion = 'LIDER_FICHA'
    ): array {
        $limite = max(1, min($limite, 500));
        $offset = max(0, $offset);

        $sql = "
            SELECT
                f.id,
                f.numero_ficha,
                f.version_programa,
                f.modalidad,
                f.regional,
                f.centro,
                f.fecha_inicio,
                f.fecha_fin,
                f.estado,

                p.codigo AS codigo_programa,
                p.nombre AS nombre_programa,
                p.nivel,

                COUNT(DISTINCT m.id) AS cantidad_aprendices

            FROM fichas f

            INNER JOIN programas p
                ON p.id = f.id_programa

            LEFT JOIN matriculas m
                ON m.id_ficha = f.id

            WHERE 1 = 1
        ";

        $params = [];

        if ($buscar !== '') {
            $sql .= "
                AND (
                    f.numero_ficha LIKE ?
                    OR p.codigo LIKE ?
                    OR p.nombre LIKE ?
                    OR f.centro LIKE ?
                    OR f.regional LIKE ?
                )
            ";

            $termino = '%' . $buscar . '%';

            $params[] = $termino;
            $params[] = $termino;
            $params[] = $termino;
            $params[] = $termino;
            $params[] = $termino;
        }

        $this->agregarFiltroLiderFicha(
            $sql,
            $params,
            $usuarioIdFiltro,
            $tipoAsignacion
        );

        $sql .= "
            GROUP BY
                f.id,
                f.numero_ficha,
                f.version_programa,
                f.modalidad,
                f.regional,
                f.centro,
                f.fecha_inicio,
                f.fecha_fin,
                f.estado,
                p.codigo,
                p.nombre,
                p.nivel

            ORDER BY
                f.fecha_inicio DESC,
                f.numero_ficha ASC

            LIMIT ? OFFSET ?
        ";

        $stmt = $this->db->prepare($sql);

        $indice = 1;

        foreach ($params as $parametro) {
            $stmt->bindValue(
                $indice++,
                $parametro,
                is_int($parametro)
                    ? PDO::PARAM_INT
                    : PDO::PARAM_STR
            );
        }

        $stmt->bindValue(
            $indice++,
            $limite,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            $indice,
            $offset,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | OBTENER UNA FICHA
    |--------------------------------------------------------------------------
    */

    public function obtener(
        int $id,
        ?int $usuarioIdFiltro = null,
        string $tipoAsignacion = 'LIDER_FICHA'
    ): ?array {
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
                f.estado,

                p.codigo AS codigo_programa,
                p.nombre AS nombre_programa,
                p.nivel,
                p.duracion_meses

            FROM fichas f

            INNER JOIN programas p
                ON p.id = f.id_programa

            WHERE f.id = ?
        ";

        $params = [$id];

        $this->agregarFiltroLiderFicha(
            $sql,
            $params,
            $usuarioIdFiltro,
            $tipoAsignacion
        );

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | APRENDICES DE LA FICHA
    |--------------------------------------------------------------------------
    */

    public function obtenerAprendices(
        int $idFicha
    ): array {
        $sql = "
            SELECT
                m.id AS id_matricula,
                m.fecha_inicio,
                m.fecha_fin,
                m.estado AS estado_matricula,

                a.id AS id_aprendiz,
                a.estado AS estado_aprendiz,

                p.tipo_documento,
                p.documento,
                p.nombres,
                p.apellidos,
                p.telefono,
                p.correo

            FROM matriculas m

            INNER JOIN aprendices a
                ON a.id = m.id_aprendiz

            INNER JOIN personas p
                ON p.id = a.id_persona

            WHERE m.id_ficha = ?

            ORDER BY
                p.apellidos ASC,
                p.nombres ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idFicha]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | RESUMEN DE ESTADOS DE LOS APRENDICES
    |--------------------------------------------------------------------------
    */

    public function obtenerResumenAprendices(
        int $idFicha
    ): array {
        $sql = "
            SELECT
                COUNT(DISTINCT a.id) AS total,

                COUNT(
                    DISTINCT CASE
                        WHEN a.estado = 'EN FORMACION'
                        THEN a.id
                    END
                ) AS en_formacion,

                COUNT(
                    DISTINCT CASE
                        WHEN a.estado = 'APLAZADO'
                        THEN a.id
                    END
                ) AS aplazados,

                COUNT(
                    DISTINCT CASE
                        WHEN a.estado = 'RETIRO VOLUNTARIO'
                        THEN a.id
                    END
                ) AS retiro_voluntario,

                COUNT(
                    DISTINCT CASE
                        WHEN a.estado = 'CANCELADO'
                        THEN a.id
                    END
                ) AS cancelados

            FROM matriculas m

            INNER JOIN aprendices a
                ON a.id = m.id_aprendiz

            WHERE m.id_ficha = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idFicha]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: [
            'total' => 0,
            'en_formacion' => 0,
            'aplazados' => 0,
            'retiro_voluntario' => 0,
            'cancelados' => 0
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | AVANCE ACADÉMICO INDIVIDUAL
    |--------------------------------------------------------------------------
    */

    public function obtenerAvanceAprendices(
        int $idFicha
    ): array {
        $sql = "
            SELECT
                a.id AS id_aprendiz,

                CONCAT(
                    p.nombres,
                    ' ',
                    p.apellidos
                ) AS aprendiz,

                a.estado AS estado_aprendiz,

                COUNT(
                    DISTINCT CASE
                        WHEN LOWER(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(
                                                c.nombre,
                                                'á', 'a'
                                            ),
                                            'é', 'e'
                                        ),
                                        'í', 'i'
                                    ),
                                    'ó', 'o'
                                ),
                                'ú', 'u'
                            )
                        ) NOT LIKE '%etapa practica%'
                        THEN r.id
                    END
                ) AS total_rap_lectivos,

                COUNT(
                    DISTINCT CASE
                        WHEN LOWER(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(
                                                c.nombre,
                                                'á', 'a'
                                            ),
                                            'é', 'e'
                                        ),
                                        'í', 'i'
                                    ),
                                    'ó', 'o'
                                ),
                                'ú', 'u'
                            )
                        ) NOT LIKE '%etapa practica%'
                        AND ra.juicio = 'aprobado'
                        THEN r.id
                    END
                ) AS rap_lectivos_aprobados,

                COUNT(
                    DISTINCT CASE
                        WHEN LOWER(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(
                                                c.nombre,
                                                'á', 'a'
                                            ),
                                            'é', 'e'
                                        ),
                                        'í', 'i'
                                    ),
                                    'ó', 'o'
                                ),
                                'ú', 'u'
                            )
                        ) LIKE '%etapa practica%'
                        THEN r.id
                    END
                ) AS total_rap_practica,

                COUNT(
                    DISTINCT CASE
                        WHEN LOWER(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(
                                                c.nombre,
                                                'á', 'a'
                                            ),
                                            'é', 'e'
                                        ),
                                        'í', 'i'
                                    ),
                                    'ó', 'o'
                                ),
                                'ú', 'u'
                            )
                        ) LIKE '%etapa practica%'
                        AND ra.juicio = 'aprobado'
                        THEN r.id
                    END
                ) AS rap_practica_aprobados,

                COUNT(
                    DISTINCT CASE
                        WHEN LOWER(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(
                                                c.nombre,
                                                'á', 'a'
                                            ),
                                            'é', 'e'
                                        ),
                                        'í', 'i'
                                    ),
                                    'ó', 'o'
                                ),
                                'ú', 'u'
                            )
                        ) LIKE '%etapa practica%'
                        AND ra.juicio IN (
                            'aprobado',
                            'no_aprobado'
                        )
                        THEN r.id
                    END
                ) AS rap_practica_evaluados,

                CASE
                    WHEN COUNT(
                        DISTINCT CASE
                            WHEN LOWER(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(
                                                REPLACE(
                                                    c.nombre,
                                                    'á', 'a'
                                                ),
                                                'é', 'e'
                                            ),
                                            'í', 'i'
                                        ),
                                        'ó', 'o'
                                    ),
                                    'ú', 'u'
                                )
                            ) NOT LIKE '%etapa practica%'
                            THEN r.id
                        END
                    ) =
                    COUNT(
                        DISTINCT CASE
                            WHEN LOWER(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(
                                                REPLACE(
                                                    c.nombre,
                                                    'á', 'a'
                                                ),
                                                'é', 'e'
                                            ),
                                            'í', 'i'
                                        ),
                                        'ó', 'o'
                                    ),
                                    'ú', 'u'
                                )
                            ) NOT LIKE '%etapa practica%'
                            AND ra.juicio = 'aprobado'
                            THEN r.id
                        END
                    )
                    THEN 'ETAPA PRACTICA'
                    ELSE 'ETAPA LECTIVA'
                END AS etapa_actual

            FROM matriculas m

            INNER JOIN aprendices a
                ON a.id = m.id_aprendiz

            INNER JOIN personas p
                ON p.id = a.id_persona

            INNER JOIN fichas f
                ON f.id = m.id_ficha

            INNER JOIN programas pr
                ON pr.id = f.id_programa

            INNER JOIN competencias c
                ON c.id_programa = pr.id

            INNER JOIN rap r
                ON r.id_competencia = c.id

            LEFT JOIN resultados_aprendiz ra
                ON ra.id_aprendiz = a.id
                AND ra.id_ficha = f.id
                AND ra.id_rap = r.id

            WHERE f.id = ?
              AND a.estado = 'EN FORMACION'

            GROUP BY
                a.id,
                p.nombres,
                p.apellidos,
                a.estado

            ORDER BY
                etapa_actual DESC,
                p.apellidos ASC,
                p.nombres ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idFicha]);

        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($resultados as &$resultado) {
            $total = (int) (
                $resultado['total_rap_lectivos'] ?? 0
            );

            $aprobados = (int) (
                $resultado['rap_lectivos_aprobados'] ?? 0
            );

            $resultado['rap_lectivos_pendientes'] = max(
                0,
                $total - $aprobados
            );

            $resultado['porcentaje_avance_lectivo'] =
                $total > 0
                    ? round(($aprobados * 100) / $total, 2)
                    : 0.0;
        }

        unset($resultado);

        return $resultados;
    }

    /*
    |--------------------------------------------------------------------------
    | RESUMEN DE RAP LECTIVOS
    |--------------------------------------------------------------------------
    */

    public function obtenerResumenRapsLectivos(
        int $idFicha
    ): array {
        $sql = "
            SELECT
                COUNT(
                    DISTINCT CASE
                        WHEN a.estado = 'EN FORMACION'
                        THEN a.id
                    END
                ) AS aprendices_en_formacion,

                COUNT(
                    DISTINCT CASE
                        WHEN LOWER(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(
                                                c.nombre,
                                                'á', 'a'
                                            ),
                                            'é', 'e'
                                        ),
                                        'í', 'i'
                                    ),
                                    'ó', 'o'
                                ),
                                'ú', 'u'
                            )
                        ) NOT LIKE '%etapa practica%'
                        THEN r.id
                    END
                ) AS total_rap_lectivos,

                COUNT(
                    DISTINCT CASE
                        WHEN a.estado = 'EN FORMACION'
                        AND LOWER(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(
                                                c.nombre,
                                                'á', 'a'
                                            ),
                                            'é', 'e'
                                        ),
                                        'í', 'i'
                                    ),
                                    'ó', 'o'
                                ),
                                'ú', 'u'
                            )
                        ) NOT LIKE '%etapa practica%'
                        AND ra.juicio IN (
                            'aprobado',
                            'no_aprobado'
                        )
                        AND (
                            ra.fecha_evaluacion IS NULL
                            OR ra.fecha_evaluacion <= CURDATE()
                        )
                        THEN CONCAT(a.id, '-', r.id)
                    END
                ) AS raps_evaluados_hasta_hoy,

                COUNT(
                    DISTINCT CASE
                        WHEN a.estado = 'EN FORMACION'
                        AND LOWER(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(
                                                c.nombre,
                                                'á', 'a'
                                            ),
                                            'é', 'e'
                                        ),
                                        'í', 'i'
                                    ),
                                    'ó', 'o'
                                ),
                                'ú', 'u'
                            )
                        ) NOT LIKE '%etapa practica%'
                        AND ra.juicio = 'aprobado'
                        AND (
                            ra.fecha_evaluacion IS NULL
                            OR ra.fecha_evaluacion <= CURDATE()
                        )
                        THEN CONCAT(a.id, '-', r.id)
                    END
                ) AS raps_aprobados_hasta_hoy

            FROM fichas f

            INNER JOIN programas pr
                ON pr.id = f.id_programa

            INNER JOIN competencias c
                ON c.id_programa = pr.id

            INNER JOIN rap r
                ON r.id_competencia = c.id

            INNER JOIN matriculas m
                ON m.id_ficha = f.id

            INNER JOIN aprendices a
                ON a.id = m.id_aprendiz

            LEFT JOIN resultados_aprendiz ra
                ON ra.id_aprendiz = a.id
                AND ra.id_ficha = f.id
                AND ra.id_rap = r.id

            WHERE f.id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idFicha]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resultado) {
            return [
                'aprendices_en_formacion' => 0,
                'total_rap_lectivos' => 0,
                'total_raps_posibles' => 0,
                'raps_evaluados_hasta_hoy' => 0,
                'raps_aprobados_hasta_hoy' => 0,
                'porcentaje_raps_evaluados' => 0,
                'porcentaje_raps_aprobados' => 0
            ];
        }

        $aprendicesEnFormacion = (int) (
            $resultado['aprendices_en_formacion'] ?? 0
        );

        $totalRapLectivos = (int) (
            $resultado['total_rap_lectivos'] ?? 0
        );

        $rapsEvaluados = (int) (
            $resultado['raps_evaluados_hasta_hoy'] ?? 0
        );

        $rapsAprobados = (int) (
            $resultado['raps_aprobados_hasta_hoy'] ?? 0
        );

        $totalRapsPosibles =
            $totalRapLectivos * $aprendicesEnFormacion;

        $porcentajeEvaluados = 0.0;
        $porcentajeAprobados = 0.0;

        if ($totalRapsPosibles > 0) {
            $porcentajeEvaluados =
                ($rapsEvaluados * 100) / $totalRapsPosibles;

            $porcentajeAprobados =
                ($rapsAprobados * 100) / $totalRapsPosibles;
        }

        return [
            'aprendices_en_formacion' => $aprendicesEnFormacion,
            'total_rap_lectivos' => $totalRapLectivos,
            'total_raps_posibles' => $totalRapsPosibles,
            'raps_evaluados_hasta_hoy' => $rapsEvaluados,
            'raps_aprobados_hasta_hoy' => $rapsAprobados,

            'porcentaje_raps_evaluados' => round(
                max(0, min(100, $porcentajeEvaluados)),
                2
            ),

            'porcentaje_raps_aprobados' => round(
                max(0, min(100, $porcentajeAprobados)),
                2
            )
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RESUMEN ACADÉMICO
    |--------------------------------------------------------------------------
    */

    public function obtenerResumenAcademico(
        int $idFicha
    ): array {
        $avance = $this->obtenerAvanceAprendices($idFicha);

        $totalEnFormacion = count($avance);
        $totalEtapaLectiva = 0;
        $totalEtapaPractica = 0;
        $sumaAvance = 0.0;

        foreach ($avance as $aprendiz) {
            $etapa = strtoupper(
                trim((string) ($aprendiz['etapa_actual'] ?? ''))
            );

            if ($etapa === 'ETAPA PRACTICA') {
                $totalEtapaPractica++;
            } else {
                $totalEtapaLectiva++;
            }

            $sumaAvance += (float) (
                $aprendiz['porcentaje_avance_lectivo'] ?? 0
            );
        }

        $promedioAvance = $totalEnFormacion > 0
            ? $sumaAvance / $totalEnFormacion
            : 0.0;

        return [
            'total_en_formacion' => $totalEnFormacion,
            'total_etapa_lectiva' => $totalEtapaLectiva,
            'total_etapa_practica' => $totalEtapaPractica,
            'promedio_avance_lectivo' => round($promedioAvance, 2)
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PROFESIONALES ASIGNADOS
    |--------------------------------------------------------------------------
    */

    public function obtenerProfesionalesAsignados(
        int $idFicha
    ): array {
        $sql = "
            SELECT
                af.id AS id_asignacion,
                af.tipo_asignacion,
                af.fecha_inicio,
                af.fecha_fin,
                af.estado AS estado_asignacion,
                af.observaciones,

                pr.id AS id_profesional,
                pr.tipo_profesional,
                pr.registro_profesional,
                pr.especialidad,
                pr.estado AS estado_profesional,

                p.id AS id_persona,
                p.nombres,
                p.apellidos,
                p.tipo_documento,
                p.documento,
                p.telefono,
                p.correo

            FROM asignaciones_ficha af

            INNER JOIN profesionales pr
                ON pr.id = af.id_profesional

            INNER JOIN personas p
                ON p.id = pr.id_persona

            WHERE af.id_ficha = ?

            ORDER BY
                CASE
                    WHEN af.estado = 'activa' THEN 1
                    ELSE 2
                END,

                CASE af.tipo_asignacion
                    WHEN 'COORDINADOR' THEN 1
                    WHEN 'LIDER_FICHA' THEN 2
                    WHEN 'INSTRUCTOR_TECNICO' THEN 3
                    WHEN 'INSTRUCTOR_TRANSVERSAL' THEN 4
                    WHEN 'BIENESTAR' THEN 5
                    WHEN 'SALUD_FISICA' THEN 6
                    WHEN 'SALUD_MENTAL' THEN 7
                    WHEN 'NUTRICION' THEN 8
                    ELSE 9
                END,

                p.apellidos ASC,
                p.nombres ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idFicha]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | LÍDER DE FICHA
    |--------------------------------------------------------------------------
    */

    public function obtenerLiderFicha(
        int $idFicha
    ): ?array {
        $sql = "
            SELECT
                af.id AS id_asignacion,
                af.tipo_asignacion,
                af.fecha_inicio,
                af.fecha_fin,
                af.estado AS estado_asignacion,
                af.observaciones,

                pr.id AS id_profesional,
                pr.tipo_profesional,
                pr.registro_profesional,
                pr.especialidad,
                pr.estado AS estado_profesional,

                p.id AS id_persona,
                p.nombres,
                p.apellidos,
                p.tipo_documento,
                p.documento,
                p.telefono,
                p.correo

            FROM asignaciones_ficha af

            INNER JOIN profesionales pr
                ON pr.id = af.id_profesional

            INNER JOIN personas p
                ON p.id = pr.id_persona

            WHERE af.id_ficha = ?
              AND af.tipo_asignacion = 'LIDER_FICHA'

            ORDER BY
                CASE
                    WHEN af.estado = 'activa' THEN 1
                    ELSE 2
                END,

                af.fecha_inicio DESC,
                af.id DESC

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idFicha]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | INSTRUCTORES QUE HAN CALIFICADO RAP
    |--------------------------------------------------------------------------
    */

    public function obtenerInstructoresEvaluadores(
        int $idFicha
    ): array {
        $sql = "
            SELECT
                pr.id AS id_profesional,

                CONCAT(
                    p.nombres,
                    ' ',
                    p.apellidos
                ) AS instructor,

                p.nombres,
                p.apellidos,

                pr.tipo_profesional,
                pr.registro_profesional,
                pr.especialidad,
                pr.estado AS estado_profesional,

                af.tipo_asignacion,

                COUNT(DISTINCT ra.id_rap) AS raps_evaluados,

                COUNT(
                    DISTINCT CASE
                        WHEN ra.juicio = 'aprobado'
                        THEN ra.id_rap
                    END
                ) AS raps_aprobados,

                COUNT(
                    DISTINCT CASE
                        WHEN ra.juicio = 'no_aprobado'
                        THEN ra.id_rap
                    END
                ) AS raps_no_aprobados,

                MAX(
                    COALESCE(
                        ra.fecha_hora_evaluacion,
                        CONCAT(ra.fecha_evaluacion, ' 00:00:00')
                    )
                ) AS ultima_evaluacion

            FROM resultados_aprendiz ra

            INNER JOIN profesionales pr
                ON pr.id = ra.id_instructor

            INNER JOIN personas p
                ON p.id = pr.id_persona

            LEFT JOIN asignaciones_ficha af
                ON af.id_ficha = ra.id_ficha
                AND af.id_profesional = pr.id
                AND af.tipo_asignacion IN (
                    'INSTRUCTOR_TECNICO',
                    'INSTRUCTOR_TRANSVERSAL'
                )

            WHERE ra.id_ficha = ?
              AND ra.id_instructor IS NOT NULL
              AND ra.juicio IN ('aprobado', 'no_aprobado')

            GROUP BY
                pr.id,
                p.nombres,
                p.apellidos,
                pr.tipo_profesional,
                pr.registro_profesional,
                pr.especialidad,
                pr.estado,
                af.tipo_asignacion

            ORDER BY
                raps_evaluados DESC,
                ultima_evaluacion DESC,
                p.apellidos ASC,
                p.nombres ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idFicha]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | PROGRAMAS ACTIVOS
    |--------------------------------------------------------------------------
    */

    public function obtenerProgramasActivos(): array
    {
        $sql = "
            SELECT
                id,
                codigo,
                nombre,
                nivel
            FROM programas
            WHERE estado = 'activo'
            ORDER BY
                codigo ASC,
                nombre ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | JORNADAS ACTIVAS
    |--------------------------------------------------------------------------
    */

    public function obtenerJornadasActivas(): array
    {
        $sql = "
            SELECT
                id,
                nombre,
                descripcion
            FROM jornadas
            WHERE estado = 'activo'
            ORDER BY id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFICAR NÚMERO DE FICHA
    |--------------------------------------------------------------------------
    */

    public function existeNumeroFicha(
        string $numeroFicha
    ): bool {
        $sql = "
            SELECT COUNT(*)
            FROM fichas
            WHERE numero_ficha = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$numeroFicha]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR FICHA MANUALMENTE
    |--------------------------------------------------------------------------
    */

    public function crear(
        array $datos
    ): int {
        $sql = "
            INSERT INTO fichas
            (
                numero_ficha,
                id_programa,
                version_programa,
                modalidad,
                regional,
                centro,
                id_jornada,
                fecha_inicio,
                fecha_fin,
                estado
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $datos['numero_ficha'],
            (int) $datos['id_programa'],
            $datos['version_programa'],
            $datos['modalidad'],
            $datos['regional'],
            $datos['centro'],
            $datos['id_jornada'],
            $datos['fecha_inicio'],
            $datos['fecha_fin'],
            $datos['estado']
        ]);

        return (int) $this->db->lastInsertId();
    }
}