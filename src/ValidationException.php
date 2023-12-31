<?php

namespace Guirong\Laravel\Validate;

use RuntimeException;

class ValidationException extends RuntimeException
{
    private $statusCode;

    private $headers;

    public function __construct($message = null, $statusCode = 500, \Exception $previous = null, array $headers = [], $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->headers    = $headers;

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getHeaders()
    {
        return $this->headers;
    }
}
