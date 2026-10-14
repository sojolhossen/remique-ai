<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SystemSetting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    protected static bool $schemaChecked = false;

    /**
     * Ensure the system_settings table exists in case migrations were not run yet.
     */
    protected static function ensureTableExists(): void
    {
        if (static::$schemaChecked) {
            return;
        }
        static::$schemaChecked = true;

        try {
            if (! Schema::hasTable('system_settings')) {
                Schema::create('system_settings', function (Blueprint $table) {
                    $table->string('key', 191)->primary();
                    $table->text('value')->nullable();
                    $table->timestamps();
                });
            }
        } catch (Throwable) {
            // Table creation handled silently
        }
    }

    /**
     * Get a setting by key, with default fallback and caching.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            static::ensureTableExists();

            return Cache::remember("sys_setting_{$key}", 3600, function () use ($key, $default) {
                try {
                    $record = static::find($key);
                    return $record ? $record->value : $default;
                } catch (Throwable) {
                    return $default;
                }
            });
        } catch (Throwable) {
            return $default;
        }
    }

    /**
     * Set a setting key and invalidate cache.
     */
    public static function set(string $key, mixed $value): void
    {
        try {
            static::ensureTableExists();

            static::updateOrCreate(
                ['key' => $key],
                ['value' => is_scalar($value) || is_null($value) ? (string) $value : json_encode($value)]
            );

            Cache::forget("sys_setting_{$key}");
        } catch (Throwable) {
            // Silent fallback if db error
        }
    }

    /**
     * Delete a setting and invalidate cache.
     */
    public static function remove(string $key): void
    {
        try {
            static::where('key', $key)->delete();
            Cache::forget("sys_setting_{$key}");
        } catch (Throwable) {
            // Silent fallback
        }
    }
}
