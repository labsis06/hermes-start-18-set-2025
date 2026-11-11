<?php
defined('_JEXEC') or die;
$escape = static fn($value) => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$managerId = (int) $module->id;
$token = \Joomla\CMS\HTML\HTMLHelper::_('form.token');
?>
<div class="hermesmanager" id="hermesmanager-<?php echo $managerId; ?>">
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

<?php
$attachmentFields = static function (string $task, string $type, int $attachmentId = 0) use ($managerId, $evento, $token, $escape): void {
    echo '<input type="hidden" name="hermesmanager_task" value="' . $escape($task) . '">';
    echo '<input type="hidden" name="manager_id" value="' . $managerId . '">';
    echo '<input type="hidden" name="event_id" value="' . (int) $evento['id'] . '">';
    echo '<input type="hidden" name="attachment_type" value="' . $escape($type) . '">';
    if ($attachmentId) echo '<input type="hidden" name="attachment_id" value="' . $attachmentId . '">';
    echo $token;
};
$headings = ['immagine' => 'Immagini associate', 'pubblicazione' => 'Pubblicazioni associate', 'file_generico' => 'File generici associati'];
?>
<p>Puoi aggiungere più allegati, rinominarli, sostituirli o eliminarli. Salva prima eventuali modifiche ai dati dell’evento.</p>
<p>Limiti per caricamento: <?php echo (int) ini_get('max_file_uploads'); ?> file,
    <?php echo $escape(ini_get('upload_max_filesize')); ?> per file,
    <?php echo $escape(ini_get('post_max_size')); ?> totali.</p>
<?php foreach (ModInsertEventAttachments::GROUPS as $type => $group): ?>
<section class="hermes-attachments" data-type="<?php echo $escape($type); ?>">
    <h4><?php echo $escape($headings[$type]); ?></h4>
    <?php if (!$allegati[$type]): ?><p>Nessun allegato presente in questa sezione.</p><?php endif; ?>
    <?php foreach ($allegati[$type] as $attachment): ?>
        <?php
        $attachmentId = (int) $attachment['id'];
        $name = $attachment['nome_file'] ?: 'allegato';
        $url = \Joomla\CMS\Uri\Uri::root() . ltrim($attachment['percorso_file'], '/');
        $fieldId = 'manager-file-' . $managerId . '-' . $type . '-' . $attachmentId;
        ?>
        <div class="hermes-attachment" data-attachment-id="<?php echo $attachmentId; ?>" style="border:1px solid #ddd; padding:12px; margin:12px 0;">
            <?php if ($type === 'immagine'): ?>
                <img src="<?php echo $escape($url); ?>" width="150" alt="<?php echo $escape($name); ?>" style="margin:5px;">
            <?php endif; ?>
            <p><a href="<?php echo $escape($url); ?>" download="<?php echo $escape($name); ?>"><?php echo $escape($name); ?></a></p>
            <form method="post" class="hermes-attachment-rename">
                <?php $attachmentFields('rename_attachment', $type, $attachmentId); ?>
                <label for="<?php echo $escape($fieldId . '-name'); ?>">Nome del file</label>
                <input id="<?php echo $escape($fieldId . '-name'); ?>" class="form-control" name="attachment_name" maxlength="255" value="<?php echo $escape($name); ?>" required>
                <small>Questo nome sarà usato anche per il download: mantieni l’estensione corretta del file.</small>
                <button class="btn btn-secondary btn-sm">Rinomina</button>
            </form>
            <form method="post" enctype="multipart/form-data" class="hermes-attachment-upload" data-single="1">
                <?php $attachmentFields('replace_attachment', $type, $attachmentId); ?>
                <input type="hidden" name="attachment_count" value="-1">
                <label for="<?php echo $escape($fieldId . '-replacement'); ?>">Sostituisci con un nuovo file</label>
                <input id="<?php echo $escape($fieldId . '-replacement'); ?>" type="file" name="<?php echo $escape($type); ?>[]"
                    <?php echo $type === 'immagine' ? 'accept="image/jpeg,image/png,image/gif,image/webp,image/bmp"' : ''; ?> required>
                <button class="btn btn-primary btn-sm">Sostituisci</button>
                <ul class="attachment-selection"></ul>
                <p class="attachment-status" role="status"></p>
            </form>
            <form method="post" class="hermes-attachment-delete" onsubmit="return confirm('Eliminare questo allegato? La copia sul server verrà rimossa se non è utilizzata da altri allegati.');">
                <?php $attachmentFields('delete_attachment', $type, $attachmentId); ?>
                <button class="btn btn-danger btn-sm">Elimina allegato</button>
            </form>
        </div>
    <?php endforeach; ?>
    <form method="post" enctype="multipart/form-data" class="hermes-attachment-upload hermes-attachment-add" data-single="0">
        <?php $attachmentFields('upload_attachment', $type); ?>
        <input type="hidden" name="attachment_count" value="-1">
        <label for="manager-add-<?php echo $managerId . '-' . $escape($type); ?>">Aggiungi <?php echo $escape(strtolower($group['label'])); ?></label>
        <input id="manager-add-<?php echo $managerId . '-' . $escape($type); ?>" type="file" name="<?php echo $escape($type); ?>[]" multiple
            <?php echo $type === 'immagine' ? 'accept="image/jpeg,image/png,image/gif,image/webp,image/bmp"' : ''; ?> required>
        <button class="btn btn-primary">Carica allegati</button>
        <ul class="attachment-selection"></ul>
        <p class="attachment-status" role="status"></p>
    </form>
</section>
<?php endforeach; ?>
<script>
(() => {
    const root = document.getElementById('hermesmanager-<?php echo $managerId; ?>');
    const maxFiles = <?php echo (int) ini_get('max_file_uploads'); ?>;
    const maxFileBytes = <?php echo ModInsertEventAttachments::bytes((string) ini_get('upload_max_filesize')); ?>;
    const maxTotalBytes = <?php echo ModInsertEventAttachments::bytes((string) ini_get('post_max_size')); ?>;
    root.querySelectorAll('.hermes-attachment-upload').forEach(form => {
        const input = form.querySelector('input[type=file]');
        input.addEventListener('change', () => {
            form.querySelector('.attachment-selection').replaceChildren(...[...input.files].map(file => {
                const li = document.createElement('li'); li.textContent = file.name; return li;
            }));
        });
        form.addEventListener('submit', event => {
            const files = [...input.files];
            let error = '';
            if (!files.length) error = 'Selezionare almeno un file.';
            else if (form.dataset.single === '1' && files.length !== 1) error = 'Per sostituire un allegato selezionare un solo file.';
            else if (maxFiles > 0 && files.length > maxFiles) error = 'Troppi file: massimo ' + maxFiles + ' per caricamento.';
            else if (maxFileBytes > 0 && files.some(file => file.size > maxFileBytes)) error = 'Un file supera il limite di dimensione.';
            else if (maxTotalBytes > 0 && files.reduce((sum, file) => sum + file.size, 0) > maxTotalBytes) error = 'La dimensione complessiva supera il limite totale.';
            form.querySelector('.attachment-status').textContent = error;
            if (error) event.preventDefault();
            else form.elements.attachment_count.value = files.length;
        });
    });
})();
</script>
<?php endif; ?>
</div>
