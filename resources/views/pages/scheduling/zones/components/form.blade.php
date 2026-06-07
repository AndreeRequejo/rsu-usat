    @php
        $storageKey = $editingId ? 'coords-storage-edit-' . $editingId : 'coords-storage-' . $createSessionId;
    @endphp

    <flux:modal
        name="zone-form"
        class="w-[950px] max-w-[98vw] max-h-[95vh] overflow-hidden flex flex-col"
        wire:close="closeFormModal"
        data-zone-storage-key="{{ $storageKey }}"
    >
        <form
            x-data="{
                activeTab: 'data',
                saveZone: function() {
                    var coordsStr = '[]';
                    var pickerEl = document.querySelector('[data-picker-id]');
                    if (pickerEl && window.Alpine) {
                        var data = window.Alpine.$data(pickerEl);
                        if (data && data.coords) {
                            coordsStr = JSON.stringify(data.coords);
                        }
                    }
                    $wire.call('save', coordsStr);
                }
            }"
            class="flex flex-col h-full"
        >
            <input type="hidden" id="coords-json-input" wire:model="coords_json" />
            <input type="hidden" id="{{ $storageKey }}" value="{{ $editingId ? htmlspecialchars(json_encode($coords)) : '[]' }}" />
    
            <div class="px-6 pt-5 pb-3 border-b border-[#A5D6A7] shrink-0">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <flux:heading size="lg">
                        {{ $editingId ? __('Editar zona') : __('Nueva zona') }}
                    </flux:heading>
                    <flux:text class="mt-1 text-sm text-[#666666]">
                        {{ $editingId ? __('Modifica los datos de la zona.') : __('Registra una nueva zona para las programaciones.') }}
                    </flux:text>
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

        <div class="flex-1 overflow-hidden" x-show="activeTab === 'perimeter'" x-cloak wire:ignore>
            <div class="h-full px-6 py-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h4 class="text-sm font-bold text-[#2E8B57] flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            {{ __('Coordenadas del perimetro') }}
                        </h4>
                        <p class="text-xs text-[#666666]">{{ __('Define o ajusta las coordenadas del perimetro de la zona en el mapa.') }}</p>
                    </div>
                </div>

                @livewire(\App\Livewire\Pages\Scheduling\Zones\ZoneMapPicker::class, [
                    'key' => 'map-'.($editingId ?? 'new'),
                    'coords' => $coords,
                    'zoneId' => $editingId,
                    'readOnly' => false,
                    'storageKey' => $storageKey,
                ])

                @if (! $editingId)
                    <div class="bg-[#FFF8E1] border border-[#F4C542]/40 rounded-lg p-3 mt-3">
                        <div class="flex gap-2">
                            <svg class="h-5 w-5 text-[#F4C542] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-xs text-[#666666]">
                                {{ __('Minimo 3 coordenadas para definir un perimetro. Si aun no tienes las coordenadas, puedes continuar y agregarlas mas tarde.') }}
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="px-6 py-4 bg-[#F5F5F5] border-t border-[#E0E0E0] flex justify-end gap-3 shrink-0">
            <flux:button type="button" variant="ghost" wire:click="closeFormModal" class="text-[#333333]">
                {{ __('Cancelar') }}
            </flux:button>
            <flux:button
                type="button"
                variant="primary"
                class="bg-[#2E8B57] text-white hover:bg-[#257046]"
                icon="check"
                x-on:click="saveZone()"
            >
                {{ $editingId ? __('Cerrar') : __('Finalizar y guardar') }}
            </flux:button>
        </div>
    </form>
</flux:modal>