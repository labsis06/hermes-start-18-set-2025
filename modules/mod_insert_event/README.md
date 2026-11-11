# Controllo prima dell'inserimento

## Immagini, pubblicazioni e file generici multipli

Il form contiene tre selezioni facoltative e multiple: **Immagini**,
**Pubblicazioni**, **File generici**. Selezionare più file insieme nella finestra
di scelta (Ctrl/Shift su Windows). Sotto ogni campo viene mostrato l'elenco dei
nomi selezionati. I tre gruppi vengono associati automaticamente allo stesso ID
evento nelle tabelle esistenti `hermes_immagini`, `hermes_pubblicazioni` e
`hermes_files`; non serve una nuova migrazione SQL per i caricamenti multipli.

Gli allegati restano nel browser durante il controllo duplicati e vengono
trasferiti soltanto nell'invio finale. **Annulla** mantiene tutte le selezioni e
non salva nulla. **Continua inserimento** autorizza l'inserimento con tutti gli
allegati scelti. Sono supportati JPEG, PNG, GIF, WebP e BMP validi per le immagini;
le altre due categorie accettano documenti e dati, senza interpretarli come codice.

Il server valida tutti i file prima di inserire il record. Evento e associazioni
degli allegati sono salvati nella stessa transazione InnoDB; il lock sui duplicati
resta attivo fino al commit. Se un salvataggio fallisce, la transazione viene
annullata e i file appena spostati vengono rimossi. Il messaggio finale mostra
quanti file sono stati salvati per ciascuna categoria.

I nomi fisici sono casuali per evitare sovrascritture, anche quando due file hanno
lo stesso nome originale. I documenti e dati sono conservati con estensione
`.bin` per non renderli eseguibili dal server web. I nomi originali sono mantenuti
nel database e nell'attributo `download` dei link della vcard: l'utente scarica il
file con nome ed estensione originali. Le immagini hanno l'estensione del formato
effettivamente rilevato, non quella dichiarata dal browser.

I limiti PHP correnti (`max_file_uploads`, `upload_max_filesize`, `post_max_size`)
sono mostrati nel form. Il massimo numero di file vale per la somma delle tre
categorie. Il browser controlla numero e dimensioni; il server rileva anche invii
parziali, file vuoti, richieste troppo grandi e allegati mancanti per troncamento.
Per cambiare le soglie occorre aggiornare la configurazione PHP del server.

Distribuire anche il nuovo file `attachments.php` insieme a helper, file
principale, layout e manifest di `mod_insert_event`, e al layout di
`mod_hermeseventi`. I form separati per aggiungere allegati a eventi già esistenti
mantengono il loro funzionamento precedente.

## Link aggiuntivi dell'evento

Il form contiene un campo facoltativo **Link aggiuntivi**, con un URL completo
HTTP/HTTPS per riga. Gli indirizzi vengono validati, privati delle righe vuote e
dei duplicati, e salvati nella colonna `hermes_eventi.link_aggiuntivi`.
Nelle vcard della homepage compaiono nei dettagli subito sotto **Wessel link**,
in nuove schede; per gli eventi senza link viene mostrato **ND**.

Prima di aggiornare i file sul sito, eseguire una sola volta sul database Joomla
lo script `modules/mod_insert_event/sql/1.3.0-link-aggiuntivi.sql`. Aggiunge una
colonna facoltativa senza cambiare i dati degli eventi esistenti. Poi aggiornare
helper e layout sia di `mod_insert_event` sia di `mod_hermeseventi`, insieme al
manifest XML di `mod_insert_event`. Questo aggiornamento non riattiva Serenade.

## Duplicati

Il modulo confronta data e ora UTC al secondo. Le frazioni di secondo delle
date Serenade vengono ignorate, senza arrotondamento.

Prima di inviare l'evento e l'immagine, il form interroga il database locale e,
solo se abilitato, l'endpoint Serenade configurato. Se trova corrispondenze mostra gli ID dei
i database e chiede **Continua inserimento** oppure **Annulla**. Annullare mantiene
i dati e il file selezionato nel modulo, senza salvare record o immagini.
Continuare crea un nuovo record locale, anche se è un duplicato; non modifica
gli eventi esistenti e non scrive mai in Serenade.

