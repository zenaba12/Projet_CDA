<?php

namespace App\Tests\Entity;

use App\Entity\Cart;
use App\Entity\User;
use App\Entity\Product;
use App\Entity\CartItem;
use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    public function testCartEntity(): void
    {
        $cart = new Cart();
        $user = new User();
        $cart->setUser($user);

        $this->assertSame($user, $cart->getUser());
    }

    public function testCartItems(): void
    {
        $cart = new Cart();
        $cartItem = new CartItem();
        $cart->addCartItem($cartItem);

        $this->assertCount(1, $cart->getCartItems());
        $this->assertSame($cart, $cartItem->getCart());

        $cart->removeCartItem($cartItem);
        $this->assertCount(0, $cart->getCartItems());
    }

    public function testTotalPriceCalculation(): void
    {
        $cart = new Cart();

        $product1 = new Product();
        $product1->setPrix(10.00);

        $product2 = new Product();
        $product2->setPrix(20.00);

        $cartItem1 = new CartItem();
        $cartItem1->setProduct($product1);
        $cartItem1->setQuantity(2); // 2 x 10€

        $cartItem2 = new CartItem();
        $cartItem2->setProduct($product2);
        $cartItem2->setQuantity(1); // 1 x 20€

        $cart->addCartItem($cartItem1);
        $cart->addCartItem($cartItem2);

        $this->assertEquals(40.00, $cart->getTotalPrice()); // 20 + 20
    }
}
