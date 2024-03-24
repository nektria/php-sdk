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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ThrowableDocument implements Document
{
    public int $status;

    public function __construct(
        public readonly Throwable $throwable
    ) {
        $exception = $this->throwable;
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
    }

    public function toArray(string $model): mixed
    {
        $exception = $this->throwable;
        if ($exception instanceof NektriaException) {
            $exception = $exception->realException();
        }

        $message = 'Internal Server Error';
        if ($model === 'dev' || $model === 'test' || $model === 'qa') {
            $message = $exception->getMessage();
        }

        if ($this->status !== Response::HTTP_INTERNAL_SERVER_ERROR) {
            $message = $exception->getMessage();
        }

        $data = [
            'message' => $message
        ];

        if ($model === 'dev' || $model === 'test' || $model === 'qa') {
            $data['file'] = $exception->getFile();
            $data['line'] = $exception->getLine();
            $data['trace'] = $exception->getTrace();
        }

        return $data;
    }
}
