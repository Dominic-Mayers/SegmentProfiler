<?php

namespace App\Entity;

use App\Repository\ArrowRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArrowRepository::class)]
class Arrow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $calls = null;

    #[ORM\ManyToOne(inversedBy: 'outArrows')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Node $source = null;

    #[ORM\ManyToOne(inversedBy: 'inArrows')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Node $target = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCalls(): ?int
    {
        return $this->calls;
    }

    public function setCalls(int $calls): static
    {
        $this->calls = $calls;

        return $this;
    }

    public function getSource(): ?Node
    {
        return $this->source;
    }

    public function setSource(?Node $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getTarget(): ?Node
    {
        return $this->target;
    }

    public function setTarget(?Node $target): static
    {
        $this->target = $target;

        return $this;
    }
}
