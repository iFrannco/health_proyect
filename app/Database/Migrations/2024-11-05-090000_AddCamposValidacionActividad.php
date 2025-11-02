<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCamposValidacionActividad extends Migration
{
    private const TABLA = 'actividades';

    public function up(): void
    {
        $campos = [
            'fecha_validacion' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'paciente_completada_en',
            ],
        ];

        $this->forge->addColumn(self::TABLA, $campos);
    }

    public function down(): void
    {
        $this->forge->dropColumn(self::TABLA, ['fecha_validacion']);
    }
}
