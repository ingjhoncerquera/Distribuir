<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Funciones auxiliares de esta vista
|--------------------------------------------------------------------------
*/

$fechaInicio = !empty($ficha['fecha_inicio'])
    ? new DateTime($ficha['fecha_inicio'])
    : null;

$fechaFin = !empty($ficha['fecha_fin'])
    ? new DateTime($ficha['fecha_fin'])
    : null;

$fechaHoy = new DateTime('today');

/*
 * La etapa práctica ocupa los últimos 6 meses
 * de la duración de la ficha.
 */
$fechaFinLectiva = null;
$fechaInicioPractica = null;

if ($fechaFin instanceof DateTime) {

    /*
     * Restar meses con modify('-6 months') desborda cuando el día no
     * existe en el mes destino (31 ago -> 31 feb -> 3 mar).
     * Se calcula el mes destino y se ajusta el día.
     */
    $diaFin = (int) $fechaFin->format('j');
    $esUltimoDiaMes = $diaFin === (int) $fechaFin->format('t');

    $fechaFinLectiva = clone $fechaFin;
    $fechaFinLectiva->modify('first day of this month');
    $fechaFinLectiva->modify('-6 months');

    $ultimoDiaDestino = (int) $fechaFinLectiva->format('t');

    $diaLectiva = $esUltimoDiaMes
        ? $ultimoDiaDestino
        : min($diaFin, $ultimoDiaDestino);

    $fechaFinLectiva->setDate(
        (int) $fechaFinLectiva->format('Y'),
        (int) $fechaFinLectiva->format('n'),
        $diaLectiva
    );

    // La etapa práctica inicia al día siguiente.
    $fechaInicioPractica = clone $fechaFinLectiva;
    $fechaInicioPractica->modify('+1 day');
}

/*
|--------------------------------------------------------------------------
| Tiempo restante de la ficha
|--------------------------------------------------------------------------
*/

$tiempoRestanteFicha = null;
$estadoTiempoFicha = '';

if ($fechaFin instanceof DateTime) {
    if ($fechaHoy < $fechaFin) {
        $diferencia = $fechaHoy->diff($fechaFin);

        $tiempoRestanteFicha = [
            'meses' => ($diferencia->y * 12) + $diferencia->m,
            'dias' => $diferencia->d,
            'total_dias' => $diferencia->days,
        ];

        $estadoTiempoFicha = 'vigente';
    } elseif ($fechaHoy->format('Y-m-d') === $fechaFin->format('Y-m-d')) {
        $tiempoRestanteFicha = [
            'meses' => 0,
            'dias' => 0,
            'total_dias' => 0,
        ];

        $estadoTiempoFicha = 'hoy';
    } else {
        $diferencia = $fechaFin->diff($fechaHoy);

        $tiempoRestanteFicha = [
            'meses' => ($diferencia->y * 12) + $diferencia->m,
            'dias' => $diferencia->d,
            'total_dias' => $diferencia->days,
        ];

        $estadoTiempoFicha = 'finalizada';
    }
}

/*
|--------------------------------------------------------------------------
| Tiempo restante para finalizar la etapa lectiva
|--------------------------------------------------------------------------
*/

$tiempoRestanteLectiva = null;
$estadoTiempoLectiva = '';

if ($fechaFinLectiva instanceof DateTime) {
    if ($fechaHoy < $fechaFinLectiva) {
        $diferenciaLectiva = $fechaHoy->diff($fechaFinLectiva);

        $tiempoRestanteLectiva = [
            'meses' => ($diferenciaLectiva->y * 12) + $diferenciaLectiva->m,
            'dias' => $diferenciaLectiva->d,
            'total_dias' => $diferenciaLectiva->days,
        ];

        $estadoTiempoLectiva = 'vigente';
    } elseif (
        $fechaHoy->format('Y-m-d') ===
        $fechaFinLectiva->format('Y-m-d')
    ) {
        $tiempoRestanteLectiva = [
            'meses' => 0,
            'dias' => 0,
            'total_dias' => 0,
        ];

        $estadoTiempoLectiva = 'hoy';
    } else {
        $diferenciaLectiva = $fechaFinLectiva->diff($fechaHoy);

        $tiempoRestanteLectiva = [
            'meses' => ($diferenciaLectiva->y * 12) + $diferenciaLectiva->m,
            'dias' => $diferenciaLectiva->d,
            'total_dias' => $diferenciaLectiva->days,
        ];

        $estadoTiempoLectiva = 'finalizada';
    }
}

/*
|--------------------------------------------------------------------------
| Porcentaje de tiempo transcurrido de la etapa lectiva
|--------------------------------------------------------------------------
|
| IMPORTANTE:
| El 100 % corresponde solamente al periodo lectivo:
|
| fecha_inicio → fecha_fin - 6 meses
|
| Una vez inicia la etapa práctica, este porcentaje permanece en 100 %.
|--------------------------------------------------------------------------
*/

$porcentajeTiempoFicha = 0;

if (
    $fechaInicio instanceof DateTime &&
    $fechaFinLectiva instanceof DateTime
) {

    $duracionLectivaDias = (int) $fechaInicio
        ->diff($fechaFinLectiva)
        ->format('%r%a');

    if ($duracionLectivaDias > 0) {

        $transcurridoDias = (int) $fechaInicio
            ->diff($fechaHoy)
            ->format('%r%a');

        $porcentajeTiempoFicha = max(
            0,
            min(
                100,
                ($transcurridoDias / $duracionLectivaDias) * 100
            )
        );

    } elseif ($fechaHoy >= $fechaFinLectiva) {

        $porcentajeTiempoFicha = 100;
    }
}

/*
|--------------------------------------------------------------------------
| Resumen académico
|--------------------------------------------------------------------------
*/

$totalEnFormacion = (int) (
    $resumenAcademico['total_en_formacion'] ?? 0
);

$totalEtapaLectiva = (int) (
    $resumenAcademico['total_etapa_lectiva'] ?? 0
);

$totalEtapaPractica = (int) (
    $resumenAcademico['total_etapa_practica'] ?? 0
);

$promedioAvance = (float) (
    $resumenAcademico['promedio_avance_lectivo'] ?? 0
);

$promedioAvance = max(
    0,
    min(100, $promedioAvance)
);

/*
|--------------------------------------------------------------------------
| Resumen de aprendices
|--------------------------------------------------------------------------
*/

$totalAprendices = (int) (
    $resumenAprendices['total'] ?? 0
);

$totalFormacion = (int) (
    $resumenAprendices['en_formacion'] ?? 0
);

$totalAplazados = (int) (
    $resumenAprendices['aplazados'] ?? 0
);

$totalRetiro = (int) (
    $resumenAprendices['retiro_voluntario'] ?? 0
);

$totalCancelados = (int) (
    $resumenAprendices['cancelados'] ?? 0
);

/*
|--------------------------------------------------------------------------
| Mayor avance individual
|--------------------------------------------------------------------------
|
| Se calcula sobre el porcentaje de RAP lectivos aprobados.
| No se utiliza el número de RAP evaluados como criterio de avance.
|--------------------------------------------------------------------------
*/

$mayorAvance = 0;
$aprendizMayorAvance = null;

foreach ($avanceAprendices as $avance) {
    $avanceIndividual = (float) (
        $avance['porcentaje_avance_lectivo'] ?? 0
    );

    $avanceIndividual = max(
        0,
        min(100, $avanceIndividual)
    );

    if (
        $aprendizMayorAvance === null ||
        $avanceIndividual > $mayorAvance
    ) {
        $mayorAvance = $avanceIndividual;
        $aprendizMayorAvance = $avance;
    }
}

$mayorAvanceTexto = number_format(
    $mayorAvance,
    1,
    ',',
    '.'
);

/*
|--------------------------------------------------------------------------
| Funciones de presentación
|--------------------------------------------------------------------------
*/

$estadoFicha = strtolower(
    trim((string) ($ficha['estado'] ?? ''))
);

$estadoFichaTexto = $ficha['estado'] ?? 'Sin estado';

if ($estadoFicha === 'activa') {
    $estadoFichaTexto = 'Activa';
} elseif ($estadoFicha === 'inactiva') {
    $estadoFichaTexto = 'Inactiva';
} elseif ($estadoFicha === 'finalizada') {
    $estadoFichaTexto = 'Finalizada';
}

$nombreFicha = trim(
    (string) ($ficha['numero_ficha'] ?? '')
);

$nombrePrograma = trim(
    (string) ($ficha['nombre_programa'] ?? '')
);

$codigoPrograma = trim(
    (string) ($ficha['codigo_programa'] ?? '')
);

$nivelPrograma = trim(
    (string) ($ficha['nivel'] ?? '')
);

$modalidad = trim(
    (string) ($ficha['modalidad'] ?? '')
);

$regional = trim(
    (string) ($ficha['regional'] ?? '')
);

$centro = trim(
    (string) ($ficha['centro'] ?? '')
);

$versionPrograma = trim(
    (string) ($ficha['version_programa'] ?? '')
);

$duracionMeses = (int) (
    $ficha['duracion_meses'] ?? 0
);

$fechaInicioTexto = $fechaInicio
    ? $fechaInicio->format('d/m/Y')
    : 'No registrada';

$fechaFinTexto = $fechaFin
    ? $fechaFin->format('d/m/Y')
    : 'No registrada';

$fechaFinLectivaTexto = $fechaFinLectiva
    ? $fechaFinLectiva->format('d/m/Y')
    : 'No disponible';

