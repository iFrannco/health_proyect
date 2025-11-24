<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDniToUsers extends Migration
{
    public function up(): void
    {
        $db       = db_connect();
        if ($db->fieldExists('dni', 'users')) {
            // Column already exists; nothing to do.
            return;
        }

        $this->forge->addColumn('users', [
            'dni' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'unique'     => true,
                'after'      => 'apellido',
            ],
        ]);

        $builder  = $db->table('users');
        $usuarios = $builder->select(['id'])->orderBy('id', 'ASC')->get()->getResultArray();

        $contador = 1;
        $now      = date('Y-m-d H:i:s');

        foreach ($usuarios as $usuario) {
            $id      = (int) ($usuario['id'] ?? 0);
            $dniFake = '999' . str_pad((string) $contador, 6, '0', STR_PAD_LEFT);

            $db->table('users')
                ->where('id', $id)
                ->update([
                    'dni'       => $dniFake,
                    'updated_at'=> $now,
                ]);

            $contador++;
        }

        $this->forge->modifyColumn('users', [
            'dni' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'unique'     => true,
            ],
        ]);
    }

    public function down(): void
    {
        $db = db_connect();
        if ($db->fieldExists('dni', 'users')) {
            $this->forge->dropColumn('users', 'dni');
        }
    }
}
