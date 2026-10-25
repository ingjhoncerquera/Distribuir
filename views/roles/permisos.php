<?php

$rol = $rol ?? null;
$permisos = $permisos ?? [];
$idsPermisosAsignados = $idsPermisosAsignados ?? [];

$mensajeExito = flash('success');
$mensajeError = flash('error');

if (!$rol) {
    echo '<div style="padding:40px;font-family:Arial,sans-serif;">';
    echo '<h2>Rol no encontrado</h2>';
    echo '<p>El rol solicitado no existe o no está disponible.</p>';
    echo '</div>';
    return;
}


/*
|--------------------------------------------------------------------------
| ORGANIZAR PERMISOS POR MÓDULO
|--------------------------------------------------------------------------
*/

$permisosPorModulo = [];

foreach ($permisos as $permiso) {

    $modulo = trim(
        (string) ($permiso['modulo'] ?? '')
    );

    if ($modulo === '') {
        $modulo = 'General';
    }

    $permisosPorModulo[$modulo][] = $permiso;
}


/*
|--------------------------------------------------------------------------
| NORMALIZAR IDS ASIGNADOS
|--------------------------------------------------------------------------
*/

$idsPermisosAsignados = array_map(
    'intval',
    $idsPermisosAsignados
);

?>

<?php require ROOT_PATH . '/views/layout/header.php'; ?>

<style>

/* =========================================================
   ROLES - PERMISOS
   ========================================================= */

.sita-permisos-page {

    width: 100%;

    max-width: 1500px;

    margin: 0 auto;

    padding: 30px 28px 45px;

    font-family:
        Inter,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        Roboto,
        Arial,
        sans-serif;

    color: #263746;
}


/* =========================================================
   ENCABEZADO
   ========================================================= */

.sita-permisos-header {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 25px;

    margin-bottom: 25px;
}


.sita-permisos-header-left {

    min-width: 0;
}


.sita-permisos-title {

    margin: 0;

    color: #0B263D;

    font-size: 25px;

    font-weight: 800;

    letter-spacing: -.4px;
}


.sita-permisos-subtitle {

    margin: 8px 0 0;

    color: #71808B;

    font-size: 13px;

    line-height: 1.5;
}


/* =========================================================
   MENSAJES FLASH
   ========================================================= */

.sita-flash {

    display: flex;

    align-items: flex-start;

    gap: 11px;

    margin-bottom: 20px;

    padding: 13px 16px;

    border-radius: 10px;

    font-size: 13px;

    font-weight: 600;

    line-height: 1.45;

    box-sizing: border-box;
}


.sita-flash-icon {

    flex: 0 0 auto;

    font-size: 16px;

    line-height: 1.3;
}


.sita-flash-success {

    color: #176B4D;

    background: #ECF8F2;

    border: 1px solid #BFE6D2;
}


.sita-flash-error {

    color: #9B3030;

    background: #FFF1F1;

    border: 1px solid #F0C7C7;
}


/* =========================================================
   INFORMACIÓN DEL ROL
   ========================================================= */

.sita-rol-card {

    display: flex;

    align-items: center;

    gap: 15px;

    padding: 18px 20px;

    margin-bottom: 25px;

    background: #FFFFFF;

    border: 1px solid #E2E9ED;

    border-radius: 14px;

    box-shadow:
        0 5px 18px rgba(11, 38, 61, .05);
}


.sita-rol-icon {

    width: 48px;
    height: 48px;

    min-width: 48px;

    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            #19A7A8,
            #13898A
        );

    color: #FFFFFF;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 21px;

    box-shadow:
        0 5px 13px rgba(25, 167, 168, .18);
}


.sita-rol-info {

    min-width: 0;
}


.sita-rol-name {

    margin: 0;

    color: #0B263D;

    font-size: 18px;

    font-weight: 800;
}


.sita-rol-description {

    margin-top: 4px;

    color: #71808B;

    font-size: 12px;

    line-height: 1.4;
}


/* =========================================================
   RESUMEN
   ========================================================= */

.sita-permisos-summary {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 20px;

    padding: 15px 18px;

    background: #F7FAFB;

    border: 1px solid #E2E9ED;

    border-radius: 12px;
}


