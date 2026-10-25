<?php

declare(strict_types=1);

require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';

$buscar = (string) ($buscar ?? '');

$asignaciones = $asignaciones ?? [];
$asignacionesResumen = $asignacionesResumen ?? $asignaciones;
$fichas = $fichas ?? [];
$profesionales = $profesionales ?? [];

$tiposAsignacion = [
    'COORDINADOR' => 'Coordinador académico',
    'LIDER_FICHA' => 'Líder de ficha',
    'INSTRUCTOR_TECNICO' => 'Instructor técnico',
    'INSTRUCTOR_TRANSVERSAL' => 'Instructor transversal',
    'BIENESTAR' => 'Bienestar',
    'SALUD_FISICA' => 'Salud física',
    'SALUD_MENTAL' => 'Salud mental',
    'NUTRICION' => 'Nutrición',
];

/*
 * Tipos disponibles en "Otro profesional".
 * Líder de ficha y Coordinador académico tienen su propio campo.
 */
$tiposAdicionales = array_diff_key(
    $tiposAsignacion,
    ['COORDINADOR' => true, 'LIDER_FICHA' => true]
);

/*
 * Resumen por ficha: quién es el líder y el coordinador vigentes.
 * Solo se toman asignaciones con estado "activa".
 */
$hoy = date('Y-m-d');

$resumenFichas = [];

foreach ($asignacionesResumen as $fila) {

    $idFichaFila = (int) ($fila['id_ficha'] ?? 0);

    if (!isset($resumenFichas[$idFichaFila])) {

        $resumenFichas[$idFichaFila] = [
            'id' => $idFichaFila,
            'numero' => (string) ($fila['numero_ficha'] ?? ''),
            'programa' => trim(
                (string) ($fila['codigo_programa'] ?? '')
                . ' - '
                . (string) ($fila['nombre_programa'] ?? ''),
                ' -'
            ),
            'ficha_activa' => strtolower(
                (string) ($fila['estado_ficha'] ?? 'activa')
            ) === 'activa',
            'lideres' => [],
            'coordinadores' => [],
            'otros' => [],
        ];
    }

    if (
        strtolower(trim((string) ($fila['estado'] ?? ''))) !== 'activa'
    ) {
        continue;
    }

    $tipoFila = (string) ($fila['tipo_asignacion'] ?? '');

    if ($tipoFila === 'LIDER_FICHA') {
        $resumenFichas[$idFichaFila]['lideres'][] = $fila;
    } elseif ($tipoFila === 'COORDINADOR') {
        $resumenFichas[$idFichaFila]['coordinadores'][] = $fila;
    } else {
        $resumenFichas[$idFichaFila]['otros'][] = $fila;
    }
}

$resumenFichas = array_values($resumenFichas);

usort(
    $resumenFichas,
    static fn (array $a, array $b): int =>
        strnatcmp($a['numero'], $b['numero'])
);

/*
 * Celda de líder / coordinador: muestra a la persona con el botón
 * "Quitar", o "Sin asignar" con el botón "Asignar".
 */
$renderResponsables = static function (
    array $ficha,
    array $titulares,
    string $campoFormulario,
    string $rolTexto
) use ($hoy): void {

    if (empty($titulares)) {

        ?>

        <div class="sita-resp sita-resp-vacio">

            <span class="sita-resp-sin">
                Sin asignar
            </span>

            <?php if ($ficha['ficha_activa']): ?>

                <button
                    type="button"
                    class="sita-action sita-btn-asignar"
                    data-ficha-id="<?= (int) $ficha['id'] ?>"
                    data-campo="<?= e($campoFormulario) ?>"
                >
                    + Asignar
                </button>

            <?php else: ?>

                <span class="sita-resp-meta">
                    Ficha no activa
                </span>

            <?php endif; ?>

        </div>

        <?php

        return;
    }

    foreach ($titulares as $titular) {

        $nombre = trim(
            (string) ($titular['nombres'] ?? '')
            . ' '
            . (string) ($titular['apellidos'] ?? '')
        );

        $fin = (string) ($titular['fecha_fin'] ?? '');

        $vencida = $fin !== '' && $fin < $hoy;

        $mensaje =
            '¿Quitar a ' . $nombre . ' como ' . $rolTexto
            . ' de la ficha ' . $ficha['numero'] . '?'
            . "\n\n"
            . 'ACEPTAR: se quita y la ficha queda sin ' . $rolTexto
            . ', para poder asignar uno nuevo.'
            . "\n"
            . 'CANCELAR: se deja como está.';

        ?>

        <div class="sita-resp">

            <span class="sita-resp-nombre">
                <?= e($nombre !== '' ? $nombre : 'Sin nombre') ?>
            </span>

            <?php if (!empty($titular['documento'])): ?>

                <span class="sita-resp-meta">
                    Doc. <?= e((string) $titular['documento']) ?>
                </span>

            <?php endif; ?>

            <span class="sita-resp-meta">

                Desde <?= e((string) ($titular['fecha_inicio'] ?? '')) ?>

                ·

                <?= $fin !== ''
                    ? 'hasta ' . e($fin)
                    : 'sin fecha de fin' ?>

                <?php if ($vencida): ?>

                    <span class="sita-badge sita-badge-vencida">
                        VENCIDA
                    </span>

                <?php endif; ?>

            </span>

            <form
                method="POST"
                action="<?= e(url('/asignaciones-ficha/finalizar')) ?>"
                class="sita-form-quitar"
                data-confirm="<?= e($mensaje) ?>"
            >

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $titular['id'] ?>"
                >

                <input
                    type="hidden"
                    name="fecha_fin"
                    value="<?= e($hoy) ?>"
                >

                <button
                    type="submit"
                    class="sita-action sita-action-danger"
                    title="Quitar a <?= e($nombre) ?> como <?= e($rolTexto) ?>"
                >
                    Quitar
                </button>

            </form>

        </div>

        <?php
    }
};

/*
 * Selector con buscador de profesionales (se reutiliza 3 veces).
 */
