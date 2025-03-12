<?php

namespace App\Tests\Entity;

use App\Entity\Product;
use App\Entity\Category;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testProductEntity(): void
    {
        $product = new Product();
        $product->setNom("Huile de Moringa");
        $product->setDescription("Huile bio et naturelle.");
        $product->setPrix(29.99);
        $product->setStock(100);
        $product->setImage("moringa.jpg");

        $this->assertEquals("Huile de Moringa", $product->getNom());
        $this->assertEquals("Huile bio et naturelle.", $product->getDescription());
        $this->assertEquals(29.99, $product->getPrix());
        $this->assertEquals(100, $product->getStock());
        $this->assertEquals("moringa.jpg", $product->getImage());
    }

    public function testProductCategory(): void
    {
        $product = new Product();
        $category = new Category();
        $product->setCategory($category);

        $this->assertSame($category, $product->getCategory());
    }
}
