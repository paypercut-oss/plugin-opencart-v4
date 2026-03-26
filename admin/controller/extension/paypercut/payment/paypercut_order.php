<?php

namespace Opencart\Admin\Controller\Extension\Paypercut\Payment;

/**
 * Paypercut Order Management Controller
 * Handles refunds and displays payment information in order view
 */
class PaypercutOrder extends \Opencart\System\Engine\Controller
{

    /**
     * Display Paypercut payment details in order view
     */
    public function info(): string
    {
        $this->load->language('extension/paypercut/payment/paypercut');

        $data = [];

        if (isset($this->request->get['order_id'])) {
            $order_id = (int)$this->request->get['order_id'];

            // Get transaction details from database
            $transaction = $this->getTransaction($order_id);

            if ($transaction) {
                $data['transaction'] = $transaction;
                $data['has_transaction'] = true;

                // Parse payment method details
                if ($transaction['payment_method_details']) {
                    $payment_details = json_decode($transaction['payment_method_details'], true);
                    $data['payment_method_formatted'] = $this->formatPaymentMethod($payment_details);
                } else {
                    $data['payment_method_formatted'] = ucfirst($transaction['payment_method_type']);
                }

                // Get refund history
                $data['refunds'] = $this->getRefunds($order_id);
                $data['total_refunded'] = $this->getTotalRefunded($order_id);
                $data['can_refund'] = ($transaction['status'] === 'succeeded' && $data['total_refunded'] < $transaction['amount']);

                // Paypercut Dashboard link
                $data['paypercut_dashboard_url'] = 'https://dashboard.paypercut.io/payments/' . $transaction['payment_id'];
            } else {
                $data['has_transaction'] = false;
            }

            $data['order_id'] = $order_id;
            $data['user_token'] = $this->session->data['user_token'];
        }

        return $this->load->view('extension/paypercut/payment/paypercut_order_info', $data);
    }

