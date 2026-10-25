<?php

declare(strict_types=1);

if (!isset($titulo)) {
    $titulo = 'Detalle del aprendiz';
}

$aprendiz = $aprendiz ?? [];
$matriculas = $matriculas ?? [];
$competenciasRAP = $competenciasRAP ?? [];

require_once ROOT_PATH . '/views/layout/header.php';

require_once ROOT_PATH . '/views/layout/navbar.php';


$nombreAprendiz = trim(
    (string) ($aprendiz['apellidos'] ?? '')
    . ' '
    . (string) ($aprendiz['nombres'] ?? '')
);

$documentoAprendiz =
    (string) ($aprendiz['tipo_documento'] ?? '')
    . ' '
    . (string) ($aprendiz['documento'] ?? '');

$estadoAprendiz = strtoupper(
    trim(
        (string) ($aprendiz['estado_aprendiz'] ?? '')
    )
);


/*
|--------------------------------------------------------------------------
| Datos personales
|--------------------------------------------------------------------------
*/

$fechaNacimiento =
    trim(
        (string) (
            $aprendiz['fecha_nacimiento']
            ?? ''
        )
    );

$sexo =
    trim(
        (string) (
            $aprendiz['sexo']
            ?? ''
        )
    );

$telefono =
    trim(
        (string) (
            $aprendiz['telefono']
            ?? ''
        )
    );

$correo =
    trim(
        (string) (
            $aprendiz['correo']
            ?? ''
        )
    );

$direccion =
    trim(
        (string) (
            $aprendiz['direccion']
            ?? ''
        )
    );

$municipio =
    trim(
        (string) (
            $aprendiz['municipio']
            ?? ''
        )
    );

$departamento =
    trim(
        (string) (
            $aprendiz['departamento']
            ?? ''
        )
    );


/*
|--------------------------------------------------------------------------
| Formatear fecha de nacimiento
|--------------------------------------------------------------------------
*/

$fechaNacimientoMostrar = 'No registrada';

if (
    $fechaNacimiento !== ''
    && $fechaNacimiento !== '0000-00-00'
) {

    $timestampNacimiento =
        strtotime($fechaNacimiento);

    if ($timestampNacimiento !== false) {

        $fechaNacimientoMostrar =
            date(
                'd/m/Y',
                $timestampNacimiento
            );
    }
}


/*
|--------------------------------------------------------------------------
| Agrupar información
|--------------------------------------------------------------------------
|
| Ficha
|   └── Competencia
|        └── RAP
|
*/

$fichas = [];

$totalCompetencias = 0;
$totalRAP = 0;
$totalAprobados = 0;
$totalNoAprobados = 0;
$totalPendientes = 0;

$competenciasRegistradas = [];


