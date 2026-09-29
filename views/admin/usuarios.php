<?php defined('APP') or exit; use App\Core\Auth; use App\Core\Icons;
$allPerms = Auth::allPermissions();
?>
<div class="toolbar">
  <span style="color:var(--ad-muted);font-size:13.5px;">Equipe com acesso ao painel. Perfis: Administrador, Gestor, Credenciamento, Comunicação e Visualização.</span>
  <span class="spacer"></span>
  <button class="btn-ad btn-gold-ad btn-sm-ad" id="btn-new"><?= Icons::get('plus') ?> Novo usuário</button>
</div>
<div class="table-wrap">
  <table class="tbl">
    <thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Status</th><th>Último acesso</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($users as $usr): ?>
      <tr>
        <td><strong><?= e($usr['name']) ?></strong><?= $usr['id'] == Auth::id() ? ' <span class="badge b-gold">você</span>' : '' ?></td>
        <td><?= e($usr['email']) ?></td>
        <td><span class="badge b-blue"><?= e($usr['role_name']) ?></span></td>
        <td><?= $usr['active'] ? '<span class="badge b-green">Ativo</span>' : '<span class="badge b-gray">Inativo</span>' ?></td>
        <td><?= $usr['last_login_at'] ? e(fdate($usr['last_login_at'], true)) : '—' ?></td>
        <td style="white-space:nowrap;">
          <button class="icon-btn u-edit" data-id="<?= (int) $usr['id'] ?>" data-name="<?= e($usr['name']) ?>" data-email="<?= e($usr['email']) ?>" data-role="<?= e($usr['role_slug']) ?>" data-active="<?= (int) $usr['active'] ?>" title="Editar"><?= Icons::get('edit') ?></button>
          <?php if ($usr['id'] != Auth::id()): ?>
          <button class="icon-btn u-del" data-id="<?= (int) $usr['id'] ?>" title="Excluir"><?= Icons::get('trash') ?></button>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="card" style="margin-top:16px;">
  <h3>Perfis & permissões</h3>
  <p class="card-sub">Marque o que cada perfil pode fazer. O Administrador sempre tem acesso total.</p>
  <?php foreach ($roles as $role):
    if ($role['slug'] === 'admin') continue;
    $rp = json_decode($role['permissions'] ?? '[]', true) ?: [];
  ?>
  <div class="card" style="background:#0e0e0e;margin-bottom:12px;" data-role="<?= (int) $role['id'] ?>">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
      <strong style="font-size:14.5px;"><?= e($role['name']) ?></strong>
      <button class="btn-ad btn-gold-ad btn-sm-ad role-save">Salvar permissões</button>
    </div>
    <div class="perm-grid">
      <?php foreach ($allPerms as $pk => $pl): ?>
      <label class="perm"><input type="checkbox" value="<?= e($pk) ?>" <?= in_array($pk, $rp, true) ? 'checked' : '' ?>> <?= e($pl) ?></label>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="modal" id="modalUser">
  <div class="modal-card">
    <div class="modal-head"><h3 id="mu-title">Novo usuário</h3><button class="icon-btn" data-close><?= Icons::get('x') ?></button></div>
    <div class="modal-body">
      <input type="hidden" id="mu-id" value="">
      <div class="field"><label>Nome</label><input id="mu-name"></div>
      <div class="field"><label>E-mail (login)</label><input id="mu-email" type="email"></div>
      <div class="field-row">
        <div class="field"><label>Perfil</label>
          <select id="mu-role"><?php foreach ($roles as $r): ?><option value="<?= (int) $r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?></select>
        </div>
        <div class="field"><label>Status</label>
          <div style="display:flex;gap:10px;align-items:center;padding:9px 0;"><label class="toggle"><input type="checkbox" id="mu-active" checked><span></span></label><span style="font-size:13px;color:var(--ad-muted);">Ativo</span></div>
        </div>
      </div>
      <div class="field"><label>Senha (mín. 8 caracteres)</label><input id="mu-pass" type="password" placeholder="Preencha para criar ou alterar"><div class="help">Na edição, deixe em branco para manter a atual.</div></div>
    </div>
    <div class="modal-foot">
      <button class="btn-ad btn-ghost-ad" data-close>Cancelar</button>
      <button class="btn-ad btn-gold-ad" id="mu-save">Salvar</button>
    </div>
  </div>
</div>

<script>
(function () {
  var roleMap = {}, defaultRole = '';
  <?php foreach ($roles as $r): ?>roleMap['<?= $r['slug'] ?>'] = <?= (int) $r['id'] ?>;
  <?php endforeach; ?>
  defaultRole = roleMap['visualizacao'] || Object.keys(roleMap).map(function (k) { return roleMap[k]; }).pop() || '';
  function openModalUser(u) {
    document.getElementById('mu-title').textContent = u ? 'Editar usuário' : 'Novo usuário';
    document.getElementById('mu-id').value = u ? u.id : '';
    document.getElementById('mu-name').value = u ? u.name : '';
    document.getElementById('mu-email').value = u ? u.email : '';
    document.getElementById('mu-role').value = u ? (roleMap[u.role] || defaultRole) : defaultRole;
    document.getElementById('mu-active').checked = u ? +u.active === 1 : true;
    document.getElementById('mu-pass').value = '';
    openModal('modalUser');
  }
  document.getElementById('btn-new').addEventListener('click', function () { openModalUser(null); });
  document.querySelectorAll('.u-edit').forEach(function (b) {
    b.addEventListener('click', function () {
      openModalUser({ id: b.dataset.id, name: b.dataset.name, email: b.dataset.email, role: b.dataset.role, active: b.dataset.active });
    });
  });
  document.getElementById('mu-save').addEventListener('click', function () {
    apiPost('/users/save', {
      id: document.getElementById('mu-id').value,
      name: document.getElementById('mu-name').value,
      email: document.getElementById('mu-email').value,
      role_id: document.getElementById('mu-role').value,
      active: document.getElementById('mu-active').checked ? 1 : 0,
      password: document.getElementById('mu-pass').value,
    }).then(function (j) {
      if (j.ok) { toast('Usuário salvo!', 'ok'); closeModal('modalUser'); setTimeout(function () { location.reload(); }, 600); }
    });
  });
  document.querySelectorAll('.role-save').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var card = btn.closest('[data-role]');
      var perms = [];
      card.querySelectorAll('input[type="checkbox"]:checked').forEach(function (cb) { perms.push(cb.value); });
      btn.disabled = true;
      apiPost('/roles', { role_id: card.dataset.role, permissions: perms }).then(function (j) {
        btn.disabled = false;
        if (j.ok) toast('Permissões atualizadas!', 'ok');
      }).catch(function () { btn.disabled = false; });
    });
  });
  document.querySelectorAll('.u-del').forEach(function (b) {
    b.addEventListener('click', function () {
      confirmDlg('Excluir usuário?', 'O acesso será removido imediatamente.', function () {
        apiPost('/users/delete', { id: b.dataset.id }).then(function (j) {
          if (j.ok) { toast('Excluído.', 'ok'); setTimeout(function () { location.reload(); }, 600); }
        });
      }, 'Excluir');
    });
  });
})();
</script>
