<?php
defined('_JEXEC') or die;

// URL del bridge FastAPI (modificabile da parametri del modulo se vuoi)
$bridgeUrl = $params->get('bridge_url', 'http://tapeserver.int.ov.ingv.it:8000');

// Default parametri stazione/canale
$defaultNetwork  = $params->get('network',  'IV');
$defaultStation  = $params->get('station',  'CSTH');
$defaultLocation = $params->get('location', '');
$defaultChannel  = $params->get('channel',  'EHZ');
?>
<style>
  #seispick-mini-studio {
    background: #111;
    color: #eee;
    border-radius: 6px;
    padding: 10px;
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    font-size: 14px;
  }
  #seispick-mini-studio h3 {
    margin-top: 0;
    margin-bottom: 6px;
    font-size: 18px;
    color: #ffd27f;
  }
  #seispick-mini-studio .subtitle {
    font-size: 12px;
    color: #aaa;
  }
  #seispick-layout {
    display: flex;
    gap: 12px;
    margin-top: 8px;
  }
  #seispick-left, #seispick-right {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  #seispick-left {
    flex: 2;
    min-width: 0;
  }
  #seispick-right {
    flex: 1;
    min-width: 0;
  }
  .seispick-card {
    background: #181818;
    border-radius: 6px;
    padding: 8px;
    border: 1px solid #333;
  }
  .seispick-card h4 {
    margin: 0 0 6px;
    font-size: 14px;
    color: #f0b94a;
  }
  .seispick-form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 6px;
  }
  .seispick-form-group {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
  }
  .seispick-form-group label {
    font-size: 11px;
    color: #aaa;
    margin-bottom: 2px;
  }
  .seispick-form-group input {
    background: #111;
    border: 1px solid #444;
    color: #eee;
    border-radius: 4px;
    padding: 3px 4px;
    font-size: 12px;
  }
  .seispick-btn-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 4px;
  }
  .seispick-btn {
    border-radius: 4px;
    border: 1px solid #555;
    background: #333;
    color: #eee;
    padding: 4px 8px;
    font-size: 12px;
    cursor: pointer;
  }
  .seispick-btn.primary {
    background: #e48b1b;
    border-color: #f0b94a;
    color: #111;
    font-weight: 600;
  }
  .seispick-btn.danger {
    background: #662222;
    border-color: #aa4444;
    color: #fdd;
  }
  .seispick-btn:disabled {
    opacity: 0.5;
    cursor: default;
  }
  #plot {
    width: 100%;
    height: 420px;
  }
  #picks-list {
    margin-top: 6px;
    max-height: 220px;
    overflow-y: auto;
    background: #111;
    border-radius: 4px;
    border: 1px solid #333;
    padding: 6px;
    font-size: 12px;
  }
  #picks-list ul {
    list-style: none;
    margin: 0;
    padding: 0;
  }
  #picks-list li {
    padding: 4px 4px;
    border-bottom: 1px solid #222;
  }
  #picks-list li:last-child {
    border-bottom: none;
  }
  #picks-list li:hover {
    background: #222;
  }
  #picks-list li.selected {
    background: #333;
    border-left: 3px solid #e48b1b;
  }
  #log {
    background: #050505;
    color: #aaa;
    border-radius: 4px;
    padding: 6px;
    font-size: 11px;
    max-height: 120px;
    overflow-y: auto;
    border: 1px solid #333;
  }
</style>

