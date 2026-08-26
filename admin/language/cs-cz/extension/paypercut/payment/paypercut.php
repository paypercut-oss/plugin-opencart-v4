<?php
// Heading
$_['heading_title'] = 'Paypercut Payments';

// Text
$_['text_extension'] = 'Rozšíření';
$_['text_success'] = 'Úspěch: Upravili jste platební modul Paypercut!';
$_['text_edit'] = 'Upravit Paypercut';
$_['text_paypercut'] = '<img src="/extension/paypercut/admin/view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE; height: 25px;" />';

// Entry
$_['entry_api_key'] = 'API klíč';
$_['entry_operating_account'] = 'ID provozního účtu';
$_['entry_statement_descriptor'] = 'Popisek na výpisu';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_applepay_domain_file'] = 'Soubor domény Apple Pay';
$_['entry_checkout_mode'] = 'Režim platby';
$_['entry_webhook_url'] = 'URL webhooku';
$_['entry_order_status'] = 'Stav objednávky';
$_['entry_status'] = 'Stav';
$_['entry_sort_order'] = 'Pořadí řazení';
$_['entry_logging'] = 'Povolit logování';
$_['entry_payment_method_config'] = 'Konfigurace platební metody';

// Help
$_['help_api_key'] = 'Zadejte váš API klíč Paypercut z dashboardu';
$_['help_operating_account'] = 'Zadejte ID vašeho provozního účtu (najdete v Paypercut Dashboardu)';
$_['help_statement_descriptor'] = 'Text, který se zobrazí na bankovním výpisu zákazníka (max 22 znaků). Nechte prázdné pro použití výchozího.';
$_['help_google_pay'] = 'Povolit Google Pay jako platební možnost';
$_['help_apple_pay'] = 'Povolit Apple Pay jako platební možnost';
$_['help_applepay_domain_file'] = 'Apple Pay vyžaduje ověřovací soubor na adrese <code>/.well-known/apple-developer-merchantid-domain-association</code>. Plugin jej automaticky nasadí při instalaci a při uložení nastavení.';
$_['help_checkout_mode'] = 'Vyberte mezi hostovaným (přesměrování na stránku Paypercut) nebo vloženým (platba na vašem webu) platebním prostředím';
$_['help_webhook_url'] = 'Zkopírujte tuto URL a nakonfigurujte ji ve vašem Paypercut Dashboardu v sekci Developers > Webhooks';
$_['help_logging'] = 'Povolit logování API požadavků, webhook událostí a chyb. Vypněte v produkci, pokud neladíte. Logy mohou obsahovat citlivá data.';
$_['help_payment_method_config'] = 'Vyberte konfiguraci platební metody (platební profil) pro kontrolu, které platební metody jsou dostupné zákazníkům. Nechte prázdné pro použití výchozího.';

// Error
$_['error_permission'] = 'Varování: Nemáte oprávnění upravovat platební modul Paypercut!';
$_['error_api_key'] = 'API klíč je povinný!';
$_['error_statement_descriptor'] = 'Popisek na výpisu nesmí být delší než 22 znaků!';
$_['error_unsupported_currency'] = 'Varování: Měna vašeho obchodu (%s) není podporována Paypercut. Podporované měny: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';
$_['error_applepay_domain_write'] = 'Nelze zapsat ověřovací soubor Apple Pay (cílová cesta: %s). Zkontrolujte oprávnění souborového systému pro kořenový adresář OpenCart.';

// Text
$_['text_mode_test'] = 'Testovací režim';
$_['text_mode_live'] = 'Produkční režim';
$_['text_mode_unknown'] = 'Neznámý režim';
$_['text_enabled'] = 'Povoleno';
$_['text_disabled'] = 'Zakázáno';
$_['text_hosted'] = 'Hostovaný (přesměrování)';
$_['text_embedded'] = 'Vložený (na webu)';
$_['text_statement_preview'] = 'Náhled';
$_['text_webhook_info'] = 'Nakonfigurujte tuto URL webhooku ve vašem <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">Paypercut Dashboardu</a>';
$_['text_webhook_configured'] = 'Webhook je nakonfigurován a aktivní';
$_['text_webhook_not_configured'] = 'Webhook není nakonfigurován';
$_['text_webhook_create'] = 'Vytvořit webhook automaticky';
$_['text_webhook_delete'] = 'Smazat webhook';
$_['text_webhook_creating'] = 'Vytváření webhooku...';
$_['text_webhook_deleting'] = 'Mazání webhooku...';
$_['text_wallet_settings'] = 'Nastavení peněženky';
$_['text_testing_connection'] = 'Testování připojení...';
$_['text_connection_success'] = 'Připojení úspěšné!';
$_['text_connection_failed'] = 'Připojení selhalo';

// Apple Pay domain verification file
$_['text_applepay_domain_ok'] = 'Soubor domény Apple Pay byl ověřen';
$_['text_applepay_domain_warning'] = 'Soubor domény Apple Pay byl nasazen, ale není ověřen';
$_['text_applepay_domain_missing'] = 'Soubor domény Apple Pay chybí';
$_['text_applepay_domain_path'] = 'Cesta: %s';
$_['text_applepay_domain_refreshing'] = 'Obnovuje se z PayPerCut CDN...';
$_['text_applepay_domain_manual_help'] = 'Stáhněte <a href="https://cdn.paypercut.io/.well-known/apple-developer-merchantid-domain-association" target="_blank">ověřovací soubor</a> a ručně jej umístěte do:';
$_['button_applepay_domain_refresh'] = 'Obnovit z PayPerCut CDN';

// Button
$_['button_test_connection'] = 'Test připojení';

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
