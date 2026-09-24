(() => {
  'use strict';
  const tabs = document.querySelector('[data-property-tabs]');
  if (tabs) {
    const panels = [...document.querySelectorAll('[data-property-panel]')];
    const links = [...tabs.querySelectorAll('a')];
    const select = () => {
      const name = location.hash.slice(1);
      const active = panels.some(p => p.id === name) ? name : 'overview';
      panels.forEach(p => { p.hidden = p.id !== active; });
      links.forEach(a => {
        const selected = a.hash === '#' + active;
        a.classList.toggle('active', selected);
        if (selected) a.setAttribute('aria-current', 'page');
        else a.removeAttribute('aria-current');
      });
      if (active === 'overview') window.dispatchEvent(new Event('property-map-resize'));
    };
    window.addEventListener('hashchange', select);
    select();
    document.querySelector('[data-transfer]')?.addEventListener('click', () => {
      const form = document.querySelector('#ownership details');
      if (form) form.open = true;
    });
  }
  const el = document.getElementById('parcel-mini-map');
  if (!el || !window.L) return;
  const geometry = JSON.parse(el.dataset.geometry || 'null');
  if (!geometry) return;
  const map = L.map(el, {scrollWheelZoom: false});
  const shape = L.geoJSON(geometry, {
    style: {color: '#0c6848', weight: 3, fillOpacity: .2},
    pointToLayer: (_, latlng) => L.circleMarker(latlng, {radius: 9, color: '#fff', weight: 3, fillColor: '#0c6848', fillOpacity: 1})
  }).addTo(map);
  map.fitBounds(shape.getBounds(), {padding: [35, 35], maxZoom: 17});
  const tiles = L.tileLayer(el.dataset.tiles, {maxZoom: 20, maxNativeZoom: 19, attribution: el.dataset.attribution}).addTo(map);
  tiles.on('tileerror', () => { document.getElementById('parcel-map-notice').hidden = false; });
  window.addEventListener('property-map-resize', () => requestAnimationFrame(() => map.invalidateSize()));
})();
