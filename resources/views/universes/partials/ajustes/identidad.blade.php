{{--
    IDENTIDAD — quién es este mundo.

    Nombre, descripción, estado y portada (la pieza que comparte con crear
    un universo) y, además, su lema: una frase corta que sale bajo su
    nombre en el Resumen y en su tarjeta de «Mis universos».
--}}

<section id="identidad" data-seccion class="scroll-mt-24 space-y-3">

    @include('universes.partials.universe-form')

    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5"
            style="background: linear-gradient(120deg, #a78bfa14, transparent 60%)">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                <x-omni-icon name="chispa" size="h-4 w-4" />
            </span>
            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">Su lema</h2>
                <p class="text-[10px] text-slate-500">Una frase que lo resume. Sale bajo su nombre en el Resumen y en «Mis universos».</p>
            </div>
            <span class="rounded-lg bg-slate-950 px-2 py-1 font-mono text-[10px] text-slate-500" x-text="(s.tagline || '').length + '/120'"></span>
        </div>

        <div class="p-4">
            <input type="text" name="settings[tagline]" x-model="s.tagline" maxlength="120"
                placeholder="«Donde las aldeas ninja se disputan la voluntad de fuego»"
                class="w-full rounded-xl border-slate-700 bg-slate-950 text-[13px] font-bold text-slate-100 placeholder:text-slate-600 focus:ring-2"
                :style="`--tw-ring-color: ${s.accent}`">

            <div class="mt-2 flex flex-wrap gap-1">
                <span class="text-[10px] text-slate-600">Ideas:</span>
                @foreach (['El torneo de los mundos', 'Cada temporada, una leyenda nueva', 'Solo uno se queda con la corona', 'Donde todo se decide en la arena'] as $idea)
                    <button type="button" @click="s.tagline = @js($idea)"
                        class="rounded-full border border-slate-800 px-2 py-0.5 text-[10px] text-slate-400 transition hover:border-violet-400 hover:text-violet-200">{{ $idea }}</button>
                @endforeach
            </div>
        </div>
    </div>
</section>
