<div class="overflow-x-auto">
    <div class="flex items-center justify-between px-4 py-3 border-b border-[#A5D6A7]">
        <div class="flex items-center gap-2 text-sm text-[#333333]">
            <span>{{ __('Mostrar') }}</span>
            <select wire:model.live="perPage" class="border border-[#A5D6A7] rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span>{{ __('registros') }}</span>
        </div>
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-[#999999]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Buscar...') }}"
                class="pl-9 pr-4 py-2 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57] w-64"
            />
        </div>
    </div>

    <table class="w-full">
        <thead>
            <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">
                <th class="px-4 py-3 text-left">{{ __('Fecha') }}</th>
                <th class="px-4 py-3 text-left">{{ __('Descripcion') }}</th>
                <th class="px-4 py-3 text-center">{{ __('Estado') }}</th>
                <th class="px-4 py-3 text-center">{{ __('Dia') }}</th>
                <th class="px-4 py-3 text-center">{{ __('Creacion') }}</th>
                <th class="px-4 py-3 text-right">{{ __('Acciones') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($this->holidays as $i => $holiday)
                <tr wire:key="holiday-{{ $holiday->id }}" class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                    <td class="px-4 py-3 text-sm font-bold text-[#333333]">{{ $holiday->date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-sm text-[#333333]">{{ $holiday->name }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $holiday->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $holiday->is_active ? __('Activo') : __('Inactivo') }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 capitalize">
                            {{ $holiday->day_name }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center text-xs text-[#666666]">
                        {{ $holiday->created_at?->format('d/m/Y') }}<br>
                        {{ $holiday->created_at?->format('H:i') }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            <button wire:click="openEdit({{ $holiday->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#F4C542] hover:bg-[#F4C542]/20 transition" title="Editar" aria-label="Editar">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.25 2.25 0 013.182 3.182l-9.193 9.193a4.5 4.5 0 01-1.897 1.13l-3.17 1.056 1.056-3.17a4.5 4.5 0 011.13-1.897l9.193-9.193z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 7.5L16.5 4.5" />
                                </svg>
                            </button>
                            <button wire:click="confirmDelete({{ $holiday->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#E53935] hover:bg-[#E53935]/20 transition" title="Eliminar" aria-label="Eliminar">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2M7 7l1 12a2 2 0 002 2h4a2 2 0 002-2l1-12" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-10 text-center text-sm text-[#333333]">
                        {{ __('No hay feriados registrados.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-[#A5D6A7]">
        {{ $this->holidays->links() }}
    </div>
</div>
