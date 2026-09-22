<?php
defined('APP') or exit;
$ev = $event ?? [];
$initial = mb_strtoupper(mb_substr(trim($ev['name'] ?? 'E'), 0, 1));
?>
<header class="header scrolled" id="header">
  <div class="container header-inner">
    <?= brand_html($ev) ?>
    <a href="<?= e(url('')) ?>" class="btn btn-ghost btn-sm header-cta" style="display:inline-flex;">Início</a>
  </div>
</header>

<section class="page-hero">
  <div class="container">
    <span class="kicker center">ACESSO DO PARTICIPANTE</span>
    <h1 class="sec-title">Recuperar meu ticket</h1>
    <p class="sec-sub" style="margin:0 auto 34px;">Informe o e-mail e o código da sua inscrição.</p>
    <div class="lookup-card">
      <?php if (!empty($error)): ?><div class="form-error show"><?= e($error) ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(\App\Core\Csrf::token()) ?>">
        <div class="f-grid">
          <div class="field f-full">
            <label for="email">E-mail da inscrição</label>
            <input type="email" name="email" id="email" required placeholder="voce@email.com">
          </div>
          <div class="field f-full">
            <label for="code">Código da inscrição</label>
            <input type="text" name="code" id="code" required placeholder="EVT-2026-XXXXXX" style="text-transform:uppercase;letter-spacing:1px;">
          </div>
        </div>
        <button class="btn btn-gold btn-block" style="margin-top:20px;">ACESSAR TICKET</button>
      </form>
    </div>
  </div>
</section>
<div class="toast" id="toast"></div>
