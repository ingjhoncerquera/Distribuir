<?php

declare(strict_types=1);

$usuarioNavbar = Auth::user() ?? [];

$nombreUsuarioNavbar = trim(
    ($usuarioNavbar['nombres'] ?? '')
    . ' '
    . ($usuarioNavbar['apellidos'] ?? '')
);

if ($nombreUsuarioNavbar === '') {
    $nombreUsuarioNavbar =
        $usuarioNavbar['username']
        ?? 'Usuario';
}

$rolesNavbar = Auth::roles();

$rolPrincipalNavbar = 'Usuario';

if (!empty($rolesNavbar)) {

    $primerRolNavbar = $rolesNavbar[0];

    if (is_array($primerRolNavbar)) {

        $rolPrincipalNavbar =
            $primerRolNavbar['nombre']
            ?? 'Usuario';

    } else {

        $rolPrincipalNavbar =
            (string) $primerRolNavbar;
    }
}

$esAdministradorNavbar = false;

foreach ($rolesNavbar as $rolNavbar) {

    if (is_array($rolNavbar)) {

        $nombreRolNavbar =
            $rolNavbar['nombre'] ?? '';

    } else {

        $nombreRolNavbar =
            (string) $rolNavbar;
    }

    if (
        mb_strtolower(trim($nombreRolNavbar))
        === 'administrador'
    ) {

        $esAdministradorNavbar = true;

        break;
    }
}

$rutaActualNavbar = rutaActual();

$menuActivoNavbar = static function (
    string $ruta
) use ($rutaActualNavbar): string {

    return $rutaActualNavbar === $ruta
        ? ' sita-menu-item-active'
        : '';
};


/*
|--------------------------------------------------------------------------
| PERMISOS ACADÉMICOS
|--------------------------------------------------------------------------
*/

$puedeVerAcademicoNavbar = Auth::can(
    'academico.ver'
);

$puedeVerFichasNavbar = Auth::can(
    'fichas.ver'
);

$puedeVerAprendicesNavbar = Auth::can(
    'aprendices.ver'
);

$puedeVerProgramasNavbar = Auth::can(
    'programas.ver'
);

/*
|--------------------------------------------------------------------------
| IMPORTACIÓN DE JUICIOS
|--------------------------------------------------------------------------
| El enlace se muestra al Administrador, Coordinador y Líder de Ficha.
| La autorización real de la ruta debe validarse también en el controlador.
*/
$puedeImportarJuiciosNavbar = $esAdministradorNavbar;

foreach ($rolesNavbar as $rolImportacionNavbar) {

    $nombreRolImportacionNavbar = is_array($rolImportacionNavbar)
        ? (string) ($rolImportacionNavbar['nombre'] ?? '')
        : (string) $rolImportacionNavbar;

    $nombreRolImportacionNavbar = mb_strtolower(
        trim($nombreRolImportacionNavbar)
    );

    $nombreRolImportacionNavbar = strtr(
        $nombreRolImportacionNavbar,
        [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
        ]
    );

    if (in_array(
        $nombreRolImportacionNavbar,
        ['administrador', 'coordinador', 'lider de ficha'],
        true
    )) {
        $puedeImportarJuiciosNavbar = true;
        break;
    }
}


/*
|--------------------------------------------------------------------------
| ACADÉMICO ACTIVO
|--------------------------------------------------------------------------
*/

$rutasAcademicoNavbar = [
    '/programas',
    '/fichas',
    '/aprendices',
    '/asignaciones-ficha',
    '/importar',
    '/competencias',
    '/rap',
    '/resultados',
];

$academicoActivoNavbar = in_array(
    $rutaActualNavbar,
    $rutasAcademicoNavbar,
    true
)
    ? ' sita-menu-item-active'
    : '';



/*
|--------------------------------------------------------------------------
| MATERIALES DE FORMACIÓN
|--------------------------------------------------------------------------
*/

$rutasMaterialesNavbar = [
    '/materiales',
    '/materiales/catalogo',
    '/materiales/catalogo/crear',
    '/materiales/catalogo/editar',
    '/materiales/catalogo/estado',

    '/materiales/importacion',
    '/materiales/importacion/preview',
    '/materiales/importacion/aplicar',

    // Procesos
    '/materiales/procesos',
    '/materiales/procesos/crear',
    '/materiales/procesos/guardar',
    '/materiales/procesos/editar',
    '/materiales/procesos/actualizar',
    '/materiales/procesos/estado',
    '/materiales/procesos/eliminar',

    // Áreas y responsables
    '/materiales/areas',
    '/materiales/areas/crear',
    '/materiales/areas/guardar',
    '/materiales/areas/editar',
    '/materiales/areas/actualizar',
    '/materiales/areas/estado',
    '/materiales/areas/responsable',
    '/materiales/areas/responsable/retirar',

    // Solicitudes
    '/materiales/solicitudes',
    '/materiales/solicitudes/crear',
    '/materiales/solicitudes/areas',
    '/materiales/solicitudes/materiales',
    '/materiales/solicitudes/carrito/agregar',
    '/materiales/solicitudes/carrito/actualizar',
    '/materiales/solicitudes/carrito/quitar',
    '/materiales/solicitudes/carrito/vaciar',
    '/materiales/solicitudes/guardar',
    '/materiales/solicitudes/ver',
    '/materiales/solicitudes/enviar',

    // Aval y consolidación de solicitudes
    '/materiales/solicitudes/aval',
    '/materiales/solicitudes/consolidar',
    '/materiales/solicitudes/exportar-consolidado-gfpi',
];

$materialesActivoNavbar = in_array(
    $rutaActualNavbar,
    $rutasMaterialesNavbar,
    true
)
    ? ' sita-menu-item-active'
    : '';

$puedeVerMaterialesNavbar =
    $esAdministradorNavbar
    || Auth::can('materiales.catalogo.ver');

$puedeImportarMaterialesNavbar =
    $esAdministradorNavbar
    || Auth::can('materiales.catalogo.crear');


/*
|--------------------------------------------------------------------------
| PROCESOS DE MATERIALES
|--------------------------------------------------------------------------
*/

$puedeVerProcesosMaterialesNavbar =
    $esAdministradorNavbar
    || Auth::can('materiales.procesos.ver')
    || Auth::can('materiales.procesos.crear');

$rutasProcesosMaterialesNavbar = [
    '/materiales/procesos',
    '/materiales/procesos/crear',
    '/materiales/procesos/guardar',
    '/materiales/procesos/editar',
    '/materiales/procesos/actualizar',
    '/materiales/procesos/estado',
    '/materiales/procesos/eliminar',
];

$procesosMaterialesActivoNavbar = in_array(
    $rutaActualNavbar,
    $rutasProcesosMaterialesNavbar,
    true
)
    ? ' sita-submenu-item-active'
    : '';



/*
|--------------------------------------------------------------------------
| ÁREAS Y RESPONSABLES
|--------------------------------------------------------------------------
*/

$puedeVerAreasMaterialesNavbar =
    Auth::hasAnyRole([
        'Administrador',
        'Coordinador',
    ]);

// Gestión de recepción/distribución para Administrador y Coordinador.
$puedeGestionarRecepcionDistribucionNavbar = Auth::hasAnyRole([
    'Administrador',
    'Coordinador',
]);

// El instructor líder puede consultar únicamente el apartado Entregas.
$puedeVerEntregasNavbar = $puedeGestionarRecepcionDistribucionNavbar;
if (!$puedeVerEntregasNavbar) {
    foreach ($rolesNavbar as $rolEntregasNavbar) {
        $nombreRolEntregasNavbar = is_array($rolEntregasNavbar)
            ? (string) ($rolEntregasNavbar['nombre'] ?? '')
            : (string) $rolEntregasNavbar;
        $nombreRolEntregasNavbar = mb_strtolower(trim($nombreRolEntregasNavbar));
        $nombreRolEntregasNavbar = strtr($nombreRolEntregasNavbar, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        ]);
        if (in_array($nombreRolEntregasNavbar, [
            'lider de ficha', 'instructor lider', 'instructor lider de ficha',
        ], true)) {
            $puedeVerEntregasNavbar = true;
            break;
        }
    }
}

