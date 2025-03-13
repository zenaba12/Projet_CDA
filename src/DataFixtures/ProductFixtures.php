<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Création d'une catégorie associée au produit
        $category = new Category();
        $category->setNom('Catégorie Test');
        $manager->persist($category);

        // Création du produit
        $product = new Product();
        $product->setNom('Produit Test');
        $product->setPrix(10.0);
        $product->setDescription('Description du produit test.');
        $product->setStock(50);
        $product->setImage('test.jpg');
        $product->setCategory($category); // Association à la catégorie créée

        $manager->persist($product);

        $manager->flush();

        echo "✅ Fixture ProductFixtures chargée avec succès.\n";
    }
}
