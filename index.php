<?php

declare(strict_types=1);

define('ROOT_PATH', __DIR__);
define('BASE_URL', '/trazabilidadaprendices');
define('APP_DEBUG', true);

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
}

// ============================================================
// CONFIGURACIÓN BASE
// ============================================================

require_once ROOT_PATH . '/config/Database.php';
require_once ROOT_PATH . '/vendor/autoload.php';

require_once ROOT_PATH . '/core/Router.php';
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Auth.php';

require_once ROOT_PATH . '/helpers/helpers.php';

require_once ROOT_PATH . '/models/UsuarioModel.php';

require_once ROOT_PATH . '/controllers/LoginController.php';
require_once ROOT_PATH . '/controllers/DashboardController.php';
require_once ROOT_PATH . '/controllers/ImportacionController.php';


// ============================================================
// MÓDULO APRENDICES
// ============================================================

require_once ROOT_PATH . '/models/AprendizModel.php';
require_once ROOT_PATH . '/controllers/AprendizController.php';


// ============================================================
// MÓDULO CUESTIONARIO DE BIENESTAR
// ============================================================

require_once ROOT_PATH . '/models/BienestarCuestionarioModel.php';
require_once ROOT_PATH . '/controllers/BienestarPublicoController.php';

require_once ROOT_PATH . '/models/BienestarConsultaModel.php';
require_once ROOT_PATH . '/controllers/BienestarConsultaController.php';

// ============================================================
// CONSULTA DE FICHA DE CARACTERIZACIÓN
// ============================================================

require_once ROOT_PATH . '/models/FichaCaracterizacionModel.php';
require_once ROOT_PATH . '/controllers/FichaCaracterizacionController.php';

require_once ROOT_PATH . '/models/BienestarHabilidadesBlandasModel.php';
require_once ROOT_PATH . '/controllers/BienestarHabilidadesBlandasController.php';


// ============================================================
// MÓDULO ACTIVIDAD FÍSICA
// ============================================================

require_once ROOT_PATH . '/models/BienestarActividadFisicaModel.php';
require_once ROOT_PATH . '/controllers/BienestarActividadFisicaController.php';


// ============================================================
// MÓDULO SEGUIMIENTO FÍSICO DE PROMOCIÓN Y PREVENCIÓN
// ============================================================

require_once ROOT_PATH . '/models/SeguimientoFisicoModel.php';
require_once ROOT_PATH . '/controllers/SeguimientoFisicoController.php';


// ============================================================
// MÓDULO HABILIDADES ARTÍSTICAS
// ============================================================

require_once ROOT_PATH . '/models/BienestarHabilidadesArtisticasModel.php';
require_once ROOT_PATH . '/controllers/BienestarHabilidadesArtisticasController.php';


// ============================================================
// MÓDULO FICHAS
// ============================================================

require_once ROOT_PATH . '/models/FichaModel.php';
require_once ROOT_PATH . '/controllers/FichaController.php';


// ============================================================
// MÓDULO PROGRAMAS
// ============================================================

require_once ROOT_PATH . '/models/ProgramaModel.php';
require_once ROOT_PATH . '/controllers/ProgramaController.php';


// ============================================================
// MÓDULO ASIGNACIONES DE FICHA
// ============================================================

require_once ROOT_PATH . '/models/AsignacionFichaModel.php';
require_once ROOT_PATH . '/controllers/AsignacionFichaController.php';


// ============================================================
// MÓDULO PROFESIONALES
// ============================================================

require_once ROOT_PATH . '/models/ProfesionalModel.php';
require_once ROOT_PATH . '/controllers/ProfesionalController.php';


// ============================================================
// MÓDULO ROLES Y PERMISOS
// ============================================================

require_once ROOT_PATH . '/models/RolModel.php';
require_once ROOT_PATH . '/controllers/RolesController.php';


// ============================================================
// MÓDULO REPORTES
// ============================================================

require_once ROOT_PATH . '/models/ReporteModel.php';
require_once ROOT_PATH . '/controllers/ReportesController.php';


// ============================================================
// MÓDULO MATERIALES
// ============================================================

