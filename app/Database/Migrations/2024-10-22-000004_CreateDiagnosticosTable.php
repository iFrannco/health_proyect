<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDiagnosticosTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'autor_user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'destinatario_user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'tipo_diagnostico_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'descripcion' => [
                'type' => 'TEXT',
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
        $this->forge->addKey('autor_user_id');
        $this->forge->addKey('destinatario_user_id');
        $this->forge->addKey('tipo_diagnostico_id');

        $this->forge->addForeignKey('autor_user_id', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('destinatario_user_id', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('tipo_diagnostico_id', 'tipos_diagnostico', 'id', 'CASCADE', 'RESTRICT');

        $this->forge->createTable('diagnosticos', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('diagnosticos', true);
    }
}
