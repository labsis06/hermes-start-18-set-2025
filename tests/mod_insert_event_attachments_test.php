<?php
define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/libraries/bootstrap.php';
require JPATH_BASE . '/modules/mod_insert_event/helper.php';
require JPATH_BASE . '/modules/mod_insert_event/attachments.php';
$checks = 0;
$check = function ($condition, $message) use (&$checks) {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};
$rejects = function (callable $callback) use ($check) {
    try { $callback(); } catch (InvalidArgumentException $e) { $check(true, 'Rejected'); return; }
    throw new RuntimeException('Invalid upload accepted');
};
$check(ModInsertEventAttachments::bytes('8M') === 8388608, 'Total upload limit');
$check(ModInsertEventAttachments::bytes('512K') === 524288, 'Small upload limit');
$check(ModInsertEventAttachments::bytes('0') === 0, 'Unlimited limit');
$single = ['name' => 'a.pdf', 'tmp_name' => '/tmp/test', 'error' => UPLOAD_ERR_OK, 'size' => 4, 'type' => 'application/pdf'];
$check(ModInsertEventAttachments::normalizza($single) === [$single], 'Legacy single upload');
$check(ModInsertEventAttachments::normalizza([$single, $single]) === [$single, $single], 'Joomla multiple uploads');
$raw = [];
foreach ($single as $field => $value) $raw[$field] = [$value, $value];
$check(ModInsertEventAttachments::normalizza($raw) === [$single, $single], 'PHP parallel arrays');
$rejects(fn() => ModInsertEventAttachments::normalizza(['bad-shape']));
$input = new class {
    public $groups = [];
    public function get($name, $default, $filter) { return $this->groups[$name] ?? $default; }
};
$check(ModInsertEventAttachments::prepara($input, 0) === [], 'No attachments are required');
$input->groups = ['immagine' => [['name' => '', 'error' => UPLOAD_ERR_NO_FILE]]];
$check(ModInsertEventAttachments::prepara($input, 0) === [], 'Empty browser file control');
$rejects(fn() => ModInsertEventAttachments::prepara($input, 2));
foreach (['immagine', 'pubblicazione', 'file_generico'] as $field) {
    $input->groups = [$field => [['name' => 'test', 'error' => UPLOAD_ERR_PARTIAL]]];
    $rejects(fn() => ModInsertEventAttachments::prepara($input, 1));
}
$input->groups = ['immagine' => [$single]];
$rejects(fn() => ModInsertEventAttachments::prepara($input, 1)); // A local file is not an HTTP upload.

$schema = getenv('HERMES_TEST_DATABASE');
if (!$schema || !preg_match('/^[A-Za-z0-9_]+_test$/D', $schema)) {
    throw new RuntimeException('Select an empty disposable *_test database.');
}
$db = new Joomla\Database\Mysqli\MysqliDriver([
    'host' => 'localhost', 'user' => 'agent', 'password' => '', 'database' => $schema,
    'socket' => getenv('HERMES_TEST_SOCKET') ?: null,
]);
Joomla\CMS\Factory::$database = $db;
$count = fn($table) => (int) $db->setQuery('SELECT COUNT(*) FROM ' . $db->quoteName($table))->loadResult();
$check($count('hermes_eventi') === 0, 'Use an empty test database');
$data = ['data' => '2050-01-02', 'ora' => '03:04:05', 'tipo' => 'terremoto', 'area' => 'Ischia',
    'stazione' => 'ND', 'componente' => 'ND', 'note' => '', 'Md' => '', 'profondita' => '', 'lat' => '', 'lon' => ''];
try {
    ModInsertEventHelper::salvaEvento($data, [], function ($id) use ($db) {
        $record = (object) ['id_evento' => $id, 'nome_file' => 'test', 'percorso_file' => 'not-written'];
        $db->insertObject('hermes_immagini', $record);
        throw new RuntimeException('Simulated attachment failure');
    });
    throw new LogicException('Failure was not propagated');
} catch (RuntimeException $e) {
    $check($e->getMessage() === 'Simulated attachment failure', 'Propagate callback failure');
}
$check($count('hermes_eventi') === 0 && $count('hermes_immagini') === 0, 'Rollback event and attachments together');
$id = ModInsertEventHelper::salvaEvento($data, [], function ($eventId) use ($db) {
    foreach (['hermes_immagini', 'hermes_pubblicazioni', 'hermes_files'] as $table) {
        foreach (['one', 'two'] as $name) {
            $record = (object) ['id_evento' => $eventId, 'nome_file' => $name, 'percorso_file' => 'test-' . $name];
            $db->insertObject($table, $record);
        }
    }
});
$check($id > 0 && $count('hermes_eventi') === 1, 'Commit single event after rollback releases lock');
foreach (['hermes_immagini', 'hermes_pubblicazioni', 'hermes_files'] as $table) {
    $check($count($table) === 2, 'Commit multiple ' . $table);
    $db->setQuery('DELETE FROM ' . $db->quoteName($table))->execute();
}
$db->setQuery('DELETE FROM hermes_eventi')->execute();
echo "Passed: $checks attachment checks.\n";
