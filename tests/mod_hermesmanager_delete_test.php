<?php
define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/libraries/bootstrap.php';
require JPATH_BASE . '/modules/mod_hermesmanager/helper.php';

$schema = getenv('HERMES_TEST_DATABASE');
if (!$schema || !preg_match('/^[A-Za-z0-9_]+_test$/D', $schema)) {
    throw new RuntimeException('Select an empty disposable *_test database.');
}
$db = new Joomla\Database\Mysqli\MysqliDriver([
    'host' => 'localhost', 'user' => 'agent', 'password' => '', 'database' => $schema,
    'socket' => getenv('HERMES_TEST_SOCKET') ?: null,
]);
Joomla\CMS\Factory::$database = $db;
$checks = 0;
$check = function ($condition, string $message) use (&$checks) {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};
$count = fn($table, $id) => (int) $db->setQuery('SELECT COUNT(*) FROM ' . $db->qn($table)
    . ' WHERE ' . $db->qn($table === 'hermes_eventi' ? 'id' : 'id_evento') . ' = ' . (int) $id)->loadResult();
$tables = ['hermes_immagini', 'hermes_pubblicazioni', 'hermes_files'];
$check((int) $db->setQuery('SELECT COUNT(*) FROM hermes_eventi')->loadResult() === 0, 'Use an empty test schema');
$fkCount = $db->setQuery('SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '
    . $db->quote($schema) . " AND TABLE_NAME = 'hermes_files' AND REFERENCED_TABLE_NAME = 'hermes_eventi'")->loadResult();
$check((int) $fkCount > 0, 'Test needs the real restrictive foreign key on hermes_files');
$created = [];
$seed = function ($seconds, $attachments = true) use ($db, &$created, $tables) {
    $event = (object) ['data' => '2050-01-01', 'ora' => '01:02:' . $seconds, 'tipo' => 'terremoto', 'area' => 'Ischia'];
    $db->insertObject('hermes_eventi', $event);
    $id = (int) $db->insertid(); $created[] = $id;
    if ($attachments) {
        foreach ($tables as $table) {
            foreach (['one', 'two'] as $name) {
                $row = (object) ['id_evento' => $id, 'nome_file' => $name, 'percorso_file' => 'not-written-' . $name];
                $db->insertObject($table, $row);
            }
        }
    }
    return $id;
};
$db->setQuery('CREATE TABLE hermes_test_delete_blocker (id INT PRIMARY KEY, id_evento INT NOT NULL,
    FOREIGN KEY (id_evento) REFERENCES hermes_eventi(id)) ENGINE=InnoDB')->execute();
try {
    $id = $seed('01'); $other = $seed('02'); $empty = $seed('03', false);
    ModHermesManagerHelper::deleteEvento($empty);
    $check($count('hermes_eventi', $empty) === 0, 'Delete event without attachments');
    ModHermesManagerHelper::deleteEvento($id);
    $check($count('hermes_eventi', $id) === 0, 'Delete event with restrictive FK');
    foreach ($tables as $table) {
        $check($count($table, $id) === 0, 'Remove all target associations from ' . $table);
        $check($count($table, $other) === 2, 'Keep other event associations in ' . $table);
    }
    $check($count('hermes_eventi', $other) === 1, 'Other event remains');
    foreach ([0, $id] as $missing) {
        try { ModHermesManagerHelper::deleteEvento($missing); throw new RuntimeException('Invalid deletion accepted'); }
        catch (DomainException $e) { $check(true, 'Invalid or missing event rejected'); }
    }
    $blocked = $seed('04');
    $db->setQuery('INSERT INTO hermes_test_delete_blocker VALUES (1,' . $blocked . ')')->execute();
    try {
        ModHermesManagerHelper::deleteEvento($blocked);
        throw new LogicException('Blocking constraint was ignored');
    } catch (RuntimeException $e) {
        $check(str_contains($e->getMessage(), 'foreign key constraint'), 'Deletion reports blocking FK');
    }
    $check($count('hermes_eventi', $blocked) === 1, 'Failed deletion preserves event');
    foreach ($tables as $table) $check($count($table, $blocked) === 2, 'Rollback restores ' . $table);
    $db->setQuery('DELETE FROM hermes_test_delete_blocker')->execute();
    ModHermesManagerHelper::deleteEvento($blocked);
    $check($count('hermes_eventi', $blocked) === 0, 'Retry after removing blocker succeeds');
} finally {
    $db->setQuery('DROP TABLE hermes_test_delete_blocker')->execute();
    foreach ($created as $id) {
        foreach ($tables as $table) $db->setQuery('DELETE FROM ' . $db->qn($table) . ' WHERE id_evento=' . $id)->execute();
        $db->setQuery('DELETE FROM hermes_eventi WHERE id=' . $id)->execute();
    }
}
echo "Passed: $checks deletion checks.\n";
