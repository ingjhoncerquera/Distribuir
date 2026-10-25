<?php

declare(strict_types=1);

$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e($tituloPagina ?? 'Ingreso al sistema') ?>
        | SITA
    </title>

    <style>
        :root {
            --azul: #123B5D;
            --azul-oscuro: #0B263D;
            --azul-profundo: #081E31;

            --turquesa: #19A7A8;
            --turquesa-oscuro: #13898A;
            --turquesa-suave: #EAF7F7;

            --blanco: #FFFFFF;
            --fondo: #F2F6F8;

            --gris-100: #F8FAFB;
            --gris-200: #E4EBEF;
            --gris-300: #D2DDE3;
            --gris-500: #82909A;
            --gris-600: #64748B;
            --gris-700: #475569;
            --gris-800: #263746;

            --sombra:
                0 25px 70px rgba(11, 38, 61, 0.18);

            --radio: 24px;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        body {
            min-height: 100vh;

            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Arial,
                sans-serif;

            color: var(--gris-800);

            background:
                radial-gradient(
                    circle at 8% 12%,
                    rgba(25, 167, 168, 0.10),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 92% 88%,
                    rgba(18, 59, 93, 0.10),
                    transparent 30%
                ),
                var(--fondo);
        }

        /* ==========================================================
           CONTENEDOR PRINCIPAL
        ========================================================== */

        .login-page {
            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 35px 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 1180px;
            min-height: 680px;

            display: grid;
            grid-template-columns: 43% 57%;

            overflow: hidden;

            background: var(--blanco);

            border-radius: var(--radio);

            box-shadow: var(--sombra);
        }

        /* ==========================================================
           PANEL IZQUIERDO
        ========================================================== */

        .login-panel {
            position: relative;

            display: flex;
            align-items: center;

            padding: 55px;
        }

        .login-panel::before {
            content: "";

            position: absolute;

            width: 250px;
            height: 250px;

            left: -125px;
            top: -125px;

            border-radius: 50%;

            background:
                rgba(25, 167, 168, 0.055);
        }

        .login-panel::after {
            content: "";

            position: absolute;

            width: 160px;
            height: 160px;

            right: -100px;
            bottom: -100px;

            border-radius: 50%;

            border: 1px solid rgba(18, 59, 93, 0.06);
        }

        .login-content {
            position: relative;
            z-index: 2;

            width: 100%;
            max-width: 390px;

            margin: auto;
        }

        /* ==========================================================
           MARCA
        ========================================================== */

        .brand-mark {
            width: 60px;
            height: 60px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 25px;

            border-radius: 17px;

            color: white;

            font-size: 21px;
            font-weight: 800;

            letter-spacing: -1px;

            background:
                linear-gradient(
                    135deg,
                    var(--azul),
                    var(--azul-oscuro)
                );

            box-shadow:
                0 12px 25px rgba(18, 59, 93, 0.20);
        }

        .login-title {
            margin: 0;

            color: var(--azul-oscuro);

            font-size: 32px;
            line-height: 1.15;

            font-weight: 800;

            letter-spacing: -0.8px;
        }

        .login-subtitle {
            margin: 10px 0 32px;

            color: var(--gris-600);

            font-size: 14px;
            line-height: 1.65;
        }

        /* ==========================================================
           FORMULARIO
        ========================================================== */

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;

            margin-bottom: 8px;

            color: var(--gris-700);

            font-size: 13px;
            font-weight: 700;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;

            left: 17px;
            top: 50%;

            width: 19px;
            height: 19px;

            transform: translateY(-50%);

            color: var(--gris-500);

            pointer-events: none;
        }

        .form-control {
            width: 100%;
            height: 54px;

            padding: 0 17px 0 49px;

            border: 1px solid var(--gris-200);

            border-radius: 13px;

            outline: none;

            color: var(--gris-800);

            background: var(--gris-100);

            font-size: 14px;

            transition:
                border-color .2s ease,
                background .2s ease,
                box-shadow .2s ease;
        }

        .form-control::placeholder {
            color: #9AA5AD;
        }

        .form-control:focus {
            background: white;

            border-color: var(--turquesa);

            box-shadow:
                0 0 0 4px rgba(25, 167, 168, 0.10);
        }

        /* ==========================================================
           ERROR
        ========================================================== */

        .alert-error {
            display: flex;
            align-items: flex-start;
            gap: 10px;

            margin-bottom: 20px;

            padding: 13px 15px;

            border-radius: 12px;

            border: 1px solid #F1D2D2;

            background: #FFF5F5;

            color: #A33A3A;

            font-size: 13px;
            line-height: 1.5;
        }

        .alert-error svg {
            flex-shrink: 0;
            margin-top: 1px;
        }

        /* ==========================================================
           BOTÓN
        ========================================================== */

        .btn-login {
            width: 100%;
            height: 54px;

            border: 0;
            border-radius: 13px;

            color: white;

            background:
                linear-gradient(
                    135deg,
                    var(--turquesa),
                    var(--turquesa-oscuro)
                );

            font-size: 14px;
            font-weight: 750;

            cursor: pointer;

            box-shadow:
                0 10px 25px rgba(25, 167, 168, 0.22);

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .btn-login:hover {
            transform: translateY(-1px);

            box-shadow:
                0 14px 30px rgba(25, 167, 168, 0.28);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .security-note {
            display: flex;
            align-items: center;
            justify-content: center;

            gap: 7px;

            margin-top: 22px;

            color: var(--gris-500);

            font-size: 11px;
        }

        .security-note svg {
            color: var(--turquesa);
        }

        /* ==========================================================
           PANEL DERECHO
        ========================================================== */

        .info-panel {
            position: relative;

            overflow: hidden;

            padding: 55px;

            color: white;

            background:
                linear-gradient(
                    135deg,
                    var(--azul-oscuro) 0%,
                    var(--azul) 55%,
                    #165B72 100%
                );
        }

        .info-panel::before {
            content: "";

            position: absolute;

            width: 500px;
            height: 500px;

            right: -250px;
            top: -270px;

            border-radius: 50%;

            background:
                rgba(25, 167, 168, 0.12);
        }

        .info-panel::after {
            content: "";

            position: absolute;

            width: 340px;
            height: 340px;

            left: -200px;
            bottom: -200px;

            border-radius: 50%;

            border:
                1px solid rgba(255, 255, 255, 0.08);
        }

        .info-content {
            position: relative;
            z-index: 2;

            width: 100%;
            max-width: 570px;
            height: 100%;

            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* ==========================================================
           SITA
        ========================================================== */

        .sita-brand {
            display: flex;
            align-items: center;
            gap: 15px;

            margin-bottom: 28px;
        }

        .sita-icon {
            width: 58px;
            height: 58px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 17px;

            color: white;

            background:
                rgba(25, 167, 168, 0.20);

            border:
                1px solid rgba(255, 255, 255, 0.18);

            font-size: 20px;
            font-weight: 800;

            backdrop-filter: blur(8px);
        }

        .sita-name {
            margin: 0;

            color: white;

            font-size: 35px;
            line-height: 1;

            font-weight: 850;

            letter-spacing: -1px;
        }

        .sita-full-name {
            margin: 7px 0 0;

            color:
                rgba(255, 255, 255, 0.70);

            font-size: 12px;
            font-weight: 500;
        }

        /* ==========================================================
           INFORMACIÓN
        ========================================================== */

        .info-title {
            margin: 0 0 14px;

            color: white;

            font-size: 29px;
            line-height: 1.2;

            font-weight: 750;

            letter-spacing: -0.6px;
        }

        .info-description {
            max-width: 530px;

            margin: 0 0 29px;

            color:
                rgba(255, 255, 255, 0.76);

            font-size: 14px;
            line-height: 1.7;
        }

        /* ==========================================================
           CARACTERÍSTICAS
        ========================================================== */

        .features {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 13px;
        }

        .feature {
            min-height: 108px;

            padding: 17px;

            border-radius: 15px;

            background:
                rgba(255, 255, 255, 0.065);

            border:
                1px solid rgba(255, 255, 255, 0.09);

            transition:
                transform .2s ease,
                background .2s ease;
        }

        .feature:hover {
            transform: translateY(-2px);

            background:
                rgba(255, 255, 255, 0.105);
        }

        .feature-icon {
            width: 34px;
            height: 34px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 10px;

            border-radius: 9px;

            color: #72E2E1;

            background:
                rgba(25, 167, 168, 0.15);
        }

        .feature-title {
            margin: 0 0 5px;

            color: white;

            font-size: 12.5px;
            font-weight: 750;
        }

        .feature-text {
            margin: 0;

            color:
                rgba(255, 255, 255, 0.60);

            font-size: 11px;
            line-height: 1.45;
        }

        .info-footer {
            display: flex;
            align-items: center;
            gap: 9px;

            margin-top: 28px;

            color:
                rgba(255, 255, 255, 0.48);

            font-size: 10.5px;
        }

        .info-footer-line {
            width: 28px;
            height: 1px;

            background:
                rgba(255, 255, 255, 0.25);
        }

        /* ==========================================================
           RESPONSIVE
        ========================================================== */

        @media (max-width: 980px) {

            .login-wrapper {
                max-width: 820px;

                grid-template-columns:
                    1fr 1fr;
            }

            .login-panel,
            .info-panel {
                padding: 45px 35px;
            }

            .login-title {
                font-size: 28px;
            }

            .info-title {
                font-size: 24px;
            }

            .sita-name {
                font-size: 30px;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .feature {
                min-height: auto;
            }
        }

        @media (max-width: 760px) {

            .login-page {
                padding: 0;
            }

            .login-wrapper {
                min-height: 100vh;

                border-radius: 0;

                grid-template-columns: 1fr;
            }

            .info-panel {
                order: 1;

                min-height: auto;

                padding: 42px 28px;
            }

            .login-panel {
                order: 2;

                padding: 42px 28px 35px;
            }

            .features {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .info-title {
                font-size: 24px;
            }
        }

        @media (max-width: 520px) {

            .info-panel,
            .login-panel {
                padding-left: 22px;
                padding-right: 22px;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .login-title {
                font-size: 27px;
            }

            .info-description {
                font-size: 13.5px;
            }
        }
    </style>
</head>

<body>

<div class="login-page">

    <div class="login-wrapper">

        <!-- ======================================================
             INGRESO
        ======================================================= -->

        <section class="login-panel">

            <div class="login-content">

                <div class="brand-mark">
                    SITA
                </div>

                <h1 class="login-title">
                    Bienvenido
                </h1>

                <p class="login-subtitle">
                    Ingrese sus credenciales para acceder a
                    la plataforma de trazabilidad de aprendices.
                </p>

                <?php if (!empty($error)): ?>

                    <div class="alert-error">

                        <svg
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle
                                cx="12"
                                cy="12"
                                r="10"
                            ></circle>

                            <line
                                x1="12"
                                y1="8"
                                x2="12"
                                y2="12"
                            ></line>

                            <line
                                x1="12"
                                y1="16"
                                x2="12.01"
                                y2="16"
                            ></line>
                        </svg>

                        <span>
                            <?= e($error) ?>
                        </span>

                    </div>

                <?php endif; ?>

                <form
                    action="<?= e(url('/login')) ?>"
                    method="POST"
                    autocomplete="off"
                >

                    <?= csrfField() ?>

                    <div class="form-group">

                        <label
                            class="form-label"
                            for="login"
                        >
                            Usuario
                        </label>

                        <div class="input-wrapper">

                            <svg
                                class="input-icon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    d="M20 21a8 8 0 0 0-16 0"
                                ></path>

                                <circle
                                    cx="12"
                                    cy="7"
                                    r="4"
                                ></circle>
                            </svg>

                            <input
                                type="text"
                                id="login"
                                name="login"
                                class="form-control"
                                placeholder="Usuario, correo o documento"
                                value="<?= e(
                                    $_POST['login'] ?? ''
                                ) ?>"
                                autocomplete="username"
                                required
                            >

                        </div>

                    </div>


                    <div class="form-group">

                        <label
                            class="form-label"
                            for="password"
                        >
                            Contraseña
                        </label>

                        <div class="input-wrapper">

                            <svg
                                class="input-icon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <rect
                                    x="3"
                                    y="11"
                                    width="18"
                                    height="10"
                                    rx="2"
                                ></rect>

                                <path
                                    d="M7 11V7a5 5 0 0 1 10 0v4"
                                ></path>
                            </svg>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                placeholder="Ingrese su contraseña"
                                autocomplete="current-password"
                                required
                            >

                        </div>

                    </div>


                    <button
                        type="submit"
                        class="btn-login"
                    >
                        Ingresar al sistema
                    </button>

                </form>


                <div class="security-note">

                    <svg
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <rect
                            x="3"
                            y="11"
                            width="18"
                            height="10"
                            rx="2"
                        ></rect>

                        <path
                            d="M7 11V7a5 5 0 0 1 10 0v4"
                        ></path>
                    </svg>

                    Acceso protegido mediante roles y permisos

                </div>

            </div>

        </section>


        <!-- ======================================================
             INFORMACIÓN SITA
        ======================================================= -->

        <section class="info-panel">

            <div class="info-content">

                <div class="sita-brand">

                    <div class="sita-icon">
                        SI
                    </div>

                    <div>

                        <h2 class="sita-name">
                            SITA
                        </h2>

                        <p class="sita-full-name">
                            Sistema de Trazabilidad de Aprendices
                        </p>

                    </div>

                </div>


                <h3 class="info-title">
                    Acompañamiento integral del aprendiz
                </h3>


                <p class="info-description">
                    SITA permite centralizar el seguimiento del
                    aprendiz durante su proceso de formación,
                    integrando información académica, bienestar,
                    salud y orientación institucional.
                </p>


                <div class="features">

                    <article class="feature">

                        <div class="feature-icon">

                            <svg
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"
                                ></path>

                                <path
                                    d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"
                                ></path>

                            </svg>

                        </div>

                        <h4 class="feature-title">
                            Seguimiento académico
                        </h4>

                        <p class="feature-text">
                            RAP, resultados y avance
                            del proceso formativo.
                        </p>

                    </article>


                    <article class="feature">

                        <div class="feature-icon">

                            <svg
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"
                                ></path>

                            </svg>

                        </div>

                        <h4 class="feature-title">
                            Bienestar integral
                        </h4>

                        <p class="feature-text">
                            Acompañamiento y orientación
                            durante la formación.
                        </p>

                    </article>


                    <article class="feature">

                        <div class="feature-icon">

                            <svg
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    d="M12 3a6 6 0 0 0-6 6v4a6 6 0 0 0 12 0V9a6 6 0 0 0-6-6z"
                                ></path>

                                <path
                                    d="M4 13a8 8 0 0 0 16 0"
                                ></path>

                                <path
                                    d="M12 21v-3"
                                ></path>

                            </svg>

                        </div>

                        <h4 class="feature-title">
                            Salud y nutrición
                        </h4>

                        <p class="feature-text">
                            Seguimiento profesional y
                            recomendaciones de bienestar.
                        </p>

                    </article>


                    <article class="feature">

                        <div class="feature-icon">

                            <svg
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6-8 10-8 10z"
                                ></path>

                                <path
                                    d="m9 12 2 2 4-4"
                                ></path>

                            </svg>

                        </div>

                        <h4 class="feature-title">
                            Información protegida
                        </h4>

                        <p class="feature-text">
                            Acceso según rol y protección
                            de información sensible.
                        </p>

                    </article>

                </div>


                <div class="info-footer">

                    <span class="info-footer-line"></span>

                    <span>
                        Formación · Seguimiento · Orientación · Bienestar
                    </span>

                </div>

            </div>

        </section>

    </div>

</div>

</body>
</html>

