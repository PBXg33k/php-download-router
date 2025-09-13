<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\SupportedSiteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(
    repositoryClass: SupportedSiteRepository::class
)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection()
    ],
    mercure: true,
    order: [
        'name' => 'ASC'
    ]
)]
final class SupportedSite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private array $domains = [];

    #[ORM\Column]
    private ?bool $enabled = null;

    #[ORM\Column]
    private array $metadata = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDomains(): array
    {
        return $this->domains;
    }

    public function setDomains(array $domains): static
    {
        $this->domains = $domains;

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }
}
