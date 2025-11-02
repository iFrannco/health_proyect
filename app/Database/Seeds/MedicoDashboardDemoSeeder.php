<?php

namespace App\Database\Seeds;

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

        foreach (['sin_iniciar', 'iniciada', 'terminada'] as $slugEstado) {
            if (! isset($estadoActividadPorSlug[$slugEstado])) {
                throw new RuntimeException(sprintf('No se encontró el estado de actividad "%s".', $slugEstado));
            }
        }

        $demoDiagnosticosExistentes = $db->table('diagnosticos')
            ->like('descripcion', self::DEMO_PREFIX, 'after')
            ->countAllResults();

        if ($demoDiagnosticosExistentes > 0) {
            // Ya se insertaron los demo anteriormente; evitar duplicados.
            return;
        }

        $ahora = new DateTimeImmutable('now');
        $nowStr = $ahora->format('Y-m-d H:i:s');

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
                                    'estado_slug'   => 'terminada',
                                    'validado'      => true,
                                    'fecha_inicio'  => $ahora->sub(new DateInterval('P68D')),
                                    'fecha_fin'     => $ahora->sub(new DateInterval('P60D')),
                                ],
                                [
                                    'nombre'        => 'Consulta nutricional',
                                    'descripcion'   => 'Sesión virtual para ajustar dieta baja en sodio.',
                                    'estado_slug'   => 'iniciada',
                                    'validado'      => false,
                                    'fecha_inicio'  => $ahora->sub(new DateInterval('P40D')),
                                    'fecha_fin'     => $ahora->sub(new DateInterval('P35D')),
                                ],
                                [
                                    'nombre'        => 'Actividad física moderada',
                                    'descripcion'   => 'Caminatas 30 minutos diarios.',
                                    'estado_slug'   => 'sin_iniciar',
                                    'validado'      => false,
                                    'fecha_inicio'  => $ahora->add(new DateInterval('P5D')),
                                    'fecha_fin'     => $ahora->add(new DateInterval('P20D')),
                                ],
                            ],
                        ],
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
                                    'estado_slug'   => 'terminada',
                                    'validado'      => true,
                                    'fecha_inicio'  => $ahora->sub(new DateInterval('P48D')),
                                    'fecha_fin'     => $ahora->sub(new DateInterval('P20D')),
                                ],
                                [
                                    'nombre'        => 'Ejercicios respiratorios en casa',
                                    'descripcion'   => 'Realizar rutina diaria indicada por fisioterapeuta.',
                                    'estado_slug'   => 'terminada',
                                    'validado'      => true,
                                    'fecha_inicio'  => $ahora->sub(new DateInterval('P48D')),
                                    'fecha_fin'     => $ahora->sub(new DateInterval('P6D')),
                                ],
                                [
                                    'nombre'        => 'Control de medicación inhalada',
                                    'descripcion'   => 'Registrar adherencia a medicación prescrita.',
                                    'estado_slug'   => 'terminada',
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
                                    'estado_slug'   => 'terminada',
                                    'validado'      => false,
                                    'fecha_inicio'  => $ahora->sub(new DateInterval('P34D')),
                                    'fecha_fin'     => $ahora->sub(new DateInterval('P7D')),
                                ],
                                [
                                    'nombre'        => 'Ejercicios de estiramiento en casa',
                                    'descripcion'   => 'Realizar rutina diaria guiada por video.',
                                    'estado_slug'   => 'iniciada',
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
}

