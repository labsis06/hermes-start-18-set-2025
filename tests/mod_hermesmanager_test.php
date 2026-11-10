<?php
define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/libraries/bootstrap.php';
require JPATH_BASE . '/modules/mod_hermesmanager/helper.php';
require JPATH_BASE . '/modules/mod_hermeseventi/helper.php';

$schema = getenv('HERMES_TEST_DATABASE');
if (!$schema || !preg_match('/^[A-Za-z0-9_]+_test$/D', $schema)) {
    fwrite(STDERR, "Select a disposable *_test database with HERMES_TEST_DATABASE.\n");
    exit(1);
}
$db = new Joomla\Database\Mysqli\MysqliDriver([
    'host' => 'localhost', 'user' => 'agent', 'password' => '', 'database' => $schema,
    'socket' => getenv('HERMES_TEST_SOCKET') ?: null,
]);
Joomla\CMS\Factory::$database = $db;
$checks = 0;
$check = function ($condition, $message) use (&$checks) {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};
$rejects = function (callable $callback) use ($check) {
    try { $callback(); }
    catch (DomainException | InvalidArgumentException $e) { $check(true, 'Rejected'); return; }
    throw new RuntimeException('Invalid update was accepted');
};
$db->transactionStart();
try {
    $data = ['data' => '2040-01-02', 'ora' => '03:04:05', 'tipo' => 'terremoto', 'area' => 'Ischia',
        'stazione' => 'ND', 'componente' => 'ND', 'note' => 'original note', 'Md' => '1.2',
        'profondita' => '2.5', 'lat' => '', 'lon' => '', 'link_aggiuntivi' => 'https://example.org/original'];
    $id = ModInsertEventHelper::salvaEvento($data);
    $other = $data; $other['ora'] = '03:04:06';
    $otherId = ModInsertEventHelper::salvaEvento($other);
    ModHermesManagerHelper::updateEvento(['id' => $id, 'note' => "first line\nsecond line", 'link_aggiuntivi' => "https://example.org/info\nhttps://example.org/report"]);
    $saved = ModHermesManagerHelper::getEvento($id);
    $check((int) $saved['id'] === $id, 'Keep event ID');
    $check($saved['note'] === "first line\nsecond line", 'Save multiline notes');
    $check($saved['link_aggiuntivi'] === "https://example.org/info\nhttps://example.org/report", 'Save multiple links');
    $check($saved['area'] === 'Ischia' && (float) $saved['Md'] === 1.2, 'Omitted fields must be preserved');
    ModHermesManagerHelper::updateEvento(['id' => $id, 'link_aggiuntivi' => 'https://example.org/report', 'Md' => '', 'note' => '']);
    $saved = ModHermesManagerHelper::getEvento($id);
    $check($saved['link_aggiuntivi'] === 'https://example.org/report', 'Remove one link');
    $check($saved['Md'] === null && $saved['note'] === null, 'Empty optional fields become NULL');
    ModHermesManagerHelper::updateEvento(['id' => $id, 'link_aggiuntivi' => '', 'numero_stazioni' => '0', 'prof' => '0']);
    $saved = ModHermesManagerHelper::getEvento($id);
    $check($saved['link_aggiuntivi'] === null, 'Remove all links');
    $check((int) $saved['numero_stazioni'] === 0 && $saved['prof'] !== null && (float) $saved['prof'] === 0.0, 'Zero is not a missing value');
    $cards = ModHermesEventiHelper::getEventi();
    $card = array_values(array_filter($cards, static fn($event) => (int) $event->id === $id))[0];
    $check(property_exists($card, 'prof') && property_exists($card, 'lat') && property_exists($card, 'ML'), 'Load all editable detail columns in homepage');
    $check($card->prof !== null && (float) $card->prof === 0.0, 'Homepage reflects changed optional detail');
    foreach ([['data' => '2023-02-29'], ['ora' => '25:00:00'], ['area' => ''], ['tipo' => ''], ['Md' => 'not a number'], ['numero_stazioni' => '1.5'], ['link_aggiuntivi' => 'javascript:alert(1)']] as $bad) {
        $rejects(fn() => ModHermesManagerHelper::updateEvento(['id' => $id] + $bad));
    }
    $check(ModHermesManagerHelper::getEvento($id) === $saved, 'Rejected updates must not mutate data');
    ModHermesManagerHelper::updateEvento(['id' => $id, 'data' => '2041-02-03', 'ora' => '05:06', 'area' => 'Campi Flegrei']);
    $saved = ModHermesManagerHelper::getEvento($id);
    $check($saved['data'] === '2041-02-03' && $saved['ora'] === '05:06:00' && $saved['area'] === 'Campi Flegrei', 'Update required fields');
    $check(ModHermesManagerHelper::getEvento($otherId)['note'] === 'original note', 'Other event is unchanged');
    $rejects(fn() => ModHermesManagerHelper::updateEvento(['id' => 0, 'note' => 'bad']));
    $check((int) $db->setQuery('SELECT COUNT(*) FROM hermes_eventi')->loadResult() === 2, 'Updates never create records');
} finally {
    $db->transactionRollback();
}
echo "Passed: $checks manager checks.\n";
