<section class="space-y-4">
    @if ($validation['valid'])
        <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5">
            <div class="flex items-start gap-3">
                <span
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-lg font-black text-emerald-700">
                    ✓
                </span>

                <div>
                    <p class="font-black text-emerald-950">
                        Grafo interno válido
                    </p>

                    <p class="mt-1 text-xs leading-5 text-emerald-700">
                        Las capacidades, resultados, rutas y salidas son estructuralmente compatibles.
                    </p>

                    @if (!$validation['executable'])
                        <p class="mt-2 text-xs font-bold text-amber-700">
                            La estructura todavía necesita decisiones manuales o personalizadas antes de ejecutarse.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="rounded-3xl border border-red-200 bg-red-50 p-5">
            <div class="flex items-start gap-3">
                <span
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-red-100 text-lg font-black text-red-700">
                    !
                </span>

                <div>
                    <p class="font-black text-red-950">
                        El grafo interno necesita correcciones
                    </p>

                    <p class="mt-1 text-xs leading-5 text-red-700">
                        Se encontraron {{ $validation['counts']['errors'] }}
                        {{ $validation['counts']['errors'] === 1 ? 'error bloqueante' : 'errores bloqueantes' }}.
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if ($validation['errors'] !== [])
        <div class="overflow-hidden rounded-3xl border border-red-200 bg-white">
            <div class="border-b border-red-100 bg-red-50 px-5 py-4">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-red-600">
                    Errores bloqueantes
                </p>
            </div>

            <div class="divide-y divide-red-100">
                @foreach ($validation['errors'] as $issue)
                    <article class="p-4">
                        <div class="flex items-start gap-3">
                            <span
                                class="mt-0.5 rounded-lg bg-red-100 px-2 py-1 font-mono text-[9px] font-black text-red-700">
                                {{ $issue['code'] }}
                            </span>

                            <div class="min-w-0">
                                <p class="text-xs font-black leading-5 text-red-900">
                                    {{ $issue['message'] }}
                                </p>

                                <p class="mt-1 truncate text-[10px] font-bold text-red-500">
                                    {{ $issue['entity_type'] }}
                                    ·
                                    {{ $issue['entity_label'] }}
                                </p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    @endif

    @if ($validation['warnings'] !== [])
        <div class="overflow-hidden rounded-3xl border border-amber-200 bg-white">
            <div class="border-b border-amber-100 bg-amber-50 px-5 py-4">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-600">
                    Advertencias
                </p>
            </div>

            <div class="divide-y divide-amber-100">
                @foreach ($validation['warnings'] as $issue)
                    <article class="p-4">
                        <p class="text-xs font-black leading-5 text-amber-900">
                            {{ $issue['message'] }}
                        </p>

                        <p class="mt-1 text-[10px] font-bold text-amber-500">
                            {{ $issue['entity_label'] }}
                            ·
                            {{ $issue['code'] }}
                        </p>
                    </article>
                @endforeach
            </div>
        </div>
    @endif

    @if ($validation['recommendations'] !== [])
        <div class="overflow-hidden rounded-3xl border border-cyan-200 bg-white">
            <div class="border-b border-cyan-100 bg-cyan-50 px-5 py-4">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-cyan-700">
                    Recomendaciones
                </p>
            </div>

            <div class="divide-y divide-cyan-100">
                @foreach ($validation['recommendations'] as $issue)
                    <article class="p-4">
                        <p class="text-xs font-bold leading-5 text-cyan-900">
                            {{ $issue['message'] }}
                        </p>

                        <p class="mt-1 text-[10px] font-bold text-cyan-500">
                            {{ $issue['code'] }}
                        </p>
                    </article>
                @endforeach
            </div>
        </div>
    @endif
</section>
