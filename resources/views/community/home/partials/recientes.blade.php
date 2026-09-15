{{--
    Lo recién publicado, de las cinco clases a la vez.

    Se filtra por clase sin recargar y se mira en mosaico o en lista. Las dos
    preferencias se recuerdan en este navegador.
--}}

@php
    $porTipo = $recientes->countBy('tipo');
@endphp

<section x-data="{
        filtro: 'todo',
        vista: localStorage.getItem('omnimerge.comunidad.recientes') ?? 'mosaico',
        init() { this.$watch('vista', (v) => localStorage.setItem('omnimerge.comunidad.recientes', v)); },
    }"
    class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-3 py-2.5">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300">
            <x-omni-icon name="chispa" size="h-4 w-4" />
        </span>

        <div class="mr-auto">
            <h2 class="text-[14px] font-black text-white">Recién publicado</h2>
            <p class="text-[10px] text-slate-500">Lo último que alguien ha compartido, de cualquier clase</p>
        </div>

        <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
            @foreach ([['mosaico', 'galeria', 'Mosaico'], ['lista', 'panel', 'Lista']] as [$modo, $icono, $ayuda])
                <button type="button" @click="vista = '{{ $modo }}'" title="{{ $ayuda }}" :aria-pressed="vista === '{{ $modo }}'"
                    :class="vista === '{{ $modo }}' ? 'bg-emerald-500 text-slate-950' : 'text-slate-500 hover:text-slate-200'"
                    class="rounded-lg px-2 py-1 transition">
                    <x-omni-icon :name="$icono" size="h-3.5 w-3.5" />
                </button>
            @endforeach
        </span>
    </div>

    @if ($recientes->isEmpty())

        <div class="px-4 py-12 text-center">
            <span class="inline-flex text-slate-700"><x-omni-icon name="globo" size="h-9 w-9" /></span>
            <p class="mt-2 text-[13px] font-black text-white">Todavía no se ha publicado nada</p>
            <p class="mx-auto mt-1 max-w-sm text-[11px] leading-relaxed text-slate-500">
                Puedes ser el primero: publica una entidad, una colección o una plantilla y aparecerá aquí para todos.
            </p>
            <a href="{{ route('profile.edit') }}"
                class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-emerald-500 px-3 py-2 text-[11px] font-black text-slate-950 transition hover:bg-emerald-400">
                <x-omni-icon name="chispa" size="h-3.5 w-3.5" />
                Ver qué puedo publicar
            </a>
        </div>

    @else

        <div class="flex flex-wrap gap-1 border-b border-slate-800/70 px-3 py-2">
            <button type="button" @click="filtro = 'todo'"
                :class="filtro === 'todo' ? 'border-slate-400 bg-slate-800 text-white' : 'border-slate-800 text-slate-500 hover:text-slate-300'"
                class="rounded-lg border px-2 py-1 text-[10px] font-black transition">
                Todo <span class="font-mono text-slate-500">{{ $recientes->count() }}</span>
            </button>

            @foreach ($tipos as $tipo => [$singular, $plural, $tono])
                @if (($porTipo[$tipo] ?? 0) > 0)
                    <button type="button" @click="filtro = '{{ $tipo }}'"
                        :style="filtro === '{{ $tipo }}' ? 'border-color: {{ $tono }}; background-color: {{ $tono }}1f; color: {{ $tono }}' : ''"
                        class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-500 transition hover:text-slate-300">
                        {{ $plural }} <span class="font-mono opacity-60">{{ $porTipo[$tipo] }}</span>
                    </button>
                @endif
            @endforeach
        </div>


        {{-- Mosaico --}}
        <div x-show="vista === 'mosaico'" class="grid grid-cols-2 gap-2 p-3 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-6">
            @foreach ($recientes as $pieza)
                <a href="{{ $pieza['url'] }}" x-show="filtro === 'todo' || filtro === '{{ $pieza['tipo'] }}'"
                    class="group relative block overflow-hidden rounded-xl border bg-slate-950 transition hover:-translate-y-0.5"
                    style="border-color: {{ $pieza['tono'] }}40">

                    <span class="relative block aspect-[4/5] overflow-hidden">
                        @if ($pieza['img'])
                            <img src="{{ $pieza['img'] }}" alt="" loading="lazy"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        @else
                            <span class="flex h-full w-full items-center justify-center" style="color: {{ $pieza['tono'] }}99; background: radial-gradient(circle, {{ $pieza['tono'] }}26, transparent 70%)">
                                <x-omni-icon :name="$pieza['icono']" size="h-12 w-12" />
                            </span>
                        @endif

                        <span class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-transparent"></span>

                        <span class="absolute left-1.5 top-1.5 rounded-md px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider backdrop-blur"
                            style="color: {{ $pieza['tono'] }}; background-color: #020617b3">{{ $pieza['etiqueta'] }}</span>

                        @if ($pieza['copias'] > 0)
                            <span class="absolute right-1.5 top-1.5 rounded-md bg-slate-950/70 px-1.5 py-0.5 font-mono text-[9px] font-black text-emerald-300 backdrop-blur"
                                title="Veces que alguien se lo ha llevado">{{ $pieza['copias'] }}×</span>
                        @endif

                        <span class="absolute inset-x-0 bottom-0 p-2">
                            <span class="block truncate text-[12px] font-black leading-tight text-white">{{ $pieza['nombre'] }}</span>
                            @if ($pieza['autor'])
                                <span class="mt-0.5 flex items-center gap-1">
                                    <span class="h-4 w-4 shrink-0 overflow-hidden rounded-full bg-slate-800">
                                        @if ($pieza['autor']['avatar'])
                                            <img src="{{ $pieza['autor']['avatar'] }}" alt="" class="h-full w-full object-cover">
                                        @endif
                                    </span>
                                    <span class="truncate text-[9px] text-slate-400">{{ $pieza['autor']['nombre'] }}</span>
                                </span>
                            @endif
                        </span>
                    </span>
                </a>
            @endforeach
        </div>


        {{-- Lista --}}
        <div x-show="vista === 'lista'" x-cloak class="divide-y divide-slate-800/70">
            @foreach ($recientes as $pieza)
                <div x-show="filtro === 'todo' || filtro === '{{ $pieza['tipo'] }}'"
                    class="flex items-center gap-3 px-3 py-2 transition hover:bg-slate-950/50">

                    <span class="h-10 w-1 shrink-0 rounded-full" style="background-color: {{ $pieza['tono'] }}"></span>

                    <a href="{{ $pieza['url'] }}" class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border bg-slate-950" style="border-color: {{ $pieza['tono'] }}40">
                        @if ($pieza['img'])
                            <img src="{{ $pieza['img'] }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        @else
                            <span class="flex h-full w-full items-center justify-center" style="color: {{ $pieza['tono'] }}66">
                                <x-omni-icon :name="$pieza['icono']" size="h-4 w-4" />
                            </span>
                        @endif
                    </a>

                    <div class="min-w-0 flex-1">
                        <a href="{{ $pieza['url'] }}" class="block truncate text-[13px] font-black text-white transition hover:text-emerald-300">{{ $pieza['nombre'] }}</a>
                        <p class="truncate text-[10px] text-slate-500">
                            <span style="color: {{ $pieza['tono'] }}">{{ $pieza['etiqueta'] }}</span>
                            @if ($pieza['autor'])
                                · de <a href="{{ $pieza['autor']['url'] }}" class="text-slate-400 hover:text-emerald-300">{{ $pieza['autor']['nombre'] }}</a>
                            @endif
                            @if ($pieza['pie'])
                                · {{ $pieza['pie'] }}
                            @endif
                        </p>
                    </div>

                    <span class="hidden shrink-0 gap-3 font-mono text-[10px] sm:flex">
                        <span title="Copias" style="color: {{ $pieza['copias'] > 0 ? '#34d399' : '#475569' }}">{{ $pieza['copias'] }}×</span>
                        <span title="Vistas" class="flex items-center gap-1 text-slate-500"><x-omni-icon name="ojo" size="h-3 w-3" />{{ $pieza['vistas'] }}</span>
                    </span>
                </div>
            @endforeach
        </div>
    @endif
</section>
