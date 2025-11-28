<?php

namespace App\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Version;
use App\Factory\DownloaderFactory;
use Psr\Log\LoggerInterface;

class VersionProvider implements ProviderInterface
{

    public function __construct(
        private readonly DownloaderFactory $downloaderFactory,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $downloaders = [];
            foreach ($this->downloaderFactory->getEnabledDownloaders() as $downloader) {
                try {
                    $downloaders[] = new Version(
                        id: $downloader->getIdentifier(),
                        version: $downloader->getCurrentVersion(),
                        currentVersion: $downloader->getCurrentVersion(),
                        latestVersion: $downloader->getLatestVersion(),
                    );
                } catch (\Throwable $e) {
                    $this->logger->error('Failed to get version info', [
                        'downloader' => $downloader->getIdentifier(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $downloaders;
        }

        if (isset($uriVariables['id'])) {
            $downloader = $this->downloaderFactory->getDownloaderByIdentifier($uriVariables['id']);
            if ($downloader) {
                try {
                    return new Version(
                        id: $downloader->getIdentifier(),
                        version: $downloader->getCurrentVersion(),
                        currentVersion: $downloader->getCurrentVersion(),
                        latestVersion: $downloader->getLatestVersion(),
                    );
                } catch (\Throwable $e) {
                    $this->logger->error('Failed to get version info', [
                        'downloader' => $downloader->getIdentifier(),
                        'error' => $e->getMessage(),
                    ]);
                    return null;
                }
            }
        }

        return null;
    }
}
