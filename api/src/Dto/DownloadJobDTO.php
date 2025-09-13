<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator as CustomAssert;
use Psr\Http\Message\UriInterface;

final class DownloadJobDTO
{
    #[Assert\Type('string')]
    #[Assert\Url]
    #[Assert\NotNull]
    public string $uri;

    #[Assert\Type('string')]
    public string $userAgent;

    #[Assert\Type('array')]
    public array $cookies;

    #[Assert\Type('string')]
    #[CustomAssert\SelectDownloader]
    public string $downloader;
}
