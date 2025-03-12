<?php

namespace App\Tests\Entity;

use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\Order;
use PHPUnit\Framework\TestCase;

class OrderItemTest extends TestCase
{
    public function testOrderItemEntity(): void
    {
        $orderItem = new OrderItem();
        $order = new Order();
        $product = new Product();

        $orderItem->setOrder($order);
        $orderItem->setProduct($product);
        $orderItem->setQuantity(3);

        $this->assertSame($order, $orderItem->getOrder());
        $this->assertSame($product, $orderItem->getProduct());
        $this->assertEquals(3, $orderItem->getQuantity());
    }
}
