<?php

namespace App\Event;

use App\Entity\DownloadJob;
use Symfony\Component\Process\Process;
use Symfony\Contracts\EventDispatcher\Event;

class CliProcessOutputEvent extends Event
{
    public function __construct(
        private(set) readonly string       $output,
        private(set) readonly ?DownloadJob $downloadJob,
        private(set) readonly Process      $process,
        private(set) readonly bool         $isError = false
    )
    {
    }

    public function hasDownloadJobEvent(): bool
    {
        return null !== $this->downloadJob;
    }
}
