@php
    $editingGroup = isset($group) && $group;

    $action = $editingGroup
        ? route('tournaments.group-stage.groups.update', [$phaseTemplate, $group])
        : route('tournaments.group-stage.groups.store', $phaseTemplate);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-3">

    @csrf

    @if ($editingGroup)
        @method('PUT')
    @endif

    <div>
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Nombre</label>

        <input type="text" name="name" value="{{ old('name', $editingGroup ? $group->name : '') }}"
            placeholder="Ej. Grupo A"
            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-400 focus:ring-indigo-400">
    </div>

    <div>
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Capacidad</label>

        <input type="number" name="capacity" min="2" max="512"
            value="{{ old('capacity', $editingGroup ? $group->capacity : '') }}" placeholder="Ej. 4"
            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-400 focus:ring-indigo-400">
    </div>

    <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-xs font-black text-white">
        {{ $editingGroup ? 'Guardar grupo' : '+ Agregar grupo' }}
    </button>

</form>
