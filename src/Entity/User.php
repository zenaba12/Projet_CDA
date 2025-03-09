<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\UserRepository;

#[ORM\Entity(repositoryClass: UserRepository::class)]

#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cet email.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['comment:read'])] // Exposé uniquement si nécessaire dans Comment
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['comment:read'])] // Nom affiché uniquement dans les commentaires
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    #[Groups(['comment:read'])] // Prénom affiché uniquement dans les commentaires
    private ?string $prenom = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 100, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le mot de passe est obligatoire.")]
    #[Assert\Length(
        min: 12,
        minMessage: "Votre mot de passe doit contenir au moins 12 caractères."
    )]
    private ?string $password = null;


    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'user', cascade: ['remove'], orphanRemoval: true)]
    #[Groups(['user:read'])]
    private Collection $comments;

    #[ORM\OneToMany(targetEntity: Cart::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['user:read'])]
    private Collection $carts;

    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'user', cascade: ['remove'], orphanRemoval: true)]
    #[Groups(['user:read'])]
    private Collection $orders;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->carts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }
    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }
    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getRoles(): array
    {
        return array_unique($this->roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }
    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void {}

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getCarts(): Collection
    {
        return $this->carts;
    }
    public function addCart(Cart $cart): static
    {
        if (!$this->carts->contains($cart)) {
            $this->carts->add($cart);
            $cart->setUser($this);
        }
        return $this;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }
    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setUser($this);
        }
        return $this;
    }

    public function getOrders(): Collection
    {
        return $this->orders;
    }
    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setUser($this);
        }
        return $this;
    }
}
