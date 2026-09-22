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
  <?php if (Auth::can('registrations.export')): ?>
  <button class="btn-ad btn-ghost-ad btn-sm-ad" id="btn-csv"><?= Icons::get('download') ?> CSV</button>
  <button class="btn-ad btn-ghost-ad btn-sm-ad" id="btn-xls"><?= Icons::get('download') ?> Excel</button>
  <?php endif; ?>
</div>

<div class="table-wrap">
  <table class="tbl">
    <thead><tr>
      <th>Código</th><th>Nome</th><th>Contato</th><th>Empresa</th><th>Ingresso</th><th>Inscrição</th><th>Status</th><th>Check-in</th>
    </tr></thead>
    <tbody id="rows"><tr><td colspan="8"><div class="skel" style="height:34px;"></div><div class="skel" style="height:34px;margin-top:8px;"></div></td></tr></tbody>
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
  var q = document.getElementById('f-q'),
    st = document.getElementById('f-status'),
    tk = document.getElementById('f-ticket');

  function filters() {
    return 'q=' + encodeURIComponent(q.value) + '&status=' + st.value + '&ticket=' + tk.value;
  }
  function load() {
    api('/registrations?page=' + page + '&' + filters()).then(function (j) {
      if (!j.ok) return;
      pages = j.pages;
      document.getElementById('pg-info').textContent = 'Página ' + j.page + ' de ' + j.pages + ' · ' + j.total + ' registros';
      var tb = document.getElementById('rows');
      if (!j.rows.length) {
        tb.innerHTML = '<tr><td colspan="8"><div class="empty">Nenhum inscrito encontrado.</div></td></tr>';
        return;
      }
      tb.innerHTML = j.rows.map(function (r) {
        var check = r.status === 'CHECKED_IN'
          ? '<span class="badge b-blue">Realizado</span>'
          : '<span class="badge b-gray">Não realizado</span>';
        return '<tr class="rowlink" data-id="' + r.id + '">' +
          '<td class="mono">' + esc(r.code) + '</td>' +
          '<td><strong>' + esc(r.name) + '</strong></td>' +
          '<td>' + esc(r.email) + '<br><small style="color:var(--ad-muted)">' + esc(r.whatsapp || r.phone || '') + '</small></td>' +
          '<td>' + esc(r.company || '—') + '</td>' +
          '<td>' + esc(r.ticket_name || '—') + '</td>' +
          '<td>' + esc((r.created_at || '').slice(0, 16).split(' ').reverse().join(' ')) + '</td>' +
          '<td>' + statusBadge(r.status) + '</td>' +
          '<td>' + check + '</td></tr>';
      }).join('');
      tb.querySelectorAll('tr.rowlink').forEach(function (tr) {
        tr.addEventListener('click', function () {
          location.href = window.ADMIN.base + 'admin/inscricoes/' + tr.dataset.id;
        });
      });
    });
  }
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
