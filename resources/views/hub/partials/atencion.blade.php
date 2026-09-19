@php
    /*
     * Lo que espera por ti, en toda la cuenta. Cada aviso lleva a donde se
     * resuelve (ver HubController); aquí solo se ordena y se pinta. Lo
     * urgente va primero y más grande.
     */
    $iconos = ['Universos' => 'orbita', 'Biblioteca' => 'libro', 'Torneos' => 'trofeo'];
@endphp

@if ($atencion->isNotEmpty())
    <section x-data="{ todos: false }">
        <div class="mb-3 flex items-end justify-between gap-3">
            <div>
                <h2 class="flex items-center gap-2 text-lg font-black text-white">
                    <x-omni-icon name="aviso" size="h-5 w-5" class="text-amber-300" /> Te está esperando
                </h2>
                <p class="text-xs text-slate-500">Cada aviso te lleva justo a donde se arregla.</p>
            </div>
            @if ($atencion->count() > 4)
                <button type="button" @click="todos = ! todos" class="text-xs font-black text-slate-400 hover:text-white"
                    x-text="todos ? 'Ver menos' : 'Ver los {{ $atencion->count() }}'"></button>
            @endif
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($atencion as $i => $aviso)
                <a href="{{ $aviso['url'] }}" @if ($i >= 4) x-show="todos" x-cloak @endif
                    class="group relative overflow-hidden rounded-2xl border bg-slate-900/60 p-4 transition hover:-translate-y-0.5 hover:bg-slate-900"
                    style="border-color: {{ $aviso['tono'] }}{{ $aviso['urgente'] ? '99' : '40' }};">

                    <span class="absolute inset-y-0 left-0 w-1" style="background-color: {{ $aviso['tono'] }}"></span>

                    <div class="flex items-start justify-between gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl" style="color: {{ $aviso['tono'] }}; background-color: {{ $aviso['tono'] }}1c;">
                            <x-omni-icon :name="$iconos[$aviso['donde']] ?? 'aviso'" size="h-5 w-5" />
                        </span>
                        <span class="text-3xl font-black leading-none" style="color: {{ $aviso['tono'] }}">{{ $aviso['cuantos'] }}</span>
                    </div>

                    <p class="mt-3 text-sm font-black leading-snug text-white">{{ $aviso['titulo'] }}</p>
                    <p class="mt-1 text-xs leading-relaxed text-slate-400">{{ $aviso['texto'] }}</p>

                    <p class="mt-3 flex items-center justify-between text-[11px] font-black">
                        <span class="rounded-full bg-white/5 px-2 py-0.5 text-slate-400">{{ $aviso['donde'] }}</span>
                        @if ($aviso['urgente'])
                            <span class="rounded-full bg-rose-500/15 px-2 py-0.5 text-rose-300">Urgente</span>
                        @endif
                        <span class="flex items-center gap-1 text-slate-500 transition group-hover:text-white">
                            Resolver <x-omni-icon name="flecha-derecha" size="h-3.5 w-3.5" />
                        </span>
                    </p>
                </a>
            @endforeach
        </div>
    </section>
@endif
