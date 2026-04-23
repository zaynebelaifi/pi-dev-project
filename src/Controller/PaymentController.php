<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\User1;
use App\Repository\OrderRepository;
use App\Repository\User1Repository;
use App\Repository\UserRepository;
use App\Service\PaymentConfirmationMailer;
use App\Service\StripeCheckoutService;
use App\Service\TwilioVoiceConfirmationService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/payment')]
final class PaymentController extends AbstractController
{
    #[Route('/stripe/session', name: 'app_payment_stripe_session', methods: ['POST'])]
    public function createStripeSession(
        Request $request,
        OrderRepository $orderRepository,
        StripeCheckoutService $stripeCheckoutService,
    ): JsonResponse {
        $payload = json_decode((string) $request->getContent(), true);
        $orderId = (int) ($payload['order_id'] ?? 0);

        if ($orderId <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid order id.'], Response::HTTP_BAD_REQUEST);
        }

        $session = $request->getSession();
        $allowedOrderId = (int) ($session->get('checkout_order_id') ?? 0);

        if ($allowedOrderId !== $orderId) {
            return new JsonResponse(['success' => false, 'message' => 'Order is not authorized for checkout.'], Response::HTTP_FORBIDDEN);
        }

        $order = $orderRepository->find($orderId);
        if (null === $order) {
            return new JsonResponse(['success' => false, 'message' => 'Order not found.'], Response::HTTP_NOT_FOUND);
        }

        $successUrl = $this->generateUrl('app_payment_success', ['orderId' => $orderId], UrlGeneratorInterface::ABSOLUTE_URL);
        $cancelUrl = $this->generateUrl('app_payment_cancel', ['orderId' => $orderId], UrlGeneratorInterface::ABSOLUTE_URL);

        try {
            $checkoutSession = $stripeCheckoutService->createSession(
                $order,
                $successUrl.'?session_id={CHECKOUT_SESSION_ID}',
                $cancelUrl
            );
            $paymentSummary = $stripeCheckoutService->getCheckoutSummary($order);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'success' => true,
            'checkoutUrl' => (string) $checkoutSession->url,
            'paymentSummary' => $paymentSummary,
        ]);
    }

    #[Route('/success/{orderId}', name: 'app_payment_success', methods: ['GET'])]
    public function success(
        Request $request,
        int $orderId,
        OrderRepository $orderRepository,
        UserRepository $userRepository,
        User1Repository $user1Repository,
        PaymentConfirmationMailer $paymentConfirmationMailer,
        TwilioVoiceConfirmationService $twilioVoiceConfirmationService,
        LoggerInterface $logger,
    ): Response
    {
        $sessionId = (string) $request->query->get('session_id', '');
        $session = $request->getSession();
        $session->remove('checkout_order_id');

        $order = $orderRepository->find($orderId);
        if (null === $order) {
            $this->addFlash('error', sprintf('Payment completed, but order #%d could not be found.', $orderId));

            return $this->redirectToRoute('app_home');
        }

        $recipientEmail = $this->resolveRecipientEmail($order->getClientId(), $userRepository, $user1Repository, (string) $session->get('user_email', ''));
        $recipientPhone = $this->resolveRecipientPhone($order->getClientId(), $userRepository, (string) $session->get('client_phone', ''));
        $recipientName = trim((string) $session->get('user_name', ''));

        if ($recipientEmail !== '') {
            $alreadySentKey = sprintf('payment_confirmation_sent_%d', $orderId);
            if (!$session->get($alreadySentKey, false)) {
                try {
                    $paymentConfirmationMailer->sendPaymentConfirmation($order, $recipientEmail, $recipientName);
                    $session->set($alreadySentKey, true);
                } catch (\Throwable $e) {
                    $this->addFlash('error', 'Payment was successful, but the confirmation email could not be sent: ' . $e->getMessage());
                }
            }
        } else {
            $this->addFlash('error', 'Payment was successful, but no customer email address was available for the confirmation message.');
        }

        if ($recipientPhone !== '') {
            $alreadySentVoiceKey = sprintf('payment_voice_confirmation_sent_%d', $orderId);
            if (!$session->get($alreadySentVoiceKey, false)) {
                try {
                    $twilioVoiceConfirmationService->sendPaymentConfirmationCall($order, $recipientPhone, $recipientName);
                    $session->set($alreadySentVoiceKey, true);
                } catch (\Throwable $e) {
                    $logger->warning('Failed to send payment voice confirmation call.', [
                        'order_id' => $orderId,
                        'client_id' => $order->getClientId(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } else {
            $logger->info('Skipping payment voice confirmation call because no customer phone number is available.', [
                'order_id' => $orderId,
                'client_id' => $order->getClientId(),
            ]);
        }

        $message = 'Payment completed successfully.';
        if ($sessionId !== '') {
            $message .= sprintf(' Stripe session: %s', $sessionId);
        }

        $this->addFlash('success', sprintf('Order #%d paid. %s', $orderId, $message));

        return $this->redirectToRoute('app_home');
    }

    #[Route('/cancel/{orderId}', name: 'app_payment_cancel', methods: ['GET'])]
    public function cancel(int $orderId): Response
    {
        $this->addFlash('error', sprintf('Payment cancelled for order #%d.', $orderId));

        return $this->redirectToRoute('app_home');
    }

    private function resolveRecipientEmail(
        ?int $clientId,
        UserRepository $userRepository,
        User1Repository $user1Repository,
        string $sessionEmail,
    ): string {
        if ($clientId !== null && $clientId > 0) {
            $user = $userRepository->find($clientId);
            if ($user instanceof User && trim((string) $user->getEmail()) !== '') {
                return trim((string) $user->getEmail());
            }

            $legacyUser = $user1Repository->find($clientId);
            if ($legacyUser instanceof User1 && trim((string) $legacyUser->getEmail()) !== '') {
                return trim((string) $legacyUser->getEmail());
            }
        }

        return trim($sessionEmail);
    }

    private function resolveRecipientPhone(
        ?int $clientId,
        UserRepository $userRepository,
        string $sessionPhone,
    ): string {
        if ($clientId !== null && $clientId > 0) {
            $user = $userRepository->find($clientId);
            if ($user instanceof User && trim((string) $user->getPhone()) !== '') {
                return trim((string) $user->getPhone());
            }
        }

        return trim($sessionPhone);
    }
}
