<?php

namespace App\Controller;

use App\Repository\DishRepository;
use App\Repository\MenuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class landingpageController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(MenuRepository $menuRepository, DishRepository $dishRepository): Response
    {
        $menus = $menuRepository->findBy(['isActive' => true], ['title' => 'ASC']);
        $menuSections = [];

        foreach ($menus as $menu) {
            $dishes = $dishRepository->findBy([
                'menu' => $menu,
                'available' => true,
            ], ['name' => 'ASC']);

            if ([] !== $dishes) {
                $menuSections[] = [
                    'menu' => $menu,
                    'dishes' => $dishes,
                ];
            }
        }

        return $this->render('base.html.twig', [
            'controller_name' => 'landingpageController',
            'menuSections' => $menuSections,
        ]);
    }

    #[Route('/landingpage', name: 'app_landingpage')]
    public function index(MenuRepository $menuRepository, DishRepository $dishRepository): Response
    {
        $menus = $menuRepository->findBy(['isActive' => true], ['title' => 'ASC']);
        $menuSections = [];

        foreach ($menus as $menu) {
            $dishes = $dishRepository->findBy([
                'menu' => $menu,
                'available' => true,
            ], ['name' => 'ASC']);

            if ([] !== $dishes) {
                $menuSections[] = [
                    'menu' => $menu,
                    'dishes' => $dishes,
                ];
            }
        }

        return $this->render('base.html.twig', [
            'controller_name' => 'landingpageController',
            'menuSections' => $menuSections,
        ]);
    }
}
