<div>
    <x-slot:title>
        Servers | Coolify
    </x-slot>
    <div class="flex items-center gap-2">
        <h1>Servers</h1>
        @can('createAnyResource')
            <x-modal-input buttonTitle="+ Add" title="New Server" :closeOutside="false">
                <livewire:server.create />
            </x-modal-input>
        @endcan
    </div>
    <div class="subtitle">All your servers are here.</div>

    @if ($servers->isEmpty())
        <div>
            <div>No servers found. Without a server, you won't be able to do much.</div>
        </div>
    @else
        {{-- Alpine.js server manager --}}
        <div x-data="serverManager(
            @js($servers->map(fn ($s) => [
                'id' => $s->id,
                'uuid' => $s->uuid,
                'name' => $s->name,
                'description' => $s->description,
                'href' => route('server.show', ['server_uuid' => $s->uuid]),
                'isReachable' => (bool) $s->settings->is_reachable,
                'isUsable' => (bool) $s->settings->is_usable,
                'forceDisabled' => (bool) $s->settings->force_disabled,
            ])->values()->toArray()),
            @js($initialSort),
            @js($initialView),
            @js($initialCustomOrder)
        )" x-cloak>

            {{-- Toolbar: Search + Sort + View Toggle --}}
            <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">
                {{-- Search input --}}
                <div class="flex-1 max-w-md">
                    <input type="text"
                           x-model.debounce.150ms="search"
                           placeholder="Search servers..."
                           class="w-full input" />
                </div>

                {{-- Sort dropdown + View toggle --}}
                <div class="flex items-center gap-2">
                    {{-- Sort dropdown --}}
                    <div class="relative" x-data="{ sortOpen: false }">
                        <button @click="sortOpen = !sortOpen"
                                @click.outside="sortOpen = false"
                                class="flex items-center gap-1.5 px-3 py-2 text-sm border rounded-md bg-white dark:bg-coolgray-100 border-neutral-300 dark:border-coolgray-300 hover:bg-neutral-50 dark:hover:bg-coolgray-200 transition-colors"
                                title="Sort order">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5" />
                            </svg>
                            <span x-text="sortLabel" class="hidden sm:inline"></span>
                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <div x-show="sortOpen"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 z-20 w-44 mt-1 bg-white dark:bg-coolgray-100 rounded-md shadow-lg border border-neutral-200 dark:border-coolgray-200">
                            <button @click="setSort('name_asc'); sortOpen = false"
                                    class="flex items-center w-full gap-2 px-4 py-2 text-sm text-left hover:bg-neutral-100 dark:hover:bg-coolgray-200 rounded-t-md"
                                    :class="sortMode === 'name_asc' ? 'text-warning font-semibold' : ''">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h5.25m5.25-.75L17.25 9m0 0L21 12.75M17.25 9v12" />
                                </svg>
                                Name A-Z
                            </button>
                            <button @click="setSort('name_desc'); sortOpen = false"
                                    class="flex items-center w-full gap-2 px-4 py-2 text-sm text-left hover:bg-neutral-100 dark:hover:bg-coolgray-200"
                                    :class="sortMode === 'name_desc' ? 'text-warning font-semibold' : ''">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h5.25m5.25.75L17.25 18m0 0L21 14.25M17.25 18V6" />
                                </svg>
                                Name Z-A
                            </button>
                            <button @click="setSort('custom'); sortOpen = false"
                                    class="flex items-center w-full gap-2 px-4 py-2 text-sm text-left hover:bg-neutral-100 dark:hover:bg-coolgray-200 rounded-b-md"
                                    :class="sortMode === 'custom' ? 'text-warning font-semibold' : ''">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                </svg>
                                Custom
                            </button>
                        </div>
                    </div>

                    {{-- View toggle: Grid / List --}}
                    <div class="flex items-center border rounded-md border-neutral-300 dark:border-coolgray-300 overflow-hidden">
                        <button @click="setView('grid')"
                                class="p-2 transition-colors"
                                :class="viewMode === 'grid' ? 'bg-warning/20 text-warning' : 'bg-white dark:bg-coolgray-100 hover:bg-neutral-50 dark:hover:bg-coolgray-200'"
                                title="Grid view">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                            </svg>
                        </button>
                        <button @click="setView('list')"
                                class="p-2 transition-colors border-l border-neutral-300 dark:border-coolgray-300"
                                :class="viewMode === 'list' ? 'bg-warning/20 text-warning' : 'bg-white dark:bg-coolgray-100 hover:bg-neutral-50 dark:hover:bg-coolgray-200'"
                                title="List view">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Empty state when search has no matches --}}
            <template x-if="displayedServers.length === 0 && search.length > 0">
                <div class="flex flex-col items-center justify-center p-8 text-center">
                    <p class="text-neutral-600 dark:text-neutral-400">No server found matching "<span class="font-semibold" x-text="search"></span>".</p>
                </div>
            </template>

            {{-- GRID VIEW --}}
            <div x-show="viewMode === 'grid'" class="grid gap-4 lg:grid-cols-2">
                <template x-for="(server, index) in displayedServers" :key="server.id">
                    <div :class="[
                            'gap-2 border cursor-pointer coolbox group relative',
                            !server.isReachable || server.forceDisabled ? 'border-red-500' : ''
                         ]"
                         :draggable="sortMode === 'custom'"
                         @dragstart="onDragStart($event, index)"
                         @dragover.prevent="onDragOver($event, index)"
                         @dragend="onDragEnd()"
                         @drop.prevent="onDrop($event, index)"
                         :style="{ opacity: dragIndex === index ? 0.5 : 1, borderColor: (dropTarget === index && dropTarget !== dragIndex) ? 'var(--warning)' : '' }">

                        <a :href="server.href" wire:navigate.hover class="absolute inset-0"></a>

                        <div class="flex flex-1 mx-6">
                            {{-- Drag handle (only in custom sort mode) --}}
                            <div x-show="sortMode === 'custom'"
                                 class="relative z-10 flex items-center pr-3 -ml-3 cursor-grab active:cursor-grabbing"
                                 @mousedown.stop @touchstart.stop>
                                <svg class="w-5 h-5 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                    <circle cx="9" cy="5" r="1.5"/>
                                    <circle cx="15" cy="5" r="1.5"/>
                                    <circle cx="9" cy="12" r="1.5"/>
                                    <circle cx="15" cy="12" r="1.5"/>
                                    <circle cx="9" cy="19" r="1.5"/>
                                    <circle cx="15" cy="19" r="1.5"/>
                                </svg>
                            </div>

                            <div class="flex flex-col justify-center flex-1">
                                <div class="font-bold dark:text-white" x-text="server.name"></div>
                                <div class="description" x-text="server.description"></div>
                                <div class="flex gap-1 text-xs text-error">
                                    <template x-if="!server.isReachable">
                                        <span>Not reachable</span>
                                    </template>
                                    <template x-if="!server.isReachable && !server.isUsable">
                                        <span>&amp;</span>
                                    </template>
                                    <template x-if="!server.isUsable">
                                        <span>Not usable by Coolify</span>
                                    </template>
                                    <template x-if="server.forceDisabled">
                                        <span>Disabled by the system</span>
                                    </template>
                                </div>
                            </div>
                            <div class="flex-1"></div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- LIST VIEW --}}
            <div x-show="viewMode === 'list'" class="flex flex-col gap-1">
                <template x-for="(server, index) in displayedServers" :key="server.id">
                    <div :class="[
                            'relative flex items-center gap-3 px-4 py-3 border rounded-md cursor-pointer bg-white dark:bg-coolgray-100 hover:bg-neutral-50 dark:hover:bg-coolgray-200 transition-colors group',
                            !server.isReachable || server.forceDisabled ? 'border-red-500' : 'border-neutral-200 dark:border-coolgray-300'
                         ]"
                         :draggable="sortMode === 'custom'"
                         @dragstart="onDragStart($event, index)"
                         @dragover.prevent="onDragOver($event, index)"
                         @dragend="onDragEnd()"
                         @drop.prevent="onDrop($event, index)"
                         :style="{ opacity: dragIndex === index ? 0.5 : 1, borderColor: (dropTarget === index && dropTarget !== dragIndex) ? 'var(--warning)' : '' }">

                        <a :href="server.href" wire:navigate.hover class="absolute inset-0"></a>

                        {{-- Drag handle --}}
                        <div x-show="sortMode === 'custom'"
                             class="relative z-10 flex items-center cursor-grab active:cursor-grabbing shrink-0"
                             @mousedown.stop @touchstart.stop>
                            <svg class="w-4 h-4 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                <circle cx="9" cy="5" r="1.5"/>
                                <circle cx="15" cy="5" r="1.5"/>
                                <circle cx="9" cy="12" r="1.5"/>
                                <circle cx="15" cy="12" r="1.5"/>
                                <circle cx="9" cy="19" r="1.5"/>
                                <circle cx="15" cy="19" r="1.5"/>
                            </svg>
                        </div>

                        {{-- Server name --}}
                        <div class="font-bold dark:text-white truncate min-w-[120px] max-w-[200px] sm:max-w-[250px]" x-text="server.name"></div>

                        {{-- Description --}}
                        <div class="flex-1 text-sm truncate text-neutral-500 dark:text-neutral-400" x-text="server.description || ''"></div>

                        {{-- Status indicators --}}
                        <div class="flex gap-1 text-xs text-error shrink-0">
                            <template x-if="!server.isReachable">
                                <span>Not reachable</span>
                            </template>
                            <template x-if="!server.isReachable && !server.isUsable">
                                <span>&amp;</span>
                            </template>
                            <template x-if="!server.isUsable">
                                <span>Not usable</span>
                            </template>
                            <template x-if="server.forceDisabled">
                                <span>Disabled</span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    @endif

    @isset($error)
        <div class="text-center text-error">
            <span>{{ $error }}</span>
        </div>
    @endisset
