(function () {
    'use strict';
    function initCarousel(car) {
        if (car.__nvTmInit) return;
        car.__nvTmInit = true;
        var track = car.querySelector('[data-nv-tm-track]');
        if (!track) return;
        var prev = car.querySelector('[data-nv-tm-prev]');
        var next = car.querySelector('[data-nv-tm-next]');
        var dots = Array.prototype.slice.call(car.querySelectorAll('[data-nv-tm-dot]'));
        var cards = Array.prototype.slice.call(track.children);
        if (!cards.length) return;

        function step() {
            return cards.length > 1 ? (cards[1].offsetLeft - cards[0].offsetLeft) : cards[0].offsetWidth;
        }
        function centerOf(card) {
            return card.offsetLeft - (track.clientWidth - card.offsetWidth) / 2;
        }
        function activeIndex() {
            var mid = track.scrollLeft + track.clientWidth / 2;
            var best = 0, bestDist = Infinity;
            cards.forEach(function (card, i) {
                var c = card.offsetLeft + card.offsetWidth / 2;
                var d = Math.abs(c - mid);
                if (d < bestDist) { bestDist = d; best = i; }
            });
            return best;
        }
        function update() {
            var i = activeIndex();
            dots.forEach(function (d, di) {
                var on = di === i;
                d.classList.toggle('is-active', on);
                d.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            var atStart = track.scrollLeft <= 2;
            var atEnd = track.scrollLeft >= (track.scrollWidth - track.clientWidth - 2);
            if (prev) prev.disabled = atStart;
            if (next) next.disabled = atEnd;
        }

        if (prev) prev.addEventListener('click', function () { track.scrollBy({ left: -step(), behavior: 'smooth' }); });
        if (next) next.addEventListener('click', function () { track.scrollBy({ left: step(), behavior: 'smooth' }); });
        dots.forEach(function (dot, i) {
            dot.addEventListener('click', function () {
                if (cards[i]) track.scrollTo({ left: centerOf(cards[i]), behavior: 'smooth' });
            });
        });
        track.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowRight') { track.scrollBy({ left: step(), behavior: 'smooth' }); e.preventDefault(); }
            else if (e.key === 'ArrowLeft') { track.scrollBy({ left: -step(), behavior: 'smooth' }); e.preventDefault(); }
        });

        var ticking = false;
        track.addEventListener('scroll', function () {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(function () { update(); ticking = false; });
        }, { passive: true });
        window.addEventListener('resize', update);
        update();

        // Autoplay (opt-in, honours reduced-motion; pauses on interaction)
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (car.getAttribute('data-nv-tm-autoplay') === '1' && !reduce) {
            var interval = parseInt(car.getAttribute('data-nv-tm-interval'), 10);
            if (isNaN(interval) || interval < 1500) interval = 5000;
            var timer = null;
            function advance() {
                var atEnd = track.scrollLeft >= (track.scrollWidth - track.clientWidth - 2);
                if (atEnd) { track.scrollTo({ left: 0, behavior: 'smooth' }); }
                else { track.scrollBy({ left: step(), behavior: 'smooth' }); }
            }
            function play() { stop(); timer = window.setInterval(advance, interval); }
            function stop() { if (timer) { window.clearInterval(timer); timer = null; } }
            car.addEventListener('mouseenter', stop);
            car.addEventListener('mouseleave', play);
            car.addEventListener('focusin', stop);
            car.addEventListener('focusout', play);
            car.addEventListener('touchstart', stop, { passive: true });
            document.addEventListener('visibilitychange', function () { document.hidden ? stop() : play(); });
            play();
        }
    }
    function initAll(root) { (root || document).querySelectorAll('[data-nv-tm-carousel]').forEach(initCarousel); }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(document); });
    } else { initAll(document); }
    if (window.jQuery) {
        window.jQuery(window).on('elementor/frontend/init', function () {
            if (window.elementorFrontend && window.elementorFrontend.hooks) {
                window.elementorFrontend.hooks.addAction('frontend/element_ready/nv-testimonials.default', function ($scope) {
                    initAll($scope[0] || document);
                });
            }
        });
    }
})();
