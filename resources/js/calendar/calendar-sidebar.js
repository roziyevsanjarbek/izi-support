(() => {
    if (window.__calendarSidebarBooted) return;
    window.__calendarSidebarBooted = true;
    function api() { return window.__calendarApp || null; }
    function normalizeFilter(value) { return String(value || 'pending').trim().replace(/\s+/g, '_').toLowerCase(); }
    const palette = {
        all: { base: ['border-slate-200', 'bg-slate-50', 'text-slate-700'], active: ['bg-slate-900', 'border-slate-900', 'text-white', 'ring-2', 'ring-slate-200', 'ring-offset-2'] },
        completed: { base: ['border-emerald-200', 'bg-emerald-50', 'text-emerald-700'], active: ['bg-emerald-600', 'border-emerald-600', 'text-white', 'ring-2', 'ring-emerald-200', 'ring-offset-2'] },
        not_completed: { base: ['border-rose-200', 'bg-rose-50', 'text-rose-700'], active: ['bg-rose-600', 'border-rose-600', 'text-white', 'ring-2', 'ring-rose-200', 'ring-offset-2'] },
        pending: { base: ['border-amber-200', 'bg-amber-50', 'text-amber-700'], active: ['bg-amber-500', 'border-amber-500', 'text-white', 'ring-2', 'ring-amber-200', 'ring-offset-2'] },
    };
    function openSidebar() { document.getElementById('calendarSidebar')?.classList.remove('-translate-x-full'); document.getElementById('sidebarBackdrop')?.classList.remove('hidden'); }
    function closeSidebar() { document.getElementById('calendarSidebar')?.classList.add('-translate-x-full'); document.getElementById('sidebarBackdrop')?.classList.add('hidden'); }
    function paintButton(btn, active) { const filter = normalizeFilter(btn.dataset.filter); const p = palette[filter] || palette.pending; btn.classList.remove(...p.base, ...p.active); btn.classList.add(...(active ? p.active : p.base)); }
    function setActiveFilterButton(filter) { const normalized = normalizeFilter(filter); document.querySelectorAll('[data-filter]').forEach((btn) => { paintButton(btn, normalizeFilter(btn.dataset.filter) === normalized); }); }
    function goSelf() { const app = api(); if (!app) return; app.state.selectedUserId = window.__calendarAuthUserId || null; app.state.selectedUserName = window.__calendarAuthUserName || app.state.selectedUserName || 'My calendar'; app.navigate(app.state.view || 'month', app.state.selectedDate || new Date().toISOString().slice(0, 10)); }
    function boot() { setActiveFilterButton(window.__calendarInitialFilter || 'pending'); }
    document.addEventListener('click', (e) => {
        if (e.target.closest('#openSidebarButton')) { openSidebar(); return; }
        if (e.target.closest('#closeSidebarButton, #sidebarBackdrop')) { closeSidebar(); return; }
        if (e.target.closest('[data-go-self]')) { goSelf(); return; }
        const filterBtn = e.target.closest('[data-filter]');
        if (filterBtn) { const filter = normalizeFilter(filterBtn.dataset.filter); setActiveFilterButton(filter); api()?.setFilter?.(filter); return; }
    });
    document.addEventListener('input', (e) => { const t = e.target; if (!t) return; if (t.id === 'searchInputMobile' || t.id === 'searchInput') api()?.setSearch?.(t.value || ''); });
    document.addEventListener('change', (e) => { const t = e.target; if (!t) return; if (t.id === 'statusFilterMobile' || t.id === 'statusFilter') api()?.setStatus?.(t.value || 'all'); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSidebar(); });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true }); else boot();
    window.__calendarSidebar = { openSidebar, closeSidebar, setActiveFilterButton };
})();