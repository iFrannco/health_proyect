<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\UserModel;
use CodeIgniter\Database\ConnectionInterface;
use Config\Database;
use DateInterval;
use DateTimeImmutable;
use App\Services\PlanEstadoService;

class AdminDashboardService
{
    private const ROL_LABELS = [
        UserModel::ROLE_PACIENTE => 'Pacientes',
        UserModel::ROLE_MEDICO   => 'Médicos',
        UserModel::ROLE_ADMIN    => 'Administradores',
    ];

    private const PLAN_LABELS = [
        'en_curso'    => 'En curso',
        'sin_iniciar' => 'Sin iniciar',
        'finalizado'  => 'Finalizados',
    ];

    private const ESTADO_ACTIVIDAD_COMPLETADA = 'completada';

    private ConnectionInterface $db;

    private DateTimeImmutable $hoy;

    private DateTimeImmutable $ventanaInicio;

    private string $periodoLabel = 'Últimos 30 días';

    public function __construct(?ConnectionInterface $db = null)
    {
        $this->db = $db ?? Database::connect();
        $this->hoy = new DateTimeImmutable('today');
        $this->ventanaInicio = $this->hoy->sub(new DateInterval('P30D'))->setTime(0, 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function obtenerDashboard(): array
    {
        $usuarios = $this->obtenerUsuariosPorRol();
        $planes   = $this->obtenerResumenPlanes();
        $comparativa = $this->obtenerComparativaMedicos();

        return [
            'periodoLabel'   => $this->periodoLabel,
            'kpisUsuarios'   => $usuarios['resumen'] ?? [],
            'kpisClinicos'   => [
                'diagnosticos30'         => $this->contarDiagnosticosUltimos30Dias(),
                'planes30'               => $planes['planesCreados30'] ?? 0,
                'actividadesCompletadas' => $this->contarActividadesCompletadas(),
                'planesActivos'          => $planes['planesActivos'] ?? 0,
            ],
            'graficos'       => [
                'usuariosPorRol'  => $usuarios['grafico'] ?? ['labels' => [], 'values' => [], 'total' => 0],
                'planesPorEstado' => $planes['grafico'] ?? ['labels' => [], 'values' => [], 'total' => 0],
            ],
            'comparativaMedicos' => $comparativa,
            'resumenPacientes'   => [
                'sinDiagnostico' => $this->contarPacientesSinDiagnostico(),
                'conPlanActivo'  => $planes['pacientesPlanActivo'] ?? 0,
            ],
        ];
    }

    /**
     * @return array{
     *     resumen: array<string, int>,
     *     grafico: array{labels: list<string>, values: list<int>, total: int}
     * }
     */
    private function obtenerUsuariosPorRol(): array
    {
        $rows = $this->db->table('users AS u')
            ->select([
                'r.slug AS rol',
                'COUNT(*) AS total',
                'SUM(CASE WHEN u.activo = 0 THEN 1 ELSE 0 END) AS inactivos',
            ])
            ->join('roles AS r', 'r.id = u.role_id', 'inner')
            ->where('u.deleted_at', null)
            ->groupBy('r.slug')
            ->get()
            ->getResultArray();

        $resumen = [
            'pacientes'       => 0,
            'medicos'         => 0,
            'administradores' => 0,
            'inactivos'       => 0,
        ];

        $conteoPorRol = [];

        foreach ($rows as $row) {
            $slug      = strtolower((string) ($row['rol'] ?? ''));
            $total     = (int) ($row['total'] ?? 0);
            $inactivos = (int) ($row['inactivos'] ?? 0);

            $resumen['inactivos'] += $inactivos;

            if ($slug === UserModel::ROLE_PACIENTE) {
                $resumen['pacientes'] = $total;
            } elseif ($slug === UserModel::ROLE_MEDICO) {
                $resumen['medicos'] = $total;
            } elseif ($slug === UserModel::ROLE_ADMIN) {
                $resumen['administradores'] = $total;
            }

            $conteoPorRol[$slug] = $total;
        }

        $labels = [];
        $values = [];
        $totalUsuarios = 0;

        foreach (self::ROL_LABELS as $slug => $label) {
            $valor = (int) ($conteoPorRol[$slug] ?? 0);
            $labels[] = $label;
            $values[] = $valor;
            $totalUsuarios += $valor;
        }

        return [
            'resumen' => $resumen,
            'grafico' => [
                'labels' => $labels,
                'values' => $values,
                'total'  => $totalUsuarios,
            ],
        ];
    }

    /**
     * @return array{
     *     grafico: array{labels: list<string>, values: list<int>, total: int},
     *     planesActivos: int,
     *     planesCreados30: int,
     *     pacientesPlanActivo: int
     * }
     */
    private function obtenerResumenPlanes(): array
    {
        $rows = $this->db->table('planes_cuidado AS pc')
            ->select([
                'pc.id',
                'pc.estado',
                'pc.fecha_inicio',
                'pc.fecha_fin',
                'pc.fecha_creacion',
                'pc.creador_user_id',
                'd.destinatario_user_id AS paciente_id',
            ])
            ->join('diagnosticos AS d', 'd.id = pc.diagnostico_id AND d.deleted_at IS NULL', 'left')
            ->where('pc.deleted_at', null)
            ->get()
            ->getResultArray();

        $conteos = [
            'en_curso'    => 0,
            'sin_iniciar' => 0,
            'finalizado'  => 0,
        ];

        $pacientesConPlanActivo = [];
        $planesActivos = 0;
        $planesCreados30 = 0;
        $fechaLimite = $this->ventanaInicio->format('Y-m-d H:i:s');

        foreach ($rows as $plan) {
            $categoria = $this->clasificarPlan($plan);
            $conteos[$categoria]++;

            if ($categoria === 'en_curso') {
                $planesActivos++;
                $pacienteId = (int) ($plan['paciente_id'] ?? 0);
                if ($pacienteId > 0) {
                    $pacientesConPlanActivo[$pacienteId] = true;
                }
            }

            $fechaCreacion = $plan['fecha_creacion'] ?? null;
            if ($fechaCreacion !== null && $fechaCreacion !== '' && $fechaCreacion >= $fechaLimite) {
                $planesCreados30++;
            }
        }

        $labels = [];
        $values = [];
        $total = 0;

        foreach (self::PLAN_LABELS as $clave => $label) {
            $valor = (int) ($conteos[$clave] ?? 0);
            $labels[] = $label;
            $values[] = $valor;
            $total += $valor;
        }

        return [
            'grafico' => [
                'labels' => $labels,
                'values' => $values,
                'total'  => $total,
            ],
            'planesActivos'       => $planesActivos,
            'planesCreados30'     => $planesCreados30,
            'pacientesPlanActivo' => count($pacientesConPlanActivo),
        ];
    }

    /**
     * @param array<string, mixed> $plan
     */
    private function clasificarPlan(array $plan): string
    {
        $estadoPlan = PlanEstadoService::calcular(
            $plan['estado'] ?? null,
            $plan['fecha_inicio'] ?? null,
            $plan['fecha_fin'] ?? null,
            $this->hoy
        );

        return match ($estadoPlan['estado']) {
            PlanEstadoService::ESTADO_FINALIZADO  => 'finalizado',
            PlanEstadoService::ESTADO_SIN_INICIAR => 'sin_iniciar',
            default                               => 'en_curso',
        };
    }

    private function contarDiagnosticosUltimos30Dias(): int
    {
        $limite = $this->ventanaInicio->format('Y-m-d H:i:s');

        return (int) $this->db->table('diagnosticos')
            ->where('deleted_at', null)
            ->where('fecha_creacion >=', $limite)
            ->countAllResults();
    }

    private function contarActividadesCompletadas(): int
    {
        $limite = $this->ventanaInicio->format('Y-m-d H:i:s');

        return (int) $this->db->table('actividades AS a')
            ->join('estado_actividad AS ea', 'ea.id = a.estado_id', 'inner')
            ->where('a.deleted_at', null)
            ->where('ea.slug', self::ESTADO_ACTIVIDAD_COMPLETADA)
            ->groupStart()
                ->where('a.paciente_completada_en >=', $limite)
                ->orWhere('a.fecha_validacion >=', $limite)
                ->orWhere('a.updated_at >=', $limite)
            ->groupEnd()
            ->countAllResults();
    }

    private function contarPacientesSinDiagnostico(): int
    {
        $row = $this->db->table('users AS u')
            ->select('COUNT(DISTINCT u.id) AS total', false)
            ->join('roles AS r', 'r.id = u.role_id', 'inner')
            ->join('diagnosticos AS d', 'd.destinatario_user_id = u.id AND d.deleted_at IS NULL', 'left')
            ->where('u.deleted_at', null)
            ->where('u.activo', 1)
            ->where('r.slug', UserModel::ROLE_PACIENTE)
            ->where('d.id', null)
            ->get()
            ->getFirstRow('array');

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @return array{
     *     labels: list<string>,
     *     diagnosticos: list<int>,
     *     planes: list<int>,
     *     hayMedicos: bool,
     *     hayDatos: bool,
     *     totalDiag: int,
     *     totalPlanes: int
     * }
     */
    private function obtenerComparativaMedicos(): array
    {
        $medicos = $this->db->table('users AS u')
            ->select(['u.id', 'u.nombre', 'u.apellido'])
            ->join('roles AS r', 'r.id = u.role_id', 'inner')
            ->where('u.deleted_at', null)
            ->where('u.activo', 1)
            ->where('r.slug', UserModel::ROLE_MEDICO)
            ->orderBy('u.apellido', 'ASC')
            ->orderBy('u.nombre', 'ASC')
            ->get()
            ->getResultArray();

        $diagnosticosPorMedico = $this->obtenerDiagnosticosPorMedico();
        $planesPorMedico       = $this->obtenerPlanesPorMedico();

        $labels = [];
        $diagnosticos = [];
        $planes = [];
        $totalDiagnosticos = 0;
        $totalPlanes = 0;

        foreach ($medicos as $medico) {
            $medicoId = (int) ($medico['id'] ?? 0);
            $label = trim((string) ($medico['apellido'] ?? '') . ' ' . ($medico['nombre'] ?? ''));
            if ($label === '') {
                $label = sprintf('Médico #%d', $medicoId);
            }

            $diag = (int) ($diagnosticosPorMedico[$medicoId] ?? 0);
            $plan = (int) ($planesPorMedico[$medicoId] ?? 0);

            $labels[]       = $label;
            $diagnosticos[] = $diag;
            $planes[]       = $plan;

            $totalDiagnosticos += $diag;
            $totalPlanes       += $plan;
        }

        return [
            'labels'        => $labels,
            'diagnosticos'  => $diagnosticos,
            'planes'        => $planes,
            'hayMedicos'    => count($labels) > 0,
            'hayDatos'      => ($totalDiagnosticos + $totalPlanes) > 0,
            'totalDiag'     => $totalDiagnosticos,
            'totalPlanes'   => $totalPlanes,
        ];
    }

    /**
     * @return array<int, int>
     */
    private function obtenerDiagnosticosPorMedico(): array
    {
        $limite = $this->ventanaInicio->format('Y-m-d H:i:s');

        $rows = $this->db->table('diagnosticos AS d')
            ->select(['d.autor_user_id AS medico_id', 'COUNT(*) AS total'], false)
            ->join('users AS u', 'u.id = d.autor_user_id AND u.deleted_at IS NULL', 'inner')
            ->join('roles AS r', 'r.id = u.role_id', 'inner')
            ->where('r.slug', UserModel::ROLE_MEDICO)
            ->where('d.deleted_at', null)
            ->where('d.fecha_creacion >=', $limite)
            ->groupBy('d.autor_user_id')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $medicoId = (int) ($row['medico_id'] ?? 0);
            if ($medicoId > 0) {
                $map[$medicoId] = (int) ($row['total'] ?? 0);
            }
        }

        return $map;
    }

    /**
     * @return array<int, int>
     */
    private function obtenerPlanesPorMedico(): array
    {
        $limite = $this->ventanaInicio->format('Y-m-d H:i:s');

        $rows = $this->db->table('planes_cuidado AS pc')
            ->select(['pc.creador_user_id AS medico_id', 'COUNT(*) AS total'], false)
            ->join('users AS u', 'u.id = pc.creador_user_id AND u.deleted_at IS NULL', 'inner')
            ->join('roles AS r', 'r.id = u.role_id', 'inner')
            ->where('r.slug', UserModel::ROLE_MEDICO)
            ->where('pc.deleted_at', null)
            ->where('pc.creador_user_id IS NOT NULL', null, false)
            ->where('pc.fecha_creacion >=', $limite)
            ->groupBy('pc.creador_user_id')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $medicoId = (int) ($row['medico_id'] ?? 0);
            if ($medicoId > 0) {
                $map[$medicoId] = (int) ($row['total'] ?? 0);
            }
        }

        return $map;
    }

    private function crearFecha(?string $fecha): ?DateTimeImmutable
    {
        if ($fecha === null || trim($fecha) === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($fecha);
        } catch (\Throwable) {
            return null;
        }
    }
}
