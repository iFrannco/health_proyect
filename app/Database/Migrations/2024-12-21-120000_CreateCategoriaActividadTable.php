<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCategoriaActividadTable extends Migration
{
    private const TABLA_CATEGORIA = 'categoria_actividad';
    private const TABLA_ACTIVIDAD = 'actividades';

    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'descripcion' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'color_hex' => [
                'type'       => 'VARCHAR',
                'constraint' => 7,
                'null'       => true,
            ],
            'activo' => [
                'type'    => 'BOOLEAN',
                'null'    => false,
                'default' => 1,
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
        $this->forge->addKey('nombre', false, true);

        $this->forge->createTable(self::TABLA_CATEGORIA, true, ['ENGINE' => 'InnoDB']);

        $now = date('Y-m-d H:i:s');
        $categoriasIniciales = [
            [
                'id'          => 1,
                'nombre'      => 'Otras / Genérica',
                'descripcion' => 'Usar cuando ninguna categoría específica aplica.',
                'color_hex'   => '#6c757d',
                'activo'      => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => 2,
                'nombre'      => 'Educación sanitaria',
                'descripcion' => 'Material educativo, indicaciones e información al paciente.',
                'color_hex'   => '#17a2b8',
                'activo'      => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => 3,
                'nombre'      => 'Medicaciones',
                'descripcion' => 'Administración o seguimiento de fármacos.',
                'color_hex'   => '#007bff',
                'activo'      => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => 4,
                'nombre'      => 'Ejercicio / Indicaciones',
                'descripcion' => 'Rutinas físicas, indicaciones de hábitos o autocuidado.',
                'color_hex'   => '#28a745',
                'activo'      => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => 5,
                'nombre'      => 'Controles y seguimiento',
                'descripcion' => 'Controles clínicos, turnos o mediciones programadas.',
                'color_hex'   => '#ffc107',
                'activo'      => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];

        // Evitar duplicados si la migración se reintenta con datos ya insertados.
        $idsSemilla = array_column($categoriasIniciales, 'id');
        $existentes = $this->db->table(self::TABLA_CATEGORIA)
            ->select('id')
            ->whereIn('id', $idsSemilla)
            ->get()
            ->getResultArray();

        $idsExistentes = array_map(static fn ($row) => (int) ($row['id'] ?? 0), $existentes);
        $faltantes = array_filter($categoriasIniciales, static function (array $categoria) use ($idsExistentes) {
            return ! in_array((int) $categoria['id'], $idsExistentes, true);
        });

        if (! empty($faltantes)) {
            $this->db->table(self::TABLA_CATEGORIA)->insertBatch($faltantes);
        }

        // Añadir columna + constraint solo si no existe ya (para reintentos seguros).
        $columnas = $this->db->getFieldNames(self::TABLA_ACTIVIDAD);
        $existeColumna = in_array('categoria_actividad_id', $columnas, true);

        if (! $existeColumna) {
            $this->forge->addColumn(self::TABLA_ACTIVIDAD, [
                'categoria_actividad_id' => [
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => false,
                    'default'    => 1,
                    'after'      => 'validado',
                ],
            ]);
        }

        // Índice y FK manuales para asegurar compatibilidad con la versión de Forge.
        $existeIdx = $this->db->query("
            SHOW INDEX FROM " . $this->db->escapeIdentifiers(self::TABLA_ACTIVIDAD) . " WHERE Key_name = 'idx_actividades_categoria'
        ")->getFirstRow();

        if ($existeIdx === null) {
            $this->db->query(sprintf(
                'ALTER TABLE %s ADD INDEX idx_actividades_categoria (categoria_actividad_id)',
                $this->db->escapeIdentifiers(self::TABLA_ACTIVIDAD)
            ));
        }

        $existeFk = $this->db->query("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = 'fk_actividades_categoria'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ", [self::TABLA_ACTIVIDAD])->getFirstRow();

        if ($existeFk === null) {
            $this->db->query(sprintf(
                'ALTER TABLE %s ADD CONSTRAINT fk_actividades_categoria FOREIGN KEY (categoria_actividad_id) REFERENCES %s(id) ON DELETE RESTRICT ON UPDATE RESTRICT',
                $this->db->escapeIdentifiers(self::TABLA_ACTIVIDAD),
                $this->db->escapeIdentifiers(self::TABLA_CATEGORIA)
            ));
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists(self::TABLA_ACTIVIDAD)) {
            $tablaEscapada = $this->db->escapeIdentifiers(self::TABLA_ACTIVIDAD);

        $existeFk = $this->db->query("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = 'fk_actividades_categoria'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ", [self::TABLA_ACTIVIDAD])->getFirstRow();

            if ($existeFk !== null) {
                $this->db->query('ALTER TABLE ' . $tablaEscapada . ' DROP FOREIGN KEY fk_actividades_categoria');
            }

            $existeIdx = $this->db->query("
                SHOW INDEX FROM " . $tablaEscapada . " WHERE Key_name = 'idx_actividades_categoria'
            ")->getFirstRow();

            if ($existeIdx !== null) {
                $this->db->query('ALTER TABLE ' . $tablaEscapada . ' DROP INDEX idx_actividades_categoria');
            }

            $this->forge->dropColumn(self::TABLA_ACTIVIDAD, ['categoria_actividad_id']);
        }

        $this->forge->dropTable(self::TABLA_CATEGORIA, true);
    }
}
