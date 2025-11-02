<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\ConnectionInterface;
use Config\Database;

class MedicoDashboardService
{
    private const ESTADOS_PLAN_FINALIZADOS = [
        'finalizado',
        'terminado',
        'completado',
        'cerrado',
    ];

    private const MESES_CORTOS = [
        1 => 'Ene',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Abr',
        5 => 'May',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Ago',
        9 => 'Sep',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Dic',
    ];

    private ConnectionInterface $db;

    private \DateTimeImmutable $hoy;

    public function __construct(?ConnectionInterface $db = null)
    {
        $this->db  = $db ?? Database::connect();
        $this->hoy = new \DateTimeImmutable('today');
    }

    /**
     * @return array<string, mixed>
     */
    public function obtenerDashboard(int $medicoId): array
    {
        $diagnosticosBase = $this->obtenerDiagnosticosBase($medicoId);

        return [
            'kpis'               => $this->calcularKpis($medicoId, $diagnosticosBase),
            'charts'             => $this->construirGraficos($medicoId),
            'diagnosticosRecientes' => $this->obtenerDiagnosticosRecientes($medicoId),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function obtenerDiagnosticosRecientes(int $medicoId): array
    {
        return $this->db->table('diagnosticos d')
            ->select([
                'd.id',
                'd.descripcion',
                'd.fecha_creacion',
                'paciente.nombre AS paciente_nombre',
                'paciente.apellido AS paciente_apellido',
                'tipo.nombre AS tipo_nombre',
            ])
            ->join('users AS paciente', 'paciente.id = d.destinatario_user_id', 'left')
            ->join('tipos_diagnostico AS tipo', 'tipo.id = d.tipo_diagnostico_id', 'left')
            ->where('d.autor_user_id', $medicoId)
            ->where('d.deleted_at', null)
            ->orderBy('d.fecha_creacion', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function construirGraficos(int $medicoId): array
    {
        $diagnosticosPorTipo = $this->db->table('diagnosticos d')
            ->select('tipo.nombre AS tipo_nombre, COUNT(*) AS total', false)
            ->join('tipos_diagnostico AS tipo', 'tipo.id = d.tipo_diagnostico_id', 'left')
            ->where('d.autor_user_id', $medicoId)
            ->where('d.deleted_at', null)
            ->groupBy('tipo.nombre')
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();

        $planesPorEstado = $this->db->table('planes_cuidado')
            ->select('estado, COUNT(*) AS total', false)
            ->where('creador_user_id', $medicoId)
            ->where('planes_cuidado.deleted_at', null)
            ->groupBy('estado')
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();

        $inicioSerie = $this->hoy
            ->modify('first day of this month')
            ->modify('-5 months');

        $diagnosticosPorMesRows = $this->db->table('diagnosticos')
            ->select("DATE_FORMAT(fecha_creacion, '%Y-%m') AS mes, COUNT(*) AS total", false)
            ->where('autor_user_id', $medicoId)
            ->where('diagnosticos.deleted_at', null)
            ->where('fecha_creacion >=', $inicioSerie->format('Y-m-d'))
            ->groupBy("DATE_FORMAT(fecha_creacion, '%Y-%m')")
            ->orderBy("mes", 'ASC')
            ->get()
            ->getResultArray();

        $mapDiagnosticosMes = [];
        foreach ($diagnosticosPorMesRows as $row) {
            $mes = (string) ($row['mes'] ?? '');
            $mapDiagnosticosMes[$mes] = (int) ($row['total'] ?? 0);
        }

        $labelsMes    = [];
        $valuesMes    = [];
        $mesEnCurso   = $inicioSerie;

        for ($i = 0; $i < 6; $i++) {
            $claveMes   = $mesEnCurso->format('Y-m');
            $labelsMes[] = $this->formatearMesCorto($mesEnCurso);
            $valuesMes[] = $mapDiagnosticosMes[$claveMes] ?? 0;
            $mesEnCurso  = $mesEnCurso->modify('+1 month');
        }

        $labelsTipo  = [];
        $valuesTipo  = [];
        foreach ($diagnosticosPorTipo as $row) {
            $label       = trim((string) ($row['tipo_nombre'] ?? ''));
            $labelsTipo[] = $label !== '' ? $label : 'Sin clasificación';
            $valuesTipo[] = (int) ($row['total'] ?? 0);
        }

        $labelsEstado = [];
        $valuesEstado = [];
        foreach ($planesPorEstado as $row) {
            $estadoCrudo = trim(strtolower((string) ($row['estado'] ?? '')));
            if ($estadoCrudo === '') {
                $estadoLabel = 'Sin estado';
            } elseif (in_array($estadoCrudo, self::ESTADOS_PLAN_FINALIZADOS, true)) {
                $estadoLabel = 'Finalizado';
            } else {
                $estadoLabel = ucfirst($estadoCrudo);
            }

            $labelsEstado[] = $estadoLabel;
            $valuesEstado[] = (int) ($row['total'] ?? 0);
        }

        return [
            'diagnosticosPorTipo' => [
                'labels' => $labelsTipo,
                'values' => $valuesTipo,
            ],
            'planesPorEstado' => [
                'labels' => $labelsEstado,
                'values' => $valuesEstado,
            ],
            'diagnosticosPorMes' => [
                'labels' => $labelsMes,
                'values' => $valuesMes,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function calcularKpis(int $medicoId, array $diagnosticosBase): array
    {
        $totalDiagnosticos = count($diagnosticosBase);

        $pacientesDiagnosticados = [];
        $pacientesBajoCuidado    = [];
        $diagnosticosActivos     = 0;

        foreach ($diagnosticosBase as $diagnostico) {
            $pacienteId = (int) ($diagnostico['paciente_id'] ?? 0);
            if ($pacienteId > 0) {
                $pacientesDiagnosticados[$pacienteId] = true;
            }

            $totalPlanes    = (int) ($diagnostico['total_planes'] ?? 0);
            $planActivo     = (bool) ($diagnostico['tiene_plan_activo'] ?? false);

            if ($totalPlanes === 0 || $planActivo) {
                $diagnosticosActivos++;
            }

            if ($planActivo && $pacienteId > 0) {
                $pacientesBajoCuidado[$pacienteId] = true;
            }
        }

        $planesRows = $this->db->table('planes_cuidado')
            ->select(['id', 'fecha_inicio', 'fecha_fin', 'estado'])
            ->where('creador_user_id', $medicoId)
            ->where('planes_cuidado.deleted_at', null)
            ->get()
            ->getResultArray();

        $totalPlanes         = count($planesRows);
        $planesFinalizados   = 0;
        $duracionesValidas   = 0;
        $duracionDiasAcum    = 0;

        foreach ($planesRows as $plan) {
            $estado   = $plan['estado'] ?? null;
            $fechaFin = $plan['fecha_fin'] ?? null;

            if ($this->planEstaFinalizado($estado, $fechaFin)) {
                $planesFinalizados++;
            }

            $fechaInicio = $plan['fecha_inicio'] ?? null;
            if ($fechaInicio !== null && $fechaInicio !== '' && $fechaFin !== null && $fechaFin !== '') {
                try {
                    $inicio = new \DateTimeImmutable($fechaInicio);
                    $fin    = new \DateTimeImmutable($fechaFin);

                    if ($fin < $inicio) {
                        continue;
                    }

                    $dias = (int) $inicio->diff($fin)->format('%a');
                    $duracionDiasAcum += $dias;
                    $duracionesValidas++;
                } catch (\Throwable $exception) {
                    continue;
                }
            }
        }

        $actividadesRows = $this->db->table('actividades AS a')
            ->select(['a.validado', 'ea.slug AS estado_slug'])
            ->join('planes_cuidado AS pc', 'pc.id = a.plan_id AND pc.deleted_at IS NULL', 'inner')
            ->join('estado_actividad AS ea', 'ea.id = a.estado_id', 'left')
            ->where('pc.creador_user_id', $medicoId)
            ->where('a.deleted_at', null)
            ->get()
            ->getResultArray();

        $totalActividades        = count($actividadesRows);
        $actividadesValidadas    = 0;
        $actividadesCompletadas  = 0;

        foreach ($actividadesRows as $actividad) {
            if ($this->esValorVerdadero($actividad['validado'] ?? null)) {
                $actividadesValidadas++;
            }

            $slug = strtolower(trim((string) ($actividad['estado_slug'] ?? '')));
            if ($slug === 'completada') {
                $actividadesCompletadas++;
            }
        }

        $promedioActividadesPorPlan = $totalPlanes > 0
            ? round($totalActividades / $totalPlanes, 1)
            : 0.0;

        $porcentajeActividadesValidadas = $totalActividades > 0
            ? round(($actividadesValidadas / $totalActividades) * 100, 1)
            : 0.0;

        $porcentajePlanesFinalizados = $totalPlanes > 0
            ? round(($planesFinalizados / $totalPlanes) * 100, 1)
            : 0.0;

        $porcentajeAdherencia = $totalActividades > 0
            ? round(($actividadesCompletadas / $totalActividades) * 100, 1)
            : 0.0;

        $duracionPromedio = $duracionesValidas > 0
            ? round($duracionDiasAcum / $duracionesValidas, 1)
            : 0.0;

        return [
            'totalDiagnosticos'            => $totalDiagnosticos,
            'pacientesDiagnosticados'      => count($pacientesDiagnosticados),
            'diagnosticosActivos'          => $diagnosticosActivos,
            'planesCreados'                => $totalPlanes,
            'planesFinalizados'            => [
                'total'      => $planesFinalizados,
                'porcentaje' => $porcentajePlanesFinalizados,
            ],
            'pacientesBajoCuidado'         => count($pacientesBajoCuidado),
            'promedioActividadesPorPlan'   => $promedioActividadesPorPlan,
            'actividadesValidadas'         => [
                'total'      => $actividadesValidadas,
                'porcentaje' => $porcentajeActividadesValidadas,
                'totales'    => $totalActividades,
            ],
            'duracionPromedioPlanes'       => $duracionPromedio,
            'adherenciaPacientes'          => $porcentajeAdherencia,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function obtenerDiagnosticosBase(int $medicoId): array
    {
        $rows = $this->db->table('diagnosticos AS d')
            ->select([
                'd.id AS diagnostico_id',
                'd.destinatario_user_id AS paciente_id',
                'pc.id AS plan_id',
                'pc.fecha_fin',
                'pc.estado',
            ])
            ->join('planes_cuidado AS pc', 'pc.diagnostico_id = d.id AND pc.deleted_at IS NULL', 'left')
            ->where('d.autor_user_id', $medicoId)
            ->where('d.deleted_at', null)
            ->orderBy('d.id', 'ASC')
            ->get()
            ->getResultArray();

        $diagnosticos = [];
        foreach ($rows as $row) {
            $diagnosticoId = (int) ($row['diagnostico_id'] ?? 0);
            if ($diagnosticoId === 0) {
                continue;
            }

            if (! isset($diagnosticos[$diagnosticoId])) {
                $diagnosticos[$diagnosticoId] = [
                    'paciente_id'        => (int) ($row['paciente_id'] ?? 0),
                    'total_planes'       => 0,
                    'tiene_plan_activo'  => false,
                ];
            }

            if ($row['plan_id'] !== null) {
                $diagnosticos[$diagnosticoId]['total_planes']++;

                if ($this->planEstaActivo($row['estado'] ?? null, $row['fecha_fin'] ?? null)) {
                    $diagnosticos[$diagnosticoId]['tiene_plan_activo'] = true;
                }
            }
        }

        return $diagnosticos;
    }

    private function planEstaActivo(?string $estado, ?string $fechaFin): bool
    {
        return ! $this->planEstaFinalizado($estado, $fechaFin);
    }

    private function planEstaFinalizado(?string $estado, ?string $fechaFin): bool
    {
        $estadoNormalizado = trim(strtolower((string) $estado));

        if ($estadoNormalizado !== '' && in_array($estadoNormalizado, self::ESTADOS_PLAN_FINALIZADOS, true)) {
            return true;
        }

        if ($fechaFin === null || $fechaFin === '') {
            return false;
        }

        try {
            $fin = new \DateTimeImmutable($fechaFin);
        } catch (\Throwable $exception) {
            return false;
        }

        return $fin < $this->hoy;
    }

    private function esValorVerdadero($valor): bool
    {
        if ($valor === null || $valor === '') {
            return false;
        }

        return filter_var($valor, FILTER_VALIDATE_BOOLEAN) === true;
    }

    private function formatearMesCorto(\DateTimeImmutable $fecha): string
    {
        $indice = (int) $fecha->format('n');
        $mes    = self::MESES_CORTOS[$indice] ?? $fecha->format('M');

        return $mes . ' ' . $fecha->format('Y');
    }
}
