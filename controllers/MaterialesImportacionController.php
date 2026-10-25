<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MaterialesImportacionController extends Controller
{
    private MaterialesImportacionModel $model;

    public function __construct()
    {
        $this->model = new MaterialesImportacionModel();
    }

    /**
     * Pantalla principal de importación.
     */
    public function index(): void
    {
        Auth::requirePermission('materiales.catalogo.ver');

        $importaciones = $this->model->listarImportaciones(20, 0);

        $this->view('materiales/importacion', [
            'importaciones' => $importaciones,
        ]);
    }

    /**
     * Recibe el archivo Excel, lo lee y genera la previsualización.
     *
     * IMPORTANTE:
     * Esta operación crea el registro de importación y sus detalles,
     * por lo tanto requiere permiso de creación.
     */
    public function previsualizar(): void
    {
        Auth::requirePermission('materiales.catalogo.crear');

        // El catálogo puede contener aproximadamente 49.000 registros.
        // Evita que PHP detenga el proceso a los 30 segundos por defecto.
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        if (!isPost()) {
            redirect('materiales/importacion');
        }

        exigirCsrf();

        if (
            !isset($_FILES['archivo_excel'])
            || !is_array($_FILES['archivo_excel'])
        ) {
            $this->errorImportacion(
                'No se recibió ningún archivo Excel.'
            );
        }

        $archivo = $_FILES['archivo_excel'];

        $errorUpload = (int) (
            $archivo['error'] ?? UPLOAD_ERR_NO_FILE
        );

        if ($errorUpload !== UPLOAD_ERR_OK) {
            $mensaje = $this->mensajeErrorUpload($errorUpload);

            $this->errorImportacion($mensaje);
        }

        $nombreArchivo = (string) (
            $archivo['name'] ?? ''
        );

        $rutaTemporal = (string) (
            $archivo['tmp_name'] ?? ''
        );

        if (
            $nombreArchivo === ''
            || $rutaTemporal === ''
        ) {
            $this->errorImportacion(
                'El archivo recibido no es válido.'
            );
        }

        if (!is_uploaded_file($rutaTemporal)) {
            $this->errorImportacion(
                'No fue posible validar el archivo cargado.'
            );
        }

        $extension = strtolower(
            pathinfo(
                $nombreArchivo,
                PATHINFO_EXTENSION
            )
        );

        $extensionesPermitidas = [
            'xls',
            'xlsx',
            'xlsm',
        ];

        if (
            !in_array(
                $extension,
                $extensionesPermitidas,
                true
            )
        ) {
            $this->errorImportacion(
                'El archivo debe estar en formato Excel (.xls, .xlsx o .xlsm).'
            );
        }

        $anioReferencia = $this->obtenerAnioReferencia();

        try {
            /*
             * ============================================================
             * LECTURA DEL EXCEL
             * ============================================================
             */
            $resultado = $this->leerExcel(
                $rutaTemporal
            );

            $filas = $resultado['filas'] ?? [];

            /*
             * Cantidad real de hojas que contienen las columnas
             * requeridas para materiales.
             */
            $hojasProcesadas = (int) (
                $resultado['hojas_procesadas'] ?? 0
            );

            if (empty($filas)) {
                $this->errorImportacion(
                    'El archivo Excel no contiene registros de materiales válidos.'
                );
            }

            /*
             * ============================================================
             * CREAR IMPORTACIÓN
             * ============================================================
             */
            $idUsuario = $this->obtenerIdUsuarioActual();

            $pdo = $this->model->getConnection();

            $pdo->beginTransaction();

            try {
                /*
                 * Se guarda también la cantidad de hojas procesadas.
                 */
                $idImportacion =
                    $this->model->crearImportacion(
                        $nombreArchivo,
                        $anioReferencia,
                        count($filas),
                        $idUsuario,
                        $hojasProcesadas
                    );

                /*
                 * ========================================================
                 * CONTADORES
                 * ========================================================
                 */
                $contadores = [
                    'total'        => 0,
                    'nuevo'        => 0,
                    'modificado'  => 0,
                    'sin_cambios' => 0,
                    'inactivado'  => 0,
                    'error'       => 0,
                ];

                /*
                 * ========================================================
                 * CONTROL DE REGISTROS COMPLETAMENTE DUPLICADOS
                 * DENTRO DEL MISMO ARCHIVO
                 *
                 * IMPORTANTE:
                 *
                 * El código UNSPSC NO es único.
                 *
                 * Por ejemplo:
                 *
                 * 40172906 + Adaptador Hembra...
                 * 40172906 + Adaptador Macho...
                 * 40172906 + Codo...
                 *
                 * son materiales diferentes y todos son válidos.
                 *
                 * Solamente consideramos duplicado dentro del archivo
                 * cuando coinciden:
                 *
                 * UNSPSC + descripción + unidad de medida
                 * ========================================================
                 */
                $registrosArchivo = [];

                // Se consulta el catálogo una sola vez en vez de ejecutar
                // una consulta SQL por cada fila del Excel.
                $indiceCatalogo = $this->model->obtenerIndiceMaterialesCatalogo();

                /*
                 * ========================================================
                 * PROCESAR CADA FILA
                 * ========================================================
                 */
                foreach ($filas as $fila) {
                    $contadores['total']++;

                    $filaExcel = (int) (
                        $fila['fila_excel'] ?? 0
                    );

                    $codigoOriginal = (string) (
                        $fila['codigo_unspsc'] ?? ''
                    );

                    $descripcionOriginal = (string) (
                        $fila['descripcion'] ?? ''
                    );

                    $codigo = $this->normalizarCodigo(
                        $codigoOriginal
                    );

                    $descripcion =
                        $this->normalizarTextoOriginal(
                            $descripcionOriginal
                        );

                    /*
                     * ----------------------------------------------------
                     * VALIDACIÓN DEL CÓDIGO
                     * ----------------------------------------------------
                     */
                    if ($codigo === '') {
                        $contadores['error']++;

                        $this->model->crearDetalle(
                            $idImportacion,
                            $filaExcel,
                            null,
                            $descripcion,
                            null,
                            'error',
                            'El código UNSPSC es obligatorio.',
                            null
                        );

                        continue;
                    }

                    /*
                     * ----------------------------------------------------
                     * VALIDACIÓN DE DESCRIPCIÓN
                     * ----------------------------------------------------
                     */
                    if ($descripcion === '') {
                        $contadores['error']++;

                        $this->model->crearDetalle(
                            $idImportacion,
                            $filaExcel,
                            $codigo,
                            null,
                            null,
                            'error',
                            'La descripción del bien es obligatoria.',
                            null
                        );

                        continue;
                    }


                    /* Código UNSPSC + nombre del producto. */
                    $claveMaterial =
                        $this->normalizarTexto($codigo)
                        . '|'
                        . $this->normalizarTexto($descripcion);

                    /*
                     * ----------------------------------------------------
                     * DUPLICADO COMPLETO DENTRO DEL MISMO EXCEL
                     *
                     * No se considera duplicado si solamente coincide
                     * el UNSPSC.
                     * ----------------------------------------------------
                     */
                    if (
                        isset(
                            $registrosArchivo[$claveMaterial]
                        )
                    ) {
                        $filaAnterior =
                            $registrosArchivo[
                                $claveMaterial
                            ];

                        $contadores['error']++;

                        $this->model->crearDetalle(
                            $idImportacion,
                            $filaExcel,
                            $codigo,
                            $descripcion,
                            null,
                            'error',
                            'El registro completo del material está '
                            . 'duplicado en el archivo. También aparece '
                            . 'en la fila Excel '
                            . $filaAnterior
                            . '. El código UNSPSC puede repetirse cuando '
                            . 'la descripción es diferente.',
                            null
                        );

                        continue;
                    }

                    $registrosArchivo[
                        $claveMaterial
                    ] = $filaExcel;

                    /*
                     * ----------------------------------------------------
                     * BUSCAR MATERIAL POR LA COMBINACIÓN COMPLETA
                     *
                     * IMPORTANTE:
                     *
                     * NO se busca únicamente por UNSPSC.
                     *
                     * El mismo UNSPSC puede representar varios
                     * materiales diferentes.
                     * ----------------------------------------------------
                     */
                    $claveCatalogo =
                        $this->normalizarTexto($codigo)
                        . '|'
                        . $this->normalizarTexto($descripcion);

                    $materialExistente =
                        $indiceCatalogo[$claveCatalogo] ?? null;

                    /*
                     * ----------------------------------------------------
                     * MATERIAL YA EXISTENTE
                     * ----------------------------------------------------
                     */
                    if ($materialExistente) {
                        $idMaterial = (int) (
                            $materialExistente['id'] ?? 0
                        );

                        $contadores['sin_cambios']++;

                        $this->model->crearDetalle(
                            $idImportacion,
                            $filaExcel,
                            $codigo,
                            $descripcion,
                            null,
                            'sin_cambios',
                            'El material ya existe con la '
                            . 'descripción.',
                            $idMaterial
                        );

                        continue;
                    }

                    /*
                     * ----------------------------------------------------
                     * MATERIAL NUEVO
                     *
                     * Puede tener un UNSPSC que ya exista en otros
                     * materiales. Eso NO es un problema.
                     * ----------------------------------------------------
                     */
                    $contadores['nuevo']++;

                    $this->model->crearDetalle(
                        $idImportacion,
                        $filaExcel,
                        $codigo,
                        $descripcion,
                        null,
                        'nuevo',
                        'Material nuevo. Será creado al aplicar la importación.',
                        null
                    );
                }

                /*
                 * ========================================================
                 * ACTUALIZAR RESUMEN
                 * ========================================================
                 */
                $this->model->actualizarResumen(
                    $idImportacion,
                    $contadores['nuevo'],
                    $contadores['modificado'],
                    $contadores['sin_cambios'],
                    $contadores['inactivado'],
                    $contadores['error']
                );

                /*
                 * ========================================================
                 * ESTADO DE LA IMPORTACIÓN
                 * ========================================================
                 */
                if ($contadores['error'] > 0) {
                    $this->model->cambiarEstado(
                        $idImportacion,
                        'lectura',
                        'La lectura terminó, pero se encontraron '
                        . $contadores['error']
                        . ' registro(s) con error. '
                        . 'La importación no puede aplicarse hasta corregir '
                        . 'los errores del archivo.'
                    );
                } else {
                    $this->model->cambiarEstado(
                        $idImportacion,
                        'validada',
                        'Lectura y validación preliminar completadas correctamente.'
                    );
                }

                $pdo->commit();

                flash(
                    'success',
                    'El archivo Excel fue leído correctamente. '
                    . 'Revise la previsualización antes de aplicar la importación.'
                );

                redirect(
                    'materiales/importacion/preview?id='
                    . $idImportacion
                );
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                throw $e;
            }
        } catch (Throwable $e) {
            $this->errorImportacion(
                'Error al procesar el archivo: '
                . $e->getMessage()
            );
        }
    }

    /**
     * Muestra la previsualización de una importación.
     */
    public function preview(): void
    {
        Auth::requirePermission(
            'materiales.catalogo.ver'
        );

        $id = isset($_GET['id'])
            ? (int) $_GET['id']
            : 0;

        if ($id <= 0) {
            flash(
                'error',
                'No se especificó una importación válida.'
            );

            redirect(
                'materiales/importacion'
            );
        }

        $importacion =
            $this->model->obtenerImportacion($id);

        if (!$importacion) {
            flash(
                'error',
                'La importación solicitada no existe.'
            );

            redirect(
                'materiales/importacion'
            );
        }

        /*
         * El modelo actual recibe únicamente el ID.
         */
        $detalles =
            $this->model->listarDetalles($id);

        $totalDetalles =
            $this->model->contarDetalles($id);

        $this->view(
            'materiales/importacion_preview',
            [
                'importacion'   => $importacion,
                'detalles'      => $detalles,
                'totalDetalles' => $totalDetalles,
            ]
        );
    }

    /**
     * Aplica definitivamente una importación validada.
     */
    public function aplicar(): void
    {
        Auth::requirePermission(
            'materiales.catalogo.crear'
        );

        // La aplicación de un catálogo grande también puede tardar más de 30 s.
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        if (!isPost()) {
            redirect(
                'materiales/importacion'
            );
        }

        exigirCsrf();

        /*
         * ================================================================
         * OBTENER ID DE LA IMPORTACIÓN
         * ================================================================
         *
         * La vista actual envía:
         *
         *     name="id"
         *
         * pero anteriormente este método esperaba:
         *
         *     name="id_importacion"
         *
         * Para mantener compatibilidad con ambos formatos, primero
         * intentamos id_importacion y, si no existe, utilizamos id.
         */
        $idImportacion = isset(
            $_POST['id_importacion']
        )
            ? (int) $_POST['id_importacion']
            : 0;

        if ($idImportacion <= 0) {
            $idImportacion = isset(
                $_POST['id']
            )
                ? (int) $_POST['id']
                : 0;
        }

        if ($idImportacion <= 0) {
            flash(
                'error',
                'No se especificó una importación válida.'
            );

            redirect(
                'materiales/importacion'
            );
        }

        $importacion =
            $this->model->obtenerImportacion(
                $idImportacion
            );

        if (!$importacion) {
            flash(
                'error',
                'La importación no existe.'
            );

            redirect(
                'materiales/importacion'
            );
        }

        $estado = strtolower(
            trim(
                (string) (
                    $importacion['estado'] ?? ''
                )
            )
        );

        if ($estado !== 'validada') {
            flash(
                'error',
                'La importación no puede aplicarse porque su estado actual es "'
                . $estado
                . '".'
            );

            redirect(
                'materiales/importacion/preview?id='
                . $idImportacion
            );
        }

        /*
         * ================================================================
         * OBTENER DETALLES
         * ================================================================
         */
        $detalles =
            $this->model->listarDetalles(
                $idImportacion
            );

        if (empty($detalles)) {
            flash(
                'error',
                'La importación no contiene registros para aplicar.'
            );

            redirect(
                'materiales/importacion/preview?id='
                . $idImportacion
            );
        }

        /*
         * ================================================================
         * NO PERMITIR APLICAR SI EXISTEN ERRORES
         * ================================================================
         */
        foreach ($detalles as $detalle) {
            $accion = strtolower(
                trim(
                    (string) (
                        $detalle['accion'] ?? ''
                    )
                )
            );

            if ($accion === 'error') {
                flash(
                    'error',
                    'La importación contiene registros con error y no puede aplicarse.'
                );

                redirect(
                    'materiales/importacion/preview?id='
                    . $idImportacion
                );
            }
        }

        $pdo =
            $this->model->getConnection();

        try {
            $pdo->beginTransaction();

            $nuevosAplicados = 0;
            $modificadosAplicados = 0;

            foreach ($detalles as $detalle) {
                $accion = strtolower(
                    trim(
                        (string) (
                            $detalle['accion'] ?? ''
                        )
                    )
                );

                $codigo =
                    $this->normalizarCodigo(
                        (string) (
                            $detalle['codigo_unspsc'] ?? ''
                        )
                    );

                $descripcion =
                    $this->normalizarTextoOriginal(
                        (string) (
                            $detalle['descripcion'] ?? ''
                        )
                    );

                $idMaterial = isset(
                    $detalle['id_material']
                )
                    ? (int) $detalle['id_material']
                    : 0;

                /*
                 * ========================================================
                 * NUEVO
                 * ========================================================
                 */
                if ($accion === 'nuevo') {
                    /*
                     * Se vuelve a comprobar la combinación completa.
                     *
                     * NO se utiliza únicamente el UNSPSC porque varios
                     * materiales pueden compartirlo.
                     */
                    $materialExistente =
                        $this->model->obtenerMaterialPorDatosCompletos(
                            $codigo,
                            $descripcion
                        );

                    if ($materialExistente) {
                        /*
                         * Otro proceso pudo haber creado exactamente el
                         * mismo material después de la previsualización.
                         *
                         * En ese caso no se vuelve a insertar.
                         */
                        continue;
                    }

                    $this->model->crearMaterialDesdeImportacion(
                        $codigo,
                        $descripcion
                    );

                    $nuevosAplicados++;

                    continue;
                }

                /*
                 * ========================================================
                 * MODIFICADO
                 *
                 * La importación actual no genera modificaciones,
                 * porque no existe un identificador único de material
                 * diferente de la combinación completa.
                 *
                 * Se conserva este bloque para compatibilidad con
                 * importaciones anteriores que pudieran contener esta
                 * acción.
                 * ========================================================
                 */
                if ($accion === 'modificado') {
                    if ($idMaterial <= 0) {
                        /*
                         * No buscamos solamente por UNSPSC.
                         */
                        $materialExistente =
                            $this->model->obtenerMaterialPorDatosCompletos(
                                $codigo,
                                $descripcion
                            );

                        if (!$materialExistente) {
                            throw new RuntimeException(
                                'No fue posible localizar el material '
                                . 'para actualizarlo.'
                            );
                        }

                        $idMaterial =
                            (int) (
                                $materialExistente['id'] ?? 0
                            );
                    }

                    $this->model->actualizarMaterialDesdeImportacion(
                        $idMaterial,
                        $descripcion
                    );

                    $modificadosAplicados++;

                    continue;
                }

                /*
                 * ========================================================
                 * SIN CAMBIOS
                 * ========================================================
                 */
                if ($accion === 'sin_cambios') {
                    continue;
                }

                /*
                 * ========================================================
                 * INACTIVADO
                 *
                 * Actualmente el Excel no contiene una columna de estado
                 * ni se deben inactivar automáticamente materiales que
                 * simplemente no aparezcan en el archivo.
                 * ========================================================
                 */
                if ($accion === 'inactivado') {
                    continue;
                }
            }

            /*
             * ============================================================
             * MARCAR IMPORTACIÓN COMO APLICADA
             * ============================================================
             */
            $observacionAplicacion =
                'Importación aplicada correctamente. '
                . 'Nuevos: '
                . $nuevosAplicados
                . '. Modificados: '
                . $modificadosAplicados
                . '.';

            $this->model->cambiarEstado(
                $idImportacion,
                'aplicada',
                $observacionAplicacion
            );

            $pdo->commit();

            flash(
                'success',
                'La importación fue aplicada correctamente. '
                . 'Nuevos: '
                . $nuevosAplicados
                . '. Modificados: '
                . $modificadosAplicados
                . '.'
            );

            redirect(
                'materiales/importacion'
            );
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            /*
             * Intentamos registrar el estado de error después
             * del rollback.
             */
            try {
                $this->model->cambiarEstado(
                    $idImportacion,
                    'error',
                    'Error al aplicar la importación: '
                    . $e->getMessage()
                );
            } catch (Throwable $errorEstado) {
                /*
                 * No interrumpir el manejo principal del error.
                 */
            }

            flash(
                'error',
                'No fue posible aplicar la importación: '
                . $e->getMessage()
            );

            redirect(
                'materiales/importacion/preview?id='
                . $idImportacion
            );
        }
    }

    /**
     * Lee todas las hojas del archivo Excel.
     *
     * IMPORTANTE:
     * Se utiliza getCell() + Coordinate::stringFromColumnIndex()
     * porque las versiones actuales de PhpSpreadsheet ya no disponen
     * de getCellByColumnAndRow().
     */
    private function leerExcel(string $rutaArchivo): array
    {
        if (!is_file($rutaArchivo)) {
            throw new RuntimeException('No se encontró el archivo temporal del Excel.');
        }

        $reader = IOFactory::createReaderForFile($rutaArchivo);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($rutaArchivo);

        $filas = [];
        $numeroHoja = 0;
        $hojasProcesadas = 0;

        foreach ($spreadsheet->getWorksheetIterator() as $hoja) {
            $numeroHoja++;
            $nombreHoja = (string) $hoja->getTitle();
            $highestRow = (int) $hoja->getHighestRow();
            $highestColumn = (string) $hoja->getHighestColumn();

            if ($highestRow < 1 || $highestColumn === '') {
                continue;
            }

            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

            /*
             * Buscar los encabezados en las primeras 10 filas.
             * Algunos catálogos incluyen un título o filas vacías antes
             * de las columnas. No se exige que los encabezados estén en A1/B1.
             */
            $filaEncabezados = null;
            $columnaCodigo = null;
            $columnaDescripcion = null;

            $ultimaFilaEncabezados = min($highestRow, 10);

            for ($filaCandidata = 1; $filaCandidata <= $ultimaFilaEncabezados; $filaCandidata++) {
                $encabezados = [];

                for ($columna = 1; $columna <= $highestColumnIndex; $columna++) {
                    $letraColumna = Coordinate::stringFromColumnIndex($columna);
                    $valor = $hoja->getCell($letraColumna . $filaCandidata)->getValue();

                    if (!is_scalar($valor)) {
                        continue;
                    }

                    $encabezado = $this->normalizarEncabezado((string) $valor);
                    if ($encabezado !== '') {
                        $encabezados[$encabezado] = $columna;
                    }
                }

                $codigoEncontrado = $this->buscarColumna($encabezados, [
                    'codigo unspsc', 'codigo_unspsc', 'codigounspsc',
                    'codigo', 'codigo de producto', 'codigo del producto',
                ]);
                $descripcionEncontrada = $this->buscarColumna($encabezados, [
                    'nombre producto', 'nombre del producto', 'nombre_producto',
                    'descripcion de bien', 'descripcion', 'descripcion del bien',
                    'descripcion_de_bien', 'nombre', 'producto',
                ]);

                if ($codigoEncontrado !== null && $descripcionEncontrada !== null) {
                    $filaEncabezados = $filaCandidata;
                    $columnaCodigo = $codigoEncontrado;
                    $columnaDescripcion = $descripcionEncontrada;
                    break;
                }
            }

            if ($filaEncabezados === null || $columnaCodigo === null || $columnaDescripcion === null) {
                continue;
            }

            $hojasProcesadas++;

            for ($filaExcel = $filaEncabezados + 1; $filaExcel <= $highestRow; $filaExcel++) {
                $codigo = $this->obtenerValorCelda($hoja, $columnaCodigo, $filaExcel);
                $descripcion = $this->obtenerValorCelda($hoja, $columnaDescripcion, $filaExcel);

                if (trim($codigo) === '' && trim($descripcion) === '') {
                    continue;
                }

                $filas[] = [
                    'hoja' => $nombreHoja,
                    'hoja_numero' => $numeroHoja,
                    'fila_excel' => $filaExcel,
                    'codigo_unspsc' => $this->normalizarCodigo($codigo),
                    'descripcion' => $this->normalizarTextoOriginal($descripcion),
                ];
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return [
            'filas' => $filas,
            'hojas_procesadas' => $hojasProcesadas,
        ];
    }

    private function obtenerValorCelda($hoja, int $columna, int $fila): string
    {
        $letraColumna = Coordinate::stringFromColumnIndex($columna);
        $valor = $hoja->getCell($letraColumna . $fila)->getValue();

        if ($valor === null) {
            return '';
        }
        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }
        if (is_scalar($valor)) {
            return trim((string) $valor);
        }
        return '';
    }

    /**
     * Busca una columna a partir de diferentes nombres posibles.
     */
    private function buscarColumna(
        array $encabezados,
        array $posiblesNombres
    ): ?int {
        foreach ($posiblesNombres as $nombre) {
            $normalizado =
                $this->normalizarEncabezado(
                    (string) $nombre
                );

            if (
                isset(
                    $encabezados[$normalizado]
                )
            ) {
                return (int) (
                    $encabezados[$normalizado]
                );
            }
        }

        return null;
    }

    /**
     * Normaliza encabezados del Excel.
     */
    private function normalizarEncabezado(
        string $texto
    ): string {
        // Quitar BOM y espacios no separables que a veces llegan de Excel.
        $texto = str_replace(["\xEF\xBB\xBF", "\u{FEFF}", "\xC2\xA0"], ' ', $texto);
        $texto = trim($texto);

        if ($texto === '') {
            return '';
        }

        /*
         * Elimina espacios repetidos.
         */
        $texto =
            preg_replace(
                '/\s+/u',
                ' ',
                $texto
            ) ?? $texto;

        /*
         * Convierte a minúsculas.
         */
        $texto =
            mb_strtolower(
                $texto,
                'UTF-8'
            );

        /*
         * Reemplaza caracteres equivalentes para facilitar
         * la comparación de encabezados.
         */
        $texto =
            str_replace(
                [
                    'á',
                    'é',
                    'í',
                    'ó',
                    'ú',
                    'ü',
                    'ñ',
                ],
                [
                    'a',
                    'e',
                    'i',
                    'o',
                    'u',
                    'u',
                    'n',
                ],
                $texto
            );

        // Tratar guiones bajos y signos de puntuación como separadores.
        $texto = str_replace(['_', '-', '.', ':'], ' ', $texto);
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

        return trim($texto);
    }

    /**
     * Normaliza el código UNSPSC.
     *
     * Si Excel lo interpreta como número decimal, por ejemplo:
     * 12345678.0
     *
     * se conserva como:
     * 12345678
     */
    private function normalizarCodigo(
        string $codigo
    ): string {
        $codigo = trim($codigo);

        if ($codigo === '') {
            return '';
        }

        /*
         * Eliminar espacios.
         */
        $codigo =
            preg_replace(
                '/\s+/u',
                '',
                $codigo
            ) ?? $codigo;

        /*
         * Excel puede entregar códigos numéricos como:
         * 12345678.0
         */
        if (
            preg_match(
                '/^(\d+)\.0+$/',
                $codigo,
                $matches
            )
        ) {
            $codigo = $matches[1];
        }

        return $codigo;
    }

    /**
     * Normaliza textos conservando mayúsculas/minúsculas originales.
     */
    private function normalizarTextoOriginal(
        string $texto
    ): string {
        $texto = trim($texto);

        if ($texto === '') {
            return '';
        }

        $texto =
            preg_replace(
                '/\s+/u',
                ' ',
                $texto
            ) ?? $texto;

        return trim($texto);
    }

    /**
     * Normalización utilizada únicamente para comparar.
     */
    private function normalizarTexto(
        string $texto
    ): string {
        $texto =
            $this->normalizarTextoOriginal(
                $texto
            );

        if ($texto === '') {
            return '';
        }

        return mb_strtolower(
            $texto,
            'UTF-8'
        );
    }

    /**
     * Obtiene el año de referencia enviado desde el formulario.
     */
    private function obtenerAnioReferencia(): ?int
    {
        $valor =
            $_POST['anio_referencia'] ?? null;

        if (
            $valor === null
            || trim((string) $valor) === ''
        ) {
            return null;
        }

        $anio = (int) $valor;

        if (
            $anio < 2000
            || $anio > 2100
        ) {
            $this->errorImportacion(
                'El año de referencia no es válido.'
            );
        }

        return $anio;
    }

    /**
     * Obtiene el ID del usuario autenticado.
     */
    private function obtenerIdUsuarioActual(): ?int
    {
        $usuario = Auth::user();

        if (is_object($usuario)) {
            $posiblesPropiedades = [
                'id',
                'id_usuario',
                'usuario_id',
                'user_id',
            ];

            foreach ($posiblesPropiedades as $propiedad) {
                if (
                    isset(
                        $usuario->{$propiedad}
                    )
                    && (int) (
                        $usuario->{$propiedad}
                    ) > 0
                ) {
                    return (int) (
                        $usuario->{$propiedad}
                    );
                }
            }
        }

        if (is_array($usuario)) {
            $posiblesClaves = [
                'id',
                'id_usuario',
                'usuario_id',
                'user_id',
            ];

            foreach ($posiblesClaves as $clave) {
                if (
                    isset(
                        $usuario[$clave]
                    )
                    && (int) (
                        $usuario[$clave]
                    ) > 0
                ) {
                    return (int) (
                        $usuario[$clave]
                    );
                }
            }
        }

        return null;
    }

    /**
     * Mensajes amigables para errores de carga.
     */
    private function mensajeErrorUpload(
        int $codigo
    ): string {
        return match ($codigo) {
            UPLOAD_ERR_INI_SIZE =>
                'El archivo supera el tamaño máximo permitido por el servidor.',

            UPLOAD_ERR_FORM_SIZE =>
                'El archivo supera el tamaño máximo permitido por el formulario.',

            UPLOAD_ERR_PARTIAL =>
                'El archivo fue cargado parcialmente.',

            UPLOAD_ERR_NO_FILE =>
                'No se seleccionó ningún archivo.',

            UPLOAD_ERR_NO_TMP_DIR =>
                'No existe el directorio temporal del servidor.',

            UPLOAD_ERR_CANT_WRITE =>
                'No fue posible escribir el archivo en el servidor.',

            UPLOAD_ERR_EXTENSION =>
                'Una extensión del servidor detuvo la carga del archivo.',

            default =>
                'Se produjo un error desconocido al cargar el archivo.',
        };
    }

    /**
     * Manejo centralizado de errores de importación.
     */
    private function errorImportacion(
        string $mensaje
    ): never {
        flash(
            'error',
            $mensaje
        );

        redirect(
            'materiales/importacion'
        );
    }
}