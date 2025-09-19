<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Log\Log;

class ModInsertEventHelper
{
    public static function salvaEvento($data)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        // Inserimento in hermes_eventi
        $columns = ['data', 'ora', 'tipo', 'area', 'stazione_first', 'comp1', 'note', 'Md', 'prof', 'lat', 'lon'];

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
            $db->quote($data['lon'])
        ];

        $query
            ->insert($db->quoteName('hermes_eventi'))
            ->columns($db->quoteName($columns))
            ->values(implode(',', $values));

        try {
            $db->setQuery($query);
            $db->execute();

            return (int) $db->insertid();
        } catch (Exception $e) {
            echo '<pre><strong>ERRORE:</strong> ' . $e->getMessage() . '</pre>';
            return false;
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