$fechaInicioPracticaTexto = $fechaInicioPractica
    ? $fechaInicioPractica->format('d/m/Y')
    : 'No disponible';

$porcentajeTiempoTexto = number_format(
    $porcentajeTiempoFicha,
    1,
    ',',
    '.'
);

$promedioAvanceTexto = number_format(
    $promedioAvance,
    1,
    ',',
    '.'
);

/*
|--------------------------------------------------------------------------
| Líder de ficha
|--------------------------------------------------------------------------
*/

$nombreLiderFicha = '';

if (!empty($liderFicha)) {
    $nombreLiderFicha = trim(
        (string) (
            ($liderFicha['nombres'] ?? '')
            . ' '
            . ($liderFicha['apellidos'] ?? '')
        )
    );
}

/*
|--------------------------------------------------------------------------
| Cantidad de instructores evaluadores
|--------------------------------------------------------------------------
*/

$totalInstructoresEvaluadores = count(
    $instructoresEvaluadores ?? []
);

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../layout/navbar.php';
?>

<style>
    /*
    |--------------------------------------------------------------------------
    | Ficha - Detalle
    |--------------------------------------------------------------------------
    | Todo el CSS está aislado dentro de .ficha-detalle
    | para no afectar las demás vistas.
    |--------------------------------------------------------------------------
    */

    .ficha-detalle {
        max-width: 1500px;
        margin: 0 auto;
        padding: 28px 24px 45px;
    }

    .ficha-detalle * {
        box-sizing: border-box;
    }

    .ficha-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 18px;
        font-size: 13px;
        color: #718096;
    }

    .ficha-breadcrumb a {
        color: #4a5568;
        text-decoration: none;
    }

    .ficha-breadcrumb a:hover {
        color: #1f6f43;
    }

    .ficha-breadcrumb-separador {
        color: #a0aec0;
    }

    /*
    |--------------------------------------------------------------------------
    | Encabezado
    |--------------------------------------------------------------------------
    */

    .ficha-hero {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 25px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 26px 28px;
        margin-bottom: 20px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
    }

    .ficha-hero-principal {
        min-width: 0;
    }

    .ficha-hero-etiqueta {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 9px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #6b7280;
    }

    .ficha-hero-titulo {
        margin: 0;
        font-size: 30px;
        line-height: 1.15;
        font-weight: 750;
        color: #17202a;
    }

    .ficha-hero-programa {
        margin: 8px 0 0;
        font-size: 17px;
        line-height: 1.5;
        color: #4b5563;
        max-width: 850px;
    }

    .ficha-hero-codigo {
        margin-top: 9px;
        font-size: 13px;
        color: #6b7280;
    }

    .ficha-hero-codigo strong {
        color: #374151;
    }

    .ficha-estado {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 14px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 700;
        background: #ecfdf3;
        color: #17663c;
        border: 1px solid #c9efd8;
    }

    .ficha-estado::before {
        content: '';
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
    }

    /*
    |--------------------------------------------------------------------------
    | Botón volver
    |--------------------------------------------------------------------------
    */

    .ficha-volver {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        height: 38px;
        padding: 0 14px;
        margin-bottom: 20px;
        border: 1px solid #d9dee5;
        border-radius: 9px;
        background: #ffffff;
        color: #374151;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        transition: all .18s ease;
    }

    .ficha-volver:hover {
        background: #f8fafc;
        border-color: #bfc7d2;
        color: #1f2937;
    }

    /*
    |--------------------------------------------------------------------------
    | Tarjetas generales
    |--------------------------------------------------------------------------
    */

    .ficha-grid {
        display: grid;
        gap: 18px;
    }

    .ficha-grid-4 {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .ficha-grid-3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .ficha-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        box-shadow: 0 3px 14px rgba(15, 23, 42, 0.04);
    }

    .ficha-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        padding: 20px 22px 16px;
        border-bottom: 1px solid #eef0f3;
    }

    .ficha-card-titulo {
        margin: 0;
        font-size: 16px;
        font-weight: 750;
        color: #1f2937;
    }

    .ficha-card-subtitulo {
        margin: 4px 0 0;
        font-size: 12px;
        color: #7b8491;
    }

    .ficha-card-body {
        padding: 20px 22px;
    }

    /*
    |--------------------------------------------------------------------------
    | Información general
    |--------------------------------------------------------------------------
    */

    .ficha-info-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 18px 25px;
    }

    .ficha-info-item {
        min-width: 0;
    }

    .ficha-info-label {
        margin-bottom: 5px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #8a94a3;
    }

    .ficha-info-value {
        font-size: 14px;
        line-height: 1.4;
        color: #26313d;
        font-weight: 600;
        word-break: break-word;
    }

    /*
    |--------------------------------------------------------------------------
    | Estadísticas
    |--------------------------------------------------------------------------
    */

    .ficha-stat {
        position: relative;
        padding: 20px;
        min-height: 125px;
        overflow: hidden;
    }

    .ficha-stat::after {
        content: '';
        position: absolute;
        right: -22px;
        bottom: -28px;
        width: 90px;
        height: 90px;
        border-radius: 50%;
        background: #f5f7f9;
    }

    .ficha-stat-label {
        position: relative;
        z-index: 1;
        font-size: 12px;
        font-weight: 700;
        color: #7b8491;
    }

    .ficha-stat-numero {
        position: relative;
        z-index: 1;
        margin-top: 8px;
        font-size: 31px;
        line-height: 1;
        font-weight: 800;
        color: #202a34;
    }

    .ficha-stat-detalle {
        position: relative;
        z-index: 1;
        margin-top: 8px;
        font-size: 12px;
        color: #8a94a3;
    }

    /*
    |--------------------------------------------------------------------------
    | Línea de tiempo
    |--------------------------------------------------------------------------
    */

    .ficha-tiempo {
        margin-top: 18px;
        padding: 17px 18px;
        border: 1px solid #e5e9ed;
        border-radius: 12px;
        background: #fafbfc;
    }

    .ficha-tiempo-titulo {
        margin-bottom: 10px;
        font-size: 13px;
        font-weight: 750;
        color: #374151;
    }

    .ficha-tiempo-fechas {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 9px;
        font-size: 12px;
        color: #6b7280;
    }

    .ficha-tiempo-fechas strong {
        color: #374151;
    }

    .ficha-barra {
        width: 100%;
        height: 22px;
        overflow: hidden;
        border-radius: 999px;
        background: #fed7aa;
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, .12);
    }

    .ficha-barra-progreso {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: 9px;
        border-radius: inherit;
        background: linear-gradient(90deg, #3b82f6, #1d4ed8);
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        transition: width .25s ease;
    }

    .ficha-barra-leyenda {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 18px;
        margin-top: 8px;
        font-size: 11px;
        color: #6b7280;
    }

    .ficha-barra-leyenda span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .ficha-barra-leyenda i {
        width: 11px;
        height: 11px;
        border-radius: 50%;
        display: inline-block;
    }

    .ficha-tiempo-centro {
        margin-top: 8px;
        text-align: center;
        font-size: 12px;
        font-weight: 700;
        color: #59636f;
    }

    /*
    |--------------------------------------------------------------------------
    | Comparación tiempo / avance académico
    |--------------------------------------------------------------------------
    */

    .ficha-comparacion {
        display: grid;
        gap: 18px;
        padding: 18px;
        border: 1px solid #e7eaee;
        border-radius: 13px;
        background: #fbfcfd;
    }

    .ficha-comparacion-intro {
        margin-bottom: 2px;
    }

    .ficha-comparacion-intro h3 {
        margin: 0;
        font-size: 14px;
        font-weight: 750;
        color: #374151;
    }

    .ficha-comparacion-intro p {
        margin: 5px 0 0;
        font-size: 12px;
        line-height: 1.5;
        color: #7b8491;
    }

    .ficha-comparacion-item {
        padding: 15px 16px;
        border: 1px solid #e7eaee;
        border-radius: 11px;
        background: #ffffff;
    }

    .ficha-comparacion-cabecera {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 9px;
    }

    .ficha-comparacion-titulo {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 750;
        color: #374151;
    }

    .ficha-comparacion-valor {
        font-size: 13px;
        font-weight: 800;
        color: #27313c;
        white-space: nowrap;
    }

    .ficha-comparacion-barra {
        width: 100%;
        height: 13px;
        border-radius: 999px;
        overflow: hidden;
        background: #e5e7eb;
    }

    /*
    | Colores suaves:
    | Azul claro  = avance académico
    | Lila suave  = tiempo lectivo
    | Verde suave = mayor avance individual
    */

    .ficha-comparacion-barra-dual {
        height: 16px;
        background: #fed7aa;
    }

    .ficha-indicador-restante {
        background: #fdba74;
    }

    .ficha-comparacion-progreso {
        height: 100%;
        border-radius: inherit;
        transition: width .35s ease;
    }

    /* Azul = tiempo · Verde = avance promedio · Naranja = mayor avance */
    .ficha-comparacion-progreso-academico {
        background: linear-gradient(90deg, #4ade80, #16a34a);
    }

    .ficha-comparacion-progreso-tiempo {
        background: linear-gradient(90deg, #60a5fa, #2563eb);
    }

    .ficha-comparacion-progreso-mayor {
        background: linear-gradient(90deg, #fbbf24, #ea580c);
    }

    .ficha-comparacion-escala {
        display: flex;
        justify-content: space-between;
        margin-top: 5px;
        font-size: 10px;
        color: #a0a7af;
    }

    .ficha-comparacion-referencia {
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
        padding-top: 13px;
        border-top: 1px solid #edf0f2;
        font-size: 11px;
        color: #6b7280;
    }

    .ficha-comparacion-referencia > div {
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .ficha-indicador {
        width: 11px;
        height: 11px;
        display: inline-block;
        border-radius: 50%;
    }

    .ficha-indicador-avance {
        background: #16a34a;
    }

    .ficha-indicador-tiempo {
        background: #2563eb;
    }

    .ficha-indicador-mayor {
        background: #ea580c;
    }

    .ficha-comparacion-nota {
        margin-top: 14px;
        font-size: 12px;
        line-height: 1.55;
        color: #707b87;
    }

    .ficha-comparacion-nota strong {
        color: #374151;
    }

    /*
    |--------------------------------------------------------------------------
    | Etapas
    |--------------------------------------------------------------------------
    */

    .ficha-etapas {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .ficha-etapa {
        padding: 17px;
        border: 1px solid #e6e9ed;
        border-radius: 12px;
        background: #fafbfc;
    }

    .ficha-etapa-activa {
        border-color: #cfe4d8;
        background: #f7fbf8;
    }

    .ficha-etapa-titulo {
        margin: 0 0 7px;
        font-size: 13px;
        font-weight: 750;
        color: #2d3748;
    }

    .ficha-etapa-fecha {
        font-size: 14px;
        font-weight: 700;
        color: #1f2937;
    }

    .ficha-etapa-detalle {
        margin-top: 5px;
        font-size: 12px;
        line-height: 1.45;
        color: #737d89;
    }

    /*
    |--------------------------------------------------------------------------
    | Avance académico
    |--------------------------------------------------------------------------
    */

    .ficha-avance-principal {
        display: flex;
        align-items: center;
        gap: 22px;
    }

    .ficha-avance-circulo {
        position: relative;
        width: 115px;
        height: 115px;
        flex: 0 0 115px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background:
            conic-gradient(
                #8fc5a7 0deg,
                #8fc5a7 <?= ($promedioAvance * 3.6) ?>deg,
                #edf0f3 <?= ($promedioAvance * 3.6) ?>deg,
                #edf0f3 360deg
            );
    }

    .ficha-avance-circulo::before {
        content: '';
        position: absolute;
        width: 88px;
        height: 88px;
        border-radius: 50%;
        background: #ffffff;
    }

    .ficha-avance-circulo-texto {
        position: relative;
        z-index: 1;
        font-size: 23px;
        font-weight: 800;
        color: #25313c;
    }

    .ficha-avance-descripcion {
        min-width: 0;
    }

    .ficha-avance-descripcion strong {
        display: block;
        margin-bottom: 6px;
        font-size: 15px;
        color: #27313c;
    }

    .ficha-avance-descripcion p {
        margin: 0;
        font-size: 13px;
        line-height: 1.55;
        color: #707b87;
    }

    .ficha-academico-mini {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-top: 18px;
    }

    .ficha-academico-mini-item {
        padding: 13px 14px;
        border-radius: 10px;
        background: #f8fafb;
        border: 1px solid #edf0f2;
    }

    .ficha-academico-mini-item span {
        display: block;
        font-size: 11px;
        color: #7a8490;
    }

    .ficha-academico-mini-item strong {
        display: block;
        margin-top: 4px;
        font-size: 20px;
        color: #27313c;
    }

    /*
    |--------------------------------------------------------------------------
    | Buscadores
    |--------------------------------------------------------------------------
    */

    .ficha-tabla-herramientas {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        padding: 14px 18px;
        border-bottom: 1px solid #eef0f2;
        background: #fcfdfd;
    }

    .ficha-buscador {
        position: relative;
        width: min(360px, 100%);
    }

    .ficha-buscador-icono {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #8a94a0;
        font-size: 14px;
        pointer-events: none;
    }

    .ficha-buscador-input {
        width: 100%;
        height: 38px;
        padding: 0 13px 0 35px;
        border: 1px solid #dfe4e8;
        border-radius: 9px;
        outline: none;
        background: #ffffff;
        color: #26313d;
        font-size: 12px;
        transition: border-color .18s ease, box-shadow .18s ease;
    }

    .ficha-buscador-input::placeholder {
        color: #9aa3ad;
    }

    .ficha-buscador-input:focus {
        border-color: #9fc6ad;
        box-shadow: 0 0 0 3px rgba(55, 125, 87, .08);
    }

    .ficha-tabla-contador {
        flex-shrink: 0;
        font-size: 11px;
        color: #7b8491;
    }

    .ficha-sin-resultados {
        display: none;
        padding: 28px 20px;
        text-align: center;
        color: #7b8491;
        font-size: 12px;
    }

    /*
    |--------------------------------------------------------------------------
    | Tabla
    |--------------------------------------------------------------------------
    */

    .ficha-tabla-contenedor {
        overflow-x: auto;
    }

    .ficha-tabla {
        width: 100%;
        border-collapse: collapse;
        min-width: 920px;
    }

    .ficha-tabla th {
        padding: 12px 14px;
        background: #f8fafb;
        border-bottom: 1px solid #e4e8ec;
        color: #707a86;
        font-size: 11px;
        font-weight: 750;
        text-align: left;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
    }

    .ficha-tabla td {
        padding: 14px;
        border-bottom: 1px solid #eef0f2;
        color: #3b4652;
        font-size: 13px;
        vertical-align: middle;
    }

    .ficha-tabla tbody tr:last-child td {
        border-bottom: 0;
    }

    .ficha-tabla tbody tr:hover {
        background: #fafcfc;
    }

    .ficha-aprendiz-nombre {
        font-weight: 700;
        color: #26313d;
    }

    .ficha-aprendiz-documento {
        margin-top: 3px;
        font-size: 11px;
        color: #8a94a0;
    }

    /*
    |--------------------------------------------------------------------------
    | Badges
    |--------------------------------------------------------------------------
    */

    .ficha-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 5px 9px;
        border-radius: 7px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

    .ficha-badge-verde {
        color: #17663c;
        background: #edf8f1;
    }

    .ficha-badge-azul {
        color: #275a8a;
        background: #eef6fd;
    }

    .ficha-badge-amarillo {
        color: #87651c;
        background: #fff8e5;
    }

    .ficha-badge-rojo {
        color: #9a3434;
        background: #fff0f0;
    }

    .ficha-badge-gris {
        color: #626b75;
        background: #f1f3f5;
    }

    /*
    |--------------------------------------------------------------------------
    | Mini barra de avance
    |--------------------------------------------------------------------------
    */

    .ficha-avance-mini {
        min-width: 150px;
    }

    .ficha-avance-mini-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 6px;
        font-size: 11px;
    }

    .ficha-avance-mini-top strong {
        color: #374151;
    }

    .ficha-avance-mini-top span {
        color: #737d89;
    }

    .ficha-avance-mini-barra {
        width: 100%;
        height: 7px;
        border-radius: 999px;
        overflow: hidden;
        background: #edf0f3;
    }

    .ficha-avance-mini-progreso {
        height: 100%;
        border-radius: inherit;
        background: #8fc5a7;
    }

    /*
    |--------------------------------------------------------------------------
    | Líder de ficha
    |--------------------------------------------------------------------------
    */

    .ficha-lider {
        display: flex;
        align-items: center;
        gap: 17px;
        padding: 18px 20px;
        border: 1px solid #dce9e1;
        border-radius: 13px;
        background: #f7fbf8;
    }

    .ficha-lider-avatar {
        width: 52px;
        height: 52px;
        flex: 0 0 52px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #e3f1e8;
        color: #2f704d;
        font-size: 16px;
        font-weight: 800;
    }

    .ficha-lider-contenido {
        min-width: 0;
        flex: 1;
    }

    .ficha-lider-etiqueta {
        margin-bottom: 4px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: #5f826d;
    }

    .ficha-lider-nombre {
        font-size: 15px;
        font-weight: 800;
        color: #263d30;
    }

    .ficha-lider-detalle {
        margin-top: 4px;
        font-size: 12px;
        color: #718079;
    }

    /*
    |--------------------------------------------------------------------------
    | Profesionales
    |--------------------------------------------------------------------------
    */

    .ficha-profesional {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .ficha-profesional-avatar {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #eef4f0;
        color: #377d57;
        font-size: 14px;
        font-weight: 800;
    }

    .ficha-profesional-nombre {
        font-weight: 700;
        color: #28333e;
    }

    .ficha-profesional-detalle {
        margin-top: 3px;
        font-size: 11px;
        color: #7a8490;
    }

    /*
    |--------------------------------------------------------------------------
    | Estado vacío
    |--------------------------------------------------------------------------
    */

    .ficha-vacio {
        padding: 35px 20px;
        text-align: center;
        color: #7b8491;
        font-size: 13px;
    }

    .ficha-vacio-icono {
        margin-bottom: 8px;
        font-size: 25px;
        opacity: .65;
    }

    /*
    |--------------------------------------------------------------------------
    | Separadores
    |--------------------------------------------------------------------------
    */

    .ficha-seccion {
        margin-top: 20px;
    }

    /*
    |--------------------------------------------------------------------------
    | Responsive
    |--------------------------------------------------------------------------
    */

    @media (max-width: 1100px) {
        .ficha-grid-4 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .ficha-info-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 800px) {
        .ficha-detalle {
            padding: 20px 15px 35px;
        }

        .ficha-hero {
            flex-direction: column;
        }

        .ficha-estado {
            align-self: flex-start;
        }

        .ficha-grid-3 {
            grid-template-columns: 1fr;
        }

        .ficha-etapas {
            grid-template-columns: 1fr;
        }

        .ficha-avance-principal {
            align-items: flex-start;
            flex-direction: column;
        }

        .ficha-tabla-herramientas {
            align-items: stretch;
            flex-direction: column;
        }

        .ficha-buscador {
            width: 100%;
        }

        .ficha-tabla-contador {
            text-align: left;
        }
    }

    @media (max-width: 600px) {
        .ficha-grid-4 {
            grid-template-columns: 1fr;
        }

        .ficha-info-grid {
            grid-template-columns: 1fr;
        }

        .ficha-hero-titulo {
            font-size: 25px;
        }

        .ficha-card-header,
        .ficha-card-body {
            padding-left: 16px;
            padding-right: 16px;
        }

        .ficha-lider {
            align-items: flex-start;
        }

        .ficha-comparacion {
            padding: 13px;
        }

        .ficha-comparacion-cabecera {
            align-items: flex-start;
            flex-direction: column;
            gap: 5px;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Paginación de tablas
    |--------------------------------------------------------------------------
    */
    .ficha-paginacion {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 18px;
        border-top: 1px solid #edf0f2;
        font-size: 12px;
        color: #6b7280;
    }

    .ficha-paginacion-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .ficha-paginacion select {
        padding: 4px 8px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: #ffffff;
        font-size: 12px;
        color: #374151;
    }

    .ficha-paginacion-botones {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }

    .ficha-paginacion-botones button {
        min-width: 32px;
        height: 32px;
        padding: 0 10px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: #ffffff;
        color: #374151;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
    }

    .ficha-paginacion-botones button:hover:not(:disabled) {
        background: #eff6ff;
        border-color: #93c5fd;
    }

    .ficha-paginacion-botones button.activo {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
    }

    .ficha-paginacion-botones button:disabled {
        opacity: .45;
        cursor: default;
    }

    .ficha-paginacion-puntos {
        align-self: center;
        padding: 0 4px;
    }

</style>

<div class="ficha-detalle">

    <!-- =========================================================
         NAVEGACIÓN
         ========================================================= -->

    <div class="ficha-breadcrumb">
        <a href="<?= e(url('/fichas')) ?>">Fichas</a>
        <span class="ficha-breadcrumb-separador">/</span>
        <span>Detalle</span>
    </div>

    <a
        href="<?= e(url('/fichas')) ?>"
        class="ficha-volver"
    >
        ← Volver a fichas
    </a>

    <!-- =========================================================
         ENCABEZADO
         ========================================================= -->

    <section class="ficha-hero">

        <div class="ficha-hero-principal">

            <div class="ficha-hero-etiqueta">
                Ficha de formación
            </div>

            <h1 class="ficha-hero-titulo">
                Ficha <?= e($nombreFicha) ?>
            </h1>

            <p class="ficha-hero-programa">
                <?= e($nombrePrograma) ?>
            </p>

            <div class="ficha-hero-codigo">
                Código del programa:
                <strong>
                    <?= e($codigoPrograma !== '' ? $codigoPrograma : 'No registrado') ?>
                </strong>

                <?php if ($nivelPrograma !== ''): ?>
                    &nbsp; · &nbsp;
                    Nivel:
                    <strong><?= e($nivelPrograma) ?></strong>
                <?php endif; ?>
            </div>

        </div>

        <div class="ficha-estado">
            <?= e($estadoFichaTexto) ?>
        </div>

    </section>
	
	
    <!-- =========================================================
         CUESTIONARIO DE BIENESTAR
    ========================================================== -->

    <section class="ficha-card" style="margin-bottom: 20px;">
        <div class="ficha-card-header">
            <div>
                <h2 class="ficha-card-titulo">
                    Cuestionario de Bienestar
                </h2>

                <p class="ficha-card-subtitulo">
                    Genere un enlace único para compartir con los
                    aprendices de esta ficha.
                </p>
            </div>
        </div>

        <div class="ficha-card-body">

            <form
                method="POST"
                action="<?= e(url('/fichas/generar-enlace-bienestar')) ?>"
            >
                <?= csrfField() ?>

                <input
                    type="hidden"
                    name="id_ficha"
                    value="<?= (int) $ficha['id'] ?>"
                >

                <button
                    type="submit"
                    class="btn btn-primary"
                    style="border-radius: 8px; padding: 10px 18px;"
                >
                    Generar / obtener enlace
                </button>
            </form>

            <?php if (!empty($enlaceBienestar)): ?>
                <div
                    style="
                        margin-top: 20px;
                        padding: 16px;
                        background: #f0f9ff;
                        border: 1px solid #bae6fd;
                        border-radius: 10px;
                    "
                >
                    <label
                        for="enlaceBienestar"
                        style="
                            display: block;
                            margin-bottom: 8px;
                            font-weight: 700;
                            color: #075985;
                        "
                    >
                        Enlace público para los aprendices
                    </label>

                    <div
                        style="
                            display: flex;
                            flex-wrap: wrap;
                            gap: 10px;
                        "
                    >
                        <input
                            type="text"
                            id="enlaceBienestar"
                            value="<?= e($enlaceBienestar) ?>"
                            readonly
                            class="form-control"
                            style="flex: 1; min-width: 240px;"
                        >

                        <button
                            type="button"
                            class="btn btn-outline-primary"
                            id="copiarEnlaceBienestar"
                        >
                            Copiar enlace
                        </button>
                    </div>

                    <p
                        id="mensajeCopiaBienestar"
                        role="status"
                        style="margin: 10px 0 0; font-size: 13px;"
                    >
                        Comparta esta URL con los aprendices de la ficha.
                    </p>
                </div>
            <?php endif; ?>

        </div>
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const boton = document.getElementById('copiarEnlaceBienestar');
        const campo = document.getElementById('enlaceBienestar');
        const mensaje = document.getElementById('mensajeCopiaBienestar');

        if (!boton || !campo || !mensaje) {
            return;
        }

        boton.addEventListener('click', async function () {
            try {
                await navigator.clipboard.writeText(campo.value);
                mensaje.textContent = 'Enlace copiado correctamente.';
            } catch (error) {
                campo.focus();
                campo.select();

                const copiado = document.execCommand('copy');

                mensaje.textContent = copiado
                    ? 'Enlace copiado correctamente.'
                    : 'Seleccione el enlace y cópielo manualmente.';
            }
        });
    });
    </script>

    <!-- =========================================================
         INFORMACIÓN GENERAL
         ========================================================= -->

    <section class="ficha-card ficha-seccion">

        <div class="ficha-card-header">
            <div>
                <h2 class="ficha-card-titulo">
                    Información general
                </h2>

                <p class="ficha-card-subtitulo">
                    Datos registrados de la ficha de formación.
                </p>
            </div>
        </div>

        <div class="ficha-card-body">

            <div class="ficha-info-grid">

                <div class="ficha-info-item">
                    <div class="ficha-info-label">
                        Número de ficha
                    </div>

                    <div class="ficha-info-value">
                        <?= e($nombreFicha) ?>
                    </div>
                </div>

                <div class="ficha-info-item">
                    <div class="ficha-info-label">
                        Código del programa
                    </div>

                    <div class="ficha-info-value">
                        <?= e($codigoPrograma ?: 'No registrado') ?>
                    </div>
                </div>

                <div class="ficha-info-item">
                    <div class="ficha-info-label">
                        Versión del programa
                    </div>

                    <div class="ficha-info-value">
                        <?= e($versionPrograma ?: 'No registrada') ?>
                    </div>
                </div>

                <div class="ficha-info-item">
                    <div class="ficha-info-label">
                        Modalidad
                    </div>

                    <div class="ficha-info-value">
                        <?= e($modalidad ?: 'No registrada') ?>
                    </div>
                </div>

                <div class="ficha-info-item">
                    <div class="ficha-info-label">
                        Regional
                    </div>

                    <div class="ficha-info-value">
                        <?= e($regional ?: 'No registrada') ?>
                    </div>
                </div>

                <div class="ficha-info-item">
                    <div class="ficha-info-label">
                        Centro
                    </div>

                    <div class="ficha-info-value">
                        <?= e($centro ?: 'No registrado') ?>
                    </div>
                </div>

                <div class="ficha-info-item">
                    <div class="ficha-info-label">
                        Inicio de ficha
                    </div>

                    <div class="ficha-info-value">
                        <?= e($fechaInicioTexto) ?>
                    </div>
                </div>

                <div class="ficha-info-item">
                    <div class="ficha-info-label">
                        Finalización de ficha
                    </div>

                    <div class="ficha-info-value">
                        <?= e($fechaFinTexto) ?>
                    </div>
                </div>

            </div>

        </div>

    </section>

    <!-- =========================================================
         ESTADÍSTICAS DE APRENDICES
         ========================================================= -->

    <section class="ficha-grid ficha-grid-4 ficha-seccion">

        <div class="ficha-card ficha-stat">
            <div class="ficha-stat-label">
                Total aprendices
            </div>

            <div class="ficha-stat-numero">
                <?= $totalAprendices ?>
            </div>

            <div class="ficha-stat-detalle">
                Matriculados en la ficha
            </div>
        </div>

        <div class="ficha-card ficha-stat">
            <div class="ficha-stat-label">
                En formación
            </div>

            <div class="ficha-stat-numero">
                <?= $totalFormacion ?>
            </div>

            <div class="ficha-stat-detalle">
                Participan en el avance académico
            </div>
        </div>

        <div class="ficha-card ficha-stat">
            <div class="ficha-stat-label">
                Aplazados
            </div>

            <div class="ficha-stat-numero">
                <?= $totalAplazados ?>
            </div>

            <div class="ficha-stat-detalle">
                Estado actual del aprendiz
            </div>
        </div>

        <div class="ficha-card ficha-stat">
            <div class="ficha-stat-label">
                Retiro / cancelación
            </div>

            <div class="ficha-stat-numero">
                <?= $totalRetiro + $totalCancelados ?>
            </div>

            <div class="ficha-stat-detalle">
                Retiro: <?= $totalRetiro ?> · Cancelados: <?= $totalCancelados ?>
            </div>
        </div>

    </section>

    <!-- =========================================================
         CALENDARIO DE LA FICHA
         ========================================================= -->

    <section class="ficha-card ficha-seccion">

        <div class="ficha-card-header">
            <div>
                <h2 class="ficha-card-titulo">
                    Calendario de formación
                </h2>

                <p class="ficha-card-subtitulo">
                    Referencia temporal de la ficha y de sus etapas.
                </p>
            </div>
        </div>

        <div class="ficha-card-body">

            <div class="ficha-grid ficha-grid-3">

                <!-- Duración general -->

                <div class="ficha-etapa ficha-etapa-activa">

                    <h3 class="ficha-etapa-titulo">
                        Duración de la ficha
                    </h3>

                    <div class="ficha-etapa-fecha">
                        <?= e($fechaInicioTexto) ?>
                        →
                        <?= e($fechaFinTexto) ?>
                    </div>

                    <div class="ficha-etapa-detalle">

                        <?php if ($duracionMeses > 0): ?>

                            Duración registrada:
                            <?= $duracionMeses ?>
                            <?= $duracionMeses === 1 ? 'mes' : 'meses' ?>.

                        <?php else: ?>

                            La duración del programa no está registrada.

                        <?php endif; ?>

                    </div>

                </div>

                <!-- Etapa lectiva -->

                <div class="ficha-etapa">

                    <h3 class="ficha-etapa-titulo">
                        Finalización estimada de etapa lectiva
                    </h3>

                    <div class="ficha-etapa-fecha">
                        <?= e($fechaFinLectivaTexto) ?>
                    </div>

                    <div class="ficha-etapa-detalle">

                        Corresponde a seis meses antes de la fecha
                        de finalización de la ficha.

                        <?php if ($estadoTiempoLectiva === 'vigente' && $tiempoRestanteLectiva): ?>

                            Faltan aproximadamente

                            <strong>
                                <?= $tiempoRestanteLectiva['meses'] ?>
                                <?= $tiempoRestanteLectiva['meses'] === 1 ? 'mes' : 'meses' ?>

                                <?php if ($tiempoRestanteLectiva['dias'] > 0): ?>
                                    y
                                    <?= $tiempoRestanteLectiva['dias'] ?>
                                    <?= $tiempoRestanteLectiva['dias'] === 1 ? 'día' : 'días' ?>
                                <?php endif; ?>
                            </strong>

                            para llegar a esta fecha.

                        <?php elseif ($estadoTiempoLectiva === 'hoy'): ?>

                            La fecha estimada corresponde a hoy.

                        <?php elseif ($estadoTiempoLectiva === 'finalizada'): ?>

                            Esta fecha estimada ya fue superada.

                        <?php endif; ?>

                    </div>

                </div>

                <!-- Etapa práctica -->

                <div class="ficha-etapa">

                    <h3 class="ficha-etapa-titulo">
                        Etapa práctica
                    </h3>

                    <div class="ficha-etapa-fecha">
                        <?= e($fechaInicioPracticaTexto) ?>
                        →
                        <?= e($fechaFinTexto) ?>
                    </div>

                    <div class="ficha-etapa-detalle">
                        Corresponde a los últimos seis meses
                        de la duración prevista de la ficha.
                    </div>

                </div>

            </div>

            <div class="ficha-tiempo">

                <div class="ficha-tiempo-titulo">
                    Tiempo transcurrido de la etapa lectiva
                </div>

                <div class="ficha-tiempo-fechas">

                    <span>
                        Inicio:
                        <strong><?= e($fechaInicioTexto) ?></strong>
                    </span>

                    <span>
                        Fin etapa lectiva:
                        <strong><?= e($fechaFinLectivaTexto) ?></strong>
                    </span>

                </div>

                <div class="ficha-barra">
                    <div
                        class="ficha-barra-progreso"
                        style="width: <?= number_format(max(0, min(100, (float) $porcentajeTiempoFicha)), 2, '.', '') ?>%;"
                    ><?= $porcentajeTiempoFicha >= 8 ? e($porcentajeTiempoTexto) . ' %' : '' ?></div>
                </div>

                <div class="ficha-barra-leyenda">
                    <span>
                        <i style="background:#2563eb;"></i>
                        Tiempo transcurrido
                    </span>
                    <span>
                        <i style="background:#fdba74;"></i>
                        Tiempo por transcurrir
                    </span>
                </div>

                <div class="ficha-tiempo-centro">

                    <?= e($porcentajeTiempoTexto) ?> %
                    del tiempo de la etapa lectiva transcurrido

                    <?php if ($estadoTiempoLectiva === 'vigente' && $tiempoRestanteLectiva): ?>

                        · Faltan
                        <?= $tiempoRestanteLectiva['meses'] ?>
                        <?= $tiempoRestanteLectiva['meses'] === 1 ? 'mes' : 'meses' ?>

                        <?php if ($tiempoRestanteLectiva['dias'] > 0): ?>
                            y
                            <?= $tiempoRestanteLectiva['dias'] ?>
                            <?= $tiempoRestanteLectiva['dias'] === 1 ? 'día' : 'días' ?>
                        <?php endif; ?>

                        para finalizar la etapa lectiva.

                    <?php elseif ($estadoTiempoLectiva === 'hoy'): ?>

                        · La etapa lectiva finaliza hoy.

                    <?php elseif ($estadoTiempoLectiva === 'finalizada'): ?>

                        · La etapa lectiva ya finalizó.
                        La barra permanece en 100 % durante la etapa práctica.

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </section>

    <!-- =========================================================
         COMPARACIÓN TIEMPO VS AVANCE ACADÉMICO
         ========================================================= -->

    <section class="ficha-card ficha-seccion">

        <div class="ficha-card-header">

            <div>
                <h2 class="ficha-card-titulo">
                    Tiempo de etapa lectiva vs. avance académico
                </h2>

                <p class="ficha-card-subtitulo">
                    Comparación visual entre el tiempo de la etapa lectiva y el mayor avance individual, sobre una escala común de 0 % a 100 %.
                </p>
            </div>

        </div>

        <div class="ficha-card-body">

            <div class="ficha-comparacion">

                <div class="ficha-comparacion-intro">

                    <h3>
                        Comparación del tiempo lectivo y el avance
                    </h3>

                    <p>
                        Las dos barras utilizan la misma escala para facilitar
                        la lectura del avance académico y del tiempo lectivo.
                    </p>

                </div>

                <!-- =====================================================
                     BARRA 2 - TIEMPO LECTIVO
                     ===================================================== -->

                <div class="ficha-comparacion-item">

                    <div class="ficha-comparacion-cabecera">

                        <div class="ficha-comparacion-titulo">

                            <span class="ficha-indicador ficha-indicador-tiempo"></span>

                            <span>
                                Tiempo transcurrido de la etapa lectiva
                            </span>

                        </div>

                        <div class="ficha-comparacion-valor">

                            <?= e(
                                number_format(
                                    (float) $porcentajeTiempoFicha,
                                    1,
                                    ',',
                                    '.'
                                )
                            ) ?>%

                        </div>

                    </div>

                    <div class="ficha-comparacion-barra ficha-comparacion-barra-dual">

                        <div
                            class="ficha-comparacion-progreso ficha-comparacion-progreso-tiempo"
                            style="width: <?= max(
                                0,
                                min(
                                    100,
                                    (float) $porcentajeTiempoFicha
                                )
                            ) ?>%;"
                        ></div>

                    </div>

                    <div class="ficha-comparacion-escala">
                        <span>0%</span>
                        <span>100%</span>
                    </div>

                </div>

                <!-- =====================================================
                     BARRA 3 - MAYOR AVANCE INDIVIDUAL
                     ===================================================== -->

                <div class="ficha-comparacion-item">

                    <div class="ficha-comparacion-cabecera">

                        <div class="ficha-comparacion-titulo">

                            <span class="ficha-indicador ficha-indicador-mayor"></span>

                            <span>
                                Mayor avance individual
                            </span>

                        </div>

                        <div class="ficha-comparacion-valor">

                            <?= e(
                                number_format(
                                    (float) $mayorAvance,
                                    1,
                                    ',',
                                    '.'
                                )
                            ) ?>%

                        </div>

                    </div>

                    <div class="ficha-comparacion-barra">

                        <div
                            class="ficha-comparacion-progreso ficha-comparacion-progreso-mayor"
                            style="width: <?= max(
                                0,
                                min(
                                    100,
                                    (float) $mayorAvance
                                )
                            ) ?>%;"
                        ></div>

                    </div>

                    <div class="ficha-comparacion-escala">
                        <span>0%</span>
                        <span>100%</span>
                    </div>

                </div>

                <!-- =====================================================
                     REFERENCIA
                     ===================================================== -->

                <div class="ficha-comparacion-referencia">


                    <div>
                        <span class="ficha-indicador ficha-indicador-tiempo"></span>
                        <span>Tiempo lectivo transcurrido</span>
                    </div>

                    <div>
                        <span class="ficha-indicador ficha-indicador-restante"></span>
                        <span>Tiempo lectivo por transcurrir</span>
                    </div>

                    <div>
                        <span class="ficha-indicador ficha-indicador-mayor"></span>
                        <span>Mayor avance individual</span>
                    </div>

                </div>

            </div>

            <div class="ficha-comparacion-nota">

                El avance individual corresponde al porcentaje de
                <strong>RAP lectivos aprobados</strong> de cada aprendiz
                que se encuentra en formación.

                <?php if ($aprendizMayorAvance !== null): ?>

                    El mayor porcentaje individual registrado actualmente es
                    <strong><?= e($mayorAvanceTexto) ?> %</strong>.

                <?php endif; ?>

                La referencia temporal corresponde únicamente al periodo
                de la <strong>etapa lectiva</strong>, desde
                <strong><?= e($fechaInicioTexto) ?></strong> hasta
                <strong><?= e($fechaFinLectivaTexto) ?></strong>.
                Una vez inicia la etapa práctica, el tiempo lectivo
                permanece representado en 100 %.

                Esta comparación es únicamente informativa y permite observar
                la relación entre el calendario de la etapa lectiva y el
                avance académico registrado.

            </div>

        </div>

    </section>

    <!-- =========================================================
         RESUMEN ACADÉMICO
         ========================================================= -->

    <section class="ficha-grid ficha-grid-3 ficha-seccion">

        <div class="ficha-card">

            <div class="ficha-card-header">
                <div>
                    <h2 class="ficha-card-titulo">
                        Avance académico
                    </h2>

                    <p class="ficha-card-subtitulo">
                        Promedio de aprendices en formación.
                    </p>
                </div>
            </div>

            <div class="ficha-card-body">

                <div class="ficha-avance-principal">

                    <div class="ficha-avance-circulo">

                        <div class="ficha-avance-circulo-texto">
                            <?= e($promedioAvanceTexto) ?>%
                        </div>

                    </div>

                    <div class="ficha-avance-descripcion">

                        <strong>
                            Avance de RAP lectivos
                        </strong>

                        <p>
                            Promedio calculado únicamente con
                            aprendices cuyo estado es
                            <strong>EN FORMACION</strong>.
                        </p>

                    </div>

                </div>

            </div>

        </div>

        <div class="ficha-card">

            <div class="ficha-card-header">
                <div>
                    <h2 class="ficha-card-titulo">
                        Etapa lectiva
                    </h2>

                    <p class="ficha-card-subtitulo">
                        Estado académico actual.
                    </p>
                </div>
            </div>

            <div class="ficha-card-body">

                <div class="ficha-academico-mini">

                    <div class="ficha-academico-mini-item">

                        <span>
                            Aprendices
                        </span>

                        <strong>
                            <?= $totalEtapaLectiva ?>
                        </strong>

                    </div>

                    <div class="ficha-academico-mini-item">

                        <span>
                            Estado
                        </span>

                        <strong style="font-size: 15px;">
                            Lectiva
                        </strong>

                    </div>

                </div>

            </div>

        </div>

        <div class="ficha-card">

            <div class="ficha-card-header">
                <div>
                    <h2 class="ficha-card-titulo">
                        Etapa práctica
                    </h2>

                    <p class="ficha-card-subtitulo">
                        Aprendices que completaron los RAP lectivos.
                    </p>
                </div>
            </div>

            <div class="ficha-card-body">

                <div class="ficha-academico-mini">

                    <div class="ficha-academico-mini-item">

                        <span>
                            Aprendices
                        </span>

                        <strong>
                            <?= $totalEtapaPractica ?>
                        </strong>

                    </div>

                    <div class="ficha-academico-mini-item">

                        <span>
                            Condición
                        </span>

                        <strong style="font-size: 15px;">
                            Práctica
                        </strong>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <!-- =========================================================
         AVANCE INDIVIDUAL
         ========================================================= -->

    <section class="ficha-card ficha-seccion">

        <div class="ficha-card-header">

            <div>
                <h2 class="ficha-card-titulo">
                    Avance académico por aprendiz
                </h2>

                <p class="ficha-card-subtitulo">
                    Seguimiento individual de aprendices en formación.
                </p>
            </div>

            <span class="ficha-badge ficha-badge-verde">
                <?= count($avanceAprendices) ?> en formación
            </span>

        </div>

        <?php if (!empty($avanceAprendices)): ?>

            <div class="ficha-tabla-herramientas">

                <div class="ficha-buscador">

                    <span class="ficha-buscador-icono">
                        🔎
                    </span>

                    <input
                        type="search"
                        class="ficha-buscador-input"
                        id="buscarAvanceAprendiz"
                        placeholder="Buscar aprendiz por nombre o ID..."
                        autocomplete="off"
                    >

                </div>

                <div
                    class="ficha-tabla-contador"
                    id="contadorAvanceAprendiz"
                >
                    <?= count($avanceAprendices) ?> registros
                </div>

            </div>

        <?php endif; ?>

        <div class="ficha-card-body" style="padding: 0;">

            <?php if (!empty($avanceAprendices)): ?>

                <div class="ficha-tabla-contenedor">

                    <table
                        class="ficha-tabla"
                        id="tablaAvanceAprendiz"
                    >

                        <thead>
                            <tr>
                                <th>Aprendiz</th>
                                <th>RAP lectivos</th>
                                <th>Aprobados</th>
                                <th>Pendientes</th>
                                <th>Avance</th>
                                <th>Etapa</th>
                                <th>RAP práctico</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($avanceAprendices as $avance): ?>

                                <?php
                                $totalRapLectivos = (int) (
                                    $avance['total_rap_lectivos'] ?? 0
                                );

                                $rapAprobados = (int) (
                                    $avance['rap_lectivos_aprobados'] ?? 0
                                );

                                $rapPendientes = max(
                                    0,
                                    $totalRapLectivos - $rapAprobados
                                );

                                $porcentaje = (float) (
                                    $avance['porcentaje_avance_lectivo'] ?? 0
                                );

                                $porcentaje = max(
                                    0,
                                    min(100, $porcentaje)
                                );

                                $porcentajeTexto = number_format(
                                    $porcentaje,
                                    1,
                                    ',',
                                    '.'
                                );

                                $etapa = strtoupper(
                                    trim(
                                        (string) (
                                            $avance['etapa_actual'] ?? ''
                                        )
                                    )
                                );

                                $etapaNormalizada = str_replace(
                                    'Á',
                                    'A',
                                    $etapa
                                );

                                $esPractica = (
                                    $etapaNormalizada === 'ETAPA PRACTICA'
                                );

                                $totalRapPractica = (int) (
                                    $avance['total_rap_practica'] ?? 0
                                );

                                $rapPracticaAprobados = (int) (
                                    $avance['rap_practica_aprobados'] ?? 0
                                );

                                $rapPracticaEvaluados = (int) (
                                    $avance['rap_practica_evaluados'] ?? 0
                                );
                                ?>

                                <tr>

                                    <td>

                                        <div class="ficha-aprendiz-nombre">
                                            <?= e($avance['aprendiz'] ?? 'Sin nombre') ?>
                                        </div>

                                        <div class="ficha-aprendiz-documento">
                                            ID aprendiz:
                                            <?= (int) ($avance['id_aprendiz'] ?? 0) ?>
                                        </div>

                                    </td>

                                    <td>
                                        <?= $totalRapLectivos ?>
                                    </td>

                                    <td>
                                        <span class="ficha-badge ficha-badge-verde">
                                            <?= $rapAprobados ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="ficha-badge ficha-badge-amarillo">
                                            <?= $rapPendientes ?>
                                        </span>
                                    </td>

                                    <td>

                                        <div class="ficha-avance-mini">

                                            <div class="ficha-avance-mini-top">

                                                <strong>
                                                    <?= e($porcentajeTexto) ?>%
                                                </strong>

                                                <span>
                                                    <?= $rapAprobados ?>/<?= $totalRapLectivos ?>
                                                </span>

                                            </div>

                                            <div class="ficha-avance-mini-barra">

                                                <div
                                                    class="ficha-avance-mini-progreso"
                                                    style="width: <?= e($porcentajeTexto) ?>%;"
                                                ></div>

                                            </div>

                                        </div>

                                    </td>

                                    <td>

                                        <?php if ($esPractica): ?>

                                            <span class="ficha-badge ficha-badge-azul">
                                                Etapa práctica
                                            </span>

                                        <?php else: ?>

                                            <span class="ficha-badge ficha-badge-gris">
                                                Etapa lectiva
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?php if ($totalRapPractica <= 0): ?>

                                            <span class="ficha-badge ficha-badge-gris">
                                                No disponible
                                            </span>

                                        <?php elseif ($rapPracticaAprobados > 0): ?>

                                            <span class="ficha-badge ficha-badge-verde">
                                                Aprobado
                                            </span>

                                        <?php elseif ($rapPracticaEvaluados > 0): ?>

                                            <span class="ficha-badge ficha-badge-amarillo">
                                                Evaluado
                                            </span>

                                        <?php else: ?>

                                            <span class="ficha-badge ficha-badge-gris">
                                                Pendiente
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <div
                    class="ficha-sin-resultados"
                    id="sinResultadosAvance"
                >
                    No se encontraron aprendices con ese criterio de búsqueda.
                </div>

                <div class="ficha-paginacion" id="paginacionAvance"></div>

            <?php else: ?>

                <div class="ficha-vacio">

                    <div class="ficha-vacio-icono">
                        —
                    </div>

                    No hay aprendices en estado
                    <strong>EN FORMACION</strong>
                    para mostrar avance académico.

                </div>

            <?php endif; ?>

        </div>

    </section>

    <!-- =========================================================
         LÍDER DE FICHA
         ========================================================= -->

    <section class="ficha-card ficha-seccion">

        <div class="ficha-card-header">

            <div>
                <h2 class="ficha-card-titulo">
                    Líder de Ficha
                </h2>

                <p class="ficha-card-subtitulo">
                    Profesional responsable de la coordinación de la ficha.
                </p>
            </div>

            <?php if ($liderFicha): ?>

                <span class="ficha-badge ficha-badge-verde">
                    Asignado
                </span>

            <?php endif; ?>

        </div>

        <div class="ficha-card-body">

            <?php if ($liderFicha): ?>

                <?php
                $liderNombres = trim(
                    (string) (
                        $liderFicha['nombres'] ?? ''
                    )
                );

                $liderApellidos = trim(
                    (string) (
                        $liderFicha['apellidos'] ?? ''
                    )
                );

                $liderNombreCompleto = trim(
                    $liderNombres . ' ' . $liderApellidos
                );

                $liderIniciales = '';

                if ($liderNombres !== '') {
                    $liderIniciales .= mb_substr(
                        $liderNombres,
                        0,
                        1
                    );
                }

                if ($liderApellidos !== '') {
                    $liderIniciales .= mb_substr(
                        $liderApellidos,
                        0,
                        1
                    );
                }

                $liderIniciales = strtoupper(
                    $liderIniciales
                );
                ?>

                <div class="ficha-lider">

                    <div class="ficha-lider-avatar">
                        <?= e($liderIniciales ?: '?') ?>
                    </div>

                    <div class="ficha-lider-contenido">

                        <div class="ficha-lider-etiqueta">
                            Líder de Ficha
                        </div>

                        <div class="ficha-lider-nombre">
                            <?= e(
                                $liderNombreCompleto
                                ?: 'Sin nombre'
                            ) ?>
                        </div>

                        <div class="ficha-lider-detalle">

                            <?= e(
                                $liderFicha['tipo_profesional']
                                ?? 'Profesional'
                            ) ?>

                            <?php if (!empty($liderFicha['especialidad'])): ?>

                                ·
                                <?= e(
                                    $liderFicha['especialidad']
                                ) ?>

                            <?php endif; ?>

                            <?php if (!empty($liderFicha['correo'])): ?>

                                ·
                                <?= e(
                                    $liderFicha['correo']
                                ) ?>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            <?php else: ?>

                <div class="ficha-vacio">

                    <div class="ficha-vacio-icono">
                        —
                    </div>

                    No hay un Líder de Ficha asignado actualmente.

                </div>

            <?php endif; ?>

        </div>

    </section>

    <!-- =========================================================
         INSTRUCTORES QUE HAN EVALUADO RAPS
         ========================================================= -->

    <section class="ficha-card ficha-seccion">

        <div class="ficha-card-header">

            <div>
                <h2 class="ficha-card-titulo">
                    Instructores que han evaluado RAPs
                </h2>

                <p class="ficha-card-subtitulo">
                    Profesionales identificados a partir de las evaluaciones registradas en la ficha.
                </p>
            </div>

            <span class="ficha-badge ficha-badge-azul">
                <?= $totalInstructoresEvaluadores ?> evaluadores
            </span>

        </div>

        <div class="ficha-card-body" style="padding: 0;">

            <?php if (!empty($instructoresEvaluadores)): ?>

                <div class="ficha-tabla-contenedor">

                    <table class="ficha-tabla" id="tablaInstructores">

                        <thead>
                            <tr>
                                <th>Instructor</th>
                                <th>Asignación</th>
                                <th>RAP evaluados</th>
                                <th>Aprobados</th>
                                <th>No aprobados</th>
                                <th>Última evaluación</th>
                                <th>Estado</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($instructoresEvaluadores as $instructor): ?>

                                <?php
                                $instructorNombres = trim(
                                    (string) (
                                        $instructor['nombres'] ?? ''
                                    )
                                );

                                $instructorApellidos = trim(
                                    (string) (
                                        $instructor['apellidos'] ?? ''
                                    )
                                );

                                $instructorNombre = trim(
                                    $instructorNombres
                                    . ' '
                                    . $instructorApellidos
                                );

                                $instructorIniciales = '';

                                if ($instructorNombres !== '') {
                                    $instructorIniciales .= mb_substr(
                                        $instructorNombres,
                                        0,
                                        1
                                    );
                                }

                                if ($instructorApellidos !== '') {
                                    $instructorIniciales .= mb_substr(
                                        $instructorApellidos,
                                        0,
                                        1
                                    );
                                }

                                $instructorIniciales = strtoupper(
                                    $instructorIniciales
                                );

                                $tipoAsignacionInstructor = str_replace(
                                    '_',
                                    ' ',
                                    (string) (
                                        $instructor['tipo_asignacion']
                                        ?? ''
                                    )
                                );

                                $tipoAsignacionInstructor = ucwords(
                                    strtolower(
                                        $tipoAsignacionInstructor
                                    )
                                );

                                $rapsEvaluados = (int) (
                                    $instructor['raps_evaluados'] ?? 0
                                );

                                $rapsAprobados = (int) (
                                    $instructor['raps_aprobados'] ?? 0
                                );

                                $rapsNoAprobados = (int) (
                                    $instructor['raps_no_aprobados'] ?? 0
                                );

                                $ultimaEvaluacion = !empty(
                                    $instructor['ultima_evaluacion']
                                )
                                    ? date(
                                        'd/m/Y',
                                        strtotime(
                                            $instructor['ultima_evaluacion']
                                        )
                                    )
                                    : '—';

                                $estadoInstructor = strtolower(
                                    trim(
                                        (string) (
                                            $instructor['estado_profesional']
                                            ?? ''
                                        )
                                    )
                                );
                                ?>

                                <tr>

                                    <td>

                                        <div class="ficha-profesional">

                                            <div class="ficha-profesional-avatar">
                                                <?= e(
                                                    $instructorIniciales ?: '?'
                                                ) ?>
                                            </div>

                                            <div>

                                                <div class="ficha-profesional-nombre">
                                                    <?= e(
                                                        $instructorNombre
                                                        ?: 'Sin nombre'
                                                    ) ?>
                                                </div>

                                                <div class="ficha-profesional-detalle">

                                                    <?php if (!empty($instructor['especialidad'])): ?>

                                                        <?= e(
                                                            $instructor['especialidad']
                                                        ) ?>

                                                    <?php elseif (!empty($instructor['tipo_profesional'])): ?>

                                                        <?= e(
                                                            $instructor['tipo_profesional']
                                                        ) ?>

                                                    <?php else: ?>

                                                        Profesional

                                                    <?php endif; ?>

                                                </div>

                                            </div>

                                        </div>

                                    </td>

                                    <td>

                                        <?php if (
                                            !empty(
                                                $instructor['tipo_asignacion']
                                            )
                                        ): ?>

                                            <span class="ficha-badge ficha-badge-azul">
                                                <?= e(
                                                    $tipoAsignacionInstructor
                                                ) ?>
                                            </span>

                                        <?php else: ?>

                                            <span class="ficha-badge ficha-badge-gris">
                                                No registrada
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <span class="ficha-badge ficha-badge-gris">
                                            <?= $rapsEvaluados ?>
                                        </span>

                                    </td>

                                    <td>

                                        <span class="ficha-badge ficha-badge-verde">
                                            <?= $rapsAprobados ?>
                                        </span>

                                    </td>

                                    <td>

                                        <span class="ficha-badge ficha-badge-rojo">
                                            <?= $rapsNoAprobados ?>
                                        </span>

                                    </td>

                                    <td>
                                        <?= e($ultimaEvaluacion) ?>
                                    </td>

                                    <td>

                                        <?php if ($estadoInstructor === 'activo'): ?>

                                            <span class="ficha-badge ficha-badge-verde">
                                                Activo
                                            </span>

                                        <?php elseif ($estadoInstructor === 'inactivo'): ?>

                                            <span class="ficha-badge ficha-badge-gris">
                                                Inactivo
                                            </span>

                                        <?php else: ?>

                                            <span class="ficha-badge ficha-badge-gris">
                                                <?= e(
                                                    $instructor['estado_profesional']
                                                    ?? '—'
                                                ) ?>
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>
                <div class="ficha-paginacion" id="paginacionInstructores"></div>

            <?php else: ?>

                <div class="ficha-vacio">

                    <div class="ficha-vacio-icono">
                        —
                    </div>

                    No hay evaluaciones de RAP registradas
                    con instructor asociado en esta ficha.

                </div>

            <?php endif; ?>

        </div>

    </section>

    <!-- =========================================================
         TODOS LOS APRENDICES
         ========================================================= -->

    <section class="ficha-card ficha-seccion">

        <div class="ficha-card-header">

            <div>
                <h2 class="ficha-card-titulo">
                    Aprendices de la ficha
                </h2>

                <p class="ficha-card-subtitulo">
                    Estado actual de todos los aprendices matriculados.
                </p>
            </div>

            <span class="ficha-badge ficha-badge-gris">
                <?= count($aprendices) ?> registros
            </span>

        </div>

        <?php if (!empty($aprendices)): ?>

            <div class="ficha-tabla-herramientas">

                <div class="ficha-buscador">

                    <span class="ficha-buscador-icono">
                        🔎
                    </span>

                    <input
                        type="search"
                        class="ficha-buscador-input"
                        id="buscarTodosAprendices"
                        placeholder="Buscar por nombre, documento o estado..."
                        autocomplete="off"
                    >

                </div>

                <div
                    class="ficha-tabla-contador"
                    id="contadorTodosAprendices"
                >
                    <?= count($aprendices) ?> registros
                </div>

            </div>

        <?php endif; ?>

        <div class="ficha-card-body" style="padding: 0;">

            <?php if (!empty($aprendices)): ?>

                <div class="ficha-tabla-contenedor">

                    <table
                        class="ficha-tabla"
                        id="tablaTodosAprendices"
                    >

                        <thead>
                            <tr>
                                <th>Aprendiz</th>
                                <th>Documento</th>
                                <th>Teléfono</th>
                                <th>Correo</th>
                                <th>Inicio matrícula</th>
                                <th>Estado</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($aprendices as $aprendiz): ?>

                                <?php
                                $estadoAprendiz = strtoupper(
                                    trim(
                                        (string) (
                                            $aprendiz['estado_aprendiz']
                                            ?? ''
                                        )
                                    )
                                );

                                $claseEstado = 'ficha-badge-gris';

                                if ($estadoAprendiz === 'EN FORMACION') {
                                    $claseEstado = 'ficha-badge-verde';
                                } elseif ($estadoAprendiz === 'APLAZADO') {
                                    $claseEstado = 'ficha-badge-amarillo';
                                } elseif (
                                    $estadoAprendiz ===
                                    'RETIRO VOLUNTARIO'
                                ) {
                                    $claseEstado = 'ficha-badge-rojo';
                                } elseif (
                                    $estadoAprendiz ===
                                    'CANCELADO'
                                ) {
                                    $claseEstado = 'ficha-badge-rojo';
                                }
                                ?>

                                <tr>

                                    <td>

                                        <div class="ficha-aprendiz-nombre">
                                            <?= e(
                                                trim(
                                                    (string) (
                                                        ($aprendiz['nombres'] ?? '')
                                                        . ' '
                                                        . ($aprendiz['apellidos'] ?? '')
                                                    )
                                                )
                                            ) ?>
                                        </div>

                                    </td>

                                    <td>
                                        <?= e(
                                            ($aprendiz['tipo_documento'] ?? '')
                                            . ' '
                                            . ($aprendiz['documento'] ?? '')
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $aprendiz['telefono'] ?? '—'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $aprendiz['correo'] ?? '—'
                                        ) ?>
                                    </td>

                                    <td>

                                        <?php if (!empty($aprendiz['fecha_inicio'])): ?>

                                            <?= e(
                                                date(
                                                    'd/m/Y',
                                                    strtotime(
                                                        $aprendiz['fecha_inicio']
                                                    )
                                                )
                                            ) ?>

                                        <?php else: ?>

                                            —

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <span class="ficha-badge <?= e($claseEstado) ?>">
                                            <?= e(
                                                $aprendiz['estado_aprendiz']
                                                ?? 'Sin estado'
                                            ) ?>
                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <div
                    class="ficha-sin-resultados"
                    id="sinResultadosTodosAprendices"
                >
                    No se encontraron aprendices con ese criterio de búsqueda.
                </div>

                <div class="ficha-paginacion" id="paginacionTodosAprendices"></div>

            <?php else: ?>

                <div class="ficha-vacio">

                    <div class="ficha-vacio-icono">
                        —
                    </div>

                    No hay aprendices registrados en esta ficha.

                </div>

            <?php endif; ?>

        </div>

    </section>

</div>

<script>
    /*
    |--------------------------------------------------------------------------
    | Tablas con buscador y paginación
    |--------------------------------------------------------------------------
    */
    function tablaPaginada(cfg) {

        const tabla = document.getElementById(cfg.tabla);
        const nav = document.getElementById(cfg.paginacion);

        if (!tabla || !nav) {
            return;
        }

        const input = cfg.buscador
            ? document.getElementById(cfg.buscador)
            : null;
        const contador = cfg.contador
            ? document.getElementById(cfg.contador)
            : null;
        const sinResultados = cfg.sinResultados
            ? document.getElementById(cfg.sinResultados)
            : null;

        const filas = Array.from(
            tabla.querySelectorAll('tbody > tr')
        );

        let porPagina = cfg.porPagina || 10;
        let pagina = 1;
        let filtradas = filas.slice();

        function normalizar(texto) {
            return texto
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
        }

        function boton(texto, onClick, opciones) {
            const b = document.createElement('button');
            b.type = 'button';
            b.textContent = texto;
            if (opciones && opciones.activo) {
                b.className = 'activo';
            }
            if (opciones && opciones.deshabilitado) {
                b.disabled = true;
            }
            b.addEventListener('click', onClick);
            return b;
        }

        function irA(n) {
            pagina = n;
            render();
        }

        function paginasVisibles(total) {
            const set = new Set([1, total, pagina - 1, pagina, pagina + 1]);
            return Array.from(set)
                .filter(function (n) { return n >= 1 && n <= total; })
                .sort(function (a, b) { return a - b; });
        }

        function renderNav() {

            nav.innerHTML = '';

            const total = filtradas.length;

            if (total === 0) {
                return;
            }

            const totalPaginas = Math.ceil(total / porPagina);
            const desde = (pagina - 1) * porPagina + 1;
            const hasta = Math.min(pagina * porPagina, total);

            const info = document.createElement('div');
            info.className = 'ficha-paginacion-info';

            const texto = document.createElement('span');
            texto.textContent =
                'Mostrando ' + desde + '–' + hasta + ' de ' + total;
            info.appendChild(texto);

            const select = document.createElement('select');
            [10, 25, 50, 100].forEach(function (n) {
                const o = document.createElement('option');
                o.value = String(n);
                o.textContent = n + ' por página';
                if (n === porPagina) {
                    o.selected = true;
                }
                select.appendChild(o);
            });
            select.addEventListener('change', function () {
                porPagina = parseInt(select.value, 10);
                pagina = 1;
                render();
            });
            info.appendChild(select);

            nav.appendChild(info);

            if (totalPaginas <= 1) {
                return;
            }

            const botones = document.createElement('div');
            botones.className = 'ficha-paginacion-botones';

            botones.appendChild(boton('‹', function () {
                irA(pagina - 1);
            }, { deshabilitado: pagina === 1 }));

            let anterior = 0;
            paginasVisibles(totalPaginas).forEach(function (n) {
                if (anterior && n - anterior > 1) {
                    const puntos = document.createElement('span');
                    puntos.className = 'ficha-paginacion-puntos';
                    puntos.textContent = '…';
                    botones.appendChild(puntos);
                }
                botones.appendChild(boton(String(n), function () {
                    irA(n);
                }, { activo: n === pagina }));
                anterior = n;
            });

            botones.appendChild(boton('›', function () {
                irA(pagina + 1);
            }, { deshabilitado: pagina === totalPaginas }));

            nav.appendChild(botones);
        }

        function render() {

            const totalPaginas = Math.max(
                1,
                Math.ceil(filtradas.length / porPagina)
            );

            if (pagina > totalPaginas) {
                pagina = totalPaginas;
            }

            const inicio = (pagina - 1) * porPagina;
            const fin = inicio + porPagina;
            const visibles = new Set(filtradas.slice(inicio, fin));

            filas.forEach(function (fila) {
                fila.style.display = visibles.has(fila) ? '' : 'none';
            });

            if (contador) {
                contador.textContent =
                    filtradas.length + ' ' +
                    (filtradas.length === 1 ? 'registro' : 'registros');
            }

            if (sinResultados) {
                sinResultados.style.display =
                    filtradas.length === 0 ? 'block' : 'none';
            }

            renderNav();
        }

        function filtrar() {
            const criterio = normalizar(input.value.trim());

            filtradas = filas.filter(function (fila) {
                return criterio === '' ||
                    normalizar(fila.textContent || '').includes(criterio);
            });

            pagina = 1;
            render();
        }

        if (input) {
            input.addEventListener('input', filtrar);
        }

        render();
    }

    tablaPaginada({
        tabla: 'tablaAvanceAprendiz',
        buscador: 'buscarAvanceAprendiz',
        contador: 'contadorAvanceAprendiz',
        sinResultados: 'sinResultadosAvance',
        paginacion: 'paginacionAvance'
    });

    tablaPaginada({
        tabla: 'tablaTodosAprendices',
        buscador: 'buscarTodosAprendices',
        contador: 'contadorTodosAprendices',
        sinResultados: 'sinResultadosTodosAprendices',
        paginacion: 'paginacionTodosAprendices'
    });

    tablaPaginada({
        tabla: 'tablaInstructores',
        paginacion: 'paginacionInstructores'
    });
</script>

<?php
require_once __DIR__ . '/../layout/footer.php';
?>