$rutasAreasMaterialesNavbar = [
    '/materiales/areas',
    '/materiales/areas/crear',
    '/materiales/areas/guardar',
    '/materiales/areas/editar',
    '/materiales/areas/actualizar',
    '/materiales/areas/estado',
    '/materiales/areas/responsable',
    '/materiales/areas/responsable/retirar',
];

$areasMaterialesActivoNavbar = in_array(
    $rutaActualNavbar,
    $rutasAreasMaterialesNavbar,
    true
)
    ? ' sita-submenu-item-active'
    : '';



/*
|--------------------------------------------------------------------------
| SOLICITUDES DE MATERIALES
|--------------------------------------------------------------------------
*/

$puedeVerSolicitudesMaterialesNavbar =
    $esAdministradorNavbar
    || Auth::can('materiales.solicitudes.ver')
    || Auth::can('materiales.solicitudes.crear');

$rutasSolicitudesMaterialesNavbar = [
    '/materiales/solicitudes',
    '/materiales/solicitudes/crear',
    '/materiales/solicitudes/areas',
    '/materiales/solicitudes/materiales',
    '/materiales/solicitudes/carrito/agregar',
    '/materiales/solicitudes/carrito/actualizar',
    '/materiales/solicitudes/carrito/quitar',
    '/materiales/solicitudes/carrito/vaciar',
    '/materiales/solicitudes/guardar',
    '/materiales/solicitudes/ver',
    '/materiales/solicitudes/enviar',
];

$solicitudesMaterialesActivoNavbar = in_array(
    $rutaActualNavbar,
    $rutasSolicitudesMaterialesNavbar,
    true
)
    ? ' sita-submenu-item-active'
    : '';



/*
|--------------------------------------------------------------------------
| AVAL DE SOLICITUDES
|--------------------------------------------------------------------------
*/

$puedeAvalarSolicitudesMaterialesNavbar =
    $esAdministradorNavbar
    || Auth::can('materiales.solicitudes.gestionar');

$rutasAvalSolicitudesMaterialesNavbar = [
    '/materiales/solicitudes/aval',
];

$avalSolicitudesMaterialesActivoNavbar = in_array(
    $rutaActualNavbar,
    $rutasAvalSolicitudesMaterialesNavbar,
    true
)
    ? ' sita-submenu-item-active'
    : '';


/*
|--------------------------------------------------------------------------
| CONSOLIDACIÓN DE MATERIALES
|--------------------------------------------------------------------------
*/

$puedeConsolidarMaterialesNavbar = Auth::hasAnyRole([
    'Administrador',
    'Coordinador',
]);

$rutasConsolidarMaterialesNavbar = [
    '/materiales/solicitudes/consolidar',
    '/materiales/solicitudes/exportar-consolidado-gfpi',
];

$consolidarMaterialesActivoNavbar = in_array(
    $rutaActualNavbar,
    $rutasConsolidarMaterialesNavbar,
    true
)
    ? ' sita-submenu-item-active'
    : '';


/*
|--------------------------------------------------------------------------
| ADMINISTRACIÓN ACTIVO
|--------------------------------------------------------------------------
*/

$rutasAdministracionNavbar = [
    '/profesionales',
    '/usuarios',
    '/roles',
    '/auditoria',
];

$administracionActivoNavbar = in_array(
    $rutaActualNavbar,
    $rutasAdministracionNavbar,
    true
)
    ? ' sita-menu-item-active'
    : '';



/*
|--------------------------------------------------------------------------
| PERMISOS DE ADMINISTRACIÓN
|--------------------------------------------------------------------------
*/

$puedeVerProfesionalesNavbar = Auth::can(
    'profesionales.ver'
);

$puedeVerUsuariosNavbar = Auth::can(
    'usuarios.ver'
);

$puedeVerRolesNavbar = Auth::can(
    'roles.ver'
);

$puedeVerAuditoriaNavbar = Auth::can(
    'auditoria.ver'
);



/*
|--------------------------------------------------------------------------
| CONSULTAS DE BIENESTAR
|--------------------------------------------------------------------------
*/

$puedeVerCaracterizacionNavbar =
    $esAdministradorNavbar
    || Auth::can('bienestar.caracterizacion.ver');

$puedeVerPrevencionNavbar =
    $esAdministradorNavbar
    || Auth::can('bienestar.prevencion.ver');

$puedeVerHabilidadesBlandasNavbar =
    $esAdministradorNavbar
    || Auth::can('bienestar.habilidades_blandas.ver');

$puedeVerActividadFisicaNavbar =
    $esAdministradorNavbar
    || Auth::can('bienestar.actividad_fisica.ver');

$puedeVerHabilidadesArtisticasNavbar =
    $esAdministradorNavbar
    || Auth::can('bienestar.habilidades_artisticas.ver');

// Seguimiento físico de Promoción y Prevención.
// Se muestra únicamente a usuarios con el permiso de consulta,
// además de los administradores.
$puedeVerSeguimientoFisicoNavbar =
    $esAdministradorNavbar
    || Auth::can('bienestar.seguimiento_fisico.ver');

$puedeVerConsultasBienestarNavbar =
    $esAdministradorNavbar
    || $puedeVerCaracterizacionNavbar
    || $puedeVerPrevencionNavbar
    || $puedeVerHabilidadesBlandasNavbar
    || $puedeVerActividadFisicaNavbar
    || $puedeVerHabilidadesArtisticasNavbar
    || $puedeVerSeguimientoFisicoNavbar;

$rutasBienestarNavbar = [
    '/bienestar/consultas',
    '/bienestar/consultas/caracterizacion',
    '/bienestar/consultas/prevencion',
    '/bienestar/consultas/habilidades-blandas',
    '/bienestar/consultas/actividad-fisica',
    '/bienestar/consultas/habilidades-artisticas',
    '/bienestar/seguimiento-fisico',
];

$bienestarActivoNavbar = in_array(
    $rutaActualNavbar,
    $rutasBienestarNavbar,
    true
)
    ? ' sita-menu-item-active'
    : '';

/*
|--------------------------------------------------------------------------
| REPORTES
|--------------------------------------------------------------------------
*/

// Misma regla que ReportesController::autorizar()
$puedeVerReportesNavbar =
    $esAdministradorNavbar
    || Auth::can('reportes.ver')
    || Auth::hasAnyRole([
        'Administrador',
        'Superadministrador',
        'Coordinador',
        'Lider de Ficha',
        'Líder de Ficha',
        'Bienestar',
        'Psicologo',
        'Psicólogo',
        'Enfermero',
        'Nutricionista',
        'Instructor',
        'Trabajador Social',
    ]);

$rutasReportesNavbar = [
    '/reportes',
    '/reportes/exportar',
];

$reportesActivoNavbar = in_array(
    $rutaActualNavbar,
    $rutasReportesNavbar,
    true
)
    ? ' sita-menu-item-active'
    : '';

?>

<style>

.sita-navbar {

    width: 100%;

    height: 76px;
    min-height: 76px;
    max-height: 76px;

    flex: 0 0 76px;

    background: #FFFFFF;

    border-bottom: 1px solid #E2E9ED;

    box-shadow:
        0 3px 14px rgba(11, 38, 61, .06);

    display: flex;
    align-items: center;

    position: relative;

    z-index: 1000;

    box-sizing: border-box;

    font-family:
        Inter,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        Roboto,
        Arial,
        sans-serif;

    overflow: visible;
}


.sita-navbar *,
.sita-navbar *::before,
.sita-navbar *::after {

    box-sizing: border-box;
}


.sita-navbar-container {

    width: 100%;

    max-width: 1600px;

    height: 76px;
    min-height: 76px;
    max-height: 76px;

    margin: 0 auto;

    padding: 0 28px;

    display: flex;

    align-items: center;

    gap: 20px;

    overflow: visible;
}


.sita-navbar-brand {

    flex: 0 0 auto;

    width: 190px;
    min-width: 190px;
    max-width: 190px;

    height: 76px;

    display: flex;

    align-items: center;

    text-decoration: none !important;

    color: #0B263D;

    white-space: nowrap;

    outline: none;
}


.sita-navbar-brand:hover,
.sita-navbar-brand:focus,
.sita-navbar-brand:active {

    text-decoration: none !important;

    color: #0B263D;
}


