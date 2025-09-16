<?php

namespace FrugalPhpPlugin\Orm\Exceptions;

use Frugal\Core\Exceptions\CustomException;
use React\Http\Message\Response;

class DuplicateEntityException extends CustomException
{
    public function __construct(string $message = "Duplicate record, please adjust data")
    {
        parent::__construct($message, Response::STATUS_CONFLICT);
    }
}