<?php defined('APP') or exit; use App\Core\Auth; use App\Core\Icons;
$r = $reg;
$consents = json_decode($r['consents'] ?? '{}', true) ?: [];
$canEdit = Auth::can('registrations.edit');
$canCheckin = Auth::can('checkin.use');
$canSend = Auth::can('comms.send');
?>
<div class="toolbar">
  <a href="<?= e(url('admin/inscricoes')) ?>" class="btn-ad btn-ghost-ad btn-sm-ad"><?= Icons::get('arrow-left') ?> Voltar</a>
  <span class="spacer"></span>
  <?php if ($canSend): ?>
    <button class="btn-ad btn-ghost-ad btn-sm-ad" id="btn-send"><?= Icons::get('send') ?> Enviar mensagem</button>
  <?php endif; ?>
  <?php if ($canCheckin && $r['status'] !== 'CHECKED_IN' && $r['status'] !== 'CANCELLED'): ?>
    <button class="btn-ad btn-green-ad btn-sm-ad" id="btn-checkin"><?= Icons::get('scan') ?> Realizar check-in</button>
  <?php endif; ?>
  <?php if ($canEdit && $r['status'] === 'PENDING'): ?>
    <button class="btn-ad btn-gold-ad btn-sm-ad" id="btn-confirm"><?= Icons::get('check') ?> Confirmar</button>
  <?php endif; ?>
  <?php if ($canEdit && $r['status'] !== 'CANCELLED'): ?>
    <button class="btn-ad btn-red-ad btn-sm-ad" id="btn-cancel"><?= Icons::get('ban') ?> Cancelar</button>
  <?php endif; ?>
</div>

