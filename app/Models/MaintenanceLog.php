<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceLog extends Model
{
    protected $fillable = [
        'equipment_id',
        'performed_at',
        'issue_description',
        'action_taken',
        'cost',
        'performed_by',
        'logged_by',
    ];

    protected function casts(): array
    {
        return [
            'performed_at' => 'date',
            'cost' => 'decimal:2',
        ];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
