(function () {
    'use strict';

    function storageKey(pop) { return 'nvpop_' + (pop.getAttribute('data-key') || 'default'); }

    function recentlyShown(pop) {
        var freq = parseInt(pop.getAttribute('data-freq'), 10);
        if (isNaN(freq)) freq = 7;
        try {
            if (freq === 0) {
                return sessionStorage.getItem(storageKey(pop)) === '1';
            }
            var last = parseInt(localStorage.getItem(storageKey(pop)) || '0', 10);
            if (!last) return false;
            var days = (Date.now() - last) / 86400000;
            return days < freq;
        } catch (e) { return false; }
    }

    function markShown(pop) {
        var freq = parseInt(pop.getAttribute('data-freq'), 10);
        try {
            if (freq === 0) sessionStorage.setItem(storageKey(pop), '1');
            else localStorage.setItem(storageKey(pop), String(Date.now()));
        } catch (e) {}
    }

    function initPop(pop) {
        if (pop.__nvPopInit) return;
        pop.__nvPopInit = true;
        if (pop.classList.contains('nv-pw-pop--preview')) return; // Elementor editor
        if (recentlyShown(pop)) return;

        var opened = false;
        function open() {
            if (opened) return;
            opened = true;
            pop.classList.add('is-open');
            document.documentElement.style.overflow = 'hidden';
            markShown(pop);
            var field = pop.querySelector('[data-nv-field]');
            if (field) { try { field.focus(); } catch (e) {} }
            cleanup();
        }
        function close() {
            pop.classList.remove('is-open');
            document.documentElement.style.overflow = '';
        }

        var trigger = pop.getAttribute('data-trigger') || 'time';
        var timer = null;
        function onScroll() {
            var depth = parseInt(pop.getAttribute('data-scroll'), 10) || 40;
            var scrolled = (window.scrollY + window.innerHeight) / document.documentElement.scrollHeight * 100;
            if (scrolled >= depth) open();
        }
        function onExit(e) {
            if (e.clientY <= 0) open();
        }
        function cleanup() {
            if (timer) clearTimeout(timer);
            window.removeEventListener('scroll', onScroll);
            document.removeEventListener('mouseout', onExit);
        }

        if (trigger === 'scroll') {
            window.addEventListener('scroll', onScroll, { passive: true });
        } else if (trigger === 'exit') {
            if (window.matchMedia && window.matchMedia('(min-width: 768px)').matches) {
                document.addEventListener('mouseout', onExit);
            } else {
                var d = parseInt(pop.getAttribute('data-delay'), 10);
                timer = setTimeout(open, (isNaN(d) ? 8 : Math.max(d, 8)) * 1000);
            }
        } else {
            var delay = parseInt(pop.getAttribute('data-delay'), 10);
            timer = setTimeout(open, (isNaN(delay) ? 5 : delay) * 1000);
        }

        pop.querySelectorAll('[data-nv-pop-close]').forEach(function (el) {
            el.addEventListener('click', close);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && pop.classList.contains('is-open')) close();
        });
    }

    function initAll(root) { (root || document).querySelectorAll('[data-nv-pop]').forEach(initPop); }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(document); });
    } else { initAll(document); }

    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/nv-email-popup.default', function ($scope) { initAll(($scope && $scope[0]) || document); });
    }
})();
