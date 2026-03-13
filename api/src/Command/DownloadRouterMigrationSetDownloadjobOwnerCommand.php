<?php

namespace App\Command;

use App\Entity\OidcSubjectIdentifier;
use App\Repository\DownloadJobRepository;
use App\Repository\OidcSubjectIdentifierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'download-router:migration:set-downloadjob-owner',
    description: 'Set the owner of (orphaned) download jobs after migrating to multi user setup',
)]
class DownloadRouterMigrationSetDownloadjobOwnerCommand extends Command
{
    private SymfonyStyle $io;
    public function __construct(
        private DownloadJobRepository $downloadJobRepository,
        private OidcSubjectIdentifierRepository $oidcSubjectIdentifierRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('owner-sub', InputArgument::OPTIONAL, 'The OidcSubject of the owner')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run, do not actually change anything')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ownerSub = $input->getArgument('owner-sub');
        $dryRun = $input->getOption('dry-run');


        $this->io = new SymfonyStyle($input, $output);

        if ($ownerSub) {
            $ownerSub = $this->oidcSubjectIdentifierRepository->findOneBy(['subject' => $ownerSub]);
            if (!$ownerSub) {
                throw new \RuntimeException('Owner sub not found');
            }
            $this->setOwnerOnDownloadJobs($ownerSub, $dryRun);
        } else {
            $ownerSubs = $this->oidcSubjectIdentifierRepository->findAll();

            $choices = array_map(fn($s) => $s->getSubject(), $ownerSubs);
            $ownerSub = $this->io->choice('Select owner sub', $choices);
            if (!$ownerSub) {
                throw new \RuntimeException('Owner sub not selected');
            }

            if ($selectedOwnerSub = array_filter($ownerSubs, fn($s) => $s->getSubject() === $ownerSub)) {
                $ownerSub = array_shift($selectedOwnerSub);

                $this->setOwnerOnDownloadJobs($ownerSub, $dryRun);
            }
        }

        return Command::SUCCESS;
    }

    private function setOwnerOnDownloadJobs(OidcSubjectIdentifier $owner, bool $dryRun = false) {
        $this->logger->debug('Setting owner on download jobs', ['owner' => $owner->getSubject()]);
        $downloadJobs = $this->downloadJobRepository->findBy(['owner' => null]);

        foreach ($downloadJobs as $downloadJob) {
            $this->logger->debug('Setting owner on download job', ['downloadJobId' => $downloadJob->getId(), 'owner' => $owner->getSubject()]);
            $downloadJob->setOwner($owner);
            if (!$dryRun) {
                $this->entityManager->persist($downloadJob);
            } else {
                $this->io->note(sprintf('Would set owner %s on download job %s', $owner->getSubject(), $downloadJob->getId()));
            }
        }


        if(!$dryRun) {
            $this->logger->debug('Flushing changes');
            $this->entityManager->flush();
        }
    }
}
