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
                    ['label' => 'Kommunikation', 'value' => 'threads'],
                    ['label' => 'Kanäle verwalten', 'value' => 'channels'],
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
        <div class="h-full w-full flex flex-col">
            {{-- Tab: Kommunikation --}}
            <div x-show="activeTab === 'threads'" x-cloak class="h-full w-full flex flex-col">
                <div class="flex flex-1 overflow-hidden divide-x divide-gray-200">
                    {{-- Spalte 1: Kanäle (nur Auswahl) --}}
                    <div class="w-72 flex-shrink-0 flex flex-col bg-white">
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
                                                <div class="text-sm font-medium truncate">{{ $channel['label'] ?? 'Kein Label' }}</div>
                                                <div class="text-xs text-gray-500 truncate">{{ $channel['id'] }}</div>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Spalte 2: Threads im Kontext --}}
                    <div class="w-80 flex-shrink-0 flex flex-col bg-white">
                        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Threads</h3>
                            <x-ui-button size="xs" variant="secondary-outline" wire:click="$set('composeMode', true)">
                                Neu
                            </x-ui-button>
                        </div>
                        <div class="flex-1 overflow-y-auto p-3 space-y-2">
                            @php $threads = $this->threads; @endphp
                            @forelse ($threads as $thread)
                                @php 
                                    $latestMessage = $thread->timeline()->first();
                                    $isActive = $activeChannelId && $activeThread && $activeThread->id === $thread->id;
                                @endphp
                                <button
                                    type="button"
                                    wire:click="selectThread({{ $thread->id }}, {{ $latestMessage?->id ?? 'null' }}, '{{ $latestMessage?->direction ?? '' }}')"
                                    class="w-full text-left px-3 py-2 rounded-md border transition
                                    {{ $isActive ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 hover:border-blue-300 hover:bg-blue-50' }}"
                                >
                                    <div class="text-sm font-semibold truncate">{{ $thread->subject ?? 'Kein Betreff' }}</div>
                                    <div class="text-xs text-gray-500 flex items-center gap-1">
                                        @if($latestMessage)
                                            <span>{{ \Carbon\Carbon::parse($latestMessage->occurred_at)->format('d.m. H:i') }}</span>
                                            <span>·</span>
                                            <span class="truncate">{{ $latestMessage->direction === 'inbound' ? ($latestMessage->from ?? '') : ($latestMessage->to ?? '') }}</span>
                                        @endif
                                    </div>
                                </button>
                            @empty
                                <div class="text-xs text-gray-500 italic px-2">Keine Threads im aktuellen Kontext.</div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Spalte 3: Detail / Composer --}}
                    <div class="flex-1 flex flex-col bg-white">
                        @if ($composeMode)
                            <div class="border-b border-gray-200 px-4 py-3 flex items-center justify-between">
                                <div class="text-sm font-semibold text-gray-700">Neuer Thread</div>
                                <x-ui-button size="xs" variant="muted" wire:click="$set('composeMode', false)">Abbrechen</x-ui-button>
                            </div>
                            <div class="p-4 flex flex-col gap-3 overflow-y-auto">
                                <x-ui-input-text name="compose.to" label="Empfänger" wire:model.defer="compose.to" placeholder="E-Mail" />
                                <x-ui-input-text name="compose.subject" label="Betreff" wire:model.defer="compose.subject" placeholder="Betreff" />
                                <x-ui-input-textarea name="compose.body" label="Nachricht" wire:model.defer="compose.body" rows="10" />
                                <div class="flex justify-end">
                                    <x-ui-button variant="primary" wire:click="sendNewMessage">Senden</x-ui-button>
                                </div>
                            </div>
                        @elseif($activeThread)
                            @php 
                                $messages = $activeThread->timeline()->sortBy('occurred_at');
                                $count = $messages->count();
                                $first = $messages->first();
                                $lastMsg = $messages->last();
                            @endphp
                            <div class="border-b border-gray-200 px-4 py-3 flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-semibold text-gray-800">{{ $activeThread->subject ?? 'Kein Betreff' }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $count }} Nachrichten · Start: {{ $first ? \Carbon\Carbon::parse($first->occurred_at)->format('d.m.Y H:i') : '–' }} · Letzte: {{ $lastMsg ? \Carbon\Carbon::parse($lastMsg->occurred_at)->format('d.m.Y H:i') : '–' }}
                                    </div>
                                </div>
                                <x-ui-button size="xs" variant="secondary-outline" wire:click="startNewMessage">Neuer Thread</x-ui-button>
                            </div>
                            <div class="flex-1 overflow-y-auto p-4 space-y-4">
                                @forelse($messages as $message)
                                    <div class="border border-gray-200 rounded-md p-3 space-y-1">
                                        <div class="flex items-center justify-between text-xs text-gray-500">
                                            <div class="flex items-center gap-2">
                                                @if ($message->direction === 'inbound')
                                                    @svg('heroicon-o-arrow-down', 'w-4 h-4 text-blue-500')
                                                    <span>Von: {{ $message->from }}</span>
                                                @else
                                                    @svg('heroicon-o-arrow-up', 'w-4 h-4 text-gray-600')
                                                    <span>An: {{ $message->to }}</span>
                                                @endif
                                            </div>
                                            <span>{{ \Carbon\Carbon::parse($message->occurred_at)->format('d.m.Y H:i') }}</span>
                                        </div>
                                        <div class="prose prose-sm max-w-none text-sm leading-relaxed">
                                            {!! $message->html_body ?: nl2br(e($message->text_body)) !!}
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-sm text-gray-500 italic">Keine Nachrichten im Thread.</div>
                                @endforelse
                            </div>
                            <div class="border-t border-gray-200 p-4">
                                <label class="text-sm font-medium text-gray-700">Antwort</label>
                                <textarea 
                                    rows="4" 
                                    wire:model.defer="replyBody" 
                                    class="form-control w-full p-3 border border-gray-300 rounded-lg resize-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    placeholder="Ihre Antwort..."
                                ></textarea>
                                <div class="mt-3 flex justify-end">
                                    <x-ui-button variant="primary" wire:click="sendReply">Antwort senden</x-ui-button>
                                </div>
                            </div>
                        @else
                            <div class="flex-1 flex items-center justify-center text-sm text-gray-500 italic">
                                Kanal und Thread wählen oder neuen Thread starten.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tab: Kanäle verwalten --}}
            <div x-show="activeTab === 'channels'" x-cloak class="h-full w-full flex flex-col gap-4 p-4">
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