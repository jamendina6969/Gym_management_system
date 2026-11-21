<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'branch_id',
        'group',
        'key',
        'value',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public static function getValue(string $key, ?int $branchId = null, mixed $default = null): mixed
    {
        $cacheKey = 'setting:' . ($branchId ?? 'global') . ':' . $key;

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($key, $branchId, $default) {
            if ($branchId !== null) {
                $branchSetting = static::query()
                    ->where('branch_id', $branchId)
                    ->where('key', $key)
                    ->first();

                if ($branchSetting !== null) {
                    return $branchSetting->value;
                }
            }

            $globalSetting = static::query()
                ->whereNull('branch_id')
                ->where('key', $key)
                ->first();

            return $globalSetting?->value ?? $default;
        });
    }

    public static function setValue(string $key, mixed $value, ?int $branchId = null, string $group = 'general'): self
    {
        $setting = static::query()->updateOrCreate(
            ['branch_id' => $branchId, 'key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value, 'group' => $group]
        );

        Cache::forget('setting:' . ($branchId ?? 'global') . ':' . $key);

        return $setting;
    }
}
