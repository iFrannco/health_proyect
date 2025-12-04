<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDocumentacionTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'usuario_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => 180,
            ],
            'tipo' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'fecha_documento' => [
                'type' => 'DATE',
            ],
            'url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('usuario_id');
        $this->forge->addKey('tipo');
        $this->forge->addForeignKey('usuario_id', 'users', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('documentacion', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('documentacion', true);
    }
}

