<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class UserControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $csrfTokenManager;
    private $session;

    protected function setUp(): void
    {
        parent::setUp();

        // 🔥 Assurer que le kernel précédent est bien arrêté avant de créer un nouveau client
        self::ensureKernelShutdown();

        // 🔥 Créer un client et récupérer les services nécessaires
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->csrfTokenManager = $container->get(CsrfTokenManagerInterface::class);

        // 🔥 Démarrer une session Symfony manuellement
        $this->session = new Session(new MockArraySessionStorage());
        $this->session->start();

        // 🔥 Ajouter un cookie de session au client pour éviter l'erreur de session
        $this->client->getCookieJar()->set(new Cookie($this->session->getName(), $this->session->getId()));

        // Vérifier si un administrateur existe, sinon en créer un
        $adminUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        if (!$adminUser) {
            $adminUser = new User();
            $adminUser->setEmail('admin@example.com');
            $adminUser->setNom('Admin Test');
            $adminUser->setPrenom('Admin');
            $adminUser->setRoles(['ROLE_ADMIN']);
            $adminUser->setPassword(password_hash('password', PASSWORD_BCRYPT));
            $this->entityManager->persist($adminUser);
            $this->entityManager->flush();
        }

        // 🔥 Se connecter en tant qu'admin
        $this->client->loginUser($adminUser);
    }

    public function testUsersListRedirectIfNotLoggedIn(): void
    {
        // 🔥 Assurer que le kernel précédent est bien arrêté avant de créer un nouveau client
        self::ensureKernelShutdown();

        // Créer un client sans connexion
        $client = static::createClient();
        $client->request('GET', '/users/');

        // Vérifier la redirection vers /login
        $this->assertResponseRedirects('/login');
    }

    public function testUsersListAccessibleForAdmin(): void
    {
        // L'admin est déjà connecté via setUp()
        $this->client->request('GET', '/users/');

        // Vérifier que la réponse est un succès (HTTP 200)
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Liste des utilisateurs');
    }

    public function testDeleteUser(): void
    {
        //  Vérifier et démarrer la session si elle n'est pas active
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        //  Ajouter un cookie de session au client
        $this->client->getCookieJar()->set(new Cookie($this->session->getName(), $this->session->getId()));

        //  Créer un utilisateur test à supprimer
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);

        if (!$user) {
            $user = new User();
            $user->setEmail('test@example.com');
            $user->setNom('Test User');
            $user->setPrenom('Utilisateur');
            $user->setRoles(['ROLE_USER']);
            $user->setPassword(password_hash('userpass', PASSWORD_BCRYPT));
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        //  Récupérer un token CSRF valide
        $csrfToken = $this->csrfTokenManager->getToken('delete' . $user->getId())->getValue();

        //  Vérifier que le token CSRF est bien généré
        $this->assertNotEmpty($csrfToken, "Le token CSRF est vide, vérifie que la session est bien active.");

        //  Envoyer la requête de suppression avec un vrai token CSRF
        $this->client->request('POST', '/users/delete/' . $user->getId(), [
            '_token' => $csrfToken
        ]);

        //  Vérifier la redirection après suppression
        $this->assertResponseRedirects('/users/');
    }
}
