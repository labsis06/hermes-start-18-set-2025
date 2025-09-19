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
<form method="post" enctype="multipart/form-data">
	<input type="hidden" name="mod_id" value="<?php echo $module->id; ?>" />
    <?php echo JHtml::_('form.token'); ?>
  <div class="form-group row">
				<label for="data" class="col-4 col-form-label">Data</label>
				<div class="col-8">
					<input id="data" name="data" type="date" class="form-control" required="required">
				</div>
			</div>
			<div class="form-group row">
				<label for="ora" class="col-4 col-form-label">Ora</label>
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
						<option value="esplosione">esplosione</option>
						<option value="frana">frana</option>
						<option value="telesisma">telesisma</option>
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
