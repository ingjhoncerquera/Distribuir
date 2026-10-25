<?php

declare(strict_types=1);

require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';

$nombre = (string) ($nombre ?? '');
$descripcion = (string) ($descripcion ?? '');
$estado = (string) ($estado ?? 'activo');

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

    .sita-panel-body {
        padding: 24px;
    }

    .sita-form-group {
        margin-bottom: 20px;
    }

    .sita-form-label {
        display: block;
        margin-bottom: 7px;
        font-size: 0.88rem;
        font-weight: 700;
        color: #343a40;
    }

    .sita-form-label .required {
        color: #dc3545;
    }

    .sita-form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ced4da;
        border-radius: 8px;
        background: #ffffff;
        color: #212529;
        font-size: 0.9rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .sita-form-control:focus {
        outline: none;
        border-color: #86b7fe;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.08);
    }

    textarea.sita-form-control {
        min-height: 120px;
        resize: vertical;
    }

    .sita-form-help {
        margin-top: 6px;
        color: #6c757d;
        font-size: 0.8rem;
    }

    .sita-form-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
        padding-top: 8px;
        border-top: 1px solid #edf0f2;
        margin-top: 10px;
    }

    .sita-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 9px 15px;
        border-radius: 8px;
        text-decoration: none;
        background: #f1f3f5;
        color: #343a40;
        border: 1px solid #dee2e6;
        font-size: 0.84rem;
        font-weight: 600;
        transition: all 0.2s ease;
        white-space: nowrap;
        cursor: pointer;
    }

    .sita-action:hover {
        background: #e9ecef;
        color: #212529;
        text-decoration: none;
    }

    .sita-action-primary {
        background: #0d6efd;
        color: #ffffff;
        border-color: #0d6efd;
    }

    .sita-action-primary:hover {
        background: #0b5ed7;
        border-color: #0a58ca;
        color: #ffffff;
    }

    .sita-info {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 24px;
    }

    .sita-info-title {
        font-weight: 700;
        color: #343a40;
        margin-bottom: 4px;
        font-size: 0.88rem;
    }

    .sita-info-text {
        color: #6c757d;
        font-size: 0.83rem;
        margin: 0;
        line-height: 1.5;
    }

    @media (max-width: 768px) {

        .sita-page {
            padding: 16px 0 30px;
        }

        .sita-panel-header {
            padding: 17px;
        }

        .sita-panel-body {
            padding: 17px;
        }

        .sita-form-actions {
            flex-direction: column-reverse;
            align-items: stretch;
        }

        .sita-action {
            width: 100%;
        }
    }
</style>

<div class="container sita-page">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">

        <div>

            <h1 class="h3 mb-1">
                Crear rol
            </h1>

            <p class="text-muted mb-0">
                Registre un nuevo rol y posteriormente asígnele los permisos correspondientes.
            </p>

        </div>

        <a
            href="<?= e(url('/roles')) ?>"
            class="sita-action"
        >
            ← Volver a roles
        </a>

    </div>


    <div class="sita-panel">

        <div class="sita-panel-header">

            <h2>
                Información del rol
            </h2>

            <p>
                Después de crear el rol podrá asignarle los permisos disponibles.
            </p>

        </div>


        <div class="sita-panel-body">

            <div class="sita-info">

                <div class="sita-info-title">
                    Configuración inicial
                </div>

                <p class="sita-info-text">
                    El nombre identifica el rol dentro del sistema.
                    La descripción permite documentar su finalidad.
                    Los permisos se asignarán después de crear el rol.
                </p>

            </div>


            <form
                method="POST"
                action="<?= e(url('/roles/guardar')) ?>"
                autocomplete="off"
            >

                <?= csrfField() ?>


                <div class="sita-form-group">

                    <label
                        for="nombre"
                        class="sita-form-label"
                    >
                        Nombre del rol
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        class="sita-form-control"
                        value="<?= e($nombre) ?>"
                        maxlength="50"
                        required
                        autofocus
                    >

                    <div class="sita-form-help">
                        Máximo 50 caracteres.
                    </div>

                </div>


                <div class="sita-form-group">

                    <label
                        for="descripcion"
                        class="sita-form-label"
                    >
                        Descripción
                    </label>

                    <textarea
                        id="descripcion"
                        name="descripcion"
                        class="sita-form-control"
                        maxlength="255"
                    ><?= e($descripcion) ?></textarea>

                    <div class="sita-form-help">
                        Describa brevemente la finalidad y alcance del rol.
                    </div>

                </div>


                <div class="sita-form-group">

                    <label
                        for="estado"
                        class="sita-form-label"
                    >
                        Estado
                        <span class="required">*</span>
                    </label>

                    <select
                        id="estado"
                        name="estado"
                        class="sita-form-control"
                        required
                    >

                        <option
                            value="activo"
                            <?= $estado === 'activo' ? 'selected' : '' ?>
                        >
                            Activo
                        </option>

                        <option
                            value="inactivo"
                            <?= $estado === 'inactivo' ? 'selected' : '' ?>
                        >
                            Inactivo
                        </option>

                    </select>

                    <div class="sita-form-help">
                        Los roles activos pueden ser asignados a los usuarios del sistema.
                    </div>

                </div>


                <div class="sita-form-actions">

                    <a
                        href="<?= e(url('/roles')) ?>"
                        class="sita-action"
                    >
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="sita-action sita-action-primary"
                    >
                        Crear rol
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php
require_once ROOT_PATH . '/views/layout/footer.php';
?>

