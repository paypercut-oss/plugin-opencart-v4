<?php
// Heading
$_['heading_title'] = 'Passerelle de Paiement Paypercut';

// Text
$_['text_extension'] = 'Extensions';
$_['text_success'] = 'Succès : Vous avez modifié le module de paiement Paypercut !';
$_['text_edit'] = 'Modifier Paypercut';
$_['text_paypercut'] = '<img src="/extension/paypercut/admin/view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE; height: 25px;" />';

// Entry
$_['entry_api_key'] = 'Clé API';
$_['entry_operating_account'] = 'ID du Compte Opérationnel';
$_['entry_statement_descriptor'] = 'Descripteur de Relevé';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_applepay_domain_file'] = 'Fichier de Domaine Apple Pay';
$_['entry_checkout_mode'] = 'Mode de Paiement';
$_['entry_webhook_url'] = 'URL du Webhook';
$_['entry_order_status'] = 'Statut de Commande';
$_['entry_status'] = 'Statut';
$_['entry_sort_order'] = 'Ordre de Tri';
$_['entry_logging'] = 'Activer la Journalisation';
$_['entry_payment_method_config'] = 'Configuration des Méthodes de Paiement';

// Help
$_['help_api_key'] = 'Entrez votre clé API Paypercut depuis le tableau de bord';
$_['help_operating_account'] = 'Entrez votre ID de compte opérationnel (disponible dans le tableau de bord Paypercut)';
$_['help_statement_descriptor'] = 'Texte qui apparaît sur le relevé bancaire du client (max 22 caractères). Laisser vide pour utiliser la valeur par défaut.';
$_['help_google_pay'] = 'Activer Google Pay comme option de paiement';
$_['help_apple_pay'] = 'Activer Apple Pay comme option de paiement';
$_['help_applepay_domain_file'] = 'Apple Pay nécessite un fichier de vérification de domaine à <code>/.well-known/apple-developer-merchantid-domain-association</code>. Le module le déploie automatiquement à l\'installation et à l\'enregistrement des paramètres.';
$_['help_checkout_mode'] = 'Choisissez entre hébergé (redirection vers la page Paypercut) ou intégré (paiement sur votre site) pour l\'expérience de paiement';
$_['help_webhook_url'] = 'Copiez cette URL et configurez-la dans votre tableau de bord Paypercut sous Développeurs > Webhooks';
$_['help_logging'] = 'Activer la journalisation des requêtes API, événements webhook et erreurs. Désactiver en production sauf pour le débogage. Les journaux peuvent contenir des données sensibles.';
$_['help_payment_method_config'] = 'Sélectionnez une configuration de méthode de paiement (profil de paiement) pour contrôler quelles méthodes de paiement sont disponibles pour les clients. Laisser vide pour utiliser la valeur par défaut.';

// Error
$_['error_permission'] = 'Attention : Vous n\'avez pas la permission de modifier le module de paiement Paypercut !';
$_['error_api_key'] = 'Clé API requise !';
$_['error_statement_descriptor'] = 'Le descripteur de relevé doit contenir 22 caractères ou moins !';
$_['error_unsupported_currency'] = 'Attention : La devise de votre boutique (%s) n\'est pas prise en charge par Paypercut. Devises prises en charge : BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';
$_['error_applepay_domain_write'] = 'Impossible d\'écrire le fichier de vérification Apple Pay (chemin cible : %s). Vérifiez les permissions du système de fichiers pour la racine web d\'OpenCart.';

// Text
$_['text_mode_test'] = 'Mode Test';
$_['text_mode_live'] = 'Mode Production';
$_['text_mode_unknown'] = 'Mode Inconnu';
$_['text_enabled'] = 'Activé';
$_['text_disabled'] = 'Désactivé';
$_['text_hosted'] = 'Hébergé (Redirection)';
$_['text_embedded'] = 'Intégré (Sur site)';
$_['text_statement_preview'] = 'Aperçu';
$_['text_webhook_info'] = 'Configurez cette URL de webhook dans votre <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">tableau de bord Paypercut</a>';
$_['text_webhook_configured'] = 'Le webhook est configuré et actif';
$_['text_webhook_not_configured'] = 'Webhook non configuré';
$_['text_webhook_create'] = 'Créer un Webhook Automatiquement';
$_['text_webhook_delete'] = 'Supprimer le Webhook';
$_['text_webhook_creating'] = 'Création du webhook...';
$_['text_webhook_deleting'] = 'Suppression du webhook...';
$_['text_wallet_settings'] = 'Paramètres du Portefeuille';
$_['text_testing_connection'] = 'Test de la connexion...';
$_['text_connection_success'] = 'Connexion réussie !';
$_['text_connection_failed'] = 'Échec de la connexion';

// Apple Pay domain verification file
$_['text_applepay_domain_ok'] = 'Fichier de domaine Apple Pay vérifié';
$_['text_applepay_domain_warning'] = 'Fichier de domaine Apple Pay déployé mais non vérifié';
$_['text_applepay_domain_missing'] = 'Fichier de domaine Apple Pay manquant';
$_['text_applepay_domain_path'] = 'Chemin : %s';
$_['text_applepay_domain_refreshing'] = 'Actualisation depuis le CDN PayPerCut...';
$_['text_applepay_domain_manual_help'] = 'Téléchargez <a href="https://cdn.paypercut.io/.well-known/apple-developer-merchantid-domain-association" target="_blank">le fichier de vérification</a> et placez-le manuellement à :';
$_['button_applepay_domain_refresh'] = 'Actualiser depuis le CDN PayPerCut';

// Button
$_['button_test_connection'] = 'Tester la Connexion';

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
