<?php

namespace Opencart\Catalog\Model\Extension\Paypercut\Payment;

class Paypercut extends \Opencart\System\Engine\Model
{
    /**
     * Get available payment methods - OpenCart 4.x format
     *
     * @param array $address Customer address
     * @return array
     */
    public function getMethods(array $address): array
    {
        $this->load->language('extension/paypercut/payment/paypercut');

        $status = true;

        // Check if currency is supported
        $currency = $this->session->data['currency'];
        if (!$this->isCurrencySupported($currency)) {
            $status = false;
        }

        $method_data = [];

        if ($status) {
            $option_data['paypercut'] = [
                'code' => 'paypercut.paypercut',
                'name' => $this->language->get('text_title')
            ];

            $method_data = [
                'code'       => 'paypercut',
                'name'       => $this->language->get('text_title'),
                'option'     => $option_data,
                'sort_order' => $this->config->get('payment_paypercut_sort_order')
            ];
        }

        return $method_data;
    }

    /**
     * Check if the provided currency is supported by Paypercut
     */
    private function isCurrencySupported(string $currency_code): bool
    {
        $supported_currencies = ['BGN', 'DKK', 'SEK', 'NOK', 'GBP', 'EUR', 'USD', 'CHF', 'CZK', 'HUF', 'PLN', 'RON'];
        return in_array(strtoupper($currency_code), $supported_currencies);
    }
}
