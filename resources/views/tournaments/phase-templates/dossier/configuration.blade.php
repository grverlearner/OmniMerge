@php
    /*
     * La configuración, contada.
     *
     * No son controles: son decisiones ya tomadas. La Super Edición enseña
     * desplegables y casillas; aquí se lee lo que salió de ellas —«ida y
     * vuelta», «se siembra por ranking del torneo», «los puestos 5 al 8 se
     * ordenan jugando»—.
     *
     * El contenido lo da cada motor en su summary(), porque solo él sabe
     * traducir sus propios ajustes a una frase. Esta vista solo lo dibuja.
     *
     * Lo que NO aparece: el formato de batalla. Cuántos juegos tiene un
     * enfrentamiento pertenece al torneo real, no a la fase.
     */

    /*
     * Las clases van literales y no construidas con el nombre del color:
     * Tailwind lee el código fuente para decidir qué compila, y una clase
     * armada en tiempo de ejecución nunca llega al CSS.
     */
    $accents = [
        'cyan' => ['dot' => 'bg-cyan-400', 'text' => 'text-cyan-300', 'soft' => 'bg-cyan-500/10', 'border' => 'border-cyan-500/25'],
        'emerald' => ['dot' => 'bg-emerald-400', 'text' => 'text-emerald-300', 'soft' => 'bg-emerald-500/10', 'border' => 'border-emerald-500/25'],
        'amber' => ['dot' => 'bg-amber-400', 'text' => 'text-amber-300', 'soft' => 'bg-amber-500/10', 'border' => 'border-amber-500/25'],
        'violet' => ['dot' => 'bg-violet-400', 'text' => 'text-violet-300', 'soft' => 'bg-violet-500/10', 'border' => 'border-violet-500/25'],
        'indigo' => ['dot' => 'bg-indigo-400', 'text' => 'text-indigo-300', 'soft' => 'bg-indigo-500/10', 'border' => 'border-indigo-500/25'],
        'sky' => ['dot' => 'bg-sky-400', 'text' => 'text-sky-300', 'soft' => 'bg-sky-500/10', 'border' => 'border-sky-500/25'],
    ];
@endphp

<section class="mb-4">

    <div class="mb-2 flex items-center gap-2">
        <span class="h-3 w-1 rounded-full bg-slate-600"></span>
        <h2 class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">
            Cómo está configurada
        </h2>
        <span class="text-[10px] text-slate-600">— lo que se decidió en la Super Edición</span>
    </div>

    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">

        @foreach ($summary as $group)
            @php $a = $accents[$group['accent']] ?? $accents['cyan']; @endphp

            <div class="overflow-hidden rounded-2xl border {{ $a['border'] }} bg-slate-900/50">

                <div class="flex items-center gap-1.5 border-b border-slate-800 px-3 py-1.5 {{ $a['soft'] }}">
                    <span class="text-[11px]">{{ $group['icon'] }}</span>
                    <h3 class="text-[10px] font-black uppercase tracking-wider {{ $a['text'] }}">
                        {{ $group['title'] }}
                    </h3>
                </div>

                <dl class="divide-y divide-slate-800/70">
                    @foreach ($group['rows'] as $row)
                        <div class="px-3 py-1.5">

                            <div class="flex items-baseline gap-2">
                                <dt class="shrink-0 text-[9px] font-black uppercase tracking-wider text-slate-500">
                                    {{ $row['label'] }}
                                </dt>
                                <dd class="ml-auto text-right text-[11px] font-bold text-slate-200">
                                    {{ $row['value'] }}
                                </dd>
                            </div>

                            @if (! empty($row['hint']))
                                <p class="mt-0.5 text-[9px] leading-relaxed text-slate-600">
                                    {{ $row['hint'] }}
                                </p>
                            @endif

                        </div>
                    @endforeach
                </dl>

            </div>
        @endforeach

    </div>

</section>
