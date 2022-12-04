<?php

declare(strict_types=1);

/**
 * Macro to add support for softDeleteUnique columns to Laravel Build classes.
 *
 * @copyright 2022 Tranzakt
 * @author Sophist
 */

namespace Tranzakt\SoftDeletesUnique\Macros;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use SebastianBergmann\LinesOfCode\NegativeValueException;

final class SoftDeletesUniqueMacro
{
    /**
     * Bootstrap the schema macro.
     */
    public function register(): void
    {
        $this->registerSoftDeletesUnique();
        $this->registerDropSoftDeletesUnique();
    }

    private function registerSoftDeletesUnique(): void
    {
        Blueprint::macro('softDeletesUnique', function (?string $column = '', ?int $precision = 0): ColumnDefinition
        {
            if ($precision < 0) {
                throw New \ValueError('softDeletesUnique: optional precision must be >= 0');
            }
            $precision = min($precision, 6);
            $maxLen = $precision > 0 ? 19 + $precision : 18;

            /** @var Blueprint $this */
            return $this->string($column ?: 'deleted_at_uniqueable', $maxLen)->default('');
        });
    }

    private function registerDropSoftDeletesUnique(): void
    {
        Blueprint::macro('dropSoftDeletesUnique', function (?string $column = ''): void
        {
            /** @var Blueprint $this */
            $this->dropColumn($column ?: 'deleted_at_uniqueable');
        });
    }
}