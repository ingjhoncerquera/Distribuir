<?php

declare(strict_types=1);

Auth::requireLogin();

$usuario = Auth::user() ?? [];

$nombreUsuario = trim(
    ($usuario['nombres'] ?? '') . ' ' . ($usuario['apellidos'] ?? '')
);

if ($nombreUsuario === '') {
    $nombreUsuario = $usuario['username'] ?? 'Usuario';
}

$roles = Auth::roles();

$rolPrincipal = 'Usuario';
$nombresRoles = [];

foreach ((array) $roles as $rolItem) {
    $nombreRolItem = is_array($rolItem)
        ? (string) ($rolItem['nombre'] ?? '')
        : (string) $rolItem;

    if (trim($nombreRolItem) !== '') {
        $nombresRoles[] = trim($nombreRolItem);
    }
}

if ($nombresRoles !== []) {
    $rolPrincipal = $nombresRoles[0];
}

$tituloPagina = $tituloPagina ?? 'Panel principal';

function dashboardTieneRol(array $roles, string $rolBuscado): bool
{
    $rolBuscado = mb_strtolower(trim($rolBuscado));

    foreach ($roles as $rol) {
        $nombre = is_array($rol) ? ($rol['nombre'] ?? '') : (string) $rol;

        if (mb_strtolower(trim((string) $nombre)) === $rolBuscado) {
            return true;
        }
    }

    return false;
}

$esAdministrador = dashboardTieneRol($roles, 'Administrador');
$esCoordinador = dashboardTieneRol($roles, 'Coordinador');
$esLider = dashboardTieneRol($roles, 'Lider de Ficha');
$esInstructor = dashboardTieneRol($roles, 'Instructor');
$esProfesional = (
    dashboardTieneRol($roles, 'Psicologo')
    || dashboardTieneRol($roles, 'Enfermero')
    || dashboardTieneRol($roles, 'Nutricionista')
    || dashboardTieneRol($roles, 'Bienestar')
);

/*
|--------------------------------------------------------------------------
| Datos recibidos del controlador
|--------------------------------------------------------------------------
*/

$indicadores = is_array($indicadores ?? null) ? $indicadores : [];
$estadosAprendices = is_array($estadosAprendices ?? null) ? $estadosAprendices : [];
$distribucionSexo = is_array($distribucionSexo ?? null) ? $distribucionSexo : [];
$aprendicesPorMunicipio = is_array($aprendicesPorMunicipio ?? null) ? $aprendicesPorMunicipio : [];
$estadosResultados = is_array($estadosResultados ?? null) ? $estadosResultados : [];
$alertasPorTipo = is_array($alertasPorTipo ?? null) ? $alertasPorTipo : [];
$calidadDatos = is_array($calidadDatos ?? null) ? $calidadDatos : [];
$distribucionPoblacion = is_array($distribucionPoblacion ?? null) ? $distribucionPoblacion : [];
$resumenBienestar = is_array($resumenBienestar ?? null) ? $resumenBienestar : [];
$evolucionCuestionarios = is_array($evolucionCuestionarios ?? null) ? $evolucionCuestionarios : [];

/*
|--------------------------------------------------------------------------
| Utilidades de presentación
|--------------------------------------------------------------------------
*/

$numeroDashboard = static function ($valor): string {
    return number_format((int) $valor, 0, ',', '.');
};

$porcentaje = static function ($parte, $total): float {
    $total = (float) $total;

    return $total > 0
        ? max(0.0, min(100.0, ((float) $parte / $total) * 100))
        : 0.0;
};

$textoPorcentaje = static function (float $valor): string {
    return number_format($valor, 1, ',', '.') . '%';
};

$anchoBarra = static function (float $valor): string {
    return number_format(max(0.0, min(100.0, $valor)), 1, '.', '');
};

$puede = static function (?string $permiso): bool {
    if ($permiso === null) {
        return true;
    }

    return class_exists('Auth') && Auth::can($permiso);
};

if (!function_exists('iconoSita')) {
    function iconoSita(string $nombre, int $tam = 19): string
    {
        $trazos = [
            'users' => '<circle cx="9" cy="8" r="3"></circle><path d="M3 21a6 6 0 0 1 12 0"></path><path d="M16 11a3 3 0 1 0 0-6"></path><path d="M18 14a5 5 0 0 1 3 4.5"></path>',
            'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>',
            'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 8-3 8h18s-3-1-3-8"></path><path d="M13.7 21a2 2 0 0 1-3.4 0"></path>',
            'check' => '<circle cx="12" cy="12" r="9"></circle><path d="m8 12 3 3 5-6"></path>',
            'clock' => '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path>',
            'clipboard' => '<rect x="8" y="2" width="8" height="4" rx="1"></rect><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><path d="m9 14 2 2 4-4"></path>',
            'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"></path>',
            'pulse' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>',
            'chart' => '<path d="M4 19V5"></path><path d="M4 19h17"></path><path d="m7 15 4-4 3 2 5-6"></path>',
            'layers' => '<path d="m12 2 10 5-10 5L2 7z"></path><path d="m2 17 10 5 10-5"></path><path d="m2 12 10 5 10-5"></path>',
            'box' => '<path d="M21 8 12 3 3 8l9 5z"></path><path d="M3 8v8l9 5 9-5V8"></path><path d="M12 13v8"></path>',
            'shield' => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"></path>',
            'upload' => '<path d="M12 16V4"></path><path d="m7 9 5-5 5 5"></path><path d="M4 20h16"></path>',
            'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"></rect><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>',
            'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path>',
            'star' => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"></path>',
            'lock' => '<rect x="3" y="11" width="18" height="10" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>',
        ];

        return '<svg width="' . $tam . '" height="' . $tam . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">'
            . ($trazos[$nombre] ?? $trazos['chart'])
            . '</svg>';
    }
}

/* Degradado cónico para las gráficas circulares */
$degradadoCircular = static function (array $segmentos): string {
    $suma = 0.0;

    foreach ($segmentos as $segmento) {
        $suma += max(0.0, (float) $segmento['valor']);
    }

    if ($suma <= 0) {
        return '#EDF2F5';
    }

    $partes = [];
    $acumulado = 0.0;

    foreach ($segmentos as $segmento) {
        $valor = max(0.0, (float) $segmento['valor']);

        if ($valor <= 0) {
            continue;
        }

        $inicio = $acumulado;
        $acumulado += ($valor / $suma) * 100;

        $partes[] = $segmento['color']
            . ' ' . number_format($inicio, 2, '.', '') . '%'
            . ' ' . number_format($acumulado, 2, '.', '') . '%';
    }

    return 'conic-gradient(' . implode(', ', $partes) . ')';
};

$paleta = ['#477FA8', '#19A7A8', '#8978B8', '#D99A4D', '#D0675F', '#4FA67A', '#B7C3CB'];

/*
|--------------------------------------------------------------------------
| Indicadores calculados
|--------------------------------------------------------------------------
*/

$totalAprendices = (int) ($indicadores['aprendices'] ?? 0);
$enFormacion = (int) ($indicadores['aprendices_en_formacion'] ?? 0);
$cancelados = (int) ($indicadores['aprendices_cancelados'] ?? 0);
$retiros = (int) ($indicadores['aprendices_retiro'] ?? 0);
$aplazados = (int) ($indicadores['aprendices_aplazados'] ?? 0);

$fichasTotal = (int) ($indicadores['fichas'] ?? 0);
$fichasActivas = (int) ($indicadores['fichas_activas'] ?? 0);

$alertasActivas = (int) ($indicadores['alertas_activas'] ?? 0);
$alertasPorVencer = (int) ($indicadores['alertas_por_vencer'] ?? 0);

