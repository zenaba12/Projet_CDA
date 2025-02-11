<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $numéro_suivi = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?user $relation = null;

    /**
     * @var Collection<int, product>
     */
    #[ORM\ManyToMany(targetEntity: product::class, inversedBy: 'commandes')]
    private Collection $product;

    public function __construct()
    {
        $this->product = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNuméroSuivi(): ?int
    {
        return $this->numéro_suivi;
    }

    public function setNuméroSuivi(int $numéro_suivi): static
    {
        $this->numéro_suivi = $numéro_suivi;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getRelation(): ?user
    {
        return $this->relation;
    }

    public function setRelation(?user $relation): static
    {
        $this->relation = $relation;

        return $this;
    }

    /**
     * @return Collection<int, product>
     */
    public function getProduct(): Collection
    {
        return $this->product;
    }

    public function addProduct(product $product): static
    {
        if (!$this->product->contains($product)) {
            $this->product->add($product);
        }

        return $this;
    }

    public function removeProduct(product $product): static
    {
        $this->product->removeElement($product);

        return $this;
    }
}
