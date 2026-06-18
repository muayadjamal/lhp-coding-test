<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { computed } from 'vue';
import CollapsibleFilters from '@/components/events/CollapsibleFilters.vue';
import PinLoader from '@/components/events/PinLoader.vue';
import { useAppearance } from '@/composables/useAppearance';
import {
    readFiltersFromUrl,
    syncFiltersToUrl,
} from '@/composables/useEventFilters';
import EventsLayout from '@/layouts/EventsLayout.vue';
import { fetchClusters, fetchFilterOptions } from '@/lib/events';
import type { EventFilters as Filters, FilterOptions } from '@/lib/events';

defineOptions({ layout: EventsLayout });

const { resolvedAppearance } = useAppearance();
const isDark = computed(() => resolvedAppearance.value === 'dark');

const TILES = {
    light: 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
    dark: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
};

const filters = ref<Filters>(readFiltersFromUrl());
const options = ref<FilterOptions | null>(null);
let lastLocationKey = '';
const mapEl = ref<HTMLElement | null>(null);
const total = ref(0);
const loading = ref(false);
const mode = ref<'clusters' | 'points'>('clusters');

let map: L.Map | null = null;
let layer: L.LayerGroup | null = null;
let tiles: L.TileLayer | null = null;
let debounce: ReturnType<typeof setTimeout> | null = null;

function clusterIcon(count: number): L.DivIcon {
    const size = count > 500 ? 64 : count > 100 ? 54 : count > 25 ? 44 : 36;
    const label = count > 999 ? `${(count / 1000).toFixed(1)}k` : String(count);
    const bg = isDark.value ? '#f1f0ea' : '#262622';
    const fg = isDark.value ? '#201f1c' : '#ffffff';
    const ring = isDark.value
        ? 'rgba(241,240,234,0.18)'
        : 'rgba(38,38,34,0.16)';

    return L.divIcon({
        html: `<div class="flex h-full w-full cursor-pointer items-center justify-center rounded-full font-pin text-[13px] font-bold transition-transform duration-150 hover:scale-110" style="background:${bg};color:${fg};box-shadow:0 0 0 5px ${ring}">${label}</div>`,
        className: '!flex',
        iconSize: [size, size],
    });
}

function pointIcon(featured: boolean): L.DivIcon {
    const bg = featured ? '#e60023' : isDark.value ? '#f1f0ea' : '#262622';
    const border = isDark.value && !featured ? '#201f1c' : '#ffffff';
    const ring = featured
        ? 'box-shadow:0 0 0 3px rgba(230,0,35,0.25),0 1px 4px rgba(0,0,0,0.35);'
        : 'box-shadow:0 1px 4px rgba(0,0,0,0.35);';

    return L.divIcon({
        html: `<div class="h-4 w-4 rounded-full border-2 transition-transform duration-150 hover:scale-125" style="background:${bg};border-color:${border};${ring}"></div>`,
        className: '!flex',
        iconSize: [16, 16],
    });
}

function escapeHtml(s: string): string {
    const div = document.createElement('div');
    div.textContent = s;

    return div.innerHTML;
}

async function refresh() {
    if (!map || !layer) {
        return;
    }

    loading.value = true;
    const b = map.getBounds();
    const bounds = {
        north: b.getNorth(),
        south: b.getSouth(),
        east: b.getEast(),
        west: b.getWest(),
    };

    try {
        const res = await fetchClusters(filters.value, bounds, map.getZoom());
        layer.clearLayers();
        total.value = res.total;
        mode.value = res.mode;

        if (res.mode === 'clusters') {
            for (const c of res.clusters ?? []) {
                const marker = L.marker([c.lat, c.lng], {
                    icon: clusterIcon(c.count),
                });
                marker.on('click', () =>
                    map?.flyTo(
                        [c.lat, c.lng],
                        Math.min((map?.getZoom() ?? 3) + 3, 13),
                        { duration: 0.6 },
                    ),
                );
                marker.addTo(layer);
            }
        } else {
            for (const p of res.points ?? []) {
                const marker = L.marker([p.lat, p.lng], {
                    icon: pointIcon(p.featured),
                });
                marker.bindPopup(
                    `<div class="flex min-w-[180px] flex-col gap-1 font-pin text-[13px] text-pin-body">
                        <span class="text-[11px] font-bold uppercase tracking-wide text-pin-mute">${escapeHtml(p.type)}</span>
                        <strong class="text-[14px] text-pin-ink">${escapeHtml(p.title)}</strong>
                        ${p.starts_at_local ? `<span>🗓 ${escapeHtml(p.starts_at_local)}</span>` : ''}
                        ${p.location_label ? `<span>📍 ${escapeHtml(p.location_label)}</span>` : ''}
                        <a class="mt-1 font-bold text-pin-red" href="/events/${p.id}">View event →</a>
                    </div>`,
                );
                marker.addTo(layer);
            }
        }
    } finally {
        loading.value = false;
    }
}

function scheduleRefresh() {
    if (debounce) {
        clearTimeout(debounce);
    }

    debounce = setTimeout(refresh, 250);
}

