<?php

namespace App\Providers;

use App\Models\Reminder;
use App\Policies\ReminderPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\AI\AiManager::class);

        $this->app->bind(\App\Services\AI\Contracts\AiServiceInterface::class, function ($app) {
            // Backwards compatibility for tests that swapped NvidiaNimService
            if ($app->bound(\App\Services\AI\NvidiaNimService::class) && ($app->isShared(\App\Services\AI\NvidiaNimService::class) || $app->make(\App\Services\AI\NvidiaNimService::class) instanceof \Mockery\MockInterface)) {
                return $app->make(\App\Services\AI\NvidiaNimService::class);
            }
            return $app->make(\App\Services\AI\AiManager::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Reminder::class, ReminderPolicy::class);

        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