$resultadosTotal = (int) ($indicadores['resultados_total'] ?? 0);
$resultadosAprobados = (int) ($indicadores['resultados_aprobados'] ?? 0);
$resultadosNoAprobados = (int) ($indicadores['resultados_no_aprobados'] ?? 0);
$resultadosPendientes = (int) ($indicadores['resultados_pendientes'] ?? 0);

$caracterizadosActualizados = (int) ($indicadores['caracterizaciones_actualizadas'] ?? 0);
$caracterizadosPendientes = (int) ($indicadores['caracterizaciones_pendientes'] ?? 0);
$caracterizadosTotal = $caracterizadosActualizados + $caracterizadosPendientes;

$cuestionariosCompletados = (int) ($resumenBienestar['completados'] ?? 0);
$cuestionariosBorrador = (int) ($resumenBienestar['borradores'] ?? 0);
$aprendicesConCuestionario = (int) ($resumenBienestar['aprendices_completados'] ?? 0);
$cuestionariosRecientes = (int) ($resumenBienestar['actualizados_30_dias'] ?? 0);

$baseCobertura = $enFormacion > 0 ? $enFormacion : $totalAprendices;

$pctPermanencia = $porcentaje($enFormacion, $totalAprendices);
$pctDesercion = $porcentaje($cancelados + $retiros, $totalAprendices);
$pctAprobacion = $porcentaje($resultadosAprobados, $resultadosTotal);
$pctCaracterizacion = $porcentaje($caracterizadosActualizados, $caracterizadosTotal);
$pctCobertura = $porcentaje($aprendicesConCuestionario, $baseCobertura);

/* Calidad de datos */
$totalCalidad = (int) ($calidadDatos['total'] ?? 0);

$camposCalidad = [
    'Teléfono sin registrar' => (int) ($calidadDatos['sin_telefono'] ?? 0),
    'Correo sin registrar' => (int) ($calidadDatos['sin_correo'] ?? 0),
    'Dirección sin registrar' => (int) ($calidadDatos['sin_direccion'] ?? 0),
    'Municipio sin registrar' => (int) ($calidadDatos['sin_municipio'] ?? 0),
    'Departamento sin registrar' => (int) ($calidadDatos['sin_departamento'] ?? 0),
    'EPS sin registrar' => (int) ($calidadDatos['sin_eps'] ?? 0),
];

$indiceCompletitud = 0.0;

if ($totalCalidad > 0) {
    $suma = 0.0;

    foreach ($camposCalidad as $faltantes) {
        $suma += 100 - $porcentaje($faltantes, $totalCalidad);
    }

    $indiceCompletitud = $suma / count($camposCalidad);
}

/*
|--------------------------------------------------------------------------
| Atención prioritaria (solo se listan los asuntos con casos)
|--------------------------------------------------------------------------
*/

$prioridades = [
    [
        'nivel' => 'danger',
        'titulo' => 'Alertas por vencer',
        'detalle' => 'Alertas vigentes que terminan en los próximos 7 días.',
        'total' => $alertasPorVencer,
    ],
    [
        'nivel' => 'warning',
        'titulo' => 'Alertas vigentes',
        'detalle' => 'Aprendices con acompañamiento activo.',
        'total' => $alertasActivas,
    ],
    [
        'nivel' => 'warning',
        'titulo' => 'Resultados de aprendizaje pendientes',
        'detalle' => 'Juicios evaluativos sin definir.',
        'total' => $resultadosPendientes,
    ],
    [
        'nivel' => 'info',
        'titulo' => 'Caracterizaciones pendientes',
        'detalle' => 'Registros por actualizar.',
        'total' => $caracterizadosPendientes,
    ],
    [
        'nivel' => 'info',
        'titulo' => 'Cuestionarios de Bienestar en proceso',
        'detalle' => 'Borradores que aún no se finalizan.',
        'total' => $cuestionariosBorrador,
    ],
    [
        'nivel' => 'info',
        'titulo' => 'Aprendices sin EPS registrada',
        'detalle' => 'Dato de salud pendiente de completar.',
        'total' => (int) ($calidadDatos['sin_eps'] ?? 0),
    ],
    [
        'nivel' => 'info',
        'titulo' => 'Aprendices sin teléfono de contacto',
        'detalle' => 'Dificulta el acompañamiento oportuno.',
        'total' => (int) ($calidadDatos['sin_telefono'] ?? 0),
    ],
];

$prioridades = array_values(array_filter(
    $prioridades,
    static fn(array $item): bool => $item['total'] > 0
));

/*
|--------------------------------------------------------------------------
| Cuestionarios completados por mes (últimos 6 meses)
|--------------------------------------------------------------------------
*/

$mesesAbreviados = [
    1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
    7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
];

$totalesPorMes = [];

foreach ($evolucionCuestionarios as $filaMes) {
    $totalesPorMes[(string) ($filaMes['periodo'] ?? '')] = (int) ($filaMes['total'] ?? 0);
}

$serieMeses = [];

for ($i = 5; $i >= 0; $i--) {
    $marca = strtotime('first day of -' . $i . ' month');
    $clave = date('Y-m', $marca);

    $serieMeses[] = [
        'etiqueta' => $mesesAbreviados[(int) date('n', $marca)],
        'total' => $totalesPorMes[$clave] ?? 0,
    ];
}

$maxMes = max(1, max(array_column($serieMeses, 'total')));
$totalSerieMeses = array_sum(array_column($serieMeses, 'total'));

/*
|--------------------------------------------------------------------------
| Gráficas circulares
|--------------------------------------------------------------------------
*/

$segmentosEstados = [];

foreach ($estadosAprendices as $indice => $fila) {
    $segmentosEstados[] = [
        'etiqueta' => (string) ($fila['etiqueta'] ?? 'Sin clasificar'),
        'valor' => (int) ($fila['total'] ?? 0),
        'color' => $paleta[$indice % count($paleta)],
    ];
}

$coloresResultado = [
    'Aprobado' => '#4FA67A',
    'No aprobado' => '#D0675F',
    'Pendiente' => '#D99A4D',
    'Sin clasificar' => '#B7C3CB',
];

$segmentosResultados = [];

foreach ($estadosResultados as $fila) {
    $etiquetaResultado = (string) ($fila['etiqueta'] ?? 'Sin clasificar');

    $segmentosResultados[] = [
        'etiqueta' => $etiquetaResultado,
        'valor' => (int) ($fila['total'] ?? 0),
        'color' => $coloresResultado[$etiquetaResultado] ?? '#B7C3CB',
    ];
}

/*
|--------------------------------------------------------------------------
| Accesos del panel (enlaces reales)
|--------------------------------------------------------------------------
*/

$modulosPrincipales = [
    ['Aprendices', 'Información y trazabilidad individual.', 'users', '/aprendices', null],
    ['Fichas', 'Grupos de formación y su estado.', 'book', '/fichas', null],
    ['Programas', 'Programas de formación registrados.', 'layers', '/programas', null],
    ['Asignaciones de ficha', 'Líderes e instructores por ficha.', 'briefcase', '/asignaciones-ficha', null],
    ['Materiales', 'Catálogo y solicitudes de materiales.', 'box', '/materiales', null],
];

$consultasBienestar = [
    ['Promoción y prevención', 'Hábitos, salud y orientación.', 'heart', '/bienestar/consultas/prevencion', 'bienestar.prevencion.ver'],
    ['Habilidades blandas', 'Estrés, apoyo y convivencia.', 'users', '/bienestar/consultas/habilidades-blandas', null],
    ['Actividad física', 'Deporte, intereses y barreras.', 'pulse', '/bienestar/consultas/actividad-fisica', 'bienestar.actividad_fisica.ver'],
    ['Habilidades artísticas', 'Talentos e intereses culturales.', 'star', '/bienestar/consultas/habilidades-artisticas', 'bienestar.habilidades_artisticas.ver'],
];

