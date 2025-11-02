<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCreadorUserIdToPlanesCuidado extends Migration
{
    private const FK_NAME = 'planes_cuidado_creador_user_id_foreign';

    public function up(): void
    {
        if (! $this->db->tableExists('planes_cuidado')) {
            return;
        }

        $tieneColumna = $this->db->fieldExists('creador_user_id', 'planes_cuidado');

        if (! $tieneColumna) {
            $this->forge->addColumn('planes_cuidado', [
                'creador_user_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => true,
                    'after'    => 'diagnostico_id',
                ],
            ]);
        }

        $this->db->query(<<<'SQL'
            UPDATE `planes_cuidado` AS pc
            INNER JOIN `diagnosticos` AS d ON d.id = pc.diagnostico_id
            SET pc.creador_user_id = d.autor_user_id
            WHERE pc.creador_user_id IS NULL
        SQL);

        $this->dropForeignKeyIfExists('planes_cuidado', self::FK_NAME);

        $this->db->query('ALTER TABLE `planes_cuidado` MODIFY `creador_user_id` INT UNSIGNED NOT NULL');

        if (! $this->foreignKeyExists('planes_cuidado', self::FK_NAME)) {
            $this->db->query(
                sprintf(
                    'ALTER TABLE `planes_cuidado` ADD CONSTRAINT `%s` FOREIGN KEY (`creador_user_id`) REFERENCES `users`(`id`) ON UPDATE RESTRICT ON DELETE RESTRICT',
                    self::FK_NAME
                )
            );
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('planes_cuidado')) {
            return;
        }

        if (! $this->db->fieldExists('creador_user_id', 'planes_cuidado')) {
            return;
        }

        $this->dropForeignKeyIfExists('planes_cuidado', self::FK_NAME);

        $this->forge->dropColumn('planes_cuidado', 'creador_user_id');
    }

    private function dropForeignKeyIfExists(string $table, string $constraintName): void
    {
        if (! $this->foreignKeyExists($table, $constraintName)) {
            return;
        }

        $this->db->query(
            sprintf(
                'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                $table,
                $constraintName
            )
        );
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $database = $this->db->getDatabase();
        $sql      = <<<'SQL'
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = ?
        SQL;

        $query = $this->db->query($sql, [$database, $table, $constraintName, 'FOREIGN KEY']);

        return $query->getFirstRow() !== null;
    }
}
