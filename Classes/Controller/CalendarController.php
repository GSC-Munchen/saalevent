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
                'title' => $hall['title'],
                'image' => $hall['image'],
                'event' => $this->icsService->getEventForHall(
                    $hall['url'],
                    $hall['index'],
                    $enableCache
                ),
            ];
        }

        $this->view->assign('hallEvents', $hallEvents);

        return $this->htmlResponse();
    }
}
