(() => {
    'use strict';
    const root = document.getElementById('cn-waiting-alerts');
    if (!root || window.cnWaitingAlerts) return;
    window.cnWaitingAlerts = true;
    const $ = id => document.getElementById(id);
    const key = `chatnivo.waiting.v1.${root.dataset.user}`;
    const snapshotKey = `${key}.snapshot`;
    const preferencesKey = `${key}.preferences`;
    const lockName = `${key}.poll`;
    const interval = 15000;
    let memorySnapshot = null, timer, stopped = false, busy = false, failures = 0;
    let audio = null, audioReady = false, currentToastIds = [], aborter = null;
    let storageAvailable = true;
    try { localStorage.setItem(`${key}.probe`, '1'); localStorage.removeItem(`${key}.probe`); }
    catch { storageAvailable = false; }
    function read(name, fallback) {
        if (!storageAvailable) return fallback;
        try { return JSON.parse(localStorage.getItem(name)) || fallback; } catch { return fallback; }
    }
    function write(name, value) {
        if (!storageAvailable) return;
        try { localStorage.setItem(name, JSON.stringify(value)); } catch { storageAvailable = false; }
    }
    let prefs = read(preferencesKey, {sound: false, desktop: false, paused: false});
    function status(text) { $('cn-alert-status').textContent = text; }
    function controls() {
        $('cn-alert-paused').checked = !!prefs.paused;
        $('cn-alert-sound').textContent = prefs.sound && audioReady ? 'Mute sound' : 'Enable sound';
        $('cn-alert-desktop').textContent = prefs.desktop ? 'Disable desktop notifications' : 'Enable desktop notifications';
    }
    function savePrefs() { write(preferencesKey, prefs); controls(); }
    function sound() {
        if (!audioReady || !audio || audio.state !== 'running') return;
        [0, 0.23].forEach((offset, index) => {
            const oscillator = audio.createOscillator(), gain = audio.createGain();
            const start = audio.currentTime + offset;
            oscillator.type = 'sine'; oscillator.frequency.value = index ? 880 : 660;
            gain.gain.setValueAtTime(0, start);
            gain.gain.linearRampToValueAtTime(0.14, start + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.001, start + 0.2);
            oscillator.connect(gain); gain.connect(audio.destination);
            oscillator.start(start); oscillator.stop(start + 0.21);
            oscillator.onended = () => { oscillator.disconnect(); gain.disconnect(); };
        });
    }
    async function enableAudio() {
        try {
            const Audio = window.AudioContext || window.webkitAudioContext;
            if (!Audio) throw new Error('unsupported');
            if (!audio) audio = new Audio();
            await audio.resume(); audioReady = audio.state === 'running';
            if (!audioReady) throw new Error('blocked');
            prefs.sound = true; savePrefs(); sound();
            status('Sound enabled for this page.');
        } catch { audioReady = false; controls(); status('Sound is blocked by this browser. Visual alerts remain available.'); }
    }
    $('cn-alert-sound').addEventListener('click', () => {
        if (prefs.sound && audioReady) { prefs.sound = false; savePrefs(); } else enableAudio();
    });
    $('cn-alert-test').addEventListener('click', enableAudio);
    $('cn-alert-desktop').addEventListener('click', async () => {
        if (prefs.desktop) { prefs.desktop = false; savePrefs(); return; }
        if (!('Notification' in window) || !window.isSecureContext) {
            status('Desktop notifications require a supported browser and HTTPS.'); return;
        }
        try {
            const permission = await Notification.requestPermission();
            prefs.desktop = permission === 'granted'; savePrefs();
            status(prefs.desktop ? 'Desktop notifications enabled.' : 'Desktop permission was not granted. In-page alerts still work.');
        } catch { status('Desktop notifications are unavailable in this browser.'); }
    });
    $('cn-alert-paused').addEventListener('change', event => {
        prefs.paused = event.target.checked; savePrefs();
        if (prefs.paused) $('cn-alert-toast').hidden = true;
        else { memorySnapshot = null; try { localStorage.removeItem(snapshotKey); } catch {} }
        poll();
    });
    $('cn-alert-dismiss').addEventListener('click', () => { $('cn-alert-toast').hidden = true; currentToastIds = []; });
    function sameOriginUrl(value) {
        const url = new URL(value, location.href);
        if (url.origin !== location.origin) throw new Error('Invalid conversation link');
        return url.href;
    }
    let lastAlert = '';
    try { lastAlert = sessionStorage.getItem(`${key}.lastAlert`) || ''; } catch {}
    async function attentionOnce(kind, eventId, action) {
        const ackKey = `${key}.${kind}`;
        const run = () => {
            if (read(ackKey, '') === eventId) return;
            action(); write(ackKey, eventId);
        };
        try {
            if (navigator.locks && storageAvailable) await navigator.locks.request(ackKey, run);
            else run();
        } catch { /* Audio/OS notification failures never interrupt the inbox. */ }
    }
    function notify(items, total, initial, eventId) {
        if (!items.length || prefs.paused) return;
        currentToastIds = items.map(item => item.id);
        const message = initial
            ? `${total} conversation${total === 1 ? '' : 's'} waiting for an agent.`
            : items.length === 1 ? `${items[0].name} is waiting for a human reply.`
            : `${items.length} conversations are now waiting for an agent.`;
        const url = items.length === 1 ? sameOriginUrl(items[0].url) : sameOriginUrl(root.dataset.inbox);
        $('cn-alert-message').textContent = message;
        $('cn-alert-open').href = url;
        $('cn-alert-open').textContent = items.length === 1 ? 'Open conversation' : 'Open waiting inbox';
        $('cn-alert-toast').hidden = false;
        if (prefs.sound && audioReady && audio?.state === 'running') attentionOnce('sound', eventId, sound);
        if (prefs.desktop && document.hidden && 'Notification' in window && Notification.permission === 'granted') {
            attentionOnce('desktop', eventId, () => {
                // Keep customer details off the operating-system notification/lock screen.
                const notification = new Notification('ChatNivo: customer waiting', {
                    body: 'A customer needs a live agent. Open your waiting inbox.',
                    tag: `${key}.waiting`, silent: true
                });
                notification.onclick = () => { window.focus(); location.assign(url); notification.close(); };
                setTimeout(() => notification.close(), 20000);
            });
        }
    }
    async function fetchWaiting() {
        const collected = new Map();
        let page = 1;
        for (let count = 0; count < 50; count++) {
            const url = new URL(root.dataset.feed, location.href); url.searchParams.set('page', String(page));
            aborter = new AbortController();
            const timeout = setTimeout(() => aborter?.abort(), 12000);
            let response;
            try {
                response = await fetch(url.href, {
                    credentials: 'same-origin', cache: 'no-store', signal: aborter.signal,
                    headers: {'Accept': 'text/html'}
                });
                if ([401, 403, 419].includes(response.status) || response.redirected) {
                    stopped = true; $('cn-alert-toast').hidden = true; $('cn-alert-count').textContent = '—';
                    throw new Error('Access ended. Reload or sign in again.');
                }
                if (!response.ok) throw new Error(`Inbox unavailable (${response.status}).`);
                const html = await response.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const node = doc.getElementById('cn-waiting-feed');
                if (!node) throw new Error('Alert feed missing. Check that the inbox view was deployed.');
                const data = JSON.parse(node.textContent);
                if (data.version !== 1 || !Array.isArray(data.items)) throw new Error('Invalid alert feed.');
                for (const item of data.items) {
                    if (!/^\d+$/.test(String(item.id)) || typeof item.name !== 'string') throw new Error('Invalid alert item.');
                    collected.set(String(item.id), {id: String(item.id), name: item.name, url: sameOriginUrl(item.url)});
                }
                if (data.nextPage === null) return [...collected.values()];
                if (!Number.isInteger(data.nextPage) || data.nextPage <= page) throw new Error('Invalid pagination.');
                page = data.nextPage;
            } finally { clearTimeout(timeout); aborter = null; }
        }
        throw new Error('Waiting queue exceeds 50 pages. Open the inbox and contact your administrator.');
    }
    function reflect(snapshot) {
        if (!snapshot || !Array.isArray(snapshot.ids)) return;
        $('cn-alert-count').textContent = String(snapshot.ids.length);
        const event = snapshot.alert;
        if (event && event.id !== lastAlert && Date.now() - event.at < 60000 && !prefs.paused) {
            lastAlert = event.id;
            try { sessionStorage.setItem(`${key}.lastAlert`, lastAlert); } catch {}
            const current = event.items.filter(item => snapshot.ids.includes(item.id));
            notify(current, snapshot.ids.length, event.initial, event.id);
        }
        if (currentToastIds.length && !currentToastIds.some(id => snapshot.ids.includes(id))) {
            $('cn-alert-toast').hidden = true; currentToastIds = [];
        }
    }
    async function check() {
        const previous = read(snapshotKey, memorySnapshot);
        // A shared completed snapshot prevents each open tab doing the same scan.
        if (previous && Date.now() - previous.checkedAt < interval - 500) { reflect(previous); status(`Checked ${new Date(previous.checkedAt).toLocaleTimeString()}.`); return; }
        const items = await fetchWaiting();
        if (stopped) return;
        const initial = !previous || Date.now() - previous.checkedAt > 300000;
        const oldIds = new Set(initial ? [] : previous.ids);
        const arrivals = items.filter(item => !oldIds.has(item.id));
        const now = Date.now();
        const alert = arrivals.length ? {
            id: `${now}-${Math.random().toString(36).slice(2)}`, at: now, initial,
            // Share only generic labels, IDs and authorized links between this user's tabs.
            items: arrivals.map(item => ({id: item.id, name: 'A customer', url: item.url}))
        } : previous?.alert;
        const snapshot = {ids: items.map(item => item.id), checkedAt: now, alert};
        memorySnapshot = snapshot; write(snapshotKey, snapshot); reflect(snapshot);
        failures = 0;
        status(prefs.paused ? 'Alerts paused in this browser.' : `Checked ${new Date().toLocaleTimeString()}.`);

    }
    async function poll() {
        clearTimeout(timer);
        if (stopped || busy) return;
        busy = true;
        try {
            if (prefs.paused) { status('Alerts paused in this browser.'); return; }
            // The browser releases the Web Lock automatically if a tab closes.
            if (navigator.locks && storageAvailable) {
                await navigator.locks.request(lockName, {ifAvailable: true}, async lock => { if (lock) await check(); });
            } else { await check(); }
        } catch (error) {
            failures++;
            status(`${error.message || 'Could not check waiting chats.'}${stopped ? '' : ' Retrying automatically.'}`);
        } finally {
            busy = false;
            if (!stopped) timer = setTimeout(poll, Math.min(60000, interval * Math.max(1, failures)));
        }
    }
    window.addEventListener('storage', event => {
        if (event.key === snapshotKey) { reflect(read(snapshotKey, null)); }
        if (event.key === preferencesKey) { prefs = read(preferencesKey, prefs); controls(); if (prefs.paused) $('cn-alert-toast').hidden = true; }
    });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
    window.addEventListener('online', poll);
    window.addEventListener('pagehide', () => { stopped = true; clearTimeout(timer); aborter?.abort(); });
    window.addEventListener('pageshow', event => { if (event.persisted) { stopped = false; poll(); } });
    document.addEventListener('submit', event => {
        const form = event.target;
        if (form instanceof HTMLFormElement && new URL(form.action, location.href).pathname.endsWith('/logout')) {
            stopped = true; clearTimeout(timer); aborter?.abort();
            try { localStorage.removeItem(snapshotKey); } catch {}
        }
    });
    controls();
    poll();
})();
