<?php

declare(strict_types=1);

namespace Wazum\ContentLiveReload\Hook;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use Wazum\ContentLiveReload\Collector\BroadcastTagCollector;
use Wazum\ContentLiveReload\Configuration\ExtensionSettings;
use Wazum\ContentLiveReload\Event\ModifyBroadcastTagsEvent;

final class ClearCachePostProcHook
{
    public function __construct(
        private readonly ExtensionSettings $settings,
        private readonly BroadcastTagCollector $collector,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function postProcessClearCache(array $parameters, DataHandler $dataHandler): void
    {
        if (!$this->settings->contextAllowed()) {
            return;
        }

        $tags = $this->normalizeTags((array)($parameters['tags'] ?? []));
        if ($tags === []) {
            return;
        }

        /** @var ModifyBroadcastTagsEvent $event */
        $event = $this->eventDispatcher->dispatch(new ModifyBroadcastTagsEvent(
            (string)($parameters['table'] ?? ''),
            (int)($parameters['uid'] ?? 0),
            (int)($parameters['uid_page'] ?? 0),
            $tags,
        ));

        $this->collector->add(...$event->getTags());
    }

    /**
     * DataHandler passes a tag => true map on record saves,
     * but a plain list of tags from clear_cacheCmd().
     *
     * @param array<int|string, mixed> $rawTags
     *
     * @return array<string>
     */
    private function normalizeTags(array $rawTags): array
    {
        if (array_is_list($rawTags)) {
            return array_values(array_filter(
                $rawTags,
                static fn (mixed $tag): bool => is_string($tag) && $tag !== '',
            ));
        }

        return array_filter(array_keys(array_filter($rawTags)), is_string(...));
    }
}
