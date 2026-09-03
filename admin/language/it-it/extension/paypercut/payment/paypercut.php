<?php
// Heading
$_['heading_title'] = 'Gateway di Pagamento Paypercut';

// Text
$_['text_extension'] = 'Estensioni';
$_['text_success'] = 'Operazione riuscita: Hai modificato il modulo di pagamento Paypercut!';
$_['text_edit'] = 'Modifica Paypercut';
$_['text_paypercut'] = '<img src="/extension/paypercut/admin/view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE; height: 25px;" />';

// Entry
$_['entry_api_key'] = 'Chiave API';
$_['entry_operating_account'] = 'ID Conto Operativo';
$_['entry_statement_descriptor'] = 'Descrizione Estratto Conto';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_applepay_domain_file'] = 'File di Dominio Apple Pay';
$_['entry_checkout_mode'] = 'Modalità Checkout';
$_['entry_webhook_url'] = 'URL Webhook';
$_['entry_order_status'] = 'Stato Ordine';
$_['entry_status'] = 'Stato';
$_['entry_sort_order'] = 'Ordine di Visualizzazione';
$_['entry_logging'] = 'Abilita Registrazione';
$_['entry_payment_method_config'] = 'Configurazione Metodo di Pagamento';

// Help
$_['help_api_key'] = 'Inserisci la tua Chiave API Paypercut dalla dashboard';
$_['help_operating_account'] = 'Inserisci il tuo ID Conto Operativo (disponibile nella Dashboard Paypercut)';
$_['help_statement_descriptor'] = 'Testo che appare sull\'estratto conto bancario del cliente (max 22 caratteri). Lasciare vuoto per utilizzare il predefinito.';
$_['help_google_pay'] = 'Abilita Google Pay come opzione di pagamento';
$_['help_apple_pay'] = 'Abilita Apple Pay come opzione di pagamento';
$_['help_applepay_domain_file'] = 'Apple Pay richiede un file di verifica del dominio in <code>/.well-known/apple-developer-merchantid-domain-association</code>. Il modulo lo distribuisce automaticamente durante l\'installazione e al salvataggio delle impostazioni.';
$_['help_checkout_mode'] = 'Scegli tra hosted (reindirizzamento alla pagina Paypercut) o embedded (checkout sul tuo sito) per l\'esperienza di pagamento';
$_['help_webhook_url'] = 'Copia questo URL e configuralo nella tua Dashboard Paypercut in Sviluppatori > Webhook';
$_['help_logging'] = 'Abilita la registrazione di richieste API, eventi webhook ed errori. Disabilita in produzione a meno che non sia necessario il debug. I log possono contenere dati sensibili.';
$_['help_payment_method_config'] = 'Seleziona una configurazione del metodo di pagamento (profilo di pagamento) per controllare quali metodi di pagamento sono disponibili per i clienti. Lasciare vuoto per utilizzare il predefinito.';

// Error
$_['error_permission'] = 'Attenzione: Non hai i permessi per modificare il modulo di pagamento Paypercut!';
$_['error_api_key'] = 'Chiave API obbligatoria!';
$_['error_statement_descriptor'] = 'La descrizione dell\'estratto conto deve essere di massimo 22 caratteri!';
$_['error_unsupported_currency'] = 'Attenzione: La valuta del tuo negozio (%s) non è supportata da Paypercut. Valute supportate: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';
$_['error_applepay_domain_write'] = 'Impossibile scrivere il file di verifica Apple Pay (percorso di destinazione: %s). Controlla i permessi del filesystem per la webroot di OpenCart.';

// Text
$_['text_mode_test'] = 'Modalità Test';
$_['text_mode_live'] = 'Modalità Live';
$_['text_mode_unknown'] = 'Modalità Sconosciuta';
$_['text_enabled'] = 'Abilitato';
$_['text_disabled'] = 'Disabilitato';
$_['text_hosted'] = 'Hosted (Reindirizzamento)';
$_['text_embedded'] = 'Embedded (Sul sito)';
$_['text_statement_preview'] = 'Anteprima';
$_['text_webhook_info'] = 'Configura questo URL webhook nella tua <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">Dashboard Paypercut</a>';
$_['text_webhook_configured'] = 'Webhook configurato e attivo';
$_['text_webhook_not_configured'] = 'Webhook non configurato';
$_['text_webhook_create'] = 'Crea Webhook Automaticamente';
$_['text_webhook_delete'] = 'Elimina Webhook';
$_['text_webhook_creating'] = 'Creazione webhook in corso...';
$_['text_webhook_deleting'] = 'Eliminazione webhook in corso...';
$_['text_wallet_settings'] = 'Impostazioni Wallet';
$_['text_testing_connection'] = 'Test connessione in corso...';
$_['text_connection_success'] = 'Connessione riuscita!';
$_['text_connection_failed'] = 'Connessione fallita';

