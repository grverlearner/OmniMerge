{{--
    Las seis puertas: cada clase de pieza con sus últimas caras y cuántas hay,
    y la gente. Cada una abre su explorador ya filtrado.
--}}

@php
    $destinos = [
        'entidad' => route('community.index', ['tab' => 'entities']),
        'coleccion' => route('community.index', ['tab' => 'collections']),
        'atributo' => route('community.index', ['tab' => 'attributes']),
        'torneo' => route('tournaments.community.index', ['kind' => 'tournaments']),
        'fase' => route('tournaments.community.index', ['kind' => 'phases']),
    ];

    $lado = ['entidad' => 'Biblioteca', 'coleccion' => 'Biblioteca', 'atributo' => 'Biblioteca', 'torneo' => 'Torneos', 'fase' => 'Torneos'];
@endphp

<section class="grid grid-cols-2 gap-2 md:grid-cols-3 xl:grid-cols-6">

    @foreach ($tipos as $tipo => [$singular, $plural, $tono, $icono])
        <a href="{{ $destinos[$tipo] }}"
            class="group overflow-hidden rounded-2xl border bg-slate-900/50 transition hover:-translate-y-0.5"
            style="border-color: {{ $tono }}33">

            <span class="grid h-20 grid-cols-4 gap-px bg-slate-950">
                @forelse ($muestras[$tipo] as $pieza)
                    <span class="block overflow-hidden">
                        <img src="{{ $pieza['img'] }}" alt="" loading="lazy"
                            class="h-full w-full object-cover opacity-70 transition duration-300 group-hover:scale-105 group-hover:opacity-100">
                    </span>
                @empty
                    <span class="col-span-4 flex items-center justify-center" style="color: {{ $tono }}55">
                        <x-omni-icon :name="$icono" size="h-7 w-7" />
                    </span>
                @endforelse
            </span>

            <span class="flex items-center gap-2 px-3 py-2.5">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                    style="background-color: {{ $tono }}1f; color: {{ $tono }}">
                    <x-omni-icon :name="$icono" size="h-4 w-4" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[13px] font-black text-white">{{ $plural }}</span>
                    <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $lado[$tipo] }}</span>
                </span>
                <span class="font-mono text-lg font-black" style="color: {{ $cifras[$tipo] > 0 ? $tono : '#475569' }}">{{ $cifras[$tipo] }}</span>
            </span>
        </a>
    @endforeach


    <a href="{{ route('community.creators.index') }}"
        class="group overflow-hidden rounded-2xl border border-emerald-500/30 bg-slate-900/50 transition hover:-translate-y-0.5">

        <span class="flex h-20 items-center justify-center bg-gradient-to-br from-emerald-500/15 to-slate-950 px-3">
            <span class="flex -space-x-3">
                @forelse ($creadores->sortByDesc('pub_total')->take(5) as $persona)
                    <span class="h-11 w-11 overflow-hidden rounded-full border-2 border-slate-900 bg-slate-950 transition group-hover:-translate-y-0.5">
                        @if ($persona->avatar_url)
                            <img src="{{ $persona->avatar_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        @else
                            <span class="flex h-full w-full items-center justify-center text-[12px] font-black text-slate-500">{{ $persona->initials }}</span>
                        @endif
                    </span>
                @empty
                    <span class="text-emerald-500/40"><x-omni-icon name="usuario" size="h-7 w-7" /></span>
                @endforelse
            </span>
        </span>

        <span class="flex items-center gap-2 px-3 py-2.5">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300">
                <x-omni-icon name="usuario" size="h-4 w-4" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-[13px] font-black text-white">Creadores</span>
                <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">Personas</span>
            </span>
            <span class="font-mono text-lg font-black" style="color: {{ $creadores->isNotEmpty() ? '#34d399' : '#475569' }}">{{ $creadores->count() }}</span>
        </span>
    </a>
</section>
