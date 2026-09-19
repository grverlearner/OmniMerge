@php
    /*
     * Lo último que has tocado, de todo, con su imagen de verdad: lo que se
     * busca al volver es reconocer la cosa. Se puede quedar solo con un tipo
     * y mirar en galería o en lista.
     */

    $iconos = [
        'Entidad' => 'libro', 'Atributo' => 'controles', 'Colección' => 'capas',
        'Torneo' => 'trofeo', 'Fase' => 'grafo', 'Universo' => 'orbita',
    ];

    $tipos = $reciente->groupBy('tipo')->map->count();
@endphp

@if ($reciente->isNotEmpty())
    <section x-data="{
        tipo: 'todo',
        vista: (() => { try { return localStorage.getItem('hub.reciente.vista') || 'galeria' } catch (e) { return 'galeria' } })(),
        poner(v) { this.vista = v; try { localStorage.setItem('hub.reciente.vista', v) } catch (e) {} },
        ve(t) { return this.tipo === 'todo' || this.tipo === t },
    }">
        <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="flex items-center gap-2 text-lg font-black text-white">
                    <x-omni-icon name="historial" size="h-5 w-5" class="text-slate-300" /> Lo último que has hecho
                </h2>
                <p class="text-xs text-slate-500">De cualquier parte de tu cuenta, lo más nuevo primero.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="flex flex-wrap gap-1">
                    <button type="button" @click="tipo = 'todo'"
                        :class="tipo === 'todo' ? 'border-white/40 bg-white/10 text-white' : 'border-white/10 text-slate-400 hover:text-white'"
                        class="rounded-lg border px-2.5 py-1 text-[11px] font-black">Todo · {{ $reciente->count() }}</button>
                    @foreach ($tipos as $tipo => $n)
                        @php $tono = $reciente->firstWhere('tipo', $tipo)['tono']; @endphp
                        <button type="button" @click="tipo = @js($tipo)"
                            :class="tipo === @js($tipo) ? 'text-white' : 'border-white/10 text-slate-400 hover:text-white'"
                            :style="tipo === @js($tipo) ? 'border-color: {{ $tono }}; background-color: {{ $tono }}22' : ''"
                            class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-[11px] font-black">
                            <span style="color: {{ $tono }}"><x-omni-icon :name="$iconos[$tipo] ?? 'punto'" size="h-3 w-3" /></span>
                            {{ $tipo }} · {{ $n }}
                        </button>
                    @endforeach
                </div>

                <div class="flex rounded-xl border border-white/10 bg-slate-950 p-1">
                    @foreach ([['galeria', 'galeria', 'Galería'], ['lista', 'menu', 'Lista']] as [$v, $icono, $texto])
                        <button type="button" @click="poner('{{ $v }}')" title="{{ $texto }}"
                            :class="vista === '{{ $v }}' ? 'bg-white/10 text-white' : 'text-slate-500 hover:text-slate-300'"
                            class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[11px] font-black">
                            <x-omni-icon :name="$icono" size="h-3.5 w-3.5" /> {{ $texto }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- GALERÍA --}}
        <div x-show="vista === 'galeria'" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($reciente as $cosa)
                <a href="{{ $cosa['url'] }}" x-show="ve(@js($cosa['tipo']))"
                    class="group overflow-hidden rounded-2xl border bg-slate-900/60 transition hover:-translate-y-0.5"
                    style="border-color: {{ $cosa['tono'] }}33;">
                    <span class="relative block aspect-square overflow-hidden" style="background-color: {{ $cosa['tono'] }}10;">
                        @if ($cosa['img'])
                            <img src="{{ $cosa['img'] }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        @else
                            <span class="flex h-full w-full items-center justify-center" style="color: {{ $cosa['tono'] }}66;">
                                <x-omni-icon :name="$iconos[$cosa['tipo']] ?? 'punto'" size="h-12 w-12" />
                            </span>
                        @endif
                        <span class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full bg-slate-950/85 px-2 py-0.5 text-[10px] font-black backdrop-blur"
                            style="color: {{ $cosa['tono'] }}">
                            <x-omni-icon :name="$iconos[$cosa['tipo']] ?? 'punto'" size="h-3 w-3" /> {{ $cosa['tipo'] }}
                        </span>
                    </span>
                    <span class="block px-3 py-2">
                        <span class="block truncate text-sm font-black text-white">{{ $cosa['nombre'] }}</span>
                        <span class="block text-[10px] text-slate-500">{{ $cosa['cuando']?->diffForHumans() }}</span>
                    </span>
                </a>
            @endforeach
        </div>

        {{-- LISTA --}}
        <div x-show="vista === 'lista'" x-cloak class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($reciente as $cosa)
                <a href="{{ $cosa['url'] }}" x-show="ve(@js($cosa['tipo']))"
                    class="flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-900/60 p-2 transition hover:bg-slate-900">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border"
                        style="border-color: {{ $cosa['tono'] }}55; background-color: {{ $cosa['tono'] }}14; color: {{ $cosa['tono'] }};">
                        @if ($cosa['img'])
                            <img src="{{ $cosa['img'] }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        @else
                            <x-omni-icon :name="$iconos[$cosa['tipo']] ?? 'punto'" size="h-5 w-5" />
                        @endif
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-bold text-white">{{ $cosa['nombre'] }}</span>
                        <span class="block text-[11px]" style="color: {{ $cosa['tono'] }}">{{ $cosa['tipo'] }}
                            <span class="text-slate-500">· {{ $cosa['cuando']?->diffForHumans() }}</span></span>
                    </span>
                    <x-omni-icon name="chevron-derecha" size="h-4 w-4" class="text-slate-600" />
                </a>
            @endforeach
        </div>
    </section>
@endif
