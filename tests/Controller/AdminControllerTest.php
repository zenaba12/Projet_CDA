<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        // Nettoyer la base avant chaque test
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();

        // Créer un admin
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setNom('Admin Test');
        $admin->setPrenom('Super Admin');

        $this->entityManager->persist($admin);
        $this->entityManager->flush();
    }

    public function testAdminAccessDeniedForUser(): void
    {
        // Créer un utilisateur normal
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'userpassword'));
        $user->setRoles(['ROLE_USER']);
        $user->setNom('Utilisateur Test');
        $user->setPrenom('Test');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Connecter l'utilisateur normal
        $this->client->loginUser($user);
        $this->client->request('GET', '/admin/');

        // Vérifier la redirection vers l'accueil
        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('/'); // Redirection vers la page d'accueil

    }

    public function testAdminAccessGrantedForAdmin(): void
    {
        // Récupérer l'admin en base
        $admin = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        // Connecter l'admin
        $this->client->loginUser($admin);
        $this->client->request('GET', '/admin/');

        // Vérifier que la page admin s'affiche
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', '👨‍💼 Tableau de bord Administrateur');
    }
}
