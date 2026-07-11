/**
 * NV Bundle Selector — Frontend JavaScript
 * Version: 1.4.0
 *
 * Handles:
 *  - Visual card selection (click to highlight a bundle card)
 *  - Add-to-cart button:
 *      • If product ID is set → WooCommerce AJAX add-to-cart
 *      • If no product ID    → smooth scroll to main form (variable product flow)
 */
(function () {
  'use strict';

  /**
   * Initialise all bundle selector widgets on the page.
   * Called on DOMContentLoaded and also on Elementor frontend init
   * so it works both in the editor preview and on the live front-end.
   */
  function initAllBundles() {
    var widgets = document.querySelectorAll('.nv-pw-bundle');
    widgets.forEach(function (widget) {
      initBundle(widget);
    });
  }

  function initBundle(widget) {
    /* Prevent double-init */
    if (widget.dataset.nvBundleInit === '1') {
      return;
    }
    widget.dataset.nvBundleInit = '1';

    var cards = widget.querySelectorAll('.nv-pw-bundle__card');
    if (!cards.length) {
      return;
    }

    /* ── Card selection ── */
    cards.forEach(function (card) {
      card.addEventListener('click', function (e) {
        /* Ignore click if it's directly on the button — button handles its own click */
        if (e.target.closest('.nv-pw-bundle__btn')) {
          return;
        }
        selectCard(cards, card);
      });
    });

    /* ── Button clicks ── */
    var buttons = widget.querySelectorAll('.nv-pw-bundle__btn');
    buttons.forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();

        var card      = btn.closest('.nv-pw-bundle__card');
        var productId = parseInt(btn.dataset.productId, 10) || 0;

        /* Select the card visually */
        if (card) {
          selectCard(cards, card);
        }

        if (productId > 0) {
          addToCart(btn, productId);
        } else {
          scrollToMainForm();
        }
      });
    });
  }

  /* ── Highlight the clicked card, un-highlight others ─── */
  function selectCard(allCards, activeCard) {
    allCards.forEach(function (c) {
      c.classList.remove('is-selected');
    });
    activeCard.classList.add('is-selected');
  }

  /* ── AJAX add-to-cart ─── */
  function addToCart(btn, productId) {
    var originalText = btn.textContent;

    btn.classList.add('is-loading');
    btn.textContent  = '…';
    btn.disabled     = true;

    fetch('/?wc-ajax=add_to_cart', {
      method:  'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body:    'product_id=' + productId + '&quantity=1'
    })
      .then(function (res) {
        if (!res.ok) throw new Error('Network error');
        return res.json();
      })
      .then(function (data) {
        btn.classList.remove('is-loading');
        btn.disabled = false;

        if (data && data.error) {
          /* Cart error — scroll to main form so WC can show messages */
          scrollToMainForm();
          btn.textContent = originalText;
          return;
        }

        /* Success */
        btn.textContent = '✓ Tillagd!';
        btn.classList.add('is-added');

        /* Refresh WooCommerce fragments (mini-cart count etc.) */
        if (window.jQuery && window.jQuery.fn) {
          window.jQuery(document.body).trigger('wc_fragment_refresh');
        }

        setTimeout(function () {
          btn.textContent = originalText;
          btn.classList.remove('is-added');
        }, 2400);
      })
      .catch(function () {
        btn.classList.remove('is-loading');
        btn.disabled    = false;
        btn.textContent = originalText;
        scrollToMainForm();
      });
  }

  /* ── Scroll to main WooCommerce add-to-cart form ─── */
  function scrollToMainForm() {
    var form = (
      document.querySelector('form.cart') ||
      document.querySelector('.woocommerce-product-gallery') ||
      document.querySelector('.entry-summary')
    );
    if (form) {
      form.scrollIntoView({ behavior: 'smooth', block: 'center' });
      /* Brief teal glow to attract the eye */
      form.style.transition = 'box-shadow 0.3s ease';
      form.style.boxShadow  = '0 0 0 3px rgba(15,118,110,0.35)';
      setTimeout(function () {
        form.style.boxShadow = '';
      }, 1400);
    }
  }

  /* ── Boot ── */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAllBundles);
  } else {
    initAllBundles();
  }

  /* Also hook into Elementor's frontend init so it works in the preview pane */
  if (window.elementorFrontend) {
    window.elementorFrontend.hooks.addAction('frontend/element_ready/nv-bundle-selector.default', function ($scope) {
      var widget = $scope[0];
      if (widget) {
        widget.dataset.nvBundleInit = ''; /* reset so initBundle runs */
        initBundle(widget.querySelector('.nv-pw-bundle') || widget);
      }
    });
  } else {
    document.addEventListener('elementor/frontend/init', initAllBundles);
  }

}());
