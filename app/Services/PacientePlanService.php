<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\PageForbiddenException;
use App\Models\EstadoActividadModel;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Database;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class PacientePlanService
{
    private const FILTRO_SIN_INICIAR = 'sin_iniciar';
    private const FILTRO_EN_CURSO    = 'en_curso';
    private const FILTRO_FINALIZADOS = 'finalizado';
    private const FILTRO_TODOS       = 'todos';

    private const ESTADO_PENDIENTE  = 'pendiente';
    private const ESTADO_COMPLETADA = 'completada';
    private const ESTADO_VENCIDA    = 'vencida';

    private ConnectionInterface $db;

    private EstadoActividadModel $estadoActividadModel;

    /**
     * @var array<string, int>
     */
    private array $estadoIds;

    public function __construct(?ConnectionInterface $db = null, ?EstadoActividadModel $estadoModel = null)
    {
        $this->db                   = $db ?? Database::connect();
        $this->estadoActividadModel = $estadoModel ?? new EstadoActividadModel();
        $this->estadoIds            = $this->mapearEstadosPorSlug();
    }

    /**
     * @return array{
     *     filtro: string,
     *     planes: array<int, array<string, mixed>>,
     *     conteos: array<string, int>
     * }
     */
    public function obtenerPlanes(int $pacienteId, string $filtro): array
    {
        $hoy        = new DateTimeImmutable('today');
        $planesBase = $this->obtenerPlanesBase($pacienteId);

        $agrupados = [
            self::FILTRO_SIN_INICIAR => [],
            self::FILTRO_EN_CURSO    => [],
            self::FILTRO_FINALIZADOS => [],
            self::FILTRO_TODOS       => [],
        ];

        foreach ($planesBase as $plan) {
            $planFormateado = $this->formatearPlanResumen($plan, $hoy);

            $agrupados[self::FILTRO_TODOS][] = $planFormateado;

            switch ($planFormateado['estado_categoria']) {
                case PlanEstadoService::ESTADO_SIN_INICIAR:
                    $agrupados[self::FILTRO_SIN_INICIAR][] = $planFormateado;
                    break;
                case PlanEstadoService::ESTADO_FINALIZADO:
                    $agrupados[self::FILTRO_FINALIZADOS][] = $planFormateado;
                    break;
                default:
                    $agrupados[self::FILTRO_EN_CURSO][] = $planFormateado;
                    break;
            }
        }

        $conteos = [
            self::FILTRO_SIN_INICIAR => count($agrupados[self::FILTRO_SIN_INICIAR]),
            self::FILTRO_EN_CURSO    => count($agrupados[self::FILTRO_EN_CURSO]),
            self::FILTRO_FINALIZADOS => count($agrupados[self::FILTRO_FINALIZADOS]),
            self::FILTRO_TODOS       => count($agrupados[self::FILTRO_TODOS]),
        ];

        $filtroNormalizado = $this->normalizarFiltro($filtro);

        return [
            'filtro'  => $filtroNormalizado,
            'planes'  => $agrupados[$filtroNormalizado],
            'conteos' => $conteos,
        ];
    }

    /**
     * @return array{
     *     plan: array<string, mixed>,
     *     metricas: array<string, int>,
     *     actividades: array<int, array<string, mixed>>
     * }
     */
    public function obtenerPlanDetalle(int $pacienteId, int $planId): array
    {
        $hoy = new DateTimeImmutable('today');

        $plan = $this->db->table('planes_cuidado AS pc')
            ->select([
                'pc.id',
                'pc.nombre',
                'pc.descripcion',
                'pc.fecha_creacion',
                'pc.fecha_inicio',
                'pc.fecha_fin',
                'pc.estado',
                'pc.plan_estandar_id',
                'pc.creador_user_id AS medico_id',
                'medico.nombre AS medico_nombre',
                'medico.apellido AS medico_apellido',
                'diagnosticos.descripcion AS diagnostico_descripcion',
                'diagnosticos.id AS diagnostico_id',
            ])
            ->join('diagnosticos', 'diagnosticos.id = pc.diagnostico_id', 'inner')
            ->join('users AS medico', 'medico.id = pc.creador_user_id AND medico.deleted_at IS NULL', 'left')
            ->where('pc.id', $planId)
            ->where('diagnosticos.destinatario_user_id', $pacienteId)
            ->where('pc.deleted_at', null)
            ->where('diagnosticos.deleted_at', null)
            ->get()
            ->getFirstRow('array');

        if ($plan === null) {
            throw PageForbiddenException::forPageForbidden('No se encontró el plan solicitado.');
        }

        $estadoPlan = PlanEstadoService::calcular(
            $plan['estado'] ?? null,
            $plan['fecha_inicio'] ?? null,
            $plan['fecha_fin'] ?? null,
            $hoy
        );

        $actividades = $this->db->table('actividades AS a')
            ->select([
                'a.id',
                'a.plan_id',
                'a.nombre',
                'a.descripcion',
                'a.fecha_inicio',
                'a.fecha_fin',
                'a.estado_id',
                'a.validado',
                'a.paciente_comentario',
                'a.paciente_completada_en',
                'a.fecha_validacion',
                'estado_actividad.slug AS estado_slug',
                'estado_actividad.nombre AS estado_nombre',
                'a.categoria_actividad_id',
                'categoria_actividad.nombre AS categoria_nombre',
                'categoria_actividad.color_hex AS categoria_color',
            ])
            ->join('estado_actividad', 'estado_actividad.id = a.estado_id', 'left')
            ->join('categoria_actividad', 'categoria_actividad.id = a.categoria_actividad_id', 'left')
            ->where('a.plan_id', $planId)
            ->where('a.deleted_at', null)
            ->orderBy('a.fecha_inicio', 'ASC')
            ->orderBy('a.id', 'ASC')
            ->get()
            ->getResultArray();

        $actividadesFormateadas = array_map(
            fn (array $actividad): array => $this->formatearActividad($actividad, $hoy, $estadoPlan['estado'] === PlanEstadoService::ESTADO_FINALIZADO),
            $actividades
        );

        $metricas = $this->calcularMetricasDesdeActividades($actividadesFormateadas);

        return [
            'plan'        => $this->formatearPlanDetalle($plan, $hoy, $metricas, $estadoPlan),
            'metricas'    => $metricas,
            'actividades' => $actividadesFormateadas,
        ];
    }

    /**
     * @return array{
     *     planId: int,
     *     actividad: array<string, mixed>,
     *     metricas: array<string, int>
     * }
     */
    public function marcarActividad(int $pacienteId, int $actividadId, ?string $comentario): array
    {
        $hoy        = new DateTimeImmutable('today');
        $contexto   = $this->obtenerContextoActividad($pacienteId, $actividadId);
        $comentario = $this->normalizarComentario($comentario);

        $estadoPlan = PlanEstadoService::calcular(
            $contexto['plan_estado'] ?? null,
            $contexto['plan_fecha_inicio'] ?? null,
            $contexto['plan_fecha_fin'] ?? null,
            $hoy
        );

        if ($estadoPlan['estado'] === PlanEstadoService::ESTADO_FINALIZADO) {
            throw new InvalidArgumentException('No se pueden modificar actividades de un plan finalizado.');
        }

        $this->validarPuedeMarcar($contexto, $hoy);

        $actualizado = $this->db->table('actividades')
            ->where('id', $actividadId)
            ->update([
                'estado_id'              => $this->estadoIds[self::ESTADO_COMPLETADA],
                'validado'               => null,
                'fecha_validacion'       => null,
                'paciente_comentario'    => $comentario,
                'paciente_completada_en' => $this->ahora(),
            ]);

        if ($actualizado === false) {
            throw new DatabaseException('No se pudo actualizar la actividad.');
        }

        return [
            'planId'    => (int) $contexto['plan_id'],
            'actividad' => $this->obtenerActividadFormateada($actividadId, $hoy),
            'metricas'  => $this->obtenerMetricasPlan((int) $contexto['plan_id']),
        ];
    }

    /**
     * @return array{
     *     planId: int,
     *     actividad: array<string, mixed>,
     *     metricas: array<string, int>
     * }
     */
    public function desmarcarActividad(int $pacienteId, int $actividadId): array
    {
        $hoy      = new DateTimeImmutable('today');
        $contexto = $this->obtenerContextoActividad($pacienteId, $actividadId);

        $estadoPlan = PlanEstadoService::calcular(
            $contexto['plan_estado'] ?? null,
            $contexto['plan_fecha_inicio'] ?? null,
            $contexto['plan_fecha_fin'] ?? null,
            $hoy
        );

        if ($estadoPlan['estado'] === PlanEstadoService::ESTADO_FINALIZADO) {
            throw new InvalidArgumentException('No se pueden modificar actividades de un plan finalizado.');
        }

        if (($contexto['estado_slug'] ?? null) !== self::ESTADO_COMPLETADA) {
            throw new InvalidArgumentException('Solo se pueden desmarcar actividades completadas.');
        }

        $actualizado = $this->db->table('actividades')
            ->where('id', $actividadId)
            ->update([
                'estado_id'              => $this->estadoIds[self::ESTADO_PENDIENTE],
                'validado'               => null,
                'fecha_validacion'       => null,
                'paciente_comentario'    => null,
                'paciente_completada_en' => null,
            ]);

        if ($actualizado === false) {
            throw new DatabaseException('No se pudo actualizar la actividad.');
        }

        return [
            'planId'    => (int) $contexto['plan_id'],
            'actividad' => $this->obtenerActividadFormateada($actividadId, $hoy),
            'metricas'  => $this->obtenerMetricasPlan((int) $contexto['plan_id']),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function obtenerPlanesBase(int $pacienteId): array
    {
        return $this->db->table('planes_cuidado AS pc')
            ->select([
                'pc.id',
                'pc.nombre',
                'pc.descripcion',
                'pc.fecha_inicio',
                'pc.fecha_fin',
                'pc.fecha_creacion',
                'pc.estado',
                'pc.creador_user_id AS medico_id',
                'medico.nombre AS medico_nombre',
                'medico.apellido AS medico_apellido',
                'diagnosticos.descripcion AS diagnostico_descripcion',
                'COUNT(a.id) AS total_actividades',
                "COALESCE(SUM(CASE WHEN estado_actividad.slug = 'pendiente' THEN 1 ELSE 0 END), 0) AS total_pendientes",
                "COALESCE(SUM(CASE WHEN estado_actividad.slug = 'completada' THEN 1 ELSE 0 END), 0) AS total_completadas",
                "COALESCE(SUM(CASE WHEN estado_actividad.slug = 'vencida' THEN 1 ELSE 0 END), 0) AS total_vencidas",
                "COALESCE(SUM(CASE WHEN a.validado = 1 THEN 1 ELSE 0 END), 0) AS total_validadas",
            ])
            ->join('diagnosticos', 'diagnosticos.id = pc.diagnostico_id', 'inner')
            ->join('users AS medico', 'medico.id = pc.creador_user_id AND medico.deleted_at IS NULL', 'left')
            ->join('actividades AS a', 'a.plan_id = pc.id AND a.deleted_at IS NULL', 'left')
            ->join('estado_actividad', 'estado_actividad.id = a.estado_id', 'left')
            ->where('diagnosticos.destinatario_user_id', $pacienteId)
            ->where('pc.deleted_at', null)
            ->where('diagnosticos.deleted_at', null)
            ->groupBy('pc.id')
            ->orderBy('pc.fecha_inicio', 'DESC')
            ->orderBy('pc.id', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * @param array<string, mixed> $plan
     *
     * @return array<string, mixed>
     */
    private function formatearPlanResumen(array $plan, DateTimeImmutable $hoy): array
    {
        $estadoPlan = PlanEstadoService::calcular(
            $plan['estado'] ?? null,
            $plan['fecha_inicio'] ?? null,
            $plan['fecha_fin'] ?? null,
            $hoy
        );

        $totalActividades = (int) ($plan['total_actividades'] ?? 0);
        $totalCompletadas = (int) ($plan['total_completadas'] ?? 0);
        $totalValidadas   = (int) ($plan['total_validadas'] ?? 0);
        $totalPendientes  = (int) ($plan['total_pendientes'] ?? 0);
        $totalVencidas    = (int) ($plan['total_vencidas'] ?? 0);

        $porcentaje = $totalActividades > 0
            ? (int) round(($totalCompletadas / $totalActividades) * 100)
            : 0;

        $medico = $this->formatearMedico(
            $plan['medico_id'] ?? null,
            $plan['medico_nombre'] ?? null,
            $plan['medico_apellido'] ?? null,
            $plan['medico_especialidad'] ?? null
        );

        return [
            'id'                  => (int) $plan['id'],
            'nombre'              => $plan['nombre'] ?? null,
            'descripcion'         => $plan['descripcion'] ?? null,
            'fecha_inicio'        => $plan['fecha_inicio'] ?? null,
            'fecha_fin'           => $plan['fecha_fin'] ?? null,
            'fecha_creacion'      => $plan['fecha_creacion'] ?? null,
            'estado'              => $estadoPlan['estado'],
            'diagnostico'         => $plan['diagnostico_descripcion'] ?? null,
            'estado_categoria'    => $estadoPlan['estado'],
            'estado_etiqueta'     => $estadoPlan['etiqueta'],
            'total_actividades'   => $totalActividades,
            'total_completadas'   => $totalCompletadas,
            'total_pendientes'    => $totalPendientes,
            'total_vencidas'      => $totalVencidas,
            'total_validadas'     => $totalValidadas,
            'total_pendientes_validacion' => max($totalCompletadas - $totalValidadas, 0),
            'porcentaje_completadas' => $porcentaje,
            'es_vigente'          => $estadoPlan['estado'] === PlanEstadoService::ESTADO_EN_CURSO,
            'es_futuro'           => $estadoPlan['estado'] === PlanEstadoService::ESTADO_SIN_INICIAR,
            'medico_id'           => $medico['id'],
            'medico_nombre'       => $medico['nombre_completo'],
            'medico_especialidad' => $medico['especialidad'],
            'medico_disponible'   => $medico['disponible'],
        ];
    }

    /**
     * @param array<string, mixed> $plan
     * @param array<string, int>   $metricas
     *
     * @return array<string, mixed>
     */
    private function formatearPlanDetalle(array $plan, DateTimeImmutable $hoy, array $metricas, ?array $estadoPlan = null): array
    {
        $estadoPlan = $estadoPlan
            ?? PlanEstadoService::calcular(
                $plan['estado'] ?? null,
                $plan['fecha_inicio'] ?? null,
                $plan['fecha_fin'] ?? null,
                $hoy
            );

        $medico = $this->formatearMedico(
            $plan['medico_id'] ?? null,
            $plan['medico_nombre'] ?? null,
            $plan['medico_apellido'] ?? null,
            $plan['medico_especialidad'] ?? null
        );

        return [
            'id'              => (int) $plan['id'],
            'nombre'          => $plan['nombre'] ?? null,
            'descripcion'     => $plan['descripcion'] ?? null,
            'fecha_creacion'  => $plan['fecha_creacion'] ?? null,
            'fecha_inicio'    => $plan['fecha_inicio'] ?? null,
            'fecha_fin'       => $plan['fecha_fin'] ?? null,
            'estado'          => $estadoPlan['estado'],
            'diagnostico_id'  => (int) ($plan['diagnostico_id'] ?? 0),
            'diagnostico'     => $plan['diagnostico_descripcion'] ?? null,
            'estado_categoria' => $estadoPlan['estado'],
            'estado_etiqueta'   => $estadoPlan['etiqueta'],
            'se_puede_finalizar' => $estadoPlan['sePuedeFinalizar'],
            'metricas'          => $metricas,
            'medico_id'         => $medico['id'],
            'medico_nombre'     => $medico['nombre_completo'],
            'medico_especialidad' => $medico['especialidad'],
            'medico_disponible' => $medico['disponible'],
        ];
    }

    /**
     * @param array<string, mixed> $actividad
     *
     * @return array<string, mixed>
     */
    private function formatearActividad(array $actividad, DateTimeImmutable $hoy, bool $planFinalizado = false): array
    {
        $fechaInicio = $this->crearFecha($actividad['fecha_inicio'] ?? null);
        $fechaFin    = $this->crearFecha($actividad['fecha_fin'] ?? null);
        $estadoSlug  = $actividad['estado_slug'] ?? null;
        $esPendiente = $estadoSlug === self::ESTADO_PENDIENTE;
        $esCompletada = $estadoSlug === self::ESTADO_COMPLETADA;
        $esVencida   = $estadoSlug === self::ESTADO_VENCIDA;

        $dentroDeRango = $this->estaDentroDeRango($fechaInicio, $fechaFin, $hoy);
        $puedeMarcar   = $esPendiente && $dentroDeRango && ! $planFinalizado;

        $bloqueoMotivo = null;
        if ($planFinalizado) {
            $bloqueoMotivo = 'Este plan está finalizado. No puedes modificar sus actividades.';
        } elseif ($esPendiente && ! $dentroDeRango) {
            if ($fechaInicio !== null && $hoy < $fechaInicio) {
                $bloqueoMotivo = sprintf('Disponible a partir del %s.', $fechaInicio->format('d/m/Y'));
            } elseif ($fechaFin !== null && $hoy > $fechaFin) {
                $bloqueoMotivo = sprintf('La actividad venció el %s.', $fechaFin->format('d/m/Y'));
            }
        } elseif ($esVencida && $fechaFin !== null) {
            $bloqueoMotivo = sprintf('La actividad venció el %s.', $fechaFin->format('d/m/Y'));
        }

        return [
            'id'                     => (int) $actividad['id'],
            'plan_id'                => (int) $actividad['plan_id'],
            'nombre'                 => $actividad['nombre'] ?? '',
            'descripcion'            => $actividad['descripcion'] ?? '',
            'fecha_inicio'           => $actividad['fecha_inicio'] ?? null,
            'fecha_fin'              => $actividad['fecha_fin'] ?? null,
            'estado_slug'            => $estadoSlug,
            'estado_nombre'          => $actividad['estado_nombre'] ?? null,
            'validado'               => $this->toBool($actividad['validado'] ?? null),
            'fecha_validacion'       => $actividad['fecha_validacion'] ?? null,
            'paciente_comentario'    => $actividad['paciente_comentario'] ?? null,
            'paciente_completada_en' => $actividad['paciente_completada_en'] ?? null,
            'puede_marcar'           => $puedeMarcar,
            'puede_desmarcar'        => $esCompletada,
            'bloqueo_motivo'         => $bloqueoMotivo,
            'categoria_actividad_id' => isset($actividad['categoria_actividad_id']) ? (int) $actividad['categoria_actividad_id'] : null,
            'categoria_nombre'       => $actividad['categoria_nombre'] ?? null,
            'categoria_color'        => $actividad['categoria_color'] ?? null,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $actividades
     *
     * @return array<string, int>
     */
    private function calcularMetricasDesdeActividades(array $actividades): array
    {
        $metricas = [
            'total'                 => 0,
            'pendientes'            => 0,
            'completadas'           => 0,
            'vencidas'              => 0,
            'validadas'             => 0,
            'pendientes_validacion' => 0,
        ];

        foreach ($actividades as $actividad) {
            $metricas['total']++;

            switch ($actividad['estado_slug']) {
                case self::ESTADO_PENDIENTE:
                    $metricas['pendientes']++;
                    break;
                case self::ESTADO_COMPLETADA:
                    $metricas['completadas']++;
                    if ($actividad['validado']) {
                        $metricas['validadas']++;
                    }
                    break;
                case self::ESTADO_VENCIDA:
                    $metricas['vencidas']++;
                    break;
            }
        }

        $metricas['pendientes_validacion'] = max(
            $metricas['completadas'] - $metricas['validadas'],
            0
        );

        return $metricas;
    }

    /**
     * @return array<string, int>
     */
    private function obtenerMetricasPlan(int $planId): array
    {
        $row = $this->db->table('actividades AS a')
            ->select([
                'COUNT(a.id) AS total',
                "COALESCE(SUM(CASE WHEN estado_actividad.slug = 'pendiente' THEN 1 ELSE 0 END), 0) AS pendientes",
                "COALESCE(SUM(CASE WHEN estado_actividad.slug = 'completada' THEN 1 ELSE 0 END), 0) AS completadas",
                "COALESCE(SUM(CASE WHEN estado_actividad.slug = 'vencida' THEN 1 ELSE 0 END), 0) AS vencidas",
                "COALESCE(SUM(CASE WHEN a.validado = 1 THEN 1 ELSE 0 END), 0) AS validadas",
            ])
            ->join('estado_actividad', 'estado_actividad.id = a.estado_id', 'left')
            ->where('a.plan_id', $planId)
            ->where('a.deleted_at', null)
            ->get()
            ->getFirstRow('array');

        if ($row === null) {
            return [
                'total'                 => 0,
                'pendientes'            => 0,
                'completadas'           => 0,
                'vencidas'              => 0,
                'validadas'             => 0,
                'pendientes_validacion' => 0,
            ];
        }

        $completadas = (int) ($row['completadas'] ?? 0);
        $validadas  = (int) ($row['validadas'] ?? 0);

        return [
            'total'                 => (int) ($row['total'] ?? 0),
            'pendientes'            => (int) ($row['pendientes'] ?? 0),
            'completadas'           => $completadas,
            'vencidas'              => (int) ($row['vencidas'] ?? 0),
            'validadas'             => $validadas,
            'pendientes_validacion' => max($completadas - $validadas, 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function obtenerContextoActividad(int $pacienteId, int $actividadId): array
    {
        $actividad = $this->db->table('actividades AS a')
            ->select([
                'a.id',
                'a.plan_id',
                'a.fecha_inicio',
                'a.fecha_fin',
                'a.estado_id',
                'estado_actividad.slug AS estado_slug',
                'diagnosticos.destinatario_user_id',
                'pc.estado AS plan_estado',
                'pc.fecha_inicio AS plan_fecha_inicio',
                'pc.fecha_fin AS plan_fecha_fin',
            ])
            ->join('planes_cuidado AS pc', 'pc.id = a.plan_id', 'inner')
            ->join('diagnosticos', 'diagnosticos.id = pc.diagnostico_id', 'inner')
            ->join('estado_actividad', 'estado_actividad.id = a.estado_id', 'left')
            ->where('a.id', $actividadId)
            ->where('a.deleted_at', null)
            ->where('pc.deleted_at', null)
            ->where('diagnosticos.deleted_at', null)
            ->get()
            ->getFirstRow('array');

        if ($actividad === null) {
            throw new InvalidArgumentException('La actividad indicada no existe.');
        }

        if ((int) ($actividad['destinatario_user_id'] ?? 0) !== $pacienteId) {
            throw PageForbiddenException::forPageForbidden('No tienes acceso a esta actividad.');
        }

        return $actividad;
    }

    private function validarPuedeMarcar(array $contexto, DateTimeImmutable $hoy): void
    {
        $estadoActual = $contexto['estado_slug'] ?? null;
        if ($estadoActual === self::ESTADO_COMPLETADA) {
            // Permite actualizar comentario incluso si ya estaba completada.
            return;
        }

        if ($estadoActual === self::ESTADO_VENCIDA) {
            throw new InvalidArgumentException('La actividad se encuentra vencida.');
        }

        $fechaInicio = $this->crearFecha($contexto['fecha_inicio'] ?? null);
        $fechaFin    = $this->crearFecha($contexto['fecha_fin'] ?? null);

        if ($fechaInicio !== null && $hoy < $fechaInicio) {
            throw new InvalidArgumentException(sprintf(
                'La actividad estará disponible a partir del %s.',
                $fechaInicio->format('d/m/Y')
            ));
        }

        if ($fechaFin !== null && $hoy > $fechaFin) {
            throw new InvalidArgumentException(sprintf(
                'La actividad venció el %s.',
                $fechaFin->format('d/m/Y')
            ));
        }
    }

    private function normalizarFiltro(string $filtro): string
    {
        $valor = strtolower(trim($filtro));

        return match ($valor) {
            'activos'       => self::FILTRO_EN_CURSO,
            'futuros'       => self::FILTRO_SIN_INICIAR,
            'finalizados'   => self::FILTRO_FINALIZADOS,
            self::FILTRO_SIN_INICIAR,
            self::FILTRO_FINALIZADOS,
            self::FILTRO_TODOS,
            self::FILTRO_EN_CURSO => $valor,
            default => self::FILTRO_EN_CURSO,
        };
    }

    private function estaDentroDeRango(?DateTimeImmutable $inicio, ?DateTimeImmutable $fin, DateTimeImmutable $hoy): bool
    {
        if ($inicio !== null && $hoy < $inicio) {
            return false;
        }

        if ($fin !== null && $hoy > $fin) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, int>
     */
    private function mapearEstadosPorSlug(): array
    {
        $estados = $this->estadoActividadModel
            ->select(['id', 'slug'])
            ->findAll();

        $map = [];
        foreach ($estados as $estado) {
            $slug = (string) ($estado['slug'] ?? '');
            if ($slug !== '') {
                $map[$slug] = (int) ($estado['id'] ?? 0);
            }
        }

        foreach ([self::ESTADO_PENDIENTE, self::ESTADO_COMPLETADA, self::ESTADO_VENCIDA] as $slugRequerido) {
            if (! isset($map[$slugRequerido])) {
                throw new RuntimeException(sprintf(
                    'No se encontró el estado de actividad requerido: %s',
                    $slugRequerido
                ));
            }
        }

        return $map;
    }

    private function crearFecha(?string $fecha): ?DateTimeImmutable
    {
        if ($fecha === null || $fecha === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($fecha);
        } catch (\Throwable) {
            return null;
        }
    }

    private function toBool(mixed $valor): bool
    {
        if ($valor === null) {
            return false;
        }

        if (is_bool($valor)) {
            return $valor;
        }

        return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
    }

    private function normalizarComentario(?string $comentario): ?string
    {
        if ($comentario === null) {
            return null;
        }

        $texto = trim($comentario);
        if ($texto === '') {
            return null;
        }

        if (mb_strlen($texto) > 1000) {
            $texto = mb_substr($texto, 0, 1000);
        }

        return $texto;
    }

    private function ahora(): string
    {
        return (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
    }

    /**
     * @return array<string, mixed>
     */
    private function obtenerActividadFormateada(int $actividadId, DateTimeImmutable $hoy): array
    {
        $actividad = $this->db->table('actividades AS a')
            ->select([
                'a.id',
                'a.plan_id',
                'a.nombre',
                'a.descripcion',
                'a.fecha_inicio',
                'a.fecha_fin',
                'a.estado_id',
                'a.validado',
                'a.paciente_comentario',
                'a.paciente_completada_en',
                'a.fecha_validacion',
                'estado_actividad.slug AS estado_slug',
                'estado_actividad.nombre AS estado_nombre',
                'pc.estado AS plan_estado',
            ])
            ->join('estado_actividad', 'estado_actividad.id = a.estado_id', 'left')
            ->join('planes_cuidado AS pc', 'pc.id = a.plan_id', 'inner')
            ->where('a.id', $actividadId)
            ->where('a.deleted_at', null)
            ->get()
            ->getFirstRow('array');

        if ($actividad === null) {
            throw new DatabaseException('No se pudo recuperar la actividad actualizada.');
        }

        $planFinalizado = PlanEstadoService::normalizar($actividad['plan_estado'] ?? null) === PlanEstadoService::ESTADO_FINALIZADO;

        return $this->formatearActividad($actividad, $hoy, $planFinalizado);
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
}
