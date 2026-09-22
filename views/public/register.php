<?php
defined('APP') or exit;
use App\Core\Csrf;
use App\Core\Icons;
$ev = $event ?? [];
$c = function ($sec, $key, $d = '') use ($content) { return $content[$sec][$key] ?? $d; };
$initial = mb_strtoupper(mb_substr(trim($ev['name'] ?? 'E'), 0, 1));
$sections = ['personal' => ['DADOS PESSOAIS', 1], 'address' => ['ENDEREÇO', 2], 'extra' => ['PERGUNTAS ADICIONAIS', 3]];
$grouped = ['personal' => [], 'address' => [], 'extra' => []];
foreach ($fields as $f) { $grouped[$f['section']][] = $f; }
?>
<header class="header scrolled" id="header">
  <div class="container header-inner">
    <?= brand_html($ev) ?>
    <a href="<?= e(url('')) ?>" class="btn btn-ghost btn-sm header-cta" style="display:inline-flex;"><?= Icons::get('arrow-left') ?> Voltar</a>
  </div>
</header>

<section class="page-hero">
  <div class="container">
    <span class="kicker center"><?= e($c('register', 'kicker', 'INSCRIÇÃO')) ?></span>
    <h1 class="sec-title"><?= e($c('register', 'title', 'Garanta sua presença')) ?></h1>
    <p class="sec-sub" style="margin:0 auto;"><?= e($c('register', 'subtitle', '')) ?></p>
  </div>
</section>

