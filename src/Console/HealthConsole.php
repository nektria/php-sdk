<?php

declare(strict_types=1);

namespace Nektria\Console;

use Nektria\Service\HealthService;
use RuntimeException;

use function count;

class HealthConsole extends Console
{
    public function __construct(
        private readonly HealthService $healthService,
    ) {
        parent::__construct('sdk:health');
    }

    protected function play(): void
    {
        $data = $this->healthService->check();
        ksort($data['results']);

        foreach ($data['results'] as $key => $result) {
            if ($result) {
                $this->output()->writeln(" - {$key}: <green>OK</green>");
            } else {
                $this->output()->writeln(" - {$key}: <red>FAIL</red>");
            }
        }

        if (count($data['errors']) > 0) {
            throw new RuntimeException('health failed');
        }
    }
}
