<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\BaseConnection;

class DropUniqueConstraintFromDni extends Migration
{
    public function up(): void
    {
        $db = db_connect();

        $this->dropIndexIfExists($db, 'dni');
        $this->dropIndexIfExists($db, 'users_dni');

        if (! $this->hasIndex($db, 'idx_users_dni')) {
            $db->query('ALTER TABLE `users` ADD INDEX `idx_users_dni` (`dni`)');
        }
    }

    public function down(): void
    {
        $db = db_connect();

        $this->dropIndexIfExists($db, 'idx_users_dni');

        if (! $this->hasUniqueIndex($db)) {
            $db->query('ALTER TABLE `users` ADD UNIQUE KEY `dni` (`dni`)');
        }
    }

    private function dropIndexIfExists(BaseConnection $db, string $indexName): void
    {
        if ($indexName === '') {
            return;
        }

        $exists = $db->query('SHOW INDEX FROM `users` WHERE `Key_name` = ?', [$indexName])->getResultArray();
        if ($exists !== []) {
            $db->query(sprintf('ALTER TABLE `users` DROP INDEX `%s`', str_replace('`', '``', $indexName)));
        }
    }

    private function hasIndex(BaseConnection $db, string $indexName): bool
    {
        if ($indexName === '') {
            return false;
        }

        $exists = $db->query('SHOW INDEX FROM `users` WHERE `Key_name` = ?', [$indexName])->getResultArray();

        return $exists !== [];
    }

    private function hasUniqueIndex(BaseConnection $db): bool
    {
        $exists = $db->query("SHOW INDEX FROM `users` WHERE `Column_name` = 'dni' AND `Non_unique` = 0")->getResultArray();

        return $exists !== [];
    }
}
