<?php

namespace Ravenna\MassModelEvents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Symfony\Component\Routing\Exception\InvalidParameterException;

trait HasMassModelEvents
{
    protected EloquentCollection $massOperationModels;

    protected function initializeHasMassModelEvents(): void
    {
        $this->addObservableEvents([
            'massUpdated',
            'massUpdating',
            'massDeleted',
            'massDeleting',
//            'massTrashed',
//            'massTrashing'
        ]);
    }

    /**
     * @return Model[]
     */
    public function getMassOperationModels(): EloquentCollection
    {
        return $this->massOperationModels;
    }

    public static function checkArrayable($ids): bool
    {
        return (($ids instanceof BaseCollection) && array_is_list($ids->all())) ||
            (($ids instanceof EloquentCollection) && array_is_list($ids->all())) ||
            (is_array($ids) && array_is_list($ids));
    }

    public static function getModelsFromList($ids, $model)
    {
        $idCount = 0;
        $modelCount = 0;
        $total = count($ids);

        if ($total === 0) {
            throw new InvalidParameterException("No IDs provided to delete.");
        }

        for ($i = 0; $i < $total; $i++) {
            if ($ids[$i] instanceof Model) {
                $modelCount++;
                continue;
            }

            if (is_int($ids[$i]) || is_string($ids[$i])) {
                $idCount++;
                continue;
            }
        }

        if ($idCount === 0 && $modelCount === 0) {
            throw new InvalidParameterException("No valid IDs or models provided to delete.");
        }

        if ($idCount !== $total && $modelCount !== $total) {
            // TODO: Convert IDs into models?
            throw new InvalidParameterException("Must not mix models and IDs for deletion");
        }

        if ($idCount === $total) {
            $ids = $model::query()->whereIn($model->getKeyName(), $ids)->get();
        }

        return $ids;
    }

    /**
     * Destroy the models for the given IDs.
     *
     * @param  \Illuminate\Support\Collection|array|int|string  $ids
     * @throws InvalidParameterException
     * @return int
     */
    public static function destroy($ids): int
    {
        $arrayable = static::checkArrayable($ids);

        $instance = new static;
        $key = $instance->getKeyName();
        $softDeletes = isset($instance->forceDeleting);

        if ($arrayable) {
            $models = static::getModelsFromList($ids, $instance);

            $instance->massOperationModels = $models;
            $instance->fireModelEvent('massDeleting');

            if ($softDeletes) {
                // TODO: Add feature
            } else {
                $instance::query()
                    ->whereIn($key, $models->pluck($key))
                    ->delete();

                foreach ($instance->massOperationModels as $model) {
                    $model->exists = false;
                }
            }

            $instance->fireModelEvent('massDeleted');

            return $instance->massOperationModels->count();
        } else if (is_int($ids) || is_string($ids)) {
            return $instance::query()->where($key, $ids)->delete();
        } else {
            throw new InvalidParameterException("Invalid ID type provided to delete.");
        }
    }

    /**
     * Update the models for the given IDs.
     *
     * @param  \Illuminate\Support\Collection|array|int|string  $ids
     * @return int
     */
    public static function patch($ids, array $attributes, array $options = []): int
    {
        $arrayable = static::checkArrayable($ids);

        $instance = new static;
        $key = $instance->getKeyName();

        if ($arrayable) {
            $models = static::getModelsFromList($ids, $instance);
            $instance->massOperationModels = $models;

            $instance->fireModelEvent('massUpdating');

            $countUpdated = $instance::query()
                ->whereIn($key, $models->pluck($key))
                ->update($attributes, $options);

            $instance->fireModelEvent('massUpdated');

            return $countUpdated;
        }

        return $instance::query()->where($key, $ids)->update($attributes, $options);
    }
}