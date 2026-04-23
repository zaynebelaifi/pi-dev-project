<?php

namespace App\Repository;

use App\Entity\WebauthnCredential;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\Bundle\Repository\CanSaveCredentialSource;
use Webauthn\Bundle\Repository\PublicKeyCredentialSourceRepositoryInterface;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

class WebauthnCredentialRepository implements PublicKeyCredentialSourceRepositoryInterface, CanSaveCredentialSource
{
    public function __construct(
        private WebauthnCredentialDoctrineRepository $credentialDoctrineRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        $credentialId = base64_encode($publicKeyCredentialId);
        $record = $this->credentialDoctrineRepository->findOneBy(['credential_id' => $credentialId]);
        if (!$record instanceof WebauthnCredential || !$record->getSourceJson()) {
            return null;
        }

        $source = $this->deserializeCredentialSource($record->getSourceJson());

        return $source instanceof PublicKeyCredentialSource ? $source : null;
    }

    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        $records = $this->credentialDoctrineRepository->findBy([
            'user_handle' => $publicKeyCredentialUserEntity->id,
        ]);

        $sources = [];
        foreach ($records as $record) {
            if (!$record instanceof WebauthnCredential || !$record->getSourceJson()) {
                continue;
            }

            $source = $this->deserializeCredentialSource($record->getSourceJson());
            if ($source instanceof PublicKeyCredentialSource) {
                $sources[] = $source;
            }
        }

        return $sources;
    }

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $credentialId = base64_encode($publicKeyCredentialSource->publicKeyCredentialId);
        $record = $this->credentialDoctrineRepository->findOneBy(['credential_id' => $credentialId]);
        if (!$record instanceof WebauthnCredential) {
            $record = new WebauthnCredential();
            $record->setCredentialId($credentialId);
            $record->setCreatedAt(new \DateTimeImmutable());
            $this->entityManager->persist($record);
        }

        $serializedSource = $this->serializeCredentialSource($publicKeyCredentialSource);

        $record
            ->setUserHandle($publicKeyCredentialSource->userHandle)
            ->setSourceJson($serializedSource)
            ->setCounter($this->extractCounterFromSerializedSource($serializedSource))
            ->setPublicKey($this->extractPublicKeyFromSerializedSource($serializedSource))
            ->setUpdatedAt(new \DateTimeImmutable());

        if (ctype_digit((string) $publicKeyCredentialSource->userHandle)) {
            $user = $this->userRepository->find((int) $publicKeyCredentialSource->userHandle);
            $record->setUser($user);
        }

        $this->entityManager->flush();
    }

    private function serializeCredentialSource(PublicKeyCredentialSource $source): string
    {
        $serializer = (new WebauthnSerializerFactory(AttestationStatementSupportManager::create()))->create();

        return $serializer->serialize($source, 'json');
    }

    private function deserializeCredentialSource(string $sourceJson): ?PublicKeyCredentialSource
    {
        $serializer = (new WebauthnSerializerFactory(AttestationStatementSupportManager::create()))->create();
        $source = $serializer->deserialize($sourceJson, PublicKeyCredentialSource::class, 'json');

        return $source instanceof PublicKeyCredentialSource ? $source : null;
    }

    private function extractCounterFromSerializedSource(string $serializedSource): int
    {
        $decoded = json_decode($serializedSource, true);
        if (!is_array($decoded)) {
            return 0;
        }

        if (isset($decoded['counter']) && is_numeric($decoded['counter'])) {
            return (int) $decoded['counter'];
        }

        if (isset($decoded['signatureCounter']) && is_numeric($decoded['signatureCounter'])) {
            return (int) $decoded['signatureCounter'];
        }

        return 0;
    }

    private function extractPublicKeyFromSerializedSource(string $serializedSource): ?string
    {
        $decoded = json_decode($serializedSource, true);
        if (!is_array($decoded)) {
            return null;
        }

        if (isset($decoded['publicKey']) && is_string($decoded['publicKey']) && $decoded['publicKey'] !== '') {
            return $decoded['publicKey'];
        }

        if (isset($decoded['credentialPublicKey']) && is_string($decoded['credentialPublicKey']) && $decoded['credentialPublicKey'] !== '') {
            return $decoded['credentialPublicKey'];
        }

        return null;
    }
}
