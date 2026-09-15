@php
    /*
     * El cierre.
     *
     * Una sola cosa que pedir, y el recordatorio de por donde se empieza si
     * no se sabe por donde empezar.
     */
@endphp

<section class="relative px-5 py-16 lg:px-8">

    <div class="mx-auto max-w-3xl">

        <div class="relative overflow-hidden rounded-3xl border border-indigo-500/25 bg-slate-900/50 p-8 text-center">

            <span class="pointer-events-none absolute -left-20 -top-20 h-64 w-64 rounded-full bg-indigo-600/20 blur-[90px]"></span>
            <span class="pointer-events-none absolute -bottom-20 -right-20 h-64 w-64 rounded-full bg-violet-600/20 blur-[90px]"></span>

            <div class="relative">

                <h2 class="text-3xl font-black tracking-tight text-white sm:text-4xl">
                    Monta tu primer mundo
                </h2>

                <p class="mx-auto mt-3 max-w-xl text-[14px] leading-relaxed text-slate-400">
                    Crea una entidad, ponle los atributos que quieras, méte­la en un universo y
                    deja que compita. Puedes parar en cualquier paso: lo que hayas hecho se
                    queda hecho.
                </p>

                <div class="mt-7 flex flex-wrap items-center justify-center gap-2.5">
                    @auth
                        <a href="{{ route('hub') }}"
                            class="flex items-center gap-2 rounded-xl bg-indigo-500 px-7 py-3.5 text-[14px] font-black text-white transition hover:bg-indigo-400">
                            <x-omni-icon name="casa" size="h-4 w-4" />
                            Abrir OmniMerge
                        </a>
                    @else
                        <a href="{{ route('register') }}"
                            class="flex items-center gap-2 rounded-xl bg-white px-7 py-3.5 text-[14px] font-black text-slate-950 transition hover:bg-slate-200">
                            Crear mi cuenta
                            <x-omni-icon name="flecha-derecha" size="h-4 w-4" />
                        </a>

                        <a href="{{ route('login') }}"
                            class="rounded-xl border border-white/15 px-7 py-3.5 text-[14px] font-black text-slate-200 transition hover:bg-white/5">
                            Ya tengo una
                        </a>
                    @endauth
                </div>

                <p class="mt-5 text-[11px] text-slate-600">
                    Gratis. Tu biblioteca es privada hasta que tú decidas lo contrario.
                </p>
            </div>
        </div>
    </div>
</section>
