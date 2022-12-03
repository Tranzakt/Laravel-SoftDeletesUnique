<?php

declare(strict_types=1);

/**
 * Initialise support for softDeleteUnique columns.
 *
 * @copyright 2022 Tranzakt
 * @author Sophist
 */

namespace Tranzakt\SoftDeletesUnique;

use Illuminate\Support\ServiceProvider;
use Tranzakt\SoftDeletesUnique\Macros\SoftDeletesUniqueMacro;

class SoftDeletesUniqueServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $macro = new SoftDeletesUniqueMacro();
        $macro->register();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
    }
}