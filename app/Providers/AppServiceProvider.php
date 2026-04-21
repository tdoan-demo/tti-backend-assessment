<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('pro-read', function (Request $request): Limit {
            $key = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute(120)
                ->by('pro-read|' . $key)
                ->response(fn (Request $request, array $headers) => response()->json([
                    'message' => 'Too many requests. Please retry shortly.',
                ], 429, $headers));
        });

        RateLimiter::for('pro-write', function (Request $request): Limit {
            $key = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute(30)
                ->by('pro-write|' . $key)
                ->response(fn (Request $request, array $headers) => response()->json([
                    'message' => 'Too many requests. Please retry shortly.',
                ], 429, $headers));
        });
    }
}
