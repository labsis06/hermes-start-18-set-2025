<?php
namespace Joomla\Component\HermesEventi\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class EventoModel extends BaseDatabaseModel
{
    public function getItem()
    {
        $id = Factory::getApplication()->getInput()->getInt('id', 0);
        $db = $this->getDbo();

        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__hermes_eventi')
            ->where('id = ' . (int) $id);
        $db->setQuery($query);
        $evento = $db->loadObject();

        $queryImg = $db->getQuery(true)
            ->select('*')
            ->from('#__hermes_immagini')
            ->where('id_evento = ' . (int) $id);
        $db->setQuery($queryImg);
        $evento->immagini = $db->loadObjectList();

        return $evento;
    }
}
