(function () {
    'use strict';

    // Replace WooCommerce mini-cart fragments and fire the standard events that
    // theme/plugin side-carts listen to — this updates the header cart count.
    function refreshCart(d) {
        var $ = window.jQuery;
        if ($ && d && d.fragments) {
            try {
                $.each(d.fragments, function (key, value) { $(key).replaceWith(value); });
                $(document.body).trigger('wc_fragments_refreshed');
            } catch (e) {}
        }
    }

    // Open the theme's slide-out cart WITHOUT navigating. Most side-carts open on
    // the standard `added_to_cart` body event; an optional custom selector covers
    // themes that need their drawer toggle clicked.
    function openSideCart(box) {
        var $ = window.jQuery;
        var d = { fragments: {}, cart_hash: '' };
        if ($) {
            try { $(document.body).trigger('added_to_cart', [d.fragments, d.cart_hash, $('<a></a>')]); } catch (e) {}
            try { $(document.body).trigger('wc_fragment_refresh'); } catch (e2) {}
        } else {
            try { document.body.dispatchEvent(new CustomEvent('added_to_cart')); } catch (e3) {}
        }
        // NV Nordic Precision theme / NV Conversion Booster convention: the side
        // cart drawer opens on this custom event.
        try {
            var ev = new CustomEvent('nv:sidecart:open', { bubbles: true });
            document.dispatchEvent(ev);
            document.body.dispatchEvent(new CustomEvent('nv:sidecart:open', { bubbles: true }));
        } catch (e4) {}
        // Optional explicit trigger element (only if the user supplies a real
        // drawer toggle — NOT a link to the cart page).
        var sel = box.getAttribute('data-cart-selector');
        if (sel) {
            var el = document.querySelector(sel);
            if (el) { try { el.click(); } catch (e5) {} }
        }
    }

    function initQB(box) {
        if (box.__nvQbInit) return;
        box.__nvQbInit = true;
        var tiers = Array.prototype.slice.call(box.querySelectorAll('[data-nv-qb-tier]'));
        var addBtn = box.querySelector('[data-nv-qb-add]');
        var msg = box.querySelector('[data-nv-qb-msg]');
        var isVariable = box.getAttribute('data-variable') === '1';
        var productId = box.getAttribute('data-product-id');

        function select(tier) {
            tiers.forEach(function (t) { t.classList.toggle('is-selected', t === tier); });
            if (msg) msg.textContent = '';
        }
        function selectedTier() {
            for (var i = 0; i < tiers.length; i++) { if (tiers[i].classList.contains('is-selected')) return tiers[i]; }
            return tiers[0] || null;
        }

        tiers.forEach(function (tier) {
            tier.addEventListener('click', function (e) {
                // Don't hijack interactions with the variation dropdowns.
                if (e.target.closest('select')) return;
                select(tier);
            });
        });
        if (!selectedTier() && tiers[0]) select(tiers[0]);

        if (addBtn) {
            addBtn.addEventListener('click', function () {
                var tier = selectedTier();
                if (!tier) return;
                var qty = parseInt(tier.getAttribute('data-qty'), 10) || 1;
                var mode = box.getAttribute('data-discount-mode') || 'coupon';
                var coupon = mode === 'coupon' ? (tier.getAttribute('data-coupon') || '') : '';
                var discountPct = mode === 'auto' ? (tier.getAttribute('data-discount') || '0') : '0';
                var isNvcc = mode === 'nvcc';
                var cartTotal = isNvcc ? (tier.getAttribute('data-cart-total') || '0') : '0';
                var dealTitle = tier.getAttribute('data-label') || '';
                var giftId = tier.getAttribute('data-gift-id') || '0';
                var units = [];

                if (isVariable) {
                    var selects = Array.prototype.slice.call(tier.querySelectorAll('[data-nv-qb-unit]'));
                    for (var i = 0; i < selects.length; i++) {
                        var v = selects[i].value;
                        if (!v) {
                            if (msg) { msg.textContent = selects[i].getAttribute('data-error') || 'Välj alla varianter först.'; msg.className = 'nv-pw-qb__msg is-error'; }
                            selects[i].focus();
                            return;
                        }
                        units.push(v);
                    }
                }

                var cfg = window.nvPwBundle || {};
                if (!cfg.ajaxurl) return;
                addBtn.disabled = true;
                var original = addBtn.textContent;
                addBtn.textContent = cfg.adding || 'Lägger till…';
                if (msg) { msg.textContent = ''; msg.className = 'nv-pw-qb__msg'; }

                var body = new URLSearchParams();
                body.set('action', 'nv_pw_add_qty_breaks');
                body.set('nonce', cfg.nonce || '');
                body.set('product_id', productId);
                body.set('qty', String(qty));
                body.set('units', JSON.stringify(units));
                body.set('coupon', coupon);
                body.set('discount_pct', discountPct);
                body.set('gift_id', giftId);
                body.set('nvcc', isNvcc ? '1' : '0');
                if (isNvcc) {
                    body.set('cart_total', cartTotal);
                    body.set('deal_title', dealTitle);
                }

                var afterAdd = box.getAttribute('data-after-add') || 'side_cart';

                fetch(cfg.ajaxurl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString() })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (res && res.success) {
                            var d = res.data || {};
                            if (afterAdd === 'redirect_cart' && d.redirect) {
                                window.location.href = d.redirect;
                                return;
                            }
                            refreshCart(d);
                            if (afterAdd === 'side_cart') openSideCart(box);
                            if (msg) { msg.textContent = cfg.added_ok || 'Tillagd i varukorgen ✓'; msg.className = 'nv-pw-qb__msg is-ok'; }
                            addBtn.disabled = false;
                            addBtn.textContent = original;
                        } else {
                            var m = (res && res.data && res.data.message) ? res.data.message : 'Något gick fel.';
                            if (msg) { msg.textContent = m; msg.className = 'nv-pw-qb__msg is-error'; }
                            addBtn.disabled = false;
                            addBtn.textContent = original;
                        }
                    })
                    .catch(function () {
                        if (msg) { msg.textContent = 'Nätverksfel. Försök igen.'; msg.className = 'nv-pw-qb__msg is-error'; }
                        addBtn.disabled = false;
                        addBtn.textContent = original;
                    });
            });
        }
    }
    function initAll(root) { (root || document).querySelectorAll('[data-nv-qb]').forEach(initQB); }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(document); });
    } else { initAll(document); }
    if (window.jQuery) {
        window.jQuery(window).on('elementor/frontend/init', function () {
            if (window.elementorFrontend && window.elementorFrontend.hooks) {
                window.elementorFrontend.hooks.addAction('frontend/element_ready/nv-quantity-breaks.default', function ($scope) {
                    initAll($scope[0] || document);
                });
            }
        });
    }
})();
