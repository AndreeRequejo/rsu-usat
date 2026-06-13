@php
    $explorerPayload = json_encode([
        'zones' => $this->filteredZonesJson,
        'center' => $this->districtCenter,
    ]);
@endphp

<script type="application/json" id="zone-explorer-payload">{!! $explorerPayload !!}</script>

<div class="flex flex-col h-full">
    <div class="bg-[#1A237E] text-white px-6 py-4 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-bold uppercase tracking-wider">{{ __('Explorador de Zonas Geograficas') }}</h2>
                <div class="flex items-center gap-2 text-sm text-white/80 mt-0.5">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>{{ $this->locationName }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-1 min-h-0">
        <div class="w-80 border-r border-[#E0E0E0] overflow-y-auto bg-[#F5F5F5] shrink-0">
            <div class="p-4 border-b border-[#E0E0E0]">
                <h3 class="text-xs font-bold uppercase tracking-wider text-[#666666] flex items-center gap-1.5 mb-3">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    {{ __('Filtros de Busqueda') }}
                </h3>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-[#333333] mb-1">{{ __('Departamento') }}</label>
                        <select
                            wire:model.live="departmentId"
                            class="w-full px-3 py-2 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                        >
                            <option value="">{{ __('Seleccionar...') }}</option>
                            @foreach($this->departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[#333333] mb-1">{{ __('Provincia') }}</label>
                        <select
                            wire:model.live="provinceId"
                            class="w-full px-3 py-2 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                            {{ !$this->departmentId ? 'disabled' : '' }}
                        >
                            <option value="">{{ __('Seleccionar...') }}</option>
                            @foreach($this->provinces as $prov)
                                <option value="{{ $prov->id }}">{{ $prov->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[#333333] mb-1">{{ __('Distrito') }}</label>
                        <select
                            wire:model.live="districtId"
                            class="w-full px-3 py-2 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                            {{ !$this->provinceId ? 'disabled' : '' }}
                        >
                            <option value="">{{ __('Seleccionar...') }}</option>
                            @foreach($this->districts as $dist)
                                <option value="{{ $dist->id }}">{{ $dist->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="p-4 border-b border-[#E0E0E0]">
                <h3 class="text-xs font-bold uppercase tracking-wider text-[#666666] flex items-center gap-1.5 mb-3">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    {{ __('Estadisticas') }}
                </h3>

                <div class="space-y-2">
                    <div class="bg-[#2E8B57] text-white rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold">{{ $this->zonesStats['total'] }}</div>
                        <div class="text-[10px] uppercase tracking-wider opacity-80">{{ __('Zonas Encontradas') }}</div>
                    </div>
                    <div class="bg-[#1565C0] text-white rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold">{{ $this->zonesStats['active'] }}</div>
                        <div class="text-[10px] uppercase tracking-wider opacity-80">{{ __('Activas') }}</div>
                    </div>
                    <div class="bg-[#F4C542] text-[#333333] rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold">{{ $this->zonesStats['total_points'] }}</div>
                        <div class="text-[10px] uppercase tracking-wider opacity-80">{{ __('Total Puntos') }}</div>
                    </div>
                </div>
            </div>

            <div class="p-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-[#666666] flex items-center gap-1.5 mb-3">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    {{ __('Leyenda del Mapa') }}
                </h3>

                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-3 rounded bg-[#2E8B57]"></div>
                        <span class="text-xs text-[#333333]">{{ __('Zonas Activas') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-3 rounded border-2 border-[#1565C0] bg-[#1565C0]/10"></div>
                        <span class="text-xs text-[#333333]">{{ __('Distrito Seleccionado') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 relative">
            <div
                id="zone-explorer-map"
                wire:ignore
                style="height: 100%; min-height: 500px; width: 100%;"
            ></div>

            <div class="absolute bottom-4 left-4 z-[1000] bg-white/90 backdrop-blur-sm rounded-lg px-3 py-1.5 shadow-md border border-[#E0E0E0]">
                <span class="text-xs font-medium text-[#333333]" id="zone-count-badge">0 {{ __('zonas encontradas en esta ubicacion') }}</span>
            </div>
        </div>
    </div>

    <div class="px-6 py-3 bg-[#F5F5F5] border-t border-[#E0E0E0] flex justify-end shrink-0">
        <flux:button
            x-on:click="Flux.modal('zone-explorer').close()"
            variant="primary"
            class="bg-[#E53935]! text-white! cursor-pointer hover:bg-[#C62828]!"
            icon="x-mark"
        >
            {{ __('Cerrar') }}
        </flux:button>
    </div>
</div>

<script>
(function () {
    var EXPLORER_COLORS = ['#2E8B57', '#1565C0', '#E53935', '#F4C542', '#9C27B0', '#00838F', '#FF6F00', '#AD1457', '#283593', '#558B2F'];
    var mapInstance = null;
    var layerGroup = null;

    function initExplorerMap() {
        var mapEl = document.getElementById('zone-explorer-map');
        if (!mapEl) { setTimeout(initExplorerMap, 150); return; }
        if (typeof L === 'undefined') { setTimeout(initExplorerMap, 150); return; }

        if (mapInstance) {
            mapInstance.remove();
            mapInstance = null;
        }

        var payloadEl = document.getElementById('zone-explorer-payload');
        if (!payloadEl) return;

        var data = JSON.parse(payloadEl.textContent || '{}');
        var zones = data.zones || [];
        var center = data.center || { lat: -6.756, lng: -79.832 };

        mapInstance = L.map(mapEl, { zoomControl: false })
            .setView([center.lat, center.lng], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(mapInstance);

        L.control.zoom({ position: 'topright' }).addTo(mapInstance);

        layerGroup = L.featureGroup().addTo(mapInstance);

        renderZones(zones);

        checkMapSize(mapEl, zones);

        if (window.ResizeObserver) {
            var ro = new ResizeObserver(function () { if (mapInstance) mapInstance.invalidateSize(); });
            ro.observe(mapEl);
        }

        updateBadge(zones.length);
    }

    function renderZones(zones) {
        if (!layerGroup || !mapInstance) return;
        layerGroup.clearLayers();

        zones.forEach(function (zone, idx) {
            if (!zone.coords || zone.coords.length < 3) return;

            var color = EXPLORER_COLORS[idx % EXPLORER_COLORS.length];
            var latLngs = zone.coords.map(function (c) { return [c.lat, c.lng]; });

            var polygon = L.polygon(latLngs, {
                color: color,
                weight: 3,
                opacity: 0.9,
                fillColor: color,
                fillOpacity: 0.15,
            }).addTo(layerGroup);

            var statusBadge = zone.status === 'active'
                ? '<span style="background:#2E8B57;color:#fff;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;">Activo</span>'
                : '<span style="background:#E53935;color:#fff;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;">Inactivo</span>';

            var location = [zone.district, zone.province, zone.department].filter(Boolean).join(', ');

            polygon.bindPopup(
                '<div style="min-width:180px;">' +
                    '<div style="font-weight:700;font-size:14px;color:#333;margin-bottom:4px;">' + zone.name + '</div>' +
                    '<div style="margin-bottom:6px;">' + statusBadge + '</div>' +
                    (zone.description ? '<div style="font-size:12px;color:#666;margin-bottom:4px;">' + zone.description + '</div>' : '') +
                    (location ? '<div style="font-size:11px;color:#999;">' + location + '</div>' : '') +
                '</div>'
            );
        });

        if (layerGroup.getLayers().length > 0) {
            try { mapInstance.fitBounds(layerGroup.getBounds(), { padding: [30, 30], maxZoom: 16 }); } catch (e) {}
        }
    }

    function checkMapSize(mapEl, zones) {
        var attempts = 0;
        var check = function () {
            attempts++;
            if (!mapInstance) return;
            mapInstance.invalidateSize();
            var size = mapInstance.getSize();
            if (size.x > 50 && size.y > 50) {
                if (layerGroup && layerGroup.getLayers().length > 0) {
                    try { mapInstance.fitBounds(layerGroup.getBounds(), { padding: [30, 30], maxZoom: 16 }); } catch (e) {}
                }
                return;
            }
            if (attempts < 40) setTimeout(check, 200);
        };
        setTimeout(check, 50);
    }

    function updateBadge(count) {
        var badge = document.getElementById('zone-count-badge');
        if (badge) badge.textContent = count + ' zonas encontradas en esta ubicacion';
    }

    document.addEventListener('modal-show', function (e) {
        if (e.detail.name === 'zone-explorer') {
            setTimeout(initExplorerMap, 200);
        }
    });

    if (window.Livewire) {
        Livewire.on('zones-updated', function (data) {
            var zonesArray = data && data.zones ? data.zones : [];
            var payloadEl = document.getElementById('zone-explorer-payload');
            if (!payloadEl) return;
            payloadEl.textContent = JSON.stringify({ zones: zonesArray, center: { lat: -6.756, lng: -79.832 } });
            renderZones(zonesArray);
            updateBadge(zonesArray.length);
            if (mapInstance) {
                checkMapSize(document.getElementById('zone-explorer-map'), zonesArray);
            }
        });
    }

    if (document.getElementById('zone-explorer-map') && document.getElementById('zone-explorer-map').offsetParent !== null) {
        setTimeout(initExplorerMap, 100);
    }
})();
</script>
