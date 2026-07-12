(function () {
    'use strict';

    var box = null, imgEl = null, capEl = null, current = [], index = 0, lastFocus = null;

    function build() {
        if (box) return;
        box = document.createElement('div');
        box.className = 'nv-pw-glb';
        box.setAttribute('role', 'dialog');
        box.setAttribute('aria-modal', 'true');
        box.innerHTML =
            '<button class="nv-pw-glb__close" type="button" aria-label="Close">✕</button>' +
            '<button class="nv-pw-glb__nav nv-pw-glb__nav--prev" type="button" aria-label="Previous">‹</button>' +
            '<figure class="nv-pw-glb__stage"><img class="nv-pw-glb__img" alt=""><figcaption class="nv-pw-glb__cap"></figcaption></figure>' +
            '<button class="nv-pw-glb__nav nv-pw-glb__nav--next" type="button" aria-label="Next">›</button>';
        document.body.appendChild(box);
        imgEl = box.querySelector('.nv-pw-glb__img');
        capEl = box.querySelector('.nv-pw-glb__cap');
        box.querySelector('.nv-pw-glb__close').addEventListener('click', close);
        box.querySelector('.nv-pw-glb__nav--prev').addEventListener('click', function () { go(-1); });
        box.querySelector('.nv-pw-glb__nav--next').addEventListener('click', function () { go(1); });
        box.addEventListener('click', function (e) { if (e.target === box) close(); });
        document.addEventListener('keydown', function (e) {
            if (!box.classList.contains('is-open')) return;
            if (e.key === 'Escape') close();
            else if (e.key === 'ArrowLeft') go(-1);
            else if (e.key === 'ArrowRight') go(1);
        });
    }

    function show() {
        var item = current[index];
        if (!item) return;
        imgEl.src = item.src;
        imgEl.alt = item.cap || '';
        capEl.textContent = item.cap || '';
        capEl.style.display = item.cap ? '' : 'none';
        var multi = current.length > 1;
        box.querySelector('.nv-pw-glb__nav--prev').style.display = multi ? '' : 'none';
        box.querySelector('.nv-pw-glb__nav--next').style.display = multi ? '' : 'none';
    }

    function go(dir) {
        if (!current.length) return;
        index = (index + dir + current.length) % current.length;
        show();
    }

    function open(items, start) {
        build();
        current = items;
        index = start || 0;
        lastFocus = document.activeElement;
        show();
        box.classList.add('is-open');
        document.documentElement.style.overflow = 'hidden';
        box.querySelector('.nv-pw-glb__close').focus();
    }

    function close() {
        if (!box) return;
        box.classList.remove('is-open');
        document.documentElement.style.overflow = '';
        if (lastFocus && lastFocus.focus) { try { lastFocus.focus(); } catch (e) {} }
    }

    function initGallery(gal) {
        if (gal.__nvGalInit) return;
        gal.__nvGalInit = true;
        var triggers = gal.querySelectorAll('[data-nv-gallery-item]');
        var items = [];
        Array.prototype.forEach.call(gal.querySelectorAll('.nv-pw-gal__img'), function (img) {
            items.push({ src: img.getAttribute('data-nv-gallery-src') || img.src, cap: img.getAttribute('data-nv-gallery-cap') || '' });
        });
        Array.prototype.forEach.call(triggers, function (trigger) {
            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                var i = parseInt(trigger.getAttribute('data-nv-gallery-item'), 10) || 0;
                open(items, i);
            });
        });
    }

    function initAll(root) { (root || document).querySelectorAll('[data-nv-gallery]').forEach(initGallery); }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(document); });
    } else { initAll(document); }

    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/nv-gallery.default', function ($scope) { initAll(($scope && $scope[0]) || document); });
    }
})();
