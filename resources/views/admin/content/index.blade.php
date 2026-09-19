<x-admin-layout :title="$meta['label']">

    <x-slot:header>{{ $meta['label'] }}</x-slot:header>

    @php
        $flagsMeta = \App\Models\ContentFlag::FLAGS;
        $eliminados = $filtros['estado'] === 'eliminados';
        $base = array_filter(['q' => $filtros['q'], 'dueno' => $filtros['dueno'], 'visibilidad' => $filtros['visibilidad'], 'orden' => $filtros['orden']]);
    @endphp

    <div x-data="{
        vista: (() => { try { return localStorage.getItem('admin.content.vista') || 'cuadricula' } catch (e) { return 'cuadricula' } })(),
        marcadas: [],
        poner(v) { this.vista = v; try { localStorage.setItem('admin.content.vista', v) } catch (e) {} },
        alternar(id) { this.marcadas.includes(id) ? this.marcadas = this.marcadas.filter(x => x !== id) : this.marcadas.push(id) },
        todas(ids) { this.marcadas = this.marcadas.length === ids.length ? [] : [...ids] },
    }" class="space-y-4">

        {{-- ========================================================= --}}
        {{-- CABECERA DEL TIPO --}}
        {{-- ========================================================= --}}

        <section class="flex flex-wrap items-center gap-4 rounded-2xl border bg-slate-900/60 p-4" style="border-color: {{ $meta['tone'] }}44;">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl border" style="color: {{ $meta['tone'] }}; border-color: {{ $meta['tone'] }}66; background-color: {{ $meta['tone'] }}1a;">
                <x-omni-icon :name="$meta['icon']" size="h-6 w-6" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">{{ $meta['module'] }} · de todas las cuentas</p>
                <h2 class="text-xl font-black text-white">{{ $meta['label'] }}</h2>
                <p class="text-xs text-slate-400">
                    Marca lo que sea de confianza, oculta de la comunidad lo que no deba verse
                    @if ($meta['visibility']), cambia su visibilidad @endif
                    o elimínalo. Para cambiar lo de dentro, abre su ficha: como admin puedes editarla aunque no sea tuya.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach ($flagsMeta as $clave => $flag)
                    <a href="{{ route('admin.content.index', [$type] + $base + ['marca' => $filtros['marca'] === $clave ? null : $clave]) }}"
                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-black transition"
                        style="color: {{ $flag['tone'] }}; border-color: {{ $flag['tone'] }}{{ $filtros['marca'] === $clave ? 'cc' : '44' }}; background-color: {{ $flag['tone'] }}{{ $filtros['marca'] === $clave ? '2a' : '10' }};">
                        <x-omni-icon :name="$flag['icon']" size="h-3 w-3" /> {{ $flag['label'] }} · {{ $totales['marcas'][$clave] ?? 0 }}
                    </a>
                @endforeach
            </div>
        </section>


        {{-- ========================================================= --}}
        {{-- ACTIVOS / ELIMINADOS --}}
        {{-- ========================================================= --}}

        <nav class="flex flex-wrap gap-2">
            @foreach (['' => ['Activos', $totales['activos'], 'check'], 'eliminados' => ['Eliminados', $totales['eliminados'], 'papelera']] as $clave => [$texto, $n, $icono])
                @php $activa = $filtros['estado'] === $clave; @endphp
                <a href="{{ route('admin.content.index', [$type] + $base + array_filter(['estado' => $clave, 'marca' => $filtros['marca']])) }}"
                    class="inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs font-black {{ $activa ? 'border-rose-500/60 bg-rose-500/15 text-rose-200' : 'border-slate-800 bg-slate-900/60 text-slate-400 hover:text-slate-200' }}">
                    <x-omni-icon :name="$icono" size="h-3.5 w-3.5" /> {{ $texto }}
                    <span class="rounded-full px-1.5 text-[10px] {{ $activa ? 'bg-rose-500/30' : 'bg-slate-800' }}">{{ $n }}</span>
                </a>
            @endforeach
        </nav>


        {{-- ========================================================= --}}
        {{-- FILTROS --}}
        {{-- ========================================================= --}}

        <form method="GET" class="flex flex-wrap items-end gap-2 rounded-2xl border border-slate-800 bg-slate-900/60 p-3">
            <input type="hidden" name="estado" value="{{ $filtros['estado'] }}">

            <label class="min-w-[12rem] flex-1">
                <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-500">Buscar</span>
                <input type="search" name="q" value="{{ $filtros['q'] }}" placeholder="Nombre o código"
                    class="w-full rounded-xl border-slate-700 bg-slate-950 text-sm text-slate-100 placeholder:text-slate-600 focus:border-rose-500 focus:ring-rose-500">
            </label>

            <label>
                <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-500">Dueño</span>
                <select name="dueno" class="max-w-[12rem] rounded-xl border-slate-700 bg-slate-950 text-sm text-slate-100">
                    <option value="">Cualquiera</option>
                    @foreach ($duenos as $d)
                        <option value="{{ $d->id }}" @selected($filtros['dueno'] == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </label>

            @if ($meta['visibility'])
                <label>
                    <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-500">Visibilidad</span>
                    <select name="visibilidad" class="rounded-xl border-slate-700 bg-slate-950 text-sm text-slate-100">
                        <option value="">Cualquiera</option>
                        @foreach (\App\Services\Admin\ContentRegistry::VISIBILITIES as $clave => $texto)
                            <option value="{{ $clave }}" @selected($filtros['visibilidad'] === $clave)>{{ $texto }}</option>
                        @endforeach
                    </select>
                </label>
            @endif

            <label>
                <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-500">Marca</span>
                <select name="marca" class="rounded-xl border-slate-700 bg-slate-950 text-sm text-slate-100">
                    <option value="">Cualquiera</option>
                    @foreach ($flagsMeta as $clave => $flag)
                        <option value="{{ $clave }}" @selected($filtros['marca'] === $clave)>{{ $flag['label'] }}</option>
                    @endforeach
                    <option value="sin-marca" @selected($filtros['marca'] === 'sin-marca')>Sin ninguna marca</option>
                </select>
            </label>

            <label>
                <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-500">Ordenar</span>
                <select name="orden" class="rounded-xl border-slate-700 bg-slate-950 text-sm text-slate-100">
                    @foreach (\App\Http\Controllers\Admin\AdminContentController::SORTS as $clave => $texto)
                        @continue($clave === 'vistas' && ! $meta['views'])
                        @continue($clave === 'clones' && ! $meta['clones'])
                        <option value="{{ $clave }}" @selected($filtros['orden'] === $clave)>{{ $texto }}</option>
                    @endforeach
                </select>
            </label>

            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-rose-500 px-4 py-2.5 text-xs font-black text-white hover:bg-rose-400">
                <x-omni-icon name="filtro" size="h-3.5 w-3.5" /> Aplicar
            </button>

            @if ($filtros['q'] || $filtros['dueno'] || $filtros['visibilidad'] || $filtros['marca'])
                <a href="{{ route('admin.content.index', [$type] + array_filter(['estado' => $filtros['estado']])) }}" class="rounded-xl border border-slate-700 px-3 py-2.5 text-xs font-black text-slate-400 hover:text-white">Quitar filtros</a>
            @endif

            <div class="ml-auto flex rounded-xl border border-slate-800 bg-slate-950 p-1">
                @foreach ([['cuadricula', 'cuadricula', 'Cuadrícula'], ['lista', 'menu', 'Lista'], ['tabla', 'panel', 'Tabla']] as [$v, $icono, $texto])
                    <button type="button" @click="poner('{{ $v }}')" title="{{ $texto }}"
                        :class="vista === '{{ $v }}' ? 'bg-rose-500/20 text-rose-200' : 'text-slate-500 hover:text-slate-300'"
                        class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[11px] font-black">
                        <x-omni-icon :name="$icono" size="h-3.5 w-3.5" /> <span class="hidden sm:inline">{{ $texto }}</span>
                    </button>
                @endforeach
            </div>
        </form>


        {{-- ========================================================= --}}
        {{-- ACCIONES EN BLOQUE --}}
        {{-- ========================================================= --}}

        <form method="POST" action="{{ route('admin.content.bulk', $type) }}" x-show="marcadas.length" x-cloak
            data-omni-confirm data-confirm-variant="warning" data-confirm-title="Aplicar a los marcados"
            data-confirm-message="Se aplicará a todos los elementos marcados y quedará en el registro de acciones." data-confirm-action="Aplicar"
            class="sticky top-24 z-20 flex flex-wrap items-center gap-2 rounded-2xl border border-rose-500/40 bg-slate-900/95 p-3 backdrop-blur">
            @csrf
            <template x-for="id in marcadas" :key="id"><input type="hidden" name="ids[]" :value="id"></template>

            <span class="text-xs font-black text-rose-200"><span x-text="marcadas.length"></span> marcados</span>

            <select name="accion" class="rounded-xl border-slate-700 bg-slate-950 text-xs text-slate-100">
                @if ($eliminados)
                    <option value="restore">Recuperar</option>
                @else
                    <optgroup label="Marcas">
                        @foreach ($flagsMeta as $clave => $flag)
                            <option value="flag-{{ $clave }}">Marcar como {{ \Illuminate\Support\Str::lower($flag['label']) }}</option>
                            <option value="unflag-{{ $clave }}">Quitar «{{ $flag['label'] }}»</option>
                        @endforeach
                    </optgroup>
                    @if ($meta['visibility'])
                        <optgroup label="Visibilidad">
                            @foreach (\App\Services\Admin\ContentRegistry::VISIBILITIES as $clave => $texto)
                                <option value="visibility-{{ $clave }}">Hacer {{ \Illuminate\Support\Str::lower($texto) }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                    <option value="delete">Eliminar (recuperable)</option>
                @endif
            </select>

            <button type="submit" class="rounded-xl bg-rose-500 px-3 py-2 text-xs font-black text-white hover:bg-rose-400">Aplicar a los marcados</button>
            <button type="button" @click="marcadas = []" class="text-xs font-bold text-slate-400 hover:text-white">Desmarcar</button>
        </form>


        @if ($items->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-800 p-10 text-center">
                <x-omni-icon :name="$meta['icon']" size="h-8 w-8" class="mx-auto text-slate-700" />
                <p class="mt-2 text-sm font-bold text-slate-400">{{ $eliminados ? 'No hay nada eliminado.' : 'No hay nada con estos filtros.' }}</p>
            </div>
        @else
            @php $ids = $items->pluck('id')->values(); @endphp

            <div class="flex items-center justify-between text-[11px] text-slate-500">
                <button type="button" @click="todas(@js($ids))" class="font-black text-slate-400 hover:text-white"
                    x-text="marcadas.length === {{ $ids->count() }} ? 'Desmarcar todos' : 'Marcar los {{ $ids->count() }} de esta página'"></button>
                <span>{{ $items->total() }} en total</span>
            </div>


            {{-- ===================================================== --}}
            {{-- CUADRÍCULA --}}
            {{-- ===================================================== --}}

            <div x-show="vista === 'cuadricula'" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5">
                @foreach ($items as $item)
                    @php
                        $marcas = $flags[$item->id] ?? [];
                        $dueno = $type === 'competition' ? $item->user : $item->user;
                    @endphp
                    <article class="group relative overflow-hidden rounded-2xl border bg-slate-900/60 transition hover:bg-slate-900 {{ in_array('HIDDEN', $marcas) ? 'border-rose-500/40' : 'border-slate-800' }} {{ $item->trashed() ? 'opacity-70' : '' }}">
                        <input type="checkbox" @change="alternar({{ $item->id }})" :checked="marcadas.includes({{ $item->id }})"
                            class="absolute left-3 top-3 z-10 rounded border-slate-600 bg-slate-950/80 text-rose-500 focus:ring-rose-500" aria-label="Marcar">

                        <a href="{{ route('admin.content.show', [$type, $item->id]) }}" class="block">
                            <div class="relative aspect-[16/10] overflow-hidden" style="background-color: {{ $meta['tone'] }}12;">
                                @if ($item->image_url)
                                    <img src="{{ $item->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @else
                                    <div class="flex h-full items-center justify-center" style="color: {{ $meta['tone'] }}88;">
                                        <x-omni-icon :name="$meta['icon']" size="h-10 w-10" />
                                    </div>
                                @endif

                                @if ($meta['visibility'] && $item->visibility)
                                    <span class="absolute right-2 top-2 rounded-full bg-slate-950/80 px-2 py-0.5 text-[10px] font-black text-slate-300">
                                        {{ \App\Services\Admin\ContentRegistry::VISIBILITIES[$item->visibility] ?? $item->visibility }}
                                    </span>
                                @endif
                            </div>

                            <div class="p-3">
                                <p class="truncate text-sm font-black text-white">{{ $item->name }}</p>
                                <p class="flex items-center gap-1.5 truncate text-[11px] text-slate-500">
                                    {{ $dueno?->name ?? 'Sin dueño' }} <x-creator-badge :user="$dueno" size="xs" />
                                </p>

                                <div class="mt-2 flex min-h-[1.25rem] flex-wrap gap-1">
                                    <x-content-badges :type="$type" :id="$item->id" :flags="$marcas" :all="true" size="xs" />
                                    @if ($item->trashed())
                                        <span class="rounded-full bg-slate-800 px-1.5 py-0.5 text-[9px] font-black text-slate-400">Eliminado</span>
                                    @endif
                                </div>

                                <p class="mt-2 flex gap-3 text-[11px] font-bold text-slate-500">
                                    @if ($meta['views']) <span class="inline-flex items-center gap-1"><x-omni-icon name="ojo" size="h-3 w-3" /> {{ $item->views_count }}</span> @endif
                                    @if ($meta['clones']) <span class="inline-flex items-center gap-1"><x-omni-icon name="copiar" size="h-3 w-3" /> {{ $item->clones_count }}</span> @endif
                                    <span class="ml-auto">{{ $item->created_at?->format('d/m/Y') }}</span>
                                </p>
                            </div>
                        </a>

                        {{-- Marcar o desmarcar sin entrar --}}
                        @unless ($item->trashed())
                            <div class="flex border-t border-slate-800">
                                @foreach ($flagsMeta as $clave => $flag)
                                    @php $puesta = in_array($clave, $marcas); @endphp
                                    <form method="POST" action="{{ route('admin.content.flag', [$type, $item->id]) }}" class="flex-1">
                                        @csrf
                                        <input type="hidden" name="flag" value="{{ $clave }}">
                                        <input type="hidden" name="on" value="{{ $puesta ? 0 : 1 }}">
                                        <button type="submit" title="{{ $puesta ? 'Quitar' : 'Marcar como' }} {{ \Illuminate\Support\Str::lower($flag['label']) }}"
                                            class="flex w-full justify-center py-2 transition hover:bg-slate-800"
                                            style="color: {{ $puesta ? $flag['tone'] : '#475569' }}">
                                            <x-omni-icon :name="$flag['icon']" size="h-4 w-4" />
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @endunless
                    </article>
                @endforeach
            </div>


            {{-- ===================================================== --}}
            {{-- LISTA --}}
            {{-- ===================================================== --}}

            <div x-show="vista === 'lista'" x-cloak class="space-y-2">
                @foreach ($items as $item)
                    <div class="flex items-center gap-3 rounded-2xl border border-slate-800 bg-slate-900/60 p-2.5 {{ $item->trashed() ? 'opacity-70' : '' }}">
                        <input type="checkbox" @change="alternar({{ $item->id }})" :checked="marcadas.includes({{ $item->id }})"
                            class="rounded border-slate-600 bg-slate-950 text-rose-500 focus:ring-rose-500">
                        @include('admin.partials.thumb', ['modelo' => $item, 'meta' => $meta])
                        <a href="{{ route('admin.content.show', [$type, $item->id]) }}" class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-1.5">
                                <span class="truncate text-sm font-black text-white">{{ $item->name }}</span>
                                <x-content-badges :type="$type" :id="$item->id" :flags="$flags[$item->id] ?? []" :all="true" size="xs" />
                            </span>
                            <span class="block truncate text-[11px] text-slate-500">{{ $item->code }} · {{ $item->user?->name }} · {{ $item->created_at?->diffForHumans() }}</span>
                        </a>
                        @if ($meta['views'])
                            <span class="hidden items-center gap-1 text-xs font-bold text-slate-400 sm:inline-flex"><x-omni-icon name="ojo" size="h-3.5 w-3.5" /> {{ $item->views_count }}</span>
                        @endif
                    </div>
                @endforeach
            </div>


            {{-- ===================================================== --}}
            {{-- TABLA --}}
            {{-- ===================================================== --}}

            <div x-show="vista === 'tabla'" x-cloak class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/60">
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead class="border-b border-slate-800 text-[10px] font-black uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="w-10 px-3 py-3"></th>
                            <th class="px-3 py-3">Nombre</th>
                            <th class="px-3 py-3">Dueño</th>
                            @if ($meta['visibility']) <th class="px-3 py-3">Visibilidad</th> @endif
                            <th class="px-3 py-3">Marcas</th>
                            @if ($meta['views']) <th class="px-3 py-3 text-right">Vistas</th> @endif
                            @if ($meta['clones']) <th class="px-3 py-3 text-right">Clones</th> @endif
                            <th class="px-3 py-3">Creado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/70">
                        @foreach ($items as $item)
                            <tr class="hover:bg-slate-900 {{ $item->trashed() ? 'opacity-70' : '' }}">
                                <td class="px-3 py-2">
                                    <input type="checkbox" @change="alternar({{ $item->id }})" :checked="marcadas.includes({{ $item->id }})"
                                        class="rounded border-slate-600 bg-slate-950 text-rose-500 focus:ring-rose-500">
                                </td>
                                <td class="px-3 py-2">
                                    <a href="{{ route('admin.content.show', [$type, $item->id]) }}" class="flex items-center gap-2.5">
                                        @include('admin.partials.thumb', ['modelo' => $item, 'meta' => $meta, 'clase' => 'h-8 w-8 rounded-lg', 'icono' => 'h-4 w-4'])
                                        <span>
                                            <span class="block font-bold text-slate-100">{{ $item->name }}</span>
                                            <span class="block text-[11px] text-slate-500">{{ $item->code }}</span>
                                        </span>
                                    </a>
                                </td>
                                <td class="px-3 py-2 text-xs text-slate-300">
                                    @if ($item->user)
                                        <a href="{{ route('admin.users.show', $item->user->id) }}" class="hover:text-white">{{ $item->user->name }}</a>
                                    @else — @endif
                                </td>
                                @if ($meta['visibility'])
                                    <td class="px-3 py-2 text-xs text-slate-400">{{ \App\Services\Admin\ContentRegistry::VISIBILITIES[$item->visibility] ?? $item->visibility }}</td>
                                @endif
                                <td class="px-3 py-2"><div class="flex flex-wrap gap-1"><x-content-badges :type="$type" :id="$item->id" :flags="$flags[$item->id] ?? []" :all="true" size="xs" /></div></td>
                                @if ($meta['views']) <td class="px-3 py-2 text-right font-bold text-slate-300">{{ $item->views_count }}</td> @endif
                                @if ($meta['clones']) <td class="px-3 py-2 text-right font-bold text-slate-300">{{ $item->clones_count }}</td> @endif
                                <td class="px-3 py-2 text-xs text-slate-400">{{ $item->created_at?->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div>{{ $items->links() }}</div>
        @endif
    </div>

</x-admin-layout>
