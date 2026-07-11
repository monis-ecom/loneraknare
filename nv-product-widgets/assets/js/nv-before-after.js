(function () {
    'use strict';

    function clamp(v, min, max) { return v < min ? min : (v > max ? max : v); }

    function initSlider(el) {
        if (el.__nvBaInit) return;
        el.__nvBaInit = true;

        var vertical = el.getAttribute('data-orientation') === 'vertical';
        var handle = el.querySelector('.nv-pw-ba__handle');
        var dragging = false;

        function setPos(pct) {
            pct = clamp(pct, 0, 100);
            el.style.setProperty('--nv-ba-pos', pct + '%');
            if (handle) handle.setAttribute('aria-valuenow', Math.round(pct));
        }

        function posFromEvent(e) {
            var rect = el.getBoundingClientRect();
            var point = (e.touches && e.touches[0]) ? e.touches[0] : e;
            if (vertical) {
                return ((point.clientY - rect.top) / rect.height) * 100;
            }
            return ((point.clientX - rect.left) / rect.width) * 100;
        }

        function onMove(e) {
            if (!dragging) return;
            setPos(posFromEvent(e));
            if (e.cancelable) e.preventDefault();
        }
        function stop() { dragging = false; el.classList.remove('is-dragging'); }
        function start(e) {
            dragging = true;
            el.classList.add('is-dragging');
            setPos(posFromEvent(e));
        }

        el.addEventListener('mousedown', start);
        window.addEventListener('mousemove', onMove, { passive: false });
        window.addEventListener('mouseup', stop);

        el.addEventListener('touchstart', start, { passive: true });
        window.addEventListener('touchmove', onMove, { passive: false });
        window.addEventListener('touchend', stop);

        if (handle) {
            handle.addEventListener('keydown', function (e) {
                var cur = parseFloat(el.style.getPropertyValue('--nv-ba-pos')) || 50;
                if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { setPos(cur - 2); e.preventDefault(); }
                else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { setPos(cur + 2); e.preventDefault(); }
                else if (e.key === 'Home') { setPos(0); e.preventDefault(); }
                else if (e.key === 'End') { setPos(100); e.preventDefault(); }
            });
        }
    }

    function initAll(root) {
        (root || document).querySelectorAll('[data-nv-ba]').forEach(initSlider);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(document); });
    } else {
        initAll(document);
    }

    // Elementor editor / frontend re-init
    if (window.jQuery) {
        window.jQuery(window).on('elementor/frontend/init', function () {
            if (window.elementorFrontend && window.elementorFrontend.hooks) {
                window.elementorFrontend.hooks.addAction('frontend/element_ready/nv-before-after.default', function ($scope) {
                    initAll($scope[0] || document);
                });
            }
        });
    }
})();
