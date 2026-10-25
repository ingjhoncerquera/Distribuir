<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReportesController extends Controller
{
    private ReporteModel $model;

    private const REPORTES = [
        'fichas' => 'Fichas',
        'aprendices_ficha' => 'Aprendices por ficha',
        'caracterizacion' => 'Caracterización de población',
        'aprendiz' => 'Informe integral por aprendiz',
        'resultados' => 'Resultados de aprendizaje',
        'alertas' => 'Alertas de acompañamiento',
        'remisiones' => 'Remisiones',
        'solicitudes' => 'Solicitudes de atención',
        'salud_fisica' => 'Valoraciones de salud física',
        'nutricion' => 'Valoraciones nutricionales',
    ];

    public function __construct()
    {
        $this->model = new ReporteModel();
    }

    public function index(): void
    {
        $this->autorizar();

        $tipo = $this->tipoValido(
            (string)($_GET['tipo'] ?? 'fichas')
        );

        $filtros = $this->leerFiltros($_GET);
        $datos = [];
        $error = '';

        try {
            $acceso = $this->obtenerAlcance();

            if (isset($_GET['consultar'])) {
                if ($tipo === 'aprendiz') {
                    $documento = trim(
                        (string)($filtros['documento'] ?: $filtros['buscar'])
                    );

                    if (!$acceso['administrador']
                        && !in_array($documento, $acceso['documentos'], true)
                    ) {
                        throw new RuntimeException(
                            'No tiene autorización para consultar el informe de este aprendiz.'
                        );
                    }
                }

                $datos = $this->model->ejecutar($tipo, $filtros);

                if (!$acceso['administrador']) {
                    $datos = $this->filtrarDatosPorAlcance(
                        $datos,
                        $tipo,
                        $acceso
                    );
                }

                if ($tipo === 'aprendiz') {
                    $datos = $this->filtrarHojasRestringidas($datos);
                }
            }

            $catalogos = $this->model->catalogos();

            if (!$acceso['administrador']) {
                $idsPermitidos = array_map(
                    'intval',
                    $acceso['ids_fichas']
                );

                $catalogos['fichas'] = array_values(
                    array_filter(
                        $catalogos['fichas'],
                        static fn(array $ficha): bool =>
                            in_array(
                                (int)$ficha['id'],
                                $idsPermitidos,
                                true
                            )
                    )
                );
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
            $datos = [];

            if (!isset($catalogos)) {
                $catalogos = [
                    'fichas' => [],
                    'programas' => [],
                    'jornadas' => [],
                    'poblaciones' => [],
                ];
            }
        }

        $tiposReporte = self::REPORTES;
        $tituloPagina = 'Módulo de reportes';

        $this->view('reportes/index', compact(
            'tipo',
            'filtros',
            'datos',
            'error',
            'catalogos',
            'tiposReporte',
            'tituloPagina'
        ));
    }

    public function exportar(): void
    {
        $this->autorizar();

        $tipo = $this->tipoValido(
            (string)($_GET['tipo'] ?? 'fichas')
        );

        $filtros = $this->leerFiltros($_GET);

        try {
            $acceso = $this->obtenerAlcance();

            if ($tipo === 'aprendiz') {
                $documento = trim(
                    (string)($filtros['documento'] ?: $filtros['buscar'])
                );

                if (!$acceso['administrador']
                    && !in_array($documento, $acceso['documentos'], true)
                ) {
                    throw new RuntimeException(
                        'No tiene autorización para exportar el informe de este aprendiz.'
                    );
                }
            }

            $datos = $this->model->ejecutar($tipo, $filtros);

            if (!$acceso['administrador']) {
                $datos = $this->filtrarDatosPorAlcance(
                    $datos,
                    $tipo,
                    $acceso
                );
            }

            if ($tipo === 'aprendiz') {
                $datos = $this->filtrarHojasRestringidas($datos);
            }

            $libro = new Spreadsheet();
            $libro->removeSheetByIndex(0);

            /*
             * Los informes generales del modelo devuelven una lista de filas.
             * El informe individual devuelve varias hojas con nombre.
             * Se normaliza la estructura sin alterar los datos del reporte.
             */
            if ($datos && array_is_list($datos)) {
                $datos = [self::REPORTES[$tipo] ?? 'Reporte' => $datos];
            }

            if (!$datos) {
                $datos = ['Reporte' => []];
            }

            foreach ($datos as $nombreHoja => $filas) {
                $hoja = $libro->createSheet();
                $hoja->setTitle(
                    $this->nombreHoja((string)$nombreHoja)
                );

                $filas = is_array($filas) ? $filas : [];

                if (!$filas) {
                    $hoja->setCellValue(
                        'A1',
                        'Sin registros para los filtros seleccionados.'
                    );
                    $hoja->getStyle('A1')
                        ->getFont()
                        ->setItalic(true);
                    continue;
                }

                $encabezados = array_keys($filas[0]);
                $col = 1;

                foreach ($encabezados as $encabezado) {
                    $hoja->setCellValue(
                        [$col++, 1],
                        (string)$encabezado
                    );
                }

                $filaExcel = 2;

                foreach ($filas as $fila) {
                    $col = 1;

                    foreach ($encabezados as $encabezado) {
                        $valor = $this->valorCelda(
                            $fila[$encabezado] ?? ''
                        );

                        // Mantener documentos y demás valores como texto.
                        $hoja->setCellValueExplicit(
                            [$col++, $filaExcel],
                            $valor,
                            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                        );
                    }

                    $filaExcel++;
                }

                $ultimaColumna = $hoja->getHighestColumn();

                $hoja->getStyle(
                    'A1:' . $ultimaColumna . '1'
                )->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '123B5D'],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $hoja->freezePane('A2');

                $hoja->setAutoFilter(
                    'A1:' . $ultimaColumna . max(1, $filaExcel - 1)
                );

                foreach (range(1, count($encabezados)) as $i) {
                    $hoja->getColumnDimensionByColumn($i)
                        ->setAutoSize(true);
                }

                $hoja->getDefaultRowDimension()->setRowHeight(-1);
            }

            $libro->setActiveSheetIndex(0);

            $nombre = 'SITA_' . $tipo . '_' . date('Ymd_His') . '.xls';

            if (ob_get_length()) {
                ob_end_clean();
            }

            header('Content-Type: application/vnd.ms-excel');
            header(
                'Content-Disposition: attachment; filename="' .
                $nombre . '"'
            );
            header('Cache-Control: max-age=0');

            $writer = new Xls($libro);
            $writer->save('php://output');
            $libro->disconnectWorksheets();

            exit;
        } catch (Throwable $e) {
            http_response_code(500);

            $mensaje = defined('APP_DEBUG') && APP_DEBUG
                ? $e->getMessage()
                : 'No fue posible generar el archivo Excel.';

            echo '<!doctype html><html lang="es"><meta charset="utf-8">';
            echo '<title>Error al exportar</title><body>';
            echo '<h2>No se pudo generar el reporte</h2><p>';
            echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8');
            echo '</p><p><a href="';
            echo htmlspecialchars(url('/reportes'), ENT_QUOTES, 'UTF-8');
            echo '">Volver a reportes</a></p></body></html>';

            exit;
        }
    }

    /**
     * Solo Administrador, Coordinador y Líder de Ficha pueden ingresar.
     */
    private function autorizar(): void
    {
        Auth::requireLogin();

        $rolesPermitidos = [
            'Administrador',
            'Coordinador',
            'Lider de Ficha',
            'Líder de Ficha',
        ];

        if (!Auth::hasAnyRole($rolesPermitidos)) {
            http_response_code(403);
            die('No tiene permisos para consultar los reportes de SITA.');
        }
    }

    /**
     * Obtiene las fichas y los aprendices que el usuario puede consultar.
     *
     * Administrador: alcance completo.
     * Coordinador: asignaciones COORDINADOR vigentes.
     * Líder: asignaciones LIDER_FICHA vigentes.
     */
    private function obtenerAlcance(): array
    {
        $esAdministrador = Auth::hasAnyRole(['Administrador']);

        if ($esAdministrador) {
            return [
                'administrador' => true,
                'ids_fichas' => [],
                'numeros_fichas' => [],
                'documentos' => [],
            ];
        }

        $tipoAsignacion = null;

        if (Auth::hasAnyRole(['Coordinador'])) {
            $tipoAsignacion = 'COORDINADOR';
        } elseif (Auth::hasAnyRole(['Lider de Ficha', 'Líder de Ficha'])) {
            $tipoAsignacion = 'LIDER_FICHA';
        }

        if ($tipoAsignacion === null) {
            throw new RuntimeException(
                'El usuario no tiene un rol autorizado para consultar reportes.'
            );
        }

        $usuario = Auth::user();
        $idPersona = (int)($usuario['id_persona'] ?? 0);

        if ($idPersona <= 0) {
            throw new RuntimeException(
                'No fue posible identificar a la persona asociada con la sesión.'
            );
        }

        $db = (new Database())->conectar();

        $sql = "
            SELECT DISTINCT
                f.id,
                f.numero_ficha
            FROM asignaciones_ficha af
            INNER JOIN profesionales pr
                ON pr.id = af.id_profesional
            INNER JOIN fichas f
                ON f.id = af.id_ficha
            WHERE pr.id_persona = ?
              AND af.tipo_asignacion = ?
              AND af.estado = 'activa'
              AND af.fecha_inicio <= CURRENT_DATE()
              AND (
                    af.fecha_fin IS NULL
                    OR af.fecha_fin >= CURRENT_DATE()
              )
            ORDER BY f.numero_ficha DESC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([$idPersona, $tipoAsignacion]);
        $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $idsFichas = array_values(array_unique(array_map(
            static fn(array $ficha): int => (int)$ficha['id'],
            $fichas
        )));

        $numerosFichas = array_values(array_unique(array_map(
            static fn(array $ficha): string =>
                (string)$ficha['numero_ficha'],
            $fichas
        )));

        $documentos = [];

        if ($idsFichas) {
            $marcadores = implode(
                ',',
                array_fill(0, count($idsFichas), '?')
            );

            $sqlDocumentos = "
                SELECT DISTINCT pe.documento
                FROM matriculas m
                INNER JOIN aprendices a
                    ON a.id = m.id_aprendiz
                INNER JOIN personas pe
                    ON pe.id = a.id_persona
                WHERE m.id_ficha IN ($marcadores)
                  AND pe.documento IS NOT NULL
                  AND pe.documento <> ''
            ";

            $stmtDocumentos = $db->prepare($sqlDocumentos);
            $stmtDocumentos->execute($idsFichas);

            $documentos = array_values(array_unique(array_map(
                'strval',
                $stmtDocumentos->fetchAll(PDO::FETCH_COLUMN)
            )));
        }

        return [
            'administrador' => false,
            'ids_fichas' => $idsFichas,
            'numeros_fichas' => $numerosFichas,
            'documentos' => $documentos,
        ];
    }

    /**
     * Filtra las filas del reporte según las fichas asignadas al usuario.
     * Los informes individuales se validan previamente por documento.
     */
    private function filtrarDatosPorAlcance(
        array $datos,
        string $tipo,
        array $acceso
    ): array {
        if ($acceso['administrador'] || $tipo === 'aprendiz') {
            return $datos;
        }

        /*
         * Los informes generales son listas de filas.
         */
        if (array_is_list($datos)) {
            return array_values(array_filter(
                $datos,
                fn(array $fila): bool =>
                    $this->filaDentroDelAlcance($fila, $acceso)
            ));
        }

        /*
         * Conserva la estructura de hojas del informe individual
         * si el modelo devuelve una estructura asociativa.
         */
        foreach ($datos as $nombreHoja => $filas) {
            if (!is_array($filas) || !$filas) {
                $datos[$nombreHoja] = [];
                continue;
            }

            if (!array_is_list($filas)) {
                continue;
            }

            $datos[$nombreHoja] = array_values(array_filter(
                $filas,
                fn(array $fila): bool =>
                    $this->filaDentroDelAlcance($fila, $acceso)
            ));
        }

        return $datos;
    }

    /**
     * Comprueba que una fila corresponda a una ficha o a un aprendiz
     * del alcance autorizado.
     */
    private function filaDentroDelAlcance(
        array $fila,
        array $acceso
    ): bool {
        $idsFichas = $acceso['ids_fichas'];
        $numerosFichas = $acceso['numeros_fichas'];
        $documentos = $acceso['documentos'];

        foreach (['Número de ficha', 'Ficha'] as $campoFicha) {
            if (array_key_exists($campoFicha, $fila)) {
                return in_array(
                    (string)$fila[$campoFicha],
                    $numerosFichas,
                    true
                );
            }
        }

        /*
         * Las alertas presentan las fichas en una columna separada
         * por comas. Se valida cada número de ficha individualmente.
         */
        if (array_key_exists('Fichas', $fila)) {
            $fichasFila = array_map(
                'trim',
                explode(',', (string)$fila['Fichas'])
            );

            foreach ($fichasFila as $numeroFicha) {
                if (in_array($numeroFicha, $numerosFichas, true)) {
                    return true;
                }
            }

            return false;
        }

        if (array_key_exists('Documento', $fila)) {
            return in_array(
                trim((string)$fila['Documento']),
                $documentos,
                true
            );
        }

        /*
         * Si la fila no contiene un campo que permita verificar
         * su alcance, no se entrega al usuario restringido.
         */
        return false;
    }

    private function filtrarHojasRestringidas(array $datos): array
    {
        $esAdmin = Auth::hasAnyRole(['Administrador']);

        $permisosComponentes = [
            'Caracterización socioeconómica (CS)' =>
                'bienestar.caracterizacion.ver',
            'Promoción y prevención (PP)' =>
                'bienestar.prevencion.ver',
            'Habilidades blandas (HB)' =>
                'bienestar.habilidades_blandas.ver',
            'Actividad física (AF)' =>
                'bienestar.actividad_fisica.ver',
            'Habilidades artísticas (HA)' =>
                'bienestar.habilidades_artisticas.ver',
            'Historial de actualización de caracterización' =>
                'bienestar.caracterizacion.ver',
            'Clasificación de población' =>
                'bienestar.caracterizacion.ver',
        ];

        foreach ($permisosComponentes as $hoja => $permiso) {
            if (!$esAdmin && !Auth::can($permiso)) {
                unset($datos[$hoja]);
            }
        }

        $puedeMental = $esAdmin
            || Auth::can('bienestar.salud_mental.ver')
            || Auth::hasAnyRole(['Psicologo', 'Psicólogo']);

        $puedeSexual = $esAdmin
            || Auth::can('bienestar.salud_sexual.ver')
            || Auth::hasAnyRole([
                'Psicologo',
                'Psicólogo',
                'Enfermero',
            ]);

        if (!$puedeMental) {
            unset($datos['Salud mental (acceso restringido)']);
        }

        if (!$puedeSexual) {
            unset($datos['Salud sexual (acceso restringido)']);
        }

        return $datos;
    }

    private function leerFiltros(array $origen): array
    {
        $f = [];

        foreach (['buscar', 'documento', 'estado', 'desde', 'hasta'] as $key) {
            $f[$key] = trim((string)($origen[$key] ?? ''));
        }

        foreach ([
            'id_ficha',
            'id_programa',
            'id_jornada',
            'id_poblacion',
        ] as $key) {
            $f[$key] = max(0, (int)($origen[$key] ?? 0));
        }

        return $f;
    }

    private function tipoValido(string $tipo): string
    {
        return array_key_exists($tipo, self::REPORTES)
            ? $tipo
            : 'fichas';
    }

    /** Convierte cualquier dato complejo a texto exportable sin warnings. */
    private function valorCelda(mixed $valor): string
    {
        if ($valor === null) {
            return '';
        }

        if (is_bool($valor)) {
            return $valor ? 'Sí' : 'No';
        }

        if (is_scalar($valor)) {
            return (string)$valor;
        }

        if (is_object($valor)) {
            $valor = method_exists($valor, '__toString')
                ? (string)$valor
                : get_object_vars($valor);
        }

        if (is_array($valor)) {
            $partes = [];

            foreach ($valor as $clave => $elemento) {
                $texto = $this->valorCelda($elemento);

                if ($texto === '') {
                    continue;
                }

                $partes[] = is_string($clave)
                    ? $clave . ': ' . $texto
                    : $texto;
            }

            return implode('; ', $partes);
        }

        return '';
    }

    private function nombreHoja(string $nombre): string
    {
        $nombre = preg_replace(
            '/[\\\\\/\?\*\[\]:]/u',
            ' ',
            $nombre
        ) ?? 'Reporte';

        $nombre = trim($nombre);

        return mb_substr(
            $nombre !== '' ? $nombre : 'Reporte',
            0,
            31
        );
    }
}