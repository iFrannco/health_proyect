<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateActividadesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'plan_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],
            'descripcion' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'fecha_creacion' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'fecha_inicio' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'fecha_fin' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'estado_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'validado' => [
                'type'    => 'BOOLEAN',
                'null'    => true,
                'default' => 0,
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
        $this->forge->addKey('plan_id');
        $this->forge->addKey('estado_id');

        $this->forge->addForeignKey('plan_id', 'planes_cuidado', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('estado_id', 'estado_actividad', 'id', 'RESTRICT', 'RESTRICT');

        $this->forge->createTable('actividades', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('actividades', true);
    }
}
