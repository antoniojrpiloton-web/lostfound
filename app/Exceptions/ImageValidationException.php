<?php

namespace App\Exceptions;

use RuntimeException;

class ImageValidationException extends RuntimeException
{
    public function __construct(public array $messages)
    {
        parent::__construct($messages[0] ?? 'The uploaded image failed validation.');
    }
}
