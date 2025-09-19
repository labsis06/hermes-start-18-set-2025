<?php defined('_JEXEC') or die; ?>
<div class="hermesimporter">
    <form method="post" enctype="multipart/form-data" class="mt-3">
        <fieldset class="border p-3">
            <legend class="mb-3">Importa Evento da Wessel</legend>
            <div class="form-group mb-3">
                <label for="eventid">ID Evento</label>
                <input type="number" name="eventid" id="eventid" class="form-control" required>
            </div>
            <div class="form-group mb-3">
                <label for="image_file">Immagine associata (opzionale)</label>
                <input type="file" name="image_file" id="image_file" class="form-control" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary">Importa</button>
        </fieldset>
    </form>
</div>
