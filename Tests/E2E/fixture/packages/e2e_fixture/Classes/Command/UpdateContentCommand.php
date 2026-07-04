<?php

declare(strict_types=1);

namespace Wazum\E2eFixture\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class UpdateContentCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('uid', InputArgument::REQUIRED);
        $this->addArgument('header', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Bootstrap::initializeBackendAuthentication();

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tt_content' => [
                (int)$input->getArgument('uid') => ['header' => (string)$input->getArgument('header')],
            ],
        ], []);
        $dataHandler->process_datamap();

        return Command::SUCCESS;
    }
}