require_once ROOT_PATH . '/models/MaterialesModel.php';
require_once ROOT_PATH . '/controllers/MaterialesController.php';

require_once ROOT_PATH . '/models/MaterialesImportacionModel.php';
require_once ROOT_PATH . '/controllers/MaterialesImportacionController.php';


// ============================================================
// MÓDULO PROCESOS DE MATERIALES
// ============================================================

require_once ROOT_PATH . '/models/MaterialesProcesoModel.php';
require_once ROOT_PATH . '/controllers/MaterialesProcesoController.php';


// ============================================================
// MÓDULO ÁREAS Y RESPONSABLES DE MATERIALES
// ============================================================

require_once ROOT_PATH . '/models/MaterialesProcesoAreaModel.php';
require_once ROOT_PATH . '/controllers/MaterialesProcesoAreaController.php';


// ============================================================
// MÓDULO SOLICITUDES DE MATERIALES
// ============================================================

require_once ROOT_PATH . '/models/MaterialesSolicitudModel.php';
require_once ROOT_PATH . '/controllers/MaterialesSolicitudController.php';

// Módulo de recepción y distribución de materiales
require_once ROOT_PATH . '/models/MaterialesRecepcionDistribucionModel.php';
require_once ROOT_PATH . '/controllers/MaterialesRecepcionDistribucionController.php';


// ============================================================
// SESIÓN
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ============================================================
// ROUTER
// ============================================================

$router = new Router();


// ============================================================
// INICIO
// ============================================================

$router->get('/', function () {

    if (Auth::check()) {
        redirect('/dashboard');
    }

    redirect('/login');
});


// ============================================================
// AUTENTICACIÓN
// ============================================================

$router->get(
    '/login',
    [LoginController::class, 'index']
);

$router->post(
    '/login',
    [LoginController::class, 'autenticar']
);

$router->get(
    '/logout',
    [LoginController::class, 'salir']
);


// ============================================================
// DASHBOARD
// ============================================================

$router->get(
    '/dashboard',
    [DashboardController::class, 'index']
);


// ============================================================
// APRENDICES
// ============================================================

$router->get(
    '/aprendices',
    [AprendizController::class, 'index']
);

$router->get(
    '/aprendices/detalle',
    [AprendizController::class, 'detalle']
);

// Caracterización administrativa: se conserva la ruta existente.
$router->get(
    '/aprendices/caracterizacion',
    [AprendizController::class, 'caracterizacion']
);

$router->post(
    '/aprendices/caracterizacion/actualizar',
    [AprendizController::class, 'actualizarCaracterizacion']
);


// ============================================================
// FICHAS
// ============================================================

$router->get(
    '/fichas',
    [FichaController::class, 'index']
);

$router->post(
    '/fichas/crear',
    [FichaController::class, 'guardar']
);

$router->get(
    '/fichas/detalle',
    [FichaController::class, 'detalle']
);

// Generar u obtener el enlace único de Bienestar por ficha.
$router->post(
    '/fichas/generar-enlace-bienestar',
    [FichaController::class, 'generarEnlaceBienestar']
);


// ============================================================
// CUESTIONARIO ÚNICO DE BIENESTAR
// ============================================================

$router->get(
    '/bienestar/cuestionario',
    [BienestarPublicoController::class, 'index']
);

$router->post(
    '/bienestar/cuestionario/validar',
    [BienestarPublicoController::class, 'validar']
);

$router->post(
    '/bienestar/cuestionario/guardar',
    [BienestarPublicoController::class, 'guardar']
);

$router->post(
    '/bienestar/cuestionario/finalizar',
    [BienestarPublicoController::class, 'finalizar']
);


// ============================================================
// CARACTERIZACIÓN PÚBLICA DEL APRENDIZ
// ============================================================

// Estas rutas son independientes de la caracterización
// administrativa y utilizan el token de la ficha.
//
// Los métodos caracterizacionPublica() y
// actualizarCaracterizacionPublica() deben existir en
// BienestarPublicoController.

$router->get(
    '/bienestar/caracterizacion',
    [BienestarPublicoController::class, 'caracterizacionPublica']
);

