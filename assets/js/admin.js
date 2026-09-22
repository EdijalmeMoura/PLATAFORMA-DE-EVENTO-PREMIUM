/* Admin — helpers globais: API, toast, modal, confirm, tabs, sidebar */
(function () {
  'use strict';

  // Sidebar mobile
  var side = document.getElementById('side'),
    overlay = document.getElementById('sideOverlay'),
    btnSide = document.getElementById('btnSide');
  function closeSide() {
    if (side) side.classList.remove('open');
    if (overlay) overlay.classList.remove('show');
  }
  if (btnSide) btnSide.addEventListener('click', function () {
    side.classList.toggle('open');
    overlay.classList.toggle('show');
  });
  if (overlay) overlay.addEventListener('click', closeSide);

  // Toast
  window.toast = function (msg, type) {
    var wrap = document.getElementById('toasts');
    if (!wrap) return;
    var el = document.createElement('div');
    el.className = 'toast ' + (type || '');
    el.innerHTML = '<span></span>';
    el.querySelector('span').textContent = msg;
    wrap.appendChild(el);
    setTimeout(function () {
      el.style.opacity = '0';
      el.style.transition = 'opacity .3s';
      setTimeout(function () { el.remove(); }, 320);
    }, 3600);
  };

  // Modal genérico
  window.openModal = function (id) {
    document.getElementById(id).classList.add('open');
  };
  window.closeModal = function (id) {
    document.getElementById(id).classList.remove('open');
  };
  document.querySelectorAll('[data-close]').forEach(function (b) {
    b.addEventListener('click', function () {
      b.closest('.modal').classList.remove('open');
    });
  });
  document.querySelectorAll('.modal').forEach(function (m) {
    m.addEventListener('click', function (ev) {
      if (ev.target === m) m.classList.remove('open');
    });
  });
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') document.querySelectorAll('.modal.open').forEach(function (m) { m.classList.remove('open'); });
  });

  // Confirm
  var confirmCb = null;
  window.confirmDlg = function (title, text, cb, yesLabel) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmText').textContent = text;
    document.getElementById('confirmYes').textContent = yesLabel || 'Confirmar';
    confirmCb = cb;
    openModal('modalConfirm');
  };
  document.getElementById('confirmYes').addEventListener('click', function () {
    closeModal('modalConfirm');
    if (confirmCb) { var cb = confirmCb; confirmCb = null; cb(); }
  });

  // API helper
  window.api = function (path, opts) {
    opts = opts || {};
    var headers = { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': window.ADMIN.csrf };
    var body = opts.body;
    if (body && !(body instanceof FormData) && typeof body === 'object') {
      headers['Content-Type'] = 'application/json';
      body = JSON.stringify(body);
    }
    return fetch(window.ADMIN.api + path, {
      method: opts.method || 'GET',
      headers: headers,
      body: body || undefined,
    }).then(function (r) {
      if (r.status === 401) { window.location.href = window.ADMIN.base + 'admin/login'; throw new Error('auth'); }
      return r.json().then(function (j) {
        if (!j.ok && r.status !== 200 && !opts.silent) toast(j.error || 'Erro na operação.', 'err');
        return j;
      });
    }).catch(function (e) {
      if (e.message === 'auth') throw e;
      if (!opts.silent) toast('Falha de conexão.', 'err');
      throw e;
    });
  };
  window.apiPost = function (path, data) {
    return window.api(path, { method: 'POST', body: data || {} });
  };

  // Tabs
  document.querySelectorAll('.tabs').forEach(function (tabs) {
    tabs.querySelectorAll('.tab-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var scope = tabs.parentElement;
        tabs.querySelectorAll('.tab-btn').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        scope.querySelectorAll('.tab-pane').forEach(function (p) { p.classList.remove('active'); });
        var pane = scope.querySelector('#' + btn.dataset.tab);
        if (pane) pane.classList.add('active');
      });
    });
  });

  // Perfil
  var chip = document.getElementById('userChip');
  if (chip) chip.addEventListener('click', function () { openModal('modalProfile'); });
  var linkPass = document.getElementById('linkChangePass');
  if (linkPass) linkPass.addEventListener('click', function (ev) { ev.preventDefault(); openModal('modalProfile'); });
  var pfSave = document.getElementById('pf-save');
  if (pfSave) pfSave.addEventListener('click', function () {
    apiPost('/profile', {
      name: document.getElementById('pf-name').value,
      current_password: document.getElementById('pf-current').value,
      new_password: document.getElementById('pf-new').value,
    }).then(function (j) {
      if (j.ok) { toast('Perfil atualizado!', 'ok'); closeModal('modalProfile'); setTimeout(function () { location.reload(); }, 600); }
    });
  });

  // Copiar texto
  window.copyText = function (txt, msg) {
    function done() { toast(msg || 'Copiado!', 'ok'); }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(txt).then(done).catch(function () { fallback(); });
    } else fallback();
    function fallback() {
      var ta = document.createElement('textarea');
      ta.value = txt;
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand('copy'); done(); } catch (e) { toast('Não foi possível copiar.', 'err'); }
      ta.remove();
    }
  };

  // Status badge helper
  window.statusBadge = function (s) {
    var map = {
      CONFIRMED: ['b-green', 'Confirmada'], PENDING: ['b-amber', 'Pendente'],
      CANCELLED: ['b-red', 'Cancelada'], CHECKED_IN: ['b-blue', 'Check-in OK'],
      queued: ['b-amber', 'Na fila'], processing: ['b-blue', 'Enviando'],
      sent: ['b-green', 'Enviado'], failed: ['b-red', 'Falhou'],
      ACTIVE: ['b-green', 'Ativo'], PAUSED: ['b-amber', 'Pausado'], SOLD_OUT: ['b-red', 'Esgotado'],
      APPROVED: ['b-green', 'Aprovado'], REFUSED: ['b-red', 'Recusado'], FREE: ['b-gold', 'Isento'],
      draft: ['b-gray', 'Rascunho'], active: ['b-green', 'Ativo'], archived: ['b-gray', 'Arquivado'],
    };
    var m = map[s] || ['b-gray', s];
    return '<span class="badge ' + m[0] + '">' + m[1] + '</span>';
  };

  // Escape
  window.esc = function (s) {
    if (s === null || s === undefined) return '';
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
})();
