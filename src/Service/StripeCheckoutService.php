<?php

namespace App\Service;

use App\Entity\Order;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\PaymentIntent;
use Stripe\Stripe;

final class StripeCheckoutService
{
    public function __construct(
        private readonly string $secretKey,
        private readonly string $currency = 'eur',
        private readonly string $displayCurrency = 'tnd',
        private readonly float $displayToChargeRate = 3.40,
    ) {
    }

    public function createSession(Order $order, string $successUrl, string $cancelUrl): CheckoutSession
    {
        $this->assertStripeIsConfigured();
        $summary = $this->getCheckoutSummary($order);
        $unitAmount = $this->resolveUnitAmount($order);

        Stripe::setApiKey($this->secretKey);

        $payload = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($this->currency),
                    'unit_amount' => $unitAmount,
                    'product_data' => [
                        'name' => sprintf('BIG 4 Order #%d', (int) $order->getOrderId()),
                        'description' => sprintf(
                            '%s order payment%s',
                            str_replace('_', ' ', (string) $order->getOrderType()),
                            $summary['requiresConversion']
                                ? sprintf(
                                    ' (%s original total)',
                                    $summary['displayAmountWithCurrency']
                                )
                                : ''
                        ),
                    ],
                ],
            ]],
            'metadata' => [
                'order_id' => (string) $order->getOrderId(),
                'order_type' => (string) $order->getOrderType(),
                'payment_method' => (string) $order->getPaymentMethod(),
                'display_currency' => $summary['displayCurrency'],
                'display_amount' => $summary['displayAmountRaw'],
                'charge_currency' => $summary['chargeCurrency'],
                'charge_amount' => $summary['chargeAmountRaw'],
            ],
        ];

        if ($summary['requiresConversion']) {
            $payload['custom_text'] = [
                'submit' => [
                    'message' => sprintf(
                        'Original menu total: %s. Your card will be charged %s.',
                        $summary['displayAmountWithCurrency'],
                        $summary['chargeAmountWithCurrency']
                    ),
                ],
            ];
        }

        return CheckoutSession::create($payload);
    }

    public function createPaymentIntent(Order $order): PaymentIntent
    {
        $this->assertStripeIsConfigured();

        Stripe::setApiKey($this->secretKey);
        $unitAmount = $this->resolveUnitAmount($order);

        return PaymentIntent::create([
            'amount' => $unitAmount,
            'currency' => strtolower($this->currency),
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
            'description' => sprintf('BIG 4 Order #%d', (int) $order->getOrderId()),
            'metadata' => [
                'order_id' => (string) $order->getOrderId(),
                'order_type' => (string) $order->getOrderType(),
            ],
        ]);
    }

    /**
     * @return array{
     *   displayCurrency: string,
     *   chargeCurrency: string,
     *   displayAmountRaw: string,
     *   chargeAmountRaw: string,
     *   displayAmountWithCurrency: string,
     *   chargeAmountWithCurrency: string,
     *   requiresConversion: bool,
     *   notice: string
     * }
     */
    public function getCheckoutSummary(Order $order): array
    {
        $displayAmount = $this->resolveDisplayAmount($order);
        $chargeAmount = $this->resolveChargeAmount($order);
        $displayCurrency = strtoupper($this->displayCurrency);
        $chargeCurrency = strtoupper($this->currency);
        $requiresConversion = strtolower($displayCurrency) !== strtolower($chargeCurrency);

        return [
            'displayCurrency' => $displayCurrency,
            'chargeCurrency' => $chargeCurrency,
            'displayAmountRaw' => number_format($displayAmount, 2, '.', ''),
            'chargeAmountRaw' => number_format($chargeAmount, 2, '.', ''),
            'displayAmountWithCurrency' => sprintf('%s %s', number_format($displayAmount, 2), $displayCurrency),
            'chargeAmountWithCurrency' => sprintf('%s %s', number_format($chargeAmount, 2), $chargeCurrency),
            'requiresConversion' => $requiresConversion,
            'notice' => $requiresConversion
                ? sprintf(
                    'Stripe will process the payment in %s because this Stripe account does not support %s.',
                    $chargeCurrency,
                    $displayCurrency
                )
                : sprintf('Stripe will process the payment in %s.', $chargeCurrency),
        ];
    }

    private function assertStripeIsConfigured(): void
    {
        if (trim($this->secretKey) === '') {
            throw new \RuntimeException('Stripe is not configured. Missing STRIPE_SECRET_KEY.');
        }
    }

    private function resolveUnitAmount(Order $order): int
    {
        $amount = $this->resolveChargeAmount($order);

        return (int) round($amount * 100);
    }

    private function resolveDisplayAmount(Order $order): float
    {
        $amount = (float) ($order->getTotalAmount() ?? 0);
        if ($amount <= 0) {
            throw new \RuntimeException('Order amount must be greater than zero for Stripe checkout.');
        }

        return $amount;
    }

    private function resolveChargeAmount(Order $order): float
    {
        $displayAmount = $this->resolveDisplayAmount($order);
        if (strtolower($this->currency) === strtolower($this->displayCurrency)) {
            return $displayAmount;
        }

        if ($this->displayToChargeRate <= 0) {
            throw new \RuntimeException('Stripe conversion rate must be greater than zero.');
        }

        return round($displayAmount / $this->displayToChargeRate, 2);
    }
}
