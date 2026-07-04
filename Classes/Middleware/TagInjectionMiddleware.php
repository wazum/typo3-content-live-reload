<?php

declare(strict_types=1);

namespace Wazum\ContentLiveReload\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;
use TYPO3\CMS\Core\Cache\CacheDataCollectorInterface;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Middleware\PolicyBag;
use TYPO3\CMS\Frontend\Page\PageInformation;
use Wazum\ContentLiveReload\Configuration\ExtensionSettings;
use Wazum\ContentLiveReload\Resolver\DevServerUrlResolver;

final class TagInjectionMiddleware implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly ExtensionSettings $settings,
        private readonly DevServerUrlResolver $devServerUrlResolver,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if (!$this->settings->contextAllowed()) {
            return $response;
        }

        try {
            return $this->inject($request, $response);
        } catch (Throwable $exception) {
            $this->logger?->warning('Content live reload injection failed', ['exception' => $exception]);

            return $response;
        }
    }

    private function inject(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!str_contains($response->getHeaderLine('Content-Type'), 'text/html')) {
            return $response;
        }

        $devServerUrl = $this->devServerUrlResolver->resolve($request);
        if ($devServerUrl === null) {
            return $response;
        }

        $html = (string)$response->getBody();
        $insertPosition = $this->insertPosition($html);
        if ($insertPosition === null) {
            return $response;
        }

        $snippet = $this->snippet($request, $devServerUrl, $html);
        $this->declareNonceUsage($request);
        $body = new Stream('php://temp', 'rw');
        $body->write(substr($html, 0, $insertPosition) . $snippet . substr($html, $insertPosition));

        return $response->withBody($body);
    }

    private function declareNonceUsage(ServerRequestInterface $request): void
    {
        if ($request->getAttribute('nonce') === null) {
            return;
        }

        $policyBag = $request->getAttribute('csp.policyBag');
        if ($policyBag instanceof PolicyBag) {
            $policyBag->behavior->useNonce = true;
        }
    }

    private function insertPosition(string $html): ?int
    {
        $headEnd = stripos($html, '</head>');
        if ($headEnd !== false) {
            return $headEnd;
        }
        $bodyEnd = stripos($html, '</body>');

        return $bodyEnd === false ? null : $bodyEnd;
    }

    private function snippet(ServerRequestInterface $request, string $devServerUrl, string $html): string
    {
        $configuration = json_encode(
            ['tags' => $this->tags($request), 'mode' => $this->mode($request)],
            JSON_THROW_ON_ERROR | JSON_HEX_TAG,
        );
        $nonceValue = $this->nonceValue($request);
        $nonceAttribute = $nonceValue === null ? '' : ' nonce="' . htmlspecialchars($nonceValue) . '"';
        $moduleUrl = htmlspecialchars($devServerUrl . '/@id/virtual:content-live-reload');

        $snippet = '<script' . $nonceAttribute . '>window.__contentLiveReload = ' . $configuration . '</script>'
            . '<script type="module" src="' . $moduleUrl . '"' . $nonceAttribute . '></script>';
        if ($nonceValue !== null && !str_contains($html, 'property="csp-nonce"')) {
            $snippet = '<meta property="csp-nonce" nonce="' . htmlspecialchars($nonceValue) . '">' . $snippet;
        }

        return $snippet;
    }

    private function mode(ServerRequestInterface $request): string
    {
        $override = $request->getAttribute('content_live_reload.mode');

        return match ($override) {
            'tagged', 'always', 'paused' => $override,
            default => $this->settings->reloadMode(),
        };
    }

    /**
     * @return array<string>
     */
    private function tags(ServerRequestInterface $request): array
    {
        $tags = [];
        $collector = $request->getAttribute('frontend.cache.collector');
        if ($collector instanceof CacheDataCollectorInterface) {
            foreach ($collector->getCacheTags() as $cacheTag) {
                $tags[$cacheTag->name] = true;
            }
        }

        $pageInformation = $request->getAttribute('frontend.page.information');
        if ($pageInformation instanceof PageInformation) {
            $tags['pageId_' . $pageInformation->getId()] = true;
        }

        return array_keys($tags);
    }

    private function nonceValue(ServerRequestInterface $request): ?string
    {
        $nonce = $request->getAttribute('nonce');

        return $nonce instanceof ConsumableNonce ? $nonce->consume() : null;
    }
}
