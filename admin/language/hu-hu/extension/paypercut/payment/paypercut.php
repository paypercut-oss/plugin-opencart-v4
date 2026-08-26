<?php
// Heading
$_['heading_title'] = 'Paypercut Fizetési Átjáró';

// Text
$_['text_extension'] = 'Bővítmények';
$_['text_success'] = 'Siker: Módosította a Paypercut fizetési modult!';
$_['text_edit'] = 'Paypercut szerkesztése';
$_['text_paypercut'] = '<img src="/extension/paypercut/admin/view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE; height: 25px;" />';

// Entry
$_['entry_api_key'] = 'API kulcs';
$_['entry_operating_account'] = 'Működési számla azonosító';
$_['entry_statement_descriptor'] = 'Számla leírás';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_applepay_domain_file'] = 'Apple Pay tartományfájl';
$_['entry_checkout_mode'] = 'Fizetési mód';
$_['entry_webhook_url'] = 'Webhook URL';
$_['entry_order_status'] = 'Rendelés állapota';
$_['entry_status'] = 'Állapot';
$_['entry_sort_order'] = 'Rendezési sorrend';
$_['entry_logging'] = 'Naplózás engedélyezése';
$_['entry_payment_method_config'] = 'Fizetési módszer konfiguráció';

// Help
$_['help_api_key'] = 'Adja meg a Paypercut API kulcsát a vezérlőpultból';
$_['help_operating_account'] = 'Adja meg a működési számla azonosítóját (megtalálható a Paypercut vezérlőpultban)';
$_['help_statement_descriptor'] = 'Szöveg, amely megjelenik az ügyfél bankszámla kivonatán (maximum 22 karakter). Hagyja üresen az alapértelmezett használatához.';
$_['help_google_pay'] = 'Google Pay engedélyezése fizetési lehetőségként';
$_['help_apple_pay'] = 'Apple Pay engedélyezése fizetési lehetőségként';
$_['help_applepay_domain_file'] = 'Az Apple Pay-hez tartomány-ellenőrző fájl szükséges itt: <code>/.well-known/apple-developer-merchantid-domain-association</code>. A bővítmény telepítéskor és a beállítások mentésekor automatikusan kihelyezi.';
$_['help_checkout_mode'] = 'Válasszon a tárolt (átirányítás a Paypercut oldalára) vagy beágyazott (fizetés a saját webhelyén) fizetési élmény között';
$_['help_webhook_url'] = 'Másolja ezt az URL-t és konfigurálja a Paypercut vezérlőpultban a Fejlesztők > Webhookok menüpontban';
$_['help_logging'] = 'API kérések, webhook események és hibák naplózásának engedélyezése. Tiltsa le éles környezetben, kivéve hibakereséskor. A naplók érzékeny adatokat tartalmazhatnak.';
$_['help_payment_method_config'] = 'Válasszon ki egy fizetési módszer konfigurációt (fizetési profil) annak szabályozására, hogy mely fizetési módok érhetők el az ügyfelek számára. Hagyja üresen az alapértelmezett használatához.';

// Error
$_['error_permission'] = 'Figyelmeztetés: Nincs jogosultsága a Paypercut fizetési modul módosítására!';
$_['error_api_key'] = 'API kulcs kötelező!';
$_['error_statement_descriptor'] = 'A számla leírás nem lehet hosszabb 22 karakternél!';
$_['error_unsupported_currency'] = 'Figyelmeztetés: Az áruház pénzneme (%s) nem támogatott a Paypercut által. Támogatott pénznemek: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';
$_['error_applepay_domain_write'] = 'Nem sikerült írni az Apple Pay ellenőrző fájlt (célútvonal: %s). Ellenőrizze az OpenCart webroot fájlrendszer-jogosultságait.';

// Text
$_['text_mode_test'] = 'Teszt mód';
$_['text_mode_live'] = 'Éles mód';
$_['text_mode_unknown'] = 'Ismeretlen mód';
$_['text_enabled'] = 'Engedélyezve';
$_['text_disabled'] = 'Letiltva';
$_['text_hosted'] = 'Tárolt (átirányítás)';
$_['text_embedded'] = 'Beágyazott (helyszíni)';
$_['text_statement_preview'] = 'Előnézet';
$_['text_webhook_info'] = 'Konfigurálja ezt a webhook URL-t a <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">Paypercut vezérlőpultban</a>';
$_['text_webhook_configured'] = 'Webhook be van állítva és aktív';
$_['text_webhook_not_configured'] = 'Webhook nincs beállítva';
$_['text_webhook_create'] = 'Webhook automatikus létrehozása';
$_['text_webhook_delete'] = 'Webhook törlése';
$_['text_webhook_creating'] = 'Webhook létrehozása...';
$_['text_webhook_deleting'] = 'Webhook törlése...';
$_['text_wallet_settings'] = 'Tárca beállítások';
$_['text_testing_connection'] = 'Kapcsolat tesztelése...';
$_['text_connection_success'] = 'Kapcsolat sikeres!';
$_['text_connection_failed'] = 'Kapcsolat sikertelen';

// Apple Pay domain verification file
$_['text_applepay_domain_ok'] = 'Apple Pay tartományfájl ellenőrizve';
$_['text_applepay_domain_warning'] = 'Apple Pay tartományfájl kihelyezve, de nincs ellenőrizve';
$_['text_applepay_domain_missing'] = 'Az Apple Pay tartományfájl hiányzik';
$_['text_applepay_domain_path'] = 'Útvonal: %s';
$_['text_applepay_domain_refreshing'] = 'Frissítés a PayPerCut CDN-ről...';
$_['text_applepay_domain_manual_help'] = 'Töltse le <a href="https://cdn.paypercut.io/.well-known/apple-developer-merchantid-domain-association" target="_blank">az ellenőrzőfájlt</a>, és helyezze el kézzel itt:';
$_['button_applepay_domain_refresh'] = 'Frissítés a PayPerCut CDN-ről';

// Button
$_['button_test_connection'] = 'Kapcsolat tesztelése';

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
$_['button_debug_cancel'] = 'Cancel';
$_['button_debug_confirm'] = 'Start session';

// Debug session errors
$_['error_debug_session_starting'] = 'A debug session is already being started.';
$_['error_debug_session_no_key'] = 'Enter and save your Paypercut API key before starting a debug session.';
$_['error_debug_session_no_environment'] = "This store's Paypercut connection doesn't record which environment it uses, so a debug session can't be started. Save your settings with an environment selected, then try again.";
$_['error_debug_session_environment'] = "Debug sessions aren't available on this store's Paypercut environment.";
$_['text_debug_notice'] = 'Paypercut: a debug session started by %s is running until %s.';
$_['text_debug_notice_manage'] = 'Manage it';
