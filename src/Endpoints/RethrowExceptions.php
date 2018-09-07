<?php
declare(strict_types=1);

namespace Firehed\CertStatus\Endpoints;

use Psr\Http\Message\ResponseInterface;
use Throwable;

trait RethrowExceptions
{

    public function handleException(Throwable $t): ResponseInterface
    {
        throw $t;
    }
}
