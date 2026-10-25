<?php

declare(strict_types=1);

require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';

$mensajeError = flash('error');

$nombreCompleto = trim(
    ($profesional['nombres'] ?? '')
    . ' '
    . ($profesional['apellidos'] ?? '')
);

$correoPersona = trim(
    (string) ($profesional['correo'] ?? '')
);

$idProfesional = (int) ($profesional['id'] ?? 0);

?>

<style>

    /*
     * ============================================================
     * PÁGINA
     * ============================================================
     */

    .sita-page {
        padding: 30px;
        background: #f5f7fa;
        min-height: calc(100vh - 140px);
    }

    /*
     * ============================================================
     * ENCABEZADO
     * ============================================================
     */

    .sita-page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 25px;
    }

    .sita-page-title {
        margin: 0;
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
    }

    .sita-page-subtitle {
        margin: 6px 0 0;
        color: #6b7280;
        font-size: 14px;
        line-height: 1.5;
    }

    .sita-page-header-actions {
        display: flex;
        align-items: center;
        flex-shrink: 0;
    }

    /*
     * ============================================================
     * BOTONES
     * ============================================================
     */

    .sita-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 42px;
        padding: 0 16px;
        border-radius: 9px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
        box-sizing: border-box;
    }

    .sita-btn-primary {
        background: #2563eb;
        color: #ffffff;
    }

    .sita-btn-primary:hover {
        background: #1d4ed8;
        color: #ffffff;
    }

    .sita-btn-secondary {
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #e5e7eb;
    }

    .sita-btn-secondary:hover {
        background: #e5e7eb;
        color: #111827;
    }

    /*
     * ============================================================
     * CONTENEDOR PRINCIPAL
     * ============================================================
     */

    .sita-access-grid {
        display: grid;
        grid-template-columns: minmax(280px, 0.85fr) minmax(420px, 1.5fr);
        gap: 22px;
        align-items: start;
    }

    .sita-panel {
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    .sita-panel-header {
        padding: 20px 22px;
        border-bottom: 1px solid #e5e7eb;
        background: #ffffff;
    }

    .sita-panel-title {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: #1f2937;
    }

    .sita-panel-subtitle {
        margin: 5px 0 0;
        font-size: 13px;
        color: #6b7280;
        line-height: 1.5;
    }

    .sita-panel-body {
        padding: 22px;
    }

    /*
     * ============================================================
     * PROFESIONAL SELECCIONADO
     * ============================================================
     */

    .sita-profesional-card {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 17px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 11px;
    }

    .sita-profesional-avatar {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: 50%;
        background: #eef2ff;
        color: #3730a3;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 21px;
        font-weight: 700;
    }

    .sita-profesional-info {
        min-width: 0;
    }

    .sita-profesional-nombre {
        display: block;
        margin-bottom: 4px;
        font-size: 15px;
        font-weight: 700;
        color: #1f2937;
        word-break: break-word;
    }

    .sita-profesional-correo {
        display: block;
        font-size: 13px;
        color: #6b7280;
        word-break: break-word;
    }

    .sita-info-list {
        margin-top: 20px;
        display: flex;
        flex-direction: column;
        gap: 13px;
    }

    .sita-info-item {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 15px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f0f2f5;
    }

    .sita-info-item:last-child {
        padding-bottom: 0;
        border-bottom: none;
    }

    .sita-info-label {
        font-size: 12px;
        color: #9ca3af;
        font-weight: 600;
    }

    .sita-info-value {
        font-size: 13px;
        color: #374151;
        font-weight: 600;
        text-align: right;
        word-break: break-word;
    }

    /*
     * ============================================================
     * FORMULARIO
     * ============================================================
     */

    .sita-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .sita-form-group {
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    .sita-form-group-full {
        grid-column: 1 / -1;
    }

    .sita-form-label {
        font-size: 13px;
        font-weight: 700;
        color: #374151;
    }

    .sita-required {
        color: #dc2626;
    }

    .sita-form-control {
        width: 100%;
        min-height: 44px;
        padding: 10px 13px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        background: #ffffff;
        color: #374151;
        font-size: 14px;
        outline: none;
        transition: all 0.2s ease;
        box-sizing: border-box;
    }

    .sita-form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
    }

    .sita-form-control::placeholder {
        color: #9ca3af;
    }

    .sita-form-help {
        margin: 0;
        font-size: 12px;
        color: #9ca3af;
        line-height: 1.4;
    }

    /*
     * ============================================================
     * MENSAJE DE ERROR
     * ============================================================
     */

    .sita-alert-error {
        margin-bottom: 20px;
        padding: 13px 15px;
        border-radius: 9px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        font-size: 13px;
        line-height: 1.5;
    }

    /*
     * ============================================================
     * INFORMACIÓN
     * ============================================================
     */

    .sita-info-box {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        margin-top: 20px;
        padding: 14px 15px;
        border-radius: 10px;
        background: #eff6ff;
        border: 1px solid #dbeafe;
    }

    .sita-info-icon {
        width: 24px;
        height: 24px;
        min-width: 24px;
        border-radius: 50%;
        background: #2563eb;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
    }

    .sita-info-text {
        margin: 0;
        color: #374151;
        font-size: 12px;
        line-height: 1.55;
    }

    .sita-info-text strong {
        color: #1f2937;
    }

    /*
     * ============================================================
     * ACCIONES DEL FORMULARIO
     * ============================================================
     */

    .sita-form-actions {
        margin-top: 22px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
    }

    /*
     * ============================================================
     * RESPONSIVE
     * ============================================================
     */

    @media (max-width: 900px) {

        .sita-access-grid {
            grid-template-columns: 1fr;
        }

    }

    @media (max-width: 768px) {

        .sita-page {
            padding: 20px 15px;
        }

        .sita-page-header {
            flex-direction: column;
        }

        .sita-page-title {
            font-size: 24px;
        }

        .sita-page-header-actions {
            width: 100%;
        }

        .sita-page-header-actions .sita-btn {
            width: 100%;
        }

        .sita-form-grid {
            grid-template-columns: 1fr;
        }

        .sita-form-group-full {
            grid-column: auto;
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

<div class="sita-page">


<!-- =========================================================
     ENCABEZADO
     ========================================================= -->

<div class="sita-page-header">

    <div>

        <h1 class="sita-page-title">
            Crear acceso al sistema
        </h1>

        <p class="sita-page-subtitle">
            Cree las credenciales de acceso para el profesional seleccionado.
        </p>

    </div>


    <div class="sita-page-header-actions">

        <a
            href="<?= e(
                url(
                    '/profesionales/detalle?id=' .
                    $idProfesional
                )
            ) ?>"
            class="sita-btn sita-btn-secondary"
        >
            ← Volver al detalle
        </a>

    </div>

</div>


<!-- =========================================================
     CONTENIDO
     ========================================================= -->

<div class="sita-access-grid">

    <!-- =====================================================
         PROFESIONAL
         ===================================================== -->

    <div class="sita-panel">

        <div class="sita-panel-header">

            <h2 class="sita-panel-title">
                Profesional seleccionado
            </h2>

            <p class="sita-panel-subtitle">
                Persona a la que se asociará el acceso.
            </p>

        </div>


        <div class="sita-panel-body">

            <div class="sita-profesional-card">

                <div class="sita-profesional-avatar">
                    <?= e(
                        strtoupper(
                            mb_substr(
                                $nombreCompleto !== ''
                                    ? $nombreCompleto
                                    : 'P',
                                0,
                                1
                            )
                        )
                    ) ?>
                </div>


                <div class="sita-profesional-info">

                    <span class="sita-profesional-nombre">
                        <?= e(
                            $nombreCompleto !== ''
                                ? $nombreCompleto
                                : 'Profesional'
                        ) ?>
                    </span>


                    <?php if ($correoPersona !== ''): ?>

                        <span class="sita-profesional-correo">
                            <?= e($correoPersona) ?>
                        </span>

                    <?php endif; ?>

                </div>

            </div>


            <div class="sita-info-list">

                <div class="sita-info-item">

                    <span class="sita-info-label">
                        ID profesional
                    </span>

                    <span class="sita-info-value">
                        #<?= $idProfesional ?>
                    </span>

                </div>


                <?php if ($correoPersona !== ''): ?>

                    <div class="sita-info-item">

                        <span class="sita-info-label">
                            Correo registrado
                        </span>

                        <span class="sita-info-value">
                            <?= e($correoPersona) ?>
                        </span>

                    </div>

                <?php endif; ?>

            </div>


            <div class="sita-info-box">

                <div class="sita-info-icon">
                    i
                </div>

                <p class="sita-info-text">
                    El acceso se asociará directamente con este
                    profesional. Después de crear la cuenta,
                    <strong>el rol y los permisos se administran
                    por separado</strong> desde el módulo de
                    administración.
                </p>

            </div>

        </div>

    </div>


    <!-- =====================================================
         CREDENCIALES
         ===================================================== -->

    <div class="sita-panel">

        <div class="sita-panel-header">

            <h2 class="sita-panel-title">
                Credenciales de acceso
            </h2>

            <p class="sita-panel-subtitle">
                Defina el usuario y la contraseña que utilizará
                para ingresar al sistema.
            </p>

        </div>


        <div class="sita-panel-body">


            <?php if (!empty($mensajeError)): ?>

                <div class="sita-alert-error">

                    <?= e((string) $mensajeError) ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                action="<?= e(url('/profesionales/acceso')) ?>"
                autocomplete="off"
            >

                <?= csrfField() ?>


                <input
                    type="hidden"
                    name="id_profesional"
                    value="<?= $idProfesional ?>"
                >


                <div class="sita-form-grid">


                    <!-- USUARIO -->

                    <div class="sita-form-group sita-form-group-full">

                        <label
                            for="username"
                            class="sita-form-label"
                        >
                            Nombre de usuario
                            <span class="sita-required">*</span>
                        </label>


                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="sita-form-control"
                            maxlength="80"
                            autocomplete="username"
                            placeholder="Ingrese el nombre de usuario"
                            required
                        >


                        <p class="sita-form-help">
                            Este será el identificador utilizado
                            para iniciar sesión en SITA.
                        </p>

                    </div>


                    <!-- CORREO -->

                    <div class="sita-form-group sita-form-group-full">

                        <label
                            for="correo"
                            class="sita-form-label"
                        >
                            Correo electrónico
                            <span class="sita-required">*</span>
                        </label>


                        <input
                            type="email"
                            id="correo"
                            name="correo"
                            class="sita-form-control"
                            maxlength="150"
                            value="<?= e($correoPersona) ?>"
                            autocomplete="email"
                            placeholder="correo@ejemplo.com"
                            required
                        >

                    </div>


                    <!-- CONTRASEÑA -->

                    <div class="sita-form-group">

                        <label
                            for="password"
                            class="sita-form-label"
                        >
                            Contraseña
                            <span class="sita-required">*</span>
                        </label>


                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="sita-form-control"
                            minlength="8"
                            autocomplete="new-password"
                            placeholder="Mínimo 8 caracteres"
                            required
                        >

                    </div>


                    <!-- CONFIRMAR CONTRASEÑA -->

                    <div class="sita-form-group">

                        <label
                            for="password_confirmacion"
                            class="sita-form-label"
                        >
                            Confirmar contraseña
                            <span class="sita-required">*</span>
                        </label>


                        <input
                            type="password"
                            id="password_confirmacion"
                            name="password_confirmacion"
                            class="sita-form-control"
                            minlength="8"
                            autocomplete="new-password"
                            placeholder="Repita la contraseña"
                            required
                        >

                    </div>

                </div>


                <!-- INFORMACIÓN -->

                <div class="sita-info-box">

                    <div class="sita-info-icon">
                        i
                    </div>

                    <p class="sita-info-text">
                        <strong>Importante:</strong>
                        la creación de este acceso no asigna
                        automáticamente un rol ni permisos.
                        Estos serán definidos posteriormente
                        desde la administración del sistema.
                    </p>

                </div>


                <!-- ACCIONES -->

                <div class="sita-form-actions">

                    <a
                        href="<?= e(
                            url(
                                '/profesionales/detalle?id=' .
                                $idProfesional
                            )
                        ) ?>"
                        class="sita-btn sita-btn-secondary"
                    >
                        Cancelar
                    </a>


                    <button
                        type="submit"
                        class="sita-btn sita-btn-primary"
                    >
                        Crear acceso
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


</div>

<?php

require_once ROOT_PATH . '/views/layout/footer.php';

?>
