<?php

declare(strict_types=1);

namespace Wazum\ContentLiveReload\Broadcaster;

use Wazum\ContentLiveReload\Broadcast\BroadcastLogInterface;

final class BroadcastLogWriter implements TagBroadcasterInterface
{
    public function __construct(
        private readonly BroadcastLogInterface $broadcastLog,
    ) {
    }

    public function broadcast(string ...$tags): void
    {
        if ($tags === []) {
            return;
        }

        $this->broadcastLog->append(array_values($tags));
    }
}
