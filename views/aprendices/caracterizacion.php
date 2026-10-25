<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| DATOS RECIBIDOS DEL CONTROLADOR
|--------------------------------------------------------------------------
*/

$aprendiz       = $aprendiz ?? [];
$eps            = $eps ?? [];
$tiposPoblacion = $tiposPoblacion ?? [];
$historial      = $historial ?? [];

$actualizado =
    isset($_GET['actualizado'])
    && $_GET['actualizado'] === '1';

$error =
    (string) ($_GET['error'] ?? '');

$mensajeError = '';
$mensajeExito = '';

if ($actualizado) {

    $mensajeExito =
        'La caracterización del aprendiz fue actualizada correctamente.';
}

switch ($error) {

    case 'persona':

        $mensajeError =
            'No fue posible identificar correctamente la información del aprendiz.';

        break;

    case 'poblacion':

        $mensajeError =
            'Debe seleccionar el tipo de población.';

        break;

    case 'fecha_nacimiento':

        $mensajeError =
            'La fecha de nacimiento no tiene un formato válido.';

        break;

    case 'actualizacion':

        $mensajeError =
            'No fue posible actualizar la caracterización. Verifique la información e inténtelo nuevamente.';

        break;
}


/*
|--------------------------------------------------------------------------
| DATOS DEL APRENDIZ
|--------------------------------------------------------------------------
*/

$idAprendiz =
    (int) (
        $aprendiz['id']
        ?? 0
    );

$idPersona =
    (int) (
        $aprendiz['id_persona']
        ?? 0
    );

$tipoDocumento =
    (string) (
        $aprendiz['tipo_documento']
        ?? ''
    );

$documento =
    (string) (
        $aprendiz['documento']
        ?? ''
    );

$nombres =
    (string) (
        $aprendiz['nombres']
        ?? ''
    );

$apellidos =
    (string) (
        $aprendiz['apellidos']
        ?? ''
    );

$fechaNacimiento =
    (string) (
        $aprendiz['fecha_nacimiento']
        ?? ''
    );

$sexo =
    (string) (
        $aprendiz['sexo']
        ?? ''
    );

$telefono =
    (string) (
        $aprendiz['telefono']
        ?? ''
    );

$correo =
    (string) (
        $aprendiz['correo']
        ?? ''
    );

$direccion =
    (string) (
        $aprendiz['direccion']
        ?? ''
    );

$municipio =
    (string) (
        $aprendiz['municipio']
        ?? ''
    );

$departamento =
    (string) (
        $aprendiz['departamento']
        ?? ''
    );

$idEps =
    (string) (
        $aprendiz['id_eps']
        ?? ''
    );

$contactoEmergenciaNombre =
    (string) (
        $aprendiz['contacto_emergencia_nombre']
        ?? ''
    );

$contactoEmergenciaTelefono =
    (string) (
        $aprendiz['contacto_emergencia_telefono']
        ?? ''
    );

$contactoEmergenciaParentesco =
    (string) (
        $aprendiz['contacto_emergencia_parentesco']
        ?? ''
    );

$idTipoPoblacion =
    (string) (
        $aprendiz['id_tipo_poblacion']
        ?? ''
    );

$fechaInicioPoblacion =
    (string) (
        $aprendiz['fecha_inicio_poblacion']
        ?? date('Y-m-d')
    );

$observacionPoblacion =
    (string) (
        $aprendiz['observacion_poblacion']
        ?? ''
    );


/*
|--------------------------------------------------------------------------
| FUNCIONES
|--------------------------------------------------------------------------
*/

