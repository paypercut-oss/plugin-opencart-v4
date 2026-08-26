<?php
// Heading
$_['heading_title'] = 'Paypercut Payments';

// Text
$_['text_extension'] = 'Extensions';
$_['text_success'] = 'Success: You have modified Paypercut payment module!';
$_['text_edit'] = 'Edit Paypercut';
$_['text_paypercut'] = '<img src="/extension/paypercut/admin/view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE; height: 25px;" />';

// Entry
$_['entry_api_key'] = 'API Key';
$_['entry_operating_account'] = 'Operating Account ID';
$_['entry_statement_descriptor'] = 'Statement Descriptor';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_applepay_domain_file'] = 'Apple Pay Domain File';
$_['entry_checkout_mode'] = 'Checkout Mode';
$_['entry_webhook_url'] = 'Webhook URL';
$_['entry_order_status'] = 'Order Status';
$_['entry_status'] = 'Status';
$_['entry_sort_order'] = 'Sort Order';
$_['entry_logging'] = 'Enable Logging';
$_['entry_payment_method_config'] = 'Payment Method Configuration';

// Help
$_['help_api_key'] = 'Enter your Paypercut API Key from the dashboard';
$_['help_operating_account'] = 'Enter your Operating Account ID (found in Paypercut Dashboard)';
$_['help_statement_descriptor'] = 'Text that appears on customer\'s bank statement (max 22 characters). Leave empty to use default.';
$_['help_google_pay'] = 'Enable Google Pay as a payment option';
$_['help_apple_pay'] = 'Enable Apple Pay as a payment option';
$_['help_applepay_domain_file'] = 'Apple Pay requires a verification file at <code>/.well-known/apple-developer-merchantid-domain-association</code>. The plugin deploys it automatically on install and on save.';
$_['help_checkout_mode'] = 'Choose between hosted (redirect to Paypercut page) or embedded (checkout on your site) payment experience';
$_['help_webhook_url'] = 'Copy this URL and configure it in your Paypercut Dashboard under Developers > Webhooks';
$_['help_logging'] = 'Enable logging of API requests, webhook events, and errors. Disable in production unless debugging. Logs may contain sensitive data.';
$_['help_payment_method_config'] = 'Select a payment method configuration (payment profile) to control which payment methods are available to customers. Leave empty to use default.';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify Paypercut payment module!';
$_['error_api_key'] = 'API Key Required!';
$_['error_statement_descriptor'] = 'Statement descriptor must be 22 characters or less!';
$_['error_unsupported_currency'] = 'Warning: Your store currency (%s) is not supported by Paypercut. Supported currencies: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';
$_['error_applepay_domain_write'] = 'Could not write the Apple Pay verification file (target path: %s). Check filesystem permissions on the OpenCart root.';

// Text
$_['text_mode_test'] = 'Test Mode';
$_['text_mode_live'] = 'Live Mode';
$_['text_mode_unknown'] = 'Unknown Mode';
$_['text_enabled'] = 'Enabled';
$_['text_disabled'] = 'Disabled';
$_['text_hosted'] = 'Hosted (Redirect)';
$_['text_embedded'] = 'Embedded (On-site)';
$_['text_statement_preview'] = 'Preview';
$_['text_webhook_info'] = 'Configure this webhook URL in your <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">Paypercut Dashboard</a>';
$_['text_webhook_configured'] = 'Webhook is configured and active';
$_['text_webhook_not_configured'] = 'Webhook not configured';
$_['text_webhook_create'] = 'Create Webhook Automatically';
$_['text_webhook_delete'] = 'Delete Webhook';
$_['text_webhook_creating'] = 'Creating webhook...';
$_['text_webhook_deleting'] = 'Deleting webhook...';
$_['text_wallet_settings'] = 'Wallet Settings';
$_['text_testing_connection'] = 'Testing connection...';
$_['text_connection_success'] = 'Connection successful!';
$_['text_connection_failed'] = 'Connection failed';

// Apple Pay domain verification file
$_['text_applepay_domain_ok'] = 'Apple Pay domain file verified';
$_['text_applepay_domain_warning'] = 'Apple Pay domain file deployed but not verified';
$_['text_applepay_domain_missing'] = 'Apple Pay domain file is missing';
$_['text_applepay_domain_path'] = 'Path: %s';
$_['text_applepay_domain_refreshing'] = 'Refreshing from PayPerCut CDN...';
$_['text_applepay_domain_manual_help'] = 'Download <a href="https://cdn.paypercut.io/.well-known/apple-developer-merchantid-domain-association" target="_blank">the verification file</a> and place it manually at:';
$_['button_applepay_domain_refresh'] = 'Refresh from PayPerCut CDN';

// Refund
$_['text_refund_success'] = 'Refund processed successfully!';
$_['error_order_id'] = 'Order ID is required!';
$_['error_no_transaction'] = 'No Paypercut transaction found for this order.';
$_['error_payment_not_succeeded'] = 'Only succeeded payments can be refunded.';
$_['error_already_refunded'] = 'This payment has already been fully refunded.';
$_['error_invalid_amount'] = 'Please enter a valid refund amount.';
$_['error_exceeds_payment'] = 'Refund amount exceeds the remaining payment amount.';
$_['error_api_key_missing'] = 'Paypercut API key is not configured.';
$_['error_connection'] = 'Could not connect to Paypercut API. Please try again.';
$_['error_timeout'] = 'Connection to Paypercut API timed out. Please try again.';
$_['error_refund_failed'] = 'Refund failed. Please try again or contact support.';

// Button
$_['button_test_connection'] = 'Test Connection';

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
