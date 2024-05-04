<?php

declare(strict_types=1);

namespace Nektria\Document;

use DomainException;
use InvalidArgumentException;
use Nektria\Exception\InsufficientCredentialsException;
use Nektria\Exception\InvalidAuthorizationException;
use Nektria\Exception\InvalidRequestParamException;
use Nektria\Exception\MissingFieldRequiredToCreateClassException;
use Nektria\Exception\MissingRequestParamException;
use Nektria\Exception\NektriaException;
use Nektria\Exception\ResourceNotFoundException;
use Nektria\Service\ContextService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ThrowableDocument implements Document
{
    public readonly int $status;

    public readonly Throwable $throwable;

    public function __construct(
        Throwable $throwable
    ) {
        $exception = $throwable;
        if ($exception instanceof NektriaException) {
            $exception = $exception->realException();
        }

        if ($exception instanceof DomainException) {
            $this->status = Response::HTTP_CONFLICT;
        } elseif ($exception instanceof InvalidArgumentException) {
            $this->status = Response::HTTP_BAD_REQUEST;
        } elseif ($exception instanceof InsufficientCredentialsException) {
            $this->status = Response::HTTP_FORBIDDEN;
        } elseif ($exception instanceof InvalidAuthorizationException) {
            $this->status = Response::HTTP_UNAUTHORIZED;
        } elseif ($exception instanceof InvalidRequestParamException) {
            $this->status = Response::HTTP_BAD_REQUEST;
        } elseif ($exception instanceof MissingRequestParamException) {
            $this->status = Response::HTTP_BAD_REQUEST;
        } elseif ($exception instanceof MissingFieldRequiredToCreateClassException) {
            $this->status = Response::HTTP_BAD_REQUEST;
        } elseif ($exception instanceof ResourceNotFoundException) {
            $this->status = Response::HTTP_NOT_FOUND;
        } elseif ($exception instanceof HttpException) {
            $this->status = $exception->getStatusCode();
        } else {
            $this->status = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        $this->throwable = $throwable;
    }

    /**
     * @return array{
     *     file: string,
     *     line: int
     * }[]
     */
    public function trace(): array
    {
        $trace = $this->throwable->getTrace();
        $finalTrace = [];
        foreach ($trace as $item) {
            $file = $item['file'] ?? '';
            $line = $item['line'] ?? 0;
            if (str_starts_with($file, '/app/src')) {
                $finalTrace[] = [
                    'file' => str_replace('/app/', '', $file),
                    'line' => $line,
                ];
            }

            if (str_starts_with($file, '/app/vendor/nektria/php-sdk/src')) {
                $finalTrace[] = [
                    'file' => str_replace('/app/vendor/nektria/php-sdk/', '', $file),
                    'line' => $line,
                ];
            }
        }

        return $finalTrace;
    }

    public function toArray(ContextService $context): mixed
    {
        $exception = $this->throwable;
        if ($exception instanceof NektriaException) {
            $exception = $exception->realException();
        }

        $message = 'Internal Server Error';
        if ($context->isPlayEnvironment()) {
            $message = $exception->getMessage();
        }

        if ($this->status !== Response::HTTP_INTERNAL_SERVER_ERROR) {
            $message = $exception->getMessage();
        }

        $data = [
            'message' => $message
        ];

        if ($context->isPlayEnvironment()) {
            $data['file'] = str_replace('/app/', '', $exception->getFile());
            $data['line'] = $exception->getLine();
            $data['trace'] = $this->trace();
        }

        return $data;
    }

    public function toDevArray(): mixed
    {
        $exception = $this->throwable;
        if ($exception instanceof NektriaException) {
            $exception = $exception->realException();
        }

        $message = $exception->getMessage();

        if ($this->status !== Response::HTTP_INTERNAL_SERVER_ERROR) {
            $message = $exception->getMessage();
        }

        $data = [
            'message' => $message
        ];

        $data['file'] = str_replace('/app/', '', $exception->getFile());
        $data['line'] = $exception->getLine();
        $data['trace'] = $this->trace();

        return $data;
    }
}