$router->post(
    '/bienestar/caracterizacion/actualizar',
    [BienestarPublicoController::class, 'actualizarCaracterizacionPublica']
);


// ============================================================
// PROGRAMAS
// ============================================================

$router->get(
    '/programas',
    [ProgramaController::class, 'index']
);

$router->get(
    '/programas/crear',
    [ProgramaController::class, 'crear']
);

$router->post(
    '/programas/crear',
    [ProgramaController::class, 'guardar']
);

$router->get(
    '/programas/editar',
    [ProgramaController::class, 'editar']
);

$router->post(
    '/programas/editar',
    [ProgramaController::class, 'actualizar']
);

$router->post(
    '/programas/estado',
    [ProgramaController::class, 'cambiarEstado']
);


// ============================================================
// MATERIALES
// ============================================================

$router->get(
    '/materiales',
    [MaterialesController::class, 'index']
);

$router->get(
    '/materiales/crear',
    [MaterialesController::class, 'crear']
);

$router->post(
    '/materiales/crear',
    [MaterialesController::class, 'guardar']
);

$router->get(
    '/materiales/editar',
    [MaterialesController::class, 'editar']
);

$router->post(
    '/materiales/editar',
    [MaterialesController::class, 'actualizar']
);

$router->post(
    '/materiales/estado',
    [MaterialesController::class, 'cambiarEstado']
);


// ============================================================
// IMPORTACIÓN DEL CATÁLOGO DE MATERIALES
// ============================================================

$router->get(
    '/materiales/importacion',
    [MaterialesImportacionController::class, 'index']
);

$router->post(
    '/materiales/importacion',
    [MaterialesImportacionController::class, 'previsualizar']
);

$router->get(
    '/materiales/importacion/preview',
    [MaterialesImportacionController::class, 'preview']
);

$router->post(
    '/materiales/importacion/aplicar',
    [MaterialesImportacionController::class, 'aplicar']
);


// ============================================================
// PROCESOS DE MATERIALES
// ============================================================

$router->get(
    '/materiales/procesos',
    [MaterialesProcesoController::class, 'index']
);

$router->get(
    '/materiales/procesos/crear',
    [MaterialesProcesoController::class, 'crear']
);

$router->post(
    '/materiales/procesos/guardar',
    [MaterialesProcesoController::class, 'guardar']
);

$router->get(
    '/materiales/procesos/editar',
    [MaterialesProcesoController::class, 'editar']
);

$router->post(
    '/materiales/procesos/actualizar',
    [MaterialesProcesoController::class, 'actualizar']
);

$router->post(
    '/materiales/procesos/estado',
    [MaterialesProcesoController::class, 'estado']
);

$router->post(
    '/materiales/procesos/eliminar',
    [MaterialesProcesoController::class, 'eliminar']
);


// ============================================================
// ÁREAS Y RESPONSABLES DE MATERIALES
// ============================================================

$router->get(
    '/materiales/areas',
    [MaterialesProcesoAreaController::class, 'index']
);

$router->get(
    '/materiales/areas/crear',
    [MaterialesProcesoAreaController::class, 'crear']
);

$router->post(
    '/materiales/areas/guardar',
    [MaterialesProcesoAreaController::class, 'guardar']
);

$router->get(
    '/materiales/areas/editar',
    [MaterialesProcesoAreaController::class, 'editar']
);

$router->post(
    '/materiales/areas/actualizar',
    [MaterialesProcesoAreaController::class, 'actualizar']
);

$router->post(
    '/materiales/areas/estado',
    [MaterialesProcesoAreaController::class, 'estado']
);

$router->post(
    '/materiales/areas/responsable',
    [MaterialesProcesoAreaController::class, 'responsable']
);

$router->post(
    '/materiales/areas/responsable/retirar',
    [MaterialesProcesoAreaController::class, 'retirarResponsable']
);

$router->get(
    '/materiales/solicitudes/buscar-materiales',
    [MaterialesSolicitudController::class, 'buscarMateriales']
);


// ============================================================
// SOLICITUDES DE MATERIALES
// ============================================================

$router->get(
    '/materiales/solicitudes',
    [MaterialesSolicitudController::class, 'index']
);

