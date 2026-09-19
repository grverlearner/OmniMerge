{{-- Una cuenta en tarjeta: quién es, en qué estado está y cuánto ha creado --}}

@php
    $piezas = [
        ['libro', $u->entities_count, 'entidades'],
        ['capas', $u->collections_count, 'colecciones'],
        ['trofeo', $u->tournament_templates_count, 'torneos'],
        ['grafo', $u->phase_templates_count, 'fases'],
        ['orbita', $u->universes_count, 'universos'],
    ];

    $borde = $u->trashed() ? 'border-slate-800 opacity-70' : ($u->isBanned() ? 'border-orange-500/40' : ($u->isAdmin() ? 'border-violet-500/40' : 'border-slate-800'));
@endphp

<article class="group relative overflow-hidden rounded-2xl border bg-slate-900/60 transition hover:bg-slate-900 {{ $borde }}">

    <label class="absolute right-3 top-3 z-10">
        <input type="checkbox" @change="alternar({{ $u->id }})" :checked="marcadas.includes({{ $u->id }})"
            class="rounded border-slate-600 bg-slate-950 text-rose-500 focus:ring-rose-500" aria-label="Marcar {{ $u->name }}">
    </label>

    <a href="{{ route('admin.users.show', $u->id) }}" class="block p-4">
        <div class="flex items-center gap-3">
            <x-user-avatar :user="$u" size="lg" />
            <div class="min-w-0 pr-6">
                <p class="truncate font-black text-white">{{ $u->name }}</p>
                <p class="truncate text-[11px] text-slate-500">&#64;{{ $u->username }}</p>
                <p class="truncate text-[11px] text-slate-600">{{ $u->email }}</p>
            </div>
        </div>

        <div class="mt-3 flex min-h-[1.5rem] flex-wrap gap-1">
            @include('admin.users.partials.badges', ['u' => $u])
        </div>

        <div class="mt-3 grid grid-cols-5 gap-1 rounded-xl border border-slate-800 bg-slate-950/50 p-2">
            @foreach ($piezas as [$icono, $n, $texto])
                <span class="text-center" title="{{ $n }} {{ $texto }}">
                    <x-omni-icon :name="$icono" size="h-3.5 w-3.5" class="mx-auto text-slate-500" />
                    <span class="block text-sm font-black {{ $n ? 'text-slate-200' : 'text-slate-600' }}">{{ $n }}</span>
                </span>
            @endforeach
        </div>

        <p class="mt-3 flex items-center justify-between text-[11px] text-slate-500">
            <span>Alta {{ $u->created_at?->format('d/m/Y') }}</span>
            <span>{{ $u->last_login_at ? 'Entró ' . $u->last_login_at->diffForHumans() : 'Nunca entró' }}</span>
        </p>
    </a>
</article>
