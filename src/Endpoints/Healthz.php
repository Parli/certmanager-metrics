<?php
declare(strict_types=1);

namespace Firehed\CertStatus\Endpoints;

use Firehed\API\Interfaces\EndpointInterface;
use Firehed\API\Traits\Authentication;
use Firehed\API\Traits\Input;
use Firehed\API\Traits\Request;
use Firehed\Input\Containers\SafeInput;
use Firehed\InputObjects;
use Psr\Http\Message\ResponseInterface;

class Healthz implements EndpointInterface
{
    use Authentication\None;
    use Input\NoOptional;
    use Input\NoRequired;
    use Request\Get;
    use RethrowExceptions;

    public function getUri(): string
    {
        return '/healthz';
    }

    public function execute(SafeInput $input): ResponseInterface
    {
        return new \Zend\Diactoros\Response\TextResponse('OK');
    }
}
