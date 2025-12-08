<?php

namespace App\Libraries;

use App\Entities\PlanEstandarActividad;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;

class CarePlanTemplate
{
    /**
     * Genera actividades concretas a partir de actividades de plantilla.
     *
     * @param PlanEstandarActividad[] $actividadesPlantilla
     * @return array{actividades: array<int, array<string, mixed>>, errores: string[]}
     */
    public function materializar(
        array $actividadesPlantilla,
        string $fechaInicioPlan,
        ?string $fechaFinPlan,
        int $categoriaActividadId
    ): array {
        $errores = [];
        $actividadesGeneradas = [];

        $inicio = $this->crearFecha($fechaInicioPlan, 'fecha de inicio del plan');
        $fin    = $fechaFinPlan !== null ? $this->crearFecha($fechaFinPlan, 'fecha de fin del plan') : null;
        $maxDiasCobertura = 0;

        foreach ($actividadesPlantilla as $actividad) {
            if (! $actividad instanceof PlanEstandarActividad) {
                $actividad = new PlanEstandarActividad((array) $actividad);
            }

            if (! $actividad->vigente) {
                continue;
            }

            $frecuenciaRepeticiones = max(0, (int) ($actividad->frecuencia_repeticiones ?? 0));
            $periodo                = $this->normalizarPeriodo($actividad->frecuencia_periodo ?? '');
            $duracionValor          = max(0, (int) ($actividad->duracion_valor ?? 0));
            $duracionUnidad         = $this->normalizarDuracionUnidad($actividad->duracion_unidad ?? '');
            $offsetInicio           = max(0, (int) ($actividad->offset_inicio_dias ?? 0));

            if ($frecuenciaRepeticiones <= 0 || $periodo === null || $duracionValor <= 0 || $duracionUnidad === null) {
                $errores[] = 'La actividad "' . ($actividad->nombre ?? 'Sin nombre') . '" tiene frecuencia o duración inválidas.';
                continue;
            }

            $diasPorPeriodo = $this->obtenerDiasPeriodo($periodo);
            $diasDeDuracion = $duracionValor * $this->obtenerDiasDuracion($duracionUnidad);

            if ($frecuenciaRepeticiones > $diasPorPeriodo) {
                $errores[] = 'La actividad "' . ($actividad->nombre ?? 'Sin nombre') . '" excede el máximo de repeticiones permitidas para el período.';
                continue;
            }

            if ($diasDeDuracion < $diasPorPeriodo) {
                $errores[] = 'La actividad "' . ($actividad->nombre ?? 'Sin nombre') . '" no cubre al menos un período completo.';
                continue;
            }

            $periodos = intdiv($diasDeDuracion, $diasPorPeriodo);
            if ($periodos <= 0) {
                $errores[] = 'La actividad "' . ($actividad->nombre ?? 'Sin nombre') . '" no genera períodos válidos.';
                continue;
            }

            $ultimoDiaActividad = $offsetInicio + ($periodos * $diasPorPeriodo) - 1;
            if ($ultimoDiaActividad > $maxDiasCobertura) {
                $maxDiasCobertura = $ultimoDiaActividad;
            }

            for ($periodoIndex = 0; $periodoIndex < $periodos; $periodoIndex++) {
                $offsetBase = $offsetInicio + ($periodoIndex * $diasPorPeriodo);
                $posiciones = $this->distribuirPosiciones($diasPorPeriodo, $frecuenciaRepeticiones);

                foreach ($posiciones as $pos) {
                    $fechaActividad = $inicio->add(new DateInterval('P' . ($offsetBase + $pos) . 'D'));

                    if ($fin !== null && $fechaActividad > $fin) {
                        $errores[] = 'Las actividades de "' . ($actividad->nombre ?? 'Sin nombre') . '" exceden la vigencia del plan.';
                        continue 2;
                    }

                    $fechaStr = $fechaActividad->format('Y-m-d');
                    $nombreActividad = (string) ($actividad->nombre ?? 'Actividad');
                    $descripcionActividad = (string) ($actividad->descripcion ?? '');

                    $actividadesGeneradas[] = [
                        'nombre'                 => $nombreActividad,
                        'descripcion'            => $descripcionActividad !== '' ? $descripcionActividad : null,
                        'fecha_inicio'           => $fechaStr,
                        'fecha_fin'              => $fechaStr,
                        'categoria_actividad_id' => $categoriaActividadId,
                    ];
                }
            }
        }

        if ($fin === null) {
            $fin = $inicio->add(new DateInterval('P' . $maxDiasCobertura . 'D'));
        } elseif ($inicio > $fin) {
            return [
                'actividades' => [],
                'errores'     => ['La fecha de inicio del plan no puede ser posterior a la fecha de fin.'],
            ];
        }

        usort($actividadesGeneradas, static function (array $a, array $b): int {
            return strcmp($a['fecha_inicio'] ?? '', $b['fecha_inicio'] ?? '');
        });

        return [
            'actividades' => $actividadesGeneradas,
            'errores'     => $errores,
            'fecha_fin_calculada' => $fin->format('Y-m-d'),
        ];
    }

    private function crearFecha(string $fecha, string $contexto): DateTimeImmutable
    {
        $instancia = DateTimeImmutable::createFromFormat('Y-m-d', $fecha);

        if (! $instancia) {
            throw new InvalidArgumentException('Fecha inválida para ' . $contexto . '.');
        }

        return $instancia;
    }

    private function normalizarPeriodo(string $periodo): ?string
    {
        $periodo = mb_strtolower(trim($periodo));
        return match ($periodo) {
            'dia', 'día', 'día(s)', 'dias', 'días' => 'dia',
            'semana', 'semanas' => 'semana',
            'mes', 'meses' => 'mes',
            default => null,
        };
    }

    private function normalizarDuracionUnidad(string $unidad): ?string
    {
        $unidad = mb_strtolower(trim($unidad));
        return match ($unidad) {
            'dia', 'día', 'dia(s)', 'dias', 'días' => 'dia',
            'semana', 'semanas' => 'semana',
            'mes', 'meses' => 'mes',
            default => null,
        };
    }

    private function obtenerDiasPeriodo(string $periodo): int
    {
        return match ($periodo) {
            'dia' => 1,
            'semana' => 7,
            default => 30,
        };
    }

    private function obtenerDiasDuracion(string $unidad): int
    {
        return match ($unidad) {
            'dia' => 1,
            'semana' => 7,
            default => 30,
        };
    }

    /**
     * Distribuye equitativamente las repeticiones en el período.
     *
     * @return int[]
     */
    private function distribuirPosiciones(int $diasPeriodo, int $repeticiones): array
    {
        if ($repeticiones <= 0) {
            return [];
        }

        $posiciones = [];
        for ($i = 1; $i <= $repeticiones; $i++) {
            $pos = (int) floor(($i * $diasPeriodo) / ($repeticiones + 1));
            if ($pos >= $diasPeriodo) {
                $pos = $diasPeriodo - 1;
            }
            $posiciones[] = $pos;
        }

        return $posiciones;
    }
}
