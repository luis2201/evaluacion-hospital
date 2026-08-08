<?php

namespace App\Console\Commands;

use App\Enums\CodigoRol;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdministrator extends Command
{
    protected $signature = 'app:create-admin {--name=} {--email=}';

    protected $description = 'Crea el administrador inicial sin almacenar contraseñas en seeders';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nombre completo');
        $email = mb_strtolower($this->option('email') ?: $this->ask('Correo electrónico'));
        $password = $this->secret('Contraseña (mínimo 12 caracteres, mayúscula, minúscula, número y símbolo)');
        $confirmation = $this->secret('Confirmar contraseña');

        $validator = Validator::make(compact('name', 'email', 'password', 'confirmation'), [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', Password::defaults(), 'same:confirmation'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->create(['name' => $name, 'email' => $email, 'password' => Hash::make($password), 'activo' => true, 'password_changed_at' => now()]);
        $role = Role::query()->where('codigo', CodigoRol::Administrador->value)->firstOrFail();
        $user->roles()->attach($role, ['created_at' => now()]);
        $this->info('Administrador creado correctamente.');

        return self::SUCCESS;
    }
}
