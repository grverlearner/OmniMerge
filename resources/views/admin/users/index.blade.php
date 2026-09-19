<x-admin-layout title="Usuarios">

    <x-slot:header>Usuarios</x-slot:header>

    @php
        $pestanas = [
            '' => ['Todas', $totales['todas'], 'usuario'],
            'activas' => ['Activas', $totales['activas'], 'check'],
            'bloqueadas' => ['Bloqueadas', $totales['bloqueadas'], 'prohibido'],
            'sin-entrar' => ['Nunca entraron', $totales['sin-entrar'], 'puerta'],
            'eliminadas' => ['Eliminadas', $totales['eliminadas'], 'papelera'],
        ];
    @endphp

    <div x-data="{
        vista: (() => { try { return localStorage.getItem('admin.users.vista') || 'tarjetas' } catch (e) { return 'tarjetas' } })(),
        marcadas: [],
        poner(v) { this.vista = v; try { localStorage.setItem('admin.users.vista', v) } catch (e) {} },
        alternar(id) { this.marcadas.includes(id) ? this.marcadas = this.marcadas.filter(x => x !== id) : this.marcadas.push(id) },
        todas(ids) { this.marcadas = this.marcadas.length === ids.length ? [] : [...ids] },
    }" class="space-y-4">

        {{-- ========================================================= --}}
        {{-- ESTADOS --}}
        {{-- ========================================================= --}}

        <nav class="flex flex-wrap gap-2">
            @foreach ($pestanas as $clave => [$texto, $cuenta, $icono])
                @php $activa = $filtros['estado'] === $clave; @endphp
                <a href="{{ route('admin.users.index', array_filter(['estado' => $clave, 'q' => $filtros['buscar'], 'rol' => $filtros['rol'], 'insignia' => $filtros['insignia'], 'orden' => $filtros['orden']])) }}"
                    class="inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs font-black transition {{ $activa ? 'border-rose-500/60 bg-rose-500/15 text-rose-200' : 'border-slate-800 bg-slate-900/60 text-slate-400 hover:border-slate-700 hover:text-slate-200' }}">
                    <x-omni-icon :name="$icono" size="h-3.5 w-3.5" />
                    {{ $texto }}
                    <span class="rounded-full px-1.5 text-[10px] {{ $activa ? 'bg-rose-500/30' : 'bg-slate-800' }}">{{ $cuenta }}</span>
                </a>
            @endforeach
        </nav>


        {{-- ========================================================= --}}
        {{-- FILTROS --}}
        {{-- ========================================================= --}}

        <form method="GET" class="flex flex-wrap items-end gap-2 rounded-2xl border border-slate-800 bg-slate-900/60 p-3">
            <input type="hidden" name="estado" value="{{ $filtros['estado'] }}">

            <label class="min-w-[14rem] flex-1">
                <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-500">Buscar</span>
                <input type="search" name="q" value="{{ $filtros['buscar'] }}" placeholder="Nombre, usuario o correo"
                    class="w-full rounded-xl border-slate-700 bg-slate-950 text-sm text-slate-100 placeholder:text-slate-600 focus:border-rose-500 focus:ring-rose-500">
            </label>

            <label>
                <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-500">Rol</span>
                <select name="rol" class="rounded-xl border-slate-700 bg-slate-950 text-sm text-slate-100 focus:border-rose-500 focus:ring-rose-500">
                    <option value="">Todos</option>
                    <option value="ADMIN" @selected($filtros['rol'] === 'ADMIN')>Administradores</option>
                    <option value="USER" @selected($filtros['rol'] === 'USER')>Usuarios</option>
                </select>
            </label>

            <label>
                <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-500">Insignia</span>
                <select name="insignia" class="rounded-xl border-slate-700 bg-slate-950 text-sm text-slate-100 focus:border-rose-500 focus:ring-rose-500">
                    <option value="">Cualquiera</option>
                    @foreach (\App\Models\User::CREATOR_BADGES as $clave => $badge)
                        <option value="{{ $clave }}" @selected($filtros['insignia'] === $clave)>{{ $badge['label'] }}</option>
                    @endforeach
                    <option value="ninguna" @selected($filtros['insignia'] === 'ninguna')>Sin insignia</option>
                </select>
            </label>

            <label>
                <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-500">Ordenar</span>
                <select name="orden" class="rounded-xl border-slate-700 bg-slate-950 text-sm text-slate-100 focus:border-rose-500 focus:ring-rose-500">
                    @foreach (\App\Http\Controllers\Admin\AdminUserController::SORTS as $clave => $texto)
                        <option value="{{ $clave }}" @selected($filtros['orden'] === $clave)>{{ $texto }}</option>
                    @endforeach
                </select>
            </label>

            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-rose-500 px-4 py-2.5 text-xs font-black text-white hover:bg-rose-400">
                <x-omni-icon name="filtro" size="h-3.5 w-3.5" /> Aplicar
            </button>

            @if ($filtros['buscar'] || $filtros['rol'] || $filtros['insignia'])
                <a href="{{ route('admin.users.index', array_filter(['estado' => $filtros['estado']])) }}" class="rounded-xl border border-slate-700 px-3 py-2.5 text-xs font-black text-slate-400 hover:text-white">Quitar filtros</a>
            @endif

            {{-- Cómo mirarlo --}}
            <div class="ml-auto flex rounded-xl border border-slate-800 bg-slate-950 p-1">
                @foreach ([['tarjetas', 'cuadricula', 'Tarjetas'], ['lista', 'menu', 'Lista'], ['tabla', 'panel', 'Tabla']] as [$v, $icono, $texto])
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

        <form method="POST" action="{{ route('admin.users.bulk') }}" x-show="marcadas.length" x-cloak
            class="sticky top-24 z-20 flex flex-wrap items-center gap-2 rounded-2xl border border-rose-500/40 bg-slate-900/95 p-3 backdrop-blur">
            @csrf
            <template x-for="id in marcadas" :key="id"><input type="hidden" name="ids[]" :value="id"></template>

            <span class="text-xs font-black text-rose-200"><span x-text="marcadas.length"></span> marcadas</span>

            <select name="accion" class="rounded-xl border-slate-700 bg-slate-950 text-xs text-slate-100">
                <option value="badge-VERIFIED">Dar «Creador verificado»</option>
                <option value="badge-TRUSTED">Dar «Creador confiable»</option>
                <option value="badge-none">Quitar la insignia</option>
                <option value="unban">Desbloquear</option>
                <option value="sessions">Cerrar sus sesiones</option>
            </select>

            <button type="submit" class="rounded-xl bg-rose-500 px-3 py-2 text-xs font-black text-white hover:bg-rose-400">Aplicar a las marcadas</button>
            <button type="button" @click="marcadas = []" class="text-xs font-bold text-slate-400 hover:text-white">Desmarcar</button>

            <span class="ml-auto text-[11px] text-slate-500">Tu propia cuenta nunca entra en una acción en bloque.</span>
        </form>


        @if ($usuarios->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-800 p-10 text-center">
                <x-omni-icon name="usuario" size="h-8 w-8" class="mx-auto text-slate-700" />
                <p class="mt-2 text-sm font-bold text-slate-400">No hay cuentas con estos filtros.</p>
            </div>
        @else
            @php $ids = $usuarios->pluck('id')->values(); @endphp

            <div class="flex items-center justify-between text-[11px] text-slate-500">
                <button type="button" @click="todas(@js($ids))" class="font-black text-slate-400 hover:text-white"
                    x-text="marcadas.length === {{ $ids->count() }} ? 'Desmarcar todas' : 'Marcar las {{ $ids->count() }} de esta página'"></button>
                <span>{{ $usuarios->total() }} {{ \Illuminate\Support\Str::plural('cuenta', $usuarios->total()) }}</span>
            </div>


            {{-- ===================================================== --}}
            {{-- TARJETAS --}}
            {{-- ===================================================== --}}

            <div x-show="vista === 'tarjetas'" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                @foreach ($usuarios as $u)
                    @include('admin.users.partials.card', ['u' => $u])
                @endforeach
            </div>


            {{-- ===================================================== --}}
            {{-- LISTA --}}
            {{-- ===================================================== --}}

            <div x-show="vista === 'lista'" x-cloak class="space-y-2">
                @foreach ($usuarios as $u)
                    <div class="flex items-center gap-3 rounded-2xl border bg-slate-900/60 p-3 {{ $u->isBanned() ? 'border-orange-500/30' : ($u->trashed() ? 'border-slate-800 opacity-70' : 'border-slate-800') }}">
                        <input type="checkbox" @change="alternar({{ $u->id }})" :checked="marcadas.includes({{ $u->id }})"
                            class="rounded border-slate-600 bg-slate-950 text-rose-500 focus:ring-rose-500">
                        <x-user-avatar :user="$u" size="sm" />
                        <a href="{{ route('admin.users.show', $u->id) }}" class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-1.5">
                                <span class="truncate text-sm font-black text-white">{{ $u->name }}</span>
                                @include('admin.users.partials.badges', ['u' => $u])
                            </span>
                            <span class="block truncate text-[11px] text-slate-500">&#64;{{ $u->username }} · {{ $u->email }}</span>
                        </a>
                        <span class="hidden text-right text-[11px] text-slate-500 md:block">
                            {{ $u->entities_count + $u->collections_count + $u->tournament_templates_count + $u->phase_templates_count + $u->universes_count }} piezas<br>
                            {{ $u->last_login_at ? 'Entró ' . $u->last_login_at->diffForHumans() : 'Nunca entró' }}
                        </span>
                    </div>
                @endforeach
            </div>


            {{-- ===================================================== --}}
            {{-- TABLA --}}
            {{-- ===================================================== --}}

            <div x-show="vista === 'tabla'" x-cloak class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/60">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="border-b border-slate-800 text-[10px] font-black uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="w-10 px-3 py-3"></th>
                            <th class="px-3 py-3">Cuenta</th>
                            <th class="px-3 py-3">Estado</th>
                            <th class="px-3 py-3 text-right">Entidades</th>
                            <th class="px-3 py-3 text-right">Colecciones</th>
                            <th class="px-3 py-3 text-right">Torneos</th>
                            <th class="px-3 py-3 text-right">Fases</th>
                            <th class="px-3 py-3 text-right">Universos</th>
                            <th class="px-3 py-3">Alta</th>
                            <th class="px-3 py-3">Última entrada</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/70">
                        @foreach ($usuarios as $u)
                            <tr class="hover:bg-slate-900">
                                <td class="px-3 py-2.5">
                                    <input type="checkbox" @change="alternar({{ $u->id }})" :checked="marcadas.includes({{ $u->id }})"
                                        class="rounded border-slate-600 bg-slate-950 text-rose-500 focus:ring-rose-500">
                                </td>
                                <td class="px-3 py-2.5">
                                    <a href="{{ route('admin.users.show', $u->id) }}" class="flex items-center gap-2.5">
                                        <x-user-avatar :user="$u" size="xs" />
                                        <span>
                                            <span class="block font-bold text-slate-100">{{ $u->name }}</span>
                                            <span class="block text-[11px] text-slate-500">{{ $u->email }}</span>
                                        </span>
                                    </a>
                                </td>
                                <td class="px-3 py-2.5"><div class="flex flex-wrap gap-1">@include('admin.users.partials.badges', ['u' => $u])</div></td>
                                <td class="px-3 py-2.5 text-right font-bold text-slate-300">{{ $u->entities_count }}</td>
                                <td class="px-3 py-2.5 text-right font-bold text-slate-300">{{ $u->collections_count }}</td>
                                <td class="px-3 py-2.5 text-right font-bold text-slate-300">{{ $u->tournament_templates_count }}</td>
                                <td class="px-3 py-2.5 text-right font-bold text-slate-300">{{ $u->phase_templates_count }}</td>
                                <td class="px-3 py-2.5 text-right font-bold text-slate-300">{{ $u->universes_count }}</td>
                                <td class="px-3 py-2.5 text-xs text-slate-400">{{ $u->created_at?->format('d/m/Y') }}</td>
                                <td class="px-3 py-2.5 text-xs text-slate-400">{{ $u->last_login_at?->diffForHumans() ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div>{{ $usuarios->links() }}</div>
        @endif
    </div>

</x-admin-layout>
