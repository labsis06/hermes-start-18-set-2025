<?php defined('_JEXEC') or die;
$db = JFactory::getDbo();
$input = JFactory::getApplication()->input;

// ---------- SEZIONE FILE GENERICI ----------

// UPLOAD FILE GENERICI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_file_event_id'])) {
    $addEventId = (int) $_POST['add_file_event_id'];
    $files = $input->files->get('file_generico', [], 'array');
    $uploadDir = 'files/eventi/';
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
                        ->insert($db->qn('hermes_files'))
                        ->columns([$db->qn('id_evento'), $db->qn('nome_file'), $db->qn('percorso_file')])
                        ->values($db->quote($addEventId) . ', ' . $db->quote($filename) . ', ' . $db->quote($uploadDir . $filename));
                    $db->setQuery($query)->execute();
                    echo '<div class="alert alert-success mt-2">✅ File "' . htmlspecialchars($filename) . '" aggiunto a evento ' . $addEventId . '.</div>';
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
                        ->insert($db->qn('hermes_files'))
                        ->columns([$db->qn('id_evento'), $db->qn('nome_file'), $db->qn('percorso_file')])
                        ->values($db->quote($addEventId) . ', ' . $db->quote($filename) . ', ' . $db->quote($uploadDir . $filename));
                    $db->setQuery($query)->execute();
                    echo '<div class="alert alert-success mt-2">✅ File "' . htmlspecialchars($filename) . '" aggiunto a evento ' . $addEventId . '.</div>';
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

<!-- UPLOAD FILE GENERICI -->
<div class="hermesimporter mt-4">
    <form method="post" enctype="multipart/form-data" class="mb-4">
        <fieldset class="border p-3">
            <legend class="mb-3">Aggiungi file generici a un evento</legend>
            <div class="mb-3">
                <label for="add_file_event_id" class="form-label">ID Evento</label>
                <input type="number" name="add_file_event_id" id="add_file_event_id" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="file_generico" class="form-label">Seleziona file</label>
                <input type="file" name="file_generico[]" id="file_generico" class="form-control" multiple required>
            </div>
            <button type="submit" class="btn btn-primary">Carica File</button>
        </fieldset>
    </form>
</div>