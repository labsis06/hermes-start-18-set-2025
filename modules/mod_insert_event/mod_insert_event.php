<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Log\Log;

require_once __DIR__ . '/helper.php';

$app      = Factory::getApplication();
$input    = $app->getInput();
$moduleId = (int) $module->id;

/** Logger dedicato (logs/mod_insert_event.log.php) */
try {
    Log::addLogger(
        ['text_file' => 'mod_insert_event.log.php', 'text_entry_format' => '{DATE} {TIME} {PRIORITY} {MESSAGE}'],
        Log::ALL,
        ['mod_insert_event']
    );
} catch (\Throwable $e) {}

/** Shortcuts logging */
$errors = []; $notes = [];
$log = fn($m, $p = Log::INFO) => Log::add($m, $p, 'mod_insert_event');
$fail = function(string $m, \Throwable $e=null) use (&$errors,$log){ $errors[]=$m.($e?' — '.$e->getMessage():''); $log($m.($e?(' EX: '.$e->getMessage()):''), Log::ERROR); };
$note = function(string $m) use (&$notes,$log){ $notes[]=$m; $log($m, Log::INFO); };

/** CSRF */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!\JSession::checkToken()) {
        $fail('CSRF token non valido.');
        $app->enqueueMessage(implode('<br>', $errors), 'error');
        require ModuleHelper::getLayoutPath('mod_insert_event', 'default');
        return;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $input->getInt('mod_id') === $moduleId) {

    $note('POST ricevuto. upload_max_filesize=' . ini_get('upload_max_filesize') . ', post_max_size=' . ini_get('post_max_size'));

    /** Dati evento */
    $data = [
        'data'       => $input->getString('data'),
        'ora'        => $input->getString('ora'),
        'tipo'       => $input->getString('tipo'),
        'area'       => $input->getString('area'),
        'stazione'   => $input->getString('stazione'),
        'componente' => $input->getString('componente'),
        'note'       => $input->getString('note'),
        'Md'         => $input->getString('Md'),
        'profondita' => $input->getString('profondita'),
        'lat'        => $input->getString('lat'),
        'lon'        => $input->getString('lon'),
    ];
    $note('Dati evento principali: tipo=' . ($data['tipo'] ?? '') . ', area=' . ($data['area'] ?? ''));

    /** Salva evento */
    $idEvento = null;
    try {
        $idEvento = ModInsertEventHelper::salvaEvento($data);
        if (!$idEvento) { $fail('Salvataggio evento fallito (ID mancante).'); }
        else { $note('Evento inserito con ID='.(int)$idEvento); }
    } catch (\Throwable $e) { $fail('Eccezione salvaEvento()', $e); }

    if (empty($idEvento)) {
        $app->enqueueMessage('<b>Errore</b>: evento non salvato. Upload immagine annullato.<br>' . implode('<br>', $errors), 'error');
        if ($notes) $app->enqueueMessage('<b>Dettagli</b>:<br>'.implode('<br>', $notes),'notice');
        require ModuleHelper::getLayoutPath('mod_insert_event', 'default');
        return;
    }

    $db = Factory::getDbo();

    /** === Upload immagine singola (solo immagini) === */
    try {
        // Leggi file direttamente (singolo)
        $file = $input->files->get('immagine', null, 'raw');
        $hasFile = is_array($file) && isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE;
        $note('Presenza file immagine: ' . ($hasFile ? 'SI' : 'NO'));
        $note('RAW $_FILES[immagine] present=' . (isset($_FILES['immagine']) ? 'yes' : 'no'));

        if ($hasFile) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $map = [
                    UPLOAD_ERR_INI_SIZE=>'UPLOAD_ERR_INI_SIZE', UPLOAD_ERR_FORM_SIZE=>'UPLOAD_ERR_FORM_SIZE',
                    UPLOAD_ERR_PARTIAL=>'UPLOAD_ERR_PARTIAL', UPLOAD_ERR_NO_FILE=>'UPLOAD_ERR_NO_FILE',
                    UPLOAD_ERR_NO_TMP_DIR=>'UPLOAD_ERR_NO_TMP_DIR', UPLOAD_ERR_CANT_WRITE=>'UPLOAD_ERR_CANT_WRITE',
                    UPLOAD_ERR_EXTENSION=>'UPLOAD_ERR_EXTENSION'
                ];
                $code = $file['error'];
                $fail('Errore upload immagine (code '.$code.' - '.($map[$code]??'UNKNOWN').')');
            } else {
                $tmp  = $file['tmp_name'] ?? '';
                $name = $file['name'] ?? '';

                if (!$tmp || !$name) {
                    $fail('tmp_name o name mancanti per immagine.');
                } elseif (!is_uploaded_file($tmp)) {
                    $fail('Il file non risulta caricato via HTTP (is_uploaded_file=false).');
                } else {
                    // Validazione tipo immagine (getimagesize + finfo)
                    $info = @getimagesize($tmp);
                    if ($info === false) {
                        $fail('Il file caricato non è una immagine valida (getimagesize fallita).');
                    } else {
                        // MIME whitelist
                        $mime = $info['mime'] ?? '';
                        $allowed = ['image/jpeg','image/png','image/gif','image/webp','image/bmp'];
                        if (!in_array($mime, $allowed, true)) {
                            $fail('MIME non consentito: '.$mime);
                        }
                    }

                    // Se tutto ok, procedi
                    if (!$errors) {
                        $safeName = File::makeSafe($name);

                        // Aggiungi estensione se manca (raro)
                        if (!str_contains($safeName, '.') && !empty($info['mime'])) {
                            $extByMime = [
                                'image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif',
                                'image/webp'=>'webp','image/bmp'=>'bmp'
                            ];
                            $safeName .= isset($extByMime[$info['mime']]) ? ('.'.$extByMime[$info['mime']]) : '';
                        }

                        $uploadDirRel = 'images/eventi/';
                        $uploadPath   = rtrim(JPATH_ROOT, '/\\') . '/' . $uploadDirRel;
                        if (!Folder::exists($uploadPath)) {
                            if (!Folder::create($uploadPath, 0755)) {
                                $fail('Impossibile creare cartella: '.$uploadPath);
                            } else {
                                $note('Creata cartella: '.$uploadPath);
                            }
                        }

                        // Evita sovrascritture
                        $targetFile = $uploadPath . $safeName;
                        if (File::exists($targetFile)) {
                            $dot = strrpos($safeName, '.');
                            $base = $dot!==false ? substr($safeName,0,$dot) : $safeName;
                            $ext  = $dot!==false ? substr($safeName,$dot+1) : '';
                            $safeName = $base . '-' . uniqid() . ($ext ? '.'.$ext : '');
                            $targetFile = $uploadPath . $safeName;
                        }

                        // Carica
                        if (!File::upload($tmp, $targetFile, false, true)) {
                            $fail('File::upload ha restituito false per '.$targetFile);
                        } elseif (!File::exists($targetFile) || filesize($targetFile)<=0) {
                            $fail('File non presente/size 0 dopo upload: '.$targetFile);
                        } else {
                            // Salva record immagine
                            $relPath = $uploadDirRel . $safeName;
                            try {
                                $query = $db->getQuery(true)
                                    ->insert($db->qn('hermes_immagini'))
                                    ->columns([$db->qn('id_evento'), $db->qn('nome_file'), $db->qn('percorso_file')])
                                    ->values((int)$idEvento . ', ' . $db->q($safeName) . ', ' . $db->q($relPath));
                                $db->setQuery($query)->execute();
                                $note('Immagine caricata e registrata: '.$relPath);
                            } catch (\Throwable $e) {
                                $fail('Insert DB immagine fallito.', $e);
                            }
                        }
                    }
                }
            }
        } else {
            $note('Nessuna immagine inviata.');
        }
    } catch (\Throwable $e) {
        $fail('Eccezione durante upload immagine.', $e);
    }

    /** Messaggi a schermo */
    if ($errors) {
        $app->enqueueMessage('<b>Operazione completata con errori</b>:<br>'.implode('<br>',$errors), 'error');
    } else {
        $app->enqueueMessage('Evento salvato (ID: '.(int)$idEvento.') e immagine caricata.', 'message');
    }
    if ($notes) {
        $app->enqueueMessage('<b>Dettagli</b>:<br>'.implode('<br>',$notes), 'notice');
    }
}

require ModuleHelper::getLayoutPath('mod_insert_event', 'default');