$renderSelectProfesional = static function (
    string $id,
    string $name,
    string $placeholder,
    array $profesionales
): void {

    ?>

    <div
        class="sita-select-search"
        data-select-search="<?= e($id) ?>"
        data-placeholder="<?= e($placeholder) ?>"
    >

        <input
            type="hidden"
            id="<?= e($id) ?>"
            name="<?= e($name) ?>"
            value=""
        >

        <button
            type="button"
            class="sita-select-search-button"
            aria-haspopup="listbox"
            aria-expanded="false"
        >
            <span class="sita-select-search-placeholder">
                <?= e($placeholder) ?>
            </span>
        </button>

        <div class="sita-select-search-dropdown">

            <div class="sita-select-search-input-wrapper">

                <span class="sita-select-search-icon">
                    🔎
                </span>

                <input
                    type="text"
                    class="sita-select-search-input"
                    placeholder="Buscar por nombre, documento o tipo..."
                    autocomplete="off"
                >

            </div>

            <div
                class="sita-select-search-options"
                role="listbox"
            >

                <button
                    type="button"
                    class="sita-select-search-option sita-select-search-option-clear"
                    data-value=""
                    data-search="sin asignar"
                >
                    Sin asignar
                </button>

                <?php foreach ($profesionales as $profesional): ?>

                    <?php

                    $nombreProfesional = trim(
                        (string) (
                            ($profesional['nombres'] ?? '')
                            . ' '
                            . ($profesional['apellidos'] ?? '')
                        )
                    );

                    $textoProfesional = trim(
                        $nombreProfesional
                        . ' '
                        . ($profesional['tipo_profesional'] ?? '')
                        . ' '
                        . ($profesional['documento'] ?? '')
                    );

                    ?>

                    <button
                        type="button"
                        class="sita-select-search-option"
                        data-value="<?= (int) $profesional['id'] ?>"
                        data-search="<?= e(
                            mb_strtolower($textoProfesional)
                        ) ?>"
                    >

                        <?= e($nombreProfesional) ?>

                        <?php if (!empty($profesional['tipo_profesional'])): ?>
                            -
                            <?= e((string) $profesional['tipo_profesional']) ?>
                        <?php endif; ?>

                        <?php if (!empty($profesional['documento'])): ?>
                            -
                            <?= e((string) $profesional['documento']) ?>
                        <?php endif; ?>

                    </button>

                <?php endforeach; ?>

            </div>

        </div>

    </div>

    <?php
};

?>

