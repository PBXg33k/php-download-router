<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\State\VersionProvider;

#[ApiResource(
    operations: array(
        new Get(
            uriTemplate: '/versions/{id}',
        ),
        new GetCollection(
            uriTemplate: '/versions',
        )
    ),
    provider: VersionProvider::class
)]
class Version
{
    public function __construct(
        public string $id,
        public string $version,
    )
    {
    }
}
