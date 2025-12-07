<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFrecuenciaDuracionToPlanEstandarActividades extends Migration
{
    public function up()
    {
        $fields = [
            'repeticion_cantidad' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'default'    => 1,
                'after'      => 'descripcion',
            ],
            'repeticion_tipo' => [
                'type'       => 'ENUM',
                'constraint' => ['dia', 'semana', 'mes'],
                'null'       => true,
                'default'    => 'dia',
                'after'      => 'repeticion_cantidad',
            ],
            'duracion_cantidad' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'default'    => 1,
                'after'      => 'repeticion_tipo',
            ],
            'duracion_tipo' => [
                'type'       => 'ENUM',
                'constraint' => ['dias', 'semanas', 'meses'],
                'null'       => true,
                'default'    => 'dias',
                'after'      => 'duracion_cantidad',
            ],
        ];

        $this->forge->addColumn('plan_estandar_actividades', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('plan_estandar_actividades', [
            'repeticion_cantidad', 
            'repeticion_tipo', 
            'duracion_cantidad', 
            'duracion_tipo'
        ]);
    }
}