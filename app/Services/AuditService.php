<?php

namespace App\Services;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    public function __construct(private readonly Request $request) {}

    /** @param array<string, mixed>|null $before @param array<string, mixed>|null $after */
    public function record(string $action, string $table, int|string|null $recordId = null, ?array $before = null, ?array $after = null, ?int $userId = null): Auditoria
    {
        return Auditoria::query()->create([
            'user_id' => $userId ?? $this->request->user()?->id,
            'accion' => $action,
            'tabla' => $table,
            'registro_id' => is_numeric($recordId) ? (int) $recordId : null,
            'valores_anteriores' => $this->withoutSensitiveData($before),
            'valores_nuevos' => $this->withoutSensitiveData($after),
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }

    public function recordModel(string $action, Model $model, ?array $before = null): Auditoria
    {
        return $this->record($action, $model->getTable(), $model->getKey(), $before, $model->getAttributes());
    }

    /** @param array<string, mixed>|null $values @return array<string, mixed>|null */
    private function withoutSensitiveData(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        return collect($values)->except(['password', 'remember_token', 'token'])->all();
    }
}
