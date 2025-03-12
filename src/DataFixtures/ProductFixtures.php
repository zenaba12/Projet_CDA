<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $product = new Product();
        $product->setNom('Produit Test'); // Correction ici (setName -> setNom)
        $product->setPrix(10.0); // Correction ici (setPrice -> setPrix)

        $manager->persist($product);
        $manager->flush();

        echo "Fixture ProductFixtures chargée ✅\n";
    }
}
