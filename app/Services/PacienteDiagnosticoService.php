<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\PageForbiddenException;
use CodeIgniter\Database\ConnectionInterface;
use Config\Database;
use DateTimeImmutable;

class PacienteDiagnosticoService
{
    public const FILTRO_ACTIVOS    = 'activos';
    public const FILTRO_HISTORICOS = 'historicos';
    public const FILTRO_TODOS      = 'todos';

    private ConnectionInterface $db;

    private DateTimeImmutable $hoy;

    public function __construct(?ConnectionInterface $db = null)
    {
        $this->db  = $db ?? Database::connect();
        $this->hoy = new DateTimeImmutable('today');
    }

    /**
     * @return array{
     *     filtro: string,
     *     diagnosticos: array<int, array<string, mixed>>,
     *     conteos: array<string, int>
     * }
     */
    public function obtenerDiagnosticos(int $pacienteId, string $filtro): array
    {
        $diagnosticosBase   = $this->obtenerDiagnosticosBase($pacienteId);
        $filtroNormalizado  = $this->normalizarFiltro($filtro);

        $agrupados = [
            self::FILTRO_ACTIVOS    => [],
            self::FILTRO_HISTORICOS => [],
            self::FILTRO_TODOS      => [],
        ];

        foreach ($diagnosticosBase as $row) {
            $diagnostico = $this->formatearDiagnosticoResumen($row);

            $agrupados[self::FILTRO_TODOS][] = $diagnostico;

            if ($diagnostico['planes_activos'] > 0) {
                $agrupados[self::FILTRO_ACTIVOS][] = $diagnostico;
            } else {
                $agrupados[self::FILTRO_HISTORICOS][] = $diagnostico;
            }
        }

        $conteos = [
            self::FILTRO_ACTIVOS    => count($agrupados[self::FILTRO_ACTIVOS]),
            self::FILTRO_HISTORICOS => count($agrupados[self::FILTRO_HISTORICOS]),
            self::FILTRO_TODOS      => count($agrupados[self::FILTRO_TODOS]),
        ];

        return [
            'filtro'       => $filtroNormalizado,
            'diagnosticos' => $agrupados[$filtroNormalizado],
            'conteos'      => $conteos,
        ];
    }