$router->get(
    '/materiales/solicitudes/crear',
    [MaterialesSolicitudController::class, 'crear']
);

$router->get(
    '/materiales/solicitudes/areas',
    [MaterialesSolicitudController::class, 'cargarAreas']
);

$router->get(
    '/materiales/solicitudes/materiales',
    [MaterialesSolicitudController::class, 'materiales']
);

$router->post(
    '/materiales/solicitudes/carrito/agregar',
    [MaterialesSolicitudController::class, 'agregarMaterial']
);

$router->post(
    '/materiales/solicitudes/carrito/actualizar',
    [MaterialesSolicitudController::class, 'actualizarCarrito']
);

$router->post(
    '/materiales/solicitudes/carrito/quitar',
    [MaterialesSolicitudController::class, 'quitarMaterial']
);

$router->post(
    '/materiales/solicitudes/carrito/vaciar',
    [MaterialesSolicitudController::class, 'vaciarCarrito']
);

$router->post(
    '/materiales/solicitudes/guardar',
    [MaterialesSolicitudController::class, 'guardarBorrador']
);

$router->get(
    '/materiales/solicitudes/ver',
    [MaterialesSolicitudController::class, 'ver']
);



$router->get(
    '/materiales/solicitudes/exportar-gfpi',
    [MaterialesSolicitudController::class, 'exportarGFPI']
);



// ============================================================
// EDICIÓN Y GESTIÓN DE BORRADORES
// ============================================================

$router->get(
    '/materiales/solicitudes/editar',
    [MaterialesSolicitudController::class, 'editar']
);

$router->post(
    '/materiales/solicitudes/actualizar-borrador',
    [MaterialesSolicitudController::class, 'actualizarBorrador']
);

$router->post(
    '/materiales/solicitudes/eliminar-borrador',
    [MaterialesSolicitudController::class, 'eliminarBorrador']
);


// ============================================================
// ENVÍO DE SOLICITUD A AVAL
// ============================================================

$router->post(
    '/materiales/solicitudes/enviar',
    [MaterialesSolicitudController::class, 'enviar']
);


// ============================================================
// AVAL DE SOLICITUDES DE MATERIALES
// ============================================================

$router->get(
    '/materiales/solicitudes/aval',
    [MaterialesSolicitudController::class, 'aval']
);

$router->post(
    '/materiales/solicitudes/avalar',
    [MaterialesSolicitudController::class, 'avalar']
);

$router->post(
    '/materiales/solicitudes/devolver',
    [MaterialesSolicitudController::class, 'devolver']
);



$router->get(
    '/materiales/solicitudes/consolidar',
    [MaterialesSolicitudController::class, 'consolidar']
);

$router->post(
    '/materiales/solicitudes/exportar-consolidado-gfpi',
    [MaterialesSolicitudController::class, 'exportarConsolidadoGFPI']
);


// ============================================================
// RECEPCIÓN Y DISTRIBUCIÓN DE MATERIALES
// ============================================================
$router->get('/materiales/recepcion-distribucion', [MaterialesRecepcionDistribucionController::class, 'index']);
$router->get('/materiales/recepcion-distribucion/recepcion', [MaterialesRecepcionDistribucionController::class, 'recepcion']);
$router->get('/materiales/recepcion-distribucion/distribucion', [MaterialesRecepcionDistribucionController::class, 'distribucion']);
$router->get('/materiales/recepcion-distribucion/entregas', [MaterialesRecepcionDistribucionController::class, 'entregas']);
$router->get('/materiales/recepcion-distribucion/acta', [MaterialesRecepcionDistribucionController::class, 'descargarActa']);
$router->post('/materiales/recepcion-distribucion/recibir', [MaterialesRecepcionDistribucionController::class, 'recibir']);
$router->post('/materiales/recepcion-distribucion/recibir-multiple', [MaterialesRecepcionDistribucionController::class, 'recibirMultiple']);
$router->post('/materiales/recepcion-distribucion/distribuir', [MaterialesRecepcionDistribucionController::class, 'distribuir']);


// ============================================================
// ASIGNACIONES DE FICHA
// ============================================================

