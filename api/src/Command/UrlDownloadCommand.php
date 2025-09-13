<?php

namespace App\Command;

use App\Factory\DownloaderFactory;
use App\Service\Downloader\DownloaderInterface;
use GuzzleHttp\Psr7\Utils;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:url:download',
    description: 'Send a URL to the download server for processing',
)]
class UrlDownloadCommand extends Command
{
    /**
     * @var \App\Service\Downloader\DownloaderInterface|mixed|null
     */
    private ?DownloaderInterface $downloader = null;

    public function __construct(
        private(set) DownloaderFactory $downloaderCollection
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('url', InputArgument::REQUIRED, 'URL to download')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $url = $input->getArgument('url');

        $downloaders = $this->downloaderCollection->getDownloadersByUri(Utils::uriFor($url));

        // Just take the first one for now, later we can add a choice if multiple are found
        foreach($downloaders as $downloader) {
            $this->downloader = $downloader;
            break;
        }


        $io->info(sprintf('Downloading URL: %s', $url));
        if($this->downloader->download(Utils::uriFor($url))) {
            $io->success('URL sent to download server successfully!');
            return Command::SUCCESS;
        } else {
            $io->error('Failed to send URL to download server!');
            return Command::FAILURE;
        }
    }
}
