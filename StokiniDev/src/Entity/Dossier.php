<?php

namespace App\Entity;

use App\Entity\User;
use App\Repository\DossierRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: DossierRepository::class)]
class Dossier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createAt = null;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'dossiersPartages')]
    #[ORM\JoinTable(name: 'dossier_user')]
    private Collection $users;



    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }
        return $this;
    }
    public function removeUser(User $user): self
    {
        $this->users->removeElement($user);
        return $this;
    }


    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: Fichier::class, cascade: ['remove'])]
    private Collection $fichiers;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->fichiers = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getFichiers(): Collection
    {
        return $this->fichiers;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getCreateAt(): ?\DateTimeImmutable
    {
        return $this->createAt;
    }

    public function setCreateAt(?\DateTimeImmutable $createAt): static
    {
        $this->createAt = $createAt;

        return $this;
    }
}
