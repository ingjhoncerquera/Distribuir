<?php
declare(strict_types=1);

/**
 * SITA - Modelo de consulta de Habilidades Artísticas.
 * Reutiliza el modelo compartido para conservar las reglas
 * de permisos, matrículas y cuestionarios existentes.
 */
class BienestarHabilidadesArtisticasModel
{
    private BienestarConsultaModel $consulta;

    public function __construct()
    {
        $this->consulta = new BienestarConsultaModel();
    }

    public function obtenerFichas(): array
    {
        return $this->consulta->obtenerFichas();
    }

    public function buscarAprendices(
        string $busqueda = '',
        int $idFicha = 0,
        string $estadoCuestionario = ''
    ): array {
        return $this->consulta->buscarAprendices(
            $busqueda,
            $idFicha,
            $estadoCuestionario
        );
    }

    public function obtenerCaracterizacion(int $idAprendiz): ?array
    {
        return $this->consulta->obtenerCaracterizacion($idAprendiz);
    }

    public function obtenerCuestionarios(int $idAprendiz): array
    {
        $cuestionarios = $this->consulta->obtenerCuestionarios($idAprendiz);

        foreach ($cuestionarios as &$cuestionario) {
            $respuestas = $cuestionario['respuestas'] ?? [];

            if (is_string($respuestas)) {
                $decodificadas = json_decode($respuestas, true);
                $respuestas = is_array($decodificadas) ? $decodificadas : [];
            }

            $filtradas = [];

            foreach ($respuestas as $codigo => $respuesta) {
                $codigoNormalizado = strtoupper(
                    str_replace('_', '-', trim((string)$codigo))
                );

                if (preg_match('/^HA(?:-|$)/', $codigoNormalizado)) {
                    $filtradas[$codigoNormalizado] = $respuesta;
                }
            }

            $cuestionario['respuestas'] = $filtradas;
        }
        unset($cuestionario);

        return $cuestionarios;
    }

    public function obtenerResumenPorFicha(int $idFicha = 0): array
    {
        return $this->consulta->obtenerResumenPorFicha($idFicha);
    }

    public function obtenerEstadisticasRespuestas(int $idFicha = 0): array
    {
        $estadisticas = $this->consulta->obtenerEstadisticasRespuestas($idFicha);

        return array_values(array_filter(
            $estadisticas,
            static function ($fila): bool {
                $codigo = strtoupper(str_replace(
                    '_',
                    '-',
                    trim((string)($fila['codigo_pregunta'] ?? $fila['codigo'] ?? ''))
                ));

                return preg_match('/^HA(?:-|$)/', $codigo) === 1;
            }
        ));
    }

    /**
     * Respuestas de TODAS las preguntas HA (HA-01 ... HA-10) de cada
     * aprendiz con matrícula activa, usando el cuestionario más reciente
     * por aprendiz y ficha.
     *
     * Usa el modelo compartido, por lo que respeta los permisos del
     * componente (si el usuario no puede ver HA, devuelve []).
     *
     * Cada fila incluye los datos del aprendiz y la clave:
     *   'respuestas_ha' => [
     *       'HA-04' => ['Guitarra', 'Piano'],
     *       'HA-09' => ['Tarde'],
     *       ...
     *   ]
     *
     * Los aprendices sin respuestas HA se devuelven con 'respuestas_ha'
     * vacío.
     */
    public function obtenerRespuestasHabilidadesArtisticas(
        int $idFicha = 0
    ): array {
        $filas = $this->consulta->obtenerRespuestasPorComponente(
            'HA',
            $idFicha
        );

        foreach ($filas as &$fila) {
            $fila['respuestas_ha'] = $fila['respuestas_componente'] ?? [];
            unset($fila['respuestas_componente']);
        }

        unset($fila);

        return $filas;
    }
}