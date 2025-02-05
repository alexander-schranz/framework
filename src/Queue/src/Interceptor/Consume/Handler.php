<?php

declare(strict_types=1);

namespace Spiral\Queue\Interceptor\Consume;

use Spiral\Core\Container;
use Spiral\Core\CoreInterface;
use Spiral\Telemetry\TraceKind;
use Spiral\Telemetry\NullTracerFactory;
use Spiral\Telemetry\TracerFactoryInterface;

final class Handler
{
    private readonly TracerFactoryInterface $tracerFactory;

    public function __construct(
        private readonly CoreInterface $core,
        ?TracerFactoryInterface $tracerFactory = null
    ) {
        $this->tracerFactory = $tracerFactory ?? new NullTracerFactory(new Container());
    }

    public function handle(
        string $name,
        string $driver,
        string $queue,
        string $id,
        array $payload,
        array $headers = []
    ): mixed {
        $tracer = $this->tracerFactory->make($headers);

        return $tracer->trace(
            name: \sprintf('Job handling [%s:%s]', $name, $id),
            callback: fn (): mixed => $this->core->callAction($name, 'handle', [
                'driver' => $driver,
                'queue' => $queue,
                'id' => $id,
                'payload' => $payload,
                'headers' => $headers,
            ]),
            attributes: [
                'queue.driver' => $driver,
                'queue.name' => $queue,
                'queue.id' => $id,
                'queue.headers' => $headers,
            ],
            scoped: true,
            traceKind: TraceKind::CONSUMER
        );
    }
}
