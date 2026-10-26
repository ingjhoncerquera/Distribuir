<?php
declare(strict_types=1);

/** Controlador independiente del módulo Entregas. */
class EntregasController extends Controller
{
    private EntregasModel $modelo;

    public function __construct()
    {
        Auth::requireLogin();
        $this->modelo = new EntregasModel();
    }

    private function acceso(): array
    {
        $admin = Auth::hasRole('Administrador');
        if (!$admin && !Auth::hasRole('Coordinador')) {
            http_response_code(403);
            die('No tiene permisos para acceder al módulo Entregas.');
        }

        $coord = 0;
        if (!$admin) {
            $coord = $this->modelo->profesionalUsuario((int) Auth::id()) ?? 0;
            if ($coord <= 0) {
                http_response_code(403);
                die('El coordinador no tiene un profesional activo asociado.');
            }
        }
        return [$coord, $admin];
    }

    public function exportarF14Entrega(): void
    {
        [$coord, $admin] = $this->acceso();
        $idSolicitud = (int) ($_GET['id'] ?? $_GET['id_solicitud'] ?? 0);
        if ($idSolicitud <= 0) {
            http_response_code(400);
            exit('La solicitud indicada no es válida.');
        }

        // Restringir la exportación a solicitudes avaladas visibles para este usuario.
        $visible = false;
        foreach ($this->modelo->solicitudesAvaladas($coord, $admin) as $item) {
            if ((int) ($item['id_solicitud'] ?? 0) === $idSolicitud) {
                $visible = true;
                break;
            }
        }
        if (!$visible) {
            http_response_code(404);
            exit('La solicitud no existe, no está avalada o no está dentro de su ámbito de acceso.');
        }

        $solicitud = $this->modelo->obtenerSolicitud($idSolicitud);
        $detalles = $this->modelo->listarDetalles($idSolicitud);
        if (!$solicitud || !$detalles) {
            http_response_code(404);
            exit('No se encontró la solicitud o no tiene materiales para exportar.');
        }

        $fichas = [];
        foreach ($this->modelo->listarFichasDestinoPorSolicitud($idSolicitud) as $ficha) {
            $idFicha = (int) ($ficha['id_ficha'] ?? $ficha['id'] ?? 0);
            if ($idFicha > 0) {
                $fichas[$idFicha] = [
                    'id' => $idFicha,
                    'numero_ficha' => (string) ($ficha['numero_ficha'] ?? $idFicha),
                ];
            }
        }
        if (!$fichas) {
            foreach ($detalles as $detalle) {
                $idDetalle = (int) ($detalle['id'] ?? 0);
                foreach ($this->modelo->listarDestinosPorDetalle($idDetalle) as $destino) {
                    $idFicha = (int) ($destino['id_ficha'] ?? 0);
                    if ($idFicha > 0) {
                        $fichas[$idFicha] = [
                            'id' => $idFicha,
                            'numero_ficha' => (string) ($destino['numero_ficha'] ?? $idFicha),
                        ];
                    }
                }
            }
        }
        if (!$fichas) {
            http_response_code(422);
            exit('La solicitud no tiene fichas destino registradas.');
        }

        $numeroSolicitud = preg_replace('/[^A-Za-z0-9_-]/', '_',
            (string) ($solicitud['numero_solicitud'] ?? $idSolicitud));
        $prefijo = 'GIL-F-014_Entrega_';

        if (count($fichas) === 1) {
            $ficha = reset($fichas);
            $tmp = tempnam(sys_get_temp_dir(), 'sita_entrega_f14_');
            if ($tmp === false) {
                http_response_code(500);
                exit('No fue posible preparar el F-14 de entrega.');
            }
            try {
                $this->generarF14ParaFicha($idSolicitud, $solicitud, (int) $ficha['id'], $tmp);
                while (ob_get_level() > 0) ob_end_clean();
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $prefijo . $numeroSolicitud . '_Ficha_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $ficha['numero_ficha']) . '.xlsx"');
                header('Content-Length: ' . filesize($tmp));
                header('Cache-Control: max-age=0, must-revalidate');
                header('Pragma: public');
                readfile($tmp);
            } catch (Throwable $e) {
                error_log('[SITA F-14 Entrega] ' . $e->getMessage());
                http_response_code(500);
                echo 'No fue posible generar el F-14 de entrega. Revise el registro de errores.';
            } finally {
                @unlink($tmp);
            }
            exit;
        }

        if (!class_exists('ZipArchive')) {
            http_response_code(500);
            exit('La extensión ZipArchive de PHP no está habilitada.');
        }
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sita_entrega_f14_' . bin2hex(random_bytes(6));
        if (!mkdir($dir, 0700, true) && !is_dir($dir)) {
            http_response_code(500);
            exit('No fue posible preparar los F-14 de entrega.');
        }
        $zipPath = $dir . DIRECTORY_SEPARATOR . $prefijo . $numeroSolicitud . '.zip';
        $zip = new ZipArchive();
        try {
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('No fue posible crear el ZIP de F-14.');
            }
            foreach ($fichas as $ficha) {
                $nombreFicha = preg_replace('/[^A-Za-z0-9_-]/', '_', $ficha['numero_ficha']);
                $xlsx = $dir . DIRECTORY_SEPARATOR . 'GIL-F-014_Entrega_Ficha_' . $nombreFicha . '.xlsx';
                $this->generarF14ParaFicha($idSolicitud, $solicitud, (int) $ficha['id'], $xlsx);
                if (!$zip->addFile($xlsx, basename($xlsx))) {
                    throw new RuntimeException('No fue posible agregar el F-14 de la ficha ' . $nombreFicha . '.');
                }
            }
            if (!$zip->close()) {
                throw new RuntimeException('No fue posible finalizar el ZIP.');
            }
            while (ob_get_level() > 0) ob_end_clean();
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $prefijo . $numeroSolicitud . '.zip"');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);
        } catch (Throwable $e) {
            error_log('[SITA F-14 Entrega ZIP] ' . $e->getMessage());
            http_response_code(500);
            echo 'No fue posible generar los F-14 de entrega.';
        } finally {
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) @unlink($file);
            @rmdir($dir);
        }
        exit;
    }


    public function entregas(): void
    {
        [$coord, $admin] = $this->acceso();
        $this->view('materiales/recepcion_distribucion/entregas', [
            'solicitudes' => $this->modelo->solicitudesAvaladas($coord, $admin),
        ]);
    }

    public function descargarActa(): void
    {
        [$coord, $admin] = $this->acceso();
        $idSolicitud = (int) ($_GET['id_solicitud'] ?? 0);
        if ($idSolicitud <= 0) { http_response_code(400); exit('La solicitud indicada no es válida.'); }
        $solicitud = null;
        foreach ($this->modelo->solicitudesAvaladas($coord, $admin) as $item) {
            if ((int) ($item['id_solicitud'] ?? 0) === $idSolicitud) { $solicitud = $item; break; }
        }
        if (!$solicitud || ($solicitud['estado'] ?? '') !== 'avalada') { http_response_code(404); exit('La solicitud no existe, no está avalada o no está dentro de su ámbito de acceso.'); }

        $archivo = '4. GFPI-F-201_Acta_De_Entrega_Materiales_De_Formación.docx';
        $plantilla = null;
        foreach ([ROOT_PATH . '/' . $archivo, ROOT_PATH . '/storage/plantillas/' . $archivo, ROOT_PATH . '/uploads/plantillas/' . $archivo] as $ruta) {
            if (is_file($ruta) && is_readable($ruta)) { $plantilla = $ruta; break; }
        }
        if ($plantilla === null) { http_response_code(500); exit('No se encontró la plantilla original GFPI-F-201 en el servidor.'); }
        if (!class_exists('ZipArchive') || !class_exists('DOMDocument')) { http_response_code(500); exit('El servidor requiere las extensiones PHP ZipArchive y DOM.'); }

        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sita_acta_' . bin2hex(random_bytes(6));
        if (!mkdir($dir, 0700, true)) { http_response_code(500); exit('No fue posible preparar el acta.'); }
        $salida = $dir . DIRECTORY_SEPARATOR . 'Acta_GFPI-F-201.docx';
        try {
            if (!copy($plantilla, $salida)) throw new RuntimeException('No fue posible copiar la plantilla.');
            $zip = new ZipArchive();
            if ($zip->open($salida) !== true) throw new RuntimeException('No fue posible abrir la plantilla DOCX.');
            $xml = $zip->getFromName('word/document.xml');
            if ($xml === false) { $zip->close(); throw new RuntimeException('El DOCX no contiene word/document.xml.'); }

            $ficha = $this->modelo->obtenerDatosFichaFormato($idSolicitud);
            $datos = $this->modelo->obtenerSolicitud($idSolicitud) ?? [];
            $detalles = $this->modelo->listarDetalles($idSolicitud);
            $idFicha = (int) ($ficha['id_ficha'] ?? 0);
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = true;
            if (!$dom->loadXML($xml, LIBXML_NONET)) { $zip->close(); throw new RuntimeException('No fue posible leer el XML de la plantilla.'); }
            $xp = new DOMXPath($dom);
            $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
            $texto = static function (DOMNode $n) use ($ns): string {
                $v = ''; foreach ($n->getElementsByTagNameNS($ns, 't') as $t) $v .= $t->textContent; return trim($v);
            };
            $escribir = static function (DOMElement $celda, string $valor) use ($xp, $dom, $ns): void {
                $ts = $xp->query('.//w:t', $celda);
                if ($ts && $ts->length) {
                    $ts->item(0)->nodeValue = $valor;
                    for ($i = $ts->length - 1; $i > 0; $i--) { $n = $ts->item($i); if ($n && $n->parentNode) $n->parentNode->removeChild($n); }
                } else {
                    $p = $xp->query('./w:p', $celda)->item(0);
                    if (!$p) { $p = $dom->createElementNS($ns, 'w:p'); $celda->appendChild($p); }
                    $run = $dom->createElementNS($ns, 'w:r'); $t = $dom->createElementNS($ns, 'w:t');
                    $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                    $t->appendChild($dom->createTextNode($valor)); $run->appendChild($t); $p->appendChild($run);
                }
            };

            $campos = [
                'Centro de formación' => (string) ($ficha['centro'] ?? ''),
                'Código Centro de formación' => (string) ($ficha['codigo_centro'] ?? ''),
                'Programa de formación' => (string) ($ficha['nombre_programa'] ?? ''),
                'Código versión del programa' => trim((string) ($ficha['codigo_programa'] ?? '') . ' ' . (string) ($ficha['version_programa'] ?? '')),
                'Lugar de formación' => (string) ($ficha['centro'] ?? ''),
                'Nombre del responsable' => (string) ($datos['nombre_coordinador'] ?? ''),
                'Cargo' => '',
                'Correo electrónico' => '',
                'Identificación de la ficha' => (string) ($ficha['numero_ficha'] ?? ''),
                'Nombre completo' => '',
                'Documento de identidad' => '',
            ];
            foreach ($xp->query('//w:tbl/w:tr') as $fila) {
                $celdas = $xp->query('./w:tc', $fila);
                if (!$celdas || $celdas->length < 2) continue;
                for ($i = 0; $i < $celdas->length - 1; $i++) {
                    $etiqueta = preg_replace('/\\s+/u', ' ', $texto($celdas->item($i)));
                    foreach ($campos as $nombre => $valor) {
                        if ($etiqueta !== '' && stripos($etiqueta, $nombre) !== false) {
                            $destino = $celdas->item($i + 1);
                            if ($destino instanceof DOMElement) $escribir($destino, $valor);
                            break;
                        }
                    }
                }
            }

            $tabla = null;
            foreach ($xp->query('//w:tbl') as $t) {
                $contenido = strtolower($texto($t));
                if (strpos($contenido, 'material de formación') !== false && strpos($contenido, 'observaciones') !== false && strpos($contenido, 'cantidad') !== false) { $tabla = $t; break; }
            }
            if (!$tabla instanceof DOMElement) throw new RuntimeException('No se identificó la tabla de materiales original del formato; no se agregó ningún anexo.');
            $filas = $xp->query('./w:tr', $tabla);
            $filaModelo = null;
            foreach ($filas as $f) {
                $contenido = strtolower($texto($f));
                if (strpos($contenido, 'material de formación') === false && strpos($contenido, 'observaciones') === false && $xp->query('./w:tc', $f)->length >= 4) { $filaModelo = $f; break; }
            }
            if (!$filaModelo) throw new RuntimeException('No se encontró una fila para materiales en la tabla original.');
            $cantidadCeldas = $xp->query('./w:tc', $filaModelo)->length;
            foreach ($detalles as $i => $d) {
                $filaNueva = $i === 0 ? $filaModelo : $filaModelo->cloneNode(true);
                $celdas = $xp->query('./w:tc', $filaNueva);
                $idDetalle = (int) ($d['id'] ?? 0);
                $entregado = $idDetalle > 0 ? $this->modelo->cantidadDistribuidaPorDetalle($idDetalle, $idFicha) : 0;
                $valores = [(string) ($i + 1), (string) ($d['descripcion'] ?? $d['descripcion_tecnica'] ?? ''), number_format($entregado, 2, ',', '.'), $entregado > 0 ? 'Entregado' : 'Pendiente', (string) ($d['observaciones'] ?? '')];
                for ($j = 0; $j < min($celdas->length, $cantidadCeldas); $j++) { $c = $celdas->item($j); if ($c instanceof DOMElement) $escribir($c, $valores[$j] ?? ''); }
                if ($i > 0) $tabla->appendChild($filaNueva);
            }
            if (!$zip->addFromString('word/document.xml', $dom->saveXML()) || !$zip->close()) throw new RuntimeException('No fue posible guardar el acta diligenciada.');
            $nombre = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($solicitud['numero_solicitud'] ?? $idSolicitud));
            while (ob_get_level() > 0) ob_end_clean();
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="GFPI-F-201_Acta_' . $nombre . '.docx"');
            header('Content-Length: ' . filesize($salida));
            readfile($salida);
        } catch (Throwable $e) {
            error_log('[SITA Acta GFPI-F-201] ' . $e->getMessage());
            http_response_code(500); echo 'No fue posible generar el acta diligenciada. Revise el registro de errores.';
        } finally { @unlink($salida); @rmdir($dir); }
        exit;
    }

