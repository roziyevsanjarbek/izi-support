export function pad(n) {
    return String(n).padStart(2, '0');
}
export function todayISO() {
    const d = new Date();
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}
export function ensureDate(value) {
    if (value instanceof Date) return new Date(value.getTime());
    if (typeof value === 'number') return Number.isNaN(value) ? null : new Date(value);
    if (typeof value !== 'string') return null;
    const raw = value.trim();
    if (!raw) return null;
    let m = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (m) return new Date(+m[1], +m[2] - 1, +m[3], 0, 0, 0, 0);
    m = raw.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?$/);
    if (m) return new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], +(m[6] || 0), 0);
    const parsed = new Date(raw);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
}
export function localDate(value) {
    const d = ensureDate(value);
    if (!d) return todayISO();
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}
export function normalizeDate(value, fallback = todayISO()) {
    const d = ensureDate(value);
    if (!d) return fallback;
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}
export function toLocalInputValue(value) {
    const d = ensureDate(value);
    if (!d) return '';
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}
export function formatTime(value) {
    const d = ensureDate(value);
    if (!d) return '';
    return new Intl.DateTimeFormat('en-US', { hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).format(d);
}
export function escapeHtml(value) {
    return String(value).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
}
export function hexToRgba(hex, alpha) {
    const clean = String(hex || '#0f172a').replace('#', '');
    const value = clean.length === 3 ? clean.split('').map((x) => x + x).join('') : clean;
    const int = Number.parseInt(value, 16);
    if (Number.isNaN(int)) return `rgba(15, 23, 42, ${alpha})`;
    const r = (int >> 16) & 255;
    const g = (int >> 8) & 255;
    const b = int & 255;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}
export function darkenColor(hex, amount) {
    const clean = String(hex || '#0f172a').replace('#', '');
    const value = clean.length === 3 ? clean.split('').map((x) => x + x).join('') : clean;
    const int = Number.parseInt(value, 16);
    if (Number.isNaN(int)) return 'rgb(15, 23, 42)';
    let r = (int >> 16) & 255;
    let g = (int >> 8) & 255;
    let b = int & 255;
    r = Math.max(0, Math.floor(r * (1 - amount)));
    g = Math.max(0, Math.floor(g * (1 - amount)));
    b = Math.max(0, Math.floor(b * (1 - amount)));
    return `rgb(${r}, ${g}, ${b})`;
}
export function startOfDay(dateLike) {
    const d = ensureDate(dateLike);
    if (!d) return null;
    d.setHours(0, 0, 0, 0);
    return d;
}
export function endOfDay(dateLike) {
    const d = ensureDate(dateLike);
    if (!d) return null;
    d.setHours(23, 59, 59, 999);
    return d;
}
export function weekdayKeyFromDate(dateLike) {
    const d = ensureDate(dateLike);
    if (!d) return 'mon';
    return ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'][d.getDay()];
}
