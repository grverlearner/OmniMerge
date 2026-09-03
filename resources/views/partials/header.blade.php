@php
    /* Acompana a la superficie de la pagina: ver App\View\Components\AppLayout */
    $dark = $dark ?? false;
@endphp

<header class="sticky top-0 z-30 border-b backdrop-blur {{ $dark ? 'border-slate-800 bg-slate-950/90' : 'border-slate-200 bg-white/90' }}">
    <div class="flex h-20 items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4">
            <button type="button"
                class="rounded-xl border p-2 transition lg:hidden {{ $dark ? 'border-slate-800 text-slate-400 hover:bg-slate-800' : 'border-slate-200 text-slate-600 hover:bg-slate-100' }}"
                @click="sidebarOpen = true">
                <x-omni-icon name="menu" size="h-5 w-5" />
            </button>

            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-indigo-600">
                    OmniMerge
                </p>

                <h1 class="text-lg font-bold {{ $dark ? 'text-slate-100' : 'text-slate-900' }}">
                    {{ $header ?? 'Panel de control' }}
                </h1>
            </div>
        </div>

        <div x-data="{ open: false }" class="relative">
            <button type="button"
                class="flex items-center gap-3 rounded-xl border px-3 py-2 transition {{ $dark ? 'border-slate-800 bg-slate-900 hover:bg-slate-800' : 'border-slate-200 bg-white hover:bg-slate-50' }}"
                @click="open = !open">
                <x-user-avatar :user="auth()->user()" size="sm" />

                <div class="hidden text-left sm:block">
                    <p class="text-sm font-semibold {{ $dark ? 'text-slate-200' : 'text-slate-800' }}">
                        {{ auth()->user()->name }}
                    </p>

                    <p class="text-xs text-slate-500">
                        {{ auth()->user()->role }}
                    </p>
                </div>

                <span class="text-xs text-slate-400">▼</span>
            </button>

            <div x-show="open" x-transition @click.outside="open = false"
                class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl"
                style="display: none;">
                <a href="{{ route('hub') }}"
                    class="
                    block
                    rounded-xl
                    px-4
                    py-3
                    text-sm
                    text-slate-700
                    hover:bg-slate-100
                ">
                    Centro OmniMerge
                </a>
                <a href="{{ route('profile.edit') }}"
                    class="block rounded-xl px-4 py-3 text-sm text-slate-700 hover:bg-slate-100">
                    Mi perfil
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit"
                        class="block w-full rounded-xl px-4 py-3 text-left text-sm text-red-600 hover:bg-red-50">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
