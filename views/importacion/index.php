<?php

declare(strict_types=1);

$error = $error ?? null;

require_once ROOT_PATH . '/views/layout/header.php';

require_once ROOT_PATH . '/views/layout/navbar.php';

?>

<style>

    .sita-importar-page {
        width: 100%;
        max-width: 1180px;
        margin: 0 auto;
        padding: 34px 28px 50px;
        font-family:
            Inter,
            -apple-system,
            BlinkMacSystemFont,
            "Segoe UI",
            Roboto,
            Arial,
            sans-serif;
    }


    /* =========================================================
       ENCABEZADO
       ========================================================= */

    .sita-importar-header {
        margin-bottom: 24px;
    }

    .sita-importar-title {
        margin: 0;
        color: #0B263D;
        font-size: 28px;
        font-weight: 800;
        line-height: 1.2;
    }

    .sita-importar-subtitle {
        margin: 8px 0 0;
        color: #71808B;
        font-size: 14px;
        line-height: 1.6;
    }


    /* =========================================================
       TARJETA PRINCIPAL
       ========================================================= */

    .sita-importar-card {
        background: #FFFFFF;
        border: 1px solid #E2E9ED;
        border-radius: 16px;
        box-shadow:
            0 6px 24px rgba(11, 38, 61, .07);
        overflow: hidden;
    }


    /* =========================================================
       CABECERA DE LA TARJETA
       ========================================================= */

    .sita-importar-card-header {
        padding: 22px 26px;
        border-bottom: 1px solid #E8EDF0;
        background: #FBFCFD;
    }

    .sita-importar-card-header h2 {
        margin: 0;
        color: #0B263D;
        font-size: 17px;
        font-weight: 750;
    }

    .sita-importar-card-header p {
        margin: 6px 0 0;
        color: #71808B;
        font-size: 13px;
        line-height: 1.5;
    }


    /* =========================================================
       CONTENIDO
       ========================================================= */

    .sita-importar-card-body {
        padding: 28px;
    }


    /* =========================================================
       ERROR
       ========================================================= */

    .sita-importar-alerta {
        display: flex;
        align-items: flex-start;
        gap: 10px;

        margin-bottom: 22px;
        padding: 14px 16px;

        border: 1px solid #F5C2C7;
        border-radius: 10px;

        background: #FDEEEE;
        color: #842029;

        font-size: 13px;
        line-height: 1.5;
    }

    .sita-importar-alerta-icono {
        flex: 0 0 auto;
        font-size: 17px;
        line-height: 1;
    }


    /* =========================================================
       ZONA DE CARGA
       ========================================================= */

    .sita-importar-upload {
        border: 2px dashed #C8D4DA;
        border-radius: 14px;

        padding: 36px 28px;

        background: #F8FAFB;

        text-align: center;

        transition:
            border-color .15s ease,
            background-color .15s ease;
    }

    .sita-importar-upload:hover {
        border-color: #19A7A8;
        background: #F5FAFA;
    }


    .sita-importar-upload-icon {
        width: 58px;
        height: 58px;

        margin: 0 auto 16px;

        border-radius: 14px;

        display: flex;
        align-items: center;
        justify-content: center;

        background: #EAF7F7;
        color: #13898A;

        font-size: 27px;
    }


    .sita-importar-upload-title {
        margin: 0;

        color: #0B263D;

        font-size: 17px;
        font-weight: 750;
    }


    .sita-importar-upload-text {
        max-width: 650px;

        margin: 8px auto 22px;

        color: #71808B;

        font-size: 13px;
        line-height: 1.6;
    }


    /* =========================================================
       INPUT ARCHIVO
       ========================================================= */

    .sita-importar-file-wrapper {
        max-width: 650px;
        margin: 0 auto;
    }

    .sita-importar-file {
        display: block;

        width: 100%;

        padding: 12px;

        border: 1px solid #D4DEE3;
        border-radius: 9px;

        background: #FFFFFF;

        color: #52626E;

        font-family: inherit;
        font-size: 13px;

        cursor: pointer;
    }

    .sita-importar-file:hover {
        border-color: #19A7A8;
    }


    /* =========================================================
       BOTÓN
       ========================================================= */

    .sita-importar-actions {
        margin-top: 18px;

        display: flex;
        justify-content: center;
    }

    .sita-importar-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;

        min-width: 190px;

        padding: 12px 22px;

        border: 0;
        border-radius: 9px;

        background: #13898A;
        color: #FFFFFF;

        font-family: inherit;
        font-size: 14px;
        font-weight: 700;

        cursor: pointer;

        box-shadow:
            0 5px 14px rgba(19, 137, 138, .18);

        transition:
            background-color .15s ease,
            transform .15s ease,
            box-shadow .15s ease;
    }

    .sita-importar-button:hover {
        background: #107778;

        transform: translateY(-1px);

        box-shadow:
            0 7px 18px rgba(19, 137, 138, .22);
    }

    .sita-importar-button:active {
        transform: translateY(0);
    }


    /* =========================================================
       INFORMACIÓN DEL FORMATO
       ========================================================= */

    .sita-importar-formato {
        margin-top: 14px;

        color: #71808B;

        font-size: 12px;
        line-height: 1.5;
    }


    /* =========================================================
       INFORMACIÓN DE SEGURIDAD
       ========================================================= */

    .sita-importar-security {
        margin-top: 22px;

        padding: 16px 18px;

        border: 1px solid #CFEBDD;
        border-radius: 10px;

        background: #F0FAF4;
        color: #146C43;

        font-size: 13px;
        line-height: 1.6;
    }

    .sita-importar-security-title {
        display: block;

        margin-bottom: 4px;

        font-weight: 750;
    }


    /* =========================================================
       INFORMACIÓN INFERIOR
       ========================================================= */

    .sita-importar-info-grid {
        display: grid;

        grid-template-columns:
            repeat(3, minmax(0, 1fr));

        gap: 14px;

        margin-top: 22px;
    }

    .sita-importar-info-item {
        padding: 16px;

        border: 1px solid #E2E9ED;
        border-radius: 11px;

        background: #FFFFFF;
    }

    .sita-importar-info-label {
        display: block;

        margin-bottom: 5px;

        color: #71808B;

        font-size: 11px;
        font-weight: 700;

        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .sita-importar-info-value {
        display: block;

        color: #263746;

        font-size: 13px;
        font-weight: 650;
    }


    /* =========================================================
       RESPONSIVE
       ========================================================= */

    @media (max-width: 800px) {

        .sita-importar-page {
            padding:
                26px
                18px
                40px;
        }

        .sita-importar-title {
            font-size: 24px;
        }

        .sita-importar-card-body {
            padding: 20px;
        }

        .sita-importar-info-grid {
            grid-template-columns:
                1fr;
        }

    }


    @media (max-width: 500px) {

        .sita-importar-page {
            padding:
                22px
                12px
                32px;
        }

        .sita-importar-card-header {
            padding: 18px;
        }

        .sita-importar-card-body {
            padding: 16px;
        }

        .sita-importar-upload {
            padding:
                28px
                16px;
        }

        .sita-importar-title {
            font-size: 22px;
        }

        .sita-importar-button {
            width: 100%;
        }

    }

</style>


<main class="sita-importar-page">


    <!-- =====================================================
         ENCABEZADO
         ===================================================== -->

    <div class="sita-importar-header">

        <h1 class="sita-importar-title">
            Importar reporte SENA
        </h1>

        <p class="sita-importar-subtitle">
            Cargue el reporte oficial de juicios de evaluación
            para actualizar la información de trazabilidad de
            los aprendices.
        </p>

    </div>


    <!-- =====================================================
         TARJETA PRINCIPAL
         ===================================================== -->

    <section class="sita-importar-card">


        <div class="sita-importar-card-header">

            <h2>
                Cargar archivo de evaluación
            </h2>

            <p>
                Seleccione el archivo Excel generado desde
                el sistema de información del SENA.
            </p>

        </div>


        <div class="sita-importar-card-body">


            <?php if ($error): ?>

                <div class="sita-importar-alerta">

                    <span class="sita-importar-alerta-icono">
                        ⚠️
                    </span>

                    <span>
                        <?= e((string) $error) ?>
                    </span>

                </div>

            <?php endif; ?>


            <form
                action="<?= e(url('/importar/leer')) ?>"
                method="POST"
                enctype="multipart/form-data"
            >

                <?= csrfField() ?>


                <div class="sita-importar-upload">


                    <div class="sita-importar-upload-icon">
                        📄
                    </div>


                    <h3 class="sita-importar-upload-title">
                        Seleccione el reporte SENA
                    </h3>


                    <p class="sita-importar-upload-text">

                        Seleccione el archivo oficial correspondiente
                        al <strong>Reporte de Juicios de Evaluación</strong>.
                        El sistema validará y procesará la información
                        antes de realizar los cambios en la base de datos.

                    </p>


                    <div class="sita-importar-file-wrapper">

                        <input
                            type="file"
                            name="archivo"
                            class="sita-importar-file"
                            accept=".xlsx,.xls"
                            required
                        >

                    </div>


                    <div class="sita-importar-actions">

                        <button
                            type="submit"
                            class="sita-importar-button"
                        >

                            <span>
                                ⬆
                            </span>

                            <span>
                                Importar archivo
                            </span>

                        </button>

                    </div>


                    <div class="sita-importar-formato">

                        Formatos permitidos:
                        <strong>XLSX</strong> y
                        <strong>XLS</strong>.
                        Tamaño máximo: <strong>20 MB</strong>.

                    </div>


                </div>


            </form>


            <!-- =================================================
                 SEGURIDAD
                 ================================================= -->

            <div class="sita-importar-security">

                <span class="sita-importar-security-title">
                    🔒 Importación segura
                </span>

                Al cargar el archivo, los datos se procesan
                dentro de una sola transacción. Si ocurre un
                error durante el proceso, los cambios no se
                guardan. Cargar nuevamente el mismo archivo
                no debe generar registros duplicados.

            </div>


            <!-- =================================================
                 INFORMACIÓN
                 ================================================= -->

            <div class="sita-importar-info-grid">


                <div class="sita-importar-info-item">

                    <span class="sita-importar-info-label">
                        Archivo
                    </span>

                    <span class="sita-importar-info-value">
                        Reporte de Juicios de Evaluación
                    </span>

                </div>


                <div class="sita-importar-info-item">

                    <span class="sita-importar-info-label">
                        Formato
                    </span>

                    <span class="sita-importar-info-value">
                        Excel XLSX / XLS
                    </span>

                </div>


                <div class="sita-importar-info-item">

                    <span class="sita-importar-info-label">
                        Tamaño máximo
                    </span>

                    <span class="sita-importar-info-value">
                        20 MB
                    </span>

                </div>


            </div>


        </div>

    </section>


</main>


<?php

require_once ROOT_PATH . '/views/layout/footer.php';

?>