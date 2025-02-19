<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(Security $security): Response
    {
       // Vérifie si l'utilisateur est administrateur
    if (!$this->isGranted('ROLE_ADMIN')) {
        // Ajoute un message flash
        $this->addFlash('error', 'Cet espace est réservé aux administrateurs.');

        // Redirige vers la page d'accueil au lieu de 403
        return $this->redirectToRoute('app_home');
    }

        return $this->render('admin/index.html.twig');
    }
}
