(function($) {
  'use strict';

  function initGallery(container) {
    var track = container.querySelector('.nv-pw-gallery__track');
    var slides = container.querySelectorAll('.nv-pw-gallery__slide');
    var dots = container.querySelectorAll('.nv-pw-gallery__dot');
    var thumbs = container.querySelectorAll('.nv-pw-gallery__thumb');
    if (!track || slides.length < 2) return;

    function goTo(index) {
      var slide = slides[index];
      if (!slide) return;
      track.scrollTo({ left: slide.offsetLeft, behavior: 'smooth' });
      updateActive(index);
    }

    function updateActive(index) {
      dots.forEach(function(d, i) { d.classList.toggle('is-active', i === index); });
      thumbs.forEach(function(t, i) { t.classList.toggle('is-active', i === index); });
    }

    // Scroll-snap observer
    var scrollTimer;
    track.addEventListener('scroll', function() {
      clearTimeout(scrollTimer);
      scrollTimer = setTimeout(function() {
        var trackRect = track.getBoundingClientRect();
        var center = trackRect.left + trackRect.width / 2;
        var closest = 0;
        var closestDist = Infinity;
        slides.forEach(function(s, i) {
          var rect = s.getBoundingClientRect();
          var dist = Math.abs(rect.left + rect.width / 2 - center);
          if (dist < closestDist) { closestDist = dist; closest = i; }
        });
        updateActive(closest);
      }, 80);
    }, { passive: true });

    dots.forEach(function(dot) {
      dot.addEventListener('click', function() { goTo(parseInt(this.dataset.index, 10)); });
    });

    thumbs.forEach(function(thumb) {
      thumb.addEventListener('click', function() { goTo(parseInt(this.dataset.index, 10)); });
    });

    // Listen for WooCommerce variation changes
    var form = document.querySelector('form.variations_form');
    if (form && $) {
      $(form).on('found_variation', function(e, variation) {
        if (variation && variation.image && variation.image.full_src) {
          var firstImg = slides[0] ? slides[0].querySelector('img') : null;
          if (firstImg) {
            firstImg.src = variation.image.full_src;
            firstImg.srcset = variation.image.srcset || '';
          }
          var firstThumb = thumbs[0] ? thumbs[0].querySelector('img') : null;
          if (firstThumb && variation.image.thumb_src) {
            firstThumb.src = variation.image.thumb_src;
          }
          goTo(0);
        }
      });
      $(form).on('reset_data', function() {
        // Could restore original images, but scroll to first is sufficient
        goTo(0);
      });
    }
  }

  function init() {
    document.querySelectorAll('.nv-pw-gallery').forEach(initGallery);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window.jQuery);
