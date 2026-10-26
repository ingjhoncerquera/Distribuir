<?php
declare(strict_types=1);

class MaterialesRecepcionDistribucionController extends Controller
{
    private MaterialesRecepcionDistribucionModel $modelo;

    public function __construct()
    {
        Auth::requireLogin();
        $this->modelo = new MaterialesRecepcionDistribucionModel();
    }

    private function acceso(): array
    {
        $admin = Auth::hasRole('Administrador');
        if (!$admin && !Auth::hasRole('Coordinador')) {
            http_response_code(403);
            die('No tiene permisos para acceder a este módulo.');
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

    public function index(): void
    {
        $this->redirect('/materiales/recepcion-distribucion/recepcion');
    }

    public function recepcion(): void
    {
        [$coord, $admin] = $this->acceso();
        $this->view('materiales/recepcion_distribucion/recepcion', [
            'solicitudes' => $this->modelo->solicitudes($coord, $admin),
            'historial' => $this->modelo->historial($coord, $admin),
        ]);
    }

    public function distribucion(): void
    {
        [$coord, $admin] = $this->acceso();
        $this->view('materiales/recepcion_distribucion/distribucion', [
            'solicitudes' => $this->modelo->solicitudes($coord, $admin),
            'historial' => $this->modelo->historial($coord, $admin),
        ]);
    }


    public function recibir(): void
    {
        if (!isPost()) {
            $this->redirect('/materiales/recepcion-distribucion/recepcion');
        }
        exigirCsrf();
        [$coord, $admin] = $this->acceso();
        try {
            $this->modelo->recibir(
                (int) post('id_detalle', 0),
                (float) str_replace(',', '.', (string) post('cantidad', '0')),
                trim((string) post('fecha', date('Y-m-d'))),
                trim((string) post('remision', '')),
                trim((string) post('observaciones', '')),
                (int) Auth::id(),
                $coord,
                $admin
            );
            flash('success', 'Recepción registrada correctamente.');
        } catch (Throwable $e) {
            flash('error', defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'No fue posible registrar la recepción. Verifique las cantidades.');
        }
        $this->redirect('/materiales/recepcion-distribucion/recepcion');
    }

    
public function recibirMultiple(): void
    {
        if (!isPost()) $this->redirect('/materiales/recepcion-distribucion/recepcion');
        exigirCsrf();
        [$coord, $admin] = $this->acceso();
        try {
            $recepciones = post('recepciones', []);
            if (!is_array($recepciones)) throw new RuntimeException('El formato de las recepciones no es válido.');
            $this->modelo->recibirMultiple($recepciones, trim((string) post('fecha', date('Y-m-d'))), trim((string) post('remision', '')), trim((string) post('observaciones', '')), (int) Auth::id(), $coord, $admin);
            flash('success', 'Recepciones registradas correctamente.');
        } catch (Throwable $e) {
            flash('error', defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'No fue posible registrar las recepciones. Verifique las cantidades y los saldos pendientes.');
        }
        $this->redirect('/materiales/recepcion-distribucion/recepcion');
    }

    public function distribuir(): void
{
    if (!isPost()) {
        $this->redirect('/materiales/recepcion-distribucion/distribucion');
    }

    exigirCsrf();
    [$coord, $admin] = $this->acceso();

    try {
        $observaciones = trim((string) post('observaciones', ''));
        $usuario = (int) Auth::id();

        // Nuevo formato: varias asignaciones en una misma operación.
        $asignaciones = post('asignaciones', []);

        if (is_array($asignaciones) && count($asignaciones) > 0) {
            // El modelo debe validar y guardar el lote completo
            // dentro de una sola transacción.
            $this->modelo->distribuirMultiple(
                $asignaciones,
                $observaciones,
                $usuario,
                $coord,
                $admin
            );

            flash('success', 'Distribuciones registradas correctamente.');
        } else {
            // Compatibilidad con el formulario anterior.
            $this->modelo->distribuir(
                (int) post('id_detalle', 0),
                (int) post('id_ficha', 0),
                (float) str_replace(',', '.', (string) post('cantidad', '0')),
                $observaciones,
                $usuario,
                $coord,
                $admin
            );

            flash('success', 'Distribución registrada correctamente.');
        }
    } catch (Throwable $e) {
        flash(
            'error',
            defined('APP_DEBUG') && APP_DEBUG
                ? $e->getMessage()
                : 'No fue posible registrar la distribución. Verifique las cantidades y los saldos disponibles.'
        );
    }

    $this->redirect('/materiales/recepcion-distribucion/distribucion');
}

}