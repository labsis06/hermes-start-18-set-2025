<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

$bridge = $params->get('bridge_url', 'http://tapeserver.int.ov.ingv.it:8000');

$doc = Factory::getApplication()->getDocument();
$wa  = $doc->getWebAssetManager();


 //Carichiamo Plotly locale:
 // percorso relativo: /modules/mod_seispick/media/plotly.min.js
 
$wa->registerScript(
  'mod_seispick.plotly',
  'modules/mod_seispick/media/plotly.min.js',
  [],
  ['defer' => true],
  ['core']
);
$wa->useScript('mod_seispick.plotly');
?>

<div class="mod-seispick card" style="padding:12px;">
  <h4 style="margin-top:0;">Web Picker SeisComP (demo)</h4>

  <div class="grid" style="display:grid;grid-template-columns:repeat(6, minmax(0,1fr));gap:8px;align-items:end;">
    <label>NET<br><input id="net" value="IV" class="form-control" /></label>
    <label>STA<br><input id="sta" value="CBAG" class="form-control" /></label>
    <label>LOC<br><input id="loc" value="" class="form-control" /></label>
    <label>CHA<br><input id="cha" value="HHZ" class="form-control" /></label>
    <label>Start (UTC)<br><input id="t0" type="datetime-local" class="form-control" /></label>
    <label>End (UTC)<br><input id="t1" type="datetime-local" class="form-control" /></label>
  </div>

  <div class="grid" style="display:grid;grid-template-columns:repeat(6, minmax(0,1fr));gap:8px;margin-top:8px;align-items:end;">
    <label>Phase<br>
      <select id="phase" class="form-select">
        <option value="P">P</option>
        <option value="S">S</option>
      </select>
    </label>
    <label>Author<br><input id="author" value="webpick" class="form-control" /></label>
    <label>Agency<br><input id="agency" value="INGV" class="form-control" /></label>
    <label>Event ID<br><input id="eventid" placeholder="(facoltativo)" class="form-control" /></label>
    <button id="load" class="btn btn-primary">Carica Traccia</button>
    <button id="send" class="btn btn-success" disabled>Invia Pick</button>
    <div style="margin-top:15px;">
    <button id="show-picks" class="btn btn-secondary">📄 Mostra ultimi pick salvati</button>
    <div id="picks-list" style="margin-top:10px; max-height:200px; overflow:auto; font-size:0.9em; background:#222; color:#eee; padding:8px;"></div>
  </div>
  </div>

  <div id="plot" style="width:100%;height:420px;margin-top:12px;border:1px solid #e5e5e5;border-radius:6px;"></div>

  <p>Hai cliccato (UTC): <span id="picked" style="font-family:monospace;"></span></p>
  <pre id="log" style="background:#f6f6f6;padding:8px;white-space:pre-wrap;border-radius:6px;"></pre>
</div>

<script>
const BRIDGE = "<?php echo htmlspecialchars($bridge, ENT_QUOTES, 'UTF-8'); ?>";

let currentMeta = null;
let lastPickUTC = null;

// 1) Carica la traccia
async function loadTrace(){
  const log = document.getElementById('log');
  log.textContent = 'Caricamento...';
  try {
    const payload = {
      network:  document.getElementById('net').value,
      station:  document.getElementById('sta').value,
      location: document.getElementById('loc').value,
      channel:  document.getElementById('cha').value,
      starttime:document.getElementById('t0').value,
      endtime:  document.getElementById('t1').value,
      decimate: 4
    };
    const res = await fetch(BRIDGE + "/api/trace", {
      method:"POST",
      headers:{"Content-Type":"application/json"},
      body: JSON.stringify(payload)
    });
    if(!res.ok){
      const txt = await res.text();
      log.textContent = "Errore /api/trace ("+res.status+"): " + txt;
      return;
    }
    const data = await res.json();
    currentMeta = data.meta;

    const t0 = new Date(currentMeta.starttime).getTime();
    const xAbs = data.x.map(s => new Date(t0 + s*1000));

    Plotly.newPlot("plot", [{
      x: xAbs, y: data.y, mode: "lines", name: `${currentMeta.station}.${currentMeta.channel}`
    }], {
      margin:{l:40,r:10,t:20,b:40},
      xaxis:{title:"Tempo (UTC)"},
      yaxis:{title:"Counts"}
    });

    const plot = document.getElementById("plot");
    plot.on("plotly_click", (ev) => {
      const t = ev.points[0].x;
      lastPickUTC = new Date(t).toISOString().replace(".000","");
      document.getElementById("picked").textContent = lastPickUTC;
      document.getElementById("send").disabled = false;
    });

    log.textContent = "Traccia caricata.";
  } catch (e) {
    // Qui catturiamo CORS/mixed content/network error/JS error
    log.textContent = "Errore JS: " + (e?.message || e);
  }
}

// 2) Invia il pick
async function sendPick(){
  const log = document.getElementById('log');
  try {
    if(!currentMeta || !lastPickUTC){
      log.textContent = "Nessuna traccia o pick selezionato.";
      return;
    }
    const payload = {
      event_public_id: document.getElementById('eventid').value,
      network: currentMeta.network,
      station: currentMeta.station,
      location: currentMeta.location || "",
      channel: currentMeta.channel,
      phase_hint: document.getElementById('phase').value,
      pick_time: lastPickUTC,
      author: document.getElementById('author').value,
      agency_id: document.getElementById('agency').value
    };
    const res = await fetch(BRIDGE + "/api/pick", {
      method:"POST", headers:{"Content-Type":"application/json"},
      body: JSON.stringify(payload)
    });
    document.getElementById('send').disabled = true;
    const txt = await res.text();
    if(!res.ok){
      log.textContent = "Errore /api/pick ("+res.status+"): " + txt;
      return;
    }
    log.textContent = txt || "Pick inviato.";
  } catch(e){
    log.textContent = "Errore JS: " + (e?.message || e);
  }
}
async function showPicks() {
  const container = document.getElementById('picks-list');
  const log = document.getElementById('log');

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

    // Costruisci un elenco HTML semplice
    let html = "<ul style='padding-left:18px; margin:0;'>";
    for (const p of picks) {
      const st = (p.station || "?");
      const ch = (p.channel || "?");
      const ph = (p.phase_hint || "?");
      const t  = (p.pick_time || "?");
      const ev = (p.event_public_id || "—");
      html += "<li>";
      html += "<b>" + st + "." + ch + "</b> ";
      html += "(" + ph + ") ";
      html += "→ " + t + " ";
      html += "<br/><span style='color:#aaa;'>Evento: " + ev + "</span>";
      html += "</li>";
    }
    html += "</ul>";
    container.innerHTML = html;

  } catch (e) {
    container.textContent = "Errore JS mentre leggo i pick: " + (e?.message || e);
  }
}

document.getElementById('load').addEventListener('click', loadTrace);
document.getElementById('send').addEventListener('click', sendPick);
document.getElementById('show-picks').addEventListener('click', showPicks);
</script>
