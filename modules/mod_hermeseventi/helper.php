<?php
defined('_JEXEC') or die;

class ModHermesEventiHelper
{
    public static function getEventi($tipo = '', $ordine = 'data_desc')
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('e.id, e.tipo, e.stazione_first, e.data, e.ora, GROUP_CONCAT(i.percorso_file SEPARATOR "||") AS percorso_file')
            ->from($db->qn('hermes_eventi', 'e'))
            ->leftJoin($db->qn('hermes_immagini', 'i') . ' ON i.id_evento = e.id')
            ->group($db->qn('e.id'));

        if (!empty($tipo)) {
            $query->where($db->qn('e.tipo') . ' = ' . $db->quote($tipo));
        }

        switch ($ordine) {
            case 'titolo_asc':
                $query->order('e.tipo ASC');
                break;
            case 'titolo_desc':
                $query->order('e.tipo DESC');
                break;
            case 'data_asc':
                $query->order('e.data ASC, e.ora ASC');
                break;
            default:
                $query->order('e.data DESC, e.ora DESC');
                break;
        }

        $db->setQuery($query);
        return $db->loadObjectList();
    }
}
