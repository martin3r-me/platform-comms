<?php

namespace Platform\Comms\Http\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Platform\Comms\Registry\ChannelRegistry;
use Platform\Comms\Registry\ChannelProviderRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class CommsModal extends Component
{
    // Steuerung des Modals
    public bool $modalShow = false;
    public string $activeTab = 'threads';

    // Capabilities (generisch)
    public bool $canUseThreads = true;
    public bool $canManageChannels = true;

    // Kontextinformationen
    public ?string $contextModel = null;
    public ?int $contextModelId = null;
    public ?string $contextSubject = null;
    public ?string $contextDescription = null;
    public ?string $contextUrl = null;
    public ?string $contextSource = null;
    public array $recipients = [];
    public array $contextMeta = [];

    // Optional: vom Kontext vorgeschlagener Channel
    public ?string $preferredChannelId = null;

    // Channel-Create Form
    public string $newChannelType = 'email';
    public ?string $newChannelAddress = null;
    public ?string $newChannelName = null;
    public bool $newChannelDefault = false;

    // Channel-Actions
    public ?string $channelActionMessage = null;

    // Channels (gruppiert nach Typ)
    public array $channels = [];

    public $activeChannelId = null;
    public ?string $activeChannelComponent = null;
    public array $activeChannelPayload = [];

    public function mount(): void
    {
        $this->loadChannels();
    }

    #[On('comms')]
    public function prepareCommsContext(array $payload = []): void
    {
        $this->applyPayload($payload);
        $this->applyCapabilities($payload);
        $this->preferredChannelId = $payload['preferred_channel_id'] ?? $this->loadBoundChannelForContext();

        // Default-Tab je nach Capabilities
        $this->activeTab = $this->canUseThreads ? 'threads' : 'channels';

        $this->loadChannels();
        $this->maybeSelectPreferredChannel();
    }

    /**
     * Kontext-Informationen aus Event übernehmen
     */
    protected function applyPayload(array $payload): void
    {
        $this->contextModel        = $payload['model']        ?? null;
        $this->contextModelId      = $payload['modelId']      ?? null;
        $this->contextSubject      = $payload['subject']      ?? null;
        $this->contextDescription  = $payload['description']  ?? null;
        $this->contextUrl          = $payload['url']          ?? null;
        $this->contextSource       = $payload['source']       ?? null;
        $this->recipients          = $payload['recipients']   ?? [];
        $this->contextMeta         = $payload['meta']         ?? [];
    }

    protected function applyCapabilities(array $payload): void
    {
        $caps = $payload['capabilities'] ?? null;

        if (is_array($caps)) {
            $this->canManageChannels = (bool) ($caps['manage_channels'] ?? false);
            $this->canUseThreads = (bool) ($caps['threads'] ?? false);
            return;
        }

        // Fallback-Heuristik (generisch): Board/Project → manage, Ticket/Task → threads, sonst beides.
        $type = $this->contextModel ? class_basename($this->contextModel) : '';

        if (str_contains($type, 'Board') || str_contains($type, 'Project')) {
            $this->canManageChannels = true;
            $this->canUseThreads = false;
            return;
        }

        if (str_contains($type, 'Ticket') || str_contains($type, 'Task')) {
            $this->canManageChannels = false;
            $this->canUseThreads = true;
            return;
        }

        $this->canManageChannels = true;
        $this->canUseThreads = true;
    }

    protected function loadBoundChannelForContext(): ?string
    {
        if (!$this->contextModel || !$this->contextModelId) {
            return null;
        }

        if (!Schema::hasTable('comms_context_channels')) {
            return null;
        }

        return DB::table('comms_context_channels')
            ->where('context_type', $this->contextModel)
            ->where('context_id', $this->contextModelId)
            ->value('channel_id');
    }

    /**
     * Alle Channels laden, die für den aktuellen User/Team relevant sind
     */
    

    protected function loadChannels(): void
    {
        // Registrare nur einmal pro Request ausführen (Registry kümmert sich um Caching)
        ChannelRegistry::runRegistrars();

        $user = Auth::user();
        $userId = $user?->id;
        $teamId = $user?->currentTeam?->id;

        $this->channels = collect(ChannelRegistry::all())
            ->filter(function ($c) use ($userId, $teamId) {
                return ($c['team_id'] ?? null) === $teamId
                    && (!isset($c['user_id']) || $c['user_id'] === $userId);
            })
            ->groupBy('type')
            ->map(fn($group) => $group->values()->all())
            ->all();
        
        $this->maybeSelectPreferredChannel();
    }

    public function selectChannel(string $channelId): void
    {
        // Stelle sicher, dass alle Registrare gelaufen sind
        ChannelRegistry::runRegistrars();

        // Hole Config
        $config = ChannelRegistry::get($channelId);

        if (!$config) {
            logger()->warning("Channel not found for ID: {$channelId}");
            return;
        }

        // Kontext in Payload injizieren
        $payload = $config['payload'] ?? [];

        $payload['context'] = [
            'model'       => $this->contextModel,
            'modelId'     => $this->contextModelId,
            'subject'     => $this->contextSubject,
            'description' => $this->contextDescription,
            'url'         => $this->contextUrl,
            'source'      => $this->contextSource,
            'recipients'  => $this->recipients,
            'meta'        => $this->contextMeta,
        ];

        // Aktiven Channel setzen
        $this->activeChannelId        = $channelId;
        $this->activeChannelComponent = $config['component'] ?? null;
        $this->activeChannelPayload   = $payload;
    }

    public function openModal(): void
    {
        $this->modalShow = true;
        $this->loadChannels();
    }

    #[On('open-modal-comms')]
    public function openModalFromSidebar(): void
    {
        $this->openModal();
    }

    public function closeModal(): void
    {
        $this->modalShow = false;
    }



    #[On('comms-account-updated')]
    public function refreshChannels(): void
    {
        // Channels neu laden wenn sich ein Account geändert hat
        $this->loadChannels();
    }

    /**
     * Legt einen neuen Channel über den Provider-Registry an.
     * Bei Helpdesk-Kontext wird der Channel dem Board/Ticket zugeordnet.
     */
    public function createChannel(): void
    {
        $this->validate([
            'newChannelType' => 'required|string',
            'newChannelAddress' => 'required|string',
            'newChannelName' => 'nullable|string',
        ]);

        if (!ChannelProviderRegistry::has($this->newChannelType)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Kein Provider für Typ {$this->newChannelType} registriert.",
            ]);
            return;
        }

        try {
            $channelId = ChannelProviderRegistry::create($this->newChannelType, [
                'address'    => $this->newChannelAddress,
                'email'      => $this->newChannelAddress, // Fallback für Provider
                'name'       => $this->newChannelName,
                'team_id'    => Auth::user()?->currentTeam?->id,
                'user_id'    => Auth::id(),
                'is_default' => $this->newChannelDefault,
            ]);

            // Channels neu laden und neu angelegten aktivieren
            ChannelRegistry::runRegistrars(true);
            $this->preferredChannelId = $channelId;
            $this->loadChannels();

            // Wenn Helpdesk-Kontext, Channel zuordnen
            $this->attachChannelToContext($channelId);
            $this->selectChannel($channelId);

            // Formular zurücksetzen
            $this->reset('newChannelAddress', 'newChannelName', 'newChannelDefault');

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Channel wurde angelegt.',
            ]);
        } catch (\Throwable $e) {
            Log::error('[Comms] Channel anlegen fehlgeschlagen', [
                'type' => $this->newChannelType,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Channel konnte nicht angelegt werden: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Löscht einen Channel über den Provider-Registry.
     */
    public function deleteChannel(string $channelId): void
    {
        [$type] = explode(':', $channelId) + [null];

        if (!$type || !ChannelProviderRegistry::has($type)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Kein Provider für Channel-ID {$channelId} registriert.",
            ]);
            return;
        }

        try {
            ChannelProviderRegistry::delete($type, $channelId);
            ChannelRegistry::runRegistrars(true);
            $this->loadChannels();

            if ($this->activeChannelId === $channelId) {
                $this->activeChannelId = null;
                $this->activeChannelComponent = null;
                $this->activeChannelPayload = [];
            }

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Channel wurde gelöscht.',
            ]);
        } catch (\Throwable $e) {
            Log::error('[Comms] Channel löschen fehlgeschlagen', [
                'channelId' => $channelId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Channel konnte nicht gelöscht werden: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Hängt einen Channel an den aktuellen Kontext (Helpdesk Board oder Ticket), falls möglich.
     */
    protected function attachChannelToContext(string $channelId): void
    {
        if (!$this->contextModel || !$this->contextModelId) {
            return;
        }

        // 1) Generisch: Binding-Tabelle
        if (Schema::hasTable('comms_context_channels')) {
            DB::table('comms_context_channels')->updateOrInsert(
                ['context_type' => $this->contextModel, 'context_id' => $this->contextModelId],
                [
                    'channel_id' => $channelId,
                    'team_id' => Auth::user()?->currentTeam?->id,
                    'created_by_user_id' => Auth::id(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // 2) Backwards-Compat: falls das Context-Model ein comms_channel_id Feld hat (Helpdesk aktuell)
        if (class_exists($this->contextModel)) {
            $model = $this->contextModel::find($this->contextModelId);
            if ($model) {
                $table = $model->getTable();
                if (Schema::hasColumn($table, 'comms_channel_id')) {
                    $model->setAttribute('comms_channel_id', $channelId);
                    $model->save();
                }
            }
        }
    }

    /**
     * Wählt den vom Kontext vorgeschlagenen Channel vor, falls vorhanden.
     */
    protected function maybeSelectPreferredChannel(): void
    {
        if (!$this->preferredChannelId) {
            return;
        }

        $config = ChannelRegistry::get($this->preferredChannelId);
        if ($config) {
            $this->selectChannel($this->preferredChannelId);
        }
    }



    public function render()
    {
        return view('comms::livewire.comms-modal');
    }
}