    /**
     * Process refund request
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
            $refund_reason_text = $refund_reason; // Keep original for order history
            $is_full_refund = isset($this->request->post['full_refund']) && $this->request->post['full_refund'] === 'true';

            $this->logError('Refund request - Order: ' . $order_id . ', Amount: ' . $refund_amount . ', Full: ' . ($is_full_refund ? 'yes' : 'no'));

            // Map to valid API enum values: duplicate, fraudulent, requested_by_customer
            $valid_reasons = ['duplicate', 'fraudulent', 'requested_by_customer'];
            if (!in_array($refund_reason, $valid_reasons)) {
                $refund_reason = 'requested_by_customer'; // Default to customer request
            }

            // Validate
            if (!$order_id) {
                $json['error'] = $this->language->get('error_order_id');
            }

            if (!$json) {
                $transaction = $this->getTransaction($order_id);

                if (!$transaction) {
                    $json['error'] = $this->language->get('error_no_transaction');
                } elseif ($transaction['status'] !== 'succeeded') {
                    $json['error'] = $this->language->get('error_payment_not_succeeded');
                } else {
                    // Check if already fully refunded
                    $total_refunded = $this->getTotalRefunded($order_id);
                    if ($total_refunded >= $transaction['amount']) {
                        $json['error'] = $this->language->get('error_already_refunded');
                    } else {
                        if ($is_full_refund) {
                            // Full refund - calculate remaining amount
                            $refund_amount = $transaction['amount'] - $total_refunded;
                            $this->logError('Full refund calculated: ' . $refund_amount);
                        } else {
                            // Partial refund - validate the amount
                            if ($refund_amount <= 0) {
                                $json['error'] = $this->language->get('error_invalid_amount');
                            } elseif (($total_refunded + $refund_amount) > $transaction['amount']) {
                                $json['error'] = $this->language->get('error_exceeds_payment');
                            }
                            $this->logError('Partial refund validated: ' . $refund_amount);
                        }
                    }
                }
            }
        }

        if (!$json) {
            try {
                // Process refund via API
                $result = $this->processRefund(
                    $transaction['payment_id'],
                    $transaction['payment_intent'],
                    $refund_amount,
                    $transaction['currency'],
                    $refund_reason
                );

                if (isset($result['error'])) {
                    $json['error'] = $result['error'];
                } else {
                    // Store refund in database
                    $this->storeRefund(
                        $order_id,
                        $transaction['paypercut_transaction_id'],
                        $transaction['payment_id'],
                        $result['refund_id'],
                        $refund_amount,
                        $transaction['currency'],
                        $refund_reason,
                        $result['status']
                    );

                    // Update order history
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

                    $json['success'] = $this->language->get('text_refund_success');
                    $json['refund_id'] = $result['refund_id'];
                    $json['amount'] = number_format($refund_amount, 2);
                }
            } catch (\Exception $e) {
                $json['error'] = $e->getMessage();
                $this->logError('Refund error: ' . $e->getMessage());
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Get transaction details from database
     */
    private function getTransaction(int $order_id): ?array
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
     * Get refund history for order
     */
    private function getRefunds(int $order_id): array
    {
        $query = $this->db->query("
            SELECT * FROM `" . DB_PREFIX . "paypercut_refund` 
            WHERE order_id = '" . (int)$order_id . "'
            ORDER BY created_at DESC
        ");

        return $query->rows;
    }

    /**
     * Get total refunded amount
     */
    private function getTotalRefunded(int $order_id): float
    {
        $query = $this->db->query("
            SELECT SUM(amount) as total FROM `" . DB_PREFIX . "paypercut_refund` 
            WHERE order_id = '" . (int)$order_id . "'
            AND status IN ('succeeded', 'pending')
        ");

        return $query->row['total'] ? (float)$query->row['total'] : 0;
    }

    /**
     * Process refund via Paypercut API
     */
    private function processRefund(string $payment_id, string $payment_intent, float $amount, string $currency, string $reason = ''): array
    {
        $api_key = $this->config->get('payment_paypercut_api_key');
        $api_url = 'https://api.paypercut.io/v1/refunds';

        if (!$api_key) {
            return ['error' => $this->language->get('error_api_key_missing')];
        }

        $payload = [
            'payment' => $payment_id,
            'amount' => (int)($amount * 100), // Convert to minor units
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
            $this->logError('Refund cURL Error: ' . $curl_error);
            return ['error' => $this->language->get('error_connection')];
        }

        if ($http_code == 0) {
            $this->logError('Refund API Timeout');
            return ['error' => $this->language->get('error_timeout')];
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

        // Handle API errors
        $error_message = $this->language->get('error_refund_failed');
        if (isset($result['error']['message'])) {
            $error_message = $result['error']['message'];
        }

        $this->logError('Refund API Error (HTTP ' . $http_code . '): ' . $response);

        return ['error' => $error_message];
    }

    /**
     * Store refund in database
     */
    private function storeRefund(int $order_id, int $transaction_id, string $payment_id, string $refund_id, float $amount, string $currency, string $reason, string $status): void
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
     * Format payment method for display
     */
    private function formatPaymentMethod(array $payment_details): string
    {
        if (isset($payment_details['card'])) {
            $card = $payment_details['card'];
            $brand = ucfirst($card['brand'] ?? 'Card');
            $last4 = $card['last4'] ?? '';
            $exp = '';

            if (isset($card['exp_month']) && isset($card['exp_year'])) {
                $exp = ' (Exp: ' . str_pad($card['exp_month'], 2, '0', STR_PAD_LEFT) . '/' . substr($card['exp_year'], -2) . ')';
            }

            return $brand . ' ****' . $last4 . $exp;
        } elseif (isset($payment_details['type'])) {
            $type = $payment_details['type'];
            if ($type === 'google_pay') {
                return 'Google Pay';
            } elseif ($type === 'apple_pay') {
                return 'Apple Pay';
            }
            return ucfirst(str_replace('_', ' ', $type));
        }

        return 'Unknown';
    }

    /**
     * Log errors
     */
    private function logError(string $message): void
    {
        // Check if logging is enabled
        if (!$this->config->get('payment_paypercut_logging')) {
            return;
        }

        $log = new \Opencart\System\Library\Log('paypercut_error.log');
        $timestamp = date('Y-m-d H:i:s');
        $log->write('[' . $timestamp . '] ' . $message);
    }

    /**
     * Get full transaction details from Paypercut API
     */
    public function getTransactionDetails(): void
    {
        $this->load->language('extension/paypercut/payment/paypercut');

        $json = [];

        if (!$this->user->hasPermission('modify', 'sale/order')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json) {
            $order_id = isset($this->request->get['order_id']) ? (int)$this->request->get['order_id'] : 0;

            if (!$order_id) {
                $json['error'] = 'Order ID required';
            }

            $transaction = $this->getTransaction($order_id);

            if (!$transaction) {
                $json['error'] = 'No transaction found';
            }
        }

        if (!$json) {
            try {
                $api_key = $this->config->get('payment_paypercut_api_key');
                $payment_id = $transaction['payment_id'];

                $api_url = 'https://api.paypercut.io/v1/payments/' . $payment_id;

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $api_key,
                    'Content-Type: application/json'
                ]);

                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                curl_close($ch);

                if ($curl_error) {
                    $json['error'] = 'Connection error: ' . $curl_error;
                } elseif ($http_code == 200) {
                    $payment = json_decode($response, true);
                    $json['success'] = true;
                    $json['payment'] = $payment;

                    // Calculate fees and net amount
                    $json['amount'] = $payment['amount'] / 100; // Convert from cents
                    $json['currency'] = $payment['currency'];

                    if (isset($payment['fees'])) {
                        $json['fees'] = $payment['fees'] / 100;
                        $json['net_amount'] = ($payment['amount'] - $payment['fees']) / 100;
                    }

                    // 3DS authentication details
                    if (isset($payment['payment_method']['card']['three_d_secure'])) {
                        $json['three_d_secure'] = $payment['payment_method']['card']['three_d_secure'];
                    }

                    // Capture status
                    $json['captured'] = isset($payment['captured']) ? $payment['captured'] : true;
                    $json['capture_before'] = isset($payment['capture_before']) ? $payment['capture_before'] : null;
                } else {
                    $error_data = json_decode($response, true);
                    $json['error'] = isset($error_data['message']) ? $error_data['message'] : 'Failed to fetch transaction details';
                }
            } catch (\Exception $e) {
                $json['error'] = $e->getMessage();
                $this->logError('Get transaction details error: ' . $e->getMessage());
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Capture an authorized payment
     */
    public function capture(): void
    {
        $this->load->language('extension/paypercut/payment/paypercut');

        $json = [];

        if (!$this->user->hasPermission('modify', 'sale/order')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json) {
            $order_id = isset($this->request->post['order_id']) ? (int)$this->request->post['order_id'] : 0;
            $capture_amount = isset($this->request->post['capture_amount']) ? (float)$this->request->post['capture_amount'] : 0;

            if (!$order_id) {
                $json['error'] = 'Order ID required';
            }

            $transaction = $this->getTransaction($order_id);

            if (!$transaction) {
                $json['error'] = 'No transaction found';
            }

            if ($transaction && $transaction['status'] !== 'requires_capture') {
                $json['error'] = 'Payment cannot be captured. Current status: ' . $transaction['status'];
            }
        }

        if (!$json) {
            try {
                $api_key = $this->config->get('payment_paypercut_api_key');
                $payment_id = $transaction['payment_id'];

                $api_url = 'https://api.paypercut.io/v1/payments/' . $payment_id . '/capture';

                $payload = [];
                if ($capture_amount > 0 && $capture_amount < $transaction['amount']) {
                    $payload['amount'] = (int)($capture_amount * 100); // Convert to cents
                }

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $api_key,
                    'Content-Type: application/json'
                ]);

                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                curl_close($ch);

                if ($curl_error) {
                    $json['error'] = 'Connection error: ' . $curl_error;
                } elseif ($http_code == 200) {
                    $result = json_decode($response, true);

                    // Update transaction status in database
                    $this->db->query("
                        UPDATE `" . DB_PREFIX . "paypercut_transaction`
                        SET status = 'succeeded',
                            updated_at = NOW()
                        WHERE order_id = '" . (int)$order_id . "'
                    ");

                    // Add order history
                    $comment = 'Payment captured via Paypercut' . "\n";
                    $comment .= 'Payment ID: ' . $payment_id . "\n";
                    $comment .= 'Amount: ' . number_format($capture_amount > 0 ? $capture_amount : $transaction['amount'], 2) . ' ' . $transaction['currency'];

                    $this->db->query("
                        INSERT INTO `" . DB_PREFIX . "order_history`
                        SET order_id = '" . (int)$order_id . "',
                            order_status_id = '" . (int)$this->config->get('payment_paypercut_order_status_id') . "',
                            notify = '0',
                            comment = '" . $this->db->escape($comment) . "',
                            date_added = NOW()
                    ");

                    $json['success'] = 'Payment captured successfully';
                    $json['payment_id'] = $payment_id;
                } else {
                    $error_data = json_decode($response, true);
                    $json['error'] = isset($error_data['message']) ? $error_data['message'] : 'Failed to capture payment';
                }
            } catch (\Exception $e) {
                $json['error'] = $e->getMessage();
                $this->logError('Capture error: ' . $e->getMessage());
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Cancel/void an uncaptured payment
     */
    public function cancel(): void
    {
        $this->load->language('extension/paypercut/payment/paypercut');

        $json = [];

        if (!$this->user->hasPermission('modify', 'sale/order')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json) {
            $order_id = isset($this->request->post['order_id']) ? (int)$this->request->post['order_id'] : 0;
            $cancel_reason = isset($this->request->post['cancel_reason']) ? $this->request->post['cancel_reason'] : '';

            if (!$order_id) {
                $json['error'] = 'Order ID required';
            }

            $transaction = $this->getTransaction($order_id);

            if (!$transaction) {
                $json['error'] = 'No transaction found';
            }

            if ($transaction && !in_array($transaction['status'], ['requires_capture', 'pending'])) {
                $json['error'] = 'Payment cannot be canceled. Current status: ' . $transaction['status'];
            }
        }

        if (!$json) {
            try {
                $api_key = $this->config->get('payment_paypercut_api_key');
                $payment_id = $transaction['payment_id'];

                $api_url = 'https://api.paypercut.io/v1/payments/' . $payment_id . '/cancel';

                $payload = [];
                if ($cancel_reason) {
                    $payload['reason'] = $cancel_reason;
                }

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $api_key,
                    'Content-Type: application/json'
                ]);

                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                curl_close($ch);

                if ($curl_error) {
                    $json['error'] = 'Connection error: ' . $curl_error;
                } elseif ($http_code == 200) {
                    $result = json_decode($response, true);

                    // Update transaction status in database
                    $this->db->query("
                        UPDATE `" . DB_PREFIX . "paypercut_transaction`
                        SET status = 'canceled',
                            updated_at = NOW()
                        WHERE order_id = '" . (int)$order_id . "'
                    ");

                    // Add order history
                    $comment = 'Payment canceled via Paypercut' . "\n";
                    $comment .= 'Payment ID: ' . $payment_id;
                    if ($cancel_reason) {
                        $comment .= "\n" . 'Reason: ' . $cancel_reason;
                    }

                    // Update order status to canceled
                    $this->db->query("
                        INSERT INTO `" . DB_PREFIX . "order_history`
                        SET order_id = '" . (int)$order_id . "',
                            order_status_id = '7',
                            notify = '0',
                            comment = '" . $this->db->escape($comment) . "',
                            date_added = NOW()
                    ");

                    $json['success'] = 'Payment canceled successfully';
                    $json['payment_id'] = $payment_id;
                } else {
                    $error_data = json_decode($response, true);
                    $json['error'] = isset($error_data['message']) ? $error_data['message'] : 'Failed to cancel payment';
                }
            } catch (\Exception $e) {
                $json['error'] = $e->getMessage();
                $this->logError('Cancel error: ' . $e->getMessage());
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