<div id="seispick-mini-studio">
  <div style="display:flex; justify-content:space-between; align-items:baseline;">
    <div>
      <h3>Seismic Mini Studio</h3>
      <div class="subtitle">
        Bridge: <code><?php echo htmlspecialchars($bridgeUrl, ENT_QUOTES, 'UTF-8'); ?></code>
      </div>
    </div>
    <div class="subtitle">
      Stazione: <code><?php echo htmlspecialchars($defaultNetwork.'.'.$defaultStation.'.'.$defaultChannel, ENT_QUOTES, 'UTF-8'); ?></code>
    </div>
  </div>

  <div id="seispick-layout">
    <!-- COLONNA SINISTRA -->
    <div id="seispick-left">
      <div class="seispick-card">
        <h4>Traccia sismica</h4>
        <div class="seispick-form-row">
          <div class="seispick-form-group" style="max-width:70px;">
            <label for="network">Network</label>
            <input id="network" type="text" value="<?php echo htmlspecialchars($defaultNetwork, ENT_QUOTES, 'UTF-8'); ?>" />
          </div>
          <div class="seispick-form-group" style="max-width:80px;">
            <label for="station">Station</label>
            <input id="station" type="text" value="<?php echo htmlspecialchars($defaultStation, ENT_QUOTES, 'UTF-8'); ?>" />
          </div>
          <div class="seispick-form-group" style="max-width:70px;">
            <label for="location">Location</label>
            <input id="location" type="text" value="<?php echo htmlspecialchars($defaultLocation, ENT_QUOTES, 'UTF-8'); ?>" />
          </div>
          <div class="seispick-form-group" style="max-width:80px;">
            <label for="channel">Channel</label>
            <input id="channel" type="text" value="<?php echo htmlspecialchars($defaultChannel, ENT_QUOTES, 'UTF-8'); ?>" />
          </div>
        </div>

        <div class="seispick-form-row">
          <div class="seispick-form-group">
            <label for="starttime">Start time (UTC, ISO)</label>
            <input id="starttime" type="text" placeholder="es. 2024-10-20T00:00:00Z" />
          </div>
          <div class="seispick-form-group">
            <label for="endtime">End time (UTC, ISO)</label>
            <input id="endtime" type="text" placeholder="es. 2024-10-20T00:02:00Z" />
          </div>
          <div class="seispick-form-group" style="max-width:80px;">
            <label for="decimate">Decimate</label>
            <input id="decimate" type="number" min="1" step="1" value="4" />
          </div>
        </div>

        <div class="seispick-btn-row">
          <button id="load" class="seispick-btn primary">📡 Carica traccia</button>
          <button id="reset-zoom" class="seispick-btn">🔁 Reset zoom</button>
          <button id="clear-shapes" class="seispick-btn danger">🧹 Pulisci linee</button>
        </div>
      </div>

      <div class="seispick-card">
        <div id="plot"></div>
      </div>
    </div>

    <!-- COLONNA DESTRA -->
    <div id="seispick-right">
      <div class="seispick-card">
        <h4>Pick corrente</h4>
        <div style="font-size:12px;">
          Tempo pick selezionato:<br />
          <code id="picked">—</code>
        </div>
        <div style="margin-top:6px; font-size:12px;">
          Evento:<br />
          <input id="event-id" type="text" style="width:100%; background:#111; border:1px solid #444; border-radius:4px; padding:3px 4px; color:#eee;" placeholder="event_public_id (opzionale)" />
        </div>
        <div style="margin-top:6px; font-size:12px;">
          Phase hint:<br />
          <input id="phase-hint" type="text" style="width:100%; background:#111; border:1px solid #444; border-radius:4px; padding:3px 4px; color:#eee;" value="P" />
        </div>

        <div class="seispick-btn-row" style="margin-top:8px;">
          <button id="zoom-pick" class="seispick-btn">🔍 Zoom sul pick</button>
          <button id="send" class="seispick-btn primary" disabled>📤 Invia pick</button>
        </div>
      </div>

      <div class="seispick-card">
        <h4>Pick salvati</h4>
        <div class="seispick-btn-row">
          <button id="show-picks" class="seispick-btn">📄 Mostra ultimi pick</button>
        </div>
        <div id="picks-list">Nessun pick visualizzato.</div>
      </div>

      <div class="seispick-card">
        <h4>Log</h4>
        <pre id="log">Pronto.</pre>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.plot.ly/plotly-2.26.0.min.js"></script>

