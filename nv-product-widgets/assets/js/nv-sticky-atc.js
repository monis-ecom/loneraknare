(function () {
    'use strict';
    function initBar(bar) {
        if (bar.__nvSatcInit) return;
        bar.__nvSatcInit = true;
        var showAfter = parseInt(bar.getAttribute('data-show-after'), 10);
        if (isNaN(showAfter)) showAfter = 600;

        function update() {
            var past = (window.pageYOffset || document.documentElement.scrollTop || 0) > showAfter;
            if (past) {
                bar.removeAttribute('hidden');
                // Force reflow so the CSS transition runs on first reveal.
                void bar.offsetHeight;
                bar.classList.add('is-visible');
            } else {
                bar.classList.remove('is-visible');
            }
        }

        var ticking = false;
        window.addEventListener('scroll', function () {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(function () { update(); ticking = false; });
        }, { passive: true });
        update();

        // Variable products (or fallback): scroll to the main add-to-cart form instead of a direct add.
        var scrollBtn = bar.querySelector('[data-nv-satc-scroll]');
        if (scrollBtn) {
            scrollBtn.addEventListener('click', function () {
                var target = document.querySelector('.single_add_to_cart_button, form.cart, .nv-pw-add-to-cart');
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    var variations = document.querySelector('.variations select, form.cart select');
                    if (variations) { try { variations.focus({ preventScroll: true }); } catch (e) { variations.focus(); } }
                }
            });
        }

        // In-bar variant add-to-cart (variable products, "inbar" mode).
        var inbarBtn = bar.querySelector('[data-nv-satc-inbar]');
        if (inbarBtn) {
            var select = bar.querySelector('[data-nv-satc-variant]');
            var msg = bar.querySelector('[data-nv-satc-msg]');
            inbarBtn.addEventListener('click', function () {
                if (select && !select.value) {
                    if (msg) msg.textContent = select.getAttribute('data-error') || 'Välj en variant först.';
                    select.focus();
                    return;
                }
                var cfg = window.nvPwBundle || {};
                if (!cfg.ajaxurl) return;
                inbarBtn.disabled = true;
                var original = inbarBtn.textContent;
                inbarBtn.textContent = cfg.adding || 'Lägger till…';
                if (msg) msg.textContent = '';

                var body = new URLSearchParams();
                body.set('action', 'nv_pw_add_qty_breaks');
                body.set('nonce', cfg.nonce || '');
                body.set('product_id', bar.getAttribute('data-product-id') || '');
                body.set('qty', '1');
                body.set('units', JSON.stringify([select ? select.value : '']));
                body.set('coupon', '');
                body.set('gift_id', '0');

                fetch(cfg.ajaxurl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString() })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (res && res.success && res.data && res.data.redirect) {
                            window.location.href = res.data.redirect;
                        } else {
                            if (msg) msg.textContent = (res && res.data && res.data.message) ? res.data.message : 'Något gick fel.';
                            inbarBtn.disabled = false;
                            inbarBtn.textContent = original;
                        }
                    })
                    .catch(function () {
                        if (msg) msg.textContent = 'Nätverksfel. Försök igen.';
                        inbarBtn.disabled = false;
                        inbarBtn.textContent = original;
                    });
            });
        }
    }
    function initAll(root) { (root || document).querySelectorAll('[data-nv-satc]').forEach(initBar); }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(document); });
    } else { initAll(document); }
    if (window.jQuery) {
        window.jQuery(window).on('elementor/frontend/init', function () {
            if (window.elementorFrontend && window.elementorFrontend.hooks) {
                window.elementorFrontend.hooks.addAction('frontend/element_ready/nv-sticky-atc.default', function ($scope) {
                    initAll($scope[0] || document);
                });
            }
        });
    }
})();
