<?php

declare(strict_types=1);

namespace Nektria\Console;

use Nektria\Document\Document;
use Nektria\Document\ThrowableDocument;
use Nektria\Infrastructure\BusInterface;
use Nektria\Infrastructure\UserServiceInterface;
use Nektria\Message\Command as CommandMessage;
use Nektria\Message\Query;
use Nektria\Service\AlertService;
use Nektria\Service\Bus;
use Nektria\Util\StringUtil;
use RuntimeException;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Throwable;

use function count;
use function in_array;

use const PHP_EOL;

abstract class Console extends BaseCommand
{
    private ?InputInterface $input;

    private ?OutputInterface $output;

    private BusInterface $bus;

    private UserServiceInterface $userService;

    private AlertService $alertService;

    private bool $lockMode = false;

    abstract protected function play(): void;

    public function inject(
        Bus $bus,
        UserServiceInterface $userService,
        AlertService $alertService,
    ): void {
        $this->bus = $bus;
        $this->userService = $userService;
        $this->alertService = $alertService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        if ($this->lockMode) {
            $this->lockScreen();
            $this->clear();
        }

        /*pcntl_signal(SIGINT, function () {
            $this->unlockScreen();

            return 0;
        });*/

        $output->getFormatter()->setStyle('red', new OutputFormatterStyle('red', null, []));
        $output->getFormatter()->setStyle('red1', new OutputFormatterStyle('red', null, ['bold']));
        $output->getFormatter()->setStyle('red2', new OutputFormatterStyle('red', null, ['dim']));
        $output->getFormatter()->setStyle('red3', new OutputFormatterStyle('red', null, ['italic']));
        $output->getFormatter()->setStyle('red4', new OutputFormatterStyle('red', null, ['underscore']));
        $output->getFormatter()->setStyle('red5', new OutputFormatterStyle('red', null, ['blink']));
        $output->getFormatter()->setStyle('red6', new OutputFormatterStyle('red', null, ['reverse']));

        $output->getFormatter()->setStyle('blue', new OutputFormatterStyle('blue', null, []));
        $output->getFormatter()->setStyle('blue1', new OutputFormatterStyle('blue', null, ['bold']));
        $output->getFormatter()->setStyle('blue2', new OutputFormatterStyle('blue', null, ['dim']));
        $output->getFormatter()->setStyle('blue3', new OutputFormatterStyle('blue', null, ['italic']));
        $output->getFormatter()->setStyle('blue4', new OutputFormatterStyle('blue', null, ['underscore']));
        $output->getFormatter()->setStyle('blue5', new OutputFormatterStyle('blue', null, ['blink']));
        $output->getFormatter()->setStyle('blue6', new OutputFormatterStyle('blue', null, ['reverse']));

        $output->getFormatter()->setStyle('green', new OutputFormatterStyle('green', null, []));
        $output->getFormatter()->setStyle('green1', new OutputFormatterStyle('green', null, ['bold']));
        $output->getFormatter()->setStyle('green2', new OutputFormatterStyle('green', null, ['dim']));
        $output->getFormatter()->setStyle('green3', new OutputFormatterStyle('green', null, ['italic']));
        $output->getFormatter()->setStyle('green4', new OutputFormatterStyle('green', null, ['underscore']));
        $output->getFormatter()->setStyle('green5', new OutputFormatterStyle('green', null, ['blink']));
        $output->getFormatter()->setStyle('green6', new OutputFormatterStyle('green', null, ['reverse']));

        $output->getFormatter()->setStyle('black', new OutputFormatterStyle('black', null, []));
        $output->getFormatter()->setStyle('black1', new OutputFormatterStyle('black', null, ['bold']));
        $output->getFormatter()->setStyle('black2', new OutputFormatterStyle('black', null, ['dim']));
        $output->getFormatter()->setStyle('black3', new OutputFormatterStyle('black', null, ['italic']));
        $output->getFormatter()->setStyle('black4', new OutputFormatterStyle('black', null, ['underscore']));
        $output->getFormatter()->setStyle('black5', new OutputFormatterStyle('black', null, ['blink']));
        $output->getFormatter()->setStyle('black6', new OutputFormatterStyle('black', null, ['reverse']));

        $output->getFormatter()->setStyle('yellow', new OutputFormatterStyle('yellow', null, []));
        $output->getFormatter()->setStyle('yellow1', new OutputFormatterStyle('yellow', null, ['bold']));
        $output->getFormatter()->setStyle('yellow2', new OutputFormatterStyle('yellow', null, ['dim']));
        $output->getFormatter()->setStyle('yellow3', new OutputFormatterStyle('yellow', null, ['italic']));
        $output->getFormatter()->setStyle('yellow4', new OutputFormatterStyle('yellow', null, ['underscore']));
        $output->getFormatter()->setStyle('yellow5', new OutputFormatterStyle('yellow', null, ['blink']));
        $output->getFormatter()->setStyle('yellow6', new OutputFormatterStyle('yellow', null, ['reverse']));

        $output->getFormatter()->setStyle('magenta', new OutputFormatterStyle('magenta', null, []));
        $output->getFormatter()->setStyle('magenta1', new OutputFormatterStyle('magenta', null, ['bold']));
        $output->getFormatter()->setStyle('magenta2', new OutputFormatterStyle('magenta', null, ['dim']));
        $output->getFormatter()->setStyle('magenta3', new OutputFormatterStyle('magenta', null, ['italic']));
        $output->getFormatter()->setStyle('magenta4', new OutputFormatterStyle('magenta', null, ['underscore']));
        $output->getFormatter()->setStyle('magenta5', new OutputFormatterStyle('magenta', null, ['blink']));
        $output->getFormatter()->setStyle('magenta6', new OutputFormatterStyle('magenta', null, ['reverse']));

        $output->getFormatter()->setStyle('cyan', new OutputFormatterStyle('cyan', null, []));
        $output->getFormatter()->setStyle('cyan1', new OutputFormatterStyle('cyan', null, ['bold']));
        $output->getFormatter()->setStyle('cyan2', new OutputFormatterStyle('cyan', null, ['dim']));
        $output->getFormatter()->setStyle('cyan3', new OutputFormatterStyle('cyan', null, ['italic']));
        $output->getFormatter()->setStyle('cyan4', new OutputFormatterStyle('cyan', null, ['underscore']));
        $output->getFormatter()->setStyle('cyan5', new OutputFormatterStyle('cyan', null, ['blink']));
        $output->getFormatter()->setStyle('cyan6', new OutputFormatterStyle('cyan', null, ['reverse']));

        $output->getFormatter()->setStyle('white', new OutputFormatterStyle('white', null, []));
        $output->getFormatter()->setStyle('white1', new OutputFormatterStyle('white', null, ['bold']));
        $output->getFormatter()->setStyle('white2', new OutputFormatterStyle('white', null, ['dim']));
        $output->getFormatter()->setStyle('white3', new OutputFormatterStyle('white', null, ['italic']));
        $output->getFormatter()->setStyle('white4', new OutputFormatterStyle('white', null, ['underscore']));
        $output->getFormatter()->setStyle('white5', new OutputFormatterStyle('white', null, ['blink']));
        $output->getFormatter()->setStyle('white6', new OutputFormatterStyle('white', null, ['reverse']));

        try {
            $this->play();
        } catch (Throwable $e) {
            $this->alertService->sendThrowable(
                $this->userService->user()?->tenant->name ?? 'none',
                'COMMAND',
                $this->getName() ?? '',
                [
                    'args' => $_SERVER['argv']
                ],
                new ThrowableDocument($e),
            );
        }

        if ($this->lockMode) {
            $this->unlockScreen();
        }

        return 0;
    }

