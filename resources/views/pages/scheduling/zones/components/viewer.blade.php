@php
    $zoneData = $this->zone?->only(['id', 'name', 'description', 'average_waste', 'status']);
    $zoneCoords = $this->zone?->zoneCoords?->map(fn ($c) => [
        'latitude' => (float) $c->latitude,
        'longitude' => (float) $c->longitude,
    ])->toArray() ?? [];
    $referenceZones = $this->referenceZones?->toArray() ?? [];
    $viewerDataJson = json_encode([
        'zone' => $zoneData,
        'coords' => $zoneCoords,
        'referenceZones' => $referenceZones,
    ]);
@endphp

<script type="application/json" id="zone-viewer-payload">{!! $viewerDataJson !!}</script>

<div>
@if ($zoneId && $this->zone)
    <div class="px-6 pt-5 pb-3 border-b border-[#A5D6A7] flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold text-[#2E8B57] flex items-center gap-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
                {{ __('Mapa de la zona') }}
            </h2>
            <p class="text-xs text-[#666666] mt-1">{{ __('Visualizacion del perimetro registrado.') }}</p>
        </div>
        <flux:modal.close>
            <flux:button variant="ghost" type="button" class="text-[#333333]">{{ __('Cerrar') }}</flux:button>
        </flux:modal.close>
    </div>

    <div class="flex-1 overflow-y-auto grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-0">
        <aside class="bg-[#F5F5F5] p-5 space-y-3 overflow-y-auto">
            <div class="bg-gradient-to-br from-[#5E35B1] to-[#4527A0] rounded-lg p-4 text-white">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold uppercase truncate">{{ $this->zone->name }}</h3>
                </div>
                <p class="text-xs text-white/80 flex items-center gap-1">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    </svg>
                    {{ $this->zone->district->name ?? '-' }}
                </p>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div class="bg-[#1A237E] rounded-lg p-3 text-white">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider opacity-80">{{ __('Puntos') }}</span>
                        <svg class="h-4 w-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <p class="text-xl font-bold">{{ $this->zone->zoneCoords->count() }}</p>
                </div>
                <div class="bg-[#2E7D32] rounded-lg p-3 text-white">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider opacity-80">{{ __('Residuos') }}</span>
                        <svg class="h-4 w-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                        </svg>
                    </div>
                    <p class="text-lg font-bold">
                        {{ $this->zone->average_waste !== null ? number_format($this->zone->average_waste, 2) : 'N/A' }}
                        <span class="text-xs font-normal opacity-80">kg</span>
                    </p>
                </div>
                <div class="bg-[#F9A825] rounded-lg p-3 text-white">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider opacity-80">{{ __('Departamento') }}</span>
                        <svg class="h-4 w-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <p class="text-sm font-bold truncate">{{ $this->zone->district->department->name ?? '-' }}</p>
                </div>
                <div class="bg-[#00838F] rounded-lg p-3 text-white">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider opacity-80">{{ __('Area') }}</span>
                        <svg class="h-4 w-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                    </div>
                    <p class="text-sm font-bold">
                        {{ $this->zone->area !== null ? number_format($this->zone->area, 2) : '0.00' }}
                        <span class="text-xs font-normal opacity-80">KM²</span>
                    </p>
                </div>
            </div>

            <div>
                <h4 class="text-xs font-bold text-[#666666] uppercase tracking-wider mb-1 flex items-center gap-1">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                    </svg>
                    {{ __('Descripcion de la zona') }}
                </h4>
                <div class="bg-white rounded-lg p-3 text-xs text-[#333333] min-h-[60px] border border-[#E0E0E0]">
                    {{ $this->zone->description ?: __('Sin descripcion') }}
                </div>
            </div>

            <div>
                <h4 class="text-xs font-bold text-[#666666] uppercase tracking-wider mb-2 flex items-center gap-1">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    </svg>
                    {{ __('Coordenadas del poligono') }}
                </h4>
                <div class="bg-white rounded-lg border border-[#E0E0E0] overflow-hidden">
                    <table class="w-full text-xs">
                        <thead class="bg-[#F5F5F5]">
                            <tr>
                                <th class="px-2 py-1.5 text-left">#</th>
                                <th class="px-2 py-1.5 text-left">{{ __('Latitud') }}</th>
                                <th class="px-2 py-1.5 text-left">{{ __('Longitud') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->zone->zoneCoords as $i => $coord)
                                <tr class="border-t border-[#E0E0E0] {{ $i % 2 === 0 ? '' : 'bg-[#FAFAFA]' }}">
                                    <td class="px-2 py-1.5 text-[#666666]">{{ $i + 1 }}</td>
                                    <td class="px-2 py-1.5 font-mono">{{ number_format($coord->latitude, 6) }}</td>
                                    <td class="px-2 py-1.5 font-mono">{{ number_format($coord->longitude, 6) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-2 py-3 text-center text-[#999999]">{{ __('Sin coordenadas') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </aside>

        <div class="relative">
            <div
                id="zone-viewer-map"
                wire:ignore
                style="height: 100%; min-height: 500px; width: 100%;"
            ></div>
        </div>
    </div>
@else
    <div class="p-6 h-full flex flex-col">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold text-[#2E8B57] flex items-center gap-2">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    {{ __('Mapa general de zonas') }}
                </h2>
                <p class="text-xs text-[#666666] mt-1">{{ __('Visualiza todas las zonas registradas en el distrito.') }}</p>
            </div>
            <flux:modal.close>
                <flux:button variant="ghost" type="button" class="text-[#333333]">{{ __('Cerrar') }}</flux:button>
            </flux:modal.close>
        </div>
        <div
            id="zone-viewer-map"
            wire:ignore
            style="height: 100%; min-height: 500px; width: 100%; border-radius: 0.5rem;"
        ></div>
    </div>
@endif
</div>


