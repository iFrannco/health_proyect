<?php

namespace App\Models;

use CodeIgniter\I18n\Time;
use CodeIgniter\Model;

class PasswordResetModel extends Model
{
    protected $table         = 'password_resets';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'user_id',
        'token_hash',
        'expires_at',
        'used_at',
        'created_at',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function findValidByTokenHash(string $tokenHash): ?array
    {
        $now = Time::now()->toDateTimeString();

        return $this->where('token_hash', $tokenHash)
            ->where('used_at', null)
            ->where('expires_at >=', $now)
            ->first();
    }

    public function invalidateExistingForUser(int $userId): void
    {
        $now = Time::now()->toDateTimeString();

        $this->set(['used_at' => $now])
            ->where('user_id', $userId)
            ->where('used_at', null)
            ->update();
    }
}