.sita-permisos-summary-text {

    color: #52626E;

    font-size: 13px;

    font-weight: 600;
}


.sita-permisos-counter {

    color: #13898A;

    font-size: 14px;

    font-weight: 800;

    white-space: nowrap;
}


/* =========================================================
   CONTROLES
   ========================================================= */

.sita-permisos-controls {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 20px;
}


.sita-permisos-control-left {

    display: flex;

    align-items: center;

    gap: 8px;

    flex-wrap: wrap;
}


.sita-permisos-control-right {

    display: flex;

    align-items: center;

    gap: 10px;
}


.sita-btn {

    min-height: 38px;

    padding: 8px 14px;

    border-radius: 9px;

    border: 1px solid transparent;

    font-family: inherit;

    font-size: 12px;

    font-weight: 700;

    cursor: pointer;

    transition:
        background-color .15s ease,
        color .15s ease,
        border-color .15s ease,
        transform .15s ease;
}


.sita-btn:hover {

    transform: translateY(-1px);
}


.sita-btn-secondary {

    background: #FFFFFF;

    color: #52626E;

    border-color: #DCE5E9;
}


.sita-btn-secondary:hover {

    background: #F2F6F8;

    color: #123B5D;
}


.sita-btn-primary {

    background: #13898A;

    color: #FFFFFF;

    border-color: #13898A;

    box-shadow:
        0 5px 12px rgba(19, 137, 138, .16);
}


.sita-btn-primary:hover {

    background: #107778;

    border-color: #107778;

    color: #FFFFFF;
}


.sita-btn-back {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    min-height: 38px;

    padding: 8px 14px;

    border-radius: 9px;

    background: #FFFFFF;

    color: #52626E;

    border: 1px solid #DCE5E9;

    font-size: 12px;

    font-weight: 700;

    text-decoration: none !important;

    transition:
        background-color .15s ease,
        color .15s ease,
        border-color .15s ease;
}


.sita-btn-back:hover {

    background: #F2F6F8;

    color: #123B5D;

    border-color: #CBD8DE;

    text-decoration: none !important;
}


/* =========================================================
   MÓDULOS
   ========================================================= */

.sita-permisos-modulos {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 18px;
}


.sita-permiso-modulo {

    background: #FFFFFF;

    border: 1px solid #E2E9ED;

    border-radius: 14px;

    overflow: hidden;

    box-shadow:
        0 5px 18px rgba(11, 38, 61, .04);
}


/* =========================================================
   CABECERA MÓDULO
   ========================================================= */

.sita-permiso-modulo-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

    padding: 15px 17px;

    background: #F7FAFB;

    border-bottom: 1px solid #E2E9ED;
}


.sita-permiso-modulo-title {

    display: flex;

    align-items: center;

    gap: 9px;

    color: #0B263D;

    font-size: 14px;

    font-weight: 800;
}


.sita-permiso-modulo-icon {

    width: 30px;
    height: 30px;

    min-width: 30px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 8px;

    background: #EAF7F7;

    color: #13898A;

    font-size: 14px;
}


.sita-permiso-modulo-count {

    color: #71808B;

    font-size: 11px;

    font-weight: 700;

    white-space: nowrap;
}


/* =========================================================
   PERMISOS
   ========================================================= */

.sita-permisos-lista {

    padding: 7px 0;
}


.sita-permiso-item {

    display: flex;

    align-items: flex-start;

    gap: 11px;

    padding: 11px 17px;

    cursor: pointer;

    transition:
        background-color .15s ease;
}


.sita-permiso-item:hover {

    background: #F8FAFB;
}


.sita-permiso-checkbox {

    width: 17px;
    height: 17px;

    min-width: 17px;

    margin-top: 1px;

    accent-color: #13898A;

    cursor: pointer;
}


.sita-permiso-info {

    min-width: 0;

    flex: 1;
}


.sita-permiso-nombre {

    color: #263746;

    font-size: 12px;

    font-weight: 700;

    line-height: 1.35;
}


.sita-permiso-descripcion {

    margin-top: 3px;

    color: #71808B;

    font-size: 11px;

    line-height: 1.4;
}


