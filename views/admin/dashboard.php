<?php defined('APP') or exit; use App\Core\Icons;
$ov = $overview ?? [];
$extra_js = '<script src="' . e(url('assets/js/admin-dashboard.js')) . '?v=1.0.0" defer></script>';
?>
<div class="grid g4" style="margin-bottom:16px;">
  <div class="stat">
    <div class="stat-top"><span class="stat-lbl">INSCRITOS</span><span class="stat-ic"><?= Icons::get('users') ?></span></div>
    <div class="stat-num"><?= number_format($ov['total'] ?? 0, 0, ',', '.') ?></div>
    <div class="stat-sub"><b>+<?= $ov['today'] ?? 0 ?></b> hoje · <?= $ov['confirmed'] ?? 0 ?> confirmados</div>
  </div>
  <div class="stat">
    <div class="stat-top"><span class="stat-lbl">CHECK-INS</span><span class="stat-ic"><?= Icons::get('scan') ?></span></div>
    <div class="stat-num"><?= number_format($ov['checkins'] ?? 0, 0, ',', '.') ?></div>
    <div class="stat-sub"><?= $ov['checkin_rate'] ?? 0 ?>% dos confirmados presentes</div>
  </div>
  <div class="stat">
    <div class="stat-top"><span class="stat-lbl">PENDENTES</span><span class="stat-ic"><?= Icons::get('clock') ?></span></div>
    <div class="stat-num"><?= number_format($ov['pending'] ?? 0, 0, ',', '.') ?></div>
    <div class="stat-sub"><?= $ov['cancelled'] ?? 0 ?> cancelados no total</div>
  </div>
  <div class="stat">
    <div class="stat-top"><span class="stat-lbl">VAGAS RESTANTES</span><span class="stat-ic"><?= Icons::get('ticket') ?></span></div>
    <div class="stat-num"><?= ($ov['capacity'] ?? 0) > 0 ? number_format($ov['remaining'] ?? 0, 0, ',', '.') : '∞' ?></div>
    <div class="stat-sub"><?= money($ov['revenue'] ?? 0) ?> em potencial de receita</div>
  </div>
</div>

<div class="grid g2" style="margin-bottom:16px;">
  <div class="card">
    <h3>Inscrições por dia</h3>
    <p class="card-sub">Últimos 30 dias</p>
    <div class="chart-box"><canvas id="ch-day"></canvas></div>
  </div>
  <div class="card">
    <h3>Inscrições por horário</h3>
    <p class="card-sub">Distribuição ao longo do dia</p>
    <div class="chart-box"><canvas id="ch-hour"></canvas></div>
  </div>
</div>

<div class="grid" style="grid-template-columns:2fr 1fr;margin-bottom:16px;">
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
      <div><h3>Inscrições recentes</h3><p class="card-sub">Últimos inscritos do evento</p></div>
      <a href="<?= e(url('admin/inscricoes')) ?>" class="btn-ad btn-ghost-ad btn-sm-ad">Ver todas</a>
    </div>
    <div class="table-wrap" style="border:0;">
      <table class="tbl" style="min-width:560px;">
        <thead><tr><th>Código</th><th>Nome</th><th>Ingresso</th><th>Status</th></tr></thead>
        <tbody>
          <?php if (empty($recent)): ?><tr><td colspan="4" style="text-align:center;color:var(--ad-muted);">Nenhuma inscrição ainda.</td></tr><?php endif; ?>
          <?php foreach ($recent as $r): ?>
          <tr class="rowlink" onclick="location.href='<?= e(url('admin/inscricoes/' . $r['id'])) ?>'">
            <td class="mono"><?= e($r['code']) ?></td>
            <td><strong><?= e($r['name']) ?></strong><br><small style="color:var(--ad-muted);"><?= e($r['email']) ?></small></td>
            <td><?= e($r['ticket_name'] ?? '—') ?></td>
            <td><span class="badge <?= $r['status'] === 'CONFIRMED' ? 'b-green' : ($r['status'] === 'CHECKED_IN' ? 'b-blue' : ($r['status'] === 'CANCELLED' ? 'b-red' : 'b-amber')) ?>"><?= e(registration_status_label($r['status'])) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div style="display:flex;flex-direction:column;gap:16px;">
    <div class="card">
      <h3>Ingressos vendidos</h3>
      <p class="card-sub">Por tipo</p>
      <div class="chart-box" style="height:200px;"><canvas id="ch-ticket"></canvas></div>
    </div>
    <div class="card">
      <h3>Comunicação</h3>
      <p class="card-sub">Mensagens e fila</p>
      <div class="report-bar"><span class="lbl">Enviadas</span><span class="track"><span class="fill" style="width:100%;"></span></span><span class="val"><?= $comms['sent'] ?? 0 ?></span></div>
      <div class="report-bar"><span class="lbl">Na fila</span><span class="track"><span class="fill" style="width:<?= ($comms['queued'] ?? 0) > 0 ? '60%' : '2%' ?>;"></span></span><span class="val"><?= $comms['queued'] ?? 0 ?></span></div>
      <div class="report-bar"><span class="lbl">Abertura e-mail</span><span class="track"><span class="fill" style="width:<?= $comms['open_rate'] ?? 0 ?>%;"></span></span><span class="val"><?= $comms['open_rate'] ?? 0 ?>%</span></div>
      <a href="<?= e(url('admin/comunicacao')) ?>" class="btn-ad btn-ghost-ad btn-sm-ad" style="margin-top:10px;">Abrir comunicação</a>
    </div>
  </div>
</div>

<div class="grid g2">
  <div class="card">
    <h3>Origem das inscrições</h3>
    <p class="card-sub">Por canal de origem</p>
    <div class="chart-box" style="height:220px;"><canvas id="ch-source"></canvas></div>
  </div>
  <div class="card">
    <h3>Check-ins por horário</h3>
    <p class="card-sub">Movimento do credenciamento</p>
    <div class="chart-box" style="height:220px;"><canvas id="ch-checkin"></canvas></div>
  </div>
</div>
