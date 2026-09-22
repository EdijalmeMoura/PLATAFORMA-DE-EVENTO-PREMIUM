<?php defined('APP') or exit; use App\Core\Icons;
$extra_js = '<script src="' . e(url('assets/js/admin-crud.js')) . '?v=1.0.0" defer></script>';
?>
<div class="toolbar">
  <span style="color:var(--ad-muted);font-size:13.5px;">Prova social exibida na landing, antes dos ingressos.</span>
  <span class="spacer"></span>
  <button class="btn-ad btn-gold-ad btn-sm-ad" id="btn-new"><?= Icons::get('plus') ?> Novo depoimento</button>
</div>
<div id="list">
  <?php if (empty($items)): ?><div class="empty"><?= Icons::get('chat') ?><p>Nenhum depoimento cadastrado.</p></div><?php endif; ?>
  <?php foreach ($items as $it): ?>
  <div class="list-item" data-id="<?= (int) $it['id'] ?>">
    <?php if ($it['photo']): ?><img class="img-thumb round" src="<?= e(url($it['photo'])) ?>" alt="">
    <?php else: ?><span class="avatar" style="width:52px;height:52px;flex:none;"><?= e(mb_strtoupper(mb_substr($it['name'], 0, 1))) ?></span><?php endif; ?>
    <div class="grow"><strong><?= e($it['name']) ?></strong><span><?= e(trim(($it['role'] ?: '') . ' · ' . ($it['company'] ?: ''), ' ·')) ?> — “<?= e(mb_strimwidth($it['text'], 0, 90, '…')) ?>”</span></div>
    <button class="icon-btn" data-move="up" title="Subir">↑</button>
    <button class="icon-btn" data-move="down" title="Descer">↓</button>
    <button class="icon-btn act-edit" title="Editar"><?= Icons::get('edit') ?></button>
    <button class="icon-btn act-del" title="Excluir"><?= Icons::get('trash') ?></button>
  </div>
  <?php endforeach; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  bindReorder('list', 'testimonials');
  var FIELDS = [
    { key: 'name', label: 'Nome', type: 'text', required: 1, placeholder: 'Ex.: Ana Paula Ribeiro' },
    { key: 'role', label: 'Cargo', type: 'text', placeholder: 'Ex.: Diretora de Operações' },
    { key: 'company', label: 'Empresa', type: 'text', placeholder: 'Ex.: Metalúrgica Atlas' },
    { key: 'photo', label: 'Foto (opcional)', type: 'image' },
    { key: 'text', label: 'Depoimento', type: 'textarea', required: 1, placeholder: 'O que essa pessoa achou do evento…' },
  ];
  function save(data, done, fail) {
    apiPost('/content/testimonials/save', data).then(function (j) {
      if (j.ok) { toast('Salvo!', 'ok'); done(); setTimeout(function () { location.reload(); }, 500); }
      else fail();
    }).catch(fail);
  }
  document.getElementById('btn-new').addEventListener('click', function () {
    crudModal({ title: 'Novo depoimento', fields: FIELDS, folder: 'testimonials', onSave: save });
  });
  document.querySelectorAll('.act-edit').forEach(function (b) {
    b.addEventListener('click', function () {
      var id = b.closest('.list-item').dataset.id;
      api('/content/testimonials/get?id=' + id).then(function (j) {
        if (!j.ok || !j.row) return;
        j.row.id = id;
        crudModal({ title: 'Editar depoimento', fields: [{ key: 'id', label: 'ID', type: 'text' }].concat(FIELDS), folder: 'testimonials', onSave: save }, j.row);
        document.querySelector('#modalCrudDyn [data-k="id"]').closest('.field').style.display = 'none';
      });
    });
  });
  document.querySelectorAll('.act-del').forEach(function (b) {
    b.addEventListener('click', function () {
      var id = b.closest('.list-item').dataset.id;
      confirmDlg('Excluir depoimento?', 'Ele será removido da landing.', function () {
        apiPost('/content/testimonials/delete', { id: id }).then(function (j) {
          if (j.ok) { toast('Excluído.', 'ok'); setTimeout(function () { location.reload(); }, 500); }
        });
      }, 'Excluir');
    });
  });
});
</script>
