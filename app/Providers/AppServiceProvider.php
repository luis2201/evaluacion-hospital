<?php

namespace App\Providers;

use App\Services\AuditService;
use App\Services\SystemSettingsService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $settings = app(SystemSettingsService::class);
        config(['app.name' => $settings->get('institution_name'), 'session.lifetime' => $settings->integer('session_lifetime_minutes')]);
        if (app()->environment('production') && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
        Password::defaults(fn () => Password::min($settings->integer('minimum_password_length'))->mixedCase()->letters()->numbers()->symbols());

        Event::listen(Failed::class, function (Failed $event): void {
            app(AuditService::class)->record('INICIO_SESION_FALLIDO', 'users', $event->user?->id, userId: $event->user?->id);
        });

        Event::listen(Lockout::class, function (): void {
            app(AuditService::class)->record('CUENTA_TEMPORALMENTE_BLOQUEADA', 'users');
        });
    }
}
