<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Product;
use App\Entity\Category;
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
            $this->testProduct->setCategory($category); // Associer la catégorie

            $this->entityManager->persist($this->testProduct);
            $this->entityManager->flush();
        }
    }

    public function testProductIndexPageIsAccessible(): void
    {
        $this->client->request('GET', '/product/');

        // Vérifie que la page s'affiche correctement
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1'); // Vérifie qu'un titre est affiché
    }

    public function testShowProduct(): void
    {
        // Vérifie que le produit test existe en base
        $this->assertNotNull($this->testProduct, 'Le produit "Produit Test" doit exister.');

        $this->client->request('GET', '/product/' . $this->testProduct->getId());

        // Vérifie que la page du produit est bien accessible
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1'); // Vérifie qu'un titre est affiché
    }

    public function testCreateProductRequiresAdmin(): void
    {
        $this->client->request('GET', '/product/new');

        // Vérifie si un utilisateur non admin est redirigé vers la page de login
        $this->assertResponseRedirects('/login');
    }
}
