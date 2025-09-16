<?php

namespace FrugalPhpPlugin\Orm\Exceptions;

use Frugal\Core\Exceptions\CustomException;
use React\Http\Message\Response;

class EntityNotFoundException extends CustomException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message, Response::STATUS_NOT_FOUND);
    }
}