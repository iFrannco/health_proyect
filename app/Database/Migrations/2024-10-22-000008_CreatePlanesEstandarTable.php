<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlanesEstandarTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('planes_estandar')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => 180,
            ],
            'descripcion' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'version' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 1,
            ],
            'vigente' => [
                'type'    => 'BOOLEAN',
                'null'    => false,
                'default' => 1,
            ],
            'fecha_creacion' => [
                'type' => 'DATETIME',
                'null' => true,
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
        $this->forge->addUniqueKey(['nombre', 'version']);

        $this->forge->createTable('planes_estandar', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        if (! $this->db->tableExists('planes_estandar')) {
            return;
        }

        $this->forge->dropTable('planes_estandar', true);
    }
}

