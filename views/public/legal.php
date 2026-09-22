<?php
defined('APP') or exit;
$ev = $event ?? [];
$initial = mb_strtoupper(mb_substr(trim($ev['name'] ?? 'E'), 0, 1));
$isTerms = ($which ?? 'terms') === 'terms';
$title = $isTerms ? ($content['legal']['terms_title'] ?? 'Termos de Inscrição') : ($content['legal']['privacy_title'] ?? 'Política de Privacidade');
$body = $isTerms ? ($content['legal']['terms'] ?? '') : ($content['legal']['privacy'] ?? '');
?>
<header class="header scrolled" id="header">
  <div class="container header-inner">
    <?= brand_html($ev) ?>
    <a href="<?= e(url('')) ?>" class="btn btn-ghost btn-sm header-cta" style="display:inline-flex;">Início</a>
  </div>
</header>

<section class="page-hero">
  <div class="container legal-wrap" style="text-align:left;">
    <span class="kicker"><?= $isTerms ? 'TERMOS' : 'PRIVACIDADE · LGPD' ?></span>
    <h1 class="sec-title" style="margin-bottom:28px;"><?= e($title) ?></h1>
    <div class="legal-card"><?= $body ?></div>
    <p style="margin-top:22px;text-align:center;"><a href="<?= e(url('')) ?>">← Voltar ao início</a></p>
  </div>
</section>
