<?php defined('APP') or exit; use App\Core\Icons;
$extra_js = '<script src="' . e(url('assets/js/admin-crud.js')) . '?v=1.0.0" defer></script>';
$secNames = ['personal' => 'Dados pessoais', 'address' => 'Endereço', 'extra' => 'Perguntas adicionais'];
$typeNames = ['text' => 'Texto', 'email' => 'E-mail', 'tel' => 'Telefone', 'number' => 'Número', 'date' => 'Data', 'select' => 'Lista (select)', 'radio' => 'Múltipla escolha (radio)', 'checkbox' => 'Caixas (checkbox)', 'textarea' => 'Texto longo'];
$lastSec = '';
?>
<div class="toolbar">
  <span style="color:var(--ad-muted);font-size:13.5px;">Campos padrão podem ser ativados/desativados e marcados como obrigatórios. Campos personalizados entram em “Perguntas adicionais”.</span>
  <span class="spacer"></span>
  <button class="btn-ad btn-gold-ad btn-sm-ad" id="btn-new"><?= Icons::get('plus') ?> Novo campo</button>
</div>
<div id="list">
  <?php foreach ($items as $it): ?>
    <?php if ($it['section'] !== $lastSec): $lastSec = $it['section']; ?>
      <h3 style="margin:18px 0 10px;font-size:13px;letter-spacing:2px;color:var(--ad-gold-l);"><?= e($secNames[$lastSec] ?? $lastSec) ?></h3>
    <?php endif; ?>
  <div class="list-item" data-id="<?= (int) $it['id'] ?>">
    <label class="toggle" title="Ativar/desativar"><input type="checkbox" class="f-toggle" <?= $it['active'] ? 'checked' : '' ?>><span></span></label>
    <div class="grow">
      <strong><?= e($it['label']) ?> <?= $it['required'] ? '<span class="badge b-gold">obrigatório</span>' : '' ?> <?= $it['active'] ? '' : '<span class="badge b-gray">desativado</span>' ?></strong>
      <span><?= e($typeNames[$it['ftype']] ?? $it['ftype']) ?><?= $it['map_column'] ? ' · campo padrão' : ' · personalizado' ?><?= $it['options'] ? ' · opções: ' . e(str_replace('|', ', ', $it['options'])) : '' ?></span>
    </div>
    <button class="icon-btn act-edit" title="Editar"><?= Icons::get('edit') ?></button>
    <?php if (!$it['map_column']): ?><button class="icon-btn act-del" title="Excluir"><?= Icons::get('trash') ?></button><?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var FIELDS = [
    { key: 'label', label: 'Rótulo (pergunta)', type: 'text', required: 1 },
    { key: 'ftype', label: 'Tipo', type: 'select', options: [['text', 'Texto'], ['textarea', 'Texto longo'], ['email', 'E-mail'], ['tel', 'Telefone'], ['number', 'Número'], ['date', 'Data'], ['select', 'Lista (select)'], ['radio', 'Múltipla escolha (radio)'], ['checkbox', 'Caixas (checkbox)']] },
    { key: 'options', label: 'Opções (separadas por | )', type: 'text', placeholder: 'Instagram|Facebook|Google|Outro', help: 'Somente para select, radio e checkbox.' },
    { key: 'placeholder', label: 'Texto de ajuda (placeholder)', type: 'text' },
    { key: 'help', label: 'Ajuda abaixo do campo', type: 'text' },
    { key: 'section', label: 'Seção', type: 'select', options: [['extra', 'Perguntas adicionais'], ['personal', 'Dados pessoais'], ['address', 'Endereço']] },
    { key: 'required', label: 'Obrigatório', type: 'check', hint: 'Resposta obrigatória' },
    { key: 'active', label: 'Ativo', type: 'check', hint: 'Exibir no formulário', def: 1 },
  ];
  function save(data, done, fail) {
    apiPost('/content/fields/save', data).then(function (j) {
      if (j.ok) { toast('Salvo!', 'ok'); done(); setTimeout(function () { location.reload(); }, 500); }
      else fail();
    }).catch(fail);
  }
  document.getElementById('btn-new').addEventListener('click', function () {
    crudModal({ title: 'Novo campo personalizado', fields: FIELDS, onSave: save });
  });
  // Edição dos existentes (valores atuais embutidos no item)
  document.querySelectorAll('.act-edit').forEach(function (b) {
    b.addEventListener('click', function () {
      var item = b.closest('.list-item');
      var vals = JSON.parse(item.dataset.val || '{}');
      vals.id = item.dataset.id;
      crudModal({ title: 'Editar campo', fields: [{ key: 'id', label: 'ID', type: 'text' }].concat(FIELDS), onSave: save }, vals);
      document.querySelector('#modalCrudDyn [data-k="id"]').closest('.field').style.display = 'none';
    });
  });
  document.querySelectorAll('.f-toggle').forEach(function (t) {
    t.addEventListener('change', function () {
      apiPost('/content/fields/toggle', { id: t.closest('.list-item').dataset.id }).then(function (j) {
        if (j.ok) { toast(j.active ? 'Campo ativado!' : 'Campo desativado.', 'ok'); setTimeout(function () { location.reload(); }, 500); }
      });
    });
  });
  document.querySelectorAll('.act-del').forEach(function (b) {
    b.addEventListener('click', function () {
      var id = b.closest('.list-item').dataset.id;
      confirmDlg('Excluir campo?', 'As respostas vinculadas também serão apagadas.', function () {
        apiPost('/content/fields/delete', { id: id }).then(function (j) {
          if (j.ok) { toast('Excluído.', 'ok'); setTimeout(function () { location.reload(); }, 500); }
        });
      }, 'Excluir');
    });
  });
});
</script>
