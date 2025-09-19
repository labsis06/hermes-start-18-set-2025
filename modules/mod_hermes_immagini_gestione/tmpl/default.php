<?php defined('_JEXEC') or die;
$db = JFactory::getDbo();
$input = JFactory::getApplication()->input;


// ELIMINAZIONE, SOSTITUZIONE IMMAGINI


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

?>

<div class="hermesimporter mt-4">
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

   