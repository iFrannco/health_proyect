<?php

namespace App\Validation;

class CustomRules
{
    /**
     * Valida que la fecha (Y-m-d) sea anterior a hoy.
     */
    public function before_today(?string $date, ?string $fields = null, array $data = [], ?string &$error = null): bool
    {
        if ($date === null || trim($date) === '') {
            return true;
        }

        $date = trim($date);
        $parsed = \DateTime::createFromFormat('Y-m-d', $date);

        if (! $parsed || $parsed->format('Y-m-d') !== $date) {
            // Deja que valid_date capture formato inválido.
            return false;
        }

        $today = new \DateTimeImmutable('today');

        return $parsed < $today;
    }
}
