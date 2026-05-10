<?php

namespace App\Tests\Service;

use App\Command\EventReminderCommand;
use App\Entity\EventRegistration;
use App\Entity\FoodDonationEvent;
use App\Entity\User;
use App\Event\EventCreatedEvent;
use App\Event\EventRegistrationEvent;
use App\Event\EventUpdatedEvent;
use App\EventListener\EventCreatedListener;
use App\EventListener\EventUpdatedListener;
use App\EventListener\RegistrationListener;
use App\Repository\EventRegistrationRepository;
use App\Repository\UserRepository;
use App\Service\TwilioSmsService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

final class EventSmsNotificationIntegrationTest extends TestCase
{
    public function testEventCreatedListenerSendsSmsToAllCustomersOnly(): void
    {
        $event = $this->createEvent(101, 'Charity Gala');
        $customerA = $this->createUser(1, 'ROLE_CLIENT', '+21611111111');
        $customerB = $this->createUser(2, 'ROLE_CUSTOMER', '+21622222222');

        $userRepository = new StubCustomerUserRepository([$customerA, $customerB]);

        $http = new RecordingHttpClient();
        $twilio = new TwilioSmsService($http, new NullLogger(), null, 'sid', 'token', '+18145189639');

        $listener = new EventCreatedListener($twilio, $userRepository, new NullLogger());
        $listener->onEventCreated(new EventCreatedEvent($event));

        $this->assertCount(2, $http->sentMessages);
    }

    public function testEventUpdatedListenerSendsSmsToRegisteredCustomersOnly(): void
    {
        $event = $this->createEvent(202, 'Community Drive');
        $customerA = $this->createUser(11, 'ROLE_CLIENT', '+21633333333');
        $customerB = $this->createUser(12, 'ROLE_CUSTOMER', '+21644444444');

        $registrationRepository = $this->createMock(EventRegistrationRepository::class);
        $registrationRepository
            ->expects($this->once())
            ->method('findRegisteredCustomerUsersForEventId')
            ->with(202)
            ->willReturn([$customerA, $customerB]);

        $http = new RecordingHttpClient();
        $twilio = new TwilioSmsService($http, new NullLogger(), null, 'sid', 'token', '+18145189639');

        $listener = new EventUpdatedListener($twilio, $registrationRepository, new NullLogger());
        $listener->onEventUpdated(new EventUpdatedEvent($event));

        $this->assertCount(2, $http->sentMessages);
    }

    public function testRegistrationListenerSendsSmsToRegisteringCustomerOnly(): void
    {
        $event = $this->createEvent(303, 'Food Share Night');
        $customer = $this->createUser(21, 'ROLE_CLIENT', '+21655555555');

        $http = new RecordingHttpClient();
        $twilio = new TwilioSmsService($http, new NullLogger(), null, 'sid', 'token', '+18145189639');

        $listener = new RegistrationListener($twilio, new NullLogger());
        $domainEvent = new EventRegistrationEvent($customer, $event);
        $listener->onRegistration($domainEvent);

        $this->assertTrue($domainEvent->isSmsSent());
        $this->assertCount(1, $http->sentMessages);
    }

    public function testEventReminderCommandSendsToRegisteredCustomersOncePerCustomer(): void
    {
        $event = $this->createEvent(404, 'Evening Relief');

        $customerA = $this->createUser(31, 'ROLE_CLIENT', '+21666666666');
        $customerB = $this->createUser(32, 'ROLE_CUSTOMER', '+21677777777');
        $admin = $this->createUser(33, 'ROLE_ADMIN', '+21688888888');

        $registrationA = (new EventRegistration())
            ->setEvent($event)
            ->setUser($customerA)
            ->setCreatedAt(new \DateTimeImmutable('now'));

        $registrationB = (new EventRegistration())
            ->setEvent($event)
            ->setUser($customerB)
            ->setCreatedAt(new \DateTimeImmutable('now'));

        $registrationAdmin = (new EventRegistration())
            ->setEvent($event)
            ->setUser($admin)
            ->setCreatedAt(new \DateTimeImmutable('now'));

        $registrationRepository = $this->createMock(EventRegistrationRepository::class);
        $registrationRepository
            ->expects($this->once())
            ->method('findRegistrationsNeedingReminder')
            ->willReturn([$registrationA, $registrationB, $registrationAdmin]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $http = new RecordingHttpClient();
        $twilio = new TwilioSmsService($http, new NullLogger(), null, 'sid', 'token', '+18145189639');

        $command = new EventReminderCommand($registrationRepository, $twilio, $entityManager);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute(['--window-minutes' => '60']);

        $this->assertSame(0, $exitCode);
        $this->assertCount(2, $http->sentMessages);
        $this->assertNotNull($registrationA->getReminderSentAt());
        $this->assertNotNull($registrationB->getReminderSentAt());
        $this->assertNull($registrationAdmin->getReminderSentAt());
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

final class StubCustomerUserRepository extends UserRepository
{
    /**
     * @param User[] $users
     */
    public function __construct(private readonly array $users)
    {
    }

    /**
     * @return User[]
     */
    public function findCustomerUsersWithPhoneNumber(): array
    {
        return $this->users;
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
