<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

class UpdateActividadEstados extends Migration
{
    public function up(): void
    {
        $db       = Database::connect();
        $estados  = $db->table('estado_actividad')->get()->getResultArray();
        $porSlug  = [];
        $porId    = [];

        foreach ($estados as $estado) {
            $slug            = strtolower((string) ($estado['slug'] ?? ''));
            $porSlug[$slug]  = $estado;
            $porId[$estado['id']] = $estado;
        }

        $pendienteId = $this->asegurarPendiente($db, $porSlug);
        $completadaId = $this->asegurarCompletada($db, $porSlug);
        $vencidaId   = $this->asegurarVencida($db, $porSlug, $pendienteId);

        if ($pendienteId !== null && isset($porSlug['iniciada'])) {
            $db->table('actividades')
                ->where('estado_id', $porSlug['iniciada']['id'])
                ->update(['estado_id' => $pendienteId, 'updated_at' => date('Y-m-d H:i:s')]);
        }

        if (isset($porSlug['terminada']) && $completadaId !== null) {
            $db->table('actividades')
                ->where('estado_id', $porSlug['terminada']['id'])
                ->update(['estado_id' => $completadaId, 'updated_at' => date('Y-m-d H:i:s')]);
        }

        if (isset($porSlug['iniciada'])) {
            $db->table('estado_actividad')
                ->where('id', $porSlug['iniciada']['id'])
                ->update([
                    'nombre' => 'Vencida',
                    'slug'   => 'vencida',
                    'orden'  => 3,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            $vencidaId = $porSlug['iniciada']['id'];
        }

        if (isset($porSlug['terminada'])) {
            $db->table('estado_actividad')
                ->where('id', $porSlug['terminada']['id'])
                ->update([
                    'nombre' => 'Completada',
                    'slug'   => 'completada',
                    'orden'  => 2,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            $completadaId = $porSlug['terminada']['id'];
        }

        if ($pendienteId !== null) {
            $db->table('estado_actividad')
                ->where('id', $pendienteId)
                ->update([
                    'nombre' => 'Pendiente',
                    'slug'   => 'pendiente',
                    'orden'  => 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }

        if ($vencidaId === null) {
            $db->table('estado_actividad')->insert([
                'nombre'     => 'Vencida',
                'slug'       => 'vencida',
                'orden'      => 3,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $vencidaId = (int) $db->insertID();
        }

        if ($completadaId === null) {
            $db->table('estado_actividad')->insert([
                'nombre'     => 'Completada',
                'slug'       => 'completada',
                'orden'      => 2,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $completadaId = (int) $db->insertID();
        }

        if ($completadaId !== null) {
            $db->table('actividades')
                ->where('validado', 1)
                ->where('estado_id !=', $completadaId)
                ->update([
                    'validado' => null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }
    }

    public function down(): void
    {
        // No revertir: se mantiene el nuevo catálogo simplificado.
    }

    private function asegurarPendiente($db, array $porSlug): ?int
    {
        if (isset($porSlug['pendiente'])) {
            return (int) $porSlug['pendiente']['id'];
        }

        if (isset($porSlug['sin_iniciar'])) {
            $db->table('estado_actividad')
                ->where('id', $porSlug['sin_iniciar']['id'])
                ->update([
                    'nombre' => 'Pendiente',
                    'slug'   => 'pendiente',
                    'orden'  => 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return (int) $porSlug['sin_iniciar']['id'];
        }

        $db->table('estado_actividad')->insert([
            'nombre'     => 'Pendiente',
            'slug'       => 'pendiente',
            'orden'      => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) $db->insertID();
    }

    private function asegurarCompletada($db, array $porSlug): ?int
    {
        if (isset($porSlug['completada'])) {
            return (int) $porSlug['completada']['id'];
        }

        if (isset($porSlug['terminada'])) {
            $db->table('estado_actividad')
                ->where('id', $porSlug['terminada']['id'])
                ->update([
                    'nombre' => 'Completada',
                    'slug'   => 'completada',
                    'orden'  => 2,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return (int) $porSlug['terminada']['id'];
        }

        return null;
    }

    private function asegurarVencida($db, array $porSlug, ?int $pendienteId): ?int
    {
        if (isset($porSlug['vencida'])) {
            return (int) $porSlug['vencida']['id'];
        }

        if (isset($porSlug['iniciada'])) {
            if ($pendienteId !== null) {
                $db->table('actividades')
                    ->where('estado_id', $porSlug['iniciada']['id'])
                    ->update(['estado_id' => $pendienteId, 'updated_at' => date('Y-m-d H:i:s')]);
            }

            $db->table('estado_actividad')
                ->where('id', $porSlug['iniciada']['id'])
                ->update([
                    'nombre' => 'Vencida',
                    'slug'   => 'vencida',
                    'orden'  => 3,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return (int) $porSlug['iniciada']['id'];
        }

        return null;
    }
}

