<?php
/** Run with PHP; DB checks use only an explicitly selected disposable *_test schema. */
define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/libraries/bootstrap.php';
require JPATH_BASE . '/modules/mod_insert_event/helper.php';
require JPATH_BASE . '/modules/mod_hermeseventi/helper.php';

$checks = 0;
$check = function ($condition, string $message) use (&$checks) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
};
$rejects = function (callable $operation, string $exception) use ($check) {
    try {
        $operation();
    } catch (Throwable $e) {
        $check($e instanceof $exception, 'Unexpected exception: ' . get_class($e));
        return;
    }
    throw new RuntimeException('Expected exception: ' . $exception);
};

$urls = "https://example.org/evento?id=1&fonte=test\nhttp://example.com/approfondimento";
$check(ModInsertEventHelper::normalizzaLinkAggiuntivi("  \r\n  " . str_replace("\n", "\r\n", $urls) . "\r\nhttps://example.org/evento?id=1&fonte=test  ") === $urls, 'Normalize line endings, trim and remove duplicate links');
$check(ModInsertEventHelper::normalizzaLinkAggiuntivi('') === '', 'Additional links are optional');
foreach (['javascript:alert(1)', 'data:text/html,test', '/evento/1', 'example.com', 'https://user:pass@example.com/', 'https://example.com/one https://example.com/two', "https://example.com/\tpath"] as $invalidLink) {
    $rejects(fn() => ModInsertEventHelper::normalizzaLinkAggiuntivi($invalidLink), InvalidArgumentException::class);
}
$check(ModHermesEventiHelper::getLinkAggiuntivi($urls) === explode("\n", $urls), 'Cards read both URLs');
$check(ModHermesEventiHelper::getLinkAggiuntivi(null) === [], 'Existing event without links');
$check(ModHermesEventiHelper::getLinkAggiuntivi("javascript:alert(1)\nhttps://example.org/evento\n<img src=x onerror=alert(1)>") === ['https://example.org/evento'], 'Never render unsafe legacy links');

$check(ModInsertEventHelper::normalizzaDataOra('2024-02-29', '03:59')['ora'] === '03:59:00', 'Missing seconds');
foreach ([['2023-02-29', '03:59:57'], ['2024-01-01', '24:00:00'], ['2024-01-01', '03:59:57.14'], ['', '']] as $invalid) {
    $rejects(fn() => ModInsertEventHelper::normalizzaDataOra(...$invalid), InvalidArgumentException::class);
}
$fixture = json_encode([
    '28768' => ['id' => 28768, 'date' => '2023-08-17 03:59:57.14'],
    '28769' => ['id' => 28769, 'date' => '2023-08-17 03:59:57.999'],
    '28770' => ['id' => 28770, 'date' => '2023-08-17 04:00:00'],
]);
$check(ModInsertEventHelper::trovaEventiSerenade($fixture, '2023-08-17', '03:59:57') === [28768, 28769], 'Fractional seconds must not round');
$check(ModInsertEventHelper::trovaEventiSerenade($fixture, '2023-08-18', '03:59:57') === [], 'Date must match too');
$check(ModInsertEventHelper::trovaEventiSerenade('{"id":28768,"date":"2023-08-17 03:59:57.14"}', '2023-08-17', '03:59:57') === [28768], 'Single event');
foreach (['<html>unavailable</html>', '{"error":"unavailable"}', 'null', '{"1":{"id":1}}'] as $bad) {
    $rejects(fn() => ModInsertEventHelper::trovaEventiSerenade($bad, '2023-08-17', '03:59:57'), RuntimeException::class);
}

$http = new class {
    public $code = 200;
    public $body = '{}';
    public $url;
    public function get($url, $headers, $timeout) {
        $this->url = $url;
        return (object) ['code' => $this->code, 'body' => $this->body];
    }
};
$when = ['data' => '2023-08-17', 'ora' => '03:59:57'];
$url = 'https://serenade.example/event/?start={inizio}&end={fine}';
$check(ModInsertEventHelper::controllaSerenade($when, '', false, $http)['status'] === 'unknown', 'No endpoint is not absence');
$check(ModInsertEventHelper::controllaSerenade($when, $url, false, $http)['status'] === 'unknown', 'Partial empty response is not absence');
$check(ModInsertEventHelper::controllaSerenade($when, $url, true, $http)['status'] === 'not_found', 'Complete empty response');
$check(str_contains($http->url, 'end=2023-08-17%2003%3A59%3A58'), 'One-second UTC search interval');
$http->body = $fixture;
$check(ModInsertEventHelper::controllaSerenade($when, $url, false, $http)['ids'] === [28768, 28769], 'Found in a partial response');
$http->code = 403;
$check(ModInsertEventHelper::controllaSerenade($when, $url, true, $http)['status'] === 'unknown', 'HTTP denial is not absence');
$http->code = 200;
$http->body = '<html>login</html>';
$check(ModInsertEventHelper::controllaSerenade($when, $url, true, $http)['status'] === 'unknown', 'HTML response is not absence');

