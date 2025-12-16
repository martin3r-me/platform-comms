<div x-data="{ activeTab: @entangle('activeTab') }">
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
                @php
                    $tabs = [];
                    $tabs[] = ['label' => 'Inbox', 'value' => 'inbox'];
                    if ($canUseThreads) $tabs[] = ['label' => 'Kommunikation', 'value' => 'threads'];
                    if ($canManageChannels) $tabs[] = ['label' => 'Kanäle verwalten', 'value' => 'channels'];
                @endphp

                @foreach($tabs as $tab)
                    <button
                        type="button"
                        @click="activeTab = '{{ $tab['value'] }}'"
                        class="px-3 py-2 text-sm font-medium rounded-t-lg transition-colors"
                        :class="{ 'text-blue-600 border-b-2 border-blue-600 bg-blue-50' : activeTab === '{{ $tab['value'] }}', 'text-gray-500 hover:text-gray-700' : activeTab !== '{{ $tab['value'] }}' }"
                    >
                        <span class="inline-flex items-center gap-2">
                            {{ $tab['label'] }}
                            @if($tab['value'] === 'inbox' && ($inboxUnreadCount ?? 0) > 0)
                                <span class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-gray-900 text-white text-[11px]">
                                    {{ $inboxUnreadCount }}
                                </span>
                            @endif
                        </span>
                    </button>
                @endforeach
            </div>
        </x-slot>

        {{-- Modal Body --}}
        <div class="h-full w-full flex flex-col">
            {{-- Tab: Inbox --}}
            <div x-show="activeTab === 'inbox'" x-cloak class="h-full w-full p-4 overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Inbox</h3>
                        <p class="text-sm text-gray-500">Kontexte mit neuen Nachrichten (über alle Channels).</p>
                    </div>
                    <x-ui-button size="sm" variant="secondary-outline" wire:click="loadInbox">Aktualisieren</x-ui-button>
                </div>

                <div class="space-y-2">
                    @forelse($inboxItems as $item)
                        @php
                            $type = $item['context_type'];
                            $id = $item['context_id'];
                            $count = $item['unread_count'] ?? 0;
                            $last = $item['last_occurred_at'] ?? null;
                            $title = $item['title'] ?? (class_basename($type) . ' #' . $id);
                            $subtitle = $item['subtitle'] ?? class_basename($type);
                            $summary = $item['last_summary'] ?? null;
                        @endphp
                        <button
                            type="button"
                            wire:click="openInboxContext('{{ addslashes($type) }}', {{ $id }}, '{{ $item['last_channel_id'] ?? '' }}')"
                            class="w-full text-left rounded-lg border border-gray-200 bg-white hover:bg-gray-50 px-4 py-3 flex items-center justify-between gap-3"
                        >
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-gray-900 truncate">
                                    {{ $title }}
                                </div>
                                <div class="text-xs text-gray-500 truncate">
                                    {{ $subtitle }} · {{ $last ? \Carbon\Carbon::parse($last)->format('d.m.Y H:i') : '–' }}
                                    @if($summary)
                                        <span class="mx-1 text-gray-300">·</span>
                                        {{ \Illuminate\Support\Str::limit($summary, 80) }}
                                    @endif
                                </div>
                            </div>
                            <div class="shrink-0 inline-flex items-center justify-center min-w-7 h-7 px-2 rounded-full bg-gray-900 text-white text-xs font-semibold">
                                {{ $count }}
                            </div>
                        </button>
                    @empty
                        <div class="text-sm text-gray-500 italic p-6 text-center border border-dashed border-gray-200 rounded-lg bg-gray-50">
                            Keine neuen Nachrichten.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Tab: Kommunikation --}}
            <div x-show="activeTab === 'threads'" x-cloak class="h-full w-full flex gap-1 overflow-x-auto" @if(!$canUseThreads) style="display:none" @endif>
                {{-- Sidebar: Kanäle (nur Auswahl) --}}
                <div class="flex-shrink-0 h-full min-w-72 w-72 max-w-72 flex flex-col border-r border-gray-200 bg-white">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Kanäle</h3>
                    </div>
                    <div class="flex-1 overflow-y-auto p-3 space-y-3">
                        @foreach ($channels as $type => $group)
                            @php
                                $groupKey = \Illuminate\Support\Str::slug($type);
                                $groupLabel = $group[0]['group'] ?? ucfirst($type);
                            @endphp
                            <div class="space-y-1" wire:key="group-{{ $groupKey }}">
                                <div class="text-xs font-semibold text-gray-500">{{ $groupLabel }}</div>
                                <div class="flex flex-col gap-1">
                                    @foreach ($group as $channel)
                                        <button
                                            type="button"
                                            wire:click="selectChannel('{{ $channel['id'] }}')"
                                            wire:key="channel-item-{{ $groupKey }}-{{ $channel['id'] }}"
                                            class="w-full text-left px-3 py-2 rounded-md border transition
                                            {{ $activeChannelId === $channel['id'] ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 hover:border-blue-300 hover:bg-blue-50' }}"
                                        >
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="text-sm font-medium truncate">{{ $channel['label'] ?? 'Kein Label' }}</div>
                                                @if(($channel['unread_count'] ?? 0) > 0)
                                                    <span class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-gray-900 text-white text-[11px]">
                                                        {{ $channel['unread_count'] }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500 truncate">{{ $channel['id'] }}</div>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Dynamische Channel-Komponente (enthält Threads/Verlauf/Reply) --}}
                <div class="flex-1 h-full flex flex-col max-h-full overflow-y-auto bg-white">
                    @if ($activeChannelComponent)
                        @livewire($activeChannelComponent, $activeChannelPayload, key($activeChannelId))
                    @else
                        <div class="text-gray-500 text-sm italic p-8 text-center">
                            Kanal auswählen, um Threads im aktuellen Kontext zu sehen.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Tab: Kanäle verwalten --}}
            <div x-show="activeTab === 'channels'" x-cloak class="h-full w-full flex flex-col gap-4 p-4" @if(!$canManageChannels) style="display:none" @endif>
                {{-- Kopf --}}
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">Kanäle verwalten</h3>
                        <p class="text-sm text-gray-500">Anlegen, aktivieren, löschen.</p>
                    </div>
                    <div class="flex gap-2">
                        <select wire:model="newChannelType" class="input input-sm w-32">
                            <option value="email">E-Mail</option>
                        </select>
                        <input type="text" wire:model.defer="newChannelAddress" class="input input-sm w-52" placeholder="Adresse">
                        <input type="text" wire:model.defer="newChannelName" class="input input-sm w-44" placeholder="Name (optional)">
                        <label class="inline-flex items-center gap-1 text-xs text-gray-600">
                            <input type="checkbox" wire:model="newChannelDefault" class="input-checkbox">
                            Standard
                        </label>
                        <x-ui-button size="sm" wire:click="createChannel">Anlegen</x-ui-button>
                    </div>
                </div>

                {{-- Liste --}}
                <div class="flex-1 overflow-y-auto max-h-[70vh]">
                    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-3">
                        @forelse ($channels as $type => $group)
                            @php
                                $groupLabel = $group[0]['group'] ?? ucfirst($type);
                            @endphp
                            @foreach ($group as $channel)
                                <div class="border border-gray-200 rounded-md p-3 flex flex-col gap-2">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900">{{ $channel['label'] ?? 'Kein Label' }}</div>
                                            <div class="text-xs text-gray-500">{{ $channel['id'] }} · {{ $groupLabel }}</div>
                                        </div>
                                        @if($activeChannelId === $channel['id'])
                                            <x-ui-badge variant="primary" size="xs">Aktiv</x-ui-badge>
                                        @endif
                                    </div>
                                    <div class="flex gap-2">
                                        <x-ui-button size="xs" variant="secondary-outline" wire:click="selectChannel('{{ $channel['id'] }}')">Aktivieren</x-ui-button>
                                        <x-ui-button size="xs" variant="danger-outline" wire:click="deleteChannel('{{ $channel['id'] }}')">Löschen</x-ui-button>
                                    </div>
                                </div>
                            @endforeach
                        @empty
                            <div class="text-sm text-gray-500 italic">Keine Kanäle vorhanden.</div>
                        @endforelse
                    </div>
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