<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Actividad extends Entity
{
    protected $datamap = [];
    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
        'fecha_creacion',
        'fecha_inicio',
        'fecha_fin',
        'paciente_completada_en',
    ];
    protected $casts   = [
        'id' => 'integer',
        'plan_id' => 'integer',
        'estado_id' => 'integer',
        'validado' => '?boolean',
    ];

    /**
     * Normaliza el valor booleando de validado admitiendo null.
     */
    protected function setValidado($val): void
    {
        if ($val === null || $val === '') {
            $this->attributes['validado'] = null;

            return;
        }

        $this->attributes['validado'] = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    /**
     * Lleva el comentario del paciente a null cuando queda vacío.
     *
     * @param mixed $comentario
     */
    protected function setPacienteComentario($comentario): void
    {
        $texto = trim((string) $comentario);

        $this->attributes['paciente_comentario'] = $texto === '' ? null : $texto;
    }
}
