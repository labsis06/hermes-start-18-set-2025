<?php
defined('_JEXEC') or die;
$escape = static fn($value) => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$managerId = (int) $module->id;
$token = \Joomla\CMS\HTML\HTMLHelper::_('form.token');
?>
<div class="hermesmanager">
<form method="post">
    <fieldset>
        <legend>Modifica un evento esistente</legend>
        <label for="manager-event-id-<?php echo $managerId; ?>">ID evento (visibile nei dettagli della vcard)</label>
        <input id="manager-event-id-<?php echo $managerId; ?>" type="number" name="event_id" min="1" placeholder="ID evento" required value="<?php echo $id > 0 ? (int) $id : ''; ?>">
        <input type="hidden" name="hermesmanager_task" value="load">
        <input type="hidden" name="manager_id" value="<?php echo $managerId; ?>">
        <?php echo $token; ?>
        <button class="btn btn-primary">Carica evento</button>
    </fieldset>
</form>

<?php if ($message !== ''): ?>
    <p class="alert alert-<?php echo $escape($messageType); ?> mt-3" role="status"><?php echo $escape($message); ?></p>
<?php endif; ?>

<?php if ($evento): ?>
<form method="post" class="hermes-event-edit">
    <fieldset>
        <legend>Informazioni dell’evento #<?php echo (int) $evento['id']; ?></legend>
        <p>Modifica i valori o svuota i campi facoltativi per cancellarne le informazioni. I campi con * sono obbligatori.</p>
        <input type="hidden" name="hermesmanager_task" value="update">
        <input type="hidden" name="manager_id" value="<?php echo $managerId; ?>">
        <input type="hidden" name="event_id" value="<?php echo (int) $evento['id']; ?>">
        <?php echo $token; ?>
        <?php foreach (ModHermesManagerHelper::getCampiModificabili() as $field => [$label, $type, $required]): ?>
            <?php $fieldId = 'manager-' . $managerId . '-' . $field; ?>
            <div class="form-group">
                <label for="<?php echo $escape($fieldId); ?>"><?php echo $escape($label); ?><?php echo $required ? ' *' : ''; ?></label>
                <?php if ($type === 'textarea'): ?>
                    <textarea id="<?php echo $escape($fieldId); ?>" class="form-control" name="<?php echo $escape($field); ?>" rows="4"><?php echo $escape($evento[$field] ?? ''); ?></textarea>
                <?php else: ?>
                    <input id="<?php echo $escape($fieldId); ?>" class="form-control" type="<?php echo $type === 'integer' ? 'number' : $escape($type); ?>"
                        name="<?php echo $escape($field); ?>" value="<?php echo $escape($evento[$field] ?? ''); ?>"
                        <?php echo $required ? 'required' : ''; ?>
                        <?php echo $type === 'integer' ? 'step="1" min="0"' : ($type === 'number' ? 'step="any"' : ($type === 'time' ? 'step="1"' : '')); ?>>
                <?php endif; ?>
                <?php if ($field === 'link_aggiuntivi'): ?>
                    <small>Un URL completo http:// o https:// per riga. Per rimuovere un link cancella la riga; per rimuoverli tutti lascia il campo vuoto.</small>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <button class="btn btn-success mt-2">Salva modifiche</button>
    </fieldset>
</form>

<form method="post" onsubmit="return confirm('Eliminare definitivamente questo evento e tutte le associazioni a immagini, pubblicazioni e file?');">
    <input type="hidden" name="hermesmanager_task" value="delete">
    <input type="hidden" name="manager_id" value="<?php echo $managerId; ?>">
    <input type="hidden" name="event_id" value="<?php echo (int) $evento['id']; ?>">
    <?php echo $token; ?>
    <button class="btn btn-danger mt-2">Elimina intero evento</button>
</form>

<h4>Immagini associate</h4>
<?php if (!$immagini): ?><p>Nessuna immagine associata.</p><?php endif; ?>
<ul>
<?php foreach ($immagini as $img): ?>
    <li>
        <img src="<?php echo $escape(\Joomla\CMS\Uri\Uri::root() . ltrim($img['percorso_file'], '/')); ?>" width="150" alt="Immagine evento" style="margin:5px;">
        <form method="post" style="display:inline;" onsubmit="return confirm('Eliminare questa immagine?');">
            <input type="hidden" name="hermesmanager_task" value="delete_img">
            <input type="hidden" name="manager_id" value="<?php echo $managerId; ?>">
            <input type="hidden" name="img_id" value="<?php echo (int) $img['id']; ?>">
            <input type="hidden" name="event_id" value="<?php echo (int) $evento['id']; ?>">
            <?php echo $token; ?>
            <button class="btn btn-sm btn-danger">Elimina immagine</button>
        </form>
    </li>
<?php endforeach; ?>
</ul>

<form method="post" enctype="multipart/form-data">
    <h5>Aggiungi immagine</h5>
    <input type="file" name="new_image" required>
    <input type="hidden" name="hermesmanager_task" value="upload_img">
    <input type="hidden" name="manager_id" value="<?php echo $managerId; ?>">
    <input type="hidden" name="event_id" value="<?php echo (int) $evento['id']; ?>">
    <?php echo $token; ?>
    <button class="btn btn-primary btn-sm">Carica immagine</button>
</form>
<?php endif; ?>
</div>
