(function () {
    'use strict';
    function fmt(n, decimals, tsep, dsep, prefix, suffix) {
        var fixed = n.toFixed(decimals);
        var parts = fixed.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, tsep || '');
        return prefix + parts.join(dsep || '.') + suffix;
    }
    function run(el) {
        if (el.__nvCounted) return;
        el.__nvCounted = true;
        var to = parseFloat(el.getAttribute('data-to')) || 0;
        var decimals = parseInt(el.getAttribute('data-decimals'), 10) || 0;
        var tsep = el.getAttribute('data-tsep') || '';
        var dsep = el.getAttribute('data-dsep') || '.';
        var prefix = el.getAttribute('data-prefix') || '';
        var suffix = el.getAttribute('data-suffix') || '';
        var dur = parseInt(el.getAttribute('data-duration'), 10) || 2000;

        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce || dur <= 0) { el.textContent = fmt(to, decimals, tsep, dsep, prefix, suffix); return; }

        var start = null;
        function ease(t) { return 1 - Math.pow(1 - t, 3); }
        function tick(ts) {
            if (start === null) start = ts;
            var p = Math.min((ts - start) / dur, 1);
            el.textContent = fmt(ease(p) * to, decimals, tsep, dsep, prefix, suffix);
            if (p < 1) { window.requestAnimationFrame(tick); }
            else { el.textContent = fmt(to, decimals, tsep, dsep, prefix, suffix); }
        }
        window.requestAnimationFrame(tick);
    }
    function observe(container) {
        var nums = Array.prototype.slice.call(container.querySelectorAll('[data-nv-count]'));
        if (!nums.length) return;
        if (!('IntersectionObserver' in window)) { nums.forEach(run); return; }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    container.querySelectorAll('[data-nv-count]').forEach(run);
                    io.disconnect();
                }
            });
        }, { threshold: 0.35 });
        io.observe(container);
    }
    function initAll(root) { (root || document).querySelectorAll('[data-nv-stats]').forEach(observe); }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(document); });
    } else { initAll(document); }
    if (window.jQuery) {
        window.jQuery(window).on('elementor/frontend/init', function () {
            if (window.elementorFrontend && window.elementorFrontend.hooks) {
                window.elementorFrontend.hooks.addAction('frontend/element_ready/nv-stats-counter.default', function ($scope) {
                    initAll($scope[0] || document);
                });
            }
        });
    }
})();
