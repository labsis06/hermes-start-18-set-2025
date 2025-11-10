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
  </div>

  <div id="plot" style="width:100%;height:420px;margin-top:12px;border:1px solid #e5e5e5;border-radius:6px;"></div>

  <p>Hai cliccato (UTC): <span id="picked" style="font-family:monospace;"></span></p>
  <pre id="log" style="background:#f6f6f6;padding:8px;white-space:pre-wrap;border-radius:6px;"></pre>
</div>

<script>
const BRIDGE = "<?php echo htmlspecialchars($bridge, ENT_QUOTES, 'UTF-8'); ?>";

//Variabili runtime
 
let currentMeta = null;     // metadati della traccia dal bridge (`network`, `station`, `location`, `channel`, `sampling_rate`, ecc.)
let lastPickUTC = null;     // ISO string del pick


 // Helper: formatta Date -> 'YYYY-MM-DDTHH:mm:ss'
 
function toLocalInputValue(date) {
  const pad = n => String(n).padStart(2, '0');
  return `${date.getUTCFullYear()}-${pad(date.getUTCMonth()+1)}-${pad(date.getUTCDate())}T${pad(date.getUTCHours())}:${pad(date.getUTCMinutes())}:${pad(date.getUTCSeconds())}`;
}


  //All’avvio proponiamo una finestra [ora-5m, ora]
 
(function initDefaults(){
  try {
    const now = new Date();
    const t1 = new Date(now.getTime());
    const t0 = new Date(now.getTime() - 5*60*1000);
    document.getElementById('t0').value = toLocalInputValue(t0);
    document.getElementById('t1').value = toLocalInputValue(t1);
  } catch (e) {}
})();


 // 1) Carica la traccia dal bridge e disegna il grafico con Plotly
 //    Atteso un JSON del tipo:
 //    {
 //      "times": ["2025-10-20T12:00:00.000Z", ...],
 //      "values": [123, ...],
 //      "meta": {"network":"IV","station":"CBAG","location":"","channel":"HHZ","sampling_rate":100}
 //    }
 


async function loadTrace(){
  const log = document.getElementById('log');
  log.textContent = "";
  document.getElementById('send').disabled = true;
  lastPickUTC = null;
  document.getElementById('picked').textContent = "";

  // Normalizza gli orari in UTC con 'Z'
  const t0 = document.getElementById('t0').value.trim();
  const t1 = document.getElementById('t1').value.trim();
  const startIso = new Date(t0 + 'Z').toISOString();
  const endIso   = new Date(t1 + 'Z').toISOString();
 

  const payload = {
    network:  document.getElementById('net').value.trim(),
    station:  document.getElementById('sta').value.trim(),
    location: document.getElementById('loc').value.trim(),
    channel:  document.getElementById('cha').value.trim(),
    starttime: startIso,
    endtime:   endIso,
    decimate:  4
  };

  // Utils
  const trimMicro = (iso) => {
    // converte '...000000Z' in '...000Z' per compatibilità Date()
    return iso.replace(/\.(\d{3})\d*Z$/, '.$1Z');
  };
  const genTimes = (n, startISO, sr) => {
    const out = new Array(n);
    const t0 = Date.parse(trimMicro(startISO));
    const dt = 1000 / sr; // ms
    for (let i = 0; i < n; i++) {
      out[i] = new Date(t0 + i*dt).toISOString();
    }
    return out;
  };
  const decimate = (arr, step) => {
    if (!step || step <= 1) return arr;
    const out = new Array(Math.ceil(arr.length/step));
    let j = 0;
    for (let i = 0; i < arr.length; i += step) out[j++] = arr[i];
    return out;
  };

  try {
    const res = await fetch(BRIDGE + "/api/trace", {
      method:"POST",
      mode:"cors",
      cache:"no-store",
      headers:{ "Content-Type":"application/json" },
      body: JSON.stringify(payload)
    });

    const ct = res.headers.get('content-type') || '';
    if (!res.ok) {
      const txt = await res.text().catch(()=>"");
      log.textContent = `HTTP ${res.status} ${res.statusText}\nCT: ${ct}\nBody:\n${txt}`;
      return;
    }
    if (!ct.includes("application/json")) {
      const txt = await res.text();
      log.textContent = `Atteso JSON ma ricevuto:\nCT: ${ct}\nBody:\n${txt}`;
      return;
    }

    const data = await res.json();

    // ===== Normalizzazione formato =====
    let times = null, values = null, meta = null;

    if (Array.isArray(data.times) && Array.isArray(data.values)) {
      // Formato già “nuovo” (times/values)
      times  = data.times;
      values = data.values;
      meta   = data.meta || {};
    } else if (Array.isArray(data.traces) && data.traces.length > 0) {
      // Formato “trace list” (il tuo)
      const tr = data.traces[0];
      const sr = tr.sampling_rate || 100;
      const n  = tr.npts || (tr.data ? tr.data.length : 0);
      if (!n || !tr.data || !tr.starttime) {
        log.textContent = "Trace priva di dati o starttime:\n" + JSON.stringify(tr, null, 2);
        return;
      }
      values = tr.data;
      times  = genTimes(values.length, tr.starttime, sr);

      // Decimazione lato client se richiesto
      const step = payload.decimate && payload.decimate > 1 ? payload.decimate : 1;
      if (step > 1) {
        times  = decimate(times, step);
        values = decimate(values, step);
      }

      // Meta
      const id = tr.id || "";
      // id tipo "IV.CSTH..EHZ"
      const [network="", station="", location="", channel=""] = id.split(".");
      meta = {
        network, station, location, channel,
        sampling_rate: sr / (step || 1)
      };
    }

    if (!times || !values || times.length !== values.length) {
      log.textContent = "JSON ricevuto ma non nel formato atteso:\n" + JSON.stringify(data, null, 2);
      return;
    }

    currentMeta = meta || {
      network: payload.network,
      station: payload.station,
      location: payload.location,
      channel:  payload.channel
    };

    const trace = {
      x: times,
      y: values,
      mode: 'lines',
      type: 'scattergl', // più performante con serie lunghe
      name: `${currentMeta.network}.${currentMeta.station}.${currentMeta.location || ''}.${currentMeta.channel}`
    };
    const layout = { margin:{l:50,r:10,t:10,b:40}, xaxis:{title:'Tempo (UTC)'}, yaxis:{title:'Counts'} };
    Plotly.newPlot('plot', [trace], layout, {responsive:true, displaylogo:false});

    const plotDiv = document.getElementById('plot');
    plotDiv.on('plotly_click', (evt) => {
      if (evt?.points?.length) {
        lastPickUTC = new Date(evt.points[0].x).toISOString();
        document.getElementById('picked').textContent = lastPickUTC;
        document.getElementById('send').disabled = false;
      }
    });

    log.textContent = "Traccia caricata ✅ (clicca per scegliere il pick)";
  } catch (err) {
    log.textContent = "Eccezione /api/trace: " + (err?.message ?? String(err));
  }
}
</script>
