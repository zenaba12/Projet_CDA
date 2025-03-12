<?php

namespace App\Tests\Entity;

use App\Entity\Comment;
use App\Entity\User;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class CommentTest extends TestCase
{
    public function testCommentEntity(): void
    {
        $comment = new Comment();
        $comment->setContenu("Super produit !");
        $date = new \DateTime();
        $comment->setCreatedAt($date);

        $this->assertEquals("Super produit !", $comment->getContenu());
        $this->assertSame($date, $comment->getCreatedAt());
    }

    public function testCommentUser(): void
    {
        $comment = new Comment();
        $user = new User();
        $comment->setUser($user);

        $this->assertSame($user, $comment->getUser());
    }

    public function testCommentProduct(): void
    {
        $comment = new Comment();
        $product = new Product();
        $comment->setProduct($product);

        $this->assertSame($product, $comment->getProduct());
    }
}
