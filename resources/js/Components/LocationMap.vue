<script setup>
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import { onBeforeUnmount, onMounted, ref } from 'vue';

// Read-only map with one pin (e.g. the business location on the Contact page).
const props = defineProps({ latitude: { type: Number, required: true }, longitude: { type: Number, required: true }, label: String, zoom: { type: Number, default: 15 } });
const el = ref(null);
let map = null;

L.Icon.Default.mergeOptions({ iconUrl: markerIcon, iconRetinaUrl: markerIcon2x, shadowUrl: markerShadow });

onMounted(() => {
    map = L.map(el.value, { center: [props.latitude, props.longitude], zoom: props.zoom, scrollWheelZoom: false });
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);
    const marker = L.marker([props.latitude, props.longitude]).addTo(map);
    if (props.label) marker.bindPopup(props.label.replace(/[<>&]/g, ''));
    setTimeout(() => map && map.invalidateSize(), 200);
});
onBeforeUnmount(() => { if (map) { map.remove(); map = null; } });
</script>

<template>
    <div ref="el" class="z-0 h-72 w-full" role="img" :aria-label="label ? `Map showing ${label}` : 'Map showing our location'" />
</template>
