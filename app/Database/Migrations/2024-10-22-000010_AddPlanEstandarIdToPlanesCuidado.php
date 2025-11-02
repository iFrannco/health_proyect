<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPlanEstandarIdToPlanesCuidado extends Migration
{
    private const FK_NAME = 'planes_cuidado_plan_estandar_id_foreign';

    public function up(): void
    {
        if (! $this->db->tableExists('planes_cuidado')) {
            return;
        }

        if (! $this->db->fieldExists('plan_estandar_id', 'planes_cuidado')) {
            $this->forge->addColumn('planes_cuidado', [
                'plan_estandar_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => true,
                    'after'    => 'diagnostico_id',
                ],
            ]);
        }

        if ($this->db->tableExists('planes_estandar') && ! $this->hasForeignKey(self::FK_NAME)) {
            $this->db->query(
                'ALTER TABLE `planes_cuidado`
                 ADD CONSTRAINT `' . self::FK_NAME . '`
                 FOREIGN KEY (`plan_estandar_id`)
                 REFERENCES `planes_estandar`(`id`)
                 ON DELETE SET NULL
                 ON UPDATE CASCADE'
            );
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('planes_cuidado')) {
            return;
        }

        if ($this->hasForeignKey(self::FK_NAME)) {
            $this->db->query('ALTER TABLE `planes_cuidado` DROP FOREIGN KEY `' . self::FK_NAME . '`');
        }

        if ($this->db->fieldExists('plan_estandar_id', 'planes_cuidado')) {
            $this->forge->dropColumn('planes_cuidado', 'plan_estandar_id');
        }
    }

    private function hasForeignKey(string $constraintName): bool
    {
        $database = $this->db->getDatabase();
        $sql      = 'SELECT COUNT(*) AS total
                     FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                     WHERE CONSTRAINT_SCHEMA = ?
                     AND TABLE_NAME = ?
                     AND CONSTRAINT_NAME = ?
                     AND CONSTRAINT_TYPE = ?';

        $query = $this->db->query($sql, [$database, 'planes_cuidado', $constraintName, 'FOREIGN KEY']);

        $row = $query->getFirstRow();

        return ($row->total ?? 0) > 0;
    }
}

