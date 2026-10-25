<?php

declare(strict_types=1);

class ReporteModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->conectar();
    }

    public function catalogos(): array
{
    return [
        'fichas' => $this->db->query("
            SELECT
                f.id,
                f.numero_ficha,
                f.id_programa
            FROM fichas f
            ORDER BY f.numero_ficha DESC
        ")->fetchAll(PDO::FETCH_ASSOC),

        'programas' => $this->db->query("
            SELECT
                id,
                codigo,
                nombre
            FROM programas
            ORDER BY nombre
        ")->fetchAll(PDO::FETCH_ASSOC),

        'jornadas' => $this->db->query("
            SELECT
                id,
                nombre
            FROM jornadas
            ORDER BY nombre
        ")->fetchAll(PDO::FETCH_ASSOC),

        'poblaciones' => $this->db->query("
            SELECT
                id,
                nombre
            FROM tipos_poblacion
            WHERE estado = 'activo'
            ORDER BY nombre
        ")->fetchAll(PDO::FETCH_ASSOC),
    ];
}

    /** Catálogo oficial de preguntas del formulario de bienestar. */
    private const PREGUNTAS_BIENESTAR = [
        'CS-01' => '¿Cuál es su grupo y subgrupo del SISBÉN?',
        'CS-02' => '¿Cuál es el estrato de su vivienda?',
        'CS-03' => '¿En qué zona está ubicada su vivienda?',
        'CS-04' => '¿Tiene alguna condición especial de protección que desee comunicar para recibir orientación?',
        'PP-01' => '¿Tiene alguna condición de salud diagnosticada que desee comunicar para recibir apoyo?',
        'PP-02' => '¿Qué sustancias ha consumido durante los últimos 30 días?',
        'PP-02-FRECUENCIA' => 'Si desea responder, indique la frecuencia de consumo de cada categoría seleccionada.',
        'PP-03' => '¿Cómo califica su nivel de información sobre anticonceptivos, ITS y derechos sexuales y reproductivos?',
        'PP-04' => '¿Qué hábitos le gustaría mejorar?',
        'PP-05' => 'En una escala de 1 a 10, ¿cómo califica actualmente su estado de salud según su percepción?',
        'PP-06' => '¿Sobre qué tema de promoción y prevención le gustaría recibir información?',
        'HB-01' => '¿Qué situaciones le generan estrés o preocupación?',
        'HB-02' => '¿Con quién cuenta cuando necesita apoyo?',
        'HB-03' => '¿Qué tan fácil le resulta pedir ayuda cuando la necesita?',
        'HB-04' => '¿Cómo califica su capacidad para comunicarse y escuchar respetuosamente?',
        'HB-05' => '¿Qué tan cómodo/a se siente trabajando en equipo con personas diferentes a usted?',
        'HB-06' => '¿Cómo suele actuar ante un desacuerdo?',
        'HB-07' => '¿Qué habilidades le gustaría fortalecer?',
        'HB-08' => '¿Cómo considera que organiza su tiempo?',
        'HB-09' => '¿En cuáles talleres le gustaría participar?',
        'AF-01' => '¿Practica habitualmente algún deporte o actividad física?',
        'AF-02' => '¿Qué deportes o actividades practica actualmente?',
        'AF-03' => '¿Qué deportes o actividades le gustaría practicar?',
        'AF-04' => '¿Ha participado en equipos o torneos deportivos?',
        'AF-05' => '¿Le gustaría participar en actividades deportivas del SENA?',
        'AF-06' => '¿Qué dificultades podrían impedirle participar?',
        'AF-07' => 'En una escala de 0 a 10, ¿qué tan motivado/a se siente para realizar actividad física?',
        'AF-08' => '¿Qué apoyo o adaptación facilitaría su participación?',
        'AF-09' => '¿Qué horarios prefiere para las actividades físicas?',
        'AF-10' => '¿Qué actividad recreativa le gustaría que se organizara y por qué?',
        'HA-01' => '¿Considera que tiene alguna habilidad o interés artístico?',
        'HA-02' => '¿En qué actividades artísticas tiene experiencia o interés?',
        'HA-03' => '¿Toca algún instrumento musical?',
        'HA-04' => '¿Qué instrumento o instrumentos toca?',
        'HA-05' => '¿Qué actividad artística le gustaría aprender o mejorar?',
        'HA-06' => '¿Ha participado en actividades artísticas o culturales?',
        'HA-07' => '¿Le gustaría participar en actividades artísticas del SENA?',
        'HA-08' => '¿Qué dificultades podrían impedirle participar?',
        'HA-09' => '¿Qué momento prefiere para realizar actividades artísticas?',
        'HA-10' => '¿Qué actividad artística o cultural propone para el SENA?',
    ];

    /**
     * Recupera las respuestas reales guardadas en cuestionarios_bienestar,
     * agrupadas por componente y con el texto oficial de cada pregunta.
     * No inventa respuestas: solo muestra los datos persistidos.
     */
    private function respuestasBienestar(int $idAprendiz, string $componente): array
    {
        $cuestionarios = $this->consulta("
            SELECT cb.id, cb.id_ficha, f.numero_ficha, cb.respuestas, cb.estado,
                cb.fecha_inicio, cb.fecha_actualizacion, cb.fecha_completado
            FROM cuestionarios_bienestar cb
            LEFT JOIN fichas f ON f.id = cb.id_ficha
            WHERE cb.id_aprendiz = ?
            ORDER BY cb.fecha_actualizacion DESC, cb.id DESC
        ", [$idAprendiz]);

        $filas = [];
        foreach ($cuestionarios as $cuestionario) {
            $json = json_decode((string)($cuestionario['respuestas'] ?? ''), true);
            if (!is_array($json)) {
                continue;
            }
            $encontradas = [];
            $recorrer = function (array $elementos) use (&$recorrer, &$encontradas, $componente): void {
                foreach ($elementos as $clave => $valor) {
                    if (is_string($clave)) {
                        $codigo = strtoupper(str_replace('_', '-', trim($clave)));
                        if (preg_match('/^(CS|PP|HB|AF|HA)(?:-|$)/', $codigo, $m)) {
                            if ($m[1] === $componente) {
                                $encontradas[$codigo] = $valor;
                                continue;
                            }
                        }
                    }
                    if (is_array($valor)) {
                        // Las respuestas de selección múltiple se mantienen como valor completo
                        // cuando la clave es un código de pregunta.
                        if (is_string($clave) && preg_match('/^(CS|PP|HB|AF|HA)(?:-|$)/i', str_replace('_', '-', $clave))) {
                            continue;
                        }
                        $recorrer($valor);
                    }
                }
            };
            $recorrer($json);

            foreach ($encontradas as $codigo => $valor) {
                if (is_array($valor)) {
                    $valor = implode(', ', array_map(static function ($v): string {
                        if (is_scalar($v) || $v === null) return (string)($v ?? '');
                        return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
                    }, $valor));
                } elseif (is_bool($valor)) {
                    $valor = $valor ? 'Sí' : 'No';
                } elseif ($valor === null) {
                    $valor = '';
                } elseif (!is_scalar($valor)) {
                    $valor = json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
                }
                $filas[] = [
                    'Ficha' => $cuestionario['numero_ficha'] ?? '',
                    'Estado del cuestionario' => $cuestionario['estado'] ?? '',
                    'Fecha de inicio' => $cuestionario['fecha_inicio'] ?? '',
                    'Última actualización' => $cuestionario['fecha_actualizacion'] ?? '',
                    'Fecha de finalización' => $cuestionario['fecha_completado'] ?? '',
                    'Código' => $codigo,
                    'Pregunta' => self::PREGUNTAS_BIENESTAR[$codigo] ?? $codigo,
                    'Respuesta registrada' => (string)$valor,
                ];
            }
        }
        return $filas;
    }

    private function resumenCuestionarios(int $idAprendiz): array
    {
        return $this->consulta("
            SELECT f.numero_ficha AS `Ficha`, cb.estado AS `Estado del cuestionario`,
                cb.fecha_inicio AS `Fecha de inicio`, cb.fecha_actualizacion AS `Última actualización`,
                cb.fecha_completado AS `Fecha de finalización`,
                CASE WHEN cb.respuestas IS NULL OR cb.respuestas = '' OR cb.respuestas = '{}'
                    THEN 0 ELSE 1 END AS `Tiene respuestas guardadas`
            FROM cuestionarios_bienestar cb
            LEFT JOIN fichas f ON f.id = cb.id_ficha
            WHERE cb.id_aprendiz = ?
            ORDER BY cb.fecha_actualizacion DESC
        ", [$idAprendiz]);
    }

    public function ejecutar(string $tipo, array $f = []): array
    {
        $buscar = trim((string)($f['buscar'] ?? ''));
        $idFicha = (int)($f['id_ficha'] ?? 0);
        $idPrograma = (int)($f['id_programa'] ?? 0);
        $idJornada = (int)($f['id_jornada'] ?? 0);
        $estado = trim((string)($f['estado'] ?? ''));
        $idPoblacion = (int)($f['id_poblacion'] ?? 0);
        $desde = trim((string)($f['desde'] ?? ''));
        $hasta = trim((string)($f['hasta'] ?? ''));

        $params = [];
        $where = [];
        $dateCol = '';

        switch ($tipo) {
            case 'fichas':
                $sql = "SELECT f.numero_ficha AS `Número de ficha`,
                    p.codigo AS `Código del programa`, p.nombre AS `Programa`,
                    p.nivel AS `Nivel`, f.version_programa AS `Versión`,
                    f.modalidad AS `Modalidad`, j.nombre AS `Jornada`,
                    f.regional AS `Regional`, f.centro AS `Centro`,
                    f.fecha_inicio AS `Fecha de inicio`, f.fecha_fin AS `Fecha de finalización`,
                    f.estado AS `Estado`,
                    COUNT(DISTINCT CASE WHEN m.estado IN ('activa','finalizada') THEN m.id_aprendiz END) AS `Aprendices vinculados`
                    FROM fichas f
                    INNER JOIN programas p ON p.id=f.id_programa
                    LEFT JOIN jornadas j ON j.id=f.id_jornada
                    LEFT JOIN matriculas m ON m.id_ficha=f.id";
                if ($idPrograma) { $where[]='f.id_programa = ?'; $params[]=$idPrograma; }
                if ($idJornada) { $where[]='f.id_jornada = ?'; $params[]=$idJornada; }
                if ($estado !== '') { $where[]='f.estado = ?'; $params[]=$estado; }
                if ($buscar !== '') {
                    $where[]="(f.numero_ficha LIKE ? OR p.codigo LIKE ? OR p.nombre LIKE ?)";
                    array_push($params, "%$buscar%", "%$buscar%", "%$buscar%");
                }
                $sql .= $this->where($where) . " GROUP BY f.id,p.codigo,p.nombre,p.nivel,j.nombre ORDER BY f.fecha_inicio DESC,f.numero_ficha";
                break;

            case 'aprendices_ficha':
                $sql = "SELECT f.numero_ficha AS `Número de ficha`,
                    p.codigo AS `Código del programa`, p.nombre AS `Programa`,
                    j.nombre AS `Jornada`, pe.tipo_documento AS `Tipo documento`,
                    pe.documento AS `Documento`, pe.nombres AS `Nombres`, pe.apellidos AS `Apellidos`,
                    a.estado AS `Estado del aprendiz`, m.estado AS `Estado de matrícula`,
                    m.fecha_inicio AS `Inicio de matrícula`, m.fecha_fin AS `Fin de matrícula`,
                    pe.fecha_nacimiento AS `Fecha de nacimiento`, pe.sexo AS `Sexo`,
                    pe.telefono AS `Teléfono`, pe.correo AS `Correo`,
                    pe.direccion AS `Dirección`, pe.municipio AS `Municipio`,
                    pe.departamento AS `Departamento`, e.nombre AS `EPS`,
                    a.contacto_emergencia_nombre AS `Contacto de emergencia`,
                    a.contacto_emergencia_telefono AS `Teléfono de emergencia`,
                    a.contacto_emergencia_parentesco AS `Parentesco de emergencia`
                    FROM matriculas m
                    INNER JOIN fichas f ON f.id=m.id_ficha
                    INNER JOIN programas p ON p.id=f.id_programa
                    LEFT JOIN jornadas j ON j.id=f.id_jornada
                    INNER JOIN aprendices a ON a.id=m.id_aprendiz
                    INNER JOIN personas pe ON pe.id=a.id_persona
                    LEFT JOIN eps e ON e.id=a.id_eps";
                if ($idFicha) { $where[]='f.id = ?'; $params[]=$idFicha; }
                if ($idPrograma) { $where[]='f.id_programa = ?'; $params[]=$idPrograma; }
                if ($idJornada) { $where[]='f.id_jornada = ?'; $params[]=$idJornada; }
                if ($estado !== '') { $where[]='a.estado = ?'; $params[]=$estado; }
                if ($buscar !== '') {
                    $where[]="(pe.documento LIKE ? OR pe.nombres LIKE ? OR pe.apellidos LIKE ? OR f.numero_ficha LIKE ?)";
                    array_push($params, "%$buscar%", "%$buscar%", "%$buscar%", "%$buscar%");
                }
                $sql .= $this->where($where) . " ORDER BY f.numero_ficha,pe.apellidos,pe.nombres";
                break;

            case 'caracterizacion':
                $sql = "SELECT f.numero_ficha AS `Número de ficha`,
                    pe.tipo_documento AS `Tipo documento`, pe.documento AS `Documento`,
                    pe.nombres AS `Nombres`, pe.apellidos AS `Apellidos`,
                    a.estado AS `Estado del aprendiz`, pe.sexo AS `Sexo`,
                    pe.fecha_nacimiento AS `Fecha de nacimiento`, pe.telefono AS `Teléfono`,
                    pe.correo AS `Correo`, pe.direccion AS `Dirección`,
                    pe.municipio AS `Municipio`, pe.departamento AS `Departamento`,
                    e.nombre AS `EPS`, tp.nombre AS `Tipo de población`,
                    ap.fecha_inicio AS `Desde`, ap.fecha_fin AS `Hasta`,
                    ap.observacion AS `Observación de caracterización`
                    FROM aprendices a
                    INNER JOIN personas pe ON pe.id=a.id_persona
                    LEFT JOIN eps e ON e.id=a.id_eps
                    LEFT JOIN matriculas m ON m.id_aprendiz=a.id AND m.estado IN ('activa','finalizada')
                    LEFT JOIN fichas f ON f.id=m.id_ficha
                    LEFT JOIN aprendiz_poblacion ap ON ap.id_aprendiz=a.id
                        AND ap.fecha_inicio <= CURRENT_DATE()
                        AND (ap.fecha_fin IS NULL OR ap.fecha_fin >= CURRENT_DATE())
                    LEFT JOIN tipos_poblacion tp ON tp.id=ap.id_tipo_poblacion";
                if ($idFicha) { $where[]='f.id = ?'; $params[]=$idFicha; }
                if ($idPrograma) { $where[]='f.id_programa = ?'; $params[]=$idPrograma; }
                if ($idPoblacion) { $where[]='tp.id = ?'; $params[]=$idPoblacion; }
                if ($estado !== '') { $where[]='a.estado = ?'; $params[]=$estado; }
                if ($buscar !== '') {
                    $where[]="(pe.documento LIKE ? OR pe.nombres LIKE ? OR pe.apellidos LIKE ? OR pe.municipio LIKE ?)";
                    array_push($params, "%$buscar%", "%$buscar%", "%$buscar%", "%$buscar%");
                }
                $sql .= $this->where($where) . " ORDER BY f.numero_ficha,pe.apellidos,pe.nombres,tp.nombre";
                break;

            case 'resultados':
                $sql = "SELECT f.numero_ficha AS `Número de ficha`,
                    pe.tipo_documento AS `Tipo documento`, pe.documento AS `Documento`,
                    pe.nombres AS `Nombres`, pe.apellidos AS `Apellidos`,
                    r.fecha_evaluacion AS `Fecha de evaluación`,
                    r.fecha_hora_evaluacion AS `Fecha y hora`,
                    co.codigo AS `Código competencia`, co.nombre AS `Competencia`,
                    rap.codigo AS `Código RAP`, rap.nombre AS `Resultado de aprendizaje`,
                    r.juicio AS `Juicio`, r.juicio_sena AS `Juicio SENA`,
                    r.calificacion AS `Calificación`, r.observaciones AS `Observaciones`
                    FROM resultados_aprendiz r
                    INNER JOIN aprendices a ON a.id=r.id_aprendiz
                    INNER JOIN personas pe ON pe.id=a.id_persona
                    LEFT JOIN fichas f ON f.id=r.id_ficha
                    LEFT JOIN rap ON rap.id=r.id_rap
                    LEFT JOIN competencias co ON co.id=rap.id_competencia";
                if ($idFicha) { $where[]='r.id_ficha = ?'; $params[]=$idFicha; }
                if ($estado !== '') { $where[]='r.juicio = ?'; $params[]=$estado; }
                if ($buscar !== '') {
                    $where[]="(pe.documento LIKE ? OR pe.nombres LIKE ? OR pe.apellidos LIKE ? OR rap.nombre LIKE ? OR co.nombre LIKE ?)";
                    array_push($params, "%$buscar%", "%$buscar%", "%$buscar%", "%$buscar%", "%$buscar%");
                }
                $dateCol = 'COALESCE(r.fecha_hora_evaluacion,r.fecha_evaluacion)';
                $this->agregarFechas($where, $params, $dateCol, $desde, $hasta);
                $sql .= $this->where($where) . " ORDER BY f.numero_ficha,pe.apellidos,pe.nombres,r.fecha_evaluacion";
                break;

            case 'alertas':
                $sql = "SELECT pe.tipo_documento AS `Tipo documento`, pe.documento AS `Documento`,
                    pe.nombres AS `Nombres`, pe.apellidos AS `Apellidos`,
                    (SELECT GROUP_CONCAT(DISTINCT fx.numero_ficha ORDER BY fx.numero_ficha SEPARATOR ', ')
                     FROM matriculas mx INNER JOIN fichas fx ON fx.id=mx.id_ficha
                     WHERE mx.id_aprendiz=a.id) AS `Fichas`,
                    al.tipo_alerta AS `Tipo de alerta`, al.nivel AS `Nivel`,
                    al.titulo AS `Título`, al.recomendacion_institucional AS `Recomendación institucional`,
                    al.fecha_inicio AS `Fecha de inicio`, al.fecha_fin AS `Fecha de finalización`,
                    al.estado AS `Estado`, al.observacion AS `Observación`,
                    al.visible_instructor AS `Visible para instructor`,
                    al.visible_lider_ficha AS `Visible para líder de ficha`,
                    al.visible_transversal AS `Visible para transversal`
                    FROM alertas_acompanamiento al
                    INNER JOIN aprendices a ON a.id=al.id_aprendiz
                    INNER JOIN personas pe ON pe.id=a.id_persona";
                if ($estado !== '') { $where[]='al.estado = ?'; $params[]=$estado; }
                if ($buscar !== '') {
                    $where[]="(pe.documento LIKE ? OR pe.nombres LIKE ? OR pe.apellidos LIKE ? OR al.titulo LIKE ?)";
                    array_push($params, "%$buscar%", "%$buscar%", "%$buscar%", "%$buscar%");
                }
                $this->agregarFechas($where, $params, 'al.fecha_inicio', $desde, $hasta);
                $sql .= $this->where($where) . " ORDER BY al.fecha_inicio DESC,pe.apellidos,pe.nombres";
                break;

            case 'remisiones':
                $sql = "SELECT pe.tipo_documento AS `Tipo documento`, pe.documento AS `Documento`,
                    pe.nombres AS `Nombres`, pe.apellidos AS `Apellidos`,
                    re.fecha_remision AS `Fecha de remisión`, re.area_origen AS `Área de origen`,
                    re.motivo_general AS `Motivo general`, re.tipo_servicio AS `Tipo de servicio`,
                    re.institucion_destino AS `Institución destino`, re.prioridad AS `Prioridad`,
                    re.fecha_cita AS `Fecha de cita`, re.estado AS `Estado`,
                    re.resultado AS `Resultado`, re.fecha_seguimiento AS `Fecha de seguimiento`,
                    re.observaciones AS `Observaciones`, re.fecha_cierre AS `Fecha de cierre`,
                    CONCAT(COALESCE(pp.nombres,''),' ',COALESCE(pp.apellidos,'')) AS `Profesional responsable`
                    FROM remisiones re
                    INNER JOIN aprendices a ON a.id=re.id_aprendiz
                    INNER JOIN personas pe ON pe.id=a.id_persona
                    LEFT JOIN profesionales pr ON pr.id=re.id_profesional
                    LEFT JOIN personas pp ON pp.id=pr.id_persona";
                if ($estado !== '') { $where[]='re.estado = ?'; $params[]=$estado; }
                if ($buscar !== '') {
                    $where[]="(pe.documento LIKE ? OR pe.nombres LIKE ? OR pe.apellidos LIKE ? OR re.motivo_general LIKE ?)";
                    array_push($params, "%$buscar%", "%$buscar%", "%$buscar%", "%$buscar%");
                }
                $this->agregarFechas($where, $params, 're.fecha_remision', $desde, $hasta);
                $sql .= $this->where($where) . " ORDER BY re.fecha_remision DESC";
                break;

            case 'solicitudes':
                $sql = "SELECT pe.tipo_documento AS `Tipo documento`, pe.documento AS `Documento`,
                    pe.nombres AS `Nombres`, pe.apellidos AS `Apellidos`,
                    sa.tipo_atencion AS `Tipo de atención`, sa.motivo_general AS `Motivo general`,
                    sa.modalidad AS `Modalidad`, sa.fecha_preferida AS `Fecha preferida`,
                    sa.hora_preferida AS `Hora preferida`, sa.estado AS `Estado`,
                    sa.created_at AS `Fecha de solicitud`, sa.updated_at AS `Última actualización`
                    FROM solicitudes_atencion sa
                    INNER JOIN aprendices a ON a.id=sa.id_aprendiz
                    INNER JOIN personas pe ON pe.id=a.id_persona";
                if ($estado !== '') { $where[]='sa.estado = ?'; $params[]=$estado; }
                if ($buscar !== '') {
                    $where[]="(pe.documento LIKE ? OR pe.nombres LIKE ? OR pe.apellidos LIKE ? OR sa.motivo_general LIKE ?)";
                    array_push($params, "%$buscar%", "%$buscar%", "%$buscar%", "%$buscar%");
                }
                $this->agregarFechas($where, $params, 'sa.created_at', $desde, $hasta);
                $sql .= $this->where($where) . " ORDER BY sa.created_at DESC";
                break;

            case 'salud_fisica':
                $sql = "SELECT pe.tipo_documento AS `Tipo documento`, pe.documento AS `Documento`,
                    pe.nombres AS `Nombres`, pe.apellidos AS `Apellidos`,
                    vf.fecha_atencion AS `Fecha de atención`, vf.motivo AS `Motivo`,
                    vf.estado_general AS `Estado general`, vf.presencia_dolor AS `Presencia de dolor`,
                    vf.intensidad_dolor AS `Intensidad del dolor`, vf.ubicacion_dolor AS `Ubicación del dolor`,
                    vf.actividad_fisica AS `Actividad física`, vf.horas_sueno AS `Horas de sueño`,
                    vf.calidad_sueno AS `Calidad del sueño`, vf.antecedentes_relevantes AS `Antecedentes relevantes`,
                    vf.observaciones AS `Observaciones`, vf.recomendaciones AS `Recomendaciones`,
                    vf.requiere_seguimiento AS `Requiere seguimiento`,
                    vf.fecha_proximo_seguimiento AS `Próximo seguimiento`
                    FROM valoraciones_salud_fisica vf
                    INNER JOIN aprendices a ON a.id=vf.id_aprendiz
                    INNER JOIN personas pe ON pe.id=a.id_persona";
                if ($buscar !== '') {
                    $where[]="(pe.documento LIKE ? OR pe.nombres LIKE ? OR pe.apellidos LIKE ?)";
                    array_push($params, "%$buscar%", "%$buscar%", "%$buscar%");
                }
                $this->agregarFechas($where, $params, 'vf.fecha_atencion', $desde, $hasta);
                $sql .= $this->where($where) . " ORDER BY vf.fecha_atencion DESC";
                break;

            case 'nutricion':
                $sql = "SELECT pe.tipo_documento AS `Tipo documento`, pe.documento AS `Documento`,
                    pe.nombres AS `Nombres`, pe.apellidos AS `Apellidos`,
                    vn.fecha_atencion AS `Fecha de atención`, vn.peso AS `Peso`,
                    vn.talla AS `Talla`, vn.imc AS `IMC`, vn.perimetro_abdominal AS `Perímetro abdominal`,
                    vn.habitos_alimentarios AS `Hábitos alimentarios`, vn.consumo_agua AS `Consumo de agua`,
                    vn.actividad_fisica AS `Actividad física`, vn.observaciones AS `Observaciones`,
                    vn.recomendaciones AS `Recomendaciones`, vn.requiere_seguimiento AS `Requiere seguimiento`,
                    vn.fecha_proximo_seguimiento AS `Próximo seguimiento`, vn.estado AS `Estado`
                    FROM valoraciones_nutricionales vn
                    INNER JOIN aprendices a ON a.id=vn.id_aprendiz
                    INNER JOIN personas pe ON pe.id=a.id_persona";
                if ($buscar !== '') {
                    $where[]="(pe.documento LIKE ? OR pe.nombres LIKE ? OR pe.apellidos LIKE ?)";
                    array_push($params, "%$buscar%", "%$buscar%", "%$buscar%");
                }
                $this->agregarFechas($where, $params, 'vn.fecha_atencion', $desde, $hasta);
                $sql .= $this->where($where) . " ORDER BY vn.fecha_atencion DESC";
                break;

            case 'aprendiz':
                $documento = trim((string)($f['documento'] ?? $buscar));
                if ($documento === '') {
                    throw new InvalidArgumentException('Escriba el documento del aprendiz para generar el informe individual.');
                }
                $aprendiz = $this->buscarAprendiz($documento);
                if (!$aprendiz) {
                    throw new RuntimeException('No se encontró un aprendiz con ese documento.');
                }
                $id = (int)$aprendiz['id'];
                return [
                    'Datos personales y contacto' => [$this->formatearAprendiz($aprendiz)],
                    'Fichas y matrículas' => $this->consulta("
                        SELECT f.numero_ficha AS `Número de ficha`, p.codigo AS `Código programa`,
                        p.nombre AS `Programa`, j.nombre AS `Jornada`, f.modalidad AS `Modalidad`,
                        f.regional AS `Regional`, f.centro AS `Centro`, f.fecha_inicio AS `Inicio de ficha`,
                        f.fecha_fin AS `Fin de ficha`, f.estado AS `Estado de ficha`,
                        m.estado AS `Estado de matrícula`, m.fecha_inicio AS `Inicio de matrícula`,
                        m.fecha_fin AS `Fin de matrícula`, m.observaciones AS `Observaciones`
                        FROM matriculas m INNER JOIN fichas f ON f.id=m.id_ficha
                        INNER JOIN programas p ON p.id=f.id_programa
                        LEFT JOIN jornadas j ON j.id=f.id_jornada
                        WHERE m.id_aprendiz=? ORDER BY m.fecha_inicio DESC", [$id]),
                    'Caracterización socioeconómica (CS)' => $this->respuestasBienestar($id, 'CS'),
                    'Promoción y prevención (PP)' => $this->respuestasBienestar($id, 'PP'),
                    'Habilidades blandas (HB)' => $this->respuestasBienestar($id, 'HB'),
                    'Actividad física (AF)' => $this->respuestasBienestar($id, 'AF'),
                    'Habilidades artísticas (HA)' => $this->respuestasBienestar($id, 'HA'),
                    'Estado e historial de cuestionarios' => $this->resumenCuestionarios($id),
                    'Clasificación de población' => $this->consulta("
                        SELECT tp.nombre AS `Tipo de población`, ap.fecha_inicio AS `Desde`,
                        ap.fecha_fin AS `Hasta`, ap.observacion AS `Observación`, ap.created_at AS `Registro creado`
                        FROM aprendiz_poblacion ap INNER JOIN tipos_poblacion tp ON tp.id=ap.id_tipo_poblacion
                        WHERE ap.id_aprendiz=? ORDER BY ap.fecha_inicio DESC", [$id]),
                    'Historial de actualización de caracterización' => $this->consulta("
                        SELECT f.numero_ficha AS `Ficha`, ac.estado AS `Estado de actualización`,
                        ac.fecha_actualizacion AS `Fecha de actualización`, ac.origen AS `Origen`,
                        ac.campos_actualizados AS `Campos actualizados`, ac.created_at AS `Creado`,
                        ac.updated_at AS `Último cambio`
                        FROM actualizaciones_caracterizacion ac
                        LEFT JOIN fichas f ON f.id=ac.id_ficha
                        WHERE ac.id_aprendiz=? ORDER BY ac.updated_at DESC", [$id]),
                    'Resultados de aprendizaje' => $this->consulta("
                        SELECT f.numero_ficha AS `Ficha`, r.fecha_evaluacion AS `Fecha`,
                        r.fecha_hora_evaluacion AS `Fecha y hora`, co.codigo AS `Código competencia`,
                        co.nombre AS `Competencia`, rap.codigo AS `Código RAP`, rap.nombre AS `Resultado de aprendizaje`,
                        r.juicio AS `Juicio`, r.juicio_sena AS `Juicio SENA`, r.calificacion AS `Calificación`,
                        r.observaciones AS `Observaciones`
                        FROM resultados_aprendiz r LEFT JOIN fichas f ON f.id=r.id_ficha
                        LEFT JOIN rap ON rap.id=r.id_rap LEFT JOIN competencias co ON co.id=rap.id_competencia
                        WHERE r.id_aprendiz=? ORDER BY r.fecha_evaluacion DESC", [$id]),
                    'Alertas de acompañamiento' => $this->consulta("
                        SELECT tipo_alerta AS `Tipo`, nivel AS `Nivel`, titulo AS `Título`,
                        recomendacion_institucional AS `Recomendación institucional`, fecha_inicio AS `Inicio`,
                        fecha_fin AS `Fin`, aplica_actividad_fisica AS `Aplica actividad física`,
                        aplica_aula AS `Aplica aula`, aplica_laboratorio AS `Aplica laboratorio`,
                        aplica_practica AS `Aplica práctica`, aplica_salida AS `Aplica salida`,
                        estado AS `Estado`, observacion AS `Observación`, visible_instructor AS `Visible para instructor`,
                        visible_lider_ficha AS `Visible para líder de ficha`, visible_transversal AS `Visible para transversal`
                        FROM alertas_acompanamiento WHERE id_aprendiz=? ORDER BY fecha_inicio DESC", [$id]),
                    'Compromisos' => $this->consulta("
                        SELECT fecha_compromiso AS `Fecha`, descripcion AS `Compromiso`, responsable AS `Responsable`,
                        fecha_limite AS `Fecha límite`, estado AS `Estado`, fecha_cumplimiento AS `Fecha de cumplimiento`,
                        observacion AS `Observación` FROM compromisos WHERE id_aprendiz=? ORDER BY fecha_compromiso DESC", [$id]),
                    'Remisiones' => $this->consulta("
                        SELECT fecha_remision AS `Fecha de remisión`, area_origen AS `Área de origen`,
                        motivo_general AS `Motivo`, tipo_servicio AS `Servicio`, institucion_destino AS `Institución destino`,
                        prioridad AS `Prioridad`, fecha_cita AS `Cita`, estado AS `Estado`, resultado AS `Resultado`,
                        fecha_seguimiento AS `Seguimiento`, observaciones AS `Observaciones`, fecha_cierre AS `Cierre`
                        FROM remisiones WHERE id_aprendiz=? ORDER BY fecha_remision DESC", [$id]),
                    'Solicitudes de atención' => $this->consulta("
                        SELECT tipo_atencion AS `Tipo de atención`, motivo_general AS `Motivo`, modalidad AS `Modalidad`,
                        fecha_preferida AS `Fecha preferida`, hora_preferida AS `Hora preferida`, estado AS `Estado`,
                        created_at AS `Fecha de solicitud`, updated_at AS `Última actualización`
                        FROM solicitudes_atencion WHERE id_aprendiz=? ORDER BY created_at DESC", [$id]),
                    'Salud física y signos vitales' => $this->consulta("
                        SELECT vf.fecha_atencion AS `Fecha`, vf.motivo AS `Motivo`, vf.estado_general AS `Estado general`,
                        vf.presencia_dolor AS `Presencia de dolor`, vf.intensidad_dolor AS `Intensidad`,
                        vf.ubicacion_dolor AS `Ubicación`, vf.actividad_fisica AS `Actividad física`,
                        vf.horas_sueno AS `Horas de sueño`, vf.calidad_sueno AS `Calidad de sueño`,
                        vf.antecedentes_relevantes AS `Antecedentes`, vf.observaciones AS `Observaciones`,
                        vf.recomendaciones AS `Recomendaciones`, vf.requiere_seguimiento AS `Requiere seguimiento`,
                        vf.fecha_proximo_seguimiento AS `Próximo seguimiento`
                        FROM valoraciones_salud_fisica vf WHERE vf.id_aprendiz=? ORDER BY vf.fecha_atencion DESC", [$id]),
                    'Signos vitales' => $this->consulta("
                        SELECT vf.fecha_atencion AS `Fecha de valoración`, sv.peso AS `Peso`, sv.talla AS `Talla`,
                        sv.imc AS `IMC`, sv.categoria_imc AS `Categoría IMC`,
                        CONCAT(COALESCE(sv.presion_sistolica,''), '/', COALESCE(sv.presion_diastolica,'')) AS `Presión arterial`,
                        sv.frecuencia_cardiaca AS `Frecuencia cardíaca`, sv.frecuencia_respiratoria AS `Frecuencia respiratoria`,
                        sv.temperatura AS `Temperatura`, sv.saturacion_oxigeno AS `Saturación de oxígeno`
                        FROM signos_vitales sv
                        INNER JOIN valoraciones_salud_fisica vf ON vf.id=sv.id_valoracion
                        WHERE vf.id_aprendiz=? ORDER BY vf.fecha_atencion DESC", [$id]),
                    'Nutrición' => $this->consulta("
                        SELECT fecha_atencion AS `Fecha`, peso AS `Peso`, talla AS `Talla`, imc AS `IMC`,
                        perimetro_abdominal AS `Perímetro abdominal`, habitos_alimentarios AS `Hábitos alimentarios`,
                        consumo_agua AS `Consumo de agua`, actividad_fisica AS `Actividad física`,
                        observaciones AS `Observaciones`, recomendaciones AS `Recomendaciones`,
                        requiere_seguimiento AS `Requiere seguimiento`, fecha_proximo_seguimiento AS `Próximo seguimiento`,
                        estado AS `Estado` FROM valoraciones_nutricionales WHERE id_aprendiz=? ORDER BY fecha_atencion DESC", [$id]),
                    'Recomendaciones nutricionales' => $this->consulta("
                        SELECT rn.fecha AS `Fecha`, rn.recomendacion AS `Recomendación`, rn.tipo AS `Tipo`,
                        rn.prioridad AS `Prioridad`, rn.requiere_seguimiento AS `Requiere seguimiento`,
                        rn.fecha_seguimiento AS `Fecha de seguimiento`, rn.estado AS `Estado`, rn.created_at AS `Registro creado`
                        FROM recomendaciones_nutricionales rn
                        INNER JOIN valoraciones_nutricionales vn ON vn.id=rn.id_valoracion
                        WHERE vn.id_aprendiz=? ORDER BY rn.fecha DESC", [$id]),
                    'Salud mental (acceso restringido)' => $this->consulta("
                        SELECT fecha_atencion AS `Fecha`, tipo_atencion AS `Tipo de atención`,
                        estado_general AS `Estado general`, factores_protectores AS `Factores protectores`,
                        factores_riesgo AS `Factores de riesgo`, intervencion_realizada AS `Intervención realizada`,
                        recomendaciones AS `Recomendaciones`, requiere_seguimiento AS `Requiere seguimiento`,
                        fecha_proximo_seguimiento AS `Próximo seguimiento`, remision AS `Remisión`,
                        estado AS `Estado`, fecha_cierre AS `Fecha de cierre`, observacion_cierre AS `Observación de cierre`
                        FROM seguimientos_salud_mental WHERE id_aprendiz=? ORDER BY fecha_atencion DESC", [$id]),
                    'Salud sexual (acceso restringido)' => $this->consulta("
                        SELECT fecha_atencion AS `Fecha`, tipo_atencion AS `Tipo de atención`,
                        tema_orientacion AS `Tema de orientación`, orientacion_realizada AS `Orientación realizada`,
                        requiere_seguimiento AS `Requiere seguimiento`, fecha_proximo_seguimiento AS `Próximo seguimiento`,
                        remision AS `Remisión`, estado AS `Estado`, fecha_cierre AS `Fecha de cierre`
                        FROM seguimientos_salud_sexual WHERE id_aprendiz=? ORDER BY fecha_atencion DESC", [$id]),
                ];
                break;

            default:
                throw new InvalidArgumentException('El tipo de reporte solicitado no es válido.');
        }

        $sql .= '';
        return $this->consulta($sql, $params);
    }

    private function buscarAprendiz(string $documento): ?array
    {
        $sql = "SELECT a.id, a.estado AS estado_aprendiz, a.contacto_emergencia_nombre,
            a.contacto_emergencia_telefono, a.contacto_emergencia_parentesco,
            pe.tipo_documento, pe.documento, pe.nombres, pe.apellidos, pe.fecha_nacimiento,
            pe.sexo, pe.telefono, pe.correo, pe.direccion, pe.municipio, pe.departamento,
            e.nombre AS eps
            FROM aprendices a INNER JOIN personas pe ON pe.id=a.id_persona
            LEFT JOIN eps e ON e.id=a.id_eps
            WHERE pe.documento = ? LIMIT 1";
        $rows = $this->consulta($sql, [$documento]);
        return $rows[0] ?? null;
    }

    private function formatearAprendiz(array $a): array
    {
        $map = [
            'Tipo de documento' => $a['tipo_documento'] ?? '',
            'Documento' => $a['documento'] ?? '',
            'Nombres' => $a['nombres'] ?? '',
            'Apellidos' => $a['apellidos'] ?? '',
            'Fecha de nacimiento' => $a['fecha_nacimiento'] ?? '',
            'Sexo' => $a['sexo'] ?? '',
            'Teléfono' => $a['telefono'] ?? '',
            'Correo' => $a['correo'] ?? '',
            'Dirección' => $a['direccion'] ?? '',
            'Municipio' => $a['municipio'] ?? '',
            'Departamento' => $a['departamento'] ?? '',
            'EPS' => $a['eps'] ?? '',
            'Estado del aprendiz' => $a['estado_aprendiz'] ?? '',
            'Contacto de emergencia' => $a['contacto_emergencia_nombre'] ?? '',
            'Teléfono de emergencia' => $a['contacto_emergencia_telefono'] ?? '',
            'Parentesco de emergencia' => $a['contacto_emergencia_parentesco'] ?? '',
        ];
        $rows = [];
        foreach ($map as $campo => $valor) {
            $rows[] = ['Campo' => $campo, 'Valor' => $valor];
        }
        return $rows;
    }

    private function consulta(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function where(array $where): string
    {
        return $where ? ' WHERE ' . implode(' AND ', $where) : '';
    }

    private function agregarFechas(array &$where, array &$params, string $columna, string $desde, string $hasta): void
    {
        if ($desde !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $where[] = "$columna >= ?";
            $params[] = $desde;
        }
        if ($hasta !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $where[] = "$columna <= ?";
            $params[] = $hasta . ' 23:59:59';
        }
    }
}
