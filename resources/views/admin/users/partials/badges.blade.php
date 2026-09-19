{{-- El estado de una cuenta en insignias: rol, bloqueo, borrado e insignia de creador --}}

@if ($u->isAdmin())
    <span class="inline-flex items-center gap-1 rounded-full border border-violet-500/40 bg-violet-500/10 px-2 py-0.5 text-[10px] font-black text-violet-300">
        <x-omni-icon name="escudo" size="h-3 w-3" /> Admin
    </span>
@endif

@if ($u->trashed())
    <span class="inline-flex items-center gap-1 rounded-full border border-slate-600 bg-slate-800 px-2 py-0.5 text-[10px] font-black text-slate-300">
        <x-omni-icon name="papelera" size="h-3 w-3" /> Eliminada
    </span>
@elseif ($u->isBanned())
    <span title="{{ $u->ban_reason }}" class="inline-flex items-center gap-1 rounded-full border border-orange-500/40 bg-orange-500/10 px-2 py-0.5 text-[10px] font-black text-orange-300">
        <x-omni-icon name="prohibido" size="h-3 w-3" />
        {{ $u->banned_until ? 'Bloqueada hasta ' . $u->banned_until->format('d/m') : 'Bloqueada' }}
    </span>
@elseif (! $u->last_login_at)
    <span class="inline-flex items-center gap-1 rounded-full border border-slate-700 bg-slate-900 px-2 py-0.5 text-[10px] font-black text-slate-400">
        Nunca entró
    </span>
@endif

<x-creator-badge :user="$u" />
