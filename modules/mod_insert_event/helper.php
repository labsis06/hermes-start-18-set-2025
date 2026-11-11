<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Log\Log;

require_once __DIR__ . '/serenade.php';

class ModInsertEventHelper
{
    /** Date e ore del form sono UTC, con precisione al secondo. */
    public static function normalizzaDataOra(string $data, string $ora): array
    {
        $data = trim($data);
        $ora = trim($ora);
        if (preg_match('/^\d{2}:\d{2}$/D', $ora)) {
            $ora .= ':00';
        }
        $value = $data . ' ' . $ora;
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new \DateTimeZone('UTC'));
        if (!$date || $date->format('Y-m-d H:i:s') !== $value) {
            throw new \InvalidArgumentException('Data o ora non valida. Specificare data e ora UTC.');
        }

        return ['data' => $data, 'ora' => $ora];
    }

    public static function trovaDuplicatoLocale(string $data, string $ora): ?int
    {
        return self::trovaDuplicatiLocali($data, $ora)[0] ?? null;
    }

    public static function trovaDuplicatiLocali(string $data, string $ora): array
    {
        $when = self::normalizzaDataOra($data, $ora);
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('hermes_eventi'))
            ->where($db->quoteName('data') . ' = ' . $db->quote($when['data']))
            ->where($db->quoteName('ora') . ' = ' . $db->quote($when['ora']))
            ->order($db->quoteName('id') . ' ASC');
        return array_map('intval', $db->setQuery($query)->loadColumn());
    }

    public static function leggiDatiEvento($input): array
    {
        $data = [];
        foreach (['data', 'ora', 'tipo', 'area', 'stazione', 'componente', 'note', 'Md', 'profondita', 'lat', 'lon', 'wessel_link', 'link_aggiuntivi'] as $field) {
            $data[$field] = $input->getString($field, '');
        }
        $data['link_aggiuntivi'] = self::normalizzaLinkAggiuntivi($data['link_aggiuntivi']);
        $data['wessel_link'] = self::normalizzaWesselLink($data['wessel_link']);
        return array_replace($data, self::normalizzaDataOra($data['data'], $data['ora']));
    }

    public static function normalizzaWesselLink(string $value): string
    {
        $value = trim($value);
        if ($value === '') return '';
        // Mantiene la compatibilità con gli indirizzi senza schema già gestiti nelle vcard.
        if (!preg_match('/^[a-z][a-z0-9+.-]*:/i', $value)) $value = 'http://' . $value;
        try {
            if (strlen($value) > 1000 || preg_match('/[\r\n]/', $value)) throw new \InvalidArgumentException();
            return self::normalizzaLinkAggiuntivi($value);
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException('Wessel link: inserire un solo indirizzo HTTP/HTTPS valido, lungo al massimo 1000 caratteri.');
        }
    }

    /** Più URL informativi, uno per riga. Mai codice HTML o schemi eseguibili. */
    public static function normalizzaLinkAggiuntivi(string $text): string
    {
        $urls = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) as $index => $line) {
            $url = trim($line);
            if ($url === '') {
                continue;
            }
            $parts = parse_url($url);
            if (!filter_var($url, FILTER_VALIDATE_URL) || !$parts
                || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
                || isset($parts['user']) || isset($parts['pass']) || preg_match('/[\x00-\x20\x7f]/', $url)) {
                throw new \InvalidArgumentException('Link aggiuntivi: URL non valido alla riga ' . ($index + 1) . '. Inserire un indirizzo completo http:// o https://, uno per riga.');
            }
            $urls[] = $url;
        }
        $normalized = implode("\n", array_values(array_unique($urls)));
        if (strlen($normalized) > 65535) {
            throw new \InvalidArgumentException('Link aggiuntivi troppo lunghi: ridurre il numero o la lunghezza degli indirizzi.');
        }
        return $normalized;
    }

    /** La risposta di un endpoint parziale non dimostra l'assenza da Serenade. */
    public static function controllaSerenade(array $data, string $url, bool $rispostaCompleta = false, $http = null): array
    {
        if (trim($url) === '') {
            return ['status' => 'unknown', 'ids' => [], 'detail' => 'URL di ricerca Serenade non configurato.'];
        }
        $start = new \DateTimeImmutable($data['data'] . ' ' . $data['ora'], new \DateTimeZone('UTC'));
        $url = strtr(trim($url), [
            '{data}' => rawurlencode($data['data']), '{ora}' => rawurlencode($data['ora']),
            '{inizio}' => rawurlencode($start->format('Y-m-d H:i:s')),
            '{fine}' => rawurlencode($start->modify('+1 second')->format('Y-m-d H:i:s')),
        ]);
        if (!in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            return ['status' => 'unknown', 'ids' => [], 'detail' => 'URL Serenade non valido.'];
        }
        try {
            $http = $http ?: \Joomla\CMS\Http\HttpFactory::getHttp();
            $response = $http->get($url, ['Accept' => 'application/json'], 10);
            if ((int) $response->code !== 200) {
                return ['status' => 'unknown', 'ids' => [], 'detail' => 'Serenade ha risposto HTTP ' . (int) $response->code . ': controllo non completato.'];
            }
            $ids = self::trovaEventiSerenade($response->body, $data['data'], $data['ora']);
            return [
                'status' => $ids ? 'found' : ($rispostaCompleta ? 'not_found' : 'unknown'),
                'ids' => $ids,
                'detail' => !$ids && !$rispostaCompleta
                    ? 'Nessuna corrispondenza nella risposta consultata; la copertura della ricerca Serenade non è confermata.' : '',
            ];
        } catch (\Throwable $e) {
            // Non esporre dettagli di connessione o eventuali credenziali nella pagina.
            return ['status' => 'unknown', 'ids' => [], 'detail' => 'Controllo Serenade non riuscito (servizio non raggiungibile o risposta non valida).'];
        }
    }

    public static function richiedeConferma(array $result): bool
    {
        return !empty($result['local_ids']) || !in_array($result['serenade']['status'], ['not_found', 'disabled'], true);
    }

    public static function controllaSerenadeConfigurato(array $data, $params): array
    {
        return match ($params->get('serenade_mode', 'off')) {
            'portal' => ModInsertEventSerenade::cerca($data, $params->get('serenade_portal_url', 'http://wessel.ov.ingv.it//serenade/index.php'), (bool) $params->get('serenade_complete', 0)),
            'json' => self::controllaSerenade($data, $params->get('serenade_url', ''), (bool) $params->get('serenade_complete', 0)),
            default => ['status' => 'disabled', 'ids' => [], 'detail' => 'Controllo Wessel/Serenade disattivato.'],
        };
    }

    public static function creaTicket($session, int $moduleId, array $data, array $result): string
    {
        $token = bin2hex(random_bytes(32));
        $session->set('mod_insert_event.check.' . $moduleId, [
            'token' => $token, 'expires' => time() + 600,
            'hash' => hash('sha256', json_encode($data, JSON_THROW_ON_ERROR)), 'result' => $result,
        ]);
        return $token;
    }

    /** Ticket legato a sessione, modulo e tutti i dati: monouso e valido 10 minuti. */
    public static function consumaTicket($session, int $moduleId, array $data, string $token, bool $confirmed): array
    {
        $key = 'mod_insert_event.check.' . $moduleId;
        $ticket = $session->get($key);
        if (!is_array($ticket) || $token === '' || !hash_equals($ticket['token'], $token)
            || $ticket['expires'] < time()
            || !hash_equals($ticket['hash'], hash('sha256', json_encode($data, JSON_THROW_ON_ERROR)))) {
            throw new \DomainException('Verifica scaduta o dati modificati. Ripetere il controllo prima di inserire.');
        }
        if (self::richiedeConferma($ticket['result']) && !$confirmed) {
            throw new \DomainException('Inserimento non confermato. Nessun dato salvato.');
        }
        $session->set($key, null);
        return $confirmed ? $ticket['result']['local_ids'] : [];
    }

    public static function checkAjax(): array
    {
        if (!\Joomla\CMS\Session\Session::checkToken('post')) {
            throw new \RuntimeException('Sessione scaduta. Ricaricare la pagina.', 403);
        }
        $app = Factory::getApplication();
        $moduleId = $app->getInput()->getInt('mod_id');
        $module = \Joomla\CMS\Helper\ModuleHelper::getModuleById((string) $moduleId);
        if (!$module->id || $module->module !== 'mod_insert_event') {
            throw new \RuntimeException('Modulo non accessibile.', 403);
        }
        $params = new \Joomla\Registry\Registry($module->params);
        $data = self::leggiDatiEvento($app->getInput());
        $result = [
            'local_ids' => self::trovaDuplicatiLocali($data['data'], $data['ora']),
            'serenade' => self::controllaSerenadeConfigurato($data, $params),
        ];
        return $result + [
            'data' => $data['data'], 'ora' => $data['ora'],
            'needs_confirmation' => self::richiedeConferma($result),
            'token' => self::creaTicket($app->getSession(), $moduleId, $data, $result),
        ];
    }

    /** Confronta date Serenade (anche frazionarie) senza arrotondare i secondi. */
    public static function trovaEventiSerenade(string $json, string $data, string $ora): array
    {
        $when = self::normalizzaDataOra($data, $ora);
        try {
            $events = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Serenade ha restituito una risposta JSON non valida.', 0, $e);
        }
        if (!is_array($events)) {
            throw new \RuntimeException('Formato della risposta Serenade non riconosciuto.');
        }
        // Sono accettati un singolo evento oppure la mappa di eventi dell'API.
        if (isset($events['id'], $events['date'])) {
            $events = [$events];
        }
        $matches = [];
        foreach ($events as $event) {
            if (!is_array($event) || !isset($event['id'], $event['date'])
                || !is_scalar($event['id']) || !ctype_digit((string) $event['id'])
                || !is_string($event['date'])
                || !preg_match('/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2}:\d{2})(?:\.\d+)?$/D', $event['date'], $parts)) {
                throw new \RuntimeException('Formato degli eventi Serenade non riconosciuto: controllo non completato.');
            }
            self::normalizzaDataOra($parts[1], $parts[2]);
            if ($parts[1] === $when['data'] && $parts[2] === $when['ora']) {
                $matches[(int) $event['id']] = (int) $event['id'];
            }
        }

        return array_values($matches);
    }

    public static function salvaEvento($data, array $duplicatiConfermati = [], ?callable $afterInsert = null)
    {
        $data = array_replace($data, self::normalizzaDataOra($data['data'], $data['ora']));
        $links = self::normalizzaLinkAggiuntivi($data['link_aggiuntivi'] ?? '');
        $wesselLink = self::normalizzaWesselLink($data['wessel_link'] ?? '');
        $db = Factory::getDbo();
        // Serializza gli invii concorrenti dello stesso istante da questo modulo.
        $lock = 'hermes_eventi:' . sha1($data['data'] . ' ' . $data['ora']);
        if ((int) $db->setQuery('SELECT GET_LOCK(' . $db->quote($lock) . ', 5)')->loadResult() !== 1) {
            throw new \RuntimeException('Inserimento in corso per questa data e ora. Riprovare tra pochi secondi.');
        }
        $transaction = false;
        try {
            if ($afterInsert !== null) {
                $db->transactionStart();
                $transaction = true;
            }
            $existingIds = self::trovaDuplicatiLocali($data['data'], $data['ora']);
            if (array_diff($existingIds, $duplicatiConfermati)) {
                throw new \DomainException('Evento già presente nel database locale (ID: ' . implode(', ', $existingIds) . '). Ripetere il controllo e confermare per continuare.');
            }
            $query = $db->getQuery(true);

            // Inserimento in hermes_eventi
            $columns = ['data', 'ora', 'tipo', 'area', 'stazione_first', 'comp1', 'note', 'Md', 'prof', 'lat', 'lon', 'wessel_link', 'link_aggiuntivi'];

            $mdValue = $data['Md'] === null || $data['Md'] === ''
                ? 'NULL'
                : $db->quote($data['Md']);

            $profValue = $data['profondita'] === null || $data['profondita'] === ''
                ? 'NULL'
                : $db->quote($data['profondita']);

            $values = [
                $db->quote($data['data']),
                $db->quote($data['ora']),
                $db->quote($data['tipo']),
                $db->quote($data['area']),
                $db->quote($data['stazione']),
                $db->quote($data['componente']),
                $db->quote($data['note']),
                $mdValue,
                $profValue,
                $db->quote($data['lat']),
                $db->quote($data['lon']),
                $wesselLink === '' ? 'NULL' : $db->quote($wesselLink),
                $links === '' ? 'NULL' : $db->quote($links)
            ];

            $query
                ->insert($db->quoteName('hermes_eventi'))
                ->columns($db->quoteName($columns))
                ->values(implode(',', $values));

            $db->setQuery($query);
            $db->execute();

            $id = (int) $db->insertid();
            if ($afterInsert !== null) $afterInsert($id);
            if ($transaction) {
                $db->transactionCommit();
                $transaction = false;
            }
            return $id;
        } catch (\Throwable $e) {
            if ($transaction) $db->transactionRollback();
            throw $e;
        } finally {
            $db->setQuery('SELECT RELEASE_LOCK(' . $db->quote($lock) . ')')->loadResult();
        }
    }

    /**
     * Upload a file to the target location using Joomla API.
     *
     * @param string $src        Temporary file path.
     * @param string $targetFile Destination file path.
     *
     * @return bool True on success, false on failure.
     */
    public static function uploadFile($src, $targetFile)
    {
        try {
            $dir = dirname($targetFile);

            if (!Folder::exists($dir)) {
                Folder::create($dir, 0755);
            }

            File::upload($src, $targetFile);

            return true;
        } catch (\Exception $e) {
            Log::add('File upload failed: ' . $e->getMessage(), Log::ERROR, 'mod_insert_event');

            return false;
        }
    }
}
