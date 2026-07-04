<?php

declare(strict_types=1);

namespace Wazum\E2eFixture\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class SeedCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Bootstrap::initializeBackendAuthentication();

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'pages' => [
                'NEW_page' => ['pid' => 1, 'title' => 'Other', 'slug' => '/other', 'doktype' => 1, 'hidden' => 0],
            ],
            'tt_content' => [
                'NEW_content_home' => ['pid' => 1, 'CType' => 'text', 'header' => 'Home content', 'colPos' => 0],
                'NEW_content_other' => ['pid' => 'NEW_page', 'CType' => 'text', 'header' => 'Other content', 'colPos' => 0],
            ],
        ], []);
        $dataHandler->process_datamap();

        $output->writeln(json_encode([
            'otherPageUid' => (int)$dataHandler->substNEWwithIDs['NEW_page'],
            'homeContentUid' => (int)$dataHandler->substNEWwithIDs['NEW_content_home'],
            'otherContentUid' => (int)$dataHandler->substNEWwithIDs['NEW_content_other'],
        ], JSON_THROW_ON_ERROR));

        return Command::SUCCESS;
    }
}
