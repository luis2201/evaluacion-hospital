<?php

namespace App\Services;

use App\Models\ConfiguracionSistema;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SystemSettingsService
{
    private const DEFAULTS = [
        'institution_name' => 'Hospital de Simulación',
        'institution_short_name' => 'MEC-SIM',
        'support_email' => '',
        'max_upload_files' => 10,
        'max_file_size_mb' => 10,
        'session_lifetime_minutes' => 120,
        'minimum_password_length' => 12,
        'login_attempts' => 5,
        'login_lock_seconds' => 60,
    ];

    public function get(string $key): string|int|null
    {
        if (! array_key_exists($key, self::DEFAULTS)) {
            return null;
        }
        if (! Schema::hasTable('configuraciones_sistema')) {
            return self::DEFAULTS[$key];
        }

        return Cache::remember("system-setting.{$key}", 300, fn () => ConfiguracionSistema::query()->where('clave', $key)->value('valor') ?? self::DEFAULTS[$key]);
    }

    public function integer(string $key): int
    {
        return (int) $this->get($key);
    }

    /** @return array<string, string|int|null> */
    public function all(): array
    {
        return collect(array_keys(self::DEFAULTS))->mapWithKeys(fn ($key) => [$key => $this->get($key)])->all();
    }

    /** @param array<string, mixed> $values */
    public function update(array $values, User $user): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;
            }
            ConfiguracionSistema::query()->updateOrCreate(
                ['clave' => $key],
                ['valor' => (string) ($value ?? ''), 'grupo' => $this->group($key), 'actualizada_por' => $user->id],
            );
            Cache::forget("system-setting.{$key}");
        }
    }

    private function group(string $key): string
    {
        return match ($key) {
            'institution_name', 'institution_short_name', 'support_email' => 'INSTITUCIONAL',
            'max_upload_files', 'max_file_size_mb' => 'DOCUMENTAL',
            default => 'SEGURIDAD',
        };
    }
}
