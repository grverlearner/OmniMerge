<?php

namespace App\Services\Admin;

use App\Models\AdminAction;
use Illuminate\Database\Eloquent\Model;

/*
| Deja escrito lo que hace un admin. Todo lo que cambia algo desde el
| espacio de administración pasa por aquí: sin historial, un bloqueo o un
| borrado no se pueden explicar después.
*/
class AdminAudit
{
    public function log(string $action, ?Model $target = null, array $details = [], ?string $label = null): AdminAction
    {
        return AdminAction::query()->create([
            'admin_id' => auth()->id(),
            'action' => $action,
            'target_type' => $target ? ContentRegistry::keyFor($target) : null,
            'target_id' => $target?->getKey(),
            'target_label' => $label ?? ($target ? ContentRegistry::labelOf($target) : null),
            'details' => $details ?: null,
            'ip' => request()->ip(),
        ]);
    }
}
