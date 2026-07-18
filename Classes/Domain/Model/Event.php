<?php
namespace Saalevent\Domain\Model;

class Event
{
    protected string $summary = '';
    protected string $location = '';
    protected ?\DateTimeImmutable $start = null;
    protected ?\DateTimeImmutable $end = null;
    protected string $message = '';
    protected bool $error = false;

    public static function createFromData(
        string $summary,
        string $location,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
        string $message = ''
    ): self {
        $event = new self();
        $event->summary = $summary;
        $event->location = $location;
        $event->start = $start;
        $event->end = $end;
        $event->message = $message !== '' ? $message : $summary;
        return $event;
    }

    public static function createError(string $message): self
    {
        $event = new self();
        $event->message = $message;
        $event->error = true;
        return $event;
    }

    public static function createFreeTraining(): self
    {
        $event = new self();
        $event->message = 'Saal ist aktuell zum freien Training verfügbar';
        return $event;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getStart(): ?\DateTimeImmutable
    {
        return $this->start;
    }

    public function getEnd(): ?\DateTimeImmutable
    {
        return $this->end;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function isError(): bool
    {
        return $this->error;
    }
}
