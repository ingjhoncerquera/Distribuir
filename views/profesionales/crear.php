<?php

declare(strict_types=1);

$tiposDocumento = $tiposDocumento ?? [
    'CC',
    'TI',
    'CE',
    'Pasaporte',
];

$tiposProfesional = $tiposProfesional ?? [
    'Instructor',
    'Psicologo',
    'Enfermero',
    'Nutricionista',
    'Trabajador Social',
    'Bienestar',
    'Coordinador',
    'Otro',
];

$valores = $valores ?? [];
$errores = $errores ?? [];

/*
|--------------------------------------------------------------------------
| Mensaje de error enviado por el controlador
|--------------------------------------------------------------------------
*/

$mensajeError = flash('error');

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

    .sita-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .sita-form-group {
        display: flex;
        flex-direction: column;
    }

    .sita-form-group-full {
        grid-column: 1 / -1;
    }

    .sita-form-label {
        display: block;
        margin-bottom: 7px;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }

    .sita-required {
        color: #dc2626;
    }

    .sita-form-control {
        width: 100%;
        min-height: 42px;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #ffffff;
        color: #1f2937;
        font-size: 14px;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
        box-sizing: border-box;
    }

    .sita-form-control:focus {
        border-color: #198754;
        box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.10);
    }

    textarea.sita-form-control {
        min-height: 90px;
        resize: vertical;
    }

    .sita-help {
        margin-top: 5px;
        font-size: 12px;
        color: #6b7280;
    }

    .sita-error {
        margin-bottom: 20px;
        padding: 13px 16px;
        border-radius: 8px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        font-size: 14px;
    }

    .sita-success {
        margin-bottom: 20px;
        padding: 13px 16px;
        border-radius: 8px;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
        font-size: 14px;
    }

    .sita-card-footer {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
        padding: 18px 24px;
        border-top: 1px solid #e5e7eb;
        background: #f9fafb;
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

    @media (max-width: 768px) {
        .sita-page {
            padding: 20px 0 40px;
        }

        .sita-form-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .sita-form-group-full {
            grid-column: auto;
        }

        .sita-card-body {
            padding: 18px;
        }

        .sita-card-footer {
            flex-direction: column-reverse;
            align-items: stretch;
        }

        .sita-btn {
            width: 100%;
        }
    }
</style>

<div class="container-fluid">
    <div class="sita-page">

        <div class="sita-page-header">
            <div class="sita-page-header-content">
                <h1>Nuevo profesional</h1>
                <p>
                    Registre la información personal y profesional.
                </p>
            </div>
        </div>

        <?php if ($mensajeError !== null): ?>

            <div class="sita-error">
                <?= e($mensajeError) ?>
            </div>

        <?php endif; ?>

        <?php if (!empty($errores)): ?>

            <div class="sita-error">

                <?php if (is_array($errores)): ?>

                    <ul style="margin: 0; padding-left: 20px;">

                        <?php foreach ($errores as $error): ?>

                            <li><?= e($error) ?></li>

                        <?php endforeach; ?>

                    </ul>

                <?php else: ?>

                    <?= e($errores) ?>

                <?php endif; ?>

            </div>

        <?php endif; ?>

        <form
            method="POST"
            action="<?= e(url('/profesionales/crear')) ?>"
        >

            <?= csrfField() ?>

            <!-- =====================================================
                 DATOS PERSONALES
            ====================================================== -->

            <div class="sita-card">

                <div class="sita-card-header">

                    <h2>Datos personales</h2>

                    <p>
                        Información básica de identificación del profesional.
                    </p>

                </div>

                <div class="sita-card-body">

                    <div class="sita-form-grid">

                        <div class="sita-form-group">

                            <label
                                for="tipo_documento"
                                class="sita-form-label"
                            >
                                Tipo de documento
                                <span class="sita-required">*</span>
                            </label>

                            <select
                                id="tipo_documento"
                                name="tipo_documento"
                                class="sita-form-control"
                                required
                            >
                                <option value="">
                                    Seleccione...
                                </option>

                                <?php foreach ($tiposDocumento as $tipo): ?>

                                    <option
                                        value="<?= e($tipo) ?>"
                                        <?= (($valores['tipo_documento'] ?? '') === $tipo)
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= e($tipo) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="sita-form-group">

                            <label
                                for="documento"
                                class="sita-form-label"
                            >
                                Número de documento
                                <span class="sita-required">*</span>
                            </label>

                            <input
                                type="text"
                                id="documento"
                                name="documento"
                                class="sita-form-control"
                                value="<?= e($valores['documento'] ?? '') ?>"
                                maxlength="30"
                                required
                            >

                        </div>

                        <div class="sita-form-group">

                            <label
                                for="nombres"
                                class="sita-form-label"
                            >
                                Nombres
                                <span class="sita-required">*</span>
                            </label>

                            <input
                                type="text"
                                id="nombres"
                                name="nombres"
                                class="sita-form-control"
                                value="<?= e($valores['nombres'] ?? '') ?>"
                                maxlength="100"
                                required
                            >

                        </div>

                        <div class="sita-form-group">

                            <label
                                for="apellidos"
                                class="sita-form-label"
                            >
                                Apellidos
                                <span class="sita-required">*</span>
                            </label>

                            <input
                                type="text"
                                id="apellidos"
                                name="apellidos"
                                class="sita-form-control"
                                value="<?= e($valores['apellidos'] ?? '') ?>"
                                maxlength="100"
                                required
                            >

                        </div>

                        <div class="sita-form-group">

                            <label
                                for="fecha_nacimiento"
                                class="sita-form-label"
                            >
                                Fecha de nacimiento
                            </label>

                            <input
                                type="date"
                                id="fecha_nacimiento"
                                name="fecha_nacimiento"
                                class="sita-form-control"
                                value="<?= e($valores['fecha_nacimiento'] ?? '') ?>"
                            >

                        </div>

                        <div class="sita-form-group">

                            <label
                                for="sexo"
                                class="sita-form-label"
                            >
                                Sexo
                            </label>

                            <select
                                id="sexo"
                                name="sexo"
                                class="sita-form-control"
                            >

                                <option value="">
                                    Seleccione...
                                </option>

                                <option
                                    value="Femenino"
                                    <?= (($valores['sexo'] ?? '') === 'Femenino')
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Femenino
                                </option>

                                <option
                                    value="Masculino"
                                    <?= (($valores['sexo'] ?? '') === 'Masculino')
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Masculino
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =====================================================
                 INFORMACIÓN DE CONTACTO
            ====================================================== -->

            <div class="sita-card">

                <div class="sita-card-header">

                    <h2>Información de contacto</h2>

                    <p>
                        Datos utilizados para comunicación y ubicación.
                    </p>

                </div>

                <div class="sita-card-body">

                    <div class="sita-form-grid">

                        <div class="sita-form-group">

                            <label
                                for="telefono"
                                class="sita-form-label"
                            >
                                Teléfono
                            </label>

                            <input
                                type="text"
                                id="telefono"
                                name="telefono"
                                class="sita-form-control"
                                value="<?= e($valores['telefono'] ?? '') ?>"
                                maxlength="30"
                            >

                        </div>

                        <div class="sita-form-group">

                            <label
                                for="correo"
                                class="sita-form-label"
                            >
                                Correo electrónico
                            </label>

                            <input
                                type="email"
                                id="correo"
                                name="correo"
                                class="sita-form-control"
                                value="<?= e($valores['correo'] ?? '') ?>"
                                maxlength="150"
                            >

                        </div>

                        <div class="sita-form-group sita-form-group-full">

                            <label
                                for="direccion"
                                class="sita-form-label"
                            >
                                Dirección
                            </label>

                            <input
                                type="text"
                                id="direccion"
                                name="direccion"
                                class="sita-form-control"
                                value="<?= e($valores['direccion'] ?? '') ?>"
                                maxlength="200"
                            >

                        </div>

                        <div class="sita-form-group">

                            <label
                                for="municipio"
                                class="sita-form-label"
                            >
                                Municipio
                            </label>

                            <input
                                type="text"
                                id="municipio"
                                name="municipio"
                                class="sita-form-control"
                                value="<?= e($valores['municipio'] ?? '') ?>"
                                maxlength="100"
                            >

                        </div>

                        <div class="sita-form-group">

                            <label
                                for="departamento"
                                class="sita-form-label"
                            >
                                Departamento
                            </label>

                            <input
                                type="text"
                                id="departamento"
                                name="departamento"
                                class="sita-form-control"
                                value="<?= e($valores['departamento'] ?? '') ?>"
                                maxlength="100"
                            >

                        </div>

                    </div>

                </div>

            </div>


            <!-- =====================================================
                 INFORMACIÓN PROFESIONAL
            ====================================================== -->

            <div class="sita-card">

                <div class="sita-card-header">

                    <h2>Información profesional</h2>

                    <p>
                        Información relacionada con el tipo y perfil del profesional.
                    </p>

                </div>

                <div class="sita-card-body">

                    <div class="sita-form-grid">

                        <div class="sita-form-group">

                            <label
                                for="tipo_profesional"
                                class="sita-form-label"
                            >
                                Tipo de profesional
                                <span class="sita-required">*</span>
                            </label>

                            <select
                                id="tipo_profesional"
                                name="tipo_profesional"
                                class="sita-form-control"
                                required
                            >

                                <option value="">
                                    Seleccione...
                                </option>

                                <?php foreach ($tiposProfesional as $tipo): ?>

                                    <option
                                        value="<?= e($tipo) ?>"
                                        <?= (($valores['tipo_profesional'] ?? '') === $tipo)
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= e($tipo) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="sita-form-group">

                            <label
                                for="estado"
                                class="sita-form-label"
                            >
                                Estado
                                <span class="sita-required">*</span>
                            </label>

                            <select
                                id="estado"
                                name="estado"
                                class="sita-form-control"
                                required
                            >

                                <option
                                    value="activo"
                                    <?= (($valores['estado'] ?? 'activo') === 'activo')
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Activo
                                </option>

                                <option
                                    value="inactivo"
                                    <?= (($valores['estado'] ?? '') === 'inactivo')
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Inactivo
                                </option>

                            </select>

                        </div>

                        <div class="sita-form-group">

                            <label
                                for="registro_profesional"
                                class="sita-form-label"
                            >
                                Registro profesional
                            </label>

                            <input
                                type="text"
                                id="registro_profesional"
                                name="registro_profesional"
                                class="sita-form-control"
                                value="<?= e($valores['registro_profesional'] ?? '') ?>"
                                maxlength="100"
                            >

                        </div>

                        <div class="sita-form-group">

                            <label
                                for="especialidad"
                                class="sita-form-label"
                            >
                                Especialidad
                            </label>

                            <input
                                type="text"
                                id="especialidad"
                                name="especialidad"
                                class="sita-form-control"
                                value="<?= e($valores['especialidad'] ?? '') ?>"
                                maxlength="150"
                            >

                        </div>

                    </div>

                </div>

                <div class="sita-card-footer">

                    <a
                        href="<?= e(url('/profesionales')) ?>"
                        class="sita-btn sita-btn-secondary"
                    >
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="sita-btn sita-btn-primary"
                    >
                        Guardar profesional
                    </button>

                </div>

            </div>

        </form>

    </div>
</div>

<?php require ROOT_PATH . '/views/layout/footer.php'; ?>