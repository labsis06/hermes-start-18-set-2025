<?php
define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/libraries/bootstrap.php';
require JPATH_BASE . '/modules/mod_hermesmanager/helper.php';
$schema = getenv('HERMES_TEST_DATABASE');
if (!$schema || !preg_match('/^[A-Za-z0-9_]+_test$/D', $schema)) throw new RuntimeException('Use an empty disposable *_test database.');
$db = new Joomla\Database\Mysqli\MysqliDriver(['host' => 'localhost', 'user' => 'agent', 'password' => '', 'database' => $schema,
    'socket' => getenv('HERMES_TEST_SOCKET') ?: null]);
Joomla\CMS\Factory::$database = $db;
$checks = 0; $paths = []; $events = [];
$check = function ($ok, $message) use (&$checks) { if (!$ok) throw new RuntimeException($message); $checks++; };
$rejects = function ($callback) use ($check) {
    try { $callback(); } catch (DomainException | InvalidArgumentException $e) { $check(true, 'Rejected'); return; }
    throw new RuntimeException('Invalid operation accepted');
};
$check((int) $db->setQuery('SELECT COUNT(*) FROM hermes_eventi')->loadResult() === 0, 'Empty test database');
$seed = function ($time) use (&$events) {
    $id = ModInsertEventHelper::salvaEvento(['data' => '2055-01-01', 'ora' => $time, 'tipo' => 'terremoto', 'area' => 'Ischia',
        'stazione' => 'ND', 'componente' => 'ND', 'note' => '', 'Md' => '', 'profondita' => '', 'lat' => '', 'lon' => '']);
    $events[] = $id; return $id;
};
$add = function ($eventId, $type, $relative = null) use ($db, &$paths) {
    $group = ModInsertEventAttachments::GROUPS[$type];
    if ($relative === null) {
        $directory = JPATH_ROOT . '/' . $group['directory'];
        if (!is_dir($directory)) mkdir($directory, 0755, true);
        $path = tempnam($directory, 'manager-unit-'); file_put_contents($path, 'unit fixture'); $paths[] = $path;
        $relative = $group['directory'] . basename($path);
    }
    $record = (object) ['id_evento' => $eventId, 'nome_file' => 'original.pdf', 'percorso_file' => $relative];
    $db->insertObject($group['table'], $record);
    return [(int) $db->insertid(), $relative];
};
try {
    $a = $seed('01:02:03'); $b = $seed('01:02:04');
    foreach (['immagine', 'pubblicazione', 'file_generico'] as $type) {
        [$id, $relative] = $add($a, $type); [$otherId, $otherPath] = $add($b, $type);
        $check(count(ModHermesManagerHelper::getAllegati($a, $type)) === 1, 'List only target event ' . $type);
        ModHermesManagerHelper::rinominaAllegato($a, $type, $id, "new's document.pdf");
        $row = ModHermesManagerHelper::getAllegati($a, $type)[0];
        $check($row['nome_file'] === "new's document.pdf" && $row['percorso_file'] === $relative, 'Rename label without moving data');
        $rejects(fn() => ModHermesManagerHelper::rinominaAllegato($a, $type, $otherId, 'wrong.pdf'));
        $rejects(fn() => ModHermesManagerHelper::eliminaAllegato($a, $type, $otherId));
        ModHermesManagerHelper::eliminaAllegato($a, $type, $id);
        $check(ModHermesManagerHelper::getAllegati($a, $type) === [] && !is_file(JPATH_ROOT . '/' . $relative), 'Delete own record and exclusive copy');
        $check(count(ModHermesManagerHelper::getAllegati($b, $type)) === 1 && is_file(JPATH_ROOT . '/' . $otherPath), 'Preserve other event file');
    }
    [$sharedId, $shared] = $add($a, 'immagine'); [$lastId] = $add($b, 'immagine', $shared);
    ModHermesManagerHelper::eliminaAllegato($a, 'immagine', $sharedId);
    $check(is_file(JPATH_ROOT . '/' . $shared), 'Keep shared physical file');
    ModHermesManagerHelper::eliminaAllegato($b, 'immagine', $lastId);
    $check(!is_file(JPATH_ROOT . '/' . $shared), 'Remove physical file after last reference');
    [$id] = $add($a, 'pubblicazione');
    foreach (['', '..', '../file.pdf', 'folder\\file.pdf', "bad\r\nname.pdf"] as $bad) {
        $rejects(fn() => ModHermesManagerHelper::rinominaAllegato($a, 'pubblicazione', $id, $bad));
    }
    $outside = tempnam(sys_get_temp_dir(), 'manager-outside-'); $paths[] = $outside;
    [$outsideId] = $add($a, 'file_generico', '../../tmp/' . basename($outside));
    ModHermesManagerHelper::eliminaAllegato($a, 'file_generico', $outsideId);
    $check(is_file($outside), 'Never unlink files outside upload directories');
    [$missingId] = $add($a, 'file_generico', 'files/eventi/manager-test-missing-file');
    ModHermesManagerHelper::eliminaAllegato($a, 'file_generico', $missingId);
    $check(ModHermesManagerHelper::getAllegati($a, 'file_generico') === [], 'Remove stale metadata when physical file is missing');
    $rejects(fn() => ModHermesManagerHelper::getAllegati($a, 'hermes_eventi'));
    $empty = new class { public function get($name, $default, $filter) { return []; } };
    $rejects(fn() => ModHermesManagerHelper::caricaAllegati($a, 'pubblicazione', $empty, 0));
    $check(count($events) === (int) $db->setQuery('SELECT COUNT(*) FROM hermes_eventi')->loadResult(), 'Attachment operations never create or delete events');
} finally {
    foreach ($events as $id) {
        foreach (ModInsertEventAttachments::GROUPS as $group) $db->setQuery('DELETE FROM ' . $db->qn($group['table']) . ' WHERE id_evento=' . $id)->execute();
        $db->setQuery('DELETE FROM hermes_eventi WHERE id=' . $id)->execute();
    }
    foreach ($paths as $path) if (is_file($path) || is_link($path)) unlink($path);
}
echo "Passed: $checks manager attachment checks.\n";