<div class="grid" style="grid-template-columns:2fr 1fr;align-items:start;">
  <div style="display:flex;flex-direction:column;gap:16px;">
    <div class="card">
      <div style="display:flex;gap:16px;align-items:center;margin-bottom:18px;">
        <span class="avatar" style="width:54px;height:54px;font-size:22px;"><?= e(mb_strtoupper(mb_substr($r['name'], 0, 1))) ?></span>
        <div>
          <h3 style="font-size:19px;"><?= e($r['name']) ?></h3>
          <div style="display:flex;gap:8px;margin-top:6px;flex-wrap:wrap;">
            <span class="badge b-gold mono"><?= e($r['code']) ?></span>
            <span class="badge <?= $r['status'] === 'CONFIRMED' ? 'b-green' : ($r['status'] === 'CHECKED_IN' ? 'b-blue' : ($r['status'] === 'CANCELLED' ? 'b-red' : 'b-amber')) ?>"><?= e(registration_status_label($r['status'])) ?></span>
            <span class="badge b-gray"><?= e(payment_status_label($r['payment_status'])) ?></span>
          </div>
        </div>
      </div>
      <div class="tabs">
        <button class="tab-btn active" data-tab="tab-dados">Dados</button>
        <button class="tab-btn" data-tab="tab-ticket">Ticket</button>
        <button class="tab-btn" data-tab="tab-msgs">Mensagens (<?= count($logs) ?>)</button>
      </div>
      <div class="tab-pane active" id="tab-dados">
        <dl class="kv">
          <dt>Nome social</dt><dd><?= e($r['social_name'] ?: '—') ?></dd>
          <dt>E-mail</dt><dd><?= e($r['email']) ?></dd>
          <dt>Telefone</dt><dd><?= e($r['phone'] ?: '—') ?></dd>
          <dt>WhatsApp</dt><dd><?= e($r['whatsapp'] ?: '—') ?></dd>
          <dt>CPF</dt><dd><?= e($r['cpf'] ?: '—') ?></dd>
          <dt>Nascimento</dt><dd><?= e($r['birthdate'] ? date('d/m/Y', strtotime($r['birthdate'])) : '—') ?></dd>
          <dt>Empresa / Cargo</dt><dd><?= e(trim(($r['company'] ?: '—') . ' / ' . ($r['role'] ?: '—'), '/ ')) ?></dd>
          <dt>Cidade / UF</dt><dd><?= e(trim(($r['city'] ?: '—') . '/' . ($r['state'] ?: ''), '/')) ?></dd>
          <dt>Endereço</dt><dd><?= e(trim(implode(', ', array_filter([$r['street'], $r['number'], $r['complement'], $r['district'], $r['addr_city'] ? $r['addr_city'] . '/' . $r['addr_state'] : '', $r['cep'] ? 'CEP ' . $r['cep'] : '']))) ?: '—') ?></dd>
          <dt>Ingresso</dt><dd><?= e($r['ticket_name'] ?? '—') ?></dd>
          <dt>Origem</dt><dd><?= e($r['source'] ?: 'site') ?></dd>
          <dt>Observações</dt><dd><?= e($r['notes'] ?: '—') ?></dd>
        </dl>
        <?php if ($answers): ?>
        <h3 style="margin:18px 0 8px;">Respostas adicionais</h3>
        <dl class="kv">
          <?php foreach ($answers as $a): ?><dt><?= e($a['label']) ?></dt><dd><?= e($a['fvalue']) ?></dd><?php endforeach; ?>
        </dl>
        <?php endif; ?>
        <?php if ($canEdit): ?>
        <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
          <button class="btn-ad btn-ghost-ad btn-sm-ad" id="btn-edit-data"><?= Icons::get('edit') ?> Editar dados</button>
          <button class="btn-ad btn-ghost-ad btn-sm-ad" id="btn-payment"><?= Icons::get('ticket') ?> Status pagamento</button>
          <button class="btn-ad btn-red-ad btn-sm-ad" id="btn-delete"><?= Icons::get('trash') ?> Excluir (LGPD)</button>
        </div>
        <?php endif; ?>
      </div>
      <div class="tab-pane" id="tab-ticket">
        <dl class="kv">
          <dt>Código</dt><dd class="mono"><?= e($r['code']) ?></dd>
          <dt>Link do ticket</dt><dd><a href="<?= e(url('ticket/' . ($r['qr_token'] ?? $r['code']))) ?>" target="_blank"><?= e(url('ticket/' . ($r['qr_token'] ?? $r['code']))) ?></a></dd>
          <dt>Check-in</dt><dd><?= $r['checked_in_at'] ? e(fdate($r['checked_in_at'], true)) : 'Não realizado' ?></dd>
        </dl>
        <div style="margin-top:14px;">
          <a class="btn-ad btn-ghost-ad btn-sm-ad" href="<?= e(url('ticket/' . ($r['qr_token'] ?? $r['code']))) ?>" target="_blank"><?= Icons::get('eye') ?> Ver ticket</a>
        </div>
      </div>
      <div class="tab-pane" id="tab-msgs">
        <?php if (empty($logs)): ?><div class="empty"><?= Icons::get('send') ?><p>Nenhuma mensagem enviada.</p></div><?php endif; ?>
        <?php foreach ($logs as $l): ?>
        <div class="list-item">
          <div class="grow">
            <strong><?= e($l['subject'] ?: ($l['channel'] === 'email' ? 'E-mail' : 'WhatsApp')) ?></strong>
            <span><?= $l['channel'] === 'email' ? 'E-mail' : 'WhatsApp' ?> → <?= e($l['to_address']) ?> · <?= e(fdate($l['created_at'], true)) ?><?= $l['opened_at'] ? ' · <b style="color:var(--ad-green)">aberto</b>' : '' ?><?= $l['error'] ? ' · <b style="color:var(--ad-red)">' . e($l['error']) . '</b>' : '' ?></span>
          </div>
          <?= $l['status'] === 'sent' ? '<span class="badge b-green">Enviado</span>' : '<span class="badge b-red">Falhou</span>' ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:16px;">
    <div class="card">
      <h3>Jornada</h3>
      <p class="card-sub">Linha do tempo da inscrição</p>
      <div class="timeline">
        <div class="tl-ev done"><strong>Inscrição criada</strong><span><?= e(fdate($r['created_at'], true)) ?></span></div>
        <div class="tl-ev <?= $r['payment_status'] === 'APPROVED' || $r['payment_status'] === 'FREE' ? 'done' : '' ?>"><strong>Pagamento <?= e(payment_status_label($r['payment_status'])) ?></strong><span><?= $r['payment_status'] === 'FREE' ? 'Evento gratuito / isento' : 'Aguardando confirmação' ?></span></div>
        <div class="tl-ev <?= count(array_filter($logs, function ($l) { return $l['channel'] === 'email' && $l['status'] === 'sent'; })) ? 'done' : '' ?>"><strong>E-mail enviado</strong><span>Confirmação e ticket</span></div>
        <div class="tl-ev <?= count(array_filter($logs, function ($l) { return $l['channel'] === 'whatsapp' && $l['status'] === 'sent'; })) ? 'done' : '' ?>"><strong>WhatsApp enviado</strong><span>Confirmação e ticket</span></div>
        <div class="tl-ev done"><strong>Ticket gerado</strong><span class="mono"><?= e($r['code']) ?></span></div>
        <div class="tl-ev <?= $r['status'] === 'CHECKED_IN' ? 'done' : '' ?>"><strong>Check-in <?= $r['status'] === 'CHECKED_IN' ? 'realizado' : 'pendente' ?></strong><span><?= $r['checked_in_at'] ? e(fdate($r['checked_in_at'], true)) : 'Aguardando o dia do evento' ?></span></div>
      </div>
      <?php if ($checkins): ?>
      <h3 style="margin-top:14px;">Registros de presença</h3>
      <?php foreach ($checkins as $ch): ?>
        <p style="font-size:13px;color:var(--ad-muted);"><?= e(fdate($ch['checked_in_at'], true)) ?> · <?= $ch['method'] === 'qr' ? 'QR Code' : 'Manual' ?><?= $ch['by_name'] ? ' · por ' . e($ch['by_name']) : '' ?></p>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="card">
      <h3>Consentimentos (LGPD)</h3>
      <p class="card-sub">Registro de aceites</p>
      <dl class="kv" style="grid-template-columns:1fr;">
        <dt>Termos</dt><dd><?= !empty($consents['terms']) ? '✓ ' . e(fdate($consents['terms'], true)) : '—' ?></dd>
        <dt>Privacidade</dt><dd><?= !empty($consents['privacy']) ? '✓ ' . e(fdate($consents['privacy'], true)) : '—' ?></dd>
        <dt>Comunicação</dt><dd><?= !empty($consents['comms']) ? '✓ ' . e(fdate($consents['comms'], true)) : 'Não autorizado' ?></dd>
        <dt>IP</dt><dd class="mono"><?= e($consents['ip'] ?? $r['ip'] ?? '—') ?></dd>
      </dl>
    </div>
  </div>
