<?php
// Heading
$_['heading_title'] = 'Pasarela de Pago Paypercut';

// Text
$_['text_extension'] = 'Extensiones';
$_['text_success'] = 'Éxito: ¡Has modificado el módulo de pago Paypercut!';
$_['text_edit'] = 'Editar Paypercut';
$_['text_paypercut'] = '<img src="/extension/paypercut/admin/view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE; height: 25px;" />';

// Entry
$_['entry_api_key'] = 'Clave API';
$_['entry_operating_account'] = 'ID de Cuenta Operativa';
$_['entry_statement_descriptor'] = 'Descriptor de Extracto';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_applepay_domain_file'] = 'Archivo de Dominio Apple Pay';
$_['entry_checkout_mode'] = 'Modo de Pago';
$_['entry_webhook_url'] = 'URL de Webhook';
$_['entry_order_status'] = 'Estado del Pedido';
$_['entry_status'] = 'Estado';
$_['entry_sort_order'] = 'Orden de Clasificación';
$_['entry_logging'] = 'Habilitar Registro';
$_['entry_payment_method_config'] = 'Configuración del Método de Pago';

// Help
$_['help_api_key'] = 'Ingrese su Clave API de Paypercut desde el panel de control';
$_['help_operating_account'] = 'Ingrese su ID de Cuenta Operativa (se encuentra en el Panel de Paypercut)';
$_['help_statement_descriptor'] = 'Texto que aparece en el extracto bancario del cliente (máximo 22 caracteres). Dejar vacío para usar el predeterminado.';
$_['help_google_pay'] = 'Habilitar Google Pay como opción de pago';
$_['help_apple_pay'] = 'Habilitar Apple Pay como opción de pago';
$_['help_applepay_domain_file'] = 'Apple Pay requiere un archivo de verificación de dominio en <code>/.well-known/apple-developer-merchantid-domain-association</code>. El módulo lo despliega automáticamente al instalar y al guardar la configuración.';
$_['help_checkout_mode'] = 'Elija entre alojado (redirigir a página de Paypercut) o integrado (pago en su sitio)';
$_['help_webhook_url'] = 'Copie esta URL y configúrela en su Panel de Paypercut en Desarrolladores > Webhooks';
$_['help_logging'] = 'Habilitar registro de solicitudes API, eventos webhook y errores. Desactivar en producción a menos que esté depurando. Los registros pueden contener datos sensibles.';
$_['help_payment_method_config'] = 'Seleccione una configuración de método de pago (perfil de pago) para controlar qué métodos de pago están disponibles para los clientes. Dejar vacío para usar el predeterminado.';

// Error
$_['error_permission'] = 'Advertencia: ¡No tienes permiso para modificar el módulo de pago Paypercut!';
$_['error_api_key'] = '¡Clave API Requerida!';
$_['error_statement_descriptor'] = '¡El descriptor de extracto debe tener 22 caracteres o menos!';
$_['error_unsupported_currency'] = 'Advertencia: La moneda de su tienda (%s) no es compatible con Paypercut. Monedas compatibles: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';
$_['error_applepay_domain_write'] = 'No se pudo escribir el archivo de verificación de Apple Pay (ruta de destino: %s). Verifique los permisos del sistema de archivos para la raíz web de OpenCart.';

// Text
$_['text_mode_test'] = 'Modo de Prueba';
$_['text_mode_live'] = 'Modo en Vivo';
$_['text_mode_unknown'] = 'Modo Desconocido';
$_['text_enabled'] = 'Habilitado';
$_['text_disabled'] = 'Deshabilitado';
$_['text_hosted'] = 'Alojado (Redirigir)';
$_['text_embedded'] = 'Integrado (En el sitio)';
$_['text_statement_preview'] = 'Vista Previa';
$_['text_webhook_info'] = 'Configure esta URL de webhook en su <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">Panel de Paypercut</a>';
$_['text_webhook_configured'] = 'El webhook está configurado y activo';
$_['text_webhook_not_configured'] = 'Webhook no configurado';
$_['text_webhook_create'] = 'Crear Webhook Automáticamente';
$_['text_webhook_delete'] = 'Eliminar Webhook';
$_['text_webhook_creating'] = 'Creando webhook...';
$_['text_webhook_deleting'] = 'Eliminando webhook...';
$_['text_wallet_settings'] = 'Configuración de Billetera';
$_['text_testing_connection'] = 'Probando conexión...';
$_['text_connection_success'] = '¡Conexión exitosa!';
$_['text_connection_failed'] = 'Conexión fallida';

// Apple Pay domain verification file
$_['text_applepay_domain_ok'] = 'Archivo de dominio Apple Pay verificado';
$_['text_applepay_domain_warning'] = 'Archivo de dominio Apple Pay desplegado pero no verificado';
$_['text_applepay_domain_missing'] = 'Falta el archivo de dominio Apple Pay';
$_['text_applepay_domain_path'] = 'Ruta: %s';
$_['text_applepay_domain_refreshing'] = 'Actualizando desde PayPerCut CDN...';
$_['text_applepay_domain_manual_help'] = 'Descargue <a href="https://cdn.paypercut.io/.well-known/apple-developer-merchantid-domain-association" target="_blank">el archivo de verificación</a> y colóquelo manualmente en:';
$_['button_applepay_domain_refresh'] = 'Actualizar desde PayPerCut CDN';

// Button
$_['button_test_connection'] = 'Probar Conexión';

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
