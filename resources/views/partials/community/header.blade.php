@php
    /* Acompaña a la superficie de la página: ver App\View\Components\CommunityLayout */
    $dark = $dark ?? false;
    $yo = auth()->user();
@endphp

<header class="sticky top-0 z-30 border-b backdrop-blur {{ $dark ? 'border-slate-800 bg-slate-950/90' : 'border-slate-200 bg-white/90' }}">

    <div class="flex h-20 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">

        <div class="flex min-w-0 items-center gap-4">

            <button type="button" @click="sidebarOpen = true" aria-label="Abrir el menú"
                class="rounded-xl border p-2 transition lg:hidden {{ $dark ? 'border-slate-800 text-slate-400 hover:bg-slate-800' : 'border-slate-200 text-slate-600 hover:bg-slate-100' }}">
                <x-omni-icon name="menu" size="h-5 w-5" />
            </button>

            <div class="min-w-0">
                <p class="text-xs font-black uppercase tracking-wider text-emerald-500">OmniMerge · Comunidad</p>

                <h1 class="truncate text-lg font-bold {{ $dark ? 'text-slate-100' : 'text-slate-900' }}">
                    {{ $header ?? 'Comunidad' }}
                </h1>
            </div>
        </div>


        <div x-data="{ abierto: false }" class="relative shrink-0">

            <button type="button" @click="abierto = ! abierto"
                class="flex items-center gap-3 rounded-xl border px-3 py-2 transition {{ $dark ? 'border-slate-800 bg-slate-900 hover:border-emerald-500/40 hover:bg-slate-800' : 'border-slate-200 bg-white hover:border-emerald-300 hover:bg-emerald-50' }}">

                <x-user-avatar :user="$yo" size="sm" />

                <span class="hidden text-left sm:block">
                    <span class="block text-sm font-semibold {{ $dark ? 'text-slate-200' : 'text-slate-800' }}">{{ $yo->name }}</span>
                    <span class="block text-xs text-slate-500">&#64;{{ $yo->username }}</span>
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
</header>
