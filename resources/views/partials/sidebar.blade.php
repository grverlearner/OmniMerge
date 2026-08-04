{{-- Fondo móvil --}}
<div
    x-show="sidebarOpen"
    x-transition.opacity
    class="fixed inset-0 z-40 bg-slate-950/60 lg:hidden"
    @click="sidebarOpen = false"
></div>

<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-slate-950 text-slate-100 transition-transform duration-300 lg:translate-x-0"
>
    {{-- Marca --}}
    <div class="flex h-20 items-center border-b border-slate-800 px-6">
        <a
            href="{{ route('dashboard') }}"
            class="flex items-center gap-3"
        >
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-500 font-black text-white shadow-lg shadow-indigo-500/30">
                OM
            </div>

            <div>
                <p class="text-lg font-bold tracking-wide">
                    OmniMerge
                </p>

                <p class="text-xs text-slate-400">
                    Crea cualquier universo
                </p>
            </div>
        </a>
    </div>

    {{-- Navegación --}}
    <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-6">
        <div>
            <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                Principal
            </p>

            <div class="space-y-1">
                <a
                    href="{{ route('dashboard') }}"
                    class="{{ request()->routeIs('dashboard')
                        ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-950/40'
                        : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}
                        flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition"
                >
                    <span class="text-lg">▦</span>
                    Dashboard
                </a>
            </div>
        </div>

        <div>
            <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                Creación
            </p>

            <div class="space-y-1">
                <a
                    href="{{ route('entity-types.index') }}"
                    class="{{ request()->routeIs('entity-types.*')
                        ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-950/40'
                        : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}
                        flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition"
                >
                    <span class="text-lg">◇</span>
                    Tipos de entidad
                </a>

                <a
                    href="{{ route('entities.index') }}"
                    class="{{ request()->routeIs('entities.*')
                        ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-950/40'
                        : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}
                        flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition"
                >
                    <span class="text-lg">✦</span>
                    Entidades
                </a>

                <span class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-600">
                    <span class="text-lg">☷</span>
                    Atributos
                    <span class="ml-auto rounded-full bg-slate-800 px-2 py-0.5 text-[10px]">
                        Próximo
                    </span>
                </span>

                <span class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-600">
                    <span class="text-lg">▤</span>
                    Colecciones
                </span>
            </div>
        </div>

        <div>
            <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                Simulación
            </p>

            <div class="space-y-1">
                <span class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-600">
                    <span class="text-lg">◎</span>
                    Universos
                    <span class="ml-auto rounded-full bg-slate-800 px-2 py-0.5 text-[10px]">
                        Futuro
                    </span>
                </span>

                <span class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-600">
                    <span class="text-lg">♜</span>
                    Torneos
                </span>

                <span class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-600">
                    <span class="text-lg">↗</span>
                    Rankings
                </span>
            </div>
        </div>
    </nav>

    {{-- Usuario --}}
    <div class="border-t border-slate-800 p-4">
        <a
            href="{{ route('profile.edit') }}"
            class="flex items-center gap-3 rounded-xl p-3 transition hover:bg-slate-900"
        >
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 font-bold text-indigo-700">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>

            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-white">
                    {{ auth()->user()->name }}
                </p>

                <p class="truncate text-xs text-slate-400">
                    {{ '@'.auth()->user()->username }}
                </p>
            </div>
        </a>
    </div>
</aside>