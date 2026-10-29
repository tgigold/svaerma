// Follow the system colour scheme (Bootstrap 5.3 colour modes).
(function () {
  var media = window.matchMedia('(prefers-color-scheme: dark)');
  function apply() {
    document.documentElement.setAttribute('data-bs-theme', media.matches ? 'dark' : 'light');
  }
  apply();
  media.addEventListener('change', apply);
})();
