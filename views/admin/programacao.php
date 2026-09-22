<?php defined('APP') or exit; use App\Core\Icons;
$extra_js = '<script src="' . e(url('assets/js/admin-crud.js')) . '?v=1.0.0" defer></script>';
?>
<div class="toolbar">
  <span style="color:var(--ad-muted);font-size:13.5px;">Timeline exibida na landing. Arraste a ordem com ↑ ↓.</span>
  <span class="spacer"></span>
  <button class="btn-ad btn-gold-ad btn-sm-ad" id="btn-new"><?= Icons::get('plus') ?> Novo item</button>
</div>
<div id="list">
  <?php if (empty($items)): ?><div class="empty"><?= Icons::get('list') ?><p>Nenhum item na programação.</p></div><?php endif; ?>
  <?php foreach ($items as $it): ?>
  <div class="list-item" data-id="<?= (int) $it['id'] ?>">
    <span style="font-family:ui-monospace,monospace;color:var(--ad-gold-l);font-weight:700;min-width:52px;"><?= e($it['stime']) ?></span>
    <div class="grow"><strong><?= e($it['title']) ?></strong><span><?= e(excerpt($it['description'] ?? '', 90)) ?><?= $it['speaker'] ? ' · 🎤 ' . e($it['speaker']) : '' ?><?= $it['slocation'] ? ' · 📍 ' . e($it['slocation']) : '' ?></span></div>
    <button class="icon-btn" data-move="up" title="Subir">↑</button>
    <button class="icon-btn" data-move="down" title="Descer">↓</button>
    <button class="icon-btn act-edit" title="Editar"><?= Icons::get('edit') ?></button>
    <button class="icon-btn act-del" title="Excluir"><?= Icons::get('trash') ?></button>
  </div>
  <?php endforeach; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  bindReorder('list', 'schedule_items');
  var FIELDS = [
    { key: 'stime', label: 'Horário', type: 'time', required: 1 },
    { key: 'title', label: 'Título', type: 'text', required: 1, placeholder: 'Ex.: Abertura oficial' },
    { key: 'description', label: 'Descrição', type: 'textarea' },
    { key: 'speaker', label: 'Palestrante (opcional)', type: 'text' },
    { key: 'slocation', label: 'Local (opcional)', type: 'text', placeholder: 'Ex.: Palco principal' },
    { key: 'image', label: 'Imagem (opcional)', type: 'image' },
  ];
  function save(data, done, fail) {
    apiPost('/content/schedule/save', data).then(function (j) {
      if (j.ok) { toast('Salvo!', 'ok'); done(); setTimeout(function () { location.reload(); }, 500); }
      else fail();
    }).catch(fail);
  }
  document.getElementById('btn-new').addEventListener('click', function () {
    crudModal({ title: 'Novo item', fields: FIELDS, folder: 'schedule', onSave: save });
  });
  document.querySelectorAll('.act-edit').forEach(function (b) {
    b.addEventListener('click', function () {
      var id = b.closest('.list-item').dataset.id;
      api('/content/schedule/get?id=' + id).then(function (j) {
        if (!j.ok || !j.row) return;
        j.row.id = id;
        crudModal({ title: 'Editar item', fields: [{ key: 'id', label: 'ID', type: 'text' }].concat(FIELDS), folder: 'schedule', onSave: save }, j.row);
        document.querySelector('#modalCrudDyn [data-k="id"]').closest('.field').style.display = 'none';
      });
    });
  });
  document.querySelectorAll('.act-del').forEach(function (b) {
    b.addEventListener('click', function () {
      var id = b.closest('.list-item').dataset.id;
      confirmDlg('Excluir item?', 'O item será removido da programação.', function () {
        apiPost('/content/schedule/delete', { id: id }).then(function (j) {
          if (j.ok) { toast('Excluído.', 'ok'); setTimeout(function () { location.reload(); }, 500); }
        });
      }, 'Excluir');
    });
  });
});
</script>
