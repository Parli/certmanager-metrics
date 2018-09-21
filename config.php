<?php
declare(strict_types=1);

use function DI\autowire;
use Firehed\API;
use Firehed\CertStatus\Endpoints;
use Kubernetes\Api as KubeAPI;
use Psr\Http\Message\ResponseInterface;

$builder = new DI\ContainerBuilder();
$env = getenv('ENVIRONMENT');
$compile = ($env && $env !== 'development');
if ($compile) {
    $builder->enableCompilation('.');
}
$builder->useAnnotations(false);
$builder->useAutowiring(true);

$defs = [
    API\Errors\HandlerInterface::class => function ($c) {
        return new class implements API\Errors\HandlerInterface
        {
            function handle($req, $err): ResponseInterface
            {
                return new Zend\Diactoros\Response\TextResponse((string)$err, 500);
            }
        };
    },

    KubeAPI::class => function ($c) {
        if (getenv('KUBERNETES_SERVICE_HOST')) {
            return new Kubernetes\ServiceAccount();
        } else {
            return new Kubernetes\LocalProxy('http://localhost:8001');
        }
    },

    Endpoints\Healthz::class => autowire(),
    Endpoints\Metrics::class => autowire(),
];

$builder->addDefinitions($defs);

return $builder->build();