    /**
     * @return array{diagnostico: array<string, mixed>, planes: array<int, array<string, mixed>>}
     */
    public function obtenerDiagnosticoDetalle(int $pacienteId, int $diagnosticoId): array
    {
        $diagnostico = $this->obtenerDiagnosticoBase($pacienteId, $diagnosticoId);

        if ($diagnostico === null) {
            throw PageForbiddenException::forPageForbidden('No se encontró el diagnóstico solicitado.');
        }

        $planes = $this->obtenerPlanesPorDiagnostico((int) $diagnostico['id']);

        return [
            'diagnostico' => $this->formatearDiagnosticoDetalle($diagnostico),
            'planes'      => $planes,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function obtenerDiagnosticosBase(int $pacienteId): array
    {
        $hoy = $this->hoy->format('Y-m-d');
        $finalizado = PlanEstadoService::ESTADO_FINALIZADO;

        $subespecialidad = '(SELECT ue.user_id, MIN(e.nombre) AS nombre FROM usuario_especialidad ue INNER JOIN especialidades e ON e.id = ue.especialidad_id GROUP BY ue.user_id)';

        return $this->db->table('diagnosticos AS d')
            ->select([
                'd.id',
                'd.descripcion',
                'd.fecha_creacion',
                'd.tipo_diagnostico_id',
                'tipo.nombre AS tipo_nombre',
                'd.autor_user_id AS medico_id',
                'medico.nombre AS medico_nombre',
                'medico.apellido AS medico_apellido',
                'medico_especialidad.nombre AS medico_especialidad',
                'COUNT(DISTINCT pc.id) AS total_planes',
                "COALESCE(SUM(CASE WHEN pc.id IS NOT NULL AND pc.fecha_inicio <= '{$hoy}' AND pc.fecha_fin >= '{$hoy}' AND (pc.estado IS NULL OR LOWER(pc.estado) != '{$finalizado}') THEN 1 ELSE 0 END), 0) AS planes_activos",
                "COALESCE(SUM(CASE WHEN pc.id IS NOT NULL AND LOWER(pc.estado) = '{$finalizado}' THEN 1 ELSE 0 END), 0) AS planes_finalizados",
            ])
            ->join('tipos_diagnostico AS tipo', 'tipo.id = d.tipo_diagnostico_id', 'left')
            ->join('users AS medico', 'medico.id = d.autor_user_id AND medico.deleted_at IS NULL', 'left')
            ->join($subespecialidad . ' AS medico_especialidad', 'medico_especialidad.user_id = medico.id', 'left')
            ->join('planes_cuidado AS pc', 'pc.diagnostico_id = d.id AND pc.deleted_at IS NULL', 'left')
            ->where('d.destinatario_user_id', $pacienteId)
            ->where('d.deleted_at', null)
            ->groupBy('d.id')
            ->orderBy('d.fecha_creacion', 'DESC')
            ->orderBy('d.id', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function obtenerDiagnosticoBase(int $pacienteId, int $diagnosticoId): ?array
    {
        $hoy        = $this->hoy->format('Y-m-d');
        $finalizado = PlanEstadoService::ESTADO_FINALIZADO;

        $subespecialidad = '(SELECT ue.user_id, MIN(e.nombre) AS nombre FROM usuario_especialidad ue INNER JOIN especialidades e ON e.id = ue.especialidad_id GROUP BY ue.user_id)';

        return $this->db->table('diagnosticos AS d')
            ->select([
                'd.id',
                'd.descripcion',
                'd.fecha_creacion',
                'd.tipo_diagnostico_id',
                'tipo.nombre AS tipo_nombre',
                'd.autor_user_id AS medico_id',
                'medico.nombre AS medico_nombre',
                'medico.apellido AS medico_apellido',
                'medico_especialidad.nombre AS medico_especialidad',
                'COUNT(DISTINCT pc.id) AS total_planes',
                "COALESCE(SUM(CASE WHEN pc.id IS NOT NULL AND pc.fecha_inicio <= '{$hoy}' AND pc.fecha_fin >= '{$hoy}' AND (pc.estado IS NULL OR LOWER(pc.estado) != '{$finalizado}') THEN 1 ELSE 0 END), 0) AS planes_activos",
                "COALESCE(SUM(CASE WHEN pc.id IS NOT NULL AND LOWER(pc.estado) = '{$finalizado}' THEN 1 ELSE 0 END), 0) AS planes_finalizados",
            ])
            ->join('tipos_diagnostico AS tipo', 'tipo.id = d.tipo_diagnostico_id', 'left')
            ->join('users AS medico', 'medico.id = d.autor_user_id AND medico.deleted_at IS NULL', 'left')
            ->join($subespecialidad . ' AS medico_especialidad', 'medico_especialidad.user_id = medico.id', 'left')
            ->join('planes_cuidado AS pc', 'pc.diagnostico_id = d.id AND pc.deleted_at IS NULL', 'left')
            ->where('d.id', $diagnosticoId)
            ->where('d.destinatario_user_id', $pacienteId)
            ->where('d.deleted_at', null)
            ->groupBy('d.id')
            ->get()
            ->getFirstRow('array') ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function obtenerPlanesPorDiagnostico(int $diagnosticoId): array
    {
        $subespecialidad = '(SELECT ue.user_id, MIN(e.nombre) AS nombre FROM usuario_especialidad ue INNER JOIN especialidades e ON e.id = ue.especialidad_id GROUP BY ue.user_id)';

        $rows = $this->db->table('planes_cuidado AS pc')
            ->select([
                'pc.id',
                'pc.nombre',
                'pc.descripcion',
                'pc.fecha_creacion',
                'pc.fecha_inicio',
                'pc.fecha_fin',
                'pc.estado',
                'pc.creador_user_id AS medico_id',
                'medico.nombre AS medico_nombre',
                'medico.apellido AS medico_apellido',
                'medico_especialidad.nombre AS medico_especialidad',
                'COUNT(DISTINCT a.id) AS total_actividades',
                "COALESCE(SUM(CASE WHEN estado_actividad.slug = 'completada' THEN 1 ELSE 0 END), 0) AS total_completadas",
            ])
            ->join('diagnosticos AS d', 'd.id = pc.diagnostico_id', 'inner')
            ->join('users AS medico', 'medico.id = pc.creador_user_id AND medico.deleted_at IS NULL', 'left')
            ->join($subespecialidad . ' AS medico_especialidad', 'medico_especialidad.user_id = medico.id', 'left')
            ->join('actividades AS a', 'a.plan_id = pc.id AND a.deleted_at IS NULL', 'left')
            ->join('estado_actividad', 'estado_actividad.id = a.estado_id', 'left')
            ->where('pc.diagnostico_id', $diagnosticoId)
            ->where('pc.deleted_at', null)
            ->where('d.deleted_at', null)
            ->groupBy('pc.id')
            ->orderBy('pc.fecha_inicio', 'DESC')
            ->orderBy('pc.id', 'DESC')
            ->get()
            ->getResultArray();

        return array_map(fn (array $row): array => $this->formatearPlanDiagnostico($row), $rows);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function formatearDiagnosticoResumen(array $row): array
    {
        $totalPlanes       = (int) ($row['total_planes'] ?? 0);
        $planesActivos     = (int) ($row['planes_activos'] ?? 0);
        $planesFinalizados = (int) ($row['planes_finalizados'] ?? 0);

        $estadoSlug = $planesActivos > 0
            ? 'activo'
            : ($totalPlanes > 0 ? 'sin_activo' : 'sin_plan');

        $estadoEtiqueta = match ($estadoSlug) {
            'activo'     => 'Con plan activo',
            'sin_activo' => 'Sin plan activo',
            default      => 'Sin plan asignado',
        };

        return [
            'id'                 => (int) $row['id'],
            'descripcion'        => $row['descripcion'] ?? '',
            'fecha_creacion'     => $row['fecha_creacion'] ?? null,
            'tipo_id'            => isset($row['tipo_diagnostico_id']) ? (int) $row['tipo_diagnostico_id'] : null,
            'tipo_nombre'        => $row['tipo_nombre'] ?? null,
            'planes_totales'     => $totalPlanes,
            'planes_activos'     => $planesActivos,
            'planes_finalizados' => $planesFinalizados,
            'estado_slug'        => $estadoSlug,
            'estado_etiqueta'    => $estadoEtiqueta,
            'medico'             => $this->formatearMedico(
                $row['medico_id'] ?? null,
                $row['medico_nombre'] ?? null,
                $row['medico_apellido'] ?? null,
                $row['medico_especialidad'] ?? null
            ),
        ];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function formatearDiagnosticoDetalle(array $row): array
    {
        $resumen = $this->formatearDiagnosticoResumen($row);

        return $resumen + [
            'descripcion_completa' => $row['descripcion'] ?? '',
        ];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function formatearPlanDiagnostico(array $row): array
    {
        $estadoPlan = PlanEstadoService::calcular(
            $row['estado'] ?? null,
            $row['fecha_inicio'] ?? null,
            $row['fecha_fin'] ?? null,
            $this->hoy
        );

        $totalActividades   = (int) ($row['total_actividades'] ?? 0);
        $totalCompletadas   = (int) ($row['total_completadas'] ?? 0);
        $porcentajeAvance   = $totalActividades > 0
            ? (int) round(($totalCompletadas / $totalActividades) * 100)
            : 0;

        return [
            'id'                    => (int) $row['id'],
            'nombre'                => $row['nombre'] ?? null,
            'descripcion'           => $row['descripcion'] ?? null,
            'fecha_creacion'        => $row['fecha_creacion'] ?? null,
            'fecha_inicio'          => $row['fecha_inicio'] ?? null,
            'fecha_fin'             => $row['fecha_fin'] ?? null,
            'estado_categoria'      => $estadoPlan['estado'],
            'estado_etiqueta'       => $estadoPlan['etiqueta'],
            'porcentaje_completadas'=> $porcentajeAvance,
            'total_actividades'     => $totalActividades,
            'total_completadas'     => $totalCompletadas,
            'medico'                => $this->formatearMedico(
                $row['medico_id'] ?? null,
                $row['medico_nombre'] ?? null,
                $row['medico_apellido'] ?? null,
                $row['medico_especialidad'] ?? null
            ),
        ];
    }

    /**
     * @param mixed $id
     * @param mixed $nombre
     * @param mixed $apellido
     * @param mixed $especialidad
     *
     * @return array{
     *     id: ?int,
     *     nombre: ?string,
     *     apellido: ?string,
     *     nombre_completo: ?string,
     *     especialidad: ?string,
     *     disponible: bool
     * }
     */
    private function formatearMedico($id, $nombre, $apellido, $especialidad): array
    {
        $nombreStr       = trim((string) ($nombre ?? ''));
        $apellidoStr     = trim((string) ($apellido ?? ''));
        $nombreCompleto  = trim($nombreStr . ' ' . $apellidoStr);
        $especialidadStr = trim((string) ($especialidad ?? ''));

        return [
            'id'              => $id !== null ? (int) $id : null,
            'nombre'          => $nombreStr !== '' ? $nombreStr : null,
            'apellido'        => $apellidoStr !== '' ? $apellidoStr : null,
            'nombre_completo' => $nombreCompleto !== '' ? $nombreCompleto : null,
            'especialidad'    => $especialidadStr !== '' ? $especialidadStr : null,
            'disponible'      => $nombreCompleto !== '',
        ];
    }

    private function normalizarFiltro(string $filtro): string
    {
        $valor = strtolower(trim($filtro));

        return match ($valor) {
            self::FILTRO_ACTIVOS,
            self::FILTRO_HISTORICOS,
            self::FILTRO_TODOS => $valor,
            default            => self::FILTRO_ACTIVOS,
        };
    }
}
