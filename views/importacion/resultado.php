<?php
declare(strict_types=1);

$resultado = $resultado ?? [];

/*
|--------------------------------------------------------------------------
| DATOS PRINCIPALES
|--------------------------------------------------------------------------
*/

$datosFicha =
    $resultado['datos_ficha']
    ?? [];

$diagnosticoBD =
    $resultado['diagnostico_bd']
    ?? [];

$diagnosticoResultados =
    is_array($diagnosticoBD)
        ? ($diagnosticoBD['resultados'] ?? [])
        : [];

/*
|--------------------------------------------------------------------------
| JUICIOS
|--------------------------------------------------------------------------
*/

$juicios =
    is_array($resultado['juicios'] ?? null)
        ? $resultado['juicios']
        : [
            'APROBADO' => 0,
            'POR EVALUAR' => 0,
        ];

/*
|--------------------------------------------------------------------------
| MUESTRAS
|--------------------------------------------------------------------------
*/

$muestraAprendices =
    $resultado['muestra_aprendices']
    ?? [];

$muestraCompetencias =
    $resultado['muestra_competencias']
    ?? [];

$muestraRap =
    $resultado['muestra_rap']
    ?? [];

$muestraFuncionarios =
    $resultado['muestra_funcionarios']
    ?? [];

/*
|--------------------------------------------------------------------------
| IMPORTACIÓN
|--------------------------------------------------------------------------
*/

$importacion =
    $resultado['importacion']
    ?? [];

$importacionRealizada =
    !empty($importacion['realizada']);

$importacionError =
    $resultado['error']
    ?? ($importacion['error'] ?? '');

/*
|--------------------------------------------------------------------------
| DATOS DEL ARCHIVO
|--------------------------------------------------------------------------
*/

$nombreHoja =
    $resultado['hoja_utilizada']
    ?? '';

$filasTotales =
    $resultado['filas_totales']
    ?? 0;

$columnasTotales =
    $resultado['columnas_totales']
    ?? 0;

$ultimaColumna =
    $resultado['ultima_columna']
    ?? '';

/*
|--------------------------------------------------------------------------
| OTROS JUICIOS
|--------------------------------------------------------------------------
*/

$otrosJuicios =
    (int) (
        $resultado['otros_juicios']
        ?? 0
    );

/*
|--------------------------------------------------------------------------
| DIAGNÓSTICO CONTRA BASE DE DATOS
|--------------------------------------------------------------------------
*/

$diagnosticoPrograma =
    is_array($diagnosticoBD)
        ? ($diagnosticoBD['programa'] ?? [])
        : [];

$diagnosticoFicha =
    is_array($diagnosticoBD)
        ? ($diagnosticoBD['ficha'] ?? [])
        : [];

$diagnosticoAprendices =
    is_array($diagnosticoBD)
        ? ($diagnosticoBD['aprendices'] ?? [])
        : [];

$diagnosticoCompetencias =
    is_array($diagnosticoBD)
        ? ($diagnosticoBD['competencias'] ?? [])
        : [];

$diagnosticoRap =
    is_array($diagnosticoBD)
        ? ($diagnosticoBD['rap'] ?? [])
        : [];

$diagnosticoFuncionarios =
    is_array($diagnosticoBD)
        ? ($diagnosticoBD['funcionarios'] ?? [])
        : [];

$diagnosticoMatriculas =
    is_array($diagnosticoBD)
        ? ($diagnosticoBD['matriculas'] ?? [])
        : [];

$diagnosticoErrores =
    is_array($diagnosticoBD)
        ? ($diagnosticoBD['errores'] ?? [])
        : [];

$diagnosticoMuestras =
    is_array($diagnosticoBD)
        ? ($diagnosticoBD['muestras'] ?? [])
        : [];

$resumenResultadosBD =
    is_array($diagnosticoBD)
        ? ($diagnosticoBD['resumen_resultados_bd'] ?? [])
        : [];

$estadoDiagnostico =
    is_array($diagnosticoBD)
        ? ($diagnosticoBD['estado'] ?? '')
        : '';

?>

<?php
require_once ROOT_PATH . '/views/layout/header.php';
require_once ROOT_PATH . '/views/layout/navbar.php';
?>

