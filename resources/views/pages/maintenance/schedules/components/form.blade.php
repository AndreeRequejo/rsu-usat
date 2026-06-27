@php
    $days = config('maintenance.days_of_week', []);
    $types = config('maintenance.types', []);
@endphp

<flux:modal name="schedule-form" class="w-[550px] max-w-[98vw] max-h-[95vh] overflow-hidden flex flex-col"
    wire:close="closeFormModal">
    <div class="px-6 py-4 border-b border-[#A5D6A7] shrink-0">
        <flux:heading size="lg">
            {{ $editingId ? __('Editar Horario') : __('Nuevo Horario') }}
        </flux:heading>
    </div>

    <div class="overflow-y-auto px-6 py-5 space-y-4 flex-1">
        <div>
            <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Vehículo') }} *</label>
            <select
                wire:model="vehicle_id"
                class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
            >
                <option value="">{{ __('Seleccionar vehículo...') }}</option>
                @foreach ($this->vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}">{{ $vehicle->name }} ({{ $vehicle->plate }})</option>
                @endforeach
            </select>
            @error('vehicle_id')
                <p class="mt-1 text-xs text-[#E53935]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Responsable') }} *</label>
            <select
                wire:model="responsible_id"
                class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
            >
                <option value="">{{ __('Seleccionar responsable...') }}</option>
                @foreach ($this->employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                @endforeach
            </select>
            @error('responsible_id')
                <p class="mt-1 text-xs text-[#E53935]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Tipo de Mantenimiento') }} *</label>
            <select
                wire:model="maintenance_type"
                class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
            >
                <option value="">{{ __('Seleccionar tipo...') }}</option>
                @foreach ($types as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('maintenance_type')
                <p class="mt-1 text-xs text-[#E53935]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Día de la Semana') }} *</label>
            <select
                wire:model="day_of_week"
                class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
            >
                <option value="">{{ __('Seleccionar día...') }}</option>
                @foreach ($days as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('day_of_week')
                <p class="mt-1 text-xs text-[#E53935]">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Hora Inicio') }} *</label>
                <input
                    type="time"
                    wire:model="start_time"
                    class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                />
                @error('start_time')
                    <p class="mt-1 text-xs text-[#E53935]">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Hora Fin') }} *</label>
                <input
                    type="time"
                    wire:model="end_time"
                    class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                />
                @error('end_time')
                    <p class="mt-1 text-xs text-[#E53935]">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <div class="px-6 py-4 bg-[#F5F5F5] border-t border-[#E0E0E0] flex justify-end gap-3 shrink-0">
        <flux:button type="button" variant="ghost" wire:click="closeFormModal" class="cursor-pointer">
            {{ __('Cancelar') }}
        </flux:button>
        <flux:button type="button" variant="primary" wire:click="save" icon="check"
            class="bg-[#2E8B57]! text-white cursor-pointer hover:bg-[#257046]!">
            {{ __('Guardar') }}
        </flux:button>
    </div>
</flux:modal>