foreach ($competenciasRAP as $fila) {

    $idFicha = (int) ($fila['id_ficha'] ?? 0);

    $idCompetencia =
        (int) ($fila['id_competencia'] ?? 0);

    if ($idFicha <= 0 || $idCompetencia <= 0) {
        continue;
    }


    /*
    |--------------------------------------------------------------------------
    | Ficha
    |--------------------------------------------------------------------------
    */

    if (!isset($fichas[$idFicha])) {

        $fichas[$idFicha] = [
            'numero_ficha' =>
                $fila['numero_ficha'] ?? '',

            'version_programa' =>
                $fila['version_programa'] ?? '',

            'estado_ficha' =>
                $fila['estado_ficha'] ?? '',

            'codigo_programa' =>
                $fila['codigo_programa'] ?? '',

            'nombre_programa' =>
                $fila['nombre_programa'] ?? '',

            'nivel' =>
                $fila['nivel'] ?? '',

            'competencias' => [],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Competencia
    |--------------------------------------------------------------------------
    */

    if (
        !isset(
            $fichas[$idFicha]['competencias'][$idCompetencia]
        )
    ) {

        $fichas[$idFicha]['competencias'][$idCompetencia] = [
            'id' => $idCompetencia,

            'codigo' =>
                $fila['codigo_competencia'] ?? '',

            'nombre' =>
                $fila['nombre_competencia'] ?? '',

            'descripcion' =>
                $fila['descripcion_competencia'] ?? '',

            'rap' => [],
        ];

        $totalCompetencias++;

        $competenciasRegistradas[] = $idCompetencia;
    }


    /*
    |--------------------------------------------------------------------------
    | RAP
    |--------------------------------------------------------------------------
    */

    $fichas[$idFicha]['competencias'][$idCompetencia]['rap'][] =
        $fila;

    $totalRAP++;


    /*
    |--------------------------------------------------------------------------
    | Estadísticas
    |--------------------------------------------------------------------------
    */

    $juicio = strtolower(
        trim(
            (string) (
                $fila['juicio'] ?? 'pendiente'
            )
        )
    );

    if ($juicio === 'aprobado') {

        $totalAprobados++;

    } elseif ($juicio === 'no_aprobado') {

        $totalNoAprobados++;

    } else {

        $totalPendientes++;
    }
}


$totalFichas = count($fichas);


/*
|--------------------------------------------------------------------------
| Estado visual del aprendiz
|--------------------------------------------------------------------------
*/

$claseEstadoAprendiz = match ($estadoAprendiz) {

    'EN FORMACION' =>
        'sita-badge sita-badge-formacion',

    'APLAZADO' =>
        'sita-badge sita-badge-aplazado',

    'CANCELADO' =>
        'sita-badge sita-badge-cancelado',

    'RETIRO VOLUNTARIO' =>
        'sita-badge sita-badge-retiro',

    default =>
        'sita-badge sita-badge-default',
};


$iniciales = '';

if (!empty($aprendiz['nombres'])) {

    $iniciales .=
        mb_strtoupper(
            mb_substr(
                (string) $aprendiz['nombres'],
                0,
                1
            )
        );
}

if (!empty($aprendiz['apellidos'])) {

    $iniciales .=
        mb_strtoupper(
            mb_substr(
                (string) $aprendiz['apellidos'],
                0,
                1
            )
        );
}

?>

<style>

    /*
    |--------------------------------------------------------------------------
    | CONTENEDOR PRINCIPAL
    |--------------------------------------------------------------------------
    */

    .sita-page {
        padding: 30px;
        background: #f5f7fa;
        min-height: calc(100vh - 70px);
    }


    /*
    |--------------------------------------------------------------------------
    | ENCABEZADO
    |--------------------------------------------------------------------------
    */

    .sita-page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 25px;
        flex-wrap: wrap;
    }

    .sita-page-title {
        margin: 0;
        font-size: 28px;
        font-weight: 700;
        color: #172033;
    }

    .sita-page-subtitle {
        margin: 6px 0 0;
        color: #6b7280;
    }


    /*
    |--------------------------------------------------------------------------
    | BOTONES
    |--------------------------------------------------------------------------
    */

    .sita-btn {
        border: 0;
        border-radius: 9px;
        padding: 10px 17px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        cursor: pointer;
        font-size: 14px;
    }

    .sita-btn-primary {
        background: #198754;
        color: #ffffff;
    }

    .sita-btn-primary:hover {
        background: #157347;
        color: #ffffff;
    }

    .sita-btn-secondary {
        background: #eef1f4;
        color: #374151;
    }

    .sita-btn-secondary:hover {
        background: #e2e6ea;
        color: #172033;
    }


    /*
    |--------------------------------------------------------------------------
    | PANEL
    |--------------------------------------------------------------------------
    */

    .sita-panel {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 5px 20px rgba(0, 0, 0, .04);
        overflow: hidden;
    }


    /*
    |--------------------------------------------------------------------------
    | PERFIL DEL APRENDIZ
    |--------------------------------------------------------------------------
    */

    .sita-profile {
        padding: 24px;
    }

    .sita-profile-main {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
    }

    .sita-profile-person {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .sita-avatar {
        width: 62px;
        height: 62px;
        border-radius: 50%;
        background: #e9f7ef;
        color: #198754;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 19px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .sita-profile-name {
        margin: 0;
        color: #172033;
        font-size: 21px;
        font-weight: 700;
    }

    .sita-profile-document {
        margin: 5px 0 0;
        color: #6b7280;
        font-size: 14px;
    }


    /*
    |--------------------------------------------------------------------------
    | BADGES DE ESTADO
    |--------------------------------------------------------------------------
    */

    .sita-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .sita-badge-formacion {
        background: #dcfce7;
        color: #166534;
    }

    .sita-badge-aplazado {
        background: #fef3c7;
        color: #92400e;
    }

    .sita-badge-cancelado {
        background: #fee2e2;
        color: #991b1b;
    }

    .sita-badge-retiro {
        background: #e5e7eb;
        color: #374151;
    }

    .sita-badge-default {
        background: #eef1f4;
        color: #374151;
    }


    /*
    |--------------------------------------------------------------------------
    | DATOS DEL APRENDIZ
    |--------------------------------------------------------------------------
    |
    | En escritorio se muestran exactamente:
    |
    | Fila 1:
    | Tipo documento | Documento | Fecha nacimiento | Sexo
    |
    | Fila 2:
    | Teléfono | Correo | Dirección | Municipio / Departamento
    |
    */

    .sita-profile-data {
        display: grid;
        grid-template-columns:
            repeat(4, minmax(0, 1fr));

        gap: 18px;

        margin-top: 22px;
        padding-top: 20px;

        border-top: 1px solid #edf0f3;
    }

    .sita-data-label {
        display: block;
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 5px;
    }

    .sita-data-value {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #172033;
        word-break: break-word;
    }


    /*
    |--------------------------------------------------------------------------
    | RESUMEN
    |--------------------------------------------------------------------------
    */

    .sita-summary {
        display: grid;
        grid-template-columns:
            repeat(4, 1fr);

        gap: 15px;

        margin: 20px 0;
    }

    .sita-summary-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 18px 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, .03);
    }

    .sita-summary-number {
        display: block;
        font-size: 25px;
        line-height: 1;
        font-weight: 700;
        color: #172033;
        margin-bottom: 7px;
    }

    .sita-summary-label {
        font-size: 13px;
        color: #6b7280;
    }


    /*
    |--------------------------------------------------------------------------
    | PANEL DE COMPETENCIAS
    |--------------------------------------------------------------------------
    */

    .sita-competencias-panel {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 5px 20px rgba(0, 0, 0, .04);
        overflow: hidden;
    }

    .sita-panel-header {
        padding: 20px 24px;
        border-bottom: 1px solid #edf0f3;

        display: flex;
        justify-content: space-between;
        align-items: center;

        gap: 15px;
        flex-wrap: wrap;
    }

    .sita-panel-title {
        margin: 0;
        color: #172033;
        font-size: 18px;
        font-weight: 700;
    }

    .sita-panel-subtitle {
        margin: 5px 0 0;
        color: #6b7280;
        font-size: 13px;
    }


    /*
    |--------------------------------------------------------------------------
    | BUSCADOR
    |--------------------------------------------------------------------------
    */

    .sita-search {
        display: flex;
        gap: 10px;
        width: 100%;
        max-width: 650px;
    }

    .sita-search input {
        flex: 1;

        border: 1px solid #d8dde5;
        border-radius: 9px;

        padding: 10px 13px;

        outline: none;
        font-size: 14px;
    }

    .sita-search input:focus {
        border-color: #198754;
        box-shadow:
            0 0 0 3px rgba(25, 135, 84, .10);
    }


    /*
    |--------------------------------------------------------------------------
    | FICHA
    |--------------------------------------------------------------------------
    */

    .sita-ficha {
        border-bottom: 1px solid #edf0f3;
    }

    .sita-ficha:last-child {
        border-bottom: 0;
    }

    .sita-ficha-header {
        padding: 18px 24px;

        background: #f8fafc;

        border-bottom: 1px solid #edf0f3;

        display: flex;
        justify-content: space-between;
        align-items: center;

        gap: 15px;
        flex-wrap: wrap;
    }

    .sita-ficha-title {
        margin: 0;

        color: #172033;
        font-size: 15px;
        font-weight: 700;
    }

    .sita-ficha-programa {
        margin: 4px 0 0;

        color: #6b7280;
        font-size: 13px;
    }

    .sita-ficha-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
    }

    .sita-ficha-meta span {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        color: #6b7280;

        padding: 4px 8px;

        border-radius: 6px;

        font-size: 11px;
    }


    /*
    |--------------------------------------------------------------------------
    | COMPETENCIA
    |--------------------------------------------------------------------------
    */

    .sita-competencia {
        border-bottom: 1px solid #edf0f3;
    }

    .sita-competencia:last-child {
        border-bottom: 0;
    }

    .sita-competencia-header {
        width: 100%;

        border: 0;

        background: #ffffff;

        padding: 18px 24px;

        display: flex;
        align-items: center;

        gap: 15px;

        text-align: left;

        cursor: pointer;
    }

    .sita-competencia-header:hover {
        background: #fafcfb;
    }

    .sita-competencia-header:focus {
        outline: none;
        background: #fafcfb;
    }

    .sita-competencia-icon {
        width: 40px;
        height: 40px;

        border-radius: 10px;

        background: #e9f7ef;
        color: #198754;

        display: flex;
        align-items: center;
        justify-content: center;

        font-weight: 700;

        flex-shrink: 0;
    }

    .sita-competencia-info {
        flex: 1;
        min-width: 0;
    }

    .sita-competencia-code {
        display: inline-block;

        color: #198754;

        font-size: 11px;
        font-weight: 700;

        margin-bottom: 4px;
    }

    .sita-competencia-name {
        display: block;

        color: #172033;

        font-size: 14px;
        font-weight: 700;

        line-height: 1.4;
    }

    .sita-competencia-count {
        color: #6b7280;
        font-size: 12px;
        white-space: nowrap;
    }

    .sita-chevron {
        width: 30px;
        height: 30px;

        border-radius: 8px;

        background: #f1f5f9;

        display: flex;
        align-items: center;
        justify-content: center;

        color: #6b7280;

        transition: transform .2s ease;
    }

    .sita-competencia-header[aria-expanded="true"]
    .sita-chevron {
        transform: rotate(180deg);
    }


    /*
    |--------------------------------------------------------------------------
    | CONTENIDO RAP
    |--------------------------------------------------------------------------
    */

    .sita-competencia-content {
        display: none;

        padding: 0 24px 20px;

        background: #fafbfc;
    }

    .sita-competencia-content.open {
        display: block;
    }

    .sita-competencia-description {
        padding: 15px 0;

        color: #6b7280;

        font-size: 12px;

        line-height: 1.5;
    }


    /*
    |--------------------------------------------------------------------------
    | RAP
    |--------------------------------------------------------------------------
    */

    .sita-rap {
        background: #ffffff;

        border: 1px solid #e5e7eb;

        border-radius: 11px;

        padding: 16px;

        margin-top: 10px;
    }

    .sita-rap:first-of-type {
        margin-top: 0;
    }

    .sita-rap-top {
        display: flex;

        justify-content: space-between;
        align-items: flex-start;

        gap: 15px;
    }

    .sita-rap-info {
        flex: 1;
        min-width: 0;
    }

    .sita-rap-code {
        color: #6b7280;

        font-size: 11px;

        font-weight: 700;

        margin-bottom: 5px;
    }

    .sita-rap-name {
        margin: 0;

        color: #172033;

        font-size: 13px;

        line-height: 1.5;

        font-weight: 600;
    }

    .sita-rap-description {
        margin: 7px 0 0;

        color: #6b7280;

        font-size: 12px;

        line-height: 1.5;
    }


    /*
    |--------------------------------------------------------------------------
    | JUICIOS
    |--------------------------------------------------------------------------
    */

    .sita-juicio {
        display: inline-flex;

        align-items: center;
        justify-content: center;

        min-width: 105px;

        padding: 6px 10px;

        border-radius: 20px;

        font-size: 11px;

        font-weight: 700;

        white-space: nowrap;
    }

    .sita-juicio-aprobado {
        background: #dcfce7;
        color: #166534;
    }

    .sita-juicio-no-aprobado {
        background: #fee2e2;
        color: #991b1b;
    }

    .sita-juicio-pendiente {
        background: #fef3c7;
        color: #92400e;
    }


    /*
    |--------------------------------------------------------------------------
    | DATOS DE EVALUACIÓN
    |--------------------------------------------------------------------------
    */

    .sita-rap-result {
        display: flex;

        flex-wrap: wrap;

        gap: 18px;

        margin-top: 13px;

        padding-top: 12px;

        border-top: 1px dashed #dfe4e8;
    }

    .sita-result-item {
        min-width: 130px;
    }

    .sita-result-label {
        display: block;

        color: #6b7280;

        font-size: 11px;

        margin-bottom: 3px;
    }

    .sita-result-value {
        display: block;

        color: #374151;

        font-size: 12px;

        font-weight: 600;
    }


    /*
    |--------------------------------------------------------------------------
    | SIN INFORMACIÓN
    |--------------------------------------------------------------------------
    */

    .sita-empty {
        padding: 50px 20px;

        text-align: center;

        color: #6b7280;
    }

    .sita-empty strong {
        display: block;

        color: #374151;

        margin-bottom: 5px;
    }


    /*
    |--------------------------------------------------------------------------
    | RESPONSIVE
    |--------------------------------------------------------------------------
    */

    @media (max-width: 1100px) {

        .sita-profile-data {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

    }


    @media (max-width: 900px) {

        .sita-summary {
            grid-template-columns:
                repeat(2, 1fr);
        }

    }


    @media (max-width: 600px) {

        .sita-page {
            padding: 18px;
        }

        .sita-summary {
            grid-template-columns: 1fr;
        }

        .sita-profile {
            padding: 18px;
        }

        .sita-profile-main {
            align-items: flex-start;
        }

        .sita-profile-data {
            grid-template-columns: 1fr;
        }

        .sita-panel-header {
            padding: 18px;
        }

        .sita-ficha-header {
            padding: 16px 18px;
        }

        .sita-competencia-header {
            padding: 16px 18px;
        }

        .sita-competencia-content {
            padding: 0 18px 18px;
        }

        .sita-rap-top {
            flex-direction: column;
        }

        .sita-juicio {
            align-self: flex-start;
        }

        .sita-search {
            flex-direction: column;
        }

        .sita-search .sita-btn {
            width: 100%;
        }

    }

</style>


<main class="sita-page">


    <!-- =========================================================
         ENCABEZADO
    ========================================================== -->

    <div class="sita-page-header">

        <div>

            <h1 class="sita-page-title">
                Detalle del aprendiz
            </h1>

            <p class="sita-page-subtitle">
                Consulta de formación, competencias y resultados de aprendizaje.
            </p>

        </div>


        <a
            href="<?= e(url('/aprendices')) ?>"
            class="sita-btn sita-btn-secondary"
        >
            ← Volver a aprendices
        </a>

    </div>


    <!-- =========================================================
         PERFIL
    ========================================================== -->

    <section class="sita-panel sita-profile">


        <div class="sita-profile-main">


            <div class="sita-profile-person">

                <div class="sita-avatar">

                    <?= e(
                        $iniciales !== ''
                            ? $iniciales
                            : 'AP'
                    ) ?>

                </div>


                <div>

                    <h2 class="sita-profile-name">

                        <?= e(
                            $nombreAprendiz
                        ) ?>

                    </h2>


                    <p class="sita-profile-document">

                        <?= e(
                            $documentoAprendiz
                        ) ?>

                    </p>

                </div>

            </div>


            <span
                class="<?= e($claseEstadoAprendiz) ?>"
            >
                <?= e(
                    $estadoAprendiz !== ''
                        ? $estadoAprendiz
                        : 'SIN ESTADO'
                ) ?>
            </span>

        </div>


        <!-- =====================================================
             INFORMACIÓN COMPLETA DEL APRENDIZ
        ====================================================== -->

        <div class="sita-profile-data">


            <!-- =================================================
                 FILA 1 - COLUMNA 1
            ================================================== -->

            <div>

                <span class="sita-data-label">
                    Tipo de documento
                </span>

                <span class="sita-data-value">

                    <?= e(
                        !empty($aprendiz['tipo_documento'])
                            ? $aprendiz['tipo_documento']
                            : 'No registrado'
                    ) ?>

                </span>

            </div>


            <!-- =================================================
                 FILA 1 - COLUMNA 2
            ================================================== -->

            <div>

                <span class="sita-data-label">
                    Documento
                </span>

                <span class="sita-data-value">

                    <?= e(
                        !empty($aprendiz['documento'])
                            ? $aprendiz['documento']
                            : 'No registrado'
                    ) ?>

                </span>

            </div>


            <!-- =================================================
                 FILA 1 - COLUMNA 3
            ================================================== -->

            <div>

                <span class="sita-data-label">
                    Fecha de nacimiento
                </span>

                <span class="sita-data-value">

                    <?= e(
                        $fechaNacimientoMostrar
                    ) ?>

                </span>

            </div>


            <!-- =================================================
                 FILA 1 - COLUMNA 4
            ================================================== -->

            <div>

                <span class="sita-data-label">
                    Sexo
                </span>

                <span class="sita-data-value">

                    <?= e(
                        $sexo !== ''
                            ? $sexo
                            : 'No registrado'
                    ) ?>

                </span>

            </div>


            <!-- =================================================
                 FILA 2 - COLUMNA 1
            ================================================== -->

            <div>

                <span class="sita-data-label">
                    Teléfono
                </span>

                <span class="sita-data-value">

                    <?= e(
                        $telefono !== ''
                            ? $telefono
                            : 'No registrado'
                    ) ?>

                </span>

            </div>


            <!-- =================================================
                 FILA 2 - COLUMNA 2
            ================================================== -->

            <div>

                <span class="sita-data-label">
                    Correo
                </span>

                <span class="sita-data-value">

                    <?= e(
                        $correo !== ''
                            ? $correo
                            : 'No registrado'
                    ) ?>

                </span>

            </div>


            <!-- =================================================
                 FILA 2 - COLUMNA 3
            ================================================== -->

            <div>

                <span class="sita-data-label">
                    Dirección
                </span>

                <span class="sita-data-value">

                    <?= e(
                        $direccion !== ''
                            ? $direccion
                            : 'No registrada'
                    ) ?>

                </span>

            </div>


            <!-- =================================================
                 FILA 2 - COLUMNA 4
            ================================================== -->

            <div>

                <span class="sita-data-label">
                    Municipio / Departamento
                </span>

                <span class="sita-data-value">

                    <?php if (
                        $municipio !== ''
                        && $departamento !== ''
                    ): ?>

                        <?= e(
                            $municipio
                        ) ?>

                        /

                        <?= e(
                            $departamento
                        ) ?>

                    <?php elseif (
                        $municipio !== ''
                    ): ?>

                        <?= e(
                            $municipio
                        ) ?>

                    <?php elseif (
                        $departamento !== ''
                    ): ?>

                        <?= e(
                            $departamento
                        ) ?>

                    <?php else: ?>

                        No registrados

                    <?php endif; ?>

                </span>

            </div>


        </div>

    </section>


    <!-- =========================================================
         RESUMEN
    ========================================================== -->

    <div class="sita-summary">


        <div class="sita-summary-card">

            <span class="sita-summary-number">
                <?= e(
                    (string) $totalFichas
                ) ?>
            </span>

            <span class="sita-summary-label">
                Fichas
            </span>

        </div>


        <div class="sita-summary-card">

            <span class="sita-summary-number">
                <?= e(
                    (string) $totalCompetencias
                ) ?>
            </span>

            <span class="sita-summary-label">
                Competencias
            </span>

        </div>


        <div class="sita-summary-card">

            <span class="sita-summary-number">
                <?= e(
                    (string) $totalRAP
                ) ?>
            </span>

            <span class="sita-summary-label">
                Resultados de aprendizaje
            </span>

        </div>


        <div class="sita-summary-card">

            <span class="sita-summary-number">
                <?= e(
                    (string) $totalAprobados
                ) ?>
            </span>

            <span class="sita-summary-label">
                RAP aprobados
            </span>

        </div>


    </div>


    <!-- =========================================================
         COMPETENCIAS
    ========================================================== -->

    <section class="sita-competencias-panel">


        <div class="sita-panel-header">


            <div>

                <h2 class="sita-panel-title">
                    Competencias
                </h2>

                <p class="sita-panel-subtitle">
                    Seleccione una competencia para consultar sus resultados de aprendizaje.
                </p>

            </div>


            <form
                class="sita-search"
                onsubmit="return false;"
            >

                <input
                    type="search"
                    id="buscarCompetencia"
                    placeholder="Buscar competencia, código o RAP..."
                    autocomplete="off"
                >

            </form>


        </div>


        <?php if (empty($fichas)): ?>


            <div class="sita-empty">

                <strong>
                    No hay competencias disponibles.
                </strong>

                Este aprendiz todavía no tiene competencias
                asociadas a sus fichas.

            </div>


        <?php else: ?>


            <div id="listaCompetencias">


                <?php foreach ($fichas as $ficha): ?>


                    <div class="sita-ficha">


                        <div class="sita-ficha-header">


                            <div>

                                <h3 class="sita-ficha-title">

                                    Ficha
                                    <?= e(
                                        (string) $ficha['numero_ficha']
                                    ) ?>

                                </h3>


                                <p class="sita-ficha-programa">

                                    <?= e(
                                        (string) $ficha['nombre_programa']
                                    ) ?>

                                </p>


                                <div class="sita-ficha-meta">

                                    <span>
                                        Programa:
                                        <?= e(
                                            (string) $ficha['codigo_programa']
                                        ) ?>
                                    </span>


                                    <?php if (
                                        !empty($ficha['nivel'])
                                    ): ?>

                                        <span>
                                            Nivel:
                                            <?= e(
                                                (string) $ficha['nivel']
                                            ) ?>
                                        </span>

                                    <?php endif; ?>


                                    <?php if (
                                        !empty($ficha['version_programa'])
                                    ): ?>

                                        <span>
                                            Versión:
                                            <?= e(
                                                (string) $ficha['version_programa']
                                            ) ?>
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>


                        </div>


                        <?php foreach (
                            $ficha['competencias']
                            as $competencia
                        ): ?>


                            <?php

                            $cantidadRAP =
                                count(
                                    $competencia['rap']
                                );

                            $textoBusqueda =
                                strtolower(
                                    trim(
                                        (string)
                                        $competencia['codigo']
                                        . ' '
                                        . (string)
                                        $competencia['nombre']
                                    )
                                );

                            foreach (
                                $competencia['rap']
                                as $rapBusqueda
                            ) {

                                $textoBusqueda .= ' ' .
                                    strtolower(
                                        (string)
                                        (
                                            $rapBusqueda['codigo_rap']
                                            ?? ''
                                        )
                                    );

                                $textoBusqueda .= ' ' .
                                    strtolower(
                                        (string)
                                        (
                                            $rapBusqueda['nombre_rap']
                                            ?? ''
                                        )
                                    );
                            }

                            ?>


                            <div
                                class="sita-competencia"
                                data-busqueda="<?= e(
                                    $textoBusqueda
                                ) ?>"
                            >


                                <button
                                    type="button"
                                    class="sita-competencia-header"
                                    aria-expanded="false"
                                >


                                    <span class="sita-competencia-icon">
                                        ✓
                                    </span>


                                    <span class="sita-competencia-info">


                                        <span class="sita-competencia-code">

                                            <?= e(
                                                (string)
                                                $competencia['codigo']
                                            ) ?>

                                        </span>


                                        <span class="sita-competencia-name">

                                            <?= e(
                                                (string)
                                                $competencia['nombre']
                                            ) ?>

                                        </span>


                                    </span>


                                    <span class="sita-competencia-count">

                                        <?= e(
                                            (string) $cantidadRAP
                                        ) ?>

                                        RAP

                                    </span>


                                    <span class="sita-chevron">
                                        ▼
                                    </span>


                                </button>


                                <div
                                    class="sita-competencia-content"
                                >


                                    <?php if (
                                        !empty(
                                            $competencia['descripcion']
                                        )
                                    ): ?>

                                        <div
                                            class="sita-competencia-description"
                                        >

                                            <?= e(
                                                (string)
                                                $competencia['descripcion']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>


                                    <?php foreach (
                                        $competencia['rap']
                                        as $rap
                                    ): ?>


                                        <?php

                                        $juicio =
                                            strtolower(
                                                trim(
                                                    (string)
                                                    (
                                                        $rap['juicio']
                                                        ?? 'pendiente'
                                                    )
                                                )
                                            );


                                        if (
                                            $juicio === 'aprobado'
                                        ) {

                                            $juicioTexto =
                                                'APROBADO';

                                            $juicioClase =
                                                'sita-juicio-aprobado';

                                        } elseif (
                                            $juicio === 'no_aprobado'
                                        ) {

                                            $juicioTexto =
                                                'NO APROBADO';

                                            $juicioClase =
                                                'sita-juicio-no-aprobado';

                                        } else {

                                            $juicioTexto =
                                                'POR EVALUAR';

                                            $juicioClase =
                                                'sita-juicio-pendiente';
                                        }

                                        ?>


                                        <article
                                            class="sita-rap"
                                        >


                                            <div
                                                class="sita-rap-top"
                                            >


                                                <div
                                                    class="sita-rap-info"
                                                >


                                                    <div
                                                        class="sita-rap-code"
                                                    >

                                                        RAP
                                                        <?= e(
                                                            (string)
                                                            $rap['codigo_rap']
                                                        ) ?>

                                                    </div>


                                                    <h4
                                                        class="sita-rap-name"
                                                    >

                                                        <?= e(
                                                            (string)
                                                            $rap['nombre_rap']
                                                        ) ?>

                                                    </h4>


                                                    <?php if (
                                                        !empty(
                                                            $rap['descripcion_rap']
                                                        )
                                                    ): ?>

                                                        <p
                                                            class="sita-rap-description"
                                                        >

                                                            <?= e(
                                                                (string)
                                                                $rap['descripcion_rap']
                                                            ) ?>

                                                        </p>

                                                    <?php endif; ?>


                                                </div>


                                                <span
                                                    class="sita-juicio <?= e(
                                                        $juicioClase
                                                    ) ?>"
                                                >

                                                    <?= e(
                                                        $juicioTexto
                                                    ) ?>

                                                </span>


                                            </div>


                                            <?php if (
                                                !empty(
                                                    $rap['id_resultado']
                                                )
                                            ): ?>


                                                <div
                                                    class="sita-rap-result"
                                                >


                                                    <div
                                                        class="sita-result-item"
                                                    >

                                                        <span
                                                            class="sita-result-label"
                                                        >
                                                            Fecha de evaluación
                                                        </span>

                                                        <span
                                                            class="sita-result-value"
                                                        >

                                                            <?= e(
                                                                !empty(
                                                                    $rap['fecha_evaluacion']
                                                                )
                                                                    ? date(
                                                                        'd/m/Y',
                                                                        strtotime(
                                                                            (string)
                                                                            $rap['fecha_evaluacion']
                                                                        )
                                                                    )
                                                                    : 'No registrada'
                                                            ) ?>

                                                        </span>

                                                    </div>


                                                    <?php if (
                                                        !empty(
                                                            $rap['fecha_hora_evaluacion']
                                                        )
                                                    ): ?>

                                                        <div
                                                            class="sita-result-item"
                                                        >

                                                            <span
                                                                class="sita-result-label"
                                                            >
                                                                Fecha y hora
                                                            </span>

                                                            <span
                                                                class="sita-result-value"
                                                            >

                                                                <?= e(
                                                                    date(
                                                                        'd/m/Y H:i',
                                                                        strtotime(
                                                                            (string)
                                                                            $rap['fecha_hora_evaluacion']
                                                                        )
                                                                    )
                                                                ) ?>

                                                            </span>

                                                        </div>

                                                    <?php endif; ?>


                                                    <?php if (
                                                        !empty(
                                                            $rap['nombre_instructor']
                                                        )
                                                    ): ?>

                                                        <div
                                                            class="sita-result-item"
                                                        >

                                                            <span
                                                                class="sita-result-label"
                                                            >
                                                                Instructor
                                                            </span>

                                                            <span
                                                                class="sita-result-value"
                                                            >

                                                                <?= e(
                                                                    (string)
                                                                    $rap['nombre_instructor']
                                                                ) ?>

                                                            </span>

                                                        </div>

                                                    <?php endif; ?>


                                                    <?php if (
                                                        !empty(
                                                            $rap['juicio_sena']
                                                        )
                                                    ): ?>

                                                        <div
                                                            class="sita-result-item"
                                                        >

                                                            <span
                                                                class="sita-result-label"
                                                            >
                                                                Juicio SENA
                                                            </span>

                                                            <span
                                                                class="sita-result-value"
                                                            >

                                                                <?= e(
                                                                    strtoupper(
                                                                        (string)
                                                                        $rap['juicio_sena']
                                                                    )
                                                                ) ?>

                                                            </span>

                                                        </div>

                                                    <?php endif; ?>


                                                    <?php if (
                                                        $rap['calificacion']
                                                        !== null
                                                        &&
                                                        $rap['calificacion']
                                                        !== ''
                                                    ): ?>

                                                        <div
                                                            class="sita-result-item"
                                                        >

                                                            <span
                                                                class="sita-result-label"
                                                            >
                                                                Calificación
                                                            </span>

                                                            <span
                                                                class="sita-result-value"
                                                            >

                                                                <?= e(
                                                                    (string)
                                                                    $rap['calificacion']
                                                                ) ?>

                                                            </span>

                                                        </div>

                                                    <?php endif; ?>


                                                </div>


                                            <?php else: ?>


                                                <div
                                                    class="sita-rap-result"
                                                >

                                                    <div
                                                        class="sita-result-item"
                                                    >

                                                        <span
                                                            class="sita-result-label"
                                                        >
                                                            Evaluación
                                                        </span>

                                                        <span
                                                            class="sita-result-value"
                                                        >
                                                            Aún no registra evaluación
                                                        </span>

                                                    </div>

                                                </div>


                                            <?php endif; ?>


                                        </article>


                                    <?php endforeach; ?>


                                </div>


                            </div>


                        <?php endforeach; ?>


                    </div>


                <?php endforeach; ?>


            </div>


            <div
                id="sinResultadosBusqueda"
                class="sita-empty"
                style="display:none;"
            >

                <strong>
                    No se encontraron coincidencias.
                </strong>

                Intente con otro código, competencia o resultado de aprendizaje.

            </div>


        <?php endif; ?>


    </section>


