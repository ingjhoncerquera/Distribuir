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
        // El generador existente conserva la plantilla institucional y la lógica del F-14.
        // Indicar al generador existente que debe emitir el F-14 de entrega.
        // Se invoca exportarIndividual(), disponible también en versiones anteriores
        // del controlador de solicitudes, para evitar depender de exportarEntrega().
        $_GET['tipo_exportacion'] = 'entrega';
        $exportador = new MaterialesSolicitudController();
        $exportador->exportarIndividual();
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
        if ($idSolicitud <= 0) {
            http_response_code(400);
            exit('La solicitud indicada no es válida.');
        }

        $solicitud = null;
        foreach ($this->modelo->solicitudes($coord, $admin) as $item) {
            if ((int) ($item['id_solicitud'] ?? 0) === $idSolicitud) {
                $solicitud = $item;
                break;
            }
        }
        if (!$solicitud || ($solicitud['estado'] ?? '') !== 'avalada') {
            http_response_code(404);
            exit('La solicitud no existe, no está avalada o no está dentro de su ámbito de acceso.');
        }

        $plantillas = [
            ROOT_PATH . '/4. GFPI-F-201_Acta_De_Entrega_Materiales_De_Formación.docx',
            ROOT_PATH . '/storage/plantillas/4. GFPI-F-201_Acta_De_Entrega_Materiales_De_Formación.docx',
            ROOT_PATH . '/uploads/plantillas/4. GFPI-F-201_Acta_De_Entrega_Materiales_De_Formación.docx',
        ];
        $plantilla = null;
        foreach ($plantillas as $ruta) {
            if (is_file($ruta) && is_readable($ruta)) {
                $plantilla = $ruta;
                break;
            }
        }
        if ($plantilla === null || !class_exists('ZipArchive')) {
            http_response_code(500);
            exit('No se encontró la plantilla GFPI-F-201 o la extensión ZipArchive no está habilitada.');
        }

        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sita_acta_' . bin2hex(random_bytes(6));
        if (!mkdir($dir, 0700, true)) {
            http_response_code(500);
            exit('No fue posible preparar el acta.');
        }
        $salida = $dir . DIRECTORY_SEPARATOR . 'Acta_GFPI-F-201.docx';
        try {
            if (!copy($plantilla, $salida)) {
                throw new RuntimeException('No fue posible copiar la plantilla del acta.');
            }
            $zip = new ZipArchive();
            if ($zip->open($salida) !== true) {
                throw new RuntimeException('No fue posible abrir la plantilla DOCX.');
            }
            $xml = $zip->getFromName('word/document.xml');
            if ($xml === false || strpos($xml, '</w:body>') === false) {
                $zip->close();
                throw new RuntimeException('La plantilla no contiene un documento Word válido.');
            }

            $esc = static function ($value): string {
                return htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
            };
            $numero = $esc($solicitud['numero_solicitud'] ?? ('Solicitud ' . $idSolicitud));
            $instructor = $esc($solicitud['solicitante'] ?? '');
            $area = $esc($solicitud['nombre_area'] ?? '');
            $proceso = $esc($solicitud['nombre_proceso'] ?? '');
            $fecha = date('d/m/Y');

            $parrafo = static function (string $texto, bool $negrita = false): string {
                $contenido = htmlspecialchars($texto, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                $runPr = $negrita ? '<w:rPr><w:b/></w:rPr>' : '';
                return '<w:p><w:r>' . $runPr . '<w:t xml:space="preserve">' . $contenido . '</w:t></w:r></w:p>';
            };
            $celda = static function (string $texto, bool $negrita = false): string {
                $contenido = htmlspecialchars($texto, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                $runPr = $negrita ? '<w:rPr><w:b/></w:rPr>' : '';
                return '<w:tc><w:tcPr><w:tcW w:w="2200" w:type="dxa"/></w:tcPr><w:p><w:r>' . $runPr . '<w:t xml:space="preserve">' . $contenido . '</w:t></w:r></w:p></w:tc>';
            };
            $fila = static function (array $valores, bool $encabezado = false) use ($celda): string {
                $celdas = '';
                foreach ($valores as $valor) $celdas .= $celda((string) $valor, $encabezado);
                return '<w:tr>' . $celdas . '</w:tr>';
            };

            $anexo = '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
            $anexo .= $parrafo('ANEXO DE ENTREGA DE MATERIALES — GFPI-F-201', true);
            $anexo .= $parrafo('Número de solicitud: ' . html_entity_decode($numero, ENT_QUOTES | ENT_XML1, 'UTF-8'));
            $anexo .= $parrafo('Instructor solicitante: ' . html_entity_decode($instructor, ENT_QUOTES | ENT_XML1, 'UTF-8'));
            $anexo .= $parrafo('Área: ' . html_entity_decode($area, ENT_QUOTES | ENT_XML1, 'UTF-8') . ' | Proceso: ' . html_entity_decode($proceso, ENT_QUOTES | ENT_XML1, 'UTF-8'));
            $anexo .= $parrafo('Fecha de generación: ' . $fecha);
            $anexo .= $parrafo('Detalle de cantidades solicitadas y entregadas', true);
            $anexo .= '<w:tbl><w:tblPr><w:tblBorders><w:top w:val="single" w:sz="4"/><w:left w:val="single" w:sz="4"/><w:bottom w:val="single" w:sz="4"/><w:right w:val="single" w:sz="4"/><w:insideH w:val="single" w:sz="4"/><w:insideV w:val="single" w:sz="4"/></w:tblBorders></w:tblPr>';
            $anexo .= $fila(['Material', 'Unidad', 'Solicitado', 'Entregado'], true);
            $totalEntregado = 0.0;
            foreach (($solicitud['detalles'] ?? []) as $detalle) {
                $entregado = (float) ($detalle['distribuido'] ?? 0);
                $totalEntregado += $entregado;
                $anexo .= $fila([
                    (string) ($detalle['descripcion'] ?? 'Material'),
                    (string) ($detalle['unidad_medida'] ?? 'Unidad'),
                    number_format((float) ($detalle['cantidad_solicitada'] ?? 0), 2, ',', '.'),
                    number_format($entregado, 2, ',', '.'),
                ]);
            }
            $anexo .= '</w:tbl>';
            $anexo .= $parrafo('Total de unidades entregadas: ' . number_format($totalEntregado, 2, ',', '.'), true);
            $anexo .= $parrafo('Las cantidades entregadas corresponden a los movimientos de distribución registrados en SITA.');
            $anexo .= $parrafo('Firma de quien entrega: ____________________________________');
            $anexo .= $parrafo('Firma del instructor que recibe: _____________________________');
            $anexo .= $parrafo('Observaciones: ______________________________________________________________');

            $xml = str_replace('</w:body>', $anexo . '</w:body>', $xml);
            if (!$zip->addFromString('word/document.xml', $xml) || !$zip->close()) {
                throw new RuntimeException('No fue posible guardar el contenido diligenciado del acta.');
            }

            $nombre = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($solicitud['numero_solicitud'] ?? $idSolicitud));
            while (ob_get_level() > 0) ob_end_clean();
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="GFPI-F-201_Acta_' . $nombre . '.docx"');
            header('Content-Length: ' . filesize($salida));
            readfile($salida);
        } catch (Throwable $e) {
            error_log('[SITA Acta GFPI-F-201] ' . $e->getMessage());
            http_response_code(500);
            echo 'No fue posible generar el acta diligenciada. Revise el registro de errores.';
        } finally {
            @unlink($salida);
            @rmdir($dir);
        }
        exit;
    }
}
