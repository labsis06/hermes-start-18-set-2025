<?php
defined('_JEXEC') or die;

/** Messaggi diagnostici controllati: non contengono URL, cookie o corpi remoti. */
class ModInsertEventSerenadeError extends \RuntimeException
{
}

/** Ricerca Wessel: sessione privata per richiesta, POST filtri e GET risultati. */
class ModInsertEventSerenade
{
    public static function cerca(array $data, string $url, bool $completa = false, ?callable $request = null): array
    {
        $unknown = static fn(string $detail) => ['status' => 'unknown', 'ids' => [], 'detail' => $detail];
        if (($data['tipo'] ?? '') !== 'terremoto') {
            return $unknown('La ricerca del portale è verificata per i terremoti; questo tipo di evento non è ancora supportato.');
        }
        $curl = null;
        $phase = 'configurazione';
        try {
            $parts = parse_url(trim($url));
            if (!$parts || !isset($parts['host']) || isset($parts['user']) || isset($parts['pass'])
                || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
                throw new ModInsertEventSerenadeError('CONFIG_URL: URL del portale non valido.');
            }
            $base = $parts['scheme'] . '://' . $parts['host']
                . (isset($parts['port']) ? ':' . $parts['port'] : '') . ($parts['path'] ?? '/');
            if ($request === null) {
                if (!function_exists('curl_init')) {
                    return $unknown('Per interrogare il portale Serenade è necessaria l’estensione PHP cURL.');
                }
                $curl = curl_init();
                if ($curl === false) {
                    $curl = null;
                    throw new ModInsertEventSerenadeError('CURL_INIT: impossibile avviare cURL.');
                }
                curl_setopt_array($curl, [
                    CURLOPT_COOKIEFILE => '', // Cookie in memoria: nessun cookie del browser o file condiviso.
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_USERAGENT => 'Hermes Joomla Serenade check',
                    CURLOPT_HTTPHEADER => ['Accept: application/json', 'Expect:'],
                ]);
                $deadline = microtime(true) + 30;
                $request = static function (string $method, string $target, array $fields = []) use ($curl, $deadline): array {
                    $body = '';
                    $headers = [];
                    $tooLarge = false;
                    $remaining = (int) ceil($deadline - microtime(true));
                    if ($remaining <= 0) {
                        throw new ModInsertEventSerenadeError('TIMEOUT_TOTAL: tempo massimo della ricerca esaurito (30 secondi).');
                    }
                    curl_setopt_array($curl, [
                        CURLOPT_URL => $target,
                        CURLOPT_TIMEOUT => min(15, $remaining),
                        CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$headers): int {
                            if (stripos($line, 'HTTP/') === 0) {
                                $headers = [];
                            } elseif (strpos($line, ':') !== false) {
                                [$name, $value] = explode(':', $line, 2);
                                $headers[strtolower(trim($name))] = trim($value);
                            }
                            return strlen($line);
                        },
                        CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body, &$tooLarge): int {
                            if (strlen($body) + strlen($chunk) > 16 * 1024 * 1024) {
                                $tooLarge = true;
                                return 0; // Una risposta troncata non deve diventare una falsa assenza.
                            }
                            $body .= $chunk;
                            return strlen($chunk);
                        },
                    ]);
                    if ($method === 'POST') {
                        curl_setopt($curl, CURLOPT_POST, true);
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
                    } else {
                        curl_setopt($curl, CURLOPT_HTTPGET, true);
                    }
                    if (curl_exec($curl) === false) {
                        if ($tooLarge) {
                            throw new ModInsertEventSerenadeError('RESPONSE_LIMIT: risposta superiore a 16 MiB; controllo interrotto senza dichiarare assente l’evento.');
                        }
                        $number = curl_errno($curl);
                        $description = [
                            5 => 'impossibile risolvere il nome del proxy',
                            6 => 'il server Joomla non riesce a risolvere il nome di Wessel (DNS)',
                            7 => 'il server Joomla non riesce a collegarsi a Wessel o al proxy configurato',
                            23 => 'impossibile acquisire la risposta',
                            28 => 'tempo massimo di connessione o risposta superato',
                            35 => 'negoziazione TLS non riuscita',
                            52 => 'il server ha chiuso la connessione senza risposta',
                            56 => 'ricezione della risposta interrotta',
                            60 => 'verifica del certificato TLS non riuscita',
                        ][$number] ?? 'richiesta di rete non completata';
                        throw new ModInsertEventSerenadeError('CURL_' . $number . ': ' . $description . '.');
                    }
                    return ['code' => (int) curl_getinfo($curl, CURLINFO_HTTP_CODE), 'headers' => $headers, 'body' => $body];
                };
            }
            $day = new \DateTimeImmutable($data['data'], new \DateTimeZone('UTC'));
            // Include il giorno successivo: l'ultimo secondo frazionario del giorno
            // cercato resta all'interno dell'intervallo, non sul suo estremo finale.
            $phase = 'POST filtri';
            $post = $request('POST', $base . '?page=filters', [
                'action' => 'filter', 'network' => 'unknown', 'event_type' => 'earthquake',
                'minmag' => '', 'maxmag' => '', 'magtype' => ' ', 'minqual' => '0', 'maxqual' => '0',
                'start_date' => $day->format('Y-m-d'), 'end_date' => $day->modify('+1 day')->format('Y-m-d'),
                'start_time' => '', 'end_time' => '', 'minlev' => '', 'maxlev' => '',
            ]);
            if (!in_array($post['code'], [302, 303], true) || empty($post['headers']['location'])) {
                return $unknown('Il portale Serenade non ha creato la ricerca (HTTP ' . (int) $post['code'] . ').');
            }
            $phase = 'redirect dopo POST';
            $location = self::redirectUrl($base, $post['headers']['location']);
            parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
            if (($query['page'] ?? '') !== 'filters' || ($query['action'] ?? '') !== 'showmap'
                || !is_string($query['context'] ?? null) || !preg_match('/^[A-Za-z0-9_-]{1,128}$/D', $query['context'])) {
                return $unknown('Il portale Serenade non ha restituito un contesto di ricerca valido.');
            }
            // Mantiene il percorso verificato //serenade/, evitando l'alias /wessel/serenade/.
            $target = $base . '?' . http_build_query(['page' => 'filters', 'action' => 'events', 'context' => $query['context']]);
            for ($redirects = 0; $redirects <= 3; $redirects++) {
                $phase = 'GET risultati';
                $response = $request('GET', $target);
                if (in_array($response['code'], [301, 302, 303, 307, 308], true) && !empty($response['headers']['location'])) {
                    $phase = 'redirect risultati';
                    $target = self::redirectUrl($target, $response['headers']['location']);
                    continue;
                }
                if ($response['code'] !== 200) {
                    return $unknown('Lettura dei risultati Serenade non riuscita (HTTP ' . (int) $response['code'] . ').');
                }
                $phase = 'lettura JSON';
                try {
                    $ids = ModInsertEventHelper::trovaEventiSerenade($response['body'], $data['data'], $data['ora']);
                } catch (\Throwable $e) {
                    $html = preg_match('/^\s*(?:<!doctype\s+html|<html|<head|<body|<br\b)/i', $response['body']) === 1;
                    throw new ModInsertEventSerenadeError('RESPONSE_JSON: HTTP 200, ma '
                        . ($html ? 'è stata ricevuta una pagina HTML invece degli eventi JSON' : 'il contenuto o la struttura degli eventi JSON non è riconosciuto')
                        . ' (' . strlen($response['body']) . ' byte).');
                }
                return [
                    'status' => $ids ? 'found' : ($completa ? 'not_found' : 'unknown'),
                    'ids' => $ids,
                    'detail' => !$ids && !$completa ? 'Nessuna corrispondenza nei risultati filtrati; la completezza della risposta non è ancora confermata nelle impostazioni.' : '',
                ];
            }
            return $unknown('Troppi reindirizzamenti durante la ricerca Serenade.');
        } catch (ModInsertEventSerenadeError $e) {
            return $unknown('[' . $phase . '] ' . $e->getMessage());
        } catch (\Throwable $e) {
            return $unknown('[' . $phase . '] PHP_ERROR: errore interno durante la ricerca (' . get_class($e) . ').');
        } finally {
            if ($curl !== null) {
                curl_close($curl);
            }
        }
    }

    /** Risolve i redirect senza inviare richieste a host o protocolli differenti. */
    private static function redirectUrl(string $source, string $location): string
    {
        $origin = parse_url($source);
        $authority = $origin['scheme'] . '://' . $origin['host'] . (isset($origin['port']) ? ':' . $origin['port'] : '');
        if (preg_match('#^https?://#i', $location) || str_starts_with($location, '//')) {
            $target = str_starts_with($location, '//') ? $origin['scheme'] . ':' . $location : $location;
        } elseif (str_starts_with($location, '/')) {
            $target = $authority . $location;
        } elseif (str_starts_with($location, '?')) {
            $target = $authority . ($origin['path'] ?? '/') . $location;
        } else {
            $target = $authority . substr($origin['path'] ?? '/', 0, strrpos($origin['path'] ?? '/', '/') + 1) . $location;
        }
        $parsed = parse_url($target);
        $port = static fn(array $p): int => $p['port'] ?? (strtolower($p['scheme'] ?? '') === 'https' ? 443 : 80);
        if (!$parsed || isset($parsed['user']) || isset($parsed['pass'])
            || strtolower($parsed['scheme'] ?? '') !== strtolower($origin['scheme'])
            || strtolower($parsed['host'] ?? '') !== strtolower($origin['host']) || $port($parsed) !== $port($origin)) {
            throw new ModInsertEventSerenadeError('REDIRECT_ORIGIN: il portale richiede un cambio di host, protocollo o porta non consentito dalla configurazione corrente.');
        }
        return $target;
    }
}