</main>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        /*
        |--------------------------------------------------------------------------
        | ABRIR / CERRAR COMPETENCIAS
        |--------------------------------------------------------------------------
        */

        const botones =
            document.querySelectorAll(
                '.sita-competencia-header'
            );


        botones.forEach(
            function (boton) {

                boton.addEventListener(
                    'click',
                    function () {

                        const contenido =
                            boton.nextElementSibling;

                        const abierto =
                            boton.getAttribute(
                                'aria-expanded'
                            ) === 'true';


                        boton.setAttribute(
                            'aria-expanded',
                            abierto
                                ? 'false'
                                : 'true'
                        );


                        if (abierto) {

                            contenido.classList.remove(
                                'open'
                            );

                        } else {

                            contenido.classList.add(
                                'open'
                            );
                        }

                    }
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | BUSCADOR
        |--------------------------------------------------------------------------
        */

        const buscador =
            document.getElementById(
                'buscarCompetencia'
            );

        const competencias =
            document.querySelectorAll(
                '.sita-competencia'
            );

        const mensaje =
            document.getElementById(
                'sinResultadosBusqueda'
            );


        if (!buscador) {
            return;
        }


        buscador.addEventListener(
            'input',
            function () {

                const termino =
                    buscador.value
                        .trim()
                        .toLowerCase();


                let encontrados = 0;


                competencias.forEach(
                    function (competencia) {

                        const texto =
                            (
                                competencia.dataset.busqueda
                                || ''
                            ).toLowerCase();


                        const coincide =
                            termino === ''
                            ||
                            texto.includes(
                                termino
                            );


                        if (coincide) {

                            competencia.style.display =
                                '';

                            encontrados++;

                        } else {

                            competencia.style.display =
                                'none';
                        }

                    }
                );


                if (mensaje) {

                    mensaje.style.display =
                        encontrados === 0
                            ? 'block'
                            : 'none';
                }


                /*
                |--------------------------------------------------------------------------
                | Cuando se busca, abrimos automáticamente las competencias
                |--------------------------------------------------------------------------
                */

                if (termino !== '') {

                    competencias.forEach(
                        function (competencia) {

                            if (
                                competencia.style.display
                                !== 'none'
                            ) {

                                const boton =
                                    competencia.querySelector(
                                        '.sita-competencia-header'
                                    );

                                const contenido =
                                    competencia.querySelector(
                                        '.sita-competencia-content'
                                    );


                                if (
                                    boton
                                    &&
                                    contenido
                                ) {

                                    boton.setAttribute(
                                        'aria-expanded',
                                        'true'
                                    );

                                    contenido.classList.add(
                                        'open'
                                    );
                                }

                            }

                        }
                    );

                }

            }
        );

    }
);

</script>


<?php

require_once ROOT_PATH . '/views/layout/footer.php';

?>