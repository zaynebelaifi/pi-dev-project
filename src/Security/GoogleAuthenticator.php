<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * GoogleAuthenticator handles authentication via Google OAuth2.
 */
final class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private readonly ClientRegistry        $clientRegistry,
        private readonly EntityManagerInterface $em,
        private readonly RouterInterface       $router,
        private readonly UserRepository        $userRepository,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client      = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client): User {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email    = strtolower(trim((string) $googleUser->getEmail()));
                $googleId = (string) $googleUser->getId();

                $user = $this->userRepository->findOneBy(['googleId' => $googleId]);

                if (!$user) {
                    $user = $this->userRepository->findOneBy(['email' => $email]);
                }

                if (!$user) {
                    $user = new User();
                    $user->setEmail($email);
                    $user->setPassword('google_oauth_' . bin2hex(random_bytes(16)));
                    $user->setRole('ROLE_CLIENT');
                }

                $user->setGoogleId($googleId);

                if (!$user->getFirstName()) {
                    $user->setFirstName((string) $googleUser->getFirstName());
                }
                if (!$user->getLastName()) {
                    $user->setLastName((string) $googleUser->getLastName());
                }

                $user->setProfilePicture((string) $googleUser->getAvatar());

                $this->em->persist($user);
                $this->em->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user    = $token->getUser();
        $session = $request->getSession();
        $role    = $user->getRole() ?? 'ROLE_CLIENT';

        $session->set('user_id',    $user->getId());
        $session->set('user_email', $user->getEmail());
        $session->set('user_name',  trim($user->getFirstName() . ' ' . $user->getLastName()));
        $session->set('user_role',  $role);

        if ($role === 'ROLE_CLIENT') {
            $session->set('client_phone', $user->getPhone());
            $session->set('client_name',  trim($user->getFirstName() . ' ' . $user->getLastName()));
        }

        return match ($role) {
            'ROLE_ADMIN'        => new RedirectResponse($this->router->generate('app_admin_dashboard')),
            'ROLE_DELIVERY_MAN' => new RedirectResponse($this->router->generate('app_driver_deliveries')),
            default             => new RedirectResponse($this->router->generate('app_home')),
        };
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->getFlashBag()->add(
            'error',
            'Google login failed: ' . strtr($exception->getMessageKey(), $exception->getMessageData())
        );

        return new RedirectResponse($this->router->generate('app_login'));
    }
}
