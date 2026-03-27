<?php

namespace Opencart\Admin\Controller\Extension\Paypercut\Payment;

class Paypercut extends \Opencart\System\Engine\Controller
{
    private array $error = [];

    public function index(): void
    {
        $this->load->language('extension/paypercut/payment/paypercut');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_paypercut', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['api_key'])) {
            $data['error_api_key'] = $this->error['api_key'];
        } else {
            $data['error_api_key'] = '';
        }

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/paypercut/payment/paypercut', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['action'] = $this->url->link('extension/paypercut/payment/paypercut', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        $data['logs_url'] = $this->url->link('extension/paypercut/paypercut/paypercut_logs', 'user_token=' . $this->session->data['user_token'], true);

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->post['payment_paypercut_api_key'])) {
            $data['payment_paypercut_api_key'] = $this->request->post['payment_paypercut_api_key'];
        } else {
            $data['payment_paypercut_api_key'] = $this->config->get('payment_paypercut_api_key');
        }

        // Detect test/live mode from API key
        $api_key = isset($this->request->post['payment_paypercut_api_key']) ? $this->request->post['payment_paypercut_api_key'] : $this->config->get('payment_paypercut_api_key');
        $data['payment_paypercut_mode'] = $this->detectApiKeyMode($api_key);

        // Statement descriptor
        if (isset($this->request->post['payment_paypercut_statement_descriptor'])) {
            $data['payment_paypercut_statement_descriptor'] = $this->request->post['payment_paypercut_statement_descriptor'];
        } else {
            $data['payment_paypercut_statement_descriptor'] = $this->config->get('payment_paypercut_statement_descriptor');
        }

        // Wallet options
        if (isset($this->request->post['payment_paypercut_google_pay'])) {
            $data['payment_paypercut_google_pay'] = $this->request->post['payment_paypercut_google_pay'];
        } else {
            $data['payment_paypercut_google_pay'] = $this->config->get('payment_paypercut_google_pay');
        }

        if (isset($this->request->post['payment_paypercut_apple_pay'])) {
            $data['payment_paypercut_apple_pay'] = $this->request->post['payment_paypercut_apple_pay'];
        } else {
            $data['payment_paypercut_apple_pay'] = $this->config->get('payment_paypercut_apple_pay');
        }

        // Checkout mode
        if (isset($this->request->post['payment_paypercut_checkout_mode'])) {
            $data['payment_paypercut_checkout_mode'] = $this->request->post['payment_paypercut_checkout_mode'];
        } else {
            $data['payment_paypercut_checkout_mode'] = $this->config->get('payment_paypercut_checkout_mode') ?: 'hosted';
        }

        // Webhook URL - use catalog URL from store config
        $catalog_url = $this->config->get('config_catalog') ?: HTTP_CATALOG;
        $data['payment_paypercut_webhook_url'] = $catalog_url . 'index.php?route=extension/paypercut/payment/paypercut|webhook';

        // Check webhook status
        $data['webhook_status'] = $this->checkWebhookStatus();

        // Payment method configuration
        if (isset($this->request->post['payment_paypercut_payment_method_config'])) {
            $data['payment_paypercut_payment_method_config'] = $this->request->post['payment_paypercut_payment_method_config'];
        } else {
            $data['payment_paypercut_payment_method_config'] = $this->config->get('payment_paypercut_payment_method_config');
        }

        // Load available payment method configurations
        $data['payment_method_configs'] = [];
        if (!empty($api_key)) {
            $configs = $this->getPaymentMethodConfigurations();
            if ($configs) {
                $data['payment_method_configs'] = $configs;
            }
        }

        if (isset($this->request->post['payment_paypercut_order_status_id'])) {
            $data['payment_paypercut_order_status_id'] = $this->request->post['payment_paypercut_order_status_id'];
        } else {
            $configured_status = $this->config->get('payment_paypercut_order_status_id');
            // Default to "Processing" status if not configured
            $data['payment_paypercut_order_status_id'] = $configured_status ? $configured_status : $this->getProcessingOrderStatusId();
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_paypercut_status'])) {
            $data['payment_paypercut_status'] = $this->request->post['payment_paypercut_status'];
        } else {
            $data['payment_paypercut_status'] = $this->config->get('payment_paypercut_status');
        }

        if (isset($this->request->post['payment_paypercut_sort_order'])) {
            $data['payment_paypercut_sort_order'] = $this->request->post['payment_paypercut_sort_order'];
        } else {
            $data['payment_paypercut_sort_order'] = $this->config->get('payment_paypercut_sort_order');
        }

        // Logging enabled
        if (isset($this->request->post['payment_paypercut_logging'])) {
            $data['payment_paypercut_logging'] = $this->request->post['payment_paypercut_logging'];
        } else {
            $data['payment_paypercut_logging'] = $this->config->get('payment_paypercut_logging');
        }

        // Check currency support
        $store_currency = $this->getStoreCurrency();
        $data['store_currency'] = $store_currency;
        $data['currency_supported'] = $this->isCurrencySupported($store_currency);

        if (!$data['currency_supported']) {
            $data['error_currency'] = sprintf($this->language->get('error_unsupported_currency'), $store_currency);
        } else {
            $data['error_currency'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        // Add user token for AJAX requests
        $data['user_token'] = $this->session->data['user_token'];

        $this->response->setOutput($this->load->view('extension/paypercut/payment/paypercut', $data));
    }

    protected function validate(): bool
    {
        if (!$this->user->hasPermission('modify', 'extension/paypercut/payment/paypercut')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_paypercut_api_key']) {
            $this->error['api_key'] = $this->language->get('error_api_key');
        }

        // Ensure payment method domain is registered for wallet payments
        if (!empty($this->request->post['payment_paypercut_api_key'])) {
            $domain_status = $this->ensurePaymentMethodDomain();
            if (!$domain_status['success']) {
                // Don't block saving, just show a warning
                $this->session->data['warning'] = 'Settings saved, but domain registration failed: ' . $domain_status['message'] . '. Wallet payment methods (Apple Pay, Google Pay) may not work until the domain is properly registered in your Paypercut dashboard.';
            }
        }

        return !$this->error;
    }

    private function detectApiKeyMode(string $api_key): string
    {
        if (empty($api_key)) {
            return '';
        }

        // Paypercut uses sk_test prefix for test keys and sk_live for live keys
        if (strpos($api_key, 'sk_test') === 0) {
            return 'test';
        } elseif (strpos($api_key, 'sk_live') === 0) {
            return 'live';
        }

        return 'unknown';
    }

    /**
     * Check if the provided currency is supported by Paypercut
     */
    private function isCurrencySupported(string $currency_code): bool
    {
        $supported_currencies = ['BGN', 'DKK', 'SEK', 'NOK', 'GBP', 'EUR', 'USD', 'CHF', 'CZK', 'HUF', 'PLN', 'RON'];
        return in_array(strtoupper($currency_code), $supported_currencies);
    }

    /**
     * Get the store's default currency
     */
    private function getStoreCurrency(): string
    {
        return $this->config->get('config_currency');
    }

    /**
     * Get the order status ID for "Processing" status
     * Looks up the status by name to avoid hardcoding the ID
     */
    private function getProcessingOrderStatusId(): int
    {
        $query = $this->db->query("
            SELECT order_status_id 
            FROM `" . DB_PREFIX . "order_status` 
            WHERE name = 'Processing' 
            AND language_id = '" . (int)$this->config->get('config_language_id') . "'
            LIMIT 1
        ");

        if ($query->num_rows) {
            return (int)$query->row['order_status_id'];
        }

        // Fallback to ID 2 if "Processing" status not found
        return 2;
    }

    private function checkWebhookStatus(): array
    {
        $api_key = $this->config->get('payment_paypercut_api_key');

        if (empty($api_key)) {
            return [
                'configured' => false,
                'message' => 'Please configure your API key first'
            ];
        }

        $catalog_url = $this->config->get('config_catalog') ?: HTTP_CATALOG;
        $webhook_url = $catalog_url . 'index.php?route=extension/paypercut/payment/paypercut|webhook';
        $webhook_id = $this->config->get('payment_paypercut_webhook_id');

        // If we have a stored webhook ID, verify it still exists
        if ($webhook_id) {
            $webhook = $this->getWebhook($webhook_id);
            if ($webhook && $webhook['url'] === $webhook_url && $webhook['status'] === 'enabled') {
                return [
                    'configured' => true,
                    'webhook_id' => $webhook_id,
                    'message' => 'Webhook is configured and active',
                    'enabled_events' => $webhook['enabled_events']
                ];
            }
        }

        // Check if webhook exists but we don't have the ID stored
        $existing_webhook = $this->findWebhookByUrl($webhook_url);
        if ($existing_webhook) {
            // Store the webhook ID
            $this->load->model('setting/setting');
            $settings = $this->model_setting_setting->getSetting('payment_paypercut');
            $settings['payment_paypercut_webhook_id'] = $existing_webhook['id'];
            $this->model_setting_setting->editSetting('payment_paypercut', $settings);

            return [
                'configured' => true,
                'webhook_id' => $existing_webhook['id'],
                'message' => 'Webhook found and linked',
                'enabled_events' => $existing_webhook['enabled_events']
            ];
        }

        return [
            'configured' => false,
            'message' => 'Webhook not configured'
        ];
    }

    private function getWebhook(string $webhook_id): ?array
    {
        $api_key = $this->config->get('payment_paypercut_api_key');
        $api_url = 'https://api.paypercut.io/v1/webhooks/' . $webhook_id;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            return json_decode($response, true);
        }

        return null;
    }

    private function findWebhookByUrl(string $webhook_url): ?array
    {
        $api_key = $this->config->get('payment_paypercut_api_key');
        $api_url = 'https://api.paypercut.io/v1/webhooks';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            $result = json_decode($response, true);
            if (isset($result['items'])) {
                foreach ($result['items'] as $webhook) {
                    if ($webhook['url'] === $webhook_url) {
                        return $webhook;
                    }
                }
            }
        }

        return null;
    }

    public function createWebhook(): void
    {
        $this->load->language('extension/paypercut/payment/paypercut');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/paypercut/payment/paypercut')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $api_key = $this->config->get('payment_paypercut_api_key');

            if (empty($api_key)) {
                $json['error'] = 'API key not configured';
            } else {
                $catalog_url = $this->config->get('config_catalog') ?: HTTP_CATALOG;
                $webhook_url = $catalog_url . 'index.php?route=extension/paypercut/payment/paypercut|webhook';

                // Check if webhook already exists
                $existing = $this->findWebhookByUrl($webhook_url);
                if ($existing) {
                    $json['error'] = 'Webhook already exists for this URL';
                    $json['webhook_id'] = $existing['id'];
                } else {
                    $api_url = 'https://api.paypercut.io/v1/webhooks';

                    // Create webhook with specific events enabled
                    $payload = [
                        'name' => 'OpenCart - ' . $catalog_url,
                        'url' => $webhook_url,
                        'enabled_events' => ['checkout_session.completed']
                    ];

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $api_url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $api_key,
                        'Content-Type: application/json'
                    ]);

                    $response = curl_exec($ch);
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($http_code == 201 || $http_code == 200) {
                        $result = json_decode($response, true);

                        // Store webhook ID and secret
                        $this->load->model('setting/setting');
                        $settings = $this->model_setting_setting->getSetting('payment_paypercut');
                        $settings['payment_paypercut_webhook_id'] = $result['id'];
                        $settings['payment_paypercut_webhook_secret'] = $result['secret'];
                        $this->model_setting_setting->editSetting('payment_paypercut', $settings);

                        $json['success'] = 'Webhook created successfully';
                        $json['webhook_id'] = $result['id'];
                    } else {
                        $error_data = json_decode($response, true);
                        $json['error'] = isset($error_data['message']) ? $error_data['message'] : 'Failed to create webhook';
                    }
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function deleteWebhook(): void
    {
        $this->load->language('extension/paypercut/payment/paypercut');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/paypercut/payment/paypercut')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $webhook_id = $this->config->get('payment_paypercut_webhook_id');

            if (empty($webhook_id)) {
                $json['error'] = 'No webhook configured';
            } else {
                $api_key = $this->config->get('payment_paypercut_api_key');
                $api_url = 'https://api.paypercut.io/v1/webhooks/' . $webhook_id;

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $api_key,
                    'Content-Type: application/json'
                ]);

                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($http_code == 200) {
                    // Remove webhook ID from settings
                    $this->load->model('setting/setting');
                    $settings = $this->model_setting_setting->getSetting('payment_paypercut');
                    unset($settings['payment_paypercut_webhook_id']);
                    unset($settings['payment_paypercut_webhook_secret']);
                    $this->model_setting_setting->editSetting('payment_paypercut', $settings);

                    $json['success'] = 'Webhook deleted successfully';
                } else {
                    $json['error'] = 'Failed to delete webhook';
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Ensure payment method domain is registered for wallet payments
     */
    private function ensurePaymentMethodDomain(): array
    {
        $api_key = $this->config->get('payment_paypercut_api_key');

        if (empty($api_key)) {
            return ['success' => false, 'message' => 'API key not configured'];
        }

        // Extract domain from catalog URL
        $catalog_url = $this->config->get('config_catalog') ?: HTTP_CATALOG;
        $domain = $this->extractDomain($catalog_url);

        if (empty($domain)) {
            return ['success' => false, 'message' => 'Could not extract domain from store URL'];
        }

        // Check if domain is already registered
        $existing_domain = $this->getPaymentMethodDomain($domain);

        if ($existing_domain) {
            // Domain exists, check if it's enabled
            if ($existing_domain['enabled']) {
                return [
                    'success' => true,
                    'message' => 'Domain already registered and enabled',
                    'domain_id' => $existing_domain['id']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Domain registered but not enabled. Please verify domain ownership in Paypercut Dashboard.'
                ];
            }
        }

        // Register the domain
        return $this->registerPaymentMethodDomain($domain);
    }

    /**
     * Extract domain name from URL
     */
    private function extractDomain(string $url): string
    {
        $parsed = parse_url($url);
        return isset($parsed['host']) ? $parsed['host'] : '';
    }

    /**
     * Get payment method domain from Paypercut
     */
    private function getPaymentMethodDomain(string $domain_name): ?array
    {
        $api_key = $this->config->get('payment_paypercut_api_key');
        $api_url = 'https://api.paypercut.io/v1/payment_method_domains';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            $result = json_decode($response, true);
            if (isset($result['items'])) {
                foreach ($result['items'] as $domain) {
                    if ($domain['domain_name'] === $domain_name) {
                        return $domain;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Register payment method domain with Paypercut
     */
    private function registerPaymentMethodDomain(string $domain_name): array
    {
        $api_key = $this->config->get('payment_paypercut_api_key');
        $api_url = 'https://api.paypercut.io/v1/payment_method_domains';

        $payload = [
            'domain_name' => $domain_name
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 201 || $http_code == 200) {
            $result = json_decode($response, true);

            // Store domain ID for reference
            $this->load->model('setting/setting');
            $settings = $this->model_setting_setting->getSetting('payment_paypercut');
            $settings['payment_paypercut_domain_id'] = $result['id'];
            $this->model_setting_setting->editSetting('payment_paypercut', $settings);

            return [
                'success' => true,
                'message' => 'Domain registered successfully. Verification may be required.',
                'domain_id' => $result['id'],
                'enabled' => isset($result['enabled']) ? $result['enabled'] : false
            ];
        } else {
            $error_data = json_decode($response, true);
            $error_message = 'Failed to register domain';

            // Provide more specific error messages
            if ($http_code == 403) {
                $error_message = 'Permission denied (403). The API key may not have access to register domains, or the domain may already be registered in another account.';
            } elseif ($http_code == 400) {
                $error_message = 'Invalid domain name (400). Please check your store URL configuration.';
            } elseif ($http_code == 409) {
                $error_message = 'Domain already exists (409). Please check your Paypercut dashboard.';
            } elseif (isset($error_data['error']['message'])) {
                $error_message = $error_data['error']['message'];
            } elseif (isset($error_data['message'])) {
                $error_message = $error_data['message'];
            }

            // Log the error for debugging
            $this->log->write('Paypercut domain registration failed: HTTP ' . $http_code . ' - ' . $error_message . ' | Response: ' . $response);

            return array(
                'success' => false,
                'message' => $error_message . ' (HTTP ' . $http_code . ')',
                'http_code' => $http_code
            );
        }
    }

    /**
     * Test API connection and get account information
     */
    public function testConnection(): void
    {
        $this->load->language('extension/paypercut/payment/paypercut');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/paypercut/payment/paypercut')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $api_key = $this->request->post['api_key'] ?? '';

            if (empty($api_key)) {
                $json['error'] = 'API key is required';
            } else {
                // Test connection by verifying account
                $api_url = 'https://api.paypercut.io/v1/account';

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $api_key,
                    'Content-Type: application/json'
                ]);

                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($http_code == 200) {
                    $result = json_decode($response, true);

                    $mode = $this->detectApiKeyMode($api_key);
                    $json['success'] = true;
                    $json['message'] = 'Connection successful!';
                    $json['mode'] = $mode;
                    if (isset($result['business_name'])) {
                        $json['account_name'] = $result['business_name'];
                    }
                } elseif ($http_code == 401) {
                    $json['error'] = 'Authentication failed. Please check your API key.';
                } else {
                    $error_data = json_decode($response, true);
                    $json['error'] = isset($error_data['message']) ? $error_data['message'] : 'Connection failed with HTTP ' . $http_code;
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Get payment method configurations from Paypercut
     */
    private function getPaymentMethodConfigurations(): array
    {
        $api_key = $this->config->get('payment_paypercut_api_key');

        if (empty($api_key)) {
            return [];
        }

        $api_url = 'https://api.paypercut.io/v1/payment-configs';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            $result = json_decode($response, true);
            return isset($result['items']) ? $result['items'] : [];
        }

        return [];
    }

    /**
     * Order tab method - Called by OpenCart when viewing an order
     * that was paid with Paypercut
     * 
     * @return string HTML content for the order tab
     */
    public function order(): string
    {
        $this->load->language('extension/paypercut/payment/paypercut');

        $data = [];

        if (isset($this->request->get['order_id'])) {
            $order_id = (int)$this->request->get['order_id'];

            // Get transaction details from database
            $transaction = $this->getOrderTransaction($order_id);

            if ($transaction) {
                $data['transaction'] = $transaction;
                $data['has_transaction'] = true;

                // Parse payment method details
                if ($transaction['payment_method_details']) {
                    $payment_details = json_decode($transaction['payment_method_details'], true);
                    $data['payment_method_formatted'] = $this->formatPaymentMethodDetails($payment_details);
                } else {
                    $data['payment_method_formatted'] = ucfirst($transaction['payment_method_type'] ?? 'card');
                }

                // Get refund history
                $data['refunds'] = $this->getOrderRefunds($order_id);
                $data['total_refunded'] = $this->getOrderTotalRefunded($order_id);
                $data['can_refund'] = ($transaction['status'] === 'succeeded' && $data['total_refunded'] < $transaction['amount']);

                // Paypercut Dashboard link
                $data['paypercut_dashboard_url'] = 'https://dashboard.paypercut.io/payments/' . $transaction['payment_id'];
            } else {
                $data['has_transaction'] = false;
            }

            $data['order_id'] = $order_id;
            $data['user_token'] = $this->session->data['user_token'];
        } else {
            $data['has_transaction'] = false;
        }

        return $this->load->view('extension/paypercut/payment/paypercut_order_info', $data);
    }

    /**
     * Get transaction details from database for order tab
     */
    private function getOrderTransaction(int $order_id): ?array
    {
        $query = $this->db->query("
            SELECT * FROM `" . DB_PREFIX . "paypercut_transaction` 
            WHERE order_id = '" . (int)$order_id . "'
            ORDER BY created_at DESC
            LIMIT 1
        ");

        if ($query->num_rows) {
            return $query->row;
        }

        return null;
    }

    /**
     * Get refund history for order tab
     */
    private function getOrderRefunds(int $order_id): array
    {
        $query = $this->db->query("
            SELECT * FROM `" . DB_PREFIX . "paypercut_refund` 
            WHERE order_id = '" . (int)$order_id . "'
            ORDER BY created_at DESC
        ");

        return $query->rows;
    }

    /**
     * Get total refunded amount for order tab
     */
    private function getOrderTotalRefunded(int $order_id): float
    {
        $query = $this->db->query("
            SELECT SUM(amount) as total FROM `" . DB_PREFIX . "paypercut_refund` 
            WHERE order_id = '" . (int)$order_id . "'
            AND status = 'succeeded'
        ");

        return $query->row['total'] ? (float)$query->row['total'] : 0.0;
    }

    /**
     * Format payment method details for display in order tab
     */
    private function formatPaymentMethodDetails($details): string
    {
        if (!$details) {
            return 'Card';
        }

        if (isset($details['card'])) {
            $card = $details['card'];
            $brand = isset($card['brand']) ? ucfirst($card['brand']) : 'Card';
            $last4 = isset($card['last4']) ? '•••• ' . $card['last4'] : '';
            return $brand . ($last4 ? ' ' . $last4 : '');
        }

        if (isset($details['type'])) {
            return ucfirst(str_replace('_', ' ', $details['type']));
        }

        return 'Card';
    }

    /**
     * Process refund request - AJAX endpoint
     */
    public function refund(): void
    {
        $this->load->language('extension/paypercut/payment/paypercut');

        $json = [];

        // Check permission
        if (!$this->user->hasPermission('modify', 'sale/order')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json) {
            $order_id = isset($this->request->post['order_id']) ? (int)$this->request->post['order_id'] : 0;
            $refund_amount = isset($this->request->post['refund_amount']) ? (float)$this->request->post['refund_amount'] : 0;
            $refund_reason = isset($this->request->post['refund_reason']) ? $this->request->post['refund_reason'] : '';
            $refund_reason_text = $refund_reason;
            $is_full_refund = isset($this->request->post['full_refund']) && $this->request->post['full_refund'] === 'true';

            // Map to valid API enum values
            $valid_reasons = ['duplicate', 'fraudulent', 'requested_by_customer'];
            if (!in_array($refund_reason, $valid_reasons)) {
                $refund_reason = 'requested_by_customer';
            }

            if (!$order_id) {
                $json['error'] = 'Order ID is required';
            }

            $transaction = null;
            if (!$json) {
                $transaction = $this->getOrderTransaction($order_id);

                if (!$transaction) {
                    $json['error'] = 'No Paypercut transaction found for this order';
                } elseif ($transaction['status'] !== 'succeeded') {
                    $json['error'] = 'Payment must be succeeded to refund';
                } else {
                    $total_refunded = $this->getOrderTotalRefunded($order_id);
                    if ($total_refunded >= $transaction['amount']) {
                        $json['error'] = 'This payment has already been fully refunded';
                    } else {
                        if ($is_full_refund) {
                            $refund_amount = $transaction['amount'] - $total_refunded;
                        } else {
                            if ($refund_amount <= 0) {
                                $json['error'] = 'Please enter a valid refund amount';
                            } elseif (($total_refunded + $refund_amount) > $transaction['amount']) {
                                $json['error'] = 'Refund amount exceeds the remaining balance';
                            }
                        }
                    }
                }
            }
        }

        if (!$json && $transaction) {
            try {
                $result = $this->processRefundApi(
                    $transaction['payment_id'],
                    $transaction['payment_intent'] ?? '',
                    $refund_amount,
                    $transaction['currency'],
                    $refund_reason
                );

                if (isset($result['error'])) {
                    $json['error'] = $result['error'];
                } else {
                    $this->storeRefundRecord(
                        $order_id,
                        $transaction['paypercut_transaction_id'],
                        $transaction['payment_id'],
                        $result['refund_id'],
                        $refund_amount,
                        $transaction['currency'],
                        $refund_reason,
                        $result['status']
                    );

                    $comment = 'Refund processed via Paypercut' . "\n";
                    $comment .= 'Refund ID: ' . $result['refund_id'] . "\n";
                    $comment .= 'Amount: ' . number_format($refund_amount, 2) . ' ' . $transaction['currency'] . "\n";
                    if ($refund_reason_text) {
                        $comment .= 'Reason: ' . $refund_reason_text;
                    }

                    $this->db->query("
                        INSERT INTO `" . DB_PREFIX . "order_history`
                        SET order_id = '" . (int)$order_id . "',
                            order_status_id = '" . (int)$this->config->get('payment_paypercut_order_status_id') . "',
                            notify = '0',
                            comment = '" . $this->db->escape($comment) . "',
                            date_added = NOW()
                    ");

                    $json['success'] = 'Refund processed successfully';
                    $json['refund_id'] = $result['refund_id'];
                    $json['amount'] = number_format($refund_amount, 2);
                }
            } catch (\Exception $e) {
                $json['error'] = $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Process refund via Paypercut API
     */
    private function processRefundApi(string $payment_id, string $payment_intent, float $amount, string $currency, string $reason = ''): array
    {
        $api_key = $this->config->get('payment_paypercut_api_key');
        $api_url = 'https://api.paypercut.io/v1/refunds';

        if (!$api_key) {
            return ['error' => 'API key not configured'];
        }

        $payload = [
            'payment' => $payment_id,
            'amount' => (int)($amount * 100),
            'currency' => strtoupper($currency)
        ];

        if ($payment_intent) {
            $payload['payment_intent'] = $payment_intent;
        }

        if ($reason) {
            $payload['reason'] = $reason;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curl_error) {
            return ['error' => 'Connection error: ' . $curl_error];
        }

        $result = json_decode($response, true);

        if ($http_code == 201 || $http_code == 200) {
            return [
                'refund_id' => $result['id'],
                'status' => $result['status'],
                'amount' => $result['amount'] / 100,
                'currency' => $result['currency']['iso'] ?? $currency
            ];
        }

        $error_message = 'Refund failed';
        if (isset($result['error']['message'])) {
            $error_message = $result['error']['message'];
        }

        return ['error' => $error_message];
    }

    /**
     * Store refund in database
     */
    private function storeRefundRecord(int $order_id, int $transaction_id, string $payment_id, string $refund_id, float $amount, string $currency, string $reason, string $status): void
    {
        $this->db->query("
            INSERT INTO `" . DB_PREFIX . "paypercut_refund` 
            SET order_id = '" . (int)$order_id . "',
                transaction_id = '" . (int)$transaction_id . "',
                payment_id = '" . $this->db->escape($payment_id) . "',
                refund_id = '" . $this->db->escape($refund_id) . "',
                amount = '" . (float)$amount . "',
                currency = '" . $this->db->escape($currency) . "',
                reason = '" . $this->db->escape($reason) . "',
                status = '" . $this->db->escape($status) . "',
                created_at = NOW(),
                updated_at = NOW()
        ");
    }

    /**
     * Install method - Called when extension is installed
     * Creates database tables and registers events
     */
    public function install(): void
    {
        // Create database tables
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "paypercut_customer` (
                `paypercut_customer_id` int(11) NOT NULL AUTO_INCREMENT,
                `customer_id` int(11) NOT NULL,
                `paypercut_id` varchar(255) NOT NULL,
                `email` varchar(255) NOT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime NOT NULL,
                PRIMARY KEY (`paypercut_customer_id`),
                UNIQUE KEY `customer_id` (`customer_id`),
                UNIQUE KEY `paypercut_id` (`paypercut_id`),
                KEY `email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "paypercut_transaction` (
                `paypercut_transaction_id` int(11) NOT NULL AUTO_INCREMENT,
                `order_id` int(11) NOT NULL,
                `payment_id` varchar(255) NOT NULL,
                `payment_intent` varchar(255) DEFAULT NULL,
                `payment_link_id` varchar(255) DEFAULT NULL,
                `checkout_id` varchar(255) DEFAULT NULL,
                `customer_id` int(11) DEFAULT NULL,
                `paypercut_customer_id` varchar(255) DEFAULT NULL,
                `amount` decimal(15,4) NOT NULL,
                `currency` varchar(3) NOT NULL,
                `status` varchar(50) NOT NULL,
                `payment_method_type` varchar(50) DEFAULT NULL,
                `payment_method_details` text,
                `created_at` datetime NOT NULL,
                `updated_at` datetime NOT NULL,
                PRIMARY KEY (`paypercut_transaction_id`),
                UNIQUE KEY `payment_id` (`payment_id`),
                KEY `order_id` (`order_id`),
                KEY `customer_id` (`customer_id`),
                KEY `checkout_id` (`checkout_id`),
                KEY `payment_intent` (`payment_intent`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "paypercut_refund` (
                `paypercut_refund_id` int(11) NOT NULL AUTO_INCREMENT,
                `order_id` int(11) NOT NULL,
                `transaction_id` int(11) NOT NULL,
                `payment_id` varchar(255) NOT NULL,
                `refund_id` varchar(255) NOT NULL,
                `amount` decimal(15,4) NOT NULL,
                `currency` varchar(3) NOT NULL,
                `reason` varchar(255) DEFAULT NULL,
                `status` varchar(50) NOT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime NOT NULL,
                PRIMARY KEY (`paypercut_refund_id`),
                UNIQUE KEY `refund_id` (`refund_id`),
                KEY `order_id` (`order_id`),
                KEY `transaction_id` (`transaction_id`),
                KEY `payment_id` (`payment_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "paypercut_webhook_log` (
                `log_id` int(11) NOT NULL AUTO_INCREMENT,
                `event_type` varchar(100) NOT NULL,
                `event_id` varchar(255) DEFAULT NULL,
                `payload` text NOT NULL,
                `processed` tinyint(1) NOT NULL DEFAULT 0,
                `error` text,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`log_id`),
                KEY `event_type` (`event_type`),
                KEY `event_id` (`event_id`),
                KEY `processed` (`processed`),
                KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");

        // Register event for order info page to display Paypercut payment information
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent([
            'code' => 'paypercut_order_info',
            'description' => 'Display Paypercut payment information on order info page',
            'trigger' => 'admin/view/sale/order_info/before',
            'action' => 'extension/paypercut/payment/paypercut_order/info',
            'status' => true,
            'sort_order' => 1
        ]);
    }

    /**
     * Uninstall method - Called when extension is uninstalled
     * Removes events (but preserves database tables for data integrity)
     */
    public function uninstall(): void
    {
        // Remove event
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('paypercut_order_info');

        // Note: We intentionally don't drop database tables to preserve transaction history
        // If you want to completely remove all data, manually drop these tables:
        // - oc_paypercut_customer
        // - oc_paypercut_transaction
        // - oc_paypercut_refund
        // - oc_paypercut_webhook_log
    }
}