<style>


    * {
        box-sizing: border-box;
    }

    /*
     * El layout principal ya administra el body y el navbar.
     * No agregar padding global, porque crea un espacio sobre el contenido.
     */
    body {
        margin: 0;
        padding: 0;
        background: #f4f6f9;
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

    .contenedor {
        width: 100%;
        max-width: 1250px;
        margin: 0 auto;
    }

    .tarjeta {
        background: #ffffff;
        border-radius: 12px;
        padding: 22px;
        margin-bottom: 18px;
        box-shadow:
            0 4px 18px rgba(0, 0, 0, 0.07);
    }

    h1 {
        margin-top: 0;
    }

    h2 {
        margin-top: 0;
        margin-bottom: 20px;
    }

    .subtitulo {
        color: #6c757d;
    }

    .grid {
        display: grid;
        grid-template-columns:
            repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
    }

    .dato {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        background: #f8f9fa;
    }

    .dato strong {
        display: block;
        font-size: 20px;
        margin-top: 6px;
    }

    .etiqueta {
        color: #6c757d;
        font-size: 11px;
    }

    .correcto {
        color: #146c43;
    }

    .pendiente {
        color: #856404;
    }

    .error {
        color: #842029;
    }

    .info {
        color: #0c5460;
    }

    .tabla-contenedor {
        width: 100%;
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }

    th,
    td {
        border: 1px solid #dee2e6;
        padding: 8px 9px;
        text-align: left;
        vertical-align: top;
    }

    th {
        background: #f1f3f5;
    }

    .tabla-cabecera td:first-child {
        width: 35%;
        font-weight: 600;
    }

    ul {
        margin-top: 0;
        margin-bottom: 0;
        padding-left: 22px;
    }

    li {
        margin-bottom: 6px;
    }

    .botones {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .boton {
        display: inline-block;
        text-decoration: none;
        padding: 11px 18px;
        border-radius: 7px;
        background: #198754;
        color: #ffffff;
        border: none;
        cursor: pointer;
        font-size: 12px;
        font-family:
            Inter,
            -apple-system,
            BlinkMacSystemFont,
            "Segoe UI",
            Roboto,
            Arial,
            sans-serif;
    }

    .boton:hover {
        opacity: 0.92;
    }

    .boton-secundario {
        background: #6c757d;
    }

    .boton-importar {
        background: #0d6efd;
        font-weight: 700;
        padding: 13px 22px;
    }

    .advertencia {
        padding: 15px;
        border-radius: 8px;
        background: #fff3cd;
        color: #664d03;
        border: 1px solid #ffecb5;
        line-height: 1.5;
    }

    .exito {
        padding: 15px;
        border-radius: 8px;
        background: #d1e7dd;
        color: #0f5132;
        border: 1px solid #badbcc;
        margin-bottom: 20px;
        line-height: 1.5;
    }

    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 5px;
        background: #e9ecef;
        font-size: 12px;
    }

    .lista-dato {
        word-break: break-word;
    }

    @media (max-width: 700px) {

        .contenedor {
            padding-left: 15px;
            padding-right: 15px;
        }

        .tarjeta {
            padding: 18px;
        }

    }

    .diagnostico-ok {
        padding: 15px;
        border-radius: 8px;
        background: #d1e7dd;
        color: #0f5132;
        border: 1px solid #badbcc;
        line-height: 1.5;
        margin-bottom: 20px;
    }

    .diagnostico-alerta {
        padding: 15px;
        border-radius: 8px;
        background: #fff3cd;
        color: #664d03;
        border: 1px solid #ffecb5;
        line-height: 1.5;
        margin-bottom: 20px;
    }

    .diagnostico-error {
        padding: 15px;
        border-radius: 8px;
        background: #f8d7da;
        color: #842029;
        border: 1px solid #f5c2c7;
        line-height: 1.5;
        margin-bottom: 20px;
    }

    .estado-si {
        color: #146c43;
        font-weight: 700;
    }

    .estado-no {
        color: #842029;
        font-weight: 700;
    }

    .diagnostico-tabla td:first-child {
        width: 42%;
        font-weight: 600;
    }

    .numero-diagnostico {
        font-size: 20px;
        font-weight: 700;
        display: block;
        margin-top: 5px;
    }

    .lista-diagnostico {
        margin: 0;
        padding-left: 20px;
    }

    .lista-diagnostico li {
        margin-bottom: 5px;
    }

    .importacion-resumen {
        margin-top: 20px;
    }

    .importacion-resumen .dato strong {
        font-size: 22px;
    }


</style>



<div class="contenedor">

<!-- ENCABEZADO -->

<div class="tarjeta">

    <h1>
        Lectura del reporte SENA
    </h1>

    <p class="subtitulo">

        Archivo:

        <strong>
            <?= e(
                (string) (
                    $resultado['archivo']
                    ?? ''
                )
            ) ?>
        </strong>

    </p>

    <div class="botones">

        <a
            class="boton"
            href="<?= e(url('/importar')) ?>"
        >
            Leer otro archivo
        </a>

        <a
            class="boton boton-secundario"
            href="<?= e(url('/dashboard')) ?>"
        >
            Volver al panel
        </a>

    </div>

</div>

<!-- MENSAJE DE ESTADO -->

<?php if ($importacionRealizada): ?>

<div class="exito">

    <strong>
        Importación realizada correctamente.
    </strong>

    Los datos del reporte fueron procesados y guardados
    en la base de datos dentro de una transacción.

</div>

<?php elseif ($importacionError): ?>

<div class="diagnostico-error">

    <strong>
        La importación no pudo completarse.
    </strong>

    <br>

    <?= e((string) $importacionError) ?>

</div>

<?php else: ?>

<div class="exito">

    <strong>
        Lectura realizada correctamente.
    </strong>

    El archivo fue leído y el diagnóstico fue generado.

</div>

<?php endif; ?>

<!-- INFORMACIÓN DEL ARCHIVO -->

<div class="tarjeta">

    <h2>
        Información del archivo
    </h2>

    <div class="grid">

        <div class="dato">

            <span class="etiqueta">
                Número de hojas
            </span>

            <strong>
                <?= e(
                    (string) (
                        $resultado['hojas']
                        ?? 0
                    )
                ) ?>
            </strong>

        </div>

        <div class="dato">

            <span class="etiqueta">
                Hoja utilizada
            </span>

            <strong style="font-size: 18px;">

                <?= e(
                    (string) $nombreHoja
                ) ?>

            </strong>

        </div>

        <div class="dato">

            <span class="etiqueta">
                Total de filas
            </span>

            <strong>
                <?= e(
                    (string) $filasTotales
                ) ?>
            </strong>

        </div>

        <div class="dato">

            <span class="etiqueta">
                Total de columnas
            </span>

            <strong>
                <?= e(
                    (string) $columnasTotales
                ) ?>
            </strong>

        </div>

        <div class="dato">

            <span class="etiqueta">
                Última columna
            </span>

            <strong>
                <?= e(
                    (string) $ultimaColumna
                ) ?>
            </strong>

        </div>

    </div>

</div>

<!-- INFORMACIÓN SENA -->

<div class="tarjeta">

    <h2>
        Información SENA detectada
    </h2>

    <div class="tabla-contenedor">

        <table class="tabla-cabecera">

            <tbody>

                <tr>
                    <td>
                        Fecha del reporte
                    </td>
                    <td>
                        <?= e(
                            (string) (
                                $datosFicha['fecha_reporte']
                                ?? ''
                            )
                        ) ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        Ficha de Caracterización
                    </td>
                    <td>
                        <?= e(
                            (string) (
                                $datosFicha['ficha']
                                ?? ''
                            )
                        ) ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        Código del programa
                    </td>
                    <td>
                        <?= e(
                            (string) (
                                $datosFicha['codigo_programa']
                                ?? ''
                            )
                        ) ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        Versión
                    </td>
                    <td>
                        <?= e(
                            (string) (
                                $datosFicha['version_programa']
                                ?? ''
                            )
                        ) ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        Denominación
                    </td>
                    <td>
                        <?= e(
                            (string) (
                                $datosFicha['denominacion']
                                ?? ''
                            )
                        ) ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        Estado de la ficha
                    </td>
                    <td>
                        <?= e(
                            (string) (
                                $datosFicha['estado_ficha']
                                ?? ''
                            )
                        ) ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        Fecha de inicio
                    </td>
                    <td>
                        <?= e(
                            (string) (
                                $datosFicha['fecha_inicio']
                                ?? ''
                            )
                        ) ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        Fecha de fin
                    </td>
                    <td>
                        <?= e(
                            (string) (
                                $datosFicha['fecha_fin']
                                ?? ''
                            )
                        ) ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        Modalidad
                    </td>
                    <td>
                        <?= e(
                            (string) (
                                $datosFicha['modalidad']
                                ?? ''
                            )
                        ) ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        Regional
                    </td>
                    <td>
                        <?= e(
                            (string) (
                                $datosFicha['regional']
                                ?? ''
                            )
                        ) ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        Centro
                    </td>
                    <td>
                        <?= e(
                            (string) (
                                $datosFicha['centro']
                                ?? ''
                            )
                        ) ?>
                    </td>
                </tr>

            </tbody>

        </table>

    </div>

</div>

<!-- RESUMEN -->

<div class="tarjeta">

    <h2>
        Resumen encontrado
    </h2>

    <div class="grid">

        <div class="dato">

            <span class="etiqueta">
                Filas de detalle
            </span>

            <strong>
                <?= e(
                    (string) (
                        $resultado['filas_detalle']
                        ?? 0
                    )
                ) ?>
            </strong>

        </div>

        <div class="dato">

            <span class="etiqueta">
                Aprendices
            </span>

            <strong>
                <?= e(
                    (string) (
                        $resultado['aprendices']
                        ?? 0
                    )
                ) ?>
            </strong>

        </div>

        <div class="dato">

            <span class="etiqueta">
                Competencias
            </span>

            <strong>
                <?= e(
                    (string) (
                        $resultado['competencias']
                        ?? 0
                    )
                ) ?>
            </strong>

        </div>

        <div class="dato">

            <span class="etiqueta">
                RAP
            </span>

            <strong>
                <?= e(
                    (string) (
                        $resultado['rap']
                        ?? 0
                    )
                ) ?>
            </strong>

        </div>

        <div class="dato">

            <span class="etiqueta">
                Funcionarios
            </span>

            <strong>
                <?= e(
                    (string) (
                        $resultado['funcionarios']
                        ?? 0
                    )
                ) ?>
            </strong>

        </div>

        <div class="dato">

            <span class="etiqueta">
                APROBADO
            </span>

            <strong class="correcto">
                <?= e(
                    (string) (
                        $juicios['APROBADO']
                        ?? 0
                    )
                ) ?>
            </strong>

        </div>

        <div class="dato">

            <span class="etiqueta">
                POR EVALUAR
            </span>

            <strong class="pendiente">
                <?= e(
                    (string) (
                        $juicios['POR EVALUAR']
                        ?? 0
                    )
                ) ?>
            </strong>

        </div>

        <div class="dato">

            <span class="etiqueta">
                Otros juicios
            </span>

            <strong class="error">
                <?= e(
                    (string) $otrosJuicios
                ) ?>
            </strong>

        </div>

    </div>

</div>

<!-- OBSERVACIONES DE LA IMPORTACIÓN -->

<?php if (!empty($importacion['errores']) || !empty($importacion['resultados']['omitidos'])): ?>

<div class="tarjeta">
    <h2>Observaciones de la importación</h2>

    <p class="subtitulo">
        Resultados omitidos por error: <strong><?= (int) ($importacion['resultados']['omitidos'] ?? 0) ?></strong>
    </p>

    <?php if (!empty($importacion['errores'])): ?>
        <ul>
            <?php foreach ($importacion['errores'] as $err): ?>
                <li>Fila <?= e((string) ($err['fila_excel'] ?? '-')) ?>: <?= e((string) ($err['mensaje'] ?? '')) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php endif; ?>

<!-- DIAGNÓSTICO CONTRA BASE DE DATOS -->

<?php if ($diagnosticoBD !== null): ?>

<div class="tarjeta">

    <h2>
        Diagnóstico contra la base de datos
    </h2>

    <p class="subtitulo">
        Esta sección compara la información del archivo Excel
        con los registros existentes en la base de datos.
    </p>

    <?php if ($estadoDiagnostico === 'ok'): ?>

        <div class="diagnostico-ok">

            <strong>
                Diagnóstico realizado correctamente.
            </strong>

            Esta comparación se hizo después de la importación.

        </div>

    <?php elseif ($estadoDiagnostico): ?>

        <div class="diagnostico-alerta">

            <strong>
                Estado del diagnóstico:
            </strong>

            <?= e((string) $estadoDiagnostico) ?>

            <br>

            Esta comparación es solo de consulta.

        </div>

    <?php endif; ?>

</div>

<!-- PROGRAMA Y FICHA -->

<div class="tarjeta">

    <h2>
        Programa y ficha
    </h2>

    <div class="tabla-contenedor">

        <table class="diagnostico-tabla">

            <thead>

                <tr>
                    <th>Elemento</th>
                    <th>Información Excel</th>
                    <th>Estado en BD</th>
                    <th>Información BD</th>
                </tr>

            </thead>

            <tbody>

                <tr>

                    <td>
                        Programa
                    </td>

                    <td>

                        <?= e(
                            (string) (
                                $diagnosticoPrograma['datos_excel']['codigo']
                                ?? $datosFicha['codigo_programa']
                                ?? ''
                            )
                        ) ?>

                        <?php if (
                            !empty($diagnosticoPrograma['datos_excel']['nombre'])
                        ): ?>

                            <br>

                            <?= e(
                                (string)
                                $diagnosticoPrograma['datos_excel']['nombre']
                            ) ?>

                        <?php endif; ?>

                    </td>

                    <td>

                        <?php if (
                            !empty($diagnosticoPrograma['existe'])
                        ): ?>

                            <span class="estado-si">
                                EXISTE
                            </span>

                        <?php else: ?>

                            <span class="estado-no">
                                NO EXISTE
                            </span>

                        <?php endif; ?>

                    </td>

                    <td>

                        <?php
                        if (
                            !empty($diagnosticoPrograma['datos_bd'])
                            && is_array($diagnosticoPrograma['datos_bd'])
                        ):
                        ?>

                            <?= e(
                                (string) (
                                    $diagnosticoPrograma['datos_bd']['codigo']
                                    ?? ''
                                )
                            ) ?>

                            <?php if (
                                !empty(
                                    $diagnosticoPrograma['datos_bd']['nombre']
                                )
                            ): ?>

                                <br>

                                <?= e(
                                    (string)
                                    $diagnosticoPrograma['datos_bd']['nombre']
                                ) ?>

                            <?php endif; ?>

                        <?php else: ?>

                            No encontrado.

                        <?php endif; ?>

                    </td>

                </tr>

                <tr>

                    <td>
                        Ficha
                    </td>

                    <td>

                        <?= e(
                            (string) (
                                $diagnosticoFicha['datos_excel']['numero_ficha']
                                ?? $datosFicha['ficha']
                                ?? ''
                            )
                        ) ?>

                    </td>

                    <td>

                        <?php if (
                            !empty($diagnosticoFicha['existe'])
                        ): ?>

                            <span class="estado-si">
                                EXISTE
                            </span>

                        <?php else: ?>

                            <span class="estado-no">
                                NO EXISTE
                            </span>

                        <?php endif; ?>

                    </td>

                    <td>

                        <?php
                        if (
                            !empty($diagnosticoFicha['datos_bd'])
                            && is_array($diagnosticoFicha['datos_bd'])
                        ):
                        ?>

                            <?= e(
                                (string) (
                                    $diagnosticoFicha['datos_bd']['numero_ficha']
                                    ?? ''
                                )
                            ) ?>

                            <?php if (
                                !empty(
                                    $diagnosticoFicha['datos_bd']['id']
                                )
                            ): ?>

                                <br>

                                ID:
                                <?= e(
                                    (string)
                                    $diagnosticoFicha['datos_bd']['id']
                                ) ?>

                            <?php endif; ?>

                        <?php else: ?>

                            No encontrada.

                        <?php endif; ?>

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

</div>

<!-- RESUMEN DE ENTIDADES -->

<div class="tarjeta">

    <h2>
        Entidades detectadas y existentes
    </h2>

    <div class="grid">

        <div class="dato">

            <span class="etiqueta">
                Aprendices en Excel
            </span>

            <span class="numero-diagnostico">
                <?= e(
                    (string) (
                        $diagnosticoAprendices['total_excel']
                        ?? $resultado['aprendices']
                        ?? 0
                    )
                ) ?>
            </span>

            Ya existen:
            <strong>
                <?= e(
                    (string) (
                        $diagnosticoAprendices['ya_existen']
                        ?? 0
                    )
                ) ?>
            </strong>

            <br>

            Faltan:
            <strong class="estado-no">
                <?= e(
                    (string) (
                        $diagnosticoAprendices['faltan']
                        ?? 0
                    )
                ) ?>
            </strong>

        </div>

        <div class="dato">

            <span class="etiqueta">
                Competencias en Excel
            </span>

            <span class="numero-diagnostico">
                <?= e(
                    (string) (
                        $diagnosticoCompetencias['total_excel']
                        ?? $resultado['competencias']
                        ?? 0
                    )
                ) ?>
            </span>

            Ya existen:
            <strong>
                <?= e(
                    (string) (
                        $diagnosticoCompetencias['ya_existen']
                        ?? 0
                    )
                ) ?>
            </strong>

            <br>

            Faltan:
            <strong class="estado-no">
                <?= e(
                    (string) (
                        $diagnosticoCompetencias['faltan']
                        ?? 0
                    )
                ) ?>
            </strong>

        </div>

        <div class="dato">

            <span class="etiqueta">
                RAP en Excel
            </span>

            <span class="numero-diagnostico">
                <?= e(
                    (string) (
                        $diagnosticoRap['total_excel']
                        ?? $resultado['rap']
                        ?? 0
                    )
                ) ?>
            </span>

            Ya existen:
            <strong>
                <?= e(
                    (string) (
                        $diagnosticoRap['ya_existen']
                        ?? 0
                    )
                ) ?>
            </strong>

            <br>

            Faltan:
            <strong class="estado-no">
                <?= e(
                    (string) (
                        $diagnosticoRap['faltan']
                        ?? 0
                    )
                ) ?>
            </strong>

        </div>

        <div class="dato">

            <span class="etiqueta">
                Funcionarios en Excel
            </span>

            <span class="numero-diagnostico">
                <?= e(
                    (string) (
                        $diagnosticoFuncionarios['total_excel']
                        ?? $resultado['funcionarios']
                        ?? 0
                    )
                ) ?>
            </span>

            Ya existen:
            <strong>
                <?= e(
                    (string) (
                        $diagnosticoFuncionarios['ya_existen']
                        ?? 0
                    )
                ) ?>
            </strong>

            <br>

            Faltan:
            <strong class="estado-no">
                <?= e(
                    (string) (
                        $diagnosticoFuncionarios['faltan']
                        ?? 0
                    )
                ) ?>
            </strong>

        </div>

        <div class="dato">

            <span class="etiqueta">
                Matrículas
            </span>

            <span class="numero-diagnostico">
                <?= e(
                    (string) (
                        $diagnosticoMatriculas['total_aprendices']
                        ?? $resultado['aprendices']
                        ?? 0
                    )
                ) ?>
            </span>

            Ya existen:
            <strong>
                <?= e(
                    (string) (
                        $diagnosticoMatriculas['ya_existen']
                        ?? 0
                    )
                ) ?>
            </strong>

            <br>

            Faltan:
            <strong class="estado-no">
                <?= e(
                    (string) (
                        $diagnosticoMatriculas['faltan']
                        ?? 0
                    )
                ) ?>
            </strong>

        </div>

    </div>

</div>

<!-- RESULTADOS YA EXISTENTES EN BD -->

<?php if (
    !empty($resumenResultadosBD)
    && is_array($resumenResultadosBD)
): ?>

<div class="tarjeta">

    <h2>
        Resultados existentes en la base de datos
    </h2>

    <p class="subtitulo">
        Esta información permite verificar que los resultados
        históricos se mantendrán separados por su fecha de evaluación.
    </p>

    <div class="tabla-contenedor">

        <table>

            <thead>

                <tr>
                    <th>Año de evaluación</th>
                    <th>Cantidad</th>
                </tr>

            </thead>

            <tbody>

            <?php foreach (
                $resumenResultadosBD
                as $anio => $cantidad
            ): ?>

                <?php
                // El modelo devuelve filas [vigencia => ..., total => ...]
                if (is_array($cantidad)) {
                    $anio = $cantidad['vigencia'] ?? $anio;
                    $cantidad = $cantidad['total'] ?? 0;
                }
                ?>

                <tr>

                    <td>
                        <?= e((string) $anio) ?>
                    </td>

                    <td>
                        <?= e((string) $cantidad) ?>
                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>

<?php endif; ?>

<!-- ERRORES DEL DIAGNÓSTICO -->

<?php if (
    !empty($diagnosticoErrores)
    && is_array($diagnosticoErrores)
): ?>

<div class="tarjeta">

    <h2>
        Observaciones y elementos que requieren atención
    </h2>

    <div class="diagnostico-alerta">

        <ul class="lista-diagnostico">

        <?php foreach ($diagnosticoErrores as $errorDiagnostico): ?>

            <li>

                <?php if (is_array($errorDiagnostico)): ?>

                    <?= e(
                        (string) (
                            $errorDiagnostico['mensaje']
                            ?? $errorDiagnostico['error']
                            ?? json_encode(
                                $errorDiagnostico,
                                JSON_UNESCAPED_UNICODE
                            )
                        )
                    ) ?>

                <?php else: ?>

                    <?= e((string) $errorDiagnostico) ?>

                <?php endif; ?>

            </li>

        <?php endforeach; ?>

        </ul>

    </div>

</div>

<?php endif; ?>

<!-- MUESTRAS DEL DIAGNÓSTICO -->

<?php if (
    !empty($diagnosticoMuestras)
    && is_array($diagnosticoMuestras)
): ?>

<div class="tarjeta">

    <h2>
        Muestras del diagnóstico
    </h2>

    <p class="subtitulo">
        Se muestran solamente muestras de referencia para revisar
        los resultados de la comparación. No representan todos los registros.
    </p>

    <?php foreach (
        $diagnosticoMuestras
        as $nombreMuestra => $datosMuestra
    ): ?>

        <h3>
            <?= e(
                ucwords(
                    str_replace(
                        '_',
                        ' ',
                        (string) $nombreMuestra
                    )
                )
            ) ?>
        </h3>

        <?php if (
            is_array($datosMuestra)
            && $datosMuestra
        ): ?>

            <div class="tabla-contenedor">

                <table>

                    <thead>

                        <tr>

                            <?php
                            $primero = reset($datosMuestra);

                            if (is_array($primero)):
                                foreach (array_keys($primero) as $columna):
                            ?>

                                    <th>
                                        <?= e(
                                            ucwords(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    (string) $columna
                                                )
                                            )
                                        ) ?>
                                    </th>

                            <?php
                                endforeach;
                            endif;
                            ?>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach (
                        $datosMuestra
                        as $filaMuestra
                    ): ?>

                        <?php if (is_array($filaMuestra)): ?>

                            <tr>

                                <?php foreach (
                                    $filaMuestra
                                    as $valorMuestra
                                ): ?>

                                    <td>

                                        <?php
                                        if (
                                            is_array($valorMuestra)
                                            || is_object($valorMuestra)
                                        ) {
                                            echo e(
                                                json_encode(
                                                    $valorMuestra,
                                                    JSON_UNESCAPED_UNICODE
                                                )
                                            );
                                        } else {
                                            echo e(
                                                (string) $valorMuestra
                                            );
                                        }
                                        ?>

                                    </td>

                                <?php endforeach; ?>

                            </tr>

                        <?php else: ?>

                            <tr>

                                <td>
                                    <?= e(
                                        (string) $filaMuestra
                                    ) ?>
                                </td>

                            </tr>

                        <?php endif; ?>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <p>
                No hay datos disponibles para esta muestra.
            </p>

        <?php endif; ?>

    <?php endforeach; ?>

