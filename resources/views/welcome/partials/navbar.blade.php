@php
    /*
     * La barra. Los enlaces apuntan a las secciones que existen de verdad;
     * antes habia uno llamado «Futuro» que llevaba a una lista de promesas.
     */

    $enlaces = [
        '#modulos' => 'Qué es',
        '#como-funciona' => 'Cómo funciona',
        '#enfrentamiento' => 'Cómo se gana',
        '#comunidad' => 'Comunidad',
    ];
@endphp

<header class="sticky top-0 z-50 border-b border-white/10 bg-slate-950/80 backdrop-blur-xl">

    <div class="mx-auto flex max-w-7xl items-center gap-4 px-5 py-3 lg:px-8">

        {{-- Marca --}}
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">

            <span class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600">
                <img src="{{ $sitio->logoUrl() ?? $sitio->faviconUrl() }}" alt="" class="h-full w-full object-cover">
            </span>

            <span class="leading-none">
                <span class="block text-[15px] font-black tracking-tight text-white">{{ $sitio->name() }}</span>
                <span class="omni-accent-text block text-[8px] font-black uppercase tracking-[0.2em]">
                    {{ $sitio->get('tagline') }}
                </span>
            </span>
        </a>


        <nav class="ml-6 hidden items-center gap-1 lg:flex">
            @foreach ($enlaces as $ancla => $texto)
                <a href="{{ $ancla }}"
                    class="rounded-xl px-3 py-2 text-[13px] font-semibold text-slate-400 transition hover:bg-white/5 hover:text-white">
                    {{ $texto }}
                </a>
            @endforeach
        </nav>


        <span class="flex-1"></span>


        <div class="hidden items-center gap-2 sm:flex">
            @auth
                <a href="{{ route('hub') }}"
                    class="flex items-center gap-1.5 rounded-xl omni-accent-bg px-4 py-2 text-[13px] font-black text-white transition">
                    <x-omni-icon name="casa" size="h-4 w-4" />
                    Ir a {{ $sitio->name() }}
                </a>
            @else
                <a href="{{ route('login') }}"
                    class="rounded-xl px-3 py-2 text-[13px] font-semibold text-slate-300 transition hover:text-white">
                    Iniciar sesión
                </a>

                <a href="{{ route('register') }}"
                    class="rounded-xl bg-white px-4 py-2 text-[13px] font-black text-slate-950 transition hover:bg-slate-200">
                    Crear cuenta
                </a>
            @endauth
        </div>


        <button type="button" @click="menuMovil = ! menuMovil"
            class="rounded-xl border border-white/10 p-2 text-slate-300 transition hover:bg-white/5 lg:hidden"
            aria-label="Menú">
            <x-omni-icon x-show="! menuMovil" name="menu" size="h-5 w-5" />
            <x-omni-icon x-show="menuMovil" x-cloak name="cerrar" size="h-5 w-5" />
        </button>
    </div>


    {{-- Menú móvil --}}
    <div x-show="menuMovil" x-cloak x-collapse class="border-t border-white/10 bg-slate-950 lg:hidden">

        <div class="space-y-1 px-5 py-4">

            @foreach ($enlaces as $ancla => $texto)
                <a href="{{ $ancla }}" @click="menuMovil = false"
                    class="block rounded-xl px-4 py-3 text-sm font-semibold text-slate-300 transition hover:bg-white/5">
                    {{ $texto }}
                </a>
            @endforeach

            <div class="pt-2">
                @auth
                    <a href="{{ route('hub') }}"
                        class="block rounded-xl omni-accent-bg px-4 py-3 text-center text-sm font-black text-white">
                        Ir a {{ $sitio->name() }}
                    </a>
                @else
                    <a href="{{ route('register') }}"
                        class="block rounded-xl bg-white px-4 py-3 text-center text-sm font-black text-slate-950">
                        Crear cuenta
                    </a>

                    <a href="{{ route('login') }}"
                        class="mt-1.5 block rounded-xl px-4 py-3 text-center text-sm font-semibold text-slate-300">
                        Ya tengo una cuenta
                    </a>
                @endauth
            </div>
        </div>
    </div>
</header>
