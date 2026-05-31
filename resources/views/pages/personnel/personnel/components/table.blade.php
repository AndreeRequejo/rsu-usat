<div class="overflow-x-auto">
    <table class="w-full">
        <thead>
            <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">
                <th class="px-4 py-3 text-left">Foto</th>
                <th class="px-4 py-3 text-left">DNI</th>
                <th class="px-4 py-3 text-left">Nombre</th>
                <th class="px-4 py-3 text-left">Tipo</th>
                <th class="px-4 py-3 text-left">Email</th>
                <th class="px-4 py-3 text-center">Estado</th>
                <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($this->employees as $i => $employee)
                @php
                $profileImage = $employee->employeeImages->where('profile', true)->first();
                $profileUrl = $profileImage ? url('storage/' . $profileImage->image) : null;
                @endphp
                <tr wire:key="employee-{{ $employee->id }}" class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-[#A5D6A7] overflow-hidden">
                            @if ($profileUrl)
                                <img src="{{ $profileUrl }}" alt="{{ $employee->first_name }}" class="w-full h-full object-cover">
                            @else
                                <span class="text-sm font-bold text-[#2E8B57]">{{ strtoupper(substr($employee->first_name, 0, 1)) }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm font-mono font-medium text-[#333333]">{{ $employee->dni }}</td>
                    <td class="px-4 py-3 text-sm font-medium text-[#333333]">{{ $employee->last_name }} {{ $employee->first_name }}</td>
                    <td class="px-4 py-3 text-sm text-[#333333]">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#A5D6A7]/50 text-[#2E8B57]">{{ $employee->employeeType->name }}</span>
                    </td>
                    <td class="px-4 py-3 text-sm text-[#333333]">{{ $employee->user->email }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $employee->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $employee->active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            <button wire:click="openEdit({{ $employee->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-[#F4C542] text-[#333333] hover:bg-[#D8AC34] transition" title="Editar" aria-label="Editar">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.25 2.25 0 013.182 3.182l-9.193 9.193a4.5 4.5 0 01-1.897 1.13l-3.17 1.056 1.056-3.17a4.5 4.5 0 011.13-1.897l9.193-9.193z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 7.5L16.5 4.5" />
                                </svg>
                            </button>
                            <button wire:click="confirmDelete({{ $employee->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-[#E53935] text-white hover:bg-[#C62828] transition" title="Eliminar" aria-label="Eliminar">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2M7 7l1 12a2 2 0 002 2h4a2 2 0 002-2l1-12" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center text-sm text-[#333333]">No hay empleados registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="px-4 py-3 border-t border-[#A5D6A7]">{{ $this->employees->links() }}</div>