</div>

<?php endif; ?>

<?php endif; ?>

<!-- APRENDICES -->

<div class="tarjeta">

    <h2>
        Aprendices detectados
    </h2>

    <p class="subtitulo">

        Se muestran los primeros registros encontrados
        para comprobar la lectura del documento.

    </p>

    <?php if ($muestraAprendices): ?>

        <div class="tabla-contenedor">

            <table>

                <thead>

                <tr>

                    <th>
                        Tipo documento
                    </th>

                    <th>
                        Número de documento
                    </th>

                    <th>
                        Nombres
                    </th>

                    <th>
                        Apellidos
                    </th>

                </tr>

                </thead>

                <tbody>

                <?php foreach (
                    $muestraAprendices
                    as $aprendiz
                ): ?>

                    <tr>

                        <td>
                            <?= e(
                                (string) (
                                    $aprendiz['tipo_documento']
                                    ?? ''
                                )
                            ) ?>
                        </td>

                        <td>
                            <?= e(
                                (string) (
                                    $aprendiz['numero_documento']
                                    ?? ''
                                )
                            ) ?>
                        </td>

                        <td>
                            <?= e(
                                (string) (
                                    $aprendiz['nombre']
                                    ?? ''
                                )
                            ) ?>
                        </td>

                        <td>
                            <?= e(
                                (string) (
                                    $aprendiz['apellidos']
                                    ?? ''
                                )
                            ) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php else: ?>

        <p>
            No se encontraron aprendices.
        </p>

    <?php endif; ?>

