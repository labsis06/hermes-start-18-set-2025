<?php
namespace Joomla\Component\HermesEventi\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\ComponentInterfaceTrait;

class HermesEventiComponent implements ComponentInterface
{
    use ComponentInterfaceTrait;

    protected string $component = 'com_hermeseventi';
}
