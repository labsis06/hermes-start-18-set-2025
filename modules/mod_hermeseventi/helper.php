<?php
defined('_JEXEC') or die;

class ModHermesEventiHelper
{
    public static function getLinkAggiuntivi(?string $text): array
    {
        $urls = [];
        foreach (preg_split('/\r\n|\r|\n/', $text ?? '') as $line) {
            $url = trim($line);
            $parts = parse_url($url);
            if (filter_var($url, FILTER_VALIDATE_URL) && $parts
                && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
                && !isset($parts['user']) && !isset($parts['pass']) && !preg_match('/[\x00-\x20\x7f]/', $url)) {
                $urls[] = $url;
            }
        }
        return array_values(array_unique($urls));
    }

    public static function getEventi($tipo = '', $ordine = 'data_desc')
    {
        $db = \Joomla\CMS\Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('e.*, GROUP_CONCAT(i.percorso_file SEPARATOR "||") AS percorso_file')
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
