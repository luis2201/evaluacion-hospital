<?php

namespace App\Services;

use App\Enums\CodigoRol;
use App\Models\User;

class RoleExperienceService
{
    /** @return array<string, bool> */
    public function navigation(User $user): array
    {
        return [
            'dashboard' => true,
            'instruments' => $user->isAdministrator() || $user->hasRole(CodigoRol::ResponsableDominio) || $user->hasRole(CodigoRol::AuditorLectura),
            'evaluations' => true,
            'users' => $user->isAdministrator(),
            'settings' => true,
        ];
    }

    /** @return array<int, array{role: string, name: string, description: string, capabilities: array<int, string>}> */
    public function profiles(User $user): array
    {
        $definitions = [
            CodigoRol::Administrador->value => ['Administrador general', 'Gestiona la operación institucional y la seguridad.', ['Administrar usuarios', 'Versionar instrumentos', 'Configurar y cerrar evaluaciones', 'Consultar todos los resultados', 'Modificar parámetros del sistema']],
            CodigoRol::ResponsableDominio->value => ['Responsable de dominio', 'Trabaja únicamente sobre los dominios que le fueron asignados.', ['Registrar autoevaluaciones', 'Cargar evidencias durante el plazo', 'Responder observaciones', 'Consultar resultados oficiales propios']],
            CodigoRol::EvaluadorExterno->value => ['Evaluador externo', 'Accede exclusivamente a su bandeja de revisión asignada.', ['Revisar evidencias', 'Crear y cerrar observaciones', 'Calificar descriptores', 'Consultar historial de calificaciones']],
            CodigoRol::AuditorLectura->value => ['Auditor de lectura', 'Consulta información institucional sin modificarla.', ['Consultar instrumentos', 'Consultar evaluaciones y evidencias', 'Consultar resultados', 'Acceder posteriormente a reportes y auditoría']],
        ];

        return $user->roles->map(function ($role) use ($definitions): array {
            [$name, $description, $capabilities] = $definitions[$role->codigo] ?? [$role->nombre, $role->descripcion, []];

            return ['role' => $role->codigo, 'name' => $name, 'description' => $description, 'capabilities' => $capabilities];
        })->values()->all();
    }
}
