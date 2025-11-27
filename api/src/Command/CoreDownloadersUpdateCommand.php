<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'core:downloaders:update',
    description: 'Updates all the downloaders used in the container without having to restart or pull images',
)]
class CoreDownloadersUpdateCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Run pip to update gallery-dl and yt-dlp[default]

        //$process = new Process('pip3 install --no-cache-dir --update gallery-dl yt-dlp[default] --break-system-packages')
        $process = new Process([
            'pip3',
            'install',
            '--no-cache-dir',
            '--upgrade',
            '--break-system-packages',
            'gallery-dl',
            '--root-user-action=ignore'
        ]);

        $process->run(function ($type, $buffer) use ($io) {
            if (Process::ERR === $type) {
                $io->error($buffer);
            }

            if (Process::OUT === $type) {
                $io->note($buffer);
            }
        });

        if($process->isSuccessful()) {
            $io->success("Upgraded all downloaders to latest available versions");
            return 0;
        }

        $io->error($process->getErrorOutput());
        return 1;

    }
}
