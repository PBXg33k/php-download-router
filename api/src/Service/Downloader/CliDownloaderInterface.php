<?php

namespace App\Service\Downloader;

interface CliDownloaderInterface extends DownloaderInterface
{
    public function getUpdateCommandArgs(): array;
}