<section>
  <div class="container form-wrap">
    <form class="form-card" id="regForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
      <div class="form-error" id="formError"></div>

      <div class="form-sec">
        <div class="form-sec-title"><span class="step">0</span> SEU INGRESSO</div>
        <div class="form-sec-sub">Escolha a experiência ideal para você.</div>
        <div class="ticket-pick" id="ticketPick">
          <?php foreach ($tickets as $t):
            $remaining = ((int) $t['quantity'] > 0) ? max(0, (int) $t['quantity'] - (int) $t['sold']) : null;
            $soldOut = $remaining !== null && $remaining <= 0;
            $sel = ((int) $selected_ticket === (int) $t['id']) || ((int) $selected_ticket === 0 && !$soldOut && !isset($picked));
            if ($sel && !$soldOut) $picked = true;
          ?>
          <label class="ticket-opt <?= $sel && !$soldOut ? 'selected' : '' ?> <?= $soldOut ? 'soldout' : '' ?>">
            <input type="radio" name="ticket_type_id" value="<?= (int) $t['id'] ?>" <?= $sel && !$soldOut ? 'checked' : '' ?> <?= $soldOut ? 'disabled' : '' ?>>
            <span><span class="t-name"><?= e($t['name']) ?><?= $soldOut ? ' — ESGOTADO' : '' ?></span><br><span class="t-desc"><?= e($t['description'] ?? '') ?></span></span>
            <span class="t-price"><?= $t['price'] > 0 ? 'R$ ' . number_format($t['price'], 2, ',', '.') : 'Grátis' ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <?php foreach ($sections as $sec => $meta):
        if (empty($grouped[$sec])) continue; ?>
      <div class="form-sec">
        <div class="form-sec-title"><span class="step"><?= $meta[1] ?></span> <?= $meta[0] ?></div>
        <div class="f-grid">
          <?php foreach ($grouped[$sec] as $f):
            $fname = 'f_' . $f['fname'];
            $req = (int) $f['required'] === 1;
            $opts = $f['options'] ? array_map('trim', explode('|', $f['options'])) : [];
            $full = in_array($f['ftype'], ['textarea'], true) || count($opts) > 3;
          ?>
          <div class="field <?= $full ? 'f-full' : '' ?>" data-field="<?= e($fname) ?>">
            <label for="<?= e($fname) ?>"><?= e($f['label']) ?><?= $req ? ' <span class="req">*</span>' : '' ?></label>
            <?php if ($f['ftype'] === 'textarea'): ?>
              <textarea name="<?= e($fname) ?>" id="<?= e($fname) ?>" placeholder="<?= e($f['placeholder'] ?? '') ?>"></textarea>
            <?php elseif ($f['ftype'] === 'select'): ?>
              <select name="<?= e($fname) ?>" id="<?= e($fname) ?>">
                <option value="">Selecione…</option>
                <?php foreach ($opts as $o): ?><option value="<?= e($o) ?>"><?= e($o) ?></option><?php endforeach; ?>
              </select>
            <?php elseif ($f['ftype'] === 'radio'): ?>
              <div class="radio-group">
                <?php foreach ($opts as $o): ?><label class="radio-pill"><input type="radio" name="<?= e($fname) ?>" value="<?= e($o) ?>"> <?= e($o) ?></label><?php endforeach; ?>
              </div>
            <?php elseif ($f['ftype'] === 'checkbox' && $opts): ?>
              <div class="check-group">
                <?php foreach ($opts as $o): ?><label class="check-pill"><input type="checkbox" name="<?= e($fname) ?>[]" value="<?= e($o) ?>"> <?= e($o) ?></label><?php endforeach; ?>
              </div>
            <?php else:
              $type = $f['ftype'] === 'tel' ? 'tel' : ($f['ftype'] === 'email' ? 'email' : ($f['ftype'] === 'number' ? 'number' : ($f['ftype'] === 'date' ? 'date' : 'text')));
              $mask = '';
              if ($f['fname'] === 'cpf') $mask = 'cpf';
              elseif (in_array($f['fname'], ['phone', 'whatsapp'], true)) $mask = 'phone';
              elseif ($f['fname'] === 'cep') $mask = 'cep';
            ?>
              <input type="<?= $type ?>" name="<?= e($fname) ?>" id="<?= e($fname) ?>" placeholder="<?= e($f['placeholder'] ?? '') ?>" <?= $mask ? 'data-mask="' . $mask . '"' : '' ?> <?= $f['fname'] === 'state' || $f['fname'] === 'addr_state' ? 'maxlength="2" style="text-transform:uppercase"' : '' ?>>
            <?php endif; ?>
            <?php if ($f['help']): ?><div class="help"><?= e($f['help']) ?></div><?php endif; ?>
            <div class="err-msg"></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="form-sec">
        <div class="form-sec-title"><span class="step">4</span> ACEITES & PRIVACIDADE</div>
        <div class="form-sec-sub">Seus dados estão protegidos conforme a LGPD.</div>
        <label class="consent"><input type="checkbox" name="consent_terms" value="1"><span>Aceito os <a href="<?= e(url('termos')) ?>" target="_blank">termos de inscrição</a>. *</span></label>
        <label class="consent"><input type="checkbox" name="consent_comms" value="1"><span>Autorizo o uso dos meus dados para comunicação relacionada ao evento.</span></label>
        <label class="consent"><input type="checkbox" name="consent_privacy" value="1"><span>Li e concordo com a <a href="<?= e(url('privacidade')) ?>" target="_blank">política de privacidade</a>. *</span></label>
      </div>

      <button type="submit" class="btn btn-gold btn-block" id="submitBtn"><?= e($c('register', 'button', 'Confirmar inscrição')) ?></button>
      <p style="text-align:center;color:var(--dim);font-size:13px;margin-top:16px;">🔒 Ambiente seguro · Seus dados protegidos pela LGPD</p>
    </form>
  </div>
</section>

<footer>
  <div class="container">
    <div class="foot-bottom" style="margin-top:0;border:0;">
      <span><?= e($ev['name']) ?></span>
      <span><a href="<?= e(url('termos')) ?>">Termos</a> &nbsp;·&nbsp; <a href="<?= e(url('privacidade')) ?>">Privacidade</a></span>
    </div>
  </div>
</footer>

<div class="toast" id="toast"></div>
<script>
window.REG_CONFIG = { endpoint: '<?= e(url('api/register')) ?>' };
</script>
<script src="<?= e(url('assets/js/register.js')) ?>?v=1.0.0" defer></script>
