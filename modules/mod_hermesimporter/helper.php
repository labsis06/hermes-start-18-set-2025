<?php
defined('_JEXEC') or die;

class ModHermesImporterHelper
{
    public static function importEventData($eventId)
    {
        $url = "http://wessel.ov.ingv.it/serenade/index.php?page=showevent&area=unknown&eventid=" . intval($eventId);

        $html = @file_get_contents($url);
        if (!$html) {
            return "Impossibile scaricare la pagina HTML dall'URL: $url";
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        if (!$dom->loadHTML($html)) {
            return "Errore nel parsing HTML con DOMDocument.";
        }
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $fields = [
            'Origin Time' => '',
            'Latitude' => '',
            'Longitude' => '',
            'Depth (km)' => '',
            'Number of stations' => '',
            'Hor/Ver errors (km)' => '',
            'Nearest station dist. (km)' => '',
            'Area' => ''
        ];

        foreach ($fields as $label => &$value) {
            $td = $xpath->query("//tr[th[contains(text(), '$label')]]/td");
            if ($td->length > 0) {
                $value = trim($td->item(0)->textContent);
            } else {
                return "Campo '$label' non trovato nella pagina HTML.";
            }
        }

        $originTime = explode(' ', $fields['Origin Time']);
        if (count($originTime) < 2) return "Origin Time non contiene data e ora separabili.";
        $data = $originTime[0];
        $ora = $originTime[1];

        // Escludi la parte tra parentesi
        $lat = trim(preg_replace('/\s*\(.*\)/', '', $fields['Latitude']));
        $long = trim(preg_replace('/\s*\(.*\)/', '', $fields['Longitude']));

        $prof = str_replace(',', '.', $fields['Depth (km)']);
        $numero_stazioni = $fields['Number of stations'];

        if (!preg_match('/\s*\/\s*/', $fields['Hor/Ver errors (km)'])) return "Errore nel formato di Hor/Ver errors.";
        list($H_err, $V_err) = preg_split('/\s*\/\s*/', $fields['Hor/Ver errors (km)']);
        $H_err = str_replace(',', '.', $H_err);
        $V_err = str_replace(',', '.', $V_err);

        $stazione_near = str_replace(',', '.', $fields['Nearest station dist. (km)']);
        $area = $fields['Area'];
        $tipo = "terremoto";
      
        // Magnitudo da <td>M<sub>d</sub></td> -> <td>0.6 ± 0.3</td>
        $magnitudeNode = $xpath->query("//tr[td[contains(., 'M')]]/td[2]");
        if ($magnitudeNode->length > 0) {
            $rawMag = trim($magnitudeNode->item(0)->textContent);
            if (preg_match('/([\d\.]+)/', $rawMag, $matches)) {
                $Md = $matches[1];
            } else {
                return "Formato magnitudo non valido: '$rawMag'";
            }
        } else {
            return "Riga con magnitudo non trovata nella tabella.";
        }

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $columns = ['data', 'ora', 'lat', 'lon', 'prof', 'numero_stazioni', 'H_err', 'V_err', 'stazione_near', 'area', 'tipo', 'Md', 'wessel_id'];
        $values = [
            
            $db->quote($data),
            $db->quote($ora),
            $db->quote($lat),
            $db->quote($long),
            (float)$prof,
            (int)$numero_stazioni,
            (float)$H_err,
            (float)$V_err,
            (float)$stazione_near,
            $db->quote($area),
            $db->quote($tipo),
            (float)$Md,
            $db->quote($eventId)
        ];

        $query
            ->insert($db->quoteName('hermes_eventi'))
            ->columns($db->quoteName($columns))
            ->values(implode(',', $values));

        try {
    $db->setQuery($query)->execute();
    $eventIdDb = $db->insertid(); // recupera l'ID generato da AUTO_INCREMENT

    return $eventIdDb; // lo restituisce alla funzione chiamante
} catch (RuntimeException $e) {
    return "Errore DB: " . $e->getMessage();
}
    }
}
