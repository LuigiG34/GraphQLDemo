<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ApiResource]
class Author
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(length: 120)]
    private string $name = '';

    /** @var Collection<int,Book> */
    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Book::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $books;

    public function __construct() { $this->books = new ArrayCollection(); }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    /** @return Collection<int,Book> */
    public function getBooks(): Collection { return $this->books; }

    public function addBook(Book $book): self
    {
        if (!$this->books->contains($book)) {
            $this->books->add($book);
            $book->setAuthor($this);
        }
        return $this;
    }

    public function removeBook(Book $book): self
    {
        if ($this->books->removeElement($book) && $book->getAuthor() === $this) {
            $book->setAuthor(null);
        }
        return $this;
    }
}
