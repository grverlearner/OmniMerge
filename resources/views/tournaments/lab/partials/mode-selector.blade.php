<section
    x-show="
        state
        &&
        state.status === 'READY'
        &&
        !labMode
        &&
        !graphRuntime()
    "
    class="rounded-[30px] border border-slate-200 bg-white p-6 shadow-sm">

    <div class="text-center">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-600">
            Elige cómo quieres probar el torneo
        </p>

        <h2 class="mt-2 text-2xl font-black text-slate-950">
            ¿Qué deseas comprobar?
        </h2>

        <p class="mx-auto mt-2 max-w-2xl text-xs leading-6 text-slate-500">
            Puedes ejecutar todo el recorrido automáticamente o probar
            una fase individual para revisar sus rondas y resultados.
        </p>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">

        <button type="button" @click="chooseMode('AUTOMATIC')"
            class="group rounded-3xl border-2 border-violet-200 bg-gradient-to-br from-violet-50 to-white p-6 text-left transition hover:border-violet-500 hover:shadow-lg">

            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-600 text-xl text-white">
                ⚡
            </div>

            <p class="mt-5 text-[10px] font-black uppercase tracking-[0.14em] text-violet-600">
                Recomendado
            </p>

            <h3 class="mt-1 text-xl font-black text-slate-950">
                Simulación completa
            </h3>

            <p class="mt-2 text-xs leading-6 text-slate-500">
                Ejecuta Starts, conexiones, fases, salidas y terminales
                de acuerdo con el Tournament Graph.
            </p>

            <span class="mt-5 inline-flex rounded-xl bg-violet-600 px-4 py-2.5 text-xs font-black text-white">
                Elegir simulación completa →
            </span>
        </button>

        <button type="button" @click="chooseMode('MANUAL')"
            class="group rounded-3xl border-2 border-slate-200 bg-slate-50 p-6 text-left transition hover:border-sky-400 hover:bg-sky-50">

            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-600 text-xl text-white">
                🧪
            </div>

            <p class="mt-5 text-[10px] font-black uppercase tracking-[0.14em] text-sky-600">
                Herramienta avanzada
            </p>

            <h3 class="mt-1 text-xl font-black text-slate-950">
                Probar una fase
            </h3>

            <p class="mt-2 text-xs leading-6 text-slate-500">
                Selecciona manualmente un nodo y sus participantes.
                Sirve para comprobar un motor de fase aislado.
            </p>

            <span class="mt-5 inline-flex rounded-xl bg-sky-600 px-4 py-2.5 text-xs font-black text-white">
                Elegir prueba manual →
            </span>
        </button>
    </div>
</section>
