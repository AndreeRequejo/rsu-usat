function registerZoneMapPicker() {
    if (!window.Alpine) return false;
    if (window.__zoneMapPickerRegistered) {
        window.Alpine.data('zoneMapPicker', zoneMapPickerFactory);
        return true;
    }
    window.__zoneMapPickerRegistered = true;
    window.Alpine.data('zoneMapPicker', zoneMapPickerFactory);
    return true;
}

function zoneMapPickerFactory(config) {
    return {
        map: null,
        polygon: null,
        vertexMarkers: [],
        referenceLayers: [],
        drawing: false,
        coords: [],
        newLat: '',
        newLng: '',
        readOnly: config.readOnly ?? false,
        mapUniqueId: 'zmp-' + Math.random().toString(36).slice(2, 10),
        initialized: false,
        resizeObserver: null,

        init() {
            if (config.zoneId) {
                var stored = null;
                var storageKey = config.storageKey || 'coords-storage';
                var storageInput = document.getElementById(storageKey);
                if (storageInput && storageInput.value) {
                    try { stored = JSON.parse(storageInput.value); } catch(e) {}
                }
                if (stored && Array.isArray(stored) && stored.length > 0) {
                    this.coords = stored.map(function(c) { return { latitude: parseFloat(c.latitude), longitude: parseFloat(c.longitude) }; });
                } else if (Array.isArray(config.initialCoords) && config.initialCoords.length > 0) {
                    this.coords = config.initialCoords.map(function(c) { return { latitude: parseFloat(c.latitude), longitude: parseFloat(c.longitude) }; });
                }
            } else if (Array.isArray(config.initialCoords) && config.initialCoords.length > 0) {
                this.coords = config.initialCoords.map(function(c) { return { latitude: parseFloat(c.latitude), longitude: parseFloat(c.longitude) }; });
            }

            this.syncCoords();

            const $el = this.$root;
            $el.setAttribute('data-picker-id', this.mapUniqueId);
            this.$nextTick(() => {
                this.waitForMapElement();
            });
        },

        waitForMapElement(retries = 0) {
            const mapEl = this.$root.querySelector('[data-map-picker]');
            if (mapEl) {
                mapEl.id = this.mapUniqueId;
                this.initMap(mapEl);
                return;
            }
            if (retries < 30) {
                setTimeout(() => this.waitForMapElement(retries + 1), 100);
            }
        },

        initMap(mapEl) {
            if (this.initialized) return;
            if (!window.L) {
                setTimeout(() => this.initMap(mapEl), 200);
                return;
            }

            const center = config.center ?? { lat: -6.756, lng: -79.832, zoom: 14 };

            this.map = L.map(mapEl, {
                zoomControl: true,
                scrollWheelZoom: true,
            }).setView([center.lat, center.lng], center.zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(this.map);

            if (config.referenceZones && config.referenceZones.length > 0) {
                this.renderReferenceZones(config.referenceZones);
            }

            if (this.coords.length >= 3) {
                this.renderPolygon();
            } else if (this.coords.length > 0) {
                this.renderVertexMarkers();
            }

            this.map.on('click', (e) => {
                if (this.drawing && !this.readOnly) {
                    this.addVertex(e.latlng);
                }
            });

            this.initialized = true;

            this.$nextTick(() => this.scheduleInvalids());

            if (window.ResizeObserver) {
                this.resizeObserver = new ResizeObserver(() => {
                    if (this.map) this.map.invalidateSize();
                });
                this.resizeObserver.observe(mapEl);
            }
        },

        scheduleInvalids() {
            if (!this.map) return;
            let attempts = 0;
            const maxAttempts = 40;
            const checkSize = () => {
                attempts++;
                if (this.map) {
                    this.map.invalidateSize();
                    const size = this.map.getSize();
                    if (size.x > 50 && size.y > 50) {
                        if (this.coords.length >= 3 && this.polygon) {
                            try {
                                this.map.fitBounds(this.polygon.getBounds(), { padding: [30, 30], maxZoom: 16 });
                            } catch (e) {}
                        }
                        return;
                    }
                }
                if (attempts < maxAttempts) setTimeout(checkSize, 200);
            };
            checkSize();
        },

        renderReferenceZones(zones) {
            if (!this.map) return;
            zones.forEach((zone) => {
                if (!zone.coords || zone.coords.length < 3) return;
                const latlngs = zone.coords.map(c => [parseFloat(c.latitude), parseFloat(c.longitude)]);
                const refPolygon = L.polygon(latlngs, {
                    color: '#E53935',
                    weight: 2,
                    fillColor: '#E53935',
                    fillOpacity: 0.15,
                    dashArray: '6, 6',
                    interactive: false,
                }).addTo(this.map);
                refPolygon.bindTooltip(zone.name, { permanent: false, direction: 'center' });
                this.referenceLayers.push(refPolygon);
            });
        },

        renderPolygon() {
            if (!this.map) return;
            if (this.polygon) {
                this.map.removeLayer(this.polygon);
                this.polygon = null;
            }
            if (this.coords.length < 3) return;
            const latlngs = this.coords.map(c => [parseFloat(c.latitude), parseFloat(c.longitude)]);
            this.polygon = L.polygon(latlngs, {
                color: '#2E8B57',
                weight: 3,
                fillColor: '#2E8B57',
                fillOpacity: 0.25,
            }).addTo(this.map);
            this.renderVertexMarkers();
        },

        renderVertexMarkers() {
            if (!this.map) return;
            this.clearVertexMarkers();
            this.coords.forEach((c, idx) => {
                const marker = L.marker([parseFloat(c.latitude), parseFloat(c.longitude)], {
                    draggable: !this.readOnly,
                    icon: L.divIcon({
                        className: '',
                        html: '<div style="width:14px;height:14px;border-radius:50%;background:' + (idx === 0 ? '#F4C542' : '#2E8B57') + ';border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,0.3);cursor:grab;"></div>',
                        iconSize: [14, 14],
                        iconAnchor: [7, 7],
                    })
                }).addTo(this.map);

                marker.on('click', (e) => {
                    L.DomEvent.stopPropagation(e);
                    if (this.drawing && idx === 0 && this.coords.length >= 3) {
                        this.finishDrawing();
                    }
                });

                marker.on('drag', (e) => {
                    const pos = e.target.getLatLng();
                    this.coords = this.coords.map((coord, i) =>
                        i === idx ? { latitude: parseFloat(pos.lat.toFixed(7)), longitude: parseFloat(pos.lng.toFixed(7)) } : coord
                    );
                    if (this.polygon) {
                        const latlngs = this.coords.map(co => [co.latitude, co.longitude]);
                        this.polygon.setLatLngs(latlngs);
                    }
                });

                marker.on('dragend', () => {
                    this.syncCoords();
                });

                this.vertexMarkers.push(marker);
            });
        },

        clearVertexMarkers() {
            this.vertexMarkers.forEach(m => this.map && this.map.removeLayer(m));
            this.vertexMarkers = [];
        },

        startDrawing() {
            this.drawing = true;
            const $el = this.$root.querySelector('[data-map-picker]');
            if ($el) $el.style.cursor = 'crosshair';
        },

        cancelDrawing() {
            this.drawing = false;
            const $el = this.$root.querySelector('[data-map-picker]');
            if ($el) $el.style.cursor = '';
        },

        addVertex(latlng) {
            const newCoord = {
                latitude: parseFloat(latlng.lat.toFixed(7)),
                longitude: parseFloat(latlng.lng.toFixed(7)),
            };
            this.coords = [...this.coords, newCoord];
            if (this.coords.length >= 3) this.renderPolygon();
            else this.renderVertexMarkers();
            this.syncCoords();
        },

        addManualCoord() {
            const lat = parseFloat(this.newLat);
            const lng = parseFloat(this.newLng);
            if (isNaN(lat) || isNaN(lng)) return;
            if (lat < -90 || lat > 90 || lng < -180 || lng > 180) return;
            this.addVertex({ lat, lng });
            this.newLat = '';
            this.newLng = '';
        },

        updateCoord(idx, lat, lng) {
            const numLat = parseFloat(lat);
            const numLng = parseFloat(lng);
            if (isNaN(numLat) || isNaN(numLng)) return;
            this.coords = this.coords.map((c, i) =>
                i === idx ? { latitude: numLat, longitude: numLng } : c
            );
            if (this.coords.length >= 3) this.renderPolygon();
            else this.renderVertexMarkers();
            this.syncCoords();
        },

        removeCoord(idx) {
            this.coords = this.coords.filter((_, i) => i !== idx);
            if (this.coords.length >= 3) this.renderPolygon();
            else if (this.coords.length > 0) {
                if (this.polygon) { this.map.removeLayer(this.polygon); this.polygon = null; }
                this.renderVertexMarkers();
            } else {
                this.clearAll();
            }
            this.syncCoords();
        },

        deleteLastPoint() {
            if (this.coords.length === 0) return;
            this.coords = this.coords.slice(0, -1);
            if (this.coords.length >= 3) this.renderPolygon();
            else if (this.coords.length > 0) {
                if (this.polygon) { this.map.removeLayer(this.polygon); this.polygon = null; }
                this.renderVertexMarkers();
            } else {
                this.clearAll();
            }
            this.syncCoords();
        },

        finishDrawing() {
            if (this.coords.length < 3) return;
            this.drawing = false;
            const $el = this.$root.querySelector('[data-map-picker]');
            if ($el) $el.style.cursor = '';
            this.renderPolygon();
            try {
                if (this.polygon && this.map) {
                    this.map.fitBounds(this.polygon.getBounds(), { padding: [30, 30], maxZoom: 16 });
                }
            } catch (e) {}
        },

        clearAll() {
            this.coords = [];
            if (this.polygon && this.map) {
                this.map.removeLayer(this.polygon);
                this.polygon = null;
            }
            this.clearVertexMarkers();
            this.cancelDrawing();
            this.syncCoords();
        },

        syncCoords() {
            var jsonInput = document.getElementById('coords-json-input');
            if (jsonInput) {
                jsonInput.value = JSON.stringify(this.coords);
                jsonInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
            var storageKey = config.storageKey || 'coords-storage';
            var storage = document.getElementById(storageKey);
            if (storage) {
                storage.value = JSON.stringify(this.coords);
                storage.dispatchEvent(new CustomEvent('coords-synced', { bubbles: true }));
            }
        },
    };
}

if (typeof window !== 'undefined') {
    window.__registerZoneMapPicker = registerZoneMapPicker;
    window.zoneMapPickerFactory = zoneMapPickerFactory;

    document.addEventListener('alpine:init', () => {
        registerZoneMapPicker();
    });

    var tryRegister = () => {
        if (window.Alpine && !window.__zoneMapPickerRegistered) {
            registerZoneMapPicker();
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', tryRegister);
    } else {
        tryRegister();
    }

    if (window.Livewire) {
        document.addEventListener('livewire:navigated', tryRegister);
        document.addEventListener('livewire:init', tryRegister);

        Livewire.on('zone-edit-opened', function (data) {
            var storageKey = 'coords-storage-edit-' + data.zoneId;
            var input = document.getElementById(storageKey);
            if (input) input.value = '';
        });
    }
}

function initZoneViewer() {
    var mapEl = document.getElementById('zone-viewer-map');
    if (!mapEl) return;
    if (typeof L === 'undefined') { setTimeout(initZoneViewer, 150); return; }

    var payloadEl = document.getElementById('zone-viewer-payload');
    if (!payloadEl) return;

    if (window.__viewerMap) {
        window.__viewerMap.remove();
        window.__viewerMap = null;
    }

    var data = JSON.parse(payloadEl.textContent || '{}');
    var primaryZone = data.zone;
    var primaryCoords = data.coords || [];
    var referenceZones = data.referenceZones || [];

    var center = { lat: -6.756, lng: -79.832, zoom: 14 };
    var map = L.map(mapEl, { zoomControl: true, scrollWheelZoom: true })
        .setView([center.lat, center.lng], center.zoom);
    window.__viewerMap = map;

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    if (primaryCoords && primaryCoords.length >= 3) {
        var latlngs = primaryCoords.map(function (c) { return [c.latitude, c.longitude]; });
        var polygon = L.polygon(latlngs, {
            color: '#5E35B1',
            weight: 3,
            fillColor: '#5E35B1',
            fillOpacity: 0.35,
        }).addTo(map);
        var popupHtml = '<div style="font-family: sans-serif;">'
            + '<strong>' + (primaryZone ? primaryZone.name : '') + '</strong><br>'
            + '<small>Puntos: ' + primaryCoords.length + '</small><br>'
            + '<small>Residuos: ' + (primaryZone && primaryZone.average_waste ? primaryZone.average_waste : 'N/A') + ' kg</small>'
            + '</div>';
        polygon.bindPopup(popupHtml);
        polygon.on('click', function (e) {
            L.DomEvent.stopPropagation(e);
            polygon.openPopup(e.latlng);
        });
        latlngs.forEach(function (latlng) {
            L.circleMarker(latlng, {
                radius: 4,
                color: '#FFFFFF',
                weight: 2,
                fillColor: '#5E35B1',
                fillOpacity: 1,
            }).addTo(map);
        });
        try { map.fitBounds(polygon.getBounds(), { padding: [50, 50], maxZoom: 16 }); } catch (e) {}
    }

    if (referenceZones && referenceZones.length > 0) {
        var allBounds = [];
        referenceZones.forEach(function (zone) {
            if (!zone.coords || zone.coords.length < 3) return;
            var latlngs = zone.coords.map(function (c) { return [c.latitude, c.longitude]; });
            var refPolygon = L.polygon(latlngs, {
                color: '#9E9E9E',
                weight: 2,
                fillColor: '#9E9E9E',
                fillOpacity: 0.15,
                dashArray: '5, 5',
            }).addTo(map);
            refPolygon.bindTooltip(zone.name, { permanent: false, direction: 'center' });
            allBounds.push(latlngs);
        });
        if (!primaryZone && allBounds.length > 0) {
            var flat = allBounds.flat();
            if (flat.length > 0) {
                try { map.fitBounds(flat, { padding: [50, 50], maxZoom: 16 }); } catch (e) {}
            }
        }
    }

    var checkAttempts = 0;
    var checkSize = function () {
        checkAttempts++;
        map.invalidateSize();
        var size = map.getSize();
        if (size.x > 50 && size.y > 50) {
            if (primaryCoords && primaryCoords.length >= 3) {
                try {
                    var fg = L.featureGroup();
                    fg.addLayer(L.polygon(primaryCoords.map(function (c) { return [c.latitude, c.longitude]; })));
                    map.fitBounds(fg.getBounds(), { padding: [50, 50], maxZoom: 16 });
                } catch (e) {}
            }
            return;
        }
        if (checkAttempts < 40) setTimeout(checkSize, 200);
    };
    setTimeout(checkSize, 50);

    if (window.ResizeObserver) {
        var ro = new ResizeObserver(function () { map.invalidateSize(); });
        ro.observe(mapEl);
    }
}

document.addEventListener('DOMContentLoaded', initZoneViewer);
document.addEventListener('livewire:navigated', initZoneViewer);
if (typeof Livewire !== 'undefined') {
    Livewire.hook('morph.updated', function () {
        initZoneViewer();
    });
}
