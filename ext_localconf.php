<?php

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    extensionName: 'saalevent',
    pluginName: 'Saalevent',
    controllerActions: [
        \Saalevent\Controller\CalendarController::class => 'list',
    ],
    pluginType: \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);