@php
    $yo = auth()->user();
@endphp

<header class="sticky top-0 z-30 border-b border-slate-800 bg-slate-950/90 backdrop-blur">

    <div class="flex h-20 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">

        <div class="flex min-w-0 items-center gap-4">

            <button type="button" @click="sidebarOpen = true" aria-label="Abrir el menú"
                class="rounded-xl border border-slate-800 p-2 text-slate-400 transition hover:bg-slate-800 lg:hidden">
                <x-omni-icon name="menu" size="h-5 w-5" />
            </button>

            <div class="min-w-0">
                <p class="text-xs font-black uppercase tracking-wider text-rose-400">{{ $sitio->name() }} · Administración</p>

                <h1 class="truncate text-lg font-bold text-slate-100">
                    {{ $header ?? 'Administración' }}
                </h1>
            </div>
        </div>


        <div class="flex shrink-0 items-center gap-3">

            {{-- Lo que ve todo el mundo ahora mismo, a la vista del admin --}}
            @if ($sitio->inMaintenance())
                <a href="{{ route('admin.settings.edit') }}#mantenimiento"
                    class="hidden items-center gap-1.5 rounded-full border border-amber-500/40 bg-amber-500/10 px-3 py-1 text-[11px] font-black text-amber-300 sm:inline-flex">
                    <x-omni-icon name="llave" size="h-3.5 w-3.5" /> Sitio en mantenimiento
                </a>
            @endif

            @unless ($sitio->get('registration_open'))
                <span class="hidden items-center gap-1.5 rounded-full border border-slate-700 bg-slate-900 px-3 py-1 text-[11px] font-black text-slate-400 md:inline-flex">
                    <x-omni-icon name="candado" size="h-3.5 w-3.5" /> Registro cerrado
                </span>
            @endunless

            <div x-data="{ abierto: false }" class="relative">

                <button type="button" @click="abierto = ! abierto"
                    class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 transition hover:border-rose-500/40 hover:bg-slate-800">

                    <x-user-avatar :user="$yo" size="sm" />

                    <span class="hidden text-left sm:block">
                        <span class="block text-sm font-semibold text-slate-200">{{ $yo->name }}</span>
                        <span class="block text-xs font-bold text-rose-400">Administrador</span>
                    </span>

                    <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" class="rotate-90 text-slate-500" />
                </button>

                <div x-show="abierto" x-transition x-cloak @click.outside="abierto = false"
                    class="absolute right-0 mt-2 w-60 rounded-2xl border border-slate-800 bg-slate-900 p-2 shadow-xl">

                    @foreach ([[route('profiles.show', $yo->username), 'chispa', 'Mi perfil público'], [route('profile.edit'), 'engranaje', 'Ajustes de la cuenta'], [route('hub'), 'casa', 'Centro OmniMerge']] as [$destino, $icono, $texto])
                        <a href="{{ $destino }}"
                            class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm text-slate-300 transition hover:bg-slate-800 hover:text-white">
                            <x-omni-icon :name="$icono" size="h-4 w-4" />
                            {{ $texto }}
                        </a>
                    @endforeach

                    <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-slate-800 pt-1">
                        @csrf
                        <button type="submit"
                            class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm text-rose-300 transition hover:bg-rose-500/10">
                            <x-omni-icon name="flecha-izquierda" size="h-4 w-4" />
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
