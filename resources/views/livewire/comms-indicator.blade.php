<button
    type="button"
    x-data
    @click="$dispatch('open-modal-comms')"
    wire:poll.15s="refresh"
    class="relative inline-flex items-center justify-center w-8 h-8 rounded-md border border-[var(--ui-border)]/60 transition text-[var(--ui-muted)] hover:text-[var(--ui-primary)] hover:bg-[var(--ui-muted-5)]"
    title="Comms"
>
    @svg('heroicon-o-paper-airplane', 'w-5 h-5')

    @if(($unreadCount ?? 0) > 0)
        <span class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-[var(--ui-primary)] text-[var(--ui-on-primary)] text-[11px] font-semibold leading-none">
            {{ $unreadCount }}
        </span>
    @endif
</button>


