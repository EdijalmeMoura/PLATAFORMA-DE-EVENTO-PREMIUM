<?php
defined('APP') or exit;
/** Layout público premium */
$ev = $event ?? ['name' => 'Evento'];
$ct = $content ?? [];
$theme = $ct['theme'] ?? [];
$gold = $theme['gold'] ?? '#C9A227';
$goldLight = $theme['gold_light'] ?? '#E7C766';
$bg = $theme['bg'] ?? '#080808';
$bg2 = $theme['bg2'] ?? '#111111';
$fontHead = $theme['font_head'] ?? 'Cormorant Garamond';
$fontBody = $theme['font_body'] ?? 'Inter';
$title = $page_title ?? ($ev['name'] ?? 'Evento');
$logo = $ev['logo'] ?? '';
$initial = mb_strtoupper(mb_substr(trim($ev['name'] ?? 'E'), 0, 1));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($ev['tagline'] ?? '') ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($ev['tagline'] ?? '') ?>">
<meta property="og:type" content="website">
<meta name="theme-color" content="#080808">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%23080808'/%3E%3Ctext x='32' y='44' font-family='Georgia' font-size='36' fill='%23C9A227' text-anchor='middle'%3E<?= e($initial) ?>%3C/text%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{
  --gold:<?= e($gold) ?>; --gold-light:<?= e($goldLight) ?>;
  --bg:<?= e($bg) ?>; --bg2:<?= e($bg2) ?>;
  --font-head:'<?= e($fontHead) ?>',Georgia,serif;
  --font-body:'<?= e($fontBody) ?>',-apple-system,'Segoe UI',sans-serif;
}
</style>
<link rel="stylesheet" href="<?= e(url('assets/css/landing.css')) ?>?v=1.0.0">
<?php if (!empty($extra_css)): ?><?= $extra_css ?><?php endif; ?>
</head>
<body>
<?= $__content ?? '' ?>
<script src="<?= e(url('assets/js/landing.js')) ?>?v=1.0.0" defer></script>
<?php if (!empty($extra_js)): ?><?= $extra_js ?><?php endif; ?>
</body>
</html>