</div>

<!-- COMPETENCIAS -->

<div class="tarjeta">

    <h2>
        Competencias detectadas
    </h2>

    <?php if ($muestraCompetencias): ?>

        <ul>

            <?php foreach (
                $muestraCompetencias
                as $competencia
            ): ?>

                <li class="lista-dato">

                    <?= e(
                        (string) $competencia
                    ) ?>

                </li>

            <?php endforeach; ?>

        </ul>

        <?php if (
            ($resultado['competencias'] ?? 0) > 10
        ): ?>

            <p class="subtitulo">

                Se muestran solamente las primeras
                10 competencias.

                Total detectado:

                <strong>
                    <?= e(
                        (string) (
                            $resultado['competencias']
                            ?? 0
                        )
                    ) ?>
                </strong>

            </p>

        <?php endif; ?>

    <?php else: ?>

        <p>
            No se encontraron competencias.
        </p>

    <?php endif; ?>

</div>

<!-- RAP -->

<div class="tarjeta">

    <h2>
        Resultados de aprendizaje (RAP)
    </h2>

    <?php if ($muestraRap): ?>

        <ul>

            <?php foreach (
                $muestraRap
                as $resultadoAprendizaje
            ): ?>

                <li class="lista-dato">

                    <?= e(
                        (string) $resultadoAprendizaje
                    ) ?>

                </li>

            <?php endforeach; ?>

        </ul>

        <?php if (
            ($resultado['rap'] ?? 0) > 10
        ): ?>

            <p class="subtitulo">

                Se muestran solamente los primeros
                10 RAP.

                Total detectado:

                <strong>
                    <?= e(
                        (string) (
                            $resultado['rap']
                            ?? 0
                        )
                    ) ?>
                </strong>

            </p>

        <?php endif; ?>

    <?php else: ?>

        <p>
            No se encontraron RAP.
        </p>

    <?php endif; ?>

