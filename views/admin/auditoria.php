<?php defined('APP') or exit; ?>
<div class="card">
  <h3>Logs de auditoria</h3>
  <p class="card-sub">Últimas 200 ações realizadas no painel</p>
  <div class="table-wrap" style="border:0;">
    <table class="tbl">
      <thead><tr><th>Data/hora</th><th>Usuário</th><th>Ação</th><th>Entidade</th><th>IP</th></tr></thead>
      <tbody>
        <?php if (empty($logs)): ?><tr><td colspan="5" style="text-align:center;color:var(--ad-muted);">Nenhum registro.</td></tr><?php endif; ?>
        <?php foreach ($logs as $l): ?>
        <tr>
          <td><?= e(fdate($l['created_at'], true)) ?></td>
          <td><?= e($l['user_name'] ?? ($l['user_id'] ? 'usuário removido' : 'sistema')) ?></td>
          <td><span class="badge b-gold mono" <?= !empty($l['details']) ? 'title="' . e($l['details']) . '"' : '' ?>><?= e($l['action']) ?></span></td>
          <td class="mono"><?= e(trim(($l['entity'] ?? '') . ' #' . ($l['entity_id'] ?? ''), ' #')) ?></td>
          <td class="mono"><?= e($l['ip'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
