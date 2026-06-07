<div class="overflow-x-auto">
    <table class="w-full">
        <thead>
            <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">
                <th class="px-4 py-3 text-left">{{ __('Nombre') }}</th>
                <th class="px-4 py-3 text-left">{{ __('Distrito') }}</th>
                <th class="px-4 py-3 text-left">{{ __('Provincia') }}</th>
                <th class="px-4 py-3 text-left">{{ __('Departamento') }}</th>
                <th class="px-4 py-3 text-left">{{ __('Descripcion') }}</th>
                <th class="px-4 py-3 text-center">{{ __('Coordenadas') }}</th>
                <th class="px-4 py-3 text-center">{{ __('Estado') }}</th>
                <th class="px-4 py-3 text-center">{{ __('Fecha creacion') }}</th>
                <th class="px-4 py-3 text-right">{{ __('Acciones') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($this->zones as $i => $zone)
                <tr wire:key="zone-{{ $zone->id }}" class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                    <td class="px-4 py-3 text-sm font-bold text-[#333333] uppercase">{{ $zone->name }}</td>
                    <td class="px-4 py-3 text-sm text-[#333333]">{{ $zone->district->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm text-[#333333]">{{ $zone->district->province->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm text-[#333333]">{{ $zone->district->department->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm text-[#333333] max-w-xs truncate">{{ $zone->description ?: __('Sin descripcion') }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#A5D6A7]/50 text-[#2E8B57]">
                            {{ $zone->zone_coords_count }} {{ __('puntos') }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $zone->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $zone->status === 'active' ? __('Activo') : __('Inactivo') }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center text-xs text-[#666666]">
                        {{ $zone->created_at?->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            <button wire:click="openEdit({{ $zone->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#F4C542] hover:bg-[#F4C542]/20 transition" title="Editar" aria-label="Editar">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.25 2.25 0 013.182 3.182l-9.193 9.193a4.5 4.5 0 01-1.897 1.13l-3.17 1.056 1.056-3.17a4.5 4.5 0 011.13-1.897l9.193-9.193z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 7.5L16.5 4.5" />
                                </svg>
                            </button>
                            <button wire:click="confirmDelete({{ $zone->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#E53935] hover:bg-[#E53935]/20 transition" title="Eliminar" aria-label="Eliminar">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2M7 7l1 12a2 2 0 002 2h4a2 2 0 002-2l1-12" />
                                </svg>
                            </button>
                            <button wire:click="$dispatch('open-viewer', { id: {{ $zone->id }} })" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#2E8B57] hover:bg-[#2E8B57]/20 transition" title="Ver mapa" aria-label="Ver mapa">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-10 text-center text-sm text-[#333333]">
                        {{ __('No hay zonas registradas.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-[#A5D6A7]">
        {{ $this->zones->links() }}
    </div>
</div>
