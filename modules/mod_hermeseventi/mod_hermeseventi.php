<?php
defined('_JEXEC') or die;
require_once __DIR__ . '/helper.php';

$input = JFactory::getApplication()->input;
$filtro_tipo = $input->getString('tipo', '');
$ordine = $input->getString('ordine', 'data_desc');

$eventi = ModHermesEventiHelper::getEventi($filtro_tipo, $ordine);
require JModuleHelper::getLayoutPath('mod_hermeseventi');
