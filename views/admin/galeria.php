<?php defined('APP') or exit; use App\Core\Icons;
$extra_js = '<script src="' . e(url('assets/js/admin-crud.js')) . '?v=1.0.0" defer></script>';
?>
<div class="toolbar">
  <span style="color:var(--ad-muted);font-size:13.5px;">Fotos exibidas na galeria da landing. Arraste a ordem com ↑↓.</span>
  <span class="spacer"></span>
  <button class="btn-ad btn-gold-ad btn-sm-ad" id="btn-new"><?= Icons::get('plus') ?> Nova foto</button>
</div>
<div id="list">
  <?php if (empty($items)): ?><div class="empty"><?= Icons::get('image') ?><p>Nenhuma foto na galeria.</p></div><?php endif; ?>
  <?php foreach ($items as $it): ?>
  <div class="list-item" data-id="<?= (int) $it['id'] ?>">
    <?php if ($it['image']): ?><img class="img-thumb" style="border-radius:10px;" src="<?= e(url($it['image'])) ?>" alt="">
    <?php else: ?><span class="avatar" style="width:52px;height:52px;flex:none;">?</span><?php endif; ?>
    <div class="grow"><strong><?= e($it['caption'] ?: 'Sem legenda') ?></strong><span>Foto da galeria</span></div>
    <button class="icon-btn" data-move="up" title="Subir">↑</button>
    <button class="icon-btn" data-move="down" title="Descer">↓</button>
    <button class="icon-btn act-edit" title="Editar"><?= Icons::get('edit') ?></button>
    <button class="icon-btn act-del" title="Excluir"><?= Icons::get('trash') ?></button>
  </div>
  <?php endforeach; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  bindReorder('list', 'event_gallery');
  var FIELDS = [
    { key: 'image', label: 'Foto', type: 'image' },
    { key: 'caption', label: 'Legenda (opcional)', type: 'text', placeholder: 'Ex.: Palco principal — 2025' },
  ];
  function save(data, done, fail) {
    apiPost('/content/gallery/save', data).then(function (j) {
      if (j.ok) { toast('Salvo!', 'ok'); done(); setTimeout(function () { location.reload(); }, 500); }
      else fail();
    }).catch(fail);
  }
  document.getElementById('btn-new').addEventListener('click', function () {
    crudModal({ title: 'Nova foto', fields: FIELDS, folder: 'gallery', onSave: save });
  });
  document.querySelectorAll('.act-edit').forEach(function (b) {
    b.addEventListener('click', function () {
      var id = b.closest('.list-item').dataset.id;
      api('/content/gallery/get?id=' + id).then(function (j) {
        if (!j.ok || !j.row) return;
        j.row.id = id;
        crudModal({ title: 'Editar foto', fields: [{ key: 'id', label: 'ID', type: 'text' }].concat(FIELDS), folder: 'gallery', onSave: save }, j.row);
        document.querySelector('#modalCrudDyn [data-k="id"]').closest('.field').style.display = 'none';
      });
    });
  });
  document.querySelectorAll('.act-del').forEach(function (b) {
    b.addEventListener('click', function () {
      var id = b.closest('.list-item').dataset.id;
      confirmDlg('Excluir foto?', 'Ela será removida da landing.', function () {
        apiPost('/content/gallery/delete', { id: id }).then(function (j) {
          if (j.ok) { toast('Excluído.', 'ok'); setTimeout(function () { location.reload(); }, 500); }
        });
      }, 'Excluir');
    });
  });
});
</script>
