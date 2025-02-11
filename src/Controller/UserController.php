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

#[Route('/users')]
class UserController extends AbstractController
{
    #[Route('/', name: 'users_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(EntityManagerInterface $em): Response
    {
        // Vérifie si l'utilisateur a le rôle ADMIN
    if (!$this->isGranted('ROLE_ADMIN')) {
        return $this->redirectToRoute('app_login'); // ✅ Redirige vers login si pas admin
    }
        $users = $em->getRepository(User::class)->findAll();

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

    #[Route('/delete/{id}', name: 'user_delete', methods: ['POST'])]
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
}
