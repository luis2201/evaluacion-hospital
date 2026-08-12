<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\UpdateSystemSettingsRequest;
use App\Models\Evaluacion;
use App\Services\AuditService;
use App\Services\RoleExperienceService;
use App\Services\SystemSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function show(Request $request, RoleExperienceService $roles, SystemSettingsService $settings): View
    {
        $user = $request->user()->load('roles');
        $system = null;
        $evaluations = collect();

        if ($user->isAdministrator()) {
            $system = [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'database' => DB::selectOne('SELECT VERSION() AS version')->version,
                'environment' => app()->environment(),
                'storage_writable' => is_writable(storage_path('app/private')),
            ];
            $evaluations = Evaluacion::query()->latest()->get();
        }

        return view('settings.show', [
            'user' => $user,
            'profiles' => $roles->profiles($user),
            'settings' => $settings->all(),
            'system' => $system,
            'evaluations' => $evaluations,
        ]);
    }

    public function update(UpdateSystemSettingsRequest $request, SystemSettingsService $settings, AuditService $audit): RedirectResponse
    {
        $before = $settings->all();
        $settings->update($request->validated(), $request->user());
        $audit->record('CONFIGURACION_SISTEMA_ACTUALIZADA', 'configuraciones_sistema', before: $before, after: $settings->all());

        return back()->with('status', 'La configuración institucional fue actualizada.');
    }
}
