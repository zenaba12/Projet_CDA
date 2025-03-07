<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/category')]
class CategoryController extends AbstractController
{
    // Affichage du catalogue pour les visiteurs et clients
    #[Route('/', name: 'category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('category/catalogue.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    // Affichage des produits par catégorie
    #[Route('/{id<\d+>}', name: 'category_show', methods: ['GET'])]
    public function show(CategoryRepository $categoryRepository, int $id): Response
    {
        $category = $categoryRepository->find($id);
        if (!$category) {
            throw $this->createNotFoundException("⚠️ Cette catégorie n'existe pas !");
        }

        return $this->render('category/show.html.twig', [
            'category' => $category,
            'products' => $category->getProducts(),
        ]);
    }

    // Affichage de l'interface admin pour gérer les catégories
    #[Route('/admin', name: 'admin_category_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(CategoryRepository $categoryRepository): Response
    {
        return $this->render('category/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    // Ajouter une catégorie (ADMIN uniquement)
    #[Route('/admin/new', name: 'category_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();
            $this->addFlash('success', 'Catégorie ajoutée avec succès.');
            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('category/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Modifier une catégorie (ADMIN uniquement)
    #[Route('/admin/edit/{id}', name: 'category_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, CategoryRepository $categoryRepository, int $id, EntityManagerInterface $em): Response
    {
        $category = $categoryRepository->find($id);
        if (!$category) {
            throw $this->createNotFoundException("⚠️ Catégorie introuvable !");
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Catégorie mise à jour avec succès.');
            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('category/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Supprimer une catégorie (ADMIN uniquement)
    #[Route('/admin/delete/{id}', name: 'category_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, CategoryRepository $categoryRepository, int $id, EntityManagerInterface $em): Response
    {
        $category = $categoryRepository->find($id);
        if (!$category) {
            throw $this->createNotFoundException("⚠️ Catégorie introuvable !");
        }

        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
            $em->remove($category);
            $em->flush();
            $this->addFlash('success', 'Catégorie supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_category_index');
    }
}
