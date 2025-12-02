<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class EspecialidadSeeder extends Seeder
{
    public function run(): void
    {
        $catalogo = [
            ['slug' => 'clinica-medica', 'nombre' => 'Clínica Médica'],
            ['slug' => 'pediatria', 'nombre' => 'Pediatría'],
            ['slug' => 'cardiologia', 'nombre' => 'Cardiología'],
            ['slug' => 'traumatologia', 'nombre' => 'Traumatología'],
            ['slug' => 'dermatologia', 'nombre' => 'Dermatología'],
            ['slug' => 'ginecologia', 'nombre' => 'Ginecología'],
            ['slug' => 'neurologia', 'nombre' => 'Neurología'],
        ];

        $existentes = $this->db->table('especialidades')
            ->select('slug')
            ->get()
            ->getResultArray();

        $slugsExistentes = array_column($existentes, 'slug');
        $ahora           = date('Y-m-d H:i:s');

        $aInsertar = [];
        foreach ($catalogo as $especialidad) {
            if (! in_array($especialidad['slug'], $slugsExistentes, true)) {
                $especialidad['created_at'] = $ahora;
                $especialidad['updated_at'] = $ahora;
                $aInsertar[]                = $especialidad;
            }
        }

        if ($aInsertar !== []) {
            $this->db->table('especialidades')->insertBatch($aInsertar);
        }
    }
}
