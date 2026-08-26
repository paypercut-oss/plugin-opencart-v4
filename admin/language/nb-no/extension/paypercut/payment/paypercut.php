<?php
// Heading
$_['heading_title'] = 'Paypercut Betalingsgateway';

// Text
$_['text_extension'] = 'Utvidelser';
$_['text_success'] = 'Suksess: Du har endret Paypercut betalingsmodul!';
$_['text_edit'] = 'Rediger Paypercut';
$_['text_paypercut'] = '<img src="/extension/paypercut/admin/view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE; height: 25px;" />';

// Entry
$_['entry_api_key'] = 'API-nøkkel';
$_['entry_operating_account'] = 'Driftskonto-ID';
$_['entry_statement_descriptor'] = 'Kontoutskriftsbeskrivelse';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_applepay_domain_file'] = 'Apple Pay-domenefil';
$_['entry_checkout_mode'] = 'Kassemodus';
$_['entry_webhook_url'] = 'Webhook URL';
$_['entry_order_status'] = 'Ordrestatus';
$_['entry_status'] = 'Status';
$_['entry_sort_order'] = 'Sorteringsrekkefølge';
$_['entry_logging'] = 'Aktiver logging';
$_['entry_payment_method_config'] = 'Betalingsmetode-konfigurasjon';

// Help
$_['help_api_key'] = 'Skriv inn din Paypercut API-nøkkel fra kontrollpanelet';
$_['help_operating_account'] = 'Skriv inn din Driftskonto-ID (finnes i Paypercut-kontrollpanelet)';
$_['help_statement_descriptor'] = 'Tekst som vises på kundens kontoutskrift (maks 22 tegn). La stå tom for å bruke standard.';
$_['help_google_pay'] = 'Aktiver Google Pay som betalingsalternativ';
$_['help_apple_pay'] = 'Aktiver Apple Pay som betalingsalternativ';
$_['help_applepay_domain_file'] = 'Apple Pay krever en domeneverifiseringsfil på <code>/.well-known/apple-developer-merchantid-domain-association</code>. Modulen distribuerer den automatisk ved installasjon og når innstillinger lagres.';
$_['help_checkout_mode'] = 'Velg mellom vertshåndtert (omdirigering til Paypercut-side) eller innebygd (kasse på din side) betalingsopplevelse';
$_['help_webhook_url'] = 'Kopier denne URL-en og konfigurer den i Paypercut-kontrollpanelet under Utviklere > Webhooks';
$_['help_logging'] = 'Aktiver logging av API-forespørsler, webhook-hendelser og feil. Deaktiver i produksjon med mindre du feilsøker. Logger kan inneholde sensitive data.';
$_['help_payment_method_config'] = 'Velg en betalingsmetode-konfigurasjon (betalingsprofil) for å kontrollere hvilke betalingsmetoder som er tilgjengelige for kunder. La stå tom for å bruke standard.';

// Error
$_['error_permission'] = 'Advarsel: Du har ikke tillatelse til å endre Paypercut betalingsmodul!';
$_['error_api_key'] = 'API-nøkkel er påkrevd!';
$_['error_statement_descriptor'] = 'Kontoutskriftsbeskrivelsen må være 22 tegn eller mindre!';
$_['error_unsupported_currency'] = 'Advarsel: Din butikkvaluta (%s) støttes ikke av Paypercut. Støttede valutaer: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';
$_['error_applepay_domain_write'] = 'Kunne ikke skrive Apple Pay-verifiseringsfilen (målsti: %s). Sjekk filsystemtillatelser for OpenCarts webroot.';

// Text
$_['text_mode_test'] = 'Testmodus';
$_['text_mode_live'] = 'Produksjonsmodus';
$_['text_mode_unknown'] = 'Ukjent modus';
$_['text_enabled'] = 'Aktivert';
$_['text_disabled'] = 'Deaktivert';
$_['text_hosted'] = 'Vertshåndtert (Omdirigering)';
$_['text_embedded'] = 'Innebygd (På siden)';
$_['text_statement_preview'] = 'Forhåndsvisning';
$_['text_webhook_info'] = 'Konfigurer denne webhook URL-en i ditt <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">Paypercut-kontrollpanel</a>';
$_['text_webhook_configured'] = 'Webhook er konfigurert og aktiv';
$_['text_webhook_not_configured'] = 'Webhook ikke konfigurert';
$_['text_webhook_create'] = 'Opprett webhook automatisk';
$_['text_webhook_delete'] = 'Slett webhook';
$_['text_webhook_creating'] = 'Oppretter webhook...';
$_['text_webhook_deleting'] = 'Sletter webhook...';
$_['text_wallet_settings'] = 'Lommebokinnstillinger';
$_['text_testing_connection'] = 'Tester tilkobling...';
$_['text_connection_success'] = 'Tilkobling vellykket!';
$_['text_connection_failed'] = 'Tilkobling mislyktes';

// Apple Pay domain verification file
$_['text_applepay_domain_ok'] = 'Apple Pay-domenefil verifisert';
$_['text_applepay_domain_warning'] = 'Apple Pay-domenefil distribuert, men ikke verifisert';
$_['text_applepay_domain_missing'] = 'Apple Pay-domenefil mangler';
$_['text_applepay_domain_path'] = 'Sti: %s';
$_['text_applepay_domain_refreshing'] = 'Oppdaterer fra PayPerCut CDN...';
$_['text_applepay_domain_manual_help'] = 'Last ned <a href="https://cdn.paypercut.io/.well-known/apple-developer-merchantid-domain-association" target="_blank">verifiseringsfilen</a>, og plasser den manuelt på:';
$_['button_applepay_domain_refresh'] = 'Oppdater fra PayPerCut CDN';

// Button
$_['button_test_connection'] = 'Test tilkobling';

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
