<?php

namespace App\Controller;

use App\Entity\DownloadedFile;
use App\Entity\DownloadJob;
use App\Repository\DownloadedFileRepository;
use App\Repository\DownloadJobRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class DownloadController extends AbstractController
{
    public function __construct(
        private DownloadJobRepository $downloadJobRepository,
        private DownloadedFileRepository $downloadedFileRepository
    )
    {
    }

    #[Route(
        '/downloads/{downloadJobUuid}/{token}/files/{fileId}',
        name: 'app_download',
        requirements: ['downloadJobUuid' => '[0-9a-fA-F\-]{36}', 'token' => '[0-9a-fA-F]{32}', 'fileId' => '\d+'],
        methods: ['GET']
    )]
    public function index(
        string $downloadJobUuid,
        string $token,
        int $fileId
    ): JsonResponse
    {
        $downloadJob = $this->downloadJobRepository->findOneByUuidAndToken($downloadJobUuid, $token);
        if (!$downloadJob) {
            throw new NotFoundHttpException('Download job not found or invalid token.');
        }

        $file = $downloadJob->getFiles()->filter(fn(DownloadedFile $f) => $f->getId() === $fileId)->first();
        if (!$file) {
            throw new NotFoundHttpException('File not found in this download job.');
        }

        dd($file);

        return $this->file(
            $file->getPath(),
            basename($file->getPath() ?: 'downloaded_file'),
            200,
            ['Content-Type' => 'application/octet-stream']
        );
    }
}
