@php
    /*
     * El perfil entero de una persona.
     *
     * Habia dos perfiles publicos -su biblioteca y sus plantillas de torneo- y
     * ninguno de los dos contestaba «quien es este». Quien llegaba desde una
     * entidad copiada no se enteraba de que ademas diseña torneos, y al reves.
     *
     * Esta pantalla es la persona. Los dos perfiles especializados se conservan
     * y se enlazan desde aqui, porque para bucear siguen siendo mejores.
     *
     * Ver docs/md/76-Perfil.md
     */
@endphp

<x-app-layout surface="dark">

    <x-slot name="header">
        {{ $esMio ? 'Tu perfil' : $creador->name }}
    </x-slot>

    <div class="space-y-3">

        @if (! $visible)

            {{-- ---------- PERFIL PRIVADO ---------- --}}

            <section class="rounded-2xl border border-slate-800 bg-slate-900/50 py-16 text-center">

                <span class="mx-auto flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl border-2 border-slate-800 bg-slate-950">
                    @if ($creador->avatar_url)
                        <img src="{{ $creador->avatar_url }}" alt="" class="h-full w-full object-cover">
                    @else
                        <span class="text-[18px] font-black text-slate-600">{{ $creador->initials }}</span>
                    @endif
                </span>

                <h1 class="mt-3 text-[17px] font-black text-white">{{ $creador->name }}</h1>

                <p class="mx-auto mt-1 max-w-sm text-[11px] leading-relaxed text-slate-500">
                    Este perfil es privado. Lo que esta persona haya creado no se muestra aquí
                    mientras no decida lo contrario.
                </p>

                <a href="{{ route('community.index') }}"
                    class="mt-4 inline-block rounded-xl border border-slate-800 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                    Volver a la comunidad
                </a>
            </section>

        @else

            @include('profiles.partials.cabecera')

            @if ($cifras['total'] === 0)

                {{-- ---------- NO HA PUBLICADO NADA ---------- --}}

                <section class="rounded-2xl border border-dashed border-slate-800 py-14 text-center">

                    <span class="inline-flex text-slate-700"><x-omni-icon name="libro" size="h-9 w-9" /></span>

                    <p class="mt-2 text-[13px] font-black text-white">
                        {{ $esMio ? 'Todavía no has publicado nada' : $creador->name . ' todavía no ha publicado nada' }}
                    </p>

                    <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                        @if ($esMio)
                            Tu perfil es visible, pero está vacío: lo que creas es privado hasta que
                            tú marcas cada cosa como pública. Aquí aparecerá lo que publiques.
                        @else
                            Su perfil es visible pero no ha hecho pública ninguna de sus creaciones
                            todavía.
                        @endif
                    </p>

                    @if ($esMio)
                        <a href="{{ route('entities.index') }}"
                            class="mt-4 inline-block rounded-xl bg-violet-500 px-4 py-2.5 text-[12px] font-black text-white transition hover:bg-violet-400">
                            Ir a mis entidades
                        </a>
                    @endif
                </section>

            @else

                @include('profiles.partials.biblioteca')

                @include('profiles.partials.torneos')

                @include('profiles.partials.a-fondo')
            @endif
        @endif
    </div>

</x-app-layout>
