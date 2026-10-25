<?php
declare(strict_types=1);

$e = static fn($valor): string => htmlspecialchars(
    (string)$valor,
    ENT_QUOTES,
    'UTF-8'
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuestionario de Bienestar | SITA</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }
        .contenedor {
            width: min(560px, calc(100% - 28px));
            margin: 55px auto;
        }
        .encabezado {
            background: #12365b;
            color: white;
            padding: 28px;
            border-radius: 16px 16px 0 0;
        }
        .encabezado h1 {
            margin: 0 0 10px;
            font-size: 25px;
        }
        .encabezado p {
            margin: 0;
            line-height: 1.6;
            color: #e2e8f0;
        }
        .tarjeta {
            background: white;
            padding: 28px;
            border-radius: 0 0 16px 16px;
            box-shadow: 0 12px 35px rgba(15, 23, 42, .09);
        }
        label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
        }
        input[type="text"] {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            font-size: 16px;
            margin-bottom: 18px;
        }
        input:focus {
            outline: 2px solid #93c5fd;
            border-color: #2563eb;
        }
        button {
            width: 100%;
            border: 0;
            border-radius: 9px;
            background: #1769aa;
            color: white;
            padding: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }
        button:hover { background: #125487; }
        .mensaje {
            padding: 13px 15px;
            border-radius: 9px;
            margin-bottom: 18px;
            background: #eff6ff;
            color: #1e40af;
            line-height: 1.5;
        }
        .aviso {
            font-size: 13px;
            line-height: 1.6;
            color: #64748b;
            margin-top: 20px;
        }
        footer {
            text-align: center;
            font-size: 12px;
            color: #64748b;
            padding: 20px 0;
        }
    </style>
</head>
<body>
<div class="contenedor">
    <header class="encabezado">
        <h1>SITA · Bienestar al Aprendiz</h1>
        <p>Cuestionario de caracterización, promoción, prevención y participación.</p>
    </header>

    <main class="tarjeta">
        <h2>Identificación del aprendiz</h2>
        <p>Ingrese su documento para comprobar su registro en la ficha correspondiente.</p>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje" role="status">
                <?= $e($mensaje) ?>
            </div>
        <?php endif; ?>

        <form method="POST"
              action="<?= $e(BASE_URL) ?>/bienestar/cuestionario/validar"
              autocomplete="on">

            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <input type="hidden" name="token" value="<?= $e($token) ?>">

            <label for="documento">Número de documento</label>
            <input
                type="text"
                id="documento"
                name="documento"
                inputmode="numeric"
                autocomplete="off"
                maxlength="30"
                required
                placeholder="Ingrese su número de documento"
            >

            <button type="submit">Validar y continuar</button>
        </form>

        <p class="aviso">
            Sus respuestas deben ser personales y veraces. La información
            suministrada debe tratarse de forma confidencial y utilizarse
            únicamente para los fines institucionales autorizados.
        </p>
    </main>

    <footer>SITA · Sistema de Trazabilidad de Aprendices</footer>
</div>
</body>
</html>