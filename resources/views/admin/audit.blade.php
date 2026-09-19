<x-admin-layout title="Registro de acciones">

    <x-slot:header>Registro de acciones</x-slot:header>

    @php
        $objetivos = ['user' => 'Cuentas'] + collect(\App\Services\Admin\ContentRegistry::TYPES)->map(fn ($t) => $t['label'])->all();
    @endphp

    <div class="space-y-4">

        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
            <p class="text-sm text-slate-400">
                Todo lo que cambia algo desde el espacio de administración queda aquí: quién, qué, sobre qué y cuándo.
                No se puede editar ni borrar.
            </p>

            <form method="GET" class="mt-3 flex flex-wrap items-end gap-2">
                <label>
                    <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-500">Acción</span>
                    <select name="accion" class="rounded-xl border-slate-700 bg-slate-950 text-sm text-slate-100">
                        <option value="">Todas</option>
                        @foreach (\App\Models\AdminAction::LABELS as $clave => [$texto])
                            <option value="{{ $clave }}" @selected($filtros['accion'] === $clave)>{{ $texto }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-500">Sobre</span>
                    <select name="objetivo" class="rounded-xl border-slate-700 bg-slate-950 text-sm text-slate-100">
                        <option value="">Cualquier cosa</option>
                        @foreach ($objetivos as $clave => $texto)
                            <option value="{{ $clave }}" @selected($filtros['objetivo'] === $clave)>{{ $texto }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-500">Admin</span>
                    <select name="admin" class="rounded-xl border-slate-700 bg-slate-950 text-sm text-slate-100">
                        <option value="">Cualquiera</option>
                        @foreach ($admins as $a)
                            <option value="{{ $a->id }}" @selected($filtros['admin'] == $a->id)>{{ $a->name }}</option>
                        @endforeach
                    </select>
                </label>

                <button class="inline-flex items-center gap-1.5 rounded-xl bg-rose-500 px-4 py-2.5 text-xs font-black text-white hover:bg-rose-400">
                    <x-omni-icon name="filtro" size="h-3.5 w-3.5" /> Filtrar
                </button>

                @if (array_filter($filtros))
                    <a href="{{ route('admin.audit') }}" class="rounded-xl border border-slate-700 px-3 py-2.5 text-xs font-black text-slate-400 hover:text-white">Quitar filtros</a>
                @endif
            </form>
        </section>

        @if ($acciones->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-800 p-10 text-center text-sm text-slate-500">
                Todavía no hay nada anotado{{ array_filter($filtros) ? ' con estos filtros' : '' }}.
            </div>
        @else
            @foreach ($acciones->groupBy(fn ($a) => $a->created_at->toDateString()) as $dia => $delDia)
                <section>
                    <h3 class="mb-2 text-[10px] font-black uppercase tracking-wider text-slate-500">
                        {{ \Illuminate\Support\Carbon::parse($dia)->translatedFormat('l j \d\e F \d\e Y') }}
                    </h3>
                    <div class="space-y-2">
                        @foreach ($delDia as $accion)
                            @include('admin.partials.action-row', ['accion' => $accion])
                        @endforeach
                    </div>
                </section>
            @endforeach

            <div>{{ $acciones->links() }}</div>
        @endif
    </div>

</x-admin-layout>
