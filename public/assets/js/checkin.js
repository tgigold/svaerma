// Check-in page: geolocation → nearby places (Overpass) / search (Photon/Nominatim) → confirm.
document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  var root = document.getElementById('checkin');
  if (!root) return;

  var baseRadius = parseInt(root.dataset.radius, 10) || 150;
  var maxRadius = parseInt(root.dataset.maxRadius, 10) || 1000;
  var radius = baseRadius;
  var position = null; // {lat, lon, accuracy}

  var statusEl = document.getElementById('geo-status');
  var listEl = document.getElementById('places');
  var messageEl = document.getElementById('places-message');
  var widenBtn = document.getElementById('widen');
  var form = document.getElementById('checkin-form');
  var confirmEl = document.getElementById('confirm');
  var offcanvas = window.bootstrap ? new bootstrap.Offcanvas(confirmEl) : null;

  form.elements.timezone.value = Intl.DateTimeFormat().resolvedOptions().timeZone || '';

  function el(tag, className, text) {
    var node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined && text !== null) node.textContent = text;
    return node;
  }

  function setStatus(text, spinning) {
    statusEl.textContent = '';
    if (spinning) {
      var s = el('span', 'spinner-border spinner-border-sm me-1');
      s.setAttribute('aria-hidden', 'true');
      statusEl.appendChild(s);
    }
    statusEl.appendChild(document.createTextNode(text));
  }

  function showMessage(text, type) {
    messageEl.className = 'alert alert-' + (type || 'secondary');
    messageEl.textContent = text;
  }

  function hideMessage() {
    messageEl.className = 'alert d-none';
  }

  function formatDistance(m) {
    if (m === null || m === undefined) return '';
    return m < 1000 ? m + ' m' : (m / 1000).toFixed(1).replace('.', ',') + ' km';
  }

  function getJson(url) {
    return fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
      .then(function (res) {
        return res.json().catch(function () { return {}; }).then(function (data) {
          if (res.status === 401) {
            window.location.href = '/login';
          }
          if (!res.ok) throw new Error(data.error || 'Fehler ' + res.status);
          return data;
        });
      });
  }

  function renderPlaces(places, emptyText) {
    listEl.textContent = '';
    if (!places.length) {
      showMessage(emptyText, 'secondary');
      return;
    }
    hideMessage();
    places.forEach(function (p) {
      var item = el('button', 'list-group-item list-group-item-action place-item d-flex justify-content-between align-items-start gap-2');
      item.type = 'button';

      var body = el('div', 'flex-grow-1');
      body.appendChild(el('div', 'fw-semibold', p.name));
      var meta = [p.category, p.address].filter(Boolean).join(' · ');
      if (meta) body.appendChild(el('div', 'small text-body-secondary', meta));
      if (p.visits > 0) {
        var visit = el('div', 'small text-success');
        visit.appendChild(el('i', 'bi bi-clock-history me-1'));
        visit.appendChild(document.createTextNode('zuletzt ' + p.last_visit + (p.visits > 1 ? ' · ' + p.visits + '×' : '')));
        body.appendChild(visit);
      }
      item.appendChild(body);
      if (p.distance !== null && p.distance !== undefined) {
        item.appendChild(el('span', 'badge text-bg-light border place-distance', formatDistance(p.distance)));
      }
      item.addEventListener('click', function () { choose(p); });
      listEl.appendChild(item);
    });
  }

  function choose(p) {
    form.elements.lat.value = p.lat;
    form.elements.lon.value = p.lon;
    form.elements.place_name.value = p.name;
    form.elements.osm_type.value = p.osm_type || '';
    form.elements.osm_id.value = p.osm_id || '';
    document.getElementById('confirm-title').textContent = p.name;
    var meta = [p.category, p.address].filter(Boolean).join(' · ');
    if (!p.osm_type) meta = 'Eigener Ort · aktueller Standort';
    document.getElementById('confirm-meta').textContent = meta;
    if (offcanvas) {
      offcanvas.show();
    } else {
      confirmEl.classList.add('show');
    }
  }

  function loadNearby() {
    if (!position) return;
    // Never search a smaller area than the GPS uncertainty.
    var r = Math.min(maxRadius, Math.max(radius, Math.round(position.accuracy || 0)));
    showMessage('Suche Orte im Umkreis von ' + r + ' m …', 'light');
    listEl.textContent = '';
    getJson('/places/nearby?lat=' + position.lat + '&lon=' + position.lon + '&radius=' + r)
      .then(function (data) {
        renderPlaces(data.places, 'Keine Orte im Umkreis von ' + r + ' m gefunden. Erweitere den Umkreis, suche nach dem Namen oder lege einen eigenen Ort an.');
        widenBtn.classList.toggle('d-none', r >= maxRadius);
      })
      .catch(function (err) {
        showMessage(err.message, 'warning');
      });
  }

  function locate() {
    if (!navigator.geolocation) {
      setStatus('Dein Browser unterstützt keine Standortbestimmung. Nutze die Suche.', false);
      return;
    }
    setStatus('Standort wird ermittelt …', true);
    navigator.geolocation.getCurrentPosition(function (pos) {
      position = { lat: pos.coords.latitude, lon: pos.coords.longitude, accuracy: pos.coords.accuracy };
      setStatus('Standort ±' + Math.round(pos.coords.accuracy) + ' m', false);
      radius = baseRadius;
      loadNearby();
    }, function (err) {
      var text = err.code === 1
        ? 'Standortzugriff verweigert. Erlaube ihn in den Browser-Einstellungen oder nutze die Suche.'
        : 'Standort konnte nicht ermittelt werden. Nutze die Suche.';
      setStatus(text, false);
    }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 30000 });
  }

  document.getElementById('geo-refresh').addEventListener('click', locate);

  widenBtn.addEventListener('click', function () {
    radius = Math.min(maxRadius, radius * 2);
    loadNearby();
  });

  document.getElementById('search-form').addEventListener('submit', function (e) {
    e.preventDefault();
    var q = document.getElementById('search-q').value.trim();
    if (q.length < 2) return;
    var url = '/places/search?q=' + encodeURIComponent(q);
    if (position) url += '&lat=' + position.lat + '&lon=' + position.lon;
    showMessage('Suche „' + q + '“ …', 'light');
    listEl.textContent = '';
    widenBtn.classList.add('d-none');
    getJson(url)
      .then(function (data) { renderPlaces(data.places, 'Nichts gefunden für „' + q + '“.'); })
      .catch(function (err) { showMessage(err.message, 'warning'); });
  });

  document.getElementById('manual-use').addEventListener('click', function () {
    var name = document.getElementById('manual-name').value.trim();
    if (!name) {
      document.getElementById('manual-name').focus();
      return;
    }
    if (!position) {
      showMessage('Für einen eigenen Ort brauche ich deinen Standort.', 'warning');
      return;
    }
    choose({ name: name, lat: position.lat, lon: position.lon });
  });

  locate();
});
