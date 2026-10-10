/* Uses the bundled Globe.GL renderer and the same satellite texture as the advanced map. */
(() => {
    'use strict';
    const host = document.getElementById('visitor-globe');
    if (!host) return;
    const status = document.getElementById('visitor-globe-status');
    const panel = document.getElementById('visitor-globe-panel');
    const periods = document.getElementById('visitor-globe-periods');
    let globe, request, revision = 0, positioned = false;
    const escape = value => String(value).replace(/[&<>"']/g, char => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[char]));
    try {
        globe = Globe()(host).globeImageUrl(host.dataset.texture)
            .width(host.clientWidth).height(host.clientHeight)
            .backgroundColor('#000011').arcColor(d => d.confirmed ? '#00c83c' : '#ff3838')
            .arcAltitudeAutoScale(.37).arcStroke(.2)
            .arcDashLength(.1).arcDashGap(.01).arcDashAnimateTime(4500)
            .arcLabel(d => `${escape(d.call)} · ${d.count} QSO${d.count === 1 ? '' : 's'} · ${d.confirmed ? 'bestätigt' : 'unbestätigt'}`)
            .labelLat('lat').labelLng('lng').labelText('call').labelSize(.35).labelDotRadius(.12)
            .labelColor(() => '#eeeeee').labelResolution(2)
            .pointOfView({lat:30, lng:10, altitude:2});
    } catch (error) {
        status.textContent = 'Der 3D-Globus ist hier nicht verfügbar. Bitte die 2D-Karte auswählen.';
    }
    function running() {
        if (!globe) return;
        if (panel.hidden || document.hidden) globe.pauseAnimation();
        else globe.resumeAnimation();
    }
    new ResizeObserver(() => {
        if (globe && !panel.hidden && host.clientWidth) globe.width(host.clientWidth).height(host.clientHeight);
    }).observe(host);
    document.addEventListener('visibilitychange', running);
    document.querySelectorAll('[data-visitor-view]').forEach(button => button.addEventListener('click', () => {
        const showGlobe = button.dataset.visitorView === 'globe';
        panel.hidden = !showGlobe;
        periods.hidden = !showGlobe;
        document.getElementById('visitor-map-panel').hidden = showGlobe;
        document.querySelectorAll('[data-visitor-view]').forEach(item => {
            const active = item === button;
            item.classList.toggle('btn-primary', active);
            item.classList.toggle('btn-outline-primary', !active);
            item.setAttribute('aria-pressed', String(active));
        });
        if (!showGlobe && typeof map !== 'undefined' && typeof map.invalidateSize === 'function') map.invalidateSize();
        window.dispatchEvent(new Event('resize'));
        running();
    }));
    async function load(period) {
        if (!globe) return;
        const current = ++revision;
        if (request) request.abort();
        request = new AbortController();
        status.textContent = 'Verbindungen werden geladen …';
        host.setAttribute('aria-busy', 'true');
        globe.arcsData([]).labelsData([]);
        try {
            const url = new URL(host.dataset.endpoint, window.location.href);
            url.searchParams.set('period', period);
            const response = await fetch(url, {signal:request.signal, credentials:'same-origin'});
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const data = await response.json();
            if (current !== revision) return;
            const labels = new Map();
            data.routes.forEach(route => {
                const key = JSON.stringify([route.call, route.endLat, route.endLng]);
                if (!labels.has(key)) labels.set(key, {lat:route.endLat, lng:route.endLng, call:route.call});
            });
            globe.arcsData(data.routes).labelsData([...labels.values()]);
            if (!positioned && data.routes.length) {
                globe.pointOfView({lat:Math.max(-60, Math.min(60, data.routes[0].startLat - 30)), lng:data.routes[0].startLng, altitude:1.7}, 700);
                positioned = true;
            }
            const number = value => value.toLocaleString('de-DE');
            status.textContent = data.total === 0 ? 'Keine QSOs in diesem Zeitraum.' :
                `${number(data.total)} QSOs · ${number(data.mapped)} auf der Karte${data.missing ? ` · ${number(data.missing)} ohne Ortsangabe` : ''}`;
        } catch (error) {
            if (error.name !== 'AbortError' && current === revision) status.textContent = 'Verbindungen konnten nicht geladen werden. Zeitraum erneut auswählen, um es noch einmal zu versuchen.';
        } finally {
            if (current === revision) host.setAttribute('aria-busy', 'false');
        }
    }
    document.querySelectorAll('[data-globe-period]').forEach(button => button.addEventListener('click', () => {
        document.querySelectorAll('[data-globe-period]').forEach(item => {
            const active = item === button;
            item.classList.toggle('btn-primary', active);
            item.classList.toggle('btn-outline-primary', !active);
            item.setAttribute('aria-pressed', String(active));
        });
        load(button.dataset.globePeriod);
    }));
    load('24h');
})();
