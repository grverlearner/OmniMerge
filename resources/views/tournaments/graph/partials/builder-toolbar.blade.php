<section class="rounded-[26px] border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div class="grid flex-1 grid-cols-2 gap-3 sm:grid-cols-4 xl:max-w-3xl">
            @foreach ([['Inicios', $flowAnalysis['stats']['starts'], 'text-emerald-600', 'bg-emerald-50'], ['Fases', $flowAnalysis['stats']['nodes'], 'text-amber-600', 'bg-amber-50'], ['Rutas', $flowAnalysis['stats']['connections'], 'text-violet-600', 'bg-violet-50'], ['Finales', $flowAnalysis['stats']['terminals'], 'text-rose-600', 'bg-rose-50']] as [$label, $value, $text, $background])
                <article class="rounded-2xl {{ $background }} px-4 py-3">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                        {{ $label }}
                    </p>

                    <p class="mt-1 text-xl font-black {{ $text }}">
                        {{ $value }}
                    </p>
                </article>
            @endforeach
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="button" @click="$dispatch('open-add-start')"
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-black text-emerald-700 transition hover:bg-emerald-100">
                ＋ Inicio
            </button>

            <button type="button" @click="$dispatch('open-add-node')"
                class="rounded-xl bg-amber-500 px-4 py-3 text-xs font-black text-white transition hover:bg-amber-600">
                ＋ Fase
            </button>

            <button type="button" @click="$dispatch('open-add-terminal')"
                class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-black text-rose-700 transition hover:bg-rose-100">
                ＋ Final
            </button>

            <button type="button" @click="$dispatch('open-add-connection')"
                class="rounded-xl border border-violet-200 bg-violet-50 px-4 py-3 text-xs font-black text-violet-700 transition hover:bg-violet-100">
                ↗ Conectar ruta
            </button>
        </div>
    </div>

    @if (
        $flowAnalysis['stats']['branches'] > 0 ||
            $flowAnalysis['stats']['convergences'] > 0 ||
            $flowAnalysis['stats']['unreachable_nodes'] > 0)
        <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
            @if ($flowAnalysis['stats']['branches'] > 0)
                <span class="rounded-full bg-violet-50 px-3 py-1.5 text-[10px] font-black text-violet-700">
                    {{ $flowAnalysis['stats']['branches'] }}
                    {{ $flowAnalysis['stats']['branches'] === 1 ? 'bifurcación' : 'bifurcaciones' }}
                </span>
            @endif

            @if ($flowAnalysis['stats']['convergences'] > 0)
                <span class="rounded-full bg-indigo-50 px-3 py-1.5 text-[10px] font-black text-indigo-700">
                    {{ $flowAnalysis['stats']['convergences'] }}
                    {{ $flowAnalysis['stats']['convergences'] === 1 ? 'convergencia' : 'convergencias' }}
                </span>
            @endif

            @if ($flowAnalysis['stats']['unreachable_nodes'] > 0)
                <span class="rounded-full bg-red-50 px-3 py-1.5 text-[10px] font-black text-red-700">
                    {{ $flowAnalysis['stats']['unreachable_nodes'] }}
                    sin ruta desde un inicio
                </span>
            @endif
        </div>
    @endif
</section>
