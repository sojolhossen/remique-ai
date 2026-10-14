<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a setting by key, with default fallback and caching.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("sys_setting_{$key}", 3600, function () use ($key, $default) {
            $record = static::find($key);
            return $record ? $record->value : $default;
        });
    }

    /**
     * Set a setting key and invalidate cache.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_scalar($value) || is_null($value) ? (string) $value : json_encode($value)]
        );

        Cache::forget("sys_setting_{$key}");
    }

    /**
     * Delete a setting and invalidate cache.
     */
    public static function remove(string $key): void
    {
        static::where('key', $key)->delete();
        Cache::forget("sys_setting_{$key}");
    }
}
