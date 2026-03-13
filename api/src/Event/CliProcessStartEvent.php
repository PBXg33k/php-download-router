<?php

namespace App\Event;

use App\Entity\DownloadJob;
use Symfony\Component\Process\Process;

class CliProcessStartEvent extends ProcessStartEvent
{
    public function __construct(
        public private(set) readonly string $command,
        DownloadJob $downloadJob,
        public private(set) readonly Process $process,
    ) {
        parent::__construct($downloadJob);
    }
}
