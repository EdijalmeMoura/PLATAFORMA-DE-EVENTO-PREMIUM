/* Ticket digital — QR Code, impressão, reenvio */
(function () {
  'use strict';

  // QR Code (qrcodejs via CDN, com fallback para o código textual)
  var box = document.getElementById('qrBox');
  if (box) {
    var payload = box.dataset.payload;
    function renderQR() {
      if (typeof QRCode !== 'undefined') {
        box.innerHTML = '';
        new QRCode(box, { text: payload, width: 180, height: 180, correctLevel: QRCode.CorrectLevel.M });
      }
    }
    if (typeof QRCode !== 'undefined') renderQR();
    else {
      var tries = 0;
      var h = setInterval(function () {
        tries++;
        if (typeof QRCode !== 'undefined') { renderQR(); clearInterval(h); }
        if (tries > 40) clearInterval(h);
      }, 250);
    }
  }

  // Imprimir / salvar PDF
  var btnPrint = document.getElementById('btnPrint');
  if (btnPrint) btnPrint.addEventListener('click', function () { window.print(); });

  // Baixar QR como PNG
  var btnPng = document.getElementById('btnQrPng');
  if (btnPng) btnPng.addEventListener('click', function () {
    var img = box ? box.querySelector('img') : null;
    var canvas = box ? box.querySelector('canvas') : null;
    var src = img ? img.src : (canvas ? canvas.toDataURL('image/png') : null);
    if (!src) { toast('QR Code ainda carregando. Tente em instantes.'); return; }
    var a = document.createElement('a');
    a.href = src;
    a.download = 'qrcode-' + window.TICKET.code + '.png';
    document.body.appendChild(a);
    a.click();
    a.remove();
  });

  // Reenviar por e-mail
  var btnEmail = document.getElementById('btnEmail');
  if (btnEmail) btnEmail.addEventListener('click', function () {
    btnEmail.disabled = true;
    var fd = new FormData();
    fd.append('csrf_token', window.TICKET.csrf);
    fd.append('code', window.TICKET.code);
    fd.append('channel', 'email');
    fetch(window.TICKET.resendUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (j) { toast(j.ok ? 'E-mail enviado com sucesso!' : (j.error || 'Falha ao enviar.')); btnEmail.disabled = false; })
      .catch(function () { toast('Falha de conexão.'); btnEmail.disabled = false; });
  });
})();