.sita-brand-icon {

    width: 42px;
    height: 42px;

    min-width: 42px;
    min-height: 42px;

    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            #19A7A8,
            #13898A
        );

    color: #FFFFFF;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 18px;

    font-weight: 800;

    margin-right: 11px;

    box-shadow:
        0 5px 13px rgba(25, 167, 168, .20);
}


.sita-brand-text {

    display: flex;

    flex-direction: column;

    justify-content: center;

    line-height: 1;

    min-width: 0;
}


.sita-brand-name {

    color: #0B263D;

    font-size: 17px;

    font-weight: 800;

    letter-spacing: -.2px;

    line-height: 1.15;

    white-space: nowrap;
}


.sita-brand-description {

    color: #71808B;

    font-size: 10px;

    font-weight: 600;

    letter-spacing: .2px;

    line-height: 1.2;

    margin-top: 4px;

    white-space: nowrap;
}


.sita-navbar .sita-menu {

    flex: 1 1 auto;

    height: 76px;
    min-height: 76px;
    max-height: 76px;

    display: flex;

    align-items: center;

    gap: 3px;

    margin: 0;

    padding: 0;

    list-style: none;

    min-width: 0;

    overflow: visible;
}


.sita-navbar .sita-menu-item {

    flex: 0 0 auto;

    height: 42px;
    min-height: 42px;
    max-height: 42px;

    padding: 0 11px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    border-radius: 10px;

    border: 1px solid transparent;

    background: transparent;

    color: #52626E;

    font-family:
        Inter,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        Roboto,
        Arial,
        sans-serif;

    font-size: 13px;

    font-weight: 600;

    line-height: 42px;

    text-decoration: none !important;

    white-space: nowrap;

    cursor: pointer;

    transition:
        background-color .15s ease,
        color .15s ease,
        border-color .15s ease;

    outline: none;
}


.sita-navbar .sita-menu-item:hover {

    background: #F2F6F8;

    color: #123B5D;

    text-decoration: none !important;
}


.sita-navbar .sita-menu-item:focus {

    text-decoration: none !important;

    outline: none;
}


.sita-navbar .sita-menu-item-active {

    background: #EAF7F7;

    color: #13898A;

    border-color:
        rgba(25, 167, 168, .12);

    font-weight: 700;

    text-decoration: none !important;
}


.sita-navbar .sita-menu-item-active:hover {

    background: #EAF7F7;

    color: #13898A;
}


.sita-menu-icon {

    width: 18px;
    min-width: 18px;

    height: 18px;
    min-height: 18px;

    display: inline-flex;

    align-items: center;
    justify-content: center;

    font-size: 15px;

    line-height: 1;
}


.sita-menu-dropdown {

    position: relative;

    display: flex;

    align-items: center;

    height: 76px;
}


.sita-menu-dropdown > .sita-menu-item {

    position: relative;
}


.sita-menu-arrow {

    font-size: 10px;

    margin-left: 1px;

    transition:
        transform .15s ease;
}


.sita-submenu {

    position: absolute;

    top: 68px;

    left: 0;

    width: 300px;

    padding: 8px;

    background: #FFFFFF;

    border: 1px solid #E2E9ED;

    border-radius: 12px;

    box-shadow:
        0 12px 30px rgba(11, 38, 61, .12);

    display: none;

    z-index: 2000;
}


.sita-menu-dropdown:hover .sita-submenu {

    display: block;
}


.sita-menu-dropdown:hover .sita-menu-arrow {

    transform: rotate(180deg);
}


.sita-submenu-item {

    display: flex;

    align-items: center;

    gap: 10px;

    width: 100%;

    min-height: 40px;

    padding: 9px 11px;

    border-radius: 8px;

    color: #52626E;

    font-size: 13px;

    font-weight: 600;

    line-height: 1.2;

    text-decoration: none !important;

    white-space: nowrap;

    transition:
        background-color .15s ease,
        color .15s ease;
}


.sita-submenu-item:hover {

    background: #F2F6F8;

    color: #123B5D;

    text-decoration: none !important;
}


.sita-submenu-item-active {

    background: #EAF7F7;

    color: #13898A;

    font-weight: 700;
}


.sita-submenu-item-active:hover {

    background: #EAF7F7;

    color: #13898A;
}


.sita-submenu-icon {

    width: 20px;
    min-width: 20px;

    display: inline-flex;

    align-items: center;
    justify-content: center;

    font-size: 15px;
}


.sita-submenu-item-disabled {

    color: #9AA6AE;

    cursor: default;

    opacity: .70;
}


.sita-submenu-item-disabled:hover {

    background: transparent;

    color: #9AA6AE;
}


.sita-navbar .sita-menu-item-disabled {

    color: #9AA6AE;

    cursor: default;

    opacity: .72;
}


.sita-navbar .sita-menu-item-disabled:hover {

    background: transparent;

    color: #9AA6AE;

    border-color: transparent;
}


.sita-navbar-user {

    flex: 0 0 auto;

    width: 205px;
    min-width: 205px;
    max-width: 205px;

    height: 76px;

    display: flex;

    align-items: center;

    gap: 10px;

    padding-left: 16px;

    border-left: 1px solid #E2E9ED;

    white-space: nowrap;
}


.sita-topbar-avatar {

    width: 38px;
    height: 38px;

    min-width: 38px;
    min-height: 38px;

    border-radius: 50%;

    background: #123B5D;

    color: #FFFFFF;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 13px;

    font-weight: 800;

    overflow: hidden;
}


.sita-navbar-user-info {

    display: flex;

    flex-direction: column;

    justify-content: center;

    min-width: 0;

    line-height: 1.15;

    flex: 1 1 auto;
}


.sita-navbar-user-name {

    max-width: 145px;

    color: #263746;

    font-size: 12px;

    font-weight: 700;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}


.sita-navbar-user-role {

    color: #71808B;

    font-size: 10px;

    font-weight: 600;

    margin-top: 4px;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}


.sita-logout {

    flex: 0 0 auto;

    width: 36px;
    height: 36px;

    min-width: 36px;
    min-height: 36px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 9px;

    color: #71808B;

    text-decoration: none !important;

    font-size: 16px;

    transition:
        background-color .15s ease,
        color .15s ease;
}


.sita-logout:hover {

    background: #FDEEEE;

    color: #B63B3B;

    text-decoration: none !important;
}


.sita-navbar a,
.sita-navbar a:hover,
.sita-navbar a:focus,
.sita-navbar a:active,
.sita-navbar a:visited {

    text-decoration: none !important;
}


.sita-navbar .sita-menu-item {

    margin-top: 0 !important;
    margin-bottom: 0 !important;

    transform: none !important;
}


.sita-navbar .sita-menu {

    margin-top: 0 !important;
    margin-bottom: 0 !important;
}


.sita-navbar-container {

    margin-top: 0 !important;
    margin-bottom: 0 !important;
}


@media (min-width: 1400px) {

    .sita-navbar-container {

        padding-left: 32px;
        padding-right: 32px;
    }

}


@media (max-width: 1250px) {

    .sita-navbar-container {

        padding-left: 18px;
        padding-right: 18px;

        gap: 12px;
    }

    .sita-navbar-brand {

        width: 165px;
        min-width: 165px;
        max-width: 165px;
    }

    .sita-brand-description {

        display: none;
    }

    .sita-brand-name {

        font-size: 16px;
    }

    .sita-navbar .sita-menu-item {

        padding-left: 8px;
        padding-right: 8px;

        font-size: 12px;
    }

    .sita-menu-icon {

        display: none;
    }

    .sita-navbar-user {

        width: 120px;
        min-width: 120px;
        max-width: 120px;

        padding-left: 10px;
    }

    .sita-navbar-user-info {

        display: none;
    }

    .sita-submenu {

        width: 285px;
    }
}


/* ==========================================================
   MENÚ RESPONSIVE (HAMBURGUESA)
   ----------------------------------------------------------
   - Escritorio (> 1024px): menú horizontal con submenús al
     pasar el cursor (sin cambios).
   - Tabletas y móviles (<= 1024px): el menú se recoge en un
     botón hamburguesa y se despliega como panel; los submenús
     funcionan como acordeón.
   - Pantallas táctiles grandes: el submenú se abre al tocar.
========================================================== */

