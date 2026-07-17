<?php

namespace App\Exceptions;

use RuntimeException;

class GmailAccountAlreadyConnected extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This Gmail account is already connected to another workspace. Disconnect it there before connecting it here.');
    }
}
