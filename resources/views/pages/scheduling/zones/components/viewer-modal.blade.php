<flux:modal name="zone-viewer" class="w-[1000px] max-w-[98vw] max-h-[95vh] overflow-hidden flex flex-col" wire:close="closeViewer">
    @if ($viewingId)
        @livewire(\App\Livewire\Pages\Scheduling\Zones\ZoneViewer::class, [
            'key' => 'viewer-'.$viewingId,
            'zoneId' => $viewingId,
        ])
    @else
        <div class="p-6">
            <h3 class="text-lg font-bold text-[#2E8B57] mb-2 flex items-center gap-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
                {{ __('Mapa de zonas') }}
            </h3>
            <p class="text-sm text-[#666666] mb-4">{{ __('Selecciona una zona de la lista para ver el detalle de su perimetro.') }}</p>
            @livewire(\App\Livewire\Pages\Scheduling\Zones\ZoneViewer::class, [
                'key' => 'viewer-all',
                'zoneId' => null,
            ])
        </div>
    @endif
</flux:modal>
