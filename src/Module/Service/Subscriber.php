<?php

namespace inisire\Xiaomi\Module\Service;

use inisire\NetBus\Event\EventInterface;
use inisire\NetBus\Event\EventSubscriber;
use inisire\NetBus\Event\Subscription\Matches;
use inisire\NetBus\Event\SubscriptionInterface;

class Subscriber implements EventSubscriber
{
    /**
     * @param array<SubscriptionInterface> $subscriptions
     */
    public function __construct(
        private readonly array $subscriptions
    )
    {
    }

    public function getEventSubscriptions(): array
    {
        return $this->subscriptions;
    }
}