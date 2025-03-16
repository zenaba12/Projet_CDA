<?php

namespace App\Tests\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class AccessSecurityTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        // Création d'un client Symfony
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testAccesInterditPourUtilisateurNonAuthentifie()
    {
        // Essayer d'accéder à une page sécurisée sans être connecté
        $this->client->request('GET', '/admin/dashboard');

        // Vérifier que l'utilisateur est bien redirigé vers /login
        $this->assertResponseRedirects('/login');
    }

    public function testAccesInterditPourUtilisateurRoleUser()
    {
        // Récupérer un utilisateur ayant le rôle ROLE_USER
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'user@test.com']);

        // Simuler la connexion de cet utilisateur
        $this->client->loginUser($user);

        // Essayer d'accéder à l'administration
        $this->client->request('GET', '/admin/dashboard');

        // Vérifier que l'accès est refusé (403 Forbidden)
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAccesAutorisePourAdmin()
    {
        // Récupérer un administrateur ayant ROLE_ADMIN
        $userRepository = $this->entityManager->getRepository(User::class);
        $adminUser = $userRepository->findOneBy(['email' => 'admin@test.com']);

        // Simuler la connexion de l'administrateur
        $this->client->loginUser($adminUser);

        // Accéder au tableau de bord admin
        $this->client->request('GET', '/admin/dashboard');

        // Vérifier que l'accès est autorisé (200 OK)
        $this->assertResponseIsSuccessful();
    }
}