    public function input(): InputInterface
    {
        if ($this->input === null) {
            throw new RuntimeException('play method has not been executed');
        }

        return $this->input;
    }

    public function output(): OutputInterface
    {
        if ($this->output === null) {
            throw new RuntimeException('play method has not been executed');
        }

        return $this->output;
    }

    public function readArgument(string $name): string
    {
        return $this->input()->getArgument($name);
    }

    protected function beep(): void
    {
        $this->output()->write("\007");
    }

    protected function clear(): void
    {
        if ($this->lockMode) {
            $this->output()->write("\33[H\33[2J");
        } else {
            $this->output()->write("\033\143");
        }
    }

    protected function lockScreen(): void
    {
        $this->output()->write("\033[?1049h\033[H");
    }

    protected function unlockScreen(): void
    {
        $this->output()->write("\033[?1049l");
    }

    public function setLockMode(): void
    {
        $this->lockMode = true;
    }

    public function forceEnterToContinue(): void
    {
        $this->output()->write('<white2>[ENTER to continue]</white2>');
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $realQuestion = new Question('');
        $realQuestion->setTrimmable(true);
        $helper->ask($this->input(), $this->output(), $realQuestion);
    }

    /**
     * @param string[]|null $validResponses
     * @param string[] $autocomplete
     * @param callable(string): bool|null $cb
     */
    public function ask(
        string $question,
        ?array $validResponses = null,
        ?string $default = null,
        array $autocomplete = [],
        ?callable $cb = null
    ): string {
        $pre = '';
        if (count($validResponses ?? []) > 0) {
            $group = implode(',', $validResponses);
            $pre = " [{$group}]";
            if ($default !== '') {
                $pre .= "({$default}) ";
            }
        } elseif ($default !== '' && $default !== null) {
            $pre .= "({$default}) ";
        } else {
            $pre = ' ';
        }

        $repeatQuestion = true;
        do {
            if ($repeatQuestion && $question !== '') {
                $this->output()->write($question . PHP_EOL . ' <white2>></white2>' . $pre);
            } else {
                $this->output()->write(' <white2>></white2>' . $pre);
            }

            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $realQuestion = new Question('', $default);
            $realQuestion->setAutocompleterValues($autocomplete);
            $realQuestion->setTrimmable(true);
            $response = StringUtil::trim(
                $helper->ask($this->input(), $this->output(), $realQuestion) ?? $default ?? ''
            );

            $valid = true;
            $validCb = true;

            if ($cb !== null) {
                $validCb = $cb($response);
            }
            if ($validResponses !== null) {
                $valid = in_array($response, $validResponses, true);
            }

            if ($default === null && $response === '') {
                $valid = false;
            }
            $repeatQuestion = $response === '?';
        } while (!($valid && $validCb));

        return StringUtil::trim($response);
    }

    /**
     * @param array{
     *     currentTry: int,
     *     maxTries: int,
     *     interval: int,
     * }|null $retryOptions
     */
    protected function dispatchCommand(
        CommandMessage $command,
        string $tenantId,
        ?string $transport = null,
        ?int $delayMs = null,
        ?array $retryOptions = null
    ): void {
        $bus = $this->bus;
        $this->userService->authenticateSystem($tenantId);
        $bus->dispatchCommand($command, $transport, $delayMs, $retryOptions);
        $this->userService->clearAuthentication();
    }

    /**
     * @template T of Document
     * @param Query<T> $query
     */
    protected function dispatchQuery(
        Query $query,
        string $tenantId,
    ): Document {
        $bus = $this->bus;
        $this->userService->authenticateSystem($tenantId);
        $document = $bus->dispatchQuery($query);
        $this->userService->clearAuthentication();

        return $document;
    }
}
