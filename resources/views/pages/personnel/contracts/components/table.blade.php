<div class="overflow-x-auto">
    <table class="w-full">
        <thead>
            <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">
                <th class="px-4 py-3 text-left">DNI</th>
                <th class="px-4 py-3 text-left">Empleado</th>
                <th class="px-4 py-3 text-left">Tipo de contrato</th>
                <th class="px-4 py-3 text-left">Inicio</th>
                <th class="px-4 py-3 text-left">Fin</th>
                <th class="px-4 py-3 text-left">Salario</th>
                <th class="px-4 py-3 text-left">Posicion</th>
                <th class="px-4 py-3 text-center">Activo</th>
                <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($this->contracts as $i => $contract)
                @php
                    $employee = $contract->employee;
                    $employeeType = $employee?->employeeType;
                @endphp
                <tr wire:key="contract-{{ $contract->id }}" class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                    <td class="px-4 py-3 text-sm font-mono font-medium text-[#333333]">
                        {{ $employee?->dni ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-sm font-medium text-[#333333]">
                        {{ $employee ? $employee->last_name . ' ' . $employee->first_name : __('Sin personal') }}
                    </td>
                    <td class="px-4 py-3 text-sm text-[#333333]">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#A5D6A7]/50 text-[#2E8B57]">
                            {{ $contract->contract_type }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-[#333333]">
                        {{ $contract->start_date?->format('d/m/Y') ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-[#333333]">
                        {{ $contract->end_date?->format('d/m/Y') ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-[#333333]">
                        S/ {{ number_format((float) $contract->salary, 2) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-[#333333]">
                        {{ $employeeType?->name ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $contract->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">
                            {{ $contract->is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            <button wire:click="openEdit({{ $contract->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#F4C542] hover:bg-[#F4C542]/20 transition" title="Editar" aria-label="Editar">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.25 2.25 0 013.182 3.182l-9.193 9.193a4.5 4.5 0 01-1.897 1.13l-3.17 1.056 1.056-3.17a4.5 4.5 0 011.13-1.897l9.193-9.193z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 7.5L16.5 4.5" />
                                </svg>
                            </button>
                            @if ($contract->is_active)
                                <button wire:click="confirmDeactivate({{ $contract->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#E53935] hover:bg-[#E53935]/20 transition" title="Desactivar" aria-label="Desactivar">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            @else
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md text-xs text-gray-400">—</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-10 text-center text-sm text-[#333333]">
                        No hay contratos registrados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="px-4 py-3 border-t border-[#A5D6A7]">
    {{ $this->contracts->links() }}
</div>
