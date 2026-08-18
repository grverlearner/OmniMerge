@php
    $diagnosticWarnings = $diagnostic['warnings'] ?? [];
    $diagnosticRecommendations = $diagnostic['recommendations'] ?? [];
@endphp

@if (!$diagnostic['valid'] || $diagnosticWarnings !== [] || $diagnosticRecommendations !== [])
    <section class="mt-6 space-y-3">
        @if (!$diagnostic['valid'])
            <div class="rounded-3xl border border-red-200 bg-red-50 p-5">
                <div class="flex items-start gap-3">
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-red-100 font-black text-red-700">
                        !
                    </span>

                    <div>
                        <p class="font-black text-red-900">
                            Errores de configuración
                        </p>

                        <p class="mt-1 text-[10px] leading-5 text-red-700">
                            Estos problemas bloquean una configuración válida o su ejecución prevista.
                        </p>

                        <div class="mt-2 space-y-1">
                            @foreach ($diagnostic['errors'] as $error)
                                <p class="text-xs leading-5 text-red-700">
                                    • {{ $error }}
                                </p>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($diagnosticWarnings !== [])
            <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5">
                <div class="flex items-start gap-3">
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-100 font-black text-amber-700">
                        !
                    </span>

                    <div>
                        <p class="font-black text-amber-900">
                            Advertencias
                        </p>

                        <p class="mt-1 text-[10px] leading-5 text-amber-700">
                            La definición puede ser válida, pero necesita atención antes de considerarla lista.
                        </p>

                        <div class="mt-2 space-y-1">
                            @foreach ($diagnosticWarnings as $warning)
                                <p class="text-xs leading-5 text-amber-800">
                                    • {{ $warning }}
                                </p>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($diagnosticRecommendations !== [])
            <div class="rounded-3xl border border-indigo-200 bg-indigo-50 p-5">
                <div class="flex items-start gap-3">
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-indigo-100 font-black text-indigo-700">
                        i
                    </span>

                    <div>
                        <p class="font-black text-indigo-900">
                            Recomendaciones
                        </p>

                        <p class="mt-1 text-[10px] leading-5 text-indigo-700">
                            No bloquean la configuración; ayudan a mantener una definición más clara y mínima.
                        </p>

                        <div class="mt-2 space-y-1">
                            @foreach ($diagnosticRecommendations as $recommendation)
                                <p class="text-xs leading-5 text-indigo-800">
                                    • {{ $recommendation }}
                                </p>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </section>
@else
    <section class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
        <p class="text-xs font-black text-emerald-800">
            ✓ El contrato, el objetivo y los overrides son compatibles sin advertencias.
        </p>
    </section>
@endif