</div>

<!-- Modal enviar mensagem -->
<div class="modal" id="modalSend">
  <div class="modal-card">
    <div class="modal-head"><h3>Enviar mensagem — <?= e(strtok($r['name'], ' ')) ?></h3><button class="icon-btn" data-close><?= Icons::get('x') ?></button></div>
    <div class="modal-body">
      <div class="field-row">
        <div class="field"><label>Canal</label>
          <select id="s-channel"><option value="email">E-mail</option><option value="whatsapp">WhatsApp</option></select>
        </div>
        <div class="field"><label>Usar template</label>
          <select id="s-template"><option value="">Mensagem livre…</option>
            <?php foreach ($templates as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="field" id="s-subject-wrap"><label>Assunto</label><input id="s-subject"></div>
      <div class="field"><label>Mensagem (variáveis {{...}} são substituídas)</label><textarea id="s-body" style="min-height:150px;"></textarea></div>
      <button class="btn-ad btn-ghost-ad btn-sm-ad" id="s-preview-btn"><?= Icons::get('eye') ?> Visualizar antes de enviar</button>
      <div class="card" id="s-preview" style="display:none;margin-top:12px;background:#0c0c0c;"><strong id="s-pv-subject"></strong><p id="s-pv-body" style="white-space:pre-line;font-size:13.5px;color:#ddd;margin-top:8px;"></p></div>
    </div>
    <div class="modal-foot">
      <button class="btn-ad btn-ghost-ad" data-close>Fechar</button>
      <button class="btn-ad btn-gold-ad" id="s-send">Enviar agora</button>
    </div>
  </div>
</div>

<!-- Modal editar dados -->
<div class="modal" id="modalEdit">
  <div class="modal-card modal-lg">
    <div class="modal-head"><h3>Editar dados</h3><button class="icon-btn" data-close><?= Icons::get('x') ?></button></div>
    <div class="modal-body">
      <div class="field-row">
        <div class="field"><label>Nome</label><input id="e-name" value="<?= e($r['name']) ?>"></div>
        <div class="field"><label>Nome social</label><input id="e-social_name" value="<?= e($r['social_name']) ?>"></div>
      </div>
      <div class="field-row">
        <div class="field"><label>E-mail</label><input id="e-email" value="<?= e($r['email']) ?>"></div>
        <div class="field"><label>CPF</label><input id="e-cpf" value="<?= e($r['cpf']) ?>"></div>
      </div>
      <div class="field-row">
        <div class="field"><label>Telefone</label><input id="e-phone" value="<?= e($r['phone']) ?>"></div>
        <div class="field"><label>WhatsApp</label><input id="e-whatsapp" value="<?= e($r['whatsapp']) ?>"></div>
      </div>
      <div class="field-row">
        <div class="field"><label>Empresa</label><input id="e-company" value="<?= e($r['company']) ?>"></div>
        <div class="field"><label>Cargo</label><input id="e-role" value="<?= e($r['role']) ?>"></div>
      </div>
      <div class="field-row">
        <div class="field"><label>Cidade</label><input id="e-city" value="<?= e($r['city']) ?>"></div>
        <div class="field"><label>UF</label><input id="e-state" value="<?= e($r['state']) ?>"></div>
      </div>
      <div class="field"><label>Ingresso</label>
        <select id="e-ticket"><?php foreach ($tickets as $t): ?><option value="<?= (int) $t['id'] ?>" <?= (int) $r['ticket_type_id'] === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?></select>
      </div>
      <div class="field"><label>Observações internas</label><textarea id="e-notes"><?= e($r['notes']) ?></textarea></div>
    </div>
    <div class="modal-foot">
      <button class="btn-ad btn-ghost-ad" data-close>Cancelar</button>
      <button class="btn-ad btn-gold-ad" id="e-save">Salvar alterações</button>
    </div>
  </div>
</div>

<script>
(function () {
  var ID = <?= (int) $r['id'] ?>;
  function post(action, data, okMsg) {
    data = data || {};
    apiPost('/registrations/' + ID + '/' + action, data).then(function (j) {
      if (j.ok) { toast(okMsg || 'Feito!', 'ok'); setTimeout(function () { location.reload(); }, 700); }
    });
  }
  var b;
  b = document.getElementById('btn-confirm'); if (b) b.addEventListener('click', function () { confirmDlg('Confirmar inscrição?', 'O participante será marcado como confirmado.', function () { post('confirm', {}, 'Inscrição confirmada!'); }); });
  b = document.getElementById('btn-cancel'); if (b) b.addEventListener('click', function () { confirmDlg('Cancelar inscrição?', 'O ticket será invalidado e o participante notificado (se a automação estiver ativa).', function () { post('cancel', { reason: 'Cancelado pelo operador' }, 'Inscrição cancelada.'); }, 'Cancelar inscrição'); });
  b = document.getElementById('btn-checkin'); if (b) b.addEventListener('click', function () { confirmDlg('Realizar check-in?', 'Confirma a presença de ' + FIRST + ' agora?', function () { post('checkin', {}, 'Check-in realizado! ✓'); }, 'Confirmar presença'); });
  b = document.getElementById('btn-delete'); if (b) b.addEventListener('click', function () { confirmDlg('Excluir permanentemente?', 'Todos os dados deste participante serão apagados (LGPD). Esta ação não pode ser desfeita.', function () { apiPost('/registrations/' + ID + '/delete', {}).then(function (j) { if (j.ok) { toast('Excluído.', 'ok'); setTimeout(function () { location.href = window.ADMIN.base + 'admin/inscricoes'; }, 700); } }); }, 'Excluir tudo'); });
  b = document.getElementById('btn-payment'); if (b) b.addEventListener('click', function () {
    var v = prompt('Status do pagamento (PENDING, APPROVED, REFUSED, CANCELLED, FREE):', '<?= e($r['payment_status']) ?>');
    if (v) post('payment', { payment_status: v }, 'Pagamento atualizado!');
  });
  // Editar
  b = document.getElementById('btn-edit-data'); if (b) b.addEventListener('click', function () { openModal('modalEdit'); });
  var eSave = document.getElementById('e-save');
  if (eSave) eSave.addEventListener('click', function () {
    var cols = ['name', 'social_name', 'email', 'cpf', 'phone', 'whatsapp', 'company', 'role', 'city', 'state', 'notes'];
    var data = { ticket_type_id: document.getElementById('e-ticket').value };
    cols.forEach(function (c) { data[c] = document.getElementById('e-' + c).value; });
    post('update', data, 'Dados atualizados!');
  });
  // Enviar mensagem
  b = document.getElementById('btn-send'); if (b) b.addEventListener('click', function () { openModal('modalSend'); });
  var tplSel = document.getElementById('s-template');
  if (tplSel) tplSel.addEventListener('change', function () {
    if (!tplSel.value) return;
    api('/comms/templates/get?id=' + tplSel.value).then(function (j) {
      if (j.ok && j.row) {
        document.getElementById('s-subject').value = j.row.subject || '';
        document.getElementById('s-body').value = j.row.body || '';
      }
    });
  });
  var pvBtn = document.getElementById('s-preview-btn');
  if (pvBtn) pvBtn.addEventListener('click', function () {
    apiPost('/comms/preview', {
      registration_id: ID,
      subject: document.getElementById('s-subject').value,
      body: document.getElementById('s-body').value,
    }).then(function (j) {
      if (j.ok) {
        document.getElementById('s-preview').style.display = 'block';
        document.getElementById('s-pv-subject').textContent = j.subject;
        document.getElementById('s-pv-body').textContent = j.body;
      }
    });
  });
  var sSend = document.getElementById('s-send');
  if (sSend) sSend.addEventListener('click', function () {
    sSend.disabled = true;
    apiPost('/comms/single-send', {
      registration_id: ID,
      channel: document.getElementById('s-channel').value,
      subject: document.getElementById('s-subject').value,
      body: document.getElementById('s-body').value,
    }).then(function (j) {
      sSend.disabled = false;
      if (j.ok) { toast('Mensagem enviada!', 'ok'); closeModal('modalSend'); setTimeout(function () { location.reload(); }, 700); }
    });
  });
  var chSel = document.getElementById('s-channel');
  if (chSel) chSel.addEventListener('change', function () {
    document.getElementById('s-subject-wrap').style.display = chSel.value === 'whatsapp' ? 'none' : 'block';
  });
})();
</script>
