/* CRUD genérico — modal dinâmico, upload e reordenação */
(function () {
  'use strict';

  // Upload de imagem
  window.uploadImage = function (file, folder) {
    var fd = new FormData();
    fd.append('file', file);
    fd.append('folder', folder || 'general');
    return fetch(window.ADMIN.api + '/content/upload', {
      method: 'POST',
      headers: { 'X-CSRF-Token': window.ADMIN.csrf, 'X-Requested-With': 'XMLHttpRequest' },
      body: fd,
    }).then(function (r) { return r.json(); });
  };

  // Modal dinâmico. fields: [{key,label,type,options,placeholder,help,required}]
  // types: text,number,money,textarea,time,datetime,select,image,check
  window.crudModal = function (opts, values) {
    values = values || {};
    var title = opts.title || 'Editar';
    var fields = opts.fields || [];
    var modalId = 'modalCrudDyn';
    var old = document.getElementById(modalId);
    if (old) old.remove();
    var html = '<div class="modal open" id="' + modalId + '"><div class="modal-card"><div class="modal-head"><h3>' + esc(title) + '</h3>' +
      '<button class="icon-btn" data-dclose>✕</button></div><div class="modal-body">';
    fields.forEach(function (f) {
      var v = values[f.key] !== undefined && values[f.key] !== null ? values[f.key] : (f.def || '');
      html += '<div class="field"><label>' + esc(f.label) + (f.required ? ' *' : '') + '</label>';
      if (f.type === 'textarea') {
        html += '<textarea data-k="' + f.key + '" placeholder="' + esc(f.placeholder || '') + '" style="min-height:' + (f.rows || 90) + 'px;">' + esc(v) + '</textarea>';
      } else if (f.type === 'select') {
        html += '<select data-k="' + f.key + '">';
        (f.options || []).forEach(function (o) {
          html += '<option value="' + esc(o[0]) + '"' + (String(v) === String(o[0]) ? ' selected' : '') + '>' + esc(o[1]) + '</option>';
        });
        html += '</select>';
      } else if (f.type === 'check') {
        html += '<div style="display:flex;gap:10px;align-items:center;"><label class="toggle"><input type="checkbox" data-k="' + f.key + '"' + (+v === 1 || v === true ? ' checked' : '') + '><span></span></label><span style="font-size:13px;color:var(--ad-muted);">' + esc(f.hint || 'Ativo') + '</span></div>';
      } else if (f.type === 'image') {
        html += (v ? '<img src="' + esc(window.ADMIN.base + v) + '" style="width:100%;max-height:160px;object-fit:cover;border-radius:10px;margin-bottom:10px;border:1px solid var(--ad-line);">' : '') +
          '<input type="file" data-k="' + f.key + '" accept="image/*"><input type="hidden" data-k="' + f.key + '_current" value="' + esc(v) + '">' +
          '<div class="help">JPG, PNG, WEBP ou SVG · máx. 5MB</div>';
      } else {
        var t = f.type === 'money' || f.type === 'number' ? 'number' : (f.type === 'time' ? 'time' : (f.type === 'datetime' ? 'datetime-local' : 'text'));
        var step = f.type === 'money' ? ' step="0.01" min="0"' : '';
        if (f.type === 'datetime' && v) v = String(v).slice(0, 16);
        html += '<input type="' + t + '"' + step + ' data-k="' + f.key + '" value="' + esc(v) + '" placeholder="' + esc(f.placeholder || '') + '">';
      }
      if (f.help) html += '<div class="help">' + esc(f.help) + '</div>';
      html += '</div>';
    });
    html += '</div><div class="modal-foot"><button class="btn-ad btn-ghost-ad" data-dclose>Cancelar</button>' +
      '<button class="btn-ad btn-gold-ad" data-dsave>Salvar</button></div></div></div>';
    document.body.insertAdjacentHTML('beforeend', html);
    var modal = document.getElementById(modalId);
    modal.querySelectorAll('[data-dclose]').forEach(function (b) {
      b.addEventListener('click', function () { modal.remove(); });
    });
    modal.addEventListener('click', function (ev) { if (ev.target === modal) modal.remove(); });
    modal.querySelector('[data-dsave]').addEventListener('click', function () {
      var btn = this;
      btn.disabled = true;
      var data = {};
      var uploads = [];
      modal.querySelectorAll('[data-k]').forEach(function (inp) {
        var k = inp.dataset.k;
        if (inp.type === 'file') {
          if (inp.files && inp.files[0]) uploads.push({ key: k, file: inp.files[0] });
          return;
        }
        if (k.endsWith('_current')) return;
        data[k] = inp.type === 'checkbox' ? (inp.checked ? 1 : 0) : inp.value;
      });
      // Validação simples
      var valid = true;
      fields.forEach(function (f) {
        if (f.required && !(data[f.key] || '').toString().trim()) { valid = false; }
      });
      if (!valid) { toast('Preencha os campos obrigatórios.', 'err'); btn.disabled = false; return; }
      function finish() {
        opts.onSave(data, function done() { modal.remove(); btn.disabled = false; }, function fail() { btn.disabled = false; });
      }
      if (!uploads.length) { finish(); return; }
      var chain = Promise.resolve();
      uploads.forEach(function (u) {
        chain = chain.then(function () {
          return uploadImage(u.file, opts.folder || 'general').then(function (j) {
            if (j.ok) data[u.key] = j.path;
            else { toast(j.error || 'Falha no upload.', 'err'); throw new Error('upload'); }
          });
        });
      });
      chain.then(finish).catch(function () { btn.disabled = false; });
    });
  };

  // Reordenação com botões ↑↓
  window.bindReorder = function (listId, table) {
    var list = document.getElementById(listId);
    if (!list) return;
    list.addEventListener('click', function (ev) {
      var btn = ev.target.closest('[data-move]');
      if (!btn) return;
      var item = btn.closest('.list-item');
      var sib = btn.dataset.move === 'up' ? item.previousElementSibling : item.nextElementSibling;
      if (!sib) return;
      if (btn.dataset.move === 'up') list.insertBefore(item, sib);
      else list.insertBefore(sib, item);
      var ids = Array.prototype.map.call(list.querySelectorAll('.list-item'), function (el) { return el.dataset.id; });
      apiPost('/content/reorder', { table: table, ids: ids }).then(function (j) {
        if (j.ok) toast('Ordem atualizada!', 'ok');
      });
    });
  };
})();
