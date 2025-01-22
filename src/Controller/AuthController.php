<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
    
        dump("Données reçues :", $data); // 🔍 Vérifie ce que Symfony reçoit
        dump("Requête brute :", $request->getContent()); // 🔍 Vérifie le JSON envoyé
    
        if (!isset($data['mail'], $data['mot_de_passe'])) {
            dump("❌ Clés manquantes !");
            return $this->json(['error' => 'Mail and password must be provided'], 400);
        }
    
        $user = $em->getRepository(User::class)->findOneBy(['mail' => $data['mail']]);
    
        if (!$user) {
            dump("❌ Utilisateur non trouvé !");
            return $this->json(['error' => 'Invalid credentials'], 401);
        } else {
            dump("✅ Utilisateur trouvé :", $user);
        }
    
        dump("Mot de passe entré :", $data['mot_de_passe']);
        dump("Mot de passe hashé en base :", $user->getPassword());
    
        if (!$passwordHasher->isPasswordValid($user, $data['mot_de_passe'])) {
            dump("❌ Le mot de passe ne correspond pas !");
            return $this->json(['error' => 'Invalid credentials'], 401);
        }
    
        dump("✅ Authentification réussie !");
        $token = $jwtManager->create($user);
    
        return $this->json(['token' => $token]);
    }
    
}
