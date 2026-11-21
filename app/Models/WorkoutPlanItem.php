<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutPlanItem extends Model
{
    protected $fillable = [
        'workout_plan_id',
        'day_label',
        'exercise_name',
        'sets',
        'reps',
        'rest_seconds',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sets' => 'integer',
            'rest_seconds' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(WorkoutPlan::class, 'workout_plan_id');
    }
}