Se Serenade non è configurato, non è raggiungibile o restituisce una risposta
incompleta, il messaggio indica **presenza non verificata** e richiede una scelta
esplicita. Non viene mostrata una falsa assenza dal database.

## Configurazione Serenade

Per il momento il valore predefinito è **Disattivato — controlla solo il database
locale**. Scegliere questa opzione e salvare anche nei moduli già configurati:
un'impostazione `portal` o `json` salvata in precedenza resta attiva fino alla
modifica. In modalità disattivata non partono richieste di rete e la conferma
compare soltanto per duplicati locali. In quel caso il messaggio indica anche
che il controllo Wessel/Serenade è disattivato. Le modalità remote restano
disponibili per una futura riattivazione.

Nelle impostazioni del modulo, scheda **Controllo Serenade**:

1. Selezionare **Metodo di ricerca → Portale Wessel: POST filtri e GET risultati**.
   Lasciare **URL portale Wessel** a
   `http://wessel.ov.ingv.it//serenade/index.php`, compresa la doppia barra.
   Non aggiungere un `context`: viene creato a ogni verifica in una sessione
   privata cURL mantenuta in memoria, senza cookie del browser o file condivisi.
   Il modulo invia `action=filter`, `network=unknown`, `event_type=earthquake`,
   `start_date` pari alla data richiesta ed `end_date` al giorno successivo,
   lasciando vuote le ore. L'intervallo include il giorno successivo per evitare
   di perdere eventi con frazioni nell'ultimo secondo del giorno cercato.
   Dalla risposta POST legge il contesto nel redirect, poi richiede
   `page=filters&action=events&context=...` e confronta data e ora nel JSON.
   I reindirizzamenti GET sono limitati e ammessi solo sullo stesso host,
   protocollo e porta. Timeout, errori e risposte oltre 16 MiB diventano una
   verifica non completata. Il controllo del portale è supportato per
   `tipo=terremoto`; gli altri tipi risultano non verificati.
2. Attivare **Risposta completa per l'istante cercato** solo dopo aver verificato
   che il servizio restituisca tutti gli eventi richiesti, senza pagine mancanti,
   troncamenti o limiti. Lasciandolo disattivato, una corrispondenza dimostra la
   presenza, ma una risposta senza corrispondenze resta una verifica incompleta.

Il metodo alternativo **Endpoint JSON diretto (GET)** conserva il comportamento
precedente e usa il campo **URL ricerca Serenade**. Supporta i segnaposto
`{data}`, `{ora}`, `{inizio}` e `{fine}` (intervallo UTC di un secondo), ma richiede
un endpoint con filtri documentati: `origintime=...` non ha filtrato l'elenco
`/events/flegrei` nelle prove dell'utente.

Il flusso del portale è stato ricostruito dalle richieste dell'utente e dal JSON
reale ricevuto: otto terremoti del 1–2 novembre 2026. Test locali verificano
multipart POST, sessioni nuove, cookie, contesti dinamici, redirect e confronto
dei risultati. L'accesso reale da questo ambiente cloud continua a restituire
HTTP 403: il server Joomla deve poter raggiungere Wessel. La prova dei due giorni
non dimostra da sola l'assenza di limiti o paginazione in ogni ricerca; lasciare
la completezza disattivata finché non è confermata.

## Conferma e verifiche

La verifica usa `com_ajax`, il token CSRF Joomla e un ticket di sessione monouso
valido dieci minuti, legato al modulo e a tutti i dati dell'evento. Sul salvataggio
vengono ricontrollati i duplicati locali sotto lock: nuove corrispondenze comparse
dopo la verifica richiedono un nuovo controllo e una nuova conferma. La verifica
Serenade fotografa il risultato remoto al momento della richiesta iniziale.

JavaScript deve essere attivo. L'immagine viene inviata soltanto nel POST finale.
I test PHP sono in `tests/mod_insert_event_test.php`; la parte database richiede
una tabella `hermes_eventi` vuota, con lo schema del sito, in un database
temporaneo il cui nome termini con `_test` (variabili `HERMES_TEST_DATABASE` e
`HERMES_TEST_SOCKET`). Il test usa l'utente locale `agent` tramite socket e annulla
le proprie transazioni. Non eseguirlo sul database del sito.
