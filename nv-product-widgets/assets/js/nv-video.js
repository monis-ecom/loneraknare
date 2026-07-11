(function () {
    'use strict';

    function activate(holder) {
        if (holder.classList.contains('is-playing')) return;
        var type = holder.getAttribute('data-type');
        var embed = holder.getAttribute('data-embed');
        var file = holder.getAttribute('data-file');
        var node;
        if (type === 'file' && file) {
            node = document.createElement('video');
            node.src = file;
            node.controls = true;
            node.autoplay = true;
            node.setAttribute('playsinline', '');
            node.className = 'nv-pw-video__el';
        } else if (embed) {
            node = document.createElement('iframe');
            node.src = embed;
            node.className = 'nv-pw-video__el';
            node.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture');
            node.setAttribute('allowfullscreen', '');
            node.setAttribute('frameborder', '0');
            node.setAttribute('title', 'Video');
        } else {
            return;
        }
        holder.classList.add('is-playing');
        holder.appendChild(node);
    }

    function initVideos(root) {
        (root || document).querySelectorAll('.nv-pw-video .nv-pw-video__play').forEach(function (btn) {
            if (btn.__nvVid) return;
            btn.__nvVid = true;
            btn.addEventListener('click', function () { activate(btn.closest('.nv-pw-video')); });
        });
    }

    function initSliders(root) {
        (root || document).querySelectorAll('.nv-pw-vs[data-nv-video]').forEach(function (vs) {
            if (vs.__nvSlider) return;
            vs.__nvSlider = true;
            var track = vs.querySelector('[data-nv-track]');
            var prev = vs.querySelector('[data-nv-prev]');
            var next = vs.querySelector('[data-nv-next]');
            if (!track) return;
            function step() {
                var card = track.querySelector('.nv-pw-vs__card');
                return card ? (card.getBoundingClientRect().width + 16) : 260;
            }
            if (prev) prev.addEventListener('click', function () { track.scrollBy({ left: -step() * 1.5, behavior: 'smooth' }); });
            if (next) next.addEventListener('click', function () { track.scrollBy({ left: step() * 1.5, behavior: 'smooth' }); });
        });
    }

    function initAll(root) { initVideos(root); initSliders(root); }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(document); });
    } else { initAll(document); }

    if (window.jQuery) {
        window.jQuery(window).on('elementor/frontend/init', function () {
            if (window.elementorFrontend && window.elementorFrontend.hooks) {
                ['nv-video-slider', 'nv-video-text'].forEach(function (name) {
                    window.elementorFrontend.hooks.addAction('frontend/element_ready/' + name + '.default', function ($scope) {
                        initAll($scope[0] || document);
                    });
                });
            }
        });
    }
})();
