<?php
// Heading
$_['heading_title'] = 'Bramka Płatności Paypercut';

// Text
$_['text_extension'] = 'Rozszerzenia';
$_['text_success'] = 'Sukces: Zmodyfikowano moduł płatności Paypercut!';
$_['text_edit'] = 'Edytuj Paypercut';
$_['text_paypercut'] = '<img src="/extension/paypercut/admin/view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE; height: 25px;" />';

// Entry
$_['entry_api_key'] = 'Klucz API';
$_['entry_operating_account'] = 'ID Konta Operacyjnego';
$_['entry_statement_descriptor'] = 'Deskryptor Wyciągu';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_applepay_domain_file'] = 'Plik Domeny Apple Pay';
$_['entry_checkout_mode'] = 'Tryb Realizacji Zamówienia';
$_['entry_webhook_url'] = 'Adres URL Webhooka';
$_['entry_order_status'] = 'Status Zamówienia';
$_['entry_status'] = 'Status';
$_['entry_sort_order'] = 'Kolejność Sortowania';
$_['entry_logging'] = 'Włącz Logowanie';
$_['entry_payment_method_config'] = 'Konfiguracja Metod Płatności';

// Help
$_['help_api_key'] = 'Wprowadź swój klucz API Paypercut z panelu';
$_['help_operating_account'] = 'Wprowadź ID swojego konta operacyjnego (znajduje się w panelu Paypercut)';
$_['help_statement_descriptor'] = 'Tekst wyświetlany na wyciągu bankowym klienta (maks. 22 znaki). Pozostaw puste, aby użyć domyślnego.';
$_['help_google_pay'] = 'Włącz Google Pay jako opcję płatności';
$_['help_apple_pay'] = 'Włącz Apple Pay jako opcję płatności';
$_['help_applepay_domain_file'] = 'Apple Pay wymaga pliku weryfikacji domeny pod adresem <code>/.well-known/apple-developer-merchantid-domain-association</code>. Wtyczka wdraża go automatycznie podczas instalacji oraz przy zapisywaniu ustawień.';
$_['help_checkout_mode'] = 'Wybierz między hostowanym (przekierowanie na stronę Paypercut) lub wbudowanym (płatność na Twojej stronie) doświadczeniem płatności';
$_['help_webhook_url'] = 'Skopiuj ten adres URL i skonfiguruj go w panelu Paypercut w zakładce Developers > Webhooks';
$_['help_logging'] = 'Włącz logowanie żądań API, zdarzeń webhook i błędów. Wyłącz w środowisku produkcyjnym, chyba że debugujesz. Logi mogą zawierać poufne dane.';
$_['help_payment_method_config'] = 'Wybierz konfigurację metody płatności (profil płatności), aby kontrolować, które metody płatności są dostępne dla klientów. Pozostaw puste, aby użyć domyślnej.';

// Error
$_['error_permission'] = 'Ostrzeżenie: Nie masz uprawnień do modyfikacji modułu płatności Paypercut!';
$_['error_api_key'] = 'Wymagany klucz API!';
$_['error_statement_descriptor'] = 'Deskryptor wyciągu nie może przekraczać 22 znaków!';
$_['error_unsupported_currency'] = 'Ostrzeżenie: Waluta Twojego sklepu (%s) nie jest obsługiwana przez Paypercut. Obsługiwane waluty: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';
$_['error_applepay_domain_write'] = 'Nie udało się zapisać pliku weryfikacji Apple Pay (ścieżka docelowa: %s). Sprawdź uprawnienia systemu plików dla katalogu głównego OpenCart.';

// Text
$_['text_mode_test'] = 'Tryb Testowy';
$_['text_mode_live'] = 'Tryb Produkcyjny';
$_['text_mode_unknown'] = 'Nieznany Tryb';
$_['text_enabled'] = 'Włączony';
$_['text_disabled'] = 'Wyłączony';
$_['text_hosted'] = 'Hostowany (Przekierowanie)';
$_['text_embedded'] = 'Wbudowany (Na stronie)';
$_['text_statement_preview'] = 'Podgląd';
$_['text_webhook_info'] = 'Skonfiguruj ten adres URL webhooka w swoim <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">panelu Paypercut</a>';
$_['text_webhook_configured'] = 'Webhook jest skonfigurowany i aktywny';
$_['text_webhook_not_configured'] = 'Webhook nie jest skonfigurowany';
$_['text_webhook_create'] = 'Utwórz Webhook Automatycznie';
$_['text_webhook_delete'] = 'Usuń Webhook';
$_['text_webhook_creating'] = 'Tworzenie webhooka...';
$_['text_webhook_deleting'] = 'Usuwanie webhooka...';
$_['text_wallet_settings'] = 'Ustawienia Portfela';
$_['text_testing_connection'] = 'Testowanie połączenia...';
$_['text_connection_success'] = 'Połączenie udane!';
$_['text_connection_failed'] = 'Połączenie nieudane';

// Apple Pay domain verification file
$_['text_applepay_domain_ok'] = 'Plik domeny Apple Pay zweryfikowany';
$_['text_applepay_domain_warning'] = 'Plik domeny Apple Pay wdrożony, ale niezweryfikowany';
$_['text_applepay_domain_missing'] = 'Brak pliku domeny Apple Pay';
$_['text_applepay_domain_path'] = 'Ścieżka: %s';
$_['text_applepay_domain_refreshing'] = 'Odświeżanie z PayPerCut CDN...';
$_['text_applepay_domain_manual_help'] = 'Pobierz <a href="https://cdn.paypercut.io/.well-known/apple-developer-merchantid-domain-association" target="_blank">plik weryfikacyjny</a> i umieść go ręcznie w:';
$_['button_applepay_domain_refresh'] = 'Odśwież z PayPerCut CDN';

// Button
$_['button_test_connection'] = 'Testuj Połączenie';

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
