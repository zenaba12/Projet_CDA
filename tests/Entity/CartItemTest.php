<?php

namespace App\Tests\Entity;

use App\Entity\CartItem;
use App\Entity\Cart;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class CartItemTest extends TestCase
{
    public function testCartItemEntity(): void
    {
        $cartItem = new CartItem();
        $cart = new Cart();
        $product = new Product();

        $cartItem->setCart($cart);
        $cartItem->setProduct($product);
        $cartItem->setQuantity(2);

        $this->assertSame($cart, $cartItem->getCart());
        $this->assertSame($product, $cartItem->getProduct());
        $this->assertEquals(2, $cartItem->getQuantity());
    }
}
