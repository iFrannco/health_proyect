<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCamposPacienteActividades extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('actividades', [
            'paciente_comentario' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'validado',
            ],
            'paciente_completada_en' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'paciente_comentario',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('actividades', [
            'paciente_completada_en',
            'paciente_comentario',
        ]);
    }
}
