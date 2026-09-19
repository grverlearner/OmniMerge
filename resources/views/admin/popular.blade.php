<x-admin-layout title="Lo más visto">

    <x-slot:header>Lo más visto</x-slot:header>

    @php
        $tipos = collect(\App\Http\Controllers\Admin\AdminInsightController::POPULAR_TYPES)->mapWithKeys(fn ($k) => [$k => \App\Services\Admin\ContentRegistry::TYPES[$k]]);
        $unidad = $filtros['medida'] === 'clones' ? 'clonaciones' : 'vistas';
        $enlace = fn (array $cambios) => route('admin.popular', array_filter(array_merge($filtros, $cambios)));
    @endphp

    <div x-data="{
        vista: (() => { try { return localStorage.getItem('admin.popular.vista') || 'ranking' } catch (e) { return 'ranking' } })(),
        poner(v) { this.vista = v; try { localStorage.setItem('admin.popular.vista', v) } catch (e) {} },
    }" class="space-y-4">

        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
            <p class="text-sm text-slate-400">
                <span class="font-black text-white">De siempre</span> usa los contadores que guarda cada pieza.
                <span class="font-black text-white">Por periodo</span> cuenta las visitas y clonaciones registradas en la comunidad en esos días.
            </p>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                {{-- Qué medir --}}
                <div class="flex rounded-xl border border-slate-800 bg-slate-950 p-1">
                    @foreach (['vistas' => ['Vistas', 'ojo'], 'clones' => ['Clonaciones', 'copiar']] as $clave => [$texto, $icono])
                        <a href="{{ $enlace(['medida' => $clave]) }}"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-[11px] font-black {{ $filtros['medida'] === $clave ? 'bg-rose-500/20 text-rose-200' : 'text-slate-500 hover:text-slate-300' }}">
                            <x-omni-icon :name="$icono" size="h-3.5 w-3.5" /> {{ $texto }}
                        </a>
                    @endforeach
                </div>

                {{-- Cuándo --}}
                <div class="flex flex-wrap rounded-xl border border-slate-800 bg-slate-950 p-1">
                    @foreach (\App\Http\Controllers\Admin\AdminInsightController::PERIODS as $clave => $texto)
                        <a href="{{ $enlace(['periodo' => $clave]) }}"
                            class="rounded-lg px-3 py-1.5 text-[11px] font-black {{ $filtros['periodo'] === (string) $clave ? 'bg-rose-500/20 text-rose-200' : 'text-slate-500 hover:text-slate-300' }}">{{ $texto }}</a>
                    @endforeach
                </div>

                {{-- Cómo mirarlo --}}
                <div class="ml-auto flex rounded-xl border border-slate-800 bg-slate-950 p-1">
                    @foreach ([['ranking', 'barras', 'Ranking'], ['galeria', 'galeria', 'Galería'], ['tabla', 'panel', 'Tabla']] as [$v, $icono, $texto])
                        <button type="button" @click="poner('{{ $v }}')"
                            :class="vista === '{{ $v }}' ? 'bg-rose-500/20 text-rose-200' : 'text-slate-500 hover:text-slate-300'"
                            class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[11px] font-black">
                            <x-omni-icon :name="$icono" size="h-3.5 w-3.5" /> <span class="hidden sm:inline">{{ $texto }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- De qué tipo --}}
            <div class="mt-3 flex flex-wrap gap-1.5">
                <a href="{{ $enlace(['tipo' => null]) }}"
                    class="rounded-full border px-3 py-1 text-[11px] font-black {{ ! $filtros['tipo'] ? 'border-rose-500/60 bg-rose-500/15 text-rose-200' : 'border-slate-800 text-slate-400 hover:text-white' }}">Todos los tipos</a>
                @foreach ($tipos as $clave => $tipo)
                    <a href="{{ $enlace(['tipo' => $clave]) }}"
                        class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-[11px] font-black"
                        style="color: {{ $tipo['tone'] }}; border-color: {{ $tipo['tone'] }}{{ $filtros['tipo'] === $clave ? 'cc' : '33' }}; background-color: {{ $tipo['tone'] }}{{ $filtros['tipo'] === $clave ? '22' : '08' }};">
                        <x-omni-icon :name="$tipo['icon']" size="h-3 w-3" /> {{ $tipo['label'] }}
                    </a>
                @endforeach
            </div>
        </section>


        <div class="grid gap-5 xl:grid-cols-4">

            <section class="xl:col-span-3">
                @if ($filas->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-800 p-10 text-center">
                        <x-omni-icon name="barras" size="h-8 w-8" class="mx-auto text-slate-700" />
                        <p class="mt-2 text-sm font-bold text-slate-400">No hay {{ $unidad }} registradas con estos filtros.</p>
                    </div>
                @else

                    {{-- RANKING --}}
                    <div x-show="vista === 'ranking'" class="space-y-2">
                        @foreach ($filas as $i => $fila)
                            <a href="{{ route('admin.content.show', [$fila['key'], $fila['model']->id]) }}"
                                class="relative flex items-center gap-3 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 p-2.5 transition hover:border-slate-700">
                                <span class="absolute inset-y-0 left-0 opacity-15" style="width: {{ $fila['total'] / $maximo * 100 }}%; background-color: {{ $fila['meta']['tone'] }}"></span>

                                <span class="relative w-8 text-center text-lg font-black {{ $i < 3 ? 'text-amber-300' : 'text-slate-600' }}">{{ $i + 1 }}</span>
                                <span class="relative">@include('admin.partials.thumb', ['modelo' => $fila['model'], 'meta' => $fila['meta']])</span>
                                <span class="relative min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-1.5">
                                        <span class="truncate text-sm font-black text-white">{{ $fila['model']->name }}</span>
                                        <x-content-badges :type="$fila['key']" :id="$fila['model']->id" :flags="$fila['flags']" :all="true" size="xs" />
                                    </span>
                                    <span class="block truncate text-[11px] text-slate-500">{{ $fila['meta']['one'] }} · {{ $fila['model']->user?->name }}</span>
                                </span>
                                <span class="relative text-right">
                                    <span class="block text-lg font-black text-white">{{ number_format($fila['total']) }}</span>
                                    <span class="block text-[10px] font-bold text-slate-500">{{ $unidad }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>

                    {{-- GALERÍA --}}
                    <div x-show="vista === 'galeria'" x-cloak class="grid grid-cols-2 gap-3 md:grid-cols-3 2xl:grid-cols-4">
                        @foreach ($filas as $i => $fila)
                            <a href="{{ route('admin.content.show', [$fila['key'], $fila['model']->id]) }}"
                                class="group overflow-hidden rounded-2xl border bg-slate-900/60" style="border-color: {{ $fila['meta']['tone'] }}33;">
                                <div class="relative aspect-square" style="background-color: {{ $fila['meta']['tone'] }}12;">
                                    @if ($fila['model']->image_url)
                                        <img src="{{ $fila['model']->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover transition group-hover:scale-105">
                                    @else
                                        <div class="flex h-full items-center justify-center" style="color: {{ $fila['meta']['tone'] }}88;"><x-omni-icon :name="$fila['meta']['icon']" size="h-10 w-10" /></div>
                                    @endif
                                    <span class="absolute left-2 top-2 rounded-full bg-slate-950/85 px-2 py-0.5 text-xs font-black {{ $i < 3 ? 'text-amber-300' : 'text-slate-300' }}">#{{ $i + 1 }}</span>
                                    <span class="absolute bottom-2 right-2 rounded-full bg-slate-950/85 px-2 py-0.5 text-xs font-black text-white">{{ number_format($fila['total']) }} {{ $unidad }}</span>
                                </div>
                                <div class="p-2.5">
                                    <p class="truncate text-sm font-black text-white">{{ $fila['model']->name }}</p>
                                    <p class="truncate text-[11px] text-slate-500">{{ $fila['meta']['one'] }} · {{ $fila['model']->user?->name }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    {{-- TABLA --}}
                    <div x-show="vista === 'tabla'" x-cloak class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/60">
                        <table class="w-full min-w-[720px] text-left text-sm">
                            <thead class="border-b border-slate-800 text-[10px] font-black uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-3 py-3">#</th>
                                    <th class="px-3 py-3">Pieza</th>
                                    <th class="px-3 py-3">Tipo</th>
                                    <th class="px-3 py-3">Dueño</th>
                                    <th class="px-3 py-3 text-right">{{ ucfirst($unidad) }} ({{ \Illuminate\Support\Str::lower(\App\Http\Controllers\Admin\AdminInsightController::PERIODS[$filtros['periodo']]) }})</th>
                                    <th class="px-3 py-3 text-right">Vistas totales</th>
                                    <th class="px-3 py-3 text-right">Clones totales</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/70">
                                @foreach ($filas as $i => $fila)
                                    <tr class="hover:bg-slate-900">
                                        <td class="px-3 py-2 font-black text-slate-500">{{ $i + 1 }}</td>
                                        <td class="px-3 py-2"><a href="{{ route('admin.content.show', [$fila['key'], $fila['model']->id]) }}" class="font-bold text-slate-100 hover:text-white">{{ $fila['model']->name }}</a></td>
                                        <td class="px-3 py-2 text-xs font-bold" style="color: {{ $fila['meta']['tone'] }}">{{ $fila['meta']['one'] }}</td>
                                        <td class="px-3 py-2 text-xs text-slate-400">{{ $fila['model']->user?->name }}</td>
                                        <td class="px-3 py-2 text-right font-black text-white">{{ $fila['total'] }}</td>
                                        <td class="px-3 py-2 text-right text-slate-300">{{ $fila['vistas'] }}</td>
                                        <td class="px-3 py-2 text-right text-slate-300">{{ $fila['clones'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>


            {{-- Los creadores más mirados --}}
            <aside class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                <h3 class="text-sm font-black text-white">Creadores más mirados</h3>
                <p class="text-xs text-slate-500">Suma de {{ $unidad }} de sus piezas en esta lista.</p>

                <div class="mt-3 space-y-2">
                    @forelse ($creadores as $c)
                        <a href="{{ route('admin.users.show', $c['user']->id) }}" class="flex items-center gap-2.5 rounded-xl border border-slate-800 bg-slate-950/40 p-2 hover:border-slate-700">
                            <x-user-avatar :user="$c['user']" size="sm" />
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-1"><span class="truncate text-sm font-bold text-slate-200">{{ $c['user']->name }}</span> <x-creator-badge :user="$c['user']" size="xs" /></span>
                                <span class="block text-[11px] text-slate-500">{{ $c['piezas'] }} piezas en la lista</span>
                            </span>
                            <span class="text-sm font-black text-white">{{ $c['total'] }}</span>
                        </a>
                    @empty
                        <p class="text-xs text-slate-500">Nadie todavía.</p>
                    @endforelse
                </div>
            </aside>
        </div>
    </div>

</x-admin-layout>