<style>

    .sita-page {
        padding: 30px;
        background: #f5f7fa;
        min-height: calc(100vh - 140px);
    }

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
    }

    .sita-panel {
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);
        border: 1px solid #e5e7eb;
        overflow: hidden;
        margin-bottom: 25px;
    }

    .sita-panel-header {
        padding: 20px 22px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .sita-panel-title {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .sita-panel-title strong {
        font-size: 15px;
        color: #1f2937;
    }

    .sita-resultados {
        font-size: 13px;
        color: #6b7280;
    }

    /*
     * FORMULARIO
     */

    .sita-form-body {
        padding: 24px 22px;
    }

    .sita-form-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
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

    .sita-form-label span {
        color: #dc2626;
    }

    .sita-form-control {
        width: 100%;
        height: 44px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        padding: 0 13px;
        font-size: 14px;
        color: #374151;
        background: #ffffff;
        outline: none;
        transition: all 0.2s ease;
        box-sizing: border-box;
    }

    textarea.sita-form-control {
        height: 90px;
        padding: 12px 13px;
        resize: vertical;
    }

    .sita-form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
    }

    .sita-form-help {
        font-size: 12px;
        color: #9ca3af;
    }

    .sita-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }

    /*
     * SELECTOR CON BUSCADOR
     */

    .sita-select-search {
        position: relative;
        width: 100%;
    }

    .sita-select-search-button {
        width: 100%;
        height: 44px;
        padding: 0 38px 0 13px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        background: #ffffff;
        color: #374151;
        font-size: 14px;
        text-align: left;
        cursor: pointer;
        outline: none;
        position: relative;
        transition: all 0.2s ease;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sita-select-search-button:hover {
        border-color: #9ca3af;
    }

    .sita-select-search-button:focus,
    .sita-select-search.open .sita-select-search-button {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
    }

    .sita-select-search-button::after {
        content: '▾';
        position: absolute;
        right: 13px;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
        font-size: 12px;
        pointer-events: none;
    }

    .sita-select-search-placeholder {
        color: #9ca3af;
    }

    .sita-select-search-dropdown {
        display: none;
        position: absolute;
        top: calc(100% + 5px);
        left: 0;
        width: 100%;
        background: #ffffff;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.14);
        z-index: 5000;
        padding: 8px;
    }

    .sita-select-search.open .sita-select-search-dropdown {
        display: block;
    }

    .sita-select-search-input-wrapper {
        position: relative;
        margin-bottom: 7px;
    }

    .sita-select-search-input {
        width: 100%;
        height: 40px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 0 11px 0 34px;
        font-size: 13px;
        color: #374151;
        background: #f9fafb;
        outline: none;
        box-sizing: border-box;
    }

    .sita-select-search-input:focus {
        border-color: #2563eb;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
    }

    .sita-select-search-icon {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 13px;
        pointer-events: none;
    }

    .sita-select-search-options {
        max-height: 250px;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .sita-select-search-option {
        width: 100%;
        min-height: 40px;
        padding: 8px 10px;
        border: none;
        border-radius: 7px;
        background: transparent;
        color: #374151;
        text-align: left;
        font-size: 13px;
        line-height: 1.35;
        cursor: pointer;
        display: block;
    }

    .sita-select-search-option:hover {
        background: #f2f6f8;
        color: #123b5d;
    }

    .sita-select-search-option.selected {
        background: #eaf7f7;
        color: #13898a;
        font-weight: 700;
    }

    .sita-select-search-empty {
        padding: 15px 10px;
        text-align: center;
        color: #9ca3af;
        font-size: 13px;
    }

    .sita-select-search-native {
        display: none;
    }

    /*
     * BOTONES
     */

    .sita-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        height: 42px;
        padding: 0 16px;
        border-radius: 9px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
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
    }

    .sita-btn-secondary:hover {
        background: #e5e7eb;
        color: #111827;
    }

    .sita-btn-danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .sita-btn-danger:hover {
        background: #fecaca;
        color: #7f1d1d;
    }

    /*
     * MENSAJES
     */

    .sita-alert {
        margin-bottom: 20px;
        padding: 14px 16px;
        border-radius: 9px;
        font-size: 14px;
        border: 1px solid;
    }

    .sita-alert-success {
        background: #ecfdf5;
        color: #065f46;
        border-color: #a7f3d0;
    }

    .sita-alert-error {
        background: #fef2f2;
        color: #991b1b;
        border-color: #fecaca;
    }

    /*
     * BUSCADOR
     */

    .sita-search-form {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        max-width: 560px;
    }

    .sita-search {
        position: relative;
        flex: 1;
    }

    .sita-search input {
        width: 100%;
        height: 44px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        padding: 0 15px;
        font-size: 14px;
        color: #374151;
        background: #ffffff;
        outline: none;
        transition: all 0.2s ease;
        box-sizing: border-box;
    }

    .sita-search input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
    }

    .sita-search input::placeholder {
        color: #9ca3af;
    }

    /*
     * TABLA
     */

    .sita-table-wrapper {
        width: 100%;
        overflow-x: auto;
    }

    .sita-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1050px;
    }

    .sita-table thead th {
        background: #f9fafb;
        color: #4b5563;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 14px 18px;
        border-bottom: 1px solid #e5e7eb;
        text-align: left;
        white-space: nowrap;
    }

    .sita-table tbody td {
        padding: 16px 18px;
        border-bottom: 1px solid #f0f2f5;
        color: #374151;
        font-size: 14px;
        vertical-align: middle;
    }

    .sita-table tbody tr {
        transition: background 0.15s ease;
    }

    .sita-table tbody tr:hover {
        background: #f9fafb;
    }

    .sita-table tbody tr:last-child td {
        border-bottom: none;
    }

    .sita-ficha {
        display: inline-flex;
        align-items: center;
        background: #eef2ff;
        color: #3730a3;
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 12px;
        font-weight: 600;
    }

    .sita-profesional {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .sita-profesional-nombre {
        font-weight: 600;
        color: #1f2937;
    }

    .sita-profesional-documento {
        font-size: 12px;
        color: #9ca3af;
    }

    .sita-tipo {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }

    .sita-fecha {
        font-size: 13px;
        color: #4b5563;
        white-space: nowrap;
    }

    /*
     * ESTADOS
     */

    .sita-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 28px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
        line-height: 1.2;
    }

    .estado-activa {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .estado-finalizada {
        background: #e5e7eb;
        color: #374151;
        border: 1px solid #d1d5db;
    }

    .estado-cancelada {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    /*
     * ACCIONES
     */

    .sita-actions {
        display: flex;
        align-items: center;
        gap: 7px;
        flex-wrap: wrap;
    }

    .sita-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 34px;
        padding: 0 10px;
        border-radius: 8px;
        border: none;
        background: #eff6ff;
        color: #2563eb;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .sita-action:hover {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .sita-action-danger {
        background: #fef2f2;
        color: #dc2626;
    }

    .sita-action-danger:hover {
        background: #fee2e2;
        color: #b91c1c;
    }

    /*
     * VACÍO
     */

    .sita-empty {
        padding: 55px 20px;
        text-align: center;
        color: #6b7280;
    }

    .sita-empty-icon {
        font-size: 38px;
        margin-bottom: 12px;
    }

    .sita-empty-title {
        margin: 0 0 6px;
        font-size: 17px;
        font-weight: 700;
        color: #374151;
    }

    .sita-empty-text {
        margin: 0;
        font-size: 14px;
        color: #9ca3af;
    }

    /*
     * RESPONSIVE
     */

    @media (max-width: 1000px) {

        .sita-form-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 900px) {

        .sita-panel-header {
            align-items: stretch;
            flex-direction: column;
        }

        .sita-search-form {
            max-width: none;
        }
    }

    @media (max-width: 768px) {

        .sita-page {
            padding: 20px 15px;
        }

        .sita-page-title {
            font-size: 24px;
        }

        .sita-form-grid {
            grid-template-columns: 1fr;
        }

        .sita-form-group-full {
            grid-column: auto;
        }

        .sita-form-actions {
            flex-direction: column;
        }

        .sita-form-actions .sita-btn {
            width: 100%;
        }

        .sita-search-form {
            flex-direction: column;
            align-items: stretch;
        }

        .sita-search-form .sita-btn {
            width: 100%;
        }
    }

    /*
     * FORMULARIO: RESPONSABLES DE LA FICHA
     */

    .sita-form-error {
        display: none;
        margin-bottom: 16px;
        padding: 12px 14px;
        border-radius: 9px;
        border: 1px solid #fecaca;
        background: #fef2f2;
        color: #991b1b;
        font-size: 14px;
    }

    .sita-form-error.visible {
        display: block;
    }

    .sita-form-group-title {
        font-size: 12px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: .4px;
        margin: 4px 0 -6px;
    }

    .sita-form-optional {
        font-weight: 500;
        color: #9ca3af;
    }

    .sita-select-search-option-clear {
        color: #6b7280;
        font-style: italic;
        border-bottom: 1px solid #e5e7eb;
    }

    /*
     * RESUMEN: RESPONSABLES POR FICHA
     */

    .sita-programa {
        margin-top: 6px;
        font-size: 12px;
        color: #6b7280;
        max-width: 260px;
    }

    .sita-resp {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 3px;
        padding: 10px 12px;
        margin-bottom: 8px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #ffffff;
        min-width: 220px;
    }

    .sita-resp:last-child {
        margin-bottom: 0;
    }

    .sita-resp-nombre {
        font-weight: 700;
        color: #1f2937;
        font-size: 13px;
    }

    .sita-resp-meta {
        font-size: 12px;
        color: #6b7280;
    }

    .sita-resp-vacio {
        border-style: dashed;
        background: #fafafa;
    }

    .sita-resp-sin {
        font-size: 13px;
        font-weight: 600;
        color: #9ca3af;
    }

    .sita-resp form {
        margin-top: 6px;
    }

    .sita-resp .sita-btn-asignar {
        margin-top: 6px;
    }

    .sita-badge-vencida {
        min-height: 20px;
        margin-left: 6px;
        padding: 2px 8px;
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }

    .sita-otros-item {
        font-size: 12px;
        color: #374151;
        margin-bottom: 4px;
    }

    .sita-otros-item small {
        color: #6b7280;
    }

    .sita-detalle {
        border-top: 1px solid #e5e7eb;
    }

    .sita-detalle > summary {
        cursor: pointer;
        padding: 14px 22px;
        font-size: 13px;
        font-weight: 700;
        color: #374151;
        background: #f9fafb;
        list-style: none;
    }

    .sita-detalle > summary::-webkit-details-marker {
        display: none;
    }

    .sita-detalle > summary::before {
        content: '+';
        display: inline-block;
        width: 18px;
        font-weight: 800;
        color: #2563eb;
    }

    .sita-detalle[open] > summary::before {
        content: '−';
    }

    .sita-form-check {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 11px 14px;
        border: 1px solid #fde68a;
        border-radius: 10px;
        background: #fffbeb;
        color: #78350f;
        font-size: 13px;
        line-height: 1.4;
        cursor: pointer;
    }

    .sita-form-check input {
        margin-top: 2px;
        flex: 0 0 auto;
    }

    .sita-form-details {
        border: 1px dashed #d1d5db;
        border-radius: 10px;
        background: #fafafa;
    }

    .sita-form-details > summary {
        cursor: pointer;
        padding: 12px 14px;
        font-size: 13px;
        font-weight: 700;
        color: #374151;
        list-style: none;
    }

    .sita-form-details > summary::-webkit-details-marker {
        display: none;
    }

    .sita-form-details > summary::before {
        content: '+';
        display: inline-block;
        width: 18px;
        font-weight: 800;
        color: #2563eb;
    }

    .sita-form-details[open] > summary::before {
        content: '−';
    }

    .sita-form-details-body {
        padding: 4px 14px 16px;
    }

    .sita-form-details-body .sita-form-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    @media (max-width: 768px) {
        .sita-form-details-body .sita-form-grid {
            grid-template-columns: 1fr;
        }
    }

</style>

<div class="sita-page">

<!-- ENCABEZADO -->

<div class="sita-page-header">


<div>

    <h1 class="sita-page-title">
        Asignaciones de ficha
    </h1>

    <p class="sita-page-subtitle">
        Administre los profesionales responsables de cada ficha y el período de su asignación.
    </p>

</div>


</div>

<!-- MENSAJES -->

<?php if (!empty($_SESSION['success'])): ?>


<div class="sita-alert sita-alert-success">

    <?= e((string) $_SESSION['success']) ?>

</div>

<?php unset($_SESSION['success']); ?>


<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>


<div class="sita-alert sita-alert-error">

    <?= e((string) $_SESSION['error']) ?>

</div>

<?php unset($_SESSION['error']); ?>


<?php endif; ?>

<!-- FORMULARIO NUEVA ASIGNACIÓN -->

<div class="sita-panel" id="sitaFormAsignacion">


<div class="sita-panel-header">

    <div class="sita-panel-title">

        <strong>
            Nueva asignación
        </strong>

        <div class="sita-resultados">
            Asigne a una ficha su líder y su coordinador académico, y defina el período de responsabilidad.
        </div>

    </div>

</div>


<div class="sita-form-body">

    <div
        id="sitaAsignacionError"
        class="sita-form-error"
        role="alert"
    ></div>

    <form
        method="POST"
        action="<?= e(url('/asignaciones-ficha/crear')) ?>"
        novalidate
    >

        <div class="sita-form-grid">


            <!-- FICHA -->

            <div class="sita-form-group">

                <label
                    for="id_ficha"
                    class="sita-form-label"
                >
                    Ficha <span>*</span>
                </label>

                <div
                    class="sita-select-search"
                    data-select-search="ficha"
                    data-placeholder="Seleccione una ficha"
                >

                    <input
                        type="hidden"
                        id="id_ficha"
                        name="id_ficha"
                        value=""
                    >

                    <button
                        type="button"
                        class="sita-select-search-button"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                    >
                        <span class="sita-select-search-placeholder">
                            Seleccione una ficha
                        </span>
                    </button>

                    <div class="sita-select-search-dropdown">

                        <div class="sita-select-search-input-wrapper">

                            <span class="sita-select-search-icon">
                                🔎
                            </span>

                            <input
                                type="text"
                                class="sita-select-search-input"
                                placeholder="Buscar ficha o programa..."
                                autocomplete="off"
                            >

                        </div>

                        <div
                            class="sita-select-search-options"
                            role="listbox"
                        >

                            <?php foreach ($fichas as $ficha): ?>

                                <?php

                                $textoFicha = trim(
                                    (string) $ficha['numero_ficha']
                                    . ' '
                                    . ($ficha['nombre_programa'] ?? '')
                                );

                                ?>

                                <button
                                    type="button"
                                    class="sita-select-search-option"
                                    data-value="<?= (int) $ficha['id'] ?>"
                                    data-search="<?= e(
                                        mb_strtolower($textoFicha)
                                    ) ?>"
                                >

                                    <?= e(
                                        (string) $ficha['numero_ficha']
                                    ) ?>

                                    <?php if (!empty($ficha['nombre_programa'])): ?>

                                        -
                                        <?= e(
                                            (string) $ficha['nombre_programa']
                                        ) ?>

                                    <?php endif; ?>

                                </button>

                            <?php endforeach; ?>

                        </div>

                    </div>

                </div>

            </div>


            <!-- LÍDER DE FICHA -->

            <div class="sita-form-group">

                <label
                    for="id_lider_ficha"
                    class="sita-form-label"
                >
                    Líder de ficha
                </label>

                <?php $renderSelectProfesional(
                    'id_lider_ficha',
                    'id_lider_ficha',
                    'Seleccione el líder de ficha',
                    $profesionales
                ); ?>

            </div>


            <!-- COORDINADOR ACADÉMICO -->

            <div class="sita-form-group">

                <label
                    for="id_coordinador"
                    class="sita-form-label"
                >
                    Coordinador académico
                </label>

                <?php $renderSelectProfesional(
                    'id_coordinador',
                    'id_coordinador',
                    'Seleccione el coordinador académico',
                    $profesionales
                ); ?>

            </div>


            <div class="sita-form-group-full">

                <div class="sita-form-help">
                    Seleccione al menos un responsable. Se creará una
                    asignación por cada uno, con las mismas fechas.
                    Una ficha solo puede tener un líder y un coordinador
                    vigentes al mismo tiempo; para cambiarlos use la casilla
                    «Reemplazar».
                </div>

            </div>


            <!-- REEMPLAZAR TITULAR VIGENTE -->

            <div class="sita-form-group-full">

                <label class="sita-form-check">

                    <input
                        type="checkbox"
                        id="reemplazar"
                        name="reemplazar"
                        value="1"
                    >

                    <span>
                        <strong>Reemplazar al titular vigente</strong>
                        — si la ficha ya tiene líder o coordinador en esas
                        fechas, se finaliza su asignación el día anterior a la
                        fecha de inicio y se registra el nuevo.
                    </span>

                </label>

            </div>


            <!-- FECHA INICIO -->

            <div class="sita-form-group">

                <label
                    for="fecha_inicio"
                    class="sita-form-label"
                >
                    Fecha de inicio <span>*</span>
                </label>

                <input
                    type="date"
                    id="fecha_inicio"
                    name="fecha_inicio"
                    class="sita-form-control"
                    value="<?= e(date('Y-m-d')) ?>"
                    required
                >

            </div>


            <!-- FECHA FIN -->

            <div class="sita-form-group">

                <label
                    for="fecha_fin"
                    class="sita-form-label"
                >
                    Fecha de finalización
                </label>

                <input
                    type="date"
                    id="fecha_fin"
                    name="fecha_fin"
                    class="sita-form-control"
                >

                <div class="sita-form-help">
                    Déjela vacía si la asignación continúa vigente.
                </div>

            </div>


            <!-- OTRO PROFESIONAL -->

            <div class="sita-form-group-full">

                <details
                    class="sita-form-details"
                    id="sitaOtroProfesional"
                >

                    <summary>
                        Asignar también otro profesional
                        <span class="sita-form-optional">
                            (instructor, bienestar, salud… — opcional)
                        </span>
                    </summary>

                    <div class="sita-form-details-body">

                        <div class="sita-form-grid">

                            <div class="sita-form-group">

                                <label
                                    for="id_profesional"
                                    class="sita-form-label"
                                >
                                    Profesional
                                </label>

                                <?php $renderSelectProfesional(
                                    'id_profesional',
                                    'id_profesional',
                                    'Seleccione un profesional',
                                    $profesionales
                                ); ?>

                            </div>

                            <div class="sita-form-group">

                                <label
                                    for="tipo_asignacion"
                                    class="sita-form-label"
                                >
                                    Tipo de asignación
                                </label>

                                <select
                                    id="tipo_asignacion"
                                    name="tipo_asignacion"
                                    class="sita-form-control"
                                >

                                    <option value="">
                                        Seleccione el tipo
                                    </option>

                                    <?php foreach ($tiposAdicionales as $valor => $nombre): ?>

                                        <option
                                            value="<?= e($valor) ?>"
                                        >
                                            <?= e($nombre) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                        </div>

                    </div>

                </details>

            </div>


            <!-- OBSERVACIONES -->

            <div class="sita-form-group sita-form-group-full">

                <label
                    for="observaciones"
                    class="sita-form-label"
                >
                    Observaciones
                </label>

                <textarea
                    id="observaciones"
                    name="observaciones"
                    class="sita-form-control"
                    maxlength="500"
                    placeholder="Observaciones sobre la asignación..."
                ></textarea>

            </div>

        </div>


        <div class="sita-form-actions">

            <button
                type="reset"
                class="sita-btn sita-btn-secondary"
            >
                Limpiar
            </button>

            <button
                type="submit"
                class="sita-btn sita-btn-primary"
            >
                + Crear asignaciones
            </button>

        </div>

    </form>

