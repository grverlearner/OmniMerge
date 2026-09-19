<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-site-head />

    <title>@yield('title', 'Centro') | {{ $sitio->name() }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

{{--
    El Centro de OmniMerge.

    Es la única pantalla que no pertenece a ningún módulo, y por eso no lleva
    sidebar: lleva una barra con los cuatro módulos a la vista, cada uno con
    su color de siempre —índigo la Biblioteca, violeta Universos, ámbar
    Torneos, esmeralda Comunidad—, para que desde aquí se vea de un golpe
    que esto es la puerta a todos y no un módulo más.
--}}

@php
    $yo = auth()->user();

    $modulos = [
        ['Biblioteca', 'libro', route('dashboard'), '#818cf8'],
        ['Universos', 'orbita', route('universes.dashboard'), '#a78bfa'],
        ['Torneos', 'trofeo', route('tournaments.dashboard'), '#fbbf24'],
        ['Comunidad', 'globo', route('community.home'), '#34d399'],
    ];

    if ($yo?->isAdmin()) {
        $modulos[] = ['Administración', 'escudo', route('admin.dashboard'), '#fb7185'];
    }

    $marca = $sitio->logoUrl() ?? $sitio->faviconUrl();
@endphp

<body class="min-h-screen bg-slate-950 text-slate-100 antialiased selection:bg-indigo-500 selection:text-white">

    <x-site-announcement />

    <div x-data="{ menu: false, cuenta: false }" class="relative min-h-screen">

        {{-- Luz de fondo: los colores de los módulos, muy tenues --}}
        <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
            <div class="absolute -left-40 -top-40 h-[520px] w-[520px] rounded-full bg-indigo-600/10 blur-[140px]"></div>
            <div class="absolute -right-40 top-40 h-[520px] w-[520px] rounded-full bg-violet-600/10 blur-[140px]"></div>
            <div class="absolute bottom-0 left-1/3 h-[420px] w-[420px] rounded-full bg-amber-500/5 blur-[140px]"></div>
        </div>


        {{-- ========================================================= --}}
        {{-- BARRA SUPERIOR --}}
        {{-- ========================================================= --}}

        <header class="sticky top-0 z-40 border-b border-white/10 bg-slate-950/85 backdrop-blur-xl">
            <div class="mx-auto flex h-16 max-w-[1500px] items-center gap-3 px-4 sm:px-6">

                <a href="{{ route('hub') }}" class="flex shrink-0 items-center gap-2.5">
                    <span class="h-9 w-9 overflow-hidden rounded-xl border border-white/10 bg-slate-900">
                        <img src="{{ $marca }}" alt="" class="h-full w-full object-cover">
                    </span>
                    <span class="hidden leading-none sm:block">
                        <span class="block text-[15px] font-black tracking-tight text-white">{{ $sitio->name() }}</span>
                        <span class="omni-accent-text block text-[9px] font-black uppercase tracking-[0.22em]">Centro</span>
                    </span>
                </a>

                <nav class="ml-4 hidden items-center gap-1 xl:flex">
                    @foreach ($modulos as [$nombre, $icono, $destino, $color])
                        <a href="{{ $destino }}"
                            class="group inline-flex items-center gap-2 rounded-xl px-3 py-2 text-[13px] font-bold text-slate-400 transition hover:bg-white/5 hover:text-white">
                            <span class="transition group-hover:scale-110" style="color: {{ $color }}">
                                <x-omni-icon :name="$icono" size="h-4 w-4" />
                            </span>
                            {{ $nombre }}
                        </a>
                    @endforeach
                </nav>

                <span class="flex-1"></span>

                {{-- Buscar en todo: ver HubController::search --}}
                <button type="button" @click="$dispatch('omni-buscar')"
                    class="hidden items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-[12px] font-semibold text-slate-400 transition hover:border-white/20 hover:text-white md:flex">
                    <x-omni-icon name="filtro" size="h-4 w-4" />
                    Buscar en todo
                    <kbd class="rounded border border-white/10 px-1.5 font-mono text-[10px] text-slate-500">/</kbd>
                </button>

                {{-- Cuenta --}}
                <div class="relative">
                    <button type="button" @click="cuenta = ! cuenta"
                        class="flex items-center gap-2.5 rounded-xl border border-white/10 bg-white/5 py-1.5 pl-1.5 pr-2.5 transition hover:bg-white/10">
                        <x-user-avatar :user="$yo" size="xs" square />
                        <span class="hidden max-w-[140px] truncate text-[13px] font-bold text-white sm:block">{{ $yo->name }}</span>
                        <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" class="rotate-90 text-slate-500" />
                    </button>

                    <div x-show="cuenta" x-cloak x-transition @click.outside="cuenta = false"
                        class="absolute right-0 mt-2 w-64 rounded-2xl border border-white/10 bg-slate-900 p-2 shadow-2xl shadow-black/50">
                        <div class="border-b border-white/10 px-3 pb-3 pt-2">
                            <p class="truncate text-sm font-black text-white">{{ $yo->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $yo->email }}</p>
                            <x-creator-badge :user="$yo" class="mt-1.5" />
                        </div>

                        @foreach ([[route('profiles.show', $yo->username), 'chispa', 'Mi perfil público'], [route('profile.edit'), 'engranaje', 'Ajustes de la cuenta'], [route('home'), 'casa', 'Portada pública']] as [$destino, $icono, $texto])
                            <a href="{{ $destino }}"
                                class="mt-1 flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm text-slate-300 transition hover:bg-white/5 hover:text-white">
                                <x-omni-icon :name="$icono" size="h-4 w-4" /> {{ $texto }}
                            </a>
                        @endforeach

                        <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-white/10 pt-1">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm text-rose-300 transition hover:bg-rose-500/10">
                                <x-omni-icon name="flecha-izquierda" size="h-4 w-4" /> Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>

                <button type="button" @click="menu = ! menu" aria-label="Abrir el menú"
                    class="rounded-xl border border-white/10 p-2 text-slate-300 xl:hidden">
                    <x-omni-icon name="menu" size="h-5 w-5" />
                </button>
            </div>

            {{-- Menú en pantallas pequeñas --}}
            <div x-show="menu" x-cloak x-transition class="border-t border-white/10 bg-slate-950 px-4 py-3 xl:hidden">
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach ($modulos as [$nombre, $icono, $destino, $color])
                        <a href="{{ $destino }}" class="flex items-center gap-2 rounded-xl border px-3 py-2.5 text-sm font-bold text-slate-200"
                            style="border-color: {{ $color }}40; background-color: {{ $color }}10;">
                            <span style="color: {{ $color }}"><x-omni-icon :name="$icono" size="h-4 w-4" /></span> {{ $nombre }}
                        </a>
                    @endforeach
                    <button type="button" @click="menu = false; $dispatch('omni-buscar')"
                        class="flex items-center gap-2 rounded-xl border border-white/10 px-3 py-2.5 text-sm font-bold text-slate-300">
                        <x-omni-icon name="filtro" size="h-4 w-4" /> Buscar en todo
                    </button>
                </div>
            </div>
        </header>


        <main>
            @yield('content')
        </main>


        <footer class="mt-10 border-t border-white/10">
            <div class="mx-auto flex max-w-[1500px] flex-col items-center justify-between gap-3 px-4 py-6 text-xs text-slate-500 sm:flex-row sm:px-6">
                <p class="flex items-center gap-2">
                    <img src="{{ $marca }}" alt="" class="h-5 w-5 rounded-md object-cover">
                    © {{ date('Y') }} {{ $sitio->name() }}
                    @if ($sitio->get('tagline'))
                        <span class="text-slate-700">·</span> {{ $sitio->get('tagline') }}
                    @endif
                </p>
                <nav class="flex flex-wrap items-center gap-4">
                    @foreach ($modulos as [$nombre, $icono, $destino, $color])
                        <a href="{{ $destino }}" class="hover:text-white">{{ $nombre }}</a>
                    @endforeach
                </nav>
            </div>
        </footer>
    </div>

    <x-omni-confirm-modal />

</body>

</html>
