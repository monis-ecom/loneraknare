(function () {
    'use strict';
    function initTabs(box) {
        if (box.__nvTabsInit) return;
        box.__nvTabsInit = true;
        var tabs = box.querySelectorAll('[data-nv-tab]');
        var panels = box.querySelectorAll('[data-nv-panel]');
        function activate(i) {
            tabs.forEach(function (t) {
                var on = t.getAttribute('data-nv-tab') === String(i);
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            panels.forEach(function (p) {
                var on = p.getAttribute('data-nv-panel') === String(i);
                p.classList.toggle('is-active', on);
                if (on) { p.removeAttribute('hidden'); } else { p.setAttribute('hidden', ''); }
            });
        }
        tabs.forEach(function (t) {
            t.addEventListener('click', function () { activate(t.getAttribute('data-nv-tab')); });
            t.addEventListener('keydown', function (e) {
                var idx = parseInt(t.getAttribute('data-nv-tab'), 10);
                if (e.key === 'ArrowRight' && tabs[idx + 1]) { tabs[idx + 1].focus(); activate(idx + 1); e.preventDefault(); }
                else if (e.key === 'ArrowLeft' && tabs[idx - 1]) { tabs[idx - 1].focus(); activate(idx - 1); e.preventDefault(); }
            });
        });
    }
    function initAll(root) { (root || document).querySelectorAll('[data-nv-tabs]').forEach(initTabs); }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(document); });
    } else { initAll(document); }
    if (window.jQuery) {
        window.jQuery(window).on('elementor/frontend/init', function () {
            if (window.elementorFrontend && window.elementorFrontend.hooks) {
                window.elementorFrontend.hooks.addAction('frontend/element_ready/nv-tabs.default', function ($scope) {
                    initAll($scope[0] || document);
                });
            }
        });
    }
})();
