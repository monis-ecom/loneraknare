(function () {
    'use strict';
    function initFigure(fig) {
        if (fig.__nvHsInit) return;
        fig.__nvHsInit = true;
        var hover = fig.getAttribute('data-trigger') === 'hover';
        var spots = Array.prototype.slice.call(fig.querySelectorAll('.nv-pw-hs__spot'));

        function closeAll(except) {
            spots.forEach(function (s) {
                if (s === except) return;
                s.classList.remove('is-open');
                var b = s.querySelector('.nv-pw-hs__pin');
                if (b) b.setAttribute('aria-expanded', 'false');
            });
        }

        spots.forEach(function (spot) {
            var pin = spot.querySelector('.nv-pw-hs__pin');
            if (!pin) return;
            if (hover) {
                // Hover mode is handled in CSS; still allow click/tap on touch devices.
                pin.addEventListener('click', function () {
                    var open = spot.classList.toggle('is-open');
                    pin.setAttribute('aria-expanded', open ? 'true' : 'false');
                    if (open) closeAll(spot);
                });
                return;
            }
            pin.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = spot.classList.toggle('is-open');
                pin.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (open) closeAll(spot);
            });
        });

        if (!hover) {
            document.addEventListener('click', function (e) {
                if (!fig.contains(e.target)) closeAll(null);
            });
        }
        fig.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeAll(null);
        });
    }
    function initAll(root) {
        (root || document).querySelectorAll('[data-nv-hotspots]').forEach(initFigure);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(document); });
    } else { initAll(document); }
    if (window.jQuery) {
        window.jQuery(window).on('elementor/frontend/init', function () {
            if (window.elementorFrontend && window.elementorFrontend.hooks) {
                window.elementorFrontend.hooks.addAction('frontend/element_ready/nv-hotspots.default', function ($scope) {
                    initAll($scope[0] || document);
                });
            }
        });
    }
})();
