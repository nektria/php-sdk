<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Infrastructure\ArrayDocumentReadModel;
use Nektria\Util\StringUtil;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Throwable;

class HealthService
{
    /**
     * @var array<string, string>
     */
    private array $errors;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly RequestClient $requestClient,
    ) {
        $this->errors = [];
    }

    /**
     * @return array{
     *     errors: array<string, string>,
     *     results: array<string, bool>,
     * }
     */
    public function check(): array
    {
        $this->errors = [];

        $data = array_merge(
            $this->checkCompass(),
            $this->checkDatabase(),
            $this->checkRabbit(),
            $this->checkRedis(),
            $this->checkRouteManager(),
            $this->checkYieldManager(),
            $this->extraChecks(),
        );

        return [
            'results' => $data,
            'errors' => $this->errors,
        ];
    }

    protected function addError(string $key, string $message): void
    {
        $this->errors[$key] = $message;
    }

    /**
     * @return array<string, bool>
     */
    protected function extraChecks(): array
    {
        return [];
    }

    /**
     * @return array<string, bool>
     */
    private function checkCompass(): array
    {
        $key = 'compass';
        if (!$this->container->has(CompassClient::class)) {
            return [];
        }

        try {
            /** @var CompassClient $srvc */
            $srvc = $this->container->get(CompassClient::class);
            $srvc->ping();

            return [$key => true];
        } catch (Throwable $e) {
            $this->addError($key, $e->getMessage());

            return [$key => false];
        }
    }

    /**
     * @return array<string, bool>
     */
    private function checkDatabase(): array
    {
        $key = 'database';
        if (!$this->container->has(ArrayDocumentReadModel::class)) {
            return [];
        }

        try {
            /** @var ArrayDocumentReadModel $srvc */
            $srvc = $this->container->get(ArrayDocumentReadModel::class);
            $srvc->readCustom('doctrine_migration_versions', 'version', 1);

            return [$key => true];
        } catch (Throwable $e) {
            $this->addError($key, $e->getMessage());

            return [$key => false];
        }
    }

    /**
     * @return array<string, bool>
     */
    private function checkRabbit(): array
    {
        $key = 'rabbit';
        if (!$this->container->hasParameter('rabbitDsn')) {
            return [];
        }

        /** @var string $rabbitDsn */
        $rabbitDsn = $this->container->getParameter('rabbitDsn');
        $host = str_replace(['amqp', '5672'], ['http', '15672'], $rabbitDsn);

        try {
            $this->requestClient->get("{$host}/api/queues")->json();

            return [$key => true];
        } catch (Throwable $e) {
            $this->addError($key, $e->getMessage());

            return [$key => false];
        }
    }

    /**
     * @return array<string, bool>
     */
    private function checkRedis(): array
    {
        $key = 'redis';
        if (!$this->container->has(VariableCache::class)) {
            return [];
        }

        try {
            $hash = StringUtil::uuid4();
            $value = StringUtil::uuid4();

            /** @var VariableCache $srvc */
            $srvc = $this->container->get(VariableCache::class);
            $srvc->saveString($hash, $value, 5);
            if ($srvc->readString($hash) !== $value) {
                throw new RuntimeException('Failed at read/write operations.');
            }

            return [$key => true];
        } catch (Throwable $e) {
            $this->addError($key, $e->getMessage());

            return [$key => false];
        }
    }

    /**
     * @return array<string, bool>
     */
    private function checkRouteManager(): array
    {
        $key = 'routemanager';
        if (!$this->container->has(RouteManagerClient::class)) {
            return [];
        }

        try {
            /** @var RouteManagerClient $srvc */
            $srvc = $this->container->get(RouteManagerClient::class);
            $srvc->ping();

            return [$key => true];
        } catch (Throwable $e) {
            $this->addError($key, $e->getMessage());

            return [$key => false];
        }
    }

    /**
     * @return array<string, bool>
     */
    private function checkYieldManager(): array
    {
        $key = 'yieldmanager';
        if (!$this->container->has(YieldManagerClient::class)) {
            return [];
        }

        try {
            /** @var YieldManagerClient $srvc */
            $srvc = $this->container->get(YieldManagerClient::class);
            $srvc->ping();

            return [$key => true];
        } catch (Throwable $e) {
            $this->addError($key, $e->getMessage());

            return [$key => false];
        }
    }
}
