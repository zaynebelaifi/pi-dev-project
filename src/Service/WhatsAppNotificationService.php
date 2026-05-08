<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Twilio\Rest\Client as TwilioClient;

/**
 * WhatsAppNotificationService — sends role-specific WhatsApp messages via Twilio.
 *
 * Phone number resolution rules (Tunisia +216):
 *   "96930620"       → whatsapp:+21696930620
 *   "0096930620"     → whatsapp:+21696930620
 *   "+21696930620"   → whatsapp:+21696930620
 *   "21696930620"    → whatsapp:+21696930620
 */
final class WhatsAppNotificationService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string          $twilioSid,
        private readonly string          $twilioAuthToken,
        private readonly string          $twilioWhatsappNumber,   // e.g. whatsapp:+14155238886
    ) {}

    // ── Public API ─────────────────────────────────────────────────────────

    /**
     * Send a ban notification to the user via WhatsApp.
     * Message differs based on ROLE_CLIENT vs ROLE_DELIVERY.
     */
    public function sendBanNotification(User $user, string $reason): void
    {
        $to = $this->formatPhone($user->getPhone());

        if ($to === null) {
            $this->logger->warning('[WhatsApp] Ban notification skipped — no valid phone.', [
                'user_id' => $user->getId(),
                'email'   => $user->getEmail(),
                'phone'   => $user->getPhone(),
            ]);
            return;
        }

        $role = $user->getRole() ?? 'ROLE_CLIENT';

        $body = str_contains($role, 'DELIVERY')
            ? "⚠️ *BIG 4 Delivery Account Suspended*\n\n"
              . "Your BIG 4 delivery account has been suspended.\n"
              . "Reason: {$reason}\n\n"
              . "Please contact the administration for more details."
            : "⚠️ *BIG 4 Account Suspended*\n\n"
              . "Hello, your BIG 4 account has been suspended by the administration.\n"
              . "Reason: {$reason}\n\n"
              . "If you believe this is a mistake, please contact support.";

        $this->dispatch($to, $body, 'ban', $user->getId());
    }

    /**
     * Send an unban/reinstatement notification to the user via WhatsApp.
     */
    public function sendUnbanNotification(User $user): void
    {
        $to = $this->formatPhone($user->getPhone());

        if ($to === null) {
            $this->logger->warning('[WhatsApp] Unban notification skipped — no valid phone.', [
                'user_id' => $user->getId(),
                'email'   => $user->getEmail(),
                'phone'   => $user->getPhone(),
            ]);
            return;
        }

        $body = "✅ *BIG 4 Account Restored*\n\n"
              . "Your BIG 4 account has been fully reinstated.\n"
              . "You can now log in and access the platform again.\n\n"
              . "Thank you for your patience.";

        $this->dispatch($to, $body, 'unban', $user->getId());
    }

    /**
     * Send a raw test message — used by /test-whatsapp diagnostic route.
     *
     * @return array{success: bool, sid?: string, to: string, error?: string}
     */
    public function sendTestMessage(string $rawPhone): array
    {
        $to = $this->formatPhone($rawPhone);

        if ($to === null) {
            return [
                'success' => false,
                'to'      => $rawPhone,
                'error'   => "Could not format '{$rawPhone}' into a valid WhatsApp number.",
            ];
        }

        try {
            $client  = new TwilioClient($this->twilioSid, $this->twilioAuthToken);
            $message = $client->messages->create($to, [
                'from' => $this->twilioWhatsappNumber,
                'body' => "🧪 BIG 4 WhatsApp test — system operational at " . date('H:i:s'),
            ]);

            $this->logger->info('[WhatsApp] Test message sent.', ['to' => $to, 'sid' => $message->sid]);

            return ['success' => true, 'to' => $to, 'sid' => $message->sid];
        } catch (\Throwable $e) {
            $this->logger->error('[WhatsApp] Test message failed: ' . $e->getMessage(), ['to' => $to]);

            return ['success' => false, 'to' => $to, 'error' => $e->getMessage()];
        }
    }

    // ── Private helpers ────────────────────────────────────────────────────

    /**
     * Dispatch a Twilio message and log result.
     */
    private function dispatch(string $to, string $body, string $type, ?int $userId): void
    {
        try {
            $client  = new TwilioClient($this->twilioSid, $this->twilioAuthToken);
            $message = $client->messages->create($to, [
                'from' => $this->twilioWhatsappNumber,
                'body' => $body,
            ]);

            $this->logger->info("[WhatsApp] {$type} message sent.", [
                'to'      => $to,
                'sid'     => $message->sid,
                'user_id' => $userId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error("[WhatsApp] {$type} message FAILED: " . $e->getMessage(), [
                'to'        => $to,
                'user_id'   => $userId,
                'exception' => $e::class,
                'code'      => $e->getCode(),
            ]);
        }
    }

    /**
     * Normalize any Tunisian phone string into a WhatsApp-prefixed E.164 number.
     *
     * Examples:
     *   "96930620"        → "whatsapp:+21696930620"
     *   "+21696930620"    → "whatsapp:+21696930620"
     *   "0021696930620"   → "whatsapp:+21696930620"
     *   "  96 93 06 20 " → "whatsapp:+21696930620"
     */
    public function formatPhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        // Strip everything except digits
        $digits = (string) preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return null;
        }

        // Already has country code 00216...
        if (str_starts_with($digits, '00216')) {
            $local = substr($digits, 5);
            return strlen($local) >= 8 ? 'whatsapp:+216' . $local : null;
        }

        // Already has country code 216...
        if (str_starts_with($digits, '216') && strlen($digits) >= 11) {
            return 'whatsapp:+' . $digits;
        }

        // Starts with leading 0 (local format 0XXXXXXXX)
        if (str_starts_with($digits, '0') && strlen($digits) >= 9) {
            return 'whatsapp:+216' . ltrim($digits, '0');
        }

        // Plain local number (8 digits, e.g. 96930620)
        if (strlen($digits) >= 8) {
            return 'whatsapp:+216' . $digits;
        }

        return null;
    }
}
