# Modifica diretta dalla vcard

Nei dettagli di ogni evento, sotto l'ID, il link **Modifica evento** apre la pagina
di Hermes Manager con l'evento già caricato (campi e allegati). Il collegamento
passa il parametro di lettura `hermes_edit_event`; aprirlo non salva, non elimina
e non avvia alcun caricamento. Le operazioni di modifica mantengono POST e CSRF.

Per impostazione predefinita il modulo cerca fra le voci di menu accessibili
l'articolo che include un `mod_hermesmanager` pubblicato, accessibile e assegnato
a quella pagina. Riconosce i tag Joomla `loadmodule`, `loadmoduleid` e
`loadposition`. Rispetta pubblicazione e accesso dell'articolo e i filtri Joomla
su accesso, pubblicazione e lingua del menu e del modulo di destinazione.
Nessun ID articolo, ID modulo o indirizzo del sito è fissato nel codice.

In configurazioni personalizzate, nelle impostazioni di **mod_hermeseventi**
scegliere **Pagina di modifica eventi**, selezionando la voce di menu che mostra
Hermes Manager. Con la scelta esplicita il modulo deve comunque essere pubblicato
e assegnato a quella pagina. La selezione non modifica le assegnazioni o gli ACL.

Se non c'è una destinazione utilizzabile il link non viene mostrato. Controllare
la pubblicazione e l'assegnazione menu di `mod_hermesmanager`, la pagina scelta,
i livelli di accesso e, per gli articoli con tag di inclusione, l'attivazione del
plugin **Contenuto - Carica moduli**.

Aggiornare `mod_hermeseventi` (file principale, helper, layout e manifest) e
`mod_hermesmanager/mod_hermesmanager.php`. Non servono modifiche al database.
