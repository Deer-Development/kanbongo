<?php

namespace App\Traits;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

trait TracksActivity
{
    // Eliminăm proprietatea de aici și o lăsăm doar în model
    protected static ?string $batchUuid = null;
    private static array $defaultRecordableEvents = ['created', 'updated', 'deleted'];

    protected static function bootTracksActivity(): void
    {
        if (static::shouldTrackActivity() === false) return;

        foreach (self::$defaultRecordableEvents as $event) {
            static::$event(function (Model $model) use ($event) {
                // Pentru update, verificăm dacă există modificări în câmpurile urmărite
                if ($event === 'updated') {
                    $recordableFields = static::$recordableFields ?? array_keys($model->getDirty());
                    $changes = array_intersect_key($model->getDirty(), array_flip($recordableFields));
                    
                    // Înregistrăm activitatea doar dacă există modificări în câmpurile urmărite
                    if (!empty($changes)) {
                        $model->recordActivity($event);
                    }
                } else {
                    $model->recordActivity($event);
                }
            });
        }
    }

    // Adăugăm o metodă pentru a verifica dacă trebuie să urmărim activitățile
    protected static function shouldTrackActivity(): bool
    {
        return !isset(static::$disableActivityTracking) || static::$disableActivityTracking === false;
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    protected static function getRecordableEvents(): array
    {
        return self::$defaultRecordableEvents;
    }

    public function recordActivity(string $event, array $properties = []): void
    {
        if (!Auth::check()) return;

        $properties = array_merge(
            $this->getActivityProperties($event),
            $properties
        );

        $containerId = $this->getContainerId();

        if (!$containerId) {
            return;
        }

        $this->activities()->create([
            'user_id' => Auth::id(),
            'container_id' => $containerId,
            'event' => $event,
            'properties' => $properties,
            'batch_uuid' => static::$batchUuid,
        ]);
    }

    protected function getContainerId()
    {
        // Încercăm să obținem container_id din diferite surse
        if (isset($this->container_id)) {
            return $this->container_id;
        }

        if (isset($this->attributes['container_id'])) {
            return $this->attributes['container_id'];
        }

        // Verificăm dacă avem relația board și apoi container_id
        if (method_exists($this, 'board') && $this->board) {
            return $this->board->container_id;
        }

        // Verificăm dacă avem relația task și apoi board->container_id
        if (method_exists($this, 'task') && $this->task) {
            return $this->task->board->container_id;
        }

        return null;
    }

    protected function getActivityProperties(string $event): array
    {
        if ($event === 'updated') {
            $dirty = $this->getDirty();
            $recordableFields = static::$recordableFields ?? array_keys($dirty);
            
            // Filtrăm doar coloanele pe care vrem să le urmărim
            $changes = array_intersect_key($dirty, array_flip($recordableFields));
            
            // Dacă nu există modificări în câmpurile urmărite, returnăm array-uri goale
            if (empty($changes)) {
                return [
                    'old' => [],
                    'attributes' => []
                ];
            }
            
            $original = array_intersect_key($this->getOriginal(), $changes);

            return [
                'old' => $original,
                'attributes' => $changes
            ];
        }

        // Pentru alte evenimente, returnăm doar atributele specificate
        $attributes = $this->getAttributes();
        if (isset(static::$recordableFields)) {
            $attributes = array_intersect_key(
                $attributes, 
                array_flip(static::$recordableFields)
            );
        }

        return ['attributes' => $attributes];
    }

    public static function withBatch(?string $uuid = null): string
    {
        static::$batchUuid = $uuid ?: (string) Str::uuid();
        return static::$batchUuid;
    }
} 