<?php defined('APP') or exit; use App\Core\Auth; use App\Core\Icons; ?>
<div class="toolbar">
  <div class="search-box"><?= Icons::get('search') ?><input id="f-q" placeholder="Buscar por nome, código, e-mail, WhatsApp, CPF, empresa…" autocomplete="off"></div>
  <select class="filter" id="f-status">
    <option value="">Todos os status</option>
    <option value="CONFIRMED">Confirmados</option>
    <option value="PENDING">Pendentes</option>
    <option value="CANCELLED">Cancelados</option>
    <option value="CHECKED_IN">Check-in realizado</option>
    <option value="NO_CHECKIN">Sem check-in</option>
  </select>
  <select class="filter" id="f-ticket">
    <option value="">Todos os ingressos</option>
    <?php foreach ($tickets as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?>
  </select>
  <span class="spacer"></span>
  <span id="sel-info" style="font-size:12.5px;color:var(--ad-gold-l);display:none;"></span>
  <?php if (Auth::can('registrations.export')): ?>
  <button class="btn-ad btn-ghost-ad btn-sm-ad" id="btn-csv"><?= Icons::get('download') ?> CSV</button>
  <button class="btn-ad btn-ghost-ad btn-sm-ad" id="btn-xls"><?= Icons::get('download') ?> Excel</button>
  <?php endif; ?>
</div>

<div class="table-wrap">
  <table class="tbl">
    <thead><tr>
      <th style="width:36px;"><input type="checkbox" id="sel-all" style="width:16px;height:16px;accent-color:#C9A227;"></th>
      <th>Código</th><th>Nome</th><th>Contato</th><th>Empresa</th><th>Ingresso</th><th>Inscrição</th><th>Status</th><th>Check-in</th>
    </tr></thead>
    <tbody id="rows"><tr><td colspan="9"><div class="skel" style="height:34px;"></div><div class="skel" style="height:34px;margin-top:8px;"></div></td></tr></tbody>
  </table>
</div>
<div class="pager">
  <button class="icon-btn" id="pg-prev"><?= Icons::get('arrow-left') ?></button>
  <span id="pg-info">—</span>
  <button class="icon-btn" id="pg-next"><?= Icons::get('arrow-right') ?></button>
</div>

<script>
(function () {
  var page = 1, pages = 1, timer = null;
  var selected = {};
  var q = document.getElementById('f-q'),
    st = document.getElementById('f-status'),
    tk = document.getElementById('f-ticket');

  // Blindagem: se o admin.js do servidor estiver desatualizado, define os helpers aqui
  if (typeof window.esc !== 'function') {
    window.esc = function (s) {
      if (s === null || s === undefined) return '';
      return String(s).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    };
  }
  if (typeof window.statusBadge !== 'function') {
    window.statusBadge = function (s) {
      var map = { CONFIRMED: 'b-green', PENDING: 'b-amber', CANCELLED: 'b-red', CHECKED_IN: 'b-blue' };
      var lbl = { CONFIRMED: 'Confirmada', PENDING: 'Pendente', CANCELLED: 'Cancelada', CHECKED_IN: 'Check-in OK' };
      return '<span class="badge ' + (map[s] || 'b-gray') + '">' + esc(lbl[s] || s) + '</span>';
    };
  }

  // Busca vinda da barra global (?q=)
  try {
    var initialQ = new URLSearchParams(window.location.search).get('q');
    if (initialQ) q.value = initialQ;
  } catch (e) {}

  function selCount() { return Object.keys(selected).length; }
  function updateSelInfo() {
    var el = document.getElementById('sel-info');
    var n = selCount();
    el.style.display = n ? 'inline' : 'none';
    el.textContent = n + ' selecionado(s) — exportação usará a seleção';
  }
  function fmtDT(s) {
    if (!s) return '—';
    var m = String(s).match(/(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
    if (!m) return s;
    return m[3] + '/' + m[2] + '/' + m[1] + ' ' + m[4] + ':' + m[5];
  }
  function filters() {
    var f = 'q=' + encodeURIComponent(q.value) + '&status=' + st.value + '&ticket=' + tk.value;
    if (selCount()) f += '&ids=' + Object.keys(selected).join(',');
    return f;
  }
  function apiUrl() {
    return '/registrations?page=' + page + '&q=' + encodeURIComponent(q.value) + '&status=' + st.value + '&ticket=' + tk.value;
  }
  function showLoadError(detail) {
    var tb = document.getElementById('rows');
    tb.innerHTML = '<tr><td colspan="9"><div class="empty">⚠️ Não foi possível carregar os inscritos.' +
      (detail ? '<br><small style="color:var(--ad-muted)">' + esc(detail) + '</small>' : '') +
      '<br><br><button class="btn-ad btn-gold-ad btn-sm-ad" id="btn-retry">Tentar novamente</button></div></td></tr>';
    document.getElementById('pg-info').textContent = '—';
    document.getElementById('btn-retry').addEventListener('click', load);
  }
  // Em caso de falha, busca o motivo real (HTTP + mensagem do servidor) para exibir
  function diagnose() {
    try {
      fetch(window.ADMIN.api + apiUrl(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': window.ADMIN.csrf },
      }).then(function (r) {
        return r.text().then(function (t) {
          var clean = t.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 180);
          showLoadError('Diagnóstico: HTTP ' + r.status + (clean ? ' — ' + clean : ''));
        });
      }).catch(function () {
        showLoadError('Diagnóstico: sem resposta do servidor. Verifique sua conexão.');
      });
    } catch (e) {
      showLoadError('Diagnóstico: falha de rede.');
    }
  }
  function load() {
    if (typeof api !== 'function' || !window.ADMIN || !window.ADMIN.api) {
      showLoadError('Diagnóstico: JS do painel não carregou. Pressione Ctrl+F5.');
      return;
    }
    api(apiUrl(), { silent: true }).then(function (j) {
      try {
      if (!j.ok) { diagnose(); return; }
      pages = j.pages;
      document.getElementById('pg-info').textContent = 'Página ' + j.page + ' de ' + j.pages + ' · ' + j.total + ' registros';
      document.getElementById('sel-all').checked = false;
      var tb = document.getElementById('rows');
      if (!j.rows.length) {
        tb.innerHTML = '<tr><td colspan="9"><div class="empty">Nenhum inscrito encontrado.</div></td></tr>';
        return;
      }
      tb.innerHTML = j.rows.map(function (r) {
        var check = r.status === 'CHECKED_IN'
          ? '<span class="badge b-blue">Realizado</span>'
          : '<span class="badge b-gray">Não realizado</span>';
        var checked = selected[r.id] ? ' checked' : '';
        return '<tr data-id="' + r.id + '">' +
          '<td><input type="checkbox" class="row-sel" data-id="' + r.id + '" style="width:16px;height:16px;accent-color:#C9A227;"' + checked + '></td>' +
          '<td class="mono rowlink">' + esc(r.code) + '</td>' +
          '<td class="rowlink"><strong>' + esc(r.name) + '</strong></td>' +
          '<td class="rowlink">' + esc(r.email) + '<br><small style="color:var(--ad-muted)">' + esc(r.whatsapp || r.phone || '') + '</small></td>' +
          '<td class="rowlink">' + esc(r.company || '—') + '</td>' +
          '<td class="rowlink">' + esc(r.ticket_name || '—') + '</td>' +
          '<td class="rowlink">' + esc(fmtDT(r.created_at)) + '</td>' +
          '<td class="rowlink">' + statusBadge(r.status) + '</td>' +
          '<td class="rowlink">' + check + '</td></tr>';
      }).join('');
      tb.querySelectorAll('tr').forEach(function (tr) {
        tr.querySelectorAll('.rowlink').forEach(function (td) {
          td.style.cursor = 'pointer';
          td.addEventListener('click', function () {
            location.href = window.ADMIN.base + 'admin/inscricoes/' + tr.dataset.id;
          });
        });
      });
      tb.querySelectorAll('.row-sel').forEach(function (cb) {
        cb.addEventListener('change', function () {
          if (cb.checked) selected[cb.dataset.id] = 1;
          else delete selected[cb.dataset.id];
          updateSelInfo();
        });
      });
      } catch (renderErr) {
        showLoadError('Diagnóstico: erro ao exibir os dados recebidos.');
      }
    }).catch(function (e) {
      if (e && e.message === 'auth') return; // api() já redireciona para o login
      diagnose();
    });
  }
  document.getElementById('sel-all').addEventListener('change', function () {
    var on = this.checked;
    document.querySelectorAll('.row-sel').forEach(function (cb) {
      cb.checked = on;
      if (on) selected[cb.dataset.id] = 1;
      else delete selected[cb.dataset.id];
    });
    updateSelInfo();
  });
  q.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(function () { page = 1; load(); }, 280); });
  st.addEventListener('change', function () { page = 1; load(); });
  tk.addEventListener('change', function () { page = 1; load(); });
  document.getElementById('pg-prev').addEventListener('click', function () { if (page > 1) { page--; load(); } });
  document.getElementById('pg-next').addEventListener('click', function () { if (page < pages) { page++; load(); } });
  var bcsv = document.getElementById('btn-csv'), bxls = document.getElementById('btn-xls');
  if (bcsv) bcsv.addEventListener('click', function () { window.open(window.ADMIN.api + '/export?format=csv&' + filters(), '_blank'); });
  if (bxls) bxls.addEventListener('click', function () { window.open(window.ADMIN.api + '/export?format=xls&' + filters(), '_blank'); });
  load();
})();
</script>