/** When the location filter changes, move the map to it (moveend refetches). */
function flyToLocation(f: Filters) {
    if (!map || !options.value) {
        return refresh();
    }

    if (f.near) {
        const [lat, lng] = f.near.split(',').map(Number);

        if (Number.isFinite(lat) && Number.isFinite(lng)) {
            return void map.flyTo([lat, lng], 9, { duration: 0.8 });
        }
    }

    if (f.city) {
        const city = options.value.cities.find((c) => c.city === f.city);

        if (city) {
            return void map.flyTo([city.lat, city.lng], 11, { duration: 0.8 });
        }
    } else if (f.country) {
        const cities = options.value.cities.filter(
            (c) => c.country === f.country,
        );

        if (cities.length) {
            const bounds = L.latLngBounds(
                cities.map((c) => [c.lat, c.lng] as [number, number]),
            );

            return void map.flyToBounds(bounds.pad(0.4), {
                duration: 0.8,
                maxZoom: 11,
            });
        }
    } else {
        return void map.flyTo([30, -20], 3, { duration: 0.6 });
    }

    refresh();
}

// Swap map tiles + re-tint markers when the theme changes.
watch(isDark, (dark) => {
    if (tiles) {
        tiles.setUrl(dark ? TILES.dark : TILES.light);
    }

    refresh();
});

watch(
    filters,
    (f) => {
        syncFiltersToUrl(f);
        const locationKey = `${f.country ?? ''}|${f.city ?? ''}|${f.near ?? ''}`;

        if (locationKey !== lastLocationKey) {
            lastLocationKey = locationKey;
            flyToLocation(f);

            return;
        }

        refresh();
    },
    { deep: true },
);

onMounted(async () => {
    if (!mapEl.value) {
        return;
    }

    map = L.map(mapEl.value, { worldCopyJump: true, minZoom: 2 }).setView(
        [30, -20],
        3,
    );
    tiles = L.tileLayer(isDark.value ? TILES.dark : TILES.light, {
        attribution: '&copy; OpenStreetMap &copy; CARTO',
        maxZoom: 19,
    }).addTo(map);
    layer = L.layerGroup().addTo(map);
    map.on('moveend', scheduleRefresh);
    options.value = await fetchFilterOptions();

    // Apply filters restored from the URL: fly to the saved location, else fit world.
    const f = filters.value;
    lastLocationKey = `${f.country ?? ''}|${f.city ?? ''}|${f.near ?? ''}`;

    if (f.country || f.city || f.near) {
        flyToLocation(f);
    } else {
        refresh();
    }
});

onBeforeUnmount(() => {
    if (debounce) {
        clearTimeout(debounce);
    }

    map?.remove();
    map = null;
});
</script>

<template>
    <Head title="Events map" />

    <section class="border-b border-pin-hairline bg-pin-canvas">
        <div
            class="mx-auto flex max-w-[1280px] animate-in flex-wrap items-end justify-between gap-3 px-4 py-10 duration-500 ease-out fade-in slide-in-from-bottom-4 sm:px-6"
        >
            <div>
                <p
                    class="text-sm font-bold tracking-widest text-pin-mute uppercase"
                >
                    Explore
                </p>
                <h1
                    class="mt-2 text-[34px] leading-[1.15] font-semibold tracking-[-1px] text-pin-ink sm:text-[44px]"
                >
                    The whole world of events
                </h1>
                <p class="mt-3 max-w-xl text-base text-pin-body">
                    Pan and zoom to explore
                    <span class="font-bold text-pin-ink">{{
                        total.toLocaleString()
                    }}</span>
                    events — clusters split apart as you zoom in.
                </p>
            </div>
            <span v-if="loading" class="rounded-full bg-pin-card px-4 py-2">
                <PinLoader label="Updating…" />
            </span>
        </div>
    </section>

    <div
        class="mx-auto max-w-[1280px] animate-in px-4 py-4 duration-700 ease-out fade-in sm:px-6"
    >
        <div class="mb-4">
            <CollapsibleFilters v-model="filters" />
        </div>

        <div
            class="relative h-[74vh] overflow-hidden rounded-pin-lg border border-pin-hairline"
        >
            <div ref="mapEl" class="h-full w-full" />

            <div
                class="pointer-events-none absolute bottom-4 left-4 z-[500] rounded-pin-md border border-pin-hairline bg-pin-canvas/95 px-4 py-2.5 text-xs backdrop-blur"
            >
                <p class="mb-0.5 font-bold text-pin-ink">
                    {{ mode === 'clusters' ? 'Clusters' : 'Individual events' }}
                </p>
                <p class="text-pin-mute">
                    {{
                        mode === 'clusters'
                            ? 'Tap a bubble to zoom in'
                            : 'Tap a pin for details'
                    }}
                </p>
            </div>
        </div>
    </div>
</template>

<style>
/* Leaflet generates the popup shell outside Vue, so it needs plain CSS. */
.leaflet-popup-content-wrapper {
    border-radius: 16px;
}
.dark .leaflet-popup-content-wrapper,
.dark .leaflet-popup-tip {
    background: #2a2925;
    color: #f5f5f0;
}
.dark .leaflet-popup-close-button {
    color: #9a9a92;
}
</style>