</div>


</div>

<!-- LISTADO -->

<div class="sita-panel">


<div class="sita-panel-header">

    <div class="sita-panel-title">

        <strong>
            Responsables por ficha
        </strong>

        <div class="sita-resultados">

            <?= number_format(count($resumenFichas), 0, ',', '.') ?>

            ficha<?= count($resumenFichas) === 1 ? '' : 's' ?>

            ·

            <?= number_format(count($asignaciones), 0, ',', '.') ?>

            asignaci<?= count($asignaciones) === 1 ? 'ón' : 'ones' ?>

            <?php if ($buscar !== ''): ?>

                encontradas para:

                <strong>
                    <?= e($buscar) ?>
                </strong>

            <?php endif; ?>

        </div>

    </div>


    <!-- BUSCADOR -->

    <form
        method="GET"
        action="<?= e(url('/asignaciones-ficha')) ?>"
        class="sita-search-form"
    >

        <div class="sita-search">

            <input
                type="text"
                name="buscar"
                value="<?= e($buscar) ?>"
                placeholder="Buscar por ficha, profesional o tipo..."
                autocomplete="off"
            >

        </div>


        <button
            type="submit"
            class="sita-btn sita-btn-primary"
        >
            🔎 Buscar
        </button>


        <?php if ($buscar !== ''): ?>

            <a
                href="<?= e(url('/asignaciones-ficha')) ?>"
                class="sita-btn sita-btn-secondary"
            >
                Limpiar
            </a>

        <?php endif; ?>

    </form>

