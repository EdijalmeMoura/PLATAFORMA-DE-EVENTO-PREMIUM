<?php
defined('APP') or exit;
use App\Core\Icons;
$ev = $event ?? [];
$c = function ($sec, $key, $d = '') use ($content) { return $content[$sec][$key] ?? $d; };
$hero = $content['hero'] ?? [];
$initial = mb_strtoupper(mb_substr(trim($ev['name'] ?? 'E'), 0, 1));
$heroBg = !empty($ev['hero_image']) ? url($ev['hero_image']) : url('assets/img/hero.jpg');
$aboutImg = !empty($ev['about_image']) ? url($ev['about_image']) : url('assets/img/about.jpg');
$venueImg = !empty($ev['venue_image']) ? url($ev['venue_image']) : null;
$diffs = json_decode($c('about', 'differentials', '[]'), true) ?: [];
$exps = json_decode($c('experience', 'cards', '[]'), true) ?: [];
$countdownTarget = $ev['countdown_target'] ?: ($ev['date_start'] ? $ev['date_start'] . ' 19:00:00' : '');
$dateLong = '';
if (!empty($ev['date_start'])) {
    $ts = strtotime($ev['date_start']);
    $meses = [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    $dateLong = date('d', $ts) . ' de ' . $meses[(int) date('n', $ts)] . ' de ' . date('Y', $ts);
}
?>

<!-- ================= HEADER ================= -->
<header class="header" id="header">
  <div class="container header-inner">
    <?= brand_html($ev) ?>
    <nav class="nav" aria-label="Menu principal">
      <a href="#inicio">Início</a>
      <a href="#evento">O Evento</a>
      <a href="#experiencia">Experiência</a>
      <a href="#programacao">Programação</a>
      <a href="#local">Local</a>
      <a href="#ingressos">Ingressos</a>
      <a href="#faq">FAQ</a>
    </nav>
    <a href="<?= e(url('inscricao')) ?>" class="btn btn-gold btn-sm header-cta">Inscreva-se</a>
    <button class="burger" id="burger" aria-label="Abrir menu"><span></span><span></span><span></span></button>
  </div>
</header>
<div class="mobile-menu" id="mobileMenu">
  <a href="#inicio">Início</a>
  <a href="#evento">O Evento</a>
  <a href="#experiencia">Experiência</a>
  <a href="#programacao">Programação</a>
  <a href="#local">Local</a>
  <a href="#ingressos">Ingressos</a>
  <a href="#faq">FAQ</a>
  <a href="<?= e(url('inscricao')) ?>" class="btn btn-gold">Inscreva-se</a>
</div>

<!-- ================= HERO ================= -->
<section class="hero" id="inicio">
  <div class="hero-bg" style="background-image:url('<?= e($heroBg) ?>')"></div>
  <div class="hero-overlay"></div>
  <div class="hero-glow"></div>
  <div class="hero-content">
    <?php if ($hero['badge'] ?? ''): ?>
      <div class="hero-badge fade-up"><span class="dot"></span><?= e($hero['badge']) ?></div>
    <?php endif; ?>
    <div class="hero-kicker fade-up" style="animation-delay:.1s"><?= e($hero['kicker'] ?? '') ?></div>
    <h1 class="hero-title fade-up" style="animation-delay:.2s"><?= e($hero['title'] ?? $ev['name']) ?></h1>
    <?php if ($hero['subtitle'] ?? ''): ?><div class="hero-sub fade-up" style="animation-delay:.3s"><?= e($hero['subtitle']) ?></div><?php endif; ?>
    <p class="hero-desc fade-up" style="animation-delay:.4s"><?= str_ireplace('excelência operacional', '<span class="gold-text">excelência operacional</span>', e($hero['description'] ?? $ev['tagline'])) ?></p>
    <div class="hero-meta fade-up" style="animation-delay:.5s">
      <span><?= Icons::get('calendar') ?><?= e($hero['date_text'] ?? $dateLong) ?></span>
      <span><?= Icons::get('pin') ?><?= e(str_replace("\n", ' · ', $hero['venue_text'] ?? (($ev['venue_name'] ?? '') . ' · ' . ($ev['city'] ?? '') . '—' . ($ev['state'] ?? '')))) ?></span>
    </div>
    <div class="hero-ctas fade-up" style="animation-delay:.6s">
      <a href="<?= e(url('inscricao')) ?>" class="btn btn-gold"><?= e($hero['cta_primary'] ?? 'Garantir minha inscrição') ?></a>
      <a href="#evento" class="btn btn-ghost"><?= e($hero['cta_secondary'] ?? 'Conhecer o evento') ?></a>
    </div>
    <div class="hero-trust fade-up" style="animation-delay:.7s">CONEXÕES DE ALTO NÍVEL <b>✦</b> CONTEÚDO QUE TRANSFORMA <b>✦</b> RESULTADOS QUE FICAM</div>
  </div>
  <div class="scroll-hint"><span>ROLE</span><span class="line"></span></div>
</section>

<!-- ================= MARQUEE ================= -->
<div class="marquee" aria-hidden="true">
  <div class="marquee-track">
    <span><b>✦</b> CONEXÕES DE ALTO NÍVEL <b>✦</b> CONTEÚDO QUE TRANSFORMA <b>✦</b> RESULTADOS QUE FICAM <b>✦</b> CASES REAIS <b>✦</b> WCM AWARDS <b>✦</b> 08 DE OUTUBRO DE 2026</span><span><b>✦</b> CONEXÕES DE ALTO NÍVEL <b>✦</b> CONTEÚDO QUE TRANSFORMA <b>✦</b> RESULTADOS QUE FICAM <b>✦</b> CASES REAIS <b>✦</b> WCM AWARDS <b>✦</b> 08 DE OUTUBRO DE 2026</span>
  </div>
</div>

<!-- ================= COUNTDOWN ================= -->
<section class="countdown" id="countdown" data-target="<?= e($countdownTarget) ?>">
  <div class="container">
    <div class="countdown-title"><?= e($c('countdown', 'title', 'FALTAM')) ?></div>
    <div class="count-grid">
      <div class="count-cell"><div class="count-num" id="cd-d">00</div><div class="count-lbl"><?= e($c('countdown', 'label_days', 'DIAS')) ?></div></div>
      <div class="count-cell"><div class="count-num" id="cd-h">00</div><div class="count-lbl"><?= e($c('countdown', 'label_hours', 'HORAS')) ?></div></div>
      <div class="count-cell"><div class="count-num" id="cd-m">00</div><div class="count-lbl"><?= e($c('countdown', 'label_minutes', 'MINUTOS')) ?></div></div>
      <div class="count-cell"><div class="count-num" id="cd-s">00</div><div class="count-lbl"><?= e($c('countdown', 'label_seconds', 'SEGUNDOS')) ?></div></div>
    </div>
  </div>
</section>

<!-- ================= SOBRE ================= -->
<section class="section" id="evento">
  <div class="container">
    <div class="center reveal">
      <span class="kicker center"><?= e($c('about', 'kicker', 'O EVENTO')) ?></span>
      <h2 class="sec-title"><?= e($c('about', 'title', 'Uma experiência além do evento')) ?></h2>
    </div>
    <div class="about-grid">
      <div class="about-img reveal"><img src="<?= e($aboutImg) ?>" alt="Sobre o evento" loading="lazy"></div>
      <div class="about-text reveal reveal-d1"><?= nl2br(e($c('about', 'text', $ev['description'] ?? ''))) ?></div>
    </div>
    <?php if ($c('about', 'stat1_num', '')): ?>
    <div class="stats-row reveal">
      <?php for ($si = 1; $si <= 3; $si++): if (!$c('about', 'stat' . $si . '_num', '')) continue; ?>
      <div class="stat-card"><b><?= e($c('about', 'stat' . $si . '_num')) ?></b><span><?= e($c('about', 'stat' . $si . '_label')) ?></span></div>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php if ($diffs): ?>
    <div class="diff-grid">
      <?php foreach ($diffs as $i => $d): ?>
      <div class="diff reveal <?= $i > 0 ? 'reveal-d' . $i : '' ?>">
        <?= Icons::get($d['icon'] ?? 'spark') ?>
        <h3><?= e($d['title'] ?? '') ?></h3>
        <p><?= e($d['text'] ?? '') ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<div class="divider"></div>

<!-- ================= EXPERIÊNCIA ================= -->
<section class="section" id="experiencia">
  <div class="container">
    <div class="center reveal">
      <span class="kicker center"><?= e($c('experience', 'kicker', 'EXPERIÊNCIA')) ?></span>
      <h2 class="sec-title"><?= e($c('experience', 'title', 'O que esperar')) ?></h2>
      <p class="sec-sub"><?= e($c('experience', 'subtitle', '')) ?></p>
    </div>
    <div class="exp-grid <?= count($exps) === 5 ? 'cols-5' : '' ?>">
      <?php foreach ($exps as $i => $x): ?>
      <div class="exp-card reveal <?= $i % 3 > 0 ? 'reveal-d' . ($i % 3) : '' ?>">
        <span class="exp-num">0<?= $i + 1 ?></span>
        <?= Icons::get($x['icon'] ?? 'star') ?>
        <h3><?= e($x['title'] ?? '') ?></h3>
        <p><?= e($x['text'] ?? '') ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<div class="divider"></div>

<!-- ================= WCM AWARDS ================= -->
<section class="section awards" id="awards">
  <div class="container">
    <div class="reveal">
      <span class="kicker center"><?= e($c('awards', 'kicker', 'RECONHECIMENTO')) ?></span>
      <div class="awards-trophy"><?= Icons::get('star') ?></div>
      <h2 class="awards-title"><?= e($c('awards', 'title', 'WCM AWARDS')) ?></h2>
      <div class="awards-sub"><?= e($c('awards', 'subtitle', '')) ?></div>
      <p class="awards-text"><?= e($c('awards', 'text', '')) ?></p>
      <?php if ($c('awards', 'stat1_num', '')): ?>
      <div class="awards-stats">
        <?php for ($ai = 1; $ai <= 3; $ai++): if (!$c('awards', 'stat' . $ai . '_num', '')) continue; ?>
        <span><b><?= e($c('awards', 'stat' . $ai . '_num')) ?></b><?= e($c('awards', 'stat' . $ai . '_label')) ?></span>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
      <a href="<?= e(url('inscricao')) ?>" class="btn btn-gold"><?= e($c('awards', 'button', 'Garantir minha presença')) ?></a>
    </div>
  </div>
</section>

<div class="divider"></div>

<!-- ================= PROGRAMAÇÃO ================= -->
<?php if (!empty($schedule)): ?>
<section class="section" id="programacao">
  <div class="container">
    <div class="center reveal">
      <span class="kicker center"><?= e($c('schedule', 'kicker', 'PROGRAMAÇÃO')) ?></span>
      <h2 class="sec-title"><?= e($c('schedule', 'title', 'Programação')) ?></h2>
      <p class="sec-sub"><?= e($c('schedule', 'subtitle', '')) ?></p>
    </div>
    <div class="timeline">
      <?php foreach ($schedule as $s): ?>
      <div class="tl-item reveal">
        <div class="tl-time"><?= e($s['stime']) ?></div>
        <div class="tl-card">
          <h3><?= e($s['title']) ?></h3>
          <?php if ($s['description']): ?><p><?= e($s['description']) ?></p><?php endif; ?>
          <div class="tl-meta">
            <?php if ($s['speaker']): ?><span><?= Icons::get('mic') ?><?= e($s['speaker']) ?></span><?php endif; ?>
            <?php if ($s['slocation']): ?><span><?= Icons::get('pin') ?><?= e($s['slocation']) ?></span><?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<div class="divider"></div>
<?php endif; ?>

<!-- ================= PALESTRANTES ================= -->
<?php if (!empty($speakers)): ?>
<section class="section" id="palestrantes">
  <div class="container">
    <div class="center reveal">
      <span class="kicker center"><?= e($c('speakers', 'kicker', 'PALCO')) ?></span>
      <h2 class="sec-title"><?= e($c('speakers', 'title', 'Convidados especiais')) ?></h2>
      <p class="sec-sub"><?= e($c('speakers', 'subtitle', '')) ?></p>
    </div>
    <div class="spk-grid">
      <?php foreach ($speakers as $i => $s): ?>
      <div class="spk reveal <?= $i % 4 > 0 ? 'reveal-d' . min(3, $i % 4) : '' ?>">
        <div class="spk-photo">
          <?php if ($s['photo']): ?><img src="<?= e(url($s['photo'])) ?>" alt="<?= e($s['name']) ?>" loading="lazy">
          <?php else: ?><span class="spk-initial"><?= e(mb_strtoupper(mb_substr($s['name'], 0, 1))) ?></span><?php endif; ?>
        </div>
        <div class="spk-body">
          <h3><?= e($s['name']) ?></h3>
          <?php if ($s['role']): ?><div class="spk-role"><?= e($s['role']) ?></div><?php endif; ?>
          <?php if ($s['company']): ?><div class="spk-co"><?= e($s['company']) ?></div><?php endif; ?>
          <?php if ($s['bio']): ?><p class="spk-bio"><?= e($s['bio']) ?></p><?php endif; ?>
          <?php if ($s['instagram'] || $s['linkedin']): ?>
          <div class="spk-social">
            <?php if ($s['instagram']): ?><a href="<?= e($s['instagram']) ?>" target="_blank" rel="noopener" aria-label="Instagram"><?= Icons::get('instagram') ?></a><?php endif; ?>
            <?php if ($s['linkedin']): ?><a href="<?= e($s['linkedin']) ?>" target="_blank" rel="noopener" aria-label="LinkedIn"><?= Icons::get('linkedin') ?></a><?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<div class="divider"></div>
<?php endif; ?>

<!-- ================= LOCAL ================= -->
<section class="section" id="local">
  <div class="container">
    <div class="center reveal">
      <span class="kicker center"><?= e($c('venue', 'kicker', 'LOCAL')) ?></span>
      <h2 class="sec-title"><?= e($c('venue', 'title', 'Onde a experiência acontece')) ?></h2>
    </div>
    <div class="venue-grid">
      <div class="venue-info reveal">
        <?php if ($venueImg): ?><div class="venue-img"><img src="<?= e($venueImg) ?>" alt="<?= e($ev['venue_name']) ?>" loading="lazy"></div><?php endif; ?>
        <h3><?= e($ev['venue_name'] ?? '') ?></h3>
        <div style="color:var(--muted);font-size:14px;margin-bottom:10px;"><?= e($ev['address'] ?? '') ?> · <?= e($ev['city'] ?? '') ?> — <?= e($ev['state'] ?? '') ?></div>
        <div class="venue-row"><?= Icons::get('calendar') ?><span><?= e($dateLong) ?></span></div>
        <div class="venue-row"><?= Icons::get('clock') ?><span><?= e($ev['time_text'] ?? '') ?></span></div>
        <div class="venue-row"><?= Icons::get('pin') ?><span><?= e(($ev['venue_name'] ?? '') . ' — ' . ($ev['city'] ?? '') . '/' . ($ev['state'] ?? '')) ?></span></div>
        <?php if ($ev['map_url']): ?>
        <a href="<?= e($ev['map_url']) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-sm" style="margin-top:22px;"><?= Icons::get('pin') ?><?= e($c('venue', 'button', 'Como chegar')) ?></a>
        <?php endif; ?>
      </div>
      <div class="venue-map reveal reveal-d1">
        <?php if (!empty($ev['maps_embed'])): ?>
          <?= $ev['maps_embed'] ?>
        <?php else: ?>
          <iframe title="Mapa" loading="lazy" src="https://www.google.com/maps?q=<?= urlencode(($ev['venue_name'] ?? '') . ' ' . ($ev['city'] ?? '') . ' ' . ($ev['state'] ?? '')) ?>&output=embed"></iframe>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<div class="divider"></div>

<!-- ================= INGRESSOS ================= -->
<section class="section" id="ingressos">
  <div class="container">
    <div class="center reveal">
      <span class="kicker center"><?= e($c('tickets', 'kicker', 'INGRESSOS')) ?></span>
      <h2 class="sec-title"><?= e($c('tickets', 'title', 'Escolha sua experiência')) ?></h2>
      <p class="sec-sub"><?= e($c('tickets', 'subtitle', '')) ?></p>
    </div>
    <div class="tix-grid <?= count($tickets) === 2 ? 'cols-2' : '' ?>">
      <?php
      $count = count($tickets);
      foreach ($tickets as $i => $t):
        $isFeatured = ($count >= 3 && $i === 1) || ($count < 3 && $i === $count - 1);
        $remaining = ((int) $t['quantity'] > 0) ? max(0, (int) $t['quantity'] - (int) $t['sold']) : null;
        $soldOut = $remaining !== null && $remaining <= 0;
        $benefits = array_filter(array_map('trim', explode("\n", str_replace(["\r", ";"], ["", "\n"], $t['benefits'] ?? ''))));
      ?>
      <div class="tix reveal <?= $isFeatured ? 'featured' : '' ?> <?= $soldOut ? 'tix-soldout' : '' ?>">
        <?php if ($isFeatured && !$soldOut): ?><span class="tix-flag">MAIS ESCOLHIDO</span><?php endif; ?>
        <div class="tix-name"><?= e($t['name']) ?></div>
        <div class="tix-price"><?= $t['price'] > 0 ? 'R$ ' . number_format($t['price'], 2, ',', '.') : 'Grátis' ?></div>
        <div class="tix-desc"><?= e($t['description'] ?? '') ?></div>
        <?php if ($benefits): ?>
        <ul class="tix-list">
          <?php foreach ($benefits as $b): ?><li><?= Icons::get('check') ?><span><?= e($b) ?></span></li><?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <?php if ($soldOut): ?>
          <div class="tix-left">ESGOTADO</div>
          <button class="btn btn-ghost btn-block" disabled>ESGOTADO</button>
        <?php else: ?>
          <?php if ($remaining !== null && $remaining <= 20): ?><div class="tix-left">RESTAM APENAS <?= $remaining ?> VAGAS</div><?php endif; ?>
          <a href="<?= e(url('inscricao?ingresso=' . $t['id'])) ?>" class="btn <?= $isFeatured ? 'btn-gold' : 'btn-ghost' ?> btn-block"><?= e($c('tickets', 'button', 'Escolher ingresso')) ?></a>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<div class="divider"></div>

<!-- ================= FAQ ================= -->
<?php if (!empty($faqs)): ?>
<section class="section" id="faq">
  <div class="container">
    <div class="center reveal">
      <span class="kicker center"><?= e($c('faq', 'kicker', 'DÚVIDAS')) ?></span>
      <h2 class="sec-title"><?= e($c('faq', 'title', 'Perguntas frequentes')) ?></h2>
      <p class="sec-sub"><?= e($c('faq', 'subtitle', '')) ?></p>
    </div>
    <div class="faq-list">
      <?php foreach ($faqs as $f): ?>
      <div class="faq reveal">
        <button class="faq-q"><?= e($f['question']) ?><?= Icons::get('chev-down') ?></button>
        <div class="faq-a"><div><?= nl2br(e($f['answer'])) ?></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<div class="divider"></div>
<?php endif; ?>

<!-- ================= CTA FINAL ================= -->
<section class="cta-final">
  <div class="container reveal">
    <h2><?= e($c('cta_final', 'title', 'Garanta sua presença')) ?></h2>
    <p><?= e($c('cta_final', 'subtitle', '')) ?></p>
    <div class="cta-badges">
      <span>◈ <?= e($dateLong) ?></span>
      <span>◷ <?= e($ev['time_text'] ?? '') ?></span>
      <span>◎ <?= e(($ev['venue_name'] ?? '') . ' · ' . ($ev['city'] ?? '') . '/' . ($ev['state'] ?? '')) ?></span>
    </div>
    <a href="<?= e(url('inscricao')) ?>" class="btn btn-gold"><?= e($c('cta_final', 'button', 'Quero participar')) ?></a>
  </div>
</section>

<!-- ================= FOOTER ================= -->
<footer>
  <div class="container">
    <div class="foot-grid">
      <div>
        <a href="<?= e(url('')) ?>" class="brand">
          <span class="brand-mark"><?php if (!empty($ev['logo'])): ?><img src="<?= e(url($ev['logo'])) ?>" alt="Logo"><?php else: ?><?= e($initial) ?><?php endif; ?></span>
          <span class="brand-name"><?= e($ev['name']) ?></span>
        </a>
        <p class="foot-about"><?= e($c('footer', 'about', '')) ?></p>
      </div>
      <div>
        <div class="foot-title">NAVEGAÇÃO</div>
        <ul class="foot-links">
          <li><a href="#evento">O Evento</a></li>
          <li><a href="#programacao">Programação</a></li>
          <li><a href="#ingressos">Ingressos</a></li>
          <li><a href="<?= e(url('inscricao')) ?>">Inscreva-se</a></li>
          <li><a href="<?= e(url('meu-ticket')) ?>">Recuperar ticket</a></li>
        </ul>
      </div>
      <div>
        <div class="foot-title">ATENDIMENTO</div>
        <ul class="foot-links">
          <?php if ($ev['contact_email']): ?><li><a href="mailto:<?= e($ev['contact_email']) ?>"><?= e($ev['contact_email']) ?></a></li><?php endif; ?>
          <?php if ($ev['contact_phone']): ?><li><a href="tel:<?= e(preg_replace('/\D/', '', $ev['contact_phone'])) ?>"><?= e($ev['contact_phone']) ?></a></li><?php endif; ?>
          <li><a href="<?= e(url('termos')) ?>">Termos de inscrição</a></li>
          <li><a href="<?= e(url('privacidade')) ?>">Política de privacidade</a></li>
        </ul>
      </div>
    </div>
    <div class="foot-bottom">
      <span><?= e($c('footer', 'rights', '© 2026 ' . ($ev['name'] ?? '') . '. Todos os direitos reservados.')) ?></span>
      <span><a href="<?= e(url('meu-ticket')) ?>">Meu ticket</a> &nbsp;·&nbsp; <a href="<?= e(url('admin/login')) ?>">Área do organizador</a></span>
    </div>
  </div>
</footer>

<div class="toast" id="toast"></div>
