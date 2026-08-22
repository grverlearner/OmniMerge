@php
    /*
     * Clases literales: Tailwind solo genera lo que encuentra escrito.
     */
    $tierSkin = [
        'GOLD' => 'from-amber-400 to-amber-600 shadow-amber-500/30',
        'SILVER' => 'from-slate-300 to-slate-500 shadow-slate-500/30',
        'BRONZE' => 'from-orange-400 to-orange-700 shadow-orange-600/30',
        'SPECIAL' => 'from-violet-400 to-violet-600 shadow-violet-500/30',
    ];
@endphp

<x-universe-layout :universe="$universe">

    <x-slot name="header">Trofeos</x-slot>


    <div>
        <p class="text-xs font-black uppercase tracking-wider text-violet-600">
            {{ $universe->name }} · Trofeos
        </p>

        <h2 class="mt-2 text-3xl font-black text-slate-900">La vitrina del Universo</h2>

        <p class="mt-2 max-w-2xl text-slate-500">
            Los trofeos pertenecen a este mundo. Se conceden desde las recompensas de un
            torneo y aparecen en el palmarés de quien los gana.
        </p>
    </div>


    @if ($errors->any())
        <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4">
            @foreach ($errors->all() as $error)
                <p class="text-sm font-bold text-rose-700">{{ $error }}</p>
            @endforeach
        </div>
    @endif


    {{-- ÚLTIMO CONQUISTADO --}}

    @if ($recentAwards->isNotEmpty())

        <section class="mt-8">

            <h3 class="text-xs font-black uppercase tracking-wider text-slate-500">
                Conquistado recientemente
            </h3>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                @foreach ($recentAwards as $award)

                    @php
                        $skin = $tierSkin[$award->trophy?->tier ?? 'GOLD'] ?? $tierSkin['GOLD'];
                    @endphp

                    <div class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4">

                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br {{ $skin }} text-2xl shadow-lg">
                            @if ($award->trophy?->image_url)
                                <img src="{{ $award->trophy->image_url }}" alt=""
                                    class="h-full w-full rounded-2xl object-cover">
                            @else
                                {{ $award->trophy?->display_icon ?? '🏆' }}
                            @endif
                        </div>

                        <div class="min-w-0">

                            <p class="truncate text-sm font-black text-slate-900">
                                {{ $award->trophy?->name ?? 'Trofeo' }}
                            </p>

                            <a href="{{ route('universes.entities.show', [$universe, $award->universe_entity_id]) }}"
                                class="block truncate text-xs font-bold text-violet-600 hover:underline">
                                {{ $award->universeEntity?->display_label ?? '—' }}
                            </a>

                            <p class="mt-0.5 truncate text-[10px] text-slate-400">
                                {{ $award->tournamentInstance?->name }}
                                @if ($award->season)
                                    · T{{ $award->season->number }}
                                @endif
                            </p>

                        </div>

                    </div>
                @endforeach

            </div>

        </section>
    @endif


    <div class="mt-10 grid gap-6 lg:grid-cols-3">

        {{-- CATÁLOGO --}}

        <section class="lg:col-span-2">

            <h3 class="text-xs font-black uppercase tracking-wider text-slate-500">
                Trofeos de este Universo
            </h3>

            @if ($trophies->isEmpty())

                <div class="mt-4 rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">

                    <div class="text-5xl opacity-40">🏆</div>

                    <h4 class="mt-4 text-lg font-black text-slate-900">Todavía no hay trofeos</h4>

                    <p class="mx-auto mt-2 max-w-sm text-sm text-slate-500">
                        Crea uno y podrás asignarlo como recompensa del campeón de cualquier torneo.
                    </p>

                </div>
            @else

                <div class="mt-4 space-y-3">

                    @foreach ($trophies as $trophy)

                        @php
                            $skin = $tierSkin[$trophy->tier] ?? $tierSkin['GOLD'];
                        @endphp

                        <div class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4">

                            <div
                                class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br {{ $skin }} text-3xl shadow-lg">
                                @if ($trophy->image_url)
                                    <img src="{{ $trophy->image_url }}" alt=""
                                        class="h-full w-full rounded-2xl object-cover">
                                @else
                                    {{ $trophy->display_icon }}
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">

                                <div class="flex flex-wrap items-center gap-2">

                                    <p class="text-base font-black text-slate-900">{{ $trophy->name }}</p>

                                    <span
                                        class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-black uppercase tracking-wide text-slate-500">
                                        {{ \App\Models\UniverseTrophy::TIERS[$trophy->tier] ?? $trophy->tier }}
                                    </span>

                                </div>

                                @if ($trophy->description)
                                    <p class="mt-1 text-xs leading-relaxed text-slate-500">
                                        {{ $trophy->description }}
                                    </p>
                                @endif

                                <p class="mt-1.5 text-[10px] font-bold text-slate-400">
                                    Concedido {{ $trophy->awards_count }}
                                    {{ $trophy->awards_count === 1 ? 'vez' : 'veces' }}
                                </p>

                            </div>

                            @if ($trophy->awards_count === 0)
                                <form method="POST"
                                    action="{{ route('universes.trophies.destroy', [$universe, $trophy]) }}"
                                    onsubmit="return confirm('¿Eliminar este trofeo?')">
                                    @csrf
                                    @method('DELETE')

                                    <button class="text-xs font-black text-slate-300 hover:text-rose-600">
                                        Eliminar
                                    </button>
                                </form>
                            @endif

                        </div>
                    @endforeach

                </div>
            @endif

        </section>


        {{-- ALTA --}}

        <section>

            <form method="POST" action="{{ route('universes.trophies.store', $universe) }}"
                enctype="multipart/form-data"
                class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6">

                @csrf

                <h3 class="text-base font-black text-slate-900">Nuevo trofeo</h3>

                <div>
                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                        Nombre *
                    </label>

                    <input type="text" name="name" value="{{ old('name') }}"
                        placeholder="Ej. Copa del Universo Anime"
                        class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                </div>

                <div>
                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                        Descripción
                    </label>

                    <textarea name="description" rows="3"
                        placeholder="Qué significa este trofeo en tu mundo..."
                        class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">{{ old('description') }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Icono
                        </label>

                        <input type="text" name="icon" value="{{ old('icon', '🏆') }}" maxlength="8"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-center text-lg focus:border-violet-400 focus:ring-violet-400">
                    </div>

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Categoría
                        </label>

                        <select name="tier"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                            @foreach (\App\Models\UniverseTrophy::TIERS as $value => $label)
                                <option value="{{ $value }}" @selected(old('tier') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div>
                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                        Imagen (opcional)
                    </label>

                    <input type="file" name="image" accept="image/png,image/jpeg,image/webp"
                        class="mt-1.5 w-full rounded-xl border border-slate-300 p-2 text-xs text-slate-600">
                </div>

                <button class="w-full rounded-xl bg-slate-950 px-5 py-3 text-xs font-black text-white hover:bg-slate-800">
                    Crear trofeo
                </button>

            </form>

        </section>

    </div>

</x-universe-layout>
