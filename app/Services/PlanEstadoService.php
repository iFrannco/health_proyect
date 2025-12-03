<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;

class PlanEstadoService
{
    public const ESTADO_SIN_INICIAR = 'sin_iniciar';
    public const ESTADO_EN_CURSO    = 'en_curso';
    public const ESTADO_FINALIZADO  = 'finalizado';

    /**
     * Devuelve el estado canonizado del plan, su etiqueta y si está listo para ser finalizado.
     *
     * @return array{estado: string, etiqueta: string, sePuedeFinalizar: bool, fueFinalizado: bool}
     */
    public static function calcular(?string $estadoActual, ?string $fechaInicio, ?string $fechaFin, ?DateTimeImmutable $hoy = null): array
    {
        $hoy = $hoy ?? new DateTimeImmutable('today');

        $estadoNormalizado = self::normalizar($estadoActual);
        $fechaInicioObj    = self::crearFecha($fechaInicio);
        $fechaFinObj       = self::crearFecha($fechaFin);

        if ($estadoNormalizado === self::ESTADO_FINALIZADO) {
            return [
                'estado'           => self::ESTADO_FINALIZADO,
                'etiqueta'         => self::etiqueta(self::ESTADO_FINALIZADO),
                'sePuedeFinalizar' => false,
                'fueFinalizado'    => true,
            ];
        }

        if ($fechaInicioObj !== null && $hoy < $fechaInicioObj) {
            $estadoNormalizado = self::ESTADO_SIN_INICIAR;
        } else {
            $estadoNormalizado = self::ESTADO_EN_CURSO;
        }

        $puedeFinalizar = $estadoNormalizado !== self::ESTADO_FINALIZADO
            && $fechaFinObj !== null
            && $hoy > $fechaFinObj;

        return [
            'estado'           => $estadoNormalizado,
            'etiqueta'         => self::etiqueta($estadoNormalizado),
            'sePuedeFinalizar' => $puedeFinalizar,
            'fueFinalizado'    => false,
        ];
    }

    public static function etiqueta(string $estado): string
    {
        return match ($estado) {
            self::ESTADO_FINALIZADO  => 'Finalizado',
            self::ESTADO_SIN_INICIAR => 'Sin iniciar',
            default                  => 'En curso',
        };
    }

    public static function normalizar(?string $estado): ?string
    {
        $valor = strtolower(trim((string) ($estado ?? '')));

        return match ($valor) {
            self::ESTADO_FINALIZADO,
            'terminado',
            'completado',
            'cerrado'                => self::ESTADO_FINALIZADO,
            self::ESTADO_EN_CURSO,
            self::ESTADO_SIN_INICIAR => $valor,
            default                  => null,
        };
    }

    public static function crearFecha(?string $fecha): ?DateTimeImmutable
    {
        if ($fecha === null || $fecha === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($fecha);
        } catch (\Throwable) {
            return null;
        }
    }
}
