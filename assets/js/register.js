/* Formulário de inscrição — máscaras, validação e envio */
(function () {
  'use strict';
  var form = document.getElementById('regForm');
  if (!form) return;

  // Seleção visual de ingresso
  form.querySelectorAll('.ticket-opt input').forEach(function (r) {
    r.addEventListener('change', function () {
      form.querySelectorAll('.ticket-opt').forEach(function (o) { o.classList.remove('selected'); });
      r.closest('.ticket-opt').classList.add('selected');
    });
  });

  // Pills radio/checkbox
  form.querySelectorAll('.radio-group input, .check-group input').forEach(function (i) {
    i.addEventListener('change', function () {
      if (i.type === 'radio') {
        var g = i.closest('.radio-group');
        g.querySelectorAll('.radio-pill').forEach(function (p) { p.classList.remove('selected'); });
      }
      i.closest('label').classList.toggle('selected', i.checked);
    });
  });

  // Máscaras BR
  function maskCPF(v) {
    v = v.replace(/\D/g, '').slice(0, 11);
    if (v.length > 9) return v.slice(0, 3) + '.' + v.slice(3, 6) + '.' + v.slice(6, 9) + '-' + v.slice(9);
    if (v.length > 6) return v.slice(0, 3) + '.' + v.slice(3, 6) + '.' + v.slice(6);
    if (v.length > 3) return v.slice(0, 3) + '.' + v.slice(3);
    return v;
  }
  function maskPhone(v) {
    v = v.replace(/\D/g, '').slice(0, 11);
    if (v.length > 10) return '(' + v.slice(0, 2) + ') ' + v.slice(2, 7) + '-' + v.slice(7);
    if (v.length > 6) return '(' + v.slice(0, 2) + ') ' + v.slice(2, 6) + '-' + v.slice(6);
    if (v.length > 2) return '(' + v.slice(0, 2) + ') ' + v.slice(2);
    return v;
  }
  function maskCEP(v) {
    v = v.replace(/\D/g, '').slice(0, 8);
    if (v.length > 5) return v.slice(0, 5) + '-' + v.slice(5);
    return v;
  }
  form.querySelectorAll('[data-mask]').forEach(function (inp) {
    inp.addEventListener('input', function () {
      var k = inp.dataset.mask;
      inp.value = k === 'cpf' ? maskCPF(inp.value) : k === 'phone' ? maskPhone(inp.value) : maskCEP(inp.value);
    });
  });

  // CEP automático (ViaCEP): preenche rua, bairro, cidade e UF
  (function () {
    var cep = document.getElementById('cep');
    if (!cep) return;
    var lastQuery = '';
    function byId(id) { return document.getElementById(id); }
    function setCepMsg(msg) {
      var wrap = cep.closest('.field') || form.querySelector('[data-field="cep"]');
      if (!wrap) return;
      wrap.classList.toggle('invalid', !!msg);
      var m = wrap.querySelector('.err-msg');
      if (m) m.textContent = msg || '';
    }
    function fill(data) {
      var map = { street: data.logradouro || '', district: data.bairro || '', addr_city: data.localidade || '', addr_state: data.uf || '' };
      Object.keys(map).forEach(function (id) {
        var el = byId(id);
        if (el && map[id]) el.value = map[id];
      });
      // Também preenche cidade/UF pessoais se existirem e estiverem vazios
      var pc = byId('city'), ps = byId('state');
      if (pc && !pc.value && data.localidade) pc.value = data.localidade;
      if (ps && !ps.value && data.uf) ps.value = data.uf;
      var num = byId('number');
      if (num) num.focus();
    }
    function loading(on) {
      ['street', 'district', 'addr_city', 'addr_state'].forEach(function (id) {
        var el = byId(id);
        if (!el) return;
        if (on) { el.dataset.ph = el.placeholder; el.placeholder = 'Buscando…'; el.readOnly = true; }
        else { if (el.dataset.ph !== undefined) el.placeholder = el.dataset.ph; el.readOnly = false; }
      });
    }
    function lookup() {
      var digits = cep.value.replace(/\D/g, '');
      setCepMsg('');
      if (digits.length !== 8 || digits === lastQuery) return;
      lastQuery = digits;
      loading(true);
      fetch('https://viacep.com.br/ws/' + digits + '/json/')
        .then(function (r) { return r.json(); })
        .then(function (data) {
          loading(false);
          if (!data || data.erro) {
            setCepMsg('CEP não encontrado. Confira o número ou preencha o endereço manualmente.');
            return;
          }
          fill(data);
        })
        .catch(function () {
          loading(false);
          lastQuery = '';
          setCepMsg('Não foi possível buscar o CEP. Preencha o endereço manualmente.');
        });
    }
    cep.addEventListener('blur', lookup);
    cep.addEventListener('input', function () {
      if (cep.value.replace(/\D/g, '').length === 8) lookup();
    });
  })();

  function showErrors(errors) {
    form.querySelectorAll('.field').forEach(function (f) {
      f.classList.remove('invalid');
      var m = f.querySelector('.err-msg');
      if (m) m.textContent = '';
    });
    var box = document.getElementById('formError');
    var msgs = [];
    Object.keys(errors).forEach(function (k) {
      var msg = errors[k];
      if (k === '_global' || k.indexOf('consent') === 0 || k === 'ticket_type_id') {
        msgs.push(msg);
        return;
      }
      var f = form.querySelector('[data-field="' + k + '"]');
      if (f) {
        f.classList.add('invalid');
        var m = f.querySelector('.err-msg');
        if (m) m.textContent = msg;
      } else {
        msgs.push(msg);
      }
    });
    if (msgs.length) {
      box.innerHTML = '';
      msgs.forEach(function (m) {
        var d = document.createElement('div');
        d.textContent = '• ' + m;
        box.appendChild(d);
      });
      box.classList.add('show');
      box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
      box.classList.remove('show');
      var first = form.querySelector('.field.invalid');
      if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    var btn = document.getElementById('submitBtn');
    var original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> PROCESSANDO…';
    var fd = new FormData(form);
    fetch(window.REG_CONFIG.endpoint, {
      method: 'POST',
      body: fd,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then(function (r) { return r.json().then(function (j) { return { s: r.status, j: j }; }); })
      .then(function (res) {
        if (res.j.ok) {
          btn.innerHTML = '<span class="spinner"></span> REDIRECIONANDO…';
          window.location.href = res.j.redirect;
        } else {
          showErrors(res.j.errors || { _global: 'Verifique os dados e tente novamente.' });
          btn.disabled = false;
          btn.innerHTML = original;
        }
      })
      .catch(function () {
        showErrors({ _global: 'Falha de conexão. Verifique sua internet e tente novamente.' });
        btn.disabled = false;
        btn.innerHTML = original;
      });
  });
})();
