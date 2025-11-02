<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\ConnectionInterface;
use Config\Database;

class PacienteDashboardService
{
    private const ESTADO_PENDIENTE  = 'pendiente';
    private const ESTADO_COMPLETADA = 'completada';
    private const ESTADO_VENCIDA    = 'vencida';

    private const ESTADOS_PLAN_FINALIZADOS = [
        'finalizado',
        'terminado',
        'completado',
        'cerrado',
    ];

    private ConnectionInterface $db;

    private PacientePlanService $planService;

    private \DateTimeImmutable $hoy;

    public function __construct(?ConnectionInterface $db = null, ?PacientePlanService $planService = null)
    {
        $this->db          = $db ?? Database::connect();
        $this->planService = $planService ?? new PacientePlanService($this->db);
        $this->hoy         = new \DateTimeImmutable('today');
    }

    /**
     * @return array<string, mixed>
     */
    public function obtenerDashboard(int $pacienteId): array
    {
        $planesRespuesta = $this->planService->obtenerPlanes($pacienteId, 'todos');
        $planes           = $planesRespuesta['planes'] ?? [];
        $conteosPlanes    = $planesRespuesta['conteos'] ?? [];

        $diagnosticos = $this->obtenerDiagnosticoStats($pacienteId);

        return [
            'kpis'                => $this->calcularKpis($planes, $conteosPlanes, $diagnosticos),
            'graficos'            => $this->construirGraficos($planes),
            'actividadesProximas' => $this->obtenerActividadesProximas($pacienteId),
            'resumen'             => $this->construirResumen($planes, $diagnosticos),
            'avisos'              => $this->obtenerAvisos($pacienteId),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $planes
     * @param array<string, int>               $conteosPlanes
     * @param array<string, int>               $diagnosticos
     *
     * @return array<string, int>
     */
    private function calcularKpis(array $planes, array $conteosPlanes, array $diagnosticos): array
    {
        $acumulados = [
            'pendientes'  => 0,
            'completadas' => 0,
            'vencidas'    => 0,
        ];

        foreach ($planes as $plan) {
            $acumulados['pendientes']  += (int) ($plan['total_pendientes'] ?? 0);
            $acumulados['completadas'] += (int) ($plan['total_completadas'] ?? 0);
            $acumulados['vencidas']    += (int) ($plan['total_vencidas'] ?? 0);
        }

        return [
            'diagnosticosActivos'    => $diagnosticos['activos'] ?? 0,
            'planesActivos'          => (int) ($conteosPlanes['activos'] ?? 0),
            'planesFinalizados'      => (int) ($conteosPlanes['finalizados'] ?? 0),
            'actividadesCompletadas' => $acumulados['completadas'],
            'actividadesPendientes'  => $acumulados['pendientes'],
            'actividadesVencidas'    => $acumulados['vencidas'],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $planes
     *
     * @return array<string, mixed>
     */
    private function construirGraficos(array $planes): array
    {
        $pendientes  = 0;
        $completadas = 0;
        $vencidas    = 0;

        $labelsProgreso = [];
        $valuesProgreso = [];

        foreach ($planes as $plan) {
            $pendientes  += (int) ($plan['total_pendientes'] ?? 0);
            $completadas += (int) ($plan['total_completadas'] ?? 0);
            $vencidas    += (int) ($plan['total_vencidas'] ?? 0);

            if (($plan['estado_categoria'] ?? '') !== 'activos') {
                continue;
            }

            $nombrePlan = trim((string) ($plan['nombre'] ?? ''));
            if ($nombrePlan === '') {
                $nombrePlan = 'Plan sin nombre';
            }

            $labelsProgreso[] = $nombrePlan;
            $valuesProgreso[] = (int) ($plan['porcentaje_completadas'] ?? 0);
        }

        $totalActividades = $pendientes + $completadas + $vencidas;

        return [
            'actividadesDistribucion' => [
                'labels' => ['Pendientes', 'Completadas', 'Vencidas'],
                'values' => [$pendientes, $completadas, $vencidas],
                'total'  => $totalActividades,
            ],
            'progresoPlanes' => [
                'labels'        => $labelsProgreso,
                'values'        => $valuesProgreso,
                'planesActivos' => count($labelsProgreso),
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $planes
     * @param array<string, int>               $diagnosticos
     *
     * @return array<string, mixed>
     */
    private function construirResumen(array $planes, array $diagnosticos): array
    {
        $totalPlanesConActividades = 0;
        $totalActividadesCompletadas = 0;

        foreach ($planes as $plan) {
            $totalActividades = (int) ($plan['total_actividades'] ?? 0);
            if ($totalActividades > 0) {
                $totalPlanesConActividades++;
                $totalActividadesCompletadas += (int) ($plan['total_completadas'] ?? 0);
            }
        }

        $promedio = 0.0;
        if ($totalPlanesConActividades > 0) {
            $promedio = $totalActividadesCompletadas / $totalPlanesConActividades;
        }

        return [
            'diagnosticosTotales'                 => $diagnosticos['totales'] ?? 0,
            'promedioActividadesCompletadasPorPlan' => $promedio,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function obtenerActividadesProximas(int $pacienteId): array
    {
        $rows = $this->db->table('actividades AS a')
            ->select([
                'a.id',
                'a.plan_id',
                'a.nombre',
                'a.descripcion',
                'a.fecha_inicio',
                'a.fecha_fin',
                'a.estado_id',
                'estado_actividad.slug AS estado_slug',
                'estado_actividad.nombre AS estado_nombre',
                'pc.nombre AS plan_nombre',
            ])
            ->join('planes_cuidado AS pc', 'pc.id = a.plan_id', 'inner')
            ->join('diagnosticos AS d', 'd.id = pc.diagnostico_id', 'inner')
            ->join('estado_actividad', 'estado_actividad.id = a.estado_id', 'left')
            ->where('d.destinatario_user_id', $pacienteId)
            ->where('a.deleted_at', null)
            ->where('pc.deleted_at', null)
            ->where('d.deleted_at', null)
            ->where('estado_actividad.slug', self::ESTADO_PENDIENTE)
            ->where('a.fecha_fin >=', $this->hoy->format('Y-m-d'))
            ->orderBy('a.fecha_inicio', 'ASC')
            ->orderBy('a.id', 'ASC')
            ->limit(5)
            ->get()
            ->getResultArray();

        $items = array_map(fn (array $row): array => $this->formatearActividadProxima($row), $rows);

        return [
            'items' => $items,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function obtenerAvisos(int $pacienteId): array
    {
        $total = $this->db->table('actividades AS a')
            ->join('planes_cuidado AS pc', 'pc.id = a.plan_id', 'inner')
            ->join('diagnosticos AS d', 'd.id = pc.diagnostico_id', 'inner')
            ->join('estado_actividad', 'estado_actividad.id = a.estado_id', 'left')
            ->where('d.destinatario_user_id', $pacienteId)
            ->where('a.deleted_at', null)
            ->where('pc.deleted_at', null)
            ->where('d.deleted_at', null)
            ->where('a.fecha_inicio', $this->hoy->format('Y-m-d'))
            ->where('estado_actividad.slug', self::ESTADO_PENDIENTE)
            ->countAllResults();

        return [
            'actividadesHoy' => (int) $total,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function obtenerDiagnosticoStats(int $pacienteId): array
    {
        $totales = $this->db->table('diagnosticos')
            ->selectCount('id', 'total')
            ->where('destinatario_user_id', $pacienteId)
            ->where('deleted_at', null)
            ->get()
            ->getFirstRow('array');

        $activos = $this->db->table('diagnosticos AS d')
            ->select('COUNT(DISTINCT d.id) AS total', false)
            ->join('planes_cuidado AS pc', 'pc.diagnostico_id = d.id', 'inner')
            ->where('d.destinatario_user_id', $pacienteId)
            ->where('d.deleted_at', null)
            ->where('pc.deleted_at', null)
            ->where('pc.fecha_inicio <=', $this->hoy->format('Y-m-d'))
            ->where('pc.fecha_fin >=', $this->hoy->format('Y-m-d'))
            ->groupStart()
                ->where('pc.estado', null)
                ->orWhereNotIn('pc.estado', self::ESTADOS_PLAN_FINALIZADOS)
            ->groupEnd()
            ->get()
            ->getFirstRow('array');

        return [
            'totales' => (int) ($totales['total'] ?? 0),
            'activos' => (int) ($activos['total'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function formatearActividadProxima(array $row): array
    {
        $fechaInicio = $this->crearFecha($row['fecha_inicio'] ?? null);
        $fechaFin    = $this->crearFecha($row['fecha_fin'] ?? null);
        $estadoSlug  = strtolower((string) ($row['estado_slug'] ?? ''));

        $dentroDeRango = $this->estaDentroDeRango($fechaInicio, $fechaFin, $this->hoy);
        $puedeMarcar   = $estadoSlug === self::ESTADO_PENDIENTE && $dentroDeRango;

        $bloqueoMotivo = null;
        if ($estadoSlug === self::ESTADO_PENDIENTE && ! $dentroDeRango) {
            if ($fechaInicio !== null && $this->hoy < $fechaInicio) {
                $bloqueoMotivo = sprintf('Disponible a partir del %s.', $fechaInicio->format('d/m/Y'));
            } elseif ($fechaFin !== null && $this->hoy > $fechaFin) {
                $bloqueoMotivo = sprintf('La actividad venció el %s.', $fechaFin->format('d/m/Y'));
            }
        }

        $esHoy = $fechaInicio !== null && $fechaInicio->format('Y-m-d') === $this->hoy->format('Y-m-d');

        $planNombre = trim((string) ($row['plan_nombre'] ?? ''));
        if ($planNombre === '') {
            $planNombre = 'Plan sin nombre';
        }

        return [
            'id'            => (int) ($row['id'] ?? 0),
            'plan_id'       => (int) ($row['plan_id'] ?? 0),
            'plan_nombre'   => $planNombre,
            'nombre'        => (string) ($row['nombre'] ?? ''),
            'descripcion'   => (string) ($row['descripcion'] ?? ''),
            'fecha_inicio'  => $row['fecha_inicio'] ?? null,
            'fecha_fin'     => $row['fecha_fin'] ?? null,
            'estado_slug'   => $estadoSlug,
            'estado_nombre' => $row['estado_nombre'] ?? ucfirst($estadoSlug),
            'puede_marcar'  => $puedeMarcar,
            'bloqueo_motivo'=> $bloqueoMotivo,
            'es_hoy'        => $esHoy,
        ];
    }

    private function crearFecha(?string $fecha): ?\DateTimeImmutable
    {
        if ($fecha === null || $fecha === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($fecha);
        } catch (\Throwable) {
            return null;
        }
    }

    private function estaDentroDeRango(?\DateTimeImmutable $inicio, ?\DateTimeImmutable $fin, \DateTimeImmutable $hoy): bool
    {
        if ($inicio !== null && $hoy < $inicio) {
            return false;
        }

        if ($fin !== null && $hoy > $fin) {
            return false;
        }

        return true;
    }
}
