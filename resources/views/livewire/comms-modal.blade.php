<div x-data="{ activeTab: 'threads' }">
    <x-ui-modal size="xl" wire:model="modalShow">
        {{-- Modal-Header mit Tabs --}}
        <x-slot name="header">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-semibold text-gray-900 m-0">Kommunikation</h2>
                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">COMMS</span>
                </div>
            </div>
            <div class="flex gap-1 mt-4 border-b border-gray-200">
                @php $tabs = [
                    ['label' => 'Nachrichten', 'value' => 'threads'],
                    ['label' => 'Aktionen', 'value' => 'actions'],
                    ['label' => 'Assistant', 'value' => 'assistant'],
                ]; @endphp

                @foreach($tabs as $tab)
                    <button
                        type="button"
                        @click="activeTab = '{{ $tab['value'] }}'"
                        class="px-3 py-2 text-sm font-medium rounded-t-lg transition-colors"
                        :class="{ 'text-blue-600 border-b-2 border-blue-600 bg-blue-50' : activeTab === '{{ $tab['value'] }}', 'text-gray-500 hover:text-gray-700' : activeTab !== '{{ $tab['value'] }}' }"
                    >
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>
        </x-slot>

        {{-- Modal Body --}}
        <div class="h-full w-full flex gap-1 overflow-x-auto">
            {{-- Tab: Nachrichten --}}
            <div x-show="activeTab === 'threads'" x-cloak class="h-full w-full flex gap-1 overflow-x-auto">
                {{-- Sidebar: Kanäle --}}
                <div class="flex-shrink-0 h-full min-w-80 w-80 max-w-80 flex flex-col border-r border-gray-200">
                <div class="flex-1 overflow-y-auto p-4 max-h-full flex flex-col gap-2">
                    <h3 class="text-sm text-gray-600 font-semibold uppercase mb-2">Kanäle</h3>

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
            <div class="flex-1 h-full flex flex-col max-h-full overflow-y-auto">
                @if ($activeChannelComponent)
                    @livewire($activeChannelComponent, $activeChannelPayload, key($activeChannelId))
                @else
                    <div class="text-gray-500 text-sm italic p-8 text-center">
                        Kein Kanal ausgewählt.
                    </div>
                @endif
            </div>

                </div>
            </div>

            {{-- Tab: Aktionen --}}
            <div x-show="activeTab === 'actions'" x-cloak class="h-full w-full flex items-center justify-center">
                <div class="text-gray-500 text-sm italic p-8 text-center">
                    Aktionen-Tab wird implementiert...
                </div>
            </div>

            {{-- Tab: Assistant --}}
            <div x-show="activeTab === 'assistant'" x-cloak class="h-full w-full flex items-center justify-center">
                <div class="text-gray-500 text-sm italic p-8 text-center">
                    Assistant-Tab wird implementiert...
                </div>
            </div>
        </div>

        {{-- Modal Footer --}}
        <x-slot name="footer">
            <div class="flex items-center justify-between w-full">
                <div class="text-sm text-gray-500 flex gap-4">
                    @if($contextModel)
                        <span class="px-3 py-1 bg-gray-100 rounded-full text-xs">
                            Kontext: {{ class_basename($contextModel) }} #{{ $contextModelId }}
                        </span>
                    @endif
                </div>

                <div>
                    <x-ui-button 
                        variant="secondary-outline"
                        wire:click="closeModal"
                    >
                        Schließen
                    </x-ui-button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>
</div>