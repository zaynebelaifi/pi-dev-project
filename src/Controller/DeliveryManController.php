<?php

namespace App\Controller;

use App\Entity\DeliveryMan;
use App\Entity\User;
use App\Form\DeliveryManType;
use App\Repository\DeliveryManRepository;
use App\Repository\UserRepository;
use App\Repository\DeliveryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/delivery/man')]
final class DeliveryManController extends AbstractController
{
    #[Route(name: 'app_delivery_man_index', methods: ['GET'])]
    public function index(Request $request, DeliveryManRepository $deliveryManRepository): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $search = trim((string) $request->query->get('search', ''));
        $sort = $request->query->get('sort', 'date_of_joining');
        $direction = $request->query->get('direction', 'DESC');

        return $this->render('delivery_man/index.html.twig', [
            'delivery_men' => $deliveryManRepository->searchAndSort($search, $sort, $direction),
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/new', name: 'app_delivery_man_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $deliveryMan = new DeliveryMan();
        $form = $this->createForm(DeliveryManType::class, $deliveryMan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Please complete all required fields and fix invalid values.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $deliveryMan->setCreated_at(new \DateTime());
            $deliveryMan->setUpdated_at(new \DateTime());
            $entityManager->persist($deliveryMan);
            $entityManager->flush();

            // Create or update a User account for this delivery driver so they can sign in
            $email = strtolower(trim((string) $deliveryMan->getEmail()));
            if ($email !== '') {
                $existing = $userRepository->createQueryBuilder('u')
                    ->andWhere('LOWER(u.email) = :email')
                    ->setParameter('email', $email)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();

                $plainPassword = preg_replace('/\s+/', '', (string) $deliveryMan->getName()) . 'delivery72';

                if (!$existing) {
                    $user = new User();
                    $user->setEmail($email);
                    // Try to split name into first/last
                    $parts = preg_split('/\s+/', trim((string) $deliveryMan->getName()));
                    $user->setFirstName($parts[0] ?? null);
                    $user->setLastName($parts[1] ?? null);
                    $user->setRole('ROLE_DELIVERY_MAN');
                    $user->setReference_id((int) $deliveryMan->getDelivery_man_id());
                    $hashed = $passwordHasher->hashPassword($user, (string) $plainPassword);
                    $user->setPassword($hashed);
                    $entityManager->persist($user);
                    $entityManager->flush();

                    $this->addFlash('success', sprintf('Driver created. Credentials — email: %s password: %s', $email, $plainPassword));
                } else {
                    // Ensure reference_id and role are set
                    $changed = false;
                    if ($existing->getReference_id() !== $deliveryMan->getDelivery_man_id()) {
                        $existing->setReference_id((int) $deliveryMan->getDelivery_man_id());
                        $changed = true;
                    }
                    if ($existing->getRole() !== 'ROLE_DELIVERY_MAN') {
                        $existing->setRole('ROLE_DELIVERY_MAN');
                        $changed = true;
                    }
                    if ($changed) {
                        $entityManager->flush();
                    }

                    $this->addFlash('info', sprintf('Driver created — existing user %s updated.', $email));
                }
            }

            return $this->redirectToRoute('app_delivery_man_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('delivery_man/new.html.twig', [
            'delivery_man' => $deliveryMan,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_delivery_man_show', methods: ['GET'])]
    public function show(Request $request, DeliveryMan $deliveryMan): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('delivery_man/show.html.twig', [
            'delivery_man' => $deliveryMan,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_delivery_man_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, DeliveryMan $deliveryMan, EntityManagerInterface $entityManager): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }
        $form = $this->createForm(DeliveryManType::class, $deliveryMan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Please complete all required fields and fix invalid values.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $deliveryMan->setUpdated_at(new \DateTime());
            $entityManager->flush();

            return $this->redirectToRoute('app_delivery_man_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('delivery_man/edit.html.twig', [
            'delivery_man' => $deliveryMan,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_delivery_man_delete', methods: ['POST'])]
    public function delete(Request $request, ?DeliveryMan $deliveryMan, EntityManagerInterface $entityManager, DeliveryRepository $deliveryRepository): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }
        
        if (null === $deliveryMan) {
            $this->addFlash('error', 'Delivery driver not found.');
            return $this->redirectToRoute('app_delivery_man_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($this->isCsrfTokenValid('delete'.$deliveryMan->getDelivery_man_id(), $request->request->get('_token'))) {
            // First, unassign this delivery man from all deliveries
            $deliveries = $deliveryRepository->findByDeliveryManId($deliveryMan->getDelivery_man_id());
            foreach ($deliveries as $delivery) {
                $delivery->setDeliveryMan(null);
                $delivery->setStatus('PENDING'); // Reset status since delivery man is removed
            }
            $entityManager->flush(); // Commit the NULL updates

            // Now safe to delete the delivery man
            $entityManager->remove($deliveryMan);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_delivery_man_index', [], Response::HTTP_SEE_OTHER);
    }
}
