<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_authenticated_pages_send_security_headers(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Content-Security-Policy');

        $secureResponse = app(SecurityHeaders::class)->handle(
            Request::create('https://localhost/dashboard'),
            fn () => new Response,
        );

        $this->assertSame(
            'max-age=31536000; includeSubDomains',
            $secureResponse->headers->get('Strict-Transport-Security'),
        );
    }

    public function test_production_check_rejects_local_environment(): void
    {
        $this->artisan('production:check')->assertFailed();
    }
}