/* =========================================================
   ESTADO VACÍO
   ========================================================= */

.sita-permisos-empty {

    padding: 45px 25px;

    text-align: center;

    background: #FFFFFF;

    border: 1px solid #E2E9ED;

    border-radius: 14px;

    color: #71808B;

    font-size: 13px;
}


/* =========================================================
   BOTONES INFERIORES
   ========================================================= */

.sita-permisos-footer-actions {

    display: flex;

    align-items: center;

    justify-content: flex-end;

    gap: 10px;

    margin-top: 25px;

    padding-top: 20px;

    border-top: 1px solid #E2E9ED;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 1000px) {

    .sita-permisos-modulos {

        grid-template-columns:
            1fr;
    }

}


@media (max-width: 700px) {

    .sita-permisos-page {

        padding:
            22px 15px 35px;
    }

    .sita-permisos-header {

        flex-direction: column;
    }

    .sita-permisos-controls {

        flex-direction: column;

        align-items: stretch;
    }

    .sita-permisos-control-left,
    .sita-permisos-control-right {

        width: 100%;
    }

    .sita-permisos-control-right {

        justify-content: flex-end;
    }

    .sita-permisos-summary {

        align-items: flex-start;

        flex-direction: column;
    }

}

</style>


<main class="sita-permisos-page">


    <!-- =====================================================
         ENCABEZADO
         ===================================================== -->

    <div class="sita-permisos-header">

        <div class="sita-permisos-header-left">

            <h1 class="sita-permisos-title">
                Gestionar permisos
            </h1>

            <p class="sita-permisos-subtitle">
                Administre los permisos asignados al rol seleccionado.
            </p>

        </div>

    </div>


    <!-- =====================================================
         MENSAJES
         ===================================================== -->

    <?php if (!empty($mensajeExito)): ?>

        <div class="sita-flash sita-flash-success">

            <span class="sita-flash-icon">
                ✓
            </span>

            <span>
                <?= e($mensajeExito) ?>
            </span>

        </div>

    <?php endif; ?>


    <?php if (!empty($mensajeError)): ?>

        <div class="sita-flash sita-flash-error">

            <span class="sita-flash-icon">
                !
            </span>

            <span>
                <?= e($mensajeError) ?>
            </span>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         INFORMACIÓN DEL ROL
         ===================================================== -->

    <div class="sita-rol-card">

        <div class="sita-rol-icon">
            🔐
        </div>

        <div class="sita-rol-info">

            <h2 class="sita-rol-name">
                <?= e($rol['nombre'] ?? 'Rol') ?>
            </h2>

            <div class="sita-rol-description">
                <?= e($rol['descripcion'] ?? 'Sin descripción') ?>
            </div>

        </div>

    </div>


    <!-- =====================================================
         FORMULARIO
         ===================================================== -->

    <form
        method="POST"
        action="<?= e(url('/roles/permisos/guardar')) ?>"
        id="formPermisos"
    >

        <?= csrfField() ?>

        <input
            type="hidden"
            name="id_rol"
            value="<?= e((string) ($rol['id'] ?? 0)) ?>"
        >


        <!-- =================================================
             RESUMEN
             ================================================= -->

        <div class="sita-permisos-summary">

            <span class="sita-permisos-summary-text">
                Permisos seleccionados
            </span>

            <span
                class="sita-permisos-counter"
                id="contadorPermisos"
            >
                0 permisos
            </span>

        </div>


        <!-- =================================================
             CONTROLES
             ================================================= -->

        <div class="sita-permisos-controls">

            <div class="sita-permisos-control-left">

                <button
                    type="button"
                    class="sita-btn sita-btn-secondary"
                    id="btnSeleccionarTodos"
                >
                    ✓ Seleccionar todos
                </button>

                <button
                    type="button"
                    class="sita-btn sita-btn-secondary"
                    id="btnDesmarcarTodos"
                >
                    ✕ Desmarcar todos
                </button>

            </div>


            <div class="sita-permisos-control-right">

                <a
                    href="<?= e(url('/roles')) ?>"
                    class="sita-btn-back"
                >
                    ← Volver a roles
                </a>

            </div>

        </div>


        <!-- =================================================
             PERMISOS AGRUPADOS
             ================================================= -->

        <?php if (!empty($permisosPorModulo)): ?>

            <div class="sita-permisos-modulos">

                <?php foreach ($permisosPorModulo as $modulo => $permisosModulo): ?>

                    <section class="sita-permiso-modulo">


                        <!-- CABECERA -->

                        <div class="sita-permiso-modulo-header">

                            <div class="sita-permiso-modulo-title">

                                <span class="sita-permiso-modulo-icon">
                                    ⚙
                                </span>

                                <span>
                                    <?= e(ucwords(str_replace('_', ' ', $modulo))) ?>
                                </span>

                            </div>

                            <span class="sita-permiso-modulo-count">
                                <?= count($permisosModulo) ?>
                                permiso<?= count($permisosModulo) === 1 ? '' : 's' ?>
                            </span>

                        </div>


                        <!-- LISTA -->

                        <div class="sita-permisos-lista">

                            <?php foreach ($permisosModulo as $permiso): ?>

                                <?php

                                $idPermiso =
                                    (int) ($permiso['id'] ?? 0);

                                $seleccionado =
                                    in_array(
                                        $idPermiso,
                                        $idsPermisosAsignados,
                                        true
                                    );

                                ?>

                                <label class="sita-permiso-item">

                                    <input
                                        type="checkbox"
                                        class="sita-permiso-checkbox"
                                        name="permisos[]"
                                        value="<?= e((string) $idPermiso) ?>"
                                        <?= $seleccionado ? 'checked' : '' ?>
                                    >

                                    <span class="sita-permiso-info">

                                        <span class="sita-permiso-nombre">
                                            <?= e($permiso['nombre'] ?? '') ?>
                                        </span>

                                        <?php if (!empty($permiso['descripcion'])): ?>

                                            <span class="sita-permiso-descripcion">
                                                <?= e($permiso['descripcion']) ?>
                                            </span>

                                        <?php endif; ?>

                                    </span>

                                </label>

                            <?php endforeach; ?>

                        </div>

                    </section>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="sita-permisos-empty">
                No hay permisos disponibles para administrar.
            </div>

        <?php endif; ?>


        <!-- =================================================
             ACCIONES
             ================================================= -->

        <div class="sita-permisos-footer-actions">

            <a
                href="<?= e(url('/roles')) ?>"
                class="sita-btn-back"
            >
                ← Cancelar
            </a>

            <button
                type="submit"
                class="sita-btn sita-btn-primary"
            >
                💾 Guardar permisos
            </button>

        </div>

    </form>

