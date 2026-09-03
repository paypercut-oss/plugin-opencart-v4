<?php
// Heading
$_['heading_title'] = 'Gateway de Plată Paypercut';

// Text
$_['text_extension'] = 'Extensii';
$_['text_success'] = 'Succes: Ați modificat modulul de plată Paypercut!';
$_['text_edit'] = 'Editează Paypercut';
$_['text_paypercut'] = '<img src="/extension/paypercut/admin/view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE; height: 25px;" />';

// Entry
$_['entry_api_key'] = 'Cheie API';
$_['entry_operating_account'] = 'ID Cont Operațional';
$_['entry_statement_descriptor'] = 'Descriptor Extras';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_applepay_domain_file'] = 'Fișier Domeniu Apple Pay';
$_['entry_checkout_mode'] = 'Mod Finalizare Comandă';
$_['entry_webhook_url'] = 'URL Webhook';
$_['entry_order_status'] = 'Status Comandă';
$_['entry_status'] = 'Status';
$_['entry_sort_order'] = 'Ordine Sortare';
$_['entry_logging'] = 'Activează Jurnalizarea';
$_['entry_payment_method_config'] = 'Configurare Metodă de Plată';

// Help
$_['help_api_key'] = 'Introduceți cheia API Paypercut din tabloul de bord';
$_['help_operating_account'] = 'Introduceți ID-ul contului operațional (se găsește în tabloul de bord Paypercut)';
$_['help_statement_descriptor'] = 'Text care apare pe extrasul bancar al clientului (maxim 22 caractere). Lăsați gol pentru a utiliza valoarea implicită.';
$_['help_google_pay'] = 'Activează Google Pay ca opțiune de plată';
$_['help_apple_pay'] = 'Activează Apple Pay ca opțiune de plată';
$_['help_applepay_domain_file'] = 'Apple Pay necesită un fișier de verificare a domeniului la <code>/.well-known/apple-developer-merchantid-domain-association</code>. Modulul îl implementează automat la instalare și la salvarea setărilor.';
$_['help_checkout_mode'] = 'Alegeți între găzduit (redirecționare către pagina Paypercut) sau încorporat (finalizare pe site-ul dvs.) experiență de plată';
$_['help_webhook_url'] = 'Copiați acest URL și configurați-l în tabloul de bord Paypercut la Dezvoltatori > Webhooks';
$_['help_logging'] = 'Activează jurnalizarea cererilor API, evenimentelor webhook și erorilor. Dezactivați în producție, cu excepția cazului în care depanați. Jurnalele pot conține date sensibile.';
$_['help_payment_method_config'] = 'Selectați o configurare a metodei de plată (profil de plată) pentru a controla care metode de plată sunt disponibile pentru clienți. Lăsați gol pentru a utiliza valoarea implicită.';

// Error
$_['error_permission'] = 'Avertisment: Nu aveți permisiunea de a modifica modulul de plată Paypercut!';
$_['error_api_key'] = 'Cheie API necesară!';
$_['error_statement_descriptor'] = 'Descriptorul extrasului trebuie să aibă maxim 22 de caractere!';
$_['error_unsupported_currency'] = 'Avertisment: Moneda magazinului dvs. (%s) nu este suportată de Paypercut. Monede suportate: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';
$_['error_applepay_domain_write'] = 'Nu s-a putut scrie fișierul de verificare Apple Pay (cale destinație: %s). Verificați permisiunile sistemului de fișiere pentru webroot-ul OpenCart.';

// Text
$_['text_mode_test'] = 'Mod Test';
$_['text_mode_live'] = 'Mod Live';
$_['text_mode_unknown'] = 'Mod Necunoscut';
$_['text_enabled'] = 'Activat';
$_['text_disabled'] = 'Dezactivat';
$_['text_hosted'] = 'Găzduit (Redirecționare)';
$_['text_embedded'] = 'Încorporat (Pe site)';
$_['text_statement_preview'] = 'Previzualizare';
$_['text_webhook_info'] = 'Configurați acest URL webhook în <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">tabloul de bord Paypercut</a>';
$_['text_webhook_configured'] = 'Webhook-ul este configurat și activ';
$_['text_webhook_not_configured'] = 'Webhook neconfigurat';
$_['text_webhook_create'] = 'Creează Webhook Automat';
$_['text_webhook_delete'] = 'Șterge Webhook';
$_['text_webhook_creating'] = 'Se creează webhook...';
$_['text_webhook_deleting'] = 'Se șterge webhook...';
$_['text_wallet_settings'] = 'Setări Portofel';
$_['text_testing_connection'] = 'Se testează conexiunea...';
$_['text_connection_success'] = 'Conexiune reușită!';
$_['text_connection_failed'] = 'Conexiune eșuată';

// Apple Pay domain verification file
$_['text_applepay_domain_ok'] = 'Fișier domeniu Apple Pay verificat';
$_['text_applepay_domain_warning'] = 'Fișier domeniu Apple Pay implementat, dar neverificat';
$_['text_applepay_domain_missing'] = 'Fișierul de domeniu Apple Pay lipsește';
$_['text_applepay_domain_path'] = 'Cale: %s';
$_['text_applepay_domain_refreshing'] = 'Se reîmprospătează de la PayPerCut CDN...';
$_['text_applepay_domain_manual_help'] = 'Descărcați <a href="https://cdn.paypercut.io/.well-known/apple-developer-merchantid-domain-association" target="_blank">fișierul de verificare</a> și plasați-l manual la:';
$_['button_applepay_domain_refresh'] = 'Reîmprospătare de la PayPerCut CDN';

// Button
$_['button_test_connection'] = 'Testează Conexiunea';

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