</div>

<!-- FUNCIONARIOS -->

<div class="tarjeta">

    <h2>
        Funcionarios detectados
    </h2>

    <?php if ($muestraFuncionarios): ?>

        <ul>

            <?php foreach (
                $muestraFuncionarios
                as $funcionario
            ): ?>

                <li class="lista-dato">

                    <?= e(
                        (string) $funcionario
                    ) ?>

                </li>

            <?php endforeach; ?>

        </ul>

        <?php if (
            ($resultado['funcionarios'] ?? 0) > 10
        ): ?>

            <p class="subtitulo">

                Se muestran solamente los primeros
                10 funcionarios.

                Total detectado:

                <strong>
                    <?= e(
                        (string) (
                            $resultado['funcionarios']
                            ?? 0
                        )
                    ) ?>
                </strong>

            </p>

        <?php endif; ?>

    <?php else: ?>

        <p>
            No se encontraron funcionarios
            registrados en las filas.

        </p>

    <?php endif; ?>

</div>

<!-- RESUMEN DE IMPORTACIÓN -->

<?php if ($importacionRealizada): ?>

<div class="tarjeta">

    <h2>
        Resumen de importación realizada
    </h2>

    <div class="importacion-resumen">

        <div class="grid">

            <div class="dato">

                <span class="etiqueta">
                    Programa creado
                </span>

                <strong class="correcto">
                    <?= e(
                        (string) (
                            $importacion['programa_creado']
                            ?? 0
                        )
                    ) ?>
                </strong>

            </div>

            <div class="dato">

                <span class="etiqueta">
                    Programa existente
                </span>

                <strong>
                    <?= e(
                        (string) (
                            $importacion['programa_existente']
                            ?? 0
                        )
                    ) ?>
                </strong>

            </div>

            <div class="dato">

                <span class="etiqueta">
                    Ficha creada
                </span>

                <strong class="correcto">
                    <?= e(
                        (string) (
                            $importacion['ficha_creada']
                            ?? 0
                        )
                    ) ?>
                </strong>

            </div>

            <div class="dato">

                <span class="etiqueta">
                    Ficha existente
                </span>

                <strong>
                    <?= e(
                        (string) (
                            $importacion['ficha_existente']
                            ?? 0
                        )
                    ) ?>
                </strong>

            </div>

            <div class="dato">

                <span class="etiqueta">
                    Personas creadas
                </span>

                <strong class="correcto">
                    <?= e(
                        (string) (
                            $importacion['personas_creadas']
                            ?? 0
                        )
                    ) ?>
                </strong>

            </div>

            <div class="dato">

                <span class="etiqueta">
                    Aprendices creados
                </span>

                <strong class="correcto">
                    <?= e(
                        (string) (
                            $importacion['aprendices_creados']
                            ?? 0
                        )
                    ) ?>
                </strong>

            </div>

            <div class="dato">

                <span class="etiqueta">
                    Competencias creadas
                </span>

                <strong class="correcto">
                    <?= e(
                        (string) (
                            $importacion['competencias_creadas']
                            ?? 0
                        )
                    ) ?>
                </strong>

            </div>

            <div class="dato">

                <span class="etiqueta">
                    RAP creados
                </span>

                <strong class="correcto">
                    <?= e(
                        (string) (
                            $importacion['rap_creados']
                            ?? 0
                        )
                    ) ?>
                </strong>

            </div>

            <div class="dato">

                <span class="etiqueta">
                    Profesionales creados
                </span>

                <strong class="correcto">
                    <?= e(
                        (string) (
                            $importacion['profesionales_creados']
                            ?? 0
                        )
                    ) ?>
                </strong>

            </div>

            <div class="dato">

                <span class="etiqueta">
                    Matrículas creadas
                </span>

                <strong class="correcto">
                    <?= e(
                        (string) (
                            $importacion['matriculas_creadas']
                            ?? 0
                        )
                    ) ?>
                </strong>

            </div>

            <div class="dato">

                <span class="etiqueta">
                    Resultados insertados
                </span>

                <strong class="correcto">
                    <?= e(
                        (string) (
                            $importacion['resultados_insertados']
                            ?? 0
                        )
                    ) ?>
                </strong>

            </div>

            <div class="dato">

                <span class="etiqueta">
                    Resultados iguales
                </span>

                <strong>
                    <?= e(
                        (string) (
                            $importacion['resultados_iguales']
                            ?? 0
                        )
                    ) ?>
                </strong>

            </div>

            <div class="dato">

                <span class="etiqueta">
                    Resultados con diferencias
                </span>

                <strong>
                    <?= e(
                        (string) (
                            $importacion['resultados_diferentes']
                            ?? 0
                        )
                    ) ?>
                </strong>

            </div>

        </div>

    </div>

</div>

<?php endif; ?>

<!-- AVISO FINAL -->

<div class="tarjeta">

    <?php if ($importacionRealizada): ?>

        <div class="exito">

            <strong>
                Importación completada.
            </strong>

            Los registros fueron guardados correctamente
            en la base de datos dentro de una transacción.

            Los resultados históricos se conservan utilizando
            su fecha de evaluación.

        </div>

    <?php else: ?>

        <div class="advertencia">
            <strong>Importante:</strong>
            la importación no se completó y no se guardó ningún dato.
            Revise el mensaje de error indicado arriba.
        </div>

    <?php endif; ?>

</div>

</div>



<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>
