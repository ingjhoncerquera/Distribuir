<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

require_once __DIR__ . '/../models/ImportacionModel.php';

class ImportacionController extends Controller
{
    /**
     * Autoriza la importación de juicios evaluativos.
     * El Administrador conserva el acceso y los demás usuarios
     * requieren el permiso academico.importar.
     */
    private function autorizarImportacion(): void
    {
        Auth::requireLogin();

        if (
            !Auth::hasAnyRole(['Administrador'])
            && !Auth::can('academico.importar')
        ) {
            http_response_code(403);

            die(
                'No tiene permisos para importar juicios evaluativos.'
            );
        }
    }

    /**
     * Impide que un Coordinador importe reportes de fichas no asignadas.
     * Para coordinadores solo se admite una ficha que ya exista y tenga
     * una asignación COORDINADOR activa y vigente para su usuario.
     * Administradores y Líderes de Ficha conservan su flujo actual.
     */
    private function validarFichaCoordinador(string $numeroFicha): ?string
    {
        if (!Auth::hasAnyRole(['Coordinador'])) {
            return null;
        }

        $numeroFicha = trim($numeroFicha);

        if ($numeroFicha === '') {
            return 'El reporte no contiene un número de ficha válido.';
        }

        $db = (new Database())->conectar();

        $sql = "
            SELECT 1
            FROM fichas f
            INNER JOIN asignaciones_ficha af
                ON af.id_ficha = f.id
            INNER JOIN profesionales pr
                ON pr.id = af.id_profesional
            INNER JOIN usuarios u
                ON u.id_persona = pr.id_persona
            WHERE f.numero_ficha = ?
              AND u.id = ?
              AND af.tipo_asignacion = 'COORDINADOR'
              AND af.estado = 'activa'
              AND af.fecha_inicio <= CURRENT_DATE
              AND (af.fecha_fin IS NULL OR af.fecha_fin >= CURRENT_DATE)
              AND pr.estado = 'activo'
            LIMIT 1
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([$numeroFicha, (int) Auth::id()]);

        if (!$stmt->fetchColumn()) {
            return 'No puede importar este reporte: la ficha ' . $numeroFicha
                . ' no está asignada actualmente a su usuario como Coordinador.';
        }

        return null;
    }

    public function index(): void
    {
        $this->autorizarImportacion();

        $this->view(
            'importacion/index',
            [
                'tituloPagina' => 'Importar reporte SENA',
            ]
        );
    }

    public function leer(): void
    {
        $this->autorizarImportacion();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/importar');
            return;
        }

        $token = $_POST['csrf_token'] ?? '';

        if (
            !isset($_SESSION['csrf_token']) ||
            !is_string($token) ||
            !hash_equals($_SESSION['csrf_token'], $token)
        ) {
            $this->view(
                'importacion/index',
                [
                    'tituloPagina' => 'Importar reporte SENA',
                    'error' => 'La solicitud no es válida. Actualice la página e inténtelo nuevamente.',
                ]
            );

            return;
        }

        if (
            !isset($_FILES['archivo']) ||
            !is_array($_FILES['archivo'])
        ) {
            $this->view(
                'importacion/index',
                [
                    'tituloPagina' => 'Importar reporte SENA',
                    'error' => 'No se recibió ningún archivo.',
                ]
            );

            return;
        }

        $archivo = $_FILES['archivo'];

        if (
            !isset($archivo['error']) ||
            $archivo['error'] !== UPLOAD_ERR_OK
        ) {
            $mensaje = $this->mensajeErrorUpload(
                (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE)
            );

            $this->view(
                'importacion/index',
                [
                    'tituloPagina' => 'Importar reporte SENA',
                    'error' => $mensaje,
                ]
            );

            return;
        }

        if (
            !isset($archivo['tmp_name']) ||
            !is_string($archivo['tmp_name']) ||
            !is_uploaded_file($archivo['tmp_name'])
        ) {
            $this->view(
                'importacion/index',
                [
                    'tituloPagina' => 'Importar reporte SENA',
                    'error' => 'El archivo recibido no es válido.',
                ]
            );

            return;
        }

        $tamanoMaximo = 20 * 1024 * 1024;

        if (
            isset($archivo['size']) &&
            (int) $archivo['size'] > $tamanoMaximo
        ) {
            $this->view(
                'importacion/index',
                [
                    'tituloPagina' => 'Importar reporte SENA',
                    'error' => 'El archivo supera el tamaño máximo permitido de 20 MB.',
                ]
            );

            return;
        }

        $nombreOriginal = isset($archivo['name'])
            ? (string) $archivo['name']
            : '';

        $extension = strtolower(
            pathinfo(
                $nombreOriginal,
                PATHINFO_EXTENSION
            )
        );

        if (
            !in_array(
                $extension,
                ['xls', 'xlsx'],
                true
            )
        ) {
            $this->view(
                'importacion/index',
                [
                    'tituloPagina' => 'Importar reporte SENA',
                    'error' => 'El archivo debe estar en formato XLS o XLSX.',
                ]
            );

            return;
        }

