(function () {
    'use strict';

    function initBundle(box) {
        if (box.__nvBundle) return;
        box.__nvBundle = true;

        var symbol = box.getAttribute('data-symbol') || '';
        var decimals = parseInt(box.getAttribute('data-decimals') || '2', 10);
        var tiers = Array.prototype.slice.call(box.querySelectorAll('[data-nv-tier]'));
        var products = Array.prototype.slice.call(box.querySelectorAll('.nv-pw-bb__product'));
        var hint = box.querySelector('[data-nv-hint]');
        var subEl = box.querySelector('[data-nv-subtotal]');
        var saveEl = box.querySelector('[data-nv-savings]');
        var totEl = box.querySelector('[data-nv-total]');
        var addBtn = box.querySelector('[data-nv-add]');
        var msg = box.querySelector('[data-nv-msg]');

        var counts = {};
        var active = tiers.length ? tiers[0] : null;

        function fmt(n) {
            var v = (Math.round(n * Math.pow(10, decimals)) / Math.pow(10, decimals)).toFixed(decimals);
            return symbol + v;
        }
        function tierQty() { return active ? parseInt(active.getAttribute('data-qty'), 10) || 1 : 1; }
        function tierDiscount() { return active ? parseFloat(active.getAttribute('data-discount')) || 0 : 0; }
        function totalSelected() { var t = 0; for (var k in counts) t += counts[k]; return t; }

        function update() {
            var max = tierQty();
            var sel = totalSelected();
            var subtotal = 0;
            products.forEach(function (p) {
                var id = p.getAttribute('data-id');
                var price = parseFloat(p.getAttribute('data-price')) || 0;
                var c = counts[id] || 0;
                subtotal += price * c;
                var cEl = p.querySelector('[data-nv-count]');
                if (cEl) cEl.textContent = c;
                p.classList.toggle('is-selected', c > 0);
            });
            var savings = subtotal * (tierDiscount() / 100);
            subEl.textContent = sel ? fmt(subtotal) : '—';
            saveEl.textContent = sel ? '−' + fmt(savings) : '—';
            totEl.textContent = sel ? fmt(subtotal - savings) : '—';
            if (hint) {
                if (sel < max) hint.textContent = 'Välj ' + (max - sel) + ' till för att slutföra ditt ' + (active ? active.querySelector('.nv-pw-bb__tier-label').textContent : '') + '.';
                else if (sel === max) hint.textContent = 'Klart! Ditt paket är fullt.';
                else hint.textContent = 'Ta bort ' + (sel - max) + ' för att matcha ' + max + '-pack.';
            }
            addBtn.disabled = !(sel === max && sel > 0);
        }

        tiers.forEach(function (t) {
            t.addEventListener('click', function () {
                tiers.forEach(function (x) { x.classList.remove('is-active'); });
                t.classList.add('is-active');
                active = t;
                update();
            });
        });

        products.forEach(function (p) {
            var id = p.getAttribute('data-id');
            var plus = p.querySelector('[data-nv-plus]');
            var minus = p.querySelector('[data-nv-minus]');
            if (plus) plus.addEventListener('click', function () {
                if (totalSelected() >= tierQty()) {
                    p.classList.add('is-shake'); setTimeout(function () { p.classList.remove('is-shake'); }, 400);
                    return;
                }
                counts[id] = (counts[id] || 0) + 1; update();
            });
            if (minus) minus.addEventListener('click', function () {
                if ((counts[id] || 0) > 0) { counts[id]--; if (counts[id] === 0) delete counts[id]; update(); }
            });
        });

        addBtn.addEventListener('click', function () {
            if (typeof nvPwBundle === 'undefined') { if (msg) msg.textContent = 'Konfigurationsfel.'; return; }
            var items = [];
            for (var id in counts) items.push({ id: parseInt(id, 10), qty: counts[id] });
            if (!items.length) return;
            addBtn.disabled = true;
            addBtn.classList.add('is-loading');
            if (msg) msg.textContent = '';
            var body = new URLSearchParams();
            body.append('action', 'nv_pw_add_bundle');
            body.append('nonce', nvPwBundle.nonce);
            body.append('items', JSON.stringify(items));
            body.append('coupon', active ? (active.getAttribute('data-coupon') || '') : '');
            fetch(nvPwBundle.ajaxurl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString() })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res && res.success && res.data && res.data.redirect) { window.location.href = res.data.redirect; }
                    else { addBtn.disabled = false; addBtn.classList.remove('is-loading'); if (msg) msg.textContent = (res && res.data && res.data.message) || 'Något gick fel.'; }
                })
                .catch(function () { addBtn.disabled = false; addBtn.classList.remove('is-loading'); if (msg) msg.textContent = 'Nätverksfel. Försök igen.'; });
        });

        update();
    }

    function initAll(root) { (root || document).querySelectorAll('[data-nv-bundle]').forEach(initBundle); }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(document); });
    } else { initAll(document); }
    if (window.jQuery) {
        window.jQuery(window).on('elementor/frontend/init', function () {
            if (window.elementorFrontend && window.elementorFrontend.hooks) {
                window.elementorFrontend.hooks.addAction('frontend/element_ready/nv-bundle-builder.default', function ($scope) {
                    initAll($scope[0] || document);
                });
            }
        });
    }
})();
