<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\OidcSubjectIdentifierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: OidcSubjectIdentifierRepository::class)]
#[UniqueEntity(fields: ['subject'], message: 'This OIDC subject identifier is already in use.')]
#[ApiResource(
    operations: [
        new Get(
            security: "is_granted('ROLE_ADMIN')"
        ),
        new GetCollection(
            security: "is_granted('ROLE_ADMIN')"
        )
    ]
)]
class OidcSubjectIdentifier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $subject = null;

    /**
     * @var Collection<int, DownloadJob>
     */
    #[ORM\OneToMany(targetEntity: DownloadJob::class, mappedBy: 'owner')]
    private Collection $downloadJobs;

    public function __construct()
    {
        $this->downloadJobs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return Collection<int, DownloadJob>
     */
    public function getDownloadJobs(): Collection
    {
        return $this->downloadJobs;
    }

    public function addDownloadJob(DownloadJob $downloadJob): static
    {
        if (!$this->downloadJobs->contains($downloadJob)) {
            $this->downloadJobs->add($downloadJob);
            $downloadJob->setOwner($this);
        }

        return $this;
    }

    public function removeDownloadJob(DownloadJob $downloadJob): static
    {
        if ($this->downloadJobs->removeElement($downloadJob)) {
            // set the owning side to null (unless already changed)
            if ($downloadJob->getOwner() === $this) {
                $downloadJob->setOwner(null);
            }
        }

        return $this;
    }
}
