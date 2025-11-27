<?php

namespace App\Tests\Unit\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Version;
use App\Factory\DownloaderFactory;
use App\Service\Downloader\DownloaderInterface;
use App\State\VersionProvider;
use PHPUnit\Framework\TestCase;

class VersionProviderTest extends TestCase
{
    private VersionProvider $provider;
    private DownloaderFactory $downloaderFactory;

    protected function setUp(): void
    {
        $this->downloaderFactory = $this->createMock(DownloaderFactory::class);
        $this->provider = new VersionProvider($this->downloaderFactory);
    }

    public function testProvideCollectionReturnsAllVersionFields(): void
    {
        $downloader1 = $this->createMockDownloader('youtube-dl', '2024.06.01', '2024.07.01');
        $downloader2 = $this->createMockDownloader('gallery-dl', '1.25.0', '1.26.0');

        $this->downloaderFactory
            ->method('getEnabledDownloaders')
            ->willReturn([$downloader1, $downloader2]);

        $operation = $this->createCollectionOperation();

        $result = $this->provider->provide($operation);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // Check first downloader
        $this->assertInstanceOf(Version::class, $result[0]);
        $this->assertSame('youtube-dl', $result[0]->id);
        $this->assertSame('2024.06.01', $result[0]->version);
        $this->assertSame('2024.06.01', $result[0]->currentVersion);
        $this->assertSame('2024.07.01', $result[0]->latestVersion);

        // Check second downloader
        $this->assertInstanceOf(Version::class, $result[1]);
        $this->assertSame('gallery-dl', $result[1]->id);
        $this->assertSame('1.25.0', $result[1]->version);
        $this->assertSame('1.25.0', $result[1]->currentVersion);
        $this->assertSame('1.26.0', $result[1]->latestVersion);
    }

    public function testProvideCollectionVersionMatchesCurrentVersion(): void
    {
        $downloader = $this->createMockDownloader('test-dl', '2.0.0', '3.0.0');

        $this->downloaderFactory
            ->method('getEnabledDownloaders')
            ->willReturn([$downloader]);

        $operation = $this->createCollectionOperation();

        $result = $this->provider->provide($operation);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame($result[0]->version, $result[0]->currentVersion);
    }

    public function testProvideSingleItemReturnsAllVersionFields(): void
    {
        $downloader = $this->createMockDownloader('youtube-dl', '2024.06.01', '2024.07.01');

        $this->downloaderFactory
            ->method('getDownloaderByIdentifier')
            ->with('youtube-dl')
            ->willReturn($downloader);

        $operation = $this->createSingleItemOperation();

        $result = $this->provider->provide($operation, ['id' => 'youtube-dl']);

        $this->assertInstanceOf(Version::class, $result);
        $this->assertSame('youtube-dl', $result->id);
        $this->assertSame('2024.06.01', $result->version);
        $this->assertSame('2024.06.01', $result->currentVersion);
        $this->assertSame('2024.07.01', $result->latestVersion);
    }

    public function testProvideSingleItemVersionMatchesCurrentVersion(): void
    {
        $downloader = $this->createMockDownloader('test-dl', '1.0.0', '2.0.0');

        $this->downloaderFactory
            ->method('getDownloaderByIdentifier')
            ->with('test-dl')
            ->willReturn($downloader);

        $operation = $this->createSingleItemOperation();

        $result = $this->provider->provide($operation, ['id' => 'test-dl']);

        $this->assertInstanceOf(Version::class, $result);
        $this->assertSame($result->version, $result->currentVersion);
    }

    public function testProvideSingleItemReturnsNullForNonExistentDownloader(): void
    {
        $this->downloaderFactory
            ->method('getDownloaderByIdentifier')
            ->with('non-existent')
            ->willReturn(null);

        $operation = $this->createSingleItemOperation();

        $result = $this->provider->provide($operation, ['id' => 'non-existent']);

        $this->assertNull($result);
    }

    public function testProvideSingleItemReturnsNullWhenNoIdProvided(): void
    {
        $operation = $this->createSingleItemOperation();

        $result = $this->provider->provide($operation, []);

        $this->assertNull($result);
    }

    public function testProvideCollectionReturnsEmptyArrayWhenNoDownloaders(): void
    {
        $this->downloaderFactory
            ->method('getEnabledDownloaders')
            ->willReturn([]);

        $operation = $this->createCollectionOperation();

        $result = $this->provider->provide($operation);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testProvideCollectionWithDownloaderHavingMatchingVersions(): void
    {
        $downloader = $this->createMockDownloader('up-to-date', '1.0.0', '1.0.0');

        $this->downloaderFactory
            ->method('getEnabledDownloaders')
            ->willReturn([$downloader]);

        $operation = $this->createCollectionOperation();

        $result = $this->provider->provide($operation);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame('1.0.0', $result[0]->currentVersion);
        $this->assertSame('1.0.0', $result[0]->latestVersion);
        $this->assertSame($result[0]->version, $result[0]->currentVersion);
    }

    /**
     * Create an anonymous class operation that implements CollectionOperationInterface.
     * This is needed because the VersionProvider checks for CollectionOperationInterface
     * to determine if it should return a collection or a single item.
     */
    private function createCollectionOperation(): Operation
    {
        return new class extends Operation implements CollectionOperationInterface {
        };
    }

    /**
     * Create a mock operation that does NOT implement CollectionOperationInterface.
     * This simulates a single item Get operation.
     */
    private function createSingleItemOperation(): Operation
    {
        return $this->createMock(Operation::class);
    }

    private function createMockDownloader(string $identifier, string $currentVersion, string $latestVersion): DownloaderInterface
    {
        $mock = $this->createMock(DownloaderInterface::class);
        $mock->method('getIdentifier')->willReturn($identifier);
        $mock->method('getCurrentVersion')->willReturn($currentVersion);
        $mock->method('getLatestVersion')->willReturn($latestVersion);

        return $mock;
    }
}
