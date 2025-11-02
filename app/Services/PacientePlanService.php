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
    private const FILTRO_ACTIVOS     = 'activos';
    private const FILTRO_FUTUROS     = 'futuros';
    private const FILTRO_FINALIZADOS = 'finalizados';
    private const FILTRO_TODOS       = 'todos';

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
            self::FILTRO_ACTIVOS     => [],
            self::FILTRO_FUTUROS     => [],
            self::FILTRO_FINALIZADOS => [],
            self::FILTRO_TODOS       => [],
        ];

        foreach ($planesBase as $plan) {
            $planFormateado = $this->formatearPlanResumen($plan, $hoy);

            $agrupados[self::FILTRO_TODOS][] = $planFormateado;

            switch ($planFormateado['estado_categoria']) {
                case self::FILTRO_ACTIVOS:
                    $agrupados[self::FILTRO_ACTIVOS][] = $planFormateado;
                    break;
                case self::FILTRO_FUTUROS:
                    $agrupados[self::FILTRO_FUTUROS][] = $planFormateado;
                    break;
                case self::FILTRO_FINALIZADOS:
                    $agrupados[self::FILTRO_FINALIZADOS][] = $planFormateado;
                    break;
            }
        }

        $conteos = [
            self::FILTRO_ACTIVOS     => count($agrupados[self::FILTRO_ACTIVOS]),
            self::FILTRO_FUTUROS     => count($agrupados[self::FILTRO_FUTUROS]),
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
                'diagnosticos.descripcion AS diagnostico_descripcion',
                'diagnosticos.id AS diagnostico_id',
            ])
            ->join('diagnosticos', 'diagnosticos.id = pc.diagnostico_id', 'inner')
            ->where('pc.id', $planId)
            ->where('diagnosticos.destinatario_user_id', $pacienteId)
            ->where('pc.deleted_at', null)
            ->where('diagnosticos.deleted_at', null)
            ->get()
            ->getFirstRow('array');

        if ($plan === null) {
            throw PageForbiddenException::forPageForbidden('No se encontró el plan solicitado.');
        }

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
            ])
            ->join('estado_actividad', 'estado_actividad.id = a.estado_id', 'left')
            ->where('a.plan_id', $planId)
            ->where('a.deleted_at', null)
            ->orderBy('a.fecha_inicio', 'ASC')
            ->orderBy('a.id', 'ASC')
            ->get()
            ->getResultArray();

        $actividadesFormateadas = array_map(
            fn (array $actividad): array => $this->formatearActividad($actividad, $hoy),
            $actividades
        );

        $metricas = $this->calcularMetricasDesdeActividades($actividadesFormateadas);

        return [
            'plan'        => $this->formatearPlanDetalle($plan, $hoy, $metricas),
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
                'diagnosticos.descripcion AS diagnostico_descripcion',
                'COUNT(a.id) AS total_actividades',
                "COALESCE(SUM(CASE WHEN estado_actividad.slug = 'pendiente' THEN 1 ELSE 0 END), 0) AS total_pendientes",
                "COALESCE(SUM(CASE WHEN estado_actividad.slug = 'completada' THEN 1 ELSE 0 END), 0) AS total_completadas",
                "COALESCE(SUM(CASE WHEN estado_actividad.slug = 'vencida' THEN 1 ELSE 0 END), 0) AS total_vencidas",
                "COALESCE(SUM(CASE WHEN a.validado = 1 THEN 1 ELSE 0 END), 0) AS total_validadas",
            ])
            ->join('diagnosticos', 'diagnosticos.id = pc.diagnostico_id', 'inner')
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
        $fechaInicio = $this->crearFecha($plan['fecha_inicio'] ?? null);
        $fechaFin    = $this->crearFecha($plan['fecha_fin'] ?? null);

        $estadoCategoria = $this->calcularCategoriaPlan($plan, $hoy, $fechaInicio, $fechaFin);

        $totalActividades = (int) ($plan['total_actividades'] ?? 0);
        $totalCompletadas = (int) ($plan['total_completadas'] ?? 0);
        $totalValidadas   = (int) ($plan['total_validadas'] ?? 0);
        $totalPendientes  = (int) ($plan['total_pendientes'] ?? 0);
        $totalVencidas    = (int) ($plan['total_vencidas'] ?? 0);

        $porcentaje = $totalActividades > 0
            ? (int) round(($totalCompletadas / $totalActividades) * 100)
            : 0;

        return [
            'id'                  => (int) $plan['id'],
            'nombre'              => $plan['nombre'] ?? null,
            'descripcion'         => $plan['descripcion'] ?? null,
            'fecha_inicio'        => $plan['fecha_inicio'] ?? null,
            'fecha_fin'           => $plan['fecha_fin'] ?? null,
            'fecha_creacion'      => $plan['fecha_creacion'] ?? null,
            'estado'              => $plan['estado'] ?? null,
            'diagnostico'         => $plan['diagnostico_descripcion'] ?? null,
            'estado_categoria'    => $estadoCategoria,
            'estado_etiqueta'     => $this->etiquetaParaCategoria($estadoCategoria),
            'total_actividades'   => $totalActividades,
            'total_completadas'   => $totalCompletadas,
            'total_pendientes'    => $totalPendientes,
            'total_vencidas'      => $totalVencidas,
            'total_validadas'     => $totalValidadas,
            'total_pendientes_validacion' => max($totalCompletadas - $totalValidadas, 0),
            'porcentaje_completadas' => $porcentaje,
            'es_vigente'          => $estadoCategoria === self::FILTRO_ACTIVOS,
            'es_futuro'           => $estadoCategoria === self::FILTRO_FUTUROS,
        ];
    }

    /**
     * @param array<string, mixed> $plan
     * @param array<string, int>   $metricas
     *
     * @return array<string, mixed>
     */
    private function formatearPlanDetalle(array $plan, DateTimeImmutable $hoy, array $metricas): array
    {
        $fechaInicio = $this->crearFecha($plan['fecha_inicio'] ?? null);
        $fechaFin    = $this->crearFecha($plan['fecha_fin'] ?? null);

        $estadoCategoria = $this->calcularCategoriaPlan($plan, $hoy, $fechaInicio, $fechaFin);

        return [
            'id'              => (int) $plan['id'],
            'nombre'          => $plan['nombre'] ?? null,
            'descripcion'     => $plan['descripcion'] ?? null,
            'fecha_creacion'  => $plan['fecha_creacion'] ?? null,
            'fecha_inicio'    => $plan['fecha_inicio'] ?? null,
            'fecha_fin'       => $plan['fecha_fin'] ?? null,
            'estado'          => $plan['estado'] ?? null,
            'diagnostico_id'  => (int) ($plan['diagnostico_id'] ?? 0),
            'diagnostico'     => $plan['diagnostico_descripcion'] ?? null,
            'estado_categoria' => $estadoCategoria,
            'estado_etiqueta'   => $this->etiquetaParaCategoria($estadoCategoria),
            'metricas'          => $metricas,
        ];
    }

    /**
     * @param array<string, mixed> $actividad
     *
     * @return array<string, mixed>
     */
    private function formatearActividad(array $actividad, DateTimeImmutable $hoy): array
    {
        $fechaInicio = $this->crearFecha($actividad['fecha_inicio'] ?? null);
        $fechaFin    = $this->crearFecha($actividad['fecha_fin'] ?? null);
        $estadoSlug  = $actividad['estado_slug'] ?? null;
        $esPendiente = $estadoSlug === self::ESTADO_PENDIENTE;
        $esCompletada = $estadoSlug === self::ESTADO_COMPLETADA;
        $esVencida   = $estadoSlug === self::ESTADO_VENCIDA;

        $dentroDeRango = $this->estaDentroDeRango($fechaInicio, $fechaFin, $hoy);
        $puedeMarcar   = $esPendiente && $dentroDeRango;

        $bloqueoMotivo = null;
        if ($esPendiente && ! $dentroDeRango) {
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
            self::FILTRO_FUTUROS, self::FILTRO_FINALIZADOS, self::FILTRO_TODOS => $valor,
            default => self::FILTRO_ACTIVOS,
        };
    }

    /**
     * @param array<string, mixed>      $plan
     * @param DateTimeImmutable|null    $fechaInicio
     * @param DateTimeImmutable|null    $fechaFin
     */
    private function calcularCategoriaPlan(
        array $plan,
        DateTimeImmutable $hoy,
        ?DateTimeImmutable $fechaInicio,
        ?DateTimeImmutable $fechaFin
    ): string {
        $estadoTexto = strtolower(trim((string) ($plan['estado'] ?? '')));
        if ($estadoTexto !== '' && in_array($estadoTexto, self::ESTADOS_PLAN_FINALIZADOS, true)) {
            return self::FILTRO_FINALIZADOS;
        }

        if ($fechaInicio !== null && $hoy < $fechaInicio) {
            return self::FILTRO_FUTUROS;
        }

        if ($fechaFin !== null && $hoy > $fechaFin) {
            return self::FILTRO_FINALIZADOS;
        }

        return self::FILTRO_ACTIVOS;
    }

    private function etiquetaParaCategoria(string $categoria): string
    {
        return match ($categoria) {
            self::FILTRO_FUTUROS => 'Futuro',
            self::FILTRO_FINALIZADOS => 'Finalizado',
            default => 'Activo',
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
            ])
            ->join('estado_actividad', 'estado_actividad.id = a.estado_id', 'left')
            ->where('a.id', $actividadId)
            ->where('a.deleted_at', null)
            ->get()
            ->getFirstRow('array');

        if ($actividad === null) {
            throw new DatabaseException('No se pudo recuperar la actividad actualizada.');
        }

        return $this->formatearActividad($actividad, $hoy);
    }
}
