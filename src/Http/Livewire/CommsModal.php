<?php

namespace Platform\Comms\Http\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Platform\Comms\Registry\ChannelRegistry;
use Platform\Comms\ChannelEmail\ChannelEmailRegistrar;
use Illuminate\Support\Facades\Auth;

class CommsModal extends Component
{
    // Steuerung des Modals
    public bool $modalShow = false;
    public string $activeTab = 'threads';

    // Kontextinformationen
    public ?string $contextModel = null;
    public ?int $contextModelId = null;
    public ?string $contextSubject = null;
    public ?string $contextDescription = null;
    public ?string $contextUrl = null;
    public ?string $contextSource = null;
    public array $recipients = [];
    public array $contextMeta = [];

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

    /**
     * Alle Channels laden, die für den aktuellen User/Team relevant sind
     */
    

    protected function loadChannels(): void
    {
        ChannelRegistry::runRegistrars(); // <- Jetzt werden alle Registrare ausgeführt

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

    public function closeModal(): void
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('comms::livewire.comms-modal');
    }
}