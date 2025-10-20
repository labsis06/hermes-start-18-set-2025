<?php
defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;

$bridge = $params->get('bridge_url', 'http://tapeserver.int.ov.ingv.it:8000');

require ModuleHelper::getLayoutPath('mod_seispick', 'default');



