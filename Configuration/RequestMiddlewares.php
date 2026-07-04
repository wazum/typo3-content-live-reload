<?php

declare(strict_types=1);

use Wazum\ContentLiveReload\Middleware\TagInjectionMiddleware;

return [
    'frontend' => [
        'wazum/content-live-reload/tag-injection' => [
            'target' => TagInjectionMiddleware::class,
            'after' => [
                'typo3/cms-frontend/csp-headers',
                'typo3/cms-frontend/content-length-headers',
            ],
            'before' => [
                'typo3/cms-core/response-propagation',
            ],
        ],
    ],
];
