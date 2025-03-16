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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

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

                // 🔹 Création du formulaire pour modifier la quantité
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
            'forms' => $forms,
        ]);
    }

    // ✅ Ajouter un produit au panier
    #[Route('/add/{id}', name: 'cart_add', methods: ['GET', 'POST'])]
    public function addToCart(Product $product, EntityManagerInterface $em, Security $security): Response
    {
        $user = $security->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        // 🔹 Vérifier si le panier de l'utilisateur existe déjà
        $cart = $em->getRepository(Cart::class)->findOneBy(['user' => $user]);

        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $em->persist($cart);
        }

        // 🔹 Vérifier si le produit est déjà dans le panier
        $item = $em->getRepository(CartItem::class)->findOneBy([
            'cart' => $cart,
            'product' => $product
        ]);

        if ($item) {
            // 🔹 Si le produit est déjà dans le panier, augmenter la quantité
            $item->setQuantity($item->getQuantity() + 1);
        } else {
            // 🔹 Sinon, créer un nouvel item de panier
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
    public function removeFromCart(int $id, EntityManagerInterface $em, Security $security): Response
    {
        $user = $security->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $cartItem = $em->getRepository(CartItem::class)->find($id);

        if (!$cartItem) {
            $this->addFlash('error', "Produit introuvable dans le panier.");
            return $this->redirectToRoute('cart_show');
        }

        // 🔹 Vérifier si le produit appartient bien au panier de l'utilisateur
        $cart = $em->getRepository(Cart::class)->findOneBy(['user' => $user]);

        if (!$cart || !$cart->getCartItems()->contains($cartItem)) {
            $this->addFlash('error', "Vous ne pouvez pas supprimer un produit qui n'est pas dans votre panier.");
            return $this->redirectToRoute('cart_show');
        }

        $em->remove($cartItem);
        $em->flush();

        $this->addFlash('success', "Produit retiré du panier !");
        return $this->redirectToRoute('cart_show');
    }

    // ✅ Mise à jour de la quantité d'un produit dans le panier


    #[Route('/update/{id}', name: 'cart_update', methods: ['POST'])]
    public function updateCartItem(Request $request, int $id, EntityManagerInterface $em, Security $security): Response
    {
        $cartItem = $em->getRepository(CartItem::class)->find($id);

        if (!$cartItem) {
            return new Response("Produit introuvable", Response::HTTP_NOT_FOUND);
        }

        $user = $security->getUser();
        if (!$user) {
            return new Response("Utilisateur non connecté", Response::HTTP_UNAUTHORIZED);
        }

        $cart = $em->getRepository(Cart::class)->findOneBy(['user' => $user]);

        if (!$cart || !$cart->getCartItems()->contains($cartItem)) {
            return new Response("Ce produit ne fait pas partie de votre panier", Response::HTTP_FORBIDDEN);
        }

        $newQuantity = (int) $request->request->get('quantity');

        if ($newQuantity < 1) {
            return new Response("La quantité ne peut pas être inférieure à 1", Response::HTTP_BAD_REQUEST);
        }

        $cartItem->setQuantity($newQuantity);
        $em->flush();

        return new Response("Quantité mise à jour", Response::HTTP_OK);
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