</div>


<!-- RESPONSABLES POR FICHA -->

<?php if (empty($resumenFichas)): ?>

    <div class="sita-empty">

        <div class="sita-empty-icon">
            👥
        </div>

        <h3 class="sita-empty-title">
            No hay asignaciones registradas
        </h3>

        <p class="sita-empty-text">

            <?php if ($buscar !== ''): ?>

                No hay resultados para
                "<strong><?= e($buscar) ?></strong>".

            <?php else: ?>

                Todavía no se han creado asignaciones de ficha.

            <?php endif; ?>

        </p>

    </div>

<?php else: ?>

    <div class="sita-table-wrapper">

        <table class="sita-table">

            <thead>

                <tr>
                    <th>Ficha</th>
                    <th>Líder de ficha</th>
                    <th>Coordinador académico</th>
                    <th>Otros profesionales</th>
                </tr>

            </thead>

            <tbody>

                <?php foreach ($resumenFichas as $ficha): ?>

                    <tr>

                        <td>

                            <span class="sita-ficha">
                                <?= e($ficha['numero']) ?>
                            </span>

                            <?php if ($ficha['programa'] !== ''): ?>

                                <div class="sita-programa">
                                    <?= e($ficha['programa']) ?>
                                </div>

                            <?php endif; ?>

                        </td>

                        <td>
                            <?php $renderResponsables(
                                $ficha,
                                $ficha['lideres'],
                                'id_lider_ficha',
                                'líder de ficha'
                            ); ?>
                        </td>

                        <td>
                            <?php $renderResponsables(
                                $ficha,
                                $ficha['coordinadores'],
                                'id_coordinador',
                                'coordinador académico'
                            ); ?>
                        </td>

                        <td>

                            <?php if (empty($ficha['otros'])): ?>

                                <span class="sita-sin-dato">—</span>

                            <?php else: ?>

                                <?php foreach ($ficha['otros'] as $otro): ?>

                                    <div class="sita-otros-item">

                                        <?= e(trim(
                                            (string) ($otro['nombres'] ?? '')
                                            . ' '
                                            . (string) ($otro['apellidos'] ?? '')
                                        )) ?>

                                        <small>
                                            ·
                                            <?= e(
                                                $tiposAsignacion[
                                                    (string) $otro['tipo_asignacion']
                                                ] ?? (string) $otro['tipo_asignacion']
                                            ) ?>
                                        </small>

                                    </div>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

