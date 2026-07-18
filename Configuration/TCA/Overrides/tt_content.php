<?php

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    extensionName: 'saalevent',
    pluginName: 'Saalevent',
    pluginTitle: 'GSC Saal Event',
    pluginIcon: 'tx-saalevent-svgicon',
    group: 'plugins',
    pluginDescription: 'GSC Saal Event des GSC München e.V.'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    table: 'tt_content',
    newFieldsString: 'pages',
    typeList: 'saalevent_saalevent',
    position: 'after:subheader'  
);