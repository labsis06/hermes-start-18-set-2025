<?php defined('_JEXEC') or die;
$db = JFactory::getDbo();
$input = JFactory::getApplication()->input;

// ---------- SEZIONE IMMAGINI (come prima, lasciata intatta) ----------
// UPLOAD, ELIMINAZIONE, SOSTITUZIONE IMMAGINI
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

// Elimina immagine
if (isset($_POST['delete_image_id'])) {
    $deleteId = (int) $_POST['delete_image_id'];
    $query = $db->getQuery(true)
        ->select('percorso_file')
        ->from($db->qn('hermes_immagini'))
        ->where('id = ' . $deleteId);
    $db->setQuery($query);
    $path = $db->loadResult();
    if ($path && file_exists(JPATH_ROOT . '/' . $path)) unlink(JPATH_ROOT . '/' . $path);
    $query = $db->getQuery(true)
        ->delete($db->qn('hermes_immagini'))
        ->where('id = ' . $deleteId);
    $db->setQuery($query)->execute();
    echo '<div class="alert alert-success mt-2">✅ Immagine eliminata.</div>';
}

// Sostituzione immagine
if (isset($_POST['replace_image_id'])) {
    $newFile = $input->files->get('file_evento', [], 'array');
    if ($newFile && isset($newFile['error']) && $newFile['error'] === UPLOAD_ERR_OK) {
        $replaceId = (int) $_POST['replace_image_id'];
        $query = $db->getQuery(true)
            ->select('percorso_file')
            ->from($db->qn('hermes_immagini'))
            ->where('id = ' . $replaceId);
        $db->setQuery($query);
        $oldPath = $db->loadResult();
        if ($oldPath && file_exists(JPATH_ROOT . '/' . $oldPath)) unlink(JPATH_ROOT . '/' . $oldPath);
        $uploadDir = 'images/eventi/';
        $uploadPath = JPATH_ROOT . '/' . $uploadDir;
        if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
        $newName = basename($newFile['name']);
        $newPath = $uploadDir . $newName;
        if (move_uploaded_file($newFile['tmp_name'], $uploadPath . '/' . $newName)) {
            $query = $db->getQuery(true)
                ->update($db->qn('hermes_immagini'))
                ->set([
                    $db->qn('nome_file') . ' = ' . $db->quote($newName),
                    $db->qn('percorso_file') . ' = ' . $db->quote($newPath)
                ])
                ->where('id = ' . $replaceId);
            $db->setQuery($query)->execute();
            echo '<div class="alert alert-success mt-2">✅ Immagine sostituita.</div>';
        } else {
            echo '<div class="alert alert-danger mt-2">❌ Errore nel caricamento del nuovo file.</div>';
        }
    } else {
        echo '<div class="alert alert-danger mt-2">❌ Nessun file selezionato o errore nell\'upload.</div>';
    }
}

// CARICA LISTA IMMAGINI
$query = $db->getQuery(true)
    ->select('*')
    ->from($db->qn('hermes_immagini'));
$db->setQuery($query);
$images = $db->loadObjectList();


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

// ELIMINAZIONE FILE GENERICO
if (isset($_POST['delete_file_id'])) {
    $deleteId = (int) $_POST['delete_file_id'];
    $query = $db->getQuery(true)
        ->select('percorso_file')
        ->from($db->qn('hermes_files'))
        ->where('id = ' . $deleteId);
    $db->setQuery($query);
    $path = $db->loadResult();
    if ($path && file_exists(JPATH_ROOT . '/' . $path)) unlink(JPATH_ROOT . '/' . $path);
    $query = $db->getQuery(true)
        ->delete($db->qn('hermes_files'))
        ->where('id = ' . $deleteId);
    $db->setQuery($query)->execute();
    echo '<div class="alert alert-success mt-2">✅ File eliminato.</div>';
}