.sita-nav-toggle {

    display: none;

    flex: 0 0 auto;

    width: 44px;
    height: 44px;
    min-width: 44px;

    padding: 0;

    flex-direction: column;
    align-items: center;
    justify-content: center;

    gap: 5px;

    border: 1px solid #E2E9ED;
    border-radius: 10px;

    background: #FFFFFF;

    cursor: pointer;

    -webkit-appearance: none;
    appearance: none;

    transition:
        background-color .15s ease,
        border-color .15s ease;
}

.sita-nav-toggle:hover {

    background: #F2F6F8;
}

.sita-nav-toggle:focus-visible,
.sita-navbar .sita-menu-item:focus-visible {

    outline: 2px solid #19A7A8;
    outline-offset: 2px;
}

.sita-nav-toggle-bar {

    display: block;

    width: 20px;
    height: 2px;

    border-radius: 2px;

    background: #123B5D;

    transition:
        transform .2s ease,
        opacity .2s ease;
}

.sita-navbar.sita-nav-open .sita-nav-toggle-bar:nth-child(1) {

    transform: translateY(7px) rotate(45deg);
}

.sita-navbar.sita-nav-open .sita-nav-toggle-bar:nth-child(2) {

    opacity: 0;
}

.sita-navbar.sita-nav-open .sita-nav-toggle-bar:nth-child(3) {

    transform: translateY(-7px) rotate(-45deg);
}

/* Submenú abierto por clic/toque (dispositivos táctiles) */
.sita-menu-dropdown.sita-dropdown-open .sita-submenu {

    display: block;
}

.sita-menu-dropdown.sita-dropdown-open .sita-menu-arrow {

    transform: rotate(180deg);
}

.sita-menu-mobile-footer,
.sita-nav-backdrop {

    display: none;
}


@media (max-width: 1024px) {

    .sita-navbar-container {

        padding-left: 14px;
        padding-right: 14px;

        gap: 10px;
    }

    .sita-navbar-brand {

        order: 1;

        width: auto;
        min-width: 0;
        max-width: none;
    }

    .sita-brand-description {

        display: none;
    }

    .sita-navbar-user {

        order: 2;

        margin-left: auto;

        width: auto;
        min-width: 0;
        max-width: none;

        padding-left: 0;

        border-left: 0;
    }

    .sita-navbar-user-info,
    .sita-logout {

        display: none;
    }

    .sita-nav-toggle {

        order: 3;

        display: inline-flex;
    }

    /* Panel desplegable */
    .sita-navbar .sita-menu {

        display: none;

        position: fixed;

        top: 76px;
        left: 0;
        right: 0;

        order: 4;

        height: auto;
        min-height: 0;

        max-height: calc(100vh - 76px);
        max-height: calc(100dvh - 76px);

        flex-direction: column;
        align-items: stretch;

        gap: 2px;

        padding: 10px 12px 16px;

        background: #FFFFFF;

        border-bottom: 1px solid #E2E9ED;

        box-shadow:
            0 18px 30px rgba(11, 38, 61, .14);

        overflow-x: hidden;
        overflow-y: auto;

        overscroll-behavior: contain;

        -webkit-overflow-scrolling: touch;

        z-index: 1100;
    }

    .sita-navbar.sita-nav-open .sita-menu {

        display: flex;
    }

    .sita-navbar .sita-menu-item {

        width: 100%;

        height: auto;
        min-height: 46px;
        max-height: none;

        padding: 0 14px;

        justify-content: flex-start;

        font-size: 14px;

        line-height: 1.2;
    }

    .sita-menu-icon {

        display: inline-flex;
    }

    .sita-menu-arrow {

        margin-left: auto;

        font-size: 12px;
    }

    /* Submenús como acordeón */
    .sita-menu-dropdown {

        display: block;

        width: 100%;

        height: auto;
    }

    .sita-navbar .sita-menu-dropdown:hover .sita-submenu {

        display: none;
    }

    .sita-navbar .sita-menu-dropdown.sita-dropdown-open .sita-submenu {

        display: block;
    }

    .sita-navbar .sita-menu-dropdown:hover .sita-menu-arrow {

        transform: none;
    }

    .sita-navbar .sita-menu-dropdown.sita-dropdown-open .sita-menu-arrow {

        transform: rotate(180deg);
    }

    .sita-submenu {

        position: static;

        width: auto;

        margin: 4px 0 8px 20px;

        padding: 2px 0 2px 8px;

        background: transparent;

        border: 0;
        border-left: 2px solid #E2E9ED;
        border-radius: 0;

        box-shadow: none;
    }

    .sita-submenu-item {

        min-height: 44px;

        font-size: 14px;

        white-space: normal;
    }

    /* Usuario y cierre de sesión dentro del panel */
    .sita-menu-mobile-footer {

        display: block;

        margin-top: 8px;

        padding-top: 10px;

        border-top: 1px solid #E2E9ED;
    }

    .sita-mobile-user {

        display: flex;

        flex-direction: column;

        padding: 4px 14px 10px;

        line-height: 1.25;
    }

    .sita-mobile-user-name {

        color: #263746;

        font-size: 13px;

        font-weight: 700;
    }

    .sita-mobile-user-role {

        color: #71808B;

        font-size: 11px;

        font-weight: 600;

        margin-top: 2px;
    }

    .sita-navbar .sita-mobile-logout {

        color: #B63B3B;
    }

    .sita-navbar .sita-mobile-logout:hover {

        background: #FDEEEE;

        color: #B63B3B;
    }

    /* Fondo oscuro detrás del panel */
    .sita-nav-backdrop {

        position: fixed;

        top: 76px;
        right: 0;
        bottom: 0;
        left: 0;

        background: rgba(7, 25, 39, .45);

        z-index: 900;
    }

    .sita-nav-backdrop.sita-nav-backdrop-visible {

        display: block;
    }
}


@media (max-width: 480px) {

    .sita-navbar-container {

        padding-left: 10px;
        padding-right: 10px;
    }

    .sita-brand-name {

        font-size: 15px;
    }

    .sita-brand-icon {

        width: 38px;
        height: 38px;

        min-width: 38px;
        min-height: 38px;

        margin-right: 9px;
    }

    .sita-navbar .sita-menu {

        padding-left: 8px;
        padding-right: 8px;
    }
}



