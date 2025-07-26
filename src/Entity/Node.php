<?php

namespace App\Entity;

use App\Repository\NodeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NodeRepository::class)]
class Node
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nodeId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $groupId = null;

    /**
     * @var Collection<int, Label>
     */
    #[ORM\OneToMany(targetEntity: Label::class, mappedBy: 'node')]
    private Collection $labels;

    /**
     * @var Collection<int, Arrow>
     */
    #[ORM\OneToMany(targetEntity: Arrow::class, mappedBy: 'source')]
    private Collection $outArrows;

    /**
     * @var Collection<int, Arrow>
     */
    #[ORM\OneToMany(targetEntity: Arrow::class, mappedBy: 'target')]
    private Collection $inArrows;

    public function __construct()
    {
        $this->labels = new ArrayCollection();
        $this->outArrows = new ArrayCollection();
        $this->inArrows = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNodeId(): ?string
    {
        return $this->nodeId;
    }

    public function setNodeId(string $nodeId): static
    {
        $this->nodeId = $nodeId;

        return $this;
    }

    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    public function setGroupId(?string $groupId): static
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * @return Collection<int, Label>
     */
    public function getLabels(): Collection
    {
        return $this->labels;
    }

    public function addLabel(Label $label): static
    {
        if (!$this->labels->contains($label)) {
            $this->labels->add($label);
            $label->setNode($this);
        }

        return $this;
    }

    public function removeLabel(Label $label): static
    {
        if ($this->labels->removeElement($label)) {
            // set the owning side to null (unless already changed)
            if ($label->getNode() === $this) {
                $label->setNode(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Arrow>
     */
    public function getOutArrows(): Collection
    {
        return $this->outArrows;
    }

    public function addOutArrow(Arrow $outArrow): static
    {
        if (!$this->outArrows->contains($outArrow)) {
            $this->outArrows->add($outArrow);
            $outArrow->setSource($this);
        }

        return $this;
    }

    public function removeOutArrow(Arrow $outArrow): static
    {
        if ($this->outArrows->removeElement($outArrow)) {
            // set the owning side to null (unless already changed)
            if ($outArrow->getSource() === $this) {
                $outArrow->setSource(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Arrow>
     */
    public function getInArrows(): Collection
    {
        return $this->inArrows;
    }

    public function addInArrow(Arrow $inArrow): static
    {
        if (!$this->inArrows->contains($inArrow)) {
            $this->inArrows->add($inArrow);
            $inArrow->setTarget($this);
        }

        return $this;
    }

    public function removeInArrow(Arrow $inArrow): static
    {
        if ($this->inArrows->removeElement($inArrow)) {
            // set the owning side to null (unless already changed)
            if ($inArrow->getTarget() === $this) {
                $inArrow->setTarget(null);
            }
        }

        return $this;
    }
}
