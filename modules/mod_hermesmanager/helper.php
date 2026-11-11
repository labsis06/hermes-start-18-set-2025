<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
require_once dirname(__DIR__) . '/mod_insert_event/helper.php';
require_once dirname(__DIR__) . '/mod_insert_event/attachments.php';

class ModHermesManagerHelper
{
    public static function getCampiModificabili(): array
    {
        return [
            'data' => ['Data', 'date', true], 'ora' => ['Ora (UTC)', 'time', true],
            'tipo' => ['Tipo di evento', 'text', true], 'area' => ['Area', 'text', true],
            'stazione_first' => ['Stazione first', 'text', false], 'comp1' => ['Componente', 'text', false],
            'Md' => ['Magnitudo Md', 'number', false], 'ML' => ['Magnitudo ML', 'number', false],
            'Mw' => ['Magnitudo Mw', 'number', false], 'prof' => ['Profondità (km)', 'number', false],
            'lat' => ['Latitudine', 'text', false], 'lon' => ['Longitudine', 'text', false],
            'numero_stazioni' => ['Numero stazioni', 'integer', false],
            'H_err' => ['Errore orizzontale', 'number', false], 'V_err' => ['Errore verticale', 'number', false],
            'stazione_near' => ['Distanza stazione più vicina', 'number', false],
            'wessel_id' => ['ID Wessel', 'integer', false], 'wessel_link' => ['Wessel link', 'text', false],
            'link_aggiuntivi' => ['Link aggiuntivi', 'textarea', false], 'note' => ['Note', 'textarea', false],
        ];
    }

