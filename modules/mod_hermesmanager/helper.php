<?php
defined('_JEXEC') or die;

class ModHermesManagerHelper
{
    public static function getEvento($id)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
                    ->select('*')
                    ->from($db->qn('hermes_eventi'))
                    ->where($db->qn('id') . ' = ' . (int)$id);
        $db->setQuery($query);
        return $db->loadAssoc();
    }

    public static function getImmagini($id_evento)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
                    ->select('*')
                    ->from($db->qn('hermes_immagini'))
                    ->where($db->qn('id_evento') . ' = ' . (int)$id_evento);
        $db->setQuery($query);
        return $db->loadAssocList();
    }

    public static function deleteEvento($id)
    {
        $db = JFactory::getDbo();
        $db->setQuery("DELETE FROM hermes_immagini WHERE id_evento = " . (int)$id)->execute();
        $db->setQuery("DELETE FROM hermes_eventi WHERE id = " . (int)$id)->execute();
        return "Evento e immagini eliminati correttamente.";
    }

    public static function updateEvento($data)
    {
        $fields = ['data','ora','tipo','stazione_first','comp1','note','wessel_link','area','Md','ML','Mw','wessel_id','prof','lat','lon','numero_stazioni','H_err','V_err','stazione_near'];
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $sets = [];
        foreach ($fields as $field) {
            $val = isset($data[$field]) ? $db->quote($data[$field]) : 'NULL';
            $sets[] = $db->qn($field) . ' = ' . $val;
        }
        $query->update($db->qn('hermes_eventi'))
              ->set($sets)
              ->where($db->qn('id') . ' = ' . (int)$data['id']);
        $db->setQuery($query)->execute();
        return "Evento aggiornato con successo.";
    }

    public static function deleteImmagine($img_id)
    {
        $db = JFactory::getDbo();
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
        $db = JFactory::getDbo();
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
