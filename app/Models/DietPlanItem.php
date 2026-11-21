<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DietPlanItem extends Model
{
    protected $fillable = [
        'diet_plan_id',
        'meal_time',
        'food_items',
        'calories',
        'protein_grams',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'calories' => 'integer',
            'protein_grams' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(DietPlan::class, 'diet_plan_id');
    }
}
