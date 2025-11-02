<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlanEstandarActividadesTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('plan_estandar_actividades')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'plan_estandar_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => 180,
            ],
            'descripcion' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'offset_inicio_dias' => [
                'type' => 'INT',
            ],
            'offset_fin_dias' => [
                'type' => 'INT',
            ],
            'orden' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('plan_estandar_id');

        if ($this->db->tableExists('planes_estandar')) {
            $this->forge->addForeignKey('plan_estandar_id', 'planes_estandar', 'id', 'CASCADE', 'CASCADE');
        }

        $this->forge->createTable('plan_estandar_actividades', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        if (! $this->db->tableExists('plan_estandar_actividades')) {
            return;
        }

        $this->forge->dropTable('plan_estandar_actividades', true);
    }
}