        try {

            /*
             * =========================================================
             * CARGAR ARCHIVO EXCEL
             * =========================================================
             */

            $spreadsheet =
                IOFactory::load(
                    $archivo['tmp_name']
                );

            $cantidadHojas =
                $spreadsheet->getSheetCount();

            $sheet =
                $spreadsheet->getActiveSheet();

            $nombreHoja =
                $sheet->getTitle();

            $highestRow =
                $sheet->getHighestRow();

            $highestColumn =
                $sheet->getHighestColumn();

            $highestColumnIndex =
                Coordinate::columnIndexFromString(
                    $highestColumn
                );

            /*
             * =========================================================
             * BUSCAR ENCABEZADOS DEL DETALLE
             * =========================================================
             */

            $filaEncabezados = null;

            $mapaColumnas = [];

            $encabezadosRequeridos = [
    'tipo_documento',
    'numero_documento',
    'nombre',
    'apellidos',
    'estado',
    'competencia',
    'resultado_aprendizaje',
    'juicio_evaluacion',
];

            $limiteBusqueda =
                min(
                    $highestRow,
                    100
                );

            for (
                $fila = 1;
                $fila <= $limiteBusqueda;
                $fila++
            ) {

                $encabezadosFila = [];

                for (
                    $columna = 1;
                    $columna <= $highestColumnIndex;
                    $columna++
                ) {

                    $valor =
                        $this->getCellValue(
                            $sheet,
                            $columna,
                            $fila
                        );

                    $normalizado =
                        $this->normalizarEncabezado(
                            $valor
                        );

                    if (
                        $normalizado !== ''
                    ) {
                        $encabezadosFila[
                            $normalizado
                        ] = $columna;
                    }
                }

                $encontrados = 0;

                foreach (
                    $encabezadosRequeridos
                    as $requerido
                ) {

                    if (
                        isset(
                            $encabezadosFila[
                                $requerido
                            ]
                        )
                    ) {
                        $encontrados++;
                    }
                }

                if (
                    $encontrados === count($encabezadosRequeridos)
                ) {

                    $filaEncabezados =
                        $fila;

                    $mapaColumnas =
                        $encabezadosFila;

                    break;
                }
            }

            if (
                $filaEncabezados === null
            ) {
                throw new RuntimeException(
                    'No fue posible identificar la fila de encabezados del reporte SENA.'
                );
            }

            /*
             * =========================================================
             * LEER CABECERA SENA
             * =========================================================
             *
             * La estructura real del reporte proporcionado es:
             *
             * A2 = Fecha del Reporte:
             * B2 = 14/09/2026
             *
             * A3 = Ficha de Caracterización:
             * B3 = 3282766
             *
             * A4 = Cógigo:
             * B4 = 228118
             *
             * A5 = Versión:
             * B5 = 1
             *
             * A6 = Denominación:
             * B6 = ANALISIS Y DESARROLLO DE SOFTWARE.
             *
             * A7 = Estado de la Ficha de Caracterización:
             * B7 = EN EJECUCION
             *
             * A8 = Fecha Inicio:
             * B8 = 25/07/2025
             *
             * A9 = Fecha Fin:
             * B9 = 24/10/2027
             *
             * A10 = Modalidad de Formación:
             * B10 = VIRTUAL
             *
             * A11 = Regional:
             * B11 = 41 - REGIONAL HUILA
             *
             * A12 = Centro de Formación:
             * B12 = 9528 - CENTRO DE GESTION Y DESARROLLO SOSTENIBLE SURCOLOMBIANO
             *
             * Se hace una lectura directa de esta estructura para que
             * la información SENA no dependa de la detección de la
             * fila de encabezados del detalle.
             * =========================================================
             */

            $datosFicha = [
                'fecha_reporte' => '',
                'ficha' => '',
                'codigo_programa' => '',
                'version_programa' => '',
                'denominacion' => '',
                'estado_ficha' => '',
                'fecha_inicio' => '',
                'fecha_fin' => '',
                'modalidad' => '',
                'regional' => '',
                'centro' => '',
            ];

            /*
             * =========================================================
             * LECTURA DIRECTA DEL FORMATO REAL DEL REPORTE
             * =========================================================
             */

            $filasCabeceraDirecta = [

                2 => 'fecha_reporte',

                3 => 'ficha',

                4 => 'codigo_programa',

                5 => 'version_programa',

                6 => 'denominacion',

                7 => 'estado_ficha',

                8 => 'fecha_inicio',

                9 => 'fecha_fin',

                10 => 'modalidad',

                11 => 'regional',

                12 => 'centro',
            ];

            foreach (
                $filasCabeceraDirecta
                as $filaCabecera => $campoCabecera
            ) {

                if (
                    $filaCabecera > $highestRow
                ) {
                    continue;
                }

                $valorCabecera =
                    $this->getCellValue(
                        $sheet,
                        2,
                        $filaCabecera
                    );

                $valorCabecera =
                    $this->limpiarValorCabecera(
                        $valorCabecera
                    );

                if (
                    $valorCabecera === ''
                ) {
                    continue;
                }

                if (
                    $campoCabecera === 'estado_ficha'
                ) {

                    $valorCabecera =
                        preg_replace(
                            '/^DE\s+CARACTERIZACION\s*:?\s*/iu',
                            '',
                            $valorCabecera
                        ) ?? $valorCabecera;

                    $valorCabecera =
                        preg_replace(
                            '/^DE\s+CARACTERIZACI[ÓO]N\s*:?\s*/iu',
                            '',
                            $valorCabecera
                        ) ?? $valorCabecera;

                    $valorCabecera =
                        trim($valorCabecera);
                }

                $datosFicha[$campoCabecera] =
                    $valorCabecera;
            }

            /*
             * =========================================================
             * ETIQUETAS DE CABECERA
             * =========================================================
             *
             * Se conservan como respaldo para otras versiones del
             * reporte SENA.
             * =========================================================
             */

            $etiquetasCabecera = [

                'fecha_reporte' => [
                    'fecha del reporte',
                    'fecha reporte',
                    'fecha de reporte',
                ],

                'ficha' => [
                    'ficha',
                    'ficha de caracterizacion',
                    'ficha de caracterización',
                ],

                'codigo_programa' => [
                    'codigo',
                    'código',
                    'cogigo',
                    'cógigo',
                    'codigo del programa',
                    'código del programa',
                    'codigo programa',
                    'código programa',
                ],

                'version_programa' => [
                    'version',
                    'versión',
                    'version del programa',
                    'versión del programa',
                ],

                'denominacion' => [
                    'denominacion',
                    'denominación',
                    'denominacion del programa',
                    'denominación del programa',
                ],

                'estado_ficha' => [
                    'estado',
                    'estado de la ficha',
                    'estado ficha',
                    'estado de la ficha de caracterizacion',
                    'estado de la ficha de caracterización',
                    'estado de la ficha de caracterizacion',
                ],

                'fecha_inicio' => [
                    'fecha inicio',
                    'fecha de inicio',
                ],

                'fecha_fin' => [
                    'fecha fin',
                    'fecha de fin',
                ],

                'modalidad' => [
                    'modalidad',
                    'modalidad de formacion',
                    'modalidad de formación',
                ],

                'regional' => [
                    'regional',
                ],

                'centro' => [
                    'centro',
                    'centro de formacion',
                    'centro de formación',
                ],
            ];

            /*
             * =========================================================
             * LÍMITE DE BÚSQUEDA DE CABECERA SENA
             * =========================================================
             */

            $limiteCabeceraSena =
                min(
                    $highestRow,
                    50
                );

            /*
             * =========================================================
             * PRIMERA LECTURA DIRECTA DE CABECERA
             * =========================================================
             *
             * Se conserva como respaldo.
             * Los campos que ya fueron encontrados directamente
             * arriba no son reemplazados.
             * =========================================================
             */

            for (
                $fila = 1;
                $fila <= $limiteCabeceraSena;
                $fila++
            ) {

                $etiqueta =
                    $this->getCellValue(
                        $sheet,
                        1,
                        $fila
                    );

                $valor =
                    $this->getCellValue(
                        $sheet,
                        2,
                        $fila
                    );

                if (
                    trim($etiqueta) === ''
                ) {
                    continue;
                }

                $etiquetaNormalizada =
                    $this->normalizarEtiquetaCabecera(
                        $etiqueta
                    );

                if (
                    $etiquetaNormalizada === ''
                ) {
                    continue;
                }

                foreach (
                    $etiquetasCabecera
                    as $campo => $posiblesEtiquetas
                ) {

                    if (
                        $datosFicha[$campo] !== ''
                    ) {
                        continue;
                    }

                    $esEtiqueta = false;

                    foreach (
                        $posiblesEtiquetas
                        as $posible
                    ) {

                        $etiquetaEsperada =
                            $this->normalizarEtiquetaCabecera(
                                $posible
                            );

                        if (
                            $etiquetaNormalizada ===
                            $etiquetaEsperada
                        ) {

                            $esEtiqueta = true;

                            break;
                        }
                    }

                    if (
                        !$esEtiqueta
                    ) {
                        continue;
                    }

                    $valor =
                        $this->limpiarValorCabecera(
                            $valor
                        );

                    if (
                        $valor === ''
                    ) {
                        continue;
                    }

                    if (
                        $campo === 'estado_ficha'
                    ) {

                        $valor =
                            preg_replace(
                                '/^DE\s+CARACTERIZACION\s*:?\s*/iu',
                                '',
                                $valor
                            ) ?? $valor;

                        $valor =
                            preg_replace(
                                '/^DE\s+CARACTERIZACI[ÓO]N\s*:?\s*/iu',
                                '',
                                $valor
                            ) ?? $valor;

                        $valor =
                            trim($valor);
                    }

                    $datosFicha[$campo] =
                        $valor;

                    break;
                }
            }

            /*
             * =========================================================
             * RECORRER CABECERA
             * =========================================================
             *
             * Esta segunda lectura se conserva para soportar otras
             * versiones del reporte SENA donde las etiquetas y
             * valores pueden encontrarse en posiciones diferentes.
             * =========================================================
             */

            for (
                $fila = 1;
                $fila <= $limiteCabeceraSena;
                $fila++
            ) {

                $celdasFila = [];

                for (
                    $columna = 1;
                    $columna <= $highestColumnIndex;
                    $columna++
                ) {

                    $valor =
                        $this->getCellValue(
                            $sheet,
                            $columna,
                            $fila
                        );

                    if (
                        trim($valor) === ''
                    ) {
                        continue;
                    }

                    $celdasFila[] = [
                        'columna' => $columna,
                        'valor' => trim($valor),
                        'normalizado' =>
                            $this->normalizarEtiquetaCabecera(
                                $valor
                            ),
                    ];
                }

                if (
                    !$celdasFila
                ) {
                    continue;
                }

                /*
                 * -----------------------------------------------------
                 * PRIMERA PASADA
                 * -----------------------------------------------------
                 */

                foreach (
                    $celdasFila
                    as $indice => $celda
                ) {

                    $etiquetaCelda =
                        $celda['normalizado'];

                    foreach (
                        $etiquetasCabecera
                        as $campo => $posiblesEtiquetas
                    ) {

                        if (
                            $datosFicha[$campo] !== ''
                        ) {
                            continue;
                        }

                        $esEtiqueta = false;

                        foreach (
                            $posiblesEtiquetas
                            as $posible
                        ) {

                            $etiquetaEsperada =
                                $this->normalizarEtiquetaCabecera(
                                    $posible
                                );

                            if (
                                $etiquetaCelda ===
                                $etiquetaEsperada
                            ) {
                                $esEtiqueta = true;
                                break;
                            }
                        }

                        if (
                            !$esEtiqueta
                        ) {
                            continue;
                        }

                        for (
                            $j = $indice + 1;
                            $j < count($celdasFila);
                            $j++
                        ) {

                            $valor =
                                trim(
                                    (string)
                                    $celdasFila[$j]['valor']
                                );

                            if (
                                $valor === ''
                            ) {
                                continue;
                            }

                            $esOtraEtiqueta = false;

                            foreach (
                                $etiquetasCabecera
                                as $etiquetasComparacion
                            ) {

                                foreach (
                                    $etiquetasComparacion
                                    as $etiquetaComparacion
                                ) {

                                    if (
                                        $celdasFila[$j]['normalizado'] ===
                                        $this->normalizarEtiquetaCabecera(
                                            $etiquetaComparacion
                                        )
                                    ) {

                                        $esOtraEtiqueta = true;

                                        break 2;
                                    }
                                }
                            }

                            if (
                                $esOtraEtiqueta
                            ) {
                                break;
                            }

                            $valor =
                                $this->limpiarValorCabecera(
                                    $valor
                                );

                            if (
                                $valor === ''
                            ) {
                                continue;
                            }

                            if (
                                $campo === 'estado_ficha'
                            ) {

                                $valor =
                                    preg_replace(
                                        '/^DE\s+CARACTERIZACION\s*:?\s*/iu',
                                        '',
                                        $valor
                                    ) ?? $valor;

                                $valor =
                                    preg_replace(
                                        '/^DE\s+CARACTERIZACI[ÓO]N\s*:?\s*/iu',
                                        '',
                                        $valor
                                    ) ?? $valor;

                                $valor =
                                    trim($valor);
                            }

                            $datosFicha[$campo] =
                                $valor;

                            break;
                        }
                    }
                }

                /*
                 * -----------------------------------------------------
                 * SEGUNDA PASADA
                 * -----------------------------------------------------
                 */

                foreach (
                    $celdasFila as $celda
                ) {

                    $valorCompleto =
                        $this->limpiarValorCabecera(
                            $celda['valor']
                        );

                    if (
                        $valorCompleto === ''
                    ) {
                        continue;
                    }

                    $normalizadoCompleto =
                        $this->normalizarEtiquetaCabecera(
                            $valorCompleto
                        );

                    foreach (
                        $etiquetasCabecera
                        as $campo => $posiblesEtiquetas
                    ) {

                        if (
                            $datosFicha[$campo] !== ''
                        ) {
                            continue;
                        }

                        foreach (
                            $posiblesEtiquetas
                            as $posible
                        ) {

                            $etiqueta =
                                $this->normalizarEtiquetaCabecera(
                                    $posible
                                );

                            if (
                                str_starts_with(
                                    $normalizadoCompleto,
                                    $etiqueta . ' '
                                ) ||
                                str_starts_with(
                                    $normalizadoCompleto,
                                    $etiqueta . ':'
                                )
                            ) {

                                $valor =
                                    preg_replace(
                                        '/^'
                                        . preg_quote(
                                            $etiqueta,
                                            '/'
                                        )
                                        . '\s*:?\s*/iu',
                                        '',
                                        $valorCompleto
                                    );

                                $valor =
                                    $this->limpiarValorCabecera(
                                        (string) $valor
                                    );

                                if (
                                    $valor !== ''
                                ) {

                                    if (
                                        $campo === 'estado_ficha'
                                    ) {

                                        $valor =
                                            preg_replace(
                                                '/^DE\s+CARACTERIZACION\s*:?\s*/iu',
                                                '',
                                                $valor
                                            ) ?? $valor;

                                        $valor =
                                            preg_replace(
                                                '/^DE\s+CARACTERIZACI[ÓO]N\s*:?\s*/iu',
                                                '',
                                                $valor
                                            ) ?? $valor;

                                        $valor =
                                            trim($valor);
                                    }

                                    $datosFicha[$campo] =
                                        $valor;
                                }

                                break;
                            }
                        }
                    }
                }
            }

            /*
             * =========================================================
             * BÚSQUEDA ADICIONAL DE FECHAS
             * =========================================================
             */

            foreach (
                [
                    'fecha_reporte' => [
                        'fecha del reporte',
                        'fecha reporte',
                        'fecha de reporte',
                    ],

                    'fecha_inicio' => [
                        'fecha inicio',
                        'fecha de inicio',
                    ],

                    'fecha_fin' => [
                        'fecha fin',
                        'fecha de fin',
                    ],
                ]
                as $campoFecha => $etiquetasFecha
            ) {

                if (
                    $datosFicha[$campoFecha] !== ''
                ) {
                    continue;
                }

                for (
                    $fila = 1;
                    $fila <= $limiteCabeceraSena;
                    $fila++
                ) {

                    for (
                        $columna = 1;
                        $columna <= $highestColumnIndex;
                        $columna++
                    ) {

                        $valorEtiqueta =
                            $this->getCellValue(
                                $sheet,
                                $columna,
                                $fila
                            );

                        if (
                            $valorEtiqueta === ''
                        ) {
                            continue;
                        }

                        $normalizado =
                            $this->normalizarEtiquetaCabecera(
                                $valorEtiqueta
                            );

                        $coincide = false;

                        foreach (
                            $etiquetasFecha
                            as $etiquetaFecha
                        ) {

                            if (
                                $normalizado ===
                                $this->normalizarEtiquetaCabecera(
                                    $etiquetaFecha
                                )
                            ) {

                                $coincide = true;
                                break;
                            }
                        }

                        if (
                            !$coincide
                        ) {
                            continue;
                        }

                        for (
                            $filaBusqueda = $fila;
                            $filaBusqueda <=
                            min(
                                $fila + 2,
                                $limiteCabeceraSena
                            );
                            $filaBusqueda++
                        ) {

                            for (
                                $columnaBusqueda =
                                    max(
                                        1,
                                        $columna - 2
                                    );

                                $columnaBusqueda <=
                                min(
                                    $highestColumnIndex,
                                    $columna + 5
                                );

                                $columnaBusqueda++
                            ) {

                                $valorFecha =
                                    $this->getCellValue(
                                        $sheet,
                                        $columnaBusqueda,
                                        $filaBusqueda
                                    );

                                if (
                                    $valorFecha === ''
                                ) {
                                    continue;
                                }

                                $fechaNormalizada =
                                    $this->normalizarFechaExcel(
                                        $valorFecha
                                    );

                                if (
                                    preg_match(
                                        '/^\d{4}-\d{2}-\d{2}$/',
                                        $fechaNormalizada
                                    )
                                ) {

                                    $datosFicha[$campoFecha] =
                                        $fechaNormalizada;

                                    break 3;
                                }
                            }
                        }
                    }
                }
            }

            /*
             * =========================================================
             * LIMPIEZA FINAL DE DATOS DE CABECERA
             * =========================================================
             */

            foreach (
                [
                    'ficha',
                    'codigo_programa',
                    'version_programa',
                    'denominacion',
                    'estado_ficha',
                    'modalidad',
                    'regional',
                    'centro',
                ]
                as $campoTexto
            ) {

                $datosFicha[$campoTexto] =
                    $this->limpiarValorCabecera(
                        (string)
                        $datosFicha[$campoTexto]
                    );
            }

            /*
             * =========================================================
             * CONVERTIR FECHAS DE LA CABECERA
             * =========================================================
             */

            $datosFicha['fecha_reporte'] =
                $this->normalizarFechaExcel(
                    $datosFicha['fecha_reporte']
                );

            $datosFicha['fecha_inicio'] =
                $this->normalizarFechaExcel(
                    $datosFicha['fecha_inicio']
                );

            $datosFicha['fecha_fin'] =
                $this->normalizarFechaExcel(
                    $datosFicha['fecha_fin']
                );

            /*
             * =========================================================
             * COLUMNAS DEL DETALLE
             * =========================================================
             */

            $colTipoDocumento =
                $mapaColumnas[
                    'tipo_documento'
                ] ?? null;

            $colNumeroDocumento =
                $mapaColumnas[
                    'numero_documento'
                ] ?? null;

            $colNombre =
                $mapaColumnas[
                    'nombre'
                ] ?? null;

            $colApellidos =
                $mapaColumnas[
                    'apellidos'
                ] ?? null;
				
			$colEstado = $mapaColumnas['estado'] ?? null;

            $colCompetencia =
                $mapaColumnas[
                    'competencia'
                ] ?? null;

            $colRAP =
                $mapaColumnas[
                    'resultado_aprendizaje'
                ] ?? null;

            $colJuicio =
                $mapaColumnas[
                    'juicio_evaluacion'
                ] ?? null;
				
				

            $colFechaJuicio =
                $mapaColumnas[
                    'fecha_y_hora_del_juicio_evaluativo'
                ] ?? null;

            $colFuncionario =
                $mapaColumnas[
                    'funcionario_que_registro_el_juicio_evaluativo'
                ] ?? null;

            /*
             * =========================================================
             * LEER DETALLE
             * =========================================================
             */

            $filasDetalle = 0;

            $aprendices = [];

            $competencias = [];

            $rap = [];

            $funcionarios = [];

            $resultados = [];

            $juicios = [
    'APROBADO' => 0,
    'POR EVALUAR' => 0,
];

$contadorAprobado = 0;
$contadorPorEvaluar = 0;
$contadorOtrosJuicios = 0;

            $otrosJuicios = 0;

            /*
             * Estadísticas de fechas
             */

            $fechaMinimaEvaluacion = null;

            $fechaMaximaEvaluacion = null;

            $evaluacionesSinFecha = 0;

            $resultadosPorAnio = [];

            for (
                $fila =
                    $filaEncabezados + 1;

                $fila <= $highestRow;

                $fila++
            ) {

                $numeroDocumento =
                    $colNumeroDocumento !== null
                        ? trim(
                            $this->getCellValue(
                                $sheet,
                                $colNumeroDocumento,
                                $fila
                            )
                        )
                        : '';

                $nombre =
                    $colNombre !== null
                        ? trim(
                            $this->getCellValue(
                                $sheet,
                                $colNombre,
                                $fila
                            )
                        )
                        : '';

                $apellidos =
                    $colApellidos !== null
                        ? trim(
                            $this->getCellValue(
                                $sheet,
                                $colApellidos,
                                $fila
                            )
                        )
                        : '';

                $competencia =
                    $colCompetencia !== null
                        ? trim(
                            $this->getCellValue(
                                $sheet,
                                $colCompetencia,
                                $fila
                            )
                        )
                        : '';

                $resultadoAprendizaje =
                    $colRAP !== null
                        ? trim(
                            $this->getCellValue(
                                $sheet,
                                $colRAP,
                                $fila
                            )
                        )
                        : '';

                $juicio = '';

if ($colJuicio !== null) {

    $coordenadaJuicio =
        Coordinate::stringFromColumnIndex(
            $colJuicio
        ) . $fila;

    $valorJuicio =
        $sheet
            ->getCell($coordenadaJuicio)
            ->getValue();

    if ($valorJuicio !== null) {
        $juicio = trim((string) $valorJuicio);
    }
}



$juicioComparacion = strtoupper(trim($juicio));

if ($juicioComparacion === 'APROBADO') {

    $contadorAprobado++;

} elseif ($juicioComparacion === 'POR EVALUAR') {

    $contadorPorEvaluar++;

} elseif ($juicioComparacion !== '') {

    $contadorOtrosJuicios++;
}




                $fechaJuicio =
                    $colFechaJuicio !== null
                        ? trim(
                            $this->getCellValue(
                                $sheet,
                                $colFechaJuicio,
                                $fila
                            )
                        )
                        : '';

                $funcionario =
                    $colFuncionario !== null
                        ? trim(
                            $this->getCellValue(
                                $sheet,
                                $colFuncionario,
                                $fila
                            )
                        )
                        : '';

                if (
                    $numeroDocumento === '' &&
                    $nombre === '' &&
                    $apellidos === '' &&
                    $competencia === '' &&
                    $resultadoAprendizaje === '' &&
                    $juicio === ''
                ) {
                    continue;
                }

                $filasDetalle++;

                /*
                 * =====================================================
                 * FECHA DEL JUICIO EVALUATIVO
                 * =====================================================
                 */

                $fechaJuicioNormalizada =
                    $this->normalizarFechaHoraExcel(
                        $fechaJuicio
                    );

                $fechaSolo =
                    $this->obtenerFechaDesdeFechaHora(
                        $fechaJuicioNormalizada
                    );

                if (
                    $fechaSolo === ''
                ) {

                    $evaluacionesSinFecha++;

                } else {

                    $anio =
                        (int) substr(
                            $fechaSolo,
                            0,
                            4
                        );

                    if (
                        !isset(
                            $resultadosPorAnio[$anio]
                        )
                    ) {
                        $resultadosPorAnio[$anio] = 0;
                    }

                    $resultadosPorAnio[$anio]++;

                    if (
                        $fechaMinimaEvaluacion === null ||
                        $fechaSolo <
                        $fechaMinimaEvaluacion
                    ) {
                        $fechaMinimaEvaluacion =
                            $fechaSolo;
                    }

                    if (
                        $fechaMaximaEvaluacion === null ||
                        $fechaSolo >
                        $fechaMaximaEvaluacion
                    ) {
                        $fechaMaximaEvaluacion =
                            $fechaSolo;
                    }
                }

                /*
                 * =====================================================
                 * APRENDIZ
                 * =====================================================
                 */

                if (
                    $numeroDocumento !== ''
                ) {

                    $aprendices[$numeroDocumento] = [
    'tipo_documento' =>
        $colTipoDocumento !== null
            ? trim($this->getCellValue($sheet, $colTipoDocumento, $fila))
            : '',

    'numero_documento' => $numeroDocumento,

    'nombre' => $nombre,

    'apellidos' => $apellidos,

    'estado' =>
        $colEstado !== null
            ? strtoupper(
                trim(
                    $this->normalizarTexto(
                        $this->getCellValue(
                            $sheet,
                            $colEstado,
                            $fila
                        )
                    )
                )
            )
            : '',
];
                }

                /*
                 * =====================================================
                 * COMPETENCIA
                 * =====================================================
                 */

                if (
                    $competencia !== ''
                ) {

                    $competencias[
                        $competencia
                    ] = $competencia;
                }

                /*
                 * =====================================================
                 * RAP
                 * =====================================================
                 */

                if (
                    $resultadoAprendizaje !== ''
                ) {

                    $rap[
                        $resultadoAprendizaje
                    ] =
                        $resultadoAprendizaje;
                }

                /*
                 * =====================================================
                 * JUICIO
                 * =====================================================
                 */

                $juicio = strtoupper(trim($juicio));

if ($juicio === 'APROBADO') {

    $juicios['APROBADO']++;

} elseif ($juicio === 'POR EVALUAR') {

    $juicios['POR EVALUAR']++;

} elseif ($juicio !== '') {

    $otrosJuicios++;
}

                /*
                 * =====================================================
                 * FUNCIONARIO
                 * =====================================================
                 */

                if (
                    $funcionario !== '' &&
                    $funcionario !== '-'
                ) {

                    $funcionarios[
                        $funcionario
                    ] =
                        $funcionario;
                }

                /*
                 * =====================================================
                 * RESULTADO INDIVIDUAL
                 * =====================================================
                 */

                $resultados[] = [

                    'fila_excel' =>
                        $fila,

                    'tipo_documento' =>
                        $colTipoDocumento !== null
                            ? trim(
                                $this->getCellValue(
                                    $sheet,
                                    $colTipoDocumento,
                                    $fila
                                )
                            )
                            : '',

                    'numero_documento' =>
                        $numeroDocumento,

                    'nombre' =>
                        $nombre,

                    'apellidos' =>
                        $apellidos,

                    'ficha' =>
                        $datosFicha['ficha'],

                    'competencia' =>
                        $competencia,

                    'resultado_aprendizaje' =>
                        $resultadoAprendizaje,

                    'juicio' =>
                        $juicio,

                    'fecha_hora_juicio' =>
                        $fechaJuicioNormalizada,

                    'fecha_evaluacion' =>
                        $fechaSolo !== ''
                            ? $fechaSolo
                            : null,

                    'vigencia' =>
                        $fechaSolo !== ''
                            ? (int) substr(
                                $fechaSolo,
                                0,
                                4
                            )
                            : null,

                    'funcionario' =>
                        $funcionario,
                ];
            }

            /*
             * =========================================================
             * ORDENAR AÑOS
             * =========================================================
             */

            ksort(
                $resultadosPorAnio
            );

            /*
             * =========================================================
             * RESULTADO BASE
             * =========================================================
             */

$juicios = [
    'APROBADO' => $contadorAprobado,
    'POR EVALUAR' => $contadorPorEvaluar,
];

$otrosJuicios = $contadorOtrosJuicios;



            $resultado = [

                'archivo' =>
                    $nombreOriginal,

                'extension' =>
                    $extension,

                'hojas' =>
                    $cantidadHojas,

                'hoja_utilizada' =>
    $nombreHoja,

'filas_totales' =>
    $highestRow,

'columnas_totales' =>
    $highestColumnIndex,

'ultima_columna' =>
    $highestColumn,

                'fila_encabezados' =>
                    $filaEncabezados,

                'datos_ficha' =>
                    $datosFicha,

                'filas_detalle' =>
                    $filasDetalle,

                'aprendices' =>
                    count($aprendices),

                'competencias' =>
                    count($competencias),

                'rap' =>
                    count($rap),

                'funcionarios' =>
                    count($funcionarios),

                'juicios' =>
                    $juicios,

                'otros_juicios' =>
                    $otrosJuicios,

                'fecha_minima_evaluacion' =>
                    $fechaMinimaEvaluacion,

                'fecha_maxima_evaluacion' =>
                    $fechaMaximaEvaluacion,

                'evaluaciones_sin_fecha' =>
                    $evaluacionesSinFecha,

                'resultados_por_anio' =>
                    $resultadosPorAnio,

                'muestra_resultados' =>
                    array_slice(
                        $resultados,
                        0,
                        10
                    ),

                'muestra_aprendices' =>
                    array_values(
                        array_slice(
                            $aprendices,
                            0,
                            10
                        )
                    ),

                'muestra_competencias' =>
                    array_values(
                        array_slice(
                            $competencias,
                            0,
                            10
                        )
                    ),

                'muestra_rap' =>
                    array_values(
                        array_slice(
                            $rap,
                            0,
                            10
                        )
                    ),

                'muestra_funcionarios' =>
                    array_values(
                        array_slice(
                            $funcionarios,
                            0,
                            10
                        )
                    ),
            ];

            /*
             * =========================================================
             * DIAGNÓSTICO CONTRA LA BASE DE DATOS
             * =========================================================
             *
             * ESTE MÉTODO SOLO CONSULTA.
             *
             * NO INSERTA.
             * NO ACTUALIZA.
             * NO ELIMINA.
             * =========================================================
             */

            /*
             * IMPORTACIÓN REAL (transaccional).
             * Se ejecuta al cargar el archivo.
             */

            // La validación se ejecuta ANTES de cualquier inserción.
            // Un Coordinador no puede crear ni modificar fichas ajenas
            // mediante la carga de un archivo Excel.
            $errorAccesoFicha = $this->validarFichaCoordinador(
                (string) ($datosFicha['ficha'] ?? '')
            );

            if ($errorAccesoFicha !== null) {
                http_response_code(403);

                $this->view(
                    'importacion/index',
                    [
                        'tituloPagina' => 'Importar reporte SENA',
                        'error' => $errorAccesoFicha,
                    ]
                );

                return;
            }

            $importacion =
                $this->importarDatos(
                    $datosFicha,
                    $aprendices,
                    $funcionarios,
                    $resultados
                );

            $resultado['importacion'] = $importacion;

            $diagnosticoBD =
                $this->analizarContraBaseDatos(
                    $datosFicha,
                    $aprendices,
                    $competencias,
                    $rap,
                    $funcionarios,
                    $resultados
                );

            $resultado[
                'diagnostico_bd'
            ] =
                $diagnosticoBD;

            /*
             * =========================================================
             * MOSTRAR RESULTADO
             * =========================================================
             */

            $this->view(
                'importacion/resultado',
                [
                    'tituloPagina' =>
                        'Resultado de lectura del reporte SENA',

                    'resultado' =>
                        $resultado,
                ]
            );

        } catch (Throwable $e) {

            $this->view(
                'importacion/index',
                [
                    'tituloPagina' =>
                        'Importar reporte SENA',

                    'error' =>
                        'No fue posible leer el archivo Excel. '
                        . $e->getMessage(),
                ]
            );
        }
    }

    /*
     * =============================================================
     * ANALIZAR EXCEL CONTRA BASE DE DATOS
     * =============================================================
     *
     * ESTE MÉTODO ES SOLO DE LECTURA.
     *
     * =============================================================
     */

    private function analizarContraBaseDatos(
        array $datosFicha,
        array $aprendices,
        array $competencias,
        array $rap,
        array $funcionarios,
        array $resultados
    ): array {

        $modelo = new ImportacionModel();

        $diagnostico = [

            'modo' =>
                'solo_lectura',

            'programa' => [
                'detectado' => false,
                'existe' => false,
                'datos_excel' => [],
                'datos_bd' => null,
            ],

            'ficha' => [
                'detectada' => false,
                'existe' => false,
                'datos_excel' => [],
                'datos_bd' => null,
            ],

            'aprendices' => [
                'total_excel' => count($aprendices),
                'ya_existen' => 0,
                'faltan' => 0,
                'sin_persona' => 0,
                'sin_aprendiz' => 0,
            ],

            'competencias' => [
                'total_excel' => count($competencias),
                'ya_existen' => 0,
                'faltan' => 0,
            ],

            'rap' => [
                'total_excel' => count($rap),
                'ya_existen' => 0,
                'faltan' => 0,
            ],

            'funcionarios' => [
                'total_excel' => count($funcionarios),
                'ya_existen' => 0,
                'faltan' => 0,
            ],

            'matriculas' => [
                'total_aprendices' => 0,
                'ya_existen' => 0,
                'faltan' => 0,
            ],

            'resultados' => [
                'total_excel' => count($resultados),
                'nuevos' => 0,
                'existentes_iguales' => 0,
                'existentes_con_diferencias' => 0,
                'sin_aprendiz' => 0,
                'sin_competencia' => 0,
                'sin_rap' => 0,
                'sin_ficha' => 0,
                'sin_funcionario' => 0,
                'sin_fecha' => 0,
                'por_vigencia' => [],
            ],

            'errores' => [],

            'muestras' => [
                'aprendices_nuevos' => [],
                'competencias_nuevas' => [],
                'rap_nuevos' => [],
                'funcionarios_nuevos' => [],
                'matriculas_nuevas' => [],
                'resultados_nuevos' => [],
                'resultados_con_diferencias' => [],
                'errores' => [],
            ],

            'resumen_resultados_bd' => [],
        ];

        /*
         * =========================================================
         * PROGRAMA
         * =========================================================
         */

        $codigoPrograma =
            trim(
                (string)
                ($datosFicha['codigo_programa'] ?? '')
            );

        $diagnostico['programa']['datos_excel'] = [
            'codigo' =>
                $codigoPrograma,

            'nombre' =>
                $datosFicha['denominacion'] ?? '',

            'version' =>
                $datosFicha['version_programa'] ?? '',
        ];

        if ($codigoPrograma !== '') {

            $programa =
                $modelo->buscarProgramaPorCodigo(
                    $codigoPrograma
                );

            $diagnostico['programa']['detectado'] =
                true;

            if ($programa !== null) {

                $diagnostico['programa']['existe'] =
                    true;

                $diagnostico['programa']['datos_bd'] =
                    $programa;
            }
        } else {

            $diagnostico['errores'][] =
                'El reporte no contiene código de programa.';
        }

        /*
         * =========================================================
         * FICHA
         * =========================================================
         */

        $numeroFicha =
            trim(
                (string)
                ($datosFicha['ficha'] ?? '')
            );

        $diagnostico['ficha']['datos_excel'] = [
            'numero_ficha' =>
                $numeroFicha,

            'codigo_programa' =>
                $codigoPrograma,

            'version_programa' =>
                $datosFicha['version_programa'] ?? '',

            'modalidad' =>
                $datosFicha['modalidad'] ?? '',

            'regional' =>
                $datosFicha['regional'] ?? '',

            'centro' =>
                $datosFicha['centro'] ?? '',

            'fecha_inicio' =>
                $datosFicha['fecha_inicio'] ?? '',

            'fecha_fin' =>
                $datosFicha['fecha_fin'] ?? '',
        ];

        $ficha = null;

        if ($numeroFicha !== '') {

            $ficha =
                $modelo->buscarFichaPorNumero(
                    $numeroFicha
                );

            $diagnostico['ficha']['detectada'] =
                true;

            if ($ficha !== null) {

                $diagnostico['ficha']['existe'] =
                    true;

                $diagnostico['ficha']['datos_bd'] =
                    $ficha;
            }
        } else {

            $diagnostico['errores'][] =
                'El reporte no contiene número de ficha.';
        }

        $idFicha =
            $ficha !== null
                ? (int) $ficha['id']
                : null;

        /*
         * =========================================================
         * CACHE DE PERSONAS Y APRENDICES
         * =========================================================
         */

        $cachePersonas = [];

        $cacheAprendices = [];

        foreach (
            $aprendices
            as $aprendizExcel
        ) {

            $tipoDocumento =
                trim(
                    (string)
                    ($aprendizExcel['tipo_documento'] ?? '')
                );

            $documento =
                trim(
                    (string)
                    ($aprendizExcel['numero_documento'] ?? '')
                );

            if ($documento === '') {
                continue;
            }

            $clave =
                strtoupper(
                    $tipoDocumento
                    . '|'
                    . $documento
                );

            $persona =
                $modelo->buscarPersonaPorDocumento(
                    $tipoDocumento,
                    $documento
                );

            $cachePersonas[$clave] =
                $persona;

            if ($persona === null) {

                $diagnostico['aprendices']['faltan']++;

                $diagnostico['aprendices']['sin_persona']++;

                if (
                    count(
                        $diagnostico['muestras']['aprendices_nuevos']
                    ) < 10
                ) {

                    $diagnostico['muestras']['aprendices_nuevos'][] =
                        $aprendizExcel;
                }

                continue;
            }

            $idPersona =
                (int) $persona['id'];

            $aprendizBD =
                $modelo->buscarAprendizPorPersona(
                    $idPersona
                );

            $cacheAprendices[$clave] =
                $aprendizBD;

            if ($aprendizBD === null) {

                $diagnostico['aprendices']['faltan']++;

                $diagnostico['aprendices']['sin_aprendiz']++;

                if (
                    count(
                        $diagnostico['muestras']['aprendices_nuevos']
                    ) < 10
                ) {

                    $diagnostico['muestras']['aprendices_nuevos'][] =
                        $aprendizExcel;
                }

            } else {

                $diagnostico['aprendices']['ya_existen']++;
            }
        }

        /*
         * =========================================================
         * COMPETENCIAS
         * =========================================================
         */

        $cacheCompetencias = [];

        $idPrograma =
            $diagnostico['programa']['existe']
                ? (int)
                    $diagnostico['programa']['datos_bd']['id']
                : null;

        foreach (
            $competencias
            as $competenciaTexto
        ) {

            $partes =
                $this->separarCodigoYNombre(
                    (string) $competenciaTexto
                );

            $codigo =
                $partes['codigo'];

            $nombre =
                $partes['nombre'];

            $clave =
                (
                    $idPrograma !== null
                        ? $idPrograma
                        : 'sin_programa'
                )
                . '|'
                . $codigo;

            if (
                $codigo === ''
            ) {

                $diagnostico['competencias']['faltan']++;

                if (
                    count(
                        $diagnostico['muestras']['competencias_nuevas']
                    ) < 10
                ) {

                    $diagnostico['muestras']['competencias_nuevas'][] =
                        $competenciaTexto;
                }

                continue;
            }

            if (
                $idPrograma === null
            ) {

                $cacheCompetencias[$clave] =
                    null;

                $diagnostico['competencias']['faltan']++;

                continue;
            }

            $competenciaBD =
                $modelo->buscarCompetencia(
                    $idPrograma,
                    $codigo
                );

            $cacheCompetencias[$clave] =
                $competenciaBD;

            if (
                $competenciaBD === null
            ) {

                $diagnostico['competencias']['faltan']++;

                if (
                    count(
                        $diagnostico['muestras']['competencias_nuevas']
                    ) < 10
                ) {

                    $diagnostico['muestras']['competencias_nuevas'][] = [
                        'codigo' =>
                            $codigo,

                        'nombre' =>
                            $nombre,
                    ];
                }

            } else {

                $diagnostico['competencias']['ya_existen']++;
            }
        }

        /*
         * =========================================================
         * RAP
         * =========================================================
         */

        $cacheRap = [];

        foreach (
            $rap
            as $rapTexto
        ) {

            $partesRap =
                $this->separarCodigoYNombre(
                    (string) $rapTexto
                );

            $codigoRap =
                $partesRap['codigo'];

            $nombreRap =
                $partesRap['nombre'];

            $competenciaRelacionada = '';

            foreach (
                $resultados
                as $resultadoExcel
            ) {

                if (
                    trim(
                        (string)
                        ($resultadoExcel['resultado_aprendizaje'] ?? '')
                    )
                    ===
                    trim(
                        (string) $rapTexto
                    )
                ) {

                    $competenciaRelacionada =
                        trim(
                            (string)
                            ($resultadoExcel['competencia'] ?? '')
                        );

                    if (
                        $competenciaRelacionada !== ''
                    ) {
                        break;
                    }
                }
            }

            $partesCompetencia =
                $this->separarCodigoYNombre(
                    $competenciaRelacionada
                );

            $codigoCompetencia =
                $partesCompetencia['codigo'];

            $claveCompetencia =
                (
                    $idPrograma !== null
                        ? $idPrograma
                        : 'sin_programa'
                )
                . '|'
                . $codigoCompetencia;

            $competenciaBD =
                $cacheCompetencias[
                    $claveCompetencia
                ] ?? null;

            if (
                $codigoRap === ''
            ) {

                $diagnostico['rap']['faltan']++;

                if (
                    count(
                        $diagnostico['muestras']['rap_nuevos']
                    ) < 10
                ) {

                    $diagnostico['muestras']['rap_nuevos'][] =
                        $rapTexto;
                }

                continue;
            }

            if (
                $competenciaBD === null
            ) {

                $cacheRap[
                    $codigoRap
                ] = null;

                $diagnostico['rap']['faltan']++;

                if (
                    count(
                        $diagnostico['muestras']['rap_nuevos']
                    ) < 10
                ) {

                    $diagnostico['muestras']['rap_nuevos'][] = [
                        'codigo' =>
                            $codigoRap,

                        'nombre' =>
                            $nombreRap,

                        'competencia' =>
                            $competenciaRelacionada,
                    ];
                }

                continue;
            }

            $idCompetencia =
                (int) $competenciaBD['id'];

            $rapBD =
                $modelo->buscarRap(
                    $idCompetencia,
                    $codigoRap
                );

            $cacheRap[
                $codigoRap
            ] = $rapBD;

            if (
                $rapBD === null
            ) {

                $diagnostico['rap']['faltan']++;

                if (
                    count(
                        $diagnostico['muestras']['rap_nuevos']
                    ) < 10
                ) {

                    $diagnostico['muestras']['rap_nuevos'][] = [
                        'codigo' =>
                            $codigoRap,

                        'nombre' =>
                            $nombreRap,

                        'competencia' =>
                            $competenciaRelacionada,
                    ];
                }

            } else {

                $diagnostico['rap']['ya_existen']++;
            }
        }

        /*
         * =========================================================
         * FUNCIONARIOS / PROFESIONALES
         * =========================================================
         */

        $cacheFuncionarios = [];

        foreach (
            $funcionarios
            as $funcionarioTexto
        ) {

            $partesFuncionario =
                $this->separarDocumentoYNombre(
                    (string) $funcionarioTexto
                );

            $tipoDocumentoFuncionario =
                $partesFuncionario['tipo_documento'];

            $documentoFuncionario =
                $partesFuncionario['documento'];

            $claveFuncionario =
                strtoupper(
                    $tipoDocumentoFuncionario
                    . '|'
                    . $documentoFuncionario
                );

            if (
                $documentoFuncionario === ''
            ) {

                $cacheFuncionarios[
                    $claveFuncionario
                ] = null;

                $diagnostico['funcionarios']['faltan']++;

                if (
                    count(
                        $diagnostico['muestras']['funcionarios_nuevos']
                    ) < 10
                ) {

                    $diagnostico['muestras']['funcionarios_nuevos'][] =
                        $funcionarioTexto;
                }

                continue;
            }

            $personaFuncionario =
                $modelo->buscarPersonaPorDocumento(
                    $tipoDocumentoFuncionario,
                    $documentoFuncionario
                );

            if (
                $personaFuncionario === null
            ) {

                $cacheFuncionarios[
                    $claveFuncionario
                ] = null;

                $diagnostico['funcionarios']['faltan']++;

                if (
                    count(
                        $diagnostico['muestras']['funcionarios_nuevos']
                    ) < 10
                ) {

                    $diagnostico['muestras']['funcionarios_nuevos'][] =
                        $funcionarioTexto;
                }

                continue;
            }

            $profesional =
                $modelo->buscarProfesionalPorPersona(
                    (int) $personaFuncionario['id']
                );

            $cacheFuncionarios[
                $claveFuncionario
            ] = $profesional;

            if (
                $profesional === null
            ) {

                $diagnostico['funcionarios']['faltan']++;

                if (
                    count(
                        $diagnostico['muestras']['funcionarios_nuevos']
                    ) < 10
                ) {

                    $diagnostico['muestras']['funcionarios_nuevos'][] =
                        $funcionarioTexto;
                }

            } else {

                $diagnostico['funcionarios']['ya_existen']++;
            }
        }

        /*
         * =========================================================
         * MATRÍCULAS
         * =========================================================
         */

        if (
            $idFicha !== null
        ) {

            $diagnostico['matriculas']['total_aprendices'] =
                count($aprendices);

            foreach (
                $aprendices
                as $aprendizExcel
            ) {

                $tipoDocumento =
                    trim(
                        (string)
                        ($aprendizExcel['tipo_documento'] ?? '')
                    );

                $documento =
                    trim(
                        (string)
                        ($aprendizExcel['numero_documento'] ?? '')
                    );

                $clave =
                    strtoupper(
                        $tipoDocumento
                        . '|'
                        . $documento
                    );

                $aprendizBD =
                    $cacheAprendices[$clave] ?? null;

                if (
                    $aprendizBD === null
                ) {
                    continue;
                }

                $matricula =
                    $modelo->buscarMatricula(
                        (int) $aprendizBD['id'],
                        $idFicha
                    );

                if (
                    $matricula === null
                ) {

                    $diagnostico['matriculas']['faltan']++;

                    if (
                        count(
                            $diagnostico['muestras']['matriculas_nuevas']
                        ) < 10
                    ) {

                        $diagnostico['muestras']['matriculas_nuevas'][] = [
                            'documento' =>
                                $documento,

                            'nombre' =>
                                ($aprendizExcel['nombre'] ?? '')
                                . ' '
                                . ($aprendizExcel['apellidos'] ?? ''),
                        ];
                    }

                } else {

                    $diagnostico['matriculas']['ya_existen']++;
                }
            }
        }

        /*
         * =========================================================
         * RESULTADOS
         * =========================================================
         */

        $resultadosBD = [];

        if (
            $idFicha !== null
        ) {

            $resultadosBD =
                $modelo->obtenerResultadosDeFicha(
                    $idFicha
                );

            $diagnostico[
                'resumen_resultados_bd'
            ] =
                $modelo->resumenResultadosPorAnio(
                    $idFicha
                );
        }

        /*
         * Crear índice en memoria.
         */

        $indiceResultados = [];

        foreach (
            $resultadosBD
            as $resultadoBD
        ) {

            $claveResultado =
                (int) $resultadoBD['id_aprendiz']
                . '|'
                . (int) $resultadoBD['id_rap'];

            if (
                !isset(
                    $indiceResultados[
                        $claveResultado
                    ]
                )
            ) {

                $indiceResultados[
                    $claveResultado
                ] = [];
            }

            $indiceResultados[
                $claveResultado
            ][] =
                $resultadoBD;
        }

        /*
         * =========================================================
         * ANALIZAR CADA RESULTADO DEL EXCEL
         * =========================================================
         */

        foreach (
            $resultados
            as $resultadoExcel
        ) {

            if (
                empty(
                    $resultadoExcel['fecha_evaluacion']
                )
            ) {

                $diagnostico['resultados']['sin_fecha']++;
            }

            /*
             * La vigencia se determina exclusivamente a partir
             * de la fecha real del juicio evaluativo.
             */

            $vigencia =
                $resultadoExcel['vigencia'] ?? null;

            if (
                $vigencia !== null
            ) {

                if (
                    !isset(
                        $diagnostico[
                            'resultados'
                        ]['por_vigencia'][$vigencia]
                    )
                ) {

                    $diagnostico[
                        'resultados'
                    ]['por_vigencia'][$vigencia] = [
                        'total' => 0,
                        'nuevos' => 0,
                        'iguales' => 0,
                        'con_diferencias' => 0,
                    ];
                }

                $diagnostico[
                    'resultados'
                ]['por_vigencia'][$vigencia]['total']++;
            }

            /*
             * =====================================================
             * APRENDIZ
             * =====================================================
             */

            $tipoDocumento =
                trim(
                    (string)
                    ($resultadoExcel['tipo_documento'] ?? '')
                );

            $documento =
                trim(
                    (string)
                    ($resultadoExcel['numero_documento'] ?? '')
                );

            $clavePersona =
                strtoupper(
                    $tipoDocumento
                    . '|'
                    . $documento
                );

            $aprendizBD =
                $cacheAprendices[
                    $clavePersona
                ] ?? null;

            if (
                $aprendizBD === null
            ) {

                $diagnostico[
                    'resultados'
                ]['sin_aprendiz']++;

                $this->agregarMuestra(
                    $diagnostico[
                        'muestras'
                    ]['errores'],
                    [
                        'fila_excel' =>
                            $resultadoExcel['fila_excel'],

                        'tipo' =>
                            'aprendiz',

                        'documento' =>
                            $documento,

                        'mensaje' =>
                            'El aprendiz no existe como aprendiz en la base de datos.',
                    ]
                );

                continue;
            }

            $idAprendiz =
                (int) $aprendizBD['id'];

            /*
             * =====================================================
             * COMPETENCIA
             * =====================================================
             */

            $competenciaTexto =
                trim(
                    (string)
                    ($resultadoExcel['competencia'] ?? '')
                );

            $partesCompetencia =
                $this->separarCodigoYNombre(
                    $competenciaTexto
                );

            $codigoCompetencia =
                $partesCompetencia['codigo'];

            $claveCompetencia =
                (
                    $idPrograma !== null
                        ? $idPrograma
                        : 'sin_programa'
                )
                . '|'
                . $codigoCompetencia;

            $competenciaBD =
                $cacheCompetencias[
                    $claveCompetencia
                ] ?? null;

            if (
                $competenciaBD === null
            ) {

                $diagnostico[
                    'resultados'
                ]['sin_competencia']++;

                $this->agregarMuestra(
                    $diagnostico[
                        'muestras'
                    ]['errores'],
                    [
                        'fila_excel' =>
                            $resultadoExcel['fila_excel'],

                        'tipo' =>
                            'competencia',

                        'codigo' =>
                            $codigoCompetencia,

                        'mensaje' =>
                            'La competencia no existe para el programa encontrado.',
                    ]
                );

                continue;
            }

            /*
             * =====================================================
             * RAP
             * =====================================================
             */

            $rapTexto =
                trim(
                    (string)
                    ($resultadoExcel['resultado_aprendizaje'] ?? '')
                );

            $partesRap =
                $this->separarCodigoYNombre(
                    $rapTexto
                );

            $codigoRap =
                $partesRap['codigo'];

            $rapBD =
                $cacheRap[
                    $codigoRap
                ] ?? null;

            if (
                $rapBD === null
            ) {

                $diagnostico[
                    'resultados'
                ]['sin_rap']++;

                $this->agregarMuestra(
                    $diagnostico[
                        'muestras'
                    ]['errores'],
                    [
                        'fila_excel' =>
                            $resultadoExcel['fila_excel'],

                        'tipo' =>
                            'rap',

                        'codigo' =>
                            $codigoRap,

                        'mensaje' =>
                            'El RAP no existe para la competencia correspondiente.',
                    ]
                );

                continue;
            }

            $idRap =
                (int) $rapBD['id'];

            /*
             * =====================================================
             * FICHA
             * =====================================================
             */

            if (
                $idFicha === null
            ) {

                $diagnostico[
                    'resultados'
                ]['sin_ficha']++;

                continue;
            }

            /*
             * =====================================================
             * FUNCIONARIO
             * =====================================================
             */

            $idInstructor = null;

            $funcionarioTexto =
                trim(
                    (string)
                    ($resultadoExcel['funcionario'] ?? '')
                );

            if (
                $funcionarioTexto !== '' &&
                $funcionarioTexto !== '-'
            ) {

                $partesFuncionario =
                    $this->separarDocumentoYNombre(
                        $funcionarioTexto
                    );

                $claveFuncionario =
                    strtoupper(
                        $partesFuncionario['tipo_documento']
                        . '|'
                        . $partesFuncionario['documento']
                    );

                $profesional =
                    $cacheFuncionarios[
                        $claveFuncionario
                    ] ?? null;

                if (
                    $profesional === null
                ) {

                    $diagnostico[
                        'resultados'
                    ]['sin_funcionario']++;

                } else {

                    $idInstructor =
                        (int) $profesional['id'];
                }
            }

            /*
             * =====================================================
             * NORMALIZAR JUICIO
             * =====================================================
             */

            $juicioSena =
                strtoupper(
                    trim(
                        (string)
                        ($resultadoExcel['juicio'] ?? '')
                    )
                );

            if (
                $juicioSena === 'APROBADO'
            ) {

                $juicioBD =
                    'aprobado';

            } elseif (
                $juicioSena === 'POR EVALUAR'
            ) {

                $juicioBD =
                    'pendiente';

            } else {

                $juicioBD =
                    'pendiente';
            }

            /*
             * =====================================================
             * RESULTADO A COMPARAR
             * =====================================================
             */

            $resultadoParaComparar = [

                'id_instructor' =>
                    $idInstructor,

                'fecha_evaluacion' =>
                    $resultadoExcel[
                        'fecha_evaluacion'
                    ] ?? null,

                'fecha_hora_evaluacion' =>
                    !empty(
                        $resultadoExcel[
                            'fecha_hora_juicio'
                        ]
                    )
                        ? $resultadoExcel[
                            'fecha_hora_juicio'
                        ]
                        : null,

                'juicio' =>
                    $juicioBD,

                'juicio_sena' =>
                    $juicioSena !== ''
                        ? $juicioSena
                        : null,

                'calificacion' =>
                    null,

                'observaciones' =>
                    null,
            ];

            /*
             * =====================================================
             * BUSCAR RESULTADOS EXISTENTES
             * =====================================================
             */

            $claveResultado =
                $idAprendiz
                . '|'
                . $idRap;

            $existentes =
                $indiceResultados[
                    $claveResultado
                ] ?? [];

            $resultadoIgual = false;

            foreach (
                $existentes
                as $existente
            ) {

                if (
                    $modelo->resultadoEsIgual(
                        $resultadoParaComparar,
                        $existente
                    )
                ) {

                    $resultadoIgual = true;

                    break;
                }
            }

            if (
                $resultadoIgual
            ) {

                $diagnostico[
                    'resultados'
                ]['existentes_iguales']++;

                if (
                    $vigencia !== null &&
                    isset(
                        $diagnostico[
                            'resultados'
                        ]['por_vigencia'][$vigencia]
                    )
                ) {

                    $diagnostico[
                        'resultados'
                    ]['por_vigencia'][$vigencia]['iguales']++;
                }

            } elseif (
                count($existentes) > 0
            ) {

                /*
                 * Ya existe un resultado para aprendiz + RAP,
                 * pero los datos del Excel no coinciden exactamente.
                 *
                 * NO se actualiza.
                 */

                $diagnostico[
                    'resultados'
                ]['existentes_con_diferencias']++;

                if (
                    $vigencia !== null &&
                    isset(
                        $diagnostico[
                            'resultados'
                        ]['por_vigencia'][$vigencia]
                    )
                ) {

                    $diagnostico[
                        'resultados'
                    ]['por_vigencia'][$vigencia]['con_diferencias']++;
                }

                if (
                    count(
                        $diagnostico[
                            'muestras'
                        ]['resultados_con_diferencias']
                    ) < 20
                ) {

                    $diagnostico[
                        'muestras'
                    ]['resultados_con_diferencias'][] = [

                        'fila_excel' =>
                            $resultadoExcel['fila_excel'],

                        'documento' =>
                            $documento,

                        'competencia' =>
                            $competenciaTexto,

                        'rap' =>
                            $rapTexto,

                        'juicio_excel' =>
                            $juicioSena,

                        'fecha_excel' =>
                            $resultadoExcel[
                                'fecha_evaluacion'
                            ] ?? null,

                        'existentes_bd' =>
                            count($existentes),
                    ];
                }

            } else {

                /*
                 * Resultado completamente nuevo.
                 */

                $diagnostico[
                    'resultados'
                ]['nuevos']++;

                if (
                    $vigencia !== null &&
                    isset(
                        $diagnostico[
                            'resultados'
                        ]['por_vigencia'][$vigencia]
                    )
                ) {

                    $diagnostico[
                        'resultados'
                    ]['por_vigencia'][$vigencia]['nuevos']++;
                }

                if (
                    count(
                        $diagnostico[
                            'muestras'
                        ]['resultados_nuevos']
                    ) < 20
                ) {

                    $diagnostico[
                        'muestras'
                    ]['resultados_nuevos'][] = [

                        'fila_excel' =>
                            $resultadoExcel['fila_excel'],

                        'documento' =>
                            $documento,

                        'competencia' =>
                            $competenciaTexto,

                        'rap' =>
                            $rapTexto,

                        'juicio' =>
                            $juicioSena,

                        'fecha_evaluacion' =>
                            $resultadoExcel[
                                'fecha_evaluacion'
                            ] ?? null,

                        'vigencia' =>
                            $vigencia,
                    ];
                }
            }
        }

        /*
         * =========================================================
         * ORDENAR VIGENCIAS
         * =========================================================
         */

        ksort(
            $diagnostico[
                'resultados'
            ]['por_vigencia']
        );

        /*
         * =========================================================
         * ESTADO GENERAL
         * =========================================================
         */

        $diagnostico['estado'] =
            $this->determinarEstadoDiagnostico(
                $diagnostico
            );

        return $diagnostico;
    }

    /*
     * =============================================================
     * SEPARAR CÓDIGO Y NOMBRE
     * =============================================================
     */

    private function separarCodigoYNombre(
        string $texto
    ): array {

        $texto =
            trim($texto);

        if (
            $texto === ''
        ) {

            return [
                'codigo' => '',
                'nombre' => '',
            ];
        }

        $partes =
            preg_split(
                '/\s*-\s*/',
                $texto,
                2
            );

        if (
            is_array($partes) &&
            isset($partes[0])
        ) {

            $codigo =
                trim(
                    (string) $partes[0]
                );

            $nombre =
                isset($partes[1])
                    ? trim(
                        (string)
                        $partes[1]
                    )
                    : '';

            if (
                preg_match(
                    '/^[0-9]+$/',
                    $codigo
                )
            ) {

                return [
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                ];
            }
        }

        return [
            'codigo' => '',
            'nombre' => $texto,
        ];
    }

    /*
     * =============================================================
     * SEPARAR DOCUMENTO Y NOMBRE
     * =============================================================
     */

    private function separarDocumentoYNombre(
        string $texto
    ): array {

        $texto =
            trim($texto);

        if (
            $texto === ''
        ) {

            return [
                'tipo_documento' => '',
                'documento' => '',
                'nombre' => '',
            ];
        }

        $partes =
            preg_split(
                '/\s*-\s*/',
                $texto,
                2
            );

        $identificacion =
            isset($partes[0])
                ? trim(
                    (string) $partes[0]
                )
                : '';

        $nombre =
            isset($partes[1])
                ? trim(
                    (string) $partes[1]
                )
                : '';

        $identificacionPartes =
            preg_split(
                '/\s+/',
                $identificacion
            );

        if (
            !is_array($identificacionPartes) ||
            count($identificacionPartes) < 2
        ) {

            return [
                'tipo_documento' => '',
                'documento' => '',
                'nombre' => $nombre,
            ];
        }

        $tipoDocumento =
            trim(
                (string)
                $identificacionPartes[0]
            );

        $documento =
            trim(
                (string)
                $identificacionPartes[1]
            );

        return [
            'tipo_documento' =>
                $tipoDocumento,

            'documento' =>
                $documento,

            'nombre' =>
                $nombre,
        ];
    }

    /*
     * =============================================================
     * AGREGAR MUESTRA
     * =============================================================
     */

    private function agregarMuestra(
        array &$coleccion,
        array $elemento,
        int $limite = 20
    ): void {

        if (
            count($coleccion) >= $limite
        ) {
            return;
        }

        $coleccion[] =
            $elemento;
    }

    /*
     * =============================================================
     * DETERMINAR ESTADO DEL DIAGNÓSTICO
     * =============================================================
     */

    private function determinarEstadoDiagnostico(
        array $diagnostico
    ): string {

        if (
            !empty(
                $diagnostico['errores']
            )
        ) {

            return 'requiere_revision';
        }

        if (
            !$diagnostico[
                'programa'
            ]['existe']
        ) {

            return 'requiere_creacion';
        }

        if (
            !$diagnostico[
                'ficha'
            ]['existe']
        ) {

            return 'requiere_creacion';
        }

        if (
            $diagnostico[
                'resultados'
            ]['existentes_con_diferencias'] > 0
        ) {

            return 'requiere_revision';
        }

        if (
            $diagnostico[
                'aprendices'
            ]['faltan'] > 0
            ||
            $diagnostico[
                'competencias'
            ]['faltan'] > 0
            ||
            $diagnostico[
                'rap'
            ]['faltan'] > 0
            ||
            $diagnostico[
                'funcionarios'
            ]['faltan'] > 0
            ||
            $diagnostico[
                'matriculas'
            ]['faltan'] > 0
            ||
            $diagnostico[
                'resultados'
            ]['nuevos'] > 0
        ) {

            return 'listo_para_revision';
        }

        return 'sin_novedades';
    }

    /*
     * =============================================================
     * OBTENER VALOR DE CELDA
     * =============================================================
     */

    private function getCellValue(
        $sheet,
        int $columna,
        int $fila
    ): string {

        $coordenada =
            Coordinate::stringFromColumnIndex(
                $columna
            ) . $fila;

        $valor =
            $sheet
                ->getCell($coordenada)
                ->getValue();

        if (
            $valor === null
        ) {
            return '';
        }

        if (
            is_scalar($valor)
        ) {
            return trim(
                (string) $valor
            );
        }

        return '';
    }

    /*
     * =============================================================
     * NORMALIZAR FECHA EXCEL
     * =============================================================
     */

    private function normalizarFechaExcel(
        string $valor
    ): string {

        $valor =
            trim($valor);

        if (
            $valor === ''
        ) {
            return '';
        }

        if (
            is_numeric($valor)
        ) {

            try {

                $numero =
                    (float) $valor;

                if (
                    $numero > 0
                ) {

                    return
                        ExcelDate::excelToDateTimeObject(
                            $numero
                        )->format(
                            'Y-m-d'
                        );
                }

            } catch (Throwable $e) {
                // Continuamos con los formatos de texto.
            }
        }

        $fecha =
            \DateTime::createFromFormat(
                'd/m/Y',
                $valor
            );

        if (
            $fecha instanceof \DateTime
        ) {
            return $fecha->format('Y-m-d');
        }

        $fecha =
            \DateTime::createFromFormat(
                'd-m-Y',
                $valor
            );

        if (
            $fecha instanceof \DateTime
        ) {
            return $fecha->format('Y-m-d');
        }

        $fecha =
            \DateTime::createFromFormat(
                'Y-m-d',
                $valor
            );

        if (
            $fecha instanceof \DateTime
        ) {
            return $fecha->format('Y-m-d');
        }

        return $valor;
    }

    /*
     * =============================================================
     * NORMALIZAR FECHA Y HORA DE EXCEL
     * =============================================================
     */

    private function normalizarFechaHoraExcel(
        string $valor
    ): string {

        $valor =
            trim($valor);

        if (
            $valor === ''
        ) {
            return '';
        }

        if (
            is_numeric($valor)
        ) {

            try {

                $numero =
                    (float) $valor;

                if (
                    $numero > 0
                ) {

                    return
                        ExcelDate::excelToDateTimeObject(
                            $numero
                        )->format(
                            'Y-m-d H:i:s'
                        );
                }

            } catch (Throwable $e) {
                // Continuamos con formatos de texto.
            }
        }

        $formatos = [

            'd/m/Y H:i:s',

            'd/m/Y H:i',

            'd-m-Y H:i:s',

            'd-m-Y H:i',

            'Y-m-d H:i:s',

            'Y-m-d H:i',
        ];

        foreach (
            $formatos
            as $formato
        ) {

            $fecha =
                \DateTime::createFromFormat(
                    $formato,
                    $valor
                );

            if (
                $fecha instanceof \DateTime
            ) {

                return $fecha->format(
                    'Y-m-d H:i:s'
                );
            }
        }

        $fecha =
            $this->normalizarFechaExcel(
                $valor
            );

        if (
            $fecha !== ''
        ) {

            return $fecha . ' 00:00:00';
        }

        return '';
    }

    /*
     * =============================================================
     * OBTENER FECHA DESDE FECHA/HORA
     * =============================================================
     */

    private function obtenerFechaDesdeFechaHora(
        string $fechaHora
    ): string {

        if (
            $fechaHora === ''
        ) {
            return '';
        }

        $fecha =
            \DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $fechaHora
            );

        if (
            $fecha instanceof \DateTime
        ) {
            return $fecha->format(
                'Y-m-d'
            );
        }

        $fecha =
            \DateTime::createFromFormat(
                'Y-m-d',
                $fechaHora
            );

        if (
            $fecha instanceof \DateTime
        ) {
            return $fecha->format(
                'Y-m-d'
            );
        }

        return '';
    }

    /*
     * =============================================================
     * NORMALIZAR ENCABEZADOS DEL DETALLE
     * =============================================================
     */

    private function normalizarEncabezado(
        string $texto
    ): string {

        $texto =
            $this->normalizarTexto(
                $texto
            );

        $texto =
            strtolower(
                trim($texto)
            );

        $texto =
            str_replace(
                [
                    ':',
                    ';',
                    '.',
                    ',',
                ],
                '',
                $texto
            );

        $texto =
            preg_replace(
                '/\s+/u',
                ' ',
                $texto
            ) ?? $texto;

        $mapa = [

            'tipo de documento'
                => 'tipo_documento',

            'numero de documento'
                => 'numero_documento',

            'nombre'
                => 'nombre',

            'apellidos'
                => 'apellidos',

            'estado'
                => 'estado',

            'competencia'
                => 'competencia',

            'resultado de aprendizaje'
                => 'resultado_aprendizaje',

            'juicio de evaluacion'
                => 'juicio_evaluacion',

            'fecha y hora del juicio evaluativo'
                => 'fecha_y_hora_del_juicio_evaluativo',

            'funcionario que registro el juicio evaluativo'
                => 'funcionario_que_registro_el_juicio_evaluativo',
        ];

        if (
            isset(
                $mapa[$texto]
            )
        ) {
            return $mapa[$texto];
        }

        return str_replace(
            ' ',
            '_',
            $texto
        );
    }

    /*
     * =============================================================
     * NORMALIZAR ETIQUETAS DE LA CABECERA SENA
     * =============================================================
     */

    private function normalizarEtiquetaCabecera(
        string $texto
    ): string {

        $texto =
            $this->normalizarTexto(
                $texto
            );

        $texto =
            strtolower(
                trim($texto)
            );

        $texto =
            str_replace(
                [
                    ':',
                    ';',
                    '.',
                    ',',
                ],
                '',
                $texto
            );

        /*
         * Se contemplan espacios normales, saltos de línea,
         * tabulaciones y espacios no separables provenientes
         * del archivo Excel.
         */

        $texto =
            preg_replace(
                '/[\s\x{00A0}]+/u',
                ' ',
                $texto
            ) ?? $texto;

        return trim($texto);
    }

    /*
     * =============================================================
     * LIMPIAR VALOR DE CABECERA SENA
     * =============================================================
     */

    private function limpiarValorCabecera(
        string $valor
    ): string {

        $valor =
            trim($valor);

        if (
            $valor === ''
        ) {
            return '';
        }

        $valor =
            preg_replace(
                '/\s*\|\s*/u',
                ' ',
                $valor
            ) ?? $valor;

        $valor =
            preg_replace(
                '/\s+/u',
                ' ',
                $valor
            ) ?? $valor;

        return trim($valor);
    }

    /*
     * =============================================================
     * NORMALIZAR TEXTO
     * =============================================================
     */

    private function normalizarTexto(
        string $texto
    ): string {

        $texto =
            trim($texto);

        if (
            $texto === ''
        ) {
            return '';
        }

        $reemplazos = [

            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',

            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
        ];

        return strtr(
            $texto,
            $reemplazos
        );
    }

    /*
     * =============================================================
     * MENSAJES DE ERROR DE CARGA
     * =============================================================
     */

    private function mensajeErrorUpload(
        int $codigo
    ): string {

        switch ($codigo) {

            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:

                return
                    'El archivo supera el tamaño máximo permitido.';

            case UPLOAD_ERR_PARTIAL:

                return
                    'El archivo se cargó parcialmente. Inténtelo nuevamente.';

            case UPLOAD_ERR_NO_FILE:

                return
                    'No se seleccionó ningún archivo.';

            case UPLOAD_ERR_NO_TMP_DIR:

                return
                    'No está disponible la carpeta temporal del servidor.';

            case UPLOAD_ERR_CANT_WRITE:

                return
                    'No fue posible guardar temporalmente el archivo.';

            case UPLOAD_ERR_EXTENSION:

                return
                    'Una extensión de PHP detuvo la carga del archivo.';

            default:

                return
                    'Ocurrió un error al cargar el archivo.';
        }
    }

    /*
     * =============================================================
     * IMPORTAR DATOS A LA BASE DE DATOS
     * =============================================================
     *
     * Todo ocurre en UNA transacción:
     *   - Si ocurre una excepción (error de BD, ficha sin fecha de
     *     inicio, etc.) se revierte TODO y no queda nada a medias.
     *   - Los problemas de una fila puntual (documento vacío, RAP sin
     *     código) se registran como error y se omite solo esa fila.
     *
     * Es idempotente: cargar el mismo Excel dos veces no duplica
     * registros.
     * =============================================================
     */

    private function importarDatos(
        array $datosFicha,
        array $aprendices,
        array $funcionarios,
        array $resultados
    ): array {

        $modelo = new ImportacionModel();
        $db     = $modelo->getConnection();

        $r = [
            'exitosa' => false,
            'mensaje_error' => null,
            'creados' => [
                'programa' => 0, 'ficha' => 0, 'personas' => 0,
                'aprendices' => 0, 'profesionales' => 0,
                'matriculas' => 0, 'competencias' => 0, 'rap' => 0,
                'resultados' => 0,
            ],
            'resultados' => [
                'nuevos' => 0,
                'actualizados' => 0,
                'sin_cambios' => 0,
                'omitidos' => 0,
            ],
            'errores' => [],
        ];

        $registrarError = static function (
            array &$r,
            $fila,
            string $mensaje
        ): void {
            if (count($r['errores']) < 50) {
                $r['errores'][] = [
                    'fila_excel' => $fila,
                    'mensaje' => $mensaje,
                ];
            }
        };

        try {

            $db->beginTransaction();

            /* ---------------- PROGRAMA ---------------- */

            $codigoPrograma = trim((string) ($datosFicha['codigo_programa'] ?? ''));

            if ($codigoPrograma === '') {
                throw new RuntimeException('El reporte no contiene código de programa.');
            }

            $programa = $modelo->buscarProgramaPorCodigo($codigoPrograma);

            if ($programa === null) {
                $idPrograma = $modelo->insertarPrograma(
                    $codigoPrograma,
                    (string) ($datosFicha['denominacion'] ?? '')
                );
                $r['creados']['programa']++;
            } else {
                $idPrograma = (int) $programa['id'];
            }

            /* ---------------- FICHA ---------------- */

            $numeroFicha = trim((string) ($datosFicha['ficha'] ?? ''));

            if ($numeroFicha === '') {
                throw new RuntimeException('El reporte no contiene número de ficha.');
            }

            $ficha = $modelo->buscarFichaPorNumero($numeroFicha);

            if ($ficha === null) {

                $fechaInicioFicha = trim((string) ($datosFicha['fecha_inicio'] ?? ''));

                if ($fechaInicioFicha === '') {
                    throw new RuntimeException(
                        'No se puede crear la ficha: el reporte no trae fecha de inicio.'
                    );
                }

                $estadoExcel = strtoupper(trim((string) ($datosFicha['estado_ficha'] ?? '')));

                $estadoFicha = 'activa';
                if (str_contains($estadoExcel, 'TERMIN') || str_contains($estadoExcel, 'FINAL')) {
                    $estadoFicha = 'finalizada';
                } elseif (str_contains($estadoExcel, 'CANCEL')) {
                    $estadoFicha = 'cancelada';
                }

                $idFicha = $modelo->insertarFicha([
                    'numero_ficha'     => $numeroFicha,
                    'id_programa'      => $idPrograma,
                    'version_programa' => trim((string) ($datosFicha['version_programa'] ?? '')),
                    'modalidad'        => trim((string) ($datosFicha['modalidad'] ?? '')),
                    'regional'         => trim((string) ($datosFicha['regional'] ?? '')),
                    'centro'           => trim((string) ($datosFicha['centro'] ?? '')),
                    'fecha_inicio'     => $fechaInicioFicha,
                    'fecha_fin'        => trim((string) ($datosFicha['fecha_fin'] ?? '')),
                    'estado'           => $estadoFicha,
                ]);

                $r['creados']['ficha']++;

                $fechaMatricula = $fechaInicioFicha;
                $fechaFinMatricula = trim((string) ($datosFicha['fecha_fin'] ?? '')) ?: null;

            } else {
                $idFicha = (int) $ficha['id'];
                $fechaMatricula = (string) $ficha['fecha_inicio'];
                $fechaFinMatricula = $ficha['fecha_fin'] ?: null;
            }

            /* ---------------- FUNCIONARIOS ---------------- */

            $mapaProfesionales = []; // "TIPO|DOC" => id_profesional

            foreach ($funcionarios as $funcionarioTexto) {

                $p = $this->separarDocumentoYNombre((string) $funcionarioTexto);

                if ($p['documento'] === '' || $p['tipo_documento'] === '') {
                    $registrarError($r, '-', 'Funcionario sin tipo/número de documento: ' . $funcionarioTexto);
                    continue;
                }

                $clave = strtoupper($p['tipo_documento'] . '|' . $p['documento']);

                $persona = $modelo->buscarPersonaPorDocumento($p['tipo_documento'], $p['documento']);

                if ($persona === null) {
                    // El reporte trae un solo texto con el nombre completo.
                    $idPersona = $modelo->insertarPersona(
                        $p['tipo_documento'],
                        $p['documento'],
                        $p['nombre'],
                        '-'
                    );
                    $r['creados']['personas']++;
                } else {
                    $idPersona = (int) $persona['id'];
                }

                $prof = $modelo->buscarProfesionalPorPersona($idPersona);

                if ($prof === null) {
                    $idProf = $modelo->insertarProfesional($idPersona);
                    $r['creados']['profesionales']++;
                } else {
                    $idProf = (int) $prof['id'];
                }

                $mapaProfesionales[$clave] = $idProf;
            }

            /* ---------------- APRENDICES + MATRÍCULAS ---------------- */

            $mapaAprendices = []; // "TIPO|DOC" => id_aprendiz

            foreach ($aprendices as $a) {

                $tipo = trim((string) ($a['tipo_documento'] ?? ''));
                $doc  = trim((string) ($a['numero_documento'] ?? ''));

                if ($doc === '' || $tipo === '') {
                    $registrarError($r, '-', 'Aprendiz sin tipo/número de documento: ' . $doc);
                    continue;
                }

                $clave = strtoupper($tipo . '|' . $doc);

                $persona = $modelo->buscarPersonaPorDocumento($tipo, $doc);

                if ($persona === null) {
                    $idPersona = $modelo->insertarPersona(
                        $tipo,
                        $doc,
                        (string) ($a['nombre'] ?? ''),
                        (string) ($a['apellidos'] ?? '')
                    );
                    $r['creados']['personas']++;
                } else {
                    $idPersona = (int) $persona['id'];
                }

                $aprendiz = $modelo->buscarAprendizPorPersona($idPersona);

                $estadoAprendiz = strtoupper(
    trim(
        (string) ($a['estado'] ?? '')
    )
);

if ($aprendiz === null) {

    $idAprendiz = $modelo->insertarAprendiz(
        $idPersona,
        $estadoAprendiz
    );

    $r['creados']['aprendices']++;

} else {

    $idAprendiz = (int) $aprendiz['id'];

    if (
        trim((string) ($aprendiz['estado'] ?? '')) !==
        $estadoAprendiz
    ) {
        $modelo->actualizarEstadoAprendiz(
            $idAprendiz,
            $estadoAprendiz
        );
    }
}

                $mapaAprendices[$clave] = $idAprendiz;

                if ($modelo->buscarMatricula($idAprendiz, $idFicha) === null) {
                    $modelo->insertarMatricula(
                        $idAprendiz,
                        $idFicha,
                        $fechaMatricula,
                        $fechaFinMatricula
                    );
                    $r['creados']['matriculas']++;
                }
            }

            /* ---------------- COMPETENCIAS + RAP ---------------- */

            $mapaCompetencias = []; // codigo => id
            $mapaRap = [];          // "codComp|codRap" => id

            foreach ($resultados as $fila) {

                $comp = $this->separarCodigoYNombre((string) ($fila['competencia'] ?? ''));
                $rapP = $this->separarCodigoYNombre((string) ($fila['resultado_aprendizaje'] ?? ''));

                if ($comp['codigo'] === '' || $rapP['codigo'] === '') {
                    continue; // se reporta al procesar el resultado
                }

                if (!isset($mapaCompetencias[$comp['codigo']])) {

                    $c = $modelo->buscarCompetencia($idPrograma, $comp['codigo']);

                    if ($c === null) {
                        $idComp = $modelo->insertarCompetencia(
                            $idPrograma,
                            $comp['codigo'],
                            $comp['nombre']
                        );
                        $r['creados']['competencias']++;
                    } else {
                        $idComp = (int) $c['id'];
                    }

                    $mapaCompetencias[$comp['codigo']] = $idComp;
                }

                $claveRap = $comp['codigo'] . '|' . $rapP['codigo'];

                if (!isset($mapaRap[$claveRap])) {

                    $idComp = $mapaCompetencias[$comp['codigo']];

                    $rp = $modelo->buscarRap($idComp, $rapP['codigo']);

                    if ($rp === null) {
                        $idRap = $modelo->insertarRap(
                            $idComp,
                            $rapP['codigo'],
                            $rapP['nombre']
                        );
                        $r['creados']['rap']++;
                    } else {
                        $idRap = (int) $rp['id'];
                    }

                    $mapaRap[$claveRap] = $idRap;
                }
            }

            /* ---------------- RESULTADOS ---------------- */

            foreach ($resultados as $fila) {

                $nFila = $fila['fila_excel'] ?? '-';

                $tipo = trim((string) ($fila['tipo_documento'] ?? ''));
                $doc  = trim((string) ($fila['numero_documento'] ?? ''));

                $idAprendiz = $mapaAprendices[strtoupper($tipo . '|' . $doc)] ?? null;

                if ($idAprendiz === null) {
                    $r['resultados']['omitidos']++;
                    $registrarError($r, $nFila, 'Aprendiz no disponible (documento ' . $doc . ').');
                    continue;
                }

                $comp = $this->separarCodigoYNombre((string) ($fila['competencia'] ?? ''));
                $rapP = $this->separarCodigoYNombre((string) ($fila['resultado_aprendizaje'] ?? ''));

                $idRap = $mapaRap[$comp['codigo'] . '|' . $rapP['codigo']] ?? null;

                if ($idRap === null) {
                    $r['resultados']['omitidos']++;
                    $registrarError($r, $nFila, 'Competencia o RAP sin código válido.');
                    continue;
                }

                $idInstructor = null;
                $funcTexto = trim((string) ($fila['funcionario'] ?? ''));

                if ($funcTexto !== '' && $funcTexto !== '-') {
                    $pf = $this->separarDocumentoYNombre($funcTexto);
                    $idInstructor = $mapaProfesionales[
                        strtoupper($pf['tipo_documento'] . '|' . $pf['documento'])
                    ] ?? null;
                }

                $juicioSena = strtoupper(trim((string) ($fila['juicio'] ?? '')));

                if ($juicioSena === 'APROBADO') {
                    $juicioBD = 'aprobado';
                } elseif ($juicioSena === 'NO APROBADO') {
                    $juicioBD = 'no_aprobado';
                } else {
                    $juicioBD = 'pendiente';
                }

                $nuevo = [
                    'id_aprendiz'           => $idAprendiz,
                    'id_ficha'              => $idFicha,
                    'id_rap'                => $idRap,
                    'id_instructor'         => $idInstructor,
                    'fecha_evaluacion'      => !empty($fila['fecha_evaluacion']) ? $fila['fecha_evaluacion'] : null,
                    'fecha_hora_evaluacion' => !empty($fila['fecha_hora_juicio']) ? $fila['fecha_hora_juicio'] : null,
                    'juicio'                => $juicioBD,
                    'juicio_sena'           => $juicioSena !== '' ? $juicioSena : null,
                    'calificacion'          => null,
                    'observaciones'         => null,
                ];

                $existentes = $modelo->buscarResultadosPorAprendizRapFicha(
                    $idAprendiz,
                    $idFicha,
                    $idRap
                );

                $accion = 'insertar';
                $idExistente = null;

                foreach ($existentes as $ex) {

                    // Igual en todo (se compara solo lo que trae el Excel).
                    if (
                        $modelo->valoresIguales($nuevo['id_instructor'], $ex['id_instructor']) &&
                        $modelo->valoresIguales($nuevo['fecha_evaluacion'], $ex['fecha_evaluacion']) &&
                        $modelo->valoresIguales($nuevo['fecha_hora_evaluacion'], $ex['fecha_hora_evaluacion']) &&
                        $modelo->valoresIguales($nuevo['juicio'], $ex['juicio']) &&
                        $modelo->valoresIguales($nuevo['juicio_sena'], $ex['juicio_sena'])
                    ) {
                        $accion = 'igual';
                        break;
                    }

                    // Misma fecha de evaluación pero con datos distintos:
                    // se actualiza ese registro (misma evaluación, otro estado).
                    if (
                        $nuevo['fecha_evaluacion'] !== null &&
                        $modelo->valoresIguales($nuevo['fecha_evaluacion'], $ex['fecha_evaluacion'])
                    ) {
                        $accion = 'actualizar';
                        $idExistente = (int) $ex['id'];
                        break;
                    }
                }

                if ($accion === 'igual') {
                    $r['resultados']['sin_cambios']++;
                } elseif ($accion === 'actualizar') {
                    $modelo->actualizarResultado($idExistente, $nuevo);
                    $r['resultados']['actualizados']++;
                } else {
                    // Fecha distinta (otro año/vigencia) o resultado
                    // nuevo: se inserta y se conserva el histórico.
                    $modelo->insertarResultado($nuevo);
                    $r['resultados']['nuevos']++;
                    $r['creados']['resultados']++;
                }
            }

            $db->commit();

            $r['exitosa'] = true;

        } catch (Throwable $e) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $r['exitosa'] = false;
            $r['mensaje_error'] = $e->getMessage();

            // Al revertir no se creó nada.
            foreach ($r['creados'] as $k => $v) {
                $r['creados'][$k] = 0;
            }
            foreach ($r['resultados'] as $k => $v) {
                $r['resultados'][$k] = 0;
            }
        }

        $r['realizada'] = $r['exitosa'];
        $r['error'] = $r['mensaje_error'];
        $r['programa_creado'] = $r['creados']['programa'];
        $r['programa_existente'] = $r['exitosa'] ? (1 - $r['creados']['programa']) : 0;
        $r['ficha_creada'] = $r['creados']['ficha'];
        $r['ficha_existente'] = $r['exitosa'] ? (1 - $r['creados']['ficha']) : 0;
        $r['personas_creadas'] = $r['creados']['personas'];
        $r['aprendices_creados'] = $r['creados']['aprendices'];
        $r['competencias_creadas'] = $r['creados']['competencias'];
        $r['rap_creados'] = $r['creados']['rap'];
        $r['profesionales_creados'] = $r['creados']['profesionales'];
        $r['matriculas_creadas'] = $r['creados']['matriculas'];
        $r['resultados_insertados'] = $r['resultados']['nuevos'];
        $r['resultados_iguales'] = $r['resultados']['sin_cambios'];
        $r['resultados_diferentes'] = $r['resultados']['actualizados'];

        return $r;
    }
}