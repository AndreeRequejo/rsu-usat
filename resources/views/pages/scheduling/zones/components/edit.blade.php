<flux:modal name="zone-edit" class="w-[1000px] max-w-[98vw] overflow-hidden flex flex-col" wire:close="closeEditModal">
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
    @endphp

    <form
        wire:key="edit-form-{{ $editingId ?? 'new' }}"
        x-data="{
            editCoords: @js($editCoords),
            editingIndex: null,
            editedLat: '',
            editedLng: '',
            init() {
                var self = this;
                var storageId = 'coords-storage-edit-{{ $editingId }}';
                document.addEventListener('coords-synced', function(e) {
                    if (e.target && e.target.id === storageId) {
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
                    if (typeof data.renderPolygon === 'function') data.renderPolygon();
                    if (typeof data.clearVertexMarkers === 'function' && data.coords.length < 3) {
                        data.clearVertexMarkers();
                        if (data.coords.length > 0) data.renderVertexMarkers();
                    }
                    if (data.coords.length >= 3 && typeof data.renderPolygon === 'function') {
                        data.renderPolygon();
                        try {
                            if (data.polygon && data.map) {
                                data.map.fitBounds(data.polygon.getBounds(), { padding: [30, 30], maxZoom: 16 });
                            }
                        } catch(ex) {}
                    }
                }
            }
        }"
        class="flex flex-col h-full"
    >
        <div class="px-6 pt-5 pb-3 border-b border-[#A5D6A7] flex items-center justify-between shrink-0">
            <div>
                <flux:heading size="lg">{{ __('Editar zona') }}</flux:heading>
                <flux:text class="mt-1 text-sm text-[#666666]">{{ __('Ajusta el perimetro en el mapa o modifica las coordenadas directamente.') }}</flux:text>
            </div>
        </div>

        <div class="flex-1 min-h-0 p-4 flex flex-col gap-3">
            <input type="hidden" id="coords-storage-edit-{{ $editingId }}" value='{{ $coordsJson }}' />

            <div class="flex-1 min-h-0">
                @livewire(\App\Livewire\Pages\Scheduling\Zones\ZoneMapPicker::class, [
                    'key' => 'edit-map-'.$editingId,
                    'coords' => $editCoords,
                    'zoneId' => $editingId,
                    'readOnly' => false,
                    'storageKey' => 'coords-storage-edit-' . $editingId,
                ])
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