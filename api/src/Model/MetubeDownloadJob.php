<?php

namespace App\Model;

/**
 * A download job for ingesting jobs send by the Metube browser extension.
 *
 * This model is converted to a DownloadJob entity in the MetubeDownloadJobProcessor.
 */
class MetubeDownloadJob
{
    public bool $auto_start;
    public string $url;
    public string $format;
    public string $quality;
}
