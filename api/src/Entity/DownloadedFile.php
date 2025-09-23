<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Repository\DownloadedFileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\QueryBuilder;

#[ApiResource(
    uriTemplate: '/download_jobs/{downloadJobUuid}/files.{_format}',
    operations: [new GetCollection()],
    uriVariables: [
        'downloadJobUuid' => new Link(
            toProperty: 'downloadJob',
            fromClass: DownloadJob::class,
            identifiers: ['uuid'],
        )
    ],
    stateOptions: new Options(
        handleLinks: [self::class, 'handleLinks'],
    )
)]
#[ORM\Entity(repositoryClass: DownloadedFileRepository::class)]
class DownloadedFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, DownloadJob>
     */
    #[ORM\ManyToMany(targetEntity: DownloadJob::class, inversedBy: 'files')]
    private Collection $downloadJob;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $path = null;

    #[ORM\Column]
    private array $metadata = [];

    #[ORM\Column]
    private ?bool $visible = null;

    public function __construct()
    {
        $this->downloadJob = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, DownloadJob>
     */
    public function getDownloadJob(): Collection
    {
        return $this->downloadJob;
    }

    public function addDownloadJob(DownloadJob $downloadJob): static
    {
        if (!$this->downloadJob->contains($downloadJob)) {
            $this->downloadJob->add($downloadJob);
        }

        return $this;
    }

    public function removeDownloadJob(DownloadJob $downloadJob): static
    {
        $this->downloadJob->removeElement($downloadJob);

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

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

    public function isVisible(): ?bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }



    public static function handleLinks(QueryBuilder $queryBuilder, array $uriVariables, QueryNameGeneratorInterface $queryNameGenerator): void
    {
        $queryBuilder
            ->join($queryBuilder->getRootAliases()[0].'.downloadJob', 'download_job')
            ->andWhere('download_job.uuid = :downloadJob')
            ->setParameter('downloadJob', $uriVariables['downloadJobUuid']);
    }
}
