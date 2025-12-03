<?php

namespace App\Database\Migrations;

use App\Services\PlanEstadoService;
use CodeIgniter\Database\Migration;
use Config\Database;
use DateTimeImmutable;

class NormalizePlanEstados extends Migration
{
    public function up(): void
    {
        $db  = Database::connect();
        $hoy = (new DateTimeImmutable('today'))->format('Y-m-d');

        $planes = $db->table('planes_cuidado')
            ->select(['id', 'estado', 'fecha_inicio', 'fecha_fin'])
            ->get()
            ->getResultArray();

        foreach ($planes as $plan) {
            $estadoCrudo   = strtolower(trim((string) ($plan['estado'] ?? '')));
            $fechaInicio   = $plan['fecha_inicio'] ?? null;
            $fechaFin      = $plan['fecha_fin'] ?? null;
            $estadoNuevo   = null;

            if (in_array($estadoCrudo, ['finalizado', 'terminado', 'completado', 'cerrado'], true)) {
                $estadoNuevo = PlanEstadoService::ESTADO_FINALIZADO;
            } elseif (($estadoCrudo === '' || $estadoCrudo === null) && $fechaFin !== null && $fechaFin !== '' && $fechaFin < $hoy) {
                $estadoNuevo = PlanEstadoService::ESTADO_FINALIZADO;
            } else {
                if ($fechaInicio !== null && $fechaInicio !== '' && $fechaInicio > $hoy) {
                    $estadoNuevo = PlanEstadoService::ESTADO_SIN_INICIAR;
                } else {
                    $estadoNuevo = PlanEstadoService::ESTADO_EN_CURSO;
                }
            }

            if ($estadoNuevo === $estadoCrudo) {
                continue;
            }

            $db->table('planes_cuidado')
                ->where('id', $plan['id'])
                ->update([
                    'estado'     => $estadoNuevo,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }
    }

    public function down(): void
    {
        // No rollback: se mantiene la normalización.
    }
}
