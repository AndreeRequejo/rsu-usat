<table class="w-full">
    <thead>
        <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">
            <th class="px-4 py-3 text-left">{{ __('Nombre') }}</th>
            <th class="px-4 py-3 text-center">{{ __('Fecha Inicio') }}</th>
            <th class="px-4 py-3 text-center">{{ __('Fecha Fin') }}</th>
            <th class="px-4 py-3 text-center">{{ __('Horarios') }}</th>
            <th class="px-4 py-3 text-center">{{ __('Acciones') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($this->maintenances as $i => $maintenance)
            <tr wire:key="maintenance-{{ $maintenance->id }}"
                class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                <td class="px-4 py-3 text-sm font-medium">{{ $maintenance->name }}</td>
                <td class="px-4 py-3 text-sm text-center">{{ $maintenance->start_date->format('d/m/Y') }}</td>
                <td class="px-4 py-3 text-sm text-center">{{ $maintenance->end_date->format('d/m/Y') }}</td>
                <td class="px-4 py-3 text-sm text-center">
                    <a href="{{ route('maintenance.schedules.index', $maintenance->id) }}"
                       wire:navigate
                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-[#2E8B57] text-white text-xs font-medium rounded-lg hover:bg-[#257046] cursor-pointer transition">
                        <flux:icon.calendar-days class="w-4 h-4" />
                        {{ $maintenance->schedules_count }}
                    </a>
                </td>
                <td class="px-4 py-3 text-sm text-center">
                    <div class="flex items-center justify-center gap-2">
                        <button
                            wire:click="openEdit({{ $maintenance->id }})"
                            class="p-1.5 text-[#2E8B57] hover:bg-[#A5D6A7]/30 rounded-lg cursor-pointer transition"
                            title="{{ __('Editar') }}"
                        >
                            <flux:icon.pencil-square class="w-4 h-4" />
                        </button>
                        <button
                            wire:click="confirmDelete({{ $maintenance->id }})"
                            class="p-1.5 text-[#E53935] hover:bg-red-50 rounded-lg cursor-pointer transition"
                            title="{{ __('Eliminar') }}"
                        >
                            <flux:icon.trash class="w-4 h-4" />
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-10 text-center text-sm text-[#666666]">
                    {{ __('No se encontraron programaciones de mantenimiento.') }}
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
<div class="px-4 py-3 border-t border-[#A5D6A7]">{{ $this->maintenances->links() }}</div>
