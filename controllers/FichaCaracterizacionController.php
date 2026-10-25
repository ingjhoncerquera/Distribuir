<?php
declare(strict_types=1);

/** SITA - Controlador de consulta de ficha de caracterización. */
class FichaCaracterizacionController extends Controller
{
    private FichaCaracterizacionModel $model;

    public function __construct()
    {
        $this->model = new FichaCaracterizacionModel();
    }

    public function index(): void
    {
        Auth::requirePermission('aprendices.ver');

        $buscar = trim((string)$this->get('buscar', ''));
        $fichaParametro = trim((string)$this->get('id_ficha', ''));
        $estado = trim((string)$this->get('estado_caracterizacion', ''));
        if (!in_array($estado, ['', 'actualizado', 'pendiente'], true)) $estado = '';

        $idFicha = null;
        if ($fichaParametro !== '' && ctype_digit($fichaParametro) && (int)$fichaParametro > 0) {
            $idFicha = (int)$fichaParametro;
        }

        $filtros = [
            'buscar' => $buscar,
            'id_ficha' => $idFicha,
            'estado_caracterizacion' => $estado,
            'sexo' => trim((string)$this->get('sexo', '')),
            'municipio' => trim((string)$this->get('municipio', '')),
            'departamento' => trim((string)$this->get('departamento', '')),
            'eps' => trim((string)$this->get('eps', '')),
            'poblacion' => trim((string)$this->get('poblacion', '')),
            'estrato' => trim((string)$this->get('estrato', '')),
        ];

        $pagina = max(1, (int)$this->get('pagina', 1));
        $porPagina = 20;
        $total = $this->model->contarConsulta($filtros);
        $estadisticas = $this->model->obtenerEstadisticas($filtros);
        $totalPaginas = max(1, (int)ceil($total / $porPagina));
        if ($pagina > $totalPaginas) $pagina = $totalPaginas;

        $this->view('bienestar/consultas/caracterizacion', [
            'tituloPagina' => 'Consulta de Ficha de Caracterización',
            'titulo' => 'Consulta de Ficha de Caracterización',
            'aprendices' => $this->model->listarConsulta($filtros, $porPagina, ($pagina - 1) * $porPagina),
            'fichas' => $this->model->listarFichas(),
            'filtros' => $filtros,
            'opciones' => [
                'sexo' => $this->model->opcionesFiltro('sexo'),
                'municipio' => $this->model->opcionesFiltro('municipio'),
                'departamento' => $this->model->opcionesFiltro('departamento'),
                'eps' => $this->model->opcionesFiltro('eps'),
                'poblacion' => $this->model->opcionesFiltro('poblacion'),
            ],
            'estadisticas' => $estadisticas,
            'estratoDisponible' => (bool)($estadisticas['estrato_disponible'] ?? false),
            'pagina' => $pagina,
            'porPagina' => $porPagina,
            'total' => $total,
            'totalPaginas' => $totalPaginas,
        ]);
    }
}
