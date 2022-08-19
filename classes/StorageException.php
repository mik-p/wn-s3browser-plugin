<?php

namespace mikp\s3browser\Classes;

use Exception;

class StorageException extends Exception
{
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
