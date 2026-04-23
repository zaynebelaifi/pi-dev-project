<?php

namespace App\Form\EventSubscriber;

use App\Entity\FoodDonationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class FoodDonationEventStatusSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
        ];
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        if (!is_array($data)) {
            return;
        }

        $submittedStatus = strtolower(trim((string) ($data['status'] ?? '')));
        if ($submittedStatus === strtolower(FoodDonationEvent::STATUS_CANCELLED)) {
            $data['status'] = FoodDonationEvent::STATUS_CANCELLED;
            $event->setData($data);

            return;
        }

        $eventAt = $this->parseEventDate($data['event_date'] ?? null);
        if (!$eventAt instanceof \DateTimeImmutable) {
            return;
        }

        $data['status'] = $this->calculateEventStatus($eventAt);
        $event->setData($data);
    }

    private function parseEventDate(mixed $value): ?\DateTimeImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        if (!is_string($value)) {
            return null;
        }

        $raw = trim($value);
        if ($raw == '') {
            return null;
        }

        $timezone = new \DateTimeZone(date_default_timezone_get());
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $raw, $timezone);
        if ($parsed instanceof \DateTimeImmutable) {
            return $parsed;
        }

        try {
            return new \DateTimeImmutable($raw, $timezone);
        } catch (\Exception) {
            return null;
        }
    }

    private function calculateEventStatus(\DateTimeImmutable $eventAt): string
    {
        $now = new \DateTimeImmutable('now', $eventAt->getTimezone());
        $todayKey = $now->format('Y-m-d');
        $eventDayKey = $eventAt->format('Y-m-d');

        if ($now < $eventAt) {
            if ($eventDayKey === $todayKey) {
                return FoodDonationEvent::STATUS_IN_PROGRESS;
            }

            return FoodDonationEvent::STATUS_SCHEDULED;
        }

        $eventEnd = $eventAt->modify('+2 hours');
        if ($now < $eventEnd) {
            return FoodDonationEvent::STATUS_ONGOING;
        }

        return FoodDonationEvent::STATUS_COMPLETED;
    }
}
