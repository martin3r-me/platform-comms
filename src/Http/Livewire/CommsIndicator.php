<?php

namespace Platform\Comms\Http\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Platform\Comms\Services\CommsActivityService;

class CommsIndicator extends Component
{
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->refresh();
    }

    #[On('comms-indicator-refresh')]
    public function refresh(): void
    {
        $user = Auth::user();
        $teamId = $user?->currentTeam?->id;
        $userId = $user?->id;

        if (!$userId || !class_exists(CommsActivityService::class) || !CommsActivityService::enabled()) {
            $this->unreadCount = 0;
            return;
        }

        $items = CommsActivityService::unreadContexts((int) $userId, $teamId, 200);
        $this->unreadCount = array_sum(array_map(fn ($i) => (int) ($i['unread_count'] ?? 0), $items));
    }

    public function render()
    {
        return view('comms::livewire.comms-indicator');
    }
}


