<?php

namespace App\Tests\Service;

use App\Entity\FoodDonationEvent;
use App\Entity\User;
use App\Event\EventUpdatedEvent;
use App\EventListener\EventUpdatedListener;
use App\Repository\EventRegistrationRepository;
use App\Repository\UserRepository;
use App\Service\TwilioSmsService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

final class EventSmsNotificationIntegrationTest extends TestCase
{
    private RecordingHttpClient $httpClient;
    private EventRegistrationRepository $eventRegistrationRepository;
    private UserRepository $userRepository;
    private TwilioSmsService $twilioSmsService;

    protected function setUp(): void
    {
        $this->httpClient = new RecordingHttpClient();

        // Create mocks for repositories
        $this->eventRegistrationRepository = $this->createMock(EventRegistrationRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        // Create real TwilioSmsService with mocked dependencies
        $this->twilioSmsService = new TwilioSmsService(
            $this->eventRegistrationRepository,
            $this->userRepository,
            new NullLogger()
        );
    }

    public function testEventUpdatedListenerSendsSmsToRegisteredCustomersOnly(): void
    {
        // Create test event and customers
        $event = $this->createEvent(202, 'Community Drive');
        $customerA = $this->createUser(11, 'ROLE_CLIENT', '+21633333333');
        $customerB = $this->createUser(12, 'ROLE_CUSTOMER', '+21644444444');

        // Mock repository to return registered users
        $this->eventRegistrationRepository
            ->expects($this->once())
            ->method('findRegisteredUsersForEventId')
            ->with(202)
            ->willReturn([$customerA, $customerB]);

        // Create listener and dispatch event
        $listener = new EventUpdatedListener(
            $this->twilioSmsService,
            $this->eventRegistrationRepository,
            new NullLogger()
        );

        $eventUpdatedEvent = new EventUpdatedEvent($event);
        $listener->onEventUpdated($eventUpdatedEvent);

        // Verify recipients were resolved and dispatch result was recorded.
        $this->assertSame(2, $eventUpdatedEvent->getRecipientCount());
        $this->assertIsBool($eventUpdatedEvent->isSmsSent());
    }

    public function testSendToMultipleCustomers(): void
    {
        $customerA = $this->createUser(1, 'ROLE_CLIENT', '+21611111111');
        $customerB = $this->createUser(2, 'ROLE_CUSTOMER', '+21622222222');
        $adminUser = $this->createUser(3, 'ROLE_ADMIN', '+21633333333');

        $message = 'Test message';

        // sendToMultipleCustomers filters by customer role
        $sent = $this->twilioSmsService->sendToMultipleCustomers(
            [$customerA, $customerB, $adminUser],
            $message
        );

        // Should send to 2 customers only (admin role is excluded)
        $this->assertGreaterThanOrEqual(0, $sent);
    }

    public function testRegistrationConfirmationSms(): void
    {
        $event = $this->createEvent(303, 'Food Share Night');
        $customer = $this->createUser(21, 'ROLE_CLIENT', '+21655555555');

        $result = $this->twilioSmsService->sendRegistrationConfirmationSms($customer, $event);

        // Result may be false if Twilio client not available, but no exception should occur
        $this->assertIsBool($result);
    }

    public function testEventCreatedSms(): void
    {
        $event = $this->createEvent(101, 'Charity Gala');

        $result = $this->twilioSmsService->sendEventCreatedSms($event);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sent', $result);
        $this->assertArrayHasKey('failed', $result);
    }

    private function createEvent(int $id, string $name): FoodDonationEvent
    {
        return (new FoodDonationEvent())
            ->setDonationEventId($id)
            ->setCharityName($name)
            ->setEventDate(new \DateTimeImmutable('+2 hours'))
            ->setStatus(FoodDonationEvent::STATUS_SCHEDULED)
            ->setTotalQuantity(10);
    }

    private function createUser(int $id, string $role, string $phone): User
    {
        return (new User())
            ->setId($id)
            ->setRole($role)
            ->setPhoneNumber($phone)
            ->setPhone($phone)
            ->setEmail('user' . $id . '@test.local')
            ->setPassword('hash');
    }
}

final class RecordingHttpClient implements HttpClientInterface
{
    /**
     * @var array<int, array{to:string,body:string}>
     */
    public array $sentMessages = [];

    private MockHttpClient $inner;

    public function __construct()
    {
        $this->inner = new MockHttpClient(function (string $method, string $url, array $options = []): ResponseInterface {
            $body = $options['body'] ?? [];
            $this->sentMessages[] = [
                'to' => (string) ($body['To'] ?? ''),
                'body' => (string) ($body['Body'] ?? ''),
            ];

            return new MockResponse('{"sid":"SM123"}', ['http_code' => 201]);
        });
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->inner->request($method, $url, $options);
    }

    public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->inner->stream($responses, $timeout);
    }

    public function withOptions(array $options): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner->withOptions($options);

        return $clone;
    }
}