    public static function getEvento($id)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
                    ->select('*')
                    ->from($db->qn('hermes_eventi'))
                    ->where($db->qn('id') . ' = ' . (int)$id);
        $db->setQuery($query);
        return $db->loadAssoc();
    }

    public static function getImmagini($id_evento)
    {
        return self::getAllegati((int) $id_evento, 'immagine');
    }

    private static function gruppoAllegati(string $type): array
    {
        if (!isset(ModInsertEventAttachments::GROUPS[$type])) {
            throw new \InvalidArgumentException('Categoria di allegato non valida.');
        }
        return ModInsertEventAttachments::GROUPS[$type];
    }

    public static function getAllegati(int $eventId, string $type): array
    {
        $group = self::gruppoAllegati($type);
        $db = Factory::getDbo();
        $query = $db->getQuery(true)->select('*')->from($db->qn($group['table']))
            ->where($db->qn('id_evento') . ' = ' . $eventId)->order($db->qn('id') . ' ASC');
        return $db->setQuery($query)->loadAssocList();
    }

    private static function bloccaEvento(int $id): void
    {
        $db = Factory::getDbo();
        if ($id < 1 || $db->setQuery('SELECT id FROM hermes_eventi WHERE id=' . $id . ' FOR UPDATE')->loadResult() === null) {
            throw new \DomainException('Evento non trovato. Controllare l’ID.');
        }
    }

    private static function trovaAllegato(int $eventId, string $type, int $attachmentId): array
    {
        $group = self::gruppoAllegati($type);
        $db = Factory::getDbo();
        $query = $db->getQuery(true)->select('*')->from($db->qn($group['table']))
            ->where($db->qn('id') . ' = ' . $attachmentId)->where($db->qn('id_evento') . ' = ' . $eventId);
        $record = $db->setQuery($query)->loadAssoc();
        if (!$record) throw new \DomainException('Allegato non trovato per questo evento.');
        return $record;
    }

    /** Dopo il commit: rimuove solo copie locali valide e non condivise. */
    private static function rimuoviCopiaNonCondivisa(array $record, string $type): string
    {
        try {
            $db = Factory::getDbo();
            $relative = (string) ($record['percorso_file'] ?? '');
            if ($relative === '') return '';
            foreach (ModInsertEventAttachments::GROUPS as $group) {
                $query = $db->getQuery(true)->select($db->qn('id'))->from($db->qn($group['table']))
                    ->where($db->qn('percorso_file') . ' = ' . $db->quote($relative));
                if ($db->setQuery($query, 0, 1)->loadResult() !== null) {
                    return ' La copia condivisa resta disponibile agli altri allegati.';
                }
            }
            $path = JPATH_ROOT . '/' . $relative;
            $resolved = realpath($path);
            if ($resolved === false || !is_file($resolved)) return '';
            $group = self::gruppoAllegati($type);
            $directory = realpath(JPATH_ROOT . '/' . $group['directory']);
            $root = realpath(JPATH_ROOT);
            if (!$directory || !$root || is_link($path)
                || !str_starts_with($directory . '/', $root . '/')
                || !str_starts_with($resolved, $directory . DIRECTORY_SEPARATOR)) {
                return ' Copia sul server conservata: percorso esterno alla cartella degli allegati.';
            }
            if (!@unlink($resolved)) return ' Copia sul server non rimossa: controllare i permessi della cartella.';
        } catch (\Throwable $e) {
            try {
                \Joomla\CMS\Log\Log::add('Pulizia della copia di un allegato non riuscita.', \Joomla\CMS\Log\Log::WARNING, 'mod_hermesmanager');
            } catch (\Throwable $ignored) {}
            return ' Copia sul server conservata: pulizia non completata.';
        }
        return '';
    }

    public static function rinominaAllegato(int $eventId, string $type, int $attachmentId, string $name): string
    {
        $group = self::gruppoAllegati($type);
        $name = trim($name);
        if ($name === '' || $name === '.' || $name === '..' || mb_strlen($name, 'UTF-8') > 255
            || preg_match('/[\x00-\x1f\x7f\/\\\\]/u', $name)) {
            throw new \InvalidArgumentException('Inserire un nome valido, senza cartelle o caratteri di controllo (massimo 255 caratteri).');
        }
        $db = Factory::getDbo();
        $db->transactionStart();
        try {
            self::bloccaEvento($eventId);
            self::trovaAllegato($eventId, $type, $attachmentId);
            $query = $db->getQuery(true)->update($db->qn($group['table']))
                ->set($db->qn('nome_file') . ' = ' . $db->quote($name))
                ->where($db->qn('id') . ' = ' . $attachmentId)->where($db->qn('id_evento') . ' = ' . $eventId);
            $db->setQuery($query)->execute();
            $db->transactionCommit();
        } catch (\Throwable $e) { $db->transactionRollback(); throw $e; }
        return 'Nome dell’allegato aggiornato.';
    }

    public static function eliminaAllegato(int $eventId, string $type, int $attachmentId): string
    {
        $group = self::gruppoAllegati($type);
        $db = Factory::getDbo();
        $db->transactionStart();
        try {
            self::bloccaEvento($eventId);
            $record = self::trovaAllegato($eventId, $type, $attachmentId);
            $query = $db->getQuery(true)->delete($db->qn($group['table']))
                ->where($db->qn('id') . ' = ' . $attachmentId)->where($db->qn('id_evento') . ' = ' . $eventId);
            $db->setQuery($query)->execute();
            $db->transactionCommit();
        } catch (\Throwable $e) { $db->transactionRollback(); throw $e; }
        return 'Allegato eliminato dall’evento.' . self::rimuoviCopiaNonCondivisa($record, $type);
    }

    public static function caricaAllegati(int $eventId, string $type, $fileInput, int $expectedCount = -1, ?int $replaceId = null): string
    {
        $group = self::gruppoAllegati($type);
        $prepared = ModInsertEventAttachments::prepara($fileInput, $expectedCount);
        if (!$prepared || ($replaceId !== null && count($prepared) !== 1)) {
            throw new \InvalidArgumentException($replaceId !== null ? 'Selezionare esattamente un file per la sostituzione.' : 'Selezionare almeno un file da caricare.');
        }
        foreach ($prepared as $file) {
            if ($file['field'] !== $type) throw new \InvalidArgumentException('I file selezionati non corrispondono alla categoria.');
        }
        $db = Factory::getDbo();
        $written = [];
        $old = null;
        $db->transactionStart();
        try {
            self::bloccaEvento($eventId);
            if ($replaceId !== null) $old = self::trovaAllegato($eventId, $type, $replaceId);
            ModInsertEventAttachments::salva($eventId, $prepared, $written);
            if ($old) {
                $query = $db->getQuery(true)->delete($db->qn($group['table']))
                    ->where($db->qn('id') . ' = ' . $replaceId)->where($db->qn('id_evento') . ' = ' . $eventId);
                $db->setQuery($query)->execute();
            }
            $db->transactionCommit();
        } catch (\Throwable $e) {
            $db->transactionRollback();
            ModInsertEventAttachments::annulla($written);
            throw $e;
        }
        return $old ? 'Allegato sostituito.' . self::rimuoviCopiaNonCondivisa($old, $type)
            : 'Caricati ' . count($prepared) . ' allegati nella sezione ' . $group['label'] . '.';
    }

    public static function deleteEvento($id)
    {
        $id = (int) $id;
        if ($id < 1) {
            throw new \DomainException('ID evento non valido.');
        }
        $db = Factory::getDbo();
        $db->transactionStart();
        try {
            $eventId = $db->setQuery('SELECT ' . $db->qn('id') . ' FROM ' . $db->qn('hermes_eventi')
                . ' WHERE ' . $db->qn('id') . ' = ' . $id . ' FOR UPDATE')->loadResult();
            if ($eventId === null) {
                throw new \DomainException('Evento non trovato. Controllare l’ID.');
            }
            // hermes_files ha un FK senza ON DELETE CASCADE: eliminare prima
            // tutti i figli, comprese le pubblicazioni che non hanno un FK.
            foreach (['hermes_immagini', 'hermes_pubblicazioni', 'hermes_files'] as $table) {
                $query = $db->getQuery(true)->delete($db->qn($table))
                    ->where($db->qn('id_evento') . ' = ' . $id);
                $db->setQuery($query)->execute();
            }
            $query = $db->getQuery(true)->delete($db->qn('hermes_eventi'))
                ->where($db->qn('id') . ' = ' . $id);
            $db->setQuery($query)->execute();
            $db->transactionCommit();
        } catch (\Throwable $e) {
            $db->transactionRollback();
            throw $e;
        }
        return 'Evento e associazioni a immagini, pubblicazioni e file eliminati correttamente.';
    }

    public static function updateEvento($data)
    {
        $id = (int) ($data['id'] ?? 0);
        $existing = $id > 0 ? self::getEvento($id) : null;
        if (!$existing) {
            throw new \DomainException('Evento non trovato. Controllare l’ID.');
        }
        $when = ModInsertEventHelper::normalizzaDataOra(
            (string) ($data['data'] ?? $existing['data']), (string) ($data['ora'] ?? $existing['ora'])
        );
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $sets = [];
        foreach (self::getCampiModificabili() as $field => [$label, $type, $required]) {
            // Un campo omesso non cancella il valore esistente; uno vuoto lo rimuove.
            if (!array_key_exists($field, $data)) {
                continue;
            }
            if (!is_scalar($data[$field]) && $data[$field] !== null) {
                throw new \InvalidArgumentException('Valore non valido per ' . $label . '.');
            }
            $value = trim((string) $data[$field]);
            if ($required && $value === '') {
                throw new \InvalidArgumentException('Il campo ' . $label . ' è obbligatorio.');
            }
            if (isset($when[$field])) {
                $value = $when[$field];
            } elseif ($field === 'link_aggiuntivi') {
                $value = ModInsertEventHelper::normalizzaLinkAggiuntivi($value);
            } elseif ($value !== '' && $type === 'integer' && !preg_match('/^\d+$/D', $value)) {
                throw new \InvalidArgumentException('Il campo ' . $label . ' richiede un numero intero non negativo.');
            } elseif ($value !== '' && $type === 'number' && (!is_numeric($value) || !is_finite((float) $value))) {
                throw new \InvalidArgumentException('Il campo ' . $label . ' richiede un numero valido.');
            }
            $sets[] = $db->qn($field) . ' = ' . ($value === '' ? 'NULL' : $db->quote($value));
        }
        if (!$sets) {
            return 'Nessun campo da modificare.';
        }
        $query->update($db->qn('hermes_eventi'))
              ->set($sets)
              ->where($db->qn('id') . ' = ' . $id);
        $db->setQuery($query)->execute();
        return "Evento aggiornato con successo.";
    }

    public static function deleteImmagine($img_id, ?int $eventId = null)
    {
        if ($eventId === null) {
            $db = Factory::getDbo();
            $eventId = (int) $db->setQuery('SELECT id_evento FROM hermes_immagini WHERE id=' . (int) $img_id)->loadResult();
        }
        return self::eliminaAllegato($eventId, 'immagine', (int) $img_id);
    }

    public static function uploadImmagine($event_id, $file)
    {
        $input = new class($file) {
            private array $file;
            public function __construct(array $file) { $this->file = $file; }
            public function get($name, $default, $filter) { return $name === 'immagine' ? $this->file : $default; }
        };
        return self::caricaAllegati((int) $event_id, 'immagine', $input, 1);
    }
}
