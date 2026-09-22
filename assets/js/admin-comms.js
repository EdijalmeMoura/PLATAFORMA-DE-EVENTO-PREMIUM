/* Comunicação — disparos, fila, logs, integrações */
(function () {
  'use strict';

  // Inserir variável no textarea
  function insertVar(targetId, v) {
    var ta = document.getElementById(targetId);
    if (!ta) return;
    var s = ta.selectionStart || ta.value.length;
    ta.value = ta.value.slice(0, s) + v + ta.value.slice(ta.selectionEnd || s);
    ta.focus();
  }
  document.querySelectorAll('.var-chip').forEach(function (chip) {
    chip.addEventListener('click', function () {
      insertVar(chip.dataset.target || 'b-body', chip.dataset.var);
    });
  });

  // Template → preenche disparo
  var bTpl = document.getElementById('b-template');
  if (bTpl) bTpl.addEventListener('change', function () {
    if (!bTpl.value) return;
    api('/comms/templates/get?id=' + bTpl.value).then(function (j) {
      if (j.ok && j.row) {
        document.getElementById('b-subject').value = j.row.subject || '';
        document.getElementById('b-body').value = j.row.body || '';
      }
    });
  });
  var bCh = document.getElementById('b-channel');
  if (bCh) bCh.addEventListener('change', function () {
    document.getElementById('b-subject-wrap').style.display = bCh.value === 'whatsapp' ? 'none' : 'block';
  });

  // Contagem de destinatários
  function segFilters() {
    return {
      ticket_type_id: document.getElementById('b-ticket').value,
      status: document.getElementById('b-status').value,
      city: document.getElementById('b-city').value,
      company: document.getElementById('b-company').value,
    };
  }
  var bCountBtn = document.getElementById('b-count-btn');
  if (bCountBtn) bCountBtn.addEventListener('click', function () {
    var f = segFilters();
    var qs = 'ticket_type_id=' + encodeURIComponent(f.ticket_type_id) + '&status=' + f.status +
      '&city=' + encodeURIComponent(f.city) + '&company=' + encodeURIComponent(f.company);
    api('/comms/bulk-count?' + qs).then(function (j) {
      if (j.ok) document.getElementById('b-count').textContent = j.count;
    });
  });

  // Preview
  var pvBtn = document.getElementById('b-preview-btn');
  if (pvBtn) pvBtn.addEventListener('click', function () {
    apiPost('/comms/preview', {
      subject: document.getElementById('b-subject').value,
      body: document.getElementById('b-body').value,
    }).then(function (j) {
      if (j.ok) {
        document.getElementById('b-preview').style.display = 'block';
        document.getElementById('b-pv-subject').textContent = j.subject;
        document.getElementById('b-pv-body').textContent = j.body;
      }
    });
  });

  // Disparo em massa (com confirmação)
  var sendBtn = document.getElementById('b-send-btn');
  if (sendBtn) sendBtn.addEventListener('click', function () {
    var body = document.getElementById('b-body').value.trim();
    var subject = document.getElementById('b-subject').value.trim();
    var channel = document.getElementById('b-channel').value;
    if (!body) { toast('Escreva a mensagem antes de disparar.', 'err'); return; }
    if (channel !== 'whatsapp' && !subject) { toast('Informe o assunto do e-mail.', 'err'); return; }
    var f = segFilters();
    var qs = 'ticket_type_id=' + encodeURIComponent(f.ticket_type_id) + '&status=' + f.status +
      '&city=' + encodeURIComponent(f.city) + '&company=' + encodeURIComponent(f.company);
    api('/comms/bulk-count?' + qs).then(function (j) {
      if (!j.ok) return;
      if (j.count === 0) { toast('Nenhum destinatário para os filtros.', 'err'); return; }
      confirmDlg(
        'Confirmar disparo?',
        'Você está prestes a enviar esta mensagem para ' + j.count + ' participante(s) via ' +
          (channel === 'both' ? 'E-mail + WhatsApp' : channel === 'email' ? 'E-mail' : 'WhatsApp') + '.',
        function () {
          sendBtn.disabled = true;
          var payload = {
            confirmed: 1, channel: channel, subject: subject, body: body,
            ticket_type_id: f.ticket_type_id, status: f.status,
            city: f.city, company: f.company,
          };
          apiPost('/comms/bulk-send', payload).then(function (r) {
            sendBtn.disabled = false;
            if (r.ok) {
              toast('Disparo enfileirado para ' + r.recipients + ' participantes!', 'ok');
              document.getElementById('b-body').value = '';
              document.getElementById('b-subject').value = '';
            }
          }).catch(function () { sendBtn.disabled = false; });
        },
        'Disparar agora'
      );
    });
  });

  // ---- CRUD mensagens / templates ----
  function bindCrud(listSel, itemSel, kind, title) {
    document.querySelectorAll(itemSel + ' .msg-edit, ' + itemSel + ' .tpl-edit').forEach(function (b) { /* noop */ });
  }
  function openEditor(kind, title, id) {
    document.getElementById('mm-kind').value = kind;
    document.getElementById('mm-title').textContent = title;
    document.getElementById('mm-active-wrap').style.display = kind === 'automations' ? 'block' : 'none';
    if (!id) {
      document.getElementById('mm-id').value = '';
      document.getElementById('mm-name').value = '';
      document.getElementById('mm-subject').value = '';
      document.getElementById('mm-body').value = '';
      openModal('modalMsg');
      return;
    }
    api('/comms/' + kind + '/get?id=' + id).then(function (j) {
      if (!j.ok || !j.row) return;
      document.getElementById('mm-id').value = j.row.id;
      document.getElementById('mm-name').value = j.row.name || '';
      document.getElementById('mm-channel').value = j.row.channel || 'email';
      document.getElementById('mm-subject').value = j.row.subject || '';
      document.getElementById('mm-body').value = j.row.body || '';
      document.getElementById('mm-active').checked = +j.row.active === 1;
      openModal('modalMsg');
    });
  }
  function delItem(kind, id) {
    confirmDlg('Excluir?', 'Esta ação não pode ser desfeita.', function () {
      apiPost('/comms/' + kind + '/delete', { id: id }).then(function (j) {
        if (j.ok) { toast('Excluído.', 'ok'); setTimeout(function () { location.reload(); }, 600); }
      });
    }, 'Excluir');
  }
  var mn = document.getElementById('msg-new');
  if (mn) mn.addEventListener('click', function () { openEditor('messages', 'Nova mensagem'); });
  var tn = document.getElementById('tpl-new');
  if (tn) tn.addEventListener('click', function () { openEditor('templates', 'Novo template'); });
  document.querySelectorAll('#msg-list .msg-edit').forEach(function (b) {
    b.addEventListener('click', function () { openEditor('messages', 'Editar mensagem', b.closest('.list-item').dataset.id); });
  });
  document.querySelectorAll('#msg-list .msg-del').forEach(function (b) {
    b.addEventListener('click', function () { delItem('messages', b.closest('.list-item').dataset.id); });
  });
  document.querySelectorAll('#tpl-list .tpl-edit').forEach(function (b) {
    b.addEventListener('click', function () { openEditor('templates', 'Editar template', b.closest('.list-item').dataset.id); });
  });
  document.querySelectorAll('#tpl-list .tpl-del').forEach(function (b) {
    b.addEventListener('click', function () { delItem('templates', b.closest('.list-item').dataset.id); });
  });
  document.querySelectorAll('.auto-edit').forEach(function (b) {
    b.addEventListener('click', function () { openEditor('automations', 'Editar automação', b.closest('.list-item').dataset.id); });
  });
  document.querySelectorAll('.auto-toggle').forEach(function (t) {
    t.addEventListener('change', function () {
      apiPost('/comms/automations/toggle', { id: t.closest('.list-item').dataset.id }).then(function (j) {
        if (j.ok) toast(j.active ? 'Automação ativada!' : 'Automação desativada.', 'ok');
      });
    });
  });
  var mmSave = document.getElementById('mm-save');
  if (mmSave) mmSave.addEventListener('click', function () {
    apiPost('/comms/' + document.getElementById('mm-kind').value + '/save', {
      id: document.getElementById('mm-id').value,
      name: document.getElementById('mm-name').value,
      channel: document.getElementById('mm-channel').value,
      subject: document.getElementById('mm-subject').value,
      body: document.getElementById('mm-body').value,
      active: document.getElementById('mm-active').checked ? 1 : 0,
    }).then(function (j) {
      if (j.ok) { toast('Salvo!', 'ok'); closeModal('modalMsg'); setTimeout(function () { location.reload(); }, 600); }
    });
  });

  // ---- Fila ----
  var qp = 1, qPages = 1;
  function qLoad() {
    var st = document.getElementById('q-status').value;
    api('/comms/queue/list?page=' + qp + '&status=' + st).then(function (j) {
      if (!j.ok) return;
      qPages = j.pages;
      document.getElementById('q-info').textContent = 'Página ' + j.page + ' de ' + j.pages + ' · ' + j.total;
      var tb = document.getElementById('q-rows');
      tb.innerHTML = j.rows.length ? j.rows.map(function (r) {
        var acts = '';
        if (r.status === 'failed') acts += '<button class="btn-ad btn-ghost-ad btn-sm-ad q-retry" data-id="' + r.id + '">Repetir</button> ';
        if (r.status === 'queued' || r.status === 'processing') acts += '<button class="btn-ad btn-red-ad btn-sm-ad q-cancel" data-id="' + r.id + '">Cancelar</button>';
        return '<tr><td class="mono">#' + r.id + '</td><td>' + esc(r.reg_name || '—') + '<br><small class="mono">' + esc(r.to_address) + '</small>' +
          (r.error ? '<br><small style="color:var(--ad-red)">' + esc(r.error) + '</small>' : '') + '</td>' +
          '<td>' + (r.channel === 'email' ? 'E-mail' : 'WhatsApp') + '</td>' +
          '<td>' + esc(r.subject || '—') + '</td><td>' + statusBadge(r.status) + '</td>' +
          '<td>' + esc((r.created_at || '').slice(0, 16)) + '</td><td>' + acts + '</td></tr>';
      }).join('') : '<tr><td colspan="7"><div class="empty">Fila vazia.</div></td></tr>';
      tb.querySelectorAll('.q-retry').forEach(function (b) {
        b.addEventListener('click', function () {
          apiPost('/comms/queue/retry', { id: b.dataset.id }).then(function (r) { if (r.ok) { toast('Reenfileirado!', 'ok'); qLoad(); } });
        });
      });
      tb.querySelectorAll('.q-cancel').forEach(function (b) {
        b.addEventListener('click', function () {
          apiPost('/comms/queue/cancel', { id: b.dataset.id }).then(function (r) { if (r.ok) { toast('Cancelado.', 'ok'); qLoad(); } });
        });
      });
    });
  }
  var qs = document.getElementById('q-status');
  if (qs) {
    qs.addEventListener('change', function () { qp = 1; qLoad(); });
    document.getElementById('q-prev').addEventListener('click', function () { if (qp > 1) { qp--; qLoad(); } });
    document.getElementById('q-next').addEventListener('click', function () { if (qp < qPages) { qp++; qLoad(); } });
    qLoad();
  }
  var qProc = document.getElementById('q-process');
  if (qProc) qProc.addEventListener('click', function () {
    qProc.disabled = true;
    apiPost('/comms/queue/process', {}).then(function (j) {
      qProc.disabled = false;
      if (j.ok) { toast('Processados: ' + j.result.processed + ' · Enviados: ' + j.result.sent + ' · Falhas: ' + j.result.failed, 'ok'); qLoad(); }
    }).catch(function () { qProc.disabled = false; });
  });

  // ---- Logs ----
  var lp = 1, lPages = 1;
  function lLoad() {
    var ch = document.getElementById('l-channel').value;
    api('/comms/logs?page=' + lp + '&channel=' + ch).then(function (j) {
      if (!j.ok) return;
      lPages = j.pages;
      document.getElementById('l-info').textContent = 'Página ' + j.page + ' de ' + j.pages + ' · ' + j.total;
      document.getElementById('l-rows').innerHTML = j.rows.length ? j.rows.map(function (r) {
        return '<tr><td>' + esc((r.created_at || '').slice(0, 16)) + '</td><td>' + esc(r.reg_name || '—') + '</td>' +
          '<td>' + (r.channel === 'email' ? 'E-mail' : 'WhatsApp') + '</td><td class="mono">' + esc(r.to_address) + '</td>' +
          '<td>' + esc(r.subject || (r.body_excerpt || '').slice(0, 60)) + (r.opened_at ? ' <span class="badge b-green">aberto</span>' : '') +
          (r.error ? '<br><small style="color:var(--ad-red)">' + esc(r.error) + '</small>' : '') + '</td>' +
          '<td>' + statusBadge(r.status) + '</td></tr>';
      }).join('') : '<tr><td colspan="6"><div class="empty">Nenhum envio registrado.</div></td></tr>';
    });
  }
  var lc = document.getElementById('l-channel');
  if (lc) {
    lc.addEventListener('change', function () { lp = 1; lLoad(); });
    document.getElementById('l-prev').addEventListener('click', function () { if (lp > 1) { lp--; lLoad(); } });
    document.getElementById('l-next').addEventListener('click', function () { if (lp < lPages) { lp++; lLoad(); } });
    lLoad();
  }

  // ---- E-mail / WhatsApp ----
  var emSave = document.getElementById('em-save');
  if (emSave) emSave.addEventListener('click', function () {
    apiPost('/comms/email-save', {
      host: document.getElementById('em-host').value,
      port: document.getElementById('em-port').value,
      username: document.getElementById('em-user').value,
      password: document.getElementById('em-pass').value,
      encryption: document.getElementById('em-enc').value,
      from_email: document.getElementById('em-from').value,
      from_name: document.getElementById('em-fromname').value,
      active: document.getElementById('em-active').checked ? 1 : 0,
    }).then(function (j) { if (j.ok) { toast('SMTP salvo!', 'ok'); document.getElementById('em-pass').value = ''; } });
  });
  var emTest = document.getElementById('em-test');
  if (emTest) emTest.addEventListener('click', function () {
    emTest.disabled = true;
    apiPost('/comms/email-test', { to: document.getElementById('em-test-to').value }).then(function (j) {
      emTest.disabled = false;
      toast(j.ok ? 'E-mail de teste enviado!' : (j.error || 'Falha.'), j.ok ? 'ok' : 'err');
    }).catch(function () { emTest.disabled = false; });
  });
  var waSave = document.getElementById('wa-save');
  if (waSave) waSave.addEventListener('click', function () {
    apiPost('/comms/wa-save', {
      phone_number_id: document.getElementById('wa-pid').value,
      access_token: document.getElementById('wa-token').value,
      business_number: document.getElementById('wa-num').value,
      active: document.getElementById('wa-active').checked ? 1 : 0,
    }).then(function (j) { if (j.ok) { toast('WhatsApp salvo! Status: ' + j.status.label, 'ok'); document.getElementById('wa-token').value = ''; setTimeout(function () { location.reload(); }, 800); } });
  });
  var waTest = document.getElementById('wa-test');
  if (waTest) waTest.addEventListener('click', function () {
    waTest.disabled = true;
    apiPost('/comms/wa-test', { to: document.getElementById('wa-test-to').value }).then(function (j) {
      waTest.disabled = false;
      toast(j.ok ? 'Mensagem de teste enviada!' : (j.error || 'Falha.'), j.ok ? 'ok' : 'err');
    }).catch(function () { waTest.disabled = false; });
  });
})();
