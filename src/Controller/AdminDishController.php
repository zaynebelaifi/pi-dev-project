<?php
 
namespace App\Controller;
 
use App\Entity\Dish;
use App\Form\DishType;
use App\Repository\DishRepository;
use App\Repository\MenuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
 
#[Route('/admin/dish')]
final class AdminDishController extends AbstractController
{
    #[Route('/', name: 'admin_dish_index', methods: ['GET'])]
    public function index(Request $request, DishRepository $dishRepository, MenuRepository $menuRepository): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }
 
        $selectedMenu = null;
        $menuId = $request->query->getInt('menu', 0);
        if ($menuId > 0) {
            $selectedMenu = $menuRepository->find($menuId);
            if (null === $selectedMenu) {
                $this->addFlash('error', 'Selected menu was not found.');
                return $this->redirectToRoute('admin_menu_index');
            }
        }

        $search = \trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'created_at');
        $dir = (string) $request->query->get('dir', 'DESC');

        return $this->render('admin/dish/index.html.twig', [
            'dishes' => $dishRepository->findForAdminList($search, $sort, $dir, $selectedMenu),
            'selectedMenu' => $selectedMenu,
            'search' => $search,
            'sort' => $sort,
            'dir' => \strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC',
        ]);
    }
 
    #[Route('/new', name: 'admin_dish_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MenuRepository $menuRepository): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }
 
        $dish = new Dish();
        $dish->setAvailable(true);
        $dish->setCreated_at(new \DateTimeImmutable());
        $dish->setUpdated_at(new \DateTimeImmutable());
 
        // Pre-select and lock menu when coming from a specific menu page
        $menuId = $request->query->getInt('menu', 0);
        $lockedMenu = null;
        if ($menuId > 0) {
            $lockedMenu = $menuRepository->find($menuId);
            if (null === $lockedMenu) {
                $this->addFlash('error', 'Selected menu was not found.');
                return $this->redirectToRoute('admin_menu_index');
            }
            $dish->setMenu($lockedMenu);
        }
 
        $form = $this->createForm(DishType::class, $dish, [
            'lock_menu' => null !== $lockedMenu,
        ]);
        $form->handleRequest($request);
 
        if ($form->isSubmitted() && $form->isValid()) {
            if (null !== $lockedMenu) {
                $dish->setMenu($lockedMenu);
            }
            $dish->setUpdated_at(new \DateTimeImmutable());
            $entityManager->persist($dish);
            $entityManager->flush();
 
            $this->addFlash('success', 'Dish created successfully.');
 
            if ($dish->getMenu()) {
                return $this->redirectToRoute('admin_menu_show', ['id' => $dish->getMenu()->getId()]);
            }
 
            return $this->redirectToRoute('admin_dish_index');
        }
 
        return $this->render('admin/dish/new.html.twig', [
            'dish' => $dish,
            'form' => $form,
            'selectedMenu' => $lockedMenu,
        ]);
    }
 
    #[Route('/{id}', name: 'admin_dish_show', methods: ['GET'])]
    public function show(Request $request, Dish $dish): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }
 
        return $this->render('admin/dish/show.html.twig', [
            'dish' => $dish,
        ]);
    }
 
    #[Route('/{id}/edit', name: 'admin_dish_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Dish $dish, EntityManagerInterface $entityManager, MenuRepository $menuRepository): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $lockedMenu = null;
        $menuId = $request->query->getInt('menu', 0);
        if ($menuId > 0) {
            $lockedMenu = $menuRepository->find($menuId);
            if (null === $lockedMenu) {
                $this->addFlash('error', 'Selected menu was not found.');
                return $this->redirectToRoute('admin_menu_index');
            }
            if (null === $dish->getMenu() || $dish->getMenu()->getId() !== $lockedMenu->getId()) {
                $this->addFlash('error', 'This dish does not belong to the selected menu.');
                return $this->redirectToRoute('admin_dish_index', ['menu' => $lockedMenu->getId()]);
            }
        }

        $form = $this->createForm(DishType::class, $dish, [
            'lock_menu' => null !== $lockedMenu,
        ]);
        $form->handleRequest($request);
 
        if ($form->isSubmitted() && $form->isValid()) {
            if (null !== $lockedMenu) {
                $dish->setMenu($lockedMenu);
            }
            $dish->setUpdated_at(new \DateTimeImmutable());
            $entityManager->flush();
 
            $this->addFlash('success', 'Dish updated successfully.');

            if (null !== $lockedMenu) {
                return $this->redirectToRoute('admin_dish_index', ['menu' => $lockedMenu->getId()]);
            }

            return $this->redirectToRoute('admin_dish_index');
        }
 
        return $this->render('admin/dish/edit.html.twig', [
            'dish' => $dish,
            'form' => $form,
            'selectedMenu' => $lockedMenu,
        ]);
    }
 
    #[Route('/{id}', name: 'admin_dish_delete', methods: ['POST'])]
    public function delete(Request $request, Dish $dish, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }
 
        if ($this->isCsrfTokenValid('delete'.$dish->getId(), $request->request->get('_token'))) {
            $entityManager->remove($dish);
            $entityManager->flush();
            $this->addFlash('success', 'Dish deleted successfully.');
        }
 
        return $this->redirectToRoute('admin_dish_index');
    }
}
