<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

/** Allegati del form: validazione prima dell'INSERT e salvataggio nello stesso evento. */
class ModInsertEventAttachments
{
    public const GROUPS = [
        'immagine' => ['label' => 'Immagini', 'table' => 'hermes_immagini', 'directory' => 'images/eventi/'],
        'pubblicazione' => ['label' => 'Pubblicazioni', 'table' => 'hermes_pubblicazioni', 'directory' => 'files/pubblicazioni/'],
        'file_generico' => ['label' => 'File generici', 'table' => 'hermes_files', 'directory' => 'files/eventi/'],
    ];

    public static function bytes(string $value): int
    {
        if (!preg_match('/^(\d+(?:\.\d+)?)\s*([KMG]?)B?$/i', trim($value), $match)) return 0;
        return (int) ((float) $match[1] * (1024 ** (['' => 0, 'K' => 1, 'M' => 2, 'G' => 3][strtoupper($match[2])])));
    }

    /** Supporta sia il singolo file precedente sia i due formati PHP/Joomla multipli. */
    public static function normalizza(array $files): array
    {
        if (!$files) return [];
        if (isset($files['name']) && is_string($files['name'])) return [$files];
        if (isset($files['name']) && is_array($files['name'])) {
            $normalized = [];
            foreach (array_keys($files['name']) as $index) {
                $file = [];
                foreach (['name', 'tmp_name', 'error', 'size', 'type'] as $field) $file[$field] = $files[$field][$index] ?? null;
                $normalized[] = $file;
            }
            return $normalized;
        }
        foreach ($files as $file) {
            if (!is_array($file) || !isset($file['name'], $file['error'])) {
                throw new \InvalidArgumentException('Elenco degli allegati non valido. Riselezionare i file.');
            }
        }
        return array_values($files);
    }

    public static function prepara($fileInput, int $expectedCount = -1): array
    {
        $prepared = [];
        foreach (self::GROUPS as $field => $group) {
            foreach (self::normalizza($fileInput->get($field, [], 'raw')) as $file) {
                if ((int) ($file['error'] ?? -1) === UPLOAD_ERR_NO_FILE) continue;
                $name = basename(str_replace('\\', '/', (string) ($file['name'] ?? '')));
                $name = preg_replace('/[\x00-\x1f\x7f]/u', '', $name) ?? '';
                $prefix = $group['label'] . ': ';
                $error = (int) ($file['error'] ?? -1);
                if ($error !== UPLOAD_ERR_OK) {
                    $detail = [
                        UPLOAD_ERR_INI_SIZE => 'un file supera il limite di dimensione del server.',
                        UPLOAD_ERR_FORM_SIZE => 'un file supera il limite di dimensione del form.',
                        UPLOAD_ERR_PARTIAL => 'un file è arrivato solo parzialmente.',
                        UPLOAD_ERR_NO_TMP_DIR => 'cartella temporanea del server non disponibile.',
                        UPLOAD_ERR_CANT_WRITE => 'il server non riesce a scrivere il file.',
                        UPLOAD_ERR_EXTENSION => 'il caricamento è stato interrotto dal server.',
                    ][$error] ?? 'caricamento non valido.';
                    throw new \InvalidArgumentException($prefix . $detail);
                }
                $tmp = $file['tmp_name'] ?? '';
                if ($name === '' || mb_strlen($name, 'UTF-8') > 255 || !is_string($tmp) || !is_uploaded_file($tmp)) {
                    throw new \InvalidArgumentException($prefix . 'nome o caricamento non valido. Riselezionare i file.');
                }
                $size = filesize($tmp);
                $limit = self::bytes((string) ini_get('upload_max_filesize'));
                if ($size === false || $size < 1 || ($limit > 0 && $size > $limit)) {
                    throw new \InvalidArgumentException($prefix . 'file vuoto o troppo grande.');
                }
                // I documenti sono conservati come .bin, senza estensioni eseguibili;
                // il nome originale viene mantenuto nel database e nel download.
                $extension = 'bin';
                if ($field === 'immagine') {
                    $info = @getimagesize($tmp);
                    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp', 'image/bmp' => 'bmp'];
                    $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
                    if (in_array($mime, ['image/x-ms-bmp', 'image/x-bmp'], true)) $mime = 'image/bmp';
                    if (!$info || !isset($extensions[$info['mime'] ?? '']) || $mime !== $info['mime']) {
                        throw new \InvalidArgumentException('Immagini: selezionare soltanto immagini JPEG, PNG, GIF, WebP o BMP valide.');
                    }
                    $extension = $extensions[$info['mime']];
                }
                $prepared[] = ['field' => $field, 'name' => $name, 'tmp' => $tmp, 'extension' => $extension];
            }
        }
        if ($expectedCount >= 0 && count($prepared) !== $expectedCount) {
            throw new \InvalidArgumentException('Non sono arrivati tutti gli allegati selezionati. Controllare il limite del server e riselezionare i file.');
        }
        $maxFiles = (int) ini_get('max_file_uploads');
        if ($maxFiles > 0 && count($prepared) > $maxFiles) {
            throw new \InvalidArgumentException('Troppi allegati: il server accetta al massimo ' . $maxFiles . ' file per invio.');
        }
        return $prepared;
    }

    /** Il chiamante mantiene la transazione DB e pulisce i file anche se il COMMIT fallisce. */
    public static function salva(int $eventId, array $prepared, array &$written): void
    {
        $db = Factory::getDbo();
        foreach ($prepared as $file) {
            $group = self::GROUPS[$file['field']];
            $directory = JPATH_ROOT . '/' . $group['directory'];
            if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new \RuntimeException('Impossibile preparare la cartella degli allegati.');
            }
            $relative = $group['directory'] . bin2hex(random_bytes(16)) . '.' . $file['extension'];
            $target = JPATH_ROOT . '/' . $relative;
            if (!move_uploaded_file($file['tmp'], $target)) {
                throw new \RuntimeException('Salvataggio di un allegato non riuscito.');
            }
            $written[] = $target;
            $record = (object) ['id_evento' => $eventId, 'nome_file' => $file['name'], 'percorso_file' => $relative];
            $db->insertObject($group['table'], $record);
        }
    }

    public static function annulla(array $written): void
    {
        foreach ($written as $path) {
            if (is_file($path) && !unlink($path)) {
                \Joomla\CMS\Log\Log::add('Rimozione allegato temporaneo non riuscita.', \Joomla\CMS\Log\Log::ERROR, 'mod_insert_event');
            }
        }
    }
}
