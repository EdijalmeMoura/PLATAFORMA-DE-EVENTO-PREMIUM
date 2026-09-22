/* Dashboard — gráficos (Chart.js com fallback em tabela) */
(function () {
  'use strict';

  function fallbackTable(canvasId, rows) {
    var c = document.getElementById(canvasId);
    if (!c) return;
    var html = rows.map(function (r) {
      return '<div class="report-bar"><span class="lbl">' + esc(r[0]) + '</span>' +
        '<span class="track"><span class="fill" style="width:' + r[2] + '%;"></span></span>' +
        '<span class="val">' + esc(String(r[1])) + '</span></div>';
    }).join('');
    c.parentElement.innerHTML = html || '<div class="empty">Sem dados.</div>';
  }

  api('/dashboard-charts').then(function (j) {
    if (!j.ok) return;
    var d = j.data;
    var hasChart = typeof Chart !== 'undefined';
    if (hasChart) {
      Chart.defaults.color = '#9a9a9a';
      Chart.defaults.borderColor = 'rgba(255,255,255,.07)';
    }

    // Por dia
    var dayLabels = d.per_day.map(function (r) {
      var p = r.d.split('-');
      return p[2] + '/' + p[1];
    });
    var dayVals = d.per_day.map(function (r) { return +r.c; });
    if (!hasChart) {
      var mx = Math.max.apply(null, dayVals.concat([1]));
      fallbackTable('ch-day', d.per_day.map(function (r) { return [r.d, r.c, Math.round(r.c / mx * 100)]; }));
    } else {
      new Chart(document.getElementById('ch-day'), {
        type: 'line',
        data: { labels: dayLabels, datasets: [{ data: dayVals, borderColor: '#C9A227', backgroundColor: 'rgba(201,162,39,.12)', fill: true, tension: 0.4, pointRadius: 2, pointBackgroundColor: '#E7C766' }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, maintainAspectRatio: false },
      });
    }

    // Por hora
    var hours = new Array(24).fill(0);
    d.per_hour.forEach(function (r) { hours[+r.h] = +r.c; });
    if (!hasChart) {
      var mh = Math.max.apply(null, hours.concat([1]));
      fallbackTable('ch-hour', hours.map(function (v, i) { return [i + 'h', v, Math.round(v / mh * 100)]; }).filter(function (r) { return r[1] > 0; }));
    } else {
      new Chart(document.getElementById('ch-hour'), {
        type: 'bar',
        data: { labels: hours.map(function (_, i) { return i + 'h'; }), datasets: [{ data: hours, backgroundColor: 'rgba(201,162,39,.65)', borderRadius: 4 }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, maintainAspectRatio: false },
      });
    }

    // Por ingresso (doughnut)
    if (hasChart && d.per_ticket.length) {
      new Chart(document.getElementById('ch-ticket'), {
        type: 'doughnut',
        data: {
          labels: d.per_ticket.map(function (r) { return r.name; }),
          datasets: [{ data: d.per_ticket.map(function (r) { return +r.sold; }), backgroundColor: ['#C9A227', '#E7C766', '#8a6d14', '#5c5c5c', '#3a3a3a'], borderColor: '#141414' }],
        },
        options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, maintainAspectRatio: false, cutout: '62%' },
      });
    } else if (!hasChart) {
      var mt = Math.max.apply(null, d.per_ticket.map(function (r) { return +r.sold; }).concat([1]));
      fallbackTable('ch-ticket', d.per_ticket.map(function (r) { return [r.name, r.sold, Math.round(r.sold / mt * 100)]; }));
    }

    // Origem
    if (hasChart && d.per_source.length) {
      new Chart(document.getElementById('ch-source'), {
        type: 'bar',
        data: {
          labels: d.per_source.map(function (r) { return r.s; }),
          datasets: [{ data: d.per_source.map(function (r) { return +r.c; }), backgroundColor: 'rgba(231,199,102,.7)', borderRadius: 6 }],
        },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }, maintainAspectRatio: false },
      });
    } else if (!hasChart) {
      var ms = Math.max.apply(null, d.per_source.map(function (r) { return +r.c; }).concat([1]));
      fallbackTable('ch-source', d.per_source.map(function (r) { return [r.s, r.c, Math.round(r.c / ms * 100)]; }));
    }

    // Check-ins por hora
    var ch = {};
    d.checkins_hour.forEach(function (r) { ch[+r.h] = +r.n; });
    var chl = Object.keys(ch).sort(function (a, b) { return a - b; });
    if (hasChart) {
      new Chart(document.getElementById('ch-checkin'), {
        type: 'line',
        data: { labels: chl.map(function (h) { return h + 'h'; }), datasets: [{ data: chl.map(function (h) { return ch[h]; }), borderColor: '#60a5fa', backgroundColor: 'rgba(96,165,250,.12)', fill: true, tension: 0.4, pointRadius: 2 }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, maintainAspectRatio: false },
      });
    } else {
      var mc = Math.max.apply(null, chl.map(function (h) { return ch[h]; }).concat([1]));
      fallbackTable('ch-checkin', chl.map(function (h) { return [h + 'h', ch[h], Math.round(ch[h] / mc * 100)]; }));
    }
  }).catch(function () { /* silencioso */ });
})();
