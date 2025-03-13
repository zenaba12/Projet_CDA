<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Product;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ProductControllerTest extends WebTestCase
{
    private ?EntityManagerInterface $entityManager = null;
    private $client;
    private ?Product $testProduct = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un client et récupérer l'EntityManager
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Vérifier si une catégorie existe, sinon en créer une
        $category = $this->entityManager->getRepository(Category::class)->findOneBy(['nom' => 'Catégorie Test']);

        if (!$category) {
            $category = new Category();
            $category->setNom('Catégorie Test');

            $this->entityManager->persist($category);
            $this->entityManager->flush();
        }

        // Vérifier si un produit test existe, sinon le créer
        $this->testProduct = $this->entityManager->getRepository(Product::class)->findOneBy(['nom' => 'Produit Test']);

        if (!$this->testProduct) {
            $this->testProduct = new Product();
            $this->testProduct->setNom('Produit Test');
            $this->testProduct->setDescription('Description du produit test.');
            $this->testProduct->setPrix(10.99);
            $this->testProduct->setStock(100);
            $this->testProduct->setImage('test.jpg');
            $this->testProduct->setCategory($category);

            $this->entityManager->persist($this->testProduct);
            $this->entityManager->flush();
        }
    }

    public function testProductIndexPageIsAccessible(): void
    {
        $this->client->loginUser($this->createAdminUser());

        $this->client->request('GET', '/product/');
        $this->assertResponseIsSuccessful();
    }

    private function createAdminUser(): User
    {
        $adminUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        if (!$adminUser) {
            $adminUser = new User();
            $adminUser->setEmail('admin@example.com');
            $adminUser->setRoles(['ROLE_ADMIN']);
            $adminUser->setPassword(password_hash('adminpass', PASSWORD_BCRYPT));
            $this->entityManager->persist($adminUser);
            $this->entityManager->flush();
        }

        return $adminUser;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
