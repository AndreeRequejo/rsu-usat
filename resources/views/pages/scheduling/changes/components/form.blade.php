<flux:modal
    name="change-form"
    class="w-[900px] max-w-[98vw] max-h-[95vh] overflow-hidden flex flex-col"
    wire:close="closeFormModal"
>
    <div class="flex flex-col h-full">
        <div class="px-6 py-4 border-b border-[#A5D6A7] shrink-0 flex items-center justify-between" style="background-color: #2E8B57;">
            <div class="flex items-center gap-2 text-white">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                </svg>
                <flux:heading size="lg" class="text-white">
                    {{ __('Cambio Masivo') }}
                </flux:heading>
            </div>
        </div>

        <div class="overflow-y-auto px-6 py-5 space-y-4 flex-1">
            @if (!$showConfirmModal)
                {{-- Formulario --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-[#333333] mb-1">
                            {{ __('Fecha de Inicio') }} <span class="text-[#E53935]">*</span>
                        </label>
                        <input type="date" wire:model="massive_start_date" class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]" />
                        @error('massive_start_date') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#333333] mb-1">
                            {{ __('Fecha de Fin') }} <span class="text-[#E53935]">*</span>
                        </label>
                        <input type="date" wire:model="massive_end_date" class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]" />
                        @error('massive_end_date') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Zonas (Opcional)') }}</label>
                        <select wire:model="massive_zone_id" class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]">
                            <option value="">{{ __('Todas las zonas') }}</option>
                            @foreach ($this->zones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-[#999999] mt-1">{{ __('Dejar vacio para aplicar a todas las zonas') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#333333] mb-1">
                            {{ __('Tipo de Cambio') }} <span class="text-[#E53935]">*</span>
                        </label>
                        <select wire:model="massive_change_type" class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]">
                            <option value="">{{ __('Seleccionar...') }}</option>
                            <option value="turn">{{ __('Cambio de Turno') }}</option>
                            <option value="vehicle">{{ __('Cambio de Vehiculo') }}</option>
                            <option value="driver">{{ __('Cambio de Conductor') }}</option>
                            <option value="helper">{{ __('Cambio de Ocupante') }}</option>
                        </select>
                        @error('massive_change_type') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-[#333333] mb-1">
                            {{ __('Recurso a Reemplazar') }} <span class="text-[#E53935]">*</span>
                        </label>
                        <div x-show="$wire.massive_change_type === 'turn'" x-cloak>
                            <select wire:model.live="massive_old_resource_id" class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]">
                                <option value="">{{ __('Seleccionar turno...') }}</option>
                                @foreach ($this->shifts as $shift)
                                    <option value="{{ $shift->id }}">{{ $shift->name }} ({{ $shift->hour_in }} - {{ $shift->hour_out }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-show="$wire.massive_change_type === 'vehicle'" x-cloak>
                            <select wire:model.live="massive_old_resource_id" class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]">
                                <option value="">{{ __('Seleccionar vehiculo...') }}</option>
                                @foreach ($this->vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}">{{ $vehicle->name }} ({{ $vehicle->plate }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-show="$wire.massive_change_type === 'driver' || $wire.massive_change_type === 'helper'" x-cloak>
                            <select wire:model.live="massive_old_resource_id" class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]">
                                <option value="">{{ __('Seleccionar personal...') }}</option>
                                @foreach ($this->employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-show="$wire.massive_change_type === ''" x-cloak>
                            <select disabled class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-gray-100 text-sm">
                                <option>{{ __('Primero seleccione el tipo de cambio') }}</option>
                            </select>
                        </div>
                        @error('massive_old_resource_id') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#333333] mb-1">
                            {{ __('Nuevo Recurso') }} <span class="text-[#E53935]">*</span>
                        </label>
                        <div x-show="$wire.massive_change_type === 'turn'" x-cloak>
                            <select wire:model.live="massive_new_resource_id" class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]">
                                <option value="">{{ __('Seleccionar turno...') }}</option>
                                @foreach ($this->shifts as $shift)
                                    <option value="{{ $shift->id }}">{{ $shift->name }} ({{ $shift->hour_in }} - {{ $shift->hour_out }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-show="$wire.massive_change_type === 'vehicle'" x-cloak>
                            <select wire:model.live="massive_new_resource_id" class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]">
                                <option value="">{{ __('Seleccionar vehiculo...') }}</option>
                                @foreach ($this->vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}">{{ $vehicle->name }} ({{ $vehicle->plate }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-show="$wire.massive_change_type === 'driver' || $wire.massive_change_type === 'helper'" x-cloak>
                            <select wire:model.live="massive_new_resource_id" class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]">
                                <option value="">{{ __('Seleccionar personal...') }}</option>
                                @foreach ($this->employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-show="$wire.massive_change_type === ''" x-cloak>
                            <select disabled class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-gray-100 text-sm">
                                <option>{{ __('Primero seleccione el tipo de cambio') }}</option>
                            </select>
                        </div>
                        @error('massive_new_resource_id') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-[#333333] mb-1">
                            {{ __('Motivo Predefinido') }} <span class="text-[#E53935]">*</span>
                        </label>
                        <select wire:model="massive_reason_preset" class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]">
                            <option value="">{{ __('Seleccionar...') }}</option>
                            @foreach ($this->reasonPresets as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('massive_reason_preset') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Descripcion Adicional (Opcional)') }}</label>
                        <input type="text" wire:model="massive_reason_detail" placeholder="{{ __('Complemento al motivo predefinido') }}" class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-[#333333] mb-1">
                        {{ __('Descripcion Completa del Cambio') }} <span class="text-[#E53935]">*</span>
                    </label>
                    <textarea wire:model="massive_reason_full" rows="2" class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]" placeholder="{{ __('Este campo se completa automaticamente con el motivo seleccionado + detalles adicionales') }}"></textarea>
                    @error('massive_reason_full') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                </div>
            @else
                {{-- Resumen de confirmación --}}
                <div class="text-center mb-4">
                    <flux:heading size="lg" class="text-[#333333]">{{ __('Resumen de la operacion') }}</flux:heading>
                    <flux:text class="text-sm text-[#666666]">{{ __('Revise cuidadosamente los detalles antes de proceder') }}</flux:text>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <h4 class="text-sm font-bold text-[#1976D2] mb-3 flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 12a7.5 7.5 0 0015 0m-15 0a7.5 7.5 0 1115 0m-15 0H3m16.5 0H21m-1.5 0H3m16.5 0H21" /></svg>
                            {{ __('Configuracion General') }}
                        </h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-[#666666]">{{ __('Tipo de Cambio:') }}</span> <span class="font-medium">{{ match($massive_change_type) { 'turn' => 'Cambio de Turno', 'vehicle' => 'Cambio de Vehiculo', 'driver' => 'Cambio de Conductor', 'helper' => 'Cambio de Ocupante', default => $massive_change_type } }}</span></div>
                            <div class="flex justify-between"><span class="text-[#666666]">{{ __('Periodo:') }}</span></div>
                            <div class="flex justify-between pl-4"><span class="text-[#666666]">{{ __('Inicio:') }}</span> <span class="text-green-600">{{ $massive_start_date }}</span></div>
                            <div class="flex justify-between pl-4"><span class="text-[#666666]">{{ __('Fin:') }}</span> <span class="text-green-600">{{ $massive_end_date }}</span></div>
                            <div class="flex justify-between"><span class="text-[#666666]">{{ __('Ambito de Aplicacion:') }}</span> <span class="font-medium text-[#F4C542]">{{ $massive_zone_id ? ($this->zones->firstWhere('id', $massive_zone_id)?->name ?? 'Zona especifica') : 'Todas las zonas' }}</span></div>
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <h4 class="text-sm font-bold text-[#1976D2] mb-3 flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 13.5a3 3 0 11-6 0 3 3 0 016 0zM9 13.5V9.75A2.25 2.25 0 0111.25 7.5h.75A2.25 2.25 0 0114.25 9.75v3.75" /></svg>
                            {{ __('Gestion de Recursos') }}
                        </h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-[#666666]">{{ __('Recurso a Reemplazar:') }}</span> <span class="text-red-600">
                                @if ($massive_change_type === 'turn') {{ $this->shifts->firstWhere('id', $massive_old_resource_id)?->name ?? '-' }} @elseif ($massive_change_type === 'vehicle') {{ $this->vehicles->firstWhere('id', $massive_old_resource_id)?->name ?? '-' }} @else {{ $this->employees->firstWhere('id', $massive_old_resource_id)?->first_name ?? '-' }} {{ $this->employees->firstWhere('id', $massive_old_resource_id)?->last_name ?? '' }} @endif
                            </span></div>
                            <div class="flex justify-between"><span class="text-[#666666]">{{ __('Nuevo Recurso:') }}</span> <span class="text-green-600">
                                @if ($massive_change_type === 'turn') {{ $this->shifts->firstWhere('id', $massive_new_resource_id)?->name ?? '-' }} @elseif ($massive_change_type === 'vehicle') {{ $this->vehicles->firstWhere('id', $massive_new_resource_id)?->name ?? '-' }} @else {{ $this->employees->firstWhere('id', $massive_new_resource_id)?->first_name ?? '-' }} {{ $this->employees->firstWhere('id', $massive_new_resource_id)?->last_name ?? '' }} @endif
                            </span></div>
                            <div class="flex justify-between"><span class="text-[#666666]">{{ __('Motivo Predefinido:') }}</span> <span class="font-medium">{{ $massive_reason_preset }}</span></div>
                        </div>
                    </div>
                </div>

                @if ($previewAffectedCount > 0)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-start gap-2">
                            <svg class="h-5 w-5 text-yellow-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                            <div>
                                <p class="text-sm font-bold text-yellow-800">{{ __('Advertencia del Sistema') }}</p>
                                <p class="text-sm text-yellow-700">
                                    {{ __('Esta operacion modificara') }} <strong>{{ $previewAffectedCount }}</strong> {{ __('programacion(es) existente(s). La accion es irreversible y requiere confirmacion expresa.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-sm text-red-700">{{ __('No se encontraron programaciones que coincidan con los criterios seleccionados.') }}</p>
                    </div>
                @endif
            @endif
        </div>

        <div class="px-6 py-4 bg-[#F5F5F5] border-t border-[#E0E0E0] flex justify-end gap-3 shrink-0">
            @if (!$showConfirmModal)
                <flux:button type="button" variant="ghost" wire:click="closeFormModal" class="text-[#333333]">
                    {{ __('Cancelar') }}
                </flux:button>
                <flux:button
                    type="button"
                    variant="primary"
                    class="bg-[#1976D2] text-white hover:bg-[#1565C0]"
                    icon="check"
                    wire:click="previewChanges"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-75"
                >
                    <span wire:loading.remove wire:target="previewChanges">{{ __('Guardar') }}</span>
                    <span wire:loading wire:target="previewChanges">{{ __('Procesando...') }}</span>
                </flux:button>
            @else
                <flux:button type="button" variant="ghost" wire:click="cancelConfirm" class="text-[#333333]">
                    {{ __('Volver') }}
                </flux:button>
                <flux:button
                    type="button"
                    variant="primary"
                    class="bg-[#1976D2] text-white hover:bg-[#1565C0]"
                    icon="check"
                    wire:click="applyMassiveChange"
                    :disabled="$previewAffectedCount === 0"
                >
                    {{ __('Confirmar') }}
                </flux:button>
            @endif
        </div>
    </div>
</flux:modal>
