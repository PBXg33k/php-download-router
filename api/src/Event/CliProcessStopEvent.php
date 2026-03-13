<?php

namespace App\Event;

use App\Entity\DownloadJob;
use Symfony\Component\Process\Process;

class CliProcessStopEvent extends ProcessStopEvent
{
    public function __construct(
        DownloadJob $downloadJob,
        bool $wasSuccessful,
        public private(set) readonly Process $process,
        public private(set) readonly int $exitCode,
        public private(set) readonly string $exitCodeText,
        ?string $errorOutput = null,
    ) {
        parent::__construct($downloadJob, $wasSuccessful, $errorOutput);
    }
}