private function generarF14ParaFicha(int $idSolicitud, array $solicitud, int $idFichaFiltro, string $rutaSalida): void
{
    /*
     * UBICACIÓN DE LA PLANTILLA.
     *
     * Guarda el archivo original en:
     * storage/plantillas/GIL-F-014FormatoSolicitudbienesparausocuentadantes.xlsx
     */
    $rutasPlantilla = [
    ROOT_PATH . '/GIL-F-014FormatoSolicitudbienesparausocuentadantes.xlsx',
    ROOT_PATH . '/storage/plantillas/GIL-F-014FormatoSolicitudbienesparausocuentadantes.xlsx',
    ROOT_PATH . '/uploads/plantillas/GIL-F-014FormatoSolicitudbienesparausocuentadantes.xlsx',
];

$rutaPlantilla = null;

foreach ($rutasPlantilla as $ruta) {
    if (is_file($ruta) && is_readable($ruta)) {
        $rutaPlantilla = $ruta;
        break;
    }
}

if ($rutaPlantilla === null) {
    throw new \RuntimeException('No se encontró la plantilla GIL-F-014 en las rutas configuradas.');
}

    /*
     * Verificar que PhpSpreadsheet esté disponible.
     */
    if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
        throw new \RuntimeException('PhpSpreadsheet no está disponible. Verifique vendor/autoload.php.');
    }

    $detallesOriginales = $this->modelo->listarDetalles($idSolicitud);
    $detalles = [];

    if ($idFichaFiltro <= 0) {
        // Exportación individual: incluir cada producto una sola vez,
        // aunque no tenga distribución por ficha registrada.
        $detalles = $detallesOriginales;
    } else {
        foreach ($detallesOriginales as $detalleOriginal) {
            $idDetalleOriginal = (int) ($detalleOriginal['id'] ?? $detalleOriginal['id_detalle'] ?? 0);
            if ($idDetalleOriginal <= 0) continue;
            foreach ($this->modelo->listarDestinosPorDetalle($idDetalleOriginal) as $destinoFicha) {
                if ((int) ($destinoFicha['id_ficha'] ?? 0) !== $idFichaFiltro) continue;
                $detalleFicha = $detalleOriginal;
                $detalleFicha['cantidad_solicitada'] = (float) ($destinoFicha['cantidad_destinada'] ?? 0);
                $detalleFicha['_destino_ficha'] = $destinoFicha;
                $detalles[] = $detalleFicha;
            }
        }
    }

    if (empty($detalles)) {
        throw new RuntimeException('La solicitud no tiene materiales para exportar.');
    }

    try {
        $documento = \PhpOffice\PhpSpreadsheet\IOFactory::load(
            $rutaPlantilla
        );

        $hoja = $documento->getSheetByName('FORMATO GIL-F-014');

        if ($hoja === null) {
            throw new \RuntimeException(
                'La plantilla no contiene la hoja FORMATO GIL-F-014.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | DATOS GENERALES — FORMATO INDIVIDUAL GIL-F-014
        |--------------------------------------------------------------------------
        */

        $fecha = $solicitud['fecha_solicitud']
            ?? $solicitud['fecha_envio']
            ?? $solicitud['created_at']
            ?? $solicitud['fecha_creacion']
            ?? null;

        $hoja->setCellValue(
            'E10',
            $fecha ? date('d/m/Y', strtotime((string) $fecha)) : date('d/m/Y')
        );

        $hoja->setCellValue('K10', (string) ($solicitud['titulo'] ?? ''));

        // Nombre y documento del instructor solicitante.
        $nombreSolicitante = trim(
            (string) ($solicitud['nombres'] ?? '') . ' ' .
            (string) ($solicitud['apellidos'] ?? '')
        );

        $documentoSolicitante = trim(
            (string) ($solicitud['documento'] ?? '')
        );

        $hoja->setCellValue('H15', $nombreSolicitante);
        $hoja->setCellValueExplicit(
            'M15',
            $documentoSolicitante,
            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
        );

        // Nombre del coordinador asignado al proceso.
        $hoja->setCellValue(
            'H13',
            (string) ($solicitud['nombre_coordinador'] ?? '')
        );

        // Recuperar los datos reales de las fichas destino.
        $destinos = [];
        $fichas = [];
        $programas = [];
        $regionales = [];
        $centros = [];

        foreach ($detalles as $detalle) {
            $idDetalle = (int) (
                $detalle['id']
                ?? $detalle['id_detalle']
                ?? 0
            );

            if ($idDetalle <= 0) {
                continue;
            }

            foreach ($this->modelo->listarDestinosPorDetalle($idDetalle) as $destino) {
                if ($idFichaFiltro > 0 && (int) ($destino['id_ficha'] ?? 0) !== $idFichaFiltro) continue;
                $tipoDestino = trim((string) ($destino['tipo_destino'] ?? ''));
                if ($tipoDestino !== '') {
                    $destinos[$tipoDestino] = $tipoDestino;
                }

                $numeroFicha = trim((string) ($destino['numero_ficha'] ?? ''));
                $nombrePrograma = trim((string) ($destino['nombre_programa'] ?? ''));

                if ($numeroFicha !== '') {
                    $fichas[$numeroFicha] = $numeroFicha;
                    $fichaPrograma = $numeroFicha;
                    if ($nombrePrograma !== '') {
                        $fichaPrograma .= ' - ' . $nombrePrograma;
                    }
                    $programas[$fichaPrograma] = $fichaPrograma;
                }

                $regional = trim((string) ($destino['regional'] ?? ''));
                if ($regional !== '') {
                    $regionales[$regional] = $regional;
                }

                $centro = trim((string) ($destino['centro'] ?? ''));
                if ($centro !== '') {
                    $centros[$centro] = $centro;
                }
            }
        }

        // Consultar regional y centro directamente desde la ficha destino
        // de la solicitud, además de conservar los destinos y programas.
        $datosFichaFormato = $this->modelo->obtenerDatosFichaFormato($idSolicitud);

        $partirCodigoNombre = static function ($valor): array {
            $valor = trim((string) $valor);
            if (preg_match('/^\s*(\d+)\s*-\s*(.*?)\s*$/u', $valor, $m)) {
                return [trim($m[1]), trim($m[2])];
            }
            return ['', $valor];
        };

        [$codigoRegional, $nombreRegional] = $partirCodigoNombre(
            $regionales ? reset($regionales) : ($datosFichaFormato['regional'] ?? '')
        );
        [$codigoCentro, $nombreCentro] = $partirCodigoNombre(
            $centros ? reset($centros) : ($datosFichaFormato['centro'] ?? '')
        );

        // Celdas de la plantilla GIL-F-014: código regional E11,
        // código de centro E12 y nombre del centro L12.
        $hoja->setCellValueExplicit(
            'E11',
            $codigoRegional,
            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
        );
        $hoja->setCellValueExplicit(
            'E12',
            $codigoCentro,
            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
        );
        $hoja->setCellValue('L12', $nombreCentro);

        // Destino y ficha/programa de formación.
		 $hoja->setCellValue('H14', 'UNIPERSONAL');
        $hoja->setCellValue('H22', 'Formación');

        // H23 contiene el código de ficha y el nombre del programa.
        // El rótulo original se conserva en B23 de la plantilla.
        $textoFichaPrograma = implode(' / ', array_values($programas));
        if ($textoFichaPrograma === '' && !empty($datosFichaFormato['numero_ficha'])) {
            $textoFichaPrograma = trim(
                (string) $datosFichaFormato['numero_ficha'] .
                (!empty($datosFichaFormato['nombre_programa'])
                    ? ' - ' . (string) $datosFichaFormato['nombre_programa']
                    : '')
            );
        }
        $hoja->setCellValue('H23', $textoFichaPrograma);
		$hoja->setCellValue('E9', 'X');
        // La plantilla tiene seis filas de productos (26 a 31).
        // Si hay más productos, insertar filas antes del pie del formato.
        $filaInicialProductos = 26;
        $filasProductosPlantilla = 6;
        $cantidadProductos = count($detalles);
        $filasAdicionales = max(0, $cantidadProductos - $filasProductosPlantilla);

        if ($filasAdicionales > 0) {
            // Insertar antes de la fila 32 para desplazar autorización y notas.
            $hoja->insertNewRowBefore(32, $filasAdicionales);

            // Copiar el formato de la última fila original de productos.
            $filaModelo = 31;
            for ($nuevaFila = 32; $nuevaFila < 32 + $filasAdicionales; $nuevaFila++) {
                $hoja->duplicateStyle(
                    $hoja->getStyle('A' . $filaModelo . ':M' . $filaModelo),
                    'A' . $nuevaFila . ':M' . $nuevaFila
                );
                $alturaModelo = $hoja->getRowDimension($filaModelo)->getRowHeight();
                if ($alturaModelo > 0) {
                    $hoja->getRowDimension($nuevaFila)->setRowHeight($alturaModelo);
                }
                foreach (range('A', 'M') as $columna) {
                    $celdaModelo = $columna . $filaModelo;
                    $celdaNueva = $columna . $nuevaFila;
                    $hoja->getCell($celdaNueva)->setXfIndex(
                        $hoja->getCell($celdaModelo)->getXfIndex()
                    );
                }
                // Combinar las celdas de cada fila adicional igual que en la plantilla.
                foreach ([
                    'C' . $nuevaFila . ':D' . $nuevaFila,
                    'E' . $nuevaFila . ':G' . $nuevaFila,
                    'K' . $nuevaFila . ':M' . $nuevaFila,
                ] as $rangoCombinar) {
                    $celdaInicio = explode(':', $rangoCombinar)[0];
                    if (!$hoja->getCell($celdaInicio)->isInMergeRange()) {
                        $hoja->mergeCells($rangoCombinar);
                    }
                }
            }
        }

        $fila = $filaInicialProductos;
        $item = 1;

        foreach ($detalles as $detalle) {
            $hoja->setCellValue('B' . $fila, $item++);

            $hoja->setCellValueExplicit(
                'C' . $fila,
                (string) ($detalle['codigo_unspsc'] ?? ''),
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );

            $nombreProducto = trim((string) ($detalle['descripcion'] ?? ''));
            $descripcionTecnica = trim((string) ($detalle['descripcion_tecnica'] ?? ''));

            // Capitalizar la primera letra de cada texto sin alterar el resto.
            $capitalizarInicial = static function (string $texto): string {
                $texto = trim($texto);
                if ($texto === '') {
                    return '';
                }
                if (function_exists('mb_substr') && function_exists('mb_strtoupper') && function_exists('mb_strtolower')) {
                    $texto = mb_strtolower($texto, 'UTF-8');
                    return mb_strtoupper(mb_substr($texto, 0, 1, 'UTF-8'), 'UTF-8')
                        . mb_substr($texto, 1, null, 'UTF-8');
                }
                $texto = strtolower($texto);
                return strtoupper(substr($texto, 0, 1)) . substr($texto, 1);
            };

            $nombreProducto = $capitalizarInicial($nombreProducto);
            $descripcionTecnica = $capitalizarInicial($descripcionTecnica);
            $descripcionCompleta = $nombreProducto;

            if ($descripcionTecnica !== '') {
                $descripcionCompleta .= ' | ' . $descripcionTecnica;
            }

            $hoja->setCellValue('E' . $fila, $descripcionCompleta);
            $hoja->setCellValue('H' . $fila, (string) ($detalle['unidad_medida'] ?? ''));
            $hoja->setCellValue('I' . $fila, (float) ($detalle['cantidad_solicitada'] ?? 0));
            $idDetalleFila = (int) ($detalle['id'] ?? $detalle['id_detalle'] ?? 0);
            $hoja->setCellValue('J' . $fila, $this->modelo->cantidadDistribuidaPorDetalle($idDetalleFila, $idFichaFiltro));
            $hoja->setCellValue('K' . $fila, (string) ($detalle['observaciones'] ?? ''));
            $fila++;
        }

        // Mantener la firma en la fila 33 cuando hay hasta seis productos.
        // Si se insertan filas por productos adicionales, el formato desplaza la firma.
        $filaCoordinadora = 33 + $filasAdicionales;
        $hoja->setCellValue(
            'E' . $filaCoordinadora,
            (string) ($solicitud['nombre_coordinador'] ?? '')
        );
        $hoja->setCellValue('K' . $filaCoordinadora, '');
        $hoja->setCellValue('E' . ($filaCoordinadora + 1), '');

        $nombreArchivo = 'GIL-F-014_Solicitud_'
            . preg_replace(
                '/[^A-Za-z0-9_-]/',
                '_',
                (string) (
                    $solicitud['numero_solicitud']
                    ?? $solicitud['numero']
                    ?? $idSolicitud
                )
            )
            . '.xlsx';

        $escritor = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($documento);
        $escritor->save($rutaSalida);
        return;

    } catch (\Throwable $e) {
        error_log(
            '[SITA GIL-F-014] Error: ' . $e->getMessage()
            . ' | Archivo: ' . $e->getFile()
            . ' | Línea: ' . $e->getLine()
        );

        throw new \RuntimeException(
            'No fue posible generar el formato GIL-F-014. Revise el registro de errores de PHP.',
            0,
            $e
        );
    }
}

}
