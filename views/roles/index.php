<?php

$roles = $roles ?? [];

require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';
?>

<style>
    .sita-page {
        padding: 24px 0 40px;
    }

    .sita-panel {
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.06);
        border: 1px solid #e9ecef;
        overflow: hidden;
    }

    .sita-panel-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e9ecef;
        background: #fafafa;
    }

    .sita-panel-header h2 {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 700;
        color: #212529;
    }

    .sita-panel-header p {
        margin: 5px 0 0;
        color: #6c757d;
        font-size: 0.9rem;
    }

    .sita-table-wrapper {
        overflow-x: auto;
    }

    .sita-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .sita-table th {
        background: #f8f9fa;
        color: #495057;
        font-size: 0.82rem;
        font-weight: 700;
        padding: 13px 16px;
        border-bottom: 1px solid #dee2e6;
        white-space: nowrap;
    }

    .sita-table td {
        padding: 14px 16px;
        border-bottom: 1px solid #edf0f2;
        color: #343a40;
        vertical-align: middle;
        font-size: 0.9rem;
    }

    .sita-table tbody tr:hover {
        background: #fafafa;
    }

    .sita-role-name {
        font-weight: 700;
        color: #212529;
        margin-bottom: 3px;
    }

    .sita-role-description {
        color: #6c757d;
        font-size: 0.82rem;
    }

    .sita-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        height: 30px;
        padding: 0 10px;
        border-radius: 20px;
        background: #eef2f7;
        color: #495057;
        font-weight: 700;
        font-size: 0.82rem;
    }

    .sita-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .sita-badge-success {
        background: #e9f7ef;
        color: #198754;
    }

    .sita-badge-secondary {
        background: #f1f3f5;
        color: #6c757d;
    }

    .sita-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 8px 13px;
        border-radius: 8px;
        text-decoration: none;
        background: #f1f3f5;
        color: #343a40;
        border: 1px solid #dee2e6;
        font-size: 0.82rem;
        font-weight: 600;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .sita-action:hover {
        background: #e9ecef;
        color: #212529;
        text-decoration: none;
    }

    .sita-empty {
        padding: 45px 20px;
        text-align: center;
        color: #6c757d;
    }

    .sita-empty-icon {
        font-size: 2rem;
        margin-bottom: 10px;
        opacity: 0.6;
    }

    @media (max-width: 768px) {
        .sita-page {
            padding: 16px 0 30px;
        }

        .sita-panel-header {
            padding: 17px;
        }

        .sita-table th,
        .sita-table td {
            padding: 11px 12px;
        }
    }
</style>

<div class="container sita-page">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">

        <div>
            <h1 class="h3 mb-1">Roles</h1>

            <p class="text-muted mb-0">
                Administración de roles y permisos de acceso al sistema.
            </p>
        </div>

        <?php if (Auth::can('roles.editar')): ?>

            <a
                href="<?= e(url('/roles/crear')) ?>"
                class="sita-action"
            >
                + Nuevo rol
            </a>

        <?php endif; ?>

    </div>

    <div class="sita-panel">

        <div class="sita-panel-header">

            <h2>Roles del sistema</h2>

            <p>
                Consulte los roles registrados y gestione los permisos
                asociados a cada uno.
            </p>

        </div>

        <?php if (!empty($roles)): ?>

            <div class="sita-table-wrapper">

                <table class="sita-table">

                    <thead>

                        <tr>
                            <th>Rol</th>
                            <th>Permisos</th>
                            <th>Estado</th>
                            <th class="text-end">Acción</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($roles as $rol): ?>

                            <tr>

                                <td>

                                    <div class="sita-role-name">
                                        <?= e($rol['nombre']) ?>
                                    </div>

                                    <?php if (!empty($rol['descripcion'])): ?>

                                        <div class="sita-role-description">
                                            <?= e($rol['descripcion']) ?>
                                        </div>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <span class="sita-count">
                                        <?= (int) $rol['total_permisos'] ?>
                                    </span>

                                </td>

                                <td>

                                    <?php if (($rol['estado'] ?? '') === 'activo'): ?>

                                        <span class="sita-badge sita-badge-success">
                                            Activo
                                        </span>

                                    <?php else: ?>

                                        <span class="sita-badge sita-badge-secondary">
                                            Inactivo
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td class="text-end">

                                    <a
                                        href="<?= e(url('/roles/permisos?id=' . (int) $rol['id'])) ?>"
                                        class="sita-action"
                                        title="Gestionar permisos"
                                    >
                                        ⚙ Gestionar permisos
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="sita-empty">

                <div class="sita-empty-icon">
                    ⚙
                </div>

                <div>
                    No hay roles activos registrados en el sistema.
                </div>

            </div>

        <?php endif; ?>

    </div>

</div>

<?php
require_once ROOT_PATH . '/views/layout/footer.php';
?>