// Apple Pay domain verification file
$_['text_applepay_domain_ok'] = 'File di dominio Apple Pay verificato';
$_['text_applepay_domain_warning'] = 'File di dominio Apple Pay distribuito ma non verificato';
$_['text_applepay_domain_missing'] = 'File di dominio Apple Pay mancante';
$_['text_applepay_domain_path'] = 'Percorso: %s';
$_['text_applepay_domain_refreshing'] = 'Aggiornamento dal CDN PayPerCut...';
$_['text_applepay_domain_manual_help'] = 'Scarica <a href="https://cdn.paypercut.io/.well-known/apple-developer-merchantid-domain-association" target="_blank">il file di verifica</a> e posizionalo manualmente in:';
$_['button_applepay_domain_refresh'] = 'Aggiorna dal CDN PayPerCut';

// Button
$_['button_test_connection'] = 'Testa Connessione';

// Environment
$_['entry_environment'] = 'Paypercut Environment';
$_['help_environment'] = 'Which Paypercut environment this store talks to. Leave on Production unless Paypercut support asked you to change it.';
$_['text_environment_production'] = 'Production';
$_['text_environment_stage'] = 'Stage';
$_['text_environment_dev'] = 'Development';

// Debug session
$_['text_debug_session'] = 'Debug Session';
$_['text_debug_idle_lead'] = 'Off. Nothing is sent to Paypercut until you start a session.';
$_['text_debug_idle_help'] = 'Turn on detailed diagnostics for about an hour so Paypercut support can see what your store is doing. The session ends by itself.';
$_['text_debug_running'] = 'Debug session running —';
$_['text_debug_remaining'] = 'remaining';
$_['text_debug_started_by'] = 'Started by';
$_['text_debug_ends_at'] = 'ends at';
$_['text_debug_session_id'] = 'Session ID';
$_['text_debug_last_session_id'] = 'Last session ID — quote this in your support ticket:';
$_['text_debug_events_sent'] = 'events sent';
$_['text_debug_events_dropped'] = 'dropped (approximate)';
$_['text_debug_ended'] = 'Debug session ended.';
$_['text_debug_ended_help'] = 'Paypercut stops receiving data from this store.';
$_['text_debug_reference'] = 'Support reference';
$_['text_debug_session_ended'] = 'The debug session has ended. Nothing more is sent to Paypercut.';
$_['text_debug_network_error'] = 'The request could not be completed. Please try again.';
$_['text_debug_admin_unreachable'] = 'This page cannot reach the store admin, so the debug session panel has stopped updating. Reload the page to try again.';
$_['text_debug_copied'] = 'Copied';
$_['text_debug_log_summary'] = 'Show the events sent';
$_['text_debug_log_help'] = 'Exactly what was sent to Paypercut, newest last. The most recent 100 are kept on this store and cleared when a new session starts.';
$_['text_debug_log_raw'] = 'Show raw JSON';
$_['column_debug_time'] = 'Time (UTC)';
$_['column_debug_event'] = 'Event';
$_['column_debug_detail'] = 'Detail';
$_['text_debug_modal_title'] = 'Start a debug session?';
$_['text_debug_modal_lead'] = 'While the session is running, this store sends the diagnostic information below to Paypercut so support can see what is happening.';
$_['text_debug_modal_duration'] = 'The session lasts about 60 minutes and then stops by itself. You can stop it sooner at any time.';
$_['text_debug_disclosure_summary'] = 'What is shared';
$_['text_debug_disclosure_shared'] = 'Extension, OpenCart, PHP and theme versions; the extensions installed on this store and their versions; how this store has the Paypercut payment method configured (which checkout mode is selected and which options are switched on — never the values of your credentials); a record of each checkout, refund and payment notification the extension handled and whether it succeeded, identified by OpenCart order number and Paypercut payment reference; when something fails, the error message, the file and line it came from, and which extension or theme raised it; and when the session started and stopped.';
$_['text_debug_disclosure_not_shared_label'] = 'Not shared:';
$_['text_debug_disclosure_not_shared'] = 'customer names, email addresses, billing or shipping addresses, order totals, line items, payment card data, the reason text you type when issuing a refund, or any API key, webhook secret or password.';
$_['text_debug_disclosure_api_key'] = 'Your API key is never sent to the telemetry service. It is used once, over HTTPS, to obtain a short-lived diagnostic token from api.paypercut.io.';
$_['text_debug_disclosure_retention'] = 'Paypercut keeps this diagnostic data for 30 days.';

// Debug session buttons
$_['button_debug_start'] = 'Start debug session';
$_['button_debug_stop'] = 'Stop now';
$_['button_debug_retry'] = 'Try again';
$_['button_debug_copy'] = 'Copy';
$_['button_debug_copy_json'] = 'Copy JSON';
$_['button_debug_cancel'] = 'Cancel';
$_['button_debug_confirm'] = 'Start session';

// Debug session errors
$_['error_debug_session_starting'] = 'A debug session is already being started.';
$_['error_debug_session_no_key'] = 'Enter and save your Paypercut API key before starting a debug session.';
$_['error_debug_session_no_environment'] = "This store's Paypercut connection doesn't record which environment it uses, so a debug session can't be started. Save your settings with an environment selected, then try again.";
$_['error_debug_session_environment'] = "Debug sessions aren't available on this store's Paypercut environment.";
$_['text_debug_notice'] = 'Paypercut: a debug session started by %s is running until %s.';
$_['text_debug_notice_manage'] = 'Manage it';
