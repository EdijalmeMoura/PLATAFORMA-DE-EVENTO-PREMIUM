<?php defined('APP') or exit; use App\Core\Icons;
$extra_js = '<script src="' . e(url('assets/js/admin-crud.js')) . '?v=1.0.0" defer></script>';
$c = function ($sec, $key, $d = '') use ($content) { return $content[$sec][$key] ?? $d; };
$icons = ['spark', 'users', 'book', 'target', 'mic', 'star', 'dish', 'diamond', 'ticket', 'calendar', 'pin', 'check', 'shield', 'zap', 'globe'];
$diffs = json_decode($c('about', 'differentials', '[]'), true) ?: [];
$exps = json_decode($c('experience', 'cards', '[]'), true) ?: [];
?>
<div class="card">
  <div class="tabs">
    <button class="tab-btn active" data-tab="cc-hero">Hero & Topo</button>
    <button class="tab-btn" data-tab="cc-about">Sobre</button>
    <button class="tab-btn" data-tab="cc-exp">Experiência</button>
    <button class="tab-btn" data-tab="cc-awards">WCM Awards</button>
    <button class="tab-btn" data-tab="cc-sec">Títulos das seções</button>
    <button class="tab-btn" data-tab="cc-faq">FAQ</button>
    <button class="tab-btn" data-tab="cc-legal">Termos & Privacidade</button>
    <button class="tab-btn" data-tab="cc-theme">Identidade & Cores</button>
  </div>

  <!-- HERO -->
  <div class="tab-pane active" id="cc-hero">
    <div class="grid g2">
      <div class="field"><label>Selo (badge)</label><input data-sec="hero" data-k="badge" value="<?= e($c('hero', 'badge')) ?>"></div>
      <div class="field"><label>Linha superior (kicker)</label><input data-sec="hero" data-k="kicker" value="<?= e($c('hero', 'kicker')) ?>"></div>
      <div class="field"><label>Título principal</label><input data-sec="hero" data-k="title" value="<?= e($c('hero', 'title')) ?>"></div>
      <div class="field"><label>Subtítulo</label><input data-sec="hero" data-k="subtitle" value="<?= e($c('hero', 'subtitle')) ?>"></div>
      <div class="field"><label>Descrição</label><input data-sec="hero" data-k="description" value="<?= e($c('hero', 'description')) ?>"></div>
      <div class="field"><label>Texto da data</label><input data-sec="hero" data-k="date_text" value="<?= e($c('hero', 'date_text')) ?>"></div>
      <div class="field"><label>Texto do local</label><input data-sec="hero" data-k="venue_text" value="<?= e($c('hero', 'venue_text')) ?>"></div>
      <div class="field"><label>Botão primário</label><input data-sec="hero" data-k="cta_primary" value="<?= e($c('hero', 'cta_primary')) ?>"></div>
      <div class="field"><label>Botão secundário</label><input data-sec="hero" data-k="cta_secondary" value="<?= e($c('hero', 'cta_secondary')) ?>"></div>
    </div>
    <button class="btn-ad btn-gold-ad sec-save" data-sec="hero">Salvar Hero</button>
    <hr style="border:0;border-top:1px solid var(--ad-line);margin:26px 0;">
    <h3>Contador regressivo</h3>
    <p class="card-sub">Rótulos do contador (a data-alvo fica em Configurações)</p>
    <div class="grid g2">
      <div class="field"><label>Título</label><input data-sec="countdown" data-k="title" value="<?= e($c('countdown', 'title')) ?>"></div>
      <div class="field"><label>Dias</label><input data-sec="countdown" data-k="label_days" value="<?= e($c('countdown', 'label_days')) ?>"></div>
      <div class="field"><label>Horas</label><input data-sec="countdown" data-k="label_hours" value="<?= e($c('countdown', 'label_hours')) ?>"></div>
      <div class="field"><label>Minutos</label><input data-sec="countdown" data-k="label_minutes" value="<?= e($c('countdown', 'label_minutes')) ?>"></div>
      <div class="field"><label>Segundos</label><input data-sec="countdown" data-k="label_seconds" value="<?= e($c('countdown', 'label_seconds')) ?>"></div>
    </div>
    <button class="btn-ad btn-gold-ad sec-save" data-sec="countdown">Salvar contador</button>
  </div>

  <!-- SOBRE -->
  <div class="tab-pane" id="cc-about">
    <div class="field"><label>Kicker</label><input data-sec="about" data-k="kicker" value="<?= e($c('about', 'kicker')) ?>"></div>
    <div class="field"><label>Título</label><input data-sec="about" data-k="title" value="<?= e($c('about', 'title')) ?>"></div>
    <div class="field"><label>Texto institucional</label><textarea data-sec="about" data-k="text" style="min-height:150px;"><?= e($c('about', 'text')) ?></textarea></div>
    <h3 style="margin:16px 0 10px;">Diferenciais (4)</h3>
    <div id="diff-list">
      <?php foreach ($diffs as $i => $d): ?>
      <div class="card diff-item" style="background:#0e0e0e;margin-bottom:10px;">
        <div class="grid g3">
          <div class="field"><label>Ícone</label><select class="d-icon"><?php foreach ($icons as $ic): ?><option value="<?= $ic ?>" <?= ($d['icon'] ?? '') === $ic ? 'selected' : '' ?>><?= $ic ?></option><?php endforeach; ?></select></div>
          <div class="field" style="grid-column:span 2;"><label>Título</label><input class="d-title" value="<?= e($d['title'] ?? '') ?>"></div>
        </div>
        <div class="field"><label>Texto</label><input class="d-text" value="<?= e($d['text'] ?? '') ?>"></div>
      </div>
      <?php endforeach; ?>
    </div>
    <h3 style="margin:16px 0 10px;">Números de impacto (3)</h3>
    <div class="grid g3">
      <?php for ($si = 1; $si <= 3; $si++): ?>
      <div class="field"><label>Número <?= $si ?></label><input data-sec="about" data-k="stat<?= $si ?>_num" value="<?= e($c('about', 'stat' . $si . '_num')) ?>" placeholder="Ex.: 30+"></div>
      <?php endfor; ?>
    </div>
    <div class="grid g3">
      <?php for ($si = 1; $si <= 3; $si++): ?>
      <div class="field"><label>Legenda <?= $si ?></label><input data-sec="about" data-k="stat<?= $si ?>_label" value="<?= e($c('about', 'stat' . $si . '_label')) ?>" placeholder="Ex.: CATEGORIAS PREMIADAS"></div>
      <?php endfor; ?>
    </div>
    <button class="btn-ad btn-gold-ad sec-save" data-sec="about">Salvar seção Sobre</button>
  </div>

  <!-- EXPERIÊNCIA -->
  <div class="tab-pane" id="cc-exp">
    <div class="field"><label>Kicker</label><input data-sec="experience" data-k="kicker" value="<?= e($c('experience', 'kicker')) ?>"></div>
    <div class="field"><label>Título</label><input data-sec="experience" data-k="title" value="<?= e($c('experience', 'title')) ?>"></div>
    <div class="field"><label>Subtítulo</label><input data-sec="experience" data-k="subtitle" value="<?= e($c('experience', 'subtitle')) ?>"></div>
    <h3 style="margin:16px 0 10px;">Cards (6)</h3>
    <div id="exp-list">
      <?php foreach ($exps as $i => $x): ?>
      <div class="card exp-item" style="background:#0e0e0e;margin-bottom:10px;">
        <div class="grid g3">
          <div class="field"><label>Ícone</label><select class="x-icon"><?php foreach ($icons as $ic): ?><option value="<?= $ic ?>" <?= ($x['icon'] ?? '') === $ic ? 'selected' : '' ?>><?= $ic ?></option><?php endforeach; ?></select></div>
          <div class="field" style="grid-column:span 2;"><label>Título</label><input class="x-title" value="<?= e($x['title'] ?? '') ?>"></div>
        </div>
        <div class="field"><label>Texto</label><input class="x-text" value="<?= e($x['text'] ?? '') ?>"></div>
      </div>
      <?php endforeach; ?>
    </div>
    <button class="btn-ad btn-gold-ad sec-save" data-sec="experience">Salvar Experiência</button>
  </div>

  <!-- WCM AWARDS -->
  <div class="tab-pane" id="cc-awards">
    <div class="grid g2">
      <div class="field"><label>Kicker</label><input data-sec="awards" data-k="kicker" value="<?= e($c('awards', 'kicker')) ?>"></div>
      <div class="field"><label>Título gigante</label><input data-sec="awards" data-k="title" value="<?= e($c('awards', 'title')) ?>"></div>
      <div class="field"><label>Subtítulo</label><input data-sec="awards" data-k="subtitle" value="<?= e($c('awards', 'subtitle')) ?>"></div>
      <div class="field"><label>Botão</label><input data-sec="awards" data-k="button" value="<?= e($c('awards', 'button')) ?>"></div>
    </div>
    <div class="field"><label>Texto</label><textarea data-sec="awards" data-k="text" style="min-height:90px;"><?= e($c('awards', 'text')) ?></textarea></div>
    <h3 style="margin:14px 0 10px;">Destaques (3)</h3>
    <div class="grid g3">
      <?php for ($ai = 1; $ai <= 3; $ai++): ?>
      <div class="field"><label>Número <?= $ai ?></label><input data-sec="awards" data-k="stat<?= $ai ?>_num" value="<?= e($c('awards', 'stat' . $ai . '_num')) ?>"></div>
      <?php endfor; ?>
    </div>
    <div class="grid g3">
      <?php for ($ai = 1; $ai <= 3; $ai++): ?>
      <div class="field"><label>Legenda <?= $ai ?></label><input data-sec="awards" data-k="stat<?= $ai ?>_label" value="<?= e($c('awards', 'stat' . $ai . '_label')) ?>"></div>
      <?php endfor; ?>
    </div>
    <button class="btn-ad btn-gold-ad sec-save" data-sec="awards">Salvar WCM Awards</button>
  </div>

  <!-- TÍTULOS -->
  <div class="tab-pane" id="cc-sec">
    <?php
    $secs = [
      'schedule' => 'Programação', 'speakers' => 'Palestrantes', 'venue' => 'Local',
      'tickets' => 'Ingressos', 'faq' => 'FAQ', 'register' => 'Inscrição', 'cta_final' => 'CTA Final', 'footer' => 'Rodapé',
    ];
    foreach ($secs as $sk => $label):
      $keys = [
        'schedule' => ['kicker' => 'Kicker', 'title' => 'Título', 'subtitle' => 'Subtítulo'],
        'speakers' => ['kicker' => 'Kicker', 'title' => 'Título', 'subtitle' => 'Subtítulo'],
        'venue' => ['kicker' => 'Kicker', 'title' => 'Título', 'button' => 'Botão'],
        'tickets' => ['kicker' => 'Kicker', 'title' => 'Título', 'subtitle' => 'Subtítulo', 'button' => 'Botão'],
        'faq' => ['kicker' => 'Kicker', 'title' => 'Título', 'subtitle' => 'Subtítulo'],
        'register' => ['kicker' => 'Kicker', 'title' => 'Título', 'subtitle' => 'Subtítulo', 'button' => 'Botão do formulário', 'success_title' => 'Título de sucesso', 'success_message' => 'Mensagem de sucesso', 'success_note' => 'Nota de sucesso'],
        'cta_final' => ['title' => 'Título', 'subtitle' => 'Subtítulo', 'button' => 'Botão'],
        'footer' => ['about' => 'Texto sobre', 'rights' => 'Direitos autorais'],
      ][$sk];
    ?>
    <h3 style="margin:6px 0 12px;"><?= $label ?></h3>
    <div class="grid g2" style="margin-bottom:8px;">
      <?php foreach ($keys as $kk => $kl): ?>
      <div class="field"><label><?= $kl ?></label><input data-sec="<?= $sk ?>" data-k="<?= $kk ?>" value="<?= e($c($sk, $kk)) ?>"></div>
      <?php endforeach; ?>
    </div>
    <button class="btn-ad btn-ghost-ad btn-sm-ad sec-save" data-sec="<?= $sk ?>" style="margin-bottom:22px;">Salvar <?= $label ?></button>
    <hr style="border:0;border-top:1px solid var(--ad-line);margin-bottom:22px;">
    <?php endforeach; ?>
  </div>

  <!-- FAQ -->
    <!-- FAQ -->
  <div class="tab-pane" id="cc-faq">
    <div class="toolbar"><span class="spacer"></span><button class="btn-ad btn-gold-ad btn-sm-ad" id="faq-new"><?= Icons::get('plus') ?> Nova pergunta</button></div>
    <div id="faq-list">
      <?php foreach ($faqs as $f): ?>
      <div class="list-item" data-id="<?= (int) $f['id'] ?>">
        <div class="grow"><strong><?= e($f['question']) ?></strong><span><?= e(excerpt($f['answer'], 110)) ?></span></div>
        <button class="icon-btn" data-move="up">↑</button>
        <button class="icon-btn" data-move="down">↓</button>
        <button class="icon-btn faq-edit"><?= Icons::get('edit') ?></button>
        <button class="icon-btn faq-del"><?= Icons::get('trash') ?></button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- LEGAL -->
  <div class="tab-pane" id="cc-legal">
    <div class="field"><label>Título — Termos</label><input data-sec="legal" data-k="terms_title" value="<?= e($c('legal', 'terms_title')) ?>"></div>
    <div class="field"><label>Termos de inscrição (HTML simples permitido)</label><textarea data-sec="legal" data-k="terms" style="min-height:170px;"><?= e($c('legal', 'terms')) ?></textarea></div>
    <div class="field"><label>Título — Privacidade</label><input data-sec="legal" data-k="privacy_title" value="<?= e($c('legal', 'privacy_title')) ?>"></div>
    <div class="field"><label>Política de privacidade (HTML simples permitido)</label><textarea data-sec="legal" data-k="privacy" style="min-height:170px;"><?= e($c('legal', 'privacy')) ?></textarea></div>
    <button class="btn-ad btn-gold-ad sec-save" data-sec="legal">Salvar textos legais</button>
  </div>

  <!-- TEMA -->
  <div class="tab-pane" id="cc-theme">
    <div class="grid g2">
      <div class="field"><label>Dourado</label><input type="color" data-sec="theme" data-k="gold" value="<?= e($c('theme', 'gold', '#C9A227')) ?>" style="height:44px;padding:4px;"></div>
      <div class="field"><label>Dourado claro</label><input type="color" data-sec="theme" data-k="gold_light" value="<?= e($c('theme', 'gold_light', '#E7C766')) ?>" style="height:44px;padding:4px;"></div>
      <div class="field"><label>Fundo</label><input type="color" data-sec="theme" data-k="bg" value="<?= e($c('theme', 'bg', '#080808')) ?>" style="height:44px;padding:4px;"></div>
      <div class="field"><label>Fundo secundário</label><input type="color" data-sec="theme" data-k="bg2" value="<?= e($c('theme', 'bg2', '#111111')) ?>" style="height:44px;padding:4px;"></div>
      <div class="field"><label>Fonte dos títulos</label><input data-sec="theme" data-k="font_head" value="<?= e($c('theme', 'font_head', 'Cormorant Garamond')) ?>"></div>
      <div class="field"><label>Fonte dos textos</label><input data-sec="theme" data-k="font_body" value="<?= e($c('theme', 'font_body', 'Inter')) ?>"></div>
    </div>
    <button class="btn-ad btn-gold-ad sec-save" data-sec="theme">Salvar identidade</button>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Salvar seção
  document.querySelectorAll('.sec-save').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var sec = btn.dataset.sec;
      var values = {};
      document.querySelectorAll('[data-sec="' + sec + '"][data-k]').forEach(function (inp) {
        values[inp.dataset.k] = inp.value;
      });
      if (sec === 'about') {
        values.differentials = JSON.stringify(Array.prototype.map.call(document.querySelectorAll('.diff-item'), function (el) {
          return { icon: el.querySelector('.d-icon').value, title: el.querySelector('.d-title').value, text: el.querySelector('.d-text').value };
        }));
      }
      if (sec === 'experience') {
        values.cards = JSON.stringify(Array.prototype.map.call(document.querySelectorAll('.exp-item'), function (el) {
          return { icon: el.querySelector('.x-icon').value, title: el.querySelector('.x-title').value, text: el.querySelector('.x-text').value };
        }));
      }
      btn.disabled = true;
      apiPost('/content/content-save', { section: sec, values: values }).then(function (j) {
        btn.disabled = false;
        if (j.ok) toast('Conteúdo salvo!', 'ok');
      }).catch(function () { btn.disabled = false; });
    });
  });
  // FAQ
  if (typeof bindReorder === 'function') bindReorder('faq-list', 'faqs');
  var FF = [
    { key: 'question', label: 'Pergunta', type: 'text', required: 1 },
    { key: 'answer', label: 'Resposta', type: 'textarea', required: 1 },
  ];
  function fsave(data, done, fail) {
    apiPost('/content/faqs/save', data).then(function (j) {
      if (j.ok) { toast('Salvo!', 'ok'); done(); setTimeout(function () { location.reload(); }, 500); } else fail();
    }).catch(fail);
  }
  document.getElementById('faq-new').addEventListener('click', function () {
    crudModal({ title: 'Nova pergunta', fields: FF, onSave: fsave });
  });
  document.querySelectorAll('.faq-edit').forEach(function (b) {
    b.addEventListener('click', function () {
      var id = b.closest('.list-item').dataset.id;
      api('/content/faqs/get?id=' + id).then(function (j) {
        if (!j.ok || !j.row) return;
        j.row.id = id;
        crudModal({ title: 'Editar pergunta', fields: [{ key: 'id', label: 'ID', type: 'text' }].concat(FF), onSave: fsave }, j.row);
        document.querySelector('#modalCrudDyn [data-k="id"]').closest('.field').style.display = 'none';
      });
    });
  });
  document.querySelectorAll('.faq-del').forEach(function (b) {
    b.addEventListener('click', function () {
      var id = b.closest('.list-item').dataset.id;
      confirmDlg('Excluir pergunta?', 'Ela sumirá da landing.', function () {
        apiPost('/content/faqs/delete', { id: id }).then(function (j) {
          if (j.ok) { toast('Excluída.', 'ok'); setTimeout(function () { location.reload(); }, 500); }
        });
      }, 'Excluir');
    });
  });
});
</script>