$router->get(
    '/asignaciones-ficha',
    [AsignacionFichaController::class, 'index']
);

$router->post(
    '/asignaciones-ficha/crear',
    [AsignacionFichaController::class, 'crear']
);

$router->post(
    '/asignaciones-ficha/finalizar',
    [AsignacionFichaController::class, 'finalizar']
);


// ============================================================
// PROFESIONALES
// ============================================================

$router->get(
    '/profesionales',
    [ProfesionalController::class, 'index']
);

$router->get(
    '/profesionales/detalle',
    [ProfesionalController::class, 'detalle']
);

$router->get(
    '/profesionales/crear',
    [ProfesionalController::class, 'crear']
);

$router->post(
    '/profesionales/crear',
    [ProfesionalController::class, 'guardar']
);


// ============================================================
// ROLES DE PROFESIONALES
// ============================================================

$router->get(
    '/profesionales/roles',
    [ProfesionalController::class, 'roles']
);

$router->post(
    '/profesionales/roles/guardar',
    [ProfesionalController::class, 'guardarRoles']
);


// ============================================================
// ACCESO DE PROFESIONALES
// ============================================================

$router->get(
    '/profesionales/acceso',
    [ProfesionalController::class, 'crearAcceso']
);

$router->post(
    '/profesionales/acceso',
    [ProfesionalController::class, 'guardarAcceso']
);


// ============================================================
// ROLES Y PERMISOS
// ============================================================

$router->get(
    '/roles',
    [RolesController::class, 'index']
);

$router->get(
    '/roles/crear',
    [RolesController::class, 'crear']
);

$router->post(
    '/roles/guardar',
    [RolesController::class, 'guardar']
);

$router->get(
    '/roles/permisos',
    [RolesController::class, 'permisos']
);

$router->post(
    '/roles/permisos/guardar',
    [RolesController::class, 'guardarPermisos']
);


// ============================================================
// IMPORTACIÓN DE REPORTES SENA
// ============================================================

$router->get(
    '/importar',
    [ImportacionController::class, 'index']
);

$router->post(
    '/importar/leer',
    [ImportacionController::class, 'leer']
);


// Descargar formato individual GIL-F-014
$router->get(
    '/materiales/solicitudes/exportar-individual',
    [MaterialesSolicitudController::class, 'exportarIndividual']
);

// F-14 independiente para documentar la entrega de materiales.
$router->get(
    '/materiales/solicitudes/exportar-entrega',
    [MaterialesSolicitudController::class, 'exportarEntrega']
);

// ============================================================
// CONSULTAS DE BIENESTAR
// ============================================================

$router->get(
    '/bienestar/consultas/prevencion',
    [BienestarConsultaController::class, 'prevencion']
);

$router->get(
    '/bienestar/consultas/caracterizacion',
    [FichaCaracterizacionController::class, 'index']
);

$router->get(
    '/bienestar/consultas/habilidades-blandas',
    [BienestarHabilidadesBlandasController::class, 'index']
);


// ============================================================
// CONSULTA DE ACTIVIDAD FÍSICA
// ============================================================

$router->get(
    '/bienestar/consultas/actividad-fisica',
    [BienestarActividadFisicaController::class, 'index']
);


// ============================================================
// SEGUIMIENTO FÍSICO DE PROMOCIÓN Y PREVENCIÓN
// ============================================================

$router->get(
    '/bienestar/seguimiento-fisico',
    [SeguimientoFisicoController::class, 'index']
);

$router->post(
    '/bienestar/seguimiento-fisico/guardar',
    [SeguimientoFisicoController::class, 'guardar']
);


// ============================================================
// CONSULTA DE HABILIDADES ARTÍSTICAS
// ============================================================

$router->get(
    '/bienestar/consultas/habilidades-artisticas',
    [BienestarHabilidadesArtisticasController::class, 'index']
);


// ============================================================
// REPORTES
// ============================================================

$router->get(
    '/reportes',
    [ReportesController::class, 'index']
);

$router->get(
    '/reportes/exportar',
    [ReportesController::class, 'exportar']
);


// ============================================================
// DESPACHAR ROUTER
// ============================================================

$router->dispatch();