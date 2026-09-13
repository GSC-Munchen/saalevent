<?php

namespace Saalevent\Service;

use Psr\Log\LoggerInterface;
use Saalevent\Domain\Model\Event;
use Sabre\VObject;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class IcsService
{
    protected const CACHE_TTL = 7200;
    protected const REQUEST_TIMEOUT = 5.0;

    protected RequestFactory $requestFactory;
    protected LoggerInterface $logger;

    public function __construct(RequestFactory $requestFactory)
    {
        $this->requestFactory = $requestFactory;
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(self::class);
    }

    public function getEventForHall(string $icsUrl, int $hallNumber, bool $enableCache): Event
    {
        $icsUrl = trim($icsUrl);
        if ($icsUrl === '') {
            return Event::createError('Dienst aktuell nicht verfügbar (URL nicht konfiguriert)');
        }

        $cacheFile = $this->getCacheFilePath($hallNumber);
        $content = '';

        if ($enableCache && file_exists($cacheFile) && (time() - filemtime($cacheFile)) < self::CACHE_TTL) {
            $content = file_get_contents($cacheFile) ?: '';
        } else {
            $content = $this->fetchRemoteFile($icsUrl);
            if ($content === false || $content === '') {
                return Event::createError('Dienst aktuell nicht verfügbar (Server-Error: ' . $icsUrl . ')');
            }

            if ($enableCache) {
                $this->writeCacheFileAtomically($cacheFile, $content);
            }
        }

        try {
            return $this->processIcsAndSelectEvent($content);
        } catch (VObject\ParseException|VObject\InvalidDataException $e) {
            $this->logger->warning('ICS-Daten für Saal {hall} konnten nicht geparst werden: {message}', [
                'hall' => $hallNumber,
                'message' => $e->getMessage(),
            ]);
            return Event::createError('Fehler beim Verarbeiten der Kalenderdaten');
        } catch (\Exception $e) {
            $this->logger->error('Unerwarteter Fehler bei der Verarbeitung der Kalenderdaten für Saal {hall}: {message}', [
                'hall' => $hallNumber,
                'message' => $e->getMessage(),
            ]);
            return Event::createError('Fehler beim Verarbeiten der Kalenderdaten');
        }
    }

    protected function fetchRemoteFile(string $url): string|false
    {
        if (preg_match('/^https?:\/\//i', $url) !== 1) {
            return GeneralUtility::getUrl($url);
        }

        try {
            $response = $this->requestFactory->request($url, 'GET', ['timeout' => self::REQUEST_TIMEOUT]);
            return $response->getBody()->getContents();
        } catch (\Throwable $e) {
            $this->logger->warning('Abruf der ICS-Datei fehlgeschlagen: {url} ({message})', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function processIcsAndSelectEvent(string $content): Event
    {
        $vcal = VObject\Reader::read($content);
        $now = new \DateTime('now', new \DateTimeZone('Europe/Berlin'));
        $limit = (clone $now)->modify('+24 hours');

        // Expandiert alle wiederkehrenden Events (RRULE) im relevanten Zeitraum
        $vcal = $vcal->expand($now, $limit);

        if (!isset($vcal->VEVENT) || count($vcal->VEVENT) === 0) {
            return Event::createError('Keine Ereignisse gefunden');
        }

        $nowImmutable = \DateTimeImmutable::createFromMutable($now);
        $limitImmutable = \DateTimeImmutable::createFromMutable($limit);

        $occurrences = $this->selectRelevantOccurrences($vcal->VEVENT, $nowImmutable, $limitImmutable);
        $selected = $occurrences['current'] ?? $occurrences['next'];

        if ($selected === null) {
            return Event::createFreeTraining();
        }

        $message = $occurrences['current'] !== null
            ? $selected['summary']
            : 'Demnächst: ' . $selected['summary'];

        return Event::createFromData(
            $selected['summary'],
            $selected['location'],
            $selected['start'],
            $selected['end'],
            $message
        );
    }

    /**
     * @return array{current: ?array, next: ?array}
     */
    protected function selectRelevantOccurrences(
        iterable $vevents,
        \DateTimeImmutable $now,
        \DateTimeImmutable $limit
    ): array {
        $currentEvent = null;
        $nextEvent = null;

        foreach ($vevents as $event) {
            $occurrence = $this->extractOccurrence($event);

            // Prüfen, ob das Event JETZT stattfindet
            if ($occurrence['start'] <= $now && $occurrence['end'] >= $now) {
                $currentEvent = $occurrence;
                break; // Aktuelles Event hat Vorrang
            }

            // Prüfen, ob es das nächste anstehende Event ist
            if ($occurrence['start'] > $now && $occurrence['start'] <= $limit) {
                if ($nextEvent === null || $occurrence['start'] < $nextEvent['start']) {
                    $nextEvent = $occurrence;
                }
            }
        }

        return ['current' => $currentEvent, 'next' => $nextEvent];
    }

    /**
     * @return array{summary: string, location: string, start: \DateTimeImmutable, end: \DateTimeImmutable}
     */
    protected function extractOccurrence(VObject\Component\VEvent $event): array
    {
        // Zeitzone auf lokale Zeitzone des Servers/TYPO3 zwingen
        $localTz = new \DateTimeZone('Europe/Berlin');

        return [
            'summary' => (string)$event->SUMMARY,
            'location' => (string)$event->LOCATION,
            'start' => $this->toLocalImmutable($event->DTSTART->getDateTime(), $localTz),
            'end' => $this->toLocalImmutable($event->DTEND->getDateTime(), $localTz),
        ];
    }

    protected function toLocalImmutable(\DateTimeInterface $dateTime, \DateTimeZone $timezone): \DateTimeImmutable
    {
        $immutable = $dateTime instanceof \DateTimeImmutable
            ? $dateTime
            : \DateTimeImmutable::createFromMutable($dateTime);

        return $immutable->setTimezone($timezone);
    }

    protected function getCacheFilePath(int $hallNumber): string
    {
        $cacheDir = GeneralUtility::getFileAbsFileName('typo3temp/saalevent/');
        GeneralUtility::mkdir_deep($cacheDir);

        return rtrim($cacheDir, '/') . '/saal' . $hallNumber . '.ics';
    }

    protected function writeCacheFileAtomically(string $cacheFile, string $content): void
    {
        GeneralUtility::mkdir_deep(dirname($cacheFile));

        // Über eine temporäre Datei + rename schreiben, um Race Conditions mit
        // parallel lesenden Requests zu vermeiden (rename ist auf POSIX-Systemen atomar).
        $tmpFile = $cacheFile . '.' . uniqid('', true) . '.tmp';
        if (file_put_contents($tmpFile, $content) !== false) {
            rename($tmpFile, $cacheFile);
        }
    }
}
