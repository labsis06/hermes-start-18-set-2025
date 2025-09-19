<?php
defined('_JEXEC') or die;

require_once __DIR__ . '/helper.php';

$input = JFactory::getApplication()->input;
$eventId = $input->getInt('eventid', 0);

if ($eventId > 0) {
    try {
        $result = ModHermesImporterHelper::importEventData($eventId);

        if (is_numeric($result)) {
            $eventIdDb = (int) $result;

            $imageFile = $_FILES['image_file'] ?? null;
            if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'images/eventi/';
                $uploadPath = JPATH_ROOT . '/' . $uploadDir;
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                $filename = basename($imageFile['name']);
                $targetFile = $uploadPath . $filename;
                if (move_uploaded_file($imageFile['tmp_name'], $targetFile)) {
                    $db = JFactory::getDbo();
                    $query = $db->getQuery(true)
                        ->insert($db->qn('hermes_immagini'))
                        ->columns([$db->qn('id_evento'), $db->qn('nome_file'), $db->qn('percorso_file')])
                        ->values(
                            $db->quote($eventIdDb) . ', ' .
                            $db->quote($filename) . ', ' .
                            $db->quote($uploadDir . $filename)
                        );
                    $db->setQuery($query)->execute();
                }
            }

            echo "<p style='color: green;'>✅ Dati evento $eventIdDb importati con successo!</p>";

        } elseif (is_string($result)) {
            echo "<p style='color: red;'>❌ Errore: $result</p>";
        } else {
            echo "<p style='color: red;'>❌ Errore sconosciuto durante l'importazione.</p>";
        }

    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Eccezione catturata: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}


require JModuleHelper::getLayoutPath('mod_hermesimporter', 'default');
