<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void {
        $container->registerServiceProvider(new MVCFactory('HermesEventi'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('HermesEventi'));
        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                return $container->get(ComponentDispatcherFactoryInterface::class)->createDispatcher('hermeseventi');
            }
        );
    }
};
