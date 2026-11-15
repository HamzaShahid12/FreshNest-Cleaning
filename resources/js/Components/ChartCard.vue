<script setup>
import { computed } from 'vue';
import { money } from '@/Composables/useFormat';

// Dependency-free SVG chart. data: [{ label, value }]
const props = defineProps({
    title: { type: String, required: true },
    subtitle: String,
    data: { type: Array, default: () => [] },
    type: { type: String, default: 'bar' }, // bar | line
    isMoney: { type: Boolean, default: true },
});

const W = 600;
const H = 220;
const pad = { l: 48, r: 12, t: 14, b: 28 };
const fmt = (v) => (props.isMoney ? money(v, { cents: false }) : String(v));

const max = computed(() => {
    const m = Math.max(0, ...props.data.map((d) => Number(d.value) || 0));
    if (m === 0) return 10;
    const pow = 10 ** Math.floor(Math.log10(m));
    return Math.ceil(m / pow) * pow;
});
const innerW = W - pad.l - pad.r;
const innerH = H - pad.t - pad.b;
const step = computed(() => innerW / Math.max(1, props.data.length));
const y = (v) => pad.t + innerH - ((Number(v) || 0) / max.value) * innerH;
const ticks = computed(() => [0, 0.25, 0.5, 0.75, 1].map((f) => ({ v: max.value * f, y: y(max.value * f) })));
const bars = computed(() => props.data.map((d, i) => {
    const bw = Math.min(34, step.value * 0.6);
    const x = pad.l + i * step.value + (step.value - bw) / 2;
    return { ...d, x, w: bw, y: y(d.value), h: pad.t + innerH - y(d.value) };
}));
const points = computed(() => props.data.map((d, i) => ({ ...d, x: pad.l + i * step.value + step.value / 2, y: y(d.value) })));
const path = computed(() => points.value.map((p, i) => `${i ? 'L' : 'M'}${p.x},${p.y}`).join(' '));
</script>

<template>
    <section class="card card-pad">
        <h2 class="font-display text-lg font-semibold text-ink">{{ title }}</h2>
        <p v-if="subtitle" class="text-xs text-gray-500">{{ subtitle }}</p>
        <div class="mt-4">
            <svg :viewBox="`0 0 ${W} ${H}`" class="h-auto w-full" role="img" :aria-label="`${title} chart`">
                <title>{{ title }}</title>
                <g v-for="t in ticks" :key="t.v">
                    <line :x1="pad.l" :x2="W - pad.r" :y1="t.y" :y2="t.y" stroke="#E5E7EB" stroke-dasharray="3 4" />
                    <text :x="pad.l - 6" :y="t.y + 3" text-anchor="end" font-size="10" fill="#6B7280">{{ fmt(t.v) }}</text>
                </g>
                <template v-if="type === 'bar'">
                    <rect v-for="b in bars" :key="b.label" :x="b.x" :y="b.y" :width="b.w" :height="Math.max(0, b.h)" rx="4" fill="#2F855A">
                        <title>{{ b.label }}: {{ fmt(b.value) }}</title>
                    </rect>
                </template>
                <template v-else>
                    <path :d="path" fill="none" stroke="#2F855A" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
                    <circle v-for="p in points" :key="p.label" :cx="p.x" :cy="p.y" r="3.5" fill="#2F855A"><title>{{ p.label }}: {{ fmt(p.value) }}</title></circle>
                </template>
                <text v-for="(d, i) in data" :key="d.label" :x="pad.l + i * step + step / 2" :y="H - 8" text-anchor="middle" font-size="10" fill="#6B7280">{{ d.label }}</text>
            </svg>
        </div>
        <table class="sr-only">
            <caption>{{ title }}</caption>
            <thead><tr><th>Period</th><th>Value</th></tr></thead>
            <tbody><tr v-for="d in data" :key="d.label"><td>{{ d.label }}</td><td>{{ fmt(d.value) }}</td></tr></tbody>
        </table>
    </section>
</template>
