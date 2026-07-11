(function () {
    'use strict';
    function initShoppable(box) {
        if (box.__nvShop) return;
        box.__nvShop = true;
        var rail = box.querySelector('[data-nv-rail]');
        var hotspots = box.querySelectorAll('[data-nv-hotspot]');
        if (!rail) return;
        hotspots.forEach(function (h) {
            h.addEventListener('click', function () {
                var idx = h.getAttribute('data-nv-hotspot');
                var card = rail.querySelector('[data-nv-card="' + idx + '"]');
                if (!card) return;
                rail.scrollTo({ left: card.offsetLeft - rail.offsetLeft - 8, behavior: 'smooth' });
                card.classList.add('is-flash');
                setTimeout(function () { card.classList.remove('is-flash'); }, 900);
            });
        });
    }
    function initAll(root) { (root || document).querySelectorAll('[data-nv-shoppable]').forEach(initShoppable); }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(document); });
    } else { initAll(document); }
    if (window.jQuery) {
        window.jQuery(window).on('elementor/frontend/init', function () {
            if (window.elementorFrontend && window.elementorFrontend.hooks) {
                window.elementorFrontend.hooks.addAction('frontend/element_ready/nv-shoppable-video.default', function ($scope) {
                    initAll($scope[0] || document);
                });
            }
        });
    }
})();
