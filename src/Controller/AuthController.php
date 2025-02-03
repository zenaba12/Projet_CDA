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

        // 🔍 Vérification des données reçues
        dump("Données reçues :", $data);
        dump("Requête brute :", $request->getContent());

        // ✅ Vérification de la présence des champs
        if (!isset($data['mail'], $data['mot_de_passe'])) {
            dump("❌ Clés manquantes !");
            return $this->json(['error' => 'Mail and password must be provided'], 400);
        }

        // 🔍 Recherche de l'utilisateur en base de données
        $user = $em->getRepository(User::class)->findOneBy(['mail' => $data['mail']]);

        if (!$user) {
            dump("❌ Utilisateur non trouvé :", $data['mail']);
            return $this->json(['error' => 'Invalid credentials - utilisateur non trouvé'], 401);
        }

        dump("✅ Utilisateur trouvé :", $user->getMail());

        // 🔍 Vérification du mot de passe
        dump("Mot de passe entré :", $data['mot_de_passe']);
        dump("Mot de passe stocké en base :", $user->getPassword());

        if (!$passwordHasher->isPasswordValid($user, $data['mot_de_passe'])) {
            dump("❌ Le mot de passe est invalide !");
            return $this->json(['error' => 'Invalid credentials - mauvais mot de passe'], 401);
        }

        dump("✅ Authentification réussie !");
        $token = $jwtManager->create($user);

        return $this->json([
            'message' => 'Login successful',
            'token' => $token
        ]);
    }
}
