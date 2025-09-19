<?php defined('_JEXEC') or die;
$db = JFactory::getDbo();
$input = JFactory::getApplication()->input;

// ---------- SEZIONE FILE GENERICI ----------

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


<div class="hermesimporter mt-4">
    

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