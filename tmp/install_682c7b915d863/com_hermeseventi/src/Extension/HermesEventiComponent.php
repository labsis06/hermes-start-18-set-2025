<?php
namespace Joomla\Component\HermesEventi\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;

final class HermesEventiComponent implements ComponentInterface
{
    public function boot(Container $container): void {}

    public function getNamespace(): string
    {
        return 'Joomla\\Component\\HermesEventi';
    }

    public function getMVCFactory(Container $container): MVCFactoryInterface
    {
        return $container->get(MVCFactoryInterface::class);
    }

    public function getDispatcher(Container $container): ComponentDispatcherFactoryInterface
    {
        return $container->get(ComponentDispatcherFactoryInterface::class);
    }

    public function getComponentName(): string
    {
        return 'com_hermeseventi';
    }
}
