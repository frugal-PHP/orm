<?php

namespace FrugalPhpPlugin\Orm\Exceptions;

use Frugal\Core\Exceptions\BusinessException;
use React\Http\Message\Response;

class DuplicateEntityException extends BusinessException
{
    public function __construct(string $message = "Duplicate record, please adjust data")
    {
        parent::__construct($message, Response::STATUS_CONFLICT);
    }
}