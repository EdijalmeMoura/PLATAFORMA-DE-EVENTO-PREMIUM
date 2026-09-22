<?php defined('APP') or exit; use App\Core\Icons;
$ev = $event;
function dt_local($v) { return $v ? date('Y-m-d\TH:i', strtotime($v)) : ''; }
$imgFields = ['logo' => 'Logo do evento', 'hero_image' => 'Imagem do Hero (fundo)', 'about_image' => 'Imagem da seção Sobre', 'venue_image' => 'Imagem do Local'];
?>
<form id="eventForm" enctype="multipart/form-data">
  <div class="grid g2" style="align-items:start;">
    <div class="card">
      <h3>Informações gerais</h3>
      <p class="card-sub">Nome, datas e contagem regressiva</p>
      <div class="field"><label>Nome do evento *</label><input name="name" value="<?= e($ev['name']) ?>"></div>
      <div class="field"><label>Frase de impacto (tagline)</label><input name="tagline" value="<?= e($ev['tagline']) ?>"></div>
      <div class="field-row">
        <div class="field"><label>Data de início</label><input type="date" name="date_start" value="<?= e($ev['date_start']) ?>"></div>
        <div class="field"><label>Data de término</label><input type="date" name="date_end" value="<?= e($ev['date_end']) ?>"></div>
      </div>
      <div class="field-row">
        <div class="field"><label>Horário (texto)</label><input name="time_text" value="<?= e($ev['time_text']) ?>" placeholder="19h às 23h"></div>
        <div class="field"><label>Alvo do contador regressivo</label><input type="datetime-local" name="countdown_target" value="<?= e(dt_local($ev['countdown_target'])) ?>"></div>
      </div>
      <div class="field"><label>Status</label>
        <select name="status"><option value="ACTIVE" <?= $ev['status'] === 'ACTIVE' ? 'selected' : '' ?>>Ativo (inscrições abertas)</option><option value="PAUSED" <?= $ev['status'] === 'PAUSED' ? 'selected' : '' ?>>Pausado</option><option value="FINISHED" <?= $ev['status'] === 'FINISHED' ? 'selected' : '' ?>>Encerrado</option></select>
      </div>
    </div>
    <div class="card">
      <h3>Local</h3>
      <p class="card-sub">Onde a experiência acontece</p>
      <div class="field"><label>Nome do local</label><input name="venue_name" value="<?= e($ev['venue_name']) ?>"></div>
      <div class="field"><label>Endereço</label><input name="address" value="<?= e($ev['address']) ?>"></div>
      <div class="field-row">
        <div class="field"><label>Cidade</label><input name="city" value="<?= e($ev['city']) ?>"></div>
        <div class="field"><label>UF</label><input name="state" value="<?= e($ev['state']) ?>" maxlength="2"></div>
      </div>
      <div class="field"><label>Link “Como chegar” (Google Maps)</label><input name="map_url" value="<?= e($ev['map_url']) ?>"></div>
      <div class="field"><label>Mapa incorporado (iframe, opcional)</label><textarea name="maps_embed" style="min-height:80px;" placeholder="<iframe …>"><?= e($ev['maps_embed']) ?></textarea></div>
    </div>
    <div class="card">
      <h3>Contato & redes</h3>
      <p class="card-sub">Canais exibidos no site</p>
      <div class="field-row">
        <div class="field"><label>E-mail</label><input name="contact_email" value="<?= e($ev['contact_email']) ?>"></div>
        <div class="field"><label>WhatsApp (só números)</label><input name="contact_whatsapp" value="<?= e($ev['contact_whatsapp']) ?>"></div>
      </div>
      <div class="field-row">
        <div class="field"><label>Telefone</label><input name="contact_phone" value="<?= e($ev['contact_phone']) ?>"></div>
        <div class="field"><label>Site</label><input name="website" value="<?= e($ev['website']) ?>"></div>
      </div>
      <div class="field-row">
        <div class="field"><label>Instagram (URL)</label><input name="instagram" value="<?= e($ev['instagram']) ?>"></div>
        <div class="field"><label>LinkedIn (URL)</label><input name="linkedin" value="<?= e($ev['linkedin']) ?>"></div>
      </div>
    </div>
    <div class="card">
      <h3>Imagens</h3>
      <p class="card-sub">Envie para substituir (máx. 5MB cada)</p>
      <?php foreach ($imgFields as $k => $lbl): ?>
      <div class="field">
        <label><?= $lbl ?></label>
        <?php if (!empty($ev[$k])): ?><img src="<?= e(url($ev[$k])) ?>" style="width:100%;max-height:120px;object-fit:cover;border-radius:10px;margin-bottom:8px;border:1px solid var(--ad-line);"><?php endif; ?>
        <input type="file" name="<?= $k ?>" accept="image/*">
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div style="margin-top:16px;display:flex;gap:10px;">
    <button type="submit" class="btn-ad btn-gold-ad" id="ev-save"><?= Icons::get('check') ?> Salvar configurações</button>
    <a class="btn-ad btn-ghost-ad" href="<?= e(url('')) ?>" target="_blank"><?= Icons::get('eye') ?> Ver site</a>
  </div>
</form>

<script>
document.getElementById('eventForm').addEventListener('submit', function (ev) {
  ev.preventDefault();
  var btn = document.getElementById('ev-save');
  btn.disabled = true;
  var fd = new FormData(this);
  fetch(window.ADMIN.api + '/content/event-save', {
    method: 'POST',
    headers: { 'X-CSRF-Token': window.ADMIN.csrf, 'X-Requested-With': 'XMLHttpRequest' },
    body: fd,
  }).then(function (r) { return r.json(); }).then(function (j) {
    btn.disabled = false;
    if (j.ok) { toast('Configurações salvas!', 'ok'); setTimeout(function () { location.reload(); }, 700); }
    else toast(j.error || 'Erro ao salvar.', 'err');
  }).catch(function () { btn.disabled = false; toast('Falha de conexão.', 'err'); });
});
</script>
