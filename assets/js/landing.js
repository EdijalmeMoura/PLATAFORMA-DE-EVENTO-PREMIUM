/* Landing pública — header, menu, countdown, reveal, FAQ */
(function () {
  'use strict';

  // Header com blur ao rolar
  var header = document.getElementById('header');
  function onScroll() {
    if (header) header.classList.toggle('scrolled', window.scrollY > 40);
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  // Menu mobile
  var burger = document.getElementById('burger');
  var menu = document.getElementById('mobileMenu');
  if (burger && menu) {
    burger.addEventListener('click', function () {
      var open = menu.classList.toggle('open');
      burger.classList.toggle('open', open);
      document.body.style.overflow = open ? 'hidden' : '';
    });
    menu.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () {
        menu.classList.remove('open');
        burger.classList.remove('open');
        document.body.style.overflow = '';
      });
    });
  }

  // Countdown
  var cd = document.getElementById('countdown');
  if (cd && cd.dataset.target) {
    var target = new Date(cd.dataset.target.replace(' ', 'T')).getTime();
    var dEl = document.getElementById('cd-d'),
      hEl = document.getElementById('cd-h'),
      mEl = document.getElementById('cd-m'),
      sEl = document.getElementById('cd-s');
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function tick() {
      var diff = target - Date.now();
      if (isNaN(target)) return;
      if (diff <= 0) {
        dEl.textContent = hEl.textContent = mEl.textContent = sEl.textContent = '00';
        return;
      }
      var d = Math.floor(diff / 864e5),
        h = Math.floor(diff / 36e5) % 24,
        m = Math.floor(diff / 6e4) % 60,
        s = Math.floor(diff / 1e3) % 60;
      dEl.textContent = pad(d); hEl.textContent = pad(h);
      mEl.textContent = pad(m); sEl.textContent = pad(s);
    }
    tick();
    setInterval(tick, 1000);
  }

  // Reveal on scroll
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      if (en.isIntersecting) {
        en.target.classList.add('visible');
        io.unobserve(en.target);
      }
    });
  }, { threshold: 0.12 });
  document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });

  // FAQ accordion
  document.querySelectorAll('.faq-q').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var item = btn.closest('.faq');
      var ans = item.querySelector('.faq-a');
      var isOpen = item.classList.contains('open');
      document.querySelectorAll('.faq.open').forEach(function (o) {
        o.classList.remove('open');
        o.querySelector('.faq-a').style.maxHeight = null;
      });
      if (!isOpen) {
        item.classList.add('open');
        ans.style.maxHeight = ans.scrollHeight + 'px';
      }
    });
  });

  // Galeria — lightbox
  (function () {
    var items = document.querySelectorAll('.gal-item');
    if (!items.length) return;
    var lb = document.createElement('div');
    lb.className = 'lightbox';
    lb.setAttribute('role', 'dialog');
    lb.setAttribute('aria-label', 'Foto ampliada');
    lb.innerHTML = '<button class="lb-x" aria-label="Fechar">✕</button><img alt=""><figcaption></figcaption>';
    document.body.appendChild(lb);
    var img = lb.querySelector('img'), cap = lb.querySelector('figcaption');
    function open(full, caption, alt) {
      img.src = full;
      img.alt = alt || 'Foto ampliada';
      cap.textContent = caption || '';
      cap.style.display = caption ? 'block' : 'none';
      lb.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
    function close() {
      lb.classList.remove('open');
      document.body.style.overflow = '';
      img.removeAttribute('src');
    }
    items.forEach(function (it) {
      function go() {
        open(it.dataset.full, it.dataset.cap, it.querySelector('img').alt);
      }
      it.addEventListener('click', go);
      it.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); go(); }
      });
    });
    lb.addEventListener('click', function (ev) {
      if (ev.target === lb || ev.target.closest('.lb-x')) close();
    });
    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape' && lb.classList.contains('open')) close();
    });
  })();

  // Toast helper global
  window.toast = function (msg) {
    var t = document.getElementById('toast');
    if (!t) return;
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(t._h);
    t._h = setTimeout(function () { t.classList.remove('show'); }, 3200);
  };
})();
