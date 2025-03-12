<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testUsersListRedirectIfNotLoggedIn(): void
    {
        $client = static::createClient();
        $client->request('GET', '/users/');

        // Vérifie si l'utilisateur est redirigé vers la connexion
        $this->assertResponseRedirects('/login');
    }

    public function testUsersListAccessibleForAdmin(): void
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'admin@example.com',
            'PHP_AUTH_PW'   => 'password'
        ]);
        $client->request('GET', '/users/');

        // Vérifie si la liste des utilisateurs est affichée
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Liste des utilisateurs');
    }

    public function testDeleteUser(): void
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'admin@example.com',
            'PHP_AUTH_PW'   => 'password'
        ]);

        $client->request('POST', '/users/delete/2', [
            '_token' => 'valid_csrf_token'
        ]);

        // Vérifie si l'utilisateur est bien supprimé
        $this->assertResponseRedirects('/users/');
    }
}
