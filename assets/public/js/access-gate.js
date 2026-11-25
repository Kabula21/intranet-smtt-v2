(function () {
    'use strict';
    if (!window.SMTTAccessGate || !window.SMTTAccessGate.enabled) return;
    var cfg = window.SMTTAccessGate;
    var seconds = Math.max(1, parseInt(cfg.visibilitySeconds, 10) || 2);
    var timer = null, generation = 0, sent = false;
    function cancel() {
        generation++;
        if (timer) { clearTimeout(timer); timer = null; }
    }
    function post(url, data) {
        return fetch(url, {
            method: 'POST', credentials: 'same-origin',
            headers: {'X-WP-Nonce': cfg.nonce, 'Accept': 'application/json', 'Content-Type': 'application/json'},
            body: JSON.stringify(data || {})
        }).then(function (r) { if (!r.ok) throw new Error('gate response: ' + r.status); return r.json(); });
    }
    function arm() {
        cancel();
        if (sent || document.visibilityState !== 'visible') return;
        var mine = generation;
        // The server issues the start timestamp; client-side timers cannot shorten it.
        post(cfg.startUrl, {}).then(function (result) {
            if (mine !== generation || document.visibilityState !== 'visible' || sent) return;
            if (!result.ticket) return;
            timer = setTimeout(function () {
                timer = null;
                if (mine !== generation || document.visibilityState !== 'visible' || sent) return;
                post(cfg.passUrl, {ticket: result.ticket}).then(function () {
                    if (mine === generation) sent = true;
                }).catch(function () {
                    // Retry on next visibility event, not a busy loop.
                });
            }, seconds * 1000 + 150);
        }).catch(function () { /* Audit mode: failure must not disrupt the Home. */ });
    }
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') arm(); else cancel();
    });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', arm, {once: true});
    else arm();
}());
