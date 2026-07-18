<?php

namespace Saalevent\Utility;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfigUtility
{
    public static function getSettings(): array
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        
        try {
            $settings = (array)$extensionConfiguration->get('saalevent');
        } catch (\Exception $e) {
            $settings = [];
        }

        $defaults = [
            'icsUrlSaal1' => '',
            'icsUrlSaal2' => '',
            'icsUrlSaal3' => '',
            'templateRootPath' => 'EXT:saalevent/Resources/Private/Templates/',
            'partialRootPath' => 'EXT:saalevent/Resources/Private/Partials/',
            'layoutRootPath' => 'EXT:saalevent/Resources/Private/Layouts/',
            'enableCache' => 1,
        ];

        // Entfernt leere Eingaben aus dem Backend, damit das Fallback greift
        $settings = array_filter($settings, static function($value) {
            return $value !== null && trim((string)$value) !== '';
        });

        return array_merge($defaults, $settings);
    }
}