<flux:modal name="horario-form" wire:close="closeHorForm" class="md:w-130">
    <form wire:submit="saveHor" class="space-y-6" novalidate>
        <div>
            <flux:heading size="lg">
                {{ $horEditingId ? __('Editar horario') : __('Nuevo horario') }}
            </flux:heading>
            <flux:text class="mt-2">
                {{ __('Seleccione el vehículo, responsable y configure el horario. Los días se generarán automáticamente.') }}
            </flux:text>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:select wire:model.live="horVehiculoId" :label="__('Vehículo')">
                <option value="">{{ __('Seleccionar...') }}</option>
                @foreach ($this->vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}">{{ $vehicle->name }} ({{ $vehicle->plate }})</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="horResponsableId" :label="__('Responsable')">
                <option value="">{{ __('Seleccionar...') }}</option>
                @foreach ($this->employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:select wire:model.live="horTipo" :label="__('Tipo de mantenimiento')">
                <option value="">{{ __('Seleccionar...') }}</option>
                <option value="Preventivo">{{ __('Preventivo') }}</option>
                <option value="Limpieza">{{ __('Limpieza') }}</option>
                <option value="Reparacion">{{ __('Reparación') }}</option>
            </flux:select>

            <flux:select wire:model.live="horDiaSemana" :label="__('Día de la semana')">
                <option value="">{{ __('Seleccionar...') }}</option>
                <option value="Lunes">{{ __('Lunes') }}</option>
                <option value="Martes">{{ __('Martes') }}</option>
                <option value="Miercoles">{{ __('Miércoles') }}</option>
                <option value="Jueves">{{ __('Jueves') }}</option>
                <option value="Viernes">{{ __('Viernes') }}</option>
                <option value="Sabado">{{ __('Sábado') }}</option>
                <option value="Domingo">{{ __('Domingo') }}</option>
            </flux:select>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input type="time" wire:model.live="horHoraInicio" :label="__('Hora de Inicio')" />
            <flux:input type="time" wire:model.live="horHoraFin" :label="__('Hora de Fin')" />
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <flux:modal.close>
                <flux:button type="button" variant="ghost" wire:click="closeHorForm" class="text-[#333333]">
                    {{ __('Cancelar') }}
                </flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary" class="bg-[#2E8B57] text-white hover:bg-[#257046]">
                {{ $horEditingId ? __('Actualizar') : __('Guardar') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
