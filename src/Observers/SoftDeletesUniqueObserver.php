<?php

declare(strict_types=1);

/**
 * Define observers for softDeleteUnique.
 *
 * @copyright 2022 Tranzakt
 * @author Sophist
 */

namespace Tranzakt\SoftDeletesUnique\Observers;

use Illuminate\Database\Eloquent\Model;

class SoftDeletesUniqueObserver
{
    public function creating(Model $model): void
    {
        $this->upsertUnique($model);
    }

    public function updating(Model $model): void
    {
        if(! $model->isDirty($model->getDeletedAtColumn()) || count($model->getDirty()) != 1){
            // not a restore
            $this->upsertUnique($model);
        }
    }

    private function upsertUnique(Model $model): void
    {
        $model->{$model->getDeletedAtUniqueableColumn()} = ((string) $model->{$model->getDeletedAtColumn()}) ?: '';
     }

    public function deleting(Model $model): void
    {
        if ($model->usingSoftDeletesUnique() && ! $model->isForceDeleting()) {
            $model->{$model->getDeletedAtUniqueableColumn()} = $model->freshTimestampString();
            $this->saveWithoutEventDispatching($model);
        }
    }

    public function restoring(Model $model): void
    {
        if ($model->usingSoftDeletesUnique()) {
            $model->{$model->getDeletedAtUniqueableColumn()} = '';
            $this->saveWithoutEventDispatching($model);
        }
    }

    /**
     * Saves a model by ignoring all other event dispatchers.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    private function saveWithoutEventDispatching(Model $model): bool
    {
        $eventDispatcher = $model->getEventDispatcher();

        $model->unsetEventDispatcher();
        $saved = $model->save();
        $model->setEventDispatcher($eventDispatcher);

        return $saved;
    }
}