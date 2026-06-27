<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-3xl font-bold text-[#2E8B57]">
            {{ __('Gestión de Mantenimientos') }}
        </h1>
        <p class="text-sm text-[#333333] mt-1">
            {{ __('Administración de la programación de mantenimientos de los vehículos.') }}
        </p>
    </div>

    <flux:button
        wire:click="openCreate"
        variant="primary"
        icon="plus-circle"
        class="bg-[#2E8B57]! text-white cursor-pointer hover:bg-[#257046]!"
    >
        {{ __('Nuevo Mantenimiento') }}
    </flux:button>
</div>

<div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] p-5 mb-6">
    <label class="block text-sm font-medium text-[#333333] mb-2">
        {{ __('Buscar por nombre') }}
    </label>
    <div class="flex gap-3">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-[#333333]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Buscar...') }}"
                class="w-full pl-10 pr-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
            />
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">
                    <th class="px-6 py-4 text-left">{{ __('Nombre') }}</th>
                    <th class="px-6 py-4 text-left">{{ __('Fecha de Inicio') }}</th>
                    <th class="px-6 py-4 text-left">{{ __('Fecha de Fin') }}</th>
                    <th class="px-6 py-4 text-center">{{ __('Horarios') }}</th>
                    <th class="px-6 py-4 text-right">{{ __('Acciones') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->mantenimientos as $i => $mant)
                    <tr wire:key="mant-{{ $mant->id }}"
                        class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                        <td class="px-6 py-4 text-sm font-semibold text-[#333333]">
                            {{ $mant->nombre }}
                        </td>
                        <td class="px-6 py-4 text-sm text-[#333333]">
                            {{ $mant->fecha_inicio->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-[#333333]">
                            {{ $mant->fecha_fin->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $mant->horarios_count }} {{ __('horario(s)') }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <button wire:click="showHorarios({{ $mant->id }})"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#1976D2] hover:bg-[#1976D2]/20 transition"
                                    title="{{ __('Horarios') }}" aria-label="{{ __('Horarios') }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </button>
                                <button wire:click="openEdit({{ $mant->id }})"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#F4C542] hover:bg-[#F4C542]/20 transition"
                                    title="{{ __('Editar') }}" aria-label="{{ __('Editar') }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.25 2.25 0 013.182 3.182l-9.193 9.193a4.5 4.5 0 01-1.897 1.13l-3.17 1.056 1.056-3.17a4.5 4.5 0 011.13-1.897l9.193-9.193z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 7.5L16.5 4.5" />
                                    </svg>
                                </button>
                                <button wire:click="confirmDelete({{ $mant->id }})"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#E53935] hover:bg-[#E53935]/20 transition"
                                    title="{{ __('Eliminar') }}" aria-label="{{ __('Eliminar') }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2M7 7l1 12a2 2 0 002 2h4a2 2 0 002-2l1-12" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-sm text-[#333333]">
                            {{ __('No hay mantenimientos registrados.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-6 py-4 border-t border-[#A5D6A7]">
        {{ $this->mantenimientos->links() }}
    </div>
</div>
