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
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['email'], $data['password'])) {
                return $this->json(['error' => 'Mail and password must be provided'], 400);
            }

            $user = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);

            if (!$user) {
                return $this->json(['error' => 'Invalid credentials - utilisateur non trouvé'], 401);
            }

            if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
                return $this->json(['error' => 'Invalid credentials - mauvais mot de passe'], 401);
            }

            $token = $jwtManager->create($user);

            return $this->json([
                'message' => 'Login successful',
                'token' => $token
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Une erreur s\'est produite: ' . $e->getMessage()], 500);
        }
    }
}
