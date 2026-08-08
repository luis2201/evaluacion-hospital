<?php

namespace App\Providers;

use App\Services\AuditService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Event;
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
        Password::defaults(fn () => Password::min(12)->mixedCase()->letters()->numbers()->symbols());

        Event::listen(Failed::class, function (Failed $event): void {
            app(AuditService::class)->record('INICIO_SESION_FALLIDO', 'users', $event->user?->id, userId: $event->user?->id);
        });

        Event::listen(Lockout::class, function (): void {
            app(AuditService::class)->record('CUENTA_TEMPORALMENTE_BLOQUEADA', 'users');
        });
    }
}
