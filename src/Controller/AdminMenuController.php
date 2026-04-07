<?php
 
namespace App\Controller;
 
use App\Entity\Menu;
use App\Form\MenuType;
use App\Repository\MenuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
 
#[Route('/admin/menu')]
final class AdminMenuController extends AbstractController
{
    #[Route('/', name: 'admin_menu_index', methods: ['GET'])]
    public function index(Request $request, MenuRepository $menuRepository): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }
 
        $search = \trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'created_at');
        $dir = (string) $request->query->get('dir', 'DESC');

        return $this->render('admin/menu/index.html.twig', [
            'menus' => $menuRepository->findForAdminList($search, $sort, $dir),
            'search' => $search,
            'sort' => $sort,
            'dir' => \strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC',
        ]);
    }
 
    #[Route('/new', name: 'admin_menu_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }
 
        $menu = new Menu();
        $menu->setIsActive(true);
        $menu->setCreated_at(new \DateTimeImmutable());
        $menu->setUpdated_at(new \DateTimeImmutable());
 
        $form = $this->createForm(MenuType::class, $menu);
        $form->handleRequest($request);
 
        if ($form->isSubmitted() && $form->isValid()) {
            $menu->setUpdated_at(new \DateTimeImmutable());
            $entityManager->persist($menu);
            $entityManager->flush();
 
            $this->addFlash('success', 'Menu created successfully.');
 
            return $this->redirectToRoute('admin_menu_index');
        }
 
        return $this->render('admin/menu/new.html.twig', [
            'menu' => $menu,
            'form' => $form,
        ]);
    }
 
    #[Route('/{id}', name: 'admin_menu_show', methods: ['GET'])]
    public function show(Request $request, Menu $menu, \App\Repository\DishRepository $dishRepository): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }
 
        return $this->render('admin/menu/show.html.twig', [
            'menu' => $menu,
            'dishes' => $dishRepository->findBy(['menu' => $menu], ['created_at' => 'DESC']),
        ]);
    }
 
    #[Route('/{id}/edit', name: 'admin_menu_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Menu $menu, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }
 
        $form = $this->createForm(MenuType::class, $menu);
        $form->handleRequest($request);
 
        if ($form->isSubmitted() && $form->isValid()) {
            $menu->setUpdated_at(new \DateTimeImmutable());
            $entityManager->flush();
 
            $this->addFlash('success', 'Menu updated successfully.');
 
            return $this->redirectToRoute('admin_menu_index');
        }
 
        return $this->render('admin/menu/edit.html.twig', [
            'menu' => $menu,
            'form' => $form,
        ]);
    }
 
    #[Route('/{id}', name: 'admin_menu_delete', methods: ['POST'])]
    public function delete(Request $request, Menu $menu, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }
 
        if ($this->isCsrfTokenValid('delete'.$menu->getId(), $request->request->get('_token'))) {
            $entityManager->remove($menu);
            $entityManager->flush();
            $this->addFlash('success', 'Menu deleted successfully.');
        }
 
        return $this->redirectToRoute('admin_menu_index');
    }
}
