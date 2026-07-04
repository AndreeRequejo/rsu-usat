<flux:modal name="zone-edit" class="w-[950px] max-w-[98vw] max-h-[95vh] overflow-hidden flex flex-col" wire:close="closeEditModal">
    @php
        $editCoords = [];
        if ($editingId) {
            $editCoords = \App\Models\ZoneCoord::where('zone_id', $editingId)
                ->orderBy('id')
                ->get()
                ->map(fn($c) => ['latitude' => (float) $c->latitude, 'longitude' => (float) $c->longitude])
                ->toArray();
        }
        $coordsJson = json_encode($editCoords);
        $storageKey = 'coords-storage-edit-' . $editingId;
    @endphp

    <form
        wire:key="edit-form-{{ $editingId ?? 'new' }}"
        x-data="{
            activeTab: 'data',
            editCoords: @js($editCoords),
            editingIndex: null,
            editedLat: '',
            editedLng: '',
            init() {
                var self = this;
                var sid = '{{ $storageKey }}';
                document.addEventListener('coords-synced', function(e) {
                    if (e.target && e.target.id === sid) {
                        try {
                            var parsed = JSON.parse(e.target.value);
                            if (Array.isArray(parsed)) self.editCoords = parsed;
                        } catch(ex) {}
                    }
                });
            },
            saveEditZone() {
                var pickerEl = document.querySelector('[data-picker-id]');
                var coordsStr;
                if (pickerEl && window.Alpine) {
                    var data = window.Alpine.$data(pickerEl);
                    if (data && data.coords && data.coords.length > 0) {
                        coordsStr = JSON.stringify(data.coords);
                    }
                }
                if (!coordsStr) coordsStr = JSON.stringify(this.editCoords);
                $wire.call('saveEdit', coordsStr);
            },
            startEditIndex(idx) {
                this.editingIndex = idx;
                this.editedLat = this.editCoords[idx].latitude;
                this.editedLng = this.editCoords[idx].longitude;
            },
            saveEditIndex(idx) {
                var lat = parseFloat(this.editedLat);
                var lng = parseFloat(this.editedLng);
                if (isNaN(lat) || isNaN(lng)) return;
                if (lat < -90 || lat > 90 || lng < -180 || lng > 180) return;
                this.editCoords[idx].latitude = lat;
                this.editCoords[idx].longitude = lng;
                this.editingIndex = null;
                this.syncToMapPicker();
            },
            cancelEditIndex() {
                this.editingIndex = null;
            },
            removeCoord(idx) {
                this.editCoords.splice(idx, 1);
                this.syncToMapPicker();
            },
            syncToMapPicker() {
                var pickerEl = document.querySelector('[data-picker-id]');
                if (pickerEl && window.Alpine) {
                    var data = window.Alpine.$data(pickerEl);
                    if (data) {
                        data.coords = JSON.parse(JSON.stringify(this.editCoords));
                    }
                }
            },
            pushToMap() {
                this.syncToMapPicker();
                var pickerEl = document.querySelector('[data-picker-id]');
                if (pickerEl && window.Alpine) {
                    var data = window.Alpine.$data(pickerEl);
                    if (typeof data.clearVertexMarkers === 'function') data.clearVertexMarkers();
                    if (data.coords.length >= 3 && typeof data.renderPolygon === 'function') {
                        data.renderPolygon();
                        try {
                            if (data.polygon && data.map) {
                                data.map.fitBounds(data.polygon.getBounds(), { padding: [30, 30], maxZoom: 16 });
                            }
                        } catch(ex) {}
                    } else if (data.coords.length > 0 && typeof data.renderVertexMarkers === 'function') {
                        data.renderVertexMarkers();
                    }
                }
            }
        }"
        class="flex flex-col h-full"
    >
        <input type="hidden" id="{{ $storageKey }}" value='{{ $coordsJson }}' />

        @error('coords')
            <div class="mx-6 mt-4 px-4 py-3 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
                <svg class="h-5 w-5 text-[#E53935] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p class="text-sm text-[#C62828]">{{ $message }}</p>
            </div>
        @enderror

        <div class="px-6 pt-5 pb-3 border-b border-[#A5D6A7] shrink-0">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <flux:heading size="lg">{{ __('Editar zona') }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-[#666666]">{{ __('Modifica los datos de la zona.') }}</flux:text>
                </div>
            </div>

            <div class="flex gap-0 border-b border-[#E0E0E0] -mx-6 px-6">
                <button
                    type="button"
                    @click="activeTab = 'data'"
                    :class="activeTab === 'data' ? 'border-b-2 border-[#2E8B57] text-[#2E8B57]' : 'text-[#999999] border-b-2 border-transparent'"
                    class="px-4 py-2 text-sm font-medium transition-colors cursor-pointer"
                >
                    <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                    </svg>
                    {{ __('Datos') }}
                </button>
                <button
                    type="button"
                    @click="activeTab = 'perimeter'"
                    :class="activeTab === 'perimeter' ? 'border-b-2 border-[#2E8B57] text-[#2E8B57]' : 'text-[#999999] border-b-2 border-transparent'"
                    class="px-4 py-2 text-sm font-medium transition-colors cursor-pointer"
                >
                    <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    {{ __('Perimetro') }}
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-hidden" x-show="activeTab === 'data'" x-cloak>
            <div class="overflow-y-auto px-6 py-5 space-y-4 h-full">
                <div>
                    <flux:input wire:model="name" :label="__('Nombre de la zona')" placeholder="{{ __('Ej: Zona Centro') }}" required />
                    @error('name') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <flux:select wire:model.live="department_id" :label="__('Departamento')" required>
                            <option value="">{{ __('Seleccionar...') }}</option>
                            @foreach ($this->departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </flux:select>
                        @error('department_id') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <flux:select wire:model.live="province_id" :label="__('Provincia')" required :disabled="! $department_id">
                            <option value="">{{ __('Seleccionar...') }}</option>
                            @foreach ($this->provinces as $province)
                                <option value="{{ $province->id }}">{{ $province->name }}</option>
                            @endforeach
                        </flux:select>
                        @error('province_id') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <flux:select wire:model.live="district_id" :label="__('Distrito')" required :disabled="! $province_id">
                            <option value="">{{ __('Seleccionar...') }}</option>
                            @foreach ($this->districts as $district)
                                <option value="{{ $district->id }}">{{ $district->name }}</option>
                            @endforeach
                        </flux:select>
                        @error('district_id') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <flux:textarea wire:model="description" :label="__('Descripcion')" rows="3" placeholder="{{ __('Agregue una descripcion de la zona') }}" />
                    @error('description') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <flux:input wire:model="average_waste" type="number" step="0.01" min="0" :label="__('Residuos promedio (kg)')" placeholder="Ej: 150.50" />
                        <p class="text-xs text-[#999999] mt-1">{{ __('Cantidad promedio de residuos en kilogramos por dia') }}</p>
                        @error('average_waste') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <flux:select wire:model="status" :label="__('Estado')">
                            <option value="active">{{ __('Activo') }}</option>
                            <option value="inactive">{{ __('Inactivo') }}</option>
                        </flux:select>
                        @error('status') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-hidden" x-show="activeTab === 'perimeter'" x-cloak>
            <div class="h-full px-6 py-5 flex flex-col gap-3">
                <div class="flex-1 min-h-0">
                    @livewire(\App\Livewire\Pages\Scheduling\Zones\ZoneMapPicker::class, [
                        'key' => 'edit-map-'.$editingId,
                        'coords' => $editCoords,
                        'zoneId' => $editingId,
                        'readOnly' => false,
                        'storageKey' => $storageKey,
                    ])
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-[#F5F5F5] border-t border-[#E0E0E0] flex justify-end gap-3 shrink-0">
            <flux:button type="button" variant="ghost" wire:click="closeEditModal" class="text-[#333333]">
                {{ __('Cancelar') }}
            </flux:button>
            <flux:button
                type="button"
                variant="primary"
                class="bg-[#2E8B57] text-white hover:bg-[#257046]"
                icon="check"
                x-on:click="saveEditZone()"
            >
                {{ __('Guardar cambios') }}
            </flux:button>
        </div>
    </form>
</flux:modal>