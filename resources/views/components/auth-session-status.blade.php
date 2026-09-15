@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3.5 py-2.5 text-[13px] font-bold leading-5 text-emerald-200']) }}
        role="status">
        {{ $status }}
    </div>
@endif
