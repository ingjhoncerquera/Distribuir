<?php

declare(strict_types=1);

$profesional = $profesional ?? null;
$usuario = $usuario ?? null;
$roles = $roles ?? [];
$rolesAsignados = $rolesAsignados ?? [];

$rolesAsignadosIds = array_map(
    'intval',
    $rolesAsignados
);

$mensajeExito = flash('success');
$mensajeError = flash('error');

if (!$profesional) {
    echo '<div class="sita-page">';
    echo '<div class="sita-alert sita-alert-error">No se encontró el profesional solicitado.</div>';
    echo '</div>';
    return;
}
?>

<style>
    .sita-page {
        width: 100%;
    }

    .sita-page-header {
        margin-bottom: 24px;
    }

    .sita-page-header h1 {
        margin: 0 0 6px 0;
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
    }

    .sita-page-header p {
        margin: 0;
        color: #6b7280;
        font-size: 14px;
    }

    .sita-flash {
        padding: 14px 18px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-size: 14px;
        font-weight: 500;
    }

    .sita-flash-success {
        background: #ecfdf5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .sita-flash-error {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .sita-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
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
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
    }

    .sita-card-header p {
        margin: 5px 0 0 0;
        font-size: 13px;
        color: #6b7280;
    }

    .sita-card-body {
        padding: 22px;
    }

    .sita-info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px 28px;
    }

    .sita-info-item {
        min-width: 0;
    }

    .sita-info-label {
        display: block;
        margin-bottom: 5px;
        font-size: 12px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .sita-info-value {
        font-size: 15px;
        color: #1f2937;
        font-weight: 500;
        word-break: break-word;
    }

    .sita-roles-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .sita-role-item {
        position: relative;
    }

    .sita-role-label {
        display: flex;
        align-items: flex-start;
        gap: 13px;
        padding: 16px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        cursor: pointer;
        background: #ffffff;
        transition: all 0.15s ease;
    }

    .sita-role-label:hover {
        border-color: #9ca3af;
        background: #f9fafb;
    }

    .sita-role-checkbox {
        width: 18px;
        height: 18px;
        margin-top: 2px;
        flex-shrink: 0;
        cursor: pointer;
        accent-color: #16a34a;
    }

    .sita-role-content {
        flex: 1;
        min-width: 0;
    }

    .sita-role-name {
        display: block;
        margin-bottom: 4px;
        font-size: 15px;
        font-weight: 700;
        color: #1f2937;
    }

    .sita-role-description {
        display: block;
        font-size: 13px;
        line-height: 1.5;
        color: #6b7280;
    }

    .sita-role-status {
        display: inline-flex;
        align-items: center;
        padding: 3px 8px;
        margin-top: 8px;
        border-radius: 999px;
        background: #ecfdf5;
        color: #047857;
        font-size: 11px;
        font-weight: 700;
    }

    .sita-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 24px;
    }

    .sita-actions-left,
    .sita-actions-right {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .sita-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 40px;
        padding: 9px 16px;
        border-radius: 9px;
        border: 1px solid transparent;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .sita-btn-primary {
        background: #16a34a;
        color: #ffffff;
        border-color: #16a34a;
    }

    .sita-btn-primary:hover {
        background: #15803d;
        border-color: #15803d;
    }

    .sita-btn-secondary {
        background: #ffffff;
        color: #374151;
        border-color: #d1d5db;
    }

    .sita-btn-secondary:hover {
        background: #f9fafb;
        border-color: #9ca3af;
    }

    .sita-no-roles {
        padding: 22px;
        text-align: center;
        border: 1px dashed #d1d5db;
        border-radius: 12px;
        color: #6b7280;
        background: #f9fafb;
        font-size: 14px;
    }

    .sita-help {
        margin-top: 14px;
        padding: 12px 14px;
        border-radius: 9px;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
        font-size: 13px;
        line-height: 1.5;
    }

    @media (max-width: 800px) {
        .sita-info-grid,
        .sita-roles-list {
            grid-template-columns: 1fr;
        }

        .sita-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .sita-actions-left,
        .sita-actions-right {
            width: 100%;
        }

        .sita-actions-right .sita-btn {
            width: 100%;
        }
    }
</style>

<div class="sita-page">

    <div class="sita-page-header">
        <h1>Roles del profesional</h1>
        <p>Administre los roles que determinan el acceso del profesional al sistema.</p>
    </div>

    <?php if ($mensajeExito): ?>
        <div class="sita-flash sita-flash-success">
            <?= e($mensajeExito) ?>
        </div>
    <?php endif; ?>

    <?php if ($mensajeError): ?>
        <div class="sita-flash sita-flash-error">
            <?= e($mensajeError) ?>
        </div>
    <?php endif; ?>

    <div class="sita-card">

        <div class="sita-card-header">
            <h2>Información del profesional</h2>
            <p>Datos de la persona y de su acceso al sistema.</p>
        </div>

        <div class="sita-card-body">

            <div class="sita-info-grid">

                <div class="sita-info-item">
                    <span class="sita-info-label">Nombre completo</span>
                    <div class="sita-info-value">
                        <?= e(
                            trim(
                                ($profesional['nombres'] ?? '') . ' ' .
                                ($profesional['apellidos'] ?? '')
                            )
                        ) ?>
                    </div>
                </div>

                <div class="sita-info-item">
                    <span class="sita-info-label">Documento</span>
                    <div class="sita-info-value">
                        <?= e($profesional['documento'] ?? '') ?>
                    </div>
                </div>

                <div class="sita-info-item">
                    <span class="sita-info-label">Tipo de profesional</span>
                    <div class="sita-info-value">
                        <?= e($profesional['tipo_profesional'] ?? '') ?>
                    </div>
                </div>

                <div class="sita-info-item">
                    <span class="sita-info-label">Usuario</span>
                    <div class="sita-info-value">
                        <?php if ($usuario): ?>
                            <?= e($usuario['username'] ?? '') ?>
                        <?php else: ?>
                            Sin usuario creado
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <div class="sita-card">

        <div class="sita-card-header">
            <h2>Asignación de roles</h2>
            <p>
                Seleccione uno o varios roles para definir los permisos del usuario.
            </p>
        </div>

        <div class="sita-card-body">

            <?php if (!$usuario): ?>

                <div class="sita-no-roles">
                    Este profesional todavía no tiene un usuario de acceso creado.
                    Primero debe crearle el acceso al sistema.
                </div>

            <?php elseif (!$roles): ?>

                <div class="sita-no-roles">
                    No existen roles activos disponibles para asignar.
                </div>

            <?php else: ?>

                <form
                    method="POST"
                    action="<?= e(url('/profesionales/roles/guardar')) ?>"
                >

                    <?= csrfField() ?>

                    <input
                        type="hidden"
                        name="id_profesional"
                        value="<?= (int) $profesional['id'] ?>"
                    >

                    <div class="sita-roles-list">

                        <?php foreach ($roles as $rol): ?>

                            <?php
                            $idRol = (int) ($rol['id'] ?? 0);
                            $asignado = in_array(
                                $idRol,
                                $rolesAsignadosIds,
                                true
                            );
                            ?>

                            <div class="sita-role-item">

                                <label
                                    class="sita-role-label"
                                    for="rol_<?= $idRol ?>"
                                >

                                    <input
                                        type="checkbox"
                                        class="sita-role-checkbox"
                                        id="rol_<?= $idRol ?>"
                                        name="roles[]"
                                        value="<?= $idRol ?>"
                                        <?= $asignado ? 'checked' : '' ?>
                                    >

                                    <span class="sita-role-content">

                                        <span class="sita-role-name">
                                            <?= e($rol['nombre'] ?? '') ?>
                                        </span>

                                        <?php if (!empty($rol['descripcion'])): ?>
                                            <span class="sita-role-description">
                                                <?= e($rol['descripcion']) ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($asignado): ?>
                                            <span class="sita-role-status">
                                                Rol asignado
                                            </span>
                                        <?php endif; ?>

                                    </span>

                                </label>

                            </div>

                        <?php endforeach; ?>

                    </div>

                    <div class="sita-help">
                        Los permisos no se asignan directamente al profesional.
                        Los permisos provienen de los roles seleccionados y de la
                        configuración establecida en <strong>Roles y permisos</strong>.
                    </div>

                    <div class="sita-actions">

                        <div class="sita-actions-left">

                            <a
                                href="<?= e(url('/profesionales/detalle?id=' . (int) $profesional['id'])) ?>"
                                class="sita-btn sita-btn-secondary"
                            >
                                ← Volver al detalle
                            </a>

                        </div>

                        <div class="sita-actions-right">

                            <button
                                type="submit"
                                class="sita-btn sita-btn-primary"
                            >
                                💾 Guardar roles
                            </button>

                        </div>

                    </div>

                </form>

            <?php endif; ?>

        </div>

    </div>

</div>