<?php

namespace Meiko\Patchable;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * Patchable trait for models
 */
trait Patchable
{
    /**
     * Changes to attributes and relationships
     *
     * @var array
     */
    protected $patchChanges = [
        'attributes' => [],
        'relations' => [],
    ];

    /**
     * Get changes
     *
     * @return array
     */
    public function getChanges()
    {
        return array_filter($this->patchChanges);
    }

    /**
     * Patch model
     *
     * @param array $data
     * @return void
     */
    public function patch(array $data)
    {
        $this->verifyModel($data);

        if (!empty($data['attributes'])) {
            $this->patchAttributes($data['attributes']);
        }

        if (!empty($data['relationships'])) {
            $this->patchSingularRelationships($data['relationships']);
            $this->patchPluralRelationships($data['relationships']);
        }

        return $this;
    }

    /**
     * Verify model based on id if present and type
     */
    protected function verifyModel(array $data)
    {
        $key = $this->getRouteKeyName();

        if ($this->$key && $this->$key != $data['id']) {
            throw new ConflictHttpException('Model id does not match');
        }

        $namespace = config('patchable.namespace', '\App\Models');
        $separatorCount = count(explode('\\', $namespace));

        $className = array_slice(explode('\\', get_class($this)), $separatorCount);
        if (studly_case(implode('_', $className)) != studly_case($data['type'])) {
            throw new ConflictHttpException('Model type does not match');
        }
    }

    /**
     * Patching the attributes
     *
     * @param array $attributes
     * @return void
     */
    protected function patchAttributes(array $attributes)
    {
        $originals = [];
        $patchable = (empty($this->patchable)) ? $this->fillable : $this->patchable;

        foreach ($attributes as $key => $value) {
            $key = snake_case($key);

            if (!in_array($key, $patchable)) {
                continue;
            }

            $originalValue = $this->getAttribute($key);
            if (is_object($originalValue) && is_a($originalValue, Carbon::class)) {
                $originals[$key] = $originalValue->format('c');
            } else {
                $originals[$key] = $originalValue;
            }
            $this->$key = $value;
        }

        foreach ($this->getDirty() as $attr => $value) {
            $oldValue = $originals[$attr] ?? null;
            if (is_object($oldValue) && is_a($oldValue, Carbon::class)) {
                $oldValue = $oldValue->format('c');
            }

            $newValue = $this->getAttribute($attr);
            if (is_object($newValue) && is_a($newValue, Carbon::class)) {
                $newValue = $newValue->format('c');
            }

            if ($oldValue == $newValue) {
                continue;
            }

            $this->patchChanges['attributes'][$attr] = [
                'from' => $originals[$attr] ?? null,
                'to' => $newValue,
            ];
        }
    }

    /**
     * Patching singular relationships (BelongsTo, HasOne)
     *
     * @param array $relationships
     * @return void
     */
    protected function patchSingularRelationships(array $relationships)
    {
        $relationships = array_filter($relationships, function ($key) {
            return ($this->$key() instanceof BelongsTo
                || $this->$key() instanceof HasOne);
        }, ARRAY_FILTER_USE_KEY);

        foreach ($relationships as $key => $relations) {
            $model = null;
            if (!empty($relations['data'])) {
                $model = $this->resolveRelationsModel($relations['data']['id'], $relations['data']['type']);
            }

            $oldModelId = ($this->$key) ? $this->$key->id : null;

            if ($this->$key() instanceof HasOne) {
                if ($model) {
                    $this->$key()->save($model);
                } elseif (!$model && $oldModelId) {
                    $this->$key()->delete();
                }
            } elseif ($this->$key() instanceof BelongsTo) {
                if ($model) {
                    $this->$key()->associate($model);
                } elseif (!$model && $oldModelId) {
                    $this->$key()->dissociate();
                }
            }

            if ($oldModelId != (($model) ? $model->id : null)) {
                $this->patchChanges['relations'][$key] = [
                    'from' => $oldModelId,
                    'to' => ($model) ? $model->id : null,
                ];
            }
        }
    }

    /**
     * Patching plural relationships (HasMany, BelongsToMany)
     *
     * @param array $relationships
     * @return void
     */
    protected function patchPluralRelationships(array $relationships)
    {
        $relationships = array_filter($relationships, function ($key) {
            return ($this->$key() instanceof HasMany
                || $this->$key() instanceof BelongsToMany);
        }, ARRAY_FILTER_USE_KEY);

        if (!empty($relationships) && empty($this->id)) {
            // force model to save if it is new
            $this->save();
        }

        foreach ($relationships as $key => $relations) {
            $to = [];
            foreach ($relations['data'] as $relation) {
                $to[] = $this->resolveRelationsModel($relation['id'], $relation['type']);
            }

            $from = [];
            foreach ($this->$key as $model) {
                $from[] = $model;
            }

            if ($this->$key() instanceof HasMany) {
                $this->$key()->delete();

                if (!empty($to)) {
                    $this->$key()->saveMany($to);
                }
            } elseif ($this->$key() instanceof BelongsToMany) {
                $toIds = [];
                foreach ($to as $model) {
                    $toIds[] = $model->id;
                }

                $this->$key()->sync($toIds);
            }

            if ($from != $to) {
                $fromIds = [];
                foreach ($from as $model) {
                    $fromIds[] = $model->id;
                }

                $toIds = [];
                foreach ($to as $model) {
                    $toIds[] = $model->id;
                }

                $this->patchChanges['relations'][$key] = [
                    'from' => $fromIds,
                    'to' => $toIds,
                ];
            }
        }
    }

    /**
     * Resolve type and id to a model instance
     *
     * @param string $id
     * @param string $type
     * @return void
     */
    protected function resolveRelationsModel(string $id, string $type)
    {
        $namespace = config('patchable.namespace', '\App');
        $modelName = studly_case($type);
        $className = $namespace . '\\' . $modelName;
        $instance = new $className;

        return $instance->where($instance->getRouteKeyName(), $id)->firstOrFail();
    }
}