<script>
(function() {
  const BRIDGE = "<?php echo htmlspecialchars($bridgeUrl, ENT_QUOTES, 'UTF-8'); ?>";

  let lastTraceMeta = null;
  let lastPickUTC = null;
  let fullXRange = null;

  const logEl     = document.getElementById('log');
  const plotEl    = document.getElementById('plot');
  const pickedEl  = document.getElementById('picked');
  const eventEl   = document.getElementById('event-id');
  const phaseEl   = document.getElementById('phase-hint');
  const picksList = document.getElementById('picks-list');

  function log(msg) {
    const now = new Date().toISOString();
    logEl.textContent = "[" + now + "] " + msg + "\n" + logEl.textContent;
  }

  async function loadTrace() {
    const network = document.getElementById('network').value.trim();
    const station = document.getElementById('station').value.trim();
    const location = document.getElementById('location').value.trim();
    const channel = document.getElementById('channel').value.trim();
    const starttime = document.getElementById('starttime').value.trim();
    const endtime = document.getElementById('endtime').value.trim();
    const decimate = document.getElementById('decimate').value.trim();

    if (!network || !station || !channel || !starttime || !endtime) {
      log("⚠ Compila network, station, channel, starttime, endtime.");
      return;
    }

    const params = new URLSearchParams({
      network: network,
      station: station,
      location: location,
      channel: channel,
      starttime: starttime,
      endtime: endtime
    });
    if (decimate && parseInt(decimate) > 1) {
      params.set('decimate', parseInt(decimate, 10));
    }

    const url = BRIDGE + "/api/trace?" + params.toString();
    log("Richiesta /api/trace: " + url);

    try {
      const res = await fetch(url);
      const text = await res.text();
      let data;

      try {
        data = JSON.parse(text);
      } catch (e) {
        log("❌ Risposta non JSON da /api/trace: " + text);
        return;
      }

      if (!res.ok) {
        log("❌ Errore /api/trace (" + res.status + "): " + text);
        return;
      }

      if (!data || !Array.isArray(data.x) || !Array.isArray(data.y) || !data.meta) {
        log("❌ Formato inatteso da /api/trace: " + JSON.stringify(data));
        return;
      }

      lastTraceMeta = data.meta;

      const t0 = new Date(data.meta.starttime);
      if (isNaN(t0.getTime())) {
        log("⚠ meta.starttime non valido: " + data.meta.starttime);
      }

      const xs = data.x.map(sec => new Date(t0.getTime() + sec * 1000.0));
      const ys = data.y;

      const trace = {
        x: xs,
        y: ys,
        type: 'scatter',
        mode: 'lines',
        line: { width: 1 },
        name: data.meta.network + "." + data.meta.station + "." + data.meta.channel
      };

      const layout = {
        margin: { t: 30, r: 10, b: 40, l: 50 },
        paper_bgcolor: '#181818',
        plot_bgcolor: '#181818',
        xaxis: {
          title: 'Tempo UTC',
          color: '#eee'
        },
        yaxis: {
          title: 'Conteggi',
          color: '#eee'
        },
        shapes: []
      };

      fullXRange = [xs[0], xs[xs.length - 1]];
      lastPickUTC = null;
      pickedEl.textContent = "—";
      document.getElementById('send').disabled = true;

      Plotly.newPlot('plot', [trace], layout, {
        displaylogo: false,
        modeBarButtonsToRemove: ['toImage', 'lasso2d', 'select2d']
      });

      log("✅ Traccia caricata correttamente.");

      plotEl.on('plotly_click', function(ev) {
        if (!ev || !ev.points || !ev.points.length) return;
        const pt = ev.points[0];
        const t = pt.x;
        if (!(t instanceof Date)) return;
        const iso = t.toISOString().replace('Z', 'Z');
        highlightPick(iso);
      });

    } catch (err) {
      log("❌ Errore JS /api/trace: " + (err && err.message ? err.message : err));
    }
  }

  function highlightPick(pickTimeIso) {
    if (!pickTimeIso) return;
    const plot = plotEl;
    if (!plot || !plot.data || !plot.data.length) return;

    lastPickUTC = pickTimeIso;
    pickedEl.textContent = pickTimeIso;
    document.getElementById('send').disabled = false;

    const t = new Date(pickTimeIso);
    if (isNaN(t.getTime())) return;

    const layout = plot.layout || {};
    const shapes = (layout.shapes || []).filter(s => s.name !== 'pick');

    shapes.push({
      type: 'line',
      x0: t,
      x1: t,
      y0: 0,
      y1: 1,
      xref: 'x',
      yref: 'paper',
      line: { dash: 'dot', width: 2 },
      name: 'pick'
    });

    Plotly.relayout('plot', { shapes: shapes });
  }

  function zoomOnPick() {
    if (!lastPickUTC || !plotEl || !plotEl.data || !plotEl.data.length) return;
    const t = new Date(lastPickUTC);
    if (isNaN(t.getTime())) return;

    const windowSec = 10;
    const t0 = new Date(t.getTime() - windowSec * 1000);
    const t1 = new Date(t.getTime() + windowSec * 1000);

    Plotly.relayout('plot', {
      'xaxis.range': [t0, t1]
    });
  }

  function resetZoom() {
    if (!fullXRange) return;
    Plotly.relayout('plot', {
      'xaxis.range': fullXRange
    });
  }

  function clearShapes() {
    if (!plotEl || !plotEl.layout) return;
    Plotly.relayout('plot', { shapes: [] });
    lastPickUTC = null;
    pickedEl.textContent = "—";
    document.getElementById('send').disabled = true;
  }

  async function sendPick() {
    if (!lastTraceMeta || !lastPickUTC) {
      log("⚠ Nessuna traccia o pick selezionato.");
      return;
    }

    const payload = {
      network:   lastTraceMeta.network || document.getElementById('network').value.trim(),
      station:   lastTraceMeta.station || document.getElementById('station').value.trim(),
      location:  lastTraceMeta.location || document.getElementById('location').value.trim(),
      channel:   lastTraceMeta.channel || document.getElementById('channel').value.trim(),
      pick_time: lastPickUTC,
      phase_hint: phaseEl.value.trim() || "P",
      event_public_id: eventEl.value.trim() || null
    };

    log("Invio pick: " + JSON.stringify(payload));

    try {
      const res = await fetch(BRIDGE + "/api/pick", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      const text = await res.text();
      let data;
      try { data = JSON.parse(text); } catch (e) { data = null; }

      if (!res.ok) {
        log("❌ Errore /api/pick (" + res.status + "): " + text);
        return;
      }

      log("✅ Pick inviato: " + text);

    } catch (err) {
      log("❌ Errore JS /api/pick: " + (err && err.message ? err.message : err));
    }
  }

  async function showPicks() {
    const container = picksList;
    container.textContent = "Caricamento pick...";
    try {
      const res = await fetch(BRIDGE + "/api/picks");
      if (!res.ok) {
        const txt = await res.text();
        container.textContent = "Errore /api/picks (" + res.status + "): " + txt;
        return;
      }
      const picks = await res.json();
      if (!Array.isArray(picks) || picks.length === 0) {
        container.textContent = "Nessun pick salvato.";
        return;
      }

      let html = "<ul>";
      for (const p of picks) {
        const st = (p.station || "?");
        const ch = (p.channel || "?");
        const ph = (p.phase_hint || "?");
        const t  = (p.pick_time || "?");
        const ev = (p.event_public_id || "—");
        html += "<li data-pick-time=\"" + t + "\">";
        html += "<b>" + st + "." + ch + "</b> (" + ph + ") → " + t;
        html += "<br/><span style='color:#aaa;'>Evento: " + ev + "</span>";
        html += "</li>";
      }
      html += "</ul>";
      container.innerHTML = html;

      const items = container.querySelectorAll('li[data-pick-time]');
      items.forEach(li => {
        li.style.cursor = 'pointer';
        li.addEventListener('click', () => {
          items.forEach(o => o.classList.remove('selected'));
          li.classList.add('selected');
          const iso = li.getAttribute('data-pick-time');
          highlightPick(iso);
        });
      });

    } catch (e) {
      container.textContent = "Errore JS mentre leggo i pick: " + (e && e.message ? e.message : e);
    }
  }

  document.getElementById('load').addEventListener('click', loadTrace);
  document.getElementById('send').addEventListener('click', sendPick);
  document.getElementById('show-picks').addEventListener('click', showPicks);
  document.getElementById('zoom-pick').addEventListener('click', zoomOnPick);
  document.getElementById('reset-zoom').addEventListener('click', resetZoom);
  document.getElementById('clear-shapes').addEventListener('click', clearShapes);

})();
</script>
