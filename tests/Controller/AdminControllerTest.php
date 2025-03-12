<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminControllerTest extends WebTestCase
{
    private ?EntityManagerInterface $entityManager = null;
    private $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un client et récupérer l'EntityManager
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Vérifier si un admin existe, sinon le créer
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        if (!$user) {
            $user = new User();
            $user->setEmail('admin@example.com');
            $user->setPassword(
                static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, 'password')
            );
            $user->setRoles(['ROLE_ADMIN']);

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }

    public function testAdminDashboardRedirectIfNotAdmin(): void
    {
        $this->client->request('GET', '/admin/');

        // Vérifie si un utilisateur non admin est redirigé vers la page de login
        $this->assertResponseRedirects('/login');
    }

    public function testAdminDashboardAccessibleForAdmin(): void
    {
        // Récupérer l'admin existant
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);
        $this->assertNotNull($user, 'L\'utilisateur admin@example.com doit exister en base.');

        // Simuler l'authentification avec loginUser()
        $this->client->loginUser($user);

        $this->client->request('GET', '/admin/');

        // Vérifie que la réponse est bien un succès
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1'); // Vérifie qu'un titre est bien affiché
    }
}
