<?php
declare(strict_types=1);

/**
 * SITA - Controlador del panel principal.
 * Archivo: controllers/DashboardController.php
 */
require_once ROOT_PATH . '/models/DashboardModel.php';

class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $modelo = new DashboardModel();

        $indicadores = $modelo->obtenerIndicadores();
        $estadosAprendices = $modelo->obtenerEstadosAprendices();
        $distribucionSexo = $modelo->obtenerDistribucionSexo();
        $aprendicesPorMunicipio = $modelo->obtenerAprendicesPorMunicipio();
        $estadosResultados = $modelo->obtenerEstadosResultados();
        $alertasPorTipo = $modelo->obtenerAlertasPorTipo();
        $calidadDatos = $modelo->obtenerCalidadDatos();
        $distribucionPoblacion = $modelo->obtenerDistribucionPoblacion();
        $resumenBienestar = $modelo->obtenerResumenBienestar();
        $evolucionCuestionarios = $modelo->obtenerEvolucionCuestionarios();

        $this->view('dashboard/index', [
            'tituloPagina' => 'Panel principal',
            'indicadores' => $indicadores,
            'estadosAprendices' => $estadosAprendices,
            'distribucionSexo' => $distribucionSexo,
            'aprendicesPorMunicipio' => $aprendicesPorMunicipio,
            'estadosResultados' => $estadosResultados,
            'alertasPorTipo' => $alertasPorTipo,
            'calidadDatos' => $calidadDatos,
            'distribucionPoblacion' => $distribucionPoblacion,
            'resumenBienestar' => $resumenBienestar,
            'evolucionCuestionarios' => $evolucionCuestionarios,
        ]);
    }
}