<?php
defined('TYPO3') or die();

$EM_CONF[$_EXTKEY] = [
    'title' => 'GSC Saal Event',
    'description' => 'Extension for Saal 1-3 event display from configurable .ics feeds.',
    'category' => 'plugin',
    'author' => 'GSC',
    'author_email' => '',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.0-14.99.99',
            'extbase' => '',
            'fluid' => '',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
