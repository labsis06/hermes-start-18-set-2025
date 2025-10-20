<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

$bridge = $params->get('bridge_url', 'http://tapeserver.int.ov.ingv.it:8000');

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
/**
 * Registriamo e carichiamo lo script locale:
 * percorsi relativi a /modules/mod_seispick/...
 */
$wa->registerAndUseScript(
  'plotly-local',
  'modules/mod_seispick/media/plotly.min.js',
  [],
  ['defer' => true]
);
?>
<div class="container" style="max-width:980px;margin:0 auto;">
  <h3>Picker sismico (demo)</h3>

  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
    <input id="net" value="IV" placeholder="Network">
    <input id="sta" value="ABCD" placeholder="Station">
    <input id="loc" value="" placeholder="Location">
    <input id="cha" value="HHZ" placeholder="Channel">
    <input id="t0"  value="2025-10-14T10:20:00Z" placeholder="Start UTC">
    <input id="t1"  value="2025-10-14T10:22:00Z" placeholder="End UTC">
    <button id="load">Carica traccia</button>
  </div>

  <div id="plot" style="width:100%;height:420px;border:1px solid #ddd;"></div>

  <div style="display:flex;gap:8px;align-items:center;margin-top:8px;flex-wrap:wrap;">
    <select id="phase"><option>P</option><option>S</option></select>
    <input id="eventid" size="40" value="smi:local/event/TEST-1234" placeholder="event publicID">
    <input id="author" value="webpick" placeholder="author">
    <input id="agency" value="MYNET" placeholder="agency">
    <button id="send" disabled>Invia Pick</button>
  </div>

  <p>Hai cliccato (UTC): <span id="picked"></span></p>
  <pre id="log" style="background:#f6f6f6;padding:8px;white-space:pre-wrap;"></pre>
</div>

<script>
const BRIDGE = "<?php echo htmlspecialchars($bridge, ENT_QUOTES, 'UTF-8'); ?>";

let currentMeta = null;
let lastPickUTC = null;

// 1) Carica la traccia dal ponte (che chiama fdsnws) e disegna il grafico
async function loadTrace(){
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
    method:"POST", headers:{"Content-Type":"application/json"},
    body: JSON.stringify(payload)
  });
  if(!res.ok){
    document.getElementById('log').textContent = "Errore /api/trace: " + await res.text();
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
    const t = ev.points[0].x; // oggetto Date
    lastPickUTC = new Date(t).toISOString().replace(".000","");
    document.getElementById("picked").textContent = lastPickUTC;
    document.getElementById("send").disabled = false;
  });
}

// 2) Invia il pick al ponte (che genera SC3ML e chiama scdispatch)
async function sendPick(){
  if(!currentMeta || !lastPickUTC) return;
  const payload = {
    event_public_id: document.getElementById('eventid').value,
    network: currentMeta.network,
    station: currentMeta.station,
    location: currentMeta.location,
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
  document.getElementById('log').textContent = await res.text();
}

document.getElementById('load').addEventListener('click', loadTrace);
document.getElementById('send').addEventListener('click', sendPick);
</script>
