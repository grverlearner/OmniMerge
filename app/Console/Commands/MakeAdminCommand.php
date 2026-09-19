<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/*
| La primera llave del espacio de administración.
|
| Un admin nombra a otros desde su ficha, pero alguien tiene que ser el
| primero. Esto da (o quita, con --revoke) el rol a una cuenta que ya
| existe; no crea cuentas ni toca contraseñas.
*/
class MakeAdminCommand extends Command
{
    protected $signature = 'omnimerge:admin {email : Correo de la cuenta} {--revoke : Quitarle el rol en vez de darlo}';

    protected $description = 'Da o quita el rol de administrador a una cuenta existente';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No hay ninguna cuenta con ese correo.');

            return self::FAILURE;
        }

        if ($this->option('revoke')) {
            if (User::query()->where('role', 'ADMIN')->count() <= 1 && $user->isAdmin()) {
                $this->error('Es el único administrador: el sitio se quedaría sin nadie que pueda administrarlo.');

                return self::FAILURE;
            }

            $user->forceFill(['role' => 'USER'])->save();
            $this->info("{$user->name} ya no es administrador.");

            return self::SUCCESS;
        }

        $user->forceFill(['role' => 'ADMIN'])->save();
        $this->info("{$user->name} ya es administrador. Entra en /admin.");

        return self::SUCCESS;
    }
}
