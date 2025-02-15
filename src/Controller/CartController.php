<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Form\CartType;
use App\Form\CartItemType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

#[Route('/cart')]
class CartController extends AbstractController
{
    // ✅ Afficher le panier
    #[Route('/', name: 'cart_show')]
public function showCart(Security $security, EntityManagerInterface $em, Request $request): Response
{
    $user = $security->getUser();
    if (!$user) return $this->redirectToRoute('app_login');

    $cart = $em->getRepository(Cart::class)->findOneBy(['user' => $user]);

    $total = 0;
    $forms = [];

    if ($cart) {
        foreach ($cart->getCartItems() as $item) {
            $total += $item->getProduct()->getPrix() * $item->getQuantity();

            // 🔹 Création du formulaire pour chaque article
            $form = $this->createFormBuilder()
                ->setAction($this->generateUrl('cart_update', ['id' => $item->getId()]))
                ->setMethod('POST')
                ->add('quantity', IntegerType::class, [
                    'data' => $item->getQuantity(),
                    'attr' => ['min' => 1]
                ])
                ->add('save', SubmitType::class, ['label' => '🔄 Mettre à jour'])
                ->getForm();

            $forms[$item->getId()] = $form->createView();
        }
    }

    return $this->render('cart/index.html.twig', [
        'cart' => $cart,
        'total' => $total,
        'forms' => $forms, // 🔹 Envoi des formulaires au template
    ]);
}

    // ✅ Ajouter un produit au panier
    #[Route('/add/{id}', name: 'cart_add', methods: ['GET', 'POST'])]
    public function addToCart(Product $product, EntityManagerInterface $em, Security $security): Response
    {
        $user = $security->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $cart = $em->getRepository(Cart::class)->findOneBy(['user' => $user]);
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $em->persist($cart);
        }

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

    // ✅ Supprimer un produit du panier
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

    // ✅ Mise à jour de la quantité d'un produit dans le panier
    #[Route('/update/{id}', name: 'cart_update', methods: ['POST'])]
    public function updateCartItem(Request $request, CartItem $cartItem, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CartItemType::class, $cartItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Quantité mise à jour !');
        }

        return $this->redirectToRoute('cart_show');
    }

    // ✅ Valider la commande
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

        // 🔹 TODO: Ajouter la logique de création de commande

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