</main>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const formulario =
            document.getElementById('formPermisos');

        const contador =
            document.getElementById('contadorPermisos');

        const btnSeleccionarTodos =
            document.getElementById('btnSeleccionarTodos');

        const btnDesmarcarTodos =
            document.getElementById('btnDesmarcarTodos');


        function obtenerCheckboxes() {

            return formulario.querySelectorAll(
                '.sita-permiso-checkbox'
            );
        }


        function actualizarContador() {

            const checkboxes =
                obtenerCheckboxes();

            let seleccionados = 0;

            checkboxes.forEach(
                function (checkbox) {

                    if (checkbox.checked) {

                        seleccionados++;
                    }
                }
            );

            contador.textContent =
                seleccionados
                + (
                    seleccionados === 1
                        ? ' permiso'
                        : ' permisos'
                );
        }


        btnSeleccionarTodos.addEventListener(
            'click',
            function () {

                obtenerCheckboxes().forEach(
                    function (checkbox) {

                        checkbox.checked = true;
                    }
                );

                actualizarContador();
            }
        );


        btnDesmarcarTodos.addEventListener(
            'click',
            function () {

                obtenerCheckboxes().forEach(
                    function (checkbox) {

                        checkbox.checked = false;
                    }
                );

                actualizarContador();
            }
        );


        obtenerCheckboxes().forEach(
            function (checkbox) {

                checkbox.addEventListener(
                    'change',
                    actualizarContador
                );
            }
        );


        actualizarContador();

    }
);

</script>


<?php require ROOT_PATH . '/views/layout/footer.php'; ?>

