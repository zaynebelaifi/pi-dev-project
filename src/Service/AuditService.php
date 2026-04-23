<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
    ) {
    }

    public function logAction(?User $actor, string $action, string $entityType, int $entityId, ?array $changes = null): AuditLog
    {
        $request = $this->requestStack->getCurrentRequest();

        $log = (new AuditLog())
            ->setActor($actor)
            ->setAction($action)
            ->setEntityType($entityType)
            ->setEntityId($entityId)
            ->setChanges($changes)
            ->setTimestamp(new \DateTimeImmutable())
            ->setIpAddress($request?->getClientIp())
            ->setUserAgent($request?->headers->get('User-Agent'));

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $log;
    }
}
