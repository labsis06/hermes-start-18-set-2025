<?php
namespace HermesEventi\View\Evento;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    protected $item;

    public function display($tpl = null): void
    {
        $this->item = $this->getModel()->getItem();

        if (!$this->item) {
            throw new \Exception('Evento non trovato', 404);
        }

        parent::display($tpl);
    }
}
