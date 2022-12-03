<?php

declare(strict_types=1);

/**
 * Add support for softDeleteUnique columns to Laravel Model classes.
 *
 * use Tranzakt\SoftDeleteUnique\Concerns\HasSoftDeleteUnique;
 *
 * @copyright 2022 Tranzakt
 * @author Sophist
 */

namespace Tranzakt\SoftDeletesUnique\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;
use Tranzakt\SoftDeletesUnique\Observers\SoftDeletesUniqueObserver;

trait HasSoftDeletesUnique
{
    use SoftDeletes;

    /**
     * Indicate that this model has softDeleteUnique.
     */
    public bool $softDeleteUnique = true;

    /**
     * Bootstrap the trait.
     *
     * @return void
     */
    public static function bootHasSoftDeletesUnique()
    {
        static::observe(SoftDeletesUniqueObserver::class);
    }

    /**
     * Determine if the model uses softDeleteUnique.
     *
     */
    public function usesSoftDeleteUnique(): bool
    {
        return $this->softDeleteUnique ?? false;
    }

    /**
     * Has the model loaded the HasSoftDeletesUnique trait.
     */
    private function usingClass(string $class): bool
    {
        return
            in_array(
                $class,
                class_uses_recursive(
                    get_called_class()
                )
            );
    }

    /**
     * Has the model loaded the HasSoftDeletesUnique trait.
     */
    public function usingSoftDeletesUnique(): bool
    {
        return
            $this->usingClass('Illuminate\Database\Eloquent\SoftDeletes') &&
            $this->usingClass('Tranzakt\SoftDeletesUnique\Concerns\HasSoftDeletesUnique');
    }

    /**
     * Get the name of the "deleted at str" column.
     *
     *
     */
    public function getDeletedAtUniqueableColumn()
    {
        return defined(static::class.'::DELETED_AT_UNIQUEABLE') ? constant('static::DELETED_AT_UNIQUEABLE') : 'deleted_at_uniqueable';
    }
}