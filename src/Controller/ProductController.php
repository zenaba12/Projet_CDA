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

    //  Modifier un produit
    #[Route('/edit/{id}', name: 'product_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(int $id, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $product = $em->getRepository(Product::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Produit non trouvé.');
        }

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
                    $this->addFlash('error', "Erreur lors de l'upload de l'image : " . $e->getMessage());
                    return $this->redirectToRoute('product_new');
                }

                $product->setImage($newFilename);
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

    //  Supprimer un produit
    #[Route('/delete/{id}', name: 'product_delete', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, EntityManagerInterface $em, Request $request): Response
    {
        $product = $em->getRepository(Product::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Produit non trouvé.');
        }

        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $em->remove($product);
            $em->flush();
            $this->addFlash('success', 'Produit supprimé avec succès.');
        }

        return $this->redirectToRoute('product_index');
    }
    #[Route('/add-to-cart/{id}', name: 'product_add_to_cart')]
    public function addToCart(int $id, EntityManagerInterface $em, Security $security): Response
    {
        $user = $security->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $product = $em->getRepository(Product::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException("Produit non trouvé.");
        }

        // Vérifier si l'utilisateur a déjà un panier
        $cart = $em->getRepository(Cart::class)->findOneBy(['user' => $user]);

        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $em->persist($cart);
        }

        // Vérifier si le produit est déjà dans le panier
        $cartItem = $em->getRepository(CartItem::class)->findOneBy(['cart' => $cart, 'product' => $product]);

        if ($cartItem) {
            $cartItem->setQuantity($cartItem->getQuantity() + 1);
        } else {
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setProduct($product);
            $cartItem->setQuantity(1);
            $em->persist($cartItem);
        }

        $em->flush();

        $this->addFlash('success', 'Produit ajouté au panier.');
        return $this->redirectToRoute('cart_show');
    }
}
