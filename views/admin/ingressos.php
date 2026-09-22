<?php defined('APP') or exit; use App\Core\Icons;
$extra_js = '<script src="' . e(url('assets/js/admin-crud.js')) . '?v=1.0.0" defer></script>';
?>
<div class="toolbar">
  <span style="color:var(--ad-muted);font-size:13.5px;">Tipos de ingresso, lotes e disponibilidade. Preço 0 = gratuito.</span>
  <span class="spacer"></span>
  <button class="btn-ad btn-gold-ad btn-sm-ad" id="btn-new"><?= Icons::get('plus') ?> Novo ingresso</button>
</div>
<div id="list">
  <?php foreach ($items as $it):
    $remaining = ((int) $it['quantity'] > 0) ? max(0, (int) $it['quantity'] - (int) $it['sold']) : null;
  ?>
  <div class="list-item" data-id="<?= (int) $it['id'] ?>">
    <div class="grow">
      <strong><?= e($it['name']) ?> · <?= money($it['price']) ?> <?= $it['status'] === 'ACTIVE' ? '<span class="badge b-green">Ativo</span>' : ($it['status'] === 'PAUSED' ? '<span class="badge b-amber">Pausado</span>' : '<span class="badge b-red">Esgotado</span>') ?></strong>
      <span><?= (int) $it['sold'] ?> vendidos<?= $remaining !== null ? ' · ' . $remaining . ' restantes de ' . (int) $it['quantity'] : ' · vagas ilimitadas' ?><?= $it['sale_end'] ? ' · até ' . e(fdate($it['sale_end'], true)) : '' ?></span>
    </div>
    <button class="icon-btn" data-move="up" title="Subir">↑</button>
    <button class="icon-btn" data-move="down" title="Descer">↓</button>
    <button class="icon-btn act-edit" title="Editar"><?= Icons::get('edit') ?></button>
    <button class="icon-btn act-del" title="Excluir"><?= Icons::get('trash') ?></button>
  </div>
  <?php endforeach; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  bindReorder('list', 'ticket_types');
  var FIELDS = [
    { key: 'name', label: 'Nome', type: 'text', required: 1, placeholder: 'Ex.: VIP' },
    { key: 'description', label: 'Descrição', type: 'text' },
    { key: 'price', label: 'Preço (R$ — 0 = gratuito)', type: 'money' },
    { key: 'quantity', label: 'Quantidade disponível (0 = ilimitado)', type: 'number', def: '100' },
    { key: 'benefits', label: 'Benefícios (um por linha)', type: 'textarea', placeholder: 'Acesso ao evento\nWelcome drink\n...' },
    { key: 'sale_start', label: 'Início das vendas (opcional)', type: 'datetime' },
    { key: 'sale_end', label: 'Fim das vendas (opcional)', type: 'datetime' },
    { key: 'status', label: 'Status', type: 'select', options: [['ACTIVE', 'Ativo'], ['PAUSED', 'Pausado'], ['SOLD_OUT', 'Esgotado']] },
  ];
  function save(data, done, fail) {
    apiPost('/content/tickets/save', data).then(function (j) {
      if (j.ok) { toast('Salvo!', 'ok'); done(); setTimeout(function () { location.reload(); }, 500); }
      else fail();
    }).catch(fail);
  }
  document.getElementById('btn-new').addEventListener('click', function () {
    crudModal({ title: 'Novo ingresso', fields: FIELDS, onSave: save });
  });
  document.querySelectorAll('.act-edit').forEach(function (b) {
    b.addEventListener('click', function () {
      var id = b.closest('.list-item').dataset.id;
      api('/content/tickets/get?id=' + id).then(function (j) {
        if (!j.ok || !j.row) return;
        j.row.id = id;
        crudModal({ title: 'Editar ingresso', fields: [{ key: 'id', label: 'ID', type: 'text' }].concat(FIELDS), onSave: save }, j.row);
        document.querySelector('#modalCrudDyn [data-k="id"]').closest('.field').style.display = 'none';
      });
    });
  });
  document.querySelectorAll('.act-del').forEach(function (b) {
    b.addEventListener('click', function () {
      var id = b.closest('.list-item').dataset.id;
      confirmDlg('Excluir ingresso?', 'Só é possível se não houver inscrições vinculadas.', function () {
        apiPost('/content/tickets/delete', { id: id }).then(function (j) {
          if (j.ok) { toast('Excluído.', 'ok'); setTimeout(function () { location.reload(); }, 500); }
        });
      }, 'Excluir');
    });
  });
});
</script>