$modulosAdministracion = [
    ['Profesionales', 'Equipo de Bienestar y accesos.', 'briefcase', '/profesionales', null],
    ['Roles y permisos', 'Control de acceso al sistema.', 'shield', '/roles', null],
    ['Importar datos', 'Carga masiva de información.', 'upload', '/importar', null],
];

$mostrarAdministracion = $esAdministrador || $esCoordinador;

$consultasBienestar = array_values(array_filter(
    $consultasBienestar,
    static fn(array $m): bool => $puede($m[4])
));

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e($tituloPagina) ?> | SITA
    </title>

    <style>

        /* ==========================================================
           VARIABLES
        ========================================================== */

        :root {

            --sita-navy:
                #0B263D;

            --sita-blue:
                #123B5D;

            --sita-blue-light:
                #1A537B;

            --sita-turquoise:
                #19A7A8;

            --sita-turquoise-dark:
                #13898A;

            --sita-turquoise-light:
                #EAF7F7;

            --sita-background:
                #F2F6F8;

            --sita-white:
                #FFFFFF;

            --sita-border:
                #E2E9ED;

            --sita-text:
                #263746;

            --sita-muted:
                #71808B;

            --sita-sidebar-text:
                rgba(255,255,255,.68);

            --sita-sidebar-hover:
                rgba(255,255,255,.08);

            --sita-shadow:
                0 8px 25px rgba(11,38,61,.07);

            --sita-shadow-hover:
                0 14px 35px rgba(11,38,61,.11);

            --sita-radius:
                17px;

        }


        /* ==========================================================
           RESET
        ========================================================== */

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {

            min-height: 100vh;

            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Arial,
                sans-serif;

            color:
                var(--sita-text);

            background:
                var(--sita-background);
        }


        a {
            text-decoration: none;
        }


        button {
            font-family: inherit;
        }


        /* ==========================================================
           ESTRUCTURA PRINCIPAL
        ========================================================== */

        .sita-app {
            min-height: 100vh;
            display: block;
        }


       
        


        /* ==========================================================
           CONTENIDO
        ========================================================== */

        .sita-main {
            width: 100%;
            min-height: calc(100vh - 72px);
        }


        /* ==========================================================
           TOPBAR
        ========================================================== */

        .sita-topbar {

            height: 76px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding:
                0 32px;

            background:
                rgba(255,255,255,.96);

            border-bottom:
                1px solid var(--sita-border);

            position: sticky;

            top: 0;

            z-index: 50;

        }


        .sita-page-heading {

            display: flex;
            align-items: center;

            gap: 12px;

        }


        .sita-page-icon {

            width: 37px;
            height: 37px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 10px;

            color:
                var(--sita-turquoise);

            background:
                var(--sita-turquoise-light);

        }


        .sita-page-title {

            margin: 0;

            color:
                var(--sita-navy);

            font-size: 17px;

            font-weight: 750;

        }


        .sita-page-subtitle {

            margin:
                2px 0 0;

            color:
                var(--sita-muted);

            font-size: 11px;

        }


        .sita-topbar-right {

            display: flex;
            align-items: center;

            gap: 15px;

        }


        .sita-topbar-date {

            color:
                var(--sita-muted);

            font-size: 11.5px;

        }


        .sita-topbar-user {

            display: flex;
            align-items: center;

            gap: 9px;

            padding-left: 15px;

            border-left:
                1px solid var(--sita-border);

        }


        .sita-topbar-avatar {

            width: 34px;
            height: 34px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 10px;

            background:
                var(--sita-turquoise-light);

            color:
                var(--sita-turquoise-dark);

            font-size: 12px;

            font-weight: 800;

        }


        .sita-topbar-name {

            color:
                var(--sita-text);

            font-size: 12px;

            font-weight: 700;

        }


        /* ==========================================================
           CONTENIDO INTERNO
        ========================================================== */

        .sita-content {

            padding:
                30px 32px 40px;

        }


        /* ==========================================================
           BIENVENIDA
        ========================================================== */

        .sita-welcome {

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 25px;

            margin-bottom: 25px;

            padding:
                27px 30px;

            border-radius:
                var(--sita-radius);

            background:
                linear-gradient(
                    135deg,
                    #123B5D 0%,
                    #16506F 65%,
                    #167B83 100%
                );

            color: white;

            position: relative;

            overflow: hidden;

            box-shadow:
                0 10px 30px rgba(11,38,61,.12);

        }


        .sita-welcome::after {

            content: "";

            position: absolute;

            width: 260px;
            height: 260px;

            right: -110px;
            top: -150px;

            border-radius: 50%;

            background:
                rgba(25,167,168,.14);

        }


        .sita-welcome-content {

            position: relative;

            z-index: 2;

        }


        .sita-welcome-label {

            margin: 0 0 7px;

            color:
                rgba(255,255,255,.58);

            font-size: 10px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing:
                1px;

        }


        .sita-welcome-title {

            margin: 0;

            color: white;

            font-size: 25px;

            font-weight: 750;

            letter-spacing:
                -.5px;

        }


        .sita-welcome-description {

            margin:
                7px 0 0;

            max-width: 650px;

            color:
                rgba(255,255,255,.67);

            font-size: 12.5px;

            line-height: 1.55;

        }


        .sita-role-badge {

            position: relative;

            z-index: 2;

            flex-shrink: 0;

            padding:
                9px 14px;

            border-radius: 20px;

            background:
                rgba(255,255,255,.10);

            border:
                1px solid rgba(255,255,255,.14);

            color:
                rgba(255,255,255,.85);

            font-size: 10px;

            font-weight: 700;

        }


        /* ==========================================================
           SECCIÓN
        ========================================================== */

        .sita-section-heading {

            display: flex;
            align-items: center;
            justify-content: space-between;

            margin:
                0 0 14px;

        }


        .sita-section-title {

            margin: 0;

            color:
                var(--sita-navy);

            font-size: 15px;

            font-weight: 750;

        }


        .sita-section-description {

            margin:
                3px 0 0;

            color:
                var(--sita-muted);

            font-size: 10.5px;

        }


        /* ==========================================================
           TARJETAS INDICADORES
        ========================================================== */

        .sita-stats {

            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 15px;

            margin-bottom: 30px;

        }


        .sita-stat-card {

            min-height: 130px;

            padding: 20px;

            background:
                var(--sita-white);

            border:
                1px solid var(--sita-border);

            border-radius:
                var(--sita-radius);

            box-shadow:
                var(--sita-shadow);

            transition:
                transform .2s ease,
                box-shadow .2s ease;

        }


        .sita-stat-card:hover {

            transform:
                translateY(-3px);

            box-shadow:
                var(--sita-shadow-hover);

        }


        .sita-stat-top {

            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-bottom: 17px;

        }


        .sita-stat-icon {

            width: 39px;
            height: 39px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 11px;

        }


        .sita-stat-icon.blue {

            color:
                #2E6E9D;

            background:
                #EAF2F8;

        }


        .sita-stat-icon.turquoise {

            color:
                var(--sita-turquoise-dark);

            background:
                var(--sita-turquoise-light);

        }


        .sita-stat-icon.purple {

            color:
                #7563A8;

            background:
                #F0EDF8;

        }


        .sita-stat-icon.orange {

            color:
                #B57A31;

            background:
                #FFF5E7;

        }


        .sita-stat-label {

            margin: 0;

            color:
                var(--sita-muted);

            font-size: 11.5px;

            font-weight: 600;

        }


        .sita-stat-value {

            margin:
                4px 0 0;

            color:
                var(--sita-navy);

            font-size: 27px;

            line-height: 1;

            font-weight: 800;

        }


        .sita-stat-status {

            margin-top: 8px;

            color:
                #8A969E;

            font-size: 9.5px;

        }


        /* ==========================================================
           CUERPO DASHBOARD
        ========================================================== */

        .sita-dashboard-grid {

            display: grid;

            grid-template-columns:
                1.55fr 1fr;

            gap: 18px;

        }


        .sita-panel {

            background:
                var(--sita-white);

            border:
                1px solid var(--sita-border);

            border-radius:
                var(--sita-radius);

            box-shadow:
                var(--sita-shadow);

            overflow: hidden;

        }


        .sita-panel-header {

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding:
                19px 20px;

            border-bottom:
                1px solid var(--sita-border);

        }


        .sita-panel-title {

            margin: 0;

            color:
                var(--sita-navy);

            font-size: 13px;

            font-weight: 750;

        }


        .sita-panel-subtitle {

            margin:
                4px 0 0;

            color:
                var(--sita-muted);

            font-size: 9.5px;

        }


        .sita-panel-body {

            padding:
                20px;

        }


        /* ==========================================================
           ACCESOS RÁPIDOS
        ========================================================== */

        .sita-module-grid {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 10px;

        }


        .sita-module {

            display: flex;
            align-items: center;

            gap: 11px;

            min-height: 67px;

            padding:
                12px;

            border:
                1px solid var(--sita-border);

            border-radius: 12px;

            color:
                var(--sita-text);

            transition:
                border-color .2s ease,
                background .2s ease,
                transform .2s ease;

        }


        .sita-module:hover {

            border-color:
                rgba(25,167,168,.35);

            background:
                #FBFEFE;

            transform:
                translateY(-1px);

        }


        .sita-module-icon {

            width: 35px;
            height: 35px;

            display: flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;

            border-radius: 10px;

            color:
                var(--sita-turquoise-dark);

            background:
                var(--sita-turquoise-light);

        }


        .sita-module-name {

            margin: 0;

            color:
                var(--sita-navy);

            font-size: 12px;

            font-weight: 750;

        }


        .sita-module-description {

            margin:
                3px 0 0;

            color:
                var(--sita-muted);

            font-size: 9.5px;

            line-height: 1.4;

        }


        /* ==========================================================
           ESTADO DEL SISTEMA
        ========================================================== */

        .sita-status-list {

            display: flex;
            flex-direction: column;

            gap: 12px;

        }


        .sita-status-item {

            display: flex;
            align-items: center;

            gap: 11px;

            padding:
                12px 0;

            border-bottom:
                1px solid var(--sita-border);

        }


        .sita-status-item:last-child {

            border-bottom:
                0;

            padding-bottom: 0;

        }


        .sita-status-dot {

            width: 9px;
            height: 9px;

            border-radius: 50%;

            background:
                var(--sita-turquoise);

            box-shadow:
                0 0 0 4px
                rgba(25,167,168,.10);

        }


        .sita-status-text {

            flex: 1;

        }


        .sita-status-title {

            margin: 0;

            color:
                var(--sita-text);

            font-size: 10.5px;

            font-weight: 700;

        }


        .sita-status-description {

            margin:
                3px 0 0;

            color:
                var(--sita-muted);

            font-size: 9px;

        }


        .sita-status-label {

            padding:
                5px 8px;

            border-radius: 7px;

            color:
                #13898A;

            background:
                var(--sita-turquoise-light);

            font-size: 8px;

            font-weight: 750;

        }


        /* ==========================================================
           INFORMACIÓN PRIVACIDAD
        ========================================================== */

        .sita-privacy {

            margin-top:
                18px;

            padding:
                18px;

            display: flex;
            align-items: flex-start;

            gap: 13px;

            border-radius:
                14px;

            background:
                linear-gradient(
                    135deg,
                    #F0F7FA,
                    #EDF8F8
                );

            border:
                1px solid #DCECEE;

        }


        .sita-privacy-icon {

            width: 36px;
            height: 36px;

            display: flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;

            border-radius: 10px;

            color:
                var(--sita-blue);

            background:
                white;

        }


        .sita-privacy-title {

            margin:
                0 0 4px;

            color:
                var(--sita-navy);

            font-size: 10.5px;

            font-weight: 750;

        }


        .sita-privacy-text {

            margin: 0;

            color:
                var(--sita-muted);

            font-size: 9px;

            line-height: 1.55;

        }


        /* ==========================================================
           FOOTER
        ========================================================== */

        .sita-footer {

            margin-top:
                28px;

            padding-top:
                18px;

            border-top:
                1px solid var(--sita-border);

            display: flex;
            align-items: center;
            justify-content: space-between;

            color:
                var(--sita-muted);

            font-size: 9px;

        }


        .sita-footer-brand {

            color:
                var(--sita-blue);

            font-weight: 750;

        }


        /* ==========================================================
           RESPONSIVE
        ========================================================== */

        @media (max-width: 1100px) {

            .sita-stats {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));

            }

            .sita-dashboard-grid {

                grid-template-columns:
                    1fr;

            }

        }


        @media (max-width: 850px) {

            .sita-navbar {
                padding: 8px 12px;
                gap: 6px;
            }

            .sita-brand-text {
                min-width: 105px;
            }

            .sita-brand-name {
                font-size: 17px;
            }

            .sita-navbar .sita-menu {
                flex: 1 1 auto;
                justify-content: center;
                gap: 1px;
                padding-top: 0;
            }

            .sita-navbar .sita-menu-item {
                min-height: 36px;
                padding: 0 5px;
                font-size: 11px;
            }

            .sita-navbar .sita-menu-icon {
                width: 15px;
                height: 15px;
                margin-right: 4px;
            }

            .sita-navbar-user-info {
                display: none;
            }

            .sita-topbar {

                padding:
                    0 22px;

            }

            .sita-content {

                padding:
                    24px 22px 35px;

            }

        }


        @media (max-width: 650px) {

            .sita-topbar-date {

                display: none;

            }

            .sita-topbar-user {

                border-left: 0;

                padding-left: 0;

            }

            .sita-welcome {

                align-items: flex-start;

                flex-direction: column;

                padding:
                    23px;

            }

            .sita-stats {

                grid-template-columns:
                    1fr 1fr;

            }

            .sita-module-grid {

                grid-template-columns:
                    1fr;

            }

        }


        @media (max-width: 480px) {

            .sita-navbar {
                min-height: 64px;
                padding: 8px 12px;
                gap: 8px;
            }

            .sita-brand-description {
                display: none;
            }

            .sita-brand-text {
                min-width: auto;
            }

            .sita-navbar-user {
                padding-left: 8px;
            }

            .sita-logout {
                width: 36px;
                padding: 0;
            }

            .sita-logout span {
                display: none;
            }

            .sita-content {

                padding:
                    18px 14px 30px;

            }

            .sita-topbar {

                height: 66px;

                padding:
                    0 14px;

            }

            .sita-page-subtitle {

                display: none;

            }

            .sita-page-title {

                font-size: 13px;

            }

            .sita-stats {

                grid-template-columns:
                    1fr;

            }

            .sita-welcome-title {

                font-size: 21px;

            }

            .sita-footer {

                flex-direction: column;

                align-items: flex-start;

                gap: 5px;

            }

        }

    
        /* ==========================================================
           ESTADÍSTICAS Y CALIDAD DE DATOS
        ========================================================== */
        .sita-analytics {
            margin-top: 22px;
        }
        .sita-analytics-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 18px;
        }
        .sita-chart-panel {
            min-width: 0;
            background: var(--sita-white);
            border: 1px solid var(--sita-border);
            border-radius: var(--sita-radius);
            box-shadow: var(--sita-shadow);
            overflow: hidden;
        }
        .sita-chart-header {
            padding: 18px 20px 12px;
            border-bottom: 1px solid var(--sita-border);
        }
        .sita-chart-title {
            margin: 0;
            color: var(--sita-navy);
            font-size: 14px;
            font-weight: 750;
        }
        .sita-chart-subtitle {
            margin: 5px 0 0;
            color: var(--sita-muted);
            font-size: 11px;
        }
        .sita-chart-body {
            padding: 18px 20px;
        }
        .sita-bar-row {
            display: grid;
            grid-template-columns: minmax(90px, 1fr) minmax(70px, 1.65fr) 42px;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }
        .sita-bar-row:last-child {
            margin-bottom: 0;
        }
        .sita-bar-label {
            color: var(--sita-text);
            font-size: 11px;
            overflow-wrap: anywhere;
        }
        .sita-bar-track {
            height: 9px;
            background: #EDF2F5;
            border-radius: 20px;
            overflow: hidden;
        }
        .sita-bar-fill {
            height: 100%;
            min-width: 0;
            border-radius: 20px;
            background: var(--sita-turquoise);
        }
        .sita-bar-fill.blue {
            background: #477FA8;
        }
        .sita-bar-fill.purple {
            background: #8978B8;
        }
        .sita-bar-fill.orange {
            background: #D99A4D;
        }
        .sita-bar-value {
            text-align: right;
            color: var(--sita-navy);
            font-size: 11px;
            font-weight: 750;
            font-variant-numeric: tabular-nums;
        }
        .sita-quality-item {
            margin-bottom: 15px;
        }
        .sita-quality-item:last-child {
            margin-bottom: 0;
        }
        .sita-quality-top {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 6px;
            color: var(--sita-text);
            font-size: 11px;
        }
        .sita-quality-top strong {
            color: var(--sita-navy);
            white-space: nowrap;
        }
        .sita-empty-chart {
            padding: 14px 0;
            color: var(--sita-muted);
            font-size: 12px;
        }
        .sita-analytics-note {
            margin: 0 0 16px;
            color: var(--sita-muted);
            font-size: 11px;
        }
        @media (max-width: 900px) {
            .sita-analytics-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 560px) {
            .sita-bar-row {
                grid-template-columns: minmax(82px, 1fr) minmax(45px, 1fr) 34px;
                gap: 7px;
            }
            .sita-chart-header,
            .sita-chart-body {
                padding-left: 14px;
                padding-right: 14px;
            }
        }

        /* ==========================================================
           PANEL MEJORADO - COMPONENTES NUEVOS
        ========================================================== */

        .sita-stat-icon.green { color: #2F8A5F; background: #E8F5EE; }
        .sita-stat-icon.red { color: #B64B44; background: #FBEDEB; }

        .sita-stat-meter {
            height: 6px;
            margin-top: 12px;
            background: #EDF2F5;
            border-radius: 20px;
            overflow: hidden;
        }

        .sita-stat-meter span {
            display: block;
            height: 100%;
            border-radius: 20px;
            background: var(--sita-turquoise);
        }

        .sita-stat-meter.green span { background: #4FA67A; }
        .sita-stat-meter.red span { background: #D0675F; }
        .sita-stat-meter.blue span { background: #477FA8; }

        .sita-stat-top { justify-content: space-between; }

        .sita-stat-badge {
            font-size: 10px;
            font-weight: 750;
            color: var(--sita-turquoise-dark);
            background: var(--sita-turquoise-light);
            padding: 4px 9px;
            border-radius: 20px;
        }

        .sita-stat-badge.red { color: #B64B44; background: #FBEDEB; }
        .sita-stat-badge.green { color: #2F8A5F; background: #E8F5EE; }
        .sita-stat-badge.orange { color: #A9701F; background: #FBF3E6; }
        .sita-stat-badge.blue { color: #2E6E9D; background: #EAF2F8; }

        .sita-stat-status { font-size: 10.5px; line-height: 1.45; }

        .sita-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        .sita-chip {
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 600;
            color: #fff;
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 20px;
        }

        .sita-block { margin-bottom: 18px; }

        .sita-priority-list { display: flex; flex-direction: column; gap: 10px; }

        .sita-priority {
            display: grid;
            grid-template-columns: 8px 1fr auto;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border: 1px solid var(--sita-border);
            border-radius: 12px;
        }

        .sita-priority-mark { align-self: stretch; border-radius: 8px; background: #477FA8; }
        .sita-priority.danger .sita-priority-mark { background: #D0675F; }
        .sita-priority.warning .sita-priority-mark { background: #D99A4D; }

        .sita-priority-title { margin: 0; color: var(--sita-navy); font-size: 12.5px; font-weight: 700; }
        .sita-priority-detail { margin: 3px 0 0; color: var(--sita-muted); font-size: 10.5px; }

        .sita-priority-total {
            min-width: 46px;
            text-align: center;
            padding: 6px 10px;
            color: var(--sita-navy);
            font-size: 15px;
            font-weight: 800;
            background: #F2F6F8;
            border-radius: 10px;
        }

        .sita-priority.danger .sita-priority-total { color: #B64B44; background: #FBEDEB; }
        .sita-priority.warning .sita-priority-total { color: #A9701F; background: #FBF3E6; }

        .sita-ok-message {
            padding: 22px;
            text-align: center;
            color: #2F8A5F;
            font-size: 12.5px;
            font-weight: 650;
            background: #E8F5EE;
            border-radius: 12px;
        }

        .sita-month-chart {
            height: 170px;
            display: flex;
            align-items: flex-end;
            gap: 12px;
            padding-top: 10px;
        }

        .sita-month-col {
            flex: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
            gap: 6px;
            min-width: 0;
        }

        .sita-month-value { color: var(--sita-navy); font-size: 11px; font-weight: 750; }

        .sita-month-bar {
            width: 100%;
            max-width: 38px;
            min-height: 4px;
            border-radius: 8px 8px 3px 3px;
            background: linear-gradient(180deg, #19A7A8, #13898A);
        }

        .sita-month-bar.empty { background: #E2E9ED; }
        .sita-month-label { color: var(--sita-muted); font-size: 10.5px; }

        .sita-mini-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid var(--sita-border);
        }

        .sita-mini-stat strong { display: block; color: var(--sita-navy); font-size: 16px; }
        .sita-mini-stat span { color: var(--sita-muted); font-size: 10px; }

        .sita-donut-wrap { display: flex; align-items: center; gap: 22px; flex-wrap: wrap; }

        .sita-donut {
            width: 132px;
            height: 132px;
            flex: 0 0 auto;
            position: relative;
            border-radius: 50%;
        }

        .sita-donut::after {
            content: "";
            position: absolute;
            inset: 22px;
            z-index: 1;
            background: #fff;
            border-radius: 50%;
        }

        .sita-donut-center {
            position: absolute;
            inset: 0;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .sita-donut-center strong { color: var(--sita-navy); font-size: 20px; line-height: 1; }
        .sita-donut-center span { margin-top: 4px; color: var(--sita-muted); font-size: 9.5px; }

        .sita-legend { flex: 1; min-width: 160px; display: flex; flex-direction: column; gap: 9px; }

        .sita-legend-item {
            display: grid;
            grid-template-columns: 10px 1fr auto;
            align-items: center;
            gap: 8px;
            font-size: 11px;
        }

        .sita-legend-dot { width: 10px; height: 10px; border-radius: 50%; }
        .sita-legend-item strong { color: var(--sita-navy); }
        .sita-legend-item small { margin-left: 5px; color: var(--sita-muted); }

        .sita-donut-footer {
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px solid var(--sita-border);
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            color: var(--sita-muted);
            font-size: 10.5px;
        }

        .sita-donut-footer strong { color: var(--sita-navy); }

        .sita-module-group-title {
            margin: 18px 0 10px;
            color: var(--sita-muted);
            font-size: 10px;
            font-weight: 750;
            letter-spacing: .9px;
            text-transform: uppercase;
        }

        .sita-module-group-title:first-child { margin-top: 0; }

        .sita-module:hover {
            border-color: var(--sita-turquoise);
            background: var(--sita-turquoise-light);
        }

        .sita-session-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 18px; }

        .sita-session-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--sita-border);
            font-size: 11.5px;
        }

        .sita-session-item span { color: var(--sita-muted); }
        .sita-session-item strong { color: var(--sita-navy); text-align: right; }

        .sita-score {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
            padding: 14px 16px;
            background: var(--sita-turquoise-light);
            border-radius: 12px;
        }

        .sita-score strong { color: var(--sita-turquoise-dark); font-size: 24px; }
        .sita-score span { color: var(--sita-text); font-size: 11px; line-height: 1.5; }

        .sita-bar-row.with-pct {
            grid-template-columns: minmax(90px, 1fr) minmax(70px, 1.5fr) 82px;
        }

        .sita-bar-value small { color: var(--sita-muted); font-weight: 500; margin-left: 3px; }

        @media (max-width: 1100px) {
            .sita-analytics-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 560px) {
            .sita-mini-stats { grid-template-columns: 1fr 1fr; }
            .sita-donut-wrap { justify-content: center; }
        }

    </style>

</head>


<body>

<div class="sita-app">

    <?php require ROOT_PATH . '/views/layout/navbar.php'; ?>

    <main class="sita-main">

        <header class="sita-topbar">

            <div class="sita-page-heading">
                <div class="sita-page-icon"><?= iconoSita('chart', 18) ?></div>

                <div>
                    <h2 class="sita-page-title">Panel principal</h2>
                    <p class="sita-page-subtitle">Sistema de Trazabilidad de Aprendices</p>
                </div>
            </div>

            <div class="sita-topbar-right">
                <div class="sita-topbar-date"><?= e(date('d/m/Y')) ?></div>

                <div class="sita-topbar-user">
                    <div class="sita-topbar-avatar">
                        <?= e(mb_strtoupper(mb_substr($nombreUsuario, 0, 1))) ?>
                    </div>

                    <div class="sita-topbar-name"><?= e($nombreUsuario) ?></div>
                </div>
            </div>

        </header>

        <div class="sita-content">

            <!-- BIENVENIDA -->
            <section class="sita-welcome">

                <div class="sita-welcome-content">
                    <p class="sita-welcome-label">Sistema de Trazabilidad de Aprendices</p>

                    <h1 class="sita-welcome-title">Bienvenido, <?= e($nombreUsuario) ?></h1>

                    <p class="sita-welcome-description">
                        Desde este espacio podrá realizar el seguimiento integral del
                        proceso formativo, académico y de acompañamiento de los
                        aprendices según sus permisos.
                    </p>

                    <div class="sita-chips">
                        <span class="sita-chip"><?= e($numeroDashboard($enFormacion)) ?> aprendices en formación</span>
                        <span class="sita-chip"><?= e($numeroDashboard($fichasActivas)) ?> fichas activas</span>
                        <span class="sita-chip"><?= e($numeroDashboard($alertasActivas)) ?> alertas vigentes</span>

                        <?php if ($alertasPorVencer > 0): ?>
                            <span class="sita-chip"><?= e($numeroDashboard($alertasPorVencer)) ?> vencen en 7 días</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="sita-role-badge"><?= e($rolPrincipal) ?></div>

            </section>

            <!-- INDICADORES -->
            <div class="sita-section-heading">
                <div>
                    <h2 class="sita-section-title">Resumen general</h2>
                    <p class="sita-section-description">Indicadores principales del sistema con su porcentaje</p>
                </div>
            </div>

            <section class="sita-stats">

                <article class="sita-stat-card">
                    <div class="sita-stat-top">
                        <div class="sita-stat-icon blue"><?= iconoSita('users') ?></div>
                        <span class="sita-stat-badge blue"><?= e($textoPorcentaje($pctPermanencia)) ?> activos</span>
                    </div>
                    <p class="sita-stat-label">Aprendices</p>
                    <p class="sita-stat-value"><?= e($numeroDashboard($totalAprendices)) ?></p>
                    <p class="sita-stat-status"><?= e($numeroDashboard($enFormacion)) ?> en formación actualmente</p>
                    <div class="sita-stat-meter blue"><span style="width: <?= e($anchoBarra($pctPermanencia)) ?>%"></span></div>
                </article>

                <article class="sita-stat-card">
                    <div class="sita-stat-top">
                        <div class="sita-stat-icon turquoise"><?= iconoSita('book') ?></div>
                    </div>
                    <p class="sita-stat-label">Fichas activas</p>
                    <p class="sita-stat-value"><?= e($numeroDashboard($fichasActivas)) ?></p>
                    <p class="sita-stat-status">De <?= e($numeroDashboard($fichasTotal)) ?> fichas registradas</p>
                    <div class="sita-stat-meter"><span style="width: <?= e($anchoBarra($porcentaje($fichasActivas, $fichasTotal))) ?>%"></span></div>
                </article>

                <article class="sita-stat-card">
                    <div class="sita-stat-top">
                        <div class="sita-stat-icon purple"><?= iconoSita('bell') ?></div>
                        <?php if ($alertasPorVencer > 0): ?>
                            <span class="sita-stat-badge orange"><?= e($numeroDashboard($alertasPorVencer)) ?> por vencer</span>
                        <?php endif; ?>
                    </div>
                    <p class="sita-stat-label">Alertas vigentes</p>
                    <p class="sita-stat-value"><?= e($numeroDashboard($alertasActivas)) ?></p>
                    <p class="sita-stat-status">Dentro de su rango de fechas</p>
                </article>

                <article class="sita-stat-card">
                    <div class="sita-stat-top">
                        <div class="sita-stat-icon green"><?= iconoSita('check') ?></div>
                        <span class="sita-stat-badge green"><?= e($textoPorcentaje($pctAprobacion)) ?></span>
                    </div>
                    <p class="sita-stat-label">Resultados aprobados</p>
                    <p class="sita-stat-value"><?= e($numeroDashboard($resultadosAprobados)) ?></p>
                    <p class="sita-stat-status">De <?= e($numeroDashboard($resultadosTotal)) ?> resultados registrados</p>
                    <div class="sita-stat-meter green"><span style="width: <?= e($anchoBarra($pctAprobacion)) ?>%"></span></div>
                </article>

                <article class="sita-stat-card">
                    <div class="sita-stat-top">
                        <div class="sita-stat-icon orange"><?= iconoSita('clock') ?></div>
                    </div>
                    <p class="sita-stat-label">Seguimiento académico</p>
                    <p class="sita-stat-value"><?= e($numeroDashboard($resultadosPendientes)) ?></p>
                    <p class="sita-stat-status">Resultados pendientes · <?= e($numeroDashboard($resultadosNoAprobados)) ?> no aprobados</p>
                </article>

                <article class="sita-stat-card">
                    <div class="sita-stat-top">
                        <div class="sita-stat-icon blue"><?= iconoSita('clipboard') ?></div>
                        <span class="sita-stat-badge blue"><?= e($textoPorcentaje($pctCaracterizacion)) ?></span>
                    </div>
                    <p class="sita-stat-label">Caracterización actualizada</p>
                    <p class="sita-stat-value"><?= e($numeroDashboard($caracterizadosActualizados)) ?></p>
                    <p class="sita-stat-status"><?= e($numeroDashboard($caracterizadosPendientes)) ?> registros pendientes</p>
                    <div class="sita-stat-meter blue"><span style="width: <?= e($anchoBarra($pctCaracterizacion)) ?>%"></span></div>
                </article>

                <article class="sita-stat-card">
                    <div class="sita-stat-top">
                        <div class="sita-stat-icon turquoise"><?= iconoSita('heart') ?></div>
                        <span class="sita-stat-badge"><?= e($textoPorcentaje($pctCobertura)) ?> cobertura</span>
                    </div>
                    <p class="sita-stat-label">Cuestionarios de Bienestar</p>
                    <p class="sita-stat-value"><?= e($numeroDashboard($cuestionariosCompletados)) ?></p>
                    <p class="sita-stat-status">Completados · <?= e($numeroDashboard($cuestionariosBorrador)) ?> en proceso</p>
                    <div class="sita-stat-meter"><span style="width: <?= e($anchoBarra($pctCobertura)) ?>%"></span></div>
                </article>

                <article class="sita-stat-card">
                    <div class="sita-stat-top">
                        <div class="sita-stat-icon red"><?= iconoSita('alert') ?></div>
                        <span class="sita-stat-badge red"><?= e($textoPorcentaje($pctDesercion)) ?></span>
                    </div>
                    <p class="sita-stat-label">Cancelados y retiros</p>
                    <p class="sita-stat-value"><?= e($numeroDashboard($cancelados + $retiros)) ?></p>
                    <p class="sita-stat-status">
                        <?= e($numeroDashboard($cancelados)) ?> cancelados ·
                        <?= e($numeroDashboard($retiros)) ?> retiros ·
                        <?= e($numeroDashboard($aplazados)) ?> aplazados
                    </p>
                    <div class="sita-stat-meter red"><span style="width: <?= e($anchoBarra($pctDesercion)) ?>%"></span></div>
                </article>

            </section>

            <!-- ATENCIÓN PRIORITARIA + EVOLUCIÓN -->
            <section class="sita-dashboard-grid sita-block">

                <div class="sita-panel">
                    <div class="sita-panel-header">
                        <div>
                            <h3 class="sita-panel-title">Atención prioritaria</h3>
                            <p class="sita-panel-subtitle">Asuntos con casos pendientes según los datos actuales</p>
                        </div>
                    </div>

                    <div class="sita-panel-body">
                        <?php if ($prioridades === []): ?>
                            <div class="sita-ok-message">No hay asuntos pendientes por atender. Todo está al día.</div>
                        <?php else: ?>
                            <div class="sita-priority-list">
                                <?php foreach ($prioridades as $item): ?>
                                    <div class="sita-priority <?= e($item['nivel']) ?>">
                                        <span class="sita-priority-mark"></span>
                                        <div>
                                            <p class="sita-priority-title"><?= e($item['titulo']) ?></p>
                                            <p class="sita-priority-detail"><?= e($item['detalle']) ?></p>
                                        </div>
                                        <span class="sita-priority-total"><?= e($numeroDashboard($item['total'])) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="sita-panel">
                    <div class="sita-panel-header">
                        <div>
                            <h3 class="sita-panel-title">Cuestionarios completados por mes</h3>
                            <p class="sita-panel-subtitle">Últimos 6 meses</p>
                        </div>
                    </div>

                    <div class="sita-panel-body">
                        <div class="sita-month-chart" role="img" aria-label="Cuestionarios completados por mes">
                            <?php foreach ($serieMeses as $mes): ?>
                                <?php $alto = $mes['total'] > 0 ? max(4, ($mes['total'] / $maxMes) * 100) : 0; ?>
                                <div class="sita-month-col">
                                    <span class="sita-month-value"><?= e($numeroDashboard($mes['total'])) ?></span>
                                    <div
                                        class="sita-month-bar<?= $mes['total'] === 0 ? ' empty' : '' ?>"
                                        style="height: <?= e($anchoBarra((float) $alto)) ?>%"
                                    ></div>
                                    <span class="sita-month-label"><?= e($mes['etiqueta']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="sita-mini-stats">
                            <div class="sita-mini-stat">
                                <strong><?= e($numeroDashboard($totalSerieMeses)) ?></strong>
                                <span>En 6 meses</span>
                            </div>
                            <div class="sita-mini-stat">
                                <strong><?= e($numeroDashboard($aprendicesConCuestionario)) ?></strong>
                                <span>Aprendices con cuestionario</span>
                            </div>
                            <div class="sita-mini-stat">
                                <strong><?= e($numeroDashboard($cuestionariosRecientes)) ?></strong>
                                <span>Movimientos 30 días</span>
                            </div>
                        </div>
                    </div>
                </div>

            </section>

            <!-- ESTADO DE APRENDICES Y RESULTADOS -->
            <section class="sita-analytics-grid">

                <article class="sita-chart-panel">
                    <div class="sita-chart-header">
                        <h3 class="sita-chart-title">Estado de los aprendices</h3>
                        <p class="sita-chart-subtitle">Distribución según el estado de matrícula</p>
                    </div>

                    <div class="sita-chart-body">
                        <?php if ($segmentosEstados === []): ?>
                            <p class="sita-empty-chart">No hay registros de aprendices para mostrar.</p>
                        <?php else: ?>
                            <div class="sita-donut-wrap">
                                <div class="sita-donut" style="background: <?= e($degradadoCircular($segmentosEstados)) ?>">
                                    <div class="sita-donut-center">
                                        <strong><?= e($numeroDashboard($totalAprendices)) ?></strong>
                                        <span>aprendices</span>
                                    </div>
                                </div>

                                <div class="sita-legend">
                                    <?php foreach ($segmentosEstados as $segmento): ?>
                                        <div class="sita-legend-item">
                                            <span class="sita-legend-dot" style="background: <?= e($segmento['color']) ?>"></span>
                                            <span><?= e($segmento['etiqueta']) ?></span>
                                            <span>
                                                <strong><?= e($numeroDashboard($segmento['valor'])) ?></strong>
                                                <small><?= e($textoPorcentaje($porcentaje($segmento['valor'], $totalAprendices))) ?></small>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="sita-donut-footer">
                                <span>Permanencia: <strong><?= e($textoPorcentaje($pctPermanencia)) ?></strong></span>
                                <span>Cancelados y retiros: <strong><?= e($textoPorcentaje($pctDesercion)) ?></strong></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="sita-chart-panel">
                    <div class="sita-chart-header">
                        <h3 class="sita-chart-title">Resultados de aprendizaje</h3>
                        <p class="sita-chart-subtitle">Juicios registrados en los RAP</p>
                    </div>

                    <div class="sita-chart-body">
                        <?php if ($segmentosResultados === []): ?>
                            <p class="sita-empty-chart">No hay resultados de aprendizaje registrados.</p>
                        <?php else: ?>
                            <div class="sita-donut-wrap">
                                <div class="sita-donut" style="background: <?= e($degradadoCircular($segmentosResultados)) ?>">
                                    <div class="sita-donut-center">
                                        <strong><?= e($textoPorcentaje($pctAprobacion)) ?></strong>
                                        <span>aprobación</span>
                                    </div>
                                </div>

                                <div class="sita-legend">
                                    <?php foreach ($segmentosResultados as $segmento): ?>
                                        <div class="sita-legend-item">
                                            <span class="sita-legend-dot" style="background: <?= e($segmento['color']) ?>"></span>
                                            <span><?= e($segmento['etiqueta']) ?></span>
                                            <span>
                                                <strong><?= e($numeroDashboard($segmento['valor'])) ?></strong>
                                                <small><?= e($textoPorcentaje($porcentaje($segmento['valor'], $resultadosTotal))) ?></small>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="sita-donut-footer">
                                <span>Total de resultados: <strong><?= e($numeroDashboard($resultadosTotal)) ?></strong></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>

            </section>

            <!-- ACCESOS Y SESIÓN -->
            <section class="sita-dashboard-grid sita-block">

                <div class="sita-panel">
                    <div class="sita-panel-header">
                        <div>
                            <h3 class="sita-panel-title">Accesos rápidos</h3>
                            <p class="sita-panel-subtitle">Ir directamente a las áreas de SITA</p>
                        </div>
                    </div>

                    <div class="sita-panel-body">

                        <p class="sita-module-group-title">Gestión académica</p>
                        <div class="sita-module-grid">
                            <?php foreach ($modulosPrincipales as $modulo): ?>
                                <?php if (!$puede($modulo[4])) { continue; } ?>
                                <a href="<?= e(url($modulo[3])) ?>" class="sita-module">
                                    <div class="sita-module-icon"><?= iconoSita($modulo[2], 17) ?></div>
                                    <div>
                                        <p class="sita-module-name"><?= e($modulo[0]) ?></p>
                                        <p class="sita-module-description"><?= e($modulo[1]) ?></p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($consultasBienestar !== []): ?>
                            <p class="sita-module-group-title">Bienestar · Consultas por componente</p>
                            <div class="sita-module-grid">
                                <?php foreach ($consultasBienestar as $modulo): ?>
                                    <a href="<?= e(url($modulo[3])) ?>" class="sita-module">
                                        <div class="sita-module-icon"><?= iconoSita($modulo[2], 17) ?></div>
                                        <div>
                                            <p class="sita-module-name"><?= e($modulo[0]) ?></p>
                                            <p class="sita-module-description"><?= e($modulo[1]) ?></p>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($mostrarAdministracion): ?>
                            <p class="sita-module-group-title">Administración</p>
                            <div class="sita-module-grid">
                                <?php foreach ($modulosAdministracion as $modulo): ?>
                                    <a href="<?= e(url($modulo[3])) ?>" class="sita-module">
                                        <div class="sita-module-icon"><?= iconoSita($modulo[2], 17) ?></div>
                                        <div>
                                            <p class="sita-module-name"><?= e($modulo[0]) ?></p>
                                            <p class="sita-module-description"><?= e($modulo[1]) ?></p>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

                <div class="sita-panel">
                    <div class="sita-panel-header">
                        <div>
                            <h3 class="sita-panel-title">Su sesión</h3>
                            <p class="sita-panel-subtitle">Información de acceso</p>
                        </div>
                    </div>

                    <div class="sita-panel-body">
                        <div class="sita-session-list">
                            <div class="sita-session-item"><span>Usuario</span><strong><?= e($nombreUsuario) ?></strong></div>
                            <div class="sita-session-item">
                                <span>Rol<?= count($nombresRoles) > 1 ? 'es' : '' ?></span>
                                <strong><?= e($nombresRoles !== [] ? implode(', ', $nombresRoles) : $rolPrincipal) ?></strong>
                            </div>
                            <div class="sita-session-item"><span>Fecha de consulta</span><strong><?= e(date('d/m/Y H:i')) ?></strong></div>
                            <div class="sita-session-item"><span>Fichas activas</span><strong><?= e($numeroDashboard($fichasActivas)) ?> de <?= e($numeroDashboard($fichasTotal)) ?></strong></div>
                        </div>

                        <div class="sita-privacy">
                            <div class="sita-privacy-icon"><?= iconoSita('lock', 17) ?></div>
                            <div>
                                <p class="sita-privacy-title">Información protegida</p>
                                <p class="sita-privacy-text">
                                    SITA organiza el acceso a la información de acuerdo con las
                                    funciones y permisos de cada usuario.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </section>

            <!-- ESTADÍSTICAS DE SEGUIMIENTO -->
            <section class="sita-analytics">

                <div class="sita-section-heading">
                    <div>
                        <h2 class="sita-section-title">Estadísticas de seguimiento</h2>
                        <p class="sita-section-description">Distribución de aprendices, alertas y población registrados en la base de datos</p>
                    </div>
                </div>

                <p class="sita-analytics-note">
                    Los gráficos reflejan los registros disponibles actualmente en SITA.
                    Los datos personales no se muestran en estas estadísticas.
                </p>

                <div class="sita-analytics-grid">

                    <?php
                    $barras = [
                        ['Distribución por sexo', 'Información reportada en la ficha personal', $distribucionSexo, 'purple', 'No hay datos de sexo para mostrar.', 'Sin registrar'],
                        ['Aprendices por municipio', 'Hasta los ocho municipios con más registros', $aprendicesPorMunicipio, '', 'No hay municipios registrados para mostrar.', 'Sin registrar'],
                        ['Alertas vigentes por tipo', 'Solo alertas dentro de su rango de fechas', $alertasPorTipo, 'purple', 'No hay alertas vigentes para mostrar.', 'Otra'],
                        ['Caracterización de población', 'Tipos de población activos registrados', $distribucionPoblacion, 'blue', 'No hay caracterizaciones de población activas.', 'Sin clasificar'],
                    ];
                    ?>

                    <?php foreach ($barras as [$tituloBarra, $subtituloBarra, $datosBarra, $colorBarra, $vacioBarra, $etiquetaDefecto]): ?>
                        <?php
                        $totalBarra = 0;
                        $maxBarra = 1;

                        foreach ($datosBarra as $filaBarra) {
                            $valorBarra = (int) ($filaBarra['total'] ?? 0);
                            $totalBarra += $valorBarra;
                            $maxBarra = max($maxBarra, $valorBarra);
                        }
                        ?>
                        <article class="sita-chart-panel">
                            <div class="sita-chart-header">
                                <h3 class="sita-chart-title"><?= e($tituloBarra) ?></h3>
                                <p class="sita-chart-subtitle"><?= e($subtituloBarra) ?></p>
                            </div>

                            <div class="sita-chart-body">
                                <?php if ($datosBarra === []): ?>
                                    <p class="sita-empty-chart"><?= e($vacioBarra) ?></p>
                                <?php else: ?>
                                    <?php foreach ($datosBarra as $filaBarra): ?>
                                        <?php $valorFila = (int) ($filaBarra['total'] ?? 0); ?>
                                        <div class="sita-bar-row with-pct">
                                            <span class="sita-bar-label"><?= e((string) ($filaBarra['etiqueta'] ?? $etiquetaDefecto)) ?></span>
                                            <div class="sita-bar-track">
                                                <div class="sita-bar-fill <?= e($colorBarra) ?>" style="width: <?= e($anchoBarra(($valorFila / $maxBarra) * 100)) ?>%"></div>
                                            </div>
                                            <span class="sita-bar-value">
                                                <?= e($numeroDashboard($valorFila)) ?>
                                                <small><?= e($textoPorcentaje($porcentaje($valorFila, $totalBarra))) ?></small>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>

                </div>

                <article class="sita-chart-panel">
                    <div class="sita-chart-header">
                        <h3 class="sita-chart-title">Calidad de los datos de contacto</h3>
                        <p class="sita-chart-subtitle">Cantidad de aprendices con información pendiente de completar</p>
                    </div>

                    <div class="sita-chart-body">
                        <?php if ($totalCalidad === 0): ?>
                            <p class="sita-empty-chart">No hay aprendices registrados para evaluar la calidad de los datos.</p>
                        <?php else: ?>
                            <div class="sita-score">
                                <strong><?= e($textoPorcentaje($indiceCompletitud)) ?></strong>
                                <span>Índice de completitud: promedio de datos registrados<br>sobre <?= e($numeroDashboard($totalCalidad)) ?> aprendices.</span>
                            </div>

                            <?php foreach ($camposCalidad as $etiquetaCalidad => $cantidadCalidad): ?>
                                <?php $porcentajeCalidad = $porcentaje($cantidadCalidad, $totalCalidad); ?>
                                <div class="sita-quality-item">
                                    <div class="sita-quality-top">
                                        <span><?= e($etiquetaCalidad) ?></span>
                                        <strong><?= e($numeroDashboard($cantidadCalidad)) ?> (<?= e($textoPorcentaje($porcentajeCalidad)) ?>)</strong>
                                    </div>
                                    <div class="sita-bar-track">
                                        <div class="sita-bar-fill orange" style="width: <?= e($anchoBarra($porcentajeCalidad)) ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </article>

            </section>

            <footer class="sita-footer">
                <span><span class="sita-footer-brand">SITA</span> · Sistema de Trazabilidad de Aprendices</span>
                <span>Versión <?= e(versionAplicacion()) ?></span>
            </footer>

        </div>
    </main>
</div>

</body>
</html>