<?php

$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['driverOptions'][PDO::ATTR_TIMEOUT] = 30;
if (str_starts_with((string)TYPO3\CMS\Core\Core\Environment::getContext(), 'Production/Staging')) {
    $defaultDatabasePath = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['path'];
    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['path'] = dirname($defaultDatabasePath) . '/staging.sqlite';
}
$GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '.*';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['frontend.cache.autoTagging'] = true;
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['content_live_reload'] = [
    'activeContexts' => 'Development,Production/Staging',
    'reloadMode' => 'tagged',
    'viteServerInternalUrl' => 'http://127.0.0.1:5273',
    'viteServerPublicUrl' => '',
];
