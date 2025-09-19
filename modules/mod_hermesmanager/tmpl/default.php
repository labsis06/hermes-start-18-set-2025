<?php defined('_JEXEC') or die; ?>
<div class="hermesmanager">
<form method="post">
    <fieldset>
        <legend>Carica Evento</legend>
        <input type="number" name="event_id" placeholder="ID evento" required>
        <input type="hidden" name="task" value="load">
        <button class="btn btn-primary">Carica</button>
    </fieldset>
</form>

<?php if (!empty($message)) echo "<p class='alert alert-info mt-3'>$message</p>"; ?>

<?php if (!empty($evento)): ?>
<form method="post">
    <fieldset>
        <legend>Modifica Evento</legend>
        <input type="hidden" name="task" value="update">
        <input type="hidden" name="id" value="<?php echo $evento['id']; ?>">
        <div class="form-group">
<label>ID</label>
<input class="form-control" name="id" value="<?php echo $evento['id']; ?>" readonly>
</div>
<?php foreach ($evento as $k => $v): if ($k === 'id') continue; ?>
            <div class="form-group">
                <label><?php echo $k; ?></label>
                <input class="form-control" name="<?php echo $k; ?>" value="<?php echo htmlspecialchars($v); ?>">
            </div>
        <?php endforeach; ?>
        <button class="btn btn-success mt-2">Salva modifiche</button>
    </fieldset>
</form>

<form method="post" onsubmit="return confirm('Sei sicuro di voler eliminare questo evento?');">
    <input type="hidden" name="task" value="delete">
    <input type="hidden" name="event_id" value="<?php echo $evento['id']; ?>">
    <button class="btn btn-danger mt-2">Elimina evento</button>
</form>

<h4>Immagini associate</h4>
<ul>
<?php foreach ($immagini as $img): ?>
    <li>
        <img src="/<?php echo $img['percorso_file']; ?>" width="150" style="margin:5px;">
        <form method="post" style="display:inline;">
            <input type="hidden" name="task" value="delete_img">
            <input type="hidden" name="img_id" value="<?php echo $img['id']; ?>">
            <input type="hidden" name="event_id" value="<?php echo $evento['id']; ?>">
            <button class="btn btn-sm btn-danger">Elimina</button>
        </form>
    </li>
<?php endforeach; ?>
</ul>

<form method="post" enctype="multipart/form-data">
    <h5>Aggiungi immagine</h5>
    <input type="file" name="new_image" required>
    <input type="hidden" name="task" value="upload_img">
    <input type="hidden" name="event_id" value="<?php echo $evento['id']; ?>">
    <button class="btn btn-primary btn-sm">Carica</button>
</form>
<?php endif; ?>
</div>
