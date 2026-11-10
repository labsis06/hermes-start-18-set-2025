-- Eseguire UNA SOLA VOLTA sul database Joomla, prima di aggiornare i file PHP.
-- Gli eventi esistenti conservano tutti i dati e hanno link_aggiuntivi = NULL.
ALTER TABLE `hermes_eventi`
    ADD COLUMN `link_aggiuntivi` TEXT NULL AFTER `wessel_link`;