$portalData = $when + ['tipo' => 'terremoto'];
$calls = [];
$request = function ($method, $url, $fields = []) use (&$calls, $fixture) {
    $calls[] = [$method, $url, $fields];
    if ($method === 'POST') {
        return ['code' => 302, 'headers' => ['location' => 'http://wessel.example/wessel/serenade/index.php?page=filters&action=showmap&context=new_search_123'], 'body' => ''];
    }
    return ['code' => 200, 'headers' => [], 'body' => $fixture];
};
$portal = 'http://wessel.example//serenade/index.php';
$check(ModInsertEventSerenade::cerca($portalData, $portal, false, $request)['ids'] === [28768, 28769], 'Portal search matches fractional timestamps');
$check($calls[0][0] === 'POST' && $calls[0][2]['start_date'] === '2023-08-17' && $calls[0][2]['end_date'] === '2023-08-18', 'Portal form date interval');
$check($calls[0][2]['event_type'] === 'earthquake' && $calls[0][2]['network'] === 'unknown', 'Verified filters include all networks');
$check($calls[1][0] === 'GET' && str_contains($calls[1][1], '//serenade/index.php?') && str_contains($calls[1][1], 'context=new_search_123'), 'Use returned context with canonical path');
$check(ModInsertEventSerenade::cerca($when + ['tipo' => 'sciame'], $portal, true, $request)['status'] === 'unknown', 'Unsupported type never implies absence');
foreach ([
    ['code' => 403, 'headers' => [], 'body' => ''],
    ['code' => 302, 'headers' => ['location' => 'http://elsewhere.example/?page=filters&action=showmap&context=123'], 'body' => ''],
    ['code' => 302, 'headers' => ['location' => '?page=login'], 'body' => ''],
] as $badPost) {
    $check(ModInsertEventSerenade::cerca($portalData, $portal, true, fn() => $badPost)['status'] === 'unknown', 'Bad portal POST cannot imply absence');
}
foreach ([['code' => 200, 'headers' => [], 'body' => '{}'], ['code' => 200, 'headers' => [], 'body' => '<html>session expired</html>'], ['code' => 503, 'headers' => [], 'body' => '']] as $get) {
    $request = fn($method) => $method === 'POST'
        ? ['code' => 303, 'headers' => ['location' => '?page=filters&action=showmap&context=123'], 'body' => ''] : $get;
    $expected = $get['body'] === '{}' ? 'not_found' : 'unknown';
    $check(ModInsertEventSerenade::cerca($portalData, $portal, true, $request)['status'] === $expected, 'Portal empty/invalid/error response handling');
    $check(ModInsertEventSerenade::cerca($portalData, $portal, false, $request)['status'] === 'unknown', 'Unconfirmed coverage cannot imply absence');
}
$redirectCount = 0;
$request = function ($method) use (&$redirectCount) {
    if ($method === 'POST') return ['code' => 302, 'headers' => ['location' => '?page=filters&action=showmap&context=123'], 'body' => ''];
    $redirectCount++;
    return ['code' => 302, 'headers' => ['location' => '?page=filters&action=events&context=123'], 'body' => ''];
};
$check(ModInsertEventSerenade::cerca($portalData, $portal, true, $request)['status'] === 'unknown' && $redirectCount === 4, 'Redirect loop is bounded');
$request = fn($method) => $method === 'POST'
    ? ['code' => 302, 'headers' => ['location' => '?page=filters&action=showmap&context=123'], 'body' => '']
    : ['code' => 200, 'headers' => [], 'body' => '<html>private session details</html>'];
$diagnostic = ModInsertEventSerenade::cerca($portalData, $portal, true, $request);
$check(str_contains($diagnostic['detail'], '[lettura JSON] RESPONSE_JSON'), 'HTML response diagnoses the JSON phase');
$check(!str_contains($diagnostic['detail'], 'private session details'), 'Do not expose remote body in diagnostics');
$diagnostic = ModInsertEventSerenade::cerca($portalData, $portal, true,
    fn() => ['code' => 302, 'headers' => ['location' => 'https://wessel.example/?page=filters&action=showmap&context=private_context'], 'body' => '']);
$check(str_contains($diagnostic['detail'], '[redirect dopo POST] REDIRECT_ORIGIN'), 'Protocol change identifies redirect phase');
$check(!str_contains($diagnostic['detail'], 'private_context'), 'Do not expose context in diagnostic');
$diagnostic = ModInsertEventSerenade::cerca($portalData, $portal, true, function () { throw new \TypeError('private internal detail'); });
$check(str_contains($diagnostic['detail'], '[POST filtri] PHP_ERROR') && !str_contains($diagnostic['detail'], 'private internal detail'), 'Internal exception identifies phase without exposing raw message');

