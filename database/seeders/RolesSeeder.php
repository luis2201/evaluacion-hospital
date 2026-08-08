<?php

namespace Database\Seeders;

use App\Enums\CodigoRol;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [CodigoRol::Administrador, 'Administrador general', 'Administración completa del sistema.'],
            [CodigoRol::ResponsableDominio, 'Responsable de dominio', 'Administra evidencias únicamente del dominio asignado.'],
            [CodigoRol::EvaluadorExterno, 'Evaluador externo', 'Revisa evidencias y asigna calificaciones 0, 1 o 2.'],
            [CodigoRol::AuditorLectura, 'Auditor de lectura', 'Consulta resultados, evidencias e historial sin modificar información.'],
        ];

        foreach ($roles as [$codigo, $nombre, $descripcion]) {
            Role::query()->updateOrCreate(
                ['codigo' => $codigo->value],
                ['nombre' => $nombre, 'descripcion' => $descripcion],
            );
        }
    }
}
