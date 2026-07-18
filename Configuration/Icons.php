<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    // GSC Icon
    'tx-saalevent-svgicon' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:saalevent/Resources/Public/Icons/Extension.svg',
    ],
];
