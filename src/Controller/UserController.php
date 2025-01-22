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


final class UserController extends AbstractController
{
    #[Route('/users', name: 'users_list', methods: ['GET'])]
public function index(EntityManagerInterface $em): Response
{
    $users = $em->getRepository(User::class)->findAll();

    return $this->render('user/index.html.twig', [
        'users' => $users,
    ]);
}

#[Route('/users/create', name: 'user_create', methods: ['GET', 'POST'])]
public function create(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
{
    $user = new User();
    $form = $this->createForm(UserType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();

        // Hachage du mot de passe si un mot de passe est fourni
        if ($form->get('mot_de_passe')->getData()) {
            $user->setMotDePasse($hasher->hashPassword($user, $form->get('mot_de_passe')->getData()));
        }

        $user->setRoles(['ROLE_USER']);
        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('users_list');
    }

    return $this->render('user/form.html.twig', [
        'form' => $form->createView(),
        'action' => 'Créer un utilisateur',
    ]);
}

#[Route('/users/edit/{id}', name: 'user_edit', methods: ['GET', 'POST'])]
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
        if ($form->get('mot_de_passe')->getData()) {
            $user->setMotDePasse($hasher->hashPassword($user, $form->get('mot_de_passe')->getData()));
        }

        $em->flush();
        return $this->redirectToRoute('users_list');
    }

    return $this->render('user/form.html.twig', [
        'form' => $form->createView(),
        'action' => 'Modifier l’utilisateur',
    ]);
}

#[Route('/users/delete/{id}', name: 'user_delete', methods: ['POST'])]
public function delete(int $id, EntityManagerInterface $em): Response
{
    $user = $em->getRepository(User::class)->find($id);

    if (!$user) {
        throw $this->createNotFoundException('Utilisateur non trouvé.');
    }

    $em->remove($user);
    $em->flush();

    return $this->redirectToRoute('users_list');
}


}
