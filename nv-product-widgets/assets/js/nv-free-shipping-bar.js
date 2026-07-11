(function () {
    'use strict';
    function fmt(amount, symbol, decimals) {
        return symbol + amount.toFixed(decimals);
    }
    function apply(bar, current) {
        var threshold = parseFloat(bar.getAttribute('data-threshold')) || 0;
        var symbol = bar.getAttribute('data-symbol') || '';
        var decimals = parseInt(bar.getAttribute('data-decimals'), 10);
        if (isNaN(decimals)) decimals = 2;
        var tpl = bar.getAttribute('data-remaining-tpl') || '';
        var unlockedText = bar.getAttribute('data-unlocked') || '';
        var msgEl = bar.querySelector('[data-nv-fsb-msg]');
        var fillEl = bar.querySelector('[data-nv-fsb-fill]');

        var remaining = Math.max(0, threshold - current);
        var unlocked = current >= threshold;
        var pct = threshold > 0 ? Math.min(100, (current / threshold) * 100) : 0;

        bar.classList.toggle('is-unlocked', unlocked);
        if (fillEl) fillEl.style.width = pct + '%';
        if (msgEl) msgEl.textContent = unlocked ? unlockedText : tpl.replace('{remaining}', fmt(remaining, symbol, decimals));
    }
    function refresh(bars) {
        var url = '/wp-json/wc/store/v1/cart';
        var headers = { 'Accept': 'application/json' };
        try {
            if (window.wcStoreApiSettings && window.wcStoreApiSettings.nonce) {
                headers['Nonce'] = window.wcStoreApiSettings.nonce;
            }
        } catch (e) {}
        fetch(url, { credentials: 'same-origin', headers: headers })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data || !data.totals) return;
                var minor = parseInt(data.totals.currency_minor_unit, 10);
                if (isNaN(minor)) minor = 2;
                var subtotalMinor = parseInt(data.totals.total_items, 10) || 0;
                var current = subtotalMinor / Math.pow(10, minor);
                bars.forEach(function (bar) { apply(bar, current); });
            })
            .catch(function () {});
    }
    function initAll(root) {
        var bars = Array.prototype.slice.call((root || document).querySelectorAll('[data-nv-fsb]'));
        if (!bars.length) return;
        // Refresh on any WooCommerce cart change (jQuery events fired by Woo/themes).
        if (window.jQuery) {
            var $ = window.jQuery;
            $(document.body).on('added_to_cart removed_from_cart wc_fragments_refreshed wc_fragments_loaded updated_cart_totals updated_wc_div', function () {
                refresh(bars);
            });
        }
        document.addEventListener('nv_pw_qb_added', function () { refresh(bars); });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(document); });
    } else { initAll(document); }
    if (window.jQuery) {
        window.jQuery(window).on('elementor/frontend/init', function () {
            if (window.elementorFrontend && window.elementorFrontend.hooks) {
                window.elementorFrontend.hooks.addAction('frontend/element_ready/nv-free-shipping-bar.default', function ($scope) {
                    initAll($scope[0] || document);
                });
            }
        });
    }
})();
