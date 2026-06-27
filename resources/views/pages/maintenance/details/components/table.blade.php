<table class="w-full">
    <thead>
        <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">
            <th class="px-4 py-3 text-left">{{ __('Fecha') }}</th>
            <th class="px-4 py-3 text-left">{{ __('Observación') }}</th>
            <th class="px-4 py-3 text-center">{{ __('Imagen') }}</th>
            <th class="px-4 py-3 text-center">{{ __('Estado') }}</th>
            <th class="px-4 py-3 text-center">{{ __('Acciones') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($this->details as $i => $detail)
            <tr wire:key="detail-{{ $detail->id }}"
                class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                <td class="px-4 py-3 text-sm font-medium">{{ $detail->date->format('d/m/Y') }}</td>
                <td class="px-4 py-3 text-sm text-[#666666] max-w-xs truncate">
                    {{ $detail->observation ?? '-' }}
                </td>
                <td class="px-4 py-3 text-sm text-center">
                    @if ($detail->image_path)
                        <a href="{{ asset('storage/' . $detail->image_path) }}" target="_blank"
                           class="text-[#1976D2] hover:underline text-xs">
                            {{ __('Ver imagen') }}
                        </a>
                    @else
                        <span class="text-[#999999] text-xs">—</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-sm text-center">
                    @if ($detail->completed === true)
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100">
                            <flux:icon.check class="w-4 h-4 text-green-600" />
                        </span>
                    @elseif ($detail->completed === false)
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100">
                            <flux:icon.x-mark class="w-4 h-4 text-red-600" />
                        </span>
                    @else
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100">
                            <flux:icon.minus class="w-4 h-4 text-gray-400" />
                        </span>
                    @endif
                </td>
                <td class="px-4 py-3 text-sm text-center">
                    <button
                        wire:click="openEdit({{ $detail->id }})"
                        class="p-1.5 text-[#2E8B57] hover:bg-[#A5D6A7]/30 rounded-lg cursor-pointer transition"
                        title="{{ __('Editar') }}"
                    >
                        <flux:icon.pencil-square class="w-4 h-4" />
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-10 text-center text-sm text-[#666666]">
                    {{ __('No se encontraron detalles para este horario.') }}
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
<div class="px-4 py-3 border-t border-[#A5D6A7]">{{ $this->details->links() }}</div>
