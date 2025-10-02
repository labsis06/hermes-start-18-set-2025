<?php defined('_JEXEC') or die;
$db = JFactory::getDbo();
$input = JFactory::getApplication()->input;


// ELIMINAZIONE PUBBLICAZIONI
if (isset($_POST['delete_pubblicazione_id'])) {
    $deleteId = (int) $_POST['delete_pubblicazione_id'];
    $query = $db->getQuery(true)
        ->select('percorso_file')
        ->from($db->qn('hermes_pubblicazioni'))
        ->where('id = ' . $deleteId);
    $db->setQuery($query);
    $path = $db->loadResult();
    if ($path && file_exists(JPATH_ROOT . '/' . $path)) unlink(JPATH_ROOT . '/' . $path);
    $query = $db->getQuery(true)
        ->delete($db->qn('hermes_pubblicazioni'))
        ->where('id = ' . $deleteId);
    $db->setQuery($query)->execute();
    echo '<div class="alert alert-success mt-2">✅ File eliminato.</div>';
}

// SOSTITUZIONE PUBBLICAZIONE
if (isset($_POST['replace_pubblicazione_id'])) {
    $newFile = $input->files->get('pubblicazione_evento', [], 'array');
    if ($newFile && isset($newFile['error']) && $newFile['error'] === UPLOAD_ERR_OK) {
        $replaceId = (int) $_POST['replace_pubblicazione_id'];
        $query = $db->getQuery(true)
            ->select('percorso_file')
            ->from($db->qn('hermes_pubblicazioni'))
            ->where('id = ' . $replaceId);
        $db->setQuery($query);
        $oldPath = $db->loadResult();
        if ($oldPath && file_exists(JPATH_ROOT . '/' . $oldPath)) unlink(JPATH_ROOT . '/' . $oldPath);
        $uploadDir = 'files/pubblicazioni/';
        $uploadPath = JPATH_ROOT . '/' . $uploadDir;
        if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
        $newName = basename($newFile['name']);
        $newPath = $uploadDir . $newName;
        if (move_uploaded_file($newFile['tmp_name'], $uploadPath . '/' . $newName)) {
            $query = $db->getQuery(true)
                ->update($db->qn('hermes_pubblicazioni'))
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
    ->from($db->qn('hermes_pubblicazioni'));
$db->setQuery($query);
$pubblicazioni = $db->loadObjectList();
?>


<div class="hermesimporter mt-4">
    

    <?php if ($pubblicazioni): ?>
        <fieldset class="border p-3">
            <legend class="mb-3">Gestione file generici caricati</legend>
            <div class="row">
                <?php foreach ($pubblicazioni as $p): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($p->nome_file) ?></h5>
                                <p class="card-text"><strong>ID Evento:</strong> <?= (int)$p->id_evento ?></p>
                                <a href="/<?= htmlspecialchars($p->percorso_file) ?>" target="_blank" class="btn btn-info mb-2 w-100">Scarica/Visualizza</a>
                                <form method="post" enctype="multipart/form-data" class="mb-2">
                                    <input type="hidden" name="replace_pubblicazione_id" value="<?= (int)$p->id ?>">
                                    <input type="file" name="pubblicazione_evento" class="form-control mb-2" required>
                                    <button type="submit" class="btn btn-warning w-100">Sostituisci</button>
                                </form>
                                <form method="post">
                                    <input type="hidden" name="delete_pubblicazione_id" value="<?= (int)$p->id ?>">
                                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Sicuro di voler eliminare questo file?');">Elimina</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </fieldset>
    <?php else: ?>
        <div class="alert alert-info">Nessuna pubblicazione trovata nel database.</div>
    <?php endif; ?>
</div>
