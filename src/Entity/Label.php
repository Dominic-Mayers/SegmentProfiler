<?php

namespace App\Entity;

use App\Repository\LabelRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LabelRepository::class)]
class Label
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $keyLabel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $valueLabel = null;

    #[ORM\ManyToOne(inversedBy: 'labels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Node $node = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKeyLabel(): ?string
    {
        return $this->keyLabel;
    }

    public function setKeyLabel(string $keyLabel): static
    {
        $this->keyLabel = $keyLabel;

        return $this;
    }

    public function getValueLabel(): ?string
    {
        return $this->valueLabel;
    }

    public function setValueLabel(?string $valueLabel): static
    {
        $this->valueLabel = $valueLabel;

        return $this;
    }

    public function getNode(): ?Node
    {
        return $this->node;
    }

    public function setNode(?Node $node): static
    {
        $this->node = $node;

        return $this;
    }
}
