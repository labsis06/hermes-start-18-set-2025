<?php defined('_JEXEC') or die;
$db = JFactory::getDbo();
$input = JFactory::getApplication()->input;


// UPLOAD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event_id'])) {
    $addEventId = (int) $_POST['add_event_id'];
    $files = $input->files->get('immagine', [], 'array');
    $uploadDir = 'images/eventi/';
    $uploadPath = JPATH_ROOT . '/' . $uploadDir;
    if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
    $uploaded = false;

    if (isset($files[0]['name'])) {
        foreach ($files as $file) {
            if (isset($file['error']) && $file['error'] === UPLOAD_ERR_OK && !empty($file['name'])) {
                $filename = basename($file['name']);
                $tmpFile = $file['tmp_name'];
                $targetFile = $uploadPath . '/' . $filename;
                if (move_uploaded_file($tmpFile, $targetFile)) {
                    $query = $db->getQuery(true)
                        ->insert($db->qn('hermes_immagini'))
                        ->columns([$db->qn('id_evento'), $db->qn('nome_file'), $db->qn('percorso_file')])
                        ->values($db->quote($addEventId) . ', ' . $db->quote($filename) . ', ' . $db->quote($uploadDir . $filename));
                    $db->setQuery($query)->execute();
                    echo '<div class="alert alert-success mt-2">✅ Immagine "' . htmlspecialchars($filename) . '" aggiunta a evento ' . $addEventId . '.</div>';
                    $uploaded = true;
                } else {
                    echo '<div class="alert alert-danger mt-2">❌ Errore nel caricamento del file "' . htmlspecialchars($filename) . '"</div>';
                }
            }
        }
    } elseif (isset($files['name']) && is_array($files['name']) && count($files['name']) > 0 && !empty($files['name'][0])) {
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK && !empty($files['name'][$i])) {
                $filename = basename($files['name'][$i]);
                $tmpFile = $files['tmp_name'][$i];
                $targetFile = $uploadPath . '/' . $filename;
                if (move_uploaded_file($tmpFile, $targetFile)) {
                    $query = $db->getQuery(true)
                        ->insert($db->qn('hermes_immagini'))
                        ->columns([$db->qn('id_evento'), $db->qn('nome_file'), $db->qn('percorso_file')])
                        ->values($db->quote($addEventId) . ', ' . $db->quote($filename) . ', ' . $db->quote($uploadDir . $filename));
                    $db->setQuery($query)->execute();
                    echo '<div class="alert alert-success mt-2">✅ Immagine "' . htmlspecialchars($filename) . '" aggiunta a evento ' . $addEventId . '.</div>';
                    $uploaded = true;
                } else {
                    echo '<div class="alert alert-danger mt-2">❌ Errore nel caricamento del file "' . htmlspecialchars($filename) . '"</div>';
                }
            }
        }
    }
    if (!$uploaded) echo '<div class="alert alert-warning mt-2">Nessun file selezionato per l\'upload.</div>';
}
?>

<!-- UPLOAD IMMAGINI -->
<div class="hermesimporter mt-4">
    <form method="post" enctype="multipart/form-data" class="mb-4">
        <fieldset class="border p-3">
            <legend class="mb-3">Aggiungi immagini a un evento</legend>
            <div class="mb-3">
                <label for="add_event_id" class="form-label">ID Evento</label>
                <input type="number" name="add_event_id" id="add_event_id" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="immagine" class="form-label">Seleziona immagini</label>
                <input type="file" name="immagine[]" id="immagine" class="form-control" multiple required accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary">Carica Immagini</button>
        </fieldset>
    </form>

    
</div>

