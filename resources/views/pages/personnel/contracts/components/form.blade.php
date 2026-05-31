<flux:modal name="contract-form" class="md:w-[720px] max-h-[90vh] overflow-y-auto">
    <form wire:submit="save" class="space-y-5" novalidate>
        <div class="flex items-center justify-between px-6 pt-4 pb-2">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Editar Contrato' : 'Nuevo Contrato' }}</flux:heading>
                <flux:text class="mt-1 text-sm text-[#666666]">Complete los campos obligatorios.</flux:text>
            </div>
        </div>

        <div class="px-6 space-y-4">
            <div class="space-y-2">
                <div class="relative">
                    <label class="block text-sm font-medium text-[#333333] mb-2">Personal</label>
                    <input
                        wire:model.live.debounce.300ms="employeeSearch"
                        type="text"
                        placeholder="Buscar por DNI o nombre"
                        class="w-full rounded-lg border border-[#A5D6A7] bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                        @if ($editingId) readonly @else required @endif
                    />

                    @if (!$editingId && mb_strlen($employeeSearch) >= 2 && !$employee_id)
                        <div class="mt-2 max-h-48 overflow-y-auto rounded-lg border border-[#A5D6A7] bg-white">
                            @forelse ($this->employeesForSelect as $employee)
                                <button
                                    type="button"
                                    wire:click="selectEmployee({{ $employee->id }})"
                                    class="flex w-full items-center justify-between px-3 py-2 text-left text-sm text-[#333333] hover:bg-[#A5D6A7]/30"
                                >
                                    <span>{{ $employee->last_name }} {{ $employee->first_name }} - {{ $employee->dni }}</span>
                                    <span class="text-xs text-[#666666]">{{ $employee->employeeType?->name }}</span>
                                </button>
                            @empty
                                <div class="px-3 py-2 text-sm text-[#666666]">Sin resultados.</div>
                            @endforelse
                            @php
                                $total = $this->employeesForSelectTotal;
                                $maxPage = max(1, (int) ceil($total / $this->employeesPerPage));
                            @endphp
                            @if ($total > $this->employeesPerPage)
                                <div class="flex items-center justify-between gap-2 border-t border-[#A5D6A7] px-3 py-2 text-xs text-[#666666]">
                                    <span>Pagina {{ $this->employeePage }} de {{ $maxPage }}</span>
                                    <div class="flex gap-2">
                                        <button
                                            type="button"
                                            wire:click="prevEmployeePage"
                                            @if ($this->employeePage <= 1) disabled @endif
                                            class="rounded-md border border-[#A5D6A7] px-2 py-1 text-[#333333] disabled:opacity-50"
                                        >
                                            Anterior
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="nextEmployeePage"
                                            @if ($this->employeePage >= $maxPage) disabled @endif
                                            class="rounded-md border border-[#A5D6A7] px-2 py-1 text-[#333333] disabled:opacity-50"
                                        >
                                            Siguiente
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
                <p class="text-xs text-[#666666]">Escriba al menos 2 letras para buscar empleados.</p>
                @if ($employee_id)
                    <p class="text-xs text-[#2E8B57]">Seleccionado: {{ $employeeSearch }}</p>
                @endif
                @error('employee_id') <span class="text-xs text-[#E53935]">{{ $message }}</span> @enderror
            </div>

            <div class="flex gap-4">
                <div class="flex-1">
                    <flux:select wire:model.live="contract_type" label="Tipo de contrato" required>
                        <option value="">Seleccionar...</option>
                        @foreach ($this->contractTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="flex-1">
                    <flux:select wire:model="department_id" label="Departamento">
                        <option value="">Seleccionar...</option>
                        @foreach ($this->departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </flux:select>
                </div>
            </div>

            <div class="flex gap-4">
                <div class="flex-1">
                    <flux:input wire:model="start_date" type="date" label="Fecha de inicio" required />
                </div>
                <div class="flex-1">
                    @if ($contract_type === 'Temporal')
                        <flux:input
                            wire:model="end_date"
                            type="date"
                            label="Fecha de finalizacion"
                            required
                        />
                    @else
                        <flux:input
                            type="date"
                            label="Fecha de finalizacion"
                            readonly
                        />
                    @endif
                </div>
            </div>

            <div class="flex gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-[#333333] mb-2">Salario</label>
                    <div class="flex items-center rounded-lg border border-[#A5D6A7] bg-white">
                        <span class="px-3 text-sm text-[#2E8B57] font-semibold">S/</span>
                        <input
                            wire:model="salary"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class="w-full border-0 bg-transparent py-2.5 text-sm focus:outline-none focus:ring-0"
                        />
                    </div>
                    @error('salary') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                </div>
                <div class="flex-1">
                    <flux:input
                        wire:model.number="probation_period_months"
                        type="number"
                        min="0"
                        label="Periodo de prueba (meses)"
                        placeholder="0"
                    />
                </div>
            </div>

            <div class="flex items-center gap-3">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" wire:model="is_active" class="sr-only peer">
                    <div class="relative w-11 h-6 bg-[#CCCCCC] peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[#2E8B57] rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:inset-s-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#2E8B57]"></div>
                    <span class="ms-3 text-sm font-medium text-[#333333]">Activo</span>
                </label>
            </div>
        </div>

        <div class="px-6 py-4 bg-[#F5F5F5] border-t border-[#E0E0E0] flex justify-end gap-3">
            <flux:button type="button" variant="ghost" wire:click="closeFormModal" class="text-[#333333]">Cancelar</flux:button>
            <flux:button type="submit" variant="primary" class="bg-[#2E8B57] text-white hover:bg-[#257046]">
                {{ $editingId ? 'Actualizar' : 'Guardar' }}
            </flux:button>
        </div>
    </form>
</flux:modal>
