<?php
defined('_JEXEC') or die;
require_once __DIR__ . '/helper.php';

$input = JFactory::getApplication()->input;
$task = $input->getCmd('task', '');
$message = '';
$evento = null;
$immagini = [];

if ($task === 'load') {
    $id = $input->getInt('event_id');
    $evento = ModHermesManagerHelper::getEvento($id);
    $immagini = ModHermesManagerHelper::getImmagini($id);
} elseif ($task === 'delete') {
    $id = $input->getInt('event_id');
    $message = ModHermesManagerHelper::deleteEvento($id);
} elseif ($task === 'update') {
    $message = ModHermesManagerHelper::updateEvento($_POST);
    $id = (int)$_POST['id'];
    $evento = ModHermesManagerHelper::getEvento($id);
    $immagini = ModHermesManagerHelper::getImmagini($id);
} elseif ($task === 'delete_img') {
    $id_img = $input->getInt('img_id');
    $message = ModHermesManagerHelper::deleteImmagine($id_img);
    $id = $input->getInt('event_id');
    $evento = ModHermesManagerHelper::getEvento($id);
    $immagini = ModHermesManagerHelper::getImmagini($id);
} elseif ($task === 'upload_img') {
    $id = $input->getInt('event_id');
    $file = $_FILES['new_image'];
    $message = ModHermesManagerHelper::uploadImmagine($id, $file);
    $evento = ModHermesManagerHelper::getEvento($id);
    $immagini = ModHermesManagerHelper::getImmagini($id);
}

require JModuleHelper::getLayoutPath('mod_hermesmanager', 'default');
