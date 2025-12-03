<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Database\Seeder;
use DateInterval;
use DateTimeImmutable;
use RuntimeException;

class MedicoDashboardDemoSeeder extends Seeder
{
    private const DEMO_PREFIX = '[Demo Dashboard]';

    public function run(): void
    {
        $db = $this->db;

        $medico = $db->table('users')
            ->select('id, email, nombre, apellido')
            ->where('email', 'ana.medina@example.com')
            ->get()
            ->getFirstRow();

        if ($medico === null) {
            throw new RuntimeException('No se encontró el médico demo (ana.medina@example.com). Ejecuta UserSeeder primero.');
        }

        $pacientes = $db->table('users AS u')
            ->select('u.id, u.nombre, u.apellido')
            ->join('roles AS r', 'r.id = u.role_id', 'inner')
            ->where('r.slug', 'paciente')
            ->where('u.activo', 1)
            ->orderBy('u.id', 'ASC')
            ->limit(6)
            ->get()
            ->getResultArray();

        if (count($pacientes) < 4) {
            throw new RuntimeException('Se requieren al menos 4 pacientes activos para poblar el dashboard demo.');
        }

        $tiposDiagnostico = $db->table('tipos_diagnostico')
            ->select('id, slug')
            ->get()
            ->getResultArray();

        $tiposPorSlug = [];
        foreach ($tiposDiagnostico as $tipo) {
            $tiposPorSlug[$tipo['slug']] = (int) $tipo['id'];
        }

        foreach (['consulta-inicial', 'seguimiento', 'tratamiento'] as $slugRequerido) {
            if (! isset($tiposPorSlug[$slugRequerido])) {
                throw new RuntimeException(sprintf('No se encontró el tipo de diagnóstico con slug "%s".', $slugRequerido));
            }
        }

        $estadosActividad = $db->table('estado_actividad')
            ->select('id, slug')
            ->get()
            ->getResultArray();

        $estadoActividadPorSlug = [];
        foreach ($estadosActividad as $estado) {
            $estadoActividadPorSlug[$estado['slug']] = (int) $estado['id'];
        }

        foreach (['pendiente', 'completada', 'vencida'] as $slugEstado) {
            if (! isset($estadoActividadPorSlug[$slugEstado])) {
                throw new RuntimeException(sprintf('No se encontró el estado de actividad "%s".', $slugEstado));
            }
        }

        $categorias = $db->table('categoria_actividad')
            ->select('id, nombre')
            ->where('activo', 1)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $categoriaIds = array_map(static fn ($categoria) => (int) ($categoria['id'] ?? 0), $categorias);
        $categoriaDefaultId = null;
        foreach ($categorias as $categoria) {
            if (isset($categoria['id']) && (int) $categoria['id'] === 1) {
                $categoriaDefaultId = 1;
                break;
            }
        }
        if ($categoriaDefaultId === null && ! empty($categoriaIds)) {
            $categoriaDefaultId = $categoriaIds[0];
        }
        $categoriaDefaultId = $categoriaDefaultId ?? 1;

        $categoriaPorIndice = static function (int $indice) use ($categoriaIds, $categoriaDefaultId): int {
            if (empty($categoriaIds)) {
                return $categoriaDefaultId;
            }

            return $categoriaIds[$indice % count($categoriaIds)];
        };

        $ahora = new DateTimeImmutable('now');
        $nowStr = $ahora->format('Y-m-d H:i:s');

        $inicioMesActual = $ahora->modify('first day of this month')->setTime(0, 0);
        $finMesActual    = $ahora->modify('last day of this month')->setTime(0, 0);
        $segundaSemana   = $inicioMesActual->add(new DateInterval('P7D'));
        $terceraSemana   = $inicioMesActual->add(new DateInterval('P14D'));
        $cuartaSemana    = $inicioMesActual->add(new DateInterval('P21D'));

        $planAutocuidadoNoviembre = [
            'nombre'       => 'Plan integral de autocuidado — Noviembre',
            'descripcion'  => 'Seguimiento intensivo de hábitos diarios durante el mes en curso.',
            'fecha_inicio' => $inicioMesActual,
            'fecha_fin'    => $finMesActual,
            'estado'       => 'en_curso',
            'actividades'  => [
                [
                    'nombre'        => 'Control matutino de presión arterial',
                    'descripcion'   => 'Registrar los valores cada mañana en la app.',
                    'estado_slug'   => 'pendiente',
                    'validado'      => false,
                    'fecha_inicio'  => $inicioMesActual,
                    'fecha_fin'     => $segundaSemana->add(new DateInterval('P6D')),
                ],
                [
                    'nombre'        => 'Bitácora de alimentación consciente',
                    'descripcion'   => 'Documentar comidas principales con comentarios diarios.',
                    'estado_slug'   => 'pendiente',
                    'validado'      => false,
                    'fecha_inicio'  => $segundaSemana,
                    'fecha_fin'     => $terceraSemana->add(new DateInterval('P6D')),
                ],
                [
                    'nombre'        => 'Consulta virtual de seguimiento',
                    'descripcion'   => 'Sesión por videollamada para revisar métricas y comentarios.',
                    'estado_slug'   => 'completada',
                    'validado'      => false,
                    'fecha_inicio'  => $terceraSemana,
                    'fecha_fin'     => $terceraSemana->add(new DateInterval('P1D')),
                ],
                [
                    'nombre'        => 'Rutina de estiramientos vespertinos',
                    'descripcion'   => 'Realizar estiramientos guiados cinco veces por semana.',
                    'estado_slug'   => 'pendiente',
                    'validado'      => false,
                    'fecha_inicio'  => $cuartaSemana,
                    'fecha_fin'     => $finMesActual,
                ],
            ],
        ];

        $demoDiagnosticosExistentes = $db->table('diagnosticos')
            ->like('descripcion', self::DEMO_PREFIX, 'after')
            ->countAllResults();

        if ($demoDiagnosticosExistentes > 0) {
            $this->asegurarPlanAutocuidadoNoviembre(
                $db,
                $medico,
                $pacientes[0],
                $estadoActividadPorSlug,
                $planAutocuidadoNoviembre,
                $tiposPorSlug,
                $nowStr
            );

            return;
        }

        $db->transStart();

        try {
            $diagnosticosPlanificados = [
                [
                    'paciente'       => $pacientes[0],
                    'tipo_slug'      => 'consulta-inicial',
                    'fecha_creacion' => $ahora->sub(new DateInterval('P75D')),
                    'descripcion'    => 'Control inicial de presión arterial y hábitos de vida.',
                    'planes'         => [
                        [
                            'nombre'       => 'Plan de control de presión arterial',
                            'descripcion'  => 'Monitoreo y ajustes nutricionales.',
                            'fecha_inicio' => $ahora->sub(new DateInterval('P70D')),
                            'fecha_fin'    => $ahora->add(new DateInterval('P15D')),
                            'estado'       => 'en_curso',
                            'actividades'  => [
                                [
                                    'nombre'        => 'Registrar presión arterial diaria',
                                    'descripcion'   => 'Medir dos veces por día y subir registros.',
                                    'estado_slug'   => 'completada',
                                    'validado'      => true,
                                    'fecha_inicio'  => $ahora->sub(new DateInterval('P68D')),
                                    'fecha_fin'     => $ahora->sub(new DateInterval('P60D')),
                                ],
                                [
                                    'nombre'        => 'Consulta nutricional',
                                    'descripcion'   => 'Sesión virtual para ajustar dieta baja en sodio.',
                                    'estado_slug'   => 'vencida',
                                    'validado'      => false,
                                    'fecha_inicio'  => $ahora->sub(new DateInterval('P40D')),
                                    'fecha_fin'     => $ahora->sub(new DateInterval('P35D')),
                                ],
                                [
                                    'nombre'        => 'Actividad física moderada',
                                    'descripcion'   => 'Caminatas 30 minutos diarios.',
                                    'estado_slug'   => 'pendiente',
                                    'validado'      => false,
                                    'fecha_inicio'  => $ahora->add(new DateInterval('P5D')),
                                    'fecha_fin'     => $ahora->add(new DateInterval('P20D')),
                                ],
                            ],
                        ],
                        $planAutocuidadoNoviembre,
                    ],
                ],
                [
                    'paciente'       => $pacientes[1],
                    'tipo_slug'      => 'seguimiento',
                    'fecha_creacion' => $ahora->sub(new DateInterval('P55D')),
                    'descripcion'    => 'Seguimiento de plan respiratorio tras consulta inicial.',
                    'planes'         => [
                        [
                            'nombre'       => 'Plan de rehabilitación pulmonar',
                            'descripcion'  => 'Ejercicios guiados y control de medicación.',
                            'fecha_inicio' => $ahora->sub(new DateInterval('P50D')),
                            'fecha_fin'    => $ahora->sub(new DateInterval('P5D')),
                            'estado'       => 'finalizado',
                            'actividades'  => [
                                [
                                    'nombre'        => 'Sesiones de fisioterapia',
                                    'descripcion'   => 'Asistir a fisioterapia dos veces por semana.',
                                    'estado_slug'   => 'completada',
                                    'validado'      => true,
                                    'fecha_inicio'  => $ahora->sub(new DateInterval('P48D')),
                                    'fecha_fin'     => $ahora->sub(new DateInterval('P20D')),
                                ],
                                [
                                    'nombre'        => 'Ejercicios respiratorios en casa',
                                    'descripcion'   => 'Realizar rutina diaria indicada por fisioterapeuta.',
                                    'estado_slug'   => 'completada',
                                    'validado'      => true,
                                    'fecha_inicio'  => $ahora->sub(new DateInterval('P48D')),
                                    'fecha_fin'     => $ahora->sub(new DateInterval('P6D')),
                                ],
                                [
                                    'nombre'        => 'Control de medicación inhalada',
                                    'descripcion'   => 'Registrar adherencia a medicación prescrita.',
                                    'estado_slug'   => 'completada',
                                    'validado'      => true,
                                    'fecha_inicio'  => $ahora->sub(new DateInterval('P47D')),
                                    'fecha_fin'     => $ahora->sub(new DateInterval('P5D')),
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'paciente'       => $pacientes[2],
                    'tipo_slug'      => 'tratamiento',
                    'fecha_creacion' => $ahora->sub(new DateInterval('P40D')),
                    'descripcion'    => 'Aplicación de tratamiento kinesiológico por dolor lumbar.',
                    'planes'         => [
                        [
                            'nombre'       => 'Plan de kinesiología lumbar',
                            'descripcion'  => 'Fortalecimiento muscular y estiramientos progresivos.',
                            'fecha_inicio' => $ahora->sub(new DateInterval('P35D')),
                            'fecha_fin'    => $ahora->add(new DateInterval('P10D')),
                            'estado'       => 'activo',
                            'actividades'  => [
                                [
                                    'nombre'        => 'Sesiones presenciales',
                                    'descripcion'   => 'Asistir a kinesiología dos veces por semana.',
                                    'estado_slug'   => 'completada',
                                    'validado'      => false,
                                    'fecha_inicio'  => $ahora->sub(new DateInterval('P34D')),
                                    'fecha_fin'     => $ahora->sub(new DateInterval('P7D')),
                                ],
                                [
                                    'nombre'        => 'Ejercicios de estiramiento en casa',
                                    'descripcion'   => 'Realizar rutina diaria guiada por video.',
                                    'estado_slug'   => 'pendiente',
                                    'validado'      => false,
                                    'fecha_inicio'  => $ahora->sub(new DateInterval('P10D')),
                                    'fecha_fin'     => $ahora->add(new DateInterval('P7D')),
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'paciente'       => $pacientes[3],
                    'tipo_slug'      => 'consulta-inicial',
                    'fecha_creacion' => $ahora->sub(new DateInterval('P20D')),
                    'descripcion'    => 'Consulta preventiva para control metabólico anual.',
                    'planes'         => [],
                ],
                [
                    'paciente'       => $pacientes[4],
                    'tipo_slug'      => 'seguimiento',
                    'fecha_creacion' => $ahora->sub(new DateInterval('P15D')),
                    'descripcion'    => 'Seguimiento por síntomas gastrointestinales leves.',
                    'planes'         => [],
                ],
            ];

            foreach ($diagnosticosPlanificados as $indice => $item) {
                $descripcion = sprintf(
                    '%s %s — %s %s',
                    self::DEMO_PREFIX,
                    $item['descripcion'],
                    $item['paciente']['nombre'],
                    $item['paciente']['apellido']
                );

                $fechaCreacionDiag = $item['fecha_creacion']->format('Y-m-d H:i:s');
                $datosDiagnostico  = [
                    'autor_user_id'        => (int) $medico->id,
                    'destinatario_user_id' => (int) $item['paciente']['id'],
                    'tipo_diagnostico_id'  => $tiposPorSlug[$item['tipo_slug']],
                    'descripcion'          => $descripcion,
                    'fecha_creacion'       => $fechaCreacionDiag,
                    'created_at'           => $fechaCreacionDiag,
                    'updated_at'           => $fechaCreacionDiag,
                ];

                $db->table('diagnosticos')->insert($datosDiagnostico);
                $diagnosticoId = (int) $db->insertID();

                foreach ($item['planes'] as $planIndice => $plan) {
                    $fechaInicio = $plan['fecha_inicio']->format('Y-m-d');
                    $fechaFin    = $plan['fecha_fin']->format('Y-m-d');
                    $fechaCreacionPlan = $plan['fecha_inicio']->format('Y-m-d') . ' 09:00:00';

                    $datosPlan = [
                        'diagnostico_id'  => $diagnosticoId,
                        'creador_user_id' => (int) $medico->id,
                        'plan_estandar_id'=> null,
                        'nombre'          => $plan['nombre'],
                        'descripcion'     => $plan['descripcion'],
                        'fecha_creacion'  => $fechaCreacionPlan,
                        'fecha_inicio'    => $fechaInicio,
                        'fecha_fin'       => $fechaFin,
                        'estado'          => $plan['estado'],
                        'created_at'      => $fechaCreacionPlan,
                        'updated_at'      => $nowStr,
                    ];

                    $db->table('planes_cuidado')->insert($datosPlan);
                    $planId = (int) $db->insertID();

                    foreach ($plan['actividades'] as $actividadIndice => $actividad) {
                        $fechaInicioAct = $actividad['fecha_inicio']->format('Y-m-d');
                        $fechaFinAct    = $actividad['fecha_fin']->format('Y-m-d');
                        $fechaCreacionActividad = $actividad['fecha_inicio']->format('Y-m-d') . ' 08:00:00';

                        $datosActividad = [
                            'plan_id'        => $planId,
                            'nombre'         => $actividad['nombre'],
                            'descripcion'    => $actividad['descripcion'],
                            'fecha_creacion' => $fechaCreacionActividad,
                            'fecha_inicio'   => $fechaInicioAct,
                            'fecha_fin'      => $fechaFinAct,
                            'estado_id'      => $estadoActividadPorSlug[$actividad['estado_slug']],
                            'categoria_actividad_id' => $categoriaPorIndice($actividadIndice),
                            'validado'       => $actividad['validado'] ? 1 : null,
                            'created_at'     => $fechaCreacionActividad,
                            'updated_at'     => $nowStr,
                        ];

                        $db->table('actividades')->insert($datosActividad);
                    }
                }
            }
        } catch (\Throwable $exception) {
            $db->transRollback();

            throw $exception;
        }

        $db->transComplete();
    }

    /**
     * Inserta el plan de autocuidado de noviembre para Luis Paz si no existe.
     *
     * @param array<string, int>   $estadoActividadPorSlug
     * @param array<string, mixed> $planAutocuidado
     * @param array<string, int>   $tiposPorSlug
     */
    private function asegurarPlanAutocuidadoNoviembre(
        ConnectionInterface $db,
        object $medico,
        array $paciente,
        array $estadoActividadPorSlug,
        array $planAutocuidado,
        array $tiposPorSlug,
        string $nowStr
    ): void {
        $planNombre = $planAutocuidado['nombre'] ?? '';

        $planExistente = $db->table('planes_cuidado AS pc')
            ->select('pc.id')
            ->join('diagnosticos AS d', 'd.id = pc.diagnostico_id', 'inner')
            ->where('pc.nombre', $planNombre)
            ->where('pc.deleted_at', null)
            ->where('d.destinatario_user_id', (int) $paciente['id'])
            ->where('d.deleted_at', null)
            ->get()
            ->getFirstRow('array');

        if ($planExistente !== null) {
            return;
        }

        $db->transStart();

        try {
            $diagnostico = $db->table('diagnosticos')
                ->select('id')
                ->where('destinatario_user_id', (int) $paciente['id'])
                ->like('descripcion', self::DEMO_PREFIX, 'after')
                ->where('deleted_at', null)
                ->orderBy('fecha_creacion', 'DESC')
                ->get()
                ->getFirstRow('array');

            if ($diagnostico === null) {
                $descripcionDiagnostico = sprintf(
                    '%s Seguimiento mensual intensivo — %s %s',
                    self::DEMO_PREFIX,
                    $paciente['nombre'] ?? '',
                    $paciente['apellido'] ?? ''
                );

                $fechaBase = $planAutocuidado['fecha_inicio'] instanceof \DateTimeInterface
                    ? $planAutocuidado['fecha_inicio']
                    : new DateTimeImmutable('now');

                $fechaCreacionDiag = $fechaBase->format('Y-m-d') . ' 08:30:00';

                $db->table('diagnosticos')->insert([
                    'autor_user_id'        => (int) $medico->id,
                    'destinatario_user_id' => (int) $paciente['id'],
                    'tipo_diagnostico_id'  => $tiposPorSlug['seguimiento'],
                    'descripcion'          => $descripcionDiagnostico,
                    'fecha_creacion'       => $fechaCreacionDiag,
                    'created_at'           => $fechaCreacionDiag,
                    'updated_at'           => $fechaCreacionDiag,
                ]);

                $diagnosticoId = (int) $db->insertID();
            } else {
                $diagnosticoId = (int) $diagnostico['id'];
            }

            $fechaInicio = $planAutocuidado['fecha_inicio'] instanceof \DateTimeInterface
                ? $planAutocuidado['fecha_inicio']->format('Y-m-d')
                : date('Y-m-d');
            $fechaFin = $planAutocuidado['fecha_fin'] instanceof \DateTimeInterface
                ? $planAutocuidado['fecha_fin']->format('Y-m-d')
                : date('Y-m-d');

            $fechaCreacionPlan = $planAutocuidado['fecha_inicio'] instanceof \DateTimeInterface
                ? $planAutocuidado['fecha_inicio']->format('Y-m-d') . ' 09:00:00'
                : $nowStr;

            $db->table('planes_cuidado')->insert([
                'diagnostico_id'  => $diagnosticoId,
                'creador_user_id' => (int) $medico->id,
                'plan_estandar_id'=> null,
                'nombre'          => $planAutocuidado['nombre'],
                'descripcion'     => $planAutocuidado['descripcion'],
                'fecha_creacion'  => $fechaCreacionPlan,
                'fecha_inicio'    => $fechaInicio,
                'fecha_fin'       => $fechaFin,
                'estado'          => $planAutocuidado['estado'],
                'created_at'      => $fechaCreacionPlan,
                'updated_at'      => $nowStr,
            ]);

            $planId = (int) $db->insertID();

            foreach ($planAutocuidado['actividades'] as $actividadIndice => $actividad) {
                $fechaInicioAct = $actividad['fecha_inicio'] instanceof \DateTimeInterface
                    ? $actividad['fecha_inicio']->format('Y-m-d')
                    : $fechaInicio;
                $fechaFinAct = $actividad['fecha_fin'] instanceof \DateTimeInterface
                    ? $actividad['fecha_fin']->format('Y-m-d')
                    : $fechaFin;

                $fechaCreacionActividad = $actividad['fecha_inicio'] instanceof \DateTimeInterface
                    ? $actividad['fecha_inicio']->format('Y-m-d') . ' 08:00:00'
                    : $fechaCreacionPlan;

                $db->table('actividades')->insert([
                    'plan_id'        => $planId,
                    'nombre'         => $actividad['nombre'],
                    'descripcion'    => $actividad['descripcion'],
                    'fecha_creacion' => $fechaCreacionActividad,
                    'fecha_inicio'   => $fechaInicioAct,
                    'fecha_fin'      => $fechaFinAct,
                    'estado_id'      => $estadoActividadPorSlug[$actividad['estado_slug']],
                    'categoria_actividad_id' => $categoriaPorIndice($actividadIndice),
                    'validado'       => ! empty($actividad['validado']) ? 1 : null,
                    'created_at'     => $fechaCreacionActividad,
                    'updated_at'     => $nowStr,
                ]);
            }
        } catch (\Throwable $exception) {
            $db->transRollback();

            throw $exception;
        }

        $db->transComplete();
    }
}
