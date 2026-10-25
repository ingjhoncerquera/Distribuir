<?php

declare(strict_types=1);

$tituloPagina = $tituloPagina ?? nombreAplicacion();

$usuarioActual = Auth::user();
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
        <?= e($tituloPagina) ?> -
        <?= e(nombreAplicacion()) ?>
    </title>

    <meta
        name="description"
        content="Sistema de Trazabilidad Integral de Aprendices"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="<?= e(url('/public/css/app.css')) ?>"
    >

</head>

<body>

<?php require ROOT_PATH . '/views/layout/navbar.php'; ?>

<main class="main-content">

    <div class="container-fluid px-4 py-4">