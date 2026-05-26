<?php

namespace App\Services;

class VswrCalculator
{
    public static function calculate(?float $forwardPower, ?float $reflectedPower): array
    {
        if (!$forwardPower || $forwardPower <= 0) {
            return ['vswr' => null, 'return_loss' => null, 'gamma' => null];
        }
        $reflected = max(0, min($reflectedPower ?? 0, $forwardPower));
        $ratio     = $reflected / $forwardPower;
        $gamma     = sqrt($ratio);

        if ($gamma >= 1) {
            return ['vswr' => 99.99, 'return_loss' => 0.0, 'gamma' => round($gamma, 4)];
        }

        $vswr       = (1 + $gamma) / (1 - $gamma);
        $returnLoss = $ratio > 0 ? -10 * log10($ratio) : null;

        return [
            'vswr'        => round($vswr, 4),
            'return_loss' => $returnLoss ? round($returnLoss, 3) : null,
            'gamma'       => round($gamma, 4),
        ];
    }

    public static function calculateAll(array $data): array
    {
        $final = self::calculate($data['output_final_pa'] ?? null, $data['reflect_final'] ?? null);
        return [
            'vswr_final'        => $final['vswr'],
            'return_loss_final' => $final['return_loss'],
        ];
    }

    public static function getStatus(float $vswr): array
    {
        if ($vswr <= 1.2)      return ['label' => 'Sangat Baik', 'color' => 'success', 'icon' => '✅'];
        if ($vswr <= 1.5)      return ['label' => 'Baik',        'color' => 'success', 'icon' => '✅'];
        if ($vswr <= 2.0)      return ['label' => 'Sedang',      'color' => 'warning', 'icon' => '⚠️'];
        if ($vswr <= 3.0)      return ['label' => 'Buruk',       'color' => 'danger',  'icon' => '❌'];
        return                        ['label' => 'Kritis',      'color' => 'danger',  'icon' => '🚨'];
    }
}
