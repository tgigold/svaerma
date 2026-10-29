// Small global behaviours, loaded on every page.
document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  // <form data-confirm="Wirklich?"> asks before submitting (no inline JS because of the CSP).
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.dataset.confirm)) e.preventDefault();
    });
  });

  // Read-only fields (e.g. a new API token) select their content on focus for easy copying.
  document.querySelectorAll('[data-select-on-focus]').forEach(function (input) {
    input.addEventListener('focus', function () { input.select(); });
  });
});
