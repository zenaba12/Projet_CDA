<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/cart')]
class CartController extends AbstractController
{
    //  Afficher le panier
    #[Route('/', name: 'cart_show')]
    public function showCart(Security $security, EntityManagerInterface $em): Response
    {
        $user = $security->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $cart = $em->getRepository(Cart::class)->findOneBy(['user' => $user]);

        $total = 0;
        if ($cart) {
            foreach ($cart->getCartItems() as $item) {
                $total += $item->getProduct()->getPrix() * $item->getQuantity();
            }
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'total' => $total
        ]);
    }

    // Ajouter un produit au panier
    #[Route('/add/{id}', name: 'cart_add')]
    public function addToCart(Product $product, EntityManagerInterface $em, Security $security): Response
    {
        $user = $security->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        // 🔹 Récupérer le panier de l'utilisateur ou en créer un
        $cart = $em->getRepository(Cart::class)->findOneBy(['user' => $user]);
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $em->persist($cart);
        }

        // 🔹 Vérifier si le produit est déjà dans le panier
        $item = $em->getRepository(CartItem::class)->findOneBy(['cart' => $cart, 'product' => $product]);

        if ($item) {
            $item->setQuantity($item->getQuantity() + 1);
        } else {
            $item = new CartItem();
            $item->setCart($cart);
            $item->setProduct($product);
            $item->setQuantity(1);
            $em->persist($item);
        }

        $em->flush();

        $this->addFlash('success', "Produit ajouté au panier !");
        return $this->redirectToRoute('cart_show');
    }

    //  Supprimer un produit du panier
    #[Route('/remove/{id}', name: 'cart_remove')]
    public function removeFromCart(CartItem $item, EntityManagerInterface $em, Security $security): Response
    {
        $user = $security->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $cart = $em->getRepository(Cart::class)->findOneBy(['user' => $user]);
        if (!$cart || !$cart->getCartItems()->contains($item)) {
            $this->addFlash('error', "Produit introuvable dans le panier.");
            return $this->redirectToRoute('cart_show');
        }

        $em->remove($item);
        $em->flush();

        $this->addFlash('success', "Produit retiré du panier !");
        return $this->redirectToRoute('cart_show');
    }

    //  Valider la commande
    #[Route('/checkout', name: 'cart_checkout')]
    public function checkout(EntityManagerInterface $em, Security $security): Response
    {
        $user = $security->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $cart = $em->getRepository(Cart::class)->findOneBy(['user' => $user]);
        if (!$cart || $cart->getCartItems()->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('cart_show');
        }

        // TODO: Ajouter la logique de création de commande

        // 🔹 Vider le panier
        foreach ($cart->getCartItems() as $item) {
            $em->remove($item);
        }
        $em->remove($cart);
        $em->flush();

        $this->addFlash('success', 'Commande validée avec succès.');
        return $this->redirectToRoute('cart_show');
    }
}
