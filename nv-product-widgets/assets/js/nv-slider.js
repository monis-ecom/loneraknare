(function () {
    'use strict';
    function initSlider(box) {
        if (box.__nvSlider) return;
        box.__nvSlider = true;
        var track = box.querySelector('[data-nv-track]');
        if (!track) return;
        var prev = box.querySelector('[data-nv-prev]');
        var next = box.querySelector('[data-nv-next]');
        var dotsWrap = box.querySelector('[data-nv-dots]');
        var slides = Array.prototype.slice.call(track.children);

        function step() {
            var s = track.querySelector('.nv-pw-cs__slide');
            return s ? (s.getBoundingClientRect().width + 16) : 280;
        }
        if (prev) prev.addEventListener('click', function () { track.scrollBy({ left: -step(), behavior: 'smooth' }); });
        if (next) next.addEventListener('click', function () { track.scrollBy({ left: step(), behavior: 'smooth' }); });

        if (dotsWrap && slides.length) {
            slides.forEach(function (sl, i) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'nv-pw-cs__dot';
                b.setAttribute('aria-label', 'Slide ' + (i + 1));
                b.addEventListener('click', function () { track.scrollTo({ left: step() * i, behavior: 'smooth' }); });
                dotsWrap.appendChild(b);
            });
            var dots = Array.prototype.slice.call(dotsWrap.children);
            function sync() {
                var idx = Math.round(track.scrollLeft / step());
                dots.forEach(function (d, i) { d.classList.toggle('is-on', i === idx); });
            }
            track.addEventListener('scroll', function () { window.requestAnimationFrame(sync); }, { passive: true });
            sync();
        }

        if (box.getAttribute('data-autoplay') === '1') {
            var timer = setInterval(function () {
                if (track.scrollLeft + track.clientWidth >= track.scrollWidth - 4) {
                    track.scrollTo({ left: 0, behavior: 'smooth' });
                } else {
                    track.scrollBy({ left: step(), behavior: 'smooth' });
                }
            }, 4000);
            box.addEventListener('mouseenter', function () { clearInterval(timer); });
        }
    }
    function initAll(root) { (root || document).querySelectorAll('[data-nv-slider]').forEach(initSlider); }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(document); });
    } else { initAll(document); }
    if (window.jQuery) {
        window.jQuery(window).on('elementor/frontend/init', function () {
            if (window.elementorFrontend && window.elementorFrontend.hooks) {
                window.elementorFrontend.hooks.addAction('frontend/element_ready/nv-content-slider.default', function ($scope) {
                    initAll($scope[0] || document);
                });
            }
        });
    }
})();
