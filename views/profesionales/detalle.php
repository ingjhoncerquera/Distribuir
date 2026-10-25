<?php

declare(strict_types=1);

$profesional = $profesional ?? [];
$usuario = $usuario ?? null;
$roles = $roles ?? [];
$rolesDisponibles = $rolesDisponibles ?? [];
$permisos = $permisos ?? [];

?>

<?php require ROOT_PATH . '/views/layout/header.php'; ?>

<style>
    .sita-page {
        padding: 30px 0 50px;
    }

    .sita-page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 25px;
        flex-wrap: wrap;
    }

    .sita-page-header-content h1 {
        margin: 0;
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
    }

    .sita-page-header-content p {
        margin: 6px 0 0;
        color: #6b7280;
        font-size: 14px;
    }

    .sita-page-header-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .sita-card {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
        margin-bottom: 22px;
        overflow: hidden;
    }

    .sita-card-header {
        padding: 18px 22px;
        border-bottom: 1px solid #e5e7eb;
        background: #f9fafb;
    }

    .sita-card-header h2 {
        margin: 0;
        font-size: 17px;
        font-weight: 700;
        color: #1f2937;
    }

    .sita-card-header p {
        margin: 5px 0 0;
        font-size: 13px;
        color: #6b7280;
    }

    .sita-card-body {
        padding: 24px;
    }

    .sita-info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0;
    }

    .sita-info-item {
        padding: 15px 18px;
        border-bottom: 1px solid #f0f0f0;
    }

    .sita-info-item:nth-child(odd) {
        border-right: 1px solid #f0f0f0;
    }

    .sita-info-label {
        display: block;
        margin-bottom: 5px;
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .sita-info-value {
        font-size: 15px;
        font-weight: 500;
        color: #1f2937;
        word-break: break-word;
    }

    .sita-info-value.empty {
        color: #9ca3af;
        font-style: italic;
    }

    .sita-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 11px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
    }

    .sita-status-active {
        background: #dcfce7;
        color: #166534;
    }

    .sita-status-inactive {
        background: #fee2e2;
        color: #991b1b;
    }

    .sita-status-blocked {
        background: #fef3c7;
        color: #92400e;
    }

    .sita-empty {
        padding: 20px;
        text-align: center;
        color: #6b7280;
        font-size: 14px;
    }

    .sita-role-list,
    .sita-permission-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .sita-role {
        display: inline-flex;
        align-items: center;
        padding: 7px 12px;
        border-radius: 20px;
        background: #e8f5e9;
        color: #166534;
        border: 1px solid #bbf7d0;
        font-size: 13px;
        font-weight: 600;
    }

    .sita-permission {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 7px;
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #e5e7eb;
        font-size: 12px;
        font-weight: 500;
    }

    /* ==========================================================
       CUENTA DE USUARIO
    ========================================================== */

    .sita-account-box {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #ffffff;
        overflow: hidden;
    }

    .sita-account-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 20px 22px;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        flex-wrap: wrap;
    }

    .sita-account-identity {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .sita-account-avatar {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #198754;
        color: #ffffff;
        font-size: 20px;
        font-weight: 700;
    }

    .sita-account-identity-info {
        min-width: 0;
    }

    .sita-account-identity-title {
        margin: 0;
        color: #1f2937;
        font-size: 16px;
        font-weight: 700;
        word-break: break-word;
    }

    .sita-account-identity-subtitle {
        margin-top: 3px;
        color: #6b7280;
        font-size: 13px;
        word-break: break-word;
    }

    .sita-account-status {
        display: flex;
        align-items: center;
    }

    .sita-account-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .sita-account-item {
        padding: 17px 20px;
        border-bottom: 1px solid #f0f0f0;
    }

    .sita-account-item:nth-child(odd) {
        border-right: 1px solid #f0f0f0;
    }

    .sita-account-label {
        display: block;
        margin-bottom: 6px;
        font-size: 11px;
        color: #6b7280;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .sita-account-value {
        font-size: 14px;
        color: #1f2937;
        font-weight: 600;
        word-break: break-word;
    }

    .sita-account-value.empty {
        color: #9ca3af;
        font-style: italic;
        font-weight: 400;
    }

    .sita-account-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        padding: 15px 20px;
        background: #fafafa;
        flex-wrap: wrap;
    }

    .sita-account-footer-info {
        color: #6b7280;
        font-size: 12px;
    }

    .sita-account-footer-info strong {
        color: #374151;
    }

    .sita-account-empty {
        border: 1px dashed #d1d5db;
        border-radius: 12px;
        background: #fafafa;
        padding: 28px 20px;
        text-align: center;
    }

    .sita-account-empty-icon {
        width: 50px;
        height: 50px;
        margin: 0 auto 12px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f3f4f6;
        color: #6b7280;
        font-size: 21px;
        font-weight: 700;
    }

    .sita-account-empty-title {
        margin: 0;
        color: #374151;
        font-size: 15px;
        font-weight: 700;
    }

    .sita-account-empty-text {
        margin: 6px auto 0;
        max-width: 520px;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.5;
    }

    .sita-account-empty-action {
        display: flex;
        justify-content: center;
        margin-top: 18px;
    }

    /* ==========================================================
       ALERTAS Y BOTONES
    ========================================================== */

    .sita-alert {
        margin-bottom: 22px;
        padding: 13px 16px;
        border-radius: 8px;
        font-size: 14px;
    }

    .sita-alert-success {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
    }

    .sita-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        padding: 9px 17px;
        border-radius: 8px;
        text-decoration: none;
        border: 1px solid transparent;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .sita-btn-primary {
        background: #198754;
        border-color: #198754;
        color: #ffffff;
    }

    .sita-btn-primary:hover {
        background: #157347;
        border-color: #157347;
        color: #ffffff;
    }

    .sita-btn-secondary {
        background: #ffffff;
        border-color: #d1d5db;
        color: #374151;
    }

    .sita-btn-secondary:hover {
        background: #f3f4f6;
        color: #1f2937;
    }

    .sita-btn-disabled {
        background: #f3f4f6;
        border-color: #e5e7eb;
        color: #9ca3af;
        cursor: not-allowed;
    }

    /* ==========================================================
       ADMINISTRACIÓN DE ROLES
    ========================================================== */

    .sita-role-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        flex-wrap: wrap;
    }

    .sita-role-card-action {
        flex-shrink: 0;
    }

    /* ==========================================================
       MODAL DE ROLES
    ========================================================== */

    .sita-modal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(17, 24, 39, 0.58);
    }

    .sita-modal.sita-modal-open {
        display: flex;
    }

    .sita-modal-dialog {
        width: 100%;
        max-width: 620px;
        max-height: calc(100vh - 40px);
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.20);
        overflow: hidden;
        animation: sitaModalEntrada 0.18s ease-out;
    }

    @keyframes sitaModalEntrada {
        from {
            opacity: 0;
            transform: translateY(-10px) scale(0.98);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .sita-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        padding: 20px 22px;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
    }

    .sita-modal-header-content {
        min-width: 0;
    }

    .sita-modal-title {
        margin: 0;
        color: #1f2937;
        font-size: 19px;
        font-weight: 700;
    }

    .sita-modal-subtitle {
        margin: 5px 0 0;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.45;
    }

    .sita-modal-close {
        width: 36px;
        height: 36px;
        min-width: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #ffffff;
        color: #6b7280;
        font-size: 20px;
        line-height: 1;
        cursor: pointer;
    }

    .sita-modal-close:hover {
        background: #f3f4f6;
        color: #1f2937;
    }

    .sita-modal-body {
        padding: 22px;
        overflow-y: auto;
        max-height: calc(100vh - 190px);
    }

    .sita-modal-info {
        padding: 13px 15px;
        margin-bottom: 18px;
        border-radius: 9px;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
        font-size: 13px;
        line-height: 1.5;
    }

    .sita-modal-section-title {
        margin: 0 0 12px;
        color: #374151;
        font-size: 14px;
        font-weight: 700;
    }

    .sita-role-options {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .sita-role-option {
        position: relative;
    }

    .sita-role-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .sita-role-option label {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        min-height: 72px;
        padding: 13px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #ffffff;
        cursor: pointer;
        transition: all 0.18s ease;
    }

    .sita-role-option label:hover {
        border-color: #86efac;
        background: #f9fffb;
    }

    .sita-role-option input:checked + label {
        border-color: #198754;
        background: #f0fdf4;
        box-shadow: 0 0 0 1px #198754;
    }

    .sita-role-check {
        width: 20px;
        height: 20px;
        min-width: 20px;
        margin-top: 1px;
        border: 2px solid #d1d5db;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: transparent;
        font-size: 12px;
        font-weight: 700;
        transition: all 0.18s ease;
    }

    .sita-role-option input:checked + label .sita-role-check {
        background: #198754;
        border-color: #198754;
        color: #ffffff;
    }

    .sita-role-option-content {
        min-width: 0;
    }

    .sita-role-option-name {
        display: block;
        color: #1f2937;
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .sita-role-option-description {
        display: block;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.4;
    }

    .sita-modal-empty {
        padding: 20px;
        text-align: center;
        border: 1px dashed #d1d5db;
        border-radius: 10px;
        background: #fafafa;
        color: #6b7280;
        font-size: 13px;
    }

    .sita-modal-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        padding: 16px 22px;
        border-top: 1px solid #e5e7eb;
        background: #fafafa;
    }

    body.sita-modal-body-open {
        overflow: hidden;
    }

    @media (max-width: 768px) {

        .sita-page {
            padding: 20px 0 40px;
        }

        .sita-page-header {
            align-items: flex-start;
        }

        .sita-page-header-actions {
            width: 100%;
        }

        .sita-page-header-actions .sita-btn {
            width: 100%;
        }

        .sita-card-body {
            padding: 18px;
        }

        .sita-info-grid,
        .sita-account-grid {
            grid-template-columns: 1fr;
        }

        .sita-info-item:nth-child(odd),
        .sita-account-item:nth-child(odd) {
            border-right: none;
        }

        .sita-btn {
            width: 100%;
        }

        .sita-account-top {
            align-items: flex-start;
        }

        .sita-account-status {
            width: 100%;
        }

        .sita-account-footer {
            align-items: flex-start;
        }

        .sita-role-card-header {
            align-items: flex-start;
        }

        .sita-role-card-action {
            width: 100%;
        }

        .sita-role-card-action .sita-btn {
            width: 100%;
        }

        .sita-role-options {
            grid-template-columns: 1fr;
        }

        .sita-modal {
            padding: 10px;
        }

        .sita-modal-dialog {
            max-height: calc(100vh - 20px);
            border-radius: 13px;
        }

        .sita-modal-header,
        .sita-modal-body,
        .sita-modal-footer {
            padding-left: 16px;
            padding-right: 16px;
        }

        .sita-modal-footer {
            flex-direction: column-reverse;
        }

        .sita-modal-footer .sita-btn {
            width: 100%;
        }
    }
</style>

<?php

$nombreCompleto = trim(
    (string) ($profesional['nombres'] ?? '')
    . ' '
    . (string) ($profesional['apellidos'] ?? '')
);

$estadoProfesional = strtolower(
    trim(
        (string) ($profesional['estado_profesional']
            ?? $profesional['estado']
            ?? '')
    )
);

$estadoUsuario = strtolower(
    trim(
        (string) ($usuario['estado'] ?? '')
    )
);

/*
|--------------------------------------------------------------------------
| Obtener los IDs de los roles actualmente asignados
|--------------------------------------------------------------------------
*/

$idsRolesAsignados = [];

foreach ($roles as $rol) {

    if (isset($rol['id'])) {

        $idsRolesAsignados[] = (int) $rol['id'];

    }
}

?>

<div class="container-fluid">


<div class="sita-page">

    <div class="sita-page-header">

        <div class="sita-page-header-content">

            <h1>
                Detalle del profesional
            </h1>

            <p>
                Información personal, profesional y de acceso al sistema.
            </p>

        </div>

        <div class="sita-page-header-actions">

            <a
                href="<?= e(url('/profesionales')) ?>"
                class="sita-btn sita-btn-secondary"
            >
                ← Volver
            </a>

            <?php if (Auth::can('profesionales.editar')): ?>

                <a
                    href="<?= e(url('/profesionales/editar?id=' . (int) ($profesional['id'] ?? 0))) ?>"
                    class="sita-btn sita-btn-primary"
                >
                    Editar profesional
                </a>

            <?php endif; ?>

        </div>

    </div>

    <?php

    $mensajeExito = flash('success');

    ?>

    <?php if ($mensajeExito !== null): ?>

        <div class="sita-alert sita-alert-success">

            <?= e($mensajeExito) ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         DATOS PERSONALES
    ====================================================== -->

    <div class="sita-card">

        <div class="sita-card-header">

            <h2>
                Datos personales
            </h2>

            <p>
                Información básica de identificación del profesional.
            </p>

        </div>

        <div class="sita-card-body">

            <div class="sita-info-grid">

                <div class="sita-info-item">

                    <span class="sita-info-label">
                        Tipo de documento
                    </span>

                    <div class="sita-info-value">

                        <?= e(
                            $profesional['tipo_documento']
                            ?? ''
                        ) ?>

                    </div>

                </div>

                <div class="sita-info-item">

                    <span class="sita-info-label">
                        Número de documento
                    </span>

                    <div class="sita-info-value">

                        <?= e(
                            $profesional['documento']
                            ?? ''
                        ) ?>

                    </div>

                </div>

                <div class="sita-info-item">

                    <span class="sita-info-label">
                        Nombres
                    </span>

                    <div class="sita-info-value">

                        <?= e(
                            $profesional['nombres']
                            ?? ''
                        ) ?>

                    </div>

                </div>

                <div class="sita-info-item">

                    <span class="sita-info-label">
                        Apellidos
                    </span>

                    <div class="sita-info-value">

                        <?= e(
                            $profesional['apellidos']
                            ?? ''
                        ) ?>

                    </div>

                </div>

                <div class="sita-info-item">

                    <span class="sita-info-label">
                        Fecha de nacimiento
                    </span>

                    <div class="sita-info-value">

                        <?php if (!empty($profesional['fecha_nacimiento'])): ?>

                            <?= e(
                                fecha(
                                    $profesional['fecha_nacimiento']
                                )
                            ) ?>

                        <?php else: ?>

                            <span class="empty">
                                No registrada
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

                <div class="sita-info-item">

                    <span class="sita-info-label">
                        Sexo
                    </span>

                    <div class="sita-info-value">

                        <?php if (!empty($profesional['sexo'])): ?>

                            <?= e(
                                $profesional['sexo']
                            ) ?>

                        <?php else: ?>

                            <span class="empty">
                                No registrado
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         INFORMACIÓN DE CONTACTO
    ====================================================== -->

    <div class="sita-card">

        <div class="sita-card-header">

            <h2>
                Información de contacto
            </h2>

            <p>
                Datos de comunicación y ubicación.
            </p>

        </div>

        <div class="sita-card-body">

            <div class="sita-info-grid">

                <div class="sita-info-item">

                    <span class="sita-info-label">
                        Teléfono
                    </span>

                    <div class="sita-info-value">

                        <?php if (!empty($profesional['telefono'])): ?>

                            <?= e(
                                $profesional['telefono']
                            ) ?>

                        <?php else: ?>

                            <span class="empty">
                                No registrado
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

                <div class="sita-info-item">

                    <span class="sita-info-label">
                        Correo electrónico
                    </span>

                    <div class="sita-info-value">

                        <?php if (!empty($profesional['correo'])): ?>

                            <?= e(
                                $profesional['correo']
                            ) ?>

                        <?php else: ?>

                            <span class="empty">
                                No registrado
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

                <div class="sita-info-item">

                    <span class="sita-info-label">
                        Dirección
                    </span>

                    <div class="sita-info-value">

                        <?php if (!empty($profesional['direccion'])): ?>

                            <?= e(
                                $profesional['direccion']
                            ) ?>

                        <?php else: ?>

                            <span class="empty">
                                No registrada
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

                <div class="sita-info-item">

                    <span class="sita-info-label">
                        Municipio
                    </span>

                    <div class="sita-info-value">

                        <?php if (!empty($profesional['municipio'])): ?>

                            <?= e(
                                $profesional['municipio']
                            ) ?>

                        <?php else: ?>

                            <span class="empty">
                                No registrado
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

                <div class="sita-info-item">

                    <span class="sita-info-label">
                        Departamento
                    </span>

                    <div class="sita-info-value">

                        <?php if (!empty($profesional['departamento'])): ?>

                            <?= e(
                                $profesional['departamento']
                            ) ?>

                        <?php else: ?>

                            <span class="empty">
                                No registrado
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         INFORMACIÓN PROFESIONAL
    ====================================================== -->

    <div class="sita-card">

        <div class="sita-card-header">

            <h2>
                Información profesional
            </h2>

            <p>
                Información relacionada con el perfil profesional.
            </p>

        </div>

        <div class="sita-card-body">

            <div class="sita-info-grid">

                <div class="sita-info-item">

                    <span class="sita-info-label">
                        Nombre completo
                    </span>

                    <div class="sita-info-value">

                        <?= e(
                            $nombreCompleto
                        ) ?>

                    </div>

                </div>

                <div class="sita-info-item">

                    <span class="sita-info-label">
                        Tipo de profesional
                    </span>

                    <div class="sita-info-value">

                        <?= e(
                            $profesional['tipo_profesional']
                            ?? ''
                        ) ?>

                    </div>

                </div>

                <div class="sita-info-item">

                    <span class="sita-info-label">
                        Registro profesional
                    </span>

                    <div class="sita-info-value">

                        <?php if (!empty($profesional['registro_profesional'])): ?>

                            <?= e(
                                $profesional['registro_profesional']
                            ) ?>

                        <?php else: ?>

                            <span class="empty">
                                No registrado
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

                <div class="sita-info-item">

                    <span class="sita-info-label">
                        Especialidad
                    </span>

                    <div class="sita-info-value">

                        <?php if (!empty($profesional['especialidad'])): ?>

                            <?= e(
                                $profesional['especialidad']
                            ) ?>

                        <?php else: ?>

                            <span class="empty">
                                No registrada
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

                <div class="sita-info-item">

                    <span class="sita-info-label">
                        Estado
                    </span>

                    <div class="sita-info-value">

                        <?php if ($estadoProfesional === 'activo'): ?>

                            <span class="sita-status sita-status-active">
                                ● Activo
                            </span>

                        <?php elseif ($estadoProfesional === 'inactivo'): ?>

                            <span class="sita-status sita-status-inactive">
                                ● Inactivo
                            </span>

                        <?php else: ?>

                            <span class="empty">
                                No definido
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

                <div class="sita-info-item">

                    <span class="sita-info-label">
                        ID profesional
                    </span>

                    <div class="sita-info-value">

                        <?= e(
                            $profesional['id']
                            ?? ''
                        ) ?>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         CUENTA DE USUARIO
    ====================================================== -->

    <div class="sita-card">

        <div class="sita-card-header">

            <h2>
                Acceso al sistema
            </h2>

            <p>
                Información de la cuenta de usuario asociada al profesional.
            </p>

        </div>

        <div class="sita-card-body">

            <?php if (!empty($usuario)): ?>

                <div class="sita-account-box">

                    <div class="sita-account-top">

                        <div class="sita-account-identity">

                            <div class="sita-account-avatar">
                                <?= e(
                                    strtoupper(
                                        substr(
                                            (string) ($usuario['username'] ?? 'U'),
                                            0,
                                            1
                                        )
                                    )
                                ) ?>
                            </div>

                            <div class="sita-account-identity-info">

                                <div class="sita-account-identity-title">
                                    <?= e(
                                        $usuario['username']
                                        ?? ''
                                    ) ?>
                                </div>

                                <div class="sita-account-identity-subtitle">
                                    Cuenta de usuario del sistema
                                </div>

                            </div>

                        </div>

                        <div class="sita-account-status">

                            <?php if ($estadoUsuario === 'activo'): ?>

                                <span class="sita-status sita-status-active">
                                    ● Cuenta activa
                                </span>

                            <?php elseif ($estadoUsuario === 'inactivo'): ?>

                                <span class="sita-status sita-status-inactive">
                                    ● Cuenta inactiva
                                </span>

                            <?php elseif ($estadoUsuario === 'bloqueado'): ?>

                                <span class="sita-status sita-status-blocked">
                                    ● Cuenta bloqueada
                                </span>

                            <?php else: ?>

                                <span class="sita-status sita-status-blocked">
                                    ● Estado no definido
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>


                    <div class="sita-account-grid">

                        <div class="sita-account-item">

                            <span class="sita-account-label">
                                Nombre de usuario
                            </span>

                            <div class="sita-account-value">

                                <?= e(
                                    $usuario['username']
                                    ?? ''
                                ) ?>

                            </div>

                        </div>


                        <div class="sita-account-item">

                            <span class="sita-account-label">
                                Correo de acceso
                            </span>

                            <div class="sita-account-value">

                                <?php if (!empty($usuario['correo'])): ?>

                                    <?= e(
                                        $usuario['correo']
                                    ) ?>

                                <?php else: ?>

                                    <span class="sita-account-value empty">
                                        No registrado
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <div class="sita-account-item">

                            <span class="sita-account-label">
                                Estado de la cuenta
                            </span>

                            <div class="sita-account-value">

                                <?php if ($estadoUsuario === 'activo'): ?>

                                    <span class="sita-status sita-status-active">
                                        ● Activo
                                    </span>

                                <?php elseif ($estadoUsuario === 'inactivo'): ?>

                                    <span class="sita-status sita-status-inactive">
                                        ● Inactivo
                                    </span>

                                <?php elseif ($estadoUsuario === 'bloqueado'): ?>

                                    <span class="sita-status sita-status-blocked">
                                        ● Bloqueado
                                    </span>

                                <?php else: ?>

                                    <span class="sita-account-value empty">
                                        No definido
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <div class="sita-account-item">

                            <span class="sita-account-label">
                                Último acceso
                            </span>

                            <div class="sita-account-value">

                                <?php if (!empty($usuario['ultimo_acceso'])): ?>

                                    <?= e(
                                        fechaHora(
                                            $usuario['ultimo_acceso']
                                        )
                                    ) ?>

                                <?php else: ?>

                                    <span class="sita-account-value empty">
                                        Sin registros de acceso
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>


                    <div class="sita-account-footer">

                        <div class="sita-account-footer-info">

                            <strong>
                                Seguridad:
                            </strong>

                            La contraseña se almacena de forma segura y no
                            se muestra en esta pantalla.

                        </div>

                    </div>

                </div>

            <?php else: ?>

                <div class="sita-account-empty">

                    <div class="sita-account-empty-icon">
                        !
                    </div>

                    <h3 class="sita-account-empty-title">
                        Sin cuenta de usuario
                    </h3>

                    <p class="sita-account-empty-text">
                        Este profesional todavía no tiene una cuenta de
                        usuario asociada para ingresar al sistema.
                    </p>

                    <?php if (
                        Auth::can('usuarios.crear')
                        && $estadoProfesional === 'activo'
                    ): ?>

                        <div class="sita-account-empty-action">

                            <a
                                href="<?= e(
                                    url(
                                        '/profesionales/acceso?id='
                                        . (int) ($profesional['id'] ?? 0)
                                    )
                                ) ?>"
                                class="sita-btn sita-btn-primary"
                            >
                                Crear acceso al sistema
                            </a>

                        </div>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- =====================================================
         ROLES
    ====================================================== -->

    <div class="sita-card">

        <div class="sita-card-header">

            <div class="sita-role-card-header">

                <div>

                    <h2>
                        Roles asignados
                    </h2>

                    <p>
                        Roles actualmente asociados a la cuenta de usuario.
                    </p>

                </div>

                <?php if (
                    !empty($usuario)
                    && Auth::can('roles.editar')
                ): ?>

                    <div class="sita-role-card-action">

                        <button
                            type="button"
                            class="sita-btn sita-btn-primary"
                            id="btnAbrirModalRoles"
                        >
                            Administrar roles
                        </button>

                    </div>

                <?php endif; ?>

            </div>

        </div>

        <div class="sita-card-body">

            <?php if (!empty($roles)): ?>

                <div class="sita-role-list">

                    <?php foreach ($roles as $rol): ?>

                        <span class="sita-role">

                            <?= e(
                                $rol['nombre']
                                ?? ''
                            ) ?>

                        </span>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="sita-empty">
                    No tiene roles asignados actualmente.
                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- =====================================================
         PERMISOS EFECTIVOS
    ====================================================== -->

    <div class="sita-card">

        <div class="sita-card-header">

            <h2>
                Permisos efectivos
            </h2>

            <p>
                Permisos obtenidos a través de los roles asignados.
            </p>

        </div>

        <div class="sita-card-body">

            <?php if (!empty($permisos)): ?>

                <div class="sita-permission-list">

                    <?php foreach ($permisos as $permiso): ?>

                        <span class="sita-permission">

                            <?= e(
                                $permiso['nombre']
                                ?? ''
                            ) ?>

                        </span>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="sita-empty">
                    No hay permisos efectivos registrados.
                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- =====================================================
         ACCIONES
    ====================================================== -->

    <div class="sita-card">

        <div class="sita-card-body">

            <div
                style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    gap:15px;
                    flex-wrap:wrap;
                "
            >

                <div>

                    <strong
                        style="
                            display:block;
                            color:#1f2937;
                            font-size:15px;
                            margin-bottom:4px;
                        "
                    >
                        Administración del profesional
                    </strong>

                    <span
                        style="
                            color:#6b7280;
                            font-size:13px;
                        "
                    >
                        Desde aquí podrá consultar y posteriormente
                        administrar su información y acceso.
                    </span>

                </div>

                <div
                    style="
                        display:flex;
                        gap:10px;
                        flex-wrap:wrap;
                    "
                >

                    <a
                        href="<?= e(url('/profesionales')) ?>"
                        class="sita-btn sita-btn-secondary"
                    >
                        Volver a profesionales
                    </a>

                    <?php if (Auth::can('profesionales.editar')): ?>

                        <a
                            href="<?= e(url('/profesionales/editar?id=' . (int) ($profesional['id'] ?? 0))) ?>"
                            class="sita-btn sita-btn-primary"
                        >
                            Editar profesional
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>


</div>

<!-- ==========================================================
     MODAL ADMINISTRAR ROLES
=========================================================== -->

<?php if (
    !empty($usuario)
    && Auth::can('roles.editar')
): ?>


<div
    class="sita-modal"
    id="modalRoles"
    aria-hidden="true"
>

    <div
        class="sita-modal-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modalRolesTitulo"
    >

        <div class="sita-modal-header">

            <div class="sita-modal-header-content">

                <h2
                    class="sita-modal-title"
                    id="modalRolesTitulo"
                >
                    Administrar roles
                </h2>

                <p class="sita-modal-subtitle">
                    Seleccione los roles que desea asociar a la cuenta
                    de <?= e($nombreCompleto) ?>.
                </p>

            </div>

            <button
                type="button"
                class="sita-modal-close"
                id="btnCerrarModalRoles"
                aria-label="Cerrar"
            >
                ×
            </button>

        </div>


        <form
            method="post"
            action="<?= e(url('/profesionales/roles/guardar')) ?>"
        >

            <?= csrfField() ?>

            <input
                type="hidden"
                name="id_profesional"
                value="<?= (int) ($profesional['id'] ?? 0) ?>"
            >


            <div class="sita-modal-body">

                <div class="sita-modal-info">

                    Los permisos del usuario se obtienen automáticamente
                    a partir de los roles seleccionados. No es necesario
                    asignar permisos individualmente desde aquí.

                </div>


                <h3 class="sita-modal-section-title">
                    Roles disponibles
                </h3>


                <?php if (!empty($rolesDisponibles)): ?>

                    <div class="sita-role-options">

                        <?php foreach ($rolesDisponibles as $rolDisponible): ?>

                            <?php

                            $idRol = (int) (
                                $rolDisponible['id']
                                ?? 0
                            );

                            $nombreRol = (string) (
                                $rolDisponible['nombre']
                                ?? ''
                            );

                            $descripcionRol = trim(
                                (string) (
                                    $rolDisponible['descripcion']
                                    ?? ''
                                )
                            );

                            $rolSeleccionado = in_array(
                                $idRol,
                                $idsRolesAsignados,
                                true
                            );

                            ?>

                            <div class="sita-role-option">

                                <input
                                    type="checkbox"
                                    id="rol_<?= $idRol ?>"
                                    name="roles[]"
                                    value="<?= $idRol ?>"
                                    <?= $rolSeleccionado ? 'checked' : '' ?>
                                >

                                <label for="rol_<?= $idRol ?>">

                                    <span class="sita-role-check">
                                        ✓
                                    </span>

                                    <span class="sita-role-option-content">

                                        <span class="sita-role-option-name">
                                            <?= e($nombreRol) ?>
                                        </span>

                                        <?php if ($descripcionRol !== ''): ?>

                                            <span class="sita-role-option-description">
                                                <?= e($descripcionRol) ?>
                                            </span>

                                        <?php endif; ?>

                                    </span>

                                </label>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="sita-modal-empty">

                        No hay roles activos disponibles para asignar.

                    </div>

                <?php endif; ?>

            </div>


            <div class="sita-modal-footer">

                <button
                    type="button"
                    class="sita-btn sita-btn-secondary"
                    id="btnCancelarModalRoles"
                >
                    Cancelar
                </button>

                <?php if (!empty($rolesDisponibles)): ?>

                    <button
                        type="submit"
                        class="sita-btn sita-btn-primary"
                    >
                        Guardar roles
                    </button>

                <?php endif; ?>

            </div>

        </form>

    </div>

</div>


<?php endif; ?>

<script>
(function () {

    const modal = document.getElementById('modalRoles');
    const btnAbrir = document.getElementById('btnAbrirModalRoles');
    const btnCerrar = document.getElementById('btnCerrarModalRoles');
    const btnCancelar = document.getElementById('btnCancelarModalRoles');

    if (!modal || !btnAbrir) {
        return;
    }

    function abrirModal() {

        modal.classList.add('sita-modal-open');
        modal.setAttribute('aria-hidden', 'false');

        document.body.classList.add(
            'sita-modal-body-open'
        );

    }

    function cerrarModal() {

        modal.classList.remove('sita-modal-open');
        modal.setAttribute('aria-hidden', 'true');

        document.body.classList.remove(
            'sita-modal-body-open'
        );

    }

    btnAbrir.addEventListener(
        'click',
        abrirModal
    );

    if (btnCerrar) {

        btnCerrar.addEventListener(
            'click',
            cerrarModal
        );

    }

    if (btnCancelar) {

        btnCancelar.addEventListener(
            'click',
            cerrarModal
        );

    }

    modal.addEventListener(
        'click',
        function (event) {

            if (event.target === modal) {
                cerrarModal();
            }

        }
    );

    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape'
                && modal.classList.contains(
                    'sita-modal-open'
                )
            ) {
                cerrarModal();
            }

        }
    );

})();
</script>

<?php require ROOT_PATH . '/views/layout/footer.php'; ?>
