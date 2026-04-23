<?php

namespace App\Service;

use App\Entity\Order;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use Twig\Environment;

final class PaymentConfirmationMailer
{
    public function __construct(
        private readonly StripeCheckoutService $stripeCheckoutService,
        private readonly Environment $twig,
        private readonly string $smtpHost,
        private readonly int $smtpPort,
        private readonly string $smtpUsername,
        private readonly string $smtpPassword,
        private readonly string $smtpEncryption,
        private readonly string $fromEmail,
        private readonly string $fromName,
    ) {
    }

    public function sendPaymentConfirmation(Order $order, string $recipientEmail, ?string $recipientName = null): void
    {
        $recipientEmail = trim($recipientEmail);
        if ($recipientEmail === '') {
            throw new \RuntimeException('Confirmation email could not be sent because the recipient email is missing.');
        }

        if (trim($this->smtpHost) === '' || trim($this->smtpUsername) === '' || trim($this->smtpPassword) === '') {
            throw new \RuntimeException('PHPMailer SMTP is not configured. Add your Gmail SMTP host, username, and app password to .env.local.');
        }

        $mail = new PHPMailer(true);

        try {
            $mail->CharSet = 'UTF-8';
            $mail->isHTML(true);
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->Port = $this->smtpPort > 0 ? $this->smtpPort : 587;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername;
            $mail->Password = $this->smtpPassword;

            $encryption = strtolower(trim($this->smtpEncryption));
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($recipientEmail, trim((string) $recipientName) !== '' ? trim((string) $recipientName) : $recipientEmail);
            $mail->Subject = sprintf('BIG 4 payment confirmation for order #%d', (int) $order->getOrderId());

            $cartItems = $this->decodeCartItems($order);
            $paymentSummary = $this->stripeCheckoutService->getCheckoutSummary($order);
            $bodyContext = [
                'order' => $order,
                'recipientName' => $recipientName,
                'cartItems' => $cartItems,
                'paymentSummary' => $paymentSummary,
            ];

            $mail->Body = $this->twig->render('email/payment_confirmation.html.twig', $bodyContext);
            $mail->AltBody = $this->buildPlainTextBody($order, $recipientName, $cartItems, $paymentSummary);
            $mail->send();
        } catch (PHPMailerException $e) {
            throw new \RuntimeException('PHPMailer could not send the payment confirmation email: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array<int, array{name: string, price: float}>
     */
    private function decodeCartItems(Order $order): array
    {
        $rawCartItems = $order->getCartItems();
        if ($rawCartItems === null || trim($rawCartItems) === '') {
            return [];
        }

        $decoded = json_decode($rawCartItems, true);
        if (!is_array($decoded)) {
            return [];
        }

        $items = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }

            $items[] = [
                'name' => trim((string) ($item['name'] ?? 'Menu item')),
                'price' => (float) ($item['price'] ?? 0),
            ];
        }

        return $items;
    }

    /**
     * @param array<int, array{name: string, price: float}> $cartItems
     * @param array{
     *   displayCurrency: string,
     *   chargeCurrency: string,
     *   displayAmountRaw: string,
     *   chargeAmountRaw: string,
     *   displayAmountWithCurrency: string,
     *   chargeAmountWithCurrency: string,
     *   requiresConversion: bool,
     *   notice: string
     * } $paymentSummary
     */
    private function buildPlainTextBody(Order $order, ?string $recipientName, array $cartItems, array $paymentSummary): string
    {
        $lines = [];
        $lines[] = sprintf('Hello %s,', trim((string) $recipientName) !== '' ? trim((string) $recipientName) : 'there');
        $lines[] = '';
        $lines[] = sprintf('Your payment for BIG 4 order #%d has been confirmed.', (int) $order->getOrderId());
        $lines[] = sprintf('Order type: %s', str_replace('_', ' ', (string) $order->getOrderType()));
        $lines[] = sprintf('Payment method: %s', (string) ($order->getPaymentMethod() ?: 'CARD'));
        $lines[] = sprintf('Original order total: %s', $paymentSummary['displayAmountWithCurrency']);

        if ($paymentSummary['requiresConversion']) {
            $lines[] = sprintf('Card charged by Stripe: %s', $paymentSummary['chargeAmountWithCurrency']);
        }

        if ($cartItems !== []) {
            $lines[] = '';
            $lines[] = 'Items:';
            foreach ($cartItems as $item) {
                $lines[] = sprintf('- %s (%s TND)', $item['name'], number_format($item['price'], 2));
            }
        }

        $lines[] = '';
        $lines[] = 'Thank you for choosing BIG 4 Coffee Lounge.';

        return implode("\n", $lines);
    }
}
