<?php
declare(strict_types=1);

class MaterialesRecepcionDistribucionController extends Controller
{
    private MaterialesRecepcionDistribucionModel $modelo;
    public function __construct() { Auth::requireLogin(); $this->modelo = new MaterialesRecepcionDistribucionModel(); }

    private function acceso(): array {
        $admin=Auth::hasRole('Administrador');
        if(!$admin&&!Auth::hasRole('Coordinador')){http_response_code(403);die('No tiene permisos para acceder a este módulo.');}
        $coord=0;
        if(!$admin){$coord=$this->modelo->profesionalUsuario((int)Auth::id())??0;if($coord<=0){http_response_code(403);die('El coordinador no tiene un profesional activo asociado.');}}
        return [$coord,$admin];
    }
    public function index(): void {
        [$coord,$admin]=$this->acceso();
        $this->view('materiales/recepcion_distribucion/index',['solicitudes'=>$this->modelo->solicitudes($coord,$admin),'historial'=>$this->modelo->historial($coord,$admin)]);
    }
    public function recibir(): void {
        if(!isPost()){$this->redirect('/materiales/recepcion-distribucion');}
        exigirCsrf();[$coord,$admin]=$this->acceso();
        try{$this->modelo->recibir((int)post('id_detalle',0),(float)str_replace(',', '.',(string)post('cantidad','0')),trim((string)post('fecha',date('Y-m-d'))),trim((string)post('remision','')),trim((string)post('observaciones','')),(int)Auth::id(),$coord,$admin);flash('success','Recepción registrada correctamente.');}
        catch(Throwable $e){flash('error',defined('APP_DEBUG')&&APP_DEBUG?$e->getMessage():'No fue posible registrar la recepción. Verifique las cantidades.');}
        $this->redirect('/materiales/recepcion-distribucion');
    }
    public function distribuir(): void {
        if(!isPost()){$this->redirect('/materiales/recepcion-distribucion');}
        exigirCsrf();[$coord,$admin]=$this->acceso();
        try{$this->modelo->distribuir((int)post('id_detalle',0),(int)post('id_ficha',0),(float)str_replace(',', '.',(string)post('cantidad','0')),trim((string)post('observaciones','')),(int)Auth::id(),$coord,$admin);flash('success','Distribución registrada correctamente.');}
        catch(Throwable $e){flash('error',defined('APP_DEBUG')&&APP_DEBUG?$e->getMessage():'No fue posible registrar la distribución. Verifique el saldo recibido y la ficha.');}
        $this->redirect('/materiales/recepcion-distribucion');
    }
}
