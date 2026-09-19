<x-admin-layout :title="$cuenta->name">

    <x-slot:header>{{ $cuenta->name }}</x-slot:header>

    @php
        $esYo = $cuenta->is(auth()->user());
        $totalPiezas = $contenido->sum(fn ($g) => $g['items']->count());
    @endphp

    <div class="space-y-5">

        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 hover:text-white">
            <x-omni-icon name="chevron-izquierda" size="h-3.5 w-3.5" /> Todas las cuentas
        </a>

        {{-- ========================================================= --}}
        {{-- LA CUENTA --}}
        {{-- ========================================================= --}}

        <section class="overflow-hidden rounded-2xl border bg-slate-900/60 {{ $cuenta->isBanned() ? 'border-orange-500/40' : ($cuenta->isAdmin() ? 'border-violet-500/40' : 'border-slate-800') }}">
            <div class="h-1.5 {{ $cuenta->trashed() ? 'bg-slate-600' : ($cuenta->isBanned() ? 'bg-orange-500' : ($cuenta->isAdmin() ? 'bg-violet-500' : 'bg-rose-500/60')) }}"></div>

            <div class="flex flex-wrap items-start gap-5 p-5">
                <x-user-avatar :user="$cuenta" size="xl" />

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-2xl font-black text-white">{{ $cuenta->name }}</h2>
                        @include('admin.users.partials.badges', ['u' => $cuenta])
                        @if ($esYo)
                            <span class="rounded-full bg-rose-500/15 px-2 py-0.5 text-[10px] font-black text-rose-300">Eres tú</span>
                        @endif
                    </div>
                    <p class="mt-0.5 text-sm text-slate-400">&#64;{{ $cuenta->username }} · {{ $cuenta->email }}</p>

                    @if ($cuenta->headline)
                        <p class="mt-2 text-sm text-slate-300">{{ $cuenta->headline }}</p>
                    @endif

                    <dl class="mt-4 grid grid-cols-2 gap-2 text-xs sm:grid-cols-4">
                        @foreach ([
                            ['Alta', $cuenta->created_at?->translatedFormat('j M Y')],
                            ['Última entrada', $cuenta->last_login_at?->diffForHumans() ?? 'Nunca'],
                            ['Perfil', $cuenta->isPublicProfile() ? 'Público' : 'Privado'],
                            ['Correo', $cuenta->email_verified_at ? 'Verificado' : 'Sin verificar'],
                        ] as [$titulo, $valor])
                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2">
                                <dt class="text-[10px] font-black uppercase tracking-wider text-slate-500">{{ $titulo }}</dt>
                                <dd class="font-bold text-slate-200">{{ $valor }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @if ($cuenta->isBanned())
                        <div class="mt-4 rounded-xl border border-orange-500/40 bg-orange-500/10 p-3 text-sm text-orange-200">
                            <p class="font-black">
                                Bloqueada {{ $cuenta->banned_until ? 'hasta el ' . $cuenta->banned_until->translatedFormat('j \d\e F \d\e Y, H:i') : 'sin fecha de fin' }}
                            </p>
                            <p class="mt-0.5 text-orange-200/80">Motivo: {{ $cuenta->ban_reason ?: 'sin indicar' }}
                                @if ($cuenta->bannedByUser) · lo decidió {{ $cuenta->bannedByUser->name }} @endif
                                · {{ $cuenta->banned_at->diffForHumans() }}</p>
                        </div>
                    @endif
                </div>

                <div class="flex flex-col gap-2">
                    @unless ($cuenta->trashed())
                        <a href="{{ route('profiles.show', $cuenta->username) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-700 px-3 py-2 text-xs font-black text-slate-300 hover:border-emerald-500/50 hover:text-white">
                            <x-omni-icon name="ojo" size="h-4 w-4" /> Ver su perfil público
                        </a>
                    @endunless
                </div>
            </div>
        </section>


        <div class="grid gap-5 xl:grid-cols-3">

            {{-- ===================================================== --}}
            {{-- ACCIONES DE ADMIN --}}
            {{-- ===================================================== --}}

            <section class="space-y-4 xl:order-2">

                @if ($cuenta->trashed())
                    <div class="rounded-2xl border border-slate-700 bg-slate-900/60 p-4">
                        <h3 class="text-sm font-black text-white">Cuenta eliminada</h3>
                        <p class="mt-1 text-xs text-slate-400">Se eliminó {{ $cuenta->deleted_at->diffForHumans() }}. Nadie puede entrar con ella, pero se puede recuperar tal como estaba.</p>
                        <form method="POST" action="{{ route('admin.users.restore', $cuenta->id) }}" class="mt-3">
                            @csrf
                            <button class="w-full rounded-xl bg-emerald-500 px-3 py-2 text-xs font-black text-white hover:bg-emerald-400">Recuperar la cuenta</button>
                        </form>
                    </div>
                @else

                    {{-- Insignia de creador --}}
                    <div class="rounded-2xl border border-sky-500/25 bg-slate-900/60 p-4">
                        <h3 class="flex items-center gap-2 text-sm font-black text-white"><x-omni-icon name="escudo-check" size="h-4 w-4" class="text-sky-300" /> Insignia de creador</h3>
                        <p class="mt-1 text-xs text-slate-400">Solo es una etiqueta: aparece junto a su nombre en la comunidad y en su perfil. No le da ningún permiso.</p>

                        <form method="POST" action="{{ route('admin.users.badge', $cuenta->id) }}" class="mt-3 space-y-2" x-data="{ badge: @js($cuenta->creator_badge ?? '') }">
                            @csrf
                            <div class="grid grid-cols-3 gap-1.5">
                                @foreach (['' => ['Ninguna', '#94a3b8', 'cerrar']] + collect(\App\Models\User::CREATOR_BADGES)->map(fn ($b) => [$b['short'], $b['tone'], $b['icon']])->all() as $clave => [$texto, $color, $icono])
                                    <label class="cursor-pointer">
                                        <input type="radio" name="badge" value="{{ $clave }}" x-model="badge" class="peer sr-only">
                                        <span class="flex flex-col items-center gap-1 rounded-xl border border-slate-800 bg-slate-950/50 px-2 py-2 text-[11px] font-black text-slate-400 transition peer-checked:text-white"
                                            :style="badge === '{{ $clave }}' ? 'border-color: {{ $color }}; background-color: {{ $color }}1f; color: {{ $color }}' : ''">
                                            <x-omni-icon :name="$icono" size="h-4 w-4" /> {{ $texto }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <input type="text" name="note" value="{{ $cuenta->creator_badge_note }}" maxlength="200" x-show="badge !== ''"
                                placeholder="Por qué (opcional, se ve al pasar el ratón)"
                                class="w-full rounded-xl border-slate-700 bg-slate-950 text-xs text-slate-100 placeholder:text-slate-600">
                            <button class="w-full rounded-xl bg-sky-500 px-3 py-2 text-xs font-black text-white hover:bg-sky-400">Guardar insignia</button>
                        </form>
                    </div>

                    {{-- Rol --}}
                    <div class="rounded-2xl border border-violet-500/25 bg-slate-900/60 p-4">
                        <h3 class="flex items-center gap-2 text-sm font-black text-white"><x-omni-icon name="escudo" size="h-4 w-4" class="text-violet-300" /> Rol</h3>
                        <p class="mt-1 text-xs text-slate-400">
                            {{ $cuenta->isAdmin() ? 'Es administrador: puede ver y cambiar todo el sitio, esta pantalla incluida.' : 'Es un usuario normal: solo ve y cambia lo suyo.' }}
                        </p>

                        @if ($esYo)
                            <p class="mt-3 rounded-xl border border-slate-800 bg-slate-950/50 p-2.5 text-[11px] text-slate-500">No puedes cambiarte el rol a ti mismo: así el sitio nunca se queda sin admin.</p>
                        @else
                            <form method="POST" action="{{ route('admin.users.role', $cuenta->id) }}" class="mt-3"
                                data-omni-confirm data-confirm-variant="warning"
                                data-confirm-title="{{ $cuenta->isAdmin() ? 'Quitar el rol de administrador' : 'Hacer administrador' }}"
                                data-confirm-message="{{ $cuenta->isAdmin() ? $cuenta->name . ' dejará de ver esta zona y solo podrá tocar lo suyo.' : $cuenta->name . ' podrá ver y cambiar todo: cuentas, contenido y configuración.' }}"
                                data-confirm-action="{{ $cuenta->isAdmin() ? 'Quitar el rol' : 'Sí, hacerlo admin' }}">
                                @csrf
                                <input type="hidden" name="role" value="{{ $cuenta->isAdmin() ? 'USER' : 'ADMIN' }}">
                                <button class="w-full rounded-xl border border-violet-500/50 px-3 py-2 text-xs font-black text-violet-200 hover:bg-violet-500/15">
                                    {{ $cuenta->isAdmin() ? 'Quitarle el rol de admin' : 'Hacerlo administrador' }}
                                </button>
                            </form>
                        @endif
                    </div>

                    {{-- Bloqueo --}}
                    <div id="bloqueo" class="rounded-2xl border border-orange-500/25 bg-slate-900/60 p-4">
                        <h3 class="flex items-center gap-2 text-sm font-black text-white"><x-omni-icon name="prohibido" size="h-4 w-4" class="text-orange-300" /> Bloqueo</h3>

                        @if ($cuenta->isBanned())
                            <p class="mt-1 text-xs text-slate-400">Ahora mismo no puede entrar. Al desbloquearla podrá volver con su contraseña de siempre.</p>
                            <form method="POST" action="{{ route('admin.users.unban', $cuenta->id) }}" class="mt-3">
                                @csrf
                                <button class="w-full rounded-xl bg-emerald-500 px-3 py-2 text-xs font-black text-white hover:bg-emerald-400">Desbloquear ya</button>
                            </form>
                        @elseif ($esYo || $cuenta->isAdmin())
                            <p class="mt-1 text-xs text-slate-400">
                                {{ $esYo ? 'No puedes bloquearte a ti mismo.' : 'Es administrador. Para bloquearlo, primero quítale el rol.' }}
                            </p>
                        @else
                            <p class="mt-1 text-xs text-slate-400">Sale de inmediato de todas sus sesiones y, al intentar entrar, verá el motivo y hasta cuándo. Su contenido no se toca.</p>

                            <form method="POST" action="{{ route('admin.users.ban', $cuenta->id) }}" class="mt-3 space-y-2" x-data="{ duracion: '7' }"
                                data-omni-confirm data-confirm-title="Bloquear a {{ $cuenta->name }}"
                                data-confirm-message="Se cerrarán sus sesiones y no podrá entrar hasta que termine el bloqueo o lo quites." data-confirm-action="Bloquear">
                                @csrf
                                <div class="grid grid-cols-5 gap-1">
                                    @foreach (['1' => '1 día', '7' => '7 días', '30' => '30 días', 'custom' => 'Fecha', 'forever' => 'Siempre'] as $valor => $texto)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="duracion" value="{{ $valor }}" x-model="duracion" class="peer sr-only">
                                            <span class="block rounded-lg border border-slate-800 bg-slate-950/50 px-1 py-1.5 text-center text-[10px] font-black text-slate-400 peer-checked:border-orange-500 peer-checked:bg-orange-500/15 peer-checked:text-orange-200">{{ $texto }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <input type="datetime-local" name="hasta" x-show="duracion === 'custom'" x-cloak :required="duracion === 'custom'"
                                    min="{{ now()->addHour()->format('Y-m-d\TH:i') }}"
                                    class="w-full rounded-xl border-slate-700 bg-slate-950 text-xs text-slate-100">
                                <textarea name="motivo" rows="2" required maxlength="500" placeholder="Motivo (lo verá al intentar entrar)"
                                    class="w-full rounded-xl border-slate-700 bg-slate-950 text-xs text-slate-100 placeholder:text-slate-600">{{ old('motivo') }}</textarea>
                                <button class="w-full rounded-xl bg-orange-500 px-3 py-2 text-xs font-black text-white hover:bg-orange-400">Bloquear la cuenta</button>
                            </form>
                        @endif
                    </div>

                    {{-- Sesiones y borrado --}}
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                        <h3 class="flex items-center gap-2 text-sm font-black text-white"><x-omni-icon name="puerta" size="h-4 w-4" class="text-amber-300" /> Sesiones abiertas · {{ $sesiones->count() }}</h3>

                        <div class="mt-2 space-y-1.5">
                            @forelse ($sesiones as $sesion)
                                <div class="rounded-lg border border-slate-800 bg-slate-950/50 px-2.5 py-1.5 text-[11px]">
                                    <p class="font-bold text-slate-300">{{ $sesion->ip_address ?? 'IP desconocida' }} · {{ \Illuminate\Support\Carbon::createFromTimestamp($sesion->last_activity)->diffForHumans() }}</p>
                                    <p class="truncate text-slate-500" title="{{ $sesion->user_agent }}">{{ \Illuminate\Support\Str::limit($sesion->user_agent, 60) }}</p>
                                </div>
                            @empty
                                <p class="text-xs text-slate-500">No tiene ninguna sesión abierta ahora.</p>
                            @endforelse
                        </div>

                        @if (! $esYo && $sesiones->isNotEmpty())
                            <form method="POST" action="{{ route('admin.users.sessions', $cuenta->id) }}" class="mt-3">
                                @csrf
                                <button class="w-full rounded-xl border border-amber-500/40 px-3 py-2 text-xs font-black text-amber-200 hover:bg-amber-500/10">Cerrar todas sus sesiones</button>
                            </form>
                        @endif

                        @unless ($esYo || $cuenta->isAdmin())
                            <form method="POST" action="{{ route('admin.users.destroy', $cuenta->id) }}" class="mt-3 border-t border-slate-800 pt-3"
                                data-omni-confirm data-confirm-title="Eliminar la cuenta de {{ $cuenta->name }}"
                                data-confirm-message="No podrá entrar y desaparecerá de la comunidad. Su contenido se queda como está y la cuenta se puede recuperar desde «Eliminadas»."
                                data-confirm-action="Eliminar la cuenta">
                                @csrf
                                @method('DELETE')
                                <button class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-rose-500/40 px-3 py-2 text-xs font-black text-rose-300 hover:bg-rose-500/10">
                                    <x-omni-icon name="papelera" size="h-3.5 w-3.5" /> Eliminar la cuenta
                                </button>
                            </form>
                        @endunless
                    </div>
                @endif
            </section>


            {{-- ===================================================== --}}
            {{-- LO QUE HA CREADO --}}
            {{-- ===================================================== --}}

            <section class="space-y-4 xl:col-span-2 xl:order-1" x-data="{ tipo: 'todo', buscar: '' }">

                <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-800 bg-slate-900/60 p-3">
                    <h3 class="mr-2 text-sm font-black text-white">Lo que ha creado · {{ $totalPiezas }}</h3>

                    <button type="button" @click="tipo = 'todo'" :class="tipo === 'todo' ? 'border-rose-500/60 bg-rose-500/15 text-rose-200' : 'border-slate-800 text-slate-400'"
                        class="rounded-lg border px-2.5 py-1 text-[11px] font-black">Todo</button>
                    @foreach ($contenido as $clave => $grupo)
                        <button type="button" @click="tipo = '{{ $clave }}'" :class="tipo === '{{ $clave }}' ? 'text-white' : 'border-slate-800 text-slate-400'"
                            :style="tipo === '{{ $clave }}' ? 'border-color: {{ $grupo['tone'] }}; background-color: {{ $grupo['tone'] }}22' : ''"
                            class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-black">
                            <x-omni-icon :name="$grupo['icon']" size="h-3.5 w-3.5" /> {{ $grupo['label'] }} · {{ $grupo['items']->count() }}
                        </button>
                    @endforeach

                    <input type="search" x-model="buscar" placeholder="Buscar por nombre"
                        class="ml-auto w-44 rounded-lg border-slate-700 bg-slate-950 py-1 text-xs text-slate-100 placeholder:text-slate-600">
                </div>

                @forelse ($contenido as $clave => $grupo)
                    <div x-show="tipo === 'todo' || tipo === '{{ $clave }}'" class="rounded-2xl border bg-slate-900/60 p-4" style="border-color: {{ $grupo['tone'] }}33;">
                        <h4 class="flex items-center gap-2 text-xs font-black uppercase tracking-wider" style="color: {{ $grupo['tone'] }}">
                            <x-omni-icon :name="$grupo['icon']" size="h-4 w-4" /> {{ $grupo['label'] }}
                        </h4>

                        <div class="mt-3 grid gap-2 sm:grid-cols-2 2xl:grid-cols-3">
                            @foreach ($grupo['items'] as $item)
                                <a href="{{ route('admin.content.show', [$clave, $item->id]) }}"
                                    x-show="! buscar || @js(\Illuminate\Support\Str::lower($item->name)).includes(buscar.toLowerCase())"
                                    class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-950/40 p-2 transition hover:border-slate-700 {{ $item->trashed() ? 'opacity-60' : '' }}">
                                    @include('admin.partials.thumb', ['modelo' => $item, 'meta' => $grupo, 'clase' => 'h-11 w-11 rounded-lg', 'icono' => 'h-4 w-4'])
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-bold text-slate-200">{{ $item->name }}</span>
                                        <span class="flex flex-wrap items-center gap-1">
                                            @if ($item->trashed())
                                                <span class="text-[10px] font-black text-slate-500">Eliminado</span>
                                            @endif
                                            @if (isset($item->visibility))
                                                <span class="text-[10px] font-bold text-slate-500">{{ \App\Services\Admin\ContentRegistry::VISIBILITIES[$item->visibility] ?? $item->visibility }}</span>
                                            @endif
                                            <x-content-badges :type="$clave" :id="$item->id" :flags="$grupo['flags'][$item->id] ?? []" :all="true" size="xs" />
                                        </span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-800 p-10 text-center text-sm text-slate-500">Esta cuenta todavía no ha creado nada.</div>
                @endforelse


                {{-- Lo que se ha hecho con esta cuenta --}}
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                    <h3 class="text-sm font-black text-white">Historial de administración</h3>
                    <p class="text-xs text-slate-500">
                        Lo que los admins han hecho con esta cuenta.
                        @if ($hechasPorEl) Ella misma ha hecho {{ $hechasPorEl }} acciones como admin. @endif
                        En la comunidad: {{ $interacciones['VIEW'] ?? 0 }} visitas y {{ $interacciones['CLONE'] ?? 0 }} clonaciones.
                    </p>
                    <div class="mt-3 space-y-2">
                        @forelse ($historial as $accion)
                            @include('admin.partials.action-row', ['accion' => $accion])
                        @empty
                            <p class="text-xs text-slate-500">Ningún admin ha tocado esta cuenta todavía.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>

</x-admin-layout>
