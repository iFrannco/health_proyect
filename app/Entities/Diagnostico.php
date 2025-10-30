<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Diagnostico extends Entity
{
    protected $datamap = [];
    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
        'fecha_creacion'
    ];
    protected $casts   = [
        'id' => 'integer',
        'autor_user_id' => 'integer',
        'destinatario_user_id' => 'integer',
        'tipo_diagnostico_id' => 'integer'
    ];

    /**
     * Mantiene los planes cargados en memoria para este diagnóstico.
     *
     * @var array<int, PlanCuidado>
     */
    protected $attributes = [
        'planes_cuidado' => [],
    ];

    /**
     * Devuelve los planes garantizando instancias de PlanCuidado.
     *
     * @param mixed $planes
     *
     * @return PlanCuidado[]
     */
    protected function getPlanesCuidado($planes): array
    {
        if (empty($planes) || ! is_array($planes)) {
            return [];
        }

        return array_map(static function ($plan): PlanCuidado {
            return $plan instanceof PlanCuidado
                ? $plan
                : new PlanCuidado((array) $plan);
        }, $planes);
    }

    /**
     * Asegura que siempre almacenemos PlanCuidado[] internamente.
     *
     * @param PlanCuidado[]|array<int, array<string, mixed>>|null $planes
     */
    protected function setPlanesCuidado($planes): void
    {
        if ($planes === null) {
            $this->attributes['planes_cuidado'] = [];

            return;
        }

        $lista = array_map(static function ($plan): PlanCuidado {
            return $plan instanceof PlanCuidado
                ? $plan
                : new PlanCuidado((array) $plan);
        }, (array) $planes);

        $this->attributes['planes_cuidado'] = $lista;
    }

    /**
     * Añade un plan al diagnóstico y sincroniza la referencia inversa.
     */
    public function addPlanCuidado(PlanCuidado $plan): self
    {
        $planes   = $this->attributes['planes_cuidado'] ?? [];
        $planes[] = $plan;

        $plan->diagnostico = $this;
        if ($this->attributes['id'] ?? null) {
            $plan->diagnostico_id = $this->attributes['id'];
        }

        $this->attributes['planes_cuidado'] = $planes;

        return $this;
    }
}
