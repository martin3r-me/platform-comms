<div>
    <x-ui-modal size="full" wire:model="modalShow">
        {{-- Modal-Header mit Tabs --}}
        <x-slot name="header">
            <div class="d-flex flex-row gap-2">
                @php $tabs = [
                    ['label' => 'Nachrichten', 'value' => 'threads'],
                    ['label' => 'Aktionen', 'value' => 'actions'],
                    ['label' => 'Assistant', 'value' => 'assistant'],
                ]; @endphp

                @foreach($tabs as $tab)
                    <x-ui-button
                        wire:click="$set('activeTab', '{{ $tab['value'] }}')"
                        variant="{{ $activeTab === $tab['value'] ? 'primary' : 'secondary-outline' }}"
                        size="sm"
                    >
                        {{ $tab['label'] }}
                    </x-ui-button>
                @endforeach
            </div>
        </x-slot>

        {{-- Modal Body --}}
        <div class="h-full w-full d-flex gap-1 overflow-x-auto">
            
            {{-- Sidebar: Kanäle --}}
            <div class="flex-shrink-0 h-full min-w-80 w-80 max-w-80 flex flex-col border-right-1 border-right-solid border-right-muted">
                <div class="flex-grow-1 overflow-y-auto p-4 max-h-full d-flex flex-col gap-2">
                    <h3 class="text-sm text-muted-foreground font-semibold uppercase mb-2">Kanäle</h3>

                    <div class="channel-groups">
                        @foreach ($channels as $type => $group)
                            @php
                                $groupKey = \Illuminate\Support\Str::slug($type);
                                $groupLabel = $group[0]['group'] ?? ucfirst($type);
                            @endphp

                            <x-ui-grouped-list 
                                :title="$groupLabel" 
                                icon="heroicon-o-envelope"
                                wire:key="group-{{ $groupKey }}"
                            >
                                @foreach ($group as $channel)
                                    <x-ui-grouped-list-item 
                                        :label="$channel['label'] ?? 'Kein Label'" 
                                        :subtitle="$channel['type'] ?? ''"
                                        :selected="$activeChannelId === $channel['id']"
                                        :badge="isset($channel['badge']) ? $channel['badge'] : null"
                                        wire:click="selectChannel('{{ $channel['id'] }}')"
                                        wire:key="channel-item-{{ $groupKey }}-{{ $channel['id'] }}"
                                    />
                                @endforeach
                                

                            </x-ui-grouped-list>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Dynamische Channel-Komponente --}}
            <div class="flex-grow-1 h-full flex flex-col max-h-full overflow-y-auto">
                @if ($activeChannelComponent)
                    @livewire($activeChannelComponent, $activeChannelPayload, key($activeChannelId))
                @else
                    <div class="text-muted-foreground text-sm italic">
                        Kein Kanal ausgewählt.
                    </div>
                @endif
            </div>

        </div>

        {{-- Modal Footer --}}
        <x-slot name="footer">
            <div class="d-flex items-center justify-between w-full">
                <div class="text-sm text-muted-foreground d-flex gap-4">
                    @if($contextModel)
                        <x-ui-button 
                            variant="muted"
                            disabled
                        >
                            Kontext: {{ class_basename($contextModel) }} #{{ $contextModelId }}
                        </x-ui-button>
                    @endif
                </div>

                <div>
                    <x-ui-button 
                        variant="info-danger"
                        wire:click="closeModal"
                    >
                        Schließen
                    </x-ui-button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Floating Action Button --}}
    <div class="position-fixed bottom-6 right-6 z-50">
        <x-ui-button 
            wire:click="$set('modalShow', true)"
            variant="info"
            size="lg"
            iconOnly
            rounded="full"
            title="COMMS öffnen"
        >            
            @svg('heroicon-o-ellipsis-horizontal-circle', 'w-6 h-6 text-on-info')
        </x-ui-button>
    </div>
</div>