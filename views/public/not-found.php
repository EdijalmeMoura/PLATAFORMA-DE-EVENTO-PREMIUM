<?php defined('APP') or exit; ?>
<header class="header scrolled" id="header">
  <div class="container header-inner">
    <a href="<?= e(url('')) ?>" class="brand"><span class="brand-mark">E</span><span class="brand-name"><?= e($event['name'] ?? 'Evento') ?></span></a>
  </div>
</header>
<section class="notfound">
  <h1>404</h1>
  <h2 style="font-family:var(--font-head);font-size:32px;margin-bottom:10px;">Página não encontrada</h2>
  <p style="color:var(--muted);margin-bottom:28px;">O endereço que você tentou acessar não existe.</p>
  <a href="<?= e(url('')) ?>" class="btn btn-gold">VOLTAR AO INÍCIO</a>
</section>
