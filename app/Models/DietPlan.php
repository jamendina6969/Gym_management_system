<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DietPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'trainer_id',
        'created_by',
        'title',
        'goal',
        'daily_calories',
        'protein_grams',
        'carbs_grams',
        'fat_grams',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'daily_calories' => 'integer',
            'protein_grams' => 'integer',
            'carbs_grams' => 'integer',
            'fat_grams' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DietPlanItem::class)->orderBy('sort_order')->orderBy('id');
    }
}
