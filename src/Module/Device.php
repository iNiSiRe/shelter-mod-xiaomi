<?php

namespace inisire\Xiaomi\Module;

use inisire\NetBus\Event\EventBusInterface;
use inisire\NetBus\Event\EventInterface;
use inisire\NetBus\Event\SubscriptionInterface;
use inisire\NetBus\Query\QueryInterface;
use inisire\NetBus\Query\Result;
use inisire\NetBus\Query\ResultInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shelter\Bus\State;
use Shelter\Bus\MutableState;


abstract class Device implements LoggerAwareInterface
{
    protected LoggerInterface $logger;

    protected State $properties;

    public function __construct(
        private readonly string            $id,
        private readonly string            $model,
        private readonly string            $discoverModel,
        private readonly array             $parameters,
        private readonly EventBusInterface $eventBus
    )
    {
        $this->logger = new NullLogger();
        $this->properties = new MutableState([]);

        $this->load();
    }

    abstract function load(): void;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getParameter(string $name): mixed
    {
        $value = $this->parameters[$name] ?? null;

        if (!$value) {
            $this->logger->error('Parameter not exists', ['name' => $name]);
        }

        return $value;
    }

    public function getProperties(): array
    {
        return $this->properties->all();
    }

    public function dispatch(EventInterface $event): void
    {
        $this->eventBus->dispatch($this->getId(), $event);
    }

    public function subscribe(SubscriptionInterface $subscriber)
    {
        $this->eventBus->subscribe($subscriber);
    }

    public function onQuery(QueryInterface $query): ResultInterface
    {
        return new Result(-1, ['error' => 'No query handler']);
    }

    public function getDiscoverModel(): string
    {
        return $this->discoverModel;
    }
}