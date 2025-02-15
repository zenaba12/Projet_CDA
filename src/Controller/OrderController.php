<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\SecurityBundle\Security;
use DateTime;

#[Route('/order')]
class OrderController extends AbstractController
{
    // 🔹 Lister les commandes de l'utilisateur (UNIQUEMENT SES PROPRES COMMANDES)
    #[Route('/', name: 'order_list')]
    public function listOrders(OrderRepository $orderRepository, Security $security): Response
    {
        $user = $security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $orders = $orderRepository->findBy(['user' => $user]); // ✅ L'utilisateur ne voit que ses commandes

        return $this->render('order/user_orders.html.twig', [
            'orders' => $orders,
        ]);
    }

    // 🔹 Voir une commande spécifique (UNIQUEMENT SI ELLE LUI APPARTIENT)
    #[Route('/{id}', name: 'order_view')]
    public function viewOrder(Order $order, Security $security): Response
    {
        $user = $security->getUser();
        if (!$user || $order->getUser() !== $user) {
            $this->addFlash('error', "Vous n'avez pas accès à cette commande.");
            return $this->redirectToRoute('order_list');
        }

        return $this->render('order/user_order_view.html.twig', [
            'order' => $order,
        ]);
    }

    // 🔹 Créer une commande depuis le panier
    #[Route('/create', name: 'order_create')]
    public function createOrder(EntityManagerInterface $em, Security $security): Response
    {
        $user = $security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $cart = $em->getRepository(Cart::class)->findOneBy(['user' => $user]);
        if (!$cart || $cart->getCartItems()->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('cart_show');
        }

        $order = new Order();
        $order->setUser($user);
        $order->setDate(new DateTime());
        $order->setStatus('pending');

        foreach ($cart->getCartItems() as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setProduct($cartItem->getProduct());
            $orderItem->setQuantity($cartItem->getQuantity());
            $em->persist($orderItem);
            $order->addOrderItem($orderItem);
        }

        $em->persist($order);
        $cart->clearCart();
        $em->flush();

        $this->addFlash('success', 'Commande créée avec succès.');
        return $this->redirectToRoute('order_list');
    }

    // 🔹 Lister toutes les commandes (ADMIN UNIQUEMENT)
    #[Route('/admin/orders', name: 'admin_order_list')]
    public function listAllOrders(OrderRepository $orderRepository, Security $security): Response
    {
        if (!$security->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', "Accès refusé.");
            return $this->redirectToRoute('order_list');
        }

        $orders = $orderRepository->findAll();

        return $this->render('order/admin_orders.html.twig', [
            'orders' => $orders,
        ]);
    }

    // 🔹 Modifier le statut d'une commande (ADMIN UNIQUEMENT)
    #[Route('/admin/order/update/{id}', name: 'admin_order_update', methods: ['POST'])]
    public function updateOrderStatus(Order $order, Request $request, EntityManagerInterface $em, Security $security): Response
    {
        if (!$security->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', "Accès refusé.");
            return $this->redirectToRoute('order_list');
        }

        $status = $request->request->get('status');
        if (!in_array($status, ['pending', 'shipped', 'delivered', 'cancelled'])) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirectToRoute('admin_order_list');
        }

        $order->setStatus($status);
        $em->flush();

        $this->addFlash('success', 'Statut mis à jour.');
        return $this->redirectToRoute('admin_order_list');
    }
}
