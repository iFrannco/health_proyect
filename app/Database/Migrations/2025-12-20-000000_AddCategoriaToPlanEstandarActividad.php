<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCategoriaToPlanEstandarActividad extends Migration
{
    private const TABLA = 'plan_estandar_actividad';
    private const FK    = 'fk_plan_estandar_actividad_categoria';

    public function up(): void
    {
        if (! $this->db->tableExists(self::TABLA)) {
            return;
        }

        if (! $this->db->fieldExists('categoria_actividad_id', self::TABLA)) {
            $this->forge->addColumn(self::TABLA, [
                'categoria_actividad_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'plan_estandar_id',
                ],
            ]);
        }

        if ($this->db->tableExists('categoria_actividad') && ! $this->tieneForeignKey()) {
            $this->db->query(
                'ALTER TABLE `' . self::TABLA . '` 
                 ADD CONSTRAINT `' . self::FK . '` 
                 FOREIGN KEY (`categoria_actividad_id`) 
                 REFERENCES `categoria_actividad`(`id`) 
                 ON DELETE SET NULL ON UPDATE CASCADE'
            );
        }

        $categoriaDefault = $this->obtenerCategoriaDefaultId();
        if ($categoriaDefault !== null) {
            $this->db->table(self::TABLA)
                ->where('categoria_actividad_id', null)
                ->update(['categoria_actividad_id' => $categoriaDefault]);
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists(self::TABLA)) {
            return;
        }

        if ($this->tieneForeignKey()) {
            $this->db->query('ALTER TABLE `' . self::TABLA . '` DROP FOREIGN KEY `' . self::FK . '`');
        }

        if ($this->db->fieldExists('categoria_actividad_id', self::TABLA)) {
            $this->forge->dropColumn(self::TABLA, 'categoria_actividad_id');
        }
    }

    private function tieneForeignKey(): bool
    {
        $dbName = $this->db->database;

        $resultado = $this->db->query(
            'SELECT CONSTRAINT_NAME 
             FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$dbName, self::TABLA, self::FK]
        )->getResult();

        return ! empty($resultado);
    }

    private function obtenerCategoriaDefaultId(): ?int
    {
        if (! $this->db->tableExists('categoria_actividad')) {
            return null;
        }

        $categoria = $this->db->table('categoria_actividad')
            ->select('id')
            ->where('id', 1)
            ->where('activo', 1)
            ->get(1)
            ->getFirstRow('array');

        if ($categoria !== null) {
            return (int) $categoria['id'];
        }

        $primeraActiva = $this->db->table('categoria_actividad')
            ->select('id')
            ->where('activo', 1)
            ->orderBy('id', 'ASC')
            ->get(1)
            ->getFirstRow('array');

        return $primeraActiva !== null ? (int) $primeraActiva['id'] : null;
    }
}
