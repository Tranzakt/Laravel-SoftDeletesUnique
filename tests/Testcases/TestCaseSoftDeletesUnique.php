<?php

declare(strict_types=1);

/**
 * Common test case for SoftDeletesUnique unit tests.
 *
 * @copyright 2022 Tranzakt
 * @author Sophist
 */

namespace Tranzakt\SoftDeletesUnique\Tests\Testcases;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCaseSoftDeletesUnique extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return ['Tranzakt\SoftDeletesUnique\SoftDeletesUniqueServiceProvider'];
    }

    /**
     * Standardised function for creating a table
     *
     * @return array
     */
    protected function createTable(
        string $tableName,
        string $deleted_at = '',
        string $deleted_at_uniqueable = '',
        int    $precision = 0,
    ) {
        Model::shouldBeStrict(true);
        Schema::dropIfExists($tableName);
        Schema::create($tableName,
            function (Blueprint $table)
            use ($deleted_at, $deleted_at_uniqueable, $precision)
            {
                $table->id();
                $table->char('field');
                $table->timestamps();
                if (empty($deleted_at)) {
                    $table->softDeletes(precision: $precision);
                } else {
                    $table->softDeletes($deleted_at, precision: $precision);
                }
                if (empty($deleted_at_uniqueable)) {
                    $table->softDeletesUnique(precision: $precision);
                } else {
                    $table->softDeletesUnique($deleted_at_uniqueable, precision: $precision);
                }

                $table->unique([$deleted_at_uniqueable ?: 'deleted_at_uniqueable', 'field']);
                $table->unique([$deleted_at ?: 'deleted_at', 'field']);
        });

        return Schema::getColumnlisting($tableName);
    }
}