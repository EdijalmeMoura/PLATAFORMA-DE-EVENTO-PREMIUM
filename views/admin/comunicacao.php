<?php defined('APP') or exit; use App\Core\Auth; use App\Core\Crypto; use App\Core\Icons;
$canSend = Auth::can('comms.send');
$extra_js = '<script src="' . e(url('assets/js/admin-comms.js')) . '?v=1.0.0" defer></script>';
$triggers = [
  'after_register' => 'Após inscrição (boas-vindas)', 'after_payment' => 'Após pagamento aprovado',
  'd7_before' => '7 dias antes do evento', 'd1_before' => '1 dia antes (24h)',
  'day_of' => 'No dia do evento', 'after_checkin' => 'Após check-in', 'on_cancel' => 'Cancelamento',
];
?>
<div class="grid g4" style="margin-bottom:16px;">
  <div class="stat"><div class="stat-top"><span class="stat-lbl">ENVIADAS</span></div><div class="stat-num"><?= $comms_stats['sent'] ?? 0 ?></div></div>
  <div class="stat"><div class="stat-top"><span class="stat-lbl">NA FILA</span></div><div class="stat-num" style="color:var(--ad-amber);"><?= $comms_stats['queued'] ?? 0 ?></div></div>
  <div class="stat"><div class="stat-top"><span class="stat-lbl">FALHAS</span></div><div class="stat-num" style="color:var(--ad-red);"><?= $comms_stats['failed'] ?? 0 ?></div></div>
  <div class="stat"><div class="stat-top"><span class="stat-lbl">ABERTURA E-MAIL</span></div><div class="stat-num"><?= $comms_stats['open_rate'] ?? 0 ?>%</div></div>
</div>

