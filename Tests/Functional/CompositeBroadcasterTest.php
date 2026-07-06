<?php

declare(strict_types=1);

namespace Wazum\ContentLiveReload\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Wazum\ContentLiveReload\Broadcast\DatabaseBroadcastLog;
use Wazum\ContentLiveReload\Broadcaster\BroadcastLogWriter;
use Wazum\ContentLiveReload\Broadcaster\CompositeBroadcaster;
use Wazum\ContentLiveReload\Broadcaster\ViteDevServerBroadcaster;
use Wazum\ContentLiveReload\Configuration\ExtensionSettings;
use Wazum\ContentLiveReload\Tests\Support\SwitchesApplicationContext;

final class CompositeBroadcasterTest extends FunctionalTestCase
{
    use SwitchesApplicationContext;

    protected array $coreExtensionsToLoad = ['typo3/cms-adminpanel'];

    protected array $testExtensionsToLoad = ['wazum/typo3-content-live-reload'];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'content_live_reload' => ['activeContexts' => 'Development, Testing'],
        ],
    ];

    protected function tearDown(): void
    {
        $this->restoreApplicationContext();
        parent::tearDown();
    }

    #[Test]
    public function appendsToTheLogWithoutCallingTheViteDevServerOutsideDevelopment(): void
    {
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->expects(self::never())->method('request');
        $log = $this->log();

        $this->broadcaster($requestFactory)->broadcast('pageId_42');

        self::assertSame([['sequence' => 1, 'tags' => ['pageId_42']]], $log->since(0));
    }

    #[Test]
    public function appendsToTheLogAndBroadcastsToTheViteDevServerInDevelopment(): void
    {
        $this->switchApplicationContext('Development');
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->expects(self::once())->method('request');
        $log = $this->log();

        $this->broadcaster($requestFactory)->broadcast('pageId_42');

        self::assertSame([['sequence' => 1, 'tags' => ['pageId_42']]], $log->since(0));
    }

    private function broadcaster(RequestFactory $requestFactory): CompositeBroadcaster
    {
        $settings = $this->get(ExtensionSettings::class);

        return new CompositeBroadcaster(
            $settings,
            new BroadcastLogWriter($this->log()),
            new ViteDevServerBroadcaster($settings, $requestFactory),
        );
    }

    private function log(): DatabaseBroadcastLog
    {
        return new DatabaseBroadcastLog(
            $this->get(ConnectionPool::class),
            $this->get(ExtensionSettings::class),
        );
    }
}
