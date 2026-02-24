<x-app-layout :title="$title ?? __('hiko.religions')">
    <x-page-lock
        scope="global"
        resource-type="religions_admin"
        :redirect-url="route('letters')"
        :read-only-on-deny="true" />
    <div
        x-data="religionManager()"
        x-init="init()"
    >
        <h1 class="text-2xl font-semibold mb-4">{{ __('hiko.religions') }}</h1>

        {{-- Religion Creation Modal --}}
        @livewire('religion-create-modal')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Religion Tree --}}
            <div>
                <div class="mb-2 flex items-center gap-2">
                    <input
                        x-ref="searchInput"
                        x-model="searchQuery"
                        @input.debounce.250ms="performSearch()"
                        type="text"
                        class="form-input w-full"
                        placeholder="{{ __('hiko.search') }}"
                    >
                    <button
                        @click="openCreateModal()"
                        type="button"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-full shadow-sm text-white bg-primary hover:bg-black focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary whitespace-nowrap"
                        :disabled="isLoading"
                    >
                        <svg class="h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        {{ __('hiko.create_religion') }}
                    </button>
                </div>
                <div x-ref="treeContainer" id="religion-tree" class="rounded border bg-white p-2"></div>
            </div>

            {{-- Religion Translations panel --}}
            <div class="rounded border bg-white p-4">
                <div
                    x-show="!currentNodeId"
                    class="text-sm text-gray-500 mb-2"
                >
                    {{ __('hiko.select_religion_node_to_edit') }}
                </div>

                <div
                    x-show="currentNodeId"
                    x-transition
                    class="space-y-4"
                >
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ __('hiko.czech') }}</label>
                            <input
                                x-model="translations.cs"
                                type="text"
                                class="form-input w-full"
                                placeholder="{{ __('hiko.name_in_czech') }}"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ __('hiko.english') }}</label>
                            <input
                                x-model="translations.en"
                                type="text"
                                class="form-input w-full"
                                placeholder="{{ __('hiko.name_in_english') }}"
                            >
                        </div>
                    </div>
                    <div class="flex">
                        <button
                            @click="saveTranslations()"
                            type="button"
                            class="px-4 py-2 mt-4 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 disabled:opacity-25 transition ease-in-out duration-150 w-full"
                            :disabled="isLoading"
                        >
                            {{ __('hiko.save') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jstree@3.3.12/dist/themes/default/style.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jstree@3.3.12/dist/jstree.min.js"></script>
    <script>
        function religionManager() {
            return {
                // State
                currentNodeId: null,
                translations: {
                    cs: '',
                    en: ''
                },
                searchQuery: '',
                isLoading: false,
                treeInstance: null,

                // Constants
                APP_LOCALE: '{{ app()->getLocale() }}',
                csrfToken: '{{ csrf_token() }}',

                // Initialize
                init() {
                    this.$nextTick(() => {  // when DOM ready
                        this.initializeTree();
                    });

                    window.addEventListener('religionCreated', (event) => {
                        this.handleReligionCreated(event.detail);
                    });
                },

                // Tree initialization
                async initializeTree() {
                    try {
                        const fullData = await this.loadFullTree();
                        const self = this;

                        // Ensure jQuery and jstree are loaded
                        if (typeof $ === 'undefined' || typeof $.jstree === 'undefined') {
                            console.error('jQuery or jsTree not loaded');
                            flashError("{{ __('hiko.failed_to_load_tree') }}");
                            return;
                        }

                        // Ensure DOM element exists
                        if (!this.$refs.treeContainer) {
                            console.error('Tree container not found');
                            flashError("{{ __('hiko.failed_to_load_tree') }}");
                            return;
                        }

                        this.treeInstance = $(this.$refs.treeContainer)
                            .jstree({
                                core: {
                                    check_callback: true,
                                    data: fullData,
                                    themes: {
                                        icons: false,
                                        stripes: true,
                                        responsive: true
                                    }
                                },
                                plugins: ['contextmenu', 'search', 'state', 'wholerow'],
                                contextmenu: {
                                    items: node => ({
                                        create: {
                                            label: "{{ __('hiko.add_child') }}",
                                            action: () => self.openCreateModalWithParent(node.id, node.text)
                                        },
                                        delete: {
                                            label: "{{ __('hiko.delete') }}",
                                            action: () => self.deleteNode(node.id)
                                        },
                                        toggle: {
                                            label: "{{ __('hiko.toggle_active') }}",
                                            action: () => self.toggleNode(node.id)
                                        }
                                    })
                                }
                            })
                            .on('select_node.jstree', async (_, data) => {
                                await self.onNodeSelect(data.node.id);
                            });
                    } catch (error) {
                        // console.error('Failed to initialize tree:', error);
                    }
                },

                // API: Load full tree data
                async loadFullTree() {
                    const res = await fetch(`{{ url('/religions/tree-full') }}?locale=${this.APP_LOCALE}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) throw new Error('Failed to load tree');
                    return res.json();
                },

                // Refresh tree and optionally keep selection
                async refreshTreeAndKeepSelection(selectedId = null) {
                    const data = await this.loadFullTree();
                    const api = $(this.$refs.treeContainer).jstree(true);

                    if (!api) {
                        console.error('jsTree API not available');
                        return;
                    }

                    // Get currently opened nodes before refresh (get_state for preserving expansion)
                    const state = api.get_state();

                    api.settings.core.data = data;

                    await new Promise(resolve => {
                        $(this.$refs.treeContainer).one('refresh.jstree', () => {
                            // Restore tree state (expansion)
                            if (state && state.core && state.core.open) {
                                state.core.open.forEach(nodeId => {
                                    if (nodeId !== '#') {
                                        api.open_node(String(nodeId), false, false);
                                    }
                                });
                            }

                            // Select and open path to the new/updated node
                            if (selectedId) {
                                // Open parent nodes to make the selected node visible
                                const parent = api.get_parent(String(selectedId));
                                if (parent && parent !== '#') {
                                    api.open_node(String(parent), false, false);
                                }
                                api.select_node(String(selectedId));
                            }
                            resolve();
                        });
                        api.refresh();
                    });
                },

                // Search functionality
                performSearch() {
                    if (this.treeInstance) {
                        $(this.$refs.treeContainer).jstree('search', this.searchQuery);
                    }
                },

                // Node selection handler
                async onNodeSelect(nodeId) {
                    this.currentNodeId = nodeId;
                    await this.loadTranslations(nodeId);
                },

                // Load translations for selected node
                async loadTranslations(id) {
                    try {
                        const res = await fetch(`{{ url('/religions') }}/${id}/translations`);
                        const json = await res.json();

                        this.translations.cs = json.cs?.name ?? '';
                        this.translations.en = json.en?.name ?? '';
                    } catch (error) {
                        // console.error('Failed to load translations:', error);
                    }
                },

                // Save translations
                async saveTranslations() {
                    if (!this.currentNodeId) return;

                    this.isLoading = true;
                    flashInfo("{{ __('hiko.saving') }}");

                    try {
                        const res = await fetch(`{{ url('/religions') }}/${this.currentNodeId}/translations`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken
                            },
                            body: JSON.stringify({
                                cs: { name: this.translations.cs || null, slug: null },
                                en: { name: this.translations.en || null, slug: null }
                            })
                        });

                        if (!res.ok) {
                            flashError("{{ __('hiko.save_failed') }}");
                            return;
                        }

                        await this.refreshTreeAndKeepSelection(this.currentNodeId);
                        flashSuccess("{{ __('hiko.saved') }}");
                    } catch (error) {
                        console.error('Failed to save translations:', error);
                        flashError("{{ __('hiko.save_failed') }}");
                    } finally {
                        this.isLoading = false;
                    }
                },

                // Open create modal via Alpine event
                openCreateModal() {
                    // Clear any pre-selected parent
                    window.Livewire.find(
                        document.querySelector('[wire\\:id]')?.getAttribute('wire:id')
                    )?.set('parentId', null);
                    window.Livewire.find(
                        document.querySelector('[wire\\:id]')?.getAttribute('wire:id')
                    )?.set('parentLabel', '');

                    this.$dispatch('open-religion-modal');
                },

                // Open create modal with pre-selected parent
                openCreateModalWithParent(parentId, parentText) {
                    // Set the parent in Livewire component
                    const modalComponent = window.Livewire.find(
                        document.querySelector('[wire\\:id]')?.getAttribute('wire:id')
                    );
                    if (modalComponent) {
                        modalComponent.set('parentId', parseInt(parentId));
                        modalComponent.set('parentLabel', parentText);
                    } else {
                        console.error('Modal component not found!');
                    }

                    this.$dispatch('open-religion-modal');
                },

                // Handle religion created event from Livewire modal
                async handleReligionCreated(detail) {
                    const religionId = detail.religionId;
                    if (religionId) {
                        // Prevent duplicate handling if already processing
                        if (this.isLoading) {
                            return;
                        }
                        this.isLoading = true;

                        await this.refreshTreeAndKeepSelection(String(religionId));
                        flashSuccess("{{ __('hiko.religion_created') }}");

                        this.isLoading = false;
                    }
                },

                // Delete node
                async deleteNode(id) {
                    if (!confirm('{{ __("hiko.confirm_delete") }}')) return;

                    this.isLoading = true;

                    try {
                        const res = await fetch(`{{ url('/religions') }}/${id}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': this.csrfToken }
                        });

                        if (res.status === 204) {
                            await this.refreshTreeAndKeepSelection(null);
                            this.clearPanel();
                            flashSuccess("{{ __('hiko.deleted') }}");
                            return;
                        }

                        if (res.status === 422) {
                            const json = await res.json().catch(() => ({}));

                            if (json.blocking) {
                                let msg = (json.message || "{{ __('hiko.cannot_delete_religion_references') }}") + '<br>' +
                                    "{{ __('hiko.religion_used_in_identities') }}<br><br>";

                                msg += Object.values(json.blocking).map(group => {
                                    const meta = group.tenant || {};
                                    const domain = meta.domain ?
                                        `<strong>${meta.domain} (${group.urls ? group.urls.length : '?'})</strong>` :
                                        '<em>?</em>';
                                    const urls = (group.urls || [])
                                        .map(u => `<a href="${u}" class="ml-4 text-sm text-blue-500 underline" target="_blank">${u}</a>`)
                                        .join('<br>');
                                    return `${domain}<br>${urls}`;
                                }).join('<br><br>');

                                flashHTML(msg, 'error', false);
                            } else {
                                flashError(json.message || "{{ __('hiko.cannot_delete_religion') }}");
                            }
                            return;
                        }

                        const txt = await res.text();
                        flashError("{{ __('hiko.cannot_delete_religion') }}: " + txt);
                    } catch (error) {
                        console.error('Failed to delete node:', error);
                        flashError("{{ __('hiko.cannot_delete_religion') }}");
                    } finally {
                        this.isLoading = false;
                    }
                },

                // Toggle node active status
                async toggleNode(id) {
                    this.isLoading = true;

                    try {
                        const res = await fetch(`{{ url('/religions') }}/${id}`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken
                            },
                            body: JSON.stringify({
                                toggle_active: true,
                                cascade: true
                            })
                        });

                        if (res.ok) {
                            await this.refreshTreeAndKeepSelection(id);
                            flashSuccess("{{ __('hiko.status_updated') }}");
                        } else {
                            const txt = await res.text();
                            flashError("{{ __('hiko.status_update_failed') }}: " + txt);
                        }
                    } catch (error) {
                        console.error('Failed to toggle node:', error);
                        flashError("{{ __('hiko.status_update_failed') }}");
                    } finally {
                        this.isLoading = false;
                    }
                },

                // Clear panel
                clearPanel() {
                    this.currentNodeId = null;
                    this.translations.cs = '';
                    this.translations.en = '';
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