<div class="card">
  <div class="tabs">
    <button class="tab-btn active" data-tab="ct-send"><?= Icons::get('send') ?> Enviar</button>
    <button class="tab-btn" data-tab="ct-msgs"><?= Icons::get('chat') ?> Mensagens</button>
    <button class="tab-btn" data-tab="ct-tpl"><?= Icons::get('doc') ?> Templates</button>
    <button class="tab-btn" data-tab="ct-auto"><?= Icons::get('zap') ?> Automações</button>
    <button class="tab-btn" data-tab="ct-queue"><?= Icons::get('clock') ?> Fila</button>
    <button class="tab-btn" data-tab="ct-logs"><?= Icons::get('history') ?> Histórico</button>
    <button class="tab-btn" data-tab="ct-email"><?= Icons::get('mail') ?> E-mail</button>
    <button class="tab-btn" data-tab="ct-wa"><?= Icons::get('whatsapp') ?> WhatsApp</button>
  </div>

  <!-- ENVIAR -->
  <div class="tab-pane active" id="ct-send">
    <?php if (!$canSend): ?><div class="empty">Seu perfil só permite visualizar.</div><?php else: ?>
    <div class="grid" style="grid-template-columns:2fr 1fr;align-items:start;">
      <div>
        <div class="field-row">
          <div class="field"><label>Canal</label>
            <select id="b-channel"><option value="email">E-mail</option><option value="whatsapp">WhatsApp</option><option value="both">E-mail + WhatsApp</option></select>
          </div>
          <div class="field"><label>Partir de template</label>
            <select id="b-template"><option value="">Escrever do zero…</option>
              <?php foreach ($templates as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="field" id="b-subject-wrap"><label>Assunto (e-mail)</label><input id="b-subject" placeholder="Ex.: Falta 1 semana!"></div>
        <div class="field"><label>Mensagem</label><textarea id="b-body" style="min-height:170px;" placeholder="Olá {{nome}}! …"></textarea></div>
        <div style="margin-bottom:14px;">
          <small style="color:var(--ad-muted);">Clique para inserir: </small><br>
          <?php foreach ($variables as $k => $lbl): ?><span class="var-chip" data-var="<?= e($k) ?>" title="<?= e($lbl) ?>"><?= e($k) ?></span><?php endforeach; ?>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <button class="btn-ad btn-ghost-ad" id="b-preview-btn"><?= Icons::get('eye') ?> Visualizar</button>
          <button class="btn-ad btn-gold-ad" id="b-send-btn"><?= Icons::get('send') ?> Revisar e disparar</button>
        </div>
        <div class="card" id="b-preview" style="display:none;margin-top:14px;background:#0c0c0c;"><strong id="b-pv-subject"></strong><p id="b-pv-body" style="white-space:pre-line;font-size:13.5px;color:#ddd;margin-top:8px;"></p></div>
      </div>
      <div class="card" style="background:#0e0e0e;">
        <h3>Público</h3>
        <p class="card-sub">Segmentação do disparo</p>
        <div class="field"><label>Ingresso</label>
          <select id="b-ticket"><option value="">Todos</option>
            <?php foreach ($tickets as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label>Status</label>
          <select id="b-status">
            <option value="">Todos (não cancelados)</option>
            <option value="CONFIRMED">Somente confirmados</option>
            <option value="PENDING">Somente pendentes</option>
            <option value="CHECKED_IN">Com check-in</option>
            <option value="NO_CHECKIN">Sem check-in</option>
          </select>
        </div>
        <div class="field"><label>Cidade</label><input id="b-city" placeholder="Ex.: Recife"></div>
        <div class="field"><label>Empresa</label><input id="b-company" placeholder="Ex.: Empresa XYZ"></div>
        <div class="card" style="background:rgba(201,162,39,.07);border-color:var(--ad-line-gold);text-align:center;">
          <div style="font-size:13px;color:var(--ad-muted);">DESTINATÁRIOS</div>
          <div style="font-size:38px;font-weight:800;" id="b-count">—</div>
          <button class="btn-ad btn-ghost-ad btn-sm-ad" id="b-count-btn">Atualizar contagem</button>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- MENSAGENS -->
  <div class="tab-pane" id="ct-msgs">
    <div class="toolbar"><span class="spacer"></span>
      <?php if ($canSend): ?><button class="btn-ad btn-gold-ad btn-sm-ad" id="msg-new"><?= Icons::get('plus') ?> Nova mensagem</button><?php endif; ?>
    </div>
    <div id="msg-list">
      <?php if (empty($messages)): ?><div class="empty"><?= Icons::get('chat') ?><p>Nenhuma mensagem salva.</p></div><?php endif; ?>
      <?php foreach ($messages as $m): ?>
      <div class="list-item" data-id="<?= (int) $m['id'] ?>">
        <div class="grow"><strong><?= e($m['name']) ?></strong><span><?= $m['channel'] === 'both' ? 'E-mail + WhatsApp' : ($m['channel'] === 'email' ? 'E-mail' : 'WhatsApp') ?> · <?= e(fdate($m['created_at'], true)) ?> · <?= e(excerpt($m['body'], 80)) ?></span></div>
        <?php if ($canSend): ?>
        <button class="icon-btn msg-edit" title="Editar"><?= Icons::get('edit') ?></button>
        <button class="icon-btn msg-del" title="Excluir"><?= Icons::get('trash') ?></button>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- TEMPLATES -->
  <div class="tab-pane" id="ct-tpl">
    <div class="toolbar"><span style="color:var(--ad-muted);font-size:13px;">Modelos reutilizáveis para envios e automações.</span><span class="spacer"></span>
      <?php if ($canSend): ?><button class="btn-ad btn-gold-ad btn-sm-ad" id="tpl-new"><?= Icons::get('plus') ?> Novo template</button><?php endif; ?>
    </div>
    <div id="tpl-list">
      <?php foreach ($templates as $t): ?>
      <div class="list-item" data-id="<?= (int) $t['id'] ?>">
        <div class="grow"><strong><?= e($t['name']) ?> <?= $t['active'] ? '' : '<span class="badge b-gray">inativo</span>' ?></strong><span><?= e($t['subject'] ?: '—') ?> · <?= e(excerpt($t['body'], 90)) ?></span></div>
        <?php if ($canSend): ?>
        <button class="icon-btn tpl-edit" title="Editar"><?= Icons::get('edit') ?></button>
        <button class="icon-btn tpl-del" title="Excluir"><?= Icons::get('trash') ?></button>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- AUTOMAÇÕES -->
  <div class="tab-pane" id="ct-auto">
    <p style="color:var(--ad-muted);font-size:13.5px;margin-bottom:16px;">Mensagens automáticas disparadas pelo sistema. Ative, desative ou edite cada uma.</p>
    <?php foreach ($automations as $a): ?>
    <div class="list-item" data-id="<?= (int) $a['id'] ?>">
      <label class="toggle" title="Ativar/desativar"><input type="checkbox" class="auto-toggle" <?= $a['active'] ? 'checked' : '' ?> <?= $canSend ? '' : 'disabled' ?>><span></span></label>
      <div class="grow"><strong><?= e($a['name']) ?></strong><span><?= e($triggers[$a['trigger']] ?? $a['trigger']) ?> · <?= $a['channel'] === 'both' ? 'E-mail + WhatsApp' : ($a['channel'] === 'email' ? 'E-mail' : 'WhatsApp') ?></span></div>
      <?php if ($canSend): ?><button class="icon-btn auto-edit" title="Editar"><?= Icons::get('edit') ?></button><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- FILA -->
  <div class="tab-pane" id="ct-queue">
    <div class="toolbar">
      <select class="filter" id="q-status"><option value="">Todos</option><option value="queued">Aguardando</option><option value="processing">Processando</option><option value="sent">Enviados</option><option value="failed">Falhas</option><option value="cancelled">Cancelados</option></select>
      <span class="spacer"></span>
      <?php if ($canSend): ?><button class="btn-ad btn-gold-ad btn-sm-ad" id="q-process"><?= Icons::get('refresh') ?> Processar agora</button><?php endif; ?>
    </div>
    <div class="table-wrap"><table class="tbl"><thead><tr><th>#</th><th>Para</th><th>Canal</th><th>Assunto</th><th>Status</th><th>Criado em</th><th></th></tr></thead><tbody id="q-rows"></tbody></table></div>
    <div class="pager"><button class="icon-btn" id="q-prev"><?= Icons::get('arrow-left') ?></button><span id="q-info"></span><button class="icon-btn" id="q-next"><?= Icons::get('arrow-right') ?></button></div>
  </div>

  <!-- HISTÓRICO -->
  <div class="tab-pane" id="ct-logs">
    <div class="toolbar">
      <select class="filter" id="l-channel"><option value="">Todos os canais</option><option value="email">E-mail</option><option value="whatsapp">WhatsApp</option></select>
    </div>
    <div class="table-wrap"><table class="tbl"><thead><tr><th>Data</th><th>Participante</th><th>Canal</th><th>Destino</th><th>Mensagem</th><th>Status</th></tr></thead><tbody id="l-rows"></tbody></table></div>
    <div class="pager"><button class="icon-btn" id="l-prev"><?= Icons::get('arrow-left') ?></button><span id="l-info"></span><button class="icon-btn" id="l-next"><?= Icons::get('arrow-right') ?></button></div>
  </div>

  <!-- E-MAIL -->
  <div class="tab-pane" id="ct-email">
    <?php if (!$canSend): ?><div class="empty">Seu perfil só permite visualizar.</div><?php else: ?>
    <?php $em = $email_cfg ?? []; ?>
    <div class="grid" style="grid-template-columns:2fr 1fr;align-items:start;">
      <div>
        <div class="field-row">
          <div class="field"><label>Servidor SMTP</label><input id="em-host" value="<?= e($em['host'] ?? '') ?>" placeholder="smtp.host.com"></div>
          <div class="field"><label>Porta</label><input id="em-port" type="number" value="<?= e($em['port'] ?? 587) ?>"></div>
        </div>
        <div class="field-row">
          <div class="field"><label>Usuário</label><input id="em-user" value="<?= e($em['username'] ?? '') ?>" autocomplete="off"></div>
          <div class="field"><label>Senha (preencha para alterar)</label><input id="em-pass" type="password" placeholder="<?= e(Crypto::mask($em['password_enc'] ?? '')) ?>" autocomplete="new-password"></div>
        </div>
        <div class="field-row">
          <div class="field"><label>Criptografia</label>
            <select id="em-enc"><option value="tls" <?= ($em['encryption'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS (587)</option><option value="ssl" <?= ($em['encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (465)</option><option value="none" <?= ($em['encryption'] ?? '') === 'none' ? 'selected' : '' ?>>Nenhuma</option></select>
          </div>
          <div class="field"><label>Status</label>
            <div style="display:flex;gap:10px;align-items:center;padding:9px 0;"><label class="toggle"><input type="checkbox" id="em-active" <?= !empty($em['active']) ? 'checked' : '' ?>><span></span></label><span style="font-size:13px;color:var(--ad-muted);">Envio ativo</span></div>
          </div>
        </div>
        <div class="field-row">
          <div class="field"><label>E-mail remetente</label><input id="em-from" value="<?= e($em['from_email'] ?? '') ?>" placeholder="contato@seudominio.com"></div>
          <div class="field"><label>Nome do remetente</label><input id="em-fromname" value="<?= e($em['from_name'] ?? '') ?>" placeholder="Nome do Evento"></div>
        </div>
        <button class="btn-ad btn-gold-ad" id="em-save"><?= Icons::get('check') ?> Salvar configuração</button>
      </div>
      <div class="card" style="background:#0e0e0e;">
        <h3>Testar conexão</h3>
        <p class="card-sub">Envia um e-mail de teste</p>
        <div class="field"><label>Enviar para</label><input id="em-test-to" placeholder="voce@email.com"></div>
        <button class="btn-ad btn-ghost-ad" id="em-test"><?= Icons::get('send') ?> Enviar teste</button>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- WHATSAPP -->
  <div class="tab-pane" id="ct-wa">
    <?php if (!$canSend): ?><div class="empty">Seu perfil só permite visualizar.</div><?php else: ?>
    <?php $wa = $wa_cfg ?? []; ?>
    <div class="card" style="background:rgba(74,222,128,.05);border-color:rgba(74,222,128,.25);margin-bottom:16px;">
      <strong style="font-size:14px;">Integração oficial — WhatsApp Business (Meta Cloud API)</strong>
      <p style="font-size:13px;color:var(--ad-muted);margin-top:4px;">Status: <b><?= !empty($wa['active']) && !empty($wa['phone_number_id']) ? '<span style="color:var(--ad-green)">● Conectado</span>' : '<span style="color:var(--ad-amber)">● Desconectado</span>' ?></b><?= !empty($wa['business_number']) ? ' · ' . e($wa['business_number']) : '' ?><?= !empty($wa['last_status']) ? ' · <small>' . e($wa['last_status']) . '</small>' : '' ?></p>
    </div>
    <div class="grid" style="grid-template-columns:2fr 1fr;align-items:start;">
      <div>
        <div class="field"><label>Phone Number ID (Meta)</label><input id="wa-pid" value="<?= e($wa['phone_number_id'] ?? '') ?>" placeholder="Ex.: 1234567890"></div>
        <div class="field"><label>Token de acesso (preencha para alterar)</label><input id="wa-token" type="password" placeholder="<?= e(Crypto::mask($wa['access_token_enc'] ?? '')) ?>" autocomplete="new-password"></div>
        <div class="field"><label>Número comercial (exibição)</label><input id="wa-num" value="<?= e($wa['business_number'] ?? '') ?>" placeholder="+55 81 99999-9999"></div>
        <div class="field"><div style="display:flex;gap:10px;align-items:center;"><label class="toggle"><input type="checkbox" id="wa-active" <?= !empty($wa['active']) ? 'checked' : '' ?>><span></span></label><span style="font-size:13px;color:var(--ad-muted);">Integração ativa</span></div></div>
        <button class="btn-ad btn-gold-ad" id="wa-save"><?= Icons::get('check') ?> Salvar configuração</button>
        <p style="font-size:12.5px;color:var(--ad-dim);margin-top:12px;">Como obter: <a href="https://developers.facebook.com/docs/whatsapp/cloud-api" target="_blank" rel="noopener">documentação oficial da Meta Cloud API</a>. Mensagens fora da janela de 24h exigem template aprovado na Meta.</p>
      </div>
      <div class="card" style="background:#0e0e0e;">
        <h3>Enviar teste</h3>
        <p class="card-sub">Mensagem de teste transacional</p>
        <div class="field"><label>WhatsApp destino</label><input id="wa-test-to" placeholder="81999999999"></div>
        <button class="btn-ad btn-ghost-ad" id="wa-test"><?= Icons::get('send') ?> Enviar teste</button>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal mensagem/template/automação -->
<div class="modal" id="modalMsg">
  <div class="modal-card">
    <div class="modal-head"><h3 id="mm-title">Mensagem</h3><button class="icon-btn" data-close><?= Icons::get('x') ?></button></div>
    <div class="modal-body">
      <input type="hidden" id="mm-kind" value="messages"><input type="hidden" id="mm-id" value="">
      <div class="field"><label>Nome</label><input id="mm-name"></div>
      <div class="field-row">
        <div class="field"><label>Canal</label><select id="mm-channel"><option value="email">E-mail</option><option value="whatsapp">WhatsApp</option><option value="both">Ambos</option></select></div>
        <div class="field" id="mm-active-wrap" style="display:none;"><label>Status</label><div style="display:flex;gap:10px;align-items:center;padding:9px 0;"><label class="toggle"><input type="checkbox" id="mm-active" checked><span></span></label><span style="font-size:13px;color:var(--ad-muted);">Ativo</span></div></div>
      </div>
      <div class="field"><label>Assunto (e-mail)</label><input id="mm-subject"></div>
      <div class="field"><label>Mensagem</label><textarea id="mm-body" style="min-height:160px;"></textarea></div>
      <div><small style="color:var(--ad-muted);">Variáveis: </small><?php foreach ($variables as $k => $lbl): ?><span class="var-chip" data-target="mm-body" data-var="<?= e($k) ?>"><?= e($k) ?></span><?php endforeach; ?></div>
    </div>
    <div class="modal-foot">
      <button class="btn-ad btn-ghost-ad" data-close>Cancelar</button>
      <button class="btn-ad btn-gold-ad" id="mm-save">Salvar</button>
    </div>
  </div>
</div>
