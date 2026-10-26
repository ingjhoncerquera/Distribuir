<?php
declare(strict_types=1);

/**
 * Modelo del módulo Entregas.
 * Reutiliza las consultas actuales para mantener consistentes los datos de
 * solicitudes avaladas, materiales y cantidades distribuidas.
 */
class EntregasModel
{
    private MaterialesRecepcionDistribucionModel $recepcionDistribucion;

    public function __construct()
    {
        $this->recepcionDistribucion = new MaterialesRecepcionDistribucionModel();
    }

    public function solicitudesAvaladas(int $coordinador, bool $admin): array
    {
        return $this->recepcionDistribucion->solicitudes($coordinador, $admin);
    }

    public function profesionalUsuario(int $idUsuario): ?int
    {
        return $this->recepcionDistribucion->profesionalUsuario($idUsuario);
    }
}
