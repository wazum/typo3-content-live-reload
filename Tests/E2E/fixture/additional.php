<?php

$GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '.*';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['frontend.cache.autoTagging'] = true;
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['content_live_reload'] = [
    'activeContexts' => 'Development',
    'reloadMode' => 'tagged',
    'viteServerInternalUrl' => 'http://127.0.0.1:5273',
    'viteServerPublicUrl' => '',
];