</div>

<script>
function serverManager(serversData, initialSort, initialView, initialCustomOrder) {
    return {
        // Raw server data passed from x-data
        servers: serversData,

        // State
        search: '',
        sortMode: initialSort,
        viewMode: initialView,
        customOrder: initialCustomOrder,

        // Drag-and-drop state
        dragIndex: null,
        dropTarget: null,

        // Computed: label for sort button
        get sortLabel() {
            const labels = { name_asc: 'A-Z', name_desc: 'Z-A', custom: 'Custom' };
            return labels[this.sortMode] || 'A-Z';
        },

        // Computed: filtered and sorted servers
        get displayedServers() {
            let items = [...this.servers];

            // Filter by search
            if (this.search.trim().length > 0) {
                const q = this.search.toLowerCase().trim();
                items = items.filter(s =>
                    (s.name && s.name.toLowerCase().includes(q)) ||
                    (s.description && s.description.toLowerCase().includes(q))
                );
            }

            // Sort
            if (this.sortMode === 'name_asc') {
                items.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
            } else if (this.sortMode === 'name_desc') {
                items.sort((a, b) => (b.name || '').localeCompare(a.name || ''));
            } else if (this.sortMode === 'custom') {
                const orderMap = {};
                this.customOrder.forEach((id, idx) => { orderMap[id] = idx; });
                items.sort((a, b) => {
                    const posA = orderMap[a.id] !== undefined ? orderMap[a.id] : 99999;
                    const posB = orderMap[b.id] !== undefined ? orderMap[b.id] : 99999;
                    return posA - posB;
                });
            }

            return items;
        },

        // Actions
        setSort(mode) {
            this.sortMode = mode;
            if (mode === 'custom' && this.customOrder.length === 0) {
                this.customOrder = this.displayedServers.map(s => s.id);
            }
            this.persistPreference();
        },

        setView(mode) {
            this.viewMode = mode;
            this.persistPreference();
        },

        persistPreference() {
            this.$wire.saveServerPreference(this.sortMode, this.viewMode, this.customOrder);
        },

        // Drag and drop handlers
        onDragStart(event, index) {
            if (this.sortMode !== 'custom') return;
            this.dragIndex = index;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', index);
        },

        onDragOver(event, index) {
            if (this.sortMode !== 'custom' || this.dragIndex === null) return;
            this.dropTarget = index;
        },

        onDrop(event, index) {
            if (this.sortMode !== 'custom' || this.dragIndex === null) return;

            const items = this.displayedServers.map(s => s.id);
            const draggedId = items[this.dragIndex];

            items.splice(this.dragIndex, 1);
            items.splice(index, 0, draggedId);

            this.customOrder = items;
            this.dragIndex = null;
            this.dropTarget = null;

            this.persistPreference();
        },

        onDragEnd() {
            this.dragIndex = null;
            this.dropTarget = null;
        },
    };
}
</script>
