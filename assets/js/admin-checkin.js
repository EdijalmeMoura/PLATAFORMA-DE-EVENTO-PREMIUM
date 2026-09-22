/* Check-in — scanner QR + validação */
(function () {
  'use strict';
  var reader = null, scanning = false, lastCode = '', lastAt = 0;
  var resultBox = document.getElementById('result');
  var resultEmpty = document.getElementById('result-empty');

  function refreshStats() {
    api('/checkin-stats').then(function (j) {
      if (!j.ok) return;
      document.getElementById('st-total').textContent = j.stats.total;
      document.getElementById('st-present').textContent = j.stats.present;
      document.getElementById('st-pending').textContent = j.stats.pending;
    }).catch(function () {});
  }

  var audioCtx = null;
  function beep(ok) {
    try {
      if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      if (audioCtx.state === 'suspended') audioCtx.resume();
      var ctx = audioCtx;
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.frequency.value = ok ? 880 : 220;
      o.start();
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
      o.stop(ctx.currentTime + 0.4);
    } catch (e) { /* sem áudio */ }
  }

  function fmtDT(s) {
    var m = /^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/.exec(s || '');
    return m ? m[3] + '/' + m[2] + '/' + m[1] + ' ' + m[4] + ':' + m[5] : (s || '');
  }

  function show(state, reg) {
    resultEmpty.style.display = 'none';
    resultBox.style.display = 'block';
    var cls = state === 'VALID' ? 'valid' : (state === 'ALREADY_USED' ? 'used' : 'invalid');
    resultBox.className = 'check-result ' + cls;
    var titles = {
      VALID: '✓ CHECK-IN REALIZADO',
      ALREADY_USED: '⚠ JÁ UTILIZADO',
      CANCELLED: '✕ INSCRIÇÃO CANCELADA',
      NOT_FOUND: '✕ CÓDIGO NÃO ENCONTRADO',
      OFFLINE: '⚠ SEM CONEXÃO — TENTE NOVAMENTE',
    };
    var html = '<div class="big">' + (titles[state] || state) + '</div>';
    if (reg) {
      html += '<div style="font-size:18px;font-weight:700;">' + esc(reg.name) + '</div>' +
        '<div style="color:var(--ad-muted);font-size:13.5px;margin-top:4px;">' + esc(reg.code) + ' · ' + esc(reg.ticket || '') + '</div>' +
        (reg.company ? '<div style="color:var(--ad-muted);font-size:13px;">' + esc(reg.company) + '</div>' : '') +
        (reg.checked_in_at ? '<div style="color:var(--ad-muted);font-size:12.5px;margin-top:6px;">Check-in anterior: ' + esc(fmtDT(reg.checked_in_at)) + '</div>' : '');
    }
    resultBox.innerHTML = html;
    beep(state === 'VALID');
  }

  function validate(code, method) {
    if (!code) return;
    apiPost('/checkin-confirm', { code: code, method: method || 'manual' }).then(function (j) {
      show(j.state || 'NOT_FOUND', j.registration);
      refreshStats();
    }).catch(function (e) {
      if (e && e.message === 'auth') return; // sessão expirada: api() já redireciona
      show('OFFLINE', null);
    });
  }

  // Scanner
  var btnStart = document.getElementById('btn-start');
  var btnStop = document.getElementById('btn-stop');
  function start() {
    if (scanning) return;
    if (typeof Html5Qrcode === 'undefined') {
      document.getElementById('qrReader').innerHTML = '<div class="empty">Leitor indisponível (sem conexão com CDN). Use a busca manual.</div>';
      return;
    }
    reader = new Html5Qrcode('qrReader');
    scanning = true;
    reader.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 240, height: 240 } },
      function (decoded) {
        var now = Date.now();
        if (decoded === lastCode && now - lastAt < 4000) return; // anti-duplicata
        lastCode = decoded; lastAt = now;
        validate(decoded, 'qr');
      },
      function () { /* frame sem QR — ignora */ }
    ).catch(function () {
      scanning = false;
      document.getElementById('qrReader').innerHTML = '<div class="empty">Não foi possível acessar a câmera.<br>Verifique a permissão do navegador ou use a busca manual.</div>';
    });
  }
  function stop() {
    if (reader && scanning) {
      scanning = false;
      reader.stop().then(function () {
        if (reader.clear) reader.clear();
      }).catch(function () {});
    }
  }
  if (btnStart) btnStart.addEventListener('click', start);
  if (btnStop) btnStop.addEventListener('click', stop);

  // Manual
  var manual = document.getElementById('manual-code');
  function goManual() { validate(manual.value.trim().toUpperCase(), 'manual'); manual.select(); }
  document.getElementById('btn-manual').addEventListener('click', goManual);
  manual.addEventListener('keydown', function (e) { if (e.key === 'Enter') goManual(); });

  // Auto-refresh dos contadores a cada 15s
  setInterval(refreshStats, 15000);
})();
