# Modificare le informazioni di un evento

Se la pagina **hermes manager** è vuota, aprire nell'amministrazione Joomla il
modulo **mod_hermesmanager** e, in **Assegnazione menu**, abilitarlo per la voce
**hermes manager**. Nel database iniziale era assegnato soltanto alla pagina
**upload immagini**, perciò l'articolo della pagina di gestione non lo mostrava.

Aprire la pagina che contiene il modulo **mod_hermesmanager**, inserire l'ID
dell'evento (visibile in **Mostra dettagli** nella vcard della homepage) e premere
**Carica evento**. Il form mostra i valori attualmente salvati.

Si possono aggiungere o correggere informazioni e rimuovere quelle facoltative
svuotando il campo. **Salva modifiche** aggiorna lo stesso ID, senza creare un
nuovo evento. Data, ora UTC, tipo e area sono obbligatori. Zero è un valore valido
per i campi numerici; un campo numerico vuoto viene salvato come NULL.

**Link aggiuntivi** accetta un URL HTTP/HTTPS per riga. Per eliminarne uno basta
cancellare la riga; per rimuoverli tutti lasciare il campo vuoto. Le note
supportano più righe. La homepage mostra i dati aggiornati dopo il ricaricamento.

Le immagini hanno i comandi separati **Aggiungi immagine** ed **Elimina immagine**.
**Elimina intero evento** elimina invece il record e tutte le associazioni a
immagini, pubblicazioni e file, dopo conferma. La cancellazione viene eseguita in
una transazione: se un vincolo impedisce l'operazione, vengono ripristinate anche
le associazioni già rimosse. Le copie dei file sul disco vengono conservate,
come nel precedente comando di cancellazione evento. Le operazioni che modificano
dati richiedono POST e token CSRF.

Il modulo riutilizza la validazione di data/ora e URL di `mod_insert_event`, che
deve essere presente. Per i link è richiesta la colonna `link_aggiuntivi`, già
prevista dalla migrazione 1.3.0 del modulo di inserimento. Non serve un'ulteriore
migrazione per questa modifica.

L'accesso al modulo segue le impostazioni Joomla esistenti: assegnare il modulo
alle pagine e ai livelli di accesso degli utenti che devono gestire gli eventi.
Il modulo non effettua richieste a Wessel/Serenade durante la modifica.
