@php
    /*
     * Los resultados del buscador de la comunidad, cuando se envía el
     * formulario en vez de mirar la lista en vivo. Ver CommunityHomeController.
     */
@endphp

<x-community-layout title="Buscar en la comunidad" surface="dark">

    <x-slot name="header">Buscar</x-slot>

    <div class="space-y-3">

        <section class="rounded-2xl border border-emerald-500/25 bg-slate-900/60 p-3 sm:p-4">
            <form method="GET" action="{{ route('community.buscar') }}" class="flex flex-wrap gap-2">
                <label class="relative min-w-[220px] flex-1">
                    <span class="sr-only">Qué buscas</span>
                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-emerald-400">
                        <x-omni-icon name="brujula" size="h-4 w-4" />
                    </span>
                    <input type="search" name="q" value="{{ $q }}" autofocus minlength="2"
                        placeholder="Buscar en toda la comunidad…"
                        class="w-full rounded-xl border-slate-700 bg-slate-950 py-2.5 pl-10 text-sm text-slate-100 placeholder:text-slate-600 focus:border-emerald-400 focus:ring-emerald-400">
                </label>
                <button type="submit" class="rounded-xl bg-emerald-500 px-4 py-2.5 text-[12px] font-black text-slate-950 transition hover:bg-emerald-400">Buscar</button>
            </form>

            <p class="mt-2 text-[11px] text-slate-500">
                @if (mb_strlen($q) < 2)
                    Escribe al menos dos letras.
                @elseif ($total === 0)
                    Nada publicado con «<span class="text-slate-300">{{ $q }}</span>».
                @else
                    {{ $total }} {{ $total === 1 ? 'resultado' : 'resultados' }} para «<span class="text-slate-300">{{ $q }}</span>».
                @endif
            </p>
        </section>


        @if ($total > 0)
            <nav class="flex flex-wrap gap-1.5">
                @foreach ($grupos as $grupo)
                    @if ($grupo['total'] > 0)
                        <a href="#grupo-{{ $grupo['clave'] }}"
                            class="flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-black transition hover:-translate-y-0.5"
                            style="border-color: {{ $grupo['tono'] }}55; color: {{ $grupo['tono'] }}">
                            <x-omni-icon :name="$grupo['icono']" size="h-3.5 w-3.5" />
                            {{ $grupo['etiqueta'] }}
                            <span class="font-mono opacity-70">{{ $grupo['total'] }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>

            @foreach ($grupos as $grupo)
                @continue($grupo['total'] === 0)

                <section id="grupo-{{ $grupo['clave'] }}" class="scroll-mt-24 overflow-hidden rounded-2xl border bg-slate-900/50"
                    style="border-color: {{ $grupo['tono'] }}33">

                    <div class="flex items-center gap-2 px-3 py-2.5" style="background: linear-gradient(120deg, {{ $grupo['tono'] }}1f, transparent 60%)">
                        <span style="color: {{ $grupo['tono'] }}"><x-omni-icon :name="$grupo['icono']" size="h-4 w-4" /></span>
                        <h2 class="text-[14px] font-black" style="color: {{ $grupo['tono'] }}">{{ $grupo['etiqueta'] }}</h2>
                        <span class="font-mono text-[11px] text-slate-500">{{ $grupo['total'] }}</span>
                        <span class="flex-1"></span>
                        @if ($grupo['total'] > count($grupo['items']))
                            <span class="text-[10px] text-slate-500">Se muestran {{ count($grupo['items']) }}</span>
                        @endif
                        <a href="{{ $grupo['ver_todo'] }}"
                            class="rounded-lg border border-slate-700 px-2.5 py-1 text-[10px] font-black text-slate-300 transition hover:border-emerald-500 hover:text-emerald-300">
                            Abrir en su explorador →
                        </a>
                    </div>

                    <div class="grid grid-cols-2 gap-2 p-3 sm:grid-cols-3 lg:grid-cols-6">
                        @foreach ($grupo['items'] as $pieza)
                            <a href="{{ $pieza['url'] }}"
                                class="group block overflow-hidden rounded-xl border bg-slate-950 transition hover:-translate-y-0.5"
                                style="border-color: {{ $pieza['tono'] }}40">
                                <span class="relative block aspect-square overflow-hidden {{ $grupo['clave'] === 'creador' ? 'bg-gradient-to-br from-emerald-500/10 to-slate-950' : '' }}">
                                    @if ($pieza['img'])
                                        <img src="{{ $pieza['img'] }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center" style="color: {{ $pieza['tono'] }}55">
                                            <x-omni-icon :name="$grupo['icono']" size="h-9 w-9" />
                                        </span>
                                    @endif
                                    @if ($grupo['clave'] === 'creador')
                                        <span class="absolute left-1.5 top-1.5 rounded-md bg-slate-950/80 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider"
                                            style="color: {{ $pieza['tono'] }}">{{ $pieza['etiqueta'] }}</span>
                                    @endif
                                    @if ($pieza['copias'] > 0)
                                        <span class="absolute right-1.5 top-1.5 rounded-md bg-slate-950/80 px-1.5 py-0.5 font-mono text-[9px] font-black text-emerald-300">{{ $pieza['copias'] }}×</span>
                                    @endif
                                </span>
                                <span class="block px-2 py-1.5">
                                    <span class="block truncate text-[12px] font-black text-slate-200">{{ $pieza['nombre'] }}</span>
                                    <span class="block truncate text-[10px] text-slate-500">
                                        {{ $pieza['autor'] ? 'de ' . $pieza['autor']['nombre'] : $pieza['pie'] }}
                                    </span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif
    </div>

</x-community-layout>