<?php endif; ?>


<!-- DETALLE DE TODAS LAS ASIGNACIONES -->

<details class="sita-detalle">

<summary>
    Ver detalle de todas las asignaciones (historial y otros profesionales)
</summary>


<!-- RESULTADOS -->

<?php if (empty($asignaciones)): ?>

    <div class="sita-empty">

        <div class="sita-empty-icon">
            👥
        </div>

        <h3 class="sita-empty-title">
            No hay asignaciones registradas
        </h3>

        <p class="sita-empty-text">

            <?php if ($buscar !== ''): ?>

                No hay resultados para
                "<strong><?= e($buscar) ?></strong>".

            <?php else: ?>

                Todavía no se han creado asignaciones de ficha.

            <?php endif; ?>

        </p>

    </div>

<?php else: ?>

    <div class="sita-table-wrapper">

        <table class="sita-table">

            <thead>

                <tr>

                    <th>
                        Ficha
                    </th>

                    <th>
                        Programa
                    </th>

                    <th>
                        Profesional
                    </th>

                    <th>
                        Tipo
                    </th>

                    <th>
                        Inicio
                    </th>

                    <th>
                        Fin
                    </th>

                    <th>
                        Estado
                    </th>

                    <th>
                        Acción
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php foreach ($asignaciones as $asignacion): ?>

                    <?php

                    $estado = strtolower(
                        trim(
                            (string) (
                                $asignacion['estado'] ?? ''
                            )
                        )
                    );

                    $claseEstado = match ($estado) {

                        'activa' =>
                            'sita-badge estado-activa',

                        'finalizada' =>
                            'sita-badge estado-finalizada',

                        'cancelada' =>
                            'sita-badge estado-cancelada',

                        default =>
                            'sita-badge estado-finalizada',
                    };

                    $tipoAsignacion = (string) (
                        $asignacion['tipo_asignacion'] ?? ''
                    );

                    $nombreTipo = $tiposAsignacion[
                        $tipoAsignacion
                    ] ?? $tipoAsignacion;

                    $nombreProfesional = trim(
                        (string) (
                            ($asignacion['nombres'] ?? '') .
                            ' ' .
                            ($asignacion['apellidos'] ?? '')
                        )
                    );

                    ?>

                    <tr>

                        <!-- FICHA -->

                        <td>

                            <span class="sita-ficha">

                                <?= e(
                                    (string) (
                                        $asignacion['numero_ficha'] ?? ''
                                    )
                                ) ?>

                            </span>

                        </td>


                        <!-- PROGRAMA -->

                        <td>

                            <?= e(
                                (string) (
                                    $asignacion['nombre_programa'] ??
                                    'Sin programa'
                                )
                            ) ?>

                        </td>


                        <!-- PROFESIONAL -->

                        <td>

                            <div class="sita-profesional">

                                <span class="sita-profesional-nombre">

                                    <?= e(
                                        $nombreProfesional !== ''
                                            ? $nombreProfesional
                                            : 'Sin profesional'
                                    ) ?>

                                </span>


                                <?php if (!empty($asignacion['documento'])): ?>

                                    <span class="sita-profesional-documento">

                                        <?= e(
                                            (string) $asignacion['documento']
                                        ) ?>

                                    </span>

                                <?php endif; ?>

                            </div>

                        </td>


                        <!-- TIPO -->

                        <td>

                            <span class="sita-tipo">

                                <?= e($nombreTipo) ?>

                            </span>

                        </td>


                        <!-- FECHA INICIO -->

                        <td>

                            <span class="sita-fecha">

                                <?= e(
                                    (string) (
                                        $asignacion['fecha_inicio'] ??
                                        ''
                                    )
                                ) ?>

                            </span>

                        </td>


                        <!-- FECHA FIN -->

                        <td>

                            <span class="sita-fecha">

                                <?= e(
                                    !empty($asignacion['fecha_fin'])
                                        ? (string) $asignacion['fecha_fin']
                                        : '—'
                                ) ?>

                            </span>

                        </td>


                        <!-- ESTADO -->

                        <td>

                            <span class="<?= e($claseEstado) ?>">

                                <?= e(
                                    $estado !== ''
                                        ? strtoupper($estado)
                                        : 'SIN ESTADO'
                                ) ?>

                            </span>

                        </td>


                        <!-- ACCIÓN -->

                        <td>

                            <div class="sita-actions">

                                <?php if ($estado === 'activa'): ?>

                                    <form
                                        method="POST"
                                        action="<?= e(
                                            url('/asignaciones-ficha/finalizar')
                                        ) ?>"
                                        onsubmit="return confirm(
                                            '¿Está seguro de finalizar esta asignación?'
                                        );"
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $asignacion['id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="fecha_fin"
                                            value="<?= date('Y-m-d') ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="sita-action sita-action-danger"
                                            title="Finalizar asignación"
                                        >
                                            Finalizar
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <span class="sita-sin-dato">
                                        Sin acciones
                                    </span>

                                <?php endif; ?>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

<?php endif; ?>

</details>


</div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const selectSearchContainers =
        document.querySelectorAll('.sita-select-search');

    selectSearchContainers.forEach(function (container) {

        const button =
            container.querySelector('.sita-select-search-button');

        const hiddenInput =
            container.querySelector('input[type="hidden"]');

        const searchInput =
            container.querySelector('.sita-select-search-input');

        const optionsContainer =
            container.querySelector('.sita-select-search-options');

        const options =
            Array.from(
                container.querySelectorAll('.sita-select-search-option')
            );

        const placeholder =
            button.querySelector('.sita-select-search-placeholder');

        function normalizarTexto(texto) {

            return String(texto || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
        }

        function cerrarSelector() {

            container.classList.remove('open');

            button.setAttribute(
                'aria-expanded',
                'false'
            );

            searchInput.value = '';

            filtrarOpciones('');
        }

        function abrirSelector() {

            document
                .querySelectorAll('.sita-select-search.open')
                .forEach(function (otro) {

                    if (otro !== container) {

                        otro.classList.remove('open');

                        const otroButton =
                            otro.querySelector(
                                '.sita-select-search-button'
                            );

                        const otraBusqueda =
                            otro.querySelector(
                                '.sita-select-search-input'
                            );

                        if (otroButton) {
                            otroButton.setAttribute(
                                'aria-expanded',
                                'false'
                            );
                        }

                        if (otraBusqueda) {
                            otraBusqueda.value = '';
                        }
                    }
                });

            container.classList.add('open');

            button.setAttribute(
                'aria-expanded',
                'true'
            );

            setTimeout(function () {
                searchInput.focus();
            }, 10);
        }

        function filtrarOpciones(termino) {

            const terminoNormalizado =
                normalizarTexto(termino);

            let visibles = 0;

            options.forEach(function (option) {

                const textoBusqueda =
                    normalizarTexto(
                        option.getAttribute('data-search') ||
                        option.textContent
                    );

                const coincide =
                    terminoNormalizado === '' ||
                    textoBusqueda.includes(
                        terminoNormalizado
                    );

                option.style.display =
                    coincide ? 'block' : 'none';

                if (coincide) {
                    visibles++;
                }
            });

            let mensajeVacio =
                optionsContainer.querySelector(
                    '.sita-select-search-empty'
                );

            if (visibles === 0) {

                if (!mensajeVacio) {

                    mensajeVacio =
                        document.createElement('div');

                    mensajeVacio.className =
                        'sita-select-search-empty';

                    mensajeVacio.textContent =
                        'No se encontraron resultados.';

                    optionsContainer.appendChild(
                        mensajeVacio
                    );
                }

                mensajeVacio.style.display = 'block';

            } else if (mensajeVacio) {

                mensajeVacio.style.display = 'none';
            }
        }

        function seleccionarOpcion(option) {

            const value =
                option.getAttribute('data-value') || '';

            hiddenInput.value = value;

            const texto =
                option.textContent
                    .replace(/\s+/g, ' ')
                    .trim();

            if (value === '') {

                // "Sin asignar": vuelve al texto inicial del selector.
                placeholder.textContent =
                    container.getAttribute('data-placeholder') || '';

                placeholder.classList.add(
                    'sita-select-search-placeholder'
                );

            } else {

                placeholder.textContent = texto;

                placeholder.classList.remove(
                    'sita-select-search-placeholder'
                );
            }

            options.forEach(function (item) {

                item.classList.remove('selected');

            });

            option.classList.add('selected');

            cerrarSelector();
        }

        button.addEventListener(
            'click',
            function (event) {

                event.stopPropagation();

                if (container.classList.contains('open')) {
                    cerrarSelector();
                } else {
                    abrirSelector();
                }
            }
        );

        searchInput.addEventListener(
            'input',
            function () {

                filtrarOpciones(
                    searchInput.value
                );
            }
        );

        searchInput.addEventListener(
            'click',
            function (event) {

                event.stopPropagation();
            }
        );

        options.forEach(function (option) {

            option.addEventListener(
                'click',
                function (event) {

                    event.stopPropagation();

                    seleccionarOpcion(option);
                }
            );

        });

        filtrarOpciones('');

    });


    document.addEventListener(
        'click',
        function (event) {

            if (
                !event.target.closest(
                    '.sita-select-search'
                )
            ) {

                document
                    .querySelectorAll(
                        '.sita-select-search.open'
                    )
                    .forEach(function (container) {

                        container.classList.remove(
                            'open'
                        );

                        const button =
                            container.querySelector(
                                '.sita-select-search-button'
                            );

                        const searchInput =
                            container.querySelector(
                                '.sita-select-search-input'
                            );

                        if (button) {

                            button.setAttribute(
                                'aria-expanded',
                                'false'
                            );
                        }

                        if (searchInput) {

                            searchInput.value = '';
                        }

                        const options =
                            container.querySelectorAll(
                                '.sita-select-search-option'
                            );

                        options.forEach(function (option) {

                            option.style.display =
                                'block';

                        });

                    });

            }

        }
    );


    const form =
        document.querySelector(
            'form[action*="/asignaciones-ficha/crear"]'
        );

    if (form) {

        form.addEventListener(
            'reset',
            function () {

                setTimeout(function () {

                    document
                        .querySelectorAll(
                            '.sita-select-search'
                        )
                        .forEach(function (container) {

                            const hiddenInput =
                                container.querySelector(
                                    'input[type="hidden"]'
                                );

                            const button =
                                container.querySelector(
                                    '.sita-select-search-button'
                                );

                            // Se busca el <span> sin usar la clase: al elegir
                            // una opción esa clase se retira y el reset
                            // no encontraba el texto para restablecerlo.
                            const placeholder =
                                button.querySelector('span');

                            const options =
                                container.querySelectorAll(
                                    '.sita-select-search-option'
                                );

                            if (hiddenInput) {
                                hiddenInput.value = '';
                            }

                            if (placeholder) {

                                placeholder.textContent =
                                    container.getAttribute(
                                        'data-placeholder'
                                    ) || '';

                                placeholder.classList.add(
                                    'sita-select-search-placeholder'
                                );
                            }

                            options.forEach(
                                function (option) {

                                    option.classList.remove(
                                        'selected'
                                    );

                                    option.style.display =
                                        'block';

                                }
                            );

                        });

                }, 0);

            }
        );

    }


    /*
     * Validación del formulario de asignaciones
     */

    if (form) {

        const cajaError =
            document.getElementById('sitaAsignacionError');

        const detalleOtro =
            document.getElementById('sitaOtroProfesional');

        function valorCampo(id) {

            const campo = document.getElementById(id);

            return campo ? campo.value.trim() : '';
        }

        function mostrarError(mensaje) {

            cajaError.textContent = mensaje;

            cajaError.classList.add('visible');

            cajaError.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        function ocultarError() {

            cajaError.textContent = '';

            cajaError.classList.remove('visible');
        }

        form.addEventListener('submit', function (evento) {

            const lider = valorCampo('id_lider_ficha');
            const coordinador = valorCampo('id_coordinador');
            const otro = valorCampo('id_profesional');
            const tipoOtro = valorCampo('tipo_asignacion');
            const inicio = valorCampo('fecha_inicio');
            const fin = valorCampo('fecha_fin');

            let mensaje = '';
            let abrirOtro = false;

            if (valorCampo('id_ficha') === '') {

                mensaje = 'Debe seleccionar una ficha.';

            } else if (!lider && !coordinador && !otro) {

                mensaje =
                    'Seleccione al menos un responsable: líder de ficha, ' +
                    'coordinador académico u otro profesional.';

            } else if (otro && !tipoOtro) {

                mensaje =
                    'Indique el tipo de asignación del profesional adicional.';

                abrirOtro = true;

            } else if (!otro && tipoOtro) {

                mensaje =
                    'Seleccione el profesional adicional o quite el tipo de asignación.';

                abrirOtro = true;

            } else if (inicio === '') {

                mensaje = 'Debe indicar la fecha de inicio.';

            } else if (fin !== '' && fin < inicio) {

                mensaje =
                    'La fecha final no puede ser anterior a la fecha inicial.';
            }

            if (mensaje !== '') {

                evento.preventDefault();

                if (abrirOtro && detalleOtro) {
                    detalleOtro.open = true;
                }

                mostrarError(mensaje);

                return;
            }

            ocultarError();
        });

        form.addEventListener('reset', function () {

            ocultarError();

            if (detalleOtro) {
                detalleOtro.open = false;
            }
        });
    }


    /*
     * Botón "Quitar": pide confirmación.
     * Aceptar = se quita; Cancelar = se deja como está.
     */
    document
        .querySelectorAll('.sita-form-quitar')
        .forEach(function (formulario) {

            formulario.addEventListener('submit', function (evento) {

                const mensaje =
                    formulario.getAttribute('data-confirm') ||
                    '¿Quitar esta asignación?';

                if (!window.confirm(mensaje)) {
                    evento.preventDefault();
                }
            });
        });


    /*
     * Botón "Asignar": lleva al formulario con la ficha ya elegida
     * y abre el selector del líder o del coordinador.
     */
    document
        .querySelectorAll('.sita-btn-asignar')
        .forEach(function (boton) {

            boton.addEventListener('click', function () {

                const idFicha = boton.getAttribute('data-ficha-id');
                const campo = boton.getAttribute('data-campo');

                const panel =
                    document.getElementById('sitaFormAsignacion');

                const opcionFicha = document.querySelector(
                    '[data-select-search="ficha"] ' +
                    '.sita-select-search-option[data-value="' +
                    idFicha + '"]'
                );

                if (panel) {
                    panel.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }

                if (opcionFicha) {
                    opcionFicha.click();
                }

                setTimeout(function () {

                    const selector = document.querySelector(
                        '[data-select-search="' + campo + '"] ' +
                        '.sita-select-search-button'
                    );

                    if (selector) {
                        selector.click();
                    }

                }, 400);
            });
        });

});
</script>

<?php

require_once ROOT_PATH . '/views/layout/footer.php';

?>