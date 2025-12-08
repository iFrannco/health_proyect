<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldsToPlanEstandarActividadMigration extends Migration
{
    public function up()
    {
        // 1. Renombrar tablas para cumplir con GEMINI.md (Plural -> Singular)
        if ($this->db->tableExists('planes_estandar') && !$this->db->tableExists('plan_estandar')) {
            $this->forge->renameTable('planes_estandar', 'plan_estandar');
        }

        if ($this->db->tableExists('plan_estandar_actividades') && !$this->db->tableExists('plan_estandar_actividad')) {
            $this->forge->renameTable('plan_estandar_actividades', 'plan_estandar_actividad');
        }

        // 2. Agregar tipo_diagnostico_id a plan_estandar
        if ($this->db->tableExists('plan_estandar') && !$this->db->fieldExists('tipo_diagnostico_id', 'plan_estandar')) {
            $this->forge->addColumn('plan_estandar', [
                'tipo_diagnostico_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true, // Nullable inicialmente por si hay datos, luego se puede ajustar
                    'after'      => 'vigente',
                ],
            ]);
            
            // Agregar FK si es posible (asumiendo que tipo_diagnostico existe)
             // Nota: Se hace en paso separado para evitar fallos si la tabla no está vacía
             $this->db->query('ALTER TABLE `plan_estandar` ADD CONSTRAINT `fk_plan_estandar_tipo_diagnostico` FOREIGN KEY (`tipo_diagnostico_id`) REFERENCES `tipos_diagnostico`(`id`) ON DELETE SET NULL ON UPDATE CASCADE');
        }

        // 3. Agregar campos a plan_estandar_actividad
        if ($this->db->tableExists('plan_estandar_actividad')) {
            $fieldsToAdd = [];

            if (!$this->db->fieldExists('vigente', 'plan_estandar_actividad')) {
                $fieldsToAdd['vigente'] = [
                    'type'       => 'BOOLEAN',
                    'default'    => true,
                    'after'      => 'orden',
                ];
            }
            if (!$this->db->fieldExists('frecuencia_repeticiones', 'plan_estandar_actividad')) {
                $fieldsToAdd['frecuencia_repeticiones'] = [
                    'type'       => 'INT',
                    'constraint' => 5,
                    'null'       => true,
                    'after'      => 'vigente',
                ];
            }
            if (!$this->db->fieldExists('frecuencia_periodo', 'plan_estandar_actividad')) {
                $fieldsToAdd['frecuencia_periodo'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'frecuencia_repeticiones',
                ];
            }
            if (!$this->db->fieldExists('duracion_valor', 'plan_estandar_actividad')) {
                $fieldsToAdd['duracion_valor'] = [
                    'type'       => 'INT',
                    'constraint' => 5,
                    'null'       => true,
                    'after'      => 'frecuencia_periodo',
                ];
            }
            if (!$this->db->fieldExists('duracion_unidad', 'plan_estandar_actividad')) {
                $fieldsToAdd['duracion_unidad'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'duracion_valor',
                ];
            }

            if (!empty($fieldsToAdd)) {
                $this->forge->addColumn('plan_estandar_actividad', $fieldsToAdd);
            }
        }
    }

    public function down()
    {
        // Revertir campos en plan_estandar_actividad
        if ($this->db->tableExists('plan_estandar_actividad')) {
            $this->forge->dropColumn('plan_estandar_actividad', [
                'vigente',
                'frecuencia_repeticiones',
                'frecuencia_periodo',
                'duracion_valor',
                'duracion_unidad',
            ]);
        }

        // Revertir campo en plan_estandar
        if ($this->db->tableExists('plan_estandar')) {
             // Dropear FK primero
            $this->db->query('ALTER TABLE `plan_estandar` DROP FOREIGN KEY `fk_plan_estandar_tipo_diagnostico`');
            $this->forge->dropColumn('plan_estandar', 'tipo_diagnostico_id');
        }

        // Revertir nombres de tablas (Singular -> Plural)
        if ($this->db->tableExists('plan_estandar') && !$this->db->tableExists('planes_estandar')) {
            $this->forge->renameTable('plan_estandar', 'planes_estandar');
        }

        if ($this->db->tableExists('plan_estandar_actividad') && !$this->db->tableExists('plan_estandar_actividades')) {
            $this->forge->renameTable('plan_estandar_actividad', 'plan_estandar_actividades');
        }
    }
}
