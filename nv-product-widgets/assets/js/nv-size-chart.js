(function () {
  var modal = document.getElementById('nvPwSizeChartModal');
  if (!modal) {
    return;
  }

  var openButtons = document.querySelectorAll('[data-nv-pw-size-chart-open]');
  var closeButtons = modal.querySelectorAll('[data-nv-pw-size-chart-close]');

  if (!openButtons.length || !closeButtons.length) {
    return;
  }

  var isOpen = false;

  var openModal = function () {
    if (isOpen) {
      return;
    }
    isOpen = true;
    modal.hidden = false;
    document.documentElement.classList.add('nv-pw-size-chart-open');
    document.body.classList.add('nv-pw-size-chart-open');
  };

  var closeModal = function () {
    if (!isOpen) {
      return;
    }
    isOpen = false;
    modal.hidden = true;
    document.documentElement.classList.remove('nv-pw-size-chart-open');
    document.body.classList.remove('nv-pw-size-chart-open');
  };

  openButtons.forEach(function (button) {
    button.addEventListener('click', openModal);
  });

  closeButtons.forEach(function (button) {
    button.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeModal();
    }
  });
})();
