<?php
declare(strict_types=1);

/*
 * SITA - Caracterización pública del aprendiz
 * Archivo: views/bienestar_publico/caracterizacion.php
 */

if (!function_exists('caracterizacionPublicaEsc')) {
    function caracterizacionPublicaEsc(mixed $valor): string
    {
        return htmlspecialchars(
            (string)($valor ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

$aprendiz = is_array($aprendiz ?? null) ? $aprendiz : [];
$eps = is_array($eps ?? null) ? $eps : [];
$tiposPoblacion = is_array($tiposPoblacion ?? null)
    ? $tiposPoblacion
    : [];

$nombre = trim(
    (string)($aprendiz['nombre_completo'] ?? '')
);

if ($nombre === '') {
    $nombre = trim(
        (string)($aprendiz['nombres'] ?? '')
        . ' '
        . (string)($aprendiz['apellidos'] ?? '')
    );
}

$documento = (string)(
    $aprendiz['documento'] ?? $aprendiz['numero_documento'] ?? ''
);

$numeroFicha = (string)(
    $enlace['numero_ficha'] ?? $enlace['ficha'] ?? ''
);

$urlActualizar = url('/bienestar/caracterizacion/actualizar');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Actualizar caracterización | SITA</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px 14px;
            background: #f1f5f9;
            color: #1e293b;
            font-family: Arial, Helvetica, sans-serif;
        }

        .contenedor {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 28px rgba(15, 23, 42, .09);
        }

        .encabezado {
            padding: 26px;
            color: #fff;
            background: #123c69;
        }

        .encabezado h1 {
            margin: 0 0 10px;
            font-size: 25px;
        }

        .encabezado p {
            margin: 5px 0;
            line-height: 1.5;
        }

        .contenido {
            padding: 26px;
        }

        .aviso {
            margin-bottom: 22px;
            padding: 14px 16px;
            border-radius: 9px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
            line-height: 1.5;
        }

        .mensaje {
            margin-bottom: 18px;
            padding: 13px 15px;
            border-radius: 8px;
            background: #fee2e2;
            color: #991b1b;
        }

        .grupo {
            margin-bottom: 18px;
        }

        .fila {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: 700;
            font-size: 14px;
        }

        input,
        select,
        textarea {
            width: 100%;
            min-height: 43px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #fff;
            font: inherit;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        .acciones {
            display: flex;
            justify-content: flex-end;
            margin-top: 25px;
        }

        button {
            border: 0;
            border-radius: 9px;
            padding: 13px 23px;
            background: #123c69;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        button:hover {
            background: #0b2c50;
        }

        .pie {
            padding: 15px;
            background: #f8fafc;
            color: #64748b;
            text-align: center;
            font-size: 12px;
        }

        @media (max-width: 640px) {
            .fila {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .contenido {
                padding: 18px;
            }

            .encabezado {
                padding: 21px;
            }

            .encabezado h1 {
                font-size: 21px;
            }

            .acciones button {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<?php
$navbarPath = defined('ROOT_PATH')
    ? ROOT_PATH . '/views/layout/navbar.php'
    : null;

if ($navbarPath !== null && is_file($navbarPath)) {
    require $navbarPath;
}
?>




<div class="contenedor">
    <header class="encabezado">
        <h1>Caracterización del aprendiz</h1>
        <p>SITA — Sistema de Trazabilidad de Aprendices</p>

        <?php if ($numeroFicha !== ''): ?>
            <p>Ficha: <?= caracterizacionPublicaEsc($numeroFicha) ?></p>
        <?php endif; ?>
    </header>

    <main class="contenido">
        <div class="aviso">
            <strong>Antes de continuar:</strong>
            revise y actualice sus datos. Debe guardar esta información
            para continuar con el cuestionario de Bienestar.
        </div>

        <?php if (trim((string)($mensaje ?? '')) !== ''): ?>
            <div class="mensaje">
                <?= caracterizacionPublicaEsc($mensaje) ?>
            </div>
        <?php endif; ?>

        <p>
            Aprendiz:
            <strong><?= caracterizacionPublicaEsc($nombre ?: 'Aprendiz') ?></strong>
        </p>

        <form action="<?= caracterizacionPublicaEsc($urlActualizar) ?>"
              method="POST">

            <input type="hidden" name="token"
                   value="<?= caracterizacionPublicaEsc($token ?? '') ?>">

            <input type="hidden" name="_csrf"
                   value="<?= caracterizacionPublicaEsc($csrf ?? '') ?>">

            <input type="hidden" name="id_aprendiz"
                   value="<?= (int)($aprendiz['id'] ?? $aprendiz['id_aprendiz'] ?? 0) ?>">

            <input type="hidden" name="id_persona"
                   value="<?= (int)($aprendiz['id_persona'] ?? 0) ?>">

            <div class="fila">
                <div class="grupo">
                    <label>Documento</label>
                    <input type="text"
                           value="<?= caracterizacionPublicaEsc($documento) ?>"
                           readonly>
                </div>

                <div class="grupo">
                    <label for="fecha_nacimiento">Fecha de nacimiento</label>
                    <input type="date" id="fecha_nacimiento"
                           name="fecha_nacimiento"
                           value="<?= caracterizacionPublicaEsc($aprendiz['fecha_nacimiento'] ?? '') ?>">
                </div>

                <div class="grupo">
                    <label for="sexo">Sexo</label>
                    <select id="sexo" name="sexo">
                        <option value="">Seleccione</option>
                        <?php foreach (['Femenino', 'Masculino', 'Otro', 'Prefiero no responder'] as $opcion): ?>
                            <option value="<?= caracterizacionPublicaEsc($opcion) ?>"
                                <?= (string)($aprendiz['sexo'] ?? '') === $opcion ? 'selected' : '' ?>>
                                <?= caracterizacionPublicaEsc($opcion) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grupo">
                    <label for="telefono">Teléfono</label>
                    <input type="text" id="telefono" name="telefono"
                           maxlength="30"
                           value="<?= caracterizacionPublicaEsc($aprendiz['telefono'] ?? '') ?>">
                </div>

                <div class="grupo">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" id="correo" name="correo"
                           maxlength="150"
                           value="<?= caracterizacionPublicaEsc($aprendiz['correo'] ?? $aprendiz['email'] ?? '') ?>">
                </div>

                <div class="grupo">
                    <label for="direccion">Dirección</label>
                    <input type="text" id="direccion" name="direccion"
                           maxlength="250"
                           value="<?= caracterizacionPublicaEsc($aprendiz['direccion'] ?? '') ?>">
                </div>

                <div class="grupo">
                    <label for="departamento">Departamento</label>
                    <input type="text" id="departamento" name="departamento"
                           maxlength="100"
                           value="<?= caracterizacionPublicaEsc($aprendiz['departamento'] ?? '') ?>">
                </div>

                <div class="grupo">
                    <label for="municipio">Municipio</label>
                    <input type="text" id="municipio" name="municipio"
                           maxlength="100"
                           value="<?= caracterizacionPublicaEsc($aprendiz['municipio'] ?? '') ?>">
                </div>

                <div class="grupo">
                    <label for="id_eps">EPS</label>
                    <select id="id_eps" name="id_eps">
                        <option value="">Seleccione</option>
                        <?php foreach ($eps as $item): ?>
                            <?php
                            $idEps = (int)($item['id_eps'] ?? $item['id'] ?? 0);
                            $nombreEps = (string)($item['nombre'] ?? $item['nombre_eps'] ?? '');
                            ?>
                            <option value="<?= $idEps ?>"
                                <?= $idEps === (int)($aprendiz['id_eps'] ?? 0) ? 'selected' : '' ?>>
                                <?= caracterizacionPublicaEsc($nombreEps) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grupo">
                    <label for="id_tipo_poblacion">Tipo de población</label>
                    <select id="id_tipo_poblacion" name="id_tipo_poblacion">
                        <option value="">Seleccione</option>
                        <?php foreach ($tiposPoblacion as $item): ?>
                            <?php
                            $idTipo = (int)($item['id_tipo_poblacion'] ?? $item['id'] ?? 0);
                            $nombreTipo = (string)($item['nombre'] ?? $item['nombre_tipo'] ?? '');
                            ?>
                            <option value="<?= $idTipo ?>"
                                <?= $idTipo === (int)($aprendiz['id_tipo_poblacion'] ?? 0) ? 'selected' : '' ?>>
                                <?= caracterizacionPublicaEsc($nombreTipo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h3>Contacto de emergencia</h3>

            <div class="fila">
                <div class="grupo">
                    <label for="contacto_emergencia">
                        Nombre del contacto
                    </label>
                    <input type="text" id="contacto_emergencia"
                           name="contacto_emergencia" maxlength="150"
                           value="<?= caracterizacionPublicaEsc($aprendiz['contacto_emergencia'] ?? '') ?>">
                </div>

                <div class="grupo">
                    <label for="telefono_emergencia">
                        Teléfono de emergencia
                    </label>
                    <input type="text" id="telefono_emergencia"
                           name="telefono_emergencia" maxlength="30"
                           value="<?= caracterizacionPublicaEsc($aprendiz['telefono_emergencia'] ?? '') ?>">
                </div>
            </div>

            <div class="grupo">
                <label for="observacion">Observación (opcional)</label>
                <textarea id="observacion" name="observacion"
                          maxlength="1000"><?= caracterizacionPublicaEsc($aprendiz['observacion'] ?? '') ?></textarea>
            </div>

            <div class="acciones">
                <button type="submit">
                    Guardar caracterización y continuar
                </button>
            </div>
        </form>
    </main>

    <footer class="pie">
        SITA — Sistema de Trazabilidad de Aprendices
    </footer>
</div>
</body>
</html>

