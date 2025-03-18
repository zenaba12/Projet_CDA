<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Form\ProductType;
use App\Entity\CartItem;
use App\Entity\Cart;
use Symfony\Bundle\SecurityBundle\Security;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/product')]
class ProductController extends AbstractController
{
    // Afficher la liste des produits
    #[Route('/', name: 'product_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Cet espace est réservé aux administrateurs.');
            return $this->redirectToRoute('app_home');
        }

        $products = $em->getRepository(Product::class)->findAll();

        return $this->render('product/index.html.twig', ['products' => $products]);
    }

    // Afficher un produit spécifique avec formulaire commentaire
    #[Route('/{id}', name: 'product_show', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function show(
        Product $product,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            if (!$this->getUser()) {
                return $this->redirectToRoute('app_login');
            }

            $comment->setUser($this->getUser());
            $comment->setProduct($product);

            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Commentaire ajouté avec succès.');

            return $this->redirectToRoute('product_show', ['id' => $product->getId()]);
        }

        // Vue spécifique selon le nom du produit
        $template = match ($product->getNom()) {
            'Huile de Batana' => 'product/batana.html.twig',
            'Huile de Moringa' => 'product/moringa.html.twig',
            'Huile de Chebé' => 'product/chebe.html.twig',
            default => 'product/show.html.twig',
        };

        return $this->render($template, [
            'product' => $product,
            'commentForm' => $commentForm->createView(),
        ]);
    }

    //  Ajouter un produit
    #[Route('/new', name: 'product_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move($this->getParameter('images_directory'), $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', "Erreur lors de l'upload de l'image.");
                    return $this->redirectToRoute('product_new');
                }

                $product->setImage($newFilename);
            }

            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Produit ajouté avec succès.');
            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'product_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(int $id, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $product = $em->getRepository(Product::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Produit non trouvé.');
        }

        $existingImage = $product->getImage(); // Sauvegarde de l'image actuelle

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                // Génération d'un nom sécurisé
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move($this->getParameter('images_directory'), $newFilename);
                    $product->setImage($newFilename); // Mise à jour de l’image avec la nouvelle
                } catch (FileException $e) {
                    $this->addFlash('error', "Erreur lors de l'upload de l'image : " . $e->getMessage());
                    return $this->redirectToRoute('product_edit', ['id' => $product->getId()]);
                }
            } else {
                // Si aucune nouvelle image n'est ajoutée, on conserve l'ancienne
                $product->setImage($existingImage);
            }

            $em->flush();
            $this->addFlash('success', 'Produit mis à jour avec succès.');
            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/edit.html.twig', [
            'form' => $form->createView(),
            'product' => $product
        ]);
    }
}
