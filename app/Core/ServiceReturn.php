<?php

namespace App\Core;

readonly class ServiceReturn
{
    public function __construct(
        private bool             $success,
        private string           $message,
        private mixed            $data = null,
        private null| \Throwable $error = null
    )
    {

    }
    public static function success(mixed $data = null, string $message = 'Success'): self
    {
        return new self(true, $message, $data);
    }

    public static function error(string $message = 'Error', \Throwable| null $exception = null, mixed $data = null): self
    {
        return new self(false, $message, $data, $exception);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
    public function isError(): bool
    {
        return !$this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
    public function getException(): ?\Throwable
    {
        return $this->error;
    }
    public function getData()
    {
        return $this->data;
    }
}
