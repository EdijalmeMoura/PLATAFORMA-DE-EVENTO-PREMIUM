<?php defined('APP') or exit; use App\Core\Icons;
$extra_js = '<script src="' . e(url('assets/js/admin-crud.js')) . '?v=1.0.0" defer></script>';
?>
<div class="toolbar">
  <span style="color:var(--ad-muted);font-size:13.5px;">Convidados exibidos na landing.</span>
  <span class="spacer"></span>
  <button class="btn-ad btn-gold-ad btn-sm-ad" id="btn-new"><?= Icons::get('plus') ?> Novo convidado</button>
</div>
<div id="list">
  <?php if (empty($items)): ?><div class="empty"><?= Icons::get('mic') ?><p>Nenhum convidado cadastrado.</p></div><?php endif; ?>
  <?php foreach ($items as $it): ?>
  <div class="list-item" data-id="<?= (int) $it['id'] ?>">
    <?php if ($it['photo']): ?><img class="img-thumb round" src="<?= e(url($it['photo'])) ?>" alt="">
    <?php else: ?><span class="avatar" style="width:52px;height:52px;flex:none;"><?= e(mb_strtoupper(mb_substr($it['name'], 0, 1))) ?></span><?php endif; ?>
    <div class="grow"><strong><?= e($it['name']) ?></strong><span><?= e(trim(($it['role'] ?: '') . ' · ' . ($it['company'] ?: ''), ' ·')) ?></span></div>
    <button class="icon-btn" data-move="up" title="Subir">↑</button>
    <button class="icon-btn" data-move="down" title="Descer">↓</button>
    <button class="icon-btn act-edit" title="Editar"><?= Icons::get('edit') ?></button>
    <button class="icon-btn act-del" title="Excluir"><?= Icons::get('trash') ?></button>
  </div>
  <?php endforeach; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  bindReorder('list', 'speakers');
  var FIELDS = [
    { key: 'name', label: 'Nome', type: 'text', required: 1 },
    { key: 'role', label: 'Cargo', type: 'text', placeholder: 'Ex.: CEO & Fundadora' },
    { key: 'company', label: 'Empresa', type: 'text' },
    { key: 'bio', label: 'Mini biografia', type: 'textarea' },
    { key: 'photo', label: 'Foto', type: 'image' },
    { key: 'instagram', label: 'Instagram (URL, opcional)', type: 'text' },
    { key: 'linkedin', label: 'LinkedIn (URL, opcional)', type: 'text' },
  ];
  function save(data, done, fail) {
    apiPost('/content/speakers/save', data).then(function (j) {
      if (j.ok) { toast('Salvo!', 'ok'); done(); setTimeout(function () { location.reload(); }, 500); }
      else fail();
    }).catch(fail);
  }
  document.getElementById('btn-new').addEventListener('click', function () {
    crudModal({ title: 'Novo convidado', fields: FIELDS, folder: 'speakers', onSave: save });
  });
  document.querySelectorAll('.act-edit').forEach(function (b) {
    b.addEventListener('click', function () {
      var id = b.closest('.list-item').dataset.id;
      api('/content/speakers/get?id=' + id).then(function (j) {
        if (!j.ok || !j.row) return;
        j.row.id = id;
        crudModal({ title: 'Editar convidado', fields: [{ key: 'id', label: 'ID', type: 'text' }].concat(FIELDS), folder: 'speakers', onSave: save }, j.row);
        document.querySelector('#modalCrudDyn [data-k="id"]').closest('.field').style.display = 'none';
      });
    });
  });
  document.querySelectorAll('.act-del').forEach(function (b) {
    b.addEventListener('click', function () {
      var id = b.closest('.list-item').dataset.id;
      confirmDlg('Excluir convidado?', 'Ele será removido da landing.', function () {
        apiPost('/content/speakers/delete', { id: id }).then(function (j) {
          if (j.ok) { toast('Excluído.', 'ok'); setTimeout(function () { location.reload(); }, 500); }
        });
      }, 'Excluir');
    });
  });
});
</script>
