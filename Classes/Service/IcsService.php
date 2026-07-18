<?php

namespace Saalevent\Service;

use Saalevent\Domain\Model\Event;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class IcsService
{
    protected const CACHE_TTL = 7200;

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
                GeneralUtility::mkdir_deep(dirname($cacheFile));
                file_put_contents($cacheFile, $content);
            }
        }

        $events = $this->parseIcs($content);
        if (count($events) === 0) {
            return Event::createError('Keine Ereignisse gefunden');
        }

        $nextEvent = $this->selectNextEvent($events);
        if ($nextEvent === null) {
            return Event::createFreeTraining();
        }

        return $nextEvent;
    }

    protected function fetchRemoteFile(string $url)
    {
        return GeneralUtility::getUrl($url);
    }

    protected function parseIcs(string $content): array
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $blocks = explode("BEGIN:VEVENT", $content);
        $events = [];

        foreach ($blocks as $block) {
            if (strpos($block, 'END:VEVENT') === false) {
                continue;
            }

            $lines = explode("\n", trim($block));
            $eventData = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if (strpos($line, 'SUMMARY:') === 0) {
                    $eventData['summary'] = substr($line, 8);
                } elseif (strpos($line, 'LOCATION:') === 0) {
                    $eventData['location'] = substr($line, 9);
                } elseif (preg_match('/^DTSTART(?:;TZID=[^:]+)?:([0-9TZ]+)$/', $line, $match)) {
                    $eventData['start'] = $this->parseIcsDate($match[1]);
                } elseif (preg_match('/^DTEND(?:;TZID=[^:]+)?:([0-9TZ]+)$/', $line, $match)) {
                    $eventData['end'] = $this->parseIcsDate($match[1]);
                }
            }

            if (!empty($eventData['summary']) && !empty($eventData['start']) && !empty($eventData['end'])) {
                $events[] = $eventData;
            }
        }

        return $events;
    }

    protected function parseIcsDate(string $value): ?\DateTimeImmutable
    {
        $value = trim($value);

        if (substr($value, -1) === 'Z') {
            $date = \DateTimeImmutable::createFromFormat('Ymd\\THis\\Z', $value, new \DateTimeZone('UTC'));
            if ($date !== false) {
                return $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            }
        }

        if (preg_match('/^(\d{8})T(\d{6})$/', $value)) {
            return \DateTimeImmutable::createFromFormat('Ymd\\THis', $value, new \DateTimeZone(date_default_timezone_get()));
        }

        if (preg_match('/^(\d{8})$/', $value)) {
            $date = \DateTimeImmutable::createFromFormat('Ymd', $value, new \DateTimeZone(date_default_timezone_get()));
            if ($date !== false) {
                return $date->setTime(0, 0);
            }
        }

        return null;
    }

    protected function selectNextEvent(array $events): ?Event
    {
        $now = new \DateTimeImmutable();
        $limit = $now->modify('+24 hours');

        $validEvents = array_filter($events, static function ($event) use ($now, $limit) {
            return isset($event['start'], $event['end'])
                && $event['start'] <= $limit
                && $event['end'] >= $now;
        });

        if (count($validEvents) === 0) {
            return null;
        }

        $currentEvent = null;
        $nextEvent = null;

        foreach ($validEvents as $event) {
            if ($event['start'] <= $now && $event['end'] >= $now) {
                $currentEvent = $event;
                break;
            }
            if ($nextEvent === null || $event['start'] < $nextEvent['start']) {
                $nextEvent = $event;
            }
        }

        $selected = $currentEvent ?? $nextEvent;
        if ($selected === null) {
            return null;
        }

        $message = $currentEvent !== null
            ? 'Aktuelles Event: ' . $selected['summary']
            : 'Nächstes Event: ' . $selected['summary'];

        return Event::createFromData(
            $selected['summary'],
            $selected['location'] ?? '',
            $selected['start'],
            $selected['end'],
            $message
        );
    }

    protected function getCacheFilePath(int $hallNumber): string
    {
        $cacheDir = GeneralUtility::getFileAbsFileName('typo3temp/saalevent/');
        GeneralUtility::mkdir_deep($cacheDir);

        return rtrim($cacheDir, '/') . '/saal' . $hallNumber . '.ics';
    }
}
