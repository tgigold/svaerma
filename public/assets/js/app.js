// Small global behaviours, loaded on every page.
document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  // <form data-confirm="Wirklich?"> asks before submitting (no inline JS because of the CSP).
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.dataset.confirm)) e.preventDefault();
    });
  });
});
