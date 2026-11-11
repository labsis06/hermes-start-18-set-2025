<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Log\Log;

require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/attachments.php';

$app = Factory::getApplication();
$input = $app->getInput();
$moduleId = (int) $module->id;
$escape = static fn(string $text) => htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

try {
    Log::addLogger(['text_file' => 'mod_insert_event.log.php'], Log::ALL, ['mod_insert_event']);
} catch (\Throwable $e) {}

$postLimit = ModInsertEventAttachments::bytes((string) ini_get('post_max_size'));
if ($input->getMethod() === 'POST' && $postLimit > 0 && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $postLimit) {
    $app->enqueueMessage('Caricamento troppo grande: superato il limite totale del server (' . $escape((string) ini_get('post_max_size')) . '). Nessun evento salvato. Ridurre gli allegati e riprovare.', 'error');
    require ModuleHelper::getLayoutPath('mod_insert_event', 'default');
    return;
}

if ($input->getMethod() === 'POST' && $input->getInt('mod_id') === $moduleId) {
    $written = [];
    try {
        if (!\Joomla\CMS\Session\Session::checkToken('post')) {
            throw new \DomainException('Sessione scaduta. Ricaricare la pagina prima di inserire.');
        }
        $data = ModInsertEventHelper::leggiDatiEvento($input);
        // Controlla TUTTI i file prima di creare l'evento o spostare un allegato.
        $attachments = ModInsertEventAttachments::prepara($input->files, $input->getInt('attachment_count', -1));
        $confirmed = ModInsertEventHelper::consumaTicket(
            $app->getSession(), $moduleId, $data,
            $input->getString('event_check_token'), $input->getInt('confirm_duplicate') === 1
        );
        $idEvento = ModInsertEventHelper::salvaEvento($data, $confirmed,
            static function (int $id) use ($attachments, &$written): void {
                ModInsertEventAttachments::salva($id, $attachments, $written);
            }
        );
        $counts = array_fill_keys(array_keys(ModInsertEventAttachments::GROUPS), 0);
        foreach ($attachments as $file) $counts[$file['field']]++;
        $app->enqueueMessage('Evento salvato (ID: ' . $idEvento . '). Immagini: ' . $counts['immagine']
            . '; pubblicazioni: ' . $counts['pubblicazione'] . '; file generici: ' . $counts['file_generico'] . '.', 'message');
    } catch (\DomainException | \InvalidArgumentException $e) {
        ModInsertEventAttachments::annulla($written);
        $app->enqueueMessage('Nessun evento o allegato salvato. ' . $escape($e->getMessage()), 'error');
    } catch (\Throwable $e) {
        ModInsertEventAttachments::annulla($written);
        Log::add($e->getMessage(), Log::ERROR, 'mod_insert_event');
        $app->enqueueMessage('Salvataggio non riuscito. Nessun nuovo evento registrato. Controllare gli allegati e riprovare.', 'error');
    }
}

require ModuleHelper::getLayoutPath('mod_insert_event', 'default');