// SOSTITUZIONE FILE GENERICO
if (isset($_POST['replace_file_id'])) {
    $newFile = $input->files->get('file_generico_evento', [], 'array');
    if ($newFile && isset($newFile['error']) && $newFile['error'] === UPLOAD_ERR_OK) {
        $replaceId = (int) $_POST['replace_file_id'];
        $query = $db->getQuery(true)
            ->select('percorso_file')
            ->from($db->qn('hermes_files'))
            ->where('id = ' . $replaceId);
        $db->setQuery($query);
        $oldPath = $db->loadResult();
        if ($oldPath && file_exists(JPATH_ROOT . '/' . $oldPath)) unlink(JPATH_ROOT . '/' . $oldPath);
        $uploadDir = 'files/eventi/';
        $uploadPath = JPATH_ROOT . '/' . $uploadDir;
        if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
        $newName = basename($newFile['name']);
        $newPath = $uploadDir . $newName;
        if (move_uploaded_file($newFile['tmp_name'], $uploadPath . '/' . $newName)) {
            $query = $db->getQuery(true)
                ->update($db->qn('hermes_files'))
                ->set([
                    $db->qn('nome_file') . ' = ' . $db->quote($newName),
                    $db->qn('percorso_file') . ' = ' . $db->quote($newPath)
                ])
                ->where('id = ' . $replaceId);
            $db->setQuery($query)->execute();
            echo '<div class="alert alert-success mt-2">✅ File sostituito.</div>';
        } else {
            echo '<div class="alert alert-danger mt-2">❌ Errore nel caricamento del nuovo file.</div>';
        }
    } else {
        echo '<div class="alert alert-danger mt-2">❌ Nessun file selezionato o errore nell\'upload.</div>';
    }
}

// CARICA LISTA FILE
$query = $db->getQuery(true)
    ->select('*')
    ->from($db->qn('hermes_files'));
$db->setQuery($query);
$files_generici = $db->loadObjectList();
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

    <?php if ($images): ?>
        <fieldset class="border p-3">
            <legend class="mb-3">Gestione immagini caricate</legend>
            <div class="row">
                <?php foreach ($images as $img): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <img src="/<?= htmlspecialchars($img->percorso_file) ?>" class="card-img-top" style="object-fit:cover; height:200px;">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($img->nome_file) ?></h5>
                                <p class="card-text"><strong>ID Evento:</strong> <?= (int)$img->id_evento ?></p>
                                <form method="post" enctype="multipart/form-data" class="mb-2">
                                    <input type="hidden" name="replace_image_id" value="<?= (int)$img->id ?>">
                                    <input type="file" name="file_evento" class="form-control mb-2" required accept="image/*">
                                    <button type="submit" class="btn btn-warning w-100">Sostituisci</button>
                                </form>
                                <form method="post">
                                    <input type="hidden" name="delete_image_id" value="<?= (int)$img->id ?>">
                                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Sicuro di voler eliminare questa immagine?');">Elimina</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </fieldset>
    <?php else: ?>
        <div class="alert alert-info">Nessuna immagine trovata nel database.</div>
    <?php endif; ?>
</div>

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

    <?php if ($files_generici): ?>
        <fieldset class="border p-3">
            <legend class="mb-3">Gestione file generici caricati</legend>
            <div class="row">
                <?php foreach ($files_generici as $f): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($f->nome_file) ?></h5>
                                <p class="card-text"><strong>ID Evento:</strong> <?= (int)$f->id_evento ?></p>
                                <a href="/<?= htmlspecialchars($f->percorso_file) ?>" target="_blank" class="btn btn-info mb-2 w-100">Scarica/Visualizza</a>
                                <form method="post" enctype="multipart/form-data" class="mb-2">
                                    <input type="hidden" name="replace_file_id" value="<?= (int)$f->id ?>">
                                    <input type="file" name="file_generico_evento" class="form-control mb-2" required>
                                    <button type="submit" class="btn btn-warning w-100">Sostituisci</button>
                                </form>
                                <form method="post">
                                    <input type="hidden" name="delete_file_id" value="<?= (int)$f->id ?>">
                                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Sicuro di voler eliminare questo file?');">Elimina</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </fieldset>
    <?php else: ?>
        <div class="alert alert-info">Nessun file generico trovato nel database.</div>
    <?php endif; ?>
</div>