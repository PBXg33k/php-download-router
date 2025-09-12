<?php

namespace App\Service\Downloader;

use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GalleryDlWebDownloader
{
    public function __construct(
        protected(set) LoggerInterface $logger,
        protected(set) HttpClientInterface $httpClient,
        protected(set) string $hostUrl = 'http://gallery-dl:9080',
    )
    {
    }

    public function sendDownloadUrlToServer(UriInterface $uri)
    {
        $this->logger->info('Sending URL to gallery-dl web server', ['url' => (string)$uri]);

        // curl -X POST --data-urlencode "url={{url}}" http://{{host}}:{{port}}/gallery-dl/q

        $response = $this->httpClient->request('POST', $this->hostUrl . '/gallery-dl/q', [
            'body' => [
                'url' => (string)$uri,
            ],
        ]);


        if (200 !== $response->getStatusCode()) {
            $this->logger->error('Failed to send URL to gallery-dl web server', [
                'url' => (string)$uri,
                'status_code' => $response->getStatusCode(),
                'response' => $response->getContent(false),
            ]);
            throw new \RuntimeException('Failed to send URL to gallery-dl web server');
        }

        $this->logger->info('Successfully sent URL to gallery-dl web server', ['url' => (string)$uri]);
        return true;
    }
}
