export function money(value, { cents = true } = {}) {
    const n = Number(value ?? 0);
    return new Intl.NumberFormat('en-US', {
        style: 'currency', currency: 'USD',
        minimumFractionDigits: cents && n % 1 !== 0 ? 2 : 0, maximumFractionDigits: 2,
    }).format(n);
}

export function duration(minutes) {
    const m = Number(minutes || 0);
    const h = Math.floor(m / 60);
    const r = m % 60;
    if (!h) return `${r} min`;
    return r ? `${h} hr ${r} min` : `${h} hr${h > 1 ? 's' : ''}`;
}

export function titleCase(s) {
    return String(s ?? '').replace(/[_-]+/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export function paragraphs(text) {
    return String(text ?? '').split(/\n{2,}/).map((p) => p.trim()).filter(Boolean);
}

export function useFormat() {
    return { money, duration, titleCase, paragraphs };
}
