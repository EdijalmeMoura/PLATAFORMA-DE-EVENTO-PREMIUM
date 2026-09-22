<?php defined('APP') or exit; use App\Core\Icons;
$s = $stats ?? ['total' => 0, 'present' => 0, 'pending' => 0];
$extra_js = '<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>'
  . '<script src="' . e(url('assets/js/admin-checkin.js')) . '?v=1.0.0" defer></script>';
?>
<div class="grid g3" style="margin-bottom:16px;">
  <div class="stat"><div class="stat-top"><span class="stat-lbl">INSCRITOS</span><span class="stat-ic"><?= Icons::get('users') ?></span></div>
    <div class="stat-num" id="st-total"><?= $s['total'] ?></div><div class="stat-sub">vagas preenchidas</div></div>
  <div class="stat"><div class="stat-top"><span class="stat-lbl">PRESENTES</span><span class="stat-ic"><?= Icons::get('check-circle') ?></span></div>
    <div class="stat-num" id="st-present" style="color:var(--ad-green);"><?= $s['present'] ?></div><div class="stat-sub">check-ins realizados</div></div>
  <div class="stat"><div class="stat-top"><span class="stat-lbl">AGUARDANDO</span><span class="stat-ic"><?= Icons::get('clock') ?></span></div>
    <div class="stat-num" id="st-pending" style="color:var(--ad-amber);"><?= $s['pending'] ?></div><div class="stat-sub">atualiza em tempo real</div></div>
</div>

<div class="grid g2" style="align-items:start;">
  <div class="card">
    <h3>Escanear QR Code</h3>
    <p class="card-sub">Aponte a câmera para o ticket do participante</p>
    <div class="qr-reader" id="qrReader"><div class="empty" style="padding:40px 20px;"><?= Icons::get('scan') ?><p>Iniciando câmera…</p></div></div>
    <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap;">
      <button class="btn-ad btn-gold-ad" id="btn-start"><?= Icons::get('scan') ?> Iniciar leitura</button>
      <button class="btn-ad btn-ghost-ad" id="btn-stop">Parar</button>
    </div>
  </div>
  <div>
    <div class="card" style="margin-bottom:16px;">
      <h3>Busca manual</h3>
      <p class="card-sub">Digite o código da inscrição</p>
      <div style="display:flex;gap:10px;">
        <input id="manual-code" placeholder="EVT-2026-XXXXXX" style="flex:1;background:#0b0b0b;border:1px solid #2a2a2a;color:#fff;border-radius:10px;padding:10px 12px;font-size:15px;text-transform:uppercase;letter-spacing:1px;">
        <button class="btn-ad btn-gold-ad" id="btn-manual">Validar</button>
      </div>
    </div>
    <div class="card">
      <h3>Resultado</h3>
      <p class="card-sub">Validação do ticket</p>
      <div class="check-result" id="result" style="display:none;"></div>
      <div class="empty" id="result-empty"><?= Icons::get('qr') ?><p>Aguardando leitura…</p></div>
    </div>
  </div>
</div>
