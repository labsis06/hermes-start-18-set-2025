<?php
namespace Joomla\Component\HermesEventi\View\Evento;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    protected $item;

    public function display($tpl = null): void
    {
        $this->item = $this->getModel()->getItem();
        parent::display($tpl);
    }
}
