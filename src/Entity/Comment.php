<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\Product;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)] // ✅ Changer en "TEXT" pour du texte long
    private ?string $contenu = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)] // ✅ Correction : Utiliser DATETIME_MUTABLE pour les dates et heures
    private ?\DateTimeInterface $dateTime = null;

    // ✅ Relation avec User (auteur du commentaire)
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)] // ✅ Un commentaire doit être lié à un utilisateur
    private ?User $user = null;

    // ✅ Relation avec Product (le produit commenté)
    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)] // ✅ Un commentaire doit être lié à un produit
    private ?Product $product = null;

    public function getId(): ?int { return $this->id; }

    public function getContenu(): ?string { return $this->contenu; }
    public function setContenu(string $contenu): static { $this->contenu = $contenu; return $this; }

    public function getDateTime(): ?\DateTimeInterface { return $this->dateTime; }
    public function setDateTime(\DateTimeInterface $dateTime): static { $this->dateTime = $dateTime; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getProduct(): ?Product { return $this->product; }
    public function setProduct(?Product $product): static { $this->product = $product; return $this; }
}
