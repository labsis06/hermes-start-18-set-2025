<?php
defined('_JEXEC') or die;
require_once __DIR__ . '/helper.php';

$app = \Joomla\CMS\Factory::getApplication();
$input = $app->getInput();
$task = $input->getInt('manager_id') === (int) $module->id ? $input->getCmd('hermesmanager_task', '') : '';
$message = '';
$messageType = 'info';
$evento = null;
$immagini = [];
$id = $input->getInt('event_id');
$submitted = [];

try {
    if ($task !== '' && $task !== 'load') {
        if ($input->getMethod() !== 'POST' || !\Joomla\CMS\Session\Session::checkToken('post')) {
            throw new \DomainException('Sessione scaduta o richiesta non valida. Ricaricare la pagina prima di salvare.');
        }
    }
    switch ($task) {
        case 'load':
            $evento = ModHermesManagerHelper::getEvento($id);
            if (!$evento) {
                throw new \DomainException('Evento non trovato. Controllare l’ID.');
            }
            break;
        case 'update':
            $id = $input->post->getInt('event_id');
            $submitted = ['id' => $id];
            foreach (ModHermesManagerHelper::getCampiModificabili() as $field => $definition) {
                if ($input->post->exists($field)) {
                    $submitted[$field] = $input->post->getString($field);
                }
            }
            $message = ModHermesManagerHelper::updateEvento($submitted);
            $evento = ModHermesManagerHelper::getEvento($id);
            $messageType = 'success';
            break;
        case 'delete':
            $message = ModHermesManagerHelper::deleteEvento($id);
            $messageType = 'success';
            break;
        case 'delete_img':
            $imageId = $input->post->getInt('img_id');
            $images = ModHermesManagerHelper::getImmagini($id);
            if (!in_array($imageId, array_map(static fn($image) => (int) $image['id'], $images), true)) {
                throw new \DomainException('Immagine non associata a questo evento.');
            }
            $message = ModHermesManagerHelper::deleteImmagine($imageId);
            $evento = ModHermesManagerHelper::getEvento($id);
            break;
        case 'upload_img':
            if (!ModHermesManagerHelper::getEvento($id)) {
                throw new \DomainException('Evento non trovato.');
            }
            $file = $input->files->get('new_image', ['error' => UPLOAD_ERR_NO_FILE], 'raw');
            $message = ModHermesManagerHelper::uploadImmagine($id, $file);
            $evento = ModHermesManagerHelper::getEvento($id);
            break;
    }
} catch (\DomainException | \InvalidArgumentException $e) {
    $message = $e->getMessage();
    $messageType = 'danger';
    if ($task === 'update' && $submitted) {
        $existing = ModHermesManagerHelper::getEvento($id);
        $evento = $existing ? array_replace($existing, $submitted) : null;
    }
} catch (\Throwable $e) {
    $message = 'Operazione non completata. Controllare i dati e riprovare.';
    $messageType = 'danger';
    \Joomla\CMS\Log\Log::add($e->getMessage(), \Joomla\CMS\Log\Log::ERROR, 'mod_hermesmanager');
}
if ($evento) {
    $immagini = ModHermesManagerHelper::getImmagini((int) $evento['id']);
}

require \Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_hermesmanager', 'default');
