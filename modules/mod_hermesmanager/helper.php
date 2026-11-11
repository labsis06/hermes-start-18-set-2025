<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
require_once dirname(__DIR__) . '/mod_insert_event/helper.php';

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
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
                    ->select('*')
                    ->from($db->qn('hermes_immagini'))
                    ->where($db->qn('id_evento') . ' = ' . (int)$id_evento);
        $db->setQuery($query);
        return $db->loadAssocList();
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

    public static function deleteImmagine($img_id)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)->select('*')->from('hermes_immagini')->where('id=' . (int)$img_id);
        $db->setQuery($query);
        $img = $db->loadAssoc();
        if ($img && file_exists(JPATH_ROOT . '/' . $img['percorso_file'])) {
            unlink(JPATH_ROOT . '/' . $img['percorso_file']);
        }
        $db->setQuery("DELETE FROM hermes_immagini WHERE id = " . (int)$img_id)->execute();
        return "Immagine eliminata.";
    }

    public static function uploadImmagine($event_id, $file)
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return "Errore nel caricamento del file.";
        }
        $targetDir = 'images/eventi/';
        $destPath = JPATH_ROOT . '/' . $targetDir;
        if (!is_dir($destPath)) {
            mkdir($destPath, 0755, true);
        }
        $filename = basename($file['name']);
        $targetFile = $destPath . $filename;
        if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
            return "Impossibile salvare il file.";
        }
        $db = Factory::getDbo();
        $columns = ['id_evento', 'nome_file', 'percorso_file'];
        $values = [
            (int)$event_id,
            $db->quote($filename),
            $db->quote($targetDir . $filename)
        ];
        $query = $db->getQuery(true)
                    ->insert('hermes_immagini')
                    ->columns($columns)
                    ->values(implode(',', $values));
        $db->setQuery($query)->execute();
        return "Immagine caricata con successo.";
    }
}