function sita_h(mixed $valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


function sita_fecha(?string $fecha): string
{
    if (
        !$fecha
        || $fecha === '0000-00-00'
    ) {
        return '—';
    }

    $timestamp = strtotime($fecha);

    if ($timestamp === false) {
        return sita_h($fecha);
    }

    return date(
        'd/m/Y',
        $timestamp
    );
}


/*
|--------------------------------------------------------------------------
| DATOS VISUALES
|--------------------------------------------------------------------------
*/

$nombreCompleto =
    trim(
        $nombres
        . ' '
        . $apellidos
    );

$iniciales = '';

if ($nombres !== '') {

    $iniciales .=
        mb_strtoupper(
            mb_substr(
                $nombres,
                0,
                1
            )
        );
}

if ($apellidos !== '') {

    $iniciales .=
        mb_strtoupper(
            mb_substr(
                $apellidos,
                0,
                1
            )
        );
}

if ($iniciales === '') {
    $iniciales = 'AP';
}


/*
|--------------------------------------------------------------------------
| LAYOUT
|--------------------------------------------------------------------------
*/

require_once ROOT_PATH . '/views/layout/header.php';

require_once ROOT_PATH . '/views/layout/navbar.php';

?>

<style>

/*
|--------------------------------------------------------------------------
| CONTENEDOR
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
    font-size: 14px;
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
    font-weight: 600;
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
| PANEL PRINCIPAL
|--------------------------------------------------------------------------
*/

.sita-panel {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 5px 20px rgba(0, 0, 0, .04);
    overflow: hidden;
    margin-bottom: 20px;
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

.sita-profile-data {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
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
| ALERTAS
|--------------------------------------------------------------------------
*/

.sita-alert {
    border-radius: 12px;
    margin-bottom: 20px;
    border: 0;
}


/*
|--------------------------------------------------------------------------
| SECCIONES
|--------------------------------------------------------------------------
*/

.sita-section {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 5px 20px rgba(0, 0, 0, .04);
    overflow: hidden;
    margin-bottom: 20px;
}

.sita-section-header {
    padding: 20px 24px;
    border-bottom: 1px solid #edf0f3;
    display: flex;
    align-items: center;
    gap: 13px;
    background: #ffffff;
}

.sita-section-number {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #e9f7ef;
    color: #198754;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
    flex-shrink: 0;
}

.sita-section-title {
    margin: 0;
    color: #172033;
    font-size: 17px;
    font-weight: 700;
}

.sita-section-subtitle {
    margin: 4px 0 0;
    color: #6b7280;
    font-size: 12px;
}

.sita-section-body {
    padding: 24px;
}


/*
|--------------------------------------------------------------------------
| CAMPOS
|--------------------------------------------------------------------------
*/

.sita-field {
    margin-bottom: 2px;
}

.sita-field label {
    display: block;
    color: #374151;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 6px;
}

.sita-required {
    color: #dc2626;
}

.sita-control {
    width: 100%;
    min-height: 43px;
    border: 1px solid #d8dde5;
    border-radius: 9px;
    padding: 9px 12px;
    color: #374151;
    background: #ffffff;
    font-size: 14px;
    outline: none;
}

.sita-control:focus {
    border-color: #198754;
    box-shadow: 0 0 0 3px rgba(25, 135, 84, .10);
}

.sita-control[readonly] {
    background: #f8fafc;
    color: #64748b;
}

textarea.sita-control {
    min-height: 100px;
    resize: vertical;
}

.sita-help {
    margin-top: 6px;
    color: #6b7280;
    font-size: 11px;
    line-height: 1.5;
}


/*
|--------------------------------------------------------------------------
| RESUMEN
|--------------------------------------------------------------------------
*/

.sita-resumen {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 11px;
    padding: 14px 16px;
    margin-bottom: 20px;
}

.sita-resumen-title {
    color: #374151;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 5px;
}

.sita-resumen-text {
    color: #6b7280;
    font-size: 12px;
}


/*
|--------------------------------------------------------------------------
| HISTORIAL
|--------------------------------------------------------------------------
*/

.sita-history {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 5px 20px rgba(0, 0, 0, .04);
    overflow: hidden;
    margin-bottom: 25px;
}

.sita-history-header {
    padding: 20px 24px;
    border-bottom: 1px solid #edf0f3;
}

.sita-history-title {
    margin: 0;
    color: #172033;
    font-size: 18px;
    font-weight: 700;
}

.sita-history-subtitle {
    margin: 5px 0 0;
    color: #6b7280;
    font-size: 13px;
}

.sita-table {
    width: 100%;
    margin: 0;
}

.sita-table thead th {
    background: #f8fafc;
    color: #475569;
    font-size: 12px;
    font-weight: 700;
    border-bottom: 1px solid #dfe4e8;
    white-space: nowrap;
    padding: 13px 15px;
}

.sita-table tbody td {
    vertical-align: middle;
    font-size: 13px;
    color: #374151;
    padding: 13px 15px;
    border-bottom: 1px solid #edf0f3;
}

.sita-table tbody tr:last-child td {
    border-bottom: 0;
}

.sita-actual {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 5px 10px;
    border-radius: 20px;
    background: #dcfce7;
    color: #166534;
    font-size: 10px;
    font-weight: 700;
}

.sita-historico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 5px 10px;
    border-radius: 20px;
    background: #eef1f4;
    color: #475569;
    font-size: 10px;
    font-weight: 700;
}


/*
|--------------------------------------------------------------------------
| ACCIONES
|--------------------------------------------------------------------------
*/

.sita-form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin: 25px 0;
}


/*
|--------------------------------------------------------------------------
| ESTADO VACÍO
|--------------------------------------------------------------------------
*/

.sita-empty {
    padding: 45px 20px;
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

@media (max-width: 900px) {

    .sita-page {
        padding: 20px;
    }

    .sita-profile-data {
        grid-template-columns: repeat(2, 1fr);
    }

}


@media (max-width: 600px) {

    .sita-page {
        padding: 15px;
    }

    .sita-page-header {
        align-items: flex-start;
    }

    .sita-page-title {
        font-size: 23px;
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

    .sita-section-header {
        padding: 17px 18px;
    }

    .sita-section-body {
        padding: 18px;
    }

    .sita-form-actions {
        flex-direction: column-reverse;
        align-items: stretch;
    }

    .sita-form-actions .sita-btn {
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
                Caracterización del aprendiz
            </h1>

            <p class="sita-page-subtitle">
                Actualización de información básica,
                contacto y caracterización socioeconómica.
            </p>

        </div>


        <a
            href="<?= e(
                url('/aprendices/detalle?id=' . $idAprendiz)
            ) ?>"
            class="sita-btn sita-btn-secondary"
        >

            ← Volver al detalle

        </a>

    </div>


    <!-- =========================================================
         PERFIL DEL APRENDIZ
    ========================================================== -->

    <section class="sita-panel sita-profile">

        <div class="sita-profile-main">


            <div class="sita-profile-person">

                <div class="sita-avatar">

                    <?= e(
                        $iniciales
                    ) ?>

                </div>


                <div>

                    <h2 class="sita-profile-name">

                        <?= sita_h(
                            $nombreCompleto !== ''
                                ? $nombreCompleto
                                : 'Aprendiz'
                        ) ?>

                    </h2>


                    <p class="sita-profile-document">

                        <?= sita_h(
                            $tipoDocumento
                        ) ?>

                        <?php if ($documento !== ''): ?>

                            ·

                            <?= sita_h(
                                $documento
                            ) ?>

                        <?php endif; ?>

                    </p>

                </div>

            </div>


            <div>

                <span
                    style="
                        display:inline-flex;
                        align-items:center;
                        padding:6px 12px;
                        border-radius:20px;
                        background:#e9f7ef;
                        color:#166534;
                        font-size:12px;
                        font-weight:700;
                    "
                >

                    CARACTERIZACIÓN

                </span>

            </div>

        </div>


        <div class="sita-profile-data">


            <div>

                <span class="sita-data-label">
                    Fecha de nacimiento
                </span>

                <span class="sita-data-value">

                    <?= sita_h(
                        sita_fecha(
                            $fechaNacimiento
                        )
                    ) ?>

                </span>

            </div>


            <div>

                <span class="sita-data-label">
                    Sexo
                </span>

                <span class="sita-data-value">

                    <?= $sexo !== ''
                        ? sita_h($sexo)
                        : 'No registrado'
                    ?>

                </span>

            </div>


            <div>

                <span class="sita-data-label">
                    Teléfono
                </span>

                <span class="sita-data-value">

                    <?= $telefono !== ''
                        ? sita_h($telefono)
                        : 'No registrado'
                    ?>

                </span>

            </div>


            <div>

                <span class="sita-data-label">
                    Correo
                </span>

                <span class="sita-data-value">

                    <?= $correo !== ''
                        ? sita_h($correo)
                        : 'No registrado'
                    ?>

                </span>

            </div>


        </div>

    </section>


    <!-- =========================================================
         MENSAJES
    ========================================================== -->

    <?php if ($mensajeError !== ''): ?>

        <div class="alert alert-danger sita-alert">

            <strong>
                Atención:
            </strong>

            <?= sita_h(
                $mensajeError
            ) ?>

        </div>

    <?php endif; ?>


    <?php if ($mensajeExito !== ''): ?>

        <div class="alert alert-success sita-alert">

            <strong>
                ✓
            </strong>

            <?= sita_h(
                $mensajeExito
            ) ?>

        </div>

    <?php endif; ?>


    <!-- =========================================================
         FORMULARIO
    ========================================================== -->

    <form
        method="POST"
        action="<?= e(
            url('/aprendices/caracterizacion/actualizar')
        ) ?>"
        autocomplete="off"
    >


        <?php

        if (function_exists('csrfField')) {

            echo csrfField();

        } elseif (function_exists('csrfToken')) {

            echo '<input
                type="hidden"
                name="csrf_token"
                value="' . sita_h(
                    csrfToken()
                ) . '"
            >';
        }

        ?>


        <input
            type="hidden"
            name="id_aprendiz"
            value="<?= $idAprendiz ?>"
        >


        <input
            type="hidden"
            name="id_persona"
            value="<?= $idPersona ?>"
        >


        <!-- =====================================================
             1. IDENTIFICACIÓN
        ====================================================== -->

        <section class="sita-section">


            <div class="sita-section-header">

                <div class="sita-section-number">
                    1
                </div>

                <div>

                    <h2 class="sita-section-title">
                        Datos de identificación
                    </h2>

                    <p class="sita-section-subtitle">
                        Información oficial registrada en SITA.
                    </p>

                </div>

            </div>


            <div class="sita-section-body">

                <div class="row g-3">


                    <div class="col-md-3">

                        <div class="sita-field">

                            <label>
                                Tipo de documento
                            </label>

                            <input
                                type="text"
                                class="sita-control"
                                value="<?= sita_h(
                                    $tipoDocumento
                                ) ?>"
                                readonly
                            >

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="sita-field">

                            <label>
                                Documento
                            </label>

                            <input
                                type="text"
                                class="sita-control"
                                value="<?= sita_h(
                                    $documento
                                ) ?>"
                                readonly
                            >

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="sita-field">

                            <label>
                                Nombres
                            </label>

                            <input
                                type="text"
                                class="sita-control"
                                value="<?= sita_h(
                                    $nombres
                                ) ?>"
                                readonly
                            >

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="sita-field">

                            <label>
                                Apellidos
                            </label>

                            <input
                                type="text"
                                class="sita-control"
                                value="<?= sita_h(
                                    $apellidos
                                ) ?>"
                                readonly
                            >

                        </div>

                    </div>


                    <!-- =================================================
                         FECHA DE NACIMIENTO EDITABLE
                    ================================================== -->

                    <div class="col-md-6">

                        <div class="sita-field">

                            <label for="fecha_nacimiento">

                                Fecha de nacimiento

                            </label>

                            <input
                                type="date"
                                id="fecha_nacimiento"
                                name="fecha_nacimiento"
                                class="sita-control"
                                value="<?= sita_h(
                                    $fechaNacimiento
                                ) ?>"
                            >

                        </div>

                    </div>


                    <!-- =================================================
                         SEXO EDITABLE
                    ================================================== -->

                    <div class="col-md-6">

                        <div class="sita-field">

                            <label for="sexo">

                                Sexo

                            </label>

                            <select
                                id="sexo"
                                name="sexo"
                                class="sita-control"
                            >

                                <option value="">
                                    Seleccione...
                                </option>


                                <option
                                    value="Femenino"
                                    <?= (
                                        $sexo === 'Femenino'
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    Femenino

                                </option>


                                <option
                                    value="Masculino"
                                    <?= (
                                        $sexo === 'Masculino'
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    Masculino

                                </option>

                            </select>

                        </div>

                    </div>


                </div>

            </div>

        </section>


        <!-- =====================================================
             2. CONTACTO
        ====================================================== -->

        <section class="sita-section">


            <div class="sita-section-header">

                <div class="sita-section-number">
                    2
                </div>

                <div>

                    <h2 class="sita-section-title">
                        Datos de contacto
                    </h2>

                    <p class="sita-section-subtitle">
                        Información de contacto actual del aprendiz.
                    </p>

                </div>

            </div>


            <div class="sita-section-body">

                <div class="row g-3">


                    <div class="col-md-4">

                        <div class="sita-field">

                            <label for="telefono">
                                Teléfono
                            </label>

                            <input
                                type="text"
                                id="telefono"
                                name="telefono"
                                maxlength="30"
                                class="sita-control"
                                value="<?= sita_h(
                                    $telefono
                                ) ?>"
                            >

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="sita-field">

                            <label for="correo">
                                Correo electrónico
                            </label>

                            <input
                                type="email"
                                id="correo"
                                name="correo"
                                maxlength="150"
                                class="sita-control"
                                value="<?= sita_h(
                                    $correo
                                ) ?>"
                            >

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="sita-field">

                            <label for="direccion">
                                Dirección
                            </label>

                            <input
                                type="text"
                                id="direccion"
                                name="direccion"
                                maxlength="200"
                                class="sita-control"
                                value="<?= sita_h(
                                    $direccion
                                ) ?>"
                            >

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="sita-field">

                            <label for="municipio">
                                Municipio
                            </label>

                            <input
                                type="text"
                                id="municipio"
                                name="municipio"
                                maxlength="100"
                                class="sita-control"
                                value="<?= sita_h(
                                    $municipio
                                ) ?>"
                            >

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="sita-field">

                            <label for="departamento">
                                Departamento
                            </label>

                            <input
                                type="text"
                                id="departamento"
                                name="departamento"
                                maxlength="100"
                                class="sita-control"
                                value="<?= sita_h(
                                    $departamento
                                ) ?>"
                            >

                        </div>

                    </div>


                </div>

            </div>

        </section>


        <!-- =====================================================
             3. SEGURIDAD SOCIAL Y EMERGENCIA
        ====================================================== -->

        <section class="sita-section">


            <div class="sita-section-header">

                <div class="sita-section-number">
                    3
                </div>

                <div>

                    <h2 class="sita-section-title">
                        Seguridad social y contacto de emergencia
                    </h2>

                    <p class="sita-section-subtitle">
                        Información necesaria para el acompañamiento del aprendiz.
                    </p>

                </div>

            </div>


            <div class="sita-section-body">

                <div class="row g-3">


                    <div class="col-md-6">

                        <div class="sita-field">

                            <label for="id_eps">
                                EPS
                            </label>

                            <select
                                id="id_eps"
                                name="id_eps"
                                class="sita-control"
                            >

                                <option value="">
                                    Seleccione EPS
                                </option>


                                <?php foreach ($eps as $item): ?>

                                    <?php

                                    $idEpsItem =
                                        (int) (
                                            $item['id']
                                            ?? 0
                                        );

                                    $nombreEps =
                                        (string) (
                                            $item['nombre']
                                            ?? ''
                                        );

                                    ?>

                                    <option
                                        value="<?= $idEpsItem ?>"
                                        <?= (
                                            (string) $idEps
                                            ===
                                            (string) $idEpsItem
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        <?= sita_h(
                                            $nombreEps
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="sita-field">

                            <label for="contacto_emergencia_nombre">
                                Nombre contacto de emergencia
                            </label>

                            <input
                                type="text"
                                id="contacto_emergencia_nombre"
                                name="contacto_emergencia_nombre"
                                maxlength="150"
                                class="sita-control"
                                value="<?= sita_h(
                                    $contactoEmergenciaNombre
                                ) ?>"
                            >

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="sita-field">

                            <label for="contacto_emergencia_telefono">
                                Teléfono contacto de emergencia
                            </label>

                            <input
                                type="text"
                                id="contacto_emergencia_telefono"
                                name="contacto_emergencia_telefono"
                                maxlength="30"
                                class="sita-control"
                                value="<?= sita_h(
                                    $contactoEmergenciaTelefono
                                ) ?>"
                            >

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="sita-field">

                            <label for="contacto_emergencia_parentesco">
                                Parentesco
                            </label>

                            <input
                                type="text"
                                id="contacto_emergencia_parentesco"
                                name="contacto_emergencia_parentesco"
                                maxlength="50"
                                class="sita-control"
                                value="<?= sita_h(
                                    $contactoEmergenciaParentesco
                                ) ?>"
                            >

                        </div>

                    </div>


                </div>

            </div>

        </section>


        <!-- =====================================================
             4. CARACTERIZACIÓN SOCIOECONÓMICA
        ====================================================== -->

        <section class="sita-section">


            <div class="sita-section-header">

                <div class="sita-section-number">
                    4
                </div>

                <div>

                    <h2 class="sita-section-title">
                        Caracterización socioeconómica
                    </h2>

                    <p class="sita-section-subtitle">
                        Población a la que pertenece actualmente el aprendiz.
                    </p>

                </div>

            </div>


            <div class="sita-section-body">


                <div class="sita-resumen">

                    <div class="sita-resumen-title">
                        Registro actual
                    </div>

                    <div class="sita-resumen-text">

                        La caracterización mantiene el historial
                        de los cambios de población del aprendiz.

                    </div>

                </div>


                <div class="row g-3">


                    <div class="col-md-7">

                        <div class="sita-field">

                            <label for="id_tipo_poblacion">

                                Tipo de población

                                <span class="sita-required">
                                    *
                                </span>

                            </label>


                            <select
                                id="id_tipo_poblacion"
                                name="id_tipo_poblacion"
                                class="sita-control"
                                required
                            >

                                <option value="">
                                    Seleccione tipo de población
                                </option>


                                <?php foreach (
                                    $tiposPoblacion
                                    as $tipo
                                ): ?>

                                    <?php

                                    $idTipo =
                                        (int) (
                                            $tipo['id']
                                            ?? 0
                                        );

                                    $nombreTipo =
                                        (string) (
                                            $tipo['nombre']
                                            ?? ''
                                        );

                                    ?>

                                    <option
                                        value="<?= $idTipo ?>"
                                        <?= (
                                            (string) $idTipoPoblacion
                                            ===
                                            (string) $idTipo
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        <?= sita_h(
                                            $nombreTipo
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>


                            <div class="sita-help">

                                Seleccione
                                <strong>NINGUNA</strong>
                                cuando el aprendiz no pertenezca
                                actualmente a una población especial.

                            </div>

                        </div>

                    </div>


                    <div class="col-md-5">

                        <div class="sita-field">

                            <label for="fecha_inicio_poblacion">

                                Fecha de caracterización

                                <span class="sita-required">
                                    *
                                </span>

                            </label>


                            <input
                                type="date"
                                id="fecha_inicio_poblacion"
                                name="fecha_inicio_poblacion"
                                class="sita-control"
                                value="<?= sita_h(
                                    $fechaInicioPoblacion
                                ) ?>"
                                required
                            >

                        </div>

                    </div>


                    <div class="col-12">

                        <div class="sita-field">

                            <label for="observacion_poblacion">
                                Observación
                            </label>


                            <textarea
                                id="observacion_poblacion"
                                name="observacion_poblacion"
                                class="sita-control"
                                rows="3"
                                maxlength="500"
                                placeholder="Observación relacionada con la caracterización..."
                            ><?= sita_h(
                                $observacionPoblacion
                            ) ?></textarea>

                        </div>

                    </div>


                </div>

            </div>

        </section>


        <!-- =====================================================
             BOTONES
        ====================================================== -->

        <div class="sita-form-actions">


            <a
                href="<?= e(
                    url('/aprendices/detalle?id=' . $idAprendiz)
                ) ?>"
                class="sita-btn sita-btn-secondary"
            >

                ← Volver al detalle

            </a>


            <button
                type="submit"
                class="sita-btn sita-btn-primary"
            >

                ✓
                Guardar caracterización

            </button>


        </div>


    </form>


    <!-- =========================================================
         5. HISTORIAL
    ========================================================== -->

    <section class="sita-history">


        <div class="sita-history-header">

            <h2 class="sita-history-title">
                Historial de caracterización
            </h2>

            <p class="sita-history-subtitle">
                Registro histórico de las poblaciones del aprendiz.
            </p>

        </div>


        <?php if (empty($historial)): ?>


            <div class="sita-empty">

                <strong>
                    No existen registros históricos.
                </strong>

                No hay registros históricos
                de caracterización para este aprendiz.

            </div>


        <?php else: ?>


            <div class="table-responsive">

                <table class="table sita-table">


                    <thead>

                        <tr>

                            <th>
                                Tipo de población
                            </th>

                            <th>
                                Fecha inicio
                            </th>

                            <th>
                                Fecha fin
                            </th>

                            <th>
                                Observación
                            </th>

                            <th>
                                Estado
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php foreach (
                            $historial
                            as $registro
                        ): ?>


                            <?php

                            $nombrePoblacion =
                                $registro[
                                    'tipo_poblacion'
                                ]
                                ??
                                $registro['nombre_poblacion']
                                ??
                                $registro['nombre']
                                ??
                                '';

                            $fechaInicio =
                                $registro[
                                    'fecha_inicio'
                                ]
                                ?? null;

                            $fechaFin =
                                $registro[
                                    'fecha_fin'
                                ]
                                ?? null;

                            $observacion =
                                $registro[
                                    'observacion'
                                ]
                                ?? '';

                            $esActual =
                                empty(
                                    $fechaFin
                                );

                            ?>


                            <tr>


                                <td>

                                    <strong>

                                        <?= sita_h(
                                            $nombrePoblacion
                                        ) ?>

                                    </strong>

                                </td>


                                <td>

                                    <?= sita_h(
                                        sita_fecha(
                                            $fechaInicio
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <?= $fechaFin
                                        ? sita_h(
                                            sita_fecha(
                                                $fechaFin
                                            )
                                        )
                                        : '—'
                                    ?>

                                </td>


                                <td>

                                    <?= $observacion !== ''
                                        ? sita_h(
                                            $observacion
                                        )
                                        : '—'
                                    ?>

                                </td>


                                <td>

                                    <?php if ($esActual): ?>

                                        <span class="sita-actual">
                                            ACTUAL
                                        </span>

                                    <?php else: ?>

                                        <span class="sita-historico">
                                            HISTÓRICO
                                        </span>

                                    <?php endif; ?>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    </tbody>


                </table>

            </div>


        <?php endif; ?>


    </section>


</main>


<?php

require_once ROOT_PATH . '/views/layout/footer.php';

?>