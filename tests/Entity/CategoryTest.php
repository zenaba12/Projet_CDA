<?php

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    public function testCategoryEntity(): void
    {
        $category = new Category();
        $category->setNom("Huiles naturelles");

        $this->assertEquals("Huiles naturelles", $category->getNom());
    }

    public function testAddAndRemoveProduct(): void
    {
        $category = new Category();
        $product = new Product();
        $product->setNom("Huile de Moringa");

        $category->addProduct($product);
        $this->assertTrue($category->getProducts()->contains($product));

        $category->removeProduct($product);
        $this->assertFalse($category->getProducts()->contains($product));
    }
}
