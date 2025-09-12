<?php

namespace App\Enum;

enum DownloaderTypeEnum
{
    /**
     * A web based downloader service, such as gallery-dl web server.
     *
     * Usually called with an HTTP request to the service's API using a HTTP client.
     */
    case WEB_DOWNLOADER;

    /**
     * A command line based downloader service, such as aria2c or youtube-dl.
     *
     * Usually called with a shell command using PHP's exec() or Symfony's Process component.
     */
    case CLI_DOWNLOADER;
}
