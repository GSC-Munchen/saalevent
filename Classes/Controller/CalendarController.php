<?php

namespace Saalevent\Controller;

use Saalevent\Service\IcsService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class CalendarController extends ActionController
{
    protected IcsService $icsService;

    public function __construct(IcsService $icsService)
    {
        $this->icsService = $icsService;
    }

    public function listAction(): ResponseInterface
    {
        // $this->settings is guaranteed to be an array by ActionController,
        // but individual keys may be absent if not configured via TypoScript/Flexform.
        $halls = [
            [
                'title' => 'Saal 1',
                'url' => $this->settings['icsUrlSaal1'] ?? '',
                'image' => 'Images/Saal1.jpg',
                'index' => 1,
            ],
            [
                'title' => 'Saal 2',
                'url' => $this->settings['icsUrlSaal2'] ?? '',
                'image' => 'Images/Saal2.jpg',
                'index' => 2,
            ],
            [
                'title' => 'Saal 3',
                'url' => $this->settings['icsUrlSaal3'] ?? '',
                'image' => 'Images/Saal3.jpg',
                'index' => 3,
            ],
        ];

        $enableCache = (bool)($this->settings['enableCache'] ?? false);

        $hallEvents = [];
        foreach ($halls as $hall) {
            $hallEvents[] = [
                'title' => (string)$hall['title'],
                'image' => (string)$hall['image'],
                'event' => $this->icsService->getEventForHall(
                    (string)$hall['url'],
                    (int)$hall['index'],
                    $enableCache
                ),
            ];
        }

        $this->view->assign('hallEvents', $hallEvents);

        return $this->htmlResponse();
    }
}
