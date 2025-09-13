<?php

namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\DownloadJobDTO;
use App\Entity\DownloadJob;
use App\Enum\DownloadStateEnum;
use App\Factory\DownloaderFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use ApiPlatform\Symfony\Messenger\Processor as MessengerProcessor;


class DownloadJobQueuedProcessor implements ProcessorInterface
{
    public function __construct(
        /**
         * @var PersistProcessor $persistProcessor
         */
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private MessengerProcessor $messengerProcessor,
        private DownloaderFactory $downloaderFactory
    )
    {
    }

    /**
     * @param DownloadJobDTO $data
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return void
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        // Validate downloader
        if (isset($data->downloader) && !$this->downloaderFactory->isValidDownloader($data->downloader)) {
            throw new BadRequestException(
                'Invalid downloader specified. Possible values: ' . implode(', ', array_map(fn($d) => $d->getIdentifier(), $this->downloaderFactory->getEnabledDownloaders()))
            );
        }


        $downloadJob = new DownloadJob()
            ->setUri($data->uri)
            ->setUserAgent($data->userAgent ?? null)
            ->setCookies($data->cookies ?? null)
            ->setState(DownloadStateEnum::PENDING)
            ->setDownloader($data->downloader ?? null);

        dump($data, $downloadJob);

        // Try converting the uri to a valid URI
        // If it fails, throw a 400 error
        try {
            $uri = new \GuzzleHttp\Psr7\Uri($downloadJob->getUri());
        } catch (\Exception $e) {
            throw new BadRequestException('Invalid URI');
        }

        // Persist the job as a queued job
        $this->persistProcessor->process($downloadJob,$operation,$uriVariables,$context);

        // Dispatch the job to the messenger queue
        return $this->messengerProcessor->process($downloadJob,$operation,$uriVariables,$context);
    }
}
