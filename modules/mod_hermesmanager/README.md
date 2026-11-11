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

## Immagini, pubblicazioni e file generici

Dopo aver caricato un evento, sono disponibili tre sezioni: **Immagini associate**,
**Pubblicazioni associate**, **File generici associati**. Ciascuna mostra i file
attualmente collegati all'evento, con un link per scaricarli; le immagini hanno
anche un'anteprima. Salvare eventuali modifiche ai dati prima di gestire gli allegati.

In ogni sezione si può:

- **Carica allegati**: aggiungere più file insieme, usando Ctrl/Shift nella finestra
  di scelta. Il caricamento modifica l'evento esistente, senza crearne uno nuovo.
- **Rinomina**: cambiare il nome visualizzato e quello proposto al download,
  mantenendo il contenuto e il percorso fisico. Mantenere l'estensione corretta.
- **Sostituisci**: caricare un singolo nuovo file al posto di quello selezionato.
  Dopo il salvataggio viene mostrato il nome del nuovo file. In caso di errore,
  il vecchio allegato e la sua copia rimangono disponibili.
- **Elimina allegato**: rimuovere il collegamento all'evento dopo conferma. La copia
  sul disco è rimossa solo se non è usata da altri allegati e si trova nella
  cartella prevista. File mancanti, percorsi esterni o copie condivise non
  impediscono la rimozione del collegamento; un'eventuale copia conservata viene
  indicata nel messaggio. Nessun altro evento viene modificato.

I caricamenti riutilizzano la validazione e i nomi fisici casuali del form di
inserimento: immagini valide, documenti e dati conservati come `.bin` con nome
originale al download, nessuna sovrascrittura per nomi uguali. I limiti PHP sono
mostrati nel modulo; il conteggio client viene verificato anche lato server.
Caricamenti e sostituzioni sono transazionali e ripuliscono i nuovi file in caso
di errore. Le operazioni verificano sempre categoria, evento e ID dell'allegato.

## Cancellazione dell'evento

**Elimina intero evento** elimina invece il record e tutte le associazioni a
immagini, pubblicazioni e file, dopo conferma. La cancellazione viene eseguita in
una transazione: se un vincolo impedisce l'operazione, vengono ripristinate anche
le associazioni già rimosse. Le copie dei file sul disco vengono conservate,
come nel precedente comando di cancellazione evento. Le operazioni che modificano
dati richiedono POST e token CSRF.

Il modulo riutilizza la validazione di data/ora e URL di `mod_insert_event`, e il
file `mod_insert_event/attachments.php` per i caricamenti: entrambi devono essere
presenti e aggiornati. Per i link è richiesta la colonna `link_aggiuntivi`, già
prevista dalla migrazione 1.3.0 del modulo di inserimento. Non serve un'ulteriore
migrazione per questa modifica.

L'accesso al modulo segue le impostazioni Joomla esistenti: assegnare il modulo
alle pagine e ai livelli di accesso degli utenti che devono gestire gli eventi.
Il modulo non effettua richieste a Wessel/Serenade durante la modifica.
