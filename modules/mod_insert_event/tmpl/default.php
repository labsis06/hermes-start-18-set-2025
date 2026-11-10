<?php defined('_JEXEC') or die; ?>

<!DOCTYPE html>
<html lang="it">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<meta charset="utf-8">
	<title>Insert New Event (Manual)</title>
	<link rel="stylesheet" href="templates/bootstrap4/css/bootstrap.min.css">
	<link rel="stylesheet" href="templates/fontawesome6/css/fontawesome.min.css">
</head>
<body>
<form id="insert-event-form-<?php echo (int) $module->id; ?>" method="post" enctype="multipart/form-data">
	<input type="hidden" name="mod_id" value="<?php echo $module->id; ?>" />
    <input type="hidden" name="event_check_token" value="">
    <input type="hidden" name="confirm_duplicate" value="0">
    <?php echo JHtml::_('form.token'); ?>
  <div class="form-group row">
				<label for="data" class="col-4 col-form-label">Data</label>
				<div class="col-8">
					<input id="data" name="data" type="date" class="form-control" required="required">
				</div>
			</div>
			<div class="form-group row">
				<label for="ora" class="col-4 col-form-label">Ora (UTC)</label>
				<div class="col-8">
					<input id="ora" name="ora" type="time" class="form-control" required="required" step="1">

				</div>
			</div>

			<div class="form-group row">
				<label for="tipo" class="col-4 col-form-label">Tipo</label>
				<div class="col-8">
					<select id="tipo" name="tipo" class="custom-select" required="required">
						<option value="terremoto">terremoto</option>
						<option value="LP">LP</option>
						<option value="esplosione artificiale">esplosione artificiale</option>
						<option value="frana">frana</option>
						<option value="telesisma">telesisma</option>
						<option value="sciame">sciame</option>
						<option value="altro">altro</option>
					</select>
				</div>
			</div>
			<div class="form-group row">
				<label for="area" class="col-4 col-form-label">Area</label>
				<div class="col-8">
					<select id="area" name="area" class="custom-select" required="required">
						<option value="select_area" selected>--Select Area--</option>
					</select>
				</div>
			</div>
			<div class="form-group row">
				<label for="stazione" class="col-4 col-form-label">Stazione first</label>
				<div class="col-8">
					<select id="stazione" name="stazione" class="custom-select">
						<option value="select_stazione" selected>--Seleziona prima l'area--</option>
					</select>
				</div>
			</div>


			<script language="javascript" type="text/javascript">
				var AreaList = {
					Vesuvio : ['ND','BKE','VBKE','CPV','CRTO','OTV','OVO','POB','PPV','NL9','TDG','TRZ','SSB','VBKN','VCNE','VCRE','VEPO','VOVO','VTIR','VVDG','Altra stazione'],
					'Campi Flegrei' :  ['ND','ASE','BAC','CAAM','CAP','CASO','CBAC','CDOV','CFB1','CFMN','CIRC','CMSA','CMIS','CNIS','COLB','CPIS','CPOZ','CSFT','CSOB','CSTH','CROS','STH','Altra Stazione'],
					Ischia :  ['ND','CAI','FO9','OC9','IFOR','IMTC','IOCA','ICVJ','IBMC','PTMR','IPSM','IBRN','IMNT','IVLC','Altra Stazione'],
					Regionale :  ['ND','CAFE','CMPR','MRLC','MSC','NL9','PSB1','SGG','SOR','SORR','Altra Stazione']
				};
				el_area = document.getElementById("area");
				el_stazione = document.getElementById("stazione");
				for (key in AreaList) {
					el_area.innerHTML = el_area.innerHTML + '<option>'+ key +'</option>';
				}
				el_area.addEventListener('change', function populate_stazioni(e)
				{

					el_stazione.innerHTML = '';

					itm = e.target.value;
					if (itm in AreaList) {
						for (i = 0; i < AreaList[itm].length; i++) {
							el_stazione.innerHTML = el_stazione.innerHTML + '<option>'+ AreaList[itm][i] +'</option>';
						}
					}
				});
				function reset_form()
				{
					location.reload();
				}
			</script>

			<div class="form-group row">
				<label for="componente" class="col-4 col-form-label">Componente</label>
				<div class="col-8">
					<select id="componente" name="componente" class="custom-select">
						<option value="ND">ND</option>
                        <option value="EDF">EDF</option>
						<option value="EHE">EHE</option>
						<option value="EHN">EHN</option>
						<option value="EHZ">EHZ</option>
						<option value="LHE">LHE</option>
						<option value="HHB">HHB</option>
						<option value="HHC">HHC</option>
						<option value="HHE">HHE</option>
						<option value="HHZ">HHZ</option>
						<option value="HNE">HNE</option>
						<option value="HNN">HNN</option>
						<option value="HNZ">HNZ</option>
						<option value="V">V</option>
						<option value="N">N</option>
						<option value="E">E</option>
						<option value="MIC">MIC</option>
					</select>
				</div>
			</div>

  <div class="form-group row">
    <label for="Md" class="col-4 col-form-label">Md</label>
    <div class="col-8">
        <input id="Md" name="Md" type="number" placeholder="Esempio: 1.25" step="0.01" class="form-control">
    </div>
