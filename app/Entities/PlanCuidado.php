<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class PlanCuidado extends Entity
{
    protected $datamap = [];
    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
        'fecha_creacion',
        'fecha_inicio',
        'fecha_fin'
    ];
    protected $casts   = [
        'id' => 'integer',
        'diagnostico_id' => 'integer',
        'creador_user_id' => 'integer',
        'plan_estandar_id' => '?integer',
        'estado' => 'string'
    ];

    /**
     * Valores por defecto en memoria para evitar comprobaciones externas.
     *
     * @var array{
     *     actividades: array<int, Actividad>,
     *     diagnostico: Diagnostico|null
     * }
     */
    protected $attributes = [
        'actividades' => [],
        'diagnostico' => null,
        'plan_estandar_id' => null,
        'creador_user_id' => null,
    ];

    /**
     * Devuelve las actividades garantizando instancias de Actividad.
     *
     * @param mixed $value
     *
     * @return Actividad[]
     */
    protected function getActividades($value): array
    {
        if (empty($value) || ! is_array($value)) {
            return [];
        }

        return array_map(static function ($actividad): Actividad {
            return $actividad instanceof Actividad
                ? $actividad
                : new Actividad((array) $actividad);
        }, $value);
    }

    /**
     * Asegura que siempre almacenemos Actividad[] internamente.
     *
     * @param Actividad[]|array<int, array<string, mixed>> $actividades
     */
    protected function setActividades($actividades): void
    {
        if ($actividades === null) {
            $this->attributes['actividades'] = [];

            return;
        }

        $lista = array_map(static function ($actividad): Actividad {
            if ($actividad instanceof Actividad) {
                return $actividad;
            }

            return new Actividad((array) $actividad);
        }, (array) $actividades);

        $this->attributes['actividades'] = $lista;
    }

    /**
     * Añade una actividad al plan en memoria.
     */
    public function addActividad(Actividad $actividad): self
    {
        $actividades   = $this->attributes['actividades'] ?? [];
        $actividades[] = $actividad;

        $this->attributes['actividades'] = $actividades;

        return $this;
    }

    /**
     * Devuelve el diagnóstico asociado como entidad (si existe).
     *
     * @param mixed $diagnostico
     */
    protected function getDiagnostico($diagnostico): ?Diagnostico
    {
        if (! $diagnostico) {
            return null;
        }

        return $diagnostico instanceof Diagnostico
            ? $diagnostico
            : new Diagnostico((array) $diagnostico);
    }

    /**
     * Acepta arrays o entidades para establecer el diagnóstico.
     *
     * @param Diagnostico|array<string, mixed>|null $diagnostico
     */
    protected function setDiagnostico($diagnostico): void
    {
        if ($diagnostico === null) {
            $this->attributes['diagnostico'] = null;

            return;
        }

        if (! $diagnostico instanceof Diagnostico) {
            $diagnostico = new Diagnostico((array) $diagnostico);
        }

        $this->attributes['diagnostico'] = $diagnostico;

        if ($diagnostico->id !== null) {
            $this->attributes['diagnostico_id'] = $diagnostico->id;
        }
    }
}