/* ==========================================================
           MODAL AYUDA SITA
        ========================================================== */

        .sita-navbar .sita-ayuda-menu-trigger {
            appearance: none;
            -webkit-appearance: none;
            border: 0;
            background: transparent;
            font-family: inherit;
            cursor: pointer;
        }

        .sita-ayuda-overlay {
            position: fixed;
            inset: 0;
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: rgba(7, 25, 39, .62);
            backdrop-filter: blur(3px);
        }

        .sita-ayuda-overlay.sita-ayuda-visible {
            display: flex;
        }

        .sita-ayuda-modal {
            width: min(100%, 620px);
            max-height: min(88vh, 760px);
            overflow-y: auto;
            background: #fff;
            color: var(--sita-text, #263746);
            border: 1px solid var(--sita-border, #E2E9ED);
            border-radius: 16px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, .25);
            animation: sitaAyudaEntrada .18s ease-out;
        }

        @keyframes sitaAyudaEntrada {
            from { opacity: 0; transform: translateY(8px) scale(.99); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .sita-ayuda-cabecera {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            padding: 20px 22px 16px;
            border-bottom: 1px solid var(--sita-border, #E2E9ED);
        }

        .sita-ayuda-titulo {
            margin: 0;
            color: var(--sita-navy, #0B263D);
            font-size: 20px;
            font-weight: 800;
            line-height: 1.25;
        }

        .sita-ayuda-subtitulo {
            margin: 5px 0 0;
            color: var(--sita-muted, #71808B);
            font-size: 12px;
            line-height: 1.5;
        }

        .sita-ayuda-cerrar {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border: 1px solid var(--sita-border, #E2E9ED);
            border-radius: 9px;
            background: #fff;
            color: #536573;
            font-size: 21px;
            line-height: 1;
            cursor: pointer;
        }

        .sita-ayuda-cerrar:hover {
            background: #F2F6F8;
            color: #B42318;
        }

        .sita-ayuda-contenido {
            padding: 18px 22px 20px;
        }

        .sita-ayuda-seccion-titulo {
            margin: 0 0 7px;
            color: var(--sita-navy, #0B263D);
            font-size: 14px;
            font-weight: 800;
        }

        .sita-ayuda-descripcion {
            margin: 0 0 16px;
            color: var(--sita-muted, #71808B);
            font-size: 12px;
            line-height: 1.6;
        }

        .sita-ayuda-integrantes {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 9px;
        }

        .sita-ayuda-integrante {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            padding: 11px;
            border: 1px solid var(--sita-border, #E2E9ED);
            border-radius: 10px;
            background: #FBFCFD;
        }

        .sita-ayuda-avatar {
            display: flex;
            flex: 0 0 36px;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--sita-turquoise-light, #EAF7F7);
            color: var(--sita-turquoise-dark, #13898A);
            font-size: 11px;
            font-weight: 800;
        }

        .sita-ayuda-persona { min-width: 0; }

        .sita-ayuda-nombre {
            margin: 0;
            color: var(--sita-text, #263746);
            font-size: 11px;
            font-weight: 800;
            line-height: 1.4;
        }

        .sita-ayuda-cargo {
            margin: 3px 0 0;
            color: var(--sita-muted, #71808B);
            font-size: 10px;
            line-height: 1.4;
        }

        .sita-ayuda-manual {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-top: 15px;
            padding: 10px 13px;
            border: 1px solid var(--sita-turquoise, #19A7A8);
            border-radius: 9px;
            background: var(--sita-turquoise-light, #EAF7F7);
            color: var(--sita-turquoise-dark, #13898A);
            font-size: 11px;
            font-weight: 800;
            text-decoration: none;
        }

        .sita-ayuda-manual:hover {
            background: var(--sita-turquoise, #19A7A8);
            color: #fff;
        }

        .sita-ayuda-agradecimiento {
            margin-top: 16px;
            padding: 11px 14px;
            border-radius: 9px;
            background: var(--sita-turquoise, #19A7A8);
            color: #fff;
            text-align: center;
            font-size: 11px;
            line-height: 1.5;
        }

        .sita-ayuda-agradecimiento-titulo {
            margin: 0 0 4px;
            color: #fff;
            font-size: 12px;
            font-weight: 800;
        }

        .sita-ayuda-agradecimiento p {
            margin: 0;
            color: #fff;
            font-size: 11px;
            line-height: 1.5;
        }

        .sita-ayuda-agradecimiento-cierre {
            margin-top: 5px;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
        }

        .sita-ayuda-pie {
            padding: 11px 22px;
            border-top: 1px solid var(--sita-border, #E2E9ED);
            color: var(--sita-muted, #71808B);
            font-size: 10px;
            text-align: center;
        }

        @media (max-width: 520px) {
            .sita-ayuda-overlay { padding: 10px; }
            .sita-ayuda-cabecera { padding: 16px 15px 13px; }
            .sita-ayuda-contenido { padding: 15px; }
            .sita-ayuda-integrantes { grid-template-columns: 1fr; }
            .sita-ayuda-titulo { font-size: 18px; }
            .sita-ayuda-pie { padding: 10px 15px; }
        }

    </style>

<nav class="sita-navbar">

<div class="sita-navbar-container">

    <!-- =================================================
         MARCA
         ================================================= -->

    <a
        href="<?= e(url('/dashboard')) ?>"
        class="sita-navbar-brand"
    >

        <span class="sita-brand-icon">
            S
        </span>

        <span class="sita-brand-text">

            <span class="sita-brand-name">
                SITA
            </span>

            <span class="sita-brand-description">
                Sistema de Trazabilidad
            </span>

        </span>

    </a>


    <!-- =================================================
         MENÚ
         ================================================= -->

    <!-- =================================================
         BOTÓN HAMBURGUESA (solo tabletas y móviles)
         ================================================= -->

    <button
        type="button"
        class="sita-nav-toggle"
        id="sitaNavToggle"
        aria-label="Abrir menú"
        aria-expanded="false"
        aria-controls="sitaMenu"
    >
        <span class="sita-nav-toggle-bar"></span>
        <span class="sita-nav-toggle-bar"></span>
        <span class="sita-nav-toggle-bar"></span>
    </button>


    <div class="sita-menu" id="sitaMenu">


        <!-- INICIO -->

        <a
            href="<?= e(url('/dashboard')) ?>"
            class="sita-menu-item<?= $menuActivoNavbar('/dashboard') ?>"
        >

            <span class="sita-menu-icon">
                🏠
            </span>

            <span>
                Inicio
            </span>

        </a>


        <!-- =================================================
             ACADÉMICO
             ================================================= -->

        <?php if ($puedeVerAcademicoNavbar): ?>

            <div class="sita-menu-dropdown">

                <a
                    href="<?= e(url('/fichas')) ?>"
                    class="sita-menu-item<?= $academicoActivoNavbar ?>"
                    aria-haspopup="true"
                >

                    <span class="sita-menu-icon">
                        🎓
                    </span>

                    <span>
                        Académico
                    </span>

                    <span class="sita-menu-arrow">
                        ▾
                    </span>

                </a>


                <div class="sita-submenu">


                    <?php if ($puedeVerProgramasNavbar): ?>

                        <a
                            href="<?= e(url('/programas')) ?>"
                            class="sita-submenu-item<?= $menuActivoNavbar('/programas') !== '' ? ' sita-submenu-item-active' : '' ?>"
                        >

                            <span class="sita-submenu-icon">
                                🎓
                            </span>

                            <span>
                                Programas
                            </span>

                        </a>

                    <?php endif; ?>


                    <?php if ($puedeVerFichasNavbar): ?>

                        <a
                            href="<?= e(url('/fichas')) ?>"
                            class="sita-submenu-item<?= $menuActivoNavbar('/fichas') !== '' ? ' sita-submenu-item-active' : '' ?>"
                        >

                            <span class="sita-submenu-icon">
                                📋
                            </span>

                            <span>
                                Fichas
                            </span>

                        </a>

                    <?php endif; ?>


                    <?php if ($esAdministradorNavbar): ?>

                        <a
                            href="<?= e(url('/asignaciones-ficha')) ?>"
                            class="sita-submenu-item<?= $menuActivoNavbar('/asignaciones-ficha') !== '' ? ' sita-submenu-item-active' : '' ?>"
                        >

                            <span class="sita-submenu-icon">
                                👥
                            </span>

                            <span>
                                Asignaciones de ficha
                            </span>

                        </a>

                    <?php endif; ?>


                    <?php if ($puedeVerAprendicesNavbar): ?>

                        <a
                            href="<?= e(url('/aprendices')) ?>"
                            class="sita-submenu-item<?= $menuActivoNavbar('/aprendices') !== '' ? ' sita-submenu-item-active' : '' ?>"
                        >

                            <span class="sita-submenu-icon">
                                👥
                            </span>

                            <span>
                                Aprendices
                            </span>

                        </a>

                    <?php endif; ?>


                    <?php if ($puedeImportarJuiciosNavbar): ?>

                        <a
                            href="<?= e(url('/importar')) ?>"
                            class="sita-submenu-item<?= $menuActivoNavbar('/importar') !== '' ? ' sita-submenu-item-active' : '' ?>"
                        >

                            <span class="sita-submenu-icon">
                                📥
                            </span>

                            <span>
                                Importar juicios
                            </span>

                        </a>

                    <?php endif; ?>


                    <a
                        href="javascript:void(0)"
                        class="sita-submenu-item sita-submenu-item-disabled"
                        aria-disabled="true"
                        onclick="return false;"
                    >

                        <span class="sita-submenu-icon">
                            📚
                        </span>

                        <span>
                            Competencias
                        </span>

                    </a>


                    <a
                        href="javascript:void(0)"
                        class="sita-submenu-item sita-submenu-item-disabled"
                        aria-disabled="true"
                        onclick="return false;"
                    >

                        <span class="sita-submenu-icon">
                            📝
                        </span>

                        <span>
                            RAP
                        </span>

                    </a>


                    <a
                        href="javascript:void(0)"
                        class="sita-submenu-item sita-submenu-item-disabled"
                        aria-disabled="true"
                        onclick="return false;"
                    >

                        <span class="sita-submenu-icon">
                            📊
                        </span>

                        <span>
                            Resultados
                        </span>

                    </a>


                </div>

            </div>

        <?php endif; ?>


        <!-- =================================================
             BIENESTAR
             ================================================= -->

        <?php if ($puedeVerConsultasBienestarNavbar): ?>

            <div class="sita-menu-dropdown">

                <a
                    href="<?= e(url('/bienestar/consultas')) ?>"
                    class="sita-menu-item<?= $bienestarActivoNavbar ?>"
                    aria-haspopup="true"
                >

                    <span class="sita-menu-icon">
                        🤝
                    </span>

                    <span>
                        Bienestar
                    </span>

                    <span class="sita-menu-arrow">
                        ▾
                    </span>

                </a>

                <div class="sita-submenu">

                    <?php if ($puedeVerCaracterizacionNavbar): ?>

                        <a
                            href="<?= e(url('/bienestar/consultas/caracterizacion')) ?>"
                            class="sita-submenu-item<?= $menuActivoNavbar('/bienestar/consultas/caracterizacion') !== '' ? ' sita-submenu-item-active' : '' ?>"
                        >

                            <span class="sita-submenu-icon">
                                👤
                            </span>

                            <span>
                                Caracterización socioeconómica
                            </span>

                        </a>

                    <?php endif; ?>


                    <?php if ($puedeVerPrevencionNavbar): ?>

                        <a
                            href="<?= e(url('/bienestar/consultas/prevencion')) ?>"
                            class="sita-submenu-item<?= $menuActivoNavbar('/bienestar/consultas/prevencion') !== '' ? ' sita-submenu-item-active' : '' ?>"
                        >

                            <span class="sita-submenu-icon">
                                🛡️
                            </span>

                            <span>
                                Promoción y prevención
                            </span>

                        </a>

                    <?php endif; ?>


                    <?php if ($puedeVerHabilidadesBlandasNavbar): ?>

                        <a
                            href="<?= e(url('/bienestar/consultas/habilidades-blandas')) ?>"
                            class="sita-submenu-item<?= $menuActivoNavbar('/bienestar/consultas/habilidades-blandas') !== '' ? ' sita-submenu-item-active' : '' ?>"
                        >

                            <span class="sita-submenu-icon">
                                🤝
                            </span>

                            <span>
                                Habilidades blandas
                            </span>

                        </a>

                    <?php endif; ?>


                    <?php if ($puedeVerActividadFisicaNavbar): ?>

                        <a
                            href="<?= e(url('/bienestar/consultas/actividad-fisica')) ?>"
                            class="sita-submenu-item<?= $menuActivoNavbar('/bienestar/consultas/actividad-fisica') !== '' ? ' sita-submenu-item-active' : '' ?>"
                        >

                            <span class="sita-submenu-icon">
                                🏃
                            </span>

                            <span>
                                Actividad física
                            </span>

                        </a>

                    <?php endif; ?>


                    <?php if ($puedeVerHabilidadesArtisticasNavbar): ?>

                        <a
                            href="<?= e(url('/bienestar/consultas/habilidades-artisticas')) ?>"
                            class="sita-submenu-item<?= $menuActivoNavbar('/bienestar/consultas/habilidades-artisticas') !== '' ? ' sita-submenu-item-active' : '' ?>"
                        >

                            <span class="sita-submenu-icon">
                                🎨
                            </span>

                            <span>
                                Habilidades artísticas
                            </span>

                        </a>

                    <?php endif; ?>

                    <?php if ($puedeVerSeguimientoFisicoNavbar): ?>

                        <a
                            href="<?= e(url('/bienestar/seguimiento-fisico')) ?>"
                            class="sita-submenu-item<?= $menuActivoNavbar('/bienestar/seguimiento-fisico') !== '' ? ' sita-submenu-item-active' : '' ?>"
                        >

                            <span class="sita-submenu-icon">
                                ❤️
                            </span>

                            <span>
                                Seguimiento físico PyP
                            </span>

                        </a>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>


        <!-- REPORTES -->

        <?php if ($puedeVerReportesNavbar): ?>

            <a
                href="<?= e(url('/reportes')) ?>"
                class="sita-menu-item<?= $reportesActivoNavbar ?>"
            >

                <span class="sita-menu-icon">
                    📊
                </span>

                <span>
                    Reportes
                </span>

            </a>

        <?php endif; ?>


        <!-- =================================================
             AYUDA
             ================================================= -->

        <button
            type="button"
            class="sita-menu-item sita-ayuda-menu-trigger"
            id="sitaAbrirAyuda"
            aria-haspopup="dialog"
            aria-controls="sitaModalAyuda"
        >
            <span class="sita-menu-icon" aria-hidden="true">❔</span>
            <span>Ayuda</span>
        </button>


        <!-- =================================================
             MATERIALES DE FORMACIÓN
             ================================================= -->

        <?php if ($puedeVerMaterialesNavbar): ?>

            <div class="sita-menu-dropdown">

                <a
                    href="<?= e(url('/materiales')) ?>"
                    class="sita-menu-item<?= $materialesActivoNavbar ?>"
                    aria-haspopup="true"
                >

                    <span class="sita-menu-icon">
                        📦
                    </span>

                    <span>
                        Materiales
                    </span>

                    <span class="sita-menu-arrow">
                        ▾
                    </span>

                </a>


                <div class="sita-submenu">


                    <a
                        href="<?= e(url('/materiales')) ?>"
                        class="sita-submenu-item<?= in_array(
                            $rutaActualNavbar,
                            [
                                '/materiales',
                                '/materiales/catalogo',
                                '/materiales/catalogo/crear',
                                '/materiales/catalogo/editar',
                                '/materiales/catalogo/estado'
                            ],
                            true
                        ) ? ' sita-submenu-item-active' : '' ?>"
                    >

                        <span class="sita-submenu-icon">
                            📦
                        </span>

                        <span>
                            Catálogo
                        </span>

                    </a>


                    <?php if ($puedeImportarMaterialesNavbar): ?>

                        <a
                            href="<?= e(url('/materiales/importacion')) ?>"
                            class="sita-submenu-item<?= in_array(
                                $rutaActualNavbar,
                                [
                                    '/materiales/importacion',
                                    '/materiales/importacion/preview',
                                    '/materiales/importacion/aplicar'
                                ],
                                true
                            ) ? ' sita-submenu-item-active' : '' ?>"
                        >

                            <span class="sita-submenu-icon">
                                📥
                            </span>

                            <span>
                                Importar catálogo
                            </span>

                        </a>

                    <?php endif; ?>


                    <?php if ($puedeVerProcesosMaterialesNavbar): ?>

                        <a
                            href="<?= e(url('/materiales/procesos')) ?>"
                            class="sita-submenu-item<?= $procesosMaterialesActivoNavbar ?>"
                        >

                            <span class="sita-submenu-icon">
                                📅
                            </span>

                            <span>
                                Procesos
                            </span>

                        </a>

                    <?php endif; ?>


                    <?php if ($puedeVerAreasMaterialesNavbar): ?>

                        <a
                            href="<?= e(url('/materiales/areas')) ?>"
                            class="sita-submenu-item<?= $areasMaterialesActivoNavbar ?>"
                        >

                            <span class="sita-submenu-icon">
                                👥
                            </span>

                            <span>
                                Áreas y responsables
                            </span>

                        </a>

                    <?php endif; ?>


                    <?php if ($puedeVerSolicitudesMaterialesNavbar): ?>

                        <a
                            href="<?= e(url('/materiales/solicitudes')) ?>"
                            class="sita-submenu-item<?= $solicitudesMaterialesActivoNavbar ?>"
                        >

                            <span class="sita-submenu-icon">
                                📝
                            </span>

                            <span>
                                Solicitudes
                            </span>

                        </a>

                    <?php endif; ?>


                    <?php if ($puedeAvalarSolicitudesMaterialesNavbar): ?>

                        <a
                            href="<?= e(url('/materiales/solicitudes/aval')) ?>"
                            class="sita-submenu-item<?= $avalSolicitudesMaterialesActivoNavbar ?>"
                        >

                            <span class="sita-submenu-icon">
                                ✅
                            </span>

                            <span>
                                Aval de solicitudes
                            </span>

                        </a>

                    <?php else: ?>

                        <a
                            href="javascript:void(0)"
                            class="sita-submenu-item sita-submenu-item-disabled"
                            aria-disabled="true"
                            onclick="return false;"
                        >

                            <span class="sita-submenu-icon">
                                ✅
                            </span>

                            <span>
                                Aval de solicitudes
                            </span>

                        </a>

                    <?php endif; ?>


                    <a
                        href="javascript:void(0)"
                        class="sita-submenu-item sita-submenu-item-disabled"
                        aria-disabled="true"
                        onclick="return false;"
                    >

                        <span class="sita-submenu-icon">
                            🛒
                        </span>

                        <span>
                            Compras
                        </span>

                    </a>


                    <?php if ($puedeGestionarRecepcionDistribucionNavbar): ?>

                        <a
                            href="<?= e(url('/materiales/recepcion-distribucion/recepcion')) ?>"
                            class="sita-submenu-item<?= $menuActivoNavbar('/materiales/recepcion-distribucion/recepcion') !== '' ? ' sita-submenu-item-active' : '' ?>"
                        >
                            <span class="sita-submenu-icon" aria-hidden="true">📥</span>
                            <span>Recepción</span>
                        </a>

                        <a
                            href="<?= e(url('/materiales/recepcion-distribucion/distribucion')) ?>"
                            class="sita-submenu-item<?= $menuActivoNavbar('/materiales/recepcion-distribucion/distribucion') !== '' ? ' sita-submenu-item-active' : '' ?>"
                        >
                            <span class="sita-submenu-icon" aria-hidden="true">📤</span>
                            <span>Distribución</span>
                        </a>

                    <?php endif; ?>


                    <?php if ($puedeVerEntregasNavbar): ?>
                        <a
                            href="<?= e(url('/materiales/recepcion-distribucion/entregas')) ?>"
                            class="sita-submenu-item<?= $menuActivoNavbar('/materiales/recepcion-distribucion/entregas') !== '' ? ' sita-submenu-item-active' : '' ?>"
                        >
                            <span class="sita-submenu-icon" aria-hidden="true">🤝</span>
                            <span>Entregas</span>
                        </a>
                    <?php endif; ?>


                    <a
                        href="javascript:void(0)"
                        class="sita-submenu-item sita-submenu-item-disabled"
                        aria-disabled="true"
                        onclick="return false;"
                    >

                        <span class="sita-submenu-icon">
                            📊
                        </span>

                        <span>
                            Reportes
                        </span>

                    </a>


                    <?php if ($puedeConsolidarMaterialesNavbar): ?>

                        <a
                            href="<?= e(url('/materiales/solicitudes/consolidar')) ?>"
                            class="sita-submenu-item<?= $consolidarMaterialesActivoNavbar ?>"
                        >

                            <span class="sita-submenu-icon" aria-hidden="true">
                                📑
                            </span>

                            <span>
                                Consolidar materiales
                            </span>

                        </a>

                    <?php endif; ?>




                </div>

            </div>

        <?php endif; ?>


        <!-- =================================================
             ADMINISTRACIÓN
             ================================================= -->

        <?php if ($esAdministradorNavbar): ?>

            <div class="sita-menu-dropdown">

                <a
                    href="javascript:void(0)"
                    class="sita-menu-item<?= $administracionActivoNavbar ?>"
                    aria-haspopup="true"
                >

                    <span class="sita-menu-icon">
                        ⚙️
                    </span>

                    <span>
                        Administración
                    </span>

                    <span class="sita-menu-arrow">
                        ▾
                    </span>

                </a>


                <div class="sita-submenu">


                    <?php if ($puedeVerProfesionalesNavbar): ?>

                        <a
                            href="<?= e(url('/profesionales')) ?>"
                            class="sita-submenu-item<?= $menuActivoNavbar('/profesionales') !== '' ? ' sita-submenu-item-active' : '' ?>"
                        >

                            <span class="sita-submenu-icon">
                                👨‍💼
                            </span>

                            <span>
                                Profesionales
                            </span>

                        </a>

                    <?php endif; ?>


                    <?php if ($puedeVerUsuariosNavbar): ?>

                        <a
                            href="javascript:void(0)"
                            class="sita-submenu-item sita-submenu-item-disabled"
                            aria-disabled="true"
                            onclick="return false;"
                        >

                            <span class="sita-submenu-icon">
                                👤
                            </span>

                            <span>
                                Usuarios
                            </span>

                        </a>

                    <?php endif; ?>


                    <?php if ($puedeVerRolesNavbar): ?>

                        <a
                            href="<?= e(url('/roles')) ?>"
                            class="sita-submenu-item<?= $menuActivoNavbar('/roles') !== '' ? ' sita-submenu-item-active' : '' ?>"
                        >

                            <span class="sita-submenu-icon">
                                🔐
                            </span>

                            <span>
                                Roles y permisos
                            </span>

                        </a>

                    <?php endif; ?>


                    <?php if ($puedeVerAuditoriaNavbar): ?>

                        <a
                            href="javascript:void(0)"
                            class="sita-submenu-item sita-submenu-item-disabled"
                            aria-disabled="true"
                            onclick="return false;"
                        >

                            <span class="sita-submenu-icon">
                                📋
                            </span>

                            <span>
                                Auditoría
                            </span>

                        </a>

                    <?php endif; ?>


                </div>

            </div>

        <?php endif; ?>


            <!-- USUARIO Y CIERRE DE SESIÓN (solo tabletas y móviles) -->

        <div class="sita-menu-mobile-footer">

            <div class="sita-mobile-user">

                <span class="sita-mobile-user-name">
                    <?= e($nombreUsuarioNavbar) ?>
                </span>

                <span class="sita-mobile-user-role">
                    <?= e($rolPrincipalNavbar) ?>
                </span>

            </div>

            <a
                href="<?= e(url('/logout')) ?>"
                class="sita-menu-item sita-mobile-logout"
            >

                <span class="sita-menu-icon" aria-hidden="true">
                    ⇥
                </span>

                <span>
                    Cerrar sesión
                </span>

            </a>

        </div>


    </div>


    <!-- =================================================
         USUARIO
         ================================================= -->

    <div class="sita-navbar-user">


        <div class="sita-topbar-avatar">

            <?php

            $inicialesNavbar = '';

            $partesNombreNavbar = preg_split(
                '/\s+/',
                trim($nombreUsuarioNavbar)
            );

            if (
                is_array($partesNombreNavbar)
                && count($partesNombreNavbar) > 0
            ) {

                $inicialesNavbar =
                    mb_substr(
                        $partesNombreNavbar[0],
                        0,
                        1
                    );

                if (
                    count($partesNombreNavbar) > 1
                ) {

                    $inicialesNavbar .=
                        mb_substr(
                            $partesNombreNavbar[
                                count($partesNombreNavbar) - 1
                            ],
                            0,
                            1
                        );
                }
            }

            echo e(
                mb_strtoupper(
                    $inicialesNavbar
                )
            );

            ?>

        </div>


        <div class="sita-navbar-user-info">

            <span class="sita-navbar-user-name">
                <?= e($nombreUsuarioNavbar) ?>
            </span>

            <span class="sita-navbar-user-role">
                <?= e($rolPrincipalNavbar) ?>
            </span>

        </div>


        <a
            href="<?= e(url('/logout')) ?>"
            class="sita-logout"
            title="Cerrar sesión"
            aria-label="Cerrar sesión"
        >
            ⇥
        </a>


    </div>


</div>

</nav>

<div class="sita-nav-backdrop" id="sitaNavBackdrop"></div>

<!-- ==========================================================
     MODAL DE AYUDA SITA
========================================================== -->
<div
    class="sita-ayuda-overlay"
    id="sitaModalAyuda"
    role="dialog"
    aria-modal="true"
    aria-labelledby="sitaAyudaTitulo"
    aria-hidden="true"
>
    <section class="sita-ayuda-modal" role="document">
        <header class="sita-ayuda-cabecera">
            <div>
                <h2 class="sita-ayuda-titulo" id="sitaAyudaTitulo">Ayuda de SITA</h2>
                <p class="sita-ayuda-subtitulo">Sistema de Trazabilidad de Aprendices</p>
            </div>
            <button type="button" class="sita-ayuda-cerrar" id="sitaCerrarAyuda" aria-label="Cerrar ventana de ayuda">&times;</button>
        </header>

        <div class="sita-ayuda-contenido">
            <h3 class="sita-ayuda-seccion-titulo">Equipo del proyecto</h3>
            <p class="sita-ayuda-descripcion">
                SITA es una iniciativa para apoyar la trazabilidad, el seguimiento y el acompañamiento integral de los aprendices.
            </p>

            <div class="sita-ayuda-integrantes">
                <article class="sita-ayuda-integrante">
                    <div class="sita-ayuda-avatar" aria-hidden="true">JC</div>
                    <div class="sita-ayuda-persona">
                        <p class="sita-ayuda-nombre">Jhon Faiber Cerquera</p>
                        <p class="sita-ayuda-cargo">Ingeniero de Sistemas</p>
                    </div>
                </article>

                <article class="sita-ayuda-integrante">
                    <div class="sita-ayuda-avatar" aria-hidden="true">ON</div>
                    <div class="sita-ayuda-persona">
                        <p class="sita-ayuda-nombre">Olga Maritza Narvaez</p>
                        <p class="sita-ayuda-cargo">Ingeniera de Sistemas</p>
                    </div>
                </article>

                <article class="sita-ayuda-integrante">
                    <div class="sita-ayuda-avatar" aria-hidden="true">CC</div>
                    <div class="sita-ayuda-persona">
                        <p class="sita-ayuda-nombre">Carmen Castellanos</p>
                        <p class="sita-ayuda-cargo">Psicóloga</p>
                    </div>
                </article>

                <article class="sita-ayuda-integrante">
                    <div class="sita-ayuda-avatar" aria-hidden="true">EM</div>
                    <div class="sita-ayuda-persona">
                        <p class="sita-ayuda-nombre">Eliana Meneses</p>
                        <p class="sita-ayuda-cargo">Psicóloga</p>
                    </div>
                </article>
            </div>

            <a
                class="sita-ayuda-manual"
                href="<?= e(url('/documentos/Manual_Basico_Usuario_SITA.docx')) ?>"
                target="_blank"
                rel="noopener noreferrer"
            >
                <span aria-hidden="true">📘</span>
                Consultar manual básico de usuario
            </a>

            <div class="sita-ayuda-agradecimiento">
                <div class="sita-ayuda-agradecimiento-titulo">Agradecimiento especial</div>
                <p>
                    Agradecemos al equipo de <strong>Bienestar al Aprendiz</strong> por su dedicación, compromiso y valioso apoyo en la realización y materialización del proyecto SITA.
                </p>
                <div class="sita-ayuda-agradecimiento-cierre">¡Gracias por contribuir a esta iniciativa!</div>
            </div>
        </div>

        <footer class="sita-ayuda-pie">
            SITA · Sistema de Trazabilidad de Aprendices
        </footer>
    </section>
</div>

<script>
(function () {
    'use strict';

    const modal = document.getElementById('sitaModalAyuda');
    const abrir = document.getElementById('sitaAbrirAyuda');
    const cerrar = document.getElementById('sitaCerrarAyuda');

    if (!modal || !abrir || !cerrar) {
        return;
    }

    let elementoAnterior = null;

    function abrirModal() {
        elementoAnterior = document.activeElement;
        modal.classList.add('sita-ayuda-visible');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        cerrar.focus();
    }

    function cerrarModal() {
        modal.classList.remove('sita-ayuda-visible');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (elementoAnterior && typeof elementoAnterior.focus === 'function') {
            elementoAnterior.focus();
        }
    }

    abrir.addEventListener('click', abrirModal);
    cerrar.addEventListener('click', cerrarModal);

    modal.addEventListener('click', function (evento) {
        if (evento.target === modal) {
            cerrarModal();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape' && modal.classList.contains('sita-ayuda-visible')) {
            cerrarModal();
        }
    });
})();
</script>


<script>
(function () {
    'use strict';

    const nav = document.querySelector('.sita-navbar');
    const boton = document.getElementById('sitaNavToggle');
    const menu = document.getElementById('sitaMenu');
    const fondo = document.getElementById('sitaNavBackdrop');

    if (!nav || !boton || !menu) {
        return;
    }

    // Pantallas donde el menú se recoge, o dispositivos táctiles
    // (sin cursor para "hover").
    const modoToque = window.matchMedia('(max-width: 1024px), (hover: none)');
    const modoMovil = window.matchMedia('(max-width: 1024px)');

    const dropdowns = Array.from(menu.querySelectorAll('.sita-menu-dropdown'));

    function cerrarDropdowns(excepto) {
        dropdowns.forEach(function (d) {
            if (d !== excepto) {
                d.classList.remove('sita-dropdown-open');
            }
        });
    }

    function abrirMenu() {
        nav.classList.add('sita-nav-open');
        boton.setAttribute('aria-expanded', 'true');
        boton.setAttribute('aria-label', 'Cerrar menú');

        if (fondo) {
            fondo.classList.add('sita-nav-backdrop-visible');
        }

        // Si todavía no hay un grupo abierto, abre el de la página actual.
        if (!menu.querySelector('.sita-dropdown-open')) {
            const activo = dropdowns.find(function (d) {
                return d.querySelector('.sita-menu-item-active');
            });
            if (activo) {
                activo.classList.add('sita-dropdown-open');
            }
        }
    }

    function cerrarMenu() {
        nav.classList.remove('sita-nav-open');
        boton.setAttribute('aria-expanded', 'false');
        boton.setAttribute('aria-label', 'Abrir menú');

        if (fondo) {
            fondo.classList.remove('sita-nav-backdrop-visible');
        }
    }

    boton.addEventListener('click', function () {
        if (nav.classList.contains('sita-nav-open')) {
            cerrarMenu();
        } else {
            abrirMenu();
        }
    });

    if (fondo) {
        fondo.addEventListener('click', cerrarMenu);
    }

    // Grupo con submenú: en móvil/táctil el toque abre o cierra el
    // submenú (no navega). En escritorio con ratón sigue funcionando
    // el hover y el clic navega como antes.
    dropdowns.forEach(function (d) {
        const enlace = d.querySelector(':scope > .sita-menu-item');

        if (!enlace) {
            return;
        }

        enlace.addEventListener('click', function (evento) {
            if (!modoToque.matches) {
                return;
            }

            evento.preventDefault();

            const abierto = d.classList.contains('sita-dropdown-open');

            cerrarDropdowns(d);

            d.classList.toggle('sita-dropdown-open', !abierto);
        });
    });

    // Al elegir una opción real, se cierra el panel.
    menu.addEventListener('click', function (evento) {
        const objetivo = evento.target.closest('a, button');

        if (!objetivo || !modoMovil.matches) {
            return;
        }

        if (objetivo.closest('.sita-menu-dropdown') &&
            objetivo.parentElement &&
            objetivo.parentElement.classList.contains('sita-menu-dropdown')) {
            return; // es el encabezado de un grupo
        }

        if (objetivo.classList.contains('sita-submenu-item-disabled')) {
            return; // opción aún no disponible
        }

        cerrarMenu();
    });

    // Tocar fuera del menú cierra los submenús (tabletas grandes).
    document.addEventListener('click', function (evento) {
        if (!modoMovil.matches && !evento.target.closest('.sita-menu-dropdown')) {
            cerrarDropdowns(null);
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape' && nav.classList.contains('sita-nav-open')) {
            cerrarMenu();
            boton.focus();
        }
    });

    // Al volver a pantalla ancha, se restablece el estado.
    function alCambiarTamano() {
        if (!modoMovil.matches) {
            cerrarMenu();
            cerrarDropdowns(null);
        }
    }

    if (modoMovil.addEventListener) {
        modoMovil.addEventListener('change', alCambiarTamano);
    } else if (modoMovil.addListener) {
        modoMovil.addListener(alCambiarTamano);
    }
})();
</script>