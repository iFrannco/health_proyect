<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlanesCuidadoTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'diagnostico_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => 180,
                'null'       => true,
            ],
            'descripcion' => [
                'type' => 'TEXT',
                'null' => true,
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
            'estado' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => true,
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
        $this->forge->addKey('diagnostico_id');

        $this->forge->addForeignKey('diagnostico_id', 'diagnosticos', 'id', 'CASCADE', 'RESTRICT');

        $this->forge->createTable('planes_cuidado', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('planes_cuidado', true);
    }
}