$session = new class {
    public $values = [];
    public function get($key) { return $this->values[$key] ?? null; }
    public function set($key, $value) { $this->values[$key] = $value; }
};
$result = ['local_ids' => [123], 'serenade' => ['status' => 'found', 'ids' => [28768]]];
$token = ModInsertEventHelper::creaTicket($session, 110, $when, $result);
$rejects(fn() => ModInsertEventHelper::consumaTicket($session, 110, $when, $token, false), DomainException::class);
$rejects(fn() => ModInsertEventHelper::consumaTicket($session, 110, $when + ['note' => 'changed'], $token, true), DomainException::class);
$rejects(fn() => ModInsertEventHelper::consumaTicket($session, 111, $when, $token, true), DomainException::class);
$check(ModInsertEventHelper::consumaTicket($session, 110, $when, $token, true) === [123], 'Explicit confirmation permits only observed local IDs');
$rejects(fn() => ModInsertEventHelper::consumaTicket($session, 110, $when, $token, true), DomainException::class);
$token = ModInsertEventHelper::creaTicket($session, 110, $when, $result);
$session->values['mod_insert_event.check.110']['expires'] = time() - 1;
$rejects(fn() => ModInsertEventHelper::consumaTicket($session, 110, $when, $token, true), DomainException::class);
foreach ([
    ['local_ids' => [], 'serenade' => ['status' => 'found']],
    ['local_ids' => [123], 'serenade' => ['status' => 'not_found']],
    ['local_ids' => [], 'serenade' => ['status' => 'unknown']],
] as $state) {
    $check(ModInsertEventHelper::richiedeConferma($state), 'Duplicate or incomplete check requires confirmation');
}
$check(!ModInsertEventHelper::richiedeConferma(['local_ids' => [], 'serenade' => ['status' => 'not_found']]), 'No matches in both databases');
$disabled = ModInsertEventHelper::controllaSerenadeConfigurato($when, new Joomla\Registry\Registry(['serenade_mode' => 'off', 'serenade_url' => 'not-a-url']));
$check($disabled['status'] === 'disabled', 'Disabled remote check must not fetch an endpoint');
$check(ModInsertEventHelper::controllaSerenadeConfigurato($when, new Joomla\Registry\Registry())['status'] === 'disabled', 'Default mode is local only');
$check(!ModInsertEventHelper::richiedeConferma(['local_ids' => [], 'serenade' => $disabled]), 'Local-only mode without matches does not prompt');
$check(ModInsertEventHelper::richiedeConferma(['local_ids' => [123], 'serenade' => $disabled]), 'Local-only duplicate still requires confirmation');

$schema = getenv('HERMES_TEST_DATABASE');
if ($schema) {
    if (!preg_match('/^[a-zA-Z0-9_]+_test$/D', $schema)) {
        throw new RuntimeException('Use a disposable database ending in _test.');
    }
    $db = new Joomla\Database\Mysqli\MysqliDriver([
        'host' => 'localhost', 'user' => 'agent', 'password' => '', 'database' => $schema,
        'socket' => getenv('HERMES_TEST_SOCKET') ?: null,
    ]);
    Joomla\CMS\Factory::$database = $db;
    $db->transactionStart();
    try {
        $data = ['data' => '2040-01-02', 'ora' => '03:04', 'tipo' => 'terremoto', 'area' => 'Ischia',
            'stazione' => 'ND', 'componente' => 'ND', 'note' => 'duplicate check test', 'Md' => '',
            'profondita' => '', 'lat' => '', 'lon' => '', 'link_aggiuntivi' => $urls];
        $id = ModInsertEventHelper::salvaEvento($data);
        $check($id > 0, 'First insert must succeed');
        $check($db->setQuery('SELECT link_aggiuntivi FROM hermes_eventi WHERE id=' . $id)->loadResult() === $urls, 'Persist all additional links without changing query parameters');
        $check(ModInsertEventHelper::trovaDuplicatoLocale('2040-01-02', '03:04:00') === $id, 'Stored duplicate not found');
        $data['area'] = 'Campi Flegrei';
        $rejects(fn() => ModInsertEventHelper::salvaEvento($data), DomainException::class);
        $check((int) $db->setQuery('SELECT COUNT(*) FROM hermes_eventi')->loadResult() === 1, 'Duplicate was inserted');
        $secondId = ModInsertEventHelper::salvaEvento($data, [$id]);
        $check($secondId > $id, 'Explicit confirmation must allow local duplicate');
        $rejects(fn() => ModInsertEventHelper::salvaEvento($data, [$id]), DomainException::class);
        $data['ora'] = '03:04:01';
        $check(ModInsertEventHelper::salvaEvento($data) !== $id, 'Different second must be insertable');
        $data['data'] = '2040-01-03';
        $check(ModInsertEventHelper::salvaEvento($data) > 0, 'Different date must be insertable');
    } finally {
        $db->transactionRollback();
    }
} else {
    echo "Database integration checks skipped: set HERMES_TEST_DATABASE to an empty disposable *_test schema.\n";
}
echo "Passed: $checks checks.\n";
