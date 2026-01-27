<?php

namespace Duli\WhatsApp\Exceptions;

use Exception;

class WhatsAppException extends Exception
{
    protected array $response;

    public function __construct(string $message, int $code = 0, array $response = [])
    {
        parent::__construct($message, $code);
        $this->response = $response;
    }

    public function getResponse(): array
    {
        return $this->response;
    }

    public function getErrorCode(): ?int
    {
        return $this->response['error']['code'] ?? null;
    }

    public function getErrorSubcode(): ?int
    {
        return $this->response['error']['error_subcode'] ?? null;
    }

    public function getErrorType(): ?string
    {
        return $this->response['error']['type'] ?? null;
    }
}
