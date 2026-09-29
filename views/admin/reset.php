<?php defined('APP') or exit; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Redefinir senha — <?= e($event['name'] ?? 'Evento') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0}body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#080808;color:#fff;font-family:Inter,sans-serif;padding:22px}
.card{width:100%;max-width:420px;background:rgba(17,17,17,.94);border:1px solid rgba(201,162,39,.25);border-radius:20px;padding:44px 40px;box-shadow:0 40px 100px rgba(0,0,0,.7)}
.brand{text-align:center;color:#C9A227;font-size:11px;letter-spacing:5px;font-weight:700}
h1{font-family:'Cormorant Garamond',serif;font-weight:500;text-align:center;font-size:32px;margin:12px 0 4px}
.sub{text-align:center;color:#888;font-size:13.5px;margin-bottom:24px}
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
  <h1>Nova senha</h1>
  <p class="sub">Defina sua nova senha de acesso</p>
  <?php if (!empty($error)): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
  <?php if (!empty($valid)): ?>
  <form method="post">
    <?= App\Core\Csrf::field() ?>
    <input type="hidden" name="email" value="<?= e($email ?? '') ?>">
    <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
    <label>Nova senha (mín. 8 caracteres)</label>
    <input type="password" name="password" required minlength="8" autofocus placeholder="••••••••">
    <label>Confirmar nova senha</label>
    <input type="password" name="password2" required minlength="8" placeholder="••••••••">
    <button class="btn">SALVAR NOVA SENHA</button>
  </form>
  <?php else: ?>
  <p style="text-align:center;"><a href="<?= e(url('admin/esqueci-senha')) ?>">Solicitar novo link →</a></p>
  <?php endif; ?>
  <a class="back" href="<?= e(url('admin/login')) ?>">← Voltar ao login</a>
</div>
</body>
</html>
