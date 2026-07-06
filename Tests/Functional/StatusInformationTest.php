<?php

declare(strict_types=1);

namespace Wazum\ContentLiveReload\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Wazum\ContentLiveReload\AdminPanel\StatusInformation;
use Wazum\ContentLiveReload\Configuration\ExtensionSettings;
use Wazum\ContentLiveReload\Middleware\PollEndpointMiddleware;
use Wazum\ContentLiveReload\Resolver\DevServerUrlResolver;
use Wazum\ContentLiveReload\Tests\Support\SwitchesApplicationContext;

final class StatusInformationTest extends FunctionalTestCase
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
    public function reportsPollTransportWithEndpointAndIntervalOutsideDevelopment(): void
    {
        $data = $this->statusInformation()->getDataToStore(new ServerRequest('https://example.org/', 'GET'));

        $stored = $data->getArrayCopy();
        self::assertSame('poll', $stored['transport']);
        self::assertSame(PollEndpointMiddleware::PATH, $stored['pollEndpoint']);
        self::assertSame(3000, $stored['pollInterval']);
    }

    #[Test]
    public function reportsViteTransportInDevelopmentContext(): void
    {
        $this->switchApplicationContext('Development');

        $data = $this->statusInformation()->getDataToStore(new ServerRequest('https://example.org/', 'GET'));

        self::assertSame('vite', $data->getArrayCopy()['transport']);
    }

    #[Test]
    public function rendersEndpointAndIntervalForThePollTransport(): void
    {
        $statusInformation = $this->statusInformation();
        $data = $statusInformation->getDataToStore(new ServerRequest('https://example.org/', 'GET'));

        $content = $statusInformation->getContent(new ModuleData($data->getArrayCopy()));

        self::assertStringContainsString('<td>poll — /__content-live-reload/poll, every 3000 ms</td>', $content);
    }

    #[Test]
    public function rendersTheViteTransportWithoutPollDetails(): void
    {
        $this->switchApplicationContext('Development');
        $statusInformation = $this->statusInformation();
        $data = $statusInformation->getDataToStore(new ServerRequest('https://example.org/', 'GET'));

        $content = $statusInformation->getContent(new ModuleData($data->getArrayCopy()));

        self::assertStringContainsString('<td>vite</td>', $content);
        self::assertStringNotContainsString('/__content-live-reload/poll', $content);
    }

    private function statusInformation(): StatusInformation
    {
        return new StatusInformation(
            $this->get(ExtensionSettings::class),
            $this->get(DevServerUrlResolver::class),
            $this->get(Features::class),
            $this->get(ViewFactoryInterface::class),
        );
    }
}
