<div
    x-data="zoneMapPicker({
        initialCoords: @js($coords),
        center: @js($this->districtCenter),
        referenceZones: @js($this->referenceZones),
        readOnly: @js($readOnly),
        zoneId: @js($zoneId),
        storageKey: '{{ $storageKey }}',
    })"
    x-init="init()"
    class="h-full"
>
    @if (! $readOnly)
        <div class="flex gap-2 mb-3">
            <input
                type="text"
                x-model="newLat"
                placeholder="{{ __('Lat') }}"
                class="flex-1 px-2 py-1.5 border border-[#A5D6A7] rounded-lg bg-white text-xs focus:outline-none focus:ring-1 focus:ring-[#2E8B57] min-w-0"
            />
            <input
                type="text"
                x-model="newLng"
                @keydown.enter.prevent="addManualCoord()"
                placeholder="{{ __('Lng') }}"
                class="flex-1 px-2 py-1.5 border border-[#A5D6A7] rounded-lg bg-white text-xs focus:outline-none focus:ring-1 focus:ring-[#2E8B57] min-w-0"
            />
            <button type="button" @click="addManualCoord()" :disabled="!newLat || !newLng" class="px-2 py-1.5 bg-[#1565C0] text-white rounded-lg text-xs hover:opacity-90 disabled:opacity-40 shrink-0" title="{{ __('Agregar') }}">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </button>
            <button type="button" @click="clearAll()" title="{{ __('Limpiar') }}" class="px-2 py-1.5 bg-[#E53935] text-white rounded-lg text-xs hover:bg-[#C62828] shrink-0">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3" />
                </svg>
            </button>
        </div>
    @endif

    <div class="grid grid-cols-[240px_1fr] gap-3 h-full min-h-[340px]">
        <div class="space-y-2 overflow-y-auto pr-1">
            @if (! $readOnly)
                <div class="flex flex-wrap gap-1">
                    <button
                        type="button"
                        @click="startDrawing()"
                        :class="drawing ? 'bg-[#666666]' : 'bg-[#1565C0]'"
                        class="px-2 py-1.5 text-white rounded-lg text-xs hover:opacity-90 inline-flex items-center gap-1"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        {{ __('Trazar') }}
                    </button>
                    <button
                        type="button"
                        @click="finishDrawing()"
                        :disabled="!drawing || coords.length < 3"
                        class="px-2 py-1.5 bg-[#2E8B57] text-white rounded-lg text-xs hover:bg-[#257046] disabled:opacity-40 disabled:cursor-not-allowed inline-flex items-center gap-1"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ __('Cerrar') }}
                    </button>
                    <button
                        type="button"
                        @click="deleteLastPoint()"
                        :disabled="!drawing || coords.length === 0"
                        class="px-2 py-1.5 bg-[#F4C542] text-white rounded-lg text-xs hover:bg-[#D4A82F] disabled:opacity-40 disabled:cursor-not-allowed inline-flex items-center gap-1"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m3-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ __('Borrar') }}
                    </button>
                    <button
                        type="button"
                        @click="cancelDrawing()"
                        x-show="drawing"
                        class="px-2 py-1.5 bg-[#E53935] text-white rounded-lg text-xs hover:bg-[#C62828] inline-flex items-center gap-1"
                    >
                        {{ __('Cancelar') }}
                    </button>
                </div>

                <p class="text-[10px] text-[#999999]">
                    {{ __('Min.3 pts') }}:
                    <span x-text="coords.length" class="font-bold text-[#2E8B57]"></span>
                    <span x-show="drawing && coords.length > 0" class="text-[#1565C0]"> - {{ __('Clic mapa') }}</span>
                </p>

                <div x-show="coords.length > 0" class="border border-[#A5D6A7] rounded-lg divide-y divide-[#A5D6A7] max-h-36 overflow-y-auto">
                    <template x-for="(c, idx) in coords" :key="idx">
                        <div class="flex items-center gap-0.5 px-1.5 py-1 text-xs">
                            <span class="font-mono text-[#666666] w-4 shrink-0" x-text="(idx + 1)"></span>
                            <input
                                type="text"
                                x-model="c.latitude"
                                @change="updateCoord(idx, c.latitude, c.longitude)"
                                class="flex-1 min-w-0 px-1 py-0.5 border border-[#A5D6A7] rounded font-mono text-[10px]"
                            />
                            <input
                                type="text"
                                x-model="c.longitude"
                                @change="updateCoord(idx, c.latitude, c.longitude)"
                                class="flex-1 min-w-0 px-1 py-0.5 border border-[#A5D6A7] rounded font-mono text-[10px]"
                            />
                            <button type="button" @click="removeCoord(idx)" class="text-[#E53935] hover:bg-[#E53935]/10 p-0.5 rounded shrink-0" title="{{ __('X') }}">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            @endif
        </div>

        <div class="relative h-full min-h-[340px]">
            <div
                x-ref="mapContainer"
                wire:ignore
                data-map-picker
                class="w-full rounded-lg border border-[#A5D6A7]"
                style="height: 100%; z-index: 1; background: #e5e7eb;"
            ></div>
        </div>
    </div>
</div>
