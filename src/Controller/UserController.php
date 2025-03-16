<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Form\UserType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Repository\OrderRepository;


#[Route('/users')]
class UserController extends AbstractController
{
    #[Route('/', name: 'users_list', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        // Vérifie si l'utilisateur est bien authentifié
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        // Vérifie si l'utilisateur est administrateur
        if (!$this->isGranted('ROLE_ADMIN')) {
            // Ajoute un message flash
            $this->addFlash('error', 'Cet espace est réservé aux administrateurs.');

            // Redirige vers la page d'accueil au lieu de 403
            return $this->redirectToRoute('app_home');
        }

        // Récupère la liste des utilisateurs si l'utilisateur est admin
        $users = $em->getRepository(className: User::class)->findAll();

        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }
    #[Route('/create', name: 'user_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Hachage du mot de passe
            if ($form->get('password')->getData()) {
                $user->setPassword($hasher->hashPassword($user, $form->get('password')->getData()));
            }

            $user->setRoles(['ROLE_USER']); // Par défaut, un utilisateur simple
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('users_list');
        }

        return $this->render('user/form.html.twig', [
            'form' => $form->createView(),
            'action' => 'Créer un utilisateur',
        ]);
    }

    #[Route('/edit/{id}', name: 'user_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(int $id, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hachage du nouveau mot de passe si modifié
            if ($form->get('password')->getData()) {
                $user->setPassword($hasher->hashPassword($user, $form->get('password')->getData()));
            }

            $em->flush();
            return $this->redirectToRoute('users_list');
        }

        return $this->render('user/form.html.twig', [
            'form' => $form->createView(),
            'action' => 'Modifier l’utilisateur',
        ]);
    }

    #[Route('/delete/{id}', name: 'user_delete', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Impossible de supprimer un administrateur.');
            return $this->redirectToRoute('users_list');
        }

        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('users_list');
    }
    #[Route('/mon-compte', name: 'user_account')]
    public function account(Request $request, EntityManagerInterface $entityManager, OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Formulaire de modification des infos
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Vos informations ont été mises à jour.');
        }

        // Récupération des commandes de l'utilisateur
        $orders = $orderRepository->findBy(['user' => $user], ['date' => 'DESC']);

        return $this->render('user/account.html.twig', [
            'form' => $form->createView(),
            'orders' => $orders,
        ]);
    }
}
