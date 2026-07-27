/**
 * Residencias Reef Cozumel — front-end behaviour (luxury-villa-theme-core).
 * Mobile drawer toggle + cache-safe inquiry submit with analytics.
 * No dependencies. Enqueued in footer by inc/conversion/inquiry-frontend.php.
 */
(function () {
  'use strict';

  // ── Conversion event tracking (measurement) ───────────────────────
  // ONE collection path per event (audit RRC-020): gtag when present, else
  // dataLayer — firing both double-counted whenever GTM forwarded the
  // dataLayer event into GA4 alongside the direct gtag hit.
  function lvcTrack(name, params) {
    try {
      if (window.gtag) { window.gtag('event', name, params || {}); }
      else if (window.dataLayer) { window.dataLayer.push(Object.assign({ event: name }, params || {})); }
    } catch (_) {}
  }
  document.addEventListener('click', function (e) {
    var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
    if (!a) { return; }
    var href = a.getAttribute('href') || '';
    if (/(^|\/\/)(wa\.me|wa\.link|api\.whatsapp\.com)\//i.test(href) || /[?&]phone=\d/i.test(href)) {
      lvcTrack('whatsapp_click', { link_url: href, location: a.getAttribute('data-lvc-loc') || 'link' });
    } else if (/^tel:/i.test(href)) {
      lvcTrack('call_click', { phone: href.replace(/^tel:/i, '') });
    }
  }, true);


  // ── Mobile nav drawer ──────────────────────────────────────────────────
  var toggle = document.querySelector('[data-lvc-drawer-toggle]');
  var drawer = document.querySelector('[data-lvc-drawer]');
  if (toggle && drawer) {
    toggle.addEventListener('click', function () {
      var closed = drawer.hasAttribute('hidden');
      if (closed) { drawer.removeAttribute('hidden'); } else { drawer.setAttribute('hidden', ''); }
      toggle.setAttribute('aria-expanded', closed ? 'true' : 'false');
    });
  }

  // ── Inquiry forms ──────────────────────────────────────────────────────
  var cfg = window.LVC_INQ || {};
  var forms = document.querySelectorAll('[data-lvc-inquiry]');

  // Attribution capture: first-touch UTMs + landing page persist in
  // sessionStorage so a lead submitted three pages later still carries them.
  var utmKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
  try {
    var qs = new URLSearchParams(window.location.search);
    utmKeys.forEach(function (k) {
      if (qs.get(k) && !sessionStorage.getItem(k)) { sessionStorage.setItem(k, qs.get(k)); }
    });
    if (!sessionStorage.getItem('landing_page')) { sessionStorage.setItem('landing_page', window.location.href); }
    if (!sessionStorage.getItem('lvc_referrer') && document.referrer) { sessionStorage.setItem('lvc_referrer', document.referrer); }
  } catch (e) {}

  var makeUuid = function () {
    if (window.crypto && crypto.randomUUID) { return crypto.randomUUID(); }
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
      var r = Math.random() * 16 | 0;
      return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
    });
  };

  Array.prototype.forEach.call(forms, function (form) {
    // One UUID per form load: retries reuse it, so the server can dedupe
    // (audit RRC-012 — a retry after a mail failure created a second lead).
    var ensureHidden = function (name, value) {
      var input = form.querySelector('input[name="' + name + '"]');
      if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        form.appendChild(input);
      }
      if (value) { input.value = value; }
    };
    ensureHidden('client_uuid', makeUuid());
    try {
      utmKeys.forEach(function (k) { ensureHidden(k, sessionStorage.getItem(k) || ''); });
      ensureHidden('landing_page', sessionStorage.getItem('landing_page') || '');
      ensureHidden('referrer', sessionStorage.getItem('lvc_referrer') || '');
    } catch (e) {}
    // Check-in cannot be in the past; check-out cannot be before check-in.
    var lvcCheckin = form.querySelector('[name="checkin"]');
    var lvcCheckout = form.querySelector('[name="checkout"]');
    if (lvcCheckin && lvcCheckout) {
      var lvcToday = new Date().toISOString().slice(0, 10);
      lvcCheckin.min = lvcToday;
      lvcCheckin.addEventListener('change', function () {
        lvcCheckout.min = lvcCheckin.value || lvcToday;
        if (lvcCheckout.value && lvcCheckin.value && lvcCheckout.value <= lvcCheckin.value) {
          lvcCheckout.value = '';
        }
      });
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var status = form.querySelector('[data-inquiry-status]');
      var btn = form.querySelector('[type="submit"]');
      var setStatus = function (m) { if (status) { status.textContent = m; } };

      if (btn) { btn.disabled = true; }
      setStatus('Sending…');

      var send = function () {
        var fd = new FormData(form);
        return fetch(cfg.ajax, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(function (r) { return r.json().catch(function () { return { success: false, data: { message: 'Unexpected response. Please try again.' } }; }); })
          .then(function (j) {
            if (j && j.success) {
              setStatus((j.data && j.data.message) || 'Thank you. We will be in touch.');
              try {
                var propEl = form.querySelector('[name="property_name"]');
                var prop = propEl ? propEl.value : '';
                var leadId = j.data && j.data.lead_id ? parseInt(j.data.lead_id, 10) : 0;
                // Primary conversion = inquiry_saved, ONLY with a new
                // persisted lead (audit RRC-012/020): an email-only success
                // or a dedupe replay must not count, and the old pair of
                // events (generate_lead + inquiry_submit) double-counted.
                if (leadId > 0 && !(j.data && j.data.duplicate)) {
                  lvcTrack('inquiry_saved', { form: 'inquiry', property: prop, lead_id: String(leadId) });
                }
              } catch (_) {}
              form.reset();
            } else {
              setStatus((j && j.data && j.data.message) || 'Something went wrong. Please try again.');
            }
          })
          .catch(function () { setStatus('Network error. Please try again or message us on WhatsApp.'); })
          .then(function () { if (btn) { btn.disabled = false; } try { if (window.turnstile) { window.turnstile.reset(); } } catch (_) {} });
      };

      // Refresh the nonce first (cache-safe), then submit.
      if (cfg.nonceUrl) {
        fetch(cfg.nonceUrl, { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (d) { if (d && d.nonce) { var n = form.querySelector('[name="_wpnonce"]'); if (n) { n.value = d.nonce; } } })
          .catch(function () {})
          .then(send);
      } else {
        send();
      }
    });
  });
})();
