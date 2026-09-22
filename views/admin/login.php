<?php defined('APP') or exit; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Acesso restrito — <?= e($event['name'] ?? 'Evento') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0}body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#080808 url('<?= e(url('assets/img/hero.jpg')) ?>') center/cover;color:#fff;font-family:Inter,sans-serif;padding:22px;position:relative}
body::before{content:"";position:absolute;inset:0;background:rgba(8,8,8,.82);backdrop-filter:blur(3px)}
.card{position:relative;width:100%;max-width:420px;background:rgba(17,17,17,.92);border:1px solid rgba(201,162,39,.25);border-radius:20px;padding:44px 40px;box-shadow:0 40px 100px rgba(0,0,0,.7)}
.brand{text-align:center;color:#C9A227;font-size:11px;letter-spacing:5px;font-weight:700}
h1{font-family:'Cormorant Garamond',serif;font-weight:500;text-align:center;font-size:34px;margin:12px 0 4px}
.sub{text-align:center;color:#888;font-size:13.5px;margin-bottom:28px}
label{display:block;font-size:12.5px;color:#bbb;margin:16px 0 7px;letter-spacing:.5px}
input{width:100%;background:#0a0a0a;border:1px solid #2c2c2c;color:#fff;border-radius:10px;padding:13px 14px;font-size:15px}
input:focus{outline:none;border-color:#C9A227;box-shadow:0 0 0 3px rgba(201,162,39,.14)}
.btn{width:100%;margin-top:24px;background:linear-gradient(135deg,#C9A227,#E7C766);color:#141414;border:0;border-radius:10px;padding:15px;font-size:14px;font-weight:700;letter-spacing:2px;cursor:pointer}
.err{background:#2a1212;border:1px solid #5c2222;color:#ffb3b3;padding:12px;border-radius:8px;font-size:13.5px;margin-bottom:6px}
.back{display:block;text-align:center;margin-top:20px;font-size:13px;color:#888}
a{color:#C9A227}
</style>
</head>
<body>
<div class="card">
  <div class="brand">ÁREA RESTRITA</div>
  <h1><?= e(strtok($event['name'] ?? 'Evento', '·—-')) ?></h1>
  <p class="sub">Painel do organizador</p>
  <?php if (!empty($error)): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= App\Core\Csrf::field() ?>
    <label>E-mail</label>
    <input type="email" name="email" required autofocus placeholder="voce@evento.com" value="<?= e($_POST['email'] ?? '') ?>">
    <label>Senha</label>
    <input type="password" name="password" required placeholder="••••••••">
    <button class="btn">ENTRAR NO PAINEL</button>
  </form>
  <a class="back" href="<?= e(url('')) ?>">← Voltar ao site</a>
</div>
</body>
</html>
