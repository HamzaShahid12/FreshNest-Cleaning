<script setup>
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Btn from '@/Components/Btn.vue';
import Icon from '@/Components/Icon.vue';

/**
 * Worldwide location picker (Leaflet + OpenStreetMap tiles, Nominatim search). No API key needed.
 * Search for an address, click the map, drag the pin, or use the device location.
 * Emits `select` with { street, city, state, zip, country, country_code, latitude, longitude }.
 * Typing the address by hand always remains possible, so the booking never depends on the map.
 */
const props = defineProps({ latitude: [Number, String, null], longitude: [Number, String, null] });
const emit = defineEmits(['select']);

const NOMINATIM = 'https://nominatim.openstreetmap.org';
const el = ref(null);
const query = ref('');
const results = ref([]);
const busy = ref(false);
const message = ref('');
let map = null;
let marker = null;

L.Icon.Default.mergeOptions({ iconUrl: markerIcon, iconRetinaUrl: markerIcon2x, shadowUrl: markerShadow });

const num = (v) => (v === null || v === undefined || v === '' || Number.isNaN(Number(v)) ? null : Number(v));

/** Nominatim address -> our form fields. */
function parse(r) {
    const a = r.address || {};
    const road = a.road || a.pedestrian || a.footway || a.path || a.neighbourhood || a.suburb || '';
    const street = [a.house_number, road].filter(Boolean).join(' ') || (r.display_name || '').split(',')[0] || '';

    return {
        street: street.trim(),
        city: a.city || a.town || a.village || a.municipality || a.city_district || a.county || '',
        state: a.state || a.region || a.province || a.state_district || '',
        zip: a.postcode || '',
        country: a.country || '',
        country_code: (a.country_code || '').toUpperCase(),
        latitude: Number(Number(r.lat).toFixed(7)),
        longitude: Number(Number(r.lon).toFixed(7)),
    };
}

async function nominatim(path, params) {
    const url = `${NOMINATIM}${path}?${new URLSearchParams({ format: 'jsonv2', addressdetails: '1', 'accept-language': navigator.language || 'en', ...params })}`;
    const res = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!res.ok) throw new Error('lookup failed');

    return res.json();
}

function place(lat, lng, zoom) {
    if (!map) return;
    const pos = [lat, lng];
    if (!marker) {
        marker = L.marker(pos, { draggable: true }).addTo(map);
        marker.on('dragend', () => { const p = marker.getLatLng(); reverse(p.lat, p.lng); });
    } else {
        marker.setLatLng(pos);
    }
    map.setView(pos, zoom ?? Math.max(map.getZoom(), 16));
}

async function reverse(lat, lng) {
    busy.value = true; message.value = '';
    place(lat, lng);
    try {
        const r = await nominatim('/reverse', { lat, lon: lng, zoom: 18 });
        if (r.error) throw new Error(r.error);
        emit('select', parse(r));
    } catch {
        // Keep the coordinates even if the address lookup fails.
        emit('select', { latitude: Number(lat.toFixed(7)), longitude: Number(lng.toFixed(7)) });
        message.value = 'Pin placed, but the address could not be looked up. Please fill in the address fields.';
    } finally {
        busy.value = false;
    }
}

async function search() {
    const q = query.value.trim();
    if (q.length < 3) { message.value = 'Type at least 3 characters.'; return; }
    busy.value = true; message.value = ''; results.value = [];
    try {
        results.value = await nominatim('/search', { q, limit: 5 });
        if (!results.value.length) message.value = 'No match found. Try a nearby landmark or drop the pin on the map.';
    } catch {
        message.value = 'Map search is unavailable right now. You can type the address below instead.';
    } finally {
        busy.value = false;
    }
}

function choose(r) {
    results.value = [];
    query.value = r.display_name;
    place(Number(r.lat), Number(r.lon), 17);
    emit('select', parse(r));
}

function locate() {
    if (!navigator.geolocation) { message.value = 'Your browser does not support location.'; return; }
    busy.value = true; message.value = '';
    navigator.geolocation.getCurrentPosition(
        (pos) => { busy.value = false; reverse(pos.coords.latitude, pos.coords.longitude); },
        () => { busy.value = false; message.value = 'Location permission was denied. Search or tap the map instead.'; },
        { enableHighAccuracy: true, timeout: 10000 },
    );
}

onMounted(() => {
    const lat = num(props.latitude);
    const lng = num(props.longitude);
    map = L.map(el.value, { center: lat !== null && lng !== null ? [lat, lng] : [20, 0], zoom: lat !== null ? 16 : 2, scrollWheelZoom: false });
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);
    if (lat !== null && lng !== null) place(lat, lng, 16);
    map.on('click', (e) => reverse(e.latlng.lat, e.latlng.lng));
    setTimeout(() => map && map.invalidateSize(), 200);
});

// A saved address (or reset) can change the coordinates from outside.
watch(() => [props.latitude, props.longitude], ([la, lo]) => {
    const lat = num(la); const lng = num(lo);
    if (lat !== null && lng !== null && map) place(lat, lng, 16);
});

onBeforeUnmount(() => { if (map) { map.remove(); map = null; marker = null; } });
</script>

<template>
    <div class="space-y-3">
        <form class="flex flex-col gap-2 sm:flex-row" role="search" @submit.prevent="search">
            <div class="relative flex-1">
                <label for="map-search" class="sr-only">Search for your address</label>
                <Icon name="search" class="pointer-events-none absolute left-3 top-3 h-4 w-4 text-gray-400" />
                <input id="map-search" v-model="query" type="search" class="input pl-9" placeholder="Search your address, city or landmark (any country)">
            </div>
            <Btn type="submit" :loading="busy" variant="dark">Search</Btn>
            <Btn variant="outline" :disabled="busy" @click="locate"><Icon name="map-pin" class="h-4 w-4" />Use my location</Btn>
        </form>

        <ul v-if="results.length" class="divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200 bg-white text-sm shadow-card" role="listbox">
            <li v-for="r in results" :key="r.place_id">
                <button type="button" class="w-full px-3 py-2 text-left hover:bg-brand-soft" @click="choose(r)">{{ r.display_name }}</button>
            </li>
        </ul>
        <p v-if="message" class="text-sm text-amber-700" role="status">{{ message }}</p>

        <div ref="el" class="z-0 h-72 w-full overflow-hidden rounded-xl border border-gray-200 sm:h-80" aria-label="Map: click to drop a pin on your address" />
        <p class="text-xs text-gray-500">Tap the map or drag the pin to fine-tune your exact location. The fields below fill in automatically and you can edit them.</p>
    </div>
</template>
