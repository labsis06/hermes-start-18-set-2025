<?php
defined('_JEXEC') or die;

class ModHermesEventiHelper
{
    /** Riconosce i tag Joomla usati per inserire il modulo nell'articolo. */
    public static function contieneManager(string $text, array $modules): bool
    {
        foreach ($modules as $module) {
            if (preg_match('/\{loadmoduleid\s+' . (int) $module->id . '\s*\}/i', $text)) return true;
            preg_match_all('/\{loadmodule\s+([^}]+)\}/i', $text, $matches);
            foreach ($matches[1] as $specification) {
                $parts = array_map('trim', explode(',', $specification));
                if (in_array(strtolower($parts[0]), ['mod_hermesmanager', 'hermesmanager'], true)
                    && (empty($parts[1]) || html_entity_decode($parts[1], ENT_QUOTES, 'UTF-8') === $module->title)) return true;
            }
            preg_match_all('/\{loadposition\s+([^},]+)(?:,[^}]*)?\}/i', $text, $positions);
            foreach ($positions[1] as $position) {
                if (trim($module->position) !== '' && strtolower(trim($position)) === strtolower(trim($module->position))) return true;
            }
        }
        return false;
    }

    /** Trova una destinazione accessibile e con un manager pubblicato/assegnato. */
    public static function getManagerMenuId(int $preferred = 0): ?int
    {
        $app = \Joomla\CMS\Factory::getApplication();
        $db = \Joomla\CMS\Factory::getDbo();
        $items = $app->getMenu()->getItems('type', 'component') ?: [];
        if ($preferred > 0) $items = array_values(array_filter($items, static fn($item) => (int) $item->id === $preferred));
        $levels = $app->getIdentity()->getAuthorisedViewLevels();
        foreach ($items as $item) {
            $text = null;
            $query = $item->query;
            $isArticle = ($query['option'] ?? '') === 'com_content' && ($query['view'] ?? '') === 'article' && !empty($query['id']);
            if (!$preferred && !$isArticle) continue;
            if ($isArticle) {
                $articleQuery = $db->getQuery(true)->select($db->qn(['introtext', 'fulltext']))->from($db->qn('#__content'))
                    ->where($db->qn('id') . ' = ' . (int) $query['id'])->where($db->qn('state') . ' = 1')
                    ->whereIn($db->qn('access'), $levels);
                $now = $db->quote(\Joomla\CMS\Factory::getDate()->toSql());
                $articleQuery->where('(' . $db->qn('publish_up') . ' IS NULL OR ' . $db->qn('publish_up') . ' <= ' . $now . ')')
                    ->where('(' . $db->qn('publish_down') . ' IS NULL OR ' . $db->qn('publish_down') . ' >= ' . $now . ')');
                $article = $db->setQuery($articleQuery)->loadAssoc();
                if (!$article) continue;
                $text = ($article['introtext'] ?? '') . ' ' . ($article['fulltext'] ?? '');
            }
            if (!$preferred && !preg_match('/\{load(?:module|position)/i', $text)) continue;
            if (!$preferred && !\Joomla\CMS\Plugin\PluginHelper::isEnabled('content', 'loadmodule')) continue;
            // Il filtro Joomla applica ACL, pubblicazione, lingua e assegnazioni
            // del menu di destinazione. Ripristina subito il contesto della homepage.
            $input = $app->getInput();
            $originalItemId = $input->get('Itemid', null, 'raw');
            try {
                $input->set('Itemid', (int) $item->id);
                $modules = \Joomla\CMS\Helper\ModuleHelper::cleanModuleList(\Joomla\CMS\Helper\ModuleHelper::getModuleList());
            } finally {
                $input->set('Itemid', $originalItemId);
            }
            $managers = array_values(array_filter($modules, static fn($module) => $module->module === 'mod_hermesmanager'));
            if ($managers && ($preferred > 0 || self::contieneManager($text, $managers))) return (int) $item->id;
        }
        return null;
    }

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