</div>

  <div class="form-group row">
    <label for="profondita" class="col-4 col-form-label">Profondità (Km)</label>
    <div class="col-8">
        <input id="profondita" name="profondita" type="number" placeholder="Esempio: 2.55" step="0.01" class="form-control">
    </div>
</div>

  <div class="form-group row">
    <label for="lat" class="col-4 col-form-label">Latitudine</label>
    <div class="col-8">
        <input id="lat" name="lat" type="text" placeholder="Esempio: 40.806167"  class="form-control">
    </div>
</div>

  <div class="form-group row">
    <label for="lon" class="col-4 col-form-label">Longitudine</label>
    <div class="col-8">
        <input id="lon" name="lon" type="text" placeholder="Esempio: 14.105667" class="form-control">
    </div>
</div>
  
  <div class="form-group row">
    <label for="immagine" class="col-4 col-form-label">Immagini</label>
    <div class="col-8">
        <input id="immagine" name="immagine" type="file" accept="image/*" class="form-control">
    </div>
  </div>


  <div class="form-group row">
    <label for="note" class="col-4 col-form-label">Note</label>
    <div class="col-8">
        <input id="note" name="note" type="text" class="form-control">
    </div>
</div>
  
  
  <div class="form-group row">
				<div class="offset-4 col-8">
					<button name="submit" type="submit" class="btn btn-primary">Submit</button>
					<button name="reset"  onclick="reset_form()" class="btn btn-primary">Reset</button>
				</div>
			</div>
		</form>
    <p id="insert-event-status-<?php echo (int) $module->id; ?>" role="status" aria-live="polite"></p>
    <noscript>Per verificare i duplicati e confermare l'inserimento è necessario abilitare JavaScript.</noscript>
    <dialog id="insert-event-confirm-<?php echo (int) $module->id; ?>" aria-labelledby="insert-event-heading-<?php echo (int) $module->id; ?>">
        <h2 id="insert-event-heading-<?php echo (int) $module->id; ?>">Verifica evento prima dell'inserimento</h2>
        <p data-check-details style="white-space: pre-line"></p>
        <p>Vuoi continuare e inserire un nuovo evento nel database locale?</p>
        <button type="button" data-continue class="btn btn-primary">Continua inserimento</button>
        <button type="button" data-cancel class="btn btn-secondary">Annulla</button>
    </dialog>
    <script>
    (() => {
        const form = document.getElementById('insert-event-form-<?php echo (int) $module->id; ?>');
        const dialog = document.getElementById('insert-event-confirm-<?php echo (int) $module->id; ?>');
        const status = document.getElementById('insert-event-status-<?php echo (int) $module->id; ?>');
        const submit = form.querySelector('button[type="submit"]');
        const snapshot = () => JSON.stringify([...new FormData(form)].filter(([name, value]) =>
            typeof value === 'string' && !['event_check_token', 'confirm_duplicate'].includes(name)));
        let busy = false;
        dialog.querySelector('[data-continue]').addEventListener('click', () => dialog.close('continue'));
        dialog.querySelector('[data-cancel]').addEventListener('click', () => dialog.close('cancel'));
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (busy) return;
            busy = true;
            submit.disabled = true;
            form.elements.event_check_token.value = '';
            form.elements.confirm_duplicate.value = '0';
            status.textContent = 'Controllo di data e ora nei database in corso…';
            try {
                const payload = new FormData(form);
                const original = snapshot();
                // Il file rimane nel form e viene caricato una sola volta, dopo la conferma.
                payload.delete('immagine');
                const url = new URL(<?php echo json_encode(\Joomla\CMS\Uri\Uri::root() . 'index.php', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
                url.search = new URLSearchParams({option: 'com_ajax', module: 'insert_event', method: 'check', format: 'json', Itemid: <?php echo (int) ($app->getMenu()->getActive()->id ?? 0); ?>});
                const response = await fetch(url, {method: 'POST', body: payload, credentials: 'same-origin'});
                const result = await response.json();
                if (!response.ok || !result.success || !result.data || !result.data.token) {
                    throw new Error(result.message || 'Verifica non riuscita. Nessun evento inserito.');
                }
                const checked = result.data;
                if (snapshot() !== original) throw new Error('Dati modificati durante il controllo. Premi nuovamente Submit per verificarli.');
                let confirmed = false;
                if (checked.needs_confirmation) {
                    const lines = ['Data e ora: ' + checked.data + ' ' + checked.ora + ' UTC.'];
                    lines.push(checked.local_ids.length
                        ? 'Database locale: evento già presente. ID: ' + checked.local_ids.join(', ') + '.'
                        : 'Database locale: nessun evento con la stessa data e ora.');
                    if (checked.serenade.status === 'found') {
                        lines.push('Serenade: evento già presente. ID: ' + checked.serenade.ids.join(', ') + '.');
                    } else if (checked.serenade.status === 'not_found') {
                        lines.push('Serenade: nessun evento con la stessa data e ora.');
                    } else {
                        lines.push('Serenade: presenza non verificata. ' + checked.serenade.detail);
                    }
                    lines.push('Il confronto è effettuato al secondo; le frazioni di secondo Serenade sono ignorate.');
                    dialog.querySelector('[data-check-details]').textContent = lines.join('\n\n');
                    dialog.returnValue = '';
                    const decision = new Promise(resolve => dialog.addEventListener('close', () => resolve(dialog.returnValue === 'continue'), {once: true}));
                    dialog.showModal();
                    confirmed = await decision;
                    if (!confirmed) {
                        status.textContent = 'Inserimento annullato. Nessun evento o immagine salvato; i dati compilati sono rimasti nel modulo.';
                        return;
                    }
                }
                if (snapshot() !== original) throw new Error('Dati modificati dopo il controllo. Premi nuovamente Submit per verificarli.');
                form.elements.event_check_token.value = checked.token;
                form.elements.confirm_duplicate.value = confirmed ? '1' : '0';
                status.textContent = 'Inserimento in corso…';
                HTMLFormElement.prototype.submit.call(form);
            } catch (error) {
                status.textContent = 'Inserimento non eseguito: ' + error.message;
            } finally {
                busy = false;
                submit.disabled = false;
            }
        });
    })();
    </script>
	</body>


<?php
if (isset($esito)) :

?>

<div>

	<?php echo $esito === true ? "Inserimento avvenuto correttamente!" : "Errore: $esito"; ?>
</div>
<?php
endif